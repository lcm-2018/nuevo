<?php

include '../../../../config/autoloader.php';

use Config\Clases\Logs;

$_post = json_decode(file_get_contents('php://input'), true);
$id = $_post['id'];
try {
    $pdo = \Config\Clases\Conexion::getConexion();

    $query = $pdo->prepare("DELETE FROM tes_detalle_pago WHERE id_detalle_pago = ?");
    $query->bindParam(1, $id);
    $query->execute();
    if ($query->rowCount() > 0) {
        $consulta = "DELETE FROM tes_detalle_pago WHERE id_detalle_pago = $id";
        Logs::guardaLog($consulta);
        $response[] = array("value" => 'ok', "id" => $id);
    } else {
        print_r($query->errorInfo()[2]);
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
echo json_encode($response);
