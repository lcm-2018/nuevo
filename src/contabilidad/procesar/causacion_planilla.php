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
    $parafiscales = $rs->fetchAll(PDO::FETCH_ASSOC);
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

// Obtener datos de nómina
$Nomina = new Nomina();
$nomina = $Nomina->getRegistro($id_nomina);

exit('Se debe ajustar');
// Obtener datos patronales con la query corregida que obtiene tipo_cargo desde el contrato
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT 
                `t1`.`id_empleado`
                , `nca`.`tipo_cargo`
                , `t1`.`id_ccosto`
                , `t1`.`id_eps`
                , `t1`.`id_arl`
                , `t1`.`id_afp`
                , `t1`.`id_api_eps`
                , `t1`.`id_api_arl`
                , `t1`.`id_api_afp`
                , `t1`.`aporte_salud_emp`
                , `t1`.`aporte_salud_empresa`
                , `t1`.`aporte_pension_emp`
                , `t1`.`aporte_solidaridad_pensional`
                , `t1`.`aporte_pension_empresa`
                , `t1`.`aporte_rieslab`
                , `t2`.`val_sena`
                , `t2`.`val_icbf`
                , `t2`.`val_comfam`
            FROM 
                (SELECT
                    `nls`.`id_empleado`
                    , `nls`.`id_contrato`
                    , `ncc`.`id_ccosto`
                    , `nlse`.`id_eps`
                    , `nlse`.`id_arl`
                    , `nlse`.`id_afp`
                    , `ne`.`id_tercero_api` AS `id_api_eps`
                    , `na`.`id_tercero_api` AS `id_api_arl`
                    , `nf`.`id_tercero_api` AS `id_api_afp`
                    , `nlse`.`aporte_salud_emp`
                    , `nlse`.`aporte_salud_empresa`
                    , `nlse`.`aporte_pension_emp`
                    , `nlse`.`aporte_solidaridad_pensional`
                    , `nlse`.`aporte_pension_empresa`
                    , `nlse`.`aporte_rieslab`
                FROM
                    `nom_liq_salario` AS `nls`
                    INNER JOIN `nom_liq_segsocial_empdo` AS `nlse`
                        ON (`nlse`.`id_empleado` = `nls`.`id_empleado` AND `nlse`.`id_nomina` = `nls`.`id_nomina` AND `nlse`.`estado` = 1)
                    INNER JOIN `nom_epss` AS `ne`
                        ON (`nlse`.`id_eps` = `ne`.`id_eps`)
                    INNER JOIN `nom_arl` AS `na`
                        ON (`nlse`.`id_arl` = `na`.`id_arl`)
                    INNER JOIN `nom_afp` AS `nf`
                        ON (`nlse`.`id_afp` = `nf`.`id_afp`)
                    LEFT JOIN `nom_ccosto_empleado` AS `ncc`
                        ON (`nls`.`id_empleado` = `ncc`.`id_empleado`)
                WHERE `nls`.`id_nomina` = $id_nomina AND `nls`.`estado` = 1) AS `t1`
            INNER JOIN `nom_contratos_empleados` AS `nce`
                ON (`t1`.`id_contrato` = `nce`.`id_contrato_emp`)
            LEFT JOIN `nom_cargo_empleado` AS `nca`
                ON (`nce`.`id_cargo` = `nca`.`id_cargo`)
            LEFT JOIN 
                (SELECT 
                    `id_empleado`
                    , `val_sena`
                    , `val_icbf`
                    , `val_comfam`
                FROM 
                    `nom_liq_parafiscales`
                WHERE `id_nomina` = $id_nomina AND `estado` = 1) AS `t2`
            ON (`t1`.`id_empleado` = `t2`.`id_empleado`)
            ORDER BY `t1`.`id_ccosto` ASC";
    $rs = $cmd->query($sql);
    $patronales = $rs->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}

// Agrupar totales por centro de costo
$totales = [];
foreach ($patronales as $p) {
    $ccosto = $p['id_ccosto'] ?? 21;
    $id_eps = $p['id_eps'];
    $id_arl = $p['id_arl'];
    $id_afp = $p['id_afp'];
    $val_caja = isset($totales[$ccosto]['caja']) ? $totales[$ccosto]['caja'] : 0;
    $val_icbf = isset($totales[$ccosto]['icbf']) ? $totales[$ccosto]['icbf'] : 0;
    $val_sena = isset($totales[$ccosto]['sena']) ? $totales[$ccosto]['sena'] : 0;
    $totales[$ccosto]['caja'] = ($p['val_comfam'] ?? 0) + $val_caja;
    $totales[$ccosto]['icbf'] = ($p['val_icbf'] ?? 0) + $val_icbf;
    $totales[$ccosto]['sena'] = ($p['val_sena'] ?? 0) + $val_sena;
    $valeps = isset($totales[$ccosto]['eps'][$id_eps]) ? $totales[$ccosto]['eps'][$id_eps] : 0;
    $valarl = isset($totales[$ccosto]['arl'][$id_arl]) ? $totales[$ccosto]['arl'][$id_arl] : 0;
    $valafp = isset($totales[$ccosto]['afp'][$id_afp]) ? $totales[$ccosto]['afp'][$id_afp] : 0;
    $totales[$ccosto]['eps'][$id_eps] = $p['aporte_salud_empresa'] + $valeps;
    $totales[$ccosto]['arl'][$id_arl] = $p['aporte_rieslab'] + $valarl;
    $totales[$ccosto]['afp'][$id_afp] = $p['aporte_pension_empresa'] + $valafp;
}

