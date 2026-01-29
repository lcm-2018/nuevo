<?php

use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Nomina\Liquidado\Php\Clases\Detalles;

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit();
}
include '../../../config/autoloader.php';

$vigencia = $_SESSION['vigencia'];
$id_vigencia = $_SESSION['id_vigencia'];

$data = explode(',', file_get_contents("php://input"));
$id_nomina = $data[0];
$tipo_nomina = $data[1] ?? '';
$id_doc = $data[2] ?? '';
$id_doc_crp = $data[3];
$id_ctb_doc = $data[4]; // Documento contable de causación (CNOM)
$fecha = $data[5];

// Usar las clases para obtener datos de forma unificada
$Detalles = new Detalles();
$Nomina = new Nomina();

$datos = $Detalles->getAporteSocial($id_nomina);
$nomina = $Nomina->getRegistro($id_nomina);

// Obtener configuración de parafiscales y terceros API
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT `id_tercero_api`, `tipo`
            FROM `nom_parafiscales`
            ORDER BY `id_parafiscal` DESC";
    $rs = $cmd->query($sql);
    $parafiscales = $rs->fetchAll(PDO::FETCH_ASSOC);
    $parafiscales = array_column($parafiscales, 'id_tercero_api', 'tipo');

    $id_api_sena = $parafiscales['SENA'] ?? exit('No se ha configurado el parafiscal SENA');
    $id_api_icbf = $parafiscales['ICBF'] ?? exit('No se ha configurado el parafiscal ICBF');
    $id_api_comfam = $parafiscales['CAJA'] ?? exit('No se ha configurado el parafiscal CAJA');

    // Obtener rubros para la vigencia
    $sql = "SELECT
                `nom_tipo_rubro`.`id_rubro`
                , `nom_rel_rubro`.`id_tipo`
                , `nom_tipo_rubro`.`nombre`
                , `nom_rel_rubro`.`r_admin`
                , `nom_rel_rubro`.`r_operativo`
                , `nom_rel_rubro`.`id_vigencia`
            FROM
                `nom_rel_rubro`
                INNER JOIN `nom_tipo_rubro` 
                    ON (`nom_rel_rubro`.`id_tipo` = `nom_tipo_rubro`.`id_rubro`)
            WHERE (`nom_rel_rubro`.`id_vigencia` = ?)";
    $stmt = $cmd->prepare($sql);
    $stmt->execute([$id_vigencia]);
    $rubros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener IDs de terceros API para cada EPS, ARL y AFP
    $idsTercero = [];

    // Extraer IDs únicos de cada tipo
    $ids_eps = array_unique(array_column(array_filter($datos, fn($d) => $d['tipo'] == 'eps'), 'id'));
    $ids_arl = array_unique(array_column(array_filter($datos, fn($d) => $d['tipo'] == 'arl'), 'id'));
    $ids_afp = array_unique(array_column(array_filter($datos, fn($d) => $d['tipo'] == 'afp'), 'id'));

    // Obtener terceros API de EPS desde nom_terceros
    if (!empty($ids_eps)) {
        $placeholders = implode(',', array_fill(0, count($ids_eps), '?'));
        $sql = "SELECT `id_tn`, `id_tercero_api` FROM `nom_terceros` WHERE `id_tn` IN ($placeholders)";
        $stmt = $cmd->prepare($sql);
        $stmt->execute(array_values($ids_eps));
        $eps_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($eps_data as $eps) {
            $idsTercero['eps'][$eps['id_tn']] = $eps['id_tercero_api'];
        }
    }

    // Obtener terceros API de ARL desde nom_terceros
    if (!empty($ids_arl)) {
        $placeholders = implode(',', array_fill(0, count($ids_arl), '?'));
        $sql = "SELECT `id_tn`, `id_tercero_api` FROM `nom_terceros` WHERE `id_tn` IN ($placeholders)";
        $stmt = $cmd->prepare($sql);
        $stmt->execute(array_values($ids_arl));
        $arl_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($arl_data as $arl) {
            $idsTercero['arl'][$arl['id_tn']] = $arl['id_tercero_api'];
        }
    }

    // Obtener terceros API de AFP desde nom_terceros
    if (!empty($ids_afp)) {
        $placeholders = implode(',', array_fill(0, count($ids_afp), '?'));
        $sql = "SELECT `id_tn`, `id_tercero_api` FROM `nom_terceros` WHERE `id_tn` IN ($placeholders)";
        $stmt = $cmd->prepare($sql);
        $stmt->execute(array_values($ids_afp));
        $afp_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($afp_data as $afp) {
            $idsTercero['afp'][$afp['id_tn']] = $afp['id_tercero_api'];
        }
    }

    // Cuentas de causación que son pasivos
    $sql = "SELECT
                `nom_causacion`.`id_causacion`
                , `nom_causacion`.`centro_costo`
                , `nom_causacion`.`id_tipo`
                , `nom_tipo_rubro`.`nombre`
                , `nom_causacion`.`cuenta`
                , `nom_causacion`.`detalle`
                , `tb_centrocostos`.`es_pasivo`
            FROM
                `nom_causacion`
            INNER JOIN `nom_tipo_rubro` 
                ON (`nom_causacion`.`id_tipo` = `nom_tipo_rubro`.`id_rubro`)
            INNER JOIN `tb_centrocostos`
                ON (`nom_causacion`.`centro_costo` = `tb_centrocostos`.`id_centro`)
            WHERE `tb_centrocostos`.`es_pasivo` = 1";
    $rs = $cmd->query($sql);
    $cPasivo = $rs->fetchAll(PDO::FETCH_ASSOC);

    // Cuenta de banco
    $sql = "SELECT `id_cuenta` AS `cta_contable` FROM `tes_cuentas` WHERE `est_nomina` = 1 LIMIT 1";
    $rs = $cmd->query($sql);
    $banco = $rs->fetch(PDO::FETCH_ASSOC);
    if (!$banco) {
        exit('No se ha configurado una cuenta de tesorería para nómina');
    }

    // Obtener tercero empresa
    $sql = "SELECT `id_tercero_api` FROM `tb_terceros` WHERE `nit_tercero` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->execute([$_SESSION['nit_emp']]);
    $id_ter_api = $stmt->fetchColumn() ?: 0;

    // Obtener detalles de COP
    $sql = "SELECT
                `pto_cop_detalle`.`id_pto_cop_det`
                , `pto_cdp_detalle`.`id_rubro`
                , `pto_cop_detalle`.`id_tercero_api`
            FROM
                `pto_cop_detalle`
                INNER JOIN `pto_crp_detalle` 
                    ON (`pto_cop_detalle`.`id_pto_crp_det` = `pto_crp_detalle`.`id_pto_crp_det`)
                INNER JOIN `pto_cdp_detalle` 
                    ON (`pto_crp_detalle`.`id_pto_cdp_det` = `pto_cdp_detalle`.`id_pto_cdp_det`)
            WHERE (`pto_cop_detalle`.`id_ctb_doc` = ?)";
    $stmt = $cmd->prepare($sql);
    $stmt->execute([$id_ctb_doc]);
    $ids_detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}

