<?php

use Config\Clases\Logs;

$id = $_POST['id'];
include '../../../../config/autoloader.php';
$response['status'] = 'error';
try {
    $pdo = \Config\Clases\Conexion::getConexion();
    $pdo->beginTransaction();

    $sql = "SELECT id_arqueo FROM tes_ids_arqueo WHERE id_causa = ?";
    $query = $pdo->prepare($sql);
    $query->bindParam(1, $id, PDO::PARAM_INT);
    $query->execute();
    $result = $query->fetchAll();

    $query = $pdo->prepare("DELETE FROM tes_causa_arqueo WHERE id_causa_arqueo = ?");
    $query->bindParam(1, $id);
    $query->execute();
    if ($query->rowCount() > 0) {
        $up = "UPDATE `fac_arqueo` SET `estado` = 2 WHERE `id_arqueo` = ?";
        $up = $pdo->prepare($up);
        foreach ($result as $row) {
            $id_arqueo = $row['id_arqueo'];
            $up->execute([$id_arqueo]);
            if ($up->rowCount() > 0) {
                Logs::guardaLog("UPDATE `fac_arqueo` SET `estado` = 2 WHERE `id_arqueo` = $id_arqueo");
            }
        }
        $consulta = "DELETE FROM tes_causa_arqueo WHERE id_causa_arqueo = $id";
        Logs::guardaLog($consulta);

        $pdo->commit();
        $response['status'] = 'ok';
        $response['id'] = $id;
    } else {
        $pdo->rollBack();
        $response['msg'] = $query->errorInfo()[2];
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['msg'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
echo json_encode($response);
