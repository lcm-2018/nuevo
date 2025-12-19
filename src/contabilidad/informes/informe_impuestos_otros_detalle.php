<?php
session_start();
set_time_limit(5600);
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
$vigencia = $_SESSION['vigencia'];
// estraigo las variables que llegan por post en json
$fecha_inicial = $_POST['fecha_inicial'];
$fecha_corte = $_POST['fecha_final'];
$id_des = $_POST['id_tercero'];
function pesos($valor)
{
    return '$' . number_format($valor, 2);
}
include '../../../config/autoloader.php';
include '../../financiero/consultas.php';
$cmd = \Config\Clases\Conexion::getConexion();
// consulto la tabla tb_terceros para obtener el id_tercero_api
try {
    $sql = "SELECT
    `ctb_retenciones`.`id_retencion`
    , `ctb_retenciones`.`nombre_retencion`
    , `ctb_doc`.`id_manu`
    , `ctb_doc`.`fecha`
    , `ctb_causa_retencion`.`id_terceroapi`
    , `ctb_doc`.`detalle`
    , `ctb_causa_retencion`.`valor_retencion`
FROM
    `ctb_causa_retencion`
    INNER JOIN `ctb_retenciones` 
        ON (`ctb_causa_retencion`.`id_retencion` = `ctb_retenciones`.`id_retencion`)
    INNER JOIN `ctb_retencion_tipo` 
        ON (`ctb_retenciones`.`id_retencion_tipo` = `ctb_retencion_tipo`.`id_retencion_tipo`)
    INNER JOIN `ctb_doc` 
        ON (`ctb_causa_retencion`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
WHERE (`ctb_retenciones`.`id_retencion` = $id_des
    AND `ctb_doc`.`fecha`  BETWEEN '$fecha_inicial' AND '$fecha_corte');";
    $res = $cmd->query($sql);
    $descuentos = $res->fetchAll();
    $res->closeCursor();
    unset($res);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}


// consulto el nombre de la empresa de la tabla tb_datos_ips
try {
    $sql = "SELECT
    `nombre`
    , `nit`
    , `dig_ver`
FROM
    `tb_datos_ips`;";
    $res = $cmd->query($sql);
    $empresa = $res->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}


?>
<div class="contenedor bg-light" id="areaImprimir">
    <div class="px-2 " style="width:90% !important;margin: 0 auto;">
        </br>
        </br>
        <table class="table-bordered bg-light" style="width:100% !important;">
            <tr>
                <td colspan="6" style="text-align:center"><?php echo ''; ?></td>
            </tr>

            <tr>
                <td colspan="6" style="text-align:center"><?php echo '<h3>' . $empresa['nombre'] . '</h3>'; ?></td>
            </tr>
            <tr>
                <td colspan="6" style="text-align:center"><?php echo $empresa['nit'] . '-' . $empresa['dig_ver']; ?></td>
            </tr>
            <tr>
                <td colspan="6" style="text-align:center"><?php echo 'RELACION DE DESCUENTOS Y RETENCIONES DETALLADO'; ?></td>
            </tr>
            <tr>
                <td colspan="6" style="text-align:center"></td>
            </tr>
            <tr>
                <td colspan="6" style="text-align:center"><?php echo ''; ?></td>
            </tr>
        </table>
        </br>
        </br>
        <table class="table-bordered bg-light" style="width:100% !important;">
            <tr>
                <td>MUNICIPIO</td>
                <td style='text-align: left;'><?php //echo $tercero; 
                                                ?></td>
            </tr>
            <tr>
                <td>NIT</td>
                <td style='text-align: left;'><?php //echo $ccnit; 
                                                ?></td>
            </tr>
            <tr>
                <td>FECHA INICIO</td>
                <td style='text-align: left;'><?php echo $fecha_inicial; ?></td>
            </tr>
            <tr>
                <td>FECHA FIN</td>
                <td style='text-align: left;'><?php echo $fecha_corte; ?></td>
            </tr>
        </table>
        </br> &nbsp;
        </br>
        <table class="table-bordered bg-light" style="width:100% !important;" border=1>
            <tr>
                <td>Comprobante</td>
                <td>Fecha</td>
                <td>Nombre</td>
                <td>Detalle</td>
                <td>Valor</td>
            </tr>
            <?php
            $total_ret = 0;
            foreach ($descuentos as $tp) {
                $nom_ter = !empty($tp['nom_tercero']) ? $tp['nom_tercero'] : '---';
                $ced_ter = !empty($tp['nit_tercero']) ? $tp['nit_tercero'] : '---';
                $fecha = date('Y-m-d', strtotime($tp['fecha']));
                echo "<tr>
                    <td class='text-end'>" . $tp['id_manu'] . "</td>
                    <td class='text-end'>" . $fecha . "</td>
                    <td class='text-end'>" . $nom_ter  . "</td>
                    <td class='text'>" . $ced_ter . "</td>
                    <td class='text-end'>" . $tp['detalle'] . "</td>
                    <td class='text-end'>" . number_format($tp['valor_retencion'], 2, ".", ",")  . "</td>
                    </tr>";
                $total_ret = $total_ret + $tp['valor_retencion'];
            }
            echo "<tr>
        <td class='text-end' colspan='5'> Total</td>
        <td class='text-end'>" . number_format($total_ret, 2, ".", ",")  . "</td>
        </tr>
        </table>
        </br> &nbsp;
        ";
            ?>
        </table>
    </div>
</div>

</html>