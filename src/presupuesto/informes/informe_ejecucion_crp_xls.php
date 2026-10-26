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
$id_manucdp = isset($_POST['id_manucdp']) ? trim($_POST['id_manucdp']) : '';
$fec_ini = isset($_POST['fec_ini']) ? trim($_POST['fec_ini']) : '';
$fec_fin = isset($_POST['fec_fin']) ? trim($_POST['fec_fin']) : '';
$contrato = isset($_POST['contrato']) ? trim($_POST['contrato']) : '';
$ccnit = isset($_POST['ccnit']) ? trim($_POST['ccnit']) : '';
$tercero = isset($_POST['tercero']) ? trim($_POST['tercero']) : '';
$estado = isset($_POST['estado']) ? strval($_POST['estado']) : '-1';
$search_value = isset($_POST['search']) ? trim($_POST['search']) : '';
$anulados = isset($_POST['anulados']) ? intval($_POST['anulados']) : 0;

// Búsqueda general
if (!empty($search_value)) {
    $search_safe = addslashes($search_value);
    $buscar = " AND (`pto_crp`.`id_manu` LIKE '%$search_safe%' OR `detalle`.`id_cdp` LIKE '%$search_safe%' OR `pto_crp`.`objeto` LIKE '%$search_safe%' OR `pto_crp`.`fecha` LIKE '%$search_safe%' OR `pto_crp`.`num_contrato` LIKE '%$search_safe%' OR `tb_terceros`.`nom_tercero` LIKE '%$search_safe%' OR `tb_terceros`.`nit_tercero` LIKE '%$search_safe%')";
} else {
    $buscar = '';
}

$buscar .= " AND `pto_crp`.`estado` >= 0";

// Filtros específicos
$andwhere = '';
$filtros_aplicados = [];

if (!empty($id_manu)) {
    $id_manu_safe = addslashes($id_manu);
    if ($option == 0) {
        $andwhere .= " AND pto_crp.id_manu LIKE '%$id_manu_safe%'";
        $filtros_aplicados[] = "No. CRP contiene: " . htmlspecialchars($id_manu);
    } else {
        $andwhere .= " AND pto_crp.id_manu = '$id_manu_safe'";
        $filtros_aplicados[] = "No. CRP exacto: " . htmlspecialchars($id_manu);
    }
}

if (!empty($id_manucdp)) {
    $id_cdp_safe = addslashes($id_manucdp);
    $andwhere .= " AND detalle.id_cdp LIKE '%$id_cdp_safe%'";
    $filtros_aplicados[] = "No. CDP: " . htmlspecialchars($id_manucdp);
}

if (!empty($fec_ini) && !empty($fec_fin)) {
    $fec_ini_safe = addslashes($fec_ini);
    $fec_fin_safe = addslashes($fec_fin);
    $andwhere .= " AND pto_crp.fecha BETWEEN '$fec_ini_safe' AND '$fec_fin_safe'";
    $filtros_aplicados[] = "Fecha: $fec_ini a $fec_fin";
} elseif (!empty($fec_ini)) {
    $fec_ini_safe = addslashes($fec_ini);
    $andwhere .= " AND pto_crp.fecha >= '$fec_ini_safe'";
    $filtros_aplicados[] = "Fecha desde: $fec_ini";
} elseif (!empty($fec_fin)) {
    $fec_fin_safe = addslashes($fec_fin);
    $andwhere .= " AND pto_crp.fecha <= '$fec_fin_safe'";
    $filtros_aplicados[] = "Fecha hasta: $fec_fin";
}

if (!empty($contrato)) {
    $contrato_safe = addslashes($contrato);
    $andwhere .= " AND pto_crp.num_contrato LIKE '%$contrato_safe%'";
    $filtros_aplicados[] = "Contrato: " . htmlspecialchars($contrato);
}

if (!empty($ccnit)) {
    $ccnit_safe = addslashes($ccnit);
    $andwhere .= " AND tb_terceros.nit_tercero LIKE '%$ccnit_safe%'";
    $filtros_aplicados[] = "CC/Nit: " . htmlspecialchars($ccnit);
}

