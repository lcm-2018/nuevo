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
use Src\Nomina\Empleados\Php\Clases\Vacaciones;

$sql        = new Liquidacion();
$permisos   = new Permisos();
$Valores    = new Valores();
$Vacaciones = new Vacaciones();


$opciones =             $permisos->PermisoOpciones($id_user);
$obj =                  $sql->getRegistrosDT($start, $length, $filtros, $col, $dir);
$totalRecordsFilter =   $sql->getRegistrosFilter($filtros);
$totalRecords =         $sql->getRegistrosTotal($filtros);
$mp =                   Combos::getMetodoPago();
$liquidados =           $Vacaciones->getRegistrosDT(0, -1, [], 1, 'asc');
$liq = [];
foreach ($liquidados as $l) {
    $id = $l['id_empleado'];
    if (isset($liq[$id])) {
        $liq[$id] += $l['liq'];
    } else {
        $liq[$id] = $l['liq'];
    }
}
$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $id = $o['id_empleado'];
        $id_contrato = $o['id_contrato'];
        $metodo = '<select style="height:auto !important; max-width: 110px;" class="no-focus border-0 rounded p-0 w-100" name="metodo[' . $id . ']">' . $mp . '</select>';
        if ($o['vac'] > 0 && $liq[$id] == 0) {
            $datos[] = [
                'check'        => '<div class="text-center"><input type="checkbox" name="chk_liquidacion[]" value="' . $id . '" checked><input type="hidden" name="id_contrato[' . $id . ']" value="' . $id_contrato . '"></div>',
                'doc'          => $o['no_documento'],
                'nombre'       => mb_strtoupper($o['nombre']),
                'observacion'  => '',
                'laborado'     => 0,
                'incapacidad'  => 0,
                'licencia'     => 0,
                'vacacion'     => 0,
                'otro'         => 0,
                'pago'         => $metodo,
            ];
        }
    }
}
$data = [
    'data'              => $datos,
    'recordsFiltered'   => $totalRecordsFilter,
    'recordsTotal'      => $totalRecords,
];
echo json_encode($data);
