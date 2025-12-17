<?php

use Config\Clases\Logs;

$_post = json_decode(file_get_contents('php://input'), true);
$id = $_post['id'];
include '../../../../config/autoloader.php';
try {
    $pdo = \Config\Clases\Conexion::getConexion();
    $query = $pdo->prepare("DELETE FROM `ctb_libaux` WHERE `id_ctb_libaux` = ?");
    $query->bindParam(1, $id);
    $query->execute();
    if ($query->rowCount() > 0) {
        $consulta = "DELETE FROM `ctb_libaux` WHERE `id_ctb_libaux` = $id";
        Logs::guardaLog($consulta);
    }
    $response[] = array("value" => 'ok', "id" => $id);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
echo json_encode($response);
