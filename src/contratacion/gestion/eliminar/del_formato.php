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
    $sql = "DELETE FROM `ctt_formatos_doc_rel`  WHERE `id_relacion` = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $id, PDO::PARAM_INT);
    $sql->execute();
    if ($sql->rowCount() > 0) {
        unlink('../../adquisiciones/soportes/' . $id . '.docx');
        $consulta = "DELETE FROM `ctt_formatos_doc_rel`  WHERE `id_relacion` = $id";
        Logs::guardaLog($consulta);
        echo 'ok';
    } else {
        echo $sql->errorInfo()[2];
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
