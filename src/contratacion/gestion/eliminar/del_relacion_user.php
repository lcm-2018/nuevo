<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Config\Clases\Conexion;
use Config\Clases\Logs;

$id = isset($_POST['id']) ? $_POST['id'] : exit('Acción no permitida');

try {
    $cmd = Conexion::getConexion();
    $sql = "DELETE FROM `ctt_relacion_user`  WHERE `id_relacion` = ?";
    $consulta = "DELETE FROM `ctt_relacion_user`  WHERE `id_relacion` = $id";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $id, PDO::PARAM_INT);
    $sql->execute();
    if ($sql->rowCount() > 0) {
        Logs::guardaLog($consulta);
        echo '1';
    } else {
        echo $sql->errorInfo()[2];
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
