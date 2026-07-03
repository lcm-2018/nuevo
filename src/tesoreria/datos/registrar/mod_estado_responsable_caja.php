<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
$id_detalle = isset($_POST['id']) ? $_POST['id'] : exit('Acceso no permitido');
$estado = $_POST['estado'] == '1' ? 0 : 1;
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha2 = $date->format('Y-m-d H:i:s');
include '../../../../config/autoloader.php';
use Config\Clases\Logs;
$response['status'] = 'error';
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $query = "UPDATE `tes_caja_respon` SET `estado` = ? WHERE (`id_caja_respon` = ?)";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $estado, PDO::PARAM_INT);
    $query->bindParam(2, $id_detalle, PDO::PARAM_INT);
    if (!($query->execute())) {
        echo $query->errorInfo()[2] . $query->queryString;
    } else {
        if ($query->rowCount() > 0) {
            $query = "UPDATE `tes_caja_respon` SET `id_user_act` = ?, `fecha_act` = ? WHERE (`id_caja_respon` = ?)";
            $query = $cmd->prepare($query);
            $query->bindParam(1, $iduser, PDO::PARAM_INT);
            $query->bindParam(2, $fecha2, PDO::PARAM_STR);
            $query->bindParam(3, $id_detalle, PDO::PARAM_INT);
            $query->execute();
            Logs::guardaLog("UPDATE `tes_caja_respon` SET `estado` = $estado, `id_user_act` = $iduser, `fecha_act` = '$fecha2' WHERE `id_caja_respon` = $id_detalle");
        }
    }
    $cmd = null;
} catch (PDOException $e) {
    $response['msg'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
echo json_encode($response);
