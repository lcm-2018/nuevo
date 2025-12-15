<?php

use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Nomina\Liquidado\Php\Clases\Detalles;

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit();
}
include '../../../config/autoloader.php';
include '../../financiero/consultas.php';

$vigencia = $_SESSION['vigencia'];
$id_vigencia = $_SESSION['id_vigencia'];
$data = explode('|', file_get_contents("php://input"));
$id_nomina = $data[0];
$tipo_nomina = $data[1];
$fec_doc = $data[2];

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `id_parafiscal`,`id_tercero_api`,`tipo`
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

// Validar saldo en rubros
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `nom_cdp_empleados`.`rubro`
                , `nom_cdp_empleados`.`valor`
                , `pto_cargue`.`cod_pptal`
            FROM
                `nom_cdp_empleados`
                INNER JOIN `pto_cargue` 
                    ON (`nom_cdp_empleados`.`rubro` = `pto_cargue`.`id_cargue`)
            WHERE (`nom_cdp_empleados`.`id_nomina` = $id_nomina AND `nom_cdp_empleados`.`tipo` = 'PL')";
    $rs = $cmd->query($sql);
    $valxrubro = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}

if (empty($valxrubro)) {
    echo 'No se ha generado una solicitud de CDP para esta nómina';
    exit();
} else {
    $cmd = \Config\Clases\Conexion::getConexion();
    $valida = false;
    $tabla = '<table class="table table-bordered table-striped table-hover table-sm" style="font-size: 12px; width: 100%">
                <thead>
                    <tr>
                        <th>Rubro</th>
                        <th>Valor</th>
                        <th>Saldo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>';
    foreach ($valxrubro as $vr) {
        $rubro = $vr['rubro'];
        $valor = $vr['valor'];
        $cod_rubro = $vr['cod_pptal'];
        $respuesta = SaldoRubro($cmd, $rubro, $fec_doc, 0);
        $saldo = $respuesta['valor_aprobado'] - $respuesta['debito_cdp'] + $respuesta['credito_cdp'] + $respuesta['debito_mod'] - $respuesta['credito_mod'];
        $estado = $saldo >= $valor ? '<span class="badge badge-success">Disponible</span>' : '<span class="badge badge-danger">Sin Saldo</span>';
        if ($saldo < $valor) {
            $valida = true;
            $tabla .= '<tr>
                        <td>' . $cod_rubro . '</td>
                        <td class="text-end">$ ' . number_format($valor, 2, ',', '.') . '</td>
                        <td class="text-end">$ ' . number_format($saldo, 2, ',', '.') . '</td>
                        <td>' . $estado . '</td>
                    </tr>';
        }
    }
    $tabla .= '</tbody></table>';
    if ($valida) {
        echo $tabla;
        exit();
    }
    $cmd = null;
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_pto` FROM `pto_presupuestos` WHERE `id_tipo` = 2 AND `id_vigencia` = $id_vigencia";
    $rs = $cmd->query($sql);
    $pto = $rs->fetch(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}

// Usar las clases para obtener datos
$Detalles = new Detalles();
$Nomina = new Nomina();

$datos = $Detalles->getAporteSocial($id_nomina);
$nomina = $Nomina->getRegistro($id_nomina);

// Agrupar aportes por cargo y tipo
$sumas = [];
foreach ($datos as $row) {
    $sumas[$row['cargo']][$row['tipo']] = ($sumas[$row['cargo']][$row['tipo']] ?? 0) + $row['valor'];
}

$admin = $sumas['admin'] ?? [];
$oper  = $sumas['oper'] ?? [];

// Obtener relación de rubros
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `r_admin`, `r_operativo`, `id_tipo` FROM `nom_rel_rubro` WHERE (`id_vigencia` = $id_vigencia)";
    $rs = $cmd->query($sql);
    $rubros = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}

// Obtener IDs de terceros
$idsTercero = [];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT * FROM (
                SELECT
                    `nom_empleado`.`id_empleado`
                    , `nom_liq_segsocial_empdo`.`id_eps`
                    , `nom_liq_segsocial_empdo`.`id_arl`
                    , `nom_liq_segsocial_empdo`.`id_afp`
                    , `nom_epss`.`id_tercero_api` AS `id_api_eps`
                    , `nom_arl`.`id_tercero_api` AS `id_api_arl`
                    , `nom_afp`.`id_tercero_api` AS `id_api_afp`
                FROM
                    `nom_empleado`
                    INNER JOIN `nom_liq_segsocial_empdo` 
                        ON (`nom_liq_segsocial_empdo`.`id_empleado` = `nom_empleado`.`id_empleado`)
                    INNER JOIN `nom_epss` 
                        ON (`nom_liq_segsocial_empdo`.`id_eps` = `nom_epss`.`id_eps`)
                    INNER JOIN `nom_arl` 
                        ON (`nom_liq_segsocial_empdo`.`id_arl` = `nom_arl`.`id_arl`)
                    INNER JOIN `nom_afp` 
                        ON (`nom_liq_segsocial_empdo`.`id_afp` = `nom_afp`.`id_afp`)
                WHERE `nom_liq_segsocial_empdo`.`id_nomina` = $id_nomina
            ) AS t1";
    $rs = $cmd->query($sql);
    $terceros_seg = $rs->fetchAll(PDO::FETCH_ASSOC);
    foreach ($terceros_seg as $t) {
        $idsTercero['eps'][$t['id_eps']] = $t['id_api_eps'];
        $idsTercero['arl'][$t['id_arl']] = $t['id_api_arl'];
        $idsTercero['afp'][$t['id_afp']] = $t['id_api_afp'];
    }
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}

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

$tipos_nomina = [
    'N' => 'MENSUAL',
    'PS' => 'DE PRESTACIONES SOCIALES',
    'VC' => 'DE VACACIONES',
    'PV' => 'DE PRIMA DE SERVICIOS',
    'RA' => 'DE RETROACTIVO',
    'CE' => 'DE CESANTIAS',
    'IC' => 'DE INTERESES DE CESANTIAS',
    'VS' => 'DE VACACIONES'
];

$cual = $tipos_nomina[$nomina['tipo']] ?? 'OTRAS';
$mes = $nomina['mes'] != '' ? $nomina['mes'] : '00';
$nom_mes = isset($meses[$mes]) ? 'MES DE ' . mb_strtoupper($meses[$mes]) : '';

$id_pto = $pto['id_pto'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha = $date->format('Y-m-d');
$objeto = "PAGO NOMINA PATRONAL " . $cual . " N° " . $nomina['id_nomina'] . ' ' . $nom_mes . " VIGENCIA " . $nomina['vigencia'];
$iduser = $_SESSION['id_user'];
$fecha2 = $date->format('Y-m-d H:i:s');
$cerrado = 2;

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

// Iniciar transacción
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $cmd->beginTransaction();

    // CDP - Consecutivo
    $sql = "SELECT MAX(`id_manu`) AS `id_manu` FROM `pto_cdp` WHERE (`id_pto` = $id_pto)";
    $rs = $cmd->query($sql);
    $consecutivo = $rs->fetch();
    $id_manu = !empty($consecutivo) ? $consecutivo['id_manu'] + 1 : 1;

    // Insertar CDP
    $sql = "INSERT INTO `pto_cdp` (`id_pto`, `id_manu`, `fecha`, `objeto`, `id_user_reg`, `fecha_reg`, `estado`) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $id_pto, PDO::PARAM_INT);
    $sql->bindParam(2, $id_manu, PDO::PARAM_INT);
    $sql->bindParam(3, $fec_doc, PDO::PARAM_STR);
    $sql->bindParam(4, $objeto, PDO::PARAM_STR);
    $sql->bindParam(5, $iduser, PDO::PARAM_INT);
    $sql->bindParam(6, $fecha2);
    $sql->bindParam(7, $cerrado, PDO::PARAM_INT);
    $sql->execute();
    $id_cdp = $cmd->lastInsertId();
    if (!($id_cdp > 0)) {
        throw new Exception($sql->errorInfo()[2]);
    }

    // CRP - Obtener tercero
    $sql = "SELECT `id_tercero_api` FROM `tb_terceros` WHERE `nit_tercero` = " . $_SESSION['nit_emp'];
    $rs = $cmd->query($sql);
    $tercero = $rs->fetch();
    $id_ter_api = !empty($tercero) ? $tercero['id_tercero_api'] : null;

    // CRP - Consecutivo
    $sql = "SELECT MAX(`id_manu`) AS `id_manu` FROM `pto_crp` WHERE (`id_pto` = $id_pto)";
    $rs = $cmd->query($sql);
    $consecutivo = $rs->fetch();
    $id_manu = !empty($consecutivo) ? $consecutivo['id_manu'] + 1 : 1;

    // Insertar CRP
    $sql = "INSERT INTO `pto_crp` (`id_pto`, `id_cdp`, `id_manu`, `fecha`, `objeto`, `id_user_reg`, `fecha_reg`, `estado`, `id_tercero_api`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $id_pto, PDO::PARAM_INT);
    $sql->bindParam(2, $id_cdp, PDO::PARAM_INT);
    $sql->bindParam(3, $id_manu, PDO::PARAM_INT);
    $sql->bindParam(4, $fec_doc, PDO::PARAM_STR);
    $sql->bindParam(5, $objeto, PDO::PARAM_STR);
    $sql->bindParam(6, $iduser, PDO::PARAM_INT);
    $sql->bindParam(7, $fecha2);
    $sql->bindParam(8, $cerrado, PDO::PARAM_INT);
    $sql->bindParam(9, $id_ter_api, PDO::PARAM_INT);
    $sql->execute();
    $id_crp = $cmd->lastInsertId();
    if (!($id_crp > 0)) {
        throw new Exception($sql->errorInfo()[2]);
    }

    // Preparar statements para detalle CDP y CRP
    $liberado = 0;
    $query = "INSERT INTO `pto_cdp_detalle` (`id_pto_cdp`, `id_rubro`, `valor`, `valor_liberado`) 
                VALUES (?, ?, ?, ?)";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $id_cdp, PDO::PARAM_INT);
    $query->bindParam(2, $rubro, PDO::PARAM_INT);
    $query->bindParam(3, $valor, PDO::PARAM_STR);
    $query->bindParam(4, $liberado, PDO::PARAM_STR);

    $sqly = "INSERT INTO `pto_crp_detalle` (`id_pto_crp`, `id_pto_cdp_det`, `id_tercero_api`, `valor`, `valor_liberado`) 
                VALUES (?, ?, ?, ?, ?)";
    $sqly = $cmd->prepare($sqly);
    $sqly->bindParam(1, $id_crp, PDO::PARAM_INT);
    $sqly->bindParam(2, $id_detalle_cdp, PDO::PARAM_INT);
    $sqly->bindParam(3, $id_tercero, PDO::PARAM_INT);
    $sqly->bindParam(4, $valor, PDO::PARAM_STR);
    $sqly->bindParam(5, $liberado, PDO::PARAM_STR);

    // Procesar cada tipo de rubro
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
                $valores_detalle = [];
                foreach ($datos as $row) {
                    if ($row['cargo'] == 'admin' && $row['tipo'] == $key && $row['valor'] > 0) {
                        $id_tercero_seg = $idsTercero[$key][$row['id_' . $key]] ?? null;
                        if ($id_tercero_seg) {
                            $valores_detalle[$id_tercero_seg] = ($valores_detalle[$id_tercero_seg] ?? 0) + $row['valor'];
                        }
                    }
                }

                foreach ($valores_detalle as $id_tercero => $valor) {
                    if ($valor > 0) {
                        $query->execute();
                        $id_detalle_cdp = $cmd->lastInsertId();
                        if ($id_detalle_cdp > 0) {
                            $sqly->execute();
                            if (!($cmd->lastInsertId() > 0)) {
                                throw new Exception($sqly->errorInfo()[2]);
                            }
                        } else {
                            throw new Exception($query->errorInfo()[2]);
                        }
                    }
                }
            } else {
                // Para parafiscales (caja, icbf, sena) usar el tercero configurado
                $valor = $valAdmin;
                $id_tercero = $terceros_map[$key];
                $query->execute();
                $id_detalle_cdp = $cmd->lastInsertId();
                if ($id_detalle_cdp > 0) {
                    $sqly->execute();
                    if (!($cmd->lastInsertId() > 0)) {
                        throw new Exception($sqly->errorInfo()[2]);
                    }
                } else {
                    throw new Exception($query->errorInfo()[2]);
                }
            }
        }

        // Procesar operativo
        if ($valOper > 0) {
            $rubro = $rb['r_operativo'];

            // Determinar terceros según el tipo
            if (in_array($key, ['eps', 'arl', 'afp'])) {
                // Para seguridad social, puede haber múltiples terceros
                $valores_detalle = [];
                foreach ($datos as $row) {
                    if ($row['cargo'] == 'oper' && $row['tipo'] == $key && $row['valor'] > 0) {
                        $id_tercero_seg = $idsTercero[$key][$row['id_' . $key]] ?? null;
                        if ($id_tercero_seg) {
                            $valores_detalle[$id_tercero_seg] = ($valores_detalle[$id_tercero_seg] ?? 0) + $row['valor'];
                        }
                    }
                }

                foreach ($valores_detalle as $id_tercero => $valor) {
                    if ($valor > 0) {
                        $query->execute();
                        $id_detalle_cdp = $cmd->lastInsertId();
                        if ($id_detalle_cdp > 0) {
                            $sqly->execute();
                            if (!($cmd->lastInsertId() > 0)) {
                                throw new Exception($sqly->errorInfo()[2]);
                            }
                        } else {
                            throw new Exception($query->errorInfo()[2]);
                        }
                    }
                }
            } else {
                // Para parafiscales (caja, icbf, sena) usar el tercero configurado
                $valor = $valOper;
                $id_tercero = $terceros_map[$key];
                $query->execute();
                $id_detalle_cdp = $cmd->lastInsertId();
                if ($id_detalle_cdp > 0) {
                    $sqly->execute();
                    if (!($cmd->lastInsertId() > 0)) {
                        throw new Exception($sqly->errorInfo()[2]);
                    }
                } else {
                    throw new Exception($query->errorInfo()[2]);
                }
            }
        }
    }

    // Actualizar estado de nómina
    $estado = 3;
    $sql = "UPDATE `nom_nominas` SET `planilla` = ? WHERE `id_nomina` = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $estado, PDO::PARAM_INT);
    $sql->bindParam(2, $id_nomina, PDO::PARAM_INT);
    $sql->execute();

    // Insertar relación nómina-CDP-CRP
    $query = "INSERT INTO `nom_nomina_pto_ctb_tes` (`id_nomina`, `cdp`, `crp`, `tipo`) 
                VALUES (?, ?, ?, ?)";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $id_nomina, PDO::PARAM_INT);
    $query->bindParam(2, $id_cdp, PDO::PARAM_INT);
    $query->bindParam(3, $id_crp, PDO::PARAM_INT);
    $query->bindParam(4, $tipo_nomina, PDO::PARAM_STR);
    $query->execute();
    if (!($cmd->lastInsertId() > 0)) {
        throw new Exception($query->errorInfo()[2]);
    }

    $cmd->commit();
    $cmd = null;
    echo 'ok';
} catch (Exception $e) {
    if ($cmd) {
        $cmd->rollBack();
    }
    echo 'Error: ' . $e->getMessage();
}
