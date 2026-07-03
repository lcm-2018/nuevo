<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
use Config\Clases\Logs;

$id_tercero_api = isset($_POST['id_tercero']) ? $_POST['id_tercero'] : exit('Acción no permitida');
$tipotercero = $_POST['slcTipoTerce'];
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT `id_relacion` FROM `tb_rel_tercero` WHERE `id_tercero_api` = '$id_tercero_api' AND `id_tipo_tercero` = '$tipotercero'";
    $rs = $cmd->query($sql);
    $registrado = $rs->fetch();
    if (!empty($registrado)) {
        echo 'Tipo de tercero ya registrado';
        exit();
    } else {
        $sql = "INSERT INTO `tb_rel_tercero` (`id_tercero_api`, `id_tipo_tercero`, `id_user_reg`, `fec_reg`) VALUES (?, ?, ?, ?)";
        $sql = $cmd->prepare($sql);
        $sql->bindParam(1, $id_tercero_api, PDO::PARAM_INT);
        $sql->bindParam(2, $tipotercero, PDO::PARAM_INT);
        $sql->bindParam(3, $iduser, PDO::PARAM_INT);
        $sql->bindValue(4, $date->format('Y-m-d H:i:s'));
        $sql->execute();
        if ($cmd->lastInsertId() > 0) {
            Logs::guardaLog("INSERT INTO `tb_rel_tercero` (`id_tercero_api`, `id_tipo_tercero`, `id_user_reg`, `fec_reg`) VALUES ($id_tercero_api, $tipotercero, $iduser, '" . $date->format('Y-m-d H:i:s') . "')");
            echo 'ok';
        } else {
            echo $sql->errorInfo()[2];
        }
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
