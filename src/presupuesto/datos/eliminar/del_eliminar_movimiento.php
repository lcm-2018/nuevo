<?php

use Config\Clases\Logs;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
$_post = json_decode(file_get_contents('php://input'), true);
$dato = $_post['id'];
include '../../../../config/autoloader.php';

$cmd = \Config\Clases\Conexion::getConexion();

// Inicio transaccion 
try {
    $query = "DELETE FROM `pto_mod_detalle` WHERE `id_pto_mod_det` = ?";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $dato);
    $query->execute();
    if ($query->rowCount() > 0) {
        $consulta = "DELETE FROM `pto_mod_detalle` WHERE `id_pto_mod_det` = $dato";
        Logs::guardaLog($consulta);
        $response[] = array("value" => 'ok', "id" => $dato);
    } else {
        $response[] = array("value" => 'error', "id" => $dato);
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
echo json_encode($response);
