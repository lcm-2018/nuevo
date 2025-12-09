<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include '../../../../config/autoloader.php';

use Config\Clases\Logs;

$id_clas = isset($_POST['id_clas']) ? $_POST['id_clas'] : 0;
$id_adq = isset($_POST['id_adq']) ? $_POST['id_adq'] : exit('Acción no permitida');
$id_unspsc = isset($_POST['id_unspsc']) ? $_POST['id_unspsc'] : 0;
$id_user = $_SESSION['id_user'];

if ($id_unspsc == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Debe seleccionar un código UNSPSC']);
    exit();
}

$cmd = \Config\Clases\Conexion::getConexion();

try {
    $cmd->beginTransaction();

    if ($id_clas == 0) {
        // REGISTRO NUEVO
        $sql = "INSERT INTO `ctt_clasificador_bs` 
                    (`id_adq`, `id_unspsc`, `id_user_reg`, `fec_reg`) 
                VALUES (?, ?, ?, NOW())";

        $stmt = $cmd->prepare($sql);
        $stmt->bindParam(1, $id_adq, PDO::PARAM_INT);
        $stmt->bindParam(2, $id_unspsc, PDO::PARAM_INT);
        $stmt->bindParam(3, $id_user, PDO::PARAM_INT);
        $stmt->execute();

        $id_clas = $cmd->lastInsertId();

        // Log de inserción
        $logs = new Logs();
        $datos_log = [
            'id_clas' => $id_clas,
            'id_adq' => $id_adq,
            'id_unspsc' => $id_unspsc,
            'id_user_reg' => $id_user
        ];
        $logs->guardaLog(
            $id_user,
            'ctt_clasificador_bs',
            $id_clas,
            'insert',
            json_encode($datos_log)
        );

        $mensaje = 'Clasificador registrado exitosamente';
    } else {
        // ACTUALIZACIÓN
        // Obtener datos anteriores para el log
        $sqlOld = "SELECT * FROM `ctt_clasificador_bs` WHERE `id_clas` = ?";
        $stmtOld = $cmd->prepare($sqlOld);
        $stmtOld->bindParam(1, $id_clas, PDO::PARAM_INT);
        $stmtOld->execute();
        $datosAnteriores = $stmtOld->fetch(PDO::FETCH_ASSOC);

        $sql = "UPDATE `ctt_clasificador_bs` 
                SET `id_unspsc` = ?,
                    `id_user_act` = ?,
                    `fec_act` = NOW()
                WHERE `id_clas` = ?";

        $stmt = $cmd->prepare($sql);
        $stmt->bindParam(1, $id_unspsc, PDO::PARAM_INT);
        $stmt->bindParam(2, $id_user, PDO::PARAM_INT);
        $stmt->bindParam(3, $id_clas, PDO::PARAM_INT);
        $stmt->execute();

        // Log de actualización
        $logs = new Logs();
        $datos_log = [
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => [
                'id_clas' => $id_clas,
                'id_adq' => $id_adq,
                'id_unspsc' => $id_unspsc,
                'id_user_act' => $id_user
            ]
        ];
        $logs->guardaLog(
            $id_user,
            'ctt_clasificador_bs',
            $id_clas,
            'update',
            json_encode($datos_log)
        );

        $mensaje = 'Clasificador actualizado exitosamente';
    }

    $cmd->commit();
    $cmd = null;

    echo json_encode(['status' => 'success', 'message' => $mensaje]);
} catch (PDOException $e) {
    $cmd->rollBack();
    $cmd = null;
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
