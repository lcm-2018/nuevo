<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}

include '../../../../config/autoloader.php';
include '../common/funciones_generales.php';

$cmd = \Config\Clases\Conexion::getConexion();

$idusr = $_SESSION['id_user'];
$idrol = $_SESSION['rol'];

$fecha = !empty($_POST['fecha']) ? $_POST['fecha'] : date('Y-m-d');
$id_reporte = $_POST['id_reporte'];

$titulo = '';
switch ($id_reporte) {
    case '2':
        $titulo = "REPORTE DE EXISTENCIAS POR ARTICULO A:$fecha, AGRUPADO POR SEDE-BODEGA-GRUPO (VR. Promedio)";
        break;
    case '5':
        $titulo = "REPORTE DE EXISTENCIAS POR ARTICULO A:$fecha, AGRUPADO POR SEDE-BODEGA-GRUPO (VR. Último Ingreso)";
        break;    
}


/* =========================================================
   FILTROS KARDEX
========================================================= */

$where_kar = ["far_kardex.estado = 1"];

if ($idrol != 1) {
    $where_kar[] = "far_kardex.id_bodega IN (SELECT id_bodega FROM seg_bodegas_usuario WHERE id_usuario = $idusr)";
}

if (!empty($_POST['id_sede'])) {
    $where_kar[] = "far_kardex.id_sede = '" . $_POST['id_sede'] . "'";
}
if (!empty($_POST['id_bodega'])) {
    $where_kar[] = "far_kardex.id_bodega = '" . $_POST['id_bodega'] . "'";
}

$where_kar[] = "far_kardex.fec_movimiento <= '$fecha'";
$where_kar = 'WHERE ' . implode(' AND ', $where_kar);

/* =========================================================
   FILTROS ARTICULOS
========================================================= */

$where_art = ["far_subgrupos.id_grupo IN (0,1,2)"];
if (!empty($_POST['codigo'])) {
    $where_art[] = "far_medicamentos.cod_medicamento LIKE '" . $_POST['codigo'] . "%'";
}
if (!empty($_POST['nombre'])) {
    $where_art[] = "far_medicamentos.nom_medicamento LIKE '" . $_POST['nombre'] . "%'";
}
if (!empty($_POST['id_subgrupo'])) {
    $where_art[] = "far_medicamentos.id_subgrupo = " . $_POST['id_subgrupo'];
}
if (strlen($_POST['tipo_asis'] ?? '') > 0) {
    $where_art[] = "far_medicamentos.es_clinico = " . $_POST['tipo_asis'];
}
if (!empty($_POST['artactivo'])) {
    $where_art[] = "far_medicamentos.estado = 1";
}
if (!empty($_POST['lotactivo'])) {
    $where_art[] = "far_medicamento_lote.estado = 1";
}

if (!empty($_POST['lote_ven'])) {
    if ($_POST['lote_ven'] == 1) {
        $where_art[] = "far_medicamento_lote.fec_vencimiento < '$fecha'";
    } else {
        $where_art[] = "far_medicamento_lote.fec_vencimiento >= '$fecha'";
    }
}

$where_art = 'WHERE ' . implode(' AND ', $where_art);

