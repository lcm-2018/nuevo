<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include '../../../../../config/autoloader.php';

$id = isset($_POST['id']) ? $_POST['id'] : exit('Acción no permitida');

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "DELETE FROM tb_terceros_deducciones WHERE id_deduccion = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo '1';
    } else {
        echo 'No se eliminó ningún registro';
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
