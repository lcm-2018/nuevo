<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include_once '../../../../../config/autoloader.php';
$id_rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : null;
$id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null;
$permisos = new \Src\Common\Php\Clases\Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$cmd = \Config\Clases\Conexion::getConexion();
$id_c = isset($_POST['id']) ? $_POST['id'] : exit('Acción no permitida');

try {
    $id_user = $id_user;
    $estado = 99;
    $date = new DateTime('now', new DateTimeZone('America/Bogota'));
    
    $sql = "UPDATE `ctt_adquisiciones` SET `estado`= ?, `id_user_act` = ?, `fec_act` = ? WHERE `id_adquisicion` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $estado, PDO::PARAM_INT);
    $stmt->bindParam(2, $id_user, PDO::PARAM_INT);
    $stmt->bindValue(3, $date->format('Y-m-d H:i:s'));
    $stmt->bindParam(4, $id_c, PDO::PARAM_INT);
    $stmt->execute();
    if (!($stmt->rowCount() > 0)) {
        echo $stmt->errorInfo()[2];
    } else {
        echo 1;
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
