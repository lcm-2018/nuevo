<?php

use Config\Clases\Logs;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
$data = isset($_POST['id']) ? ($_POST['id']) : exit('Acceso denegado');
include '../../../../config/autoloader.php';
// Inicio conexion a la base de datos
$cmd = \Config\Clases\Conexion::getConexion();

// Inicio transaccion 
try {
    $query = "DELETE FROM `pto_cdp_detalle` WHERE `id_pto_cdp_det` = ?";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $data);
    $query->execute();
    if ($query->rowCount() > 0) {
        $consulta = "DELETE FROM `pto_cdp_detalle` WHERE `id_pto_cdp_det` = $data";
        Logs::guardaLog($consulta);
        echo "ok";
    } else {
        echo $query->errorInfo()[2];
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
