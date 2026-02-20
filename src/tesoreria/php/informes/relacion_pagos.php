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
        `pag`.`id_ctb_doc` AS `id_egreso`
        , `cop`.`id_ctb_doc` AS `id_causacion`
        , `doc_egreso`.`id_manu` AS `no_egreso`
        , `doc_egreso`.`detalle`
        , DATE_FORMAT(`doc_egreso`.`fecha`,'%Y-%m-%d') AS `fecha`
        , `doc_causacion`.`id_manu` AS `no_causacion`
        , `tt`.`nit_tercero`
        , `tt`.`nom_tercero`
        , `ttd`.`codigo_ne` AS `tipo_doc`
        , `rubro`.`cod_pptal`
        , `rubro`.`nom_rubro`
        , SUM(IFNULL(`pag`.`valor`,0) - IFNULL(`pag`.`valor_liberado`,0)) AS `val_bruto`
        , IFNULL(`ret`.`valor_retencion`, 0) AS `retencion_causacion`
        , `cta`.`numero` AS `cta_bancaria`
        , `pgcp`.`cuenta` AS `cod_contable`
    FROM
        `pto_pag_detalle` AS `pag`
        INNER JOIN `ctb_doc` AS `doc_egreso` 
            ON (`pag`.`id_ctb_doc` = `doc_egreso`.`id_ctb_doc`)
        INNER JOIN `pto_cop_detalle` AS `cop` 
            ON (`pag`.`id_pto_cop_det` = `cop`.`id_pto_cop_det`)
        INNER JOIN `ctb_doc` AS `doc_causacion` 
            ON (`cop`.`id_ctb_doc` = `doc_causacion`.`id_ctb_doc`)
        LEFT JOIN `pto_crp_detalle` AS `crp` 
            ON (`cop`.`id_pto_crp_det` = `crp`.`id_pto_crp_det`)
        LEFT JOIN `pto_cdp_detalle` AS `cdp` 
            ON (`crp`.`id_pto_cdp_det` = `cdp`.`id_pto_cdp_det`)
        LEFT JOIN `pto_cargue` AS `rubro` 
            ON (`cdp`.`id_rubro` = `rubro`.`id_cargue`)
        LEFT JOIN `tb_terceros` AS `tt` 
            ON (`pag`.`id_tercero_api` = `tt`.`id_tercero_api`)
        LEFT JOIN `tb_tipos_documento` AS `ttd` 
            ON (`tt`.`tipo_doc` = `ttd`.`id_tipodoc`)
        LEFT JOIN `tes_detalle_pago` AS `det_pago` 
            ON (`det_pago`.`id_ctb_doc` = `doc_egreso`.`id_ctb_doc`)
        LEFT JOIN `tes_cuentas` AS `cta` 
            ON (`det_pago`.`id_tes_cuenta` = `cta`.`id_tes_cuenta`)
        LEFT JOIN (
            SELECT `id_ctb_doc`, SUM(`valor_retencion`) AS `valor_retencion` 
            FROM `ctb_causa_retencion` 
            GROUP BY `id_ctb_doc`
        ) AS `ret` 
            ON (`ret`.`id_ctb_doc` = `cop`.`id_ctb_doc`)
        LEFT JOIN `ctb_libaux` AS `libaux` 
            ON (`libaux`.`id_ctb_doc` = `doc_egreso`.`id_ctb_doc` AND `libaux`.`credito` > 0)
        LEFT JOIN `ctb_pgcp` AS `pgcp` 
            ON (`libaux`.`id_cuenta` = `pgcp`.`id_pgcp`)
    WHERE (
        `doc_egreso`.`estado` = 2 
        AND `doc_causacion`.`estado` = 2
        AND DATE_FORMAT(`doc_egreso`.`fecha`,'%Y-%m-%d') BETWEEN '$fecha_inicial' AND '$fecha_corte'
    )
    GROUP BY `pag`.`id_ctb_doc`, `pag`.`id_tercero_api`, `pag`.`id_pto_cop_det`
    ORDER BY `doc_egreso`.`fecha`, `doc_egreso`.`id_manu`";

    $res = $cmd->query($sql);
    $datos = $res->fetchAll();
    $res->closeCursor();
    unset($res);
} catch (Exception $e) {
    echo $e->getMessage();
}

$nom_informe = "RELACION DE PAGOS";
include_once '../../../financiero/encabezado_empresa.php';

