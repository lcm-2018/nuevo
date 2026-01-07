<?php

use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Nomina\Liquidado\Php\Clases\Detalles;

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}
include '../../../config/autoloader.php';

$vigencia = $_SESSION['vigencia'];
$id_vigencia = $_SESSION['id_vigencia'];
$data = explode(',', file_get_contents("php://input"));
$id_nomina = $data[0];
$crp = $data[1];
$tipo_nomina = $data[2];
$fecha = $data[3];

// Obtener configuración de parafiscales
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_parafiscal`, `id_tercero_api`, `tipo`
            FROM `nom_parafiscales`
            ORDER BY `id_parafiscal` DESC";
    $rs = $cmd->query($sql);
    $parafiscales = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);

    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}

$kpf = array_search('SENA', array_column($parafiscales, 'tipo'));
$id_api_sena = $kpf !== false ? $parafiscales[$kpf]['id_tercero_api'] : exit('No se ha configurado el parafiscal SENA');
$kpf = array_search('ICBF', array_column($parafiscales, 'tipo'));
$id_api_icbf = $kpf !== false ? $parafiscales[$kpf]['id_tercero_api'] : exit('No se ha configurado el parafiscal ICBF');
$kpf = array_search('CAJA', array_column($parafiscales, 'tipo'));
$id_api_comfam = $kpf !== false ? $parafiscales[$kpf]['id_tercero_api'] : exit('No se ha configurado el parafiscal CAJA DE COMPENSACION');

// Usar las clases para obtener datos
$Detalles = new Detalles();
$Nomina = new Nomina();

$datos = $Detalles->getAporteSocial($id_nomina);
$nomina = $Nomina->getRegistro($id_nomina);

// Agrupar aportes por cargo y tipo
// Para parafiscales: valores simples
// Para seguridad social (eps, arl, afp): arrays por ID de entidad
$admin = [];
$oper = [];

foreach ($datos as $row) {
    $cargo = $row['cargo']; // 'admin' o 'oper'
    $tipo = $row['tipo']; // 'eps', 'arl', 'afp', 'sena', 'icbf', 'caja'
    $id_entidad = $row['id']; // ID de la entidad (EPS, ARL, AFP, etc.)
    $valor = $row['valor'];

    if ($cargo == 'admin') {
        if (in_array($tipo, ['eps', 'arl', 'afp'])) {
            // Para seguridad social, agrupar por ID de entidad
            $admin[$tipo][$id_entidad] = ($admin[$tipo][$id_entidad] ?? 0) + $valor;
        } else {
            // Para parafiscales, sumar directamente
            $admin[$tipo] = ($admin[$tipo] ?? 0) + $valor;
        }
    } else { // 'oper'
        if (in_array($tipo, ['eps', 'arl', 'afp'])) {
            // Para seguridad social, agrupar por ID de entidad
            $oper[$tipo][$id_entidad] = ($oper[$tipo][$id_entidad] ?? 0) + $valor;
        } else {
            // Para parafiscales, sumar directamente
            $oper[$tipo] = ($oper[$tipo] ?? 0) + $valor;
        }
    }
}

// Obtener IDs de terceros API para cada EPS, ARL y AFP
$idsTercero = [];
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    // Extraer IDs únicos de EPS, ARL y AFP de los datos
    $ids_eps = array_unique(array_column(array_filter($datos, fn($d) => $d['tipo'] == 'eps'), 'id'));
    $ids_arl = array_unique(array_column(array_filter($datos, fn($d) => $d['tipo'] == 'arl'), 'id'));
    $ids_afp = array_unique(array_column(array_filter($datos, fn($d) => $d['tipo'] == 'afp'), 'id'));

    // Obtener terceros API de EPS
    if (!empty($ids_eps)) {
        $placeholders_eps = implode(',', array_fill(0, count($ids_eps), '?'));
        $sql = "SELECT `id_tn`, `id_tercero_api` FROM `nom_terceros` WHERE `id_tn` IN ($placeholders_eps)";
        $stmt = $cmd->prepare($sql);
        $stmt->execute(array_values($ids_eps));
        $eps_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($eps_data as $eps) {
            $idsTercero['eps'][$eps['id_tn']] = $eps['id_tercero_api'];
        }
    }

    // Obtener terceros API de ARL
    if (!empty($ids_arl)) {
        $placeholders_arl = implode(',', array_fill(0, count($ids_arl), '?'));
        $sql = "SELECT `id_tn`, `id_tercero_api` FROM `nom_terceros` WHERE `id_tn` IN ($placeholders_arl)";
        $stmt = $cmd->prepare($sql);
        $stmt->execute(array_values($ids_arl));
        $arl_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($arl_data as $arl) {
            $idsTercero['arl'][$arl['id_tn']] = $arl['id_tercero_api'];
        }
    }

    // Obtener terceros API de AFP
    if (!empty($ids_afp)) {
        $placeholders_afp = implode(',', array_fill(0, count($ids_afp), '?'));
        $sql = "SELECT `id_tn`, `id_tercero_api` FROM `nom_terceros` WHERE `id_tn` IN ($placeholders_afp)";
        $stmt = $cmd->prepare($sql);
        $stmt->execute(array_values($ids_afp));
        $afp_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($afp_data as $afp) {
            $idsTercero['afp'][$afp['id_tn']] = $afp['id_tercero_api'];
        }
    }

    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}

// Obtener centros de costo por empleado y tipo de cargo
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT 
                `nls`.`id_empleado`
                , IFNULL(`ncc`.`id_ccosto`, 21) AS `id_ccosto`
                , `nca`.`tipo_cargo`
                , `nlse`.`id_eps`
                , `nlse`.`id_arl`
                , `nlse`.`id_afp`
            FROM 
                `nom_liq_salario` AS `nls`
                INNER JOIN `nom_contratos_empleados` AS `nce`
                    ON `nce`.`id_contrato_emp` = `nls`.`id_contrato`
                INNER JOIN `nom_cargo_empleado` AS `nca`
                    ON `nca`.`id_cargo` = `nce`.`id_cargo`
                LEFT JOIN `nom_ccosto_empleado` AS `ncc`
                    ON `nls`.`id_empleado` = `ncc`.`id_empleado`
                LEFT JOIN `nom_liq_segsocial_empdo` AS `nlse`
                    ON `nlse`.`id_empleado` = `nls`.`id_empleado` 
                    AND `nlse`.`id_nomina` = `nls`.`id_nomina` 
                    AND `nlse`.`estado` = 1
            WHERE 
                `nls`.`id_nomina` = $id_nomina AND `nls`.`estado` = 1";
    $rs = $cmd->query($sql);
    $empleados_ccosto = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}

// Reconstruir totales por centro de costo usando los datos de getAporteSocial()
$totales = [];
foreach ($datos as $row) {
    // Buscar el centro de costo para este empleado y tipo de aporte
    foreach ($empleados_ccosto as $emp) {
        $tipo_cargo_calc = $emp['tipo_cargo'] == 1 ? 'admin' : 'oper';

        // Determinar si este registro corresponde a este empleado
        $coincide = false;
        if ($row['tipo'] == 'eps' && $emp['id_eps'] == $row['id'] && $row['cargo'] == $tipo_cargo_calc) {
            $coincide = true;
        } elseif ($row['tipo'] == 'arl' && $emp['id_arl'] == $row['id'] && $row['cargo'] == $tipo_cargo_calc) {
            $coincide = true;
        } elseif ($row['tipo'] == 'afp' && $emp['id_afp'] == $row['id'] && $row['cargo'] == $tipo_cargo_calc) {
            $coincide = true;
        } elseif (in_array($row['tipo'], ['sena', 'icbf', 'caja']) && $row['cargo'] == $tipo_cargo_calc) {
            $coincide = true;
        }

        if ($coincide) {
            $ccosto = $emp['id_ccosto'];
            $tipo = $row['tipo'];

            if (in_array($tipo, ['eps', 'arl', 'afp'])) {
                // Para seguridad social, agrupar por ID de entidad
                $id_entidad = $row['id'];
                $totales[$ccosto][$tipo][$id_entidad] = ($totales[$ccosto][$tipo][$id_entidad] ?? 0) + $row['valor'];
            } else {
                // Para parafiscales (caja, icbf, sena)
                $totales[$ccosto][$tipo] = ($totales[$ccosto][$tipo] ?? 0) + $row['valor'];
            }
            break; // Ya encontramos el empleado, salir del bucle
        }
    }
}

// Obtener relación de rubros
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `nom_tipo_rubro`.`id_rubro`
                , `nom_rel_rubro`.`id_tipo`
                , `nom_tipo_rubro`.`nombre`
                , `nom_rel_rubro`.`r_admin`
                , `nom_rel_rubro`.`r_operativo`
            FROM
                `nom_rel_rubro`
                INNER JOIN `nom_tipo_rubro` 
                    ON (`nom_rel_rubro`.`id_tipo` = `nom_tipo_rubro`.`id_rubro`)
            WHERE (`nom_rel_rubro`.`id_vigencia` = $id_vigencia)";
    $rs = $cmd->query($sql);
    $rubros = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}

// Obtener cuentas de causación
try {
    $cmd = \Config\Clases\Conexion::getConexion();
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
                    ON (`nom_causacion`.`centro_costo` = `tb_centrocostos`.`id_centro`)";
    $rs = $cmd->query($sql);
    $cuentas_causacion = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}

// Obtener cuenta de tesorería
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `tes_cuentas`.`estado`
                , `tes_cuentas`.`id_tes_cuenta`
                , `ctb_pgcp`.`cuenta` AS `cta_contable`
            FROM
                `tes_cuentas`
                INNER JOIN `ctb_pgcp` 
                    ON (`tes_cuentas`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`)
            WHERE (`tes_cuentas`.`estado` = 1)";
    $rs = $cmd->query($sql);
    $banco = $rs->fetch(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}

