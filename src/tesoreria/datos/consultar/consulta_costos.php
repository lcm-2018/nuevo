<?php
// Realiza la suma del valor total asignado a un CDP
include '../../../../config/autoloader.php';
$_post = json_decode(file_get_contents('php://input'), true);
// Buscamos si hay registros posteriores a la fecha recibida
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
    `far_centrocosto_area`.`id_sede`
    ,`tb_centrocostos`.`descripcion`
    ,`tb_centrocostos`.`id_centro`
    FROM
    `far_centrocosto_area`
    INNER JOIN `tb_centrocostos` 
        ON (`far_centrocosto_area`.`id_centrocosto` = `tb_centrocostos`.`id_centro`)
    WHERE (`far_centrocosto_area`.`id_sede` =$_post[id])";
    $rs = $cmd->query($sql);
    $centros = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$response = '
<select class="form-control form-control-sm bg-input py-0 sm" id="id_cc" name="id_cc" >
<option value="">-- Seleccionar --</option>';
foreach ($centros as $sed) {
    $response .= '<option value="' . $sed['id_centro'] . '">' . $sed['descripcion'] .  '</option>';
}
$response .= "</select>";
echo $response;
exit;
