<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$col = $_POST['order'][0]['column'] + 1;
$dir = $_POST['order'][0]['dir'];

$filtros = [];
foreach ($_POST as $clave => $valor) {
    if (strpos($clave, 'filter_') === 0) {
        $filtros[$clave] = $valor;
    }
}

$id_user = $_SESSION['id_user'];

use Src\Common\Php\Clases\Combos;
use Src\Common\Php\Clases\Permisos;
use Src\Nomina\Liquidacion\Php\Clases\Liquidacion;

$sql = new Liquidacion();
$permisos = new Permisos();

$opciones = $permisos->PermisoOpciones($id_user);
$obj = $sql->getRegistrosRetroDT($start, $length, $filtros, $col, $dir);
$totalRecordsFilter = $sql->getRegistrosRetroFilter($filtros);
$totalRecords = $sql->getRegistrosRetroTotal($filtros);
$mp = Combos::getMetodoPago();

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $id = $o['id_empleado'];
        $id_contrato = $o['id_contrato'];
        $input = '<input type="number" style="height: auto !important;" class="no-focus text-end border-0 rounded p-0 w-100" name="lab[' . $id . ']" value="' . intval($o['laborado']) . '" min="' . intval($o['laborado']) . '" max="' . intval($o['laborado']) . '" step="1" readonly>';
        $metodo = '<select style="height:auto !important; max-width: 110px;" class="no-focus border-0 rounded p-0 w-100" name="metodo[' . $id . ']">' . $mp . '</select>';
        $datos[] = [
            'check'        => '<div class="text-center"><input type="checkbox" name="chk_liquidacion[]" value="' . $id . '" checked><input type="hidden" name="id_contrato[' . $id . ']" value="' . $id_contrato . '"></div>',
            'doc'          => $o['no_documento'],
            'nombre'       => mb_strtoupper($o['nombre']),
            'observacion'  => 'RETROACTIVO ' . $o['rango'],
            'laborado'     => $input,
            'incapacidad'  => intval($o['inc']),
            'licencia'     => intval($o['lic']),
            'vacacion'     => intval($o['vac']),
            'otro'         => intval($o['meses']),
            'pago'         => $metodo,
        ];
    }
}

$data = [
    'data' => $datos,
    'recordsFiltered' => $totalRecordsFilter,
    'recordsTotal' => $totalRecords,
];
echo json_encode($data);
