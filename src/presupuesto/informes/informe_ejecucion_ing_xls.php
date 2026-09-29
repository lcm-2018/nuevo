<?php

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Valores;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
$vigencia = $_SESSION['vigencia'];
$fecha_corte = $_POST['fecha_corte'];
$detalle_mes = $_POST['mes'] ?? '0';
$detalle_periodo = $_POST['periodo'] ?? '0';
$fecha_ini = $_POST['fecha_ini'];
$mes = date("m", strtotime($fecha_corte));
$fecha_ini_mes = date("Y-m-d", strtotime($_SESSION['vigencia'] . '-' . $mes . '-01'));
// Último día del mes anterior a la fecha de corte
$fecha_fin_mes_ant = date("Y-m-d", strtotime($fecha_ini_mes . ' -1 day'));
$id_vigencia = $_SESSION['id_vigencia'];
$total_cols = 15;
if ($detalle_mes == '1') {
    $total_cols = 21;
} elseif ($detalle_periodo == '1') {
    $total_cols = 23; // Add columns for Periodo/Acumulado etc.
}
function pesos($valor)
{
    return number_format($valor, 2, ".", ",");
}
include '../../../config/autoloader.php';
include '../../financiero/consultas.php';
$cmd = \Config\Clases\Conexion::getConexion();

// Configuración del propietario: controla visibilidad de columnas
$ownerConfig = Valores::getOwnerConfig();
// Mostrar "Por Ejecutar" si col_ejecucion no existe, o si existe y no es 0
$mostrar_col_ejecucion = !array_key_exists('col_ejecucion', $ownerConfig) || $ownerConfig['col_ejecucion'] != 0;
// Restar 1 columna si "Por Ejecutar" está oculta
if (!$mostrar_col_ejecucion) {
    $total_cols--;
}

