<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

use Config\Clases\Logs;

$iduser = $_SESSION['id_user'];

$response = [
    'status' => 'error',
    'msg' => 'Acción no permitida'
];

$id_ctb_doc = isset($_POST['id_ctb_doc']) ? intval($_POST['id_ctb_doc']) : 0;
$id_tercero = isset($_POST['id_tercero']) ? intval($_POST['id_tercero']) : 0;
$id_tercero_ant = isset($_POST['id_tercero_ant']) ? intval($_POST['id_tercero_ant']) : 0;
$motivo_edita = isset($_POST['motivo_edita']) ? trim($_POST['motivo_edita']) : '';

if ($id_ctb_doc <= 0) {
    $response['msg'] = 'ID de documento no válido';
    echo json_encode($response);
    exit();
}

if ($id_tercero <= 0) {
    $response['msg'] = 'Debe seleccionar un tercero válido';
    echo json_encode($response);
    exit();
}

if ($motivo_edita === '') {
    $response['msg'] = 'El motivo de modificación es obligatorio';
    echo json_encode($response);
    exit();
}

$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha2 = $date->format('Y-m-d H:i:s');

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $query = "UPDATE `ctb_doc`
              SET `id_tercero` = ?, `motivo_edita` = ?, `id_user_act` = ?, `fecha_act` = ?
              WHERE `id_ctb_doc` = ?";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $id_tercero, PDO::PARAM_INT);
    $query->bindParam(2, $motivo_edita, PDO::PARAM_STR);
    $query->bindParam(3, $iduser, PDO::PARAM_INT);
    $query->bindParam(4, $fecha2, PDO::PARAM_STR);
    $query->bindParam(5, $id_ctb_doc, PDO::PARAM_INT);

    if ($query->execute()) {
        $motivo_escapado = addslashes($motivo_edita);
        $logSql = "UPDATE `ctb_doc` SET `id_tercero` = $id_tercero, `motivo_edita` = '$motivo_escapado', `id_user_act` = $iduser, `fecha_act` = '$fecha2' WHERE `id_ctb_doc` = $id_ctb_doc /* Tercero anterior: $id_tercero_ant */";
        Logs::guardaLog($logSql);

        $response['status'] = 'ok';
        $response['msg'] = 'Tercero actualizado correctamente';
    } else {
        $response['msg'] = $query->errorInfo()[2] ?? 'Error al actualizar el documento';
    }
    $cmd = null;
} catch (PDOException $e) {
    $response['msg'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

echo json_encode($response);
