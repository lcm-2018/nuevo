<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}

include_once '../../../config/autoloader.php';

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


use Src\Documentos\Php\Clases\Documentos;
use Src\Common\Php\Clases\Permisos;

$sql        = new Documentos();
$permisos   = new Permisos();

$opciones =             $permisos->PermisoOpciones($id_user);
$obj =                  $sql->getRegistrosDT($start, $length, $filtros, $col, $dir);
$totalRecordsFilter =   $sql->getRegistrosFilter($filtros);
$totalRecords =         $sql->getRegistrosTotal($filtros);

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $id = $o['id_maestro'];
        $detalles =  $editar = $borrar = '';
        if ($permisos->PermisosUsuario($opciones, 6001, 1) || $id_rol == 1) {
            $detalles = '<button data-id="' . $id . '" class="btn btn-outline-warning btn-xs rounded-circle shadow me-1 detalles" title="Ver detalles"><div class="fas fa-eye fa-sm"></div></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 6001, 3) || $id_rol == 1) {
            $editar = '<button data-id="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 editar" title="Editar"><div class="fas fa-pencil-alt fa-sm"></div></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 6001, 4) || $id_rol == 1) {
            $borrar = '<button data-id="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 borrar" title="Borrar"><div class="fas fa-trash fa-sm"></div></button>';
        }
        $estado = $o['estado'] == 1 ? '<a href="javascript:void(0)" class="estado" data-id="' . $id . '|0' . '"><span class="badge bg-success">ACTIVO</span></a>' : ' <a href="javascript:void(0)" class="estado" data-id="' . $id . '|1' . '"><span class="badge bg-secondary">INACTIVO</span></a>';
        $control = $o['control_doc'] == 1 ? 'SI' : 'NO';

        if ($o['estado'] == 0) {
            $editar = $borrar = '';
        }

        $datos[] = [
            'id' => $id,
            'modulo' => $o['nom_modulo'],
            'documento' => $o['nombre'],
            'version' => $o['version_doc'],
            'fecha' => $o['fecha_doc'],
            'control' => '<div class="text-center">' . $control . '</div>',
            'estado' => '<div class="text-center">' . $estado . '</div>',
            'accion' => '<div class="text-center">' . $editar . $detalles . $borrar . '</div>',
        ];
    }
}
$data = [
    'data'              => $datos,
    'recordsFiltered'   => $totalRecordsFilter,
    'recordsTotal'      => $totalRecords,
];
echo json_encode($data);
