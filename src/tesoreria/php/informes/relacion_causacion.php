<?php
session_start();
// incrementar el tiempo de ejecucion del script
ini_set('max_execution_time', 5600);

include_once '../../../../config/autoloader.php';
$vigencia = $_SESSION['vigencia'];

// Función para calcular el dígito de verificación del NIT (algoritmo DIAN módulo 11)
function calcularDV($nit)
{
    if (empty($nit)) return '';
    $factores = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
    $nit = strval($nit);
    $len = strlen($nit);
    $suma = 0;
    for ($i = 0; $i < $len; $i++) {
        $suma += intval($nit[$len - 1 - $i]) * $factores[$i];
    }
    $residuo = $suma % 11;
    if ($residuo > 1) {
        return 11 - $residuo;
    }
    return $residuo;
}

// extraigo las variables que llegan por post
$fecha_inicial = $_POST['fec_ini'];
$fecha_corte = $_POST['fec_fin'];

$cmd = \Config\Clases\Conexion::getConexion();
try {
    $sql = "SELECT
        `cop`.`id_ctb_doc` AS `id_causacion`
        , `doc_causacion`.`id_manu` AS `no_causacion`
        , DATE_FORMAT(`doc_causacion`.`fecha`,'%Y-%m-%d') AS `fecha`
        , `doc_causacion`.`detalle` AS `objeto`
        , `tt`.`nit_tercero`
        , `tt`.`nom_tercero`
        , `ttd`.`codigo_ne` AS `tipo_doc`
        , `fact`.`num_factura`
        , `rubro`.`cod_pptal`
        , (IFNULL(`cop`.`valor`,0) - IFNULL(`cop`.`valor_liberado`,0)) AS `val_bruto`
        , IFNULL(`ret`.`valor_retencion`, 0) AS `retencion_causacion`
        , IFNULL(`pag`.`val_pagado`, 0) AS `val_pagado`
    FROM
        `pto_cop_detalle` AS `cop`
        INNER JOIN `ctb_doc` AS `doc_causacion`
            ON (`cop`.`id_ctb_doc` = `doc_causacion`.`id_ctb_doc`)
        LEFT JOIN `tb_terceros` AS `tt`
            ON (`cop`.`id_tercero_api` = `tt`.`id_tercero_api`)
        LEFT JOIN `tb_tipos_documento` AS `ttd`
            ON (`tt`.`tipo_doc` = `ttd`.`id_tipodoc`)
        LEFT JOIN (
            SELECT `id_ctb_doc`, GROUP_CONCAT(`num_doc` SEPARATOR ', ') AS `num_factura`
            FROM `ctb_factura`
            GROUP BY `id_ctb_doc`
        ) AS `fact`
            ON (`doc_causacion`.`id_ctb_doc` = `fact`.`id_ctb_doc`)
        LEFT JOIN `pto_crp_detalle` AS `crp`
            ON (`cop`.`id_pto_crp_det` = `crp`.`id_pto_crp_det`)
        LEFT JOIN `pto_cdp_detalle` AS `cdp`
            ON (`crp`.`id_pto_cdp_det` = `cdp`.`id_pto_cdp_det`)
        LEFT JOIN `pto_cargue` AS `rubro`
            ON (`cdp`.`id_rubro` = `rubro`.`id_cargue`)
        LEFT JOIN (
            SELECT `id_ctb_doc`, SUM(`valor_retencion`) AS `valor_retencion`
            FROM `ctb_causa_retencion`
            GROUP BY `id_ctb_doc`
        ) AS `ret`
            ON (`ret`.`id_ctb_doc` = `cop`.`id_ctb_doc`)
        LEFT JOIN (
            SELECT `id_pto_cop_det`, SUM(IFNULL(`valor`,0) - IFNULL(`valor_liberado`,0)) AS `val_pagado`
            FROM `pto_pag_detalle`
            GROUP BY `id_pto_cop_det`
        ) AS `pag`
            ON (`pag`.`id_pto_cop_det` = `cop`.`id_pto_cop_det`)
    WHERE (
        `doc_causacion`.`estado` = 2
        AND DATE_FORMAT(`doc_causacion`.`fecha`,'%Y-%m-%d') BETWEEN '$fecha_inicial' AND '$fecha_corte'
    )
    ORDER BY `doc_causacion`.`fecha`, `doc_causacion`.`id_manu`";

    $res = $cmd->query($sql);
    $datos = $res->fetchAll();
    $res->closeCursor();
    unset($res);
} catch (Exception $e) {
    echo $e->getMessage();
}

$nom_informe = "RELACION DE CAUSACIONES POR PAGAR";
include_once '../../../financiero/encabezado_empresa.php';

