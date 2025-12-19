<?php
// Realiza la suma del valor total asignado a un CDP
include '../../../../config/autoloader.php';
$_post = json_decode(file_get_contents('php://input'), true);
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `tes_cuentas`.`id_tes_cuenta`
                , `tes_cuentas`.`nombre`
                , `tes_cuentas`.`id_banco`
            FROM
                `tes_cuentas`
                INNER JOIN `tb_bancos` 
                    ON (`tes_cuentas`.`id_banco` = `tb_bancos`.`id_banco`)
            WHERE (`tes_cuentas`.`id_banco` ={$_post['id']} AND `tes_cuentas`.`estado` = 1)
            ORDER BY `tes_cuentas`.`nombre` ASC";
    $rs = $cmd->query($sql);
    $retenciones = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$response = '
<select class="form-control form-control-sm bg-input py-0 sm" id="cuentas" name="cuentas"  onchange="SaldoCuenta(value)">
<option value="0">-- Seleccionar --</option>';
foreach ($retenciones as $ret) {
    $response .= '<option value="' . $ret['id_tes_cuenta'] . '">' . $ret['nombre'] .  '</option>';
}
$response .= "</select>";
echo $response;
exit;
