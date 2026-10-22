<?php
// Busca consecutivos del tipo de documento recibido para validar que no se duplique número
include '../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();
$_post = json_decode(file_get_contents('php://input'), true);

$doc = $_post['doc'] ?? '';
$tipo = $_post['tipo'] ?? '';
$sql = "SELECT COUNT(*) as total FROM pto_documento WHERE id_manu = :doc AND tipo_doc = :tipo";
$stmt = $cmd->prepare($sql);
$stmt->execute([':doc' => $doc, ':tipo' => $tipo]);
$num = (int)$stmt->fetchColumn();

$response[] = array("numero" => $num);
echo json_encode($response);
exit;
