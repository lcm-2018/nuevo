<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

$vigencia =     $_SESSION['vigencia'];
$start =        isset($_POST['start']) ? intval($_POST['start']) : 0;
$length =       isset($_POST['length']) ? intval($_POST['length']) : 10;
$col =          $_POST['order'][0]['column'] + 1;
$dir =          $_POST['order'][0]['dir'];

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];


use Src\Nomina\Empleados\Php\Clases\Empleados;
use Src\Common\Php\Clases\Terceros;
use Src\Common\Php\Clases\Permisos;
use Src\Common\Php\Clases\Valores;


$sql        = new Empleados();
$permisos   = new Permisos();
$pesos      = new Valores();
$tercero    = new Terceros();

$filtros = [];

foreach ($_POST as $clave => $valor) {
    if (strpos($clave, 'filter_') === 0) {
        $filtros[$clave] = $valor;
    }
}

$opciones =             $permisos->PermisoOpciones($id_user);
$obj =                  $sql->getEmpleadosDT($start, $length, $filtros, $col, $dir);
$totalRecordsFilter =   $sql->getRegistrosFilter($filtros);
$totalRecords =         $sql->getRegistrosTotal();

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $id = $o['id_empleado'];
        $actualizar = $eliminar = '';
        if ($permisos->PermisosUsuario($opciones, 5101, 3) || $id_rol == 1) {
            $actualizar = '<button data-id="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 actualizar" title="Actualizar valor concepto"><span class="fas fa-pencil-alt"></span></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 5101, 4) || $id_rol == 1) {
            $eliminar = '<button data-id="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 eliminar" title="Eliminar concepto"><span class="fas fa-trash-alt"></span></button>';
        }
        $nombre = $tercero->setNombre($o['nombre']);
        $estado = $o['estado'] == 1 ? '<a href="javascript:CambiaEstadoEmpleado(' . $id . ',0);" title="Inactivar"><i class="fas fa-toggle-on fa-lg text-success"></i></a>' : '<a href="javascript:CambiaEstadoEmpleado(' . $id . ',1)" title="Activar"><i class="fas fa-toggle-off fa-lg text-secondary"></i></a>';
        $datos[] = [
            'id'      => $id,
            'nodoc'   => $o['no_documento'],
            'nombre'  => $nombre,
            'correo'  => $o['correo'],
            'tel'     => $o['telefono'],
            'estado'  => '<div class="text-center">' . $estado . '</div>',
            'acciones' => '<div class="text-center">' . $actualizar . $eliminar . '</div>',
        ];
    }
}
$data = [
    'data'              => $datos,
    'recordsFiltered'   => $totalRecordsFilter,
    'recordsTotal'      => $totalRecords,
];
echo json_encode($data);