if (!empty($tercero)) {
    $tercero_safe = addslashes($tercero);
    $andwhere .= " AND tb_terceros.nom_tercero LIKE '%$tercero_safe%'";
    $filtros_aplicados[] = "Tercero: " . htmlspecialchars($tercero);
}

if (strlen($estado) > 0) {
    if ($estado == "-1") {
        $andwhere .= " AND pto_crp.estado >= -1";
    } else {
        $estado_int = intval($estado);
        $andwhere .= " AND pto_crp.estado = $estado_int";
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

// Consultar listado de CRPs
try {
    $sql = "SELECT
                `pto_crp`.`id_pto_crp`
                , `pto_crp`.`id_pto`
                , `pto_crp`.`fecha`
                , `pto_crp`.`id_manu`
                , `pto_crp`.`id_tercero_api`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
                , `pto_crp`.`objeto`
                , `pto_crp`.`num_contrato`
                , `pto_crp`.`estado`
                , IFNULL(`detalle`.`debito`, 0) AS `debito`
                , IFNULL(`detalle`.`credito`, 0) AS `credito`
                , `detalle`.`id_cdp`
                , IFNULL(`cop`.`saldo`, 0) AS `saldo`
            FROM
                `pto_crp`
            LEFT JOIN 
                (SELECT
                    `pto_crp_detalle`.`id_pto_crp`
                    , SUM(`pto_crp_detalle`.`valor`) AS `debito`
                    , SUM(`pto_crp_detalle`.`valor_liberado`) AS `credito`
                    , `pto_cdp`.`id_manu` AS `id_cdp`
                FROM
                    `pto_crp_detalle`
                    LEFT JOIN `pto_cdp_detalle` 
                        ON (`pto_crp_detalle`.`id_pto_cdp_det` = `pto_cdp_detalle`.`id_pto_cdp_det`)
                    LEFT JOIN `pto_cdp` 
                        ON (`pto_cdp_detalle`.`id_pto_cdp` = `pto_cdp`.`id_pto_cdp`)  
                GROUP BY `pto_crp_detalle`.`id_pto_crp`) AS `detalle`
                ON (`pto_crp`.`id_pto_crp` = `detalle`.`id_pto_crp`)
            LEFT JOIN 
                (SELECT
                    SUM(`pto_cop_detalle`.`valor`) - SUM(`pto_cop_detalle`.`valor_liberado`) AS `saldo`
                    , `pto_crp_detalle`.`id_pto_crp`
                FROM
                    `pto_cop_detalle`
                    INNER JOIN `pto_crp_detalle` 
                        ON (`pto_cop_detalle`.`id_pto_crp_det` = `pto_crp_detalle`.`id_pto_crp_det`)
                    INNER JOIN `ctb_doc` 
                        ON (`pto_cop_detalle`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                    INNER JOIN `ctb_fuente` 
                            ON (`ctb_doc`.`id_tipo_doc` = `ctb_fuente`.`id_doc_fuente`)
                WHERE (`ctb_doc`.`estado` > 0 AND `ctb_fuente`.`cod` <> 'CXPA' )
                GROUP BY `pto_crp_detalle`.`id_pto_crp`) AS `cop`
                ON (`pto_crp`.`id_pto_crp` = `cop`.`id_pto_crp`)
            LEFT JOIN `tb_terceros`
                ON (`pto_crp`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
            WHERE (`id_pto` = $id_pto_presupuestos) $buscar $andwhere
            ORDER BY `id_manu` DESC";
    $rs = $cmd->query($sql);
    $listappto = $rs ? $rs->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}

$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$filename = "EJECUCION_CRP_" . $date->format('Ymd_His') . ".xls";

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
    <title>Ejecución CRP</title>
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
                <th colspan="11" style="background-color: #FFFFFF; color: #000000; border: none; font-size: 13pt; text-align: center;">
                    <?= mb_strtoupper($empresa['nombre']) ?>
                </th>
            </tr>
            <tr>
                <th colspan="11" style="background-color: #FFFFFF; color: #333333; border: none; font-size: 10pt; text-align: center;">
                    NIT: <?= $empresa['nit'] . ($empresa['dig_ver'] !== '' ? '-' . $empresa['dig_ver'] : '') ?>
                </th>
            </tr>
            <tr>
                <th colspan="11" style="background-color: #FFFFFF; color: #000000; border: none; font-size: 11pt; font-weight: bold; text-align: center;">
                    EJECUCIÓN <?= mb_strtoupper($nombrePto) ?> - REGISTROS PRESUPUESTALES (CRP)
                </th>
            </tr>
            <tr>
                <th colspan="11" style="background-color: #FFFFFF; color: #555555; border: none; font-size: 9pt; text-align: center;">
                    Vigencia: <?= $vigencia ?> | Fecha de reporte: <?= $date->format('Y-m-d H:i:s') ?>
                </th>
            </tr>
            <?php if (!empty($filtros_aplicados)): ?>
            <tr>
                <th colspan="11" style="background-color: #FFFFFF; color: #198754; border: none; font-size: 9pt; text-align: center;">
                    Filtros: <?= implode(' | ', $filtros_aplicados) ?>
                </th>
            </tr>
            <?php endif; ?>
            <tr>
                <th colspan="11" style="background-color: #FFFFFF; border: none; height: 10px;"></th>
            </tr>
            <tr>
                <th>Número</th>
                <th>CDP</th>
                <th>Fecha</th>
                <th>Contrato</th>
                <th>CC / Nit</th>
                <th>Tercero</th>
                <th>Objeto</th>
                <th>Valor CRP</th>
                <th>Valor Liberado</th>
                <th>Valor Neto</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_debito = 0;
            $total_credito = 0;
            $total_neto = 0;

            if (!empty($listappto)) {
                foreach ($listappto as $lp) {
                    $fecha = date('Y-m-d', strtotime($lp['fecha']));
                    $val_debito = floatval($lp['debito']);
                    $val_credito = floatval($lp['credito']);
                    $val_neto = $val_debito - $val_credito;

                    $tercero_nombre = $lp['nom_tercero'];
                    $ccnit_val = $lp['nit_tercero'];
                    if ($lp['id_tercero_api'] == 0) {
                        $tercero_nombre = 'NOMINA DE EMPLEADOS';
                    }

                    if ($lp['estado'] == 0) {
                        $estado_nombre = 'Anulado';
                    } elseif ($lp['estado'] == 1) {
                        $estado_nombre = 'Abierto';
                    } elseif ($lp['estado'] == 2) {
                        $estado_nombre = 'Cerrado';
                    } else {
                        $estado_nombre = '';
                    }

                    // Acumular totales de los que no están anulados
                    if ($lp['estado'] != 0) {
                        $total_debito += $val_debito;
                        $total_credito += $val_credito;
                        $total_neto += $val_neto;
                    }
                    ?>
                    <tr class="resaltar">
                        <td class="texto-center"><?= $lp['id_manu'] ?></td>
                        <td class="texto-center"><?= $lp['id_cdp'] ?></td>
                        <td class="texto-center" style="white-space: nowrap;"><?= $fecha ?></td>
                        <td class="texto"><?= htmlspecialchars($lp['num_contrato'] ?? '') ?></td>
                        <td class="texto-center"><?= $ccnit_val ?></td>
                        <td class="texto"><?= htmlspecialchars($tercero_nombre ?? '') ?></td>
                        <td class="texto"><?= htmlspecialchars($lp['objeto'] ?? '') ?></td>
                        <td class="numero"><?= number_format($val_debito, 2, '.', '') ?></td>
                        <td class="numero"><?= number_format($val_credito, 2, '.', '') ?></td>
                        <td class="numero"><?= number_format($val_neto, 2, '.', '') ?></td>
                        <td class="texto-center"><?= $estado_nombre ?></td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="11" style="text-align: center; padding: 15px;">No se encontraron registros con los filtros aplicados.</td></tr>';
            }
            ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #E2E3E5; font-weight: bold;">
                <td colspan="7" style="text-align: right; padding: 6px;">TOTALES:</td>
                <td class="numero"><?= number_format($total_debito, 2, '.', '') ?></td>
                <td class="numero"><?= number_format($total_credito, 2, '.', '') ?></td>
                <td class="numero"><?= number_format($total_neto, 2, '.', '') ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