// Configuración de constantes
$meses = array(
    '00' => '',
    '01' => 'Enero',
    '02' => 'Febrero',
    '03' => 'Marzo',
    '04' => 'Abril',
    '05' => 'Mayo',
    '06' => 'Junio',
    '07' => 'Julio',
    '08' => 'Agosto',
    '09' => 'Septiembre',
    '10' => 'Octubre',
    '11' => 'Noviembre',
    '12' => 'Diciembre'
);

$mes = $nomina['mes'] != '' ? $nomina['mes'] : '00';
$nom_mes = isset($meses[$mes]) ? 'MES DE ' . mb_strtoupper($meses[$mes]) : '';

// Mapa entre id_tipo → clave usada en admin/oper
$map = [
    11 => 'caja',
    12 => 'eps',
    13 => 'arl',
    14 => 'afp',
    15 => 'icbf',
    16 => 'sena'
];

$terceros_map = [
    'caja' => $id_api_comfam,
    'icbf' => $id_api_icbf,
    'sena' => $id_api_sena
];

$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$objeto = "PAGO NOMINA PATRONAL " . $nomina['descripcion'] . " N° " . $id_nomina . ' ' . $nom_mes . " VIGENCIA " . $nomina['vigencia'];
$iduser = $_SESSION['id_user'];
$fecha2 = $date->format('Y-m-d H:i:s');
$cnom = 5;

