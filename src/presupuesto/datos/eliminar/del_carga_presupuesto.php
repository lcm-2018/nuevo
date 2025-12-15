<?php

use Config\Clases\Logs;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$id_cargue = isset($_POST['id']) ? $_POST['id'] : exit('Acceso no permitido');
$response['status'] = 'error';
$response['msg'] = '';

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    // Valido que el rubro no tenga cuentas asociadas
    $sql = "SELECT `cod_pptal`, `id_pto` FROM `pto_cargue` WHERE `id_cargue` = $id_cargue";
    $rs = $cmd->query($sql);
    $codigo = $rs->fetch();
    $cod_pptal = $codigo['cod_pptal'];
    $id_pto = $codigo['id_pto'];
    // consulta codigo asociado
    $sql = "SELECT `cod_pptal` FROM `pto_cargue` WHERE `cod_pptal` LIKE '$cod_pptal%' AND `id_pto` = $id_pto";
    $rs = $cmd->query($sql);
    $fil = $rs->rowCount();
    //Pendiente ajustar consulta para poder eliminar 
    $sql = "SELECT `id_rubro` FROM `pto_cdp_detalle` 
            WHERE (`id_rubro` = $id_cargue)
            UNION ALL
            SELECT `id_cargue` FROM `pto_mod_detalle` 
            WHERE (`id_cargue` = $id_cargue)";
    $rs = $cmd->query($sql);
    $fil2 = $rs->rowCount();
    if ($fil > 1) {
        $response['msg'] = 'No se puede eliminar el registro, tiene cuentas asociadas';
    } else if ($fil2 > 1) {
        $response['msg'] = 'El rubro ya fue utilizado en movimientos presupuestales';
    } else {
        $sql = "DELETE FROM `pto_cargue`  WHERE `id_cargue` = ?";
        $sql = $cmd->prepare($sql);
        $sql->bindParam(1, $id_cargue, PDO::PARAM_INT);
        $sql->execute();
        if ($sql->rowCount() > 0) {
            $consulta =  "DELETE FROM `pto_cargue`  WHERE `id_cargue` = $id_cargue";
            Logs::guardaLog($consulta);
            $response['status'] = 'ok';
            $response['msg'] = 'ok';
        } else {
            $response['msg'] = $sql->errorInfo()[2];
        }
    }
    $cmd = null;
} catch (PDOException $e) {
    $response['msg'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

echo json_encode($response);
