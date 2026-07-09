<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Src\Contratos\Contratos\Php\Clases\Contratos;
use Src\Common\Php\Clases\Permisos;

$id_rol   = $_SESSION['rol'];
$id_user  = $_SESSION['id_user'];
$start    = isset($_POST['start'])  ? intval($_POST['start'])  : 0;
$length   = isset($_POST['length']) ? intval($_POST['length']) : 10;
$val_busca = $_POST['search']['value'] ?? '';
$col      = ($_POST['order'][0]['column'] ?? 0) + 1;
$dir      = $_POST['order'][0]['dir'] ?? 'desc';

$clase    = new Contratos();
$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$obj                = $clase->getContratos($start, $length, $val_busca, $col, $dir);
$totalRecordsFilter = $clase->getRegistrosFilter($val_busca);
$totalRecords       = $clase->getRegistrosTotal();

$datos = [];
if (!empty($obj)) {
    foreach ($obj as $o) {
        $actualizar = $eliminar = $pdf = '';
        $id = $o['id_contrato'];

        // Permiso 5803 = contratos, nivel 3=editar, 4=eliminar
        if ($permisos->PermisosUsuario($opciones, 5803, 3) || $id_rol == 1) {
            $actualizar = '<button data-id="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 actualizar" title="Editar contrato"><span class="fas fa-pencil-alt"></span></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 5803, 4) || $id_rol == 1) {
            $eliminar = '<button data-id="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 eliminar" title="Eliminar contrato"><span class="fas fa-trash-alt"></span></button>';
        }

        // Botón PDF
        $pdf = '<a href="../pdf/exportar_contrato.php?id=' . $id . '" target="_blank" class="btn btn-outline-secondary btn-xs rounded-circle shadow me-1" title="Ver PDF"><span class="fas fa-file-pdf"></span></a>';


        $badgeColor = $o['color_badge'];
        $estado = '<span class="badge bg-' . $badgeColor . '">' . $o['estado'] . '</span>';
        $nit = $o['nit_tercero'] ?: 'N/A';

        $datos[] = [
            'id'          => $id,
            'codigo'      => '<span class="fw-bold text-success">' . $o['codigo_contrato'] . '</span>',
            'proceso'     => '<span class="fw-bold text-primary">' . $o['codigo_proceso'] . '</span>',
            'contratista' => $o['contratista'] . '<br><small class="text-muted">NIT: ' . $nit . '</small>',
            'fechas'      => 'Inicio: ' . $o['fec_inicio'] . '<br>Fin: ' . $o['fec_fin'],
            'valor'       => '$' . number_format($o['valor_total'], 2),
            'estado'      => '<div class="text-center">' . $estado . '</div>',
            'botones'     => '<div class="text-center">' . $pdf . $actualizar . $eliminar . '</div>',
        ];
    }
}

echo json_encode([
    'data'            => $datos,
    'recordsFiltered' => $totalRecordsFilter,
    'recordsTotal'    => $totalRecords,
]);
