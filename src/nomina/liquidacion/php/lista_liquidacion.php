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

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $id = $o['id_empleado'];
        $detalles =  '';
        if ($permisos->PermisosUsuario($opciones, 5101, 1) || $id_rol == 1) {
            $detalles = '<button data-id="' . $id . '" class="btn btn-outline-warning btn-xs rounded-circle shadow me-1 detalles" title="Ver detalles"><div class="fas fa-eye fa-sm"></div></button>';
        }
        $datos[] = [
            'check'        => $id,
            'doc'          => '',
            'nombre'       => '',
            'observacion'  => '',
            'laborado'     => '',
            'incapacidad'  => '',
            'licencia'     => '',
            'vacacion'     => '',
            'otro'         => '',
        ];
    }
}
$data = [
    'data'              => $datos,
    'recordsFiltered'   => $totalRecordsFilter,
    'recordsTotal'      => $totalRecords,
];
echo json_encode($data);
