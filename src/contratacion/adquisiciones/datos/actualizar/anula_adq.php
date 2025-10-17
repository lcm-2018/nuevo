<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include_once '../../../../../config/autoloader.php';

$id = isset($_POST['id']) ? $_POST['id'] : exit('Acción no permitida');

$id_user = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$estado = 99;
$cmd = \Config\Clases\Conexion::getConexion();

try {
    $sq1 = "SELECT `id_orden` FROM `ctt_adquisiciones` WHERE `id_adquisicion` = $id";
    $rs = $cmd->query($sq1);
    $res = $rs->fetch();
    if ($res['id_orden'] > 0) {
        $sq2 = "UPDATE `far_alm_pedido` SET `estado` = 2 WHERE `id_pedido` = ?";
        $stmt = $cmd->prepare($sq2);
        $stmt->bindParam(1, $res['id_orden'], PDO::PARAM_INT);
        $stmt->execute();
    }
    $sql = "UPDATE `ctt_adquisiciones` SET `estado`= ?, `id_user_act` = ?, `fec_act` = ? WHERE `id_adquisicion` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $estado, PDO::PARAM_INT);
    $stmt->bindParam(2, $id_user, PDO::PARAM_INT);
    $stmt->bindValue(3, $date->format('Y-m-d H:i:s'));
    $stmt->bindParam(4, $id, PDO::PARAM_INT);
    $stmt->execute();
    if (!($stmt->rowCount() > 0)) {
        echo $stmt->errorInfo()[2];
    } else {
        echo 'ok';
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
