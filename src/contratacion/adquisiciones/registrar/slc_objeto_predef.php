<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include_once '../../../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;
use Config\Clases\Conexion;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($_SESSION['id_user']);
$cmd = Conexion::getConexion();
$id = isset($_POST['id_bs']) ? $_POST['id_bs'] : exit('Acción no permitida');
try {

    $sql = "SELECT id_tipo_b_s, objeto_definido
            FROM
            tb_tipo_bien_servicio
            WHERE id_tipo_b_s= '$id'";
    $rs = $cmd->query($sql);
    $objeto_pred = $rs->fetch();
    echo $objeto_pred['objeto_definido'];
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
