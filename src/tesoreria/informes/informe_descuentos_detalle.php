<?php
session_start();
set_time_limit(5600);
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Reporte de descuentos por cuenta en un periodo</title>
    <style>
        .text {
            mso-number-format: "\@"
        }

        .centrar {
            text-align: center;
        }

        .resaltar:nth-child(even) {
            background-color: #F8F9F9;
        }

        .resaltar:nth-child(odd) {
            background-color: #ffffff;
        }
    </style>
    <?php
    header("Content-type: application/vnd.ms-excel charset=utf-8");
    header("Content-Disposition: attachment; filename=Reporte de Descuentos.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    ?>
</head>
<?php
ini_set('max_execution_time', 5600);
include '../../../config/autoloader.php';

$fecha_inicial = $_POST['fecha_inicial'];
$fecha_corte = $_POST['fecha_final'];

$cmd = \Config\Clases\Conexion::getConexion();

try {
    // 1. Obtener pagos (egresos) en el periodo que están vinculados a una causación presupuestal
    $sql_egresos_periodo = "SELECT DISTINCT `pag`.`id_pto_cop_det`
        FROM `pto_pag_detalle` AS `pag`
        INNER JOIN `ctb_doc` AS `doc_egreso` ON `pag`.`id_ctb_doc` = `doc_egreso`.`id_ctb_doc`
        WHERE `doc_egreso`.`estado` = 2
          AND `doc_egreso`.`id_tipo_doc` = 4
          AND `doc_egreso`.`fecha` BETWEEN '$fecha_inicial' AND '$fecha_corte'";
    
    $res = $cmd->query($sql_egresos_periodo);
    $cop_periodo = $res->fetchAll(PDO::FETCH_COLUMN);
    $res->closeCursor();

    $bancos_resumen = [];
    $totales_banco = [];

    if (!empty($cop_periodo)) {
        $in_cop = implode(',', array_map('intval', $cop_periodo));

        // 2. Consultar TODOS los pagos (históricos y actuales) de esas causaciones
        $sql_todos_pagos = "SELECT
                `pag`.`id_pto_pag_det`,
                `pag`.`id_pto_cop_det`,
                `pag`.`id_ctb_doc` AS `id_egreso`,
                `doc_egreso`.`fecha` AS `fecha_egreso`,
                (`pag`.`valor` - IFNULL(`pag`.`valor_liberado`,0)) AS `valor_pago`,
                `cop`.`id_ctb_doc` AS `id_causacion`,
                (`cop`.`valor` - IFNULL(`cop`.`valor_liberado`,0)) AS `valor_causacion`
            FROM `pto_pag_detalle` AS `pag`
            INNER JOIN `ctb_doc` AS `doc_egreso` ON `pag`.`id_ctb_doc` = `doc_egreso`.`id_ctb_doc`
            INNER JOIN `pto_cop_detalle` AS `cop` ON `pag`.`id_pto_cop_det` = `cop`.`id_pto_cop_det`
            WHERE `pag`.`id_pto_cop_det` IN ($in_cop)
              AND `doc_egreso`.`estado` = 2
            ORDER BY `pag`.`id_pto_cop_det`, `doc_egreso`.`fecha` ASC, `pag`.`id_pto_pag_det` ASC";
        
        $res = $cmd->query($sql_todos_pagos);
        $todos_pagos = $res->fetchAll(PDO::FETCH_ASSOC);
        $res->closeCursor();

        // 3. Obtener los id_causacion para buscar los descuentos
        $id_causaciones = array_unique(array_column($todos_pagos, 'id_causacion'));
        $in_causaciones = implode(',', array_map('intval', $id_causaciones));
        
        $descuentos_causacion = [];
        if (!empty($id_causaciones)) {
            // Descuentos en causación: créditos sin valor en ref
            $sql_desc = "SELECT
                    `la`.`id_ctb_doc` AS `id_causacion`,
                    `pg`.`cuenta`,
                    `pg`.`nombre`,
                    SUM(`la`.`credito`) AS `valor_descuento`
                FROM `ctb_libaux` AS `la`
                INNER JOIN `ctb_pgcp` AS `pg` ON `la`.`id_cuenta` = `pg`.`id_pgcp`
                WHERE `la`.`id_ctb_doc` IN ($in_causaciones)
                  AND `la`.`credito` > 0
                  AND (`la`.`ref` IS NULL OR `la`.`ref` = 0)
                GROUP BY `la`.`id_ctb_doc`, `pg`.`cuenta`, `pg`.`nombre`";
            $res = $cmd->query($sql_desc);
            $filas_desc = $res->fetchAll(PDO::FETCH_ASSOC);
            $res->closeCursor();

            foreach ($filas_desc as $f) {
                $idc = $f['id_causacion'];
                $concepto = $f['nombre'] . ' (' . $f['cuenta'] . ')';
                $descuentos_causacion[$idc][] = [
                    'concepto' => $f['nombre'],
                    'cuenta' => $f['cuenta'],
                    'valor' => floatval($f['valor_descuento'])
                ];
            }
        }

        // 4. Obtener bancos de los pagos que están EN el periodo
        $id_egresos_periodo = [];
        foreach ($todos_pagos as $p) {
            if ($p['fecha_egreso'] >= $fecha_inicial && $p['fecha_egreso'] <= $fecha_corte) {
                $id_egresos_periodo[] = $p['id_egreso'];
            }
        }
        $id_egresos_periodo = array_unique($id_egresos_periodo);
        
        $bancos_pago = [];
        if (!empty($id_egresos_periodo)) {
            $in_egresos = implode(',', array_map('intval', $id_egresos_periodo));
            $sql_bancos = "SELECT
                    `dp`.`id_ctb_doc` AS `id_egreso`,
                    `cta`.`numero` AS `cta_bancaria`,
                    `cta`.`nombre` AS `nom_banco`
                FROM `tes_detalle_pago` AS `dp`
                INNER JOIN `tes_cuentas` AS `cta` ON `dp`.`id_tes_cuenta` = `cta`.`id_tes_cuenta`
                WHERE `dp`.`id_ctb_doc` IN ($in_egresos)";
            $res = $cmd->query($sql_bancos);
            $filas_bancos = $res->fetchAll(PDO::FETCH_ASSOC);
            $res->closeCursor();

            foreach ($filas_bancos as $fb) {
                $bancos_pago[$fb['id_egreso']] = $fb['nom_banco'] . ' - ' . $fb['cta_bancaria'];
            }
        }

        // 5. Procesar pagos ordenados y aplicar regla
        // Agrupar pagos por causación para control de acumulados
        $pagos_por_cop = [];
        foreach ($todos_pagos as $p) {
            $pagos_por_cop[$p['id_pto_cop_det']][] = $p;
        }

        foreach ($pagos_por_cop as $id_cop => $pagos) {
            $valor_causacion = floatval($pagos[0]['valor_causacion']);
            $id_causacion = $pagos[0]['id_causacion'];
            $acumulado_pago = 0;
            $descuentos_asignados = []; // Para llevar el rastro por cada concepto: $descuentos_asignados[$concepto] = valor
            
            $lista_desc = isset($descuentos_causacion[$id_causacion]) ? $descuentos_causacion[$id_causacion] : [];

            foreach ($pagos as $pago) {
                $val_pago = floatval($pago['valor_pago']);
                $acumulado_pago += $val_pago;
                
                // Determinar si es el último pago
                // Se tolera una pequeña diferencia por decimales para considerarlo último pago
                $es_ultimo_pago = ($acumulado_pago >= $valor_causacion - 0.01);
                
                // Verificar si este pago está en el periodo
                $en_periodo = ($pago['fecha_egreso'] >= $fecha_inicial && $pago['fecha_egreso'] <= $fecha_corte);
                
                $id_egr = $pago['id_egreso'];
                $banco = isset($bancos_pago[$id_egr]) ? $bancos_pago[$id_egr] : 'Otras cuentas';

                foreach ($lista_desc as $desc) {
                    $clave_concepto = $desc['concepto'];
                    $valor_total_desc = $desc['valor'];
                    $ya_asignado = isset($descuentos_asignados[$clave_concepto]) ? $descuentos_asignados[$clave_concepto] : 0;
                    
                    $descuento_pago = 0;

                    if ($valor_causacion > 0) {
                        if ($es_ultimo_pago) {
                            // Por diferencia
                            $descuento_pago = $valor_total_desc - $ya_asignado;
                        } else {
                            // Proporcional
                            $descuento_pago = round($valor_total_desc * ($val_pago / $valor_causacion), 2);
                        }
                    }

                    // Acumulamos para la causacion
                    if (!isset($descuentos_asignados[$clave_concepto])) {
                        $descuentos_asignados[$clave_concepto] = 0;
                    }
                    $descuentos_asignados[$clave_concepto] += $descuento_pago;

                    // Si el pago está en el periodo, lo añadimos al reporte
                    if ($en_periodo && $descuento_pago != 0) {
                        if (!isset($bancos_resumen[$banco][$clave_concepto])) {
                            $bancos_resumen[$banco][$clave_concepto] = 0;
                        }
                        $bancos_resumen[$banco][$clave_concepto] += $descuento_pago;
                        
                        if (!isset($totales_banco[$banco])) {
                            $totales_banco[$banco] = 0;
                        }
                        $totales_banco[$banco] += $descuento_pago;
                    }
                }
            }
        }
    }

    // Ordenar bancos (Otras cuentas al final)
    ksort($bancos_resumen);
    if (isset($bancos_resumen['Otras cuentas'])) {
        $otras = $bancos_resumen['Otras cuentas'];
        unset($bancos_resumen['Otras cuentas']);
        $bancos_resumen['Otras cuentas'] = $otras;
    }
    
} catch (Exception $e) {
    echo $e->getMessage();
    exit;
}

// Datos de empresa
try {
    $sql_emp = "SELECT `razon_social_ips` AS `nombre`, `nit_ips` AS `nit`, `dv` AS `dig_ver` FROM `tb_datos_ips`";
    $res_emp = $cmd->query($sql_emp);
    $empresa = $res_emp->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $empresa = ['nombre' => '', 'nit' => '', 'dig_ver' => ''];
}

$nom_informe = "Reporte de descuentos por cuenta en un periodo";
?>
<div class="contenedor bg-light" id="areaImprimir">
    <div class="px-2" style="width:90% !important; margin: 0 auto;">
        <br>
        <table style="width:100% !important;" border="0">
            <tr>
                <td colspan="2" style="text-align:center"><?php echo $empresa['nombre']; ?></td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:center"><?php echo $empresa['nit'] . '-' . $empresa['dig_ver']; ?></td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:center"><?php echo $nom_informe; ?></td>
            </tr>
        </table>
        <br>
        <table style="width:100% !important; border-collapse: collapse;" border="1">
            <tr>
                <td><strong>Fecha Inicial:</strong></td>
                <td style="text-align:left"><?php echo $fecha_inicial; ?></td>
                <td><strong>Fecha Corte:</strong></td>
                <td style="text-align:left"><?php echo $fecha_corte; ?></td>
            </tr>
        </table>
        <br>

        <?php if (!empty($bancos_resumen)): ?>
            <?php foreach ($bancos_resumen as $banco => $conceptos): ?>
                <?php
                // Ordenar conceptos alfabéticamente
                ksort($conceptos);
                $total_banco = isset($totales_banco[$banco]) ? $totales_banco[$banco] : 0;
                ?>
                <h3 style="margin-bottom: 5px;"><?php echo mb_convert_encoding($banco, 'UTF-8'); ?></h3>
                <p style="margin-top: 0;"><strong>Total:</strong> <?php echo number_format($total_banco, 2, '.', ','); ?></p>
                <table class="table-bordered bg-light" style="width:100% !important; border-collapse: collapse;" border="1">
                    <thead>
                        <tr class="centrar">
                            <th>Concepto</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($conceptos as $concepto => $valor): ?>
                            <tr class="resaltar">
                                <td class="text"><?php echo mb_convert_encoding($concepto, 'UTF-8'); ?></td>
                                <td class="text" style="text-align: right;"><?php echo number_format($valor, 2, '.', ','); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <br><br>
            <?php endforeach; ?>
        <?php else: ?>
            <table class="table-bordered bg-light" style="width:100% !important; border-collapse: collapse;" border="1">
                <tr><td style="text-align:center; padding: 20px;">No hay datos para el periodo seleccionado</td></tr>
            </table>
        <?php endif; ?>
    </div>
</div>

</html>
