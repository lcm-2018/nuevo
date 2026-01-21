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

$id_reporte = $_POST['id_reporte'];
$titulo = 'REPORTE DE EXISTENCIAS POR ARTICULO - AGRUPADO POR SEDE';

$where = " WHERE HV.estado IN (1,2,3,4) AND (FG.id_grupo IN (3,4,5) OR FG.af_menor_cuantia=1)";
if (isset($_POST['id_sede']) && $_POST['id_sede']) {
    $where .= " AND HV.id_sede=" . $_POST['id_sede'];
}
if (isset($_POST['id_area']) && $_POST['id_area']) {
    $where .= " AND HV.id_area=" . $_POST['id_area'];
}
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
    if ($_POST['con_existencia'] == 2){
        $where .= " AND ME.id_med=0";
    }    
}

try {
    $sql = "SELECT SE.id_sede,SE.nom_sede,SUM(HV.valor) AS val_total	
            FROM acf_hojavida AS HV
            INNER JOIN far_medicamentos AS ME ON (ME.id_med=HV.id_articulo)
            INNER JOIN far_subgrupos AS FG ON (FG.id_subgrupo=ME.id_subgrupo)
            INNER JOIN tb_sedes SE ON (SE.id_sede=HV.id_sede)
            $where
            GROUP BY SE.id_sede
            ORDER BY SE.id_sede";
    $rs = $cmd->query($sql);
    $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);

    $sql = "SELECT ME.id_med,ME.cod_medicamento,ME.nom_medicamento,ME.top_min,ME.top_max,
                FG.nom_subgrupo,COUNT(*) AS existencia,
                SUM(HV.valor)/COUNT(*) AS val_promedio,
                SUM(HV.valor) AS val_total	
            FROM acf_hojavida AS HV
            INNER JOIN far_medicamentos AS ME ON (ME.id_med=HV.id_articulo)
            INNER JOIN far_subgrupos AS FG ON (FG.id_subgrupo=ME.id_subgrupo)
            $where AND HV.id_sede=:id_sede
            GROUP BY ME.id_med
            ORDER BY ME.nom_medicamento";
    $rs_d = $cmd->prepare($sql);
        
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
            <th><?php echo $titulo; ?></th>
        </tr>
    </table>

    <table style="width:100% !important">
        <tbody style="font-size: 60%;">
            <?php
           
            $tabla = '<tr style="background-color:#CED3D3; text-align:center">
                <th>ID Art.</th><th>Código</th><th>Nombre</th><th>Subgrupo</th>
                <th>Tope Mínimo</th><th>Tope Máximo</th><th>Existencia</th>
                <th>Vr. Promedio</th><th>Vr. Parcial</th><th>Vr. Total</th></tr>';
           
            $total = 0;
            $numreg = 0;

            foreach ($objs as $obj1) {

                $id_sede = $obj1['id_sede'];
                $tabla .= '<tr><th colspan="9" style="text-align:left">' . strtoupper($obj1['nom_sede']) . '</th><th style="text-align:right">' . formato_valor($obj1['val_total']) . '</th></tr>';

                $rs_d->bindParam(':id_sede', $id_sede);
                $rs_d->execute();
                $objd = $rs_d->fetchAll();

                foreach ($objd as $obj) {
                    $tabla .=  '<tr class="resaltar"> 
                            <td>' . str_repeat('&nbsp', 10) . $obj['id_med'] . '</td>
                            <td>' . $obj['cod_medicamento'] . '</td>
                            <td style="text-align:left">' . mb_strtoupper($obj['nom_medicamento']) . '</td>   
                            <td>' . mb_strtoupper($obj['nom_subgrupo']) . '</td>   
                            <td>' . $obj['top_min'] . '</td>   
                            <td>' . $obj['top_max'] . '</td>   
                            <td>' . $obj['existencia'] . '</td>   
                            <td style="text-align:right">' . formato_valor($obj['val_promedio']) . '</td>   
                            <td style="text-align:right">' . formato_valor($obj['val_total']) . '</td></tr>';
                    $total += $obj['val_total'];
                    $numreg += 1;
                }
            }
            echo $tabla;
            ?>
        </tbody>
        <tfoot style="font-size:60%">
            <tr style="background-color:#CED3D3; color:#000000">
                <th colspan="8" style="text-align:left">
                    No. de Registros: <?php echo $numreg; ?>
                </th>
                <th style="text-align:left">
                    TOTAL:
                </th>
                <th style="text-align:right">
                    <?php echo formato_valor($total); ?>
                </th>
            </tr>
        </tfoot>
    </table>
</div>