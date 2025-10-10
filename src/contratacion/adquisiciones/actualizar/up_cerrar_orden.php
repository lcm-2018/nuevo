<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include_once '../../../../../config/autoloader.php';
$id_rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : null;
$id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null;
$permisos = new \Src\Common\Php\Clases\Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$cmd = \Config\Clases\Conexion::getConexion();
$id_orden = isset($_POST['id_orden']) ? $_POST['id_orden'] : exit('Accion no permitida');
$id_adq = $_POST['id_adq'];
$valor = $_POST['suma'];
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$estado = 3;
try {
    $sql = "UPDATE `far_alm_pedido` SET `estado` = ? WHERE `id_pedido` = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $estado, PDO::PARAM_INT);
    $sql->bindParam(2, $id_orden, PDO::PARAM_INT);
    $sql->execute();
    $estado = 5;
    $query = "UPDATE `ctt_adquisiciones` SET `estado` = ?, `val_contrato` = ? WHERE `id_adquisicion` = ?";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $estado, PDO::PARAM_INT);
    $query->bindParam(2, $valor, PDO::PARAM_STR);
    $query->bindParam(3, $id_adq, PDO::PARAM_INT);
    $query->execute();
    if ($query->rowCount() > 0) {
        echo 'ok';
    } else {
        echo 'Error al cerrar la orden A';
    }
    $cmd = NULL;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
