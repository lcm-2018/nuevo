<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Config\Clases\Logs;
use Config\Clases\Conexion;

$id = isset($_POST['id']) ? $_POST['id'] : exit('Acción no permitida');

try {
    $cmd = Conexion::getConexion();
    $sql = "DELETE FROM tb_tipo_bien_servicio  WHERE id_tipo_b_s = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $id, PDO::PARAM_INT);
    $sql->execute();
    if ($sql->rowCount() > 0) {
        $consulta = "DELETE FROM tb_tipo_bien_servicio  WHERE id_tipo_b_s = $id";
        Logs::guardaLog($consulta);
        echo '1';
    } else {
        echo $sql->errorInfo()[2];
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
