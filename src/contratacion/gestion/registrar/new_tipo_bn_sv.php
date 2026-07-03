<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include_once '../../../../config/autoloader.php';
use Config\Clases\Logs;

$tipo = isset($_POST['slcTipo']) ? $_POST['slcTipo'] : exit('Acción no permitida');
$tbnsv = mb_strtoupper($_POST['txtTipoBnSv']);
$objpre = mb_strtoupper($_POST['txtObjPre']);
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));

$cmd = Config\Clases\Conexion::getConexion();

try {

    $sql = "INSERT INTO `tb_tipo_bien_servicio`
                (`id_tipo`,`tipo_bn_sv`,`objeto_definido`,`id_user_reg`,`fec_reg`)
            VALUES (?, ?, ?, ?, ?)";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $tipo, PDO::PARAM_INT);
    $sql->bindParam(2, $tbnsv, PDO::PARAM_STR);
    $sql->bindParam(3, $objpre, PDO::PARAM_STR);
    $sql->bindParam(4, $iduser, PDO::PARAM_INT);
    $sql->bindValue(5, $date->format('Y-m-d H:i:s'));
    $sql->execute();
    if ($cmd->lastInsertId() > 0) {
        Logs::guardaLog("INSERT INTO `tb_tipo_bien_servicio` (`id_tipo`,`tipo_bn_sv`,`objeto_definido`,`id_user_reg`,`fec_reg`) VALUES ($tipo, '$tbnsv', '$objpre', $iduser, '{$date->format('Y-m-d H:i:s')}')");
        echo '1';
    } else {
        echo $sql->errorInfo()[2];
    }
    $cmd = NULL;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
