<?php

use Config\Clases\Logs;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
$id = isset($_POST['id']) ? $_POST['id'] : exit('Acceso no disponible');
include_once '../../../../config/autoloader.php';

$cmd = \Config\Clases\Conexion::getConexion();

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "DELETE FROM `ctt_orden_compra_detalle`  WHERE `id_detalle` = ?";
    $consulta = "DELETE FROM `ctt_orden_compra_detalle`  WHERE `id_detalle` = $id";
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
