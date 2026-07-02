<?php

use Sabberworm\CSS\Value\Value;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
// Realiza la suma del valor total asignado a un CDP
include '../../../../config/autoloader.php';
$_post = json_decode(file_get_contents('php://input'), true);
$tipo = $_post['id'];
$response['status'] = "error";
$cmd = \Config\Clases\Conexion::getConexion();

$pref = '';
try {
    $siguiente = 0;
    if ($tipo == '3') {
        $sql = "SELECT `consecutivo`, `prefijo` FROM `nom_resoluciones` 
                WHERE `id_resol` = (SELECT MAX(`id_resol`) FROM `nom_resoluciones` WHERE `tipo` = 2)";
        $rs = $cmd->query($sql);
        $prefijo = $rs->fetch();
        if (!empty($prefijo)) {
            $siguiente = $prefijo['consecutivo'];
            $pref =  $prefijo['prefijo'];
        }
    }

    // Usar REPLACE + CAST para extraer correctamente la parte numérica del num_doc
    // (consistente con el cálculo en registrar_mvto_contable_doc_cxp.php)
    $sql = "SELECT MAX(CAST(REPLACE(`num_doc`, ?, '') AS UNSIGNED)) AS `max_num` FROM `ctb_factura` WHERE (`id_tipo_doc` = ?)";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $pref, PDO::PARAM_STR);
    $stmt->bindParam(2, $tipo, PDO::PARAM_INT);
    $stmt->execute();
    $datos = $stmt->fetch();

    $consecutivo = !empty($datos['max_num']) ? intval($datos['max_num']) + 1 : 1;
    $consecutivo = $siguiente >= $consecutivo ? $siguiente : $consecutivo;
    $response['status'] = 'ok';
    $response['consecutivo'] = $pref . $consecutivo;
    $response['msg'] = 'Consecutivo generado';
    $cmd = null;
} catch (PDOException $e) {
    $response['msg'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
echo json_encode($response);
