<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include_once '../../../../config/autoloader.php';

$id_adq = isset($_POST['id_adq']) ? $_POST['id_adq'] : exit('Accion no permitida');
$id_orden = $_POST['id_orden'];
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "UPDATE `ctt_adquisiciones` SET
                `id_orden` = ? WHERE `id_adquisicion` = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $id_orden, PDO::PARAM_INT);
    $sql->bindParam(2, $id_adq, PDO::PARAM_INT);
    if (!($sql->execute())) {
        echo $sql->errorInfo()[2];
        exit();
    } else {
        if ($sql->rowCount() > 0) {
            $cmd =  \Config\Clases\Conexion::getConexion();
            $sql = "UPDATE `ctt_adquisiciones` SET  `id_user_act` = ?, `fec_act` = ? WHERE `id_adquisicion` = ?";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $iduser, PDO::PARAM_INT);
            $sql->bindValue(2, $date->format('Y-m-d H:i:s'));
            $sql->bindParam(3, $id_adq, PDO::PARAM_INT);
            $sql->execute();
            if ($sql->rowCount() > 0) {
                echo 'ok';
            } else {
                echo $sql->errorInfo()[2];
                exit();
            }
        } else {
            echo 'No se registró ningún nuevo dato';
            exit();
        }
    }
    $cmd = NULL;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
