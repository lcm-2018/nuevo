<?php

include '../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();
$_post = json_decode(file_get_contents('php://input') ?: '{}', true);
$id_doc = $_post['id'][0] ?? 0;
$sql = "SELECT sum(valor) as valor FROM tes_detalle_pago WHERE id_ctb_doc = :id_doc";
$stmt = $cmd->prepare($sql);
$stmt->execute([':id_doc' => $id_doc]);
$response = [];
while ($row = $stmt->fetch()) {
    $valor = $row['valor'] ?? 0;
    $response[] = array("valor_pag" => $valor);
}
echo json_encode($response);
exit;
