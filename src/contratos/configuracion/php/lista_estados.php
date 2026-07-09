<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Src\Contratos\Configuracion\Php\Clases\Estados;
use Src\Common\Php\Clases\Permisos;

$id_rol    = $_SESSION['rol'];
$id_user   = $_SESSION['id_user'];
$start     = isset($_POST['start'])  ? intval($_POST['start'])  : 0;
$length    = isset($_POST['length']) ? intval($_POST['length']) : 10;
$val_busca = $_POST['search']['value'] ?? '';
$col       = ($_POST['order'][0]['column'] ?? 0) + 1;
$dir       = $_POST['order'][0]['dir'] ?? 'asc';

$clase    = new Estados();
$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$obj                = $clase->getEstados($start, $length, $val_busca, $col, $dir);
$totalRecordsFilter = $clase->getRegistrosFilter($val_busca);
$totalRecords       = $clase->getRegistrosTotal();

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $actualizar = $eliminar = '';
        $id = $o['id_estado'];

        if ($permisos->PermisosUsuario($opciones, 5601, 3) || $id_rol == 1) {
            $actualizar = '<button data-id="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 actualizar" title="Editar estado"><span class="fas fa-pencil-alt"></span></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 5601, 4) || $id_rol == 1) {
            $eliminar = '<button data-id="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 eliminar" title="Eliminar estado"><span class="fas fa-trash-alt"></span></button>';
        }

        $iconLock   = $o['permite_edicion'] ? '<i class="fas fa-lock-open text-success"></i>' : '<i class="fas fa-lock text-danger"></i>';
        $badgeColor = $o['color_badge'];

        $datos[] = [
            'id'              => $o['orden'],
            'nombre'          => '<span class="badge bg-' . $badgeColor . '">' . mb_strtoupper($o['nombre']) . '</span>',
            'permite_edicion' => '<div class="text-center">' . $iconLock . '</div>',
            'botones'         => '<div class="text-center">' . $actualizar . $eliminar . '</div>',
        ];
    }
}

echo json_encode([
    'data'            => $datos,
    'recordsFiltered' => $totalRecordsFilter,
    'recordsTotal'    => $totalRecords,
]);
