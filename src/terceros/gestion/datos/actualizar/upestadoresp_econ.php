<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include '../../../../../config/autoloader.php';
use Config\Clases\Logs;

$id_respxtercero = isset($_POST['idt']) ? $_POST['idt'] : 0;
$estado = isset($_POST['e']) ? $_POST['e'] : -1;

if ($id_respxtercero > 0 && $estado != -1) {
    try {
        $cmd = \Config\Clases\Conexion::getConexion();
        $sql = "UPDATE ctt_resposabilidad_terceros SET estado = ? WHERE id_respxtercero = ?";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([$estado, $id_respxtercero]);
        if ($stmt->rowCount() > 0 || $stmt->errorCode() == '00000') {
            if ($stmt->rowCount() > 0) {
                Logs::guardaLog("UPDATE ctt_resposabilidad_terceros SET estado = $estado WHERE id_respxtercero = $id_respxtercero");
            }
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
