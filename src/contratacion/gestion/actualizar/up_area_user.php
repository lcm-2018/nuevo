<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';
use Config\Clases\Logs;

$user = isset($_POST['id_user']) ? $_POST['id_user'] : exit('Acción no permitida');
$id_area = $_POST['slcAreaUser'];
$id_resp = $_POST['id_resp'];

$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    if ($id_resp == 0) {
        $sql = "INSERT INTO `ctt_area_user`
                    (`id_resp`, `id_user`, `id_area`, `id_user_reg`, `fec_reg`)
                VALUES (?, ?, ?, ?, ?)";
        $sql = $cmd->prepare($sql);
        $sql->bindParam(1, $id_resp, PDO::PARAM_INT);
        $sql->bindParam(2, $user, PDO::PARAM_INT);
        $sql->bindParam(3, $id_area, PDO::PARAM_INT);
        $sql->bindParam(4, $iduser, PDO::PARAM_INT);
        $sql->bindValue(5, $date->format('Y-m-d H:i:s'));
        $sql->execute();
        if ($sql->rowCount() > 0) {
            Logs::guardaLog("INSERT INTO `ctt_area_user` (`id_resp`, `id_user`, `id_area`, `id_user_reg`, `fec_reg`) VALUES ($id_resp, $user, $id_area, $iduser, '{$date->format('Y-m-d H:i:s')}')");
            echo 'ok';
        } else {
            echo $sql->errorInfo()[2];
        }
    } else {
        $sql = "UPDATE `ctt_area_user` SET `id_area` = ? WHERE `id_resp` = ?";
        $sql = $cmd->prepare($sql);
        $sql->bindParam(1, $id_area, PDO::PARAM_INT);
        $sql->bindParam(2, $id_resp, PDO::PARAM_INT);
        $sql->execute();
        if ($sql->rowCount() > 0) {
            Logs::guardaLog("UPDATE `ctt_area_user` SET `id_area` = $id_area WHERE `id_resp` = $id_resp");
            $sql = "UPDATE `ctt_area_user` SET  `id_user_act` = ?, `fec_act` = ? WHERE `id_resp` = ?";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $iduser, PDO::PARAM_INT);
            $sql->bindValue(2, $date->format('Y-m-d H:i:s'));
            $sql->bindParam(3, $id_resp, PDO::PARAM_INT);
            $sql->execute();
            if ($sql->rowCount() > 0) {
                Logs::guardaLog("UPDATE `ctt_area_user` SET  `id_user_act` = $iduser, `fec_act` = '{$date->format('Y-m-d H:i:s')}' WHERE `id_resp` = $id_resp");
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
