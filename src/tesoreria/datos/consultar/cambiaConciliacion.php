<?php

include '../../../../config/autoloader.php';
session_start();
$_post = json_decode(file_get_contents('php://input'), true);
$id = $_post['id'];
$estado = $_post['estado'];
$mes = $_post['mes'];

$vigencia = $_SESSION['vigencia'];
$id_user = $_SESSION['id_user'];

$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha = $date->format('Y-m-d H:i:s');

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "UPDATE `tes_conciliacion`  SET `estado` = ?
            WHERE `mes` =  ? AND `vigencia` = ? AND `id_cuenta` = ?";
    $query = $cmd->prepare($sql);
    $query->bindParam(1, $estado, PDO::PARAM_INT);
    $query->bindParam(2, $mes, PDO::PARAM_STR);
    $query->bindParam(3, $vigencia, PDO::PARAM_INT);
    $query->bindParam(4, $id, PDO::PARAM_INT);
    $query->execute();
    if ($query->rowCount() > 0) {
        echo "ok";
    } else {
        echo "error: " . $cmd->errorInfo()[2];
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
