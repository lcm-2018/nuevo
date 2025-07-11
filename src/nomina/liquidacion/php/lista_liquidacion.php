<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

$vigencia =                 $_SESSION['vigencia'];
$start =                    isset($_POST['start']) ? intval($_POST['start']) : 0;
$length =                   isset($_POST['length']) ? intval($_POST['length']) : 10;
$col =                      $_POST['order'][0]['column'] + 1;
$dir =                      $_POST['order'][0]['dir'];

$filtros = [];

foreach ($_POST as $clave => $valor) {
    if (strpos($clave, 'filter_') === 0) {
        $filtros[$clave] = $valor;
    }
}

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Src\Common\Php\Clases\Combos;
use Src\Nomina\Liquidacion\Php\Clases\Liquidacion;
use Src\Common\Php\Clases\Permisos;
use Src\Common\Php\Clases\Valores;

$sql        = new Liquidacion();
$permisos   = new Permisos();
$Valores    = new Valores();

$opciones =             $permisos->PermisoOpciones($id_user);
$obj =                  $sql->getRegistrosDT($start, $length, $filtros, $col, $dir);
$totalRecordsFilter =   $sql->getRegistrosFilter($filtros);
$totalRecords =         $sql->getRegistrosTotal($filtros);
$mp =                   Combos::getMetodoPago();

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $id = $o['id_empleado'];
        $laborado = 30 - $o['inc'] - $o['lic'] - $o['vac'];
        $input = '<input type="number" style="height: auto !important;" class="no-focus text-end border-0 rounded p-0 w-100" name="lab[' . $id . ']" value="' . $laborado . '" min="0" max="' . $laborado . '" step="1">';
        $metodo = '<select style="height:auto !important; max-width: 110px;" class="no-focus border-0 rounded p-0 w-100" name="metodo[' . $id . ']">' . $mp . '</select>';
        $datos[] = [
            'check'        => '<div class="text-center"><input type="checkbox" name="chk_liquidacion[]" value="' . $id . '" checked></div>',
            'doc'          => $o['no_documento'],
            'nombre'       => mb_strtoupper($o['nombre']),
            'observacion'  => $o['observacion'] >= 365 ? '<span class="text-danger"><b>Vacaciones: ' . ($o['observacion']) . ' días</b></span>' :  '',
            'laborado'     => $input,
            'incapacidad'  => $o['inc'],
            'licencia'     => $o['lic'],
            'vacacion'     => $o['vac'],
            'otro'         => 0,
            'pago'         => $metodo,
        ];
    }
}
$data = [
    'data'              => $datos,
    'recordsFiltered'   => $totalRecordsFilter,
    'recordsTotal'      => $totalRecords,
];
echo json_encode($data);
