<?php

use Src\Usuarios\Login\Php\Clases\Usuario;

session_start();
set_time_limit(5600);
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}

$vigencia      = $_SESSION['vigencia'];
$fecha_inicial = $_POST['fecha_inicial'];
$fecha_corte   = $_POST['fecha_final'];
$sede          = $_POST['id_tercero'];

function pesos($valor)
{
    return '$' . number_format($valor, 2);
}

include '../../../config/autoloader.php';
include '../../financiero/consultas.php';
$cmd = \Config\Clases\Conexion::getConexion();

// Consulto el municipio (tercero) desde tb_terceros
try {
    $sql = "SELECT
                `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
                , `tb_terceros`.`id_tercero_api`
            FROM
                `tb_terceros`
            WHERE (`tb_terceros`.`id_tercero_api` = $sede)";
    $res     = $cmd->query($sql);
    $tercero = $res->fetch();
    $res->closeCursor();
    unset($res);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// Consulto detalle de Rete ICA (id_retencion_tipo = 3) con campos exógena
try {
    $sql = "SELECT
                `ctb_retencion_tipo`.`id_retencion_tipo`
                , `ctb_retenciones`.`nombre_retencion`
                , `ctb_retencion_tipo`.`tipo`
                , `ctb_retenciones`.`id_retencion`
                , `ctb_pgcp`.`cuenta`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
                , `tb_terceros`.`id_tercero_api` AS `id_ter`
                , `tb_terceros`.`dir_tercero`
                , `tb_terceros`.`tel_tercero`
                , SUM(`ctb_causa_retencion`.`valor_base`)      AS `base`
                , SUM(`ctb_causa_retencion`.`valor_retencion`) AS `retencion`
            FROM
                `ctb_retenciones`
                INNER JOIN `ctb_retencion_tipo`
                    ON (`ctb_retenciones`.`id_retencion_tipo` = `ctb_retencion_tipo`.`id_retencion_tipo`)
                INNER JOIN `ctb_retencion_rango`
                    ON (`ctb_retencion_rango`.`id_retencion` = `ctb_retenciones`.`id_retencion`)
                INNER JOIN `ctb_causa_retencion`
                    ON (`ctb_causa_retencion`.`id_rango` = `ctb_retencion_rango`.`id_rango`)
                INNER JOIN `ctb_doc`
                    ON (`ctb_causa_retencion`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                INNER JOIN `ctb_pgcp`
                    ON (`ctb_retenciones`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`)
                LEFT JOIN `tb_terceros`
                    ON (`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            WHERE (`ctb_retencion_tipo`.`id_retencion_tipo` = 3 AND `ctb_doc`.`estado` = 2
                AND DATE_FORMAT(`ctb_doc`.`fecha`,'%Y-%m-%d') BETWEEN '$fecha_inicial' AND '$fecha_corte'
                AND `ctb_causa_retencion`.`id_terceroapi` = {$tercero['id_tercero_api']})
            GROUP BY
                `ctb_retenciones`.`nombre_retencion`
                , `ctb_doc`.`id_tercero`";
    $res        = $cmd->query($sql);
    $causaciones = $res->fetchAll();
    $res->closeCursor();
    unset($res);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// Consulto detalle de Sobretasa (id_retencion_tipo = 4) con campos exógena
try {
    $sql = "SELECT
                `ctb_retencion_tipo`.`id_retencion_tipo`
                , `ctb_retencion_tipo`.`tipo`
                , `ctb_retenciones`.`nombre_retencion`
                , `ctb_retenciones`.`id_retencion`
                , `ctb_pgcp`.`cuenta`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
                , `tb_terceros`.`id_tercero_api` AS `id_ter`
                , `tb_terceros`.`dir_tercero`
                , `tb_terceros`.`tel_tercero`
                , SUM(`ctb_causa_retencion`.`valor_base`)      AS base
                , SUM(`ctb_causa_retencion`.`valor_retencion`) AS retencion
            FROM
                `ctb_retenciones`
                INNER JOIN `ctb_retencion_tipo`
                    ON (`ctb_retenciones`.`id_retencion_tipo` = `ctb_retencion_tipo`.`id_retencion_tipo`)
                INNER JOIN `ctb_retencion_rango`
                    ON (`ctb_retencion_rango`.`id_retencion` = `ctb_retenciones`.`id_retencion`)
                INNER JOIN `ctb_causa_retencion`
                    ON (`ctb_causa_retencion`.`id_rango` = `ctb_retencion_rango`.`id_rango`)
                INNER JOIN `ctb_doc`
                    ON (`ctb_causa_retencion`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                INNER JOIN `ctb_pgcp`
                    ON (`ctb_retenciones`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`)
                LEFT JOIN `tb_terceros`
                    ON (`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
            WHERE (`ctb_retencion_tipo`.`id_retencion_tipo` = 4 AND `ctb_doc`.`estado` = 2
                AND DATE_FORMAT(`ctb_doc`.`fecha`,'%Y-%m-%d') BETWEEN '$fecha_inicial' AND '$fecha_corte'
                AND `ctb_causa_retencion`.`id_terceroapi` = {$tercero['id_tercero_api']})
            GROUP BY
                `ctb_retenciones`.`nombre_retencion`
                , `ctb_doc`.`id_tercero`";
    $res       = $cmd->query($sql);
    $sobretasa = $res->fetchAll();
    $res->closeCursor();
    unset($res);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

// ── Unificar ICA y Sobretasa en un solo array indexado por nit_tercero ──
// Para cada tercero acumulamos base+retención+pago de ICA y de Sobretasa
$combined_data = [];

foreach ($causaciones as $rp) {
    $key = $rp['nit_tercero'] ?? ('__' . ($rp['nom_tercero'] ?? uniqid()));
    if (!isset($combined_data[$key])) {
        $combined_data[$key] = [
            'id_ter'      => $rp['id_ter']      ?? null,
            'nom_tercero' => $rp['nom_tercero'] ?? '---',
            'nit_tercero' => $rp['nit_tercero'] ?? '---',
            'dir_tercero' => $rp['dir_tercero'] ?? '---',
            'tel_tercero' => $rp['tel_tercero'] ?? '---',
            'actividades' => '---',
            'ica'         => ['base' => 0, 'retencion' => 0, 'pago' => 0],
            'sob'         => ['base' => 0, 'retencion' => 0, 'pago' => 0],
        ];
    }
    $combined_data[$key]['ica']['base']      += $rp['base'];
    $combined_data[$key]['ica']['retencion'] += $rp['retencion'];
    $combined_data[$key]['ica']['pago']      += round($rp['retencion'], -3);
}

foreach ($sobretasa as $rp) {
    $key = $rp['nit_tercero'] ?? ('__' . ($rp['nom_tercero'] ?? uniqid()));
    if (!isset($combined_data[$key])) {
        $combined_data[$key] = [
            'id_ter'      => $rp['id_ter']      ?? null,
            'nom_tercero' => $rp['nom_tercero'] ?? '---',
            'nit_tercero' => $rp['nit_tercero'] ?? '---',
            'dir_tercero' => $rp['dir_tercero'] ?? '---',
            'tel_tercero' => $rp['tel_tercero'] ?? '---',
            'actividades' => '---',
            'ica'         => ['base' => 0, 'retencion' => 0, 'pago' => 0],
            'sob'         => ['base' => 0, 'retencion' => 0, 'pago' => 0],
        ];
    }
    $combined_data[$key]['sob']['base']      += $rp['base'];
    $combined_data[$key]['sob']['retencion'] += $rp['retencion'];
    $combined_data[$key]['sob']['pago']      += round($rp['retencion'], -3);
}

// ── Consultar actividades económicas de los terceros vía API ──
$ids_api = array_values(array_filter(array_unique(array_column($combined_data, 'id_ter'))));
if (!empty($ids_api)) {
    $api     = \Config\Clases\Conexion::Api();
    $url     = $api . 'terceros/datos/res/lista/reportes';
    $payload = json_encode($ids_api);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $result = curl_exec($ch);
    curl_close($ch);
    // Indexar actividades por id_tercero
    $api_actividades = array_column(json_decode($result, true) ?? [], 'actividades', 'id_tercero');
    // Asignar actividades a cada fila del combined_data
    foreach ($combined_data as &$row) {
        $act = $api_actividades[$row['id_ter']] ?? '';
        $row['actividades'] = ($act !== '' && $act !== null)
            ? implode(', ', array_unique(explode('|', $act)))
            : '---';
    }
    unset($row);
}

$ips = (new Usuario())->getEmpresa();
?>
<div class="contenedor bg-light" id="areaImprimir">
    <div class="px-2 " style="width:90% !important;margin: 0 auto;">

        <table class="table-bordered bg-light mt-3" style="width:100% !important; font-size: 80%;">
            <tr>
                <td colspan="11" style="text-align:center"><?php echo $ips['nombre']; ?></td>
            </tr>
            <tr>
                <td colspan="11" style="text-align:center"><?php echo $ips['nit'] . '-' . $ips['dv']; ?></td>
            </tr>
            <tr>
                <td colspan="11" style="text-align:center">RELACION DE DESCUENTOS Y RETENCIONES EX&Oacute;GENA</td>
            </tr>
        </table>
        </br>
        </br>

        <table class="table-bordered bg-light" style="width:100% !important; font-size: 80%;">
            <tr>
                <td>MUNICIPIO</td>
                <td style='text-align: left;'><?php echo $tercero['nom_tercero']; ?></td>
            </tr>
            <tr>
                <td>NIT</td>
                <td style='text-align: left;'><?php echo $tercero['nit_tercero']; ?></td>
            </tr>
            <tr>
                <td>FECHA INICIO</td>
                <td style='text-align: left;'><?php echo $fecha_inicial; ?></td>
            </tr>
            <tr>
                <td>FECHA FIN</td>
                <td style='text-align: left;'><?php echo $fecha_corte; ?></td>
            </tr>
        </table>
        </br>&nbsp;
        </br>

        <!-- Tabla unificada Exógena: ICA + Sobretasa por tercero en una sola tabla -->
        <table class="table-bordered bg-light" style="width:100% !important; font-size: 80%;" border=1>
            <!-- Fila 1: encabezados de sección -->
            <tr>
                <td rowspan="2" style="text-align:center; vertical-align:middle;"><strong>Nombre</strong></td>
                <td rowspan="2" style="text-align:center; vertical-align:middle;"><strong>CC / Nit</strong></td>
                <td rowspan="2" style="text-align:center; vertical-align:middle;"><strong>Direcci&oacute;n</strong></td>
                <td rowspan="2" style="text-align:center; vertical-align:middle;"><strong>Tel&eacute;fono</strong></td>
                <td rowspan="2" style="text-align:center; vertical-align:middle;"><strong>Actividades</strong></td>
                <td colspan="3" style="text-align:center; background:#d6eaf8;"><strong>INDUSTRIA Y COMERCIO</strong></td>
                <td colspan="3" style="text-align:center; background:#d5f5e3;"><strong>SOBRETASA BOMBERIL</strong></td>
            </tr>
            <!-- Fila 2: sub-encabezados de columnas numéricas -->
            <tr>
                <td style="text-align:center; background:#d6eaf8;"><strong>Base</strong></td>
                <td style="text-align:center; background:#d6eaf8;"><strong>Valor retenido</strong></td>
                <td style="text-align:center; background:#d6eaf8;"><strong>Valor pago</strong></td>
                <td style="text-align:center; background:#d5f5e3;"><strong>Base</strong></td>
                <td style="text-align:center; background:#d5f5e3;"><strong>Valor retenido</strong></td>
                <td style="text-align:center; background:#d5f5e3;"><strong>Valor pago</strong></td>
            </tr>
            <?php
            $gtotal_ica_base = $gtotal_ica_ret = $gtotal_ica_pago = 0;
            $gtotal_sob_base = $gtotal_sob_ret = $gtotal_sob_pago = 0;

            foreach ($combined_data as $row) {
                $ica_base = $row['ica']['base'];
                $ica_ret  = $row['ica']['retencion'];
                $ica_pago = $row['ica']['pago'];
                $sob_base = $row['sob']['base'];
                $sob_ret  = $row['sob']['retencion'];
                $sob_pago = $row['sob']['pago'];

                echo "<tr>
                    <td class='text'>" . $row['nom_tercero']                           . "</td>
                    <td class='text'>" . $row['nit_tercero']                           . "</td>
                    <td class='text'>" . $row['dir_tercero']                           . "</td>
                    <td class='text'>" . $row['tel_tercero']                           . "</td>
                    <td class='text'>" . $row['actividades']                          . "</td>
                    <td class='text-end' style='background:#eaf4fb;'>" . number_format($ica_base, 2, '.', ',') . "</td>
                    <td class='text-end' style='background:#eaf4fb;'>" . number_format($ica_ret,  2, '.', ',') . "</td>
                    <td class='text-end' style='background:#eaf4fb;'>" . number_format($ica_pago, 2, '.', ',') . "</td>
                    <td class='text-end' style='background:#eafaf1;'>" . number_format($sob_base, 2, '.', ',') . "</td>
                    <td class='text-end' style='background:#eafaf1;'>" . number_format($sob_ret,  2, '.', ',') . "</td>
                    <td class='text-end' style='background:#eafaf1;'>" . number_format($sob_pago, 2, '.', ',') . "</td>
                  </tr>";

                $gtotal_ica_base += $ica_base;
                $gtotal_ica_ret  += $ica_ret;
                $gtotal_ica_pago += $ica_pago;
                $gtotal_sob_base += $sob_base;
                $gtotal_sob_ret  += $sob_ret;
                $gtotal_sob_pago += $sob_pago;
            }

            echo "<tr>
                    <td class='text-end' colspan='5'><strong>Total</strong></td>
                    <td class='text-end' style='background:#eaf4fb;'><strong>" . number_format($gtotal_ica_base, 2, '.', ',') . "</strong></td>
                    <td class='text-end' style='background:#eaf4fb;'><strong>" . number_format($gtotal_ica_ret,  2, '.', ',') . "</strong></td>
                    <td class='text-end' style='background:#eaf4fb;'><strong>" . number_format($gtotal_ica_pago, 2, '.', ',') . "</strong></td>
                    <td class='text-end' style='background:#eafaf1;'><strong>" . number_format($gtotal_sob_base, 2, '.', ',') . "</strong></td>
                    <td class='text-end' style='background:#eafaf1;'><strong>" . number_format($gtotal_sob_ret,  2, '.', ',') . "</strong></td>
                    <td class='text-end' style='background:#eafaf1;'><strong>" . number_format($gtotal_sob_pago, 2, '.', ',') . "</strong></td>
                  </tr>";
            ?>
        </table>
        </br>
        </br>
        </br>

    </div>
</div>