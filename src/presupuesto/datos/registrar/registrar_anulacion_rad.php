<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include '../../../../config/autoloader.php';
use Config\Clases\Logs;

$id = isset($_POST['id_pto_rad']) ? $_POST['id_pto_rad'] : exit('Acceso no disponible');

$fecha = $_POST['fecha'];
$motivo = $_POST['objeto'];
$id_user = $_SESSION['id_user'];
$estado = 0;
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "UPDATE `pto_rad`
                SET `estado`= ?, `id_user_anula` = ?, `fecha_anula` = ?, `concepto_anula` = ? 
            WHERE `id_pto_rad` = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $estado, PDO::PARAM_INT);
    $sql->bindParam(2, $id_user, PDO::PARAM_INT);
    $sql->bindParam(3, $fecha, PDO::PARAM_STR);
    $sql->bindParam(4, $motivo, PDO::PARAM_STR);
    $sql->bindParam(5, $id, PDO::PARAM_INT);
    $sql->execute();
    if ($sql->rowCount() > 0) {
        Logs::guardaLog("UPDATE `pto_rad` SET `estado`= $estado, `id_user_anula` = $id_user, `fecha_anula` = '$fecha', `concepto_anula` = '$motivo' WHERE `id_pto_rad` = $id");
        echo 'ok';
    } else {
        echo $sql->errorInfo()[2];
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
