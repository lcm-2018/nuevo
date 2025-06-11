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


use Src\Nomina\Empleados\Php\Clases\Seguridad_Social;
use Src\Common\Php\Clases\Terceros;
use Src\Common\Php\Clases\Permisos;
use Src\Common\Php\Clases\Valores;


$sql        = new Seguridad_Social();
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
$obj =                  $sql->getRegistrosDT($start, $length, $filtros, $col, $dir);
$totalRecordsFilter =   $sql->getRegistrosFilter($filtros);
$totalRecords =         $sql->getRegistrosTotal($_POST['filter_id']);

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $id = $o['id_novedad'];
        $actualizar = $eliminar = $texto = '';
        if ($o['activo'] == 1) {
            if ($permisos->PermisosUsuario($opciones, 5101, 3) || $id_rol == 1) {
                $actualizar = '<button data-id="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 actualizar" title="Actualizar Empleado"><span class="fas fa-pencil-alt fa-sm"></span></button>';
            }
            if ($permisos->PermisosUsuario($opciones, 5101, 4) || $id_rol == 1) {
                $eliminar = '<button data-id="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 eliminar" title="Eliminar Empleado"><span class="fas fa-trash-alt fa-sm"></span></button>';
            }
        }

        if ($o['riesgo'] == 'N/A') {
            $texto = 'text-body-tertiary';
        }

        $datos[] = [
            'id'            => $id,
            'tipo'          => $o['descripcion'],
            'nombre'        => $o['nom_tercero'],
            'nit'           => $o['nit_tercero'],
            'afiliacion'    => $o['fec_inicia'],
            'retiro'        => $o['fec_fin'],
            'riesgo'        => '<div class="text-center ' . $texto . '">' . $o['riesgo'] . '</div>',
            'acciones'      => '<div class="text-center">' . $actualizar . $eliminar . '</div>',
        ];
    }
}
$data = [
    'data'              => $datos,
    'recordsFiltered'   => $totalRecordsFilter,
    'recordsTotal'      => $totalRecords,
];
echo json_encode($data);
