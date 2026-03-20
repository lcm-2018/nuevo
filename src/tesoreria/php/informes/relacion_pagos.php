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
$fecha_corte   = $_POST['fec_fin'];
// pto=1 => con presupuesto, egr_man=1 => egresos manuales, desc_det=1 => detalle de descuentos
$pto      = isset($_POST['pto'])      ? intval($_POST['pto'])      : 0;
$egr_man  = isset($_POST['egr_man'])  ? intval($_POST['egr_man'])  : 0;
$desc_det = isset($_POST['desc_det']) ? intval($_POST['desc_det']) : 0;

// ─── SQL base para egresos CON presupuesto ────────────────────────────────────
$sql_pto = "SELECT
    `pag`.`id_ctb_doc`                                    AS `id_egreso`
    , `cop`.`id_ctb_doc`                                  AS `id_causacion`
    , `doc_egreso`.`id_manu`                              AS `no_egreso`
    , `doc_egreso`.`detalle`
    , DATE_FORMAT(`doc_egreso`.`fecha`,'%Y-%m-%d')        AS `fecha`
    , `doc_causacion`.`id_manu`                           AS `no_causacion`
    , `tt`.`nit_tercero`
    , `tt`.`nom_tercero`
    , `ttd`.`codigo_ne`                                   AS `tipo_doc`
    , `rubro`.`cod_pptal`
    , `rubro`.`nom_rubro`
    , (IFNULL(`pag`.`valor`,0) - IFNULL(`pag`.`valor_liberado`,0)) AS `val_bruto`
    , IFNULL(`ret`.`valor_retencion`, 0)                  AS `retencion_causacion`
    , `det_pago`.`cta_bancaria`
    , `libaux`.`cod_contable`
    , 'pto'                                               AS `origen`
FROM
    `pto_pag_detalle` AS `pag`
    INNER JOIN `ctb_doc` AS `doc_egreso`
        ON (`pag`.`id_ctb_doc` = `doc_egreso`.`id_ctb_doc`)
    INNER JOIN `pto_cop_detalle` AS `cop`
        ON (`pag`.`id_pto_cop_det` = `cop`.`id_pto_cop_det`)
    INNER JOIN `ctb_doc` AS `doc_causacion`
        ON (`cop`.`id_ctb_doc` = `doc_causacion`.`id_ctb_doc`)
    INNER JOIN `pto_crp_detalle` AS `crp`
        ON (`cop`.`id_pto_crp_det` = `crp`.`id_pto_crp_det`)
    INNER JOIN `pto_cdp_detalle` AS `cdp`
        ON (`crp`.`id_pto_cdp_det` = `cdp`.`id_pto_cdp_det`)
    INNER JOIN `pto_cargue` AS `rubro`
        ON (`cdp`.`id_rubro` = `rubro`.`id_cargue`)
    LEFT JOIN `tb_terceros` AS `tt`
        ON (`pag`.`id_tercero_api` = `tt`.`id_tercero_api`)
    LEFT JOIN `tb_tipos_documento` AS `ttd`
        ON (`tt`.`tipo_doc` = `ttd`.`id_tipodoc`)
    LEFT JOIN (
        SELECT `id_ctb_doc`, GROUP_CONCAT(`cta`.`numero` SEPARATOR ', ') AS `cta_bancaria`
        FROM `tes_detalle_pago` AS `dp`
        INNER JOIN `tes_cuentas` AS `cta` ON (`dp`.`id_tes_cuenta` = `cta`.`id_tes_cuenta`)
        GROUP BY `dp`.`id_ctb_doc`
    ) AS `det_pago`
        ON (`det_pago`.`id_ctb_doc` = `doc_egreso`.`id_ctb_doc`)
    LEFT JOIN (
        SELECT `id_ctb_doc`, SUM(`valor_retencion`) AS `valor_retencion`
        FROM `ctb_causa_retencion`
        GROUP BY `id_ctb_doc`
    ) AS `ret`
        ON (`ret`.`id_ctb_doc` = `cop`.`id_ctb_doc`)
    LEFT JOIN (
        SELECT `la`.`id_ctb_doc`, GROUP_CONCAT(DISTINCT `pg`.`cuenta` SEPARATOR ', ') AS `cod_contable`
        FROM `ctb_libaux` AS `la`
        INNER JOIN `ctb_pgcp` AS `pg` ON (`la`.`id_cuenta` = `pg`.`id_pgcp`)
        WHERE `la`.`credito` > 0
        GROUP BY `la`.`id_ctb_doc`
    ) AS `libaux`
        ON (`libaux`.`id_ctb_doc` = `doc_egreso`.`id_ctb_doc`)
