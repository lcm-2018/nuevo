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
                pto_crp.id_pto_crp,
                pto_crp.id_manu,
                DATE_FORMAT(pto_crp.fecha,'%Y-%m-%d') AS fecha,
                'CRP' AS tipo,
                pto_crp.num_contrato,
                SUM(IFNULL(pto_crp_detalle2.valor,0)) AS vr_crp,
                SUM(IFNULL(pto_crp_detalle2.valor_liberado,0)) AS vr_crp_liberado,
                IFNULL(cop_sum.vr_cop, 0) AS vr_cop,
                IFNULL(cop_sum.vr_cop_liberado, 0) AS vr_cop_liberado,
                (SUM(IFNULL(pto_crp_detalle2.valor,0)) - SUM(IFNULL(pto_crp_detalle2.valor_liberado,0))) AS vr_registro,
                (SUM(IFNULL(pto_crp_detalle2.valor,0)) - SUM(IFNULL(pto_crp_detalle2.valor_liberado,0))) - 
                (IFNULL(cop_sum.vr_cop, 0) - IFNULL(cop_sum.vr_cop_liberado, 0)) AS vr_saldo,
                CASE pto_crp.estado WHEN 1 THEN 'Pendiente' WHEN 2 THEN 'Cerrado' WHEN 0 THEN 'Anulado' END AS estado,
                COUNT(*) OVER() AS filas
            FROM
                (SELECT id_pto_crp, id_pto_crp_det, id_pto_cdp_det, SUM(valor) AS valor, SUM(valor_liberado) AS valor_liberado 
                FROM pto_crp_detalle 
                GROUP BY id_pto_crp, id_pto_crp_det, id_pto_cdp_det) AS pto_crp_detalle2
            INNER JOIN pto_cdp_detalle ON (pto_crp_detalle2.id_pto_cdp_det = pto_cdp_detalle.id_pto_cdp_det)
            INNER JOIN pto_crp ON (pto_crp_detalle2.id_pto_crp = pto_crp.id_pto_crp)
            LEFT JOIN (
                SELECT 
                    id_pto_crp_det,
                    SUM(valor) AS vr_cop,
                    SUM(valor_liberado) AS vr_cop_liberado
                FROM pto_cop_detalle
                GROUP BY id_pto_crp_det
            ) cop_sum ON cop_sum.id_pto_crp_det = pto_crp_detalle2.id_pto_crp_det
            WHERE pto_crp.id_cdp = $id_cdp
            AND pto_crp.estado=2
            GROUP BY pto_crp.id_pto_crp, pto_crp.id_manu, pto_crp.fecha, pto_crp.num_contrato, pto_crp.estado";

    $rs = $cmd->query($sql);
    $objs = $rs->fetchAll();
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
        $id_crp = $obj['id_pto_crp'];
        $saldo = $obj['vr_saldo'];
        $totalRecords = $obj['filas'];
        $totalRecordsFilter = $obj['filas'];

        $liberar = null;
        $liberaciones = null;

        // include '../../../permisos.php'; -> Este include se elimina y se usa la clase Permisos
        if ($permisos->PermisosUsuario($opciones, 5401, 3) || $id_rol == 1) {
            if ($saldo > 0 || $saldo < 0) {
                $liberar =  '<a value="' . $id_crp . '" class="btn btn-outline-success btn-xs rounded-circle shadow me-1 btn_liberar_crp" title="Liberar"><span class="fas fa-arrow-alt-circle-left"></span></a>';
            }
            $liberaciones =  '<a value="' . $id_crp . '" class="btn btn-outline-warning btn-xs rounded-circle shadow me-1 btn_liberaciones_crp" title="Listar liberaciones"><span class="fas fa-hand-holding-usd"></span></a>';
        }

        $data[] = [
            "id_pto_crp" => $obj['id_pto_crp'],
            "id_manu" => $obj['id_manu'],
            "fecha" => $obj['fecha'],
            "tipo" => $obj['tipo'],
            "num_contrato" => $obj['num_contrato'],
            "vr_registro" => $obj['vr_registro'],
            "vr_saldo" => $obj['vr_saldo'],
            "estado" => $obj['estado'],
            "botones" => '<div class="text-center centro-vertical">' . $liberar . $liberaciones . '</div>',
        ];
    }
}
$datos = [
    "data" => $data,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecordsFilter
];

echo json_encode($datos);