// Procesar datos para totales y descuentos
$totales = ['caja' => 0, 'icbf' => 0, 'sena' => 0, 'eps' => [], 'arl' => [], 'afp' => []];
$descuentos = ['eps' => [], 'afp' => []];
$valores = ['administrativo' => [], 'operativo' => []];

foreach ($datos as $d) {
    $tipo = $d['tipo'];
    $id = $d['id'];
    $valor = floatval($d['valor']);
    $valor_emp = floatval($d['valor_emp']);
    $cargo = $d['cargo'] == 'admin' ? 'administrativo' : 'operativo';

    // Totales patronales
    if (in_array($tipo, ['caja', 'icbf', 'sena'])) {
        $totales[$tipo] += $valor;
        $valores[$cargo][$tipo] = ($valores[$cargo][$tipo] ?? 0) + $valor;
    } elseif (in_array($tipo, ['eps', 'arl', 'afp'])) {
        $totales[$tipo][$id] = ($totales[$tipo][$id] ?? 0) + $valor;
        $valores[$cargo][$tipo][$id] = ($valores[$cargo][$tipo][$id] ?? 0) + $valor;
    }

    // Descuentos empleado (solo EPS y AFP)
    if ($tipo == 'eps' && $valor_emp > 0) {
        $descuentos['eps'][$id] = ($descuentos['eps'][$id] ?? 0) + $valor_emp;
    }
    if ($tipo == 'afp' && $valor_emp > 0) {
        $descuentos['afp'][$id] = ($descuentos['afp'][$id] ?? 0) + $valor_emp;
    }
}

$administrativo = $valores['administrativo'];
$operativo = $valores['operativo'];

