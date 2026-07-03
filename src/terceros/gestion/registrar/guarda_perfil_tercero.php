<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
use Config\Clases\Logs;
$id = isset($_POST['id_perfil']) ? $_POST['id_perfil'] : exit('Acción no permitida');
$descripcion = $_POST['txtPerfilTercero'];
$id_user = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    if ($id == 0) {
        $sql = "INSERT INTO `ctt_perfil_tercero`
                    (`descripcion`,`id_user_reg`,`fec_reg`)
                VALUES (?, ?, ?)";
        $sql = $cmd->prepare($sql);
        $sql->bindParam(1, $descripcion, PDO::PARAM_STR);
        $sql->bindParam(2, $id_user, PDO::PARAM_INT);
        $sql->bindValue(3, $date->format('Y-m-d H:i:s'));
        $sql->execute();
        if ($cmd->lastInsertId() > 0) {
            Logs::guardaLog("INSERT INTO `ctt_perfil_tercero` (`descripcion`,`id_user_reg`,`fec_reg`) VALUES ('$descripcion', $id_user, '" . $date->format('Y-m-d H:i:s') . "')");
            echo 'ok';
        } else {
            echo $sql->errorInfo()[2];
        }
    } else {
        $sql = "UPDATE `ctt_perfil_tercero` SET `descripcion` = ? WHERE `id_perfil` = ?";
        $sql = $cmd->prepare($sql);
        $sql->bindParam(1, $descripcion, PDO::PARAM_STR);
        $sql->bindParam(2, $id, PDO::PARAM_INT);
        $sql->execute();
        if ($sql->rowCount() > 0) {
            Logs::guardaLog("UPDATE `ctt_perfil_tercero` SET `descripcion` = '$descripcion' WHERE `id_perfil` = $id");
            echo 'ok';
        } else {
            echo 'No se actualizó ningún registro';
        }
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
