<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
use Config\Clases\Logs;
$id = isset($_POST['id_responsabilidad']) ? $_POST['id_responsabilidad'] : exit('Acción no permitida');
$codigo = $_POST['codigoRespEcono'];
$descripcion = $_POST['nombreRespEcono'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    if ($id == 0) {
        $sql = "INSERT INTO `tb_responsabilidades_tributarias` (`codigo`, `descripcion`, `fec_reg`) 
            VALUES (?, ?, ?)";
        $sql = $cmd->prepare($sql);
        $sql->bindParam(1, $codigo);
        $sql->bindParam(2, $descripcion);
        $sql->bindValue(3, $date->format('Y-m-d H:i:s'));
        $sql->execute();
        if ($cmd->lastInsertId() > 0) {
            Logs::guardaLog("INSERT INTO `tb_responsabilidades_tributarias` (`codigo`, `descripcion`, `fec_reg`) VALUES ('$codigo', '$descripcion', '" . $date->format('Y-m-d H:i:s') . "')");
            echo 'ok';
        } else {
            echo $sql->errorInfo()[2];
        }
    } else {
        $sql = "UPDATE `tb_responsabilidades_tributarias` SET `codigo` = ?, `descripcion` = ? WHERE `id_responsabilidad` = ?";
        $sql = $cmd->prepare($sql);
        $sql->bindParam(1, $codigo);
        $sql->bindParam(2, $descripcion);
        $sql->bindParam(3, $id);
        $sql->execute();
        if ($sql->rowCount() > 0) {
            Logs::guardaLog("UPDATE `tb_responsabilidades_tributarias` SET `codigo` = '$codigo', `descripcion` = '$descripcion' WHERE `id_responsabilidad` = $id");
            echo 'ok';
        } else {
            echo 'No se actualizó ningún registro';
        }
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
