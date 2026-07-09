<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Src\Contratos\Aprobaciones\Php\Clases\Aprobaciones;
use Src\Common\Php\Clases\Permisos;

$id_rol   = $_SESSION['rol'];
$id_user  = $_SESSION['id_user'];
$start    = isset($_POST['start'])  ? intval($_POST['start'])  : 0;
$length   = isset($_POST['length']) ? intval($_POST['length']) : 10;
$val_busca = $_POST['search']['value'] ?? '';
$col      = ($_POST['order'][0]['column'] ?? 0) + 1;
$dir      = $_POST['order'][0]['dir'] ?? 'desc';

$clase    = new Aprobaciones();
$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$obj                = $clase->getAprobaciones($start, $length, $val_busca, $col, $dir);
$totalRecordsFilter = $clase->getRegistrosFilter($val_busca);
$totalRecords       = $clase->getRegistrosTotal();

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $actualizar = $eliminar = '';
        $id = $o['id_aprobacion'];

        // Permiso 5805 = aprobaciones, nivel 3=editar, 4=eliminar
        if ($permisos->PermisosUsuario($opciones, 5805, 3) || $id_rol == 1) {
            $actualizar = '<button data-id="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 actualizar" title="Editar aprobación"><span class="fas fa-pencil-alt"></span></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 5805, 4) || $id_rol == 1) {
            $eliminar = '<button data-id="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 eliminar" title="Eliminar aprobación"><span class="fas fa-trash-alt"></span></button>';
        }

        $estadoStr = $o['estado'] == 1 
            ? '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Aprobado</span>' 
            : '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Rechazado</span>';

        $datos[] = [
            'id'            => $id,
            'fecha'         => $o['fec_aprobacion'],
            'aprobador'     => $o['aprobador'] . '<br><small class="text-muted">' . $o['rol_aprobador'] . '</small>',
            'decision'      => '<div class="text-center">' . $estadoStr . '</div>',
            'proceso_ctt'   => 'Proc: ' . ($o['codigo_proceso'] ?: 'N/A') . '<br>Ctt: ' . ($o['codigo_contrato'] ?: 'N/A'),
            'observaciones' => $o['observaciones'],
            'botones'       => '<div class="text-center">' . $actualizar . $eliminar . '</div>',
        ];
    }
}

echo json_encode([
    'data'            => $datos,
    'recordsFiltered' => $totalRecordsFilter,
    'recordsTotal'    => $totalRecords,
]);
