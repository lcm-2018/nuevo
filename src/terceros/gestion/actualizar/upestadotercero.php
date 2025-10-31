<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
$estado = $_POST['e'];
$idter = isset($_POST['idt']) ? $_POST['idt'] : exit('Acción no permitida');
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    $sql = "UPDATE `tb_terceros` SET estado = ? WHERE `id_tercero_api` = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $estado);
    $sql->bindParam(2, $idter);
    $sql->execute();
    if ($sql->rowCount() > 0) {
        echo $estado;
    } else {
        echo $sql->errorInfo()[2];
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
