<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
$id = isset($_POST['id']) ? $_POST['id'] : exit('Acceso no disponible');
$response['status'] = 'error';

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "DELETE FROM `tes_caja_rubros` WHERE `id_caja_rubros` = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $id, PDO::PARAM_INT);
    $sql->execute();
    if ($sql->rowCount() > 0) {
        $response['status'] = 'ok';
    } else {
        $response['msg'] = $sql->errorInfo()[2];
    }
    $cmd = null;
} catch (PDOException $e) {
    $response['msg'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
echo json_encode($response);
