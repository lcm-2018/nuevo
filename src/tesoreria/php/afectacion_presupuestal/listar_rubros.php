<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;

$id_rol = (int) $_SESSION['rol'];
$id_user = (int) $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$id_pto_rad = isset($_POST['id_pto_rad']) ? (int) $_POST['id_pto_rad'] : 0;
$draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
$start = isset($_POST['start']) ? max(0, (int) $_POST['start']) : 0;
$length = isset($_POST['length']) ? (int) $_POST['length'] : 10;

$columnas = array(
    0 => 'pto_rad_detalle.id_pto_rad_det',
    1 => 'pto_cargue.cod_pptal',
    2 => 'pto_rad_detalle.valor',
);
$orderIndex = isset($_POST['order'][0]['column']) ? (int) $_POST['order'][0]['column'] : 0;
$orderDir = isset($_POST['order'][0]['dir']) && strtolower($_POST['order'][0]['dir']) === 'asc' ? 'ASC' : 'DESC';
$orderBy = isset($columnas[$orderIndex]) ? $columnas[$orderIndex] : $columnas[0];

$puedeEliminar = $id_rol === 1 || $permisos->PermisosUsuario($opciones, 5401, 4) || $permisos->PermisosUsuario($opciones, 5601, 4);

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT COUNT(*) AS total
            FROM pto_rad_detalle
            LEFT JOIN pto_cargue ON pto_rad_detalle.id_rubro = pto_cargue.id_cargue
            WHERE pto_rad_detalle.id_pto_rad = :id_pto_rad";
    $stmt = $cmd->prepare($sql);
    $stmt->execute([':id_pto_rad' => $id_pto_rad]);
    $totalRecords = (int) $stmt->fetchColumn();

    $limit = '';
    if ($length !== -1) {
        $limit = " LIMIT $start, $length";
    }

    $sql = "SELECT
                pto_rad_detalle.id_pto_rad_det,
                pto_rad_detalle.id_rubro,
                pto_cargue.cod_pptal,
                pto_cargue.nom_rubro,
                pto_rad_detalle.valor
            FROM pto_rad_detalle
            LEFT JOIN pto_cargue ON pto_rad_detalle.id_rubro = pto_cargue.id_cargue
            WHERE pto_rad_detalle.id_pto_rad = :id_pto_rad
            ORDER BY $orderBy $orderDir $limit";
    $stmt = $cmd->prepare($sql);
    $stmt->execute([':id_pto_rad' => $id_pto_rad]);
    $obj_rubros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo json_encode([
        "draw" => $draw,
        "data" => [],
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "error" => $e->getCode() == 2002 ? 'Sin Conexion a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage(),
    ]);
    exit();
}

$data = [];
if (!empty($obj_rubros)) {
    foreach ($obj_rubros as $obj) {
        $botonEliminar = '';
        if ($puedeEliminar) {
            $botonEliminar = '<a value="' . (int) $obj['id_pto_rad_det'] . '" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow btn_eliminar_rubro" title="Eliminar"><span class="fas fa-minus"></span></a>';
        }

        $data[] = [
            "id_pto_rad_det" => (int) $obj['id_pto_rad_det'],
            "rubro" => ($obj['cod_pptal'] !== null || $obj['nom_rubro'] !== null)
                ? trim(($obj['cod_pptal'] ?? 'SIN CODIGO') . " - " . mb_strtoupper($obj['nom_rubro'] ?? 'RUBRO SIN NOMBRE'))
                : 'RUBRO NO ENCONTRADO (ID ' . (int) $obj['id_rubro'] . ')',
            "valor" => $obj['valor'],
            "botones" => '<div class="text-center centro-vertical">' . $botonEliminar . '</div>',
        ];
    }
}

echo json_encode([
    "draw" => $draw,
    "data" => $data,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecords,
]);
