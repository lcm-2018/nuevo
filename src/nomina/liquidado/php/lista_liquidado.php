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
        $detalles = $borrar = $anular = $imprimir1 = $imprimir2 = $pdfMensual = $pdfPatronal = $estado = '';
        if ($o['estado'] >= 2) {
            $estado = '<span class="badge bg-success">DEFINITIVA</span>';
        } elseif ($o['estado'] == 1) {
            $estado = '<span class="badge bg-warning text-dark">PENDIENTE</span>';
        } else if ($o['estado'] == 0) {
            $estado = '<span class="badge bg-secondary text-dark">ANULADO</span>';
        }
        if ($permisos->PermisosUsuario($opciones, 5101, 1) || $id_rol == 1) {
            $detalles = '<button data-id="' . $id . '" class="btn btn-outline-warning btn-xs rounded-circle shadow me-1 detalles" title="Ver detalles"><span class="fas fa-eye fa-sm"></span></button>';
        }
        if (($permisos->PermisosUsuario($opciones, 5101, 4) || $id_rol == 1) && $o['estado'] == 1) {
            $borrar = '<button data-id="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 borrar" title="Borrar Registro"><span class="fas fa-trash-alt fa-sm"></span></button>';
        }
        if (($permisos->PermisosUsuario($opciones, 5101, 5) || $id_rol == 1) && $o['estado'] == 1) {
            $anular = '<button data-id="' . $id . '" class="btn btn-outline-secondary btn-xs rounded-circle shadow me-1 anular" title="Anular Registro"><span class="fas fa-ban fa-sm"></span></button>';
        }
        if (($permisos->PermisosUsuario($opciones, 5101, 6) || $id_rol == 1) && $o['estado'] >= 2) {
            $imprimir1 = '<button data-id="' . $id . '" text="M" class="btn btn-outline-success btn-xs rounded-circle shadow me-1 imprimir" title="Solicitud de CDP"><span class="fas fa-print fa-sm"></span></button>';
            $imprimir2 = '<button data-id="' . $id . '" text="P" class="btn btn-outline-info btn-xs rounded-circle shadow me-1 imprimir" title="Solicitud de CDP Patronal"><span class="fas fa-print fa-sm"></span></button>';
            //$pdfMensual = '<button data-id="' . $id . '" text="M" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 descargar-pdf" title="Descargar PDF CDP Mensual"><span class="fas fa-download fa-sm"></span></button>';
            //$pdfPatronal = '<button data-id="' . $id . '" text="P" class="btn btn-outline-dark btn-xs rounded-circle shadow me-1 descargar-pdf" title="Descargar PDF CDP Patronal"><span class="fas fa-download fa-sm"></span></button>';
        }

        $datos[] = [
            'id'            =>  $id,
            'descripcion'   =>  mb_strtoupper($o['descripcion']),
            'mes'           =>  mb_strtoupper($o['nom_mes']),
            'tipo'          =>  mb_strtoupper($o['tipo']),
            'estado'        =>  '<div class="text-center">' . $estado . '</div>',
            'accion'        =>  '<div class="text-center">' . $detalles . $borrar . $anular . $imprimir1 . $imprimir2 . $pdfMensual . $pdfPatronal . '</div>',
        ];
    }
}
$data = [
    'data'              => $datos,
    'recordsFiltered'   => $totalRecordsFilter,
    'recordsTotal'      => $totalRecords,
];
echo json_encode($data);
