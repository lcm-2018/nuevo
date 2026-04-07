<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}

$_post = json_decode(file_get_contents('php://input'), true);
include_once '../../../../config/autoloader.php';
include_once '../../../financiero/consultas.php';

$id_doc = $_post['id_doc'];
$fec_ini = isset($_post['fec_ini']) && strlen($_post['fec_ini']) > 0 ? $_post['fec_ini'] : '2020-01-01';
$fec_fin = isset($_post['fec_fin']) && strlen($_post['fec_fin']) > 0 ? $_post['fec_fin'] : '2050-12-31';
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha2 = $date->format('Y-m-d H:i:s');

$cmd = \Config\Clases\Conexion::getConexion();
$response = [
    'status' => 'error',
    'msg' => 'Ningun registro afectado',
];
$datosDoc = GetValoresCxP($id_doc, $cmd);
$acumulador = 0;

try {
    $cmd->beginTransaction();

    $query = $cmd->prepare("SELECT `id_ctb_libaux` FROM `ctb_libaux` WHERE `id_ctb_doc` = ?");
    $query->bindParam(1, $id_doc, PDO::PARAM_INT);
    $query->execute();
    $datos = $query->fetch();

    if (!empty($datos)) {
        $query = $cmd->prepare("DELETE FROM `ctb_libaux` WHERE `id_ctb_doc` = ?");
        $query->bindParam(1, $id_doc, PDO::PARAM_INT);
        $query->execute();
    }

    $id_tercero = $datosDoc['id_tercero'];

    $sq2 = "SELECT
                sb.id_sede,
                sb.nom_sede,
                sb.id_bodega,
                sb.nom_bodega,
                CASE
                    WHEN d.consumo = 1 THEN (
                        SELECT CTA.id_pgcp
                        FROM tb_centrocostos_subgr_cta_detalle AS CSG
                        INNER JOIN ctb_pgcp AS CTA
                            ON CTA.id_pgcp = CSG.id_cuenta
                        WHERE CSG.id_cecsubgrp = (
                            SELECT id_cecsubgrp
                            FROM tb_centrocostos_subgr_cta
                            WHERE estado = 1
                              AND fecha_vigencia <= DATE_FORMAT(NOW(), '%Y-%m-%d')
                              AND id_cencos = d.id_centro
                            ORDER BY fecha_vigencia DESC
                            LIMIT 1
                        )
                          AND CSG.id_subgrupo = d.id_subgrupo
                        LIMIT 1
                    )
                    ELSE (
                        SELECT CACT.id_pgcp
                        FROM far_orden_egreso_tipo_cta AS TEGR
                        INNER JOIN ctb_pgcp AS CACT
                            ON CACT.id_pgcp = TEGR.id_cuenta
                        WHERE TEGR.estado = 1
                          AND TEGR.fecha_vigencia <= DATE_FORMAT(NOW(), '%Y-%m-%d')
                          AND TEGR.id_tipo_egreso = d.id_tipo_egreso
                        ORDER BY TEGR.fecha_vigencia DESC
                        LIMIT 1
                    )
                END AS id_cuenta_egreso,
                CASE
                    WHEN d.consumo = 1 THEN (
                        SELECT CTA.cuenta
                        FROM tb_centrocostos_subgr_cta_detalle AS CSG
                        INNER JOIN ctb_pgcp AS CTA
                            ON CTA.id_pgcp = CSG.id_cuenta
                        WHERE CSG.id_cecsubgrp = (
                            SELECT id_cecsubgrp
                            FROM tb_centrocostos_subgr_cta
                            WHERE estado = 1
                              AND fecha_vigencia <= DATE_FORMAT(NOW(), '%Y-%m-%d')
                              AND id_cencos = d.id_centro
                            ORDER BY fecha_vigencia DESC
                            LIMIT 1
                        )
                          AND CSG.id_subgrupo = d.id_subgrupo
                        LIMIT 1
                    )
                    ELSE (
                        SELECT CACT.cuenta
                        FROM far_orden_egreso_tipo_cta AS TEGR
                        INNER JOIN ctb_pgcp AS CACT
                            ON CACT.id_pgcp = TEGR.id_cuenta
                        WHERE TEGR.estado = 1
                          AND TEGR.fecha_vigencia <= DATE_FORMAT(NOW(), '%Y-%m-%d')
                          AND TEGR.id_tipo_egreso = d.id_tipo_egreso
                        ORDER BY TEGR.fecha_vigencia DESC
                        LIMIT 1
                    )
                END AS cuenta_egreso,
                CASE
                    WHEN d.consumo = 1 THEN d.nom_centro
                    ELSE CONCAT(d.nom_centro, ' (Egreso: ', d.nom_tipo_egreso, ')')
                END AS centro_costo_tipo_egreso,
                (
                    SELECT CACT.id_pgcp
                    FROM far_subgrupos_cta AS SBG
                    INNER JOIN ctb_pgcp AS CACT
                        ON CACT.id_pgcp = SBG.id_cuenta
                    WHERE SBG.estado = 1
                      AND SBG.fecha_vigencia <= DATE_FORMAT(NOW(), '%Y-%m-%d')
                      AND SBG.id_subgrupo = d.id_subgrupo
                    ORDER BY SBG.fecha_vigencia DESC
                    LIMIT 1
                ) AS id_cuenta_subgrupo,
                (
                    SELECT CACT.cuenta
                    FROM far_subgrupos_cta AS SBG
                    INNER JOIN ctb_pgcp AS CACT
                        ON CACT.id_pgcp = SBG.id_cuenta
                    WHERE SBG.estado = 1
                      AND SBG.fecha_vigencia <= DATE_FORMAT(NOW(), '%Y-%m-%d')
                      AND SBG.id_subgrupo = d.id_subgrupo
                    ORDER BY SBG.fecha_vigencia DESC
                    LIMIT 1
                ) AS cuenta_subgrupo,
                d.nom_subgrupo,
                d.val_total_sg AS vr_parcial,
                sb.val_total_sb AS vr_total_sede_bodega
            FROM (
                SELECT
                    oe.id_sede,
                    s.nom_sede,
                    oe.id_bodega,
                    b.nombre AS nom_bodega,
                    SUM(oe.val_total) AS val_total_sb
                FROM far_orden_egreso AS oe
                INNER JOIN tb_sedes AS s
                    ON s.id_sede = oe.id_sede
                INNER JOIN far_bodegas AS b
                    ON b.id_bodega = oe.id_bodega
                WHERE oe.estado = 2
                  AND oe.fec_egreso BETWEEN :fec_ini_1 AND :fec_fin_1
                GROUP BY oe.id_sede, s.nom_sede, oe.id_bodega, b.nombre
            ) AS sb
            INNER JOIN (
                SELECT
                    oe.id_sede,
                    oe.id_bodega,
                    tipo.consumo,
                    MAX(oe.id_tipo_egreso) AS id_tipo_egreso,
                    MAX(tipo.nom_tipo_egreso) AS nom_tipo_egreso,
                    IF(oe.id_centrocosto = 0, farm.id_centro, cc.id_centro) AS id_centro,
                    IF(oe.id_centrocosto = 0, farm.nom_centro, cc.nom_centro) AS nom_centro,
                    sg.id_subgrupo,
                    CONCAT_WS(' - ', sg.cod_subgrupo, sg.nom_subgrupo) AS nom_subgrupo,
                    SUM(det.cantidad * det.valor) AS val_total_sg,
                    IF(tipo.consumo = 1, 0, oe.id_tipo_egreso) AS grp_tipo_egreso
                FROM far_orden_egreso_detalle AS det
                INNER JOIN far_orden_egreso AS oe
                    ON oe.id_egreso = det.id_egreso
                INNER JOIN far_medicamento_lote AS lot
                    ON lot.id_lote = det.id_lote
                INNER JOIN far_medicamentos AS med
                    ON med.id_med = lot.id_med
                INNER JOIN far_subgrupos AS sg
                    ON sg.id_subgrupo = med.id_subgrupo
                INNER JOIN tb_centrocostos AS cc
                    ON cc.id_centro = oe.id_centrocosto
                INNER JOIN far_orden_egreso_tipo AS tipo
                    ON tipo.id_tipo_egreso = oe.id_tipo_egreso
                CROSS JOIN (
                    SELECT id_centro, nom_centro
                    FROM tb_centrocostos
                    WHERE es_farmacia = 1
                    LIMIT 1
                ) AS farm
                WHERE oe.estado = 2
                  AND oe.fec_egreso BETWEEN :fec_ini_2 AND :fec_fin_2
                GROUP BY
                    oe.id_sede,
                    oe.id_bodega,
                    IF(tipo.consumo = 1, 0, oe.id_tipo_egreso),
                    IF(oe.id_centrocosto = 0, farm.id_centro, cc.id_centro),
                    sg.id_subgrupo,
                    CONCAT_WS(' - ', sg.cod_subgrupo, sg.nom_subgrupo)
            ) AS d
                ON d.id_sede = sb.id_sede
               AND d.id_bodega = sb.id_bodega
            ORDER BY
                sb.id_sede,
                sb.nom_bodega,
                d.grp_tipo_egreso,
                d.id_centro,
                d.id_subgrupo";
    $rs = $cmd->prepare($sq2);
    $rs->bindValue(':fec_ini_1', $fec_ini, PDO::PARAM_STR);
    $rs->bindValue(':fec_fin_1', $fec_fin, PDO::PARAM_STR);
    $rs->bindValue(':fec_ini_2', $fec_ini, PDO::PARAM_STR);
    $rs->bindValue(':fec_fin_2', $fec_fin, PDO::PARAM_STR);
    $rs->execute();
    $objeto = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);

    $id_cuenta = null;
    $debito = 0;
    $credito = 0;
    $ref = 0;
    $total_debito = 0;
    $total_credito = 0;

    $query = "INSERT INTO `ctb_libaux`
                (`id_ctb_doc`,`id_tercero_api`,`id_cuenta`,`debito`,`credito`,`id_user_reg`,`fecha_reg`,`ref`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $id_doc, PDO::PARAM_INT);
    $query->bindParam(2, $id_tercero, PDO::PARAM_INT);
    $query->bindParam(3, $id_cuenta, PDO::PARAM_INT);
    $query->bindParam(4, $debito, PDO::PARAM_STR);
    $query->bindParam(5, $credito, PDO::PARAM_STR);
    $query->bindParam(6, $iduser, PDO::PARAM_INT);
    $query->bindParam(7, $fecha2);
    $query->bindParam(8, $ref, PDO::PARAM_INT);

    if (!empty($objeto)) {
        foreach ($objeto as $o) {
            $valor = (float) $o['vr_parcial'];

            $id_cuenta = $o['id_cuenta_egreso'] ?? null;
            if (empty($id_cuenta)) {
                $id_cuenta = NULL;
            }
            $debito = $valor;
            $credito = 0;
            $query->execute();
            if ($cmd->lastInsertId() > 0) {
                $total_debito += $debito;
                $acumulador++;
            } else {
                throw new Exception($query->errorInfo()[2]);
            }

            $id_cuenta = $o['id_cuenta_subgrupo'] ?? null;
            if (empty($id_cuenta)) {
                $id_cuenta = NULL;
            }
            $debito = 0;
            $credito = $valor;
            $query->execute();
            if ($cmd->lastInsertId() > 0) {
                $total_credito += $credito;
                $acumulador++;
            } else {
                throw new Exception($query->errorInfo()[2]);
            }
        }
    } else {
        $response['msg'] = 'No se encontraron registros para generar el movimiento automatico';
    }

    $cmd->commit();
} catch (Throwable $e) {
    if ($cmd->inTransaction()) {
        $cmd->rollBack();
    }
    $response['msg'] = $e->getMessage();
}

if ($acumulador > 0) {
    $response['status'] = 'ok';
}

echo json_encode($response);
exit();
