<?php

include '../../../../config/autoloader.php';
use Config\Clases\Logs;
$data = file_get_contents("php://input");
// Realizo conexion con la base de datos
try {
    $cmd = \Config\Clases\Conexion::getConexion();
} catch (Exception $e) {
    die("No se pudo conectar: " . $e->getMessage());
}
// Incio la transaccion
try {
    $query = $cmd->prepare("UPDATE tes_referencia SET estado=1 WHERE id_referencia=?");
    $query->bindParam(1, $data, PDO::PARAM_INT);
    $query->execute();
    if ($query->rowCount() > 0) {
        Logs::guardaLog("UPDATE tes_referencia SET estado=1 WHERE id_referencia=$data");
    }
    $response[] = array("value" => "ok");
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
echo json_encode($response);
$cmd = null;
