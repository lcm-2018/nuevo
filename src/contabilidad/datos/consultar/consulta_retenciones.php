<?php
// Realiza la suma del valor total asignado a un CDP
include '../../../../config/autoloader.php';
$_post = json_decode(file_get_contents('php://input'), true);
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT id_retencion, nombre_retencion FROM ctb_retenciones WHERE id_retencion_tipo={$_post['id']} ORDER BY nombre_retencion ASC";
    $rs = $cmd->query($sql);
    $retenciones = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$response = '<div class="row mb-2">
<div class="col-md-6">
<label for="id_rete" class="small">Retención</label>
<select class="form-control form-control-sm bg-input py-0 sm" id="id_rete" name="id_rete" onchange="aplicaDescuentoRetenciones(value);" required>
<option value="0">-- Seleccionar --</option>';
foreach ($retenciones as $ret) {
    $response .= '<option value="' . $ret['id_retencion'] . '">' . $ret['nombre_retencion'] .  '</option>';
}
$response .= "</select>";
$response .= '</div>';
$response .= '<div class="col-md-6">
                <label for="valor_rte" class="small">Valor retención</label>
                <input type="text" name="valor_rte" id="valor_rte" class="form-control form-control-sm bg-input text-end" onkeyup="NumberMiles(this)" value="0">
            </div>';
$response .= '</div>';
echo $response;
exit;
