<?php

use Config\Clases\Logs;

$_post = json_decode(file_get_contents('php://input'), true);
$id = $_post['id'];
include '../../../../config/autoloader.php';
$pdo = \Config\Clases\Conexion::getConexion();
$response['status'] = 'error';
// consulto si el id de la chequera fue utilizado en seg_fin_chequera_cont
try {
    $query = "SELECT
                `inicial`, `contador`
            FROM
                `fin_chequeras`
            WHERE (`id_chequera` = $id)";
    $rs = $pdo->query($query);
    $chequera = $rs->fetch(PDO::FETCH_ASSOC);

    // consulto cuantos registros genera la sentencia
    if ($chequera['contador'] > $chequera['inicial']) {
        $response['msg'] = 'La chequera tiene registros asociados, no se puede eliminar';
    } else {
        try {
            $query = "DELETE FROM `fin_chequeras` WHERE `id_chequera` = ?";
            $query = $pdo->prepare($query);
            $query->bindParam(1, $id);
            $query->execute();
            if ($query->rowCount() > 0) {
                $consulta = "DELETE FROM `fin_chequeras` WHERE `id_chequera` = $id";
                Logs::guardaLog($consulta);
                $response['status'] = 'ok';
            } else {
                $response['msg'] = $pdo->errorInfo()[2];
            }
            $cmd = null;
        } catch (PDOException $e) {
            $response['msg'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
        }
    }
} catch (PDOException $e) {
    $response['msg'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

echo json_encode($response);
