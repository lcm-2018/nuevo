<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;
use Config\Clases\Conexion;
$idbs = isset($_POST['idBnSv']) ? $_POST['idBnSv'] : exit('Acción no permitida');
$idtbnsv = $_POST['slcTipoBnSv'];
$tbnsv = mb_strtoupper($_POST['txtBnSv']);
$iduser = $_SESSION['id_user'];
$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($iduser);
$cmd = Conexion::getConexion();
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    $sql = "UPDATE ctt_bien_servicio SET id_tipo_bn_sv = ?, bien_servicio = ? WHERE id_b_s = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $idtbnsv, PDO::PARAM_INT);
    $sql->bindParam(2, $tbnsv, PDO::PARAM_STR);
    $sql->bindParam(3, $idbs, PDO::PARAM_INT);
    $sql->execute();
    $cambio = $sql->rowCount();
    if (!($sql->execute())) {
        echo $sql->errorInfo()[2];
        exit();
    } else {
        if ($cambio > 0) {
            $cmd = \Config\Clases\Conexion::getConexion();
            
            $sql = "UPDATE ctt_bien_servicio SET  id_user_act = ? ,fec_act = ? WHERE id_b_s = ?";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $iduser, PDO::PARAM_INT);
            $sql->bindValue(2, $date->format('Y-m-d H:i:s'));
            $sql->bindParam(3, $idbs, PDO::PARAM_INT);
            $sql->execute();
            if ($sql->rowCount() > 0) {
                echo '1';
            } else {
                echo $sql->errorInfo()[2];
            }
        } else {
            echo 'No se registró ningún nuevo dato';
        }
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
