<?php


session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include_once '../../../../config/autoloader.php';

use Config\Clases\Logs;

$id = $_POST['id'];

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "DELETE FROM `ctt_adquisiciones`  WHERE `id_adquisicion` = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $id, PDO::PARAM_INT);
    $sql->execute();
    if ($sql->rowCount() > 0) {
        $consulta = "DELETE FROM `ctt_productos_adq` WHERE `id_adquisicion` = $id";
        Logs::guardaLog($consulta);
        echo '1';
    } else {
        echo $sql->errorInfo()[2];
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
