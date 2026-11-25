<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
if (intval($_SESSION['rol']) !== 1) {
    echo 'Acceso denegado: solo el administrador puede anular recaudos.';
    exit();
}
include '../../../../config/autoloader.php';
include '../../../financiero/consultas.php';

use Config\Clases\Logs;

$id = isset($_POST['id_pto_rec']) ? intval($_POST['id_pto_rec']) : exit('Acceso no disponible');
if ($id <= 0) {
    echo 'Identificador de recaudo no válido';
    exit();
}

$fecha = trim((string)($_POST['fecha'] ?? ''));
$motivo = trim((string)($_POST['objeto'] ?? ''));
$id_user = intval($_SESSION['id_user']);
$estado = 0;

if ($fecha === '' || $motivo === '') {
    echo 'La fecha y el concepto de anulación son obligatorios';
    exit();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $fecha_cierre = fechaCierre($_SESSION['vigencia'], 54, $cmd);

    $stmtChk = $cmd->prepare("SELECT `fecha`, `estado` FROM `pto_rec` WHERE `id_pto_rec` = :id");
    $stmtChk->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtChk->execute();
    $rowRec = $stmtChk->fetch(PDO::FETCH_ASSOC);
    $stmtChk->closeCursor();
    unset($stmtChk);

    if (!$rowRec) {
        echo 'No se encontró el recaudo especificado';
        exit();
    }
    if (intval($rowRec['estado']) === 0) {
        echo 'El recaudo ya se encuentra anulado';
        exit();
    }
    $fechaDoc = date('Y-m-d', strtotime($rowRec['fecha']));
    if ($fechaDoc <= $fecha_cierre || $fecha <= $fecha_cierre) {
        echo 'No se puede anular un documento en período cerrado (cierre: ' . $fecha_cierre . ')';
        exit();
    }

    $sql = "UPDATE `pto_rec`
                SET `estado` = ?, `id_user_anula` = ?, `fecha_anula` = ?, `concepto_anula` = ?
            WHERE `id_pto_rec` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $estado, PDO::PARAM_INT);
    $stmt->bindParam(2, $id_user, PDO::PARAM_INT);
    $stmt->bindParam(3, $fecha, PDO::PARAM_STR);
    $stmt->bindParam(4, $motivo, PDO::PARAM_STR);
    $stmt->bindParam(5, $id, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $motivoLog = addslashes($motivo);
        Logs::guardaLog("UPDATE `pto_rec` SET `estado` = $estado, `id_user_anula` = $id_user, `fecha_anula` = '$fecha', `concepto_anula` = '$motivoLog' WHERE `id_pto_rec` = $id");
        echo 'ok';
    } else {
        echo $stmt->errorInfo()[2] ?: 'No se pudo anular el recaudo';
    }
    $stmt->closeCursor();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
