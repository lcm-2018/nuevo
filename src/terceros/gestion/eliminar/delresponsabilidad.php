<?php

use Config\Clases\Logs;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$id = isset($_POST['id']) ? $_POST['id'] : exit('Acceso no disponible');

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "DELETE FROM `tb_responsabilidades_tributarias`  WHERE `id_responsabilidad` = ?";
    $consulta = "DELETE FROM `tb_responsabilidades_tributarias`  WHERE `id_responsabilidad` = $id";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $id, PDO::PARAM_INT);
    $sql->execute();
    if ($sql->rowCount() > 0) {
        Logs::guardaLog($consulta);
        echo 'ok';
    } else {
        echo $sql->errorInfo()[2];
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
