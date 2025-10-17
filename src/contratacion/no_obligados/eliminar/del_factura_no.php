<?php

use Config\Clases\Logs;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

$id = isset($_POST['id']) ?  $_POST['id'] : exit('Acción no permitida');
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "DELETE FROM `ctt_fact_noobligado`  WHERE `id_facturano`  = ?";
    $consulta = "DELETE FROM `ctt_fact_noobligado`  WHERE `id_facturano`  = $id";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $id, PDO::PARAM_INT);
    $sql->execute();
    if (!($sql->rowCount() > 0)) {
        echo $sql->errorInfo()[2];
    } else {
        Logs::guardaLog($consulta);
        echo 'ok';
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
