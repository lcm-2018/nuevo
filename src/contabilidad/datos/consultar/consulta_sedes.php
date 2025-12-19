<?php
// Realiza la suma del valor total asignado a un CDP
include '../../../../config/autoloader.php';
$_post = json_decode(file_get_contents('php://input'), true);
// Buscamos si hay registros asocidos al municipio
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT id_sede, nom_sede as nombre FROM tb_sedes WHERE id_municipio = $_post[id]";
    $rs = $cmd->query($sql);
    $sedes = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$response = '<label for=id_"sede" class="small">SEDE</label><select class="form-control form-control-sm bg-input py-0 sm" id="id_sede" name="id_sede" onchange="mostrarCentroCostos(value);">
<option value="0">-- Seleccionar --</option>';
foreach ($sedes as $sed) {
    $response .= '<option value="' . $sed['id_sede'] . '">' . $sed['nombre'] .  '</option>';
}
$response .= "</select>";
echo $response;
exit;
