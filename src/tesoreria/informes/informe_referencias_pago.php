<?php

use Config\Clases\Conexion;

session_start();
set_time_limit(5600);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}



header("Content-type: application/vnd.ms-excel charset=utf-8");
header("Content-Disposition: attachment; filename=FORMATO_201101_F07_AGR.xls");
header("Pragma: no-cache");
header("Expires: 0");

include '../../../config/autoloader.php';

$id_referencia = $_POST['referencia'];

$cmd = \Config\Clases\Conexion::getConexion();
//
try {
    $sql = "SELECT
                `ctb_doc`.`id_ctb_doc`
                , `ctb_doc`.`id_tercero`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
                , `tb_tipos_documento`.`codigo_ne`
                , `ctb_doc`.`id_manu`
                , `tt`.`valor`
                , `cb`.`num_cuenta`
                , `cb`.`tipo_cuenta`
                , `b`.`cod_banco`
                , `b`.`nom_banco`
            FROM
                `ctb_doc`
                INNER JOIN `tb_terceros` 
                    ON (`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
                INNER JOIN `tb_tipos_documento` 
                    ON (`tb_terceros`.`tipo_doc` = `tb_tipos_documento`.`id_tipodoc`)
                INNER JOIN 
                    (SELECT
                        SUM(`credito`) AS `valor`
                        , `id_ctb_doc`
                    FROM
                        `ctb_libaux`
                    GROUP BY `id_ctb_doc`) AS `tt`
                        ON (`tt`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                LEFT JOIN `ctt_cuenta_bancaria` AS `cb`
                    ON (`cb`.`id_tercero` = `ctb_doc`.`id_tercero`)
                LEFT JOIN `tb_bancos` AS `b`
                    ON (`b`.`id_banco` = `cb`.`id_banco`)
            WHERE (`ctb_doc`.`id_ref` = $id_referencia AND `ctb_doc`.`estado` = 2 AND `ctb_doc`.`id_tipo_doc` = 4)";
    $res = $cmd->query($sql);
    $causaciones = $res->fetchAll();
    $res->closeCursor();
    unset($res);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

echo "\xEF\xBB\xBF";
?>
<table class="table-bordered bg-light" style="width:100% !important;" border=1>
    <tr>
        <td>No. de registro</td>
        <td>Identificación</td>
        <td>Nombre tercero</td>
        <td>Tipo de identificación</td>
        <td>Producto de Destino</td>
        <td>Nombre banco</td>
        <td>Tipo de producto</td>
        <td>Detalle tipo cuenta</td>
        <td>Código del banco</td>
        <td>Valor del traslado</td>
        <td>No. Egreso</td>
    </tr>
    <?php
    $reg = 1;
    foreach ($causaciones as $c) {
        $producto = $c['num_cuenta'];
        $banco = $c['nom_banco'];
        $tipo_cuenta = $c['tipo_cuenta'];
        $detalle_cuenta = $tipo_cuenta != '' ? ($tipo_cuenta == 'Ahorros' ? 'A' : 'C') : '';
        $cod_banco = $c['cod_banco'];
        $val = number_format($c['valor'], 2, ',', '');
        echo "<tr>
                <td class='text-start'>{$reg}</td>
                <td class='text-start'>{$c['nit_tercero']}</td>
                <td class='text-start'>{$c['nom_tercero']}</td>
                <td class='text-start'>{$c['codigo_ne']}</td>
                <td class='text-start' style=\"mso-number-format:'\@'\">{$producto}</td>
                <td class='text-start'>{$banco}</td>
                <td class='text-start'>{$tipo_cuenta}</td>
                <td class='text-start'>{$detalle_cuenta}</td>
                <td class='text-start' style=\"mso-number-format:'\@'\">{$cod_banco}</td>
                <td class='text-end'>{$val}</td>
                <td class='text-start'>{$c['id_manu']}</td>
            </tr>";
        $reg++;
    }
    ?>
</table>