// CONSULTA OPTIMIZADA CON CTEs Y SELF-JOIN PARA ACUMULADOS
$sql = "WITH 
    modificaciones AS (
        SELECT
            pmd.id_cargue,
            SUM(CASE 
                WHEN pm.id_tipo_mod = 2 AND DATE(pm.fecha) BETWEEN :fecha_ini AND :fecha_corte 
                THEN pmd.valor_deb 
                ELSE 0 
            END) AS val_adicion,
            SUM(CASE 
                WHEN pm.id_tipo_mod = 3 AND DATE(pm.fecha) BETWEEN :fecha_ini AND :fecha_corte 
                THEN pmd.valor_deb 
                ELSE 0 
            END) AS val_reduccion,
            -- Acumulados periodo
            SUM(CASE 
                WHEN pm.id_tipo_mod = 2 AND DATE(pm.fecha) <= :fecha_corte 
                THEN pmd.valor_deb 
                ELSE 0 
            END) AS val_adicion_acum,
            SUM(CASE 
                WHEN pm.id_tipo_mod = 3 AND DATE(pm.fecha) <= :fecha_corte 
                THEN pmd.valor_deb 
                ELSE 0 
            END) AS val_reduccion_acum,
            SUM(CASE 
                WHEN pm.id_tipo_mod = 2 AND DATE(pm.fecha) BETWEEN :fecha_ini_mes AND :fecha_corte 
                THEN pmd.valor_deb 
                ELSE 0 
            END) AS val_adicion_mes,
            SUM(CASE 
                WHEN pm.id_tipo_mod = 3 AND DATE(pm.fecha) BETWEEN :fecha_ini_mes AND :fecha_corte 
                THEN pmd.valor_deb 
                ELSE 0 
            END) AS val_reduccion_mes
        FROM pto_mod_detalle pmd
        INNER JOIN pto_mod pm ON pmd.id_pto_mod = pm.id_pto_mod
        WHERE pm.estado = 2 
            AND pm.id_tipo_mod IN (2, 3)
            AND DATE(pm.fecha) <= :fecha_corte
        GROUP BY pmd.id_cargue
    ),
    -- CTE para reconocimientos
    reconocimientos AS (
        SELECT
            prd.id_rubro,
            SUM(CASE 
                WHEN DATE(pr.fecha) BETWEEN :fecha_ini AND :fecha_fin_mes_ant 
                THEN IFNULL(prd.valor, 0) - IFNULL(prd.valor_liberado, 0)
                ELSE 0 
            END) AS val_reconocimiento_ant,
            SUM(CASE 
                WHEN DATE(pr.fecha) BETWEEN :fecha_ini_mes AND :fecha_corte 
                THEN IFNULL(prd.valor, 0)
                ELSE 0 
            END) AS val_reconocimiento_mes,
            SUM(CASE 
                WHEN DATE(pr.fecha) BETWEEN :fecha_ini_mes AND :fecha_corte 
                THEN IFNULL(prd.valor_liberado, 0)
                ELSE 0 
            END) AS val_liberado_mes,
            -- Acumulados y periodos
            SUM(CASE 
                WHEN DATE(pr.fecha) < :fecha_ini 
                THEN IFNULL(prd.valor, 0) - IFNULL(prd.valor_liberado, 0)
                ELSE 0 
            END) AS val_reconocimiento_antes,
            SUM(CASE 
                WHEN DATE(pr.fecha) BETWEEN :fecha_ini AND :fecha_corte 
                THEN IFNULL(prd.valor, 0)
                ELSE 0 
            END) AS val_reconocimiento_periodo,
            SUM(CASE 
                WHEN DATE(pr.fecha) <= :fecha_corte 
                THEN IFNULL(prd.valor_liberado, 0)
                ELSE 0 
            END) AS val_liberado_acum
        FROM pto_rad_detalle prd
        INNER JOIN pto_rad pr ON prd.id_pto_rad = pr.id_pto_rad
        WHERE pr.estado = 2
            AND DATE(pr.fecha) <= :fecha_corte
        GROUP BY prd.id_rubro
    ),
    -- CTE para recaudos
    recaudos AS (
        SELECT
            COALESCE(prd.id_rubro, prdd.id_rubro) AS id_rubro,
            SUM(CASE 
                WHEN DATE(pr.fecha) BETWEEN :fecha_ini AND :fecha_fin_mes_ant 
                THEN IFNULL(prd.valor, 0) - IFNULL(prd.valor_liberado, 0)
                ELSE 0 
            END) AS val_recaudo_ant,
            SUM(CASE 
                WHEN DATE(pr.fecha) BETWEEN :fecha_ini_mes AND :fecha_corte 
                THEN IFNULL(prd.valor, 0)
                ELSE 0 
            END) AS val_recaudo_mes,
            SUM(CASE 
                WHEN DATE(pr.fecha) BETWEEN :fecha_ini_mes AND :fecha_corte 
                THEN IFNULL(prd.valor_liberado, 0)
                ELSE 0 
            END) AS val_recaudo_liberado_mes,
            -- Acumulados y periodos
            SUM(CASE 
                WHEN DATE(pr.fecha) < :fecha_ini 
                THEN IFNULL(prd.valor, 0) - IFNULL(prd.valor_liberado, 0)
                ELSE 0 
            END) AS val_recaudo_antes,
            SUM(CASE 
                WHEN DATE(pr.fecha) BETWEEN :fecha_ini AND :fecha_corte 
                THEN IFNULL(prd.valor, 0)
                ELSE 0 
            END) AS val_recaudo_periodo,
            SUM(CASE 
                WHEN DATE(pr.fecha) <= :fecha_corte 
                THEN IFNULL(prd.valor_liberado, 0)
                ELSE 0 
            END) AS val_recaudo_liberado_acum
        FROM pto_rec_detalle prd
        INNER JOIN pto_rec pr ON prd.id_pto_rac = pr.id_pto_rec
        LEFT JOIN pto_rad_detalle prdd ON prd.id_pto_rad_detalle = prdd.id_pto_rad_det
        WHERE pr.estado = 2
            AND DATE(pr.fecha) <= :fecha_corte
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
            IFNULL(rk.val_liberado_mes, 0) AS val_liberado_mes,
            -- Nuevos acumulados y periodos
            IFNULL(m.val_adicion_acum, 0) AS val_adicion_acum,
            IFNULL(m.val_reduccion_acum, 0) AS val_reduccion_acum,
            IFNULL(rk.val_reconocimiento_antes, 0) AS val_reconocimiento_antes,
            IFNULL(rk.val_reconocimiento_periodo, 0) AS val_reconocimiento_periodo,
            IFNULL(rk.val_liberado_acum, 0) AS val_liberado_acum,
            IFNULL(rc.val_recaudo_antes, 0) AS val_recaudo_antes,
            IFNULL(rc.val_recaudo_periodo, 0) AS val_recaudo_periodo,
            IFNULL(rc.val_recaudo_liberado_acum, 0) AS val_recaudo_liberado_acum
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
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_liberado_mes ELSE 0 END), 0) AS liberado_mes,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_adicion_acum ELSE 0 END), 0) AS adicion_acum,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_reduccion_acum ELSE 0 END), 0) AS reduccion_acum,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_reconocimiento_antes ELSE 0 END), 0) AS reconocimiento_antes,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_reconocimiento_periodo ELSE 0 END), 0) AS reconocimiento_periodo,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_liberado_acum ELSE 0 END), 0) AS liberado_acum,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_recaudo_antes ELSE 0 END), 0) AS recaudo_antes,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_recaudo_periodo ELSE 0 END), 0) AS recaudo_periodo,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_recaudo_liberado_acum ELSE 0 END), 0) AS recaudo_liberado_acum
    FROM base_calculos parent
    LEFT JOIN base_calculos child ON child.cod_pptal LIKE CONCAT(parent.cod_pptal, '%')
    GROUP BY parent.cod_pptal, parent.nom_rubro, parent.tipo_dato
    ORDER BY parent.cod_pptal ASC";

