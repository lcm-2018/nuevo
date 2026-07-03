<?php

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
use Config\Clases\Logs;
$cmd = \Config\Clases\Conexion::getConexion();

$id_mod = file_get_contents("php://input");
$id_user = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$estado = 2;
$response['status'] = 'ok';
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "UPDATE `pto_mod` SET `estado` = ? WHERE `id_pto_mod` = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $estado, PDO::PARAM_INT);
    $sql->bindParam(2, $id_mod, PDO::PARAM_INT);
    if (!($sql->execute())) {
        $response['msg'] = $sql->errorInfo()[2];
        exit();
    } else {
        if ($sql->rowCount() > 0) {
            Logs::guardaLog("UPDATE `pto_mod` SET `estado` = $estado WHERE `id_pto_mod` = $id_mod");
            $sql = "UPDATE `pto_mod` SET `id_user_act` = ?, `fecha_act` = ? WHERE `id_pto_mod` = ?";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $id_user, PDO::PARAM_STR);
            $fecha = $date->format('Y-m-d H:i:s');
            $sql->bindValue(2, $fecha);
            $sql->bindParam(3, $id_mod, PDO::PARAM_INT);
            $sql->execute();
            if ($sql->rowCount() > 0) {
                Logs::guardaLog("UPDATE `pto_mod` SET `id_user_act` = $id_user, `fecha_act` = '$fecha' WHERE `id_pto_mod` = $id_mod");
            }
            $response['status'] = 'ok';
        } else {
            $response['status'] = 'ok';
            $response['msg'] = 'No se registró ningún nuevo dato';
        }
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
echo json_encode($response);