?>
<br>
<table class="table-hover" style="width:100% !important; border-collapse: collapse; font-size: 70%;" border="1">
    <thead>
        <tr class="centrar" style="background-color:#CED3D3; color:#000000;">
            <th>Recepción de Factura</th>
            <th>Fecha</th>
            <th>Tipo Doc.</th>
            <th>Documento</th>
            <th>Nombre</th>
            <th>Objeto</th>
            <th>Num. Factura</th>
            <th>Rubro Presupuestal</th>
            <th>Val. Bruto</th>
            <th>Descuentos</th>
            <th>T. Neto</th>
            <th>V. Pagado</th>
            <th>Pendiente por Pagar</th>
        </tr>
    </thead>
    <tbody id="tbRelacionCausacion">
        <?php
        $total_bruto = 0;
        $total_descuentos = 0;
        $total_neto = 0;
        $total_pagado = 0;
        $total_pendiente = 0;

        if (!empty($datos)) {
            // === Primera pasada: calcular totales por causación ===
            // Se agrupa por id_causacion para sumar val_bruto total y retenciones únicas
            $causaciones_totales = [];  // id_causacion => total_bruto
            $causaciones_ret = [];      // id_causacion => retencion (única por causación)

            foreach ($datos as $row) {
                $id_caus = $row['id_causacion'];
                if (!isset($causaciones_totales[$id_caus])) {
                    $causaciones_totales[$id_caus] = 0;
                }
                $causaciones_totales[$id_caus] += $row['val_bruto'];

                // Retención única por causación (evita contar doble si varias filas comparten causación)
                if (!isset($causaciones_ret[$id_caus])) {
                    $causaciones_ret[$id_caus] = $row['retencion_causacion'];
                }
            }

            // === Segunda pasada: renderizar con distribución proporcional ===
            foreach ($datos as $row) {
                $id_caus = $row['id_causacion'];
                $total_bruto_causacion = $causaciones_totales[$id_caus];
                $total_ret_causacion = $causaciones_ret[$id_caus] ?? 0;

                // Distribución proporcional: descuento = retención_total × (bruto_rubro / bruto_total_causación)
                $descuento = ($total_bruto_causacion > 0)
                    ? round($total_ret_causacion * ($row['val_bruto'] / $total_bruto_causacion), 0)
                    : 0;
                $val_neto = $row['val_bruto'] - $descuento;
                $val_pagado = $row['val_pagado'];
                $pendiente = $val_neto - $val_pagado;

                $total_bruto += $row['val_bruto'];
                $total_descuentos += $descuento;
                $total_neto += $val_neto;
                $total_pagado += $val_pagado;
                $total_pendiente += $pendiente;

                echo '<tr class="resaltar">
                    <td style="text-align:center; border:#A9A9A9 1px solid;">' . $row['no_causacion'] . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid;">' . $row['fecha'] . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid;">' . $row['tipo_doc'] . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid; mso-number-format:\@;">' . $row['nit_tercero'] . '</td>
                    <td style="text-align:left; border:#A9A9A9 1px solid;">' . mb_strtoupper($row['nom_tercero']) . '</td>
                    <td style="text-align:left; border:#A9A9A9 1px solid;">' . mb_strtoupper($row['objeto']) . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid; mso-number-format:\@;">' . $row['num_factura'] . '</td>
                    <td style="text-align:right; border:#A9A9A9 1px solid; mso-number-format:\@;">' . $row['cod_pptal'] . '</td>
                    <td style="text-align:right; border:#A9A9A9 1px solid;">' . number_format($row['val_bruto'], 2) . '</td>
                    <td style="text-align:right; border:#A9A9A9 1px solid;">' . number_format($descuento, 2) . '</td>
                    <td style="text-align:right; border:#A9A9A9 1px solid;">' . number_format($val_neto, 2) . '</td>
                    <td style="text-align:right; border:#A9A9A9 1px solid;">' . number_format($val_pagado, 2) . '</td>
                    <td style="text-align:right; border:#A9A9A9 1px solid;">' . number_format($pendiente, 2) . '</td>
                </tr>';
            }
        }
        ?>
    </tbody>
    <tfoot>
        <tr style="background-color:#CED3D3; font-weight:bold;">
            <td colspan="8" style="text-align:right; border:#A9A9A9 1px solid;">TOTALES:</td>
            <td style="text-align:right; border:#A9A9A9 1px solid;"><?php echo number_format($total_bruto, 2); ?></td>
            <td style="text-align:right; border:#A9A9A9 1px solid;"><?php echo number_format($total_descuentos, 2); ?></td>
            <td style="text-align:right; border:#A9A9A9 1px solid;"><?php echo number_format($total_neto, 2); ?></td>
            <td style="text-align:right; border:#A9A9A9 1px solid;"><?php echo number_format($total_pagado, 2); ?></td>
            <td style="text-align:right; border:#A9A9A9 1px solid;"><?php echo number_format($total_pendiente, 2); ?></td>
        </tr>
    </tfoot>
</table>