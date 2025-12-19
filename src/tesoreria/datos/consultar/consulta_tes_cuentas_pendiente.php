<?php
// Realiza la suma del valor total asignado a un CDP
include '../../../../config/autoloader.php';
$_post = json_decode(file_get_contents('php://input'), true);
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `ctb_pgcp`.`id_pgcp`
                , `ctb_pgcp`.`cuenta`
                , `ctb_pgcp`.`nombre`
                , `tb_bancos`.`id_banco`
            FROM
                `ctb_pgcp`
                LEFT JOIN `tes_cuentas` 
                    ON (`tes_cuentas`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`)
                LEFT JOIN `tb_bancos` 
                                ON (`tes_cuentas`.`id_banco` = `tb_bancos`.`id_banco`)
            WHERE (`ctb_pgcp`.`cuenta` LIKE '1110%' OR `ctb_pgcp`.`cuenta` LIKE '1132%' OR `ctb_pgcp`.`cuenta` LIKE '110106%')
                AND `ctb_pgcp`.`tipo_dato` = 'D'
                AND `tes_cuentas`.`id_tes_cuenta` IS NULL";
    $rs = $cmd->query($sql);
    $retenciones = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$response = '
<select class="form-control form-control-sm bg-input py-0 sm" id="cuentas" name="cuentas"  required>
<option value="0">-- Seleccionar --</option>';
foreach ($retenciones as $ret) {
    $value = base64_encode($ret['id_pgcp'] . '|' . $ret['nombre']);
    $response .= '<option value="' . $value . '">' . $ret['cuenta'] . ' | ' . $ret['nombre'] .  '</option>';
}
$response .= "</select>";
echo $response;
exit;
