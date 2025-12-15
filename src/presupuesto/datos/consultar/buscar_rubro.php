<?php

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$res = "";
$id = $_POST['valor'];
$pto = $_POST['pto'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT cod_pptal FROM pto_cargue  WHERE cod_pptal='$id' AND id_pto_presupuestos=$pto";
    $rs = $cmd->query($sql);
    $res = $rs->rowCount();
    if ($res > 0) {
        $rta = 'ok';
    } else {
        $rta = '0';
        $cam = $id;
        $j = 0;
        for ($i = 1; $i < strlen($cam); ++$i) {
            $j = $i * -1;
            $var = substr($cam, 0, $j);
            $sql = "SELECT tipo_gasto, id_tipo_recurso FROM pto_cargue  WHERE cod_pptal= '$var'";
            $rs = $cmd->query($sql);
            $res = $rs->fetch();
            if ($res) {
                $rta = $res['id_tipo_recurso'] . "-" . $res['tipo_gasto'];
                break;
            } else {
                $rta = '' . '';
            }
        }
    }
    $cmd = null;
} catch (PDOException $e) {
    $res['mensaje'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
echo $rta;
