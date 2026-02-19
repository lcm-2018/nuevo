<?php

use Config\Clases\Plantilla;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
$vigencia = $_SESSION['vigencia'];
$fecha_corte = $_POST['fecha_corte'];
$detalle_mes = $_POST['mes'];
$fecha_ini = $_POST['fecha_ini'];
$mes = date("m", strtotime($fecha_corte));
$fecha_ini_mes = date("Y-m-d", strtotime($_SESSION['vigencia'] . '-' . $mes . '-01'));
// Último día del mes anterior a la fecha de corte
$fecha_fin_mes_ant = date("Y-m-d", strtotime($fecha_ini_mes . ' -1 day'));
$id_vigencia = $_SESSION['id_vigencia'];
$total_cols = $detalle_mes == '1' ? 20 : 14;
function pesos($valor)
{
    return number_format($valor, 2, ".", ",");
}
include '../../../config/autoloader.php';
include '../../financiero/consultas.php';
$cmd = \Config\Clases\Conexion::getConexion();

// CONSULTA OPTIMIZADA CON CTEs Y SELF-JOIN PARA ACUMULADOS
$sql = "WITH 
    -- CTE para modificaciones (adiciones y reducciones)
    modificaciones AS (
        SELECT
            pmd.id_cargue,
            SUM(CASE 
                WHEN pm.id_tipo_mod = 2 AND pm.fecha BETWEEN :fecha_ini AND :fecha_corte 
                THEN pmd.valor_deb 
                ELSE 0 
            END) AS val_adicion,
            SUM(CASE 
                WHEN pm.id_tipo_mod = 3 AND pm.fecha BETWEEN :fecha_ini AND :fecha_corte 
                THEN pmd.valor_cred 
                ELSE 0 
            END) AS val_reduccion,
            SUM(CASE 
                WHEN pm.id_tipo_mod = 2 AND pm.fecha BETWEEN :fecha_ini_mes AND :fecha_corte 
                THEN pmd.valor_deb 
                ELSE 0 
            END) AS val_adicion_mes,
            SUM(CASE 
                WHEN pm.id_tipo_mod = 3 AND pm.fecha BETWEEN :fecha_ini_mes AND :fecha_corte 
                THEN pmd.valor_cred 
                ELSE 0 
            END) AS val_reduccion_mes
        FROM pto_mod_detalle pmd
        INNER JOIN pto_mod pm ON pmd.id_pto_mod = pm.id_pto_mod
        WHERE pm.estado = 2 
            AND pm.id_tipo_mod IN (2, 3)
            AND pm.fecha BETWEEN :fecha_ini AND :fecha_corte
        GROUP BY pmd.id_cargue
    ),
    -- CTE para reconocimientos
    reconocimientos AS (
        SELECT
            prd.id_rubro,
            SUM(CASE 
                WHEN pr.fecha BETWEEN :fecha_ini AND :fecha_fin_mes_ant 
                THEN IFNULL(prd.valor, 0) - IFNULL(prd.valor_liberado, 0)
                ELSE 0 
            END) AS val_reconocimiento_ant,
            SUM(CASE 
                WHEN pr.fecha BETWEEN :fecha_ini_mes AND :fecha_corte 
                THEN IFNULL(prd.valor, 0)
                ELSE 0 
            END) AS val_reconocimiento_mes,
            SUM(CASE 
                WHEN pr.fecha BETWEEN :fecha_ini_mes AND :fecha_corte 
                THEN IFNULL(prd.valor_liberado, 0)
                ELSE 0 
            END) AS val_liberado_mes
        FROM pto_rad_detalle prd
        INNER JOIN pto_rad pr ON prd.id_pto_rad = pr.id_pto_rad
        WHERE pr.estado = 2
            AND pr.fecha BETWEEN :fecha_ini AND :fecha_corte
        GROUP BY prd.id_rubro
    ),
    -- CTE para recaudos
    recaudos AS (
        SELECT
            COALESCE(prd.id_rubro, prdd.id_rubro) AS id_rubro,
            SUM(CASE 
                WHEN pr.fecha BETWEEN :fecha_ini AND :fecha_fin_mes_ant 
                THEN IFNULL(prd.valor, 0) - IFNULL(prd.valor_liberado, 0)
                ELSE 0 
            END) AS val_recaudo_ant,
            SUM(CASE 
                WHEN pr.fecha BETWEEN :fecha_ini_mes AND :fecha_corte 
                THEN IFNULL(prd.valor, 0)
                ELSE 0 
            END) AS val_recaudo_mes,
            SUM(CASE 
                WHEN pr.fecha BETWEEN :fecha_ini_mes AND :fecha_corte 
                THEN IFNULL(prd.valor_liberado, 0)
                ELSE 0 
            END) AS val_recaudo_liberado_mes
        FROM pto_rec_detalle prd
        INNER JOIN pto_rec pr ON prd.id_pto_rac = pr.id_pto_rec
        LEFT JOIN pto_rad_detalle prdd ON prd.id_pto_rad_detalle = prdd.id_pto_rad_det
        WHERE pr.estado = 2
            AND pr.fecha BETWEEN :fecha_ini AND :fecha_corte
        GROUP BY COALESCE(prd.id_rubro, prdd.id_rubro)
    ),
    -- CTE base con valores individuales
    base_calculos AS (
        SELECT 
            pc.id_cargue,
            pc.cod_pptal,
            pc.nom_rubro,
            pc.tipo_dato,
            pc.valor_aprobado AS inicial,
            IFNULL(m.val_adicion, 0) AS val_adicion,
            IFNULL(m.val_reduccion, 0) AS val_reduccion,
            IFNULL(rc.val_recaudo_ant, 0) AS val_recaudo_ant,
            IFNULL(rk.val_reconocimiento_ant, 0) AS val_reconocimiento_ant,
            IFNULL(m.val_adicion_mes, 0) AS val_adicion_mes,
            IFNULL(m.val_reduccion_mes, 0) AS val_reduccion_mes,
            IFNULL(rc.val_recaudo_mes, 0) AS val_recaudo_mes,
            IFNULL(rc.val_recaudo_liberado_mes, 0) AS val_recaudo_liberado_mes,
            IFNULL(rk.val_reconocimiento_mes, 0) AS val_reconocimiento_mes,
            IFNULL(rk.val_liberado_mes, 0) AS val_liberado_mes
        FROM pto_cargue pc
        INNER JOIN pto_presupuestos pp ON pc.id_pto = pp.id_pto
        LEFT JOIN modificaciones m ON m.id_cargue = pc.id_cargue
        LEFT JOIN recaudos rc ON rc.id_rubro = pc.id_cargue
        LEFT JOIN reconocimientos rk ON rk.id_rubro = pc.id_cargue
        WHERE pp.id_tipo = 1 
            AND pp.id_vigencia = :id_vigencia
    )
    -- Consulta principal acumulando jerárquicamente
    SELECT 
        parent.cod_pptal,
        parent.nom_rubro,
        parent.tipo_dato,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.inicial ELSE 0 END), 0) AS inicial,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_adicion ELSE 0 END), 0) AS adicion,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_reduccion ELSE 0 END), 0) AS reduccion,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_recaudo_ant ELSE 0 END), 0) AS recaudo_ant,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_reconocimiento_ant ELSE 0 END), 0) AS reconocimiento_ant,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_adicion_mes ELSE 0 END), 0) AS adicion_mes,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_reduccion_mes ELSE 0 END), 0) AS reduccion_mes,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_recaudo_mes ELSE 0 END), 0) AS recaudo_mes,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_recaudo_liberado_mes ELSE 0 END), 0) AS recaudo_liberado_mes,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_reconocimiento_mes ELSE 0 END), 0) AS reconocimiento_mes,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_liberado_mes ELSE 0 END), 0) AS liberado_mes
    FROM base_calculos parent
    LEFT JOIN base_calculos child ON child.cod_pptal LIKE CONCAT(parent.cod_pptal, '%')
    GROUP BY parent.cod_pptal, parent.nom_rubro, parent.tipo_dato
    ORDER BY parent.cod_pptal ASC";

