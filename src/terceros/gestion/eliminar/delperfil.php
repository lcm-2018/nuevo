<?php

use Config\Clases\Logs;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$id = $_POST['id'];

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $query = "DELETE FROM `ctt_perfil_tercero` WHERE `id_perfil` = ?";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $id);
    $query->execute();
    if ($query->rowCount() > 0) {
        $consulta = "DELETE FROM `ctt_perfil_tercero` WHERE `id_perfil` = $id";
        Logs::guardaLog($consulta);
        echo 'ok';
    } else {
        echo $query->errorInfo()[2];
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
