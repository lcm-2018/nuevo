<?php
session_start();
include '../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();
$_post = json_decode(file_get_contents('php://input'), true);
$search = $_post['search'][0];
if (isset($_POST['search'])) {
    $sql = "SELECT
            `pto_cargue`.`cod_pptal`
            , `pto_cargue`.`nom_rubro`
            , `pto_cargue`.`tipo_dato`
        FROM
            `pto_presupuestos`
            INNER JOIN `pto_cargue` 
                ON (`pto_presupuestos`.`id_pto` = `pto_cargue`.`id_pto_presupuestos`)
        WHERE (`pto_cargue`.`cod_pptal` LIKE '$search%'
            AND `pto_cargue`.`id_pto_presupuestos` =1
            AND `pto_cargue`.`vigencia` ={$_SESSION['vigencia']});";
    $rs = $cmd->query($sql);
    $datos = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    foreach ($datos as $key => $value) {
        $response[] = array("value" => $value['cod_pptal'], "label" => $value['cod_pptal'] . " - " . $value['nom_rubro'], "tipo" => $value['tipo_dato']);
    }
    echo json_encode($response);
}

exit;