?>
<br>
<table class="table-hover" style="width:100% !important; border-collapse: collapse; font-size: 70%;" border="1">
    <thead>
        <tr class="centrar" style="background-color:#CED3D3; color:#000000;">
            <th>No. Egreso</th>
            <th>Fecha Egreso</th>
            <th>No. Causación</th>
            <th>Tipo Doc.</th>
            <th>Documento</th>
            <th>D.V.</th>
            <th>Nombre</th>
            <th>Cód. Presupuestal</th>
            <th>Rubro</th>
            <th>Cód. Contable</th>
            <th>Objeto</th>
            <th>Cta. Bancaria</th>
            <th>Val. Bruto</th>
            <th>Deducciones</th>
            <th>Val. Neto</th>
        </tr>
    </thead>
    <tbody id="tbRelacionPagos">
        <?php
        $total_bruto = 0;
        $total_deducciones = 0;
        $total_neto = 0;

        if (!empty($datos)) {
            // === Primera pasada: calcular totales por egreso ===
            // Se agrupa por id_egreso para sumar val_bruto total y retenciones únicas por causación
            $egresos_totales = [];  // id_egreso => total_bruto del egreso
            $causaciones_ret = [];  // id_egreso => [id_causacion => retencion] (evita duplicados)

            foreach ($datos as $row) {
                $id_eg = $row['id_egreso'];
                if (!isset($egresos_totales[$id_eg])) {
                    $egresos_totales[$id_eg] = 0;
                }
                $egresos_totales[$id_eg] += $row['val_bruto'];

                // Registrar retención por causación única (evita contar doble si varias filas comparten causación)
                $id_caus = $row['id_causacion'];
                if ($id_caus && !isset($causaciones_ret[$id_eg][$id_caus])) {
                    $causaciones_ret[$id_eg][$id_caus] = $row['retencion_causacion'];
                }
            }

            // Calcular retención total por egreso (suma de retenciones únicas por causación)
            $ret_por_egreso = [];
            foreach ($causaciones_ret as $id_eg => $causaciones) {
                $ret_por_egreso[$id_eg] = array_sum($causaciones);
            }

            // === Segunda pasada: renderizar con distribución proporcional ===
            foreach ($datos as $row) {
                $id_eg = $row['id_egreso'];
                $total_bruto_egreso = $egresos_totales[$id_eg];
                $total_ret_egreso = $ret_por_egreso[$id_eg] ?? 0;

                // Distribución proporcional: deducción = retención_total × (bruto_rubro / bruto_total_egreso)
                $deduccion = ($total_bruto_egreso > 0)
                    ? round($total_ret_egreso * ($row['val_bruto'] / $total_bruto_egreso), 0)
                    : 0;
                $val_neto = $row['val_bruto'] - $deduccion;

                $total_bruto += $row['val_bruto'];
                $total_deducciones += $deduccion;
                $total_neto += $val_neto;

                echo '<tr class="resaltar">
                    <td style="text-align:center; border:#A9A9A9 1px solid;">' . $row['no_egreso'] . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid;">' . $row['fecha'] . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid;">' . $row['no_causacion'] . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid;">' . $row['tipo_doc'] . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid;">' . $row['nit_tercero'] . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid;">' . calcularDV($row['nit_tercero']) . '</td>
                    <td style="text-align:left; border:#A9A9A9 1px solid;">' . mb_strtoupper($row['nom_tercero']) . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid;">' . $row['cod_pptal'] . '</td>
                    <td style="text-align:left; border:#A9A9A9 1px solid;">' . mb_strtoupper($row['nom_rubro']) . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid;">' . $row['cod_contable'] . '</td>
                    <td style="text-align:left; border:#A9A9A9 1px solid;">' . mb_strtoupper($row['detalle']) . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid;">' . $row['cta_bancaria'] . '</td>
                    <td style="text-align:right; border:#A9A9A9 1px solid;">' . number_format($row['val_bruto'], 2) . '</td>
                    <td style="text-align:right; border:#A9A9A9 1px solid;">' . number_format($deduccion, 2) . '</td>
                    <td style="text-align:right; border:#A9A9A9 1px solid;">' . number_format($val_neto, 2) . '</td>
                </tr>';
            }
        }
        ?>
    </tbody>
    <tfoot>
        <tr style="background-color:#CED3D3; font-weight:bold;">
            <td colspan="12" style="text-align:right; border:#A9A9A9 1px solid;">TOTALES:</td>
            <td style="text-align:right; border:#A9A9A9 1px solid;"><?php echo number_format($total_bruto, 2); ?></td>
            <td style="text-align:right; border:#A9A9A9 1px solid;"><?php echo number_format($total_deducciones, 2); ?></td>
            <td style="text-align:right; border:#A9A9A9 1px solid;"><?php echo number_format($total_neto, 2); ?></td>
        </tr>
    </tfoot>
</table>