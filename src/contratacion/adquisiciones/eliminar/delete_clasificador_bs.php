<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include '../../../../../config/autoloader.php';

use Config\Clases\Logs;

$id_clas = isset($_POST['id']) ? $_POST['id'] : exit('Acción no permitida');
$id_user = $_SESSION['id_user'];

$cmd = \Config\Clases\Conexion::getConexion();

try {
    $cmd->beginTransaction();

    // Obtener datos para el log antes de eliminar
    $sqlSelect = "SELECT 
                    `ctt_clasificador_bs`.`id_clas`,
                    `ctt_clasificador_bs`.`id_adq`,
                    `ctt_clasificador_bs`.`id_unspsc`,
                    `tb_codificacion_unspsc`.`codigo`,
                    `tb_codificacion_unspsc`.`descripcion`
                FROM 
                    `ctt_clasificador_bs`
                INNER JOIN `tb_codificacion_unspsc` 
                    ON (`ctt_clasificador_bs`.`id_unspsc` = `tb_codificacion_unspsc`.`id_codificacion`)
                WHERE `ctt_clasificador_bs`.`id_clas` = ?";

    $stmtSelect = $cmd->prepare($sqlSelect);
    $stmtSelect->bindParam(1, $id_clas, PDO::PARAM_INT);
    $stmtSelect->execute();
    $datosEliminados = $stmtSelect->fetch(PDO::FETCH_ASSOC);

    if (!$datosEliminados) {
        throw new Exception('Clasificador no encontrado');
    }

    // Eliminar el registro
    $sqlDelete = "DELETE FROM `ctt_clasificador_bs` WHERE `id_clas` = ?";
    $stmtDelete = $cmd->prepare($sqlDelete);
    $stmtDelete->bindParam(1, $id_clas, PDO::PARAM_INT);
    $stmtDelete->execute();

    // Guardar log de eliminación
    $logs = new Logs();
    $logs->guardaLog(
        $id_user,
        'ctt_clasificador_bs',
        $id_clas,
        'delete',
        json_encode($datosEliminados)
    );

    $cmd->commit();
    $cmd = null;

    echo json_encode(['status' => 'success', 'message' => 'Clasificador eliminado exitosamente']);
} catch (PDOException $e) {
    $cmd->rollBack();
    $cmd = null;
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Exception $e) {
    $cmd->rollBack();
    $cmd = null;
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
