<?php

use Config\Clases\Logs;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include_once '../../../../config/autoloader.php';

$id = isset($_POST['id']) ? $_POST['id'] : exit('Acción no permitida');
$id_compra = $_POST['id_compra'];

$id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null;
$date = new DateTime('now', new DateTimeZone('America/Bogota'));

$cmd = \Config\Clases\Conexion::getConexion();
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "DELETE FROM `ctt_contratos`  WHERE `id_contrato_compra` = ?";
    $consulta = "DELETE FROM `ctt_contratos`  WHERE `id_contrato_compra` = $id";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $id, PDO::PARAM_INT);
    $sql->execute();
    if ($sql->rowCount() > 0) {
        try {
            $estado = 6;

            $sql = "UPDATE `ctt_adquisiciones` SET `estado`= ?, `id_user_act` = ?, `fec_act` = ? WHERE `id_adquisicion` = ?";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $estado, PDO::PARAM_INT);
            $sql->bindParam(2, $id_user, PDO::PARAM_INT);
            $sql->bindValue(3, $date->format('Y-m-d H:i:s'));
            $sql->bindParam(4, $id_compra, PDO::PARAM_INT);
            $sql->execute();
            if (!($sql->rowCount() > 0)) {
                echo $sql->errorInfo()[2];
            } else {
                Logs::guardaLog($consulta);
                echo 'ok';
            }
            $cmd = null;
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
    } else {
        echo $sql->errorInfo()[2];
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
