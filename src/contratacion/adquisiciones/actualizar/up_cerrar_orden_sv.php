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
$id_adq = isset($_POST['id_adq']) ? $_POST['id_adq'] : exit('Accion no permitida');
$valor = $_POST['suma'];
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));

try {
    $estado = 5;
    $query = "UPDATE `ctt_adquisiciones` SET `estado` = ?, `id_user_act` = ?, `fec_act` = ?, `val_contrato` = ? WHERE `id_adquisicion` = ?";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $estado, PDO::PARAM_INT);
    $query->bindParam(2, $iduser, PDO::PARAM_INT);
    $query->bindValue(3, $date->format('Y-m-d H:i:s'));
    $query->bindParam(4, $valor, PDO::PARAM_STR);
    $query->bindParam(5, $id_adq, PDO::PARAM_INT);
    $query->execute();
    if ($query->rowCount() > 0) {
        echo 'ok';
    } else {
        echo 'Error al cerrar la orden A' . $sql->errorInfo()[2];
    }
    $cmd = NULL;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexi\u0010n a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
