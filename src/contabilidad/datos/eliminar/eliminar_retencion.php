<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$id = isset($_POST['id']) ? base64_decode($_POST['id']) : exit('Acceso no disponible');

$cmd = \Config\Clases\Conexion::getConexion();

try {
    $query = "DELETE FROM `ctb_retenciones` WHERE `id_retencion` = ?";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $id);
    $query->execute();
    if ($query->rowCount() > 0) {
        include '../../../financiero/reg_logs.php';
        $ruta = '../../../log';
        $consulta = "DELETE FROM `ctb_retenciones` WHERE `id_retencion` = $id";
        RegistraLogs($ruta, $consulta);
        echo 'ok';
    } else {
        echo $query->errorInfo()[2];
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
