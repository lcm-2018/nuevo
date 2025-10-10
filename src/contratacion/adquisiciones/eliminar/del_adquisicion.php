<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
$idadq = $_SESSION['del'];
include_once '../../../../../config/autoloader.php';
$id_rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : null;
$id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null;
$permisos = new \Src\Common\Php\Clases\Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$cmd = \Config\Clases\Conexion::getConexion();

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    $sql = "DELETE FROM ctt_adquisiciones  WHERE id_adquisicion = ?";
    $sql = $cmd-> prepare($sql);
    $sql -> bindParam(1, $idadq, PDO::PARAM_INT);
    $sql->execute();
    if($sql->rowCount() > 0){
       echo '1';
    }
    else{
        echo $sql->errorInfo()[2];
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

