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

$where_usr = " WHERE HV.estado IN (1,2,3,4)";
if (isset($_POST['id_sede']) && $_POST['id_sede']) {
    $where_usr .= " AND HV.id_sede=" . $_POST['id_sede'];
}
if (isset($_POST['id_area']) && $_POST['id_area']) {
    $where_usr .= " AND HV.id_area=" . $_POST['id_area'];
}

$where = " WHERE (FG.id_grupo IN (3,4,5) OR FG.af_menor_cuantia=1)";
if (isset($_POST['codigo']) && $_POST['codigo']) {
    $where .= " AND ME.cod_medicamento LIKE '" . $_POST['codigo'] . "%'";
}
if (isset($_POST['nombre']) && $_POST['nombre']) {
    $where .= " AND ME.nom_medicamento LIKE '" . $_POST['nombre'] . "%'";
}
if (isset($_POST['id_subgrupo']) && $_POST['id_subgrupo']) {
    $where .= " AND ME.id_subgrupo=" . $_POST['id_subgrupo'];
}
if (isset($_POST['art_activo']) && $_POST['art_activo']) {
    $where .= " AND ME.estado=1";
}

if (isset($_POST['con_existencia']) && $_POST['con_existencia']) {
    if ($_POST['con_existencia'] == 1) {
        $where .= " AND IF(EX.existencia IS NULL,0,EX.existencia)>=1";
    } else {
        $where .= " AND IF(EX.existencia IS NULL,0,EX.existencia)=0";
    }
}

try {
    $sql = "SELECT ME.id_med,ME.cod_medicamento,ME.nom_medicamento,
                FG.nom_subgrupo,
                IF(EX.existencia IS NULL,0,EX.existencia) AS existencia,
                IF(EX.existencia IS NULL,0,EX.val_total/EX.existencia) AS val_promedio,
                IF(EX.val_total IS NULL,0,EX.val_total) AS val_total,
                IF(DE.valor IS NULL,0,DE.valor) AS val_ult_compra,
                IF(ME.estado=1,'ACTIVO','INACTIVO') AS estado
            FROM far_medicamentos AS ME
            INNER JOIN far_subgrupos AS FG ON (FG.id_subgrupo=ME.id_subgrupo)
            LEFT JOIN (SELECT id_articulo, COUNT(*) AS existencia,SUM(valor) AS val_total
                        FROM acf_hojavida AS HV
                        $where_usr
                        GROUP BY id_articulo) AS EX ON (EX.id_articulo=ME.id_med)
            LEFT JOIN (SELECT OED.id_articulo,
                            MAX(OED.id_ing_detalle) AS id 
                        FROM acf_orden_ingreso_detalle AS OED
                        INNER JOIN acf_orden_ingreso AS OE ON (OE.id_ingreso=OED.id_ingreso)
                        WHERE OE.estado=2
                        GROUP BY OED.id_articulo) AS VU ON (VU.id_articulo=ME.id_med)	   
            LEFT JOIN acf_orden_ingreso_detalle AS DE ON (DE.id_ing_detalle=VU.id)
            $where ORDER BY ME.nom_medicamento ASC";
    $rs = $cmd->query($sql);
    $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);

 $sql = "SELECT SUM(IF(EX.val_total IS NULL,0,EX.val_total)) AS val_total
        FROM far_medicamentos AS ME
        INNER JOIN far_subgrupos AS FG ON (FG.id_subgrupo=ME.id_subgrupo)
        LEFT JOIN (SELECT id_articulo, SUM(valor) AS val_total
                    FROM acf_hojavida AS HV
                    $where_usr
                    GROUP BY id_articulo) AS EX ON (EX.id_articulo=ME.id_med)
        $where";
    $rs = $cmd->query($sql);
    $obj_tot = $rs->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
?>
<div class="text-end py-3">
    <a type="button" id="btnExcelEntrada" class="btn btn-outline-success btn-sm" value="01" title="Exprotar a Excel">
        <span class="fas fa-file-excel fa-lg" aria-hidden="true"></span>
    </a>
    <a type="button" class="btn btn-primary btn-sm" id="btnImprimir">Imprimir</a>
    <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cerrar</a>
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
            <th>REPORTE DE EXISTENCIAS</th>
        </tr>
    </table>

    <table style="width:100% !important">
        <thead style="font-size:60%">
            <tr style="background-color:#CED3D3; color:#000000; text-align:center">
                <th>ID</th>
                <th>Código</th>
                <th>Nombre</th>
                <th>Subgrupo</th>
                <th>Existencia</th>
                <th>Vr. Promedio</th>
                <th>Vr. Total</th>
                <th>Valor Última Compra</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody style="font-size: 60%;">
            <?php
            $tabla = '';
            foreach ($objs as $obj) {
                $tabla .=  '<tr class="resaltar"> 
                        <td>' . $obj['id_med'] . '</td>                                                
                        <td>' . $obj['cod_medicamento'] . '</td>
                        <td style="text-align:left">' . mb_strtoupper($obj['nom_medicamento']) . '</td>
                        <td style="text-align:left">' . mb_strtoupper($obj['nom_subgrupo']) . '</td>   
                        <td style="text-align:right">' . $obj['existencia'] . '</td>   
                        <td style="text-align:right">' . formato_valor($obj['val_promedio']) . '</td>   
                        <td style="text-align:right">' . formato_valor($obj['val_total']) . '</td>  
                        <td style="text-align:right">' . formato_valor($obj['val_ult_compra']) . '</td>                        
                        <td style="text-align:center">' . $obj['estado'] . '</td></tr>';
            }
            echo $tabla;
            ?>
        </tbody>
        <tfoot style="font-size:60%">
            <tr style="background-color:#CED3D3; color:#000000">
                <td colspan="5" style="text-align:left">
                    No. de Registros: <?php echo count($objs); ?>
                </td>
                <td style="text-align:right">
                    TOTAL:
                </td>
                <td style="text-align:right">
                    <?php echo formato_valor($obj_tot['val_total']); ?>
                </td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>