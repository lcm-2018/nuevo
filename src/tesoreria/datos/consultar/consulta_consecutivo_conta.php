<?php
// Busca consecutivos del tipo de documento recibido para sugerir un numero 
include '../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();
$_post = json_decode(file_get_contents('php://input'), true);

$fecha = $_post['fecha'] ?? date('Y-m-d');
$documento = $_post['documento'] ?? '';

$sql = "SELECT max(id_manu) as id_manu from ctb_doc WHERE CAST(fecha AS DATE) <= :fecha AND tipo_doc = :documento";
$stmt = $cmd->prepare($sql);
$stmt->execute([':fecha' => $fecha, ':documento' => $documento]);
$row = $stmt->fetch();
$id_manu = (int)($row['id_manu'] ?? 0);

// Buscar espacios libre hacia adelante
$stmtCheck = $cmd->prepare("SELECT COUNT(*) as total from ctb_doc WHERE id_manu = :id_manu AND tipo_doc = :documento");
$i = 1;
do {
    $id_manu++;
    $stmtCheck->execute([':id_manu' => $id_manu, ':documento' => $documento]);
    $num = (int)$stmtCheck->fetchColumn();
    if ($num === 0) {
        $i = 2;
    }
} while ($i < 2);

$response[] = array("numero" => $id_manu);
echo json_encode($response);
exit;
