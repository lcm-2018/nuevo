<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include_once '../../../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;
use Config\Clases\Conexion;

$tbnsv = isset($_POST['slcTipoBnSv']) ? $_POST['slcTipoBnSv'] : exit('Acción no permitida');
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$bnsvs = $_REQUEST['txtBnSv'];
$tot_reg = 0;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($iduser);
$cmd = Conexion::getConexion();

foreach ($bnsvs as $bs) {
    if ($bs !== '') {
        $bnsv = mb_strtoupper($bs);
        try {
            
            $sql = "INSERT INTO ctt_bien_servicio (id_tipo_bn_sv, bien_servicio, id_user_reg, fec_reg) VALUES (?, ?, ?, ?)";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $tbnsv, PDO::PARAM_INT);
            $sql->bindParam(2, $bnsv, PDO::PARAM_STR);
            $sql->bindParam(3, $iduser, PDO::PARAM_INT);
            $sql->bindValue(4, $date->format('Y-m-d H:i:s'));
            $sql->execute();
            if ($cmd->lastInsertId() > 0) {
                $tot_reg++;
            } else {
                echo $sql->errorInfo()[2];
            }
        } catch (PDOException $e) {
            echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
        }
    }
}
if ($tot_reg > 0) {
    echo '1';
} else {
    echo 'No se pudo registrar el bien o servicio';
}
