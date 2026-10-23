<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$usuario = $_SESSION['id_user'];
$term = isset($_POST['term']) ? $_POST['term'] : exit('Acción no permitida');
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT id_tipo_ingreso,nom_tipo_ingreso 
            FROM far_orden_ingreso_tipo
            WHERE almacen=1 AND orden_compra=0";
    $rs = $cmd->query($sql);
    $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$data = [];
foreach ($objs as $obj) {
    $data[] = [
        "id" => $obj['id_tipo_ingreso'],
        "label" => $obj['nom_tipo_ingreso'],
    ];
}

if (empty($data)) {
    $data[] = [
        "id" => '',
        "label" => 'No hay coincidencias...',
    ];
}
echo json_encode($data);
