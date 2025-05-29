<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

$vigencia   = $_SESSION['vigencia'];
$start      = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length     = isset($_POST['length']) ? intval($_POST['length']) : 10;
$val_busca  = $_POST['search']['value'] ?? '';
$col        = $_POST['order'][0]['column'] + 1;
$dir        = $_POST['order'][0]['dir'];
$id_empleado = $_POST['id_empleado'];

$id_rol    = $_SESSION['rol'];
$id_user   = $_SESSION['id_user'];


use Src\Nomina\Empleados\Php\Clases\Contratos;
use Src\Common\Php\Clases\Permisos;
use Src\Common\Php\Clases\Valores;


$sql        = new Contratos();
$permisos   = new Permisos();
$pesos      = new Valores();


$opciones           =   $permisos->PermisoOpciones($id_user);
$obj                =   $sql->getContratosEmpleado($start, $length, $val_busca, $col, $dir, $id_empleado);
$totalRecordsFilter =   $sql->getRegistrosFilter($val_busca, $id_empleado);
$totalRecords       =   $sql->getRegistrosTotal($id_empleado);

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $id = $o['id_salario'];
        $actualizar = $eliminar = '';
        if ($permisos->PermisosUsuario($opciones, 5101, 3) || $id_rol == 1) {
            $actualizar = '<button data-id="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 actualizar" title="Actualizar Empleado"><span class="fas fa-pencil-alt fa-sm"></span></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 5101, 4) || $id_rol == 1) {
            $eliminar = '<button data-id="' . $o['id_contrato_emp'] . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 eliminar" title="Eliminar Empleado"><span class="fas fa-trash-alt fa-sm"></span></button>';
        }
        if ($o['estado'] == 0) {
            $actualizar = $eliminar = '';
        }
        $estado = $o['estado'] == 1 ? '<i class="fas fa-toggle-on fa-lg text-success" title="Activo"></i>' : '<i class="fas fa-toggle-off fa-lg text-secondary" title="Inactivo"></i>';
        $datos[] = [
            'id'      => $id,
            'inicia'   => $o['fec_inicio'],
            'termina'  => $o['fec_fin'],
            'salario'  => $pesos->Pesos($o['salario_basico']),
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
