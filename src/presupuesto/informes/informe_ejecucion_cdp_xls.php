<?php
session_start();
set_time_limit(5600);
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}

include '../../../config/autoloader.php';

$cmd = \Config\Clases\Conexion::getConexion();
$vigencia = $_SESSION['vigencia'] ?? date('Y');

// Filtros recibidos por POST
$id_pto_presupuestos = isset($_POST['id_ejec']) ? intval($_POST['id_ejec']) : 0;
$id_manu = isset($_POST['id_manu']) ? trim($_POST['id_manu']) : '';
$option = isset($_POST['option']) ? intval($_POST['option']) : 0;
$fec_ini = isset($_POST['fec_ini']) ? trim($_POST['fec_ini']) : '';
$fec_fin = isset($_POST['fec_fin']) ? trim($_POST['fec_fin']) : '';
$objeto = isset($_POST['objeto']) ? trim($_POST['objeto']) : '';
$estado = isset($_POST['estado']) ? strval($_POST['estado']) : '-1';
$search_value = isset($_POST['search']) ? trim($_POST['search']) : '';
$anulados = isset($_POST['anulados']) ? intval($_POST['anulados']) : 0;

// Búsqueda general
if (!empty($search_value)) {
    $search_safe = addslashes($search_value);
    $buscar = " AND (pto_cdp.id_manu LIKE '%$search_safe%' OR pto_cdp.objeto LIKE '%$search_safe%' OR pto_cdp.fecha LIKE '%$search_safe%')";
} else {
    $buscar = '';
}

$buscar .= " AND pto_cdp.estado >= 0";

// Filtros específicos
$andwhere = '';
$filtros_aplicados = [];

if (!empty($id_manu)) {
    $id_manu_safe = addslashes($id_manu);
    if ($option == 0) {
        $andwhere .= " AND pto_cdp.id_manu LIKE '%$id_manu_safe%'";
        $filtros_aplicados[] = "No. CDP contiene: " . htmlspecialchars($id_manu);
    } else {
        $andwhere .= " AND pto_cdp.id_manu = '$id_manu_safe'";
        $filtros_aplicados[] = "No. CDP exacto: " . htmlspecialchars($id_manu);
    }
}

if (!empty($fec_ini) && !empty($fec_fin)) {
    $fec_ini_safe = addslashes($fec_ini);
    $fec_fin_safe = addslashes($fec_fin);
    $andwhere .= " AND pto_cdp.fecha BETWEEN '$fec_ini_safe' AND '$fec_fin_safe'";
    $filtros_aplicados[] = "Fecha: $fec_ini a $fec_fin";
} elseif (!empty($fec_ini)) {
    $fec_ini_safe = addslashes($fec_ini);
    $andwhere .= " AND pto_cdp.fecha >= '$fec_ini_safe'";
    $filtros_aplicados[] = "Fecha desde: $fec_ini";
} elseif (!empty($fec_fin)) {
    $fec_fin_safe = addslashes($fec_fin);
    $andwhere .= " AND pto_cdp.fecha <= '$fec_fin_safe'";
    $filtros_aplicados[] = "Fecha hasta: $fec_fin";
}

if (!empty($objeto)) {
    $objeto_safe = addslashes($objeto);
    $andwhere .= " AND pto_cdp.objeto LIKE '%$objeto_safe%'";
    $filtros_aplicados[] = "Objeto: " . htmlspecialchars($objeto);
}

if (strlen($estado) > 0) {
    if ($estado == "-1") {
        $andwhere .= " AND pto_cdp.estado >= -1";
    } else {
        $estado_int = intval($estado);
        $andwhere .= " AND pto_cdp.estado = $estado_int";
        $nom_est = $estado_int == 1 ? 'Abierto' : ($estado_int == 2 ? 'Cerrado' : ($estado_int == 0 ? 'Anulado' : $estado_int));
        $filtros_aplicados[] = "Estado: $nom_est";
    }
}

// Consultar nombre del presupuesto
$nombrePto = 'EJECUCION PRESUPUESTAL';
try {
    $sql = "SELECT `nombre` FROM `pto_presupuestos` WHERE `id_pto` = $id_pto_presupuestos";
    $rs = $cmd->query($sql);
    if ($rs) {
        $nomPresupuestos = $rs ? $rs->fetch(PDO::FETCH_ASSOC) : null;
        if (!empty($nomPresupuestos) && !empty($nomPresupuestos['nombre'])) {
            $nombrePto = $nomPresupuestos['nombre'];
        }
    }
} catch (PDOException $e) {
    // Si falla dejamos el valor por defecto
}

