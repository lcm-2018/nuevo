<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}

include '../../../../config/autoloader.php';
use Src\Analytics\Conf_Consultas\Php\Clases\ConsultasModel;

$filters = [
    'nombre' => isset($_POST['nombre']) ? trim($_POST['nombre']) : '',
    'estado' => isset($_POST['estado']) ? $_POST['estado'] : '',
];

try {
    $model = new ConsultasModel();
    $objs = $model->fetchList($filters, 0, -1, 'id_consulta', 'DESC');
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

    <?php include('../../common/php/reporte_header.php'); ?>

    <table style="width:100%; font-size:80%">
        <tr style="text-align:center">
            <th>REPORTE DE CONSULTAS ANALÍTICAS</th>
        </tr>
    </table>

    <table style="width:100% !important">
        <thead style="font-size:80%">
            <tr style="background-color:#CED3D3; color:#000000; text-align:center">
                <th>ID</th>
                <th>Título</th>                
                <th>Tipo Base Datos</th>
                <th>Tipo Informe</th>
                <th>Tipo Servidor</th>
                <th>Tipo Acceso</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody style="font-size: 60%;">
            <?php
            $tabla = '';
            foreach ($objs as $obj) {
                $tabla .=  '<tr class="resaltar" style="text-align:center"> 
                    <td>' . $obj['id_consulta'] . '</td>                    
                    <td style="text-align:left">' . mb_strtoupper($obj['titulo_consulta']) . '</td>
                    <td>' . $obj['tipo_bdatos'] . '</td>
                    <td>' . $obj['tipo_informe'] . '</td>
                    <td>' . $obj['tipo_consulta'] . '</td>
                    <td>' . $obj['tipo_acceso'] . '</td>
                    <td>' . $obj['estado'] . '</td></tr>';
            }
            echo $tabla;
            ?>
        </tbody>
        <tfoot style="font-size:60%">
            <tr style="background-color:#CED3D3; color:#000000">
                <td colspan="6" style="text-align:left">
                    No. de Registros: <?php echo count($objs); ?>
                </td>
            </tr>
        </tfoot>
    </table>
</div>