<?php
// Realiza la suma del valor total asignado a un CDP
include '../../../../config/autoloader.php';
$_post = json_decode(file_get_contents('php://input'), true);
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                MAX(seg_fin_chequera_cont.contador) as valor
            FROM
                seg_fin_chequera_cont
                INNER JOIN fin_chequeras 
                    ON (seg_fin_chequera_cont.id_chequera = fin_chequeras.id_chequera)
            WHERE fin_chequeras.id_cuenta ={$_post['id']};";
    $rs = $cmd->query($sql);
    $cheques = $rs->fetch();
    $cheque = $cheques['valor'] + 1;
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$response[] = array("value" => 'ok', "num_cheque" => $cheque);
echo json_encode($response);
exit;
