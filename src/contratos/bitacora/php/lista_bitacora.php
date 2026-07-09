<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Src\Contratos\Bitacora\Php\Clases\Bitacora;
use Src\Common\Php\Clases\Permisos;

$id_rol   = $_SESSION['rol'];
$id_user  = $_SESSION['id_user'];
$start    = isset($_POST['start'])  ? intval($_POST['start'])  : 0;
$length   = isset($_POST['length']) ? intval($_POST['length']) : 10;
$val_busca = $_POST['search']['value'] ?? '';
$col      = ($_POST['order'][0]['column'] ?? 0) + 1;
$dir      = $_POST['order'][0]['dir'] ?? 'desc';

$clase    = new Bitacora();
$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$obj                = $clase->getBitacora($start, $length, $val_busca, $col, $dir);
$totalRecordsFilter = $clase->getRegistrosFilter($val_busca);
$totalRecords       = $clase->getRegistrosTotal();

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $actualizar = $eliminar = '';
        $id = $o['id_bitacora'];

        // Permiso 5807 = bitácora, nivel 3=editar, 4=eliminar
        if ($permisos->PermisosUsuario($opciones, 5807, 3) || $id_rol == 1) {
            $actualizar = '<button data-id="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 actualizar" title="Editar bitácora"><span class="fas fa-pencil-alt"></span></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 5807, 4) || $id_rol == 1) {
            $eliminar = '<button data-id="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 eliminar" title="Eliminar bitácora"><span class="fas fa-trash-alt"></span></button>';
        }

        $datos[] = [
            'id'          => $id,
            'fecha'       => $o['fec_evento'],
            'usuario'     => $o['usuario'],
            'tipo'        => '<span class="badge bg-info">' . $o['tipo_evento'] . '</span>',
            'descripcion' => $o['descripcion'],
            'relacion'    => 'Proc: ' . ($o['codigo_proceso'] ?: 'N/A') . '<br>Ctt: ' . ($o['codigo_contrato'] ?: 'N/A'),
            'botones'     => '<div class="text-center">' . $actualizar . $eliminar . '</div>',
        ];
    }
}

echo json_encode([
    'data'            => $datos,
    'recordsFiltered' => $totalRecordsFilter,
    'recordsTotal'    => $totalRecords,
]);