WHERE (
    `doc_egreso`.`estado` = 2
    AND `doc_causacion`.`estado` = 2
    AND DATE_FORMAT(`doc_egreso`.`fecha`,'%Y-%m-%d') BETWEEN '$fecha_inicial' AND '$fecha_corte'
)";

// ─── SQL base para egresos MANUALES (sin presupuesto) ─────────────────────────
$sql_egr = "SELECT
    `doc_egreso`.`id_ctb_doc`                              AS `id_egreso`
    , NULL                                                 AS `id_causacion`
    , `doc_egreso`.`id_manu`                               AS `no_egreso`
    , `doc_egreso`.`detalle`
    , DATE_FORMAT(`doc_egreso`.`fecha`, '%Y-%m-%d')        AS `fecha`
    , NULL                                                 AS `no_causacion`
    , `tt`.`nit_tercero`
    , `tt`.`nom_tercero`
    , `ttd`.`codigo_ne`                                    AS `tipo_doc`
    , NULL                                                 AS `cod_pptal`
    , NULL                                                 AS `nom_rubro`
    , `libaux`.`valor`                                     AS `val_bruto`
    , 0                                                    AS `retencion_causacion`
    , `det_pago`.`cta_bancaria`
    , `libaux`.`cod_contable`
    , 'egr_man'                                            AS `origen`
FROM
    `ctb_doc` AS `doc_egreso`
    LEFT JOIN `pto_pag_detalle` AS `pag`
        ON (`pag`.`id_ctb_doc` = `doc_egreso`.`id_ctb_doc`)
    INNER JOIN (
        SELECT
            `la`.`id_ctb_doc`,
            GROUP_CONCAT(DISTINCT `pg`.`cuenta` SEPARATOR ', ') AS `cod_contable`,
            SUM(`la`.`credito`) AS `valor`
        FROM `ctb_libaux` `la`
        INNER JOIN `ctb_pgcp` `pg`
            ON (`pg`.`id_pgcp` = `la`.`id_cuenta`)
        WHERE `la`.`credito` > 0
        GROUP BY `la`.`id_ctb_doc`
    ) AS `libaux`
        ON (`libaux`.`id_ctb_doc` = `doc_egreso`.`id_ctb_doc`)
    LEFT JOIN `tb_terceros` AS `tt`
        ON (`doc_egreso`.`id_tercero` = `tt`.`id_tercero_api`)
    LEFT JOIN `tb_tipos_documento` AS `ttd`
        ON (`tt`.`tipo_doc` = `ttd`.`id_tipodoc`)
    LEFT JOIN (
        SELECT `id_ctb_doc`, GROUP_CONCAT(`cta`.`numero` SEPARATOR ', ') AS `cta_bancaria`
        FROM `tes_detalle_pago` AS `dp`
        INNER JOIN `tes_cuentas` AS `cta` ON (`dp`.`id_tes_cuenta` = `cta`.`id_tes_cuenta`)
        GROUP BY `dp`.`id_ctb_doc`
    ) AS `det_pago`
        ON (`det_pago`.`id_ctb_doc` = `doc_egreso`.`id_ctb_doc`)
WHERE
    `doc_egreso`.`id_tipo_doc` = 4
    AND `doc_egreso`.`estado` = 2
    AND DATE_FORMAT(`doc_egreso`.`fecha`, '%Y-%m-%d') BETWEEN '$fecha_inicial' AND '$fecha_corte'
    AND `pag`.`id_pto_pag_det` IS NULL";

