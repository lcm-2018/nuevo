<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
$id_chequera = isset($_POST['id_chequera']) ? $_POST['id_chequera'] : exit('Acceso no disponible');
$fecha = $_POST['fecha'];
$banco = $_POST['banco'];
$cuentas = $_POST['cuentas'];
$num_chequera = $_POST['num_chequera'];
$inicial = $_POST['inicial'];
$maximo = $_POST['maximo'];
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha2 = $date->format('Y-m-d H:i:s');
include '../../../../config/autoloader.php';
use Config\Clases\Logs;
$response['status'] = 'error';
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    if ($id_chequera == 0) {
        $query = "INSERT INTO `fin_chequeras`
                    (`id_cuenta`,`numero`,`fecha`,`inicial`,`maximo`,`id_user_reg`,`fec_reg`)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $query = $cmd->prepare($query);
        $query->bindParam(1, $cuentas, PDO::PARAM_INT);
        $query->bindParam(2, $num_chequera, PDO::PARAM_STR);
        $query->bindParam(3, $fecha, PDO::PARAM_STR);
        $query->bindParam(4, $inicial, PDO::PARAM_INT);
        $query->bindParam(5, $maximo, PDO::PARAM_STR);
        $query->bindParam(6, $iduser, PDO::PARAM_INT);
        $query->bindParam(7, $fecha2);
        $query->execute();
        if ($cmd->lastInsertId() > 0) {
            Logs::guardaLog("INSERT INTO `fin_chequeras` (`id_cuenta`,`numero`,`fecha`,`inicial`,`maximo`,`id_user_reg`,`fec_reg`) VALUES ($cuentas, '$num_chequera', '$fecha', $inicial, '$maximo', $iduser, '$fecha2')");
            $response['status'] = 'ok';
        } else {
            $response['msg'] = $query->errorInfo()[2];
        }
    } else {
        $query = "UPDATE `fin_chequeras`
                    SET `id_cuenta` = ?, `numero` = ?, `fecha` = ?, `inicial` = ?, `maximo` = ?
                WHERE `id_chequera` = ?";
        $query = $cmd->prepare($query);
        $query->bindParam(1, $cuentas, PDO::PARAM_INT);
        $query->bindParam(2, $num_chequera, PDO::PARAM_STR);
        $query->bindParam(3, $fecha, PDO::PARAM_STR);
        $query->bindParam(4, $inicial, PDO::PARAM_INT);
        $query->bindParam(5, $maximo, PDO::PARAM_STR);
        $query->bindParam(6, $id_chequera, PDO::PARAM_INT);
        if (!($query->execute())) {
            $response['msg'] = $query->errorInfo()[2];
        } else {
            if ($query->rowCount() > 0) {
                $query = "UPDATE `fin_chequeras`
                            SET `fec_act` = ?, `id_user_act` = ?
                        WHERE `id_chequera` = ?";
                $query = $cmd->prepare($query);
                $query->bindParam(1, $fecha2, PDO::PARAM_STR);
                $query->bindParam(2, $iduser, PDO::PARAM_INT);
                $query->bindParam(3, $id_chequera, PDO::PARAM_INT);
                $query->execute();
                Logs::guardaLog("UPDATE `fin_chequeras` SET `id_cuenta` = $cuentas, `numero` = '$num_chequera', `fecha` = '$fecha', `inicial` = $inicial, `maximo` = '$maximo', `fec_act` = '$fecha2', `id_user_act` = $iduser WHERE `id_chequera` = $id_chequera");
                $response['status'] = 'ok';
            } else {
                $response['msg'] = 'No se realizaron cambios';
            }
        }
    }
    $cmd = null;
} catch (PDOException $e) {
    $response['msg'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
echo json_encode($response);
