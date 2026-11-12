<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$idusr = $_SESSION['id_user'];
$idrol = $_SESSION['rol'];
$titulo = isset($_POST['titulo']) ? $_POST['titulo'] : '';
$rol_tercero = isset($_POST['rol_tercero']) ? $_POST['rol_tercero'] : '';

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    echo '<option value="">' . $titulo . '</option>';
    $sql = "SELECT id_tipo_transac,CONCAT_WS('-',cod_tipo_transac,desc_tipo_transac) AS tipo_transac
            FROM far_tipo_transac
            WHERE estado = 1 AND rol_tercero = $rol_tercero
            ORDER BY cod_tipo_transac";    
    $rs = $cmd->query($sql);
    $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    foreach ($objs as $obj) {
        echo '<option value="' . $obj['id_tipo_transac'] . '">' . $obj['tipo_transac'] . '</option>';
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
