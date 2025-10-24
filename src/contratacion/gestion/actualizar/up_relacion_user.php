<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

$user = isset($_POST['id_user']) ? $_POST['id_user'] : exit('Acción no permitida');
$id_usuario = $_POST['id_usuario'];
$id_relacion = $_POST['id_relacion'];

$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    if ($id_relacion == 0) {
        $sql = "INSERT INTO `ctt_relacion_user`
                    (`user1`,`user_rel`,`id_user_reg`,`fec_reg`)
                VALUES (?, ?, ?, ?)";
        $sql = $cmd->prepare($sql);
        $sql->bindParam(1, $user, PDO::PARAM_INT);
        $sql->bindParam(2, $id_usuario, PDO::PARAM_INT);
        $sql->bindParam(3, $iduser, PDO::PARAM_INT);
        $sql->bindValue(4, $date->format('Y-m-d H:i:s'));
        $sql->execute();
        if ($sql->rowCount() > 0) {
            echo 'ok';
        } else {
            echo $sql->errorInfo()[2];
        }
    } else {
        $sql = "UPDATE `ctt_relacion_user` SET `user_rel` = ? WHERE `id_relacion` = ?";
        $sql = $cmd->prepare($sql);
        $sql->bindParam(1, $id_usuario, PDO::PARAM_INT);
        $sql->bindParam(2, $id_relacion, PDO::PARAM_INT);
        $sql->execute();
        if ($sql->rowCount() > 0) {
            $sql = "UPDATE `ctt_relacion_user` SET  `id_user_act` = ?, `fec_act` = ? WHERE `id_relacion` = ?";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $iduser, PDO::PARAM_INT);
            $sql->bindValue(2, $date->format('Y-m-d H:i:s'));
            $sql->bindParam(3, $id_relacion, PDO::PARAM_INT);
            $sql->execute();
            if ($sql->rowCount() > 0) {
                echo 'ok';
            } else {
                echo $sql->errorInfo()[2];
            }
        } else {
            echo 'No se realizaron cambios.';
        }
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
