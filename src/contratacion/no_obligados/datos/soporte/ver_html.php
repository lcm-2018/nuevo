<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
$data = json_decode(file_get_contents('php://input'), true);
$id_soporte = isset($data['id']) ? $data['id'] : exit('Acción no permitida');
$reponse = [];

include_once '../../../../../config/autoloader.php';

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `id_soporte`, `id_factura_no`, `shash`
            FROM
                `seg_soporte_fno`
            WHERE `id_factura_no` = '$id_soporte' AND `tipo` = 1";
    $rs = $cmd->query($sql);
    $soporte = $rs->fetch();
    if ($soporte['id_soporte'] == '') {
        $response[] = array("value" => "Error", "msg" => json_encode('No se encontró el soporte solicitado'));
    } else {
        $response[] = array("value" => "ok", "msg" => json_encode($soporte['shash']));
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
echo json_encode($response);
