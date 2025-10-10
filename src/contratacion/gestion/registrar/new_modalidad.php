<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include_once '../../../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;
use Config\Clases\Conexion;

$modalidad = isset($_POST['datos']) ? mb_strtoupper($_POST['datos']) : exit('Acción no permitida');
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($iduser);
$cmd = Conexion::getConexion();

try {
    
    $sql = "INSERT INTO ctt_modalidad (modalidad,id_user_reg,fec_reg) VALUES (?, ?, ?)";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $modalidad, PDO::PARAM_STR);
    $sql->bindParam(2, $iduser, PDO::PARAM_INT);
    $sql->bindValue(3, $date->format('Y-m-d H:i:s'));
    $sql->execute();
    if ($cmd->lastInsertId() > 0) {
        echo '1';
    } else {
        echo $sql->errorInfo()[2];
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
