<?php

use Config\Clases\Logs;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$idpto = isset($_POST['id']) ? $_POST['id'] : exit('Acceso denegado');

// Comprobar si en la tabla pto_cargue hay registros con el id_pto_presupuestos
$response['status'] = 'error';
$response['msg'] = '';
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `cod_pptal` FROM `pto_cargue`  WHERE `id_pto` = $idpto";
    $rs = $cmd->query($sql);
    $res = $rs->rowCount();
    if ($res > 0) {
        $response['msg'] = 'El presupuesto tiene rubros cargados';
    } else {
        try {
            $cmd = \Config\Clases\Conexion::getConexion();

            $sql = "DELETE FROM `pto_presupuestos`  WHERE `id_pto` = ?";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $idpto, PDO::PARAM_INT);
            $sql->execute();
            if ($sql->rowCount() > 0) {
                $response['status'] = 'ok';
                $consulta = "DELETE FROM `pto_presupuestos` WHERE `id_pto` = $idpto";
                Logs::guardaLog($consulta);
            } else {
                $response['msg'] = $sql->errorInfo()[2];
            }
            $cmd = null;
        } catch (PDOException $e) {
            $response['msg'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
        }
        $cmd = null;
    }
} catch (PDOException $e) {
    $response['msg'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

echo json_encode($response);