$objeto = $nomina['descripcion'];
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha2 = $date->format('Y-m-d H:i:s');
$tipo_doc = 4; // CEVA

// Función auxiliar para obtener ID de detalle
function IdDetalle($ids_detalle, $rubro, $id_ter_api)
{
    foreach ($ids_detalle as $detalle) {
        if ($detalle['id_rubro'] == $rubro && $detalle['id_tercero_api'] == $id_ter_api) {
            return $detalle['id_pto_cop_det'];
        }
    }
    return null;
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $cmd->beginTransaction();

    // ========================================
    // PRIMER DOCUMENTO CEVA - APORTES PATRONALES
    // ========================================

    $sql = "SELECT MAX(`id_manu`) AS `id_manu` FROM `ctb_doc` WHERE `id_vigencia` = ? AND `id_tipo_doc` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->execute([$id_vigencia, $tipo_doc]);
    $consecutivo = $stmt->fetch();
    $id_manu = ($consecutivo['id_manu'] ?? 0) + 1;

    // Crear primer CEVA
    $sql = "INSERT INTO `ctb_doc` (`id_vigencia`, `id_tipo_doc`, `id_manu`,`id_tercero`, `fecha`, `detalle`, `id_user_reg`, `fecha_reg`, `estado`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $cmd->prepare($sql);
    $stmt->execute([$id_vigencia, $tipo_doc, $id_manu, $id_ter_api, $fecha, $objeto, $iduser, $fecha2, 2]);
    $id_ctb_doc_ceva1 = $cmd->lastInsertId();
    if (!($id_ctb_doc_ceva1 > 0)) {
        throw new Exception("No se pudo insertar el primer documento CEVA.");
    }

    // Insertar en pto_pag_detalle para aportes patronales
    $sql_pag = "INSERT INTO `pto_pag_detalle` (`id_ctb_doc`,`id_pto_cop_det`,`valor`,`valor_liberado`,`id_tercero_api`)
                VALUES (?, ?, ?, ?, ?)";
    $stmt_pag = $cmd->prepare($sql_pag);

    foreach ($rubros as $rb) {
        $tipo = $rb['id_tipo'];
        $valor = 0;
        $liberado = 0;

        switch ($tipo) {
            case 11: // Caja de Compensación
                $valor = $administrativo['caja'] ?? 0;
                $rubro = $rb['r_admin'];
                $id_tercero = $id_api_comfam;
                $id_det = IdDetalle($ids_detalle, $rubro, $id_tercero);
                if ($valor > 0 && $id_det) {
                    $stmt_pag->execute([$id_ctb_doc_ceva1, $id_det, $valor, $liberado, $id_tercero]);
                }
                $rubro = $rb['r_operativo'];
                $id_det = IdDetalle($ids_detalle, $rubro, $id_tercero);
                $valor = $operativo['caja'] ?? 0;
                if ($valor > 0 && $id_det) {
                    $stmt_pag->execute([$id_ctb_doc_ceva1, $id_det, $valor, $liberado, $id_tercero]);
                }
                break;
            case 12: // EPS
                if (!empty($administrativo['eps'])) {
                    $rubro = $rb['r_admin'];
                    foreach ($administrativo['eps'] as $key => $value) {
                        $id_tercero = $idsTercero['eps'][$key] ?? null;
                        if ($id_tercero && $value > 0) {
                            $id_det = IdDetalle($ids_detalle, $rubro, $id_tercero);
                            if ($id_det) {
                                $stmt_pag->execute([$id_ctb_doc_ceva1, $id_det, $value, $liberado, $id_tercero]);
                            }
                        }
                    }
                }
                if (!empty($operativo['eps'])) {
                    $rubro = $rb['r_operativo'];
                    foreach ($operativo['eps'] as $key => $value) {
                        $id_tercero = $idsTercero['eps'][$key] ?? null;
                        if ($id_tercero && $value > 0) {
                            $id_det = IdDetalle($ids_detalle, $rubro, $id_tercero);
                            if ($id_det) {
                                $stmt_pag->execute([$id_ctb_doc_ceva1, $id_det, $value, $liberado, $id_tercero]);
                            }
                        }
                    }
                }
                break;
            case 13: // ARL
                if (!empty($administrativo['arl'])) {
                    $rubro = $rb['r_admin'];
                    foreach ($administrativo['arl'] as $key => $value) {
                        $id_tercero = $idsTercero['arl'][$key] ?? null;
                        if ($id_tercero && $value > 0) {
                            $id_det = IdDetalle($ids_detalle, $rubro, $id_tercero);
                            if ($id_det) {
                                $stmt_pag->execute([$id_ctb_doc_ceva1, $id_det, $value, $liberado, $id_tercero]);
                            }
                        }
                    }
                }
                if (!empty($operativo['arl'])) {
                    $rubro = $rb['r_operativo'];
                    foreach ($operativo['arl'] as $key => $value) {
                        $id_tercero = $idsTercero['arl'][$key] ?? null;
                        if ($id_tercero && $value > 0) {
                            $id_det = IdDetalle($ids_detalle, $rubro, $id_tercero);
                            if ($id_det) {
                                $stmt_pag->execute([$id_ctb_doc_ceva1, $id_det, $value, $liberado, $id_tercero]);
                            }
                        }
                    }
                }
                break;
            case 14: // AFP
                if (!empty($administrativo['afp'])) {
                    $rubro = $rb['r_admin'];
                    foreach ($administrativo['afp'] as $key => $value) {
                        $id_tercero = $idsTercero['afp'][$key] ?? null;
                        if ($id_tercero && $value > 0) {
                            $id_det = IdDetalle($ids_detalle, $rubro, $id_tercero);
                            if ($id_det) {
                                $stmt_pag->execute([$id_ctb_doc_ceva1, $id_det, $value, $liberado, $id_tercero]);
                            }
                        }
                    }
                }
                if (!empty($operativo['afp'])) {
                    $rubro = $rb['r_operativo'];
                    foreach ($operativo['afp'] as $key => $value) {
                        $id_tercero = $idsTercero['afp'][$key] ?? null;
                        if ($id_tercero && $value > 0) {
                            $id_det = IdDetalle($ids_detalle, $rubro, $id_tercero);
                            if ($id_det) {
                                $stmt_pag->execute([$id_ctb_doc_ceva1, $id_det, $value, $liberado, $id_tercero]);
                            }
                        }
                    }
                }
                break;
            case 15: // ICBF
                $valor = $administrativo['icbf'] ?? 0;
                $rubro = $rb['r_admin'];
                $id_tercero = $id_api_icbf;
                $id_det = IdDetalle($ids_detalle, $rubro, $id_tercero);
                if ($valor > 0 && $id_det) {
                    $stmt_pag->execute([$id_ctb_doc_ceva1, $id_det, $valor, $liberado, $id_tercero]);
                }
                $rubro = $rb['r_operativo'];
                $id_det = IdDetalle($ids_detalle, $rubro, $id_tercero);
                $valor = $operativo['icbf'] ?? 0;
                if ($valor > 0 && $id_det) {
                    $stmt_pag->execute([$id_ctb_doc_ceva1, $id_det, $valor, $liberado, $id_tercero]);
                }
                break;
            case 16: // SENA
                $valor = $administrativo['sena'] ?? 0;
                $rubro = $rb['r_admin'];
                $id_tercero = $id_api_sena;
                $id_det = IdDetalle($ids_detalle, $rubro, $id_tercero);
                if ($valor > 0 && $id_det) {
                    $stmt_pag->execute([$id_ctb_doc_ceva1, $id_det, $valor, $liberado, $id_tercero]);
                }
                $rubro = $rb['r_operativo'];
                $id_det = IdDetalle($ids_detalle, $rubro, $id_tercero);
                $valor = $operativo['sena'] ?? 0;
                if ($valor > 0 && $id_det) {
                    $stmt_pag->execute([$id_ctb_doc_ceva1, $id_det, $valor, $liberado, $id_tercero]);
                }
                break;
        }
    }

    // Insertar en ctb_libaux para aportes patronales (DÉBITOS)
    $sql_libaux = "INSERT INTO `ctb_libaux` (`id_ctb_doc`,`id_tercero_api`,`id_cuenta`,`debito`,`credito`,`id_user_reg`,`fecha_reg`) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_libaux = $cmd->prepare($sql_libaux);

    foreach ($cPasivo as $cp) {
        $tipo = $cp['id_tipo'];
        $cuenta = $cp['cuenta'];
        $credito = 0;
        $valor = 0;

        switch ($tipo) {
            case 11: // Caja
                $valor = $totales['caja'] > 0 ? $totales['caja'] : 0;
                $id_tercero = $id_api_comfam;
                if ($valor > 0) {
                    $stmt_libaux->execute([$id_ctb_doc_ceva1, $id_tercero, $cuenta, $valor, $credito, $iduser, $fecha2]);
                }
                break;
            case 12: // EPS
                if (!empty($totales['eps'])) {
                    foreach ($totales['eps'] as $key => $value) {
                        $id_tercero = $idsTercero['eps'][$key] ?? null;
                        if ($id_tercero && $value > 0) {
                            $stmt_libaux->execute([$id_ctb_doc_ceva1, $id_tercero, $cuenta, $value, $credito, $iduser, $fecha2]);
                        }
                    }
                }
                break;
            case 13: // ARL
                if (!empty($totales['arl'])) {
                    foreach ($totales['arl'] as $key => $value) {
                        $id_tercero = $idsTercero['arl'][$key] ?? null;
                        if ($id_tercero && $value > 0) {
                            $stmt_libaux->execute([$id_ctb_doc_ceva1, $id_tercero, $cuenta, $value, $credito, $iduser, $fecha2]);
                        }
                    }
                }
                break;
            case 14: // AFP
                if (!empty($totales['afp'])) {
                    foreach ($totales['afp'] as $key => $value) {
                        $id_tercero = $idsTercero['afp'][$key] ?? null;
                        if ($id_tercero && $value > 0) {
                            $stmt_libaux->execute([$id_ctb_doc_ceva1, $id_tercero, $cuenta, $value, $credito, $iduser, $fecha2]);
                        }
                    }
                }
                break;
            case 15: // ICBF
                $valor = $totales['icbf'] > 0 ? $totales['icbf'] : 0;
                $id_tercero = $id_api_icbf;
                if ($valor > 0) {
                    $stmt_libaux->execute([$id_ctb_doc_ceva1, $id_tercero, $cuenta, $valor, $credito, $iduser, $fecha2]);
                }
                break;
            case 16: // SENA
                $valor = $totales['sena'] > 0 ? $totales['sena'] : 0;
                $id_tercero = $id_api_sena;
                if ($valor > 0) {
                    $stmt_libaux->execute([$id_ctb_doc_ceva1, $id_tercero, $cuenta, $valor, $credito, $iduser, $fecha2]);
                }
                break;
        }
    }

    // CRÉDITOS a la cuenta del banco para aportes patronales
    $valor = 0;
    $cuenta = $banco['cta_contable'];

    $credito = $totales['caja'] > 0 ? $totales['caja'] : 0;
    $id_tercero = $id_api_comfam;
    if ($credito > 0) {
        $stmt_libaux->execute([$id_ctb_doc_ceva1, $id_tercero, $cuenta, $valor, $credito, $iduser, $fecha2]);
    }

    if (!empty($totales['eps'])) {
        foreach ($totales['eps'] as $key => $value) {
            $id_tercero = $idsTercero['eps'][$key] ?? null;
            if ($id_tercero && $value > 0) {
                $stmt_libaux->execute([$id_ctb_doc_ceva1, $id_tercero, $cuenta, $valor, $value, $iduser, $fecha2]);
            }
        }
    }

    if (!empty($totales['arl'])) {
        foreach ($totales['arl'] as $key => $value) {
            $id_tercero = $idsTercero['arl'][$key] ?? null;
            if ($id_tercero && $value > 0) {
                $stmt_libaux->execute([$id_ctb_doc_ceva1, $id_tercero, $cuenta, $valor, $value, $iduser, $fecha2]);
            }
        }
    }

    if (!empty($totales['afp'])) {
        foreach ($totales['afp'] as $key => $value) {
            $id_tercero = $idsTercero['afp'][$key] ?? null;
            if ($id_tercero && $value > 0) {
                $stmt_libaux->execute([$id_ctb_doc_ceva1, $id_tercero, $cuenta, $valor, $value, $iduser, $fecha2]);
            }
        }
    }

    $credito = $totales['icbf'] > 0 ? $totales['icbf'] : 0;
    $id_tercero = $id_api_icbf;
    if ($credito > 0) {
        $stmt_libaux->execute([$id_ctb_doc_ceva1, $id_tercero, $cuenta, $valor, $credito, $iduser, $fecha2]);
    }

    $credito = $totales['sena'] > 0 ? $totales['sena'] : 0;
    $id_tercero = $id_api_sena;
    if ($credito > 0) {
        $stmt_libaux->execute([$id_ctb_doc_ceva1, $id_tercero, $cuenta, $valor, $credito, $iduser, $fecha2]);
    }

    // ========================================
    // SEGUNDO DOCUMENTO CEVA - DESCUENTOS EMPLEADO
    // ========================================

    // Obtener nuevo consecutivo
    $sql = "SELECT MAX(`id_manu`) AS `id_manu` FROM `ctb_doc` WHERE `id_vigencia` = ? AND `id_tipo_doc` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->execute([$id_vigencia, $tipo_doc]);
    $consecutivo = $stmt->fetch();
    $id_manu = ($consecutivo['id_manu'] ?? 0) + 1;

    // Crear segundo CEVA
    $sql = "INSERT INTO `ctb_doc` (`id_vigencia`, `id_tipo_doc`, `id_manu`,`id_tercero`, `fecha`, `detalle`, `id_user_reg`, `fecha_reg`, `estado`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $cmd->prepare($sql);
    $stmt->execute([$id_vigencia, $tipo_doc, $id_manu, $id_ter_api, $fecha, $objeto, $iduser, $fecha2, 2]);
    $id_ctb_doc_ceva2 = $cmd->lastInsertId();
    if (!($id_ctb_doc_ceva2 > 0)) {
        throw new Exception("No se pudo insertar el segundo documento CEVA.");
    }

    // Insertar en ctb_libaux para descuentos empleado (DÉBITOS - cases 24 y 25)
    foreach ($cPasivo as $cp) {
        $tipo = $cp['id_tipo'];
        $cuenta = $cp['cuenta'];
        $credito = 0;
        $valor = 0;

        switch ($tipo) {
            case 24: // Aporte empleado salud (EPS)
                if (!empty($descuentos['eps'])) {
                    foreach ($descuentos['eps'] as $key => $value) {
                        $id_tercero = $idsTercero['eps'][$key] ?? null;
                        if ($id_tercero && $value > 0) {
                            $stmt_libaux->execute([$id_ctb_doc_ceva2, $id_tercero, $cuenta, $value, $credito, $iduser, $fecha2]);
                        }
                    }
                }
                break;
            case 25: // Aporte empleado pensión (AFP)
                if (!empty($descuentos['afp'])) {
                    foreach ($descuentos['afp'] as $key => $value) {
                        $id_tercero = $idsTercero['afp'][$key] ?? null;
                        if ($id_tercero && $value > 0) {
                            $stmt_libaux->execute([$id_ctb_doc_ceva2, $id_tercero, $cuenta, $value, $credito, $iduser, $fecha2]);
                        }
                    }
                }
                break;
        }
    }

    // CRÉDITOS a la cuenta del banco para descuentos empleado
    $valor = 0;
    $cuenta = $banco['cta_contable'];

    if (!empty($descuentos['eps'])) {
        foreach ($descuentos['eps'] as $key => $value) {
            $id_tercero = $idsTercero['eps'][$key] ?? null;
            if ($id_tercero && $value > 0) {
                $stmt_libaux->execute([$id_ctb_doc_ceva2, $id_tercero, $cuenta, $valor, $value, $iduser, $fecha2]);
            }
        }
    }

    if (!empty($descuentos['afp'])) {
        foreach ($descuentos['afp'] as $key => $value) {
            $id_tercero = $idsTercero['afp'][$key] ?? null;
            if ($id_tercero && $value > 0) {
                $stmt_libaux->execute([$id_ctb_doc_ceva2, $id_tercero, $cuenta, $valor, $value, $iduser, $fecha2]);
            }
        }
    }

    // ========================================
    // ACTUALIZAR ESTADOS
    // ========================================

    // Actualizar estado de nómina
    $sql = "UPDATE `nom_nominas` SET `planilla` = ? WHERE `id_nomina` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->execute([5, $id_nomina]); // 5 = Pagada

    // Actualizar relación con el segundo CEVA (descuentos empleado)
    $sql = "UPDATE `nom_nomina_pto_ctb_tes` SET `ceva` = ? WHERE `id_nomina` = ? AND `crp` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->execute([$id_ctb_doc_ceva2, $id_nomina, $id_doc_crp]);

    $cmd->commit();
    echo 'ok';
} catch (Exception $e) {
    if ($cmd->inTransaction()) {
        $cmd->rollBack();
    }
    echo 'Error: ' . $e->getMessage();
}
