<?php

include '../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();
$_post = json_decode(file_get_contents('php://input') ?: '{}', true);
$id_doc = $_post['id'][0] ?? 0;
$estado = $_post['id'][1] ?? 0;
$sql = "SELECT sum(valor) as valor FROM ctb_causa_costos WHERE id_ctb_doc = :id_doc AND estado = :estado";
$stmt = $cmd->prepare($sql);
$stmt->execute([':id_doc' => $id_doc, ':estado' => $estado]);
$response = [];
while ($row = $stmt->fetch()) {
    $valor = $row['valor'] ?? 0;
    $response[] = array("valorcc" => $valor);
}
echo json_encode($response);
exit;
