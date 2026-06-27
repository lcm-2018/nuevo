<?php
include '../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();
$data = file_get_contents("php://input");
// update ctb_libaux set estado='C' where id_ctb_doc=$data;
$sql = "UPDATE pto_mod SET estado=1 WHERE id_pto_mod = $data";
$res = $cmd->query($sql);
$response[] = array("value" => "ok");
echo json_encode($response);
exit;
