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
$id_tercero = isset($_POST['id_tercero']) ? $_POST['id_tercero'] : '';
$todas = isset($_POST['todas']) ? $_POST['todas'] : 0;

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    echo '<option value="">' . $titulo . '</option>';
    $sql = "SELECT id_rem_int, estado, 
            CONCAT(num_remision,'-',desc_remision,' (', CASE estado WHEN 1 THEN 'Activa' WHEN 2 THEN 'Cerrada' END, ')') AS remision
            FROM far_remision_interna
            WHERE id_tercero = $id_tercero " . ($todas ? "" : "AND estado= 1") . "
            ORDER BY estado, id_rem_int";    
    $rs = $cmd->query($sql);
    $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    foreach ($objs as $obj) {
        echo '<option value="' . $obj['id_rem_int'] . '">' . $obj['remision'] . '</option>';
    }
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
