<?php
// Realiza la suma del valor total asignado a un CDP
include '../../../../config/autoloader.php';
$_post = json_decode(file_get_contents('php://input'), true);
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_retencion`, `nombre_retencion` FROM `ctb_retenciones` WHERE `id_retencion_tipo` = 4";
    $rs = $cmd->query($sql);
    $retenciones = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$response = '
<label for="id_rete_sobre" class="small">Sobretasa</label>
<select class="form-control form-control-sm bg-input py-0 sm" id="id_rete_sobre" name="id_rete_sobre"  required>
<option value="0">-- Seleccionar--</option>';
foreach ($retenciones as $ret) {
    $response .= '<option value="' . $ret['id_retencion'] . '">' . $ret['nombre_retencion'] .  '</option>';
}
$response .= "</select>";
echo $response;
exit;
