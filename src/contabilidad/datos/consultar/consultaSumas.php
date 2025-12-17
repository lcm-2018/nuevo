<?php

include '../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();
$data = file_get_contents("php://input");
$sql = "SELECT sum(debito) as debito, sum(credito) as credito FROM ctb_libaux WHERE id_ctb_doc=$data GROUP BY id_ctb_doc";
$res = $cmd->query($sql);
while ($row = $res->fetch_assoc()) {
    $response[] = array("valordeb" => $row['debito'], "valorcrd" => $row['credito']);
}
echo json_encode($response);
exit;
