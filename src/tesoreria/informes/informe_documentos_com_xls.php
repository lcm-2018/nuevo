<?php
session_start();
set_time_limit(5600);
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}

include '../../../config/autoloader.php';

$cmd = \Config\Clases\Conexion::getConexion();
$id_vigencia = $_SESSION['id_vigencia'] ?? 0;
$vigencia = $_SESSION['vigencia'] ?? date('Y');

// Filtros recibidos por POST
$id_ctb_doc = isset($_POST['id_doc']) ? intval($_POST['id_doc']) : 0;
if ($id_ctb_doc <= 0) {
    die("Debe seleccionar un tipo de documento.");
}

$id_manu = isset($_POST['id_manu']) ? trim($_POST['id_manu']) : '';
$option = isset($_POST['option']) ? intval($_POST['option']) : 0;
$fec_ini = isset($_POST['fec_ini']) ? trim($_POST['fec_ini']) : '';
$fec_fin = isset($_POST['fec_fin']) ? trim($_POST['fec_fin']) : '';
$causacion = isset($_POST['causacion']) ? trim($_POST['causacion']) : '';
$ccnit = isset($_POST['ccnit']) ? trim($_POST['ccnit']) : '';
$tercero = isset($_POST['tercero']) ? trim($_POST['tercero']) : '';
$valor = isset($_POST['valor']) ? trim($_POST['valor']) : '';
$estado = isset($_POST['estado']) ? strval($_POST['estado']) : '-1';
$anulados = isset($_POST['anulados']) ? intval($_POST['anulados']) : 0;

// Consultar nombre y código del tipo de documento
$nombreDoc = 'TESORERÍA';
$codDoc = '';
try {
    $sql = "SELECT `nombre`, `cod` FROM `ctb_fuente` WHERE `id_doc_fuente` = $id_ctb_doc";
    $rs = $cmd->query($sql);
    if ($rs) {
        $docInfo = $rs->fetch(PDO::FETCH_ASSOC);
        if ($docInfo) {
            $nombreDoc = $docInfo['nombre'];
            $codDoc = $docInfo['cod'];
        }
    }
} catch (PDOException $e) {
    // Mantener valor por defecto
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
    // Mantener vacíos si falla
}

$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$cleanDoc = preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $nombreDoc));
$filename = "TES_" . $cleanDoc . "_" . $date->format('Ymd_His') . ".xls";

