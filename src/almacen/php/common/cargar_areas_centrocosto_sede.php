<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$titulo = isset($_POST['titulo']) ? $_POST['titulo'] : '';
$idcec = $_POST['id_cecos'] != '' ? $_POST['id_cecos'] : 0;
$idsede = $_POST['id_sede'] != '' ? $_POST['id_sede'] : 0;

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    echo '<option value="">' . $titulo . '</option>';
    $sql = "SELECT id_area,nom_area FROM far_centrocosto_area 
            WHERE id_area<>0 AND estado=1 AND id_centrocosto=$idcec AND id_sede=$idsede
            ORDER BY es_almacen DESC, nom_area";

    $rs = $cmd->query($sql);
    $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    foreach ($objs as $obj) {
        echo '<option value="' . $obj['id_area'] . '">' . $obj['nom_area'] . '</option>';
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