// ─── Construir la consulta final según las opciones marcadas ──────────────────
$cmd = \Config\Clases\Conexion::getConexion();
try {
    if ($pto == 1 && $egr_man == 1) {
        // Ambos: UNION ordenada por fecha
        $sql = "($sql_pto) UNION ALL ($sql_egr) ORDER BY `fecha` ASC, `no_egreso` ASC";
    } elseif ($pto == 1) {
        $sql = "$sql_pto ORDER BY `doc_egreso`.`fecha` ASC, `doc_egreso`.`id_manu` ASC";
    } elseif ($egr_man == 1) {
        $sql = "$sql_egr ORDER BY `doc_egreso`.`fecha` ASC, `doc_egreso`.`id_manu` ASC";
    } else {
        // Por defecto si ninguno está marcado, mostrar con presupuesto
        $sql = "$sql_pto ORDER BY `doc_egreso`.`fecha` ASC, `doc_egreso`.`id_manu` ASC";
    }

    $res   = $cmd->query($sql);
    $datos = $res->fetchAll();
    $res->closeCursor();
    unset($res);

    // ── Si desc_det=1: cargar retenciones detalladas por causación ────────────
    // id_retencion_tipo: 1=Ret.fuente, 2=Ret.IVA, 3=Ret.ICA, 4=Sobretasa, 5=Estampillas, 6=Otras
    $ret_detalle = [];
    if ($desc_det == 1) {
        // Recolectar todos los id_causacion no-null
        $ids_causacion = array_filter(array_unique(array_column($datos, 'id_causacion')));
        if (!empty($ids_causacion)) {
            $placeholders = implode(',', array_map('intval', $ids_causacion));
            $sql_ret = "SELECT
                `cr`.`id_ctb_doc`,
                IFNULL(`rt`.`tipo`, '') AS `tipo_nombre`,
                SUM(`cr`.`valor_retencion`) AS `valor`
            FROM `ctb_causa_retencion` AS `cr`
            LEFT JOIN `ctb_retencion_rango` AS `rng` ON (`cr`.`id_rango` = `rng`.`id_rango`)
            LEFT JOIN `ctb_retenciones`     AS `r`   ON (`rng`.`id_retencion` = `r`.`id_retencion`)
            LEFT JOIN `ctb_retencion_tipo`  AS `rt`  ON (`r`.`id_retencion_tipo` = `rt`.`id_retencion_tipo`)
            WHERE `cr`.`id_ctb_doc` IN ($placeholders)
            GROUP BY `cr`.`id_ctb_doc`, `r`.`id_retencion_tipo`";
            $res_ret = $cmd->query($sql_ret);
            $filas_ret = $res_ret->fetchAll();
            $res_ret->closeCursor();
            // Indexar por id_ctb_doc (=id_causacion) y nombre tipo
            foreach ($filas_ret as $fr) {
                $idc  = $fr['id_ctb_doc'];
                $tipo = mb_strtolower($fr['tipo_nombre']);
                $ret_detalle[$idc][$tipo] = ($ret_detalle[$idc][$tipo] ?? 0) + floatval($fr['valor']);
            }
        }
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

$nom_informe = "RELACION DE PAGOS";
include_once '../../../financiero/encabezado_empresa.php';

// ─── Mapeo de tipos de retención a columnas de encabezado ──────────────────
// Se usan las claves en minúsculas tal como vienen de la BD (campo 'tipo')
$tipos_col = [
    'retención en la fuente' => 'Ret. Fuente',
    'retención de iva'       => 'Ret. IVA',
    'retención de ica'       => 'Ret. ICA',
    'sobretasa bomberil'     => 'Sobretasa<br>Bomberil',
    'estampillas'            => 'Estampillas',
    'otras retenciones'      => 'Otras<br>Retenciones',
];
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
            <?php if ($desc_det == 1): ?>
                <?php foreach ($tipos_col as $llave => $etiqueta): ?>
                    <th style="background-color:#FFFF99;"><?php echo $etiqueta; ?></th>
                <?php endforeach; ?>
            <?php else: ?>
                <th>Deducciones</th>
            <?php endif; ?>
            <th>Val. Neto</th>
        </tr>
    </thead>
    <tbody id="tbRelacionPagos">
        <?php
        $total_bruto       = 0;
        $total_deducciones = 0;
        $total_neto        = 0;
        $totales_tipo      = array_fill_keys(array_keys($tipos_col), 0);

        if (!empty($datos)) {
            // === Primera pasada: calcular totales por egreso ===
            $egresos_totales = [];
            $causaciones_ret = [];

            foreach ($datos as $row) {
                $id_eg = $row['id_egreso'];
                if (!isset($egresos_totales[$id_eg])) {
                    $egresos_totales[$id_eg] = 0;
                }
                $egresos_totales[$id_eg] += floatval($row['val_bruto']);

                $id_caus = $row['id_causacion'];
                if ($id_caus && !isset($causaciones_ret[$id_eg][$id_caus])) {
                    $causaciones_ret[$id_eg][$id_caus] = floatval($row['retencion_causacion']);
                }
            }

            $ret_por_egreso = [];
            foreach ($causaciones_ret as $id_eg => $causaciones) {
                $ret_por_egreso[$id_eg] = array_sum($causaciones);
            }

            // === Segunda pasada: renderizar ===
            foreach ($datos as $row) {
                $id_eg               = $row['id_egreso'];
                $id_caus             = $row['id_causacion'];
                $total_bruto_egreso  = $egresos_totales[$id_eg];
                $total_ret_egreso    = $ret_por_egreso[$id_eg] ?? 0;

                // Distribución proporcional de la deducción total
                $deduccion = ($total_bruto_egreso > 0)
                    ? round($total_ret_egreso * (floatval($row['val_bruto']) / $total_bruto_egreso), 0)
                    : 0;
                $val_neto  = floatval($row['val_bruto']) - $deduccion;

                $total_bruto       += floatval($row['val_bruto']);
                $total_deducciones += $deduccion;
                $total_neto        += $val_neto;

                echo '<tr class="resaltar">
                    <td style="text-align:center; border:#A9A9A9 1px solid;">'  . $row['no_egreso']                               . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid;">'  . $row['fecha']                                   . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid;">'  . $row['no_causacion']                            . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid;">'  . $row['tipo_doc']                                . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid; mso-number-format:\@;">' . $row['nit_tercero']        . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid;">'  . calcularDV($row['nit_tercero'])                 . '</td>
                    <td style="text-align:left;   border:#A9A9A9 1px solid;">'  . mb_strtoupper($row['nom_tercero'])              . '</td>
                    <td style="text-align:right;  border:#A9A9A9 1px solid; mso-number-format:\@;">' . $row['cod_pptal']          . '</td>
                    <td style="text-align:left;   border:#A9A9A9 1px solid;">'  . mb_strtoupper($row['nom_rubro'])                . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid; mso-number-format:\@;">' . $row['cod_contable']       . '</td>
                    <td style="text-align:left;   border:#A9A9A9 1px solid;">'  . mb_strtoupper($row['detalle'])                  . '</td>
                    <td style="text-align:center; border:#A9A9A9 1px solid; mso-number-format:\@;">' . $row['cta_bancaria']       . '</td>
                    <td style="text-align:right;  border:#A9A9A9 1px solid;">'  . number_format(floatval($row['val_bruto']), 2)   . '</td>';

                if ($desc_det == 1) {
                    // Detalle por tipo de retención
                    $ret_caus = ($id_caus && isset($ret_detalle[$id_caus])) ? $ret_detalle[$id_caus] : [];
                    foreach ($tipos_col as $llave => $etiqueta) {
                        $val_tipo = $ret_caus[$llave] ?? 0;
                        // Distribuir proporcionalmente si el egreso tiene varios rubros
                        $val_tipo_prop = ($total_bruto_egreso > 0)
                            ? round($val_tipo * (floatval($row['val_bruto']) / $total_bruto_egreso), 0)
                            : $val_tipo;
                        $totales_tipo[$llave] += $val_tipo_prop;
                        echo '<td style="text-align:right; border:#A9A9A9 1px solid; background-color:#FFFFF0;">'
                            . number_format($val_tipo_prop, 2)
                            . '</td>';
                    }
                } else {
                    echo '<td style="text-align:right; border:#A9A9A9 1px solid;">' . number_format($deduccion, 2) . '</td>';
                }

                echo '<td style="text-align:right; border:#A9A9A9 1px solid;">' . number_format($val_neto, 2) . '</td>
                </tr>';
            }
        }
        ?>
    </tbody>
    <tfoot>
        <tr style="background-color:#CED3D3; font-weight:bold;">
            <td colspan="12" style="text-align:right; border:#A9A9A9 1px solid;">TOTALES:</td>
            <td style="text-align:right; border:#A9A9A9 1px solid;"><?php echo number_format($total_bruto, 2); ?></td>
            <?php if ($desc_det == 1): ?>
                <?php foreach ($tipos_col as $llave => $etiqueta): ?>
                    <td style="text-align:right; border:#A9A9A9 1px solid; background-color:#FFFF99;"><?php echo number_format($totales_tipo[$llave], 2); ?></td>
                <?php endforeach; ?>
            <?php else: ?>
                <td style="text-align:right; border:#A9A9A9 1px solid;"><?php echo number_format($total_deducciones, 2); ?></td>
            <?php endif; ?>
            <td style="text-align:right; border:#A9A9A9 1px solid;"><?php echo number_format($total_neto, 2); ?></td>
        </tr>
    </tfoot>
</table>