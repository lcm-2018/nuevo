<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
use Config\Clases\Logs;

$ingreso = isset($_POST['ingreso']) ? $_POST['ingreso'] : exit('Acción no permitida');
$id_doc = $_POST['id_doc'] == '0' ? NULL : $_POST['id_doc'];

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "UPDATE `far_orden_ingreso` SET `id_ctb_doc` = ? WHERE `id_ingreso` = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $id_doc);
    $sql->bindParam(2, $ingreso);
    $sql->execute();
    Logs::guardaLog("UPDATE `far_orden_ingreso` SET `id_ctb_doc` = $id_doc WHERE `id_ingreso` = $ingreso");
    echo 'ok';
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
