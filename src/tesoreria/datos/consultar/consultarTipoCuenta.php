<?php
session_start();
include '../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();
$_post = json_decode(file_get_contents('php://input') ?: '{}', true);

$cuenta = $_post['cuenta'] ?? '';
$sql = "SELECT tipo_dato FROM ctb_pgcp WHERE cuenta = :cuenta";
$stmt = $cmd->prepare($sql);
$stmt->execute([':cuenta' => $cuenta]);
$datos = $stmt ? $stmt->fetch() : null;
$tipo = $datos['tipo_dato'] ?? null;
$response[] = array("tipo" => $tipo);
echo json_encode($response);
exit;