try {

    /* =========================================================
       ELIMINAR TEMPORAL
    ========================================================= */

    $cmd->exec("DROP TEMPORARY TABLE IF EXISTS tmp_existencias");

    /* =========================================================
       CREAR TABLA TEMPORAL
    ========================================================= */
    $sql ='';
    switch ($id_reporte) {
    case '2':
        $sql = "CREATE TEMPORARY TABLE tmp_existencias AS
                SELECT tb_sedes.id_sede,tb_sedes.nom_sede,
                    far_bodegas.id_bodega,far_bodegas.nombre AS nom_bodega,
                    far_subgrupos.id_subgrupo,CONCAT_WS(' - ',far_subgrupos.cod_subgrupo,far_subgrupos.nom_subgrupo) AS nom_subgrupo,
                    far_medicamentos.id_med,far_medicamentos.cod_medicamento,far_medicamentos.nom_medicamento,
                    far_medicamento_lote.id_lote,far_medicamento_lote.lote,far_medicamento_lote.fec_vencimiento,
                    e.existencia_lote,
                    v.val_promedio AS val_unitario,
                    (e.existencia_lote * v.val_promedio) AS val_total
                FROM far_medicamento_lote
                INNER JOIN far_medicamentos ON far_medicamentos.id_med = far_medicamento_lote.id_med
                INNER JOIN far_subgrupos ON far_subgrupos.id_subgrupo = far_medicamentos.id_subgrupo
                INNER JOIN far_bodegas ON far_bodegas.id_bodega = far_medicamento_lote.id_bodega
                INNER JOIN tb_sedes_bodega ON tb_sedes_bodega.id_bodega = far_bodegas.id_bodega
                INNER JOIN tb_sedes ON tb_sedes.id_sede = tb_sedes_bodega.id_sede
                INNER JOIN (SELECT id_lote,MAX(id_kardex) AS id
                            FROM far_kardex
                            $where_kar
                            GROUP BY id_lote
                            ) AS kei ON kei.id_lote = far_medicamento_lote.id_lote
                INNER JOIN far_kardex AS e ON e.id_kardex = kei.id
                INNER JOIN (SELECT id_med, MAX(id_kardex) AS id
                            FROM far_kardex
                            WHERE fec_movimiento <= '$fecha' AND estado = 1
                            GROUP BY id_med
                            ) AS kvi ON kvi.id_med = far_medicamento_lote.id_med
                INNER JOIN far_kardex AS v ON v.id_kardex = kvi.id
                $where_art";
        break;
    case '5':
        $sql = "CREATE TEMPORARY TABLE tmp_existencias AS
                SELECT tb_sedes.id_sede,tb_sedes.nom_sede,
                    far_bodegas.id_bodega,far_bodegas.nombre AS nom_bodega,
                    far_subgrupos.id_subgrupo,CONCAT_WS(' - ',far_subgrupos.cod_subgrupo,far_subgrupos.nom_subgrupo) AS nom_subgrupo,
                    far_medicamentos.id_med,far_medicamentos.cod_medicamento,far_medicamentos.nom_medicamento,
                    far_medicamento_lote.id_lote,far_medicamento_lote.lote,far_medicamento_lote.fec_vencimiento,
                    e.existencia_lote,
                    v.val_ingreso AS val_unitario,
                    (e.existencia_lote * v.val_ingreso) AS val_total
                FROM far_medicamento_lote
                INNER JOIN far_medicamentos ON far_medicamentos.id_med = far_medicamento_lote.id_med
                INNER JOIN far_subgrupos ON far_subgrupos.id_subgrupo = far_medicamentos.id_subgrupo
                INNER JOIN far_bodegas ON far_bodegas.id_bodega = far_medicamento_lote.id_bodega
                INNER JOIN tb_sedes_bodega ON tb_sedes_bodega.id_bodega = far_bodegas.id_bodega
                INNER JOIN tb_sedes ON tb_sedes.id_sede = tb_sedes_bodega.id_sede
                INNER JOIN (SELECT id_lote,MAX(id_kardex) AS id
                            FROM far_kardex
                            $where_kar
                            GROUP BY id_lote
                            ) AS kei ON kei.id_lote = far_medicamento_lote.id_lote
                INNER JOIN far_kardex AS e ON e.id_kardex = kei.id
                INNER JOIN (SELECT id_med, MAX(id_kardex) AS id
                            FROM far_kardex
                            WHERE fec_movimiento <= '$fecha' AND estado = 1 AND (id_ingreso IS NOT NULL OR id_ingreso_tra_r IS NOT NULL)
                            GROUP BY id_med
                            ) AS kvi ON kvi.id_med = far_medicamento_lote.id_med
                INNER JOIN far_kardex AS v ON v.id_kardex = kvi.id
                $where_art";
        break;    
    }        
    $cmd->exec($sql);

    /* =========================================================
       INDICES TEMPORALES
    ========================================================= */

    $cmd->exec("
        ALTER TABLE tmp_existencias
        ADD INDEX idx1(id_sede,id_bodega),
        ADD INDEX idx2(id_subgrupo),
        ADD INDEX idx3(id_med)
    ");

    /* =========================================================
       FILTRO EXISTENCIA
    ========================================================= */

    if (!empty($_POST['con_existencia'])) {
        if ($_POST['con_existencia'] == 1) {
            $cmd->exec("DELETE FROM tmp_existencias WHERE existencia_lote < 1");
        } else {
            $cmd->exec("DELETE FROM tmp_existencias WHERE existencia_lote > 0");
        }
    }

    /* =========================================================
       CONSULTA PRINCIPAL
    ========================================================= */

    $sql = "SELECT id_sede,nom_sede,id_bodega,nom_bodega,
                SUM(val_total) AS val_total_sb
            FROM tmp_existencias
            GROUP BY id_sede,id_bodega
            ORDER BY id_sede,nom_bodega";
    $rs = $cmd->query($sql);
    $objs = $rs->fetchAll(PDO::FETCH_ASSOC);

    /* =========================================================
       CONSULTA SUBGRUPOS
    ========================================================= */

    $sql = "SELECT id_subgrupo,nom_subgrupo,
                SUM(val_total) AS val_total_sg
            FROM tmp_existencias
            WHERE id_sede = :id_sede AND id_bodega = :id_bodega
            GROUP BY id_subgrupo
            ORDER BY id_subgrupo";
    $rs_g = $cmd->prepare($sql);

    /* =========================================================
       CONSULTA DETALLE
    ========================================================= */

    $sql = "SELECT id_med,cod_medicamento,nom_medicamento,
                SUM(existencia_lote) AS existencia,val_unitario,
                SUM(val_total) AS val_total
            FROM tmp_existencias
            WHERE id_sede = :id_sede AND id_bodega = :id_bodega AND id_subgrupo = :id_subgrupo
            GROUP BY id_med
            ORDER BY nom_medicamento";
    $rs_d = $cmd->prepare($sql);

} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    exit;
}
?>