try {
    $datos_sedes = obtenerSedesActivas($cmd);
    $sedes = $datos_sedes['sedes'];
    if (empty($sedes)) {
        $sedes = [['es_principal' => 1, 'nom_sede' => 'PRINCIPAL']];
    }

    $rubros_consolidados = [];
    foreach ($sedes as $sede) {
        if ($sede['es_principal'] == 1) {
            $cmd_sede = $cmd;
        } else {
            $cmd_sede = conectarSede($sede['bd_sede']);
            if ($cmd_sede === null) {
                error_log("No se pudo conectar a la sede {$sede['nom_sede']} para ejecución de ingresos");
                continue;
            }
        }

        try {
            $stmt = $cmd_sede->prepare($sql);
            $stmt->bindParam(':fecha_ini', $fecha_ini, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_corte', $fecha_corte, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_ini_mes', $fecha_ini_mes, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_fin_mes_ant', $fecha_fin_mes_ant, PDO::PARAM_STR);
            $stmt->bindParam(':id_vigencia', $id_vigencia, PDO::PARAM_INT);
            $stmt->execute();
            $rubros_sede = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rubros_sede as $registro) {
                $clave = $registro['cod_pptal'];
                if (!isset($rubros_consolidados[$clave])) {
                    $rubros_consolidados[$clave] = $registro;
                } else {
                    // 'inicial' solo se toma de la sede principal (es_principal = 1),
                    // no se acumula de las sedes secundarias.
                    $rubros_consolidados[$clave]['adicion'] += $registro['adicion'];
                    $rubros_consolidados[$clave]['reduccion'] += $registro['reduccion'];
                    $rubros_consolidados[$clave]['recaudo_ant'] += $registro['recaudo_ant'];
                    $rubros_consolidados[$clave]['reconocimiento_ant'] += $registro['reconocimiento_ant'];
                    $rubros_consolidados[$clave]['adicion_mes'] += $registro['adicion_mes'];
                    $rubros_consolidados[$clave]['reduccion_mes'] += $registro['reduccion_mes'];
                    $rubros_consolidados[$clave]['recaudo_mes'] += $registro['recaudo_mes'];
                    $rubros_consolidados[$clave]['recaudo_liberado_mes'] += $registro['recaudo_liberado_mes'];
                    $rubros_consolidados[$clave]['reconocimiento_mes'] += $registro['reconocimiento_mes'];
                    $rubros_consolidados[$clave]['liberado_mes'] += $registro['liberado_mes'];
                }
            }
            unset($stmt);
        } catch (PDOException $e) {
            error_log("Error consultando ejecución de ingresos en sede {$sede['nom_sede']}: " . $e->getMessage());
        }

        if ($sede['es_principal'] != 1) {
            $cmd_sede = null;
        }
    }

    $rubros = array_values($rubros_consolidados);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
// Reindexar el array para usarlo en la vista (cod_pptal como clave)
$acum = [];
foreach ($rubros as $rb) {
    $acum[$rb['cod_pptal']] = $rb;
}

try {
    $sql = "SELECT
                 `razon_social_ips`AS `nombre`, `nit_ips` AS `nit`, `dv` AS `dig_ver`
            FROM
                `tb_datos_ips`";
    $res = $cmd->query($sql);
    $empresa = $res->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
<style>
    .resaltar:nth-child(even) {
        background-color: #F8F9F9;
    }

    .resaltar:nth-child(odd) {
        background-color: #ffffff;
    }
</style>
<table style="width:100% !important; border-collapse: collapse;" class="table-hover" border="1">
    <thead>
        <tr>
            <td rowspan="4" style="text-align:center"><label class="small"><img src="<?= Plantilla::getHost() ?>/assets/images/logo.png" width="100"></label></td>
            <td colspan="<?= $total_cols ?>" style="text-align:center"><?php echo $empresa['nombre']; ?></td>
        </tr>
        <tr>
            <td colspan="<?= $total_cols ?>" style="text-align:center"><?php echo $empresa['nit'] . '-' . $empresa['dig_ver']; ?></td>
        </tr>
        <tr>
            <td colspan="<?= $total_cols ?>" style="text-align:center"><?php echo 'EJECUCION PRESUPUESTAL DE INGRESOS'; ?></td>
        </tr>
        <tr>
            <td colspan="<?= $total_cols ?>" style="text-align:center"><?php echo 'Fecha de corte: ' . $fecha_corte; ?></td>
        </tr>
        <!-- Fila 1: Encabezados de grupo -->
        <tr style="background-color: #CED3D3; text-align:center;font-size:9px;" border="1">
            <td rowspan="2">Código</td>
            <td rowspan="2">Nombre</td>
            <td rowspan="2">Tipo</td>
            <td rowspan="2">Inicial</td>
            <?php if ($detalle_mes == '1'): ?>
                <td colspan="2">Adiciones</td>
                <td colspan="2">Reducciones</td>
            <?php else: ?>
                <td rowspan="2">Adiciones</td>
                <td rowspan="2">Reducciones</td>
            <?php endif; ?>
            <td rowspan="2">Definitivo</td>
            <?php if ($detalle_mes == '1'): ?>
                <td colspan="4">Reconocimiento</td>
                <td colspan="4">Recaudo</td>
            <?php else: ?>
                <td colspan="2">Reconocimiento</td>
                <td colspan="2">Recaudo</td>
            <?php endif; ?>
            <td rowspan="2">% Ejec</td>
            <td rowspan="2">Por Ejecutar</td>
            <td rowspan="2">Ctas por Cobrar</td>
        </tr>
        <!-- Fila 2: Sub-encabezados -->
        <tr style="background-color: #CED3D3; text-align:center;font-size:9px;" border="1">
            <?php if ($detalle_mes == '1'): ?>
                <td>Mes</td>
                <td>Acumulada</td>
                <td>Mes</td>
                <td>Acumulada</td>
            <?php endif; ?>
            <td>Saldo ant.</td>
            <?= $detalle_mes == '1' ? '<td>Mes</td>' : ''; ?>
            <?= $detalle_mes == '1' ? '<td>Liberado</td>' : ''; ?>
            <td>Acumulado</td>
            <?php if ($detalle_mes == '1'): ?>
                <td>Saldo Ant.</td>
                <td>Mes</td>
                <td>Liberado</td>
                <td>Acumulado</td>
            <?php else: ?>
                <td>Saldo Ant.</td>
                <td>Acumulado</td>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody style="font-size:9px;">
        <?php
        foreach ($acum as $key => $value) {
            $definitivo = 0;
            $saldo_recaudar = 0;

            $keyrb = array_search($key, array_column($rubros, 'cod_pptal'));
            $nomrb = $keyrb !== false ? $rubros[$keyrb]['nom_rubro'] : '';
            $tipo = $keyrb !== false ? $rubros[$keyrb]['tipo_dato'] : '99';

            $tipo_dat = $tipo == '0' ? 'M' : 'D';
            $definitivo = $value['inicial'] + $value['adicion'] - $value['reduccion'];
            $reconocimiento_acumulado = $value['reconocimiento_ant'] + $value['reconocimiento_mes'] - $value['liberado_mes'];
            $recaudo_acumulado = $value['recaudo_ant'] + $value['recaudo_mes'] - $value['recaudo_liberado_mes'];
            $presupuesto_por_ejecutar = $definitivo - $reconocimiento_acumulado;
            $cuentas_por_cobrar = $reconocimiento_acumulado - $recaudo_acumulado;
            $porc_ejec = $reconocimiento_acumulado != 0 ? round(($definitivo / $reconocimiento_acumulado) * 100, 2) : 0;
            echo '<tr class="resaltar">
                    <td class="text">' . $key . '</td>
                    <td class="text">' . $nomrb . '</td>
                    <td class="text">' . $tipo_dat . '</td>
                    <td style="text-align:right">' . pesos($value['inicial']) . '</td>';
            echo  $detalle_mes == '1' ? '<td style="text-align:right">' . pesos($value['adicion_mes']) . '</td>' : '';
            echo '<td style="text-align:right">' . pesos($value['adicion']) . '</td>';
            echo  $detalle_mes == '1' ? '<td style="text-align:right">' . pesos($value['reduccion_mes']) . '</td>' : '';
            echo '<td style="text-align:right">' . pesos($value['reduccion']) . '</td>';
            echo '<td style="text-align:right">' . pesos(($value['inicial'] + $value['adicion'] - $value['reduccion'])) . '</td>';
            echo '<td style="text-align:right">' . pesos($value['reconocimiento_ant']) . '</td>';
            echo  $detalle_mes == '1' ? '<td style="text-align:right">' . pesos($value['reconocimiento_mes']) . '</td>' : '';
            echo  $detalle_mes == '1' ? '<td style="text-align:right">' . pesos($value['liberado_mes']) . '</td>' : '';
            echo '<td style="text-align:right">' . pesos($reconocimiento_acumulado) . '</td>';
            echo '<td style="text-align:right">' . pesos($value['recaudo_ant']) . '</td>';
            echo  $detalle_mes == '1' ? '<td style="text-align:right">' . pesos($value['recaudo_mes']) . '</td>' : '';
            echo  $detalle_mes == '1' ? '<td style="text-align:right">' . pesos($value['recaudo_liberado_mes']) . '</td>' : '';
            echo '<td style="text-align:right">' . pesos($recaudo_acumulado) . '</td>
             <td style="text-align:right">' .  $porc_ejec . '</td>
                    <td style="text-align:right">' .  pesos($presupuesto_por_ejecutar) . '</td>
                    <td style="text-align:right">' .  pesos($cuentas_por_cobrar) . '</td>
                </tr>';
        }
        ?>
    </tbody>
</table>