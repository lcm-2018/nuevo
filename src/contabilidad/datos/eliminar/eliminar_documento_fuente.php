<?php

use Config\Clases\Logs;

$_post = json_decode(file_get_contents('php://input'), true);
include '../../../../config/autoloader.php';
$id = $_post['id'];
$pdo = \Config\Clases\Conexion::getConexion();

$response['value'] = 'error';
// consulto si el id de la cuenta fue utilizado en seg_fin_chequera_cont
try {
    $query = $pdo->prepare("DELETE FROM `ctb_fuente` WHERE `id_doc_fuente` = ? ");
    $query->bindParam(1, $id);
    $query->execute();
    if ($query->rowCount() > 0) {
        $consulta = "DELETE FROM `ctb_fuente` WHERE `id_doc_fuente` = $id";
        Logs::guardaLog($consulta);
        $response['value'] = 'ok';
        $response['msg'] = 'Cuenta eliminada correctamente';
    } else {
        $response['msg'] = $query->errorInfo()[2];
    }
    $cmd = null;
} catch (PDOException $e) {
    $response['msg'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

echo json_encode($response);
