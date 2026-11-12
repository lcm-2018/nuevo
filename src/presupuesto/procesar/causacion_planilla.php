<?php

use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Nomina\Liquidado\Php\Clases\Detalles;
use Config\Clases\Logs;

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
    $has_dpto_caja = false;
    try {
        $rsCol = $cmd->query("SHOW COLUMNS FROM `nom_parafiscales` LIKE 'dpto_caja'");
        $has_dpto_caja = ($rsCol && $rsCol->fetch() !== false);
        if ($rsCol) {
            $rsCol->closeCursor();
        }
    } catch (Exception $e) {
        $has_dpto_caja = false;
    }

    $col_dpto = $has_dpto_caja ? ', `dpto_caja`' : '';
    $sql = "SELECT
                `id_parafiscal`,`id_tercero_api`,`tipo`{$col_dpto}
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

// Filtrar cajas de compensación configuradas
$cajas_config = array_values(array_filter($parafiscales, function ($p) {
    return $p['tipo'] === 'CAJA';
}));

if (empty($cajas_config)) {
    exit('No se ha configurado el parafiscal CAJA DE COMPENSACION');
}
$num_cajas = $has_dpto_caja ? count($cajas_config) : 1;

// Validar saldo en rubros
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `nom_cdp_empleados`.`rubro`
                , SUM(`nom_cdp_empleados`.`valor`) AS `valor`
                , `pto_cargue`.`cod_pptal`
            FROM
                `nom_cdp_empleados`
                INNER JOIN `pto_cargue` 
                    ON (`nom_cdp_empleados`.`rubro` = `pto_cargue`.`id_cargue`)
            WHERE (`nom_cdp_empleados`.`id_nomina` = $id_nomina AND `nom_cdp_empleados`.`tipo` = 'PL')
            GROUP BY `nom_cdp_empleados`.`rubro`, `pto_cargue`.`cod_pptal`";
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
                        <th class="bg-sofia">Rubro</th>
                        <th class="bg-sofia">Valor</th>
                        <th class="bg-sofia">Saldo</th>
                        <th class="bg-sofia">Estado</th>
                    </tr>
                </thead>
                <tbody>';
    foreach ($valxrubro as $vr) {
        $rubro = $vr['rubro'];
        $valor = $vr['valor'];
        $cod_rubro = $vr['cod_pptal'];
        $respuesta = SaldoRubro($cmd, $rubro, $fec_doc, 0);
        $saldo = $respuesta['valor_aprobado'] - $respuesta['debito_cdp'] + $respuesta['credito_cdp'] + $respuesta['debito_mod'] - $respuesta['credito_mod'];
        $estado = $saldo >= $valor ? '<span class="badge rounded-pill text-bg-success">Disponible</span>' : '<span class="badge rounded-pill text-bg-danger">Sin Saldo</span>';
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
    $sql = "SELECT `r_admin`, `r_operativo`, `id_tipo` FROM `nom_rel_rubro` WHERE (`id_vigencia` = $id_vigencia) ORDER BY `id_tipo` ASC";
    $rs = $cmd->query($sql);
    $rubros = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}