<div class="text-end py-3">
    <a type="button" id="btnExcelEntrada" class="btn btn-outline-success btn-sm" title="Exportar a Excel">
        <span class="fas fa-file-excel fa-lg"></span>
    </a>
    <a type="button" class="btn btn-primary btn-sm" id="btnImprimir">Imprimir</a>
    <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</a>
</div>

<div class="content bg-light" id="areaImprimir">
    <style>
        @media print {
            body {
                font-family: Arial, sans-serif;
            }
        }
        .resaltar:nth-child(even) {
            background-color: #F8F9F9;
        }
        .resaltar:nth-child(odd) {
            background-color: #ffffff;
        }
    </style>

    <?php include('../common/reporte_header.php'); ?>

    <table style="width:100%; font-size:70%">
        <tr style="text-align:center">
            <th><?= $titulo ?></th>
        </tr>
    </table>

    <table style="width:100% !important">
        <tbody style="font-size:60%">
        <?php

        $tabla = '';
        switch ($id_reporte) {
            case '2':
                $tabla .= '
                    <tr style="background-color:#CED3D3; text-align:center">
                        <th>ID Articulo</th>
                        <th>Código Art.</th>
                        <th>Nombre</th>                        
                        <th>Existencia</th>
                        <th>Vr. Promedio</th>
                        <th>Vr. Parcial</th>
                        <th>Vr. Total</th>
                    </tr>';
                break;
             case '5':
                $tabla .= '
                    <tr style="background-color:#CED3D3; text-align:center">
                        <th>ID Articulo</th>
                        <th>Código Art.</th>
                        <th>Nombre</th>                        
                        <th>Existencia</th>
                        <th>Vr. Último Ingreso</th>
                        <th>Vr. Parcial</th>
                        <th>Vr. Total</th>
                    </tr>';
                break;    
        }

        $total = 0;
        $numreg = 0;

        foreach ($objs as $obj1) {
            $tabla .= '
            <tr>
                <th colspan="6" style="text-align:left">' . strtoupper($obj1['nom_sede'] . ' - ' . $obj1['nom_bodega']) . '</th>
                <th style="text-align:right">' . formato_valor($obj1['val_total_sb']) . '</th>
            </tr>';

            $rs_g->execute([
                ':id_sede'   => $obj1['id_sede'],
                ':id_bodega' => $obj1['id_bodega']
            ]);

            $objg = $rs_g->fetchAll(PDO::FETCH_ASSOC);
            foreach ($objg as $obj2) {
                $tabla .= '
                <tr>
                    <th colspan="5" style="text-align:left">' . str_repeat('&nbsp;', 10) . strtoupper($obj2['nom_subgrupo']) . '</th>                        
                    <th style="text-align:right">' . formato_valor($obj2['val_total_sg']) . '</th>
                </tr>';

                $rs_d->execute([
                    ':id_sede'     => $obj1['id_sede'],
                    ':id_bodega'   => $obj1['id_bodega'],
                    ':id_subgrupo' => $obj2['id_subgrupo']
                ]);

                $objd = $rs_d->fetchAll(PDO::FETCH_ASSOC);
                foreach ($objd as $obj) {
                    $tabla .= '
                    <tr class="resaltar">
                        <td>' . str_repeat('&nbsp;', 20) . $obj['id_med'] . '</td>
                        <td>' . $obj['cod_medicamento'] . '</td>
                        <td style="text-align:left">' . mb_strtoupper($obj['nom_medicamento']) . '</td>
                        <td style="text-align:center">' . $obj['existencia'] . '</td>
                        <td style="text-align:right">' . formato_valor($obj['val_unitario']) . '</td>
                        <td style="text-align:right">' . formato_valor($obj['val_total']) . '</td>
                    </tr>';
                    
                    $numreg++;
                }
                $total += $obj['val_total'];
            }
        }
        echo $tabla;
        ?>

        </tbody>
        <tfoot style="font-size:60%">
            <tr style="background-color:#CED3D3">
                <th colspan="5" style="text-align:left">
                    No. Registros: <?= $numreg ?>
                </th>
                <th style="text-align:left">
                    TOTAL:
                </th>
                <th style="text-align:center">
                    <?= formato_valor($total) ?>
                </th>
            </tr>
        </tfoot>
    </table>

</div>