try {
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(':fecha_ini', $fecha_ini, PDO::PARAM_STR);
    $stmt->bindParam(':fecha_corte', $fecha_corte, PDO::PARAM_STR);
    $stmt->bindParam(':fecha_ini_mes', $fecha_ini_mes, PDO::PARAM_STR);
    $stmt->bindParam(':fecha_fin_mes_ant', $fecha_fin_mes_ant, PDO::PARAM_STR);
    $stmt->bindParam(':id_vigencia', $id_vigencia, PDO::PARAM_INT);
    $stmt->execute();
    $rubros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    unset($stmt);
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
            <?php if ($detalle_mes == '1' || $detalle_periodo == '1'): ?>
                <td colspan="2">Adiciones</td>
                <td colspan="2">Reducciones</td>
            <?php else: ?>
                <td rowspan="2">Adiciones</td>
                <td rowspan="2">Reducciones</td>
            <?php endif; ?>
            <td rowspan="2">Definitivo</td>
            <?php if ($detalle_mes == '1' || $detalle_periodo == '1'): ?>
                <td colspan="4">Reconocimiento</td>
                <td colspan="4">Recaudo</td>
            <?php else: ?>
                <td colspan="2">Reconocimiento</td>
                <td colspan="2">Recaudo</td>
            <?php endif; ?>
            <td rowspan="2">% Ejec</td>
            <?php if ($mostrar_col_ejecucion): ?>
            <td rowspan="2">Por Ejecutar</td>
            <?php endif; ?>
            <td rowspan="2">Saldo por Ejecutar</td>
            <td rowspan="2">Ctas por Cobrar</td>
        </tr>
        <!-- Fila 2: Sub-encabezados -->
        <tr style="background-color: #CED3D3; text-align:center;font-size:9px;" border="1">
            <?php if ($detalle_mes == '1'): ?>
                <td>Mes</td>
                <td>Acumulada</td>
                <td>Mes</td>
                <td>Acumulada</td>
                <td>Saldo ant.</td>
                <td>Mes</td>
                <td>Liberado</td>
                <td>Acumulado</td>
                <td>Saldo Ant.</td>
                <td>Mes</td>
                <td>Liberado</td>
                <td>Acumulado</td>
            <?php elseif ($detalle_periodo == '1'): ?>
                <td>Periodo</td>
                <td>Acumulada</td>
                <td>Periodo</td>
                <td>Acumulada</td>
                <td>Saldo ant.</td>
                <td>Periodo</td>
                <td>Liberado</td>
                <td>Acumulado</td>
                <td>Saldo Ant.</td>
                <td>Periodo</td>
                <td>Liberado</td>
                <td>Acumulado</td>
            <?php else: ?>
                <td>Saldo ant.</td>
                <td>Acumulado</td>
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
            $definitivo = $value['inicial'] + $value['adicion_acum'] - $value['reduccion_acum'];
            if ($detalle_periodo == '1') {
                $reconocimiento_acumulado = $value['reconocimiento_antes'] + $value['reconocimiento_periodo'] - $value['liberado_acum'];
                $recaudo_acumulado = $value['recaudo_antes'] + $value['recaudo_periodo'] - $value['recaudo_liberado_acum'];
            } else {
                $reconocimiento_acumulado = $value['reconocimiento_ant'] + $value['reconocimiento_mes'] - $value['liberado_mes'];
                $recaudo_acumulado = $value['recaudo_ant'] + $value['recaudo_mes'] - $value['recaudo_liberado_mes'];
            }
            $presupuesto_por_ejecutar = $definitivo - $reconocimiento_acumulado;
            $cuentas_por_cobrar = $reconocimiento_acumulado - $recaudo_acumulado;
            $porc_ejec = $definitivo != 0 ? round(($recaudo_acumulado / $definitivo) * 100, 2) : 0;
            echo '<tr class="resaltar">
                    <td class="text">' . $key . '</td>
                    <td class="text">' . $nomrb . '</td>
                    <td class="text">' . $tipo_dat . '</td>
                    <td style="text-align:right">' . pesos($value['inicial']) . '</td>';
            if ($detalle_mes == '1') {
                echo '<td style="text-align:right">' . pesos($value['adicion_mes']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['adicion']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['reduccion_mes']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['reduccion']) . '</td>';
            } elseif ($detalle_periodo == '1') {
                echo '<td style="text-align:right">' . pesos($value['adicion']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['adicion_acum']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['reduccion']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['reduccion_acum']) . '</td>';
            } else {
                echo '<td style="text-align:right">' . pesos($value['adicion']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['reduccion']) . '</td>';
            }
            
            echo '<td style="text-align:right">' . pesos($definitivo) . '</td>';
            
            if ($detalle_mes == '1') {
                echo '<td style="text-align:right">' . pesos($value['reconocimiento_ant']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['reconocimiento_mes']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['liberado_mes']) . '</td>';
                echo '<td style="text-align:right">' . pesos($reconocimiento_acumulado) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['recaudo_ant']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['recaudo_mes']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['recaudo_liberado_mes']) . '</td>';
            } elseif ($detalle_periodo == '1') {
                echo '<td style="text-align:right">' . pesos($value['reconocimiento_antes']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['reconocimiento_periodo']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['liberado_acum']) . '</td>';
                echo '<td style="text-align:right">' . pesos($reconocimiento_acumulado) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['recaudo_antes']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['recaudo_periodo']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['recaudo_liberado_acum']) . '</td>';
            } else {
                echo '<td style="text-align:right">' . pesos($value['reconocimiento_ant']) . '</td>';
                echo '<td style="text-align:right">' . pesos($reconocimiento_acumulado) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['recaudo_ant']) . '</td>';
            }
            $saldo_por_ejecutar = $definitivo - $recaudo_acumulado;
            echo '<td style="text-align:right">' . pesos($recaudo_acumulado) . '</td>
             <td style="text-align:right">' .  $porc_ejec . '</td>';
            if ($mostrar_col_ejecucion) {
                echo '<td style="text-align:right">' . pesos($presupuesto_por_ejecutar) . '</td>';
            }
            echo '<td style="text-align:right">' .  pesos($saldo_por_ejecutar) . '</td>
                    <td style="text-align:right">' .  pesos($cuentas_por_cobrar) . '</td>
                </tr>';
        }
        ?>
    </tbody>
</table>