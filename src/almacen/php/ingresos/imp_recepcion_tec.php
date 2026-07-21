<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}

include '../../../../config/autoloader.php';
include '../common/funciones_generales.php';

$cmd = \Config\Clases\Conexion::getConexion();

$id = isset($_POST['id']) ? $_POST['id'] : -1;

try {
    $sql = "SELECT far_orden_ingreso.num_ingreso,far_orden_ingreso.fec_ingreso,far_orden_ingreso.hor_ingreso,
                far_orden_ingreso.num_factura, far_orden_ingreso.fec_factura,
                tb_terceros.nom_tercero,tb_terceros.dir_tercero,tb_municipios.nom_municipio,     
                CONCAT_WS(' ',usr.nombre1,usr.nombre2,usr.apellido1,usr.apellido2) AS usr_crea,
                usr.descripcion AS usr_perfil,usr.nom_firma
            FROM far_orden_ingreso
            INNER JOIN tb_terceros ON (tb_terceros.id_tercero = far_orden_ingreso.id_provedor)
            LEFT JOIN tb_municipios ON (tb_municipios.id_municipio = tb_terceros.id_municipio)
            LEFT JOIN seg_usuarios_sistema AS usr ON(usr.id_usuario = far_orden_ingreso.id_usr_crea) 
            WHERE far_orden_ingreso.id_ingreso=$id LIMIT 1";
    $rs = $cmd->query($sql);
    $obj_e = $rs->fetch();

    $sql = "SELECT far_medicamentos.nom_medicamento,
                far_laboratorios.nom_laboratorio,
                far_medicamento_lote.serie,far_medicamento_cum.cum,
                far_orden_ingreso_detalle.cantidad,
                far_medicamento_lote.lote,far_medicamento_lote.fec_vencimiento,
                far_medicamento_lote.reg_invima,                
                far_orden_ingreso_detalle.tam_muestra,far_orden_ingreso_detalle.def_menores,
                far_orden_ingreso_detalle.def_mayores,far_orden_ingreso_detalle.def_criticos,
                'SI' AS aprobado,far_orden_ingreso_detalle.obs_recepcion                                     
            FROM far_orden_ingreso_detalle
            INNER JOIN far_medicamento_lote ON (far_medicamento_lote.id_lote = far_orden_ingreso_detalle.id_lote)
            INNER JOIN far_medicamento_cum ON(far_medicamento_cum.id_cum = far_medicamento_lote.id_cum)
            INNER JOIN far_laboratorios ON (far_laboratorios.id_lab = far_medicamento_cum.id_lab)
            INNER JOIN far_medicamentos ON (far_medicamentos.id_med = far_medicamento_lote.id_med)
            WHERE far_orden_ingreso_detalle.id_ingreso=" . $id . " ORDER BY far_orden_ingreso_detalle.id_ing_detalle";
    $rs = $cmd->query($sql);
    $obj_ds = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
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
            <th>RECEPCIÓN DE MEDICAMENTOS E INSUMOS</th>
        </tr>
    </table>

    <table style="width:100%; font-size:60%; text-align:left; border:#A9A9A9 1px solid;">
        <tr style="background-color:#CED3D3; border:#A9A9A9 1px solid">
            <td>Proveedor</td>
            <td>Direccion</td>
            <td>Ciudad</td>            
        </tr>
        <tr>
            <td><?php echo $obj_e['nom_tercero']; ?></td>
            <td><?php echo $obj_e['dir_tercero']; ?></td>
            <td><?php echo $obj_e['nom_municipio']; ?></td>
        </tr>
        <tr style="background-color:#CED3D3; border:#A9A9A9 1px solid">
            <td>Fecha Recepción</td>
            <td>No. Factura/Acta/Remisión</td>
            <td>Fecha Factura/Acta/Remisión</td>
        </tr>
        <tr>
            <td><?php echo $obj_e['fec_ingreso']; ?></td>
            <td><?php echo $obj_e['num_factura']; ?></td>
            <td><?php echo $obj_e['fec_factura']; ?></td>
        </tr>        
    </table>

    <table style="width:100% !important">
        <thead style="font-size:60%">
            <tr style="background-color:#CED3D3; color:#000000; text-align:center">
                <th rowspan="2">No</th>
                <th colspan="4">Producto</th>
                <th rowspan="2">Cantidad</th>
                <th colspan="2">Lote</th>
                <th rowspan="2">Invima</th>
                <th rowspan="2">Tamaño Muestra</th>
                <th colspan="3">No. Defecos</th>
                <th colspan="2">Aprobado</th>
                <th rowspan="2">Observación</th>
            </tr>
            <tr style="background-color:#CED3D3; color:#000000; text-align:center">
                <th>Nombre</th>
                <th>Laboratorio</th>
                <th>Serie</th>
                <th>CUM</th>
                <th>No.</th>
                <th>Fec. Vence</th>
                <th>Menores</th>
                <th>Mayores</th>
                <th>Críticos</th>
                <th>SI</th>
                <th>NO</th>
            </tr>
        </thead>
        <tbody style="font-size: 60%;">
            <?php
            $tabla = '';
            $n = 1;
            foreach ($obj_ds as $obj) {
                $tabla .=  '<tr class="resaltar"> 
                        <td>' . $n . '</td>
                        <td>' . $obj['nom_medicamento'] . '</td>
                        <td>' . $obj['nom_laboratorio'] . '</td>                        
                        <td>' . $obj['serie'] . '</td>
                        <td>' . $obj['cum'] . '</td>
                        <td>' . $obj['cantidad'] . '</td>
                        <td>' . $obj['lote'] . '</td>
                        <td>' . $obj['fec_vencimiento'] . '</td>
                        <td>' . $obj['reg_invima'] . '</td>
                        <td>' . $obj['tam_muestra'] . '</td>
                        <td>' . $obj['def_menores'] . '</td>
                        <td>' . $obj['def_mayores'] . '</td>
                        <td>' . $obj['def_criticos'] . '</td>
                        <td>' . $obj['aprobado'] . '</td>
                        <td></td></tr>';            }
            echo $tabla;
            $n++;
            ?>
        </tbody> 
        <tfoot style="font-size:60%; font-weight:bold;">
            <tr style="background-color:#CED3D3; color:#000000">
                <td colspan="16"></td>
            </tr>
        </tfoot>       
    </table>

    <table style="width:100%; font-size:70%; text-align:center">
        <tr style="height: 25px;">
        </tr>
        <tr>
            <td style="vertical-align: top">
                <div>-------------------------------------------------</div>
                <div><?php echo $obj_e['usr_crea']; ?></div>
                <div><?php echo $obj_e['usr_perfil']; ?></div>
            </td>
            <td style="vertical-align: top">
                <div>Firma: ---------------------------------------------------</div>                
                <div>Nombre: -------------------------------------------------</div>
                <div>Cédula: -------------------------------------------------</div>                
            </td>
        </tr>
    </table>
</div>