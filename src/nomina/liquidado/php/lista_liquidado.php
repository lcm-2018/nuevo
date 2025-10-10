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

use Src\Nomina\Liquidado\Php\Clases\Liquidado;
use Src\Common\Php\Clases\Permisos;

$sql        = new Liquidado();
$permisos   = new Permisos();

$opciones =             $permisos->PermisoOpciones($id_user);
$obj =                  $sql->getRegistrosDT($start, $length, $filtros, $col, $dir);
$totalRecordsFilter =   $sql->getRegistrosFilter($filtros);
$totalRecords =         $sql->getRegistrosTotal($filtros);

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $id = $o['id_nomina'];
        $detalles = '';
        if ($o['estado'] >= 2) {
            $estado = '<span class="badge bg-success">DEFINITIVA</span>';
        } elseif ($o['estado'] == 1) {
            $estado = '<span class="badge bg-warning text-dark">PENDIENTE</span>';
        } else if ($o['estado'] == 0) {
            $estado = '<span class="badge bg-secondary text-dark">ANULADO</span>';
        }
        if ($permisos->PermisosUsuario($opciones, 5101, 1) || $id_rol == 1) {
            $detalles = '<button data-id="' . $id . '" class="btn btn-outline-warning btn-xs rounded-circle shadow me-1 detalles" title="Ver detalles"><div class="fas fa-eye fa-sm"></div></button>';
        }
        $datos[] = [
            'id'            =>  $id,
            'descripcion'   =>  mb_strtoupper($o['descripcion']),
            'mes'           =>  mb_strtoupper($o['nom_mes']),
            'tipo'          =>  mb_strtoupper($o['tipo']),
            'estado'        =>  '<div class="text-center">' . $estado . '</div>',
            'accion'        =>  '<div class="text-center">' . $detalles . '</div>',
        ];
    }
}
$data = [
    'data'              => $datos,
    'recordsFiltered'   => $totalRecordsFilter,
    'recordsTotal'      => $totalRecords,
];
echo json_encode($data);
