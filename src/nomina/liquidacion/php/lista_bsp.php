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

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Src\Common\Php\Clases\Combos;
use Src\Nomina\Liquidacion\Php\Clases\Liquidacion;
use Src\Common\Php\Clases\Permisos;
use Src\Nomina\Empleados\Php\Clases\Bsp;

$sql = new Liquidacion();
$permisos = new Permisos();
$BSP = new Bsp();

$opciones = $permisos->PermisoOpciones($id_user);
$obj = $sql->getRegistrosDT($start, $length, $filtros, $col, $dir);
$mp = Combos::getMetodoPago();
$emp_bsp = $BSP->getRegistroPorEmpleado(1);

$datos = [];
if (!empty($obj) && !empty($emp_bsp)) {
    foreach ($obj as $o) {
        $id = $o['id_empleado'];

        // Solo procesar empleados que tengan BSP pendiente
        if (!isset($emp_bsp[$id])) {
            continue;
        }

        $id_contrato = $o['id_contrato'];
        $metodo = '<select style="height:auto !important; max-width: 110px;" class="no-focus border-0 rounded p-0 w-100" name="metodo[' . $id . ']" >' . $mp . '</select>';
        $datos[] = [
            'check' => '<div class="text-center"><input type="checkbox" name="chk_liquidacion[]" value="' . $id . '" checked><input type="hidden" name="id_contrato[' . $id . ']" value="' . $id_contrato . '"></div>',
            'doc' => $o['no_documento'],
            'nombre' => mb_strtoupper($o['nombre']),
            'observacion' => '',
            'laborado' => 0,
            'incapacidad' => 0,
            'licencia' => 0,
            'vacacion' => 0,
            'otro' => 0,
            'pago' => $metodo,
        ];
    }
}

$data = [
    'data' => $datos,
    'recordsFiltered' => count($datos),    // empleados BSP que coinciden con el filtro activo
    'recordsTotal' => count($emp_bsp),  // total de empleados con BSP pendiente (sin filtro)
];
echo json_encode($data);