// Consultar datos de la empresa
$empresa = ['nombre' => '', 'nit' => '', 'dig_ver' => ''];
try {
    $sql = "SELECT `razon_social_ips` AS `nombre`, `nit_ips` AS `nit`, `dv` AS `dig_ver` FROM `tb_datos_ips`";
    $rs = $cmd->query($sql);
    if ($rs) {
        $emp = $rs->fetch(PDO::FETCH_ASSOC);
        if ($emp) {
            $empresa = $emp;
        }
    }
} catch (PDOException $e) {
    // Si falla dejamos valores vacíos
}

// Consultar listado de CDPs
try {
    $sql = "SELECT
                `pto_cdp`.`id_pto_cdp`
                , `pto_cdp`.`id_manu`
                , `pto_cdp`.`fecha`
                , `pto_cdp`.`objeto`
                , `pto_cdp`.`estado`
                , IFNULL(`cdp`.`val_cdp`,0) AS `val_cdp`
                , IFNULL(`cdp`.`val_lib_cdp`,0) AS `val_lib_cdp`
                , IFNULL(`crp`.`val_crp`,0) AS `val_crp`
                , IFNULL(`crp`.`val_lib_crp`,0) AS `val_lib_crp`
            FROM `pto_cdp`
            LEFT JOIN 
                (SELECT
                    `id_pto_cdp`
                    , SUM(`valor`) AS `val_cdp`
                    , SUM(`valor_liberado`) AS `val_lib_cdp`
                FROM
                    `pto_cdp_detalle`
                GROUP BY `id_pto_cdp`) AS `cdp`
                ON (`pto_cdp`.`id_pto_cdp` = `cdp`.`id_pto_cdp`)
            LEFT JOIN
                (SELECT
                    `pto_cdp_detalle`.`id_pto_cdp`
                    , SUM(`pto_crp_detalle`.`valor`) AS `val_crp`
                    , SUM(`pto_crp_detalle`.`valor_liberado`) AS `val_lib_crp`
                FROM
                    `pto_crp_detalle`
                    INNER JOIN `pto_crp` 
                    ON (`pto_crp_detalle`.`id_pto_crp` = `pto_crp`.`id_pto_crp`)
                    INNER JOIN `pto_cdp_detalle` 
                    ON (`pto_crp_detalle`.`id_pto_cdp_det` = `pto_cdp_detalle`.`id_pto_cdp_det`)
                WHERE (`pto_crp`.`estado` > 0)
                GROUP BY `pto_cdp_detalle`.`id_pto_cdp`) AS `crp`
                ON (`pto_cdp`.`id_pto_cdp` = `crp`.`id_pto_cdp`)
            WHERE `pto_cdp`.`id_pto` = $id_pto_presupuestos $buscar $andwhere
            ORDER BY `pto_cdp`.`id_manu` DESC";
    $rs = $cmd->query($sql);
    $listappto = $rs ? $rs->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}

$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$filename = "EJECUCION_CDP_" . $date->format('Ymd_His') . ".xls";

