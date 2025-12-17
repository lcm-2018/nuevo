<?php

use Config\Clases\Logs;

$_post = json_decode(file_get_contents('php://input'), true);
$id = $_post['id'];

try {
    $pdo = \Config\Clases\Conexion::getConexion();
    $query = "DELETE FROM `tes_caja_const` WHERE `id_caja_const` = ?";
    $query = $pdo->prepare($query);
    $query->bindParam(1, $id);
    $query->execute();
    if ($query->rowCount() > 0) {
        $consulta = "DELETE FROM `tes_caja_const` WHERE `id_caja_const` = $id";
        Logs::guardaLog($consulta);
        $response['status'] = 'ok';
    } else {
        $response['msg'] = 'Error: ' . $query->errorInfo()[2];
    }
    $pdo = null;
} catch (PDOException $e) {
    $response['msg'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

echo json_encode($response);
