<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include_once '../../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$opciones = $permisos->PermisoOpciones($id_user);

$id_cdp = $_POST['id_cdp'];

$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$limit = "";
if ($length != -1) {
    $limit = "LIMIT $start, $length";
}
$col = $_POST['order'][0]['column'] + 1;
$dir = $_POST['order'][0]['dir'];

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                id_pto_cdp_det
                , DATE_FORMAT(fecha_libera,'%Y-%m-%d') AS fecha
                , concepto_libera
                , valor_liberado    
            FROM pto_cdp_detalle
            WHERE id_pto_cdp = $id_cdp
            AND valor_liberado > 0 ";

    $rs = $cmd->query($sql);
    $objs = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$totalRecords = 0;
$totalRecordsFilter = 0;

$data = [];
if (!empty($objs)) {
    foreach ($objs as $obj) {
        $totalRecords = 0;
        $totalRecordsFilter = 0;

        $anular = null;
        $imprimir = null;
        // include '../../../permisos.php'; -> Este include se elimina y se usa la clase Permisos
        //5401 presupuesto - gestion
        if ($permisos->PermisosUsuario($opciones, 5401, 3) || $id_rol == 1) {
            $anular = '<a value="' . $obj['id_pto_cdp_det'] . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 btn_anular_liberacion_cdp" title="Anular liberacion"><span class="fas fa-trash-restore"></span></a>';
            $imprimir = '<a value="' . $obj['id_pto_cdp_det'] . '" class="btn btn-outline-success btn-xs rounded-circle shadow me-1 btn_imprimir_liberacion_cdp" title="Imprimir liberacion"><span class="fas fa-print"></span></a>';
        }

        $data[] = [
            "id_pto_cdp_det" => $obj['id_pto_cdp_det'],
            "fecha" => $obj['fecha'],
            "concepto_libera" => mb_strtoupper($obj['concepto_libera']),
            "valor_liberado" => $obj['valor_liberado'],
            "botones" => '<div class="text-center centro-vertical">' . $imprimir . $anular . '</div>',
        ];
    }
}
$datos = [
    "data" => $data,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecordsFilter
];

echo json_encode($datos);