header("Content-type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF";

// Si es Caja Menor (id_tipo_doc == 13)
if ($id_ctb_doc == 13) {
    $where = "";
    $filtros_aplicados = [];

    if (!empty($fec_ini) && !empty($fec_fin)) {
        $fec_ini_safe = addslashes($fec_ini);
        $fec_fin_safe = addslashes($fec_fin);
        $where .= " AND `tes_caja_const`.`fecha_ini` BETWEEN '$fec_ini_safe' AND '$fec_fin_safe'";
        $filtros_aplicados[] = "Fecha: $fec_ini a $fec_fin";
    }

    if (strlen($estado) > 0 && $estado != "-1") {
        $estado_int = intval($estado);
        $where .= " AND `tes_caja_const`.`estado` = $estado_int";
        $nom_est = $estado_int == 1 ? 'Abierto' : ($estado_int == 2 ? 'Cerrado' : ($estado_int == 0 ? 'Anulado' : $estado_int));
        $filtros_aplicados[] = "Estado: $nom_est";
    }

    try {
        $sql = "SELECT
                    `tes_caja_const`.`id_caja_const`
                    , `pto_actos_admin`.`nombre` AS `acto`
                    , `tes_caja_const`.`num_acto`
                    , `tes_caja_const`.`nombre_caja`
                    , `tes_caja_const`.`fecha_ini`
                    , `tes_caja_const`.`fecha_acto`
                    , `tes_caja_const`.`valor_total`
                    , `tes_caja_const`.`valor_minimo`
                    , `tes_caja_const`.`num_poliza`
                    , `tes_caja_const`.`porcentaje`
                    , `tes_caja_const`.`estado`
                FROM
                    `tes_caja_const`
                    INNER JOIN `pto_actos_admin` 
                        ON (`tes_caja_const`.`id_tipo_acto` = `pto_actos_admin`.`id_acto`)
                WHERE (`tes_caja_const`.`fecha_ini` BETWEEN '$vigencia-01-01' AND '$vigencia-12-31' $where)
                ORDER BY `tes_caja_const`.`id_caja_const` DESC";
        $rs = $cmd->query($sql);
        $cajas = $rs ? $rs->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title><?= $nombreDoc ?></title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 10pt; }
            .texto { mso-number-format: "\@"; text-align: left; }
            .texto-center { mso-number-format: "\@"; text-align: center; }
            .numero { mso-number-format: "0\.00"; text-align: right; }
            .porcentaje { mso-number-format: "0\.00%"; text-align: right; }
            tr.resaltar:hover { background-color: #F8F9FA; }
            th { background-color: #2E5B88; color: #FFFFFF; border: 1px solid #1C3B5E; padding: 6px; text-align: center; }
            td { border: 1px solid #D9D9D9; padding: 5px; }
        </style>
    </head>
    <body>
        <table style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th colspan="10" style="background-color: #FFFFFF; color: #000000; border: none; font-size: 13pt; text-align: center;">
                        <?= mb_strtoupper($empresa['nombre']) ?>
                    </th>
                </tr>
                <tr>
                    <th colspan="10" style="background-color: #FFFFFF; color: #333333; border: none; font-size: 10pt; text-align: center;">
                        NIT: <?= $empresa['nit'] . ($empresa['dig_ver'] !== '' ? '-' . $empresa['dig_ver'] : '') ?>
                    </th>
                </tr>
                <tr>
                    <th colspan="10" style="background-color: #FFFFFF; color: #000000; border: none; font-size: 11pt; font-weight: bold; text-align: center;">
                        MOVIMIENTOS DE TESORERÍA: <?= mb_strtoupper($nombreDoc) ?> <?= $codDoc != '' ? "($codDoc)" : '' ?>
                    </th>
                </tr>
                <tr>
                    <th colspan="10" style="background-color: #FFFFFF; color: #555555; border: none; font-size: 9pt; text-align: center;">
                        Vigencia: <?= $vigencia ?> | Fecha de reporte: <?= $date->format('Y-m-d H:i:s') ?>
                    </th>
                </tr>
                <?php if (!empty($filtros_aplicados)): ?>
                <tr>
                    <th colspan="10" style="background-color: #FFFFFF; color: #198754; border: none; font-size: 9pt; text-align: center;">
                        Filtros: <?= implode(' | ', $filtros_aplicados) ?>
                    </th>
                </tr>
                <?php endif; ?>
                <tr>
                    <th colspan="10" style="background-color: #FFFFFF; border: none; height: 10px;"></th>
                </tr>
                <tr>
                    <th>Acto</th>
                    <th>Num. Acto</th>
                    <th>Nombre Caja</th>
                    <th>Inicia</th>
                    <th>Fecha Acto</th>
                    <th>Total</th>
                    <th>Mínimo</th>
                    <th>Póliza</th>
                    <th>%</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $tot_total = 0;
                $tot_minimo = 0;
                if (!empty($cajas)) {
                    foreach ($cajas as $c) {
                        $v_total = floatval($c['valor_total']);
                        $v_min = floatval($c['valor_minimo']);
                        $tot_total += $v_total;
                        $tot_minimo += $v_min;
                        $estado_nom = $c['estado'] == 1 ? 'Abierto' : ($c['estado'] == 2 ? 'Cerrado' : ($c['estado'] == 0 ? 'Anulado' : $c['estado']));
                        ?>
                        <tr class="resaltar">
                            <td class="texto"><?= htmlspecialchars($c['acto']) ?></td>
                            <td class="texto-center"><?= htmlspecialchars($c['num_acto']) ?></td>
                            <td class="texto"><?= htmlspecialchars($c['nombre_caja']) ?></td>
                            <td class="texto-center"><?= $c['fecha_ini'] ?></td>
                            <td class="texto-center"><?= $c['fecha_acto'] ?></td>
                            <td class="numero"><?= number_format($v_total, 2, '.', '') ?></td>
                            <td class="numero"><?= number_format($v_min, 2, '.', '') ?></td>
                            <td class="texto-center"><?= htmlspecialchars($c['num_poliza']) ?></td>
                            <td class="numero"><?= number_format(floatval($c['porcentaje']), 2, '.', '') ?>%</td>
                            <td class="texto-center"><?= $estado_nom ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="10" style="text-align: center; padding: 15px;">No se encontraron registros de caja menor.</td></tr>';
                }
                ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #E2E3E5; font-weight: bold;">
                    <td colspan="5" style="text-align: right; padding: 6px;">TOTALES:</td>
                    <td class="numero"><?= number_format($tot_total, 2, '.', '') ?></td>
                    <td class="numero"><?= number_format($tot_minimo, 2, '.', '') ?></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </body>
    </html>
    <?php
    exit();
}

// Para todos los demás documentos de tesorería (CEVA, etc.)
$andwhere = '';
$filtros_aplicados = [];

if (!empty($id_manu)) {
    $id_manu_safe = addslashes($id_manu);
    if ($option == 0) {
        $andwhere .= " AND ctb_doc.id_manu LIKE '%$id_manu_safe%'";
        $filtros_aplicados[] = "No. Doc contiene: " . htmlspecialchars($id_manu);
    } else {
        $andwhere .= " AND ctb_doc.id_manu = '$id_manu_safe'";
        $filtros_aplicados[] = "No. Doc exacto: " . htmlspecialchars($id_manu);
    }
}

if (!empty($fec_ini) && !empty($fec_fin)) {
    $fec_ini_safe = addslashes($fec_ini);
    $fec_fin_safe = addslashes($fec_fin);
    $andwhere .= " AND ctb_doc.fecha BETWEEN '$fec_ini_safe' AND '$fec_fin_safe'";
    $filtros_aplicados[] = "Fecha: $fec_ini a $fec_fin";
} elseif (!empty($fec_ini)) {
    $fec_ini_safe = addslashes($fec_ini);
    $andwhere .= " AND ctb_doc.fecha >= '$fec_ini_safe'";
    $filtros_aplicados[] = "Fecha desde: $fec_ini";
} elseif (!empty($fec_fin)) {
    $fec_fin_safe = addslashes($fec_fin);
    $andwhere .= " AND ctb_doc.fecha <= '$fec_fin_safe'";
    $filtros_aplicados[] = "Fecha hasta: $fec_fin";
}

if (!empty($causacion)) {
    $causacion_safe = addslashes($causacion);
    $andwhere .= " AND EXISTS (SELECT 1 FROM `pto_pag_detalle`
                        INNER JOIN `pto_cop_detalle` 
                            ON `pto_pag_detalle`.`id_pto_cop_det` = `pto_cop_detalle`.`id_pto_cop_det`
                        INNER JOIN `ctb_doc` AS `doc_causacion`
                            ON (`pto_cop_detalle`.`id_ctb_doc` = `doc_causacion`.`id_ctb_doc`)
                        WHERE `pto_pag_detalle`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`
                        AND `doc_causacion`.`id_manu` LIKE '%$causacion_safe%')";
    $filtros_aplicados[] = "Causación: " . htmlspecialchars($causacion);
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

if ($valor !== '' && is_numeric($valor)) {
    $valor_num = floatval($valor);
    $andwhere .= " AND (SELECT SUM(`credito`) FROM `ctb_libaux` WHERE `ctb_libaux`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`) = $valor_num";
    $filtros_aplicados[] = "Valor: $" . number_format($valor_num, 2);
}

if (strlen($estado) > 0) {
    if ($estado == "-1") {
        $andwhere .= " AND ctb_doc.estado >= 0";
    } else {
        $estado_int = intval($estado);
        $andwhere .= " AND ctb_doc.estado = $estado_int";
        $nom_est = $estado_int == 1 ? 'Abierto' : ($estado_int == 2 ? 'Cerrado' : ($estado_int == 0 ? 'Anulado' : $estado_int));
        $filtros_aplicados[] = "Estado: $nom_est";
    }
}

// Consultar listado de documentos de tesorería
try {
    $sql = "SELECT
                `ctb_doc`.`id_ctb_doc`
                , `ctb_doc`.`id_manu`
                , `ctb_doc`.`fecha`
                , `ctb_doc`.`detalle`
                , `ctb_doc`.`id_tercero`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
                , `ctb_doc`.`estado`
                , `ctb_doc`.`id_tipo_doc`
                , `ctb_doc`.`id_vigencia`
                , `causaciones`.`id_manu` AS `causacion`
                , `ctb_referencia`.`nombre` AS `referencia`  
                , IFNULL(`libaux`.`credito`, 0) AS `valor`
                , IFNULL(`libaux`.`diferencia`, 0) AS `diferencia`
            FROM
                `ctb_doc`
                LEFT JOIN `ctb_referencia`
                    ON (`ctb_doc`.`id_ref_ctb` = `ctb_referencia`.`id_ctb_referencia`)
                LEFT JOIN `tb_terceros`
                    ON (`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
                LEFT JOIN 
                    (SELECT
                        `pto_pag_detalle`.`id_ctb_doc` AS `pag`
                        , GROUP_CONCAT(DISTINCT `ctb_doc`.`id_manu` ORDER BY `ctb_doc`.`id_manu` SEPARATOR ', ') AS `id_manu`
                    FROM
                        `pto_pag_detalle`
                        INNER JOIN `pto_cop_detalle` 
                            ON `pto_pag_detalle`.`id_pto_cop_det` = `pto_cop_detalle`.`id_pto_cop_det`
                        INNER JOIN `ctb_doc` 
                            ON (`pto_cop_detalle`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                    GROUP BY `pto_pag_detalle`.`id_ctb_doc`) AS `causaciones`
                    ON (`ctb_doc`.`id_ctb_doc` = `causaciones`.`pag`)
                LEFT JOIN 
                    (SELECT
                        `id_ctb_doc`
                        , SUM(`debito`) AS `debito`
                        , SUM(`credito`) AS `credito`
                        , SUM(`debito` - `credito`) AS `diferencia`
                    FROM
                        `ctb_libaux`
                    GROUP BY `id_ctb_doc`) AS `libaux`
                    ON (`libaux`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
            WHERE (`ctb_doc`.`id_tipo_doc` = $id_ctb_doc AND `ctb_doc`.`id_vigencia` = $id_vigencia) $andwhere  
            GROUP BY `ctb_doc`.`id_ctb_doc`
            ORDER BY `ctb_doc`.`fecha` DESC, `ctb_doc`.`id_manu` DESC";
    $rs = $cmd->query($sql);
    $documentos = $rs ? $rs->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $nombreDoc ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }
        .texto {
            mso-number-format: "\@";
            text-align: left;
        }
        .texto-center {
            mso-number-format: "\@";
            text-align: center;
        }
        .numero {
            mso-number-format: "0\.00";
            text-align: right;
        }
        tr.resaltar:hover {
            background-color: #F8F9FA;
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
                <th colspan="9" style="background-color: #FFFFFF; color: #000000; border: none; font-size: 13pt; text-align: center;">
                    <?= mb_strtoupper($empresa['nombre']) ?>
                </th>
            </tr>
            <tr>
                <th colspan="9" style="background-color: #FFFFFF; color: #333333; border: none; font-size: 10pt; text-align: center;">
                    NIT: <?= $empresa['nit'] . ($empresa['dig_ver'] !== '' ? '-' . $empresa['dig_ver'] : '') ?>
                </th>
            </tr>
            <tr>
                <th colspan="9" style="background-color: #FFFFFF; color: #000000; border: none; font-size: 11pt; font-weight: bold; text-align: center;">
                    MOVIMIENTOS DE TESORERÍA: <?= mb_strtoupper($nombreDoc) ?> <?= $codDoc != '' ? "($codDoc)" : '' ?>
                </th>
            </tr>
            <tr>
                <th colspan="9" style="background-color: #FFFFFF; color: #555555; border: none; font-size: 9pt; text-align: center;">
                    Vigencia: <?= $vigencia ?> | Fecha de reporte: <?= $date->format('Y-m-d H:i:s') ?>
                </th>
            </tr>
            <?php if (!empty($filtros_aplicados)): ?>
            <tr>
                <th colspan="9" style="background-color: #FFFFFF; color: #198754; border: none; font-size: 9pt; text-align: center;">
                    Filtros: <?= implode(' | ', $filtros_aplicados) ?>
                </th>
            </tr>
            <?php endif; ?>
            <tr>
                <th colspan="9" style="background-color: #FFFFFF; border: none; height: 10px;"></th>
            </tr>
            <tr>
                <th>Número</th>
                <th>Causación</th>
                <th>Fecha</th>
                <th>CC / Nit</th>
                <th>Tercero</th>
                <th>Referencia</th>
                <th>Detalle / Concepto</th>
                <th>Valor</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_valor = 0;

            if (!empty($documentos)) {
                foreach ($documentos as $doc) {
                    $fecha = date('Y-m-d', strtotime($doc['fecha']));
                    $valor_num = floatval($doc['valor']);
                    $tercero_nom = !empty($doc['nom_tercero']) ? $doc['nom_tercero'] : '---';
                    $ccnit_val = !empty($doc['nit_tercero']) ? $doc['nit_tercero'] : '';
                    $causacion_val = !empty($doc['causacion']) ? $doc['causacion'] : '-';
                    $referencia_val = !empty($doc['referencia']) ? $doc['referencia'] : '-';

                    if ($doc['estado'] == 0) {
                        $estado_nom = 'Anulado';
                    } elseif ($doc['estado'] == 1) {
                        $estado_nom = 'Abierto';
                    } elseif ($doc['estado'] == 2) {
                        $estado_nom = 'Cerrado';
                    } else {
                        $estado_nom = '';
                    }

                    if ($doc['estado'] != 0) {
                        $total_valor += $valor_num;
                    }
                    ?>
                    <tr class="resaltar">
                        <td class="texto-center"><?= $doc['id_manu'] ?></td>
                        <td class="texto-center"><?= $causacion_val ?></td>
                        <td class="texto-center" style="white-space: nowrap;"><?= $fecha ?></td>
                        <td class="texto-center"><?= $ccnit_val ?></td>
                        <td class="texto"><?= htmlspecialchars($tercero_nom) ?></td>
                        <td class="texto"><?= htmlspecialchars($referencia_val) ?></td>
                        <td class="texto"><?= htmlspecialchars($doc['detalle'] ?? '') ?></td>
                        <td class="numero"><?= number_format($valor_num, 2, '.', '') ?></td>
                        <td class="texto-center"><?= $estado_nom ?></td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="9" style="text-align: center; padding: 15px;">No se encontraron registros con los filtros aplicados.</td></tr>';
            }
            ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #E2E3E5; font-weight: bold;">
                <td colspan="7" style="text-align: right; padding: 6px;">TOTAL:</td>
                <td class="numero"><?= number_format($total_valor, 2, '.', '') ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
