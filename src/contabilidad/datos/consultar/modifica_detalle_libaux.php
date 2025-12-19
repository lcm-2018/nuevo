<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$cmd = \Config\Clases\Conexion::getConexion();
function pesos($valor)
{
    return number_format($valor, 2, '.', ',');
}
$id_detalle = isset($_POST['id']) ?  $_POST['id'] : exit('Acceso no disponible');
try {
    $sql = "SELECT
                `ctb_libaux`.`id_ctb_libaux`
                , `ctb_libaux`.`id_tercero_api`
                , `ctb_pgcp`.`cuenta`
                , `ctb_pgcp`.`nombre`
                , `ctb_libaux`.`debito`
                , `ctb_libaux`.`credito`
                , `ctb_libaux`.`id_cuenta`
                , `ctb_pgcp`.`tipo_dato`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM
                `ctb_libaux`
                LEFT JOIN `ctb_pgcp` 
                    ON (`ctb_libaux`.`id_cuenta` = `ctb_pgcp`.`id_pgcp`)
                LEFT JOIN `tb_terceros`
                    ON (`ctb_libaux`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
            WHERE (`ctb_libaux`.`id_ctb_libaux` = $id_detalle)";
    $rs = $cmd->query($sql);
    $detalle = $rs->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$cmd = null;
$res['status'] = 'error';
if (!empty($detalle)) {
    $tercero = !empty($detalle['nom_tercero']) ? ltrim($detalle['nom_tercero'] . ' -> ' . $detalle['nit_tercero']) : '';
    $id_tercero = $detalle['id_tercero_api'] > 0 ? $detalle['id_tercero_api'] : 0;
    $res['status'] = 'ok';
    $res[1] = '<input type="text" id="codigoCta" name="codigoCta" class="form-control form-control-sm bg-input" value="' .  $detalle['cuenta'] . ' - ' . $detalle['nombre'] . '">
            <input type="hidden" name="id_codigoCta" id="id_codigoCta" class="form-control form-control-sm bg-input" value="' . $detalle['id_cuenta'] . '">
            <input type="hidden" id="tipoDato" name="tipoDato" value="' . $detalle['tipo_dato'] . '">';
    $res[2] = '<input type="text" name="bTercero" id="bTercero" class="form-control form-control-sm bg-input bTercero" value="' . $tercero . '">
            <input type="hidden" name="idTercero" id="idTercero" value="' . $detalle['id_tercero_api'] . '">';
    $res[3] = '<input type="text" name="valorDebito" id="valorDebito" class="form-control form-control-sm bg-input " style="text-align: right;" onkeyup="NumberMiles(this)" onchange="llenarCero(id)" value="' . pesos($detalle['debito']) . '">';
    $res[4] = '<input type="text" name="valorCredito" id="valorCredito" class="form-control form-control-sm bg-input " style="text-align: right;" onkeyup="NumberMiles(this)" onchange="llenarCero(id)" value="' . pesos($detalle['credito']) . '">';
    $res[5] = '<div class="text-center"><button text="' . $id_detalle . '" class="btn btn-primary btn-sm" onclick="GestMvtoDetalle(this)">Modificar</button></div>';
    $res['msg'] = 'Consulta exitosa';
} else {
    $res['msg'] = 'No se encontraron datos';
}
echo json_encode($res);