// Obtener IDs de terceros API para cada EPS, ARL y AFP
$idsTercero = [];
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    // Extraer IDs únicos de EPS, ARL y AFP de los datos
    $ids_eps = array_unique(array_column(array_filter($datos, function ($d) {
        return $d['tipo'] == 'eps';
    }), 'id'));
    $ids_arl = array_unique(array_column(array_filter($datos, function ($d) {
        return $d['tipo'] == 'arl';
    }), 'id'));
    $ids_afp = array_unique(array_column(array_filter($datos, function ($d) {
        return $d['tipo'] == 'afp';
    }), 'id'));
    // Obtener terceros API de EPS (id_tipo = 1)
    if (!empty($ids_eps) && is_countable($ids_eps)) {
        $placeholders_eps = implode(',', array_fill(0, count($ids_eps), '?'));
        $sql = "SELECT `id_tn`, `id_tercero_api` FROM `nom_terceros` WHERE `id_tn` IN ($placeholders_eps) AND `id_tipo` = 1";
        $stmt = $cmd->prepare($sql);
        $stmt->execute(array_values($ids_eps));
        $eps_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($eps_data as $eps) {
            $idsTercero['eps'][$eps['id_tn']] = $eps['id_tercero_api'];
        }
    }

    // Obtener terceros API de ARL (id_tipo = 3)
    if (!empty($ids_arl) && is_countable($ids_arl)) {
        $placeholders_arl = implode(',', array_fill(0, count($ids_arl), '?'));
        $sql = "SELECT `id_tn`, `id_tercero_api` FROM `nom_terceros` WHERE `id_tn` IN ($placeholders_arl) AND `id_tipo` = 3";
        $stmt = $cmd->prepare($sql);
        $stmt->execute(array_values($ids_arl));
        $arl_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($arl_data as $arl) {
            $idsTercero['arl'][$arl['id_tn']] = $arl['id_tercero_api'];
        }
    }

    // Obtener terceros API de AFP (id_tipo = 2)
    if (!empty($ids_afp) && is_countable($ids_afp)) {
        $placeholders_afp = implode(',', array_fill(0, count($ids_afp), '?'));
        $sql = "SELECT `id_tn`, `id_tercero_api` FROM `nom_terceros` WHERE `id_tn` IN ($placeholders_afp) AND `id_tipo` = 2";
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

// Resolver detalle de aportes a Caja de Compensación (Monocaja vs Multicaja)
$cajas_detalle = ['admin' => [], 'oper' => []];

if ($num_cajas === 1) {
    // Condición 3: Si solo hay una caja, se toma la disponible sin importar el departamento
    $id_caja_unica = $cajas_config[0]['id_tercero_api'];
    if (($admin['caja'] ?? 0) > 0) {
        $cajas_detalle['admin'][$id_caja_unica] = $admin['caja'];
    }
    if (($oper['caja'] ?? 0) > 0) {
        $cajas_detalle['oper'][$id_caja_unica] = $oper['caja'];
    }
} else {
    // Condición 4: Si hay más de una caja, relacionar empleado -> sede -> municipio -> departamento -> dpto_caja
    try {
        $cmd = \Config\Clases\Conexion::getConexion();
        $sql = "SELECT
                    IF(`nca`.`tipo_cargo` = 1, 'admin', 'oper') AS `cargo`,
                    `np`.`id_tercero_api`,
                    `m`.`id_departamento`,
                    SUM(`nlp`.`val_comfam`) AS `valor`
                FROM `nom_liq_parafiscales` AS `nlp`
                INNER JOIN (
                    SELECT `id_empleado`, MAX(`id_contrato`) AS `id_contrato`
                    FROM `nom_liq_salario`
                    WHERE `id_nomina` = ? AND `estado` = 1
                    GROUP BY `id_empleado`
                ) AS `sal` ON `sal`.`id_empleado` = `nlp`.`id_empleado`
                INNER JOIN `nom_contratos_empleados` AS `nce` ON `nce`.`id_contrato_emp` = `sal`.`id_contrato`
                INNER JOIN `nom_cargo_empleado` AS `nca` ON `nca`.`id_cargo` = `nce`.`id_cargo`
                INNER JOIN `nom_empleado` AS `ne` ON `ne`.`id_empleado` = `nlp`.`id_empleado`
                LEFT JOIN `tb_sedes` AS `s` ON `s`.`id_sede` = `ne`.`sede_emp`
                LEFT JOIN `tb_municipios` AS `m` ON `m`.`id_municipio` = `s`.`id_municipio`
                LEFT JOIN `nom_parafiscales` AS `np` ON (`np`.`tipo` = 'CAJA' AND `np`.`dpto_caja` = `m`.`id_departamento`)
                WHERE `nlp`.`id_nomina` = ? 
                    AND `nlp`.`estado` = 1 
                    AND `nlp`.`val_comfam` > 0
                GROUP BY `nca`.`tipo_cargo`, `np`.`id_tercero_api`, `m`.`id_departamento`";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([$id_nomina, $id_nomina]);
        $cajas_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        $cmd = null;

        foreach ($cajas_rows as $cr) {
            if (empty($cr['id_tercero_api'])) {
                $dptoId = !empty($cr['id_departamento']) ? $cr['id_departamento'] : 'sin sede/municipio asignado';
                exit("Existen empleados con aporte a caja de compensación cuyo departamento (ID: {$dptoId}) no tiene una Caja de Compensación configurada en parafiscales.");
            }
            $cajas_detalle[$cr['cargo']][$cr['id_tercero_api']] = ($cajas_detalle[$cr['cargo']][$cr['id_tercero_api']] ?? 0) + $cr['valor'];
        }
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        exit();
    }
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

$mes = $nomina['mes'] != '' ? $nomina['mes'] : '00';
$nom_mes = isset($meses[$mes]) ? 'MES DE ' . mb_strtoupper($meses[$mes]) : '';

$id_pto = $pto['id_pto'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha = $date->format('Y-m-d');
$objeto = $nomina['descripcion'] . ' (PATRONAL).';
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
    $consecutivo = $rs ? $rs->fetch() : null;
    $id_manu = !empty($consecutivo) ? ($consecutivo['id_manu'] + 1) : 1;

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
    Logs::guardaLog("INSERT INTO `pto_cdp` (`id_pto`, `id_manu`, `fecha`, `objeto`, `id_user_reg`, `fecha_reg`, `estado`) VALUES ($id_pto, $id_manu, '$fec_doc', '$objeto', $iduser, '$fecha2', $cerrado)");

    // CRP - Obtener tercero
    $sql = "SELECT `id_tercero_api` FROM `tb_terceros` WHERE `nit_tercero` = " . $_SESSION['nit_emp'];
    $rs = $cmd->query($sql);
    $tercero = $rs ? $rs->fetch() : null;
    $id_ter_api = !empty($tercero) ? ($tercero['id_tercero_api'] ?? null) : null;

    // CRP - Consecutivo
    $sql = "SELECT MAX(`id_manu`) AS `id_manu` FROM `pto_crp` WHERE (`id_pto` = $id_pto)";
    $rs = $cmd->query($sql);
    $consecutivo = $rs ? $rs->fetch() : null;
    $id_manu = !empty($consecutivo) ? ($consecutivo['id_manu'] + 1) : 1;

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
    Logs::guardaLog("INSERT INTO `pto_crp` (`id_pto`, `id_cdp`, `id_manu`, `fecha`, `objeto`, `id_user_reg`, `fecha_reg`, `estado`, `id_tercero_api`) VALUES ($id_pto, $id_cdp, $id_manu, '$fec_doc', '$objeto', $iduser, '$fecha2', $cerrado, " . ($id_ter_api ?? 'NULL') . ")");

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
            $valores_detalle = [];

            if ($key === 'caja') {
                $valores_detalle = $cajas_detalle['admin'] ?? [];
            } elseif (in_array($key, ['eps', 'arl', 'afp'])) {
                foreach ($datos as $row) {
                    if ($row['cargo'] == 'admin' && $row['tipo'] == $key && $row['valor'] > 0) {
                        $id_tercero_seg = $idsTercero[$key][$row['id']] ?? null;
                        if ($id_tercero_seg) {
                            $valores_detalle[$id_tercero_seg] = ($valores_detalle[$id_tercero_seg] ?? 0) + $row['valor'];
                        }
                    }
                }
            } else {
                $id_tercero_fijo = $terceros_map[$key] ?? null;
                if ($id_tercero_fijo) {
                    $valores_detalle[$id_tercero_fijo] = $valAdmin;
                }
            }

            foreach ($valores_detalle as $id_tercero => $valor) {
                if ($valor > 0) {
                    $query->execute();
                    $id_detalle_cdp = $cmd->lastInsertId();
                    if ($id_detalle_cdp > 0) {
                        Logs::guardaLog("INSERT INTO `pto_cdp_detalle` (`id_pto_cdp`, `id_rubro`, `valor`, `valor_liberado`) VALUES ($id_cdp, $rubro, $valor, $liberado)");
                        $sqly->execute();
                        if (!($cmd->lastInsertId() > 0)) {
                            throw new Exception($sqly->errorInfo()[2]);
                        }
                        Logs::guardaLog("INSERT INTO `pto_crp_detalle` (`id_pto_crp`, `id_pto_cdp_det`, `id_tercero_api`, `valor`, `valor_liberado`) VALUES ($id_crp, $id_detalle_cdp, $id_tercero, $valor, $liberado)");
                    } else {
                        throw new Exception($query->errorInfo()[2]);
                    }
                }
            }
        }

        // Procesar operativo
        if ($valOper > 0) {
            $rubro = $rb['r_operativo'];
            $valores_detalle = [];

            if ($key === 'caja') {
                $valores_detalle = $cajas_detalle['oper'] ?? [];
            } elseif (in_array($key, ['eps', 'arl', 'afp'])) {
                foreach ($datos as $row) {
                    if ($row['cargo'] == 'oper' && $row['tipo'] == $key && $row['valor'] > 0) {
                        $id_tercero_seg = $idsTercero[$key][$row['id']] ?? null;
                        if ($id_tercero_seg) {
                            $valores_detalle[$id_tercero_seg] = ($valores_detalle[$id_tercero_seg] ?? 0) + $row['valor'];
                        }
                    }
                }
            } else {
                $id_tercero_paraf = $terceros_map[$key] ?? null;
                if ($id_tercero_paraf) {
                    $valores_detalle[$id_tercero_paraf] = $valOper;
                }
            }

            foreach ($valores_detalle as $id_tercero => $valor) {
                if ($valor > 0) {
                    $query->execute();
                    $id_detalle_cdp = $cmd->lastInsertId();
                    if ($id_detalle_cdp > 0) {
                        Logs::guardaLog("INSERT INTO `pto_cdp_detalle` (`id_pto_cdp`, `id_rubro`, `valor`, `valor_liberado`) VALUES ($id_cdp, $rubro, $valor, $liberado)");
                        $sqly->execute();
                        if (!($cmd->lastInsertId() > 0)) {
                            throw new Exception($sqly->errorInfo()[2]);
                        }
                        Logs::guardaLog("INSERT INTO `pto_crp_detalle` (`id_pto_crp`, `id_pto_cdp_det`, `id_tercero_api`, `valor`, `valor_liberado`) VALUES ($id_crp, $id_detalle_cdp, $id_tercero, $valor, $liberado)");
                    } else {
                        throw new Exception($query->errorInfo()[2]);
                    }
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
    if ($sql->rowCount() > 0) Logs::guardaLog("UPDATE `nom_nominas` SET `planilla` = $estado WHERE `id_nomina` = $id_nomina");

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
    Logs::guardaLog("INSERT INTO `nom_nomina_pto_ctb_tes` (`id_nomina`, `cdp`, `crp`, `tipo`) VALUES ($id_nomina, $id_cdp, $id_crp, '$tipo_nomina')");

    $cmd->commit();
    $cmd = null;
    echo 'ok';
} catch (Exception $e) {
    if ($cmd) {
        $cmd->rollBack();
    }
    echo 'Error: ' . $e->getMessage();
}
