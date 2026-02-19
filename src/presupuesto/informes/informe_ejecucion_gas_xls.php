<?php

use Config\Clases\Plantilla;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
$vigencia        = $_SESSION['vigencia'];
$fecha_corte     = $_POST['fecha_corte'];
$detalle_mes     = $_POST['mes'];
$fecha_ini       = $_POST['fecha_ini'];
$mes             = date("m", strtotime($fecha_corte));
$fecha_ini_mes   = date("Y-m-d", strtotime($_SESSION['vigencia'] . '-' . $mes . '-01'));
// Último día del mes anterior a la fecha de corte
$fecha_fin_mes_ant = date("Y-m-d", strtotime($fecha_ini_mes . ' -1 day'));
$id_vigencia     = $_SESSION['id_vigencia'];

// Número de columnas dinámico según si se muestra detalle mensual
// Modo sin detalle: Código, Nombre, Estado, Tipo, Inicial, Adiciones, Reducciones,
//                   Créditos, Contracréditos, Definitivo, Disponibilidades(CDP),
//                   Compromisos(CRP), %Ejec, Obligación(Causado), Pagos,
//                   Saldo Pto., Compromisos x Pagar, Ctas x Pagar = 18 cols
// Modo con detalle: agrega 4 grupos × 3 subcols (Saldo Ant. + Mes + Acumulada) = 18 + 12 = 30 cols
$total_cols = $detalle_mes == '1' ? 30 : 18;

function pesos($valor)
{
    return number_format($valor, 2, ".", ",");
}

include '../../../config/autoloader.php';
include '../../financiero/consultas.php';
$cmd = \Config\Clases\Conexion::getConexion();

