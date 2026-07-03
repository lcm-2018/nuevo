<?php

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
use Config\Clases\Logs;
$id_cdp = isset($_POST['id']) ? $_POST['id'] : exit('Acceso no disponible');
$id_user = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$estado = 1;

$cmd = \Config\Clases\Conexion::getConexion();


try {
    $sql = "UPDATE `pto_rad` SET `estado` = ? WHERE `id_pto_rad` = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $estado, PDO::PARAM_INT);
    $sql->bindParam(2, $id_cdp, PDO::PARAM_INT);
    if (!($sql->execute())) {
        echo $sql->errorInfo()[2];
        exit();
    } else {
        if ($sql->rowCount() > 0) {
            Logs::guardaLog("UPDATE `pto_rad` SET `estado` = $estado WHERE `id_pto_rad` = $id_cdp");
            $sql = "UPDATE `pto_rad` SET `id_user_act` = ?, `fecha_act` = ? WHERE `id_pto_rad` = ?";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $id_user, PDO::PARAM_STR);
            $fecha = $date->format('Y-m-d H:i:s');
            $sql->bindValue(2, $fecha);
            $sql->bindParam(3, $id_cdp, PDO::PARAM_INT);
            $sql->execute();
            if ($sql->rowCount() > 0) {
                Logs::guardaLog("UPDATE `pto_rad` SET `id_user_act` = $id_user, `fecha_act` = '$fecha' WHERE `id_pto_rad` = $id_cdp");
            }
            echo 'ok';
        } else {
            echo 'No se registró ningún nuevo dato';
        }
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
