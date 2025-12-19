<?php

use Config\Clases\Logs;

$_post = json_decode(file_get_contents('php://input'), true);
$id = $_post['id'];
include '../../../../config/autoloader.php';
$response['status'] = 'error';
try {
    $pdo = \Config\Clases\Conexion::getConexion();
    $query = "DELETE FROM `tes_referencia` WHERE `id_referencia` = ?";
    $query = $pdo->prepare($query);
    $query->bindParam(1, $id);
    $query->execute();
    if ($query->rowCount() > 0) {
        $consulta = "DELETE FROM `tes_referencia` WHERE `id_referencia` = $id";
        Logs::guardaLog($consulta);
        $response['status'] = 'ok';
    } else {
        $response['msg'] = 'No se eliminó ningún registro';
    }
} catch (PDOException $e) {
    $response['msg'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
echo json_encode($response);
