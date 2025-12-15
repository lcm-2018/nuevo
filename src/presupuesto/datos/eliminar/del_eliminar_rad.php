<?php

use Config\Clases\Logs;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
$data = file_get_contents("php://input");
include '../../../../config/autoloader.php';
// Inicio conexion a la base de datos
$cmd = \Config\Clases\Conexion::getConexion();

// Inicio transaccion 

try {
    $query = "DELETE FROM `pto_rad` WHERE `id_pto_rad` = ?";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $data);
    $query->execute();
    if ($query->rowCount() > 0) {
        $sql = "DELETE FROM `pto_rad` WHERE `id_pto_rad` = $data";
        Logs::guardaLog($sql);
        echo "ok";
    } else {
        echo $query->errorInfo()[2];
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
