<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Src\Contratos\Configuracion\Php\Clases\TiposProceso;
use Src\Common\Php\Clases\Permisos;

$id_rol   = $_SESSION['rol'];
$id_user  = $_SESSION['id_user'];
$start    = isset($_POST['start'])  ? intval($_POST['start'])  : 0;
$length   = isset($_POST['length']) ? intval($_POST['length']) : 10;
$val_busca = $_POST['search']['value'] ?? '';
$col      = ($_POST['order'][0]['column'] ?? 0) + 1;
$dir      = $_POST['order'][0]['dir'] ?? 'desc';

$clase    = new TiposProceso();
$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$obj              = $clase->getTipos($start, $length, $val_busca, $col, $dir);
$totalRecordsFilter = $clase->getRegistrosFilter($val_busca);
$totalRecords     = $clase->getRegistrosTotal();

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $actualizar = $eliminar = '';
        $id = $o['id_tipo_proceso'];

        // Permiso 5601 = configuración contratos, nivel 3=editar, 4=eliminar
        if ($permisos->PermisosUsuario($opciones, 5601, 3) || $id_rol == 1) {
            $actualizar = '<button data-id="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 actualizar" title="Editar tipo de proceso"><span class="fas fa-pencil-alt"></span></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 5601, 4) || $id_rol == 1) {
            $eliminar = '<button data-id="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 eliminar" title="Eliminar tipo de proceso"><span class="fas fa-trash-alt"></span></button>';
        }

        $badgeActivo = $o['activo']
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-secondary">Inactivo</span>';

        $datos[] = [
            'id'          => $id,
            'nombre'      => mb_strtoupper($o['nombre']),
            'descripcion' => $o['descripcion'] ?: '—',
            'activo'      => '<div class="text-center">' . $badgeActivo . '</div>',
            'botones'     => '<div class="text-center">' . $actualizar . $eliminar . '</div>',
        ];
    }
}

echo json_encode([
    'data'            => $datos,
    'recordsFiltered' => $totalRecordsFilter,
    'recordsTotal'    => $totalRecords,
]);