header("Content-type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Ejecución CDP</title>
    <style>
        .texto {
            mso-number-format: "\@";
            text-align: left;
        }
        .texto-center {
            mso-number-format: "\@";
            text-align: center;
        }
        .numero {
            mso-number-format: "#,##0.00";
            text-align: right;
        }
        .resaltar:nth-child(even) {
            background-color: #F8F9FA;
        }
        .resaltar:nth-child(odd) {
            background-color: #FFFFFF;
        }
        th {
            background-color: #2E5B88;
            color: #FFFFFF;
            border: 1px solid #1C3B5E;
            padding: 6px;
            text-align: center;
        }
        td {
            border: 1px solid #D9D9D9;
            padding: 5px;
        }
    </style>
</head>
<body>
    <table style="border-collapse: collapse; width: 100%;">
        <thead>
            <tr>
                <th colspan="8" style="background-color: #FFFFFF; color: #000000; border: none; font-size: 13pt; text-align: center;">
                    <?= mb_strtoupper($empresa['nombre']) ?>
                </th>
            </tr>
            <tr>
                <th colspan="8" style="background-color: #FFFFFF; color: #333333; border: none; font-size: 10pt; text-align: center;">
                    NIT: <?= $empresa['nit'] . ($empresa['dig_ver'] !== '' ? '-' . $empresa['dig_ver'] : '') ?>
                </th>
            </tr>
            <tr>
                <th colspan="8" style="background-color: #FFFFFF; color: #000000; border: none; font-size: 11pt; font-weight: bold; text-align: center;">
                    EJECUCIÓN <?= mb_strtoupper($nombrePto) ?> - DISPONIBILIDADES (CDP)
                </th>
            </tr>
            <tr>
                <th colspan="8" style="background-color: #FFFFFF; color: #555555; border: none; font-size: 9pt; text-align: center;">
                    Vigencia: <?= $vigencia ?> | Fecha de reporte: <?= $date->format('Y-m-d H:i:s') ?>
                </th>
            </tr>
            <?php if (!empty($filtros_aplicados)): ?>
            <tr>
                <th colspan="8" style="background-color: #FFFFFF; color: #198754; border: none; font-size: 9pt; text-align: center;">
                    Filtros: <?= implode(' | ', $filtros_aplicados) ?>
                </th>
            </tr>
            <?php endif; ?>
            <tr>
                <th colspan="8" style="background-color: #FFFFFF; border: none; height: 10px;"></th>
            </tr>
            <tr>
                <th>Número</th>
                <th>Fecha</th>
                <th>Objeto</th>
                <th>Valor CDP</th>
                <th>Valor Liberado</th>
                <th>Valor Registrado</th>
                <th>X Registrar</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_cdp = 0;
            $total_lib = 0;
            $total_crp = 0;
            $total_xreg = 0;

            if (!empty($listappto)) {
                foreach ($listappto as $lp) {
                    $fecha = date('Y-m-d', strtotime($lp['fecha']));
                    $val_cdp = floatval($lp['val_cdp']);
                    $val_lib = floatval($lp['val_lib_cdp']);
                    $val_crp = floatval($lp['val_crp']) - floatval($lp['val_lib_crp']);
                    $val_cdp_neto = $val_cdp - $val_lib;
                    $cxregistrar = $val_cdp_neto - $val_crp;

                    if ($lp['estado'] == 0) {
                        $estado_nombre = 'Anulado';
                        $cxregistrar = 0;
                    } elseif ($lp['estado'] == 1) {
                        $estado_nombre = 'Abierto';
                    } elseif ($lp['estado'] == 2) {
                        $estado_nombre = 'Cerrado';
                    } else {
                        $estado_nombre = '';
                    }

                    // Acumular totales de los que no están anulados
                    if ($lp['estado'] != 0) {
                        $total_cdp += $val_cdp;
                        $total_lib += $val_lib;
                        $total_crp += $val_crp;
                        $total_xreg += $cxregistrar;
                    }
                    ?>
                    <tr class="resaltar">
                        <td class="texto-center"><?= $lp['id_manu'] ?></td>
                        <td class="texto-center" style="white-space: nowrap;"><?= $fecha ?></td>
                        <td class="texto"><?= htmlspecialchars($lp['objeto'] ?? '') ?></td>
                        <td class="numero"><?= number_format($val_cdp, 2, '.', '') ?></td>
                        <td class="numero"><?= number_format($val_lib, 2, '.', '') ?></td>
                        <td class="numero"><?= number_format($val_crp, 2, '.', '') ?></td>
                        <td class="numero"><?= number_format($cxregistrar, 2, '.', '') ?></td>
                        <td class="texto-center"><?= $estado_nombre ?></td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="8" style="text-align: center; padding: 15px;">No se encontraron registros con los filtros aplicados.</td></tr>';
            }
            ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #E2E3E5; font-weight: bold;">
                <td colspan="3" style="text-align: right; padding: 6px;">TOTALES:</td>
                <td class="numero"><?= number_format($total_cdp, 2, '.', '') ?></td>
                <td class="numero"><?= number_format($total_lib, 2, '.', '') ?></td>
                <td class="numero"><?= number_format($total_crp, 2, '.', '') ?></td>
                <td class="numero"><?= number_format($total_xreg, 2, '.', '') ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
