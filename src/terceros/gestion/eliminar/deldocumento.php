<?php

use Config\Clases\Logs;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$id = $_POST['id'];

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    $sql = "SELECT
                    `ruta_doc`,`nombre_doc`
                FROM `ctt_documentos`
                WHERE `id_soportester` = $id";
    $rs = $cmd->query($sql);
    $pdf = $rs->fetch(PDO::FETCH_ASSOC);

    $query = "DELETE FROM `ctt_documentos` WHERE `id_soportester` = ?";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $id);
    $query->execute();
    if ($query->rowCount() > 0) {
        $consulta = "DELETE FROM `ctt_documentos` WHERE `id_soportester` = $id";
        Logs::guardaLog($consulta);
        if (!empty($pdf)) {
            $filePath = $pdf['ruta_doc'] . $pdf['nombre_doc'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        echo 'ok';
    } else {
        echo $query->errorInfo()[2];
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
