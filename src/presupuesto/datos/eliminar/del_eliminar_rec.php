<?php

use Config\Clases\Logs;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
if (intval($_SESSION['rol']) !== 1) {
    echo 'Acceso denegado: solo el administrador puede eliminar recaudos.';
    exit();
}

$data = intval(trim((string)file_get_contents("php://input")));
if ($data <= 0) {
    echo 'Identificador de recaudo no válido';
    exit();
}

include '../../../../config/autoloader.php';
include '../../../financiero/consultas.php';

$cmd = \Config\Clases\Conexion::getConexion();

try {
    $fecha_cierre = fechaCierre($_SESSION['vigencia'], 54, $cmd);
    $stmtChk = $cmd->prepare("SELECT `fecha`, `estado` FROM `pto_rec` WHERE `id_pto_rec` = :id");
    $stmtChk->bindValue(':id', $data, PDO::PARAM_INT);
    $stmtChk->execute();
    $rowRec = $stmtChk->fetch(PDO::FETCH_ASSOC);
    $stmtChk->closeCursor();
    unset($stmtChk);

    if (!$rowRec) {
        echo 'No se encontró el recaudo especificado';
        exit();
    }
    if (intval($rowRec['estado']) === 0) {
        echo 'No se puede eliminar un recaudo anulado';
        exit();
    }
    $fechaDoc = date('Y-m-d', strtotime($rowRec['fecha']));
    if ($fechaDoc <= $fecha_cierre) {
        echo 'No se puede eliminar un documento en período cerrado (cierre: ' . $fecha_cierre . ')';
        exit();
    }

    $query = "DELETE FROM `pto_rec` WHERE `id_pto_rec` = ?";
    $stmt = $cmd->prepare($query);
    $stmt->bindParam(1, $data, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $sql = "DELETE FROM `pto_rec` WHERE `id_pto_rec` = $data";
        Logs::guardaLog($sql);
        echo "ok";
    } else {
        echo $stmt->errorInfo()[2] ?: 'No se pudo eliminar el recaudo';
    }
    $stmt->closeCursor();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
