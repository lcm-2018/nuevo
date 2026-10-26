<?php

include '../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();
$data = file_get_contents("php://input");
$response = [];
if (!empty($data)) {
    $sql = "SELECT sum(debito) as debito, sum(credito) as credito FROM ctb_libaux WHERE id_ctb_doc = :id_doc GROUP BY id_ctb_doc";
    $stmt = $cmd->prepare($sql);
    $stmt->execute([':id_doc' => $data]);
    while ($row = $stmt->fetch()) {
        $response[] = array("valordeb" => $row['debito'] ?? 0, "valorcrd" => $row['credito'] ?? 0);
    }
}
echo json_encode($response);
exit;