// Obtener consecutivo
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT MAX(`id_manu`) AS `id_manu` FROM `ctb_doc` WHERE `id_vigencia` = $id_vigencia AND `id_tipo_doc` = $cnom";
    $rs = $cmd->query($sql);
    $consecutivo = $rs->fetch();
    $id_manu = !empty($consecutivo) ? $consecutivo['id_manu'] + 1 : 1;
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}

// Obtener tercero empresa
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_tercero_api` FROM `tb_terceros` WHERE `nit_tercero` = " . $_SESSION['nit_emp'];
    $rs = $cmd->query($sql);
    $tercero = $rs->fetch();
    $id_ter_api = !empty($tercero) ? $tercero['id_tercero_api'] : NULL;
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    exit();
}

// Obtener detalles de CRP
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `pto_crp_detalle`.`id_pto_crp_det`
                , `pto_cdp_detalle`.`id_rubro`
                , `pto_crp_detalle`.`id_tercero_api`
            FROM
                `pto_crp_detalle`
                INNER JOIN `pto_cdp_detalle` 
                    ON (`pto_crp_detalle`.`id_pto_cdp_det` = `pto_cdp_detalle`.`id_pto_cdp_det`)
            WHERE (`pto_crp_detalle`.`id_pto_crp` = $crp)";
    $rs = $cmd->query($sql);
    $ids_detalle = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
    exit();
}

// Función para obtener ID de detalle
function IdDetalle($ids_detalle, $rubro, $id_ter_api)
{
    $id_det = NULL;
    foreach ($ids_detalle as $detalle) {
        if ($detalle['id_rubro'] == $rubro && $detalle['id_tercero_api'] == $id_ter_api) {
            $id_det = $detalle['id_pto_crp_det'];
            break;
        }
    }
    return $id_det;
}

// Iniciar transacción
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $cmd->beginTransaction();

    // ENCABEZADO DOCUMENTO
    $estado = 2;
    $query = "INSERT INTO `ctb_doc` 
                (`id_vigencia`, `id_tipo_doc`, `id_manu`,`id_tercero`, `fecha`, `detalle`, `id_user_reg`, `fecha_reg`, `estado`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $id_vigencia, PDO::PARAM_INT);
    $query->bindParam(2, $cnom, PDO::PARAM_STR);
    $query->bindParam(3, $id_manu, PDO::PARAM_INT);
    $query->bindParam(4, $id_ter_api, PDO::PARAM_INT);
    $query->bindParam(5, $fecha, PDO::PARAM_STR);
    $query->bindParam(6, $objeto, PDO::PARAM_STR);
    $query->bindParam(7, $iduser, PDO::PARAM_INT);
    $query->bindParam(8, $fecha2);
    $query->bindParam(9, $estado, PDO::PARAM_INT);
    $query->execute();
    $id_doc_nom = $cmd->lastInsertId();
    if (!($id_doc_nom > 0)) {
        throw new Exception($query->errorInfo()[2]);
    }

    // DETALLE DOCUMENTO (si módulo de presupuesto está activo)
    $liberado = 0;
    if ($_SESSION['pto'] == '1') {
        $query = "INSERT INTO `pto_cop_detalle`
                    (`id_ctb_doc`, `id_pto_crp_det`,`id_tercero_api`,`valor`,`valor_liberado`,`id_user_reg`,`fecha_reg`) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $query = $cmd->prepare($query);
        $query->bindParam(1, $id_doc_nom, PDO::PARAM_INT);
        $query->bindParam(2, $id_det, PDO::PARAM_INT);
        $query->bindParam(3, $id_tercero, PDO::PARAM_INT);
        $query->bindParam(4, $valor, PDO::PARAM_STR);
        $query->bindParam(5, $liberado, PDO::PARAM_STR);
        $query->bindParam(6, $iduser, PDO::PARAM_INT);
        $query->bindParam(7, $fecha2, PDO::PARAM_STR);

        foreach ($rubros as $rb) {
            $key = $map[$rb['id_tipo']] ?? null;

            if (!$key) {
                continue;
            }

            $valAdmin = $admin[$key] ?? 0;
            $valOper  = $oper[$key] ?? 0;

            // Procesar administrativo
            if ($valAdmin > 0) {
                $rubro = $rb['r_admin'];

                // Determinar terceros según el tipo
                if (in_array($key, ['eps', 'arl', 'afp'])) {
                    // Para seguridad social, puede haber múltiples terceros
                    if (!empty($admin[$key])) {
                        foreach ($admin[$key] as $id_entidad => $valor_seg) {
                            $id_tercero_seg = $idsTercero[$key][$id_entidad] ?? null;
                            if ($id_tercero_seg && $valor_seg > 0) {
                                $id_det = IdDetalle($ids_detalle, $rubro, $id_tercero_seg);
                                if ($id_det) {
                                    $valor = $valor_seg;
                                    $query->execute();
                                    if (!($cmd->lastInsertId() > 0)) {
                                        throw new Exception($query->errorInfo()[2]);
                                    }
                                }
                            }
                        }
                    }
                } else {
                    // Para parafiscales (caja, icbf, sena) usar el tercero configurado
                    $valor = $valAdmin;
                    $id_tercero = $terceros_map[$key];
                    $id_det = IdDetalle($ids_detalle, $rubro, $id_tercero);
                    if ($id_det && $valor > 0) {
                        $query->execute();
                        if (!($cmd->lastInsertId() > 0)) {
                            throw new Exception($query->errorInfo()[2]);
                        }
                    }
                }
            }

            // Procesar operativo
            if ($valOper > 0) {
                $rubro = $rb['r_operativo'];

                // Determinar terceros según el tipo
                if (in_array($key, ['eps', 'arl', 'afp'])) {
                    // Para seguridad social, puede haber múltiples terceros
                    if (!empty($oper[$key])) {
                        foreach ($oper[$key] as $id_entidad => $valor_seg) {
                            $id_tercero_seg = $idsTercero[$key][$id_entidad] ?? null;
                            if ($id_tercero_seg && $valor_seg > 0) {
                                $id_det = IdDetalle($ids_detalle, $rubro, $id_tercero_seg);
                                if ($id_det) {
                                    $valor = $valor_seg;
                                    $query->execute();
                                    if (!($cmd->lastInsertId() > 0)) {
                                        throw new Exception($query->errorInfo()[2]);
                                    }
                                }
                            }
                        }
                    }
                } else {
                    // Para parafiscales (caja, icbf, sena) usar el tercero configurado
                    $valor = $valOper;
                    $id_tercero = $terceros_map[$key];
                    $id_det = IdDetalle($ids_detalle, $rubro, $id_tercero);
                    if ($id_det && $valor > 0) {
                        $query->execute();
                        if (!($cmd->lastInsertId() > 0)) {
                            throw new Exception($query->errorInfo()[2]);
                        }
                    }
                }
            }
        }
    }

    // LIBRO AUXILIAR
    $vPasivos = [];
    $credito = 0;
    $query = "INSERT INTO `ctb_libaux` (`id_ctb_doc`,`id_tercero_api`,`id_cuenta`,`debito`,`credito`,`id_user_reg`,`fecha_reg`) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $id_doc_nom, PDO::PARAM_INT);
    $query->bindParam(2, $id_tercero, PDO::PARAM_INT);
    $query->bindParam(3, $cuenta, PDO::PARAM_STR);
    $query->bindParam(4, $valor, PDO::PARAM_STR);
    $query->bindParam(5, $credito, PDO::PARAM_STR);
    $query->bindParam(6, $iduser, PDO::PARAM_INT);
    $query->bindParam(7, $fecha2);

    // Procesar por centro de costo
    foreach ($totales as $cc => $t) {
        $filtro = array_filter($cuentas_causacion, function ($cuentas_causacion) use ($cc) {
            return $cuentas_causacion["centro_costo"] == $cc;
        });

        foreach ($filtro as $ca) {
            $key = $map[$ca['id_tipo']] ?? null;
            if (!$key) {
                continue;
            }

            $cuenta = $ca['cuenta'];
            $valor = 0;

            if (in_array($key, ['eps', 'arl', 'afp'])) {
                // Para seguridad social, puede haber múltiples terceros
                if (!empty($t[$key])) {
                    foreach ($t[$key] as $id_entidad => $valor_seg) {
                        $id_tercero = $idsTercero[$key][$id_entidad] ?? null;
                        $valor = $valor_seg;
                        $val_pas = isset($vPasivos[$key][$id_entidad]) ? $vPasivos[$key][$id_entidad] : 0;
                        $vPasivos[$key][$id_entidad] = $valor + $val_pas;
                        if ($valor > 0 && $id_tercero) {
                            $query->execute();
                            if (!($cmd->lastInsertId() > 0)) {
                                throw new Exception($query->errorInfo()[2]);
                            }
                        }
                    }
                }
            } else {
                // Para parafiscales
                $valor = isset($t[$key]) && $t[$key] > 0 ? $t[$key] : 0;
                if ($valor > 0) {
                    $val_pas = isset($vPasivos[$key]) ? $vPasivos[$key] : 0;
                    $vPasivos[$key] = $valor + $val_pas;
                    $id_tercero = $terceros_map[$key];
                    $query->execute();
                    if (!($cmd->lastInsertId() > 0)) {
                        throw new Exception($query->errorInfo()[2]);
                    }
                }
            }
        }
    }

    // Registrar pasivos
    $cPasivo = array_filter($cuentas_causacion, function ($cuentas_causacion) {
        return $cuentas_causacion["es_pasivo"] == 1;
    });

    $valor = 0;
    foreach ($cPasivo as $cp) {
        $key = $map[$cp['id_tipo']] ?? null;
        if (!$key) {
            continue;
        }

        $cuenta = $cp['cuenta'];
        $credito = 0;

        if (in_array($key, ['eps', 'arl', 'afp'])) {
            // Para seguridad social
            if (!empty($vPasivos[$key])) {
                foreach ($vPasivos[$key] as $id_entidad => $value) {
                    $id_tercero = $idsTercero[$key][$id_entidad] ?? null;
                    $credito = $value;
                    if ($credito > 0 && $id_tercero) {
                        $query->execute();
                        if (!($cmd->lastInsertId() > 0)) {
                            throw new Exception($query->errorInfo()[2]);
                        }
                    }
                }
            }
        } else {
            // Para parafiscales
            $credito = isset($vPasivos[$key]) && $vPasivos[$key] > 0 ? $vPasivos[$key] : 0;
            $id_tercero = $terceros_map[$key];
            if ($credito > 0) {
                $query->execute();
                if (!($cmd->lastInsertId() > 0)) {
                    throw new Exception($query->errorInfo()[2]);
                }
            }
        }
    }

    // Actualizar estado de nómina
    $estado = 4;
    $sql = "UPDATE `nom_nominas` SET `planilla` = ? WHERE `id_nomina` = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $estado, PDO::PARAM_INT);
    $sql->bindParam(2, $id_nomina, PDO::PARAM_INT);
    $sql->execute();
    if (!($sql->rowCount() > 0)) {
        throw new Exception('No se pudo actualizar el estado de la nómina');
    }

    // Actualizar relación nómina-CDP-CRP
    $query = "UPDATE `nom_nomina_pto_ctb_tes` SET `cnom` = ? WHERE `id_nomina` = ? AND `tipo` = ? AND `crp` = ?";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $id_doc_nom, PDO::PARAM_INT);
    $query->bindParam(2, $id_nomina, PDO::PARAM_INT);
    $query->bindParam(3, $tipo_nomina, PDO::PARAM_STR);
    $query->bindParam(4, $crp, PDO::PARAM_INT);
    $query->execute();

    $cmd->commit();
    $cmd = null;
    echo 'ok';
} catch (Exception $e) {
    if ($cmd) {
        $cmd->rollBack();
    }
    echo 'Error: ' . $e->getMessage();
}
