<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$id = isset($_POST['idt']) ? intval($_POST['idt']) : 0;
$e  = isset($_POST['e'])   ? intval($_POST['e'])   : 0;

if ($id < 1) {
    echo 'ID no válido';
    exit();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "UPDATE `ctt_documentos` SET `estado` = ? WHERE `id_soportester` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $e, PDO::PARAM_INT);
    $stmt->bindParam(2, $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo $e; // devuelve '1' (activo) o '0' (inactivo)
    } else {
        echo 'Sin cambios';
    }
} catch (PDOException $ex) {
    echo $ex->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $ex->getMessage();
}
