<?php

use Config\Clases\Logs;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$idt = $_POST['id'];

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "DELETE FROM `tb_terceros`  WHERE `id_tercero_api` = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $idt, PDO::PARAM_INT);
    $sql->execute();
    if ($sql->rowCount() > 0) {
        $consulta = "DELETE FROM `tb_terceros`  WHERE `id_tercero_api` = $idt";
        Logs::guardaLog($consulta);
        echo '1';
    } else {
        echo $sql->errorInfo()[2];
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
