<?php

use Config\Clases\Logs;

$data = file_get_contents("php://input");
include '../../../../config/autoloader.php';
// Inicio conexion a la base de datos
try {
    $cmd = \Config\Clases\Conexion::getConexion();
} catch (Exception $e) {
    die("No se pudo conectar: " . $e->getMessage());
}
// Inicio transaccion 
try {
    $query = $cmd->prepare("DELETE FROM `pto_crp` WHERE `id_pto_crp` = ?");
    $query->bindParam(1, $data);
    $query->execute();
    if ($query->rowCount() > 0) {
        $consulta = "DELETE FROM `pto_crp` WHERE `id_pto_crp` = $data";
        Logs::guardaLog($consulta);
        echo 'ok';
    } else {
        echo 'error:' . $query->errorInfo()[2];
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
