<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
$cmd = \Config\Clases\Conexion::getConexion();


$id_detalle = isset($_POST['id']) ?  $_POST['id'] : exit('Acceso no disponible');
try {
    $sql = "SELECT
                `pto_rad_detalle`.`id_pto_rad_det`
                , `pto_cargue`.`nom_rubro`
                , `pto_cargue`.`cod_pptal`
                , `pto_cargue`.`id_cargue`
                , `pto_cargue`.`tipo_dato`
                , `pto_rad_detalle`.`valor`
                , `pto_rad`.`id_pto`
            FROM
                `pto_rad_detalle`
                INNER JOIN `pto_rad` 
                    ON (`pto_rad_detalle`.`id_pto_rad` = `pto_rad`.`id_pto_rad`)
                INNER JOIN `pto_cargue` 
                    ON (`pto_rad_detalle`.`id_rubro` = `pto_cargue`.`id_cargue`)
            WHERE (`pto_rad_detalle`.`id_pto_rad_det` = $id_detalle)";
    $rs = $cmd->query($sql);
    $detalle = $rs->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$res['status'] = 'error';
if (!empty($detalle)) {
    $res['status'] = 'ok';
    $res[1] = ' <input type="text" id="rubroCod" class="form-control form-control-sm bg-input" value="' .  $detalle['cod_pptal'] . ' - ' . $detalle['nom_rubro'] . '">
            <input type="hidden" name="id_rubroCod" id="id_rubroCod" class="form-control form-control-sm bg-input" value="' . $detalle['id_cargue'] . '">
            <input type="hidden" id="tipoRubro" name="tipoRubro" value="' . $detalle['tipo_dato'] . '">';
    $res[2] = '<input type="text" name="valorDeb" id="valorDeb" class="form-control form-control-sm  bg-input" size="6" style="text-align: right;" onkeyup="NumberMiles(this)" value="' . $detalle['valor'] . '">';
    $res[3] = '<div class="text-center"><input type="hidden" name="id_pto_mod" id="id_pto_mod" value="' . $detalle['id_pto'] . '">
            <a class="btn btn-outline-warning btn-xs rounded-circle me-1 shadow" title="Ver historial del rubro" onclick="verHistorial(this)"><span class="far fa-list-alt "></span></a>
            <button text="' . $id_detalle . '" class="btn btn-primary btn-sm" onclick="RegDetalleRads(this)">Modificar</button></div>';
    $res['msg'] = 'Consulta exitosa';
} else {
    $res['msg'] = 'No se encontraron datos';
}
echo json_encode($res);
