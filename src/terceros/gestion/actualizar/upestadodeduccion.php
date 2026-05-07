<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include '../../../../config/autoloader.php';

$id_deduccion = isset($_POST['idt']) ? $_POST['idt'] : 0;
$estado = isset($_POST['e']) ? $_POST['e'] : -1;

if ($id_deduccion > 0 && $estado != -1) {
    try {
        $cmd = \Config\Clases\Conexion::getConexion();
        $sql = "UPDATE tb_terceros_deducciones SET estado = ? WHERE id_deduccion = ?";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([$estado, $id_deduccion]);
        if ($stmt->rowCount() > 0 || $stmt->errorCode() == '00000') {
            echo $estado;
        } else {
            echo 'No se actualizó el estado.';
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
} else {
    echo 'Datos inválidos';
}
