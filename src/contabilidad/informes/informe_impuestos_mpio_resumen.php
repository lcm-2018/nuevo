<?php

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Valores;
use Src\Usuarios\Login\Php\Clases\Usuario;

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
$sede = $_POST['id_tercero'];
function pesos($valor)
{
    return '$' . number_format($valor, 2);
}
include '../../../config/autoloader.php';
include '../../financiero/consultas.php';
$cmd = \Config\Clases\Conexion::getConexion();
// consulto la tabla seg_terceros para obtener el id_tercero_api
try {
    $sql = "SELECT
                `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
                , `tb_terceros`.`id_tercero_api`
            FROM
                `tb_terceros`
            WHERE (`tb_terceros`.`id_tercero_api` = $sede)";
    $res = $cmd->query($sql);
    $tercero = $res->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
//Consulto descuentos de rete ICA
try {
    $sql = "SELECT
                `ctb_retencion_tipo`.`id_retencion_tipo`
                , `ctb_retenciones`.`nombre_retencion`
                , `ctb_retencion_tipo`.`tipo`
                , `ctb_retenciones`.`id_retencion`
                , `ctb_pgcp`.`cuenta`
                , SUM(`ctb_causa_retencion`.`valor_base`) AS `base`
                , SUM(`ctb_causa_retencion`.`valor_retencion`) AS `retencion`
            FROM
                `ctb_retenciones`
                INNER JOIN `ctb_retencion_tipo` 
                    ON (`ctb_retenciones`.`id_retencion_tipo` = `ctb_retencion_tipo`.`id_retencion_tipo`)
                INNER JOIN `ctb_retencion_rango` 
                    ON (`ctb_retencion_rango`.`id_retencion` = `ctb_retenciones`.`id_retencion`)
                INNER JOIN `ctb_causa_retencion` 
                    ON (`ctb_causa_retencion`.`id_rango` = `ctb_retencion_rango`.`id_rango`)
                INNER JOIN `ctb_doc` 
                    ON (`ctb_causa_retencion`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                INNER JOIN `ctb_pgcp`
                    ON (`ctb_retenciones`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`)
            WHERE (`ctb_retencion_tipo`.`id_retencion_tipo` = 3
                AND DATE_FORMAT(`ctb_doc`.`fecha`,'%Y-%m-%d') BETWEEN '$fecha_inicial' AND '$fecha_corte'
                AND `ctb_causa_retencion`.`id_terceroapi` ={$tercero['id_tercero_api']})
                GROUP BY `ctb_retenciones`.`nombre_retencion`";
    $res = $cmd->query($sql);
    $causaciones = $res->fetchAll();
    $res->closeCursor();
    unset($res);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
