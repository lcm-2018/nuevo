<?php

use Config\Clases\Logs;

$_post = json_decode(file_get_contents('php://input'), true);
$id = $_post['id'];
$id_doc = $_post['id_doc'];
include '../../../../config/autoloader.php';
include_once '../../../financiero/consultas.php';
function pesos($valor)
{
    return '$ ' . number_format($valor, 2, '.', ',');
}
$response = [
    "value" => '0',
    "id" => $id,
    "acumulado" => '0'
];
try {
    $pdo = \Config\Clases\Conexion::getConexion();
    $query = $pdo->prepare("DELETE FROM ctb_causa_retencion WHERE id_causa_retencion = ?");
    $query->bindParam(1, $id);
    $query->execute();
    if ($query->rowCount() > 0) {
        $consulta = "DELETE FROM ctb_causa_retencion WHERE id_causa_retencion = $id";
        Logs::guardaLog($consulta);
        $acumulado = GetValoresCxP($id_doc, $pdo);
        $acumulado = pesos($acumulado['val_retencion']);
        $response[] = array("value" => 'ok', "id" => $id, "acumulado" => $acumulado);
        $pdo = null;
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

echo json_encode($response);