// Agrupar valores por tipo de cargo
$valores = [];
foreach ($patronales as $p) {
    $tipo = $p['tipo_cargo'] == 1 ? 'administrativo' : 'operativo';
    $id_eps = $p['id_eps'];
    $id_arl = $p['id_arl'];
    $id_afp = $p['id_afp'];
    $totsena = isset($valores[$tipo]['sena']) ? $valores[$tipo]['sena'] : 0;
    $toticbf = isset($valores[$tipo]['icbf']) ? $valores[$tipo]['icbf'] : 0;
    $totcomfam = isset($valores[$tipo]['caja']) ? $valores[$tipo]['caja'] : 0;
    $valores[$tipo]['sena'] = ($p['val_sena'] ?? 0) + $totsena;
    $valores[$tipo]['icbf'] = ($p['val_icbf'] ?? 0) + $toticbf;
    $valores[$tipo]['caja'] = ($p['val_comfam'] ?? 0) + $totcomfam;
    $valeps = isset($valores[$tipo]['eps'][$id_eps]) ? $valores[$tipo]['eps'][$id_eps] : 0;
    $valarl = isset($valores[$tipo]['arl'][$id_arl]) ? $valores[$tipo]['arl'][$id_arl] : 0;
    $valafp = isset($valores[$tipo]['afp'][$id_afp]) ? $valores[$tipo]['afp'][$id_afp] : 0;
    $valores[$tipo]['eps'][$id_eps] = $p['aporte_salud_empresa'] + $valeps;
    $valores[$tipo]['arl'][$id_arl] = $p['aporte_rieslab'] + $valarl;
    $valores[$tipo]['afp'][$id_afp] = $p['aporte_pension_empresa'] + $valafp;
}

$administrativo = isset($valores['administrativo']) ? $valores['administrativo'] : [];
$operativo = isset($valores['operativo']) ? $valores['operativo'] : [];

// Crear mapas de IDs de terceros API
$admin = [];
$oper = [];
$idsTercero = [];
foreach ($patronales as $p) {
    $id_eps = $p['id_eps'];
    $id_arl = $p['id_arl'];
    $id_afp = $p['id_afp'];
    $idsTercero['eps'][$id_eps] = $p['id_api_eps'];
    $idsTercero['arl'][$id_arl] = $p['id_api_arl'];
    $idsTercero['afp'][$id_afp] = $p['id_api_afp'];

    // Agrupar por admin/oper para compatibilidad con la lógica existente
    if ($p['tipo_cargo'] == 1) {
        $admin['caja'] = ($admin['caja'] ?? 0) + ($p['val_comfam'] ?? 0);
        $admin['icbf'] = ($admin['icbf'] ?? 0) + ($p['val_icbf'] ?? 0);
        $admin['sena'] = ($admin['sena'] ?? 0) + ($p['val_sena'] ?? 0);
        $admin['eps'][$id_eps] = ($admin['eps'][$id_eps] ?? 0) + $p['aporte_salud_empresa'];
        $admin['arl'][$id_arl] = ($admin['arl'][$id_arl] ?? 0) + $p['aporte_rieslab'];
        $admin['afp'][$id_afp] = ($admin['afp'][$id_afp] ?? 0) + $p['aporte_pension_empresa'];
    } else {
        $oper['caja'] = ($oper['caja'] ?? 0) + ($p['val_comfam'] ?? 0);
        $oper['icbf'] = ($oper['icbf'] ?? 0) + ($p['val_icbf'] ?? 0);
        $oper['sena'] = ($oper['sena'] ?? 0) + ($p['val_sena'] ?? 0);
        $oper['eps'][$id_eps] = ($oper['eps'][$id_eps] ?? 0) + $p['aporte_salud_empresa'];
        $oper['arl'][$id_arl] = ($oper['arl'][$id_arl] ?? 0) + $p['aporte_rieslab'];
        $oper['afp'][$id_afp] = ($oper['afp'][$id_afp] ?? 0) + $p['aporte_pension_empresa'];
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
    $rubros = $rs->fetchAll(PDO::FETCH_ASSOC);
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
    $cuentas_causacion = $rs->fetchAll(PDO::FETCH_ASSOC);
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
