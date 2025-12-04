<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

$start =        isset($_POST['start']) ? intval($_POST['start']) : 0;
$length =       isset($_POST['length']) ? intval($_POST['length']) : 10;
$val_busca =    $_POST['search']['value'] ?? '';
$col =          $_POST['order'][0]['column'] + 1;
$dir =          $_POST['order'][0]['dir'];

use Src\Usuarios\General\Php\Clases\Roles;

$sql =      new Roles();
$obj =                  $sql->getRegistrosDT($start, $length, $val_busca, $col, $dir);
$totalRecordsFilter =   $sql->getRegistrosFilter($val_busca);
$totalRecords =         $sql->getRegistrosTotal();

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $id = $o['id_rol'];
        $actualizar = '<button data-id="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 actualizar" title="Actualizar valor concepto"><span class="fas fa-pencil-alt"></span></button>';
        $eliminar = '<button data-id="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 eliminar" title="Eliminar concepto"><span class="fas fa-trash-alt"></span></button>';
        $permisos = '<button data-id="' . $id . '" class="btn btn-outline-warning btn-xs rounded-circle shadow me-1 permisos" title="Ver permisos"><span class="fas fa-lock"></span></button>';
        $datos[] = [
            'id_rol' =>     $id,
            'rol'     =>     $o['nom_rol'],
            'acciones'   =>     '<div class="text-center">' . $actualizar . $permisos . $eliminar . '</div>',
        ];
    }
}
$data = [
    'data' =>               $datos,
    'recordsFiltered' =>    $totalRecordsFilter,
    'recordsTotal' =>       $totalRecords,
];
echo json_encode($data);
