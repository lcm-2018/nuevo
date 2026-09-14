<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
include '../../../../config/autoloader.php';
use Config\Clases\Logs;

$id_ctb_doc = isset($_POST['id_doc'])  ? $_POST['id_doc'] : exit('Acceso no disponible');
$id_detalle = isset($_POST['id_detalle'])  ? $_POST['id_detalle'] : 0;
$id_banco = $_POST['banco'];
$id_pto_cop = $_POST['id_pto_cop'];
$cuenta_banco = $_POST['cuentas'];
$forma_pago = $_POST['forma_pago_det'];
$documento = $_POST['documento'];
$valor_pag = str_replace(",", "", $_POST['valor_pag']);
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha2 = $date->format('Y-m-d H:i:s');

$id_fp = $_POST['id_fp'] ?? 0;

$response['status'] = 'error';
$cmd = \Config\Clases\Conexion::getConexion();
try {
    if ($id_fp > 0) {
        $query = "UPDATE `tes_detalle_pago` SET 
                    `id_tes_cuenta` = ?, 
                    `id_forma_pago` = ?, 
                    `documento` = ?, 
                    `valor` = ?, 
                    `id_user_act` = ?, 
                    `fecha_act` = ? 
                  WHERE `id_detalle_pago` = ?";
        $query = $cmd->prepare($query);
        $query->bindParam(1, $cuenta_banco, PDO::PARAM_INT);
        $query->bindParam(2, $forma_pago, PDO::PARAM_STR);
        $query->bindParam(3, $documento, PDO::PARAM_INT);
        $query->bindParam(4, $valor_pag, PDO::PARAM_STR);
        $query->bindParam(5, $iduser, PDO::PARAM_INT);
        $query->bindParam(6, $fecha2, PDO::PARAM_STR);
        $query->bindParam(7, $id_fp, PDO::PARAM_INT);
        $query->execute();
        if ($query->rowCount() > 0) {
            Logs::guardaLog("UPDATE `tes_detalle_pago` SET `id_tes_cuenta` = $cuenta_banco, `id_forma_pago` = '$forma_pago', `documento` = $documento, `valor` = '$valor_pag', `id_user_act` = $iduser, `fecha_act` = '$fecha2' WHERE `id_detalle_pago` = $id_fp");
            $response['status'] = 'ok';
        } else {
            $response['msg'] = $query->errorInfo()[2] ?? 'No se modificó ningún registro.';
        }
    } else {
        $query = "INSERT INTO `tes_detalle_pago`
                        (`id_ctb_doc`,`id_tes_cuenta`,`id_forma_pago`,`documento`,`valor`,`id_user_reg`,`fecha_reg`)
                    VALUES(?, ?, ?, ?, ?, ?, ?)";
        $query = $cmd->prepare($query);
        $query->bindParam(1, $id_ctb_doc, PDO::PARAM_INT);
        $query->bindParam(2, $cuenta_banco, PDO::PARAM_INT);
        $query->bindParam(3, $forma_pago, PDO::PARAM_STR);
        $query->bindParam(4, $documento, PDO::PARAM_INT);
        $query->bindParam(5, $valor_pag, PDO::PARAM_STR);
        $query->bindParam(6, $iduser, PDO::PARAM_INT);
        $query->bindParam(7, $fecha2, PDO::PARAM_STR);
        $query->execute();
        if ($cmd->lastInsertId() > 0) {
            Logs::guardaLog("INSERT INTO `tes_detalle_pago` (`id_ctb_doc`,`id_tes_cuenta`,`id_forma_pago`,`documento`,`valor`,`id_user_reg`,`fecha_reg`) VALUES($id_ctb_doc, $cuenta_banco, '$forma_pago', $documento, '$valor_pag', $iduser, '$fecha2')");
            $response['status'] = 'ok';
        } else {
            $response['msg'] = $query->errorInfo()[2];
        }
    }
    
    if ($response['status'] == 'ok') {
        $query = "SELECT SUM(`valor`) AS `valor` FROM `tes_detalle_pago` WHERE `id_ctb_doc` = $id_ctb_doc";
        $rs = $cmd->query($query);
        $valor = $rs->fetch();
        $response['valor'] = $valor['valor'];
    }
    $cmd = null;
} catch (PDOException $e) {
    $response['msg'] = $e->getMessage();
}

echo json_encode($response);
