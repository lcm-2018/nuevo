<?php

include '../../../../config/autoloader.php';
$data = file_get_contents("php://input");
$data = str_replace('|', ',', $data);
$data = str_replace("'", "", $data);
// Incio la transaccion
$response['status'] = 'error';
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $estado = 2;
    $query = "UPDATE `ctb_doc` SET `estado`= ? WHERE `id_ctb_doc` IN ($data)";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $estado, PDO::PARAM_INT);
    $query->execute();
    $response['status'] = 'ok';
    $cmd = null;
} catch (Exception $e) {
    $response['msg'] = $e->getMessage();
}
echo json_encode($response);
