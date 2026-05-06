<?php

use Config\Clases\Logs;

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include '../../../../../config/autoloader.php';

$id = isset($_POST['id']) ? $_POST['id'] : 0;

if ($id > 0) {
    try {
        $cmd = \Config\Clases\Conexion::getConexion();
        $sql = "DELETE FROM tb_terceros_dependientes WHERE id_dependiente = ?";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            $consulta = "DELETE FROM tb_terceros WHERE id_tercero = $id";
            Logs::guardaLog($consulta);
            echo '1';
        } else {
            echo 'No se pudo eliminar el registro o ya fue eliminado.';
        }
        $cmd = null;
    } catch (PDOException $e) {
        // If there's a foreign key constraint violation (e.g. 1451)
        if ($e->getCode() == 23000 || $e->getCode() == 1451) {
            echo 'No se puede eliminar el registro, está referenciado en otros procesos.';
        } else {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
    }
} else {
    echo 'No se recibió el ID del registro a eliminar.';
}
