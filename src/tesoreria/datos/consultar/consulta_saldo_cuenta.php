<?php
// Realiza la suma del valor total asignado a un CDP
include '../../../../config/autoloader.php';
$id_cta = isset($_POST['id']) ? $_POST['id'] : exit('Acceso no autorizado');
$response['status'] = 'error';
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                SUM(`ctb_libaux`.`debito`) - SUM(`ctb_libaux`.`credito`) AS `saldo`
            FROM
                `tes_cuentas`
                INNER JOIN `ctb_libaux` 
                    ON (`tes_cuentas`.`id_cuenta` = `ctb_libaux`.`id_cuenta`)
            WHERE (`tes_cuentas`.`id_tes_cuenta` = {$id_cta})";
    $rs = $cmd->query($sql);
    $valores = $rs->fetch();
    $saldo = !empty($valores) ? $valores['saldo'] : 0;
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

if ($saldo > 0) {
    $response['status'] = 'ok';
    $response['saldo'] = $saldo;
} else {
    $response['msg'] = 'La cuenta no tiene saldo disponible $' . number_format($saldo, 2, '.', ',');
}
echo json_encode($response);
