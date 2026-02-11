<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

$start =    isset($_POST['start']) ? intval($_POST['start']) : 0;
$length =   isset($_POST['length']) ? intval($_POST['length']) : 10;
$col =      $_POST['order'][0]['column'] + 1;
$dir =      $_POST['order'][0]['dir'];
$_POST['search']['id_viatico'] = $_POST['id_viatico'];
$busca =    $_POST['search'];

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Src\Nomina\Empleados\Php\Clases\ViaticoNovedades;
use Src\Common\Php\Clases\Permisos;

$sql        = new ViaticoNovedades();
$permisos   = new Permisos();

$opciones =             $permisos->PermisoOpciones($id_user);
$obj =                  $sql->getRegistrosDT($start, $length, $busca, $col, $dir);
$totalRecordsFilter =   $sql->getRegistrosFilter($busca);
$totalRecords =         $sql->getRegistrosTotal($busca);

// Obtener el ID de la última novedad para bloquear acciones de las anteriores
$ultimaNovedadId = $sql->getUltimaNovedadId($_POST['id_viatico']);

$tiposRegistro = [
    1 => 'Anticipo',
    2 => 'Aprobado',
    3 => 'Legalizado',
    4 => 'Rechazado',
    5 => 'Caducado'
];

$badgeClases = [
    1 => 'bg-info',
    2 => 'bg-success',
    3 => 'bg-primary',
    4 => 'bg-danger',
    5 => 'bg-secondary'
];

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $id = $o['id_novedad'];
        $actualizar = $eliminar = $soporte = '';

        // Solo mostrar acciones en la última novedad
        if ($id == $ultimaNovedadId) {
            if ($permisos->PermisosUsuario($opciones, 5101, 3) || $id_rol == 1) {
                $actualizar = '<button data-id="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 actualizarNov" title="Actualizar"><span class="fas fa-pencil-alt fa-sm"></span></button>';
            }
            if ($permisos->PermisosUsuario($opciones, 5101, 4) || $id_rol == 1) {
                $eliminar = '<button data-id="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 eliminarNov" title="Eliminar"><span class="fas fa-trash-alt fa-sm"></span></button>';
            }
        }

        // Botón de soporte para Legalizado
        if ($o['tipo_registro'] == 3) {
            $soporte = '<button data-id="' . $id . '" class="btn btn-outline-info btn-xs rounded-circle shadow me-1 soporteNov" title="Ver soporte legalización"><span class="fas fa-file-alt fa-sm"></span></button>';
        }

        // Botón para Caducado
        if ($o['tipo_registro'] == 5) {
            $soporte = '<button data-id="' . $id . '" class="btn btn-outline-secondary btn-xs rounded-circle shadow me-1 caducadoNov" title="Gestión caducado"><span class="fas fa-undo fa-sm"></span></button>';
        }

        // Badge de tipo de registro
        $tipoTexto = isset($tiposRegistro[$o['tipo_registro']]) ? $tiposRegistro[$o['tipo_registro']] : $o['tipo_registro'];
        $badgeClase = isset($badgeClases[$o['tipo_registro']]) ? $badgeClases[$o['tipo_registro']] : 'bg-warning text-dark';
        $tipoBadge = '<span class="badge ' . $badgeClase . '">' . $tipoTexto . '</span>';

        $datos[] = [
            'id'             => $id,
            'fecha'          => $o['fecha'],
            'tipo_registro'  => $tipoBadge,
            'observacion'    => $o['observacion'],
            'acciones'       => '<div class="text-center">' . $actualizar . $eliminar . $soporte . '</div>',
        ];
    }
}
$data = [
    'data'              => $datos,
    'recordsFiltered'   => $totalRecordsFilter,
    'recordsTotal'      => $totalRecords,
];
echo json_encode($data);