// ============================================================
// CONSULTA OPTIMIZADA CON CTEs Y SELF-JOIN PARA ACUMULADOS
// Equivalente al patrón usado en informe_ejecucion_ing_xls.php
// ============================================================
$sql = "WITH
    -- CTE para modificaciones (adiciones, reducciones, créditos, contracréditos)
    modificaciones AS (
        SELECT
            pmd.id_cargue,
            SUM(CASE
                WHEN pm.id_tipo_mod = 2 AND pm.fecha BETWEEN :fecha_ini AND :fecha_corte
                THEN pmd.valor_deb ELSE 0
            END) AS val_adicion,
            SUM(CASE
                WHEN pm.id_tipo_mod = 3 AND pm.fecha BETWEEN :fecha_ini AND :fecha_corte
                THEN pmd.valor_deb ELSE 0
            END) AS val_reduccion,
            SUM(CASE
                WHEN pm.id_tipo_mod IN (1,6) AND pm.fecha BETWEEN :fecha_ini AND :fecha_corte
                THEN pmd.valor_deb ELSE 0
            END) AS val_credito,
            SUM(CASE
                WHEN pm.id_tipo_mod IN (1,6) AND pm.fecha BETWEEN :fecha_ini AND :fecha_corte
                THEN pmd.valor_cred ELSE 0
            END) AS val_contracredito,
            -- Valores del mes actual
            SUM(CASE
                WHEN pm.id_tipo_mod = 2 AND pm.fecha BETWEEN :fecha_ini_mes AND :fecha_corte
                THEN pmd.valor_deb ELSE 0
            END) AS val_adicion_mes,
            SUM(CASE
                WHEN pm.id_tipo_mod = 3 AND pm.fecha BETWEEN :fecha_ini_mes AND :fecha_corte
                THEN pmd.valor_deb ELSE 0
            END) AS val_reduccion_mes,
            SUM(CASE
                WHEN pm.id_tipo_mod IN (1,6) AND pm.fecha BETWEEN :fecha_ini_mes AND :fecha_corte
                THEN pmd.valor_deb ELSE 0
            END) AS val_credito_mes,
            SUM(CASE
                WHEN pm.id_tipo_mod IN (1,6) AND pm.fecha BETWEEN :fecha_ini_mes AND :fecha_corte
                THEN pmd.valor_cred ELSE 0
            END) AS val_contracredito_mes
        FROM pto_mod_detalle pmd
        INNER JOIN pto_mod pm ON pmd.id_pto_mod = pm.id_pto_mod
        WHERE pm.estado = 2
            AND pm.id_tipo_mod IN (1, 2, 3, 6)
            AND pm.fecha BETWEEN :fecha_ini AND :fecha_corte
        GROUP BY pmd.id_cargue
    ),
    -- CTE para comprometido (CDP)
    comprometido AS (
        SELECT
            cd.id_rubro,
            SUM(CASE
                WHEN c.fecha BETWEEN :fecha_ini AND :fecha_corte
                THEN IFNULL(cd.valor, 0) ELSE 0
            END) AS val_comprometido,
            SUM(CASE
                WHEN c.fecha BETWEEN :fecha_ini AND :fecha_corte
                THEN IFNULL(cd.valor_liberado, 0) ELSE 0
            END) AS val_comprometido_liberado,
            -- Saldo anterior (hasta fin del mes anterior)
            SUM(CASE
                WHEN c.fecha BETWEEN :fecha_ini AND :fecha_fin_mes_ant
                THEN IFNULL(cd.valor, 0) ELSE 0
            END) AS val_comprometido_ant,
            SUM(CASE
                WHEN cd.fecha_libera BETWEEN :fecha_ini AND :fecha_fin_mes_ant
                THEN IFNULL(cd.valor_liberado, 0) ELSE 0
            END) AS val_comprometido_liberado_ant,
            -- Mes actual
            SUM(CASE
                WHEN c.fecha BETWEEN :fecha_ini_mes AND :fecha_corte
                THEN IFNULL(cd.valor, 0) ELSE 0
            END) AS val_comprometido_mes,
            SUM(CASE
                WHEN cd.fecha_libera BETWEEN :fecha_ini_mes AND :fecha_corte
                THEN IFNULL(cd.valor_liberado, 0) ELSE 0
            END) AS val_comprometido_liberado_mes
        FROM pto_cdp_detalle cd
        INNER JOIN pto_cdp c ON cd.id_pto_cdp = c.id_pto_cdp
        WHERE c.estado = 2
            AND c.fecha BETWEEN :fecha_ini AND :fecha_corte
        GROUP BY cd.id_rubro
    ),
    -- CTE para registrado (CRP)
    registrado AS (
        SELECT
            cd.id_rubro,
            SUM(CASE
                WHEN cr.fecha BETWEEN :fecha_ini AND :fecha_corte
                THEN IFNULL(crd.valor, 0) ELSE 0
            END) AS val_registrado,
            SUM(CASE
                WHEN crd.fecha_libera BETWEEN :fecha_ini AND :fecha_corte
                THEN IFNULL(crd.valor_liberado, 0) ELSE 0
            END) AS val_registrado_liberado,
            -- Saldo anterior (hasta fin del mes anterior)
            SUM(CASE
                WHEN cr.fecha BETWEEN :fecha_ini AND :fecha_fin_mes_ant
                THEN IFNULL(crd.valor, 0) ELSE 0
            END) AS val_registrado_ant,
            SUM(CASE
                WHEN crd.fecha_libera BETWEEN :fecha_ini AND :fecha_fin_mes_ant
                THEN IFNULL(crd.valor_liberado, 0) ELSE 0
            END) AS val_registrado_liberado_ant,
            -- Mes actual
            SUM(CASE
                WHEN cr.fecha BETWEEN :fecha_ini_mes AND :fecha_corte
                THEN IFNULL(crd.valor, 0) ELSE 0
            END) AS val_registrado_mes,
            SUM(CASE
                WHEN crd.fecha_libera BETWEEN :fecha_ini_mes AND :fecha_corte
                THEN IFNULL(crd.valor_liberado, 0) ELSE 0
            END) AS val_registrado_liberado_mes
        FROM pto_crp_detalle crd
        INNER JOIN pto_crp cr ON crd.id_pto_crp = cr.id_pto_crp
        INNER JOIN pto_cdp_detalle cd ON crd.id_pto_cdp_det = cd.id_pto_cdp_det
        WHERE cr.estado = 2
            AND cr.fecha BETWEEN :fecha_ini AND :fecha_corte
        GROUP BY cd.id_rubro
    ),
    -- CTE para causado (COP/Obligaciones)
    causado AS (
        SELECT
            cd.id_rubro,
            SUM(CASE
                WHEN doc.fecha BETWEEN :fecha_ini AND :fecha_corte
                THEN IFNULL(copd.valor, 0) - IFNULL(copd.valor_liberado, 0) ELSE 0
            END) AS val_causado,
            -- Saldo anterior (hasta fin del mes anterior)
            SUM(CASE
                WHEN doc.fecha BETWEEN :fecha_ini AND :fecha_fin_mes_ant
                THEN IFNULL(copd.valor, 0) - IFNULL(copd.valor_liberado, 0) ELSE 0
            END) AS val_causado_ant,
            -- Mes actual
            SUM(CASE
                WHEN doc.fecha BETWEEN :fecha_ini_mes AND :fecha_corte
                THEN IFNULL(copd.valor, 0) - IFNULL(copd.valor_liberado, 0) ELSE 0
            END) AS val_causado_mes
        FROM pto_cop_detalle copd
        INNER JOIN ctb_doc doc ON copd.id_ctb_doc = doc.id_ctb_doc
        INNER JOIN pto_crp_detalle crd ON copd.id_pto_crp_det = crd.id_pto_crp_det
        INNER JOIN pto_cdp_detalle cd ON crd.id_pto_cdp_det = cd.id_pto_cdp_det
        WHERE doc.estado = 2
            AND doc.fecha BETWEEN :fecha_ini AND :fecha_corte
        GROUP BY cd.id_rubro
    ),
    -- CTE para pagado
    pagado AS (
        SELECT
            cd.id_rubro,
            SUM(CASE
                WHEN doc.fecha BETWEEN :fecha_ini AND :fecha_corte
                THEN IFNULL(pd.valor, 0) - IFNULL(pd.valor_liberado, 0) ELSE 0
            END) AS val_pagado,
            -- Saldo anterior (hasta fin del mes anterior)
            SUM(CASE
                WHEN doc.fecha BETWEEN :fecha_ini AND :fecha_fin_mes_ant
                THEN IFNULL(pd.valor, 0) - IFNULL(pd.valor_liberado, 0) ELSE 0
            END) AS val_pagado_ant,
            -- Mes actual
            SUM(CASE
                WHEN doc.fecha BETWEEN :fecha_ini_mes AND :fecha_corte
                THEN IFNULL(pd.valor, 0) - IFNULL(pd.valor_liberado, 0) ELSE 0
            END) AS val_pagado_mes
        FROM pto_pag_detalle pd
        INNER JOIN ctb_doc doc ON pd.id_ctb_doc = doc.id_ctb_doc
        INNER JOIN pto_cop_detalle copd ON pd.id_pto_cop_det = copd.id_pto_cop_det
        INNER JOIN pto_crp_detalle crd ON copd.id_pto_crp_det = crd.id_pto_crp_det
        INNER JOIN pto_cdp_detalle cd ON crd.id_pto_cdp_det = cd.id_pto_cdp_det
        WHERE doc.estado = 2
            AND doc.fecha BETWEEN :fecha_ini AND :fecha_corte
        GROUP BY cd.id_rubro
    ),
    -- CTE base con valores individuales por rubro hoja
    base_calculos AS (
        SELECT
            pc.id_cargue,
            pc.cod_pptal,
            pc.nom_rubro,
            pc.tipo_dato,
            pc.valor_aprobado                                       AS inicial,
            IFNULL(m.val_adicion, 0)                               AS val_adicion,
            IFNULL(m.val_reduccion, 0)                             AS val_reduccion,
            IFNULL(m.val_credito, 0)                               AS val_credito,
            IFNULL(m.val_contracredito, 0)                         AS val_contracredito,
            IFNULL(comp.val_comprometido, 0)
                - IFNULL(comp.val_comprometido_liberado, 0)        AS val_comprometido,
            IFNULL(reg.val_registrado, 0)
                - IFNULL(reg.val_registrado_liberado, 0)           AS val_registrado,
            IFNULL(caus.val_causado, 0)                            AS val_causado,
            IFNULL(pag.val_pagado, 0)                              AS val_pagado,
            -- Valores del mes
            IFNULL(m.val_adicion_mes, 0)                           AS val_adicion_mes,
            IFNULL(m.val_reduccion_mes, 0)                         AS val_reduccion_mes,
            IFNULL(m.val_credito_mes, 0)                           AS val_credito_mes,
            IFNULL(m.val_contracredito_mes, 0)                     AS val_contracredito_mes,
            -- Saldo anterior (acumulado hasta fin del mes anterior)
            IFNULL(comp.val_comprometido_ant, 0)
                - IFNULL(comp.val_comprometido_liberado_ant, 0)    AS val_comprometido_ant,
            IFNULL(reg.val_registrado_ant, 0)
                - IFNULL(reg.val_registrado_liberado_ant, 0)       AS val_registrado_ant,
            IFNULL(caus.val_causado_ant, 0)                        AS val_causado_ant,
            IFNULL(pag.val_pagado_ant, 0)                          AS val_pagado_ant,
            -- Mes actual
            IFNULL(comp.val_comprometido_mes, 0)
                - IFNULL(comp.val_comprometido_liberado_mes, 0)    AS val_comprometido_mes,
            IFNULL(reg.val_registrado_mes, 0)
                - IFNULL(reg.val_registrado_liberado_mes, 0)       AS val_registrado_mes,
            IFNULL(caus.val_causado_mes, 0)                        AS val_causado_mes,
            IFNULL(pag.val_pagado_mes, 0)                          AS val_pagado_mes
        FROM pto_cargue pc
        INNER JOIN pto_presupuestos pp ON pc.id_pto = pp.id_pto
        LEFT JOIN modificaciones m    ON m.id_cargue    = pc.id_cargue
        LEFT JOIN comprometido comp   ON comp.id_rubro   = pc.id_cargue
        LEFT JOIN registrado reg      ON reg.id_rubro    = pc.id_cargue
        LEFT JOIN causado caus        ON caus.id_rubro   = pc.id_cargue
        LEFT JOIN pagado pag          ON pag.id_rubro    = pc.id_cargue
        WHERE pp.id_tipo = 2
            AND pp.id_vigencia = :id_vigencia
    )
    -- Consulta principal: acumula jerárquicamente con SELF-JOIN
    SELECT
        parent.cod_pptal,
        parent.nom_rubro,
        parent.tipo_dato,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.inicial            ELSE 0 END), 0) AS inicial,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_adicion        ELSE 0 END), 0) AS adicion,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_reduccion      ELSE 0 END), 0) AS reduccion,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_credito        ELSE 0 END), 0) AS credito,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_contracredito  ELSE 0 END), 0) AS contracredito,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_comprometido   ELSE 0 END), 0) AS comprometido,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_registrado     ELSE 0 END), 0) AS registrado,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_causado        ELSE 0 END), 0) AS causado,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_pagado         ELSE 0 END), 0) AS pagado,
        -- Valores del mes
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_adicion_mes       ELSE 0 END), 0) AS adicion_mes,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_reduccion_mes     ELSE 0 END), 0) AS reduccion_mes,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_credito_mes       ELSE 0 END), 0) AS credito_mes,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_contracredito_mes ELSE 0 END), 0) AS contracredito_mes,
        -- Saldo anterior
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_comprometido_ant  ELSE 0 END), 0) AS comprometido_ant,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_registrado_ant    ELSE 0 END), 0) AS registrado_ant,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_causado_ant       ELSE 0 END), 0) AS causado_ant,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_pagado_ant        ELSE 0 END), 0) AS pagado_ant,
        -- Mes actual
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_comprometido_mes  ELSE 0 END), 0) AS comprometido_mes,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_registrado_mes    ELSE 0 END), 0) AS registrado_mes,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_causado_mes       ELSE 0 END), 0) AS causado_mes,
        IFNULL(SUM(CASE WHEN child.tipo_dato = 1 THEN child.val_pagado_mes        ELSE 0 END), 0) AS pagado_mes
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
                error_log("No se pudo conectar a la sede {$sede['nom_sede']} para ejecución de gastos");
                continue;
            }
        }

        try {
            $stmt = $cmd_sede->prepare($sql);
            $stmt->bindParam(':fecha_ini',         $fecha_ini,         PDO::PARAM_STR);
            $stmt->bindParam(':fecha_corte',       $fecha_corte,       PDO::PARAM_STR);
            $stmt->bindParam(':fecha_ini_mes',     $fecha_ini_mes,     PDO::PARAM_STR);
            $stmt->bindParam(':fecha_fin_mes_ant', $fecha_fin_mes_ant, PDO::PARAM_STR);
            $stmt->bindParam(':id_vigencia',       $id_vigencia,       PDO::PARAM_INT);
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
                    $rubros_consolidados[$clave]['adicion']           += $registro['adicion'];
                    $rubros_consolidados[$clave]['reduccion']         += $registro['reduccion'];
                    $rubros_consolidados[$clave]['credito']           += $registro['credito'];
                    $rubros_consolidados[$clave]['contracredito']     += $registro['contracredito'];
                    $rubros_consolidados[$clave]['comprometido']      += $registro['comprometido'];
                    $rubros_consolidados[$clave]['registrado']        += $registro['registrado'];
                    $rubros_consolidados[$clave]['causado']           += $registro['causado'];
                    $rubros_consolidados[$clave]['pagado']            += $registro['pagado'];
                    $rubros_consolidados[$clave]['adicion_mes']       += $registro['adicion_mes'];
                    $rubros_consolidados[$clave]['reduccion_mes']     += $registro['reduccion_mes'];
                    $rubros_consolidados[$clave]['credito_mes']       += $registro['credito_mes'];
                    $rubros_consolidados[$clave]['contracredito_mes'] += $registro['contracredito_mes'];
                    $rubros_consolidados[$clave]['comprometido_ant']  += $registro['comprometido_ant'];
                    $rubros_consolidados[$clave]['registrado_ant']    += $registro['registrado_ant'];
                    $rubros_consolidados[$clave]['causado_ant']       += $registro['causado_ant'];
                    $rubros_consolidados[$clave]['pagado_ant']        += $registro['pagado_ant'];
                    $rubros_consolidados[$clave]['comprometido_mes']  += $registro['comprometido_mes'];
                    $rubros_consolidados[$clave]['registrado_mes']    += $registro['registrado_mes'];
                    $rubros_consolidados[$clave]['causado_mes']       += $registro['causado_mes'];
                    $rubros_consolidados[$clave]['pagado_mes']        += $registro['pagado_mes'];
                }
            }
            unset($stmt);
        } catch (PDOException $e) {
            error_log("Error consultando ejecución de gastos en sede {$sede['nom_sede']}: " . $e->getMessage());
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
                 `razon_social_ips` AS `nombre`, `nit_ips` AS `nit`, `dv` AS `dig_ver`
            FROM
                `tb_datos_ips`";
    $res     = $cmd->query($sql);
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
            <td colspan="<?= $total_cols ?>" style="text-align:center"><?php echo 'EJECUCION PRESUPUESTAL DE GASTOS'; ?></td>
        </tr>
        <tr>
            <td colspan="<?= $total_cols ?>" style="text-align:center"><?php echo 'Fecha de corte: ' . $fecha_corte; ?></td>
        </tr>
        <!-- Fila 1: Encabezados de grupo -->
        <tr style="background-color: #CED3D3; text-align:center; font-size:9px; border:1px solid #999;">
            <td rowspan="2" border="1" style="border:1px solid #999;">Código</td>
            <td rowspan="2" border="1" style="border:1px solid #999;">Nombre</td>
            <td rowspan="2" border="1" style="border:1px solid #999;">Estado</td>
            <td rowspan="2" border="1" style="border:1px solid #999;">Tipo</td>
            <td rowspan="2" border="1" style="border:1px solid #999;">Inicial</td>
            <?php if ($detalle_mes == '1'): ?>
                <td colspan="2" border="1" style="border:1px solid #999;">Adiciones</td>
                <td colspan="2" border="1" style="border:1px solid #999;">Reducciones</td>
                <td colspan="2" border="1" style="border:1px solid #999;">Créditos</td>
                <td colspan="2" border="1" style="border:1px solid #999;">Contracréditos</td>
            <?php else: ?>
                <td rowspan="2" border="1" style="border:1px solid #999;">Adiciones</td>
                <td rowspan="2" border="1" style="border:1px solid #999;">Reducciones</td>
                <td rowspan="2" border="1" style="border:1px solid #999;">Créditos</td>
                <td rowspan="2" border="1" style="border:1px solid #999;">Contracréditos</td>
            <?php endif; ?>
            <td rowspan="2" border="1" style="border:1px solid #999;">Definitivo</td>
            <?php if ($detalle_mes == '1'): ?>
                <td colspan="3" border="1" style="border:1px solid #999;">Disponibilidades</td>
                <td colspan="3" border="1" style="border:1px solid #999;">Compromisos</td>
            <?php else: ?>
                <td rowspan="2" border="1" style="border:1px solid #999;">Disponibilidades</td>
                <td rowspan="2" border="1" style="border:1px solid #999;">Compromisos</td>
            <?php endif; ?>
            <td rowspan="2" border="1" style="border:1px solid #999;">% Ejec</td>
            <?php if ($detalle_mes == '1'): ?>
                <td colspan="3" border="1" style="border:1px solid #999;">Obligación</td>
                <td colspan="3" border="1" style="border:1px solid #999;">Pagos</td>
            <?php else: ?>
                <td rowspan="2" border="1" style="border:1px solid #999;">Obligación</td>
                <td rowspan="2" border="1" style="border:1px solid #999;">Pagos</td>
            <?php endif; ?>
            <td rowspan="2" border="1" style="border:1px solid #999;">Saldo Pto.</td>
            <td rowspan="2" border="1" style="border:1px solid #999;">Compromisos x Pagar</td>
            <td rowspan="2" border="1" style="border:1px solid #999;">Ctas x Pagar</td>
        </tr>
        <!-- Fila 2: Sub-encabezados -->
        <tr style="background-color: #CED3D3; text-align:center; font-size:9px; border:1px solid #999;">
            <?php if ($detalle_mes == '1'): ?>
                <!-- Adiciones: Mes / Acumulada -->
                <td style="border:1px solid #999;">Mes</td>
                <td style="border:1px solid #999;">Acumulada</td>
                <!-- Reducciones: Mes / Acumulada -->
                <td style="border:1px solid #999;">Mes</td>
                <td style="border:1px solid #999;">Acumulada</td>
                <!-- Créditos: Mes / Acumulada -->
                <td style="border:1px solid #999;">Mes</td>
                <td style="border:1px solid #999;">Acumulada</td>
                <!-- Contracréditos: Mes / Acumulada -->
                <td style="border:1px solid #999;">Mes</td>
                <td style="border:1px solid #999;">Acumulada</td>
                <!-- Disponibilidades: Saldo Ant. / Mes / Acumulada -->
                <td style="border:1px solid #999;">Saldo Ant.</td>
                <td style="border:1px solid #999;">Mes</td>
                <td style="border:1px solid #999;">Acumulada</td>
                <!-- Compromisos: Saldo Ant. / Mes / Acumulada -->
                <td style="border:1px solid #999;">Saldo Ant.</td>
                <td style="border:1px solid #999;">Mes</td>
                <td style="border:1px solid #999;">Acumulada</td>
                <!-- Obligación: Saldo Ant. / Mes / Acumulada -->
                <td style="border:1px solid #999;">Saldo Ant.</td>
                <td style="border:1px solid #999;">Mes</td>
                <td style="border:1px solid #999;">Acumulada</td>
                <!-- Pagos: Saldo Ant. / Mes / Acumulada -->
                <td style="border:1px solid #999;">Saldo Ant.</td>
                <td style="border:1px solid #999;">Mes</td>
                <td style="border:1px solid #999;">Acumulada</td>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody style="font-size:9px;">
        <?php
        foreach ($acum as $key => $value) {
            $keyrb    = array_search($key, array_column($rubros, 'cod_pptal'));
            $nomrb    = $keyrb !== false ? $rubros[$keyrb]['nom_rubro'] : '';
            $tipo     = $keyrb !== false ? $rubros[$keyrb]['tipo_dato'] : '99';
            $tipo_dat = $tipo == '0' ? 'M' : 'D';

            // Presupuesto definitivo
            $definitivo = $value['inicial'] + $value['adicion'] - $value['reduccion']
                + $value['credito'] - $value['contracredito'];
            $div = $definitivo == 0 ? 1 : $definitivo;

            // Porcentaje de ejecución (comprometido / definitivo)
            $porc_ejec = round(($value['registrado'] / $div) * 100, 2);

            // Indicador de color según nivel de ejecución
            $ratio = $definitivo != 0 ? $value['comprometido'] / $definitivo : 0;
            if ($ratio >= 0 && $ratio <= 0.4) {
                $color = '#2ECC71';
            } elseif ($ratio > 0.4 && $ratio <= 0.7) {
                $color = '#F1C40F';
            } elseif ($ratio > 0.7 && $ratio <= 0.9) {
                $color = '#E67E22';
            } else {
                $color = '#E74C3C';
            }

            echo '<tr class="resaltar">';
            echo '<td class="text">' . $key . '</td>';
            echo '<td class="text">' . $nomrb . '</td>';
            echo '<td class="text border border-light" style="background-color:' . $color . '"></td>';
            echo '<td class="text">' . $tipo_dat . '</td>';
            echo '<td style="text-align:right">' . pesos($value['inicial']) . '</td>';

            echo $detalle_mes == '1' ? '<td style="text-align:right">' . pesos($value['adicion_mes'])       . '</td>' : '';
            echo '<td style="text-align:right">' . pesos($value['adicion'])       . '</td>';
            echo $detalle_mes == '1' ? '<td style="text-align:right">' . pesos($value['reduccion_mes'])     . '</td>' : '';
            echo '<td style="text-align:right">' . pesos($value['reduccion'])     . '</td>';
            echo $detalle_mes == '1' ? '<td style="text-align:right">' . pesos($value['credito_mes'])       . '</td>' : '';
            echo '<td style="text-align:right">' . pesos($value['credito'])       . '</td>';
            echo $detalle_mes == '1' ? '<td style="text-align:right">' . pesos($value['contracredito_mes']) . '</td>' : '';
            echo '<td style="text-align:right">' . pesos($value['contracredito']) . '</td>';

            echo '<td style="text-align:right">' . pesos($definitivo) . '</td>';

            if ($detalle_mes == '1') {
                // Disponibilidades (CDP): Saldo Ant. | Mes | Acumulada
                $comprometido_acum = $value['comprometido_ant'] + $value['comprometido_mes'];
                echo '<td style="text-align:right">' . pesos($value['comprometido_ant']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['comprometido_mes']) . '</td>';
                echo '<td style="text-align:right">' . pesos($comprometido_acum)         . '</td>';
                // Compromisos (CRP): Saldo Ant. | Mes | Acumulada
                $registrado_acum = $value['registrado_ant'] + $value['registrado_mes'];
                echo '<td style="text-align:right">' . pesos($value['registrado_ant'])   . '</td>';
                echo '<td style="text-align:right">' . pesos($value['registrado_mes'])   . '</td>';
                echo '<td style="text-align:right">' . pesos($registrado_acum)           . '</td>';
            } else {
                echo '<td style="text-align:right">' . pesos($value['comprometido']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['registrado'])   . '</td>';
            }

            echo '<td style="text-align:right">' . $porc_ejec . '</td>';

            if ($detalle_mes == '1') {
                // Obligación (Causado): Saldo Ant. | Mes | Acumulada
                $causado_acum = $value['causado_ant'] + $value['causado_mes'];
                echo '<td style="text-align:right">' . pesos($value['causado_ant']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['causado_mes']) . '</td>';
                echo '<td style="text-align:right">' . pesos($causado_acum)         . '</td>';
                // Pagos: Saldo Ant. | Mes | Acumulada
                $pagado_acum = $value['pagado_ant'] + $value['pagado_mes'];
                echo '<td style="text-align:right">' . pesos($value['pagado_ant'])  . '</td>';
                echo '<td style="text-align:right">' . pesos($value['pagado_mes'])  . '</td>';
                echo '<td style="text-align:right">' . pesos($pagado_acum)          . '</td>';
            } else {
                echo '<td style="text-align:right">' . pesos($value['causado']) . '</td>';
                echo '<td style="text-align:right">' . pesos($value['pagado'])  . '</td>';
            }

            // Columnas de saldos calculados
            // En modo detalle usamos los acumulados (ant + mes), en modo simple los totales directos
            $comp_acum_final = $detalle_mes == '1' ? ($value['comprometido_ant'] + $value['comprometido_mes']) : $value['comprometido'];
            $reg_acum_final  = $detalle_mes == '1' ? ($value['registrado_ant']   + $value['registrado_mes'])   : $value['registrado'];
            $caus_acum_final = $detalle_mes == '1' ? ($value['causado_ant']      + $value['causado_mes'])      : $value['causado'];
            $pag_acum_final  = $detalle_mes == '1' ? ($value['pagado_ant']       + $value['pagado_mes'])       : $value['pagado'];

            echo '<td style="text-align:right">' . pesos($definitivo - $comp_acum_final)          . '</td>'; // Saldo Pto. = Definitivo - Acum. Disponibilidades
            echo '<td style="text-align:right">' . pesos($reg_acum_final - $pag_acum_final)        . '</td>'; // Compromisos x Pagar = Acum. Compromisos - Acum. Pagos
            echo '<td style="text-align:right">' . pesos($caus_acum_final - $pag_acum_final)       . '</td>'; // Ctas x Pagar = Acum. Obligación - Acum. Pagos
            echo '</tr>';
        }
        ?>
    </tbody>
</table>