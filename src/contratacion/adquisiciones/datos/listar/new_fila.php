<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include_once '../../../../../config/autoloader.php';
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT `id_sede`, `nom_sede` AS `nombre` FROM `tb_sedes`";
    $rs = $cmd->query($sql);
    $sedes = $rs->fetchAll();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$res = '';
$opc = '';
foreach ($sedes as $s) {
    $opc .=  '<option value="' . $s['id_sede'] . '">' . $s['nombre'] . '</option>';
}
$res =
    '<div class="row px-4 g-2">
    <div class="col-md-4 mb-2">
        <select name="slcSedeAC[]" class="form-select form-select-sm slcSedeAC bg-input">
            <option value="0">--Seleccione--</option>' .
    $opc . '
        </select>
    </div>
    <div class="col-md-4 mb-2">
        <select name="slcCentroCosto[]" class="form-select form-select-sm slcCentroCosto bg-input">
            <option value="0">--Seleccionar Sede--</option>
        </select>
    </div>
    <div class="col-md-4 mb-2">
        <div class="input-group input-group-sm">
            <input type="number" name="numHorasMes[]" class="form-control form-control-sm bg-input">
            <button class="btn btn-outline-danger delRowSedes" type="button"><i class="fas fa-minus"></i></button>
        </div>
    </div>
</div>';
echo $res;
