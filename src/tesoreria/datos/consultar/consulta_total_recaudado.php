<?php

include '../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();
$_post = json_decode(file_get_contents('php://input'), true);
$doc = $_post['doc'];
// Consulto el valor que el facturador ya tiene registrado en esa fecha
$sql = "SELECT SUM(`valor_arq`) as valor FROM `tes_causa_arqueo` WHERE id_ctb_doc =$doc;";
$res = $cmd->query($sql);
$registro = $res->fetch();
$valor =  $registro['valor'];
if ($valor > 0) {
    $response[] = array("total" => $valor);
} else {
    $response[] = array("total" => 0);
}
echo json_encode($response);
exit;
