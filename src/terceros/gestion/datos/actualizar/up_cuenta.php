<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}
include '../../../../../config/autoloader.php';
use Config\Clases\Logs;

$id_cta = $_POST['idCta'];
$id_tercero = $_POST['idTercero'];
$id_banco = $_POST['slcBancoE'];
$tp_cta = $_POST['slcTipoCtaE'];
$num_cta = $_POST['numCuentaE'];
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    if ($id_cta != '0') {
        $sql = "UPDATE `ctt_cuenta_bancaria` SET 
                    `id_banco` = ?,
                    `tipo_cuenta` = ?,
                    `num_cuenta` = ?,
                    `id_user_act` = ?,
                    `fec_act` = ?
                WHERE `id_cta` = ?";
        $sql = $cmd->prepare($sql);
        $sql->bindParam(1, $id_banco, PDO::PARAM_INT);
        $sql->bindParam(2, $tp_cta, PDO::PARAM_STR);
        $sql->bindParam(3, $num_cta, PDO::PARAM_STR);
        $sql->bindParam(4, $iduser, PDO::PARAM_INT);
        $sql->bindValue(5, $date->format('Y-m-d H:i:s'));
        $sql->bindParam(6, $id_cta, PDO::PARAM_INT);
        $sql->execute();
        
        if ($sql->rowCount() > 0) {
            Logs::guardaLog("UPDATE `ctt_cuenta_bancaria` SET `id_banco` = $id_banco, `tipo_cuenta` = '$tp_cta', `num_cuenta` = '$num_cta', `id_user_act` = $iduser, `fec_act` = '" . $date->format('Y-m-d H:i:s') . "' WHERE `id_cta` = $id_cta");
            echo 'ok';
        } else {
            echo 'No se registró ningún cambio o ocurrió un error: ' . $sql->errorInfo()[2];
        }
    } else {
        // If there was no existing account to edit, create one
        $sql = "INSERT INTO `ctt_cuenta_bancaria`
                    (`id_tercero`,`id_banco`,`tipo_cuenta`,`num_cuenta`,`id_user_reg`,`fec_reg`)
                VALUES (?, ?, ?, ?, ?, ?)";
        $sql = $cmd->prepare($sql);
        $sql->bindParam(1, $id_tercero, PDO::PARAM_INT);
        $sql->bindParam(2, $id_banco, PDO::PARAM_INT);
        $sql->bindParam(3, $tp_cta, PDO::PARAM_STR);
        $sql->bindParam(4, $num_cta, PDO::PARAM_STR);
        $sql->bindParam(5, $iduser, PDO::PARAM_INT);
        $sql->bindValue(6, $date->format('Y-m-d H:i:s'));
        $sql->execute();
        
        if ($cmd->lastInsertId() > 0) {
            Logs::guardaLog("INSERT INTO `ctt_cuenta_bancaria` (`id_tercero`,`id_banco`,`tipo_cuenta`,`num_cuenta`,`id_user_reg`,`fec_reg`) VALUES ($id_tercero, $id_banco, '$tp_cta', '$num_cta', $iduser, '" . $date->format('Y-m-d H:i:s') . "')");
            echo 'ok';
        } else {
            echo 'Error: ' . $sql->errorInfo()[2];
        }
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
