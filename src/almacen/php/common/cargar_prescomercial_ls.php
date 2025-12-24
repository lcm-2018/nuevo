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
    $sql = "SELECT id_prescom,nom_presentacion,IFNULL(cantidad,1) AS cantidad
            FROM far_presentacion_comercial
            WHERE nom_presentacion LIKE '%$term%'
            ORDER BY IF(id_prescom=0,0,CONCAT('1',nom_presentacion))";
    $rs = $cmd->query($sql);
    $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

foreach ($objs as $obj) {
    $data[] = [
        "id" => $obj['id_prescom'],
        "label" => $obj['nom_presentacion'],
        "cantidad" => $obj['cantidad'],
    ];
}

if (empty($data)) {
    $data[] = [
        "id" => '',
        "label" => 'No hay coincidencias...',
    ];
}
echo json_encode($data);