//Consulto descuentos de sobretasa
try {
    $sql = "SELECT
                `ctb_retencion_tipo`.`id_retencion_tipo`
                , `ctb_retencion_tipo`.`tipo`
                , `ctb_retenciones`.`nombre_retencion`
                , `ctb_retenciones`.`id_retencion`
                , `ctb_pgcp`.`cuenta`
                , SUM(`ctb_causa_retencion`.`valor_base`) AS base
                , SUM(`ctb_causa_retencion`.`valor_retencion`) AS retencion
            FROM
                `ctb_retenciones`
                INNER JOIN `ctb_retencion_tipo` 
                    ON (`ctb_retenciones`.`id_retencion_tipo` = `ctb_retencion_tipo`.`id_retencion_tipo`)
                INNER JOIN `ctb_retencion_rango` 
                    ON (`ctb_retencion_rango`.`id_retencion` = `ctb_retenciones`.`id_retencion`)
                INNER JOIN `ctb_causa_retencion` 
                    ON (`ctb_causa_retencion`.`id_rango` = `ctb_retencion_rango`.`id_rango`)
                INNER JOIN `ctb_doc` 
                    ON (`ctb_causa_retencion`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                INNER JOIN `ctb_pgcp`
                    ON (`ctb_retenciones`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`)
            WHERE (`ctb_retencion_tipo`.`id_retencion_tipo` =4
                AND DATE_FORMAT(`ctb_doc`.`fecha`,'%Y-%m-%d') BETWEEN '$fecha_inicial' AND '$fecha_corte'
                AND `ctb_causa_retencion`.`id_terceroapi` ={$tercero['id_tercero_api']})
            GROUP BY `ctb_retenciones`.`nombre_retencion`";
    $res = $cmd->query($sql);
    $sobretasa = $res->fetchAll();
    $res->closeCursor();
    unset($res);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$ips = (new Usuario())->getEmpresa();

?>
<div class="contenedor bg-light" id="areaImprimir">
    <div class="px-2 " style="width:100% !important;margin: 0 auto;">
        <table class="table-bordered bg-light mt-3" style="width:100% !important; font-size: 80%;">
            <tr>
                <td colspan="6" style="text-align:center"><?php echo $ips['nombre']; ?></td>
            </tr>
            <tr>
                <td colspan="6" style="text-align:center"><?php echo $ips['nit'] . '-' . $ips['dv']; ?></td>
            </tr>
            <tr>
                <td colspan="6" style="text-align:center"><?php echo 'RELACION DE DESCUENTOS Y RETENCIONES RESUMEN '; ?></td>
            </tr>
        </table>
        </br>
        </br>
        <table class="table-bordered bg-light" style="width:100% !important; font-size: 80%;">
            <tr>
                <td>MUNICIPIO</td>
                <td style='text-align: left;'><?php echo $tercero['nom_tercero']; ?></td>
            </tr>
            <tr>
                <td>NIT</td>
                <td style='text-align: left;'><?php echo $tercero['nit_tercero'] ?></td>
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
        <table class="table-bordered bg-light" style="width:100% !important; font-size: 80%;" border=1>
            <tr>
                <td>Tipo de retenci&oacute;n</td>
                <td>Cuenta</td>
                <td>Retenci&oacute;n aplicada</td>
                <td>Base</td>
                <td>Valor retenido</td>
                <td>Valor pago</td>
            </tr>
            <?php
            $total_base =   0;
            $total_ret = 0;
            $total_pago =  0;
            foreach ($causaciones as $rp) {
                // redodear valor al mil mas cercano
                $pago = round($rp['retencion'], -3);
                echo "<tr>
                    <td class='text-end'>" . $rp['tipo'] . "</td>
                    <td class='text'>" . $rp['cuenta'] . "</td>
                    <td class='text'>" . $rp['nombre_retencion'] . "</td>
                    <td class='text-end'>" . number_format($rp['base'], 2, ".", ",")  . "</td>
                    <td class='text-end'>" . number_format($rp['retencion'], 2, ".", ",")  . "</td>
                    <td class='text-end'>" . number_format($pago, 2, ".", ",")  . "</td>
                    </tr>";
                $total_base =   $total_base + $rp['base'];
                $total_ret = $total_ret + $rp['retencion'];
                $total_pago =  $total_pago + $pago;
            }
            echo "<tr>
            <td class='text-end' colspan='3'> Total</td>
            <td class='text-end'>" . number_format($total_base, 2, ".", ",")  . "</td>
            <td class='text-end'>" . number_format($total_ret, 2, ".", ",")  . "</td>
            <td class='text-end'>" . number_format($total_pago, 2, ".", ",")  . "</td>
            </tr>";

            ?>
        </table>
        &nbsp;
        &nbsp;
        &nbsp;

        <table class="table-bordered bg-light" style="width:100% !important; font-size: 80%;" border=1>
            <tr>
                <td>Tipo de retenci&oacute;n</td>
                <td>Cuenta</td>
                <td>Retenci&oacute;n aplicada</td>
                <td>Base</td>
                <td>Valor retenido</td>
                <td>Valor pago</td>
            </tr>
            <?php
            $total_base =   0;
            $total_ret = 0;
            $total_pago =  0;
            foreach ($sobretasa as $rp) {
                // redodear valor al mil mas cercano
                $pago = round($rp['retencion'], -3);
                echo "<tr>
                <td class='text-end'>" . $rp['tipo'] . "</td>
                <td class='text'>" . $rp['cuenta'] . "</td>
                <td class='text'>" . $rp['nombre_retencion'] . "</td>
                <td class='text-end'>" . number_format($rp['base'], 2, ".", ",") . "</td>
                <td class='text-end'>" . number_format($rp['retencion'], 2, ".", ",") . "</td>
                <td class='text-end'>" . number_format($pago, 2, ".", ",")  . "</td>
                </tr>";
                $total_base =   $total_base + $rp['base'];
                $total_ret = $total_ret + $rp['retencion'];
                $total_pago =  $total_pago + $pago;
            }
            echo "<tr>
                    <td class='text-end' colspan='3'> Total</td>
                    <td class='text-end'>" . number_format($total_base, 2, ".", ",")  . "</td>
                    <td class='text-end'>" . number_format($total_ret, 2, ".", ",")  . "</td>
                    <td class='text-end'>" . number_format($total_pago, 2, ".", ",")  . "</td>
                    </tr>";
            ?>
        </table>
        </br>
        </br>
        </br>

    </div>

</div>