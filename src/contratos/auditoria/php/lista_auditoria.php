<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Src\Contratos\Auditoria\Php\Clases\Auditoria;
use Src\Common\Php\Clases\Permisos;

$id_rol   = $_SESSION['rol'];
$id_user  = $_SESSION['id_user'];
$start    = isset($_POST['start'])  ? intval($_POST['start'])  : 0;
$length   = isset($_POST['length']) ? intval($_POST['length']) : 10;
$val_busca = $_POST['search']['value'] ?? '';
$col      = ($_POST['order'][0]['column'] ?? 0) + 1;
$dir      = $_POST['order'][0]['dir'] ?? 'desc';

$clase    = new Auditoria();
$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$obj                = $clase->getAuditoria($start, $length, $val_busca, $col, $dir);
$totalRecordsFilter = $clase->getRegistrosFilter($val_busca);
$totalRecords       = $clase->getRegistrosTotal();

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $id = $o['id_auditoria'];

        // Solo el admin o rol específico puede ver detalles, pero lo dejamos básico
        $ver = '<button data-id="' . $id . '" class="btn btn-outline-info btn-xs rounded-circle shadow me-1 ver" title="Ver detalle"><span class="fas fa-eye"></span></button>';

        $datos[] = [
            'id'       => $id,
            'fecha'    => $o['fec_reg'],
            'usuario'  => $o['usuario'],
            'modulo'   => '<span class="fw-bold">' . mb_strtoupper($o['modulo']) . '</span>',
            'accion'   => mb_strtoupper($o['accion']),
            'id_reg'   => $o['id_reg'],
            'botones'  => '<div class="text-center">' . $ver . '</div>',
        ];
    }
}

echo json_encode([
    'data'            => $datos,
    'recordsFiltered' => $totalRecordsFilter,
    'recordsTotal'    => $totalRecords,
]);
