<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include_once '../../../config/autoloader.php';

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
                COUNT(*) AS filas
                ,pto_cdp_detalle2.id_pto_cdp
                ,pto_cdp_detalle2.id_rubro
                ,pto_cargue.cod_pptal
                ,pto_cdp_detalle2.id_pto_cdp_det
                ,SUM(pto_cdp_detalle2.valor) AS valorcdp
                ,SUM(pto_cdp_detalle2.valor_liberado) AS cdpliberado
                ,SUM(pto_crp_detalle.valor) AS valorcrp
                ,SUM(IFNULL(pto_crp_detalle.valor_liberado,0)) AS crpliberado
                ,((SUM(pto_cdp_detalle2.valor) - SUM(pto_cdp_detalle2.valor_liberado)) - (SUM(pto_crp_detalle.valor) - SUM(IFNULL(pto_crp_detalle.valor_liberado,0)))) AS saldo_final
            FROM
                pto_crp_detalle
                INNER JOIN (SELECT id_pto_cdp,id_rubro,id_pto_cdp_det,SUM(valor) AS valor,SUM(valor_liberado) AS valor_liberado FROM pto_cdp_detalle GROUP BY id_pto_cdp) AS pto_cdp_detalle2 ON (pto_cdp_detalle2.id_pto_cdp_det = pto_crp_detalle.id_pto_cdp_det)
                INNER JOIN pto_cargue ON (pto_cdp_detalle2.id_rubro = pto_cargue.id_cargue)
            WHERE pto_cdp_detalle2.id_pto_cdp = $id_cdp
            GROUP BY pto_cdp_detalle2.id_pto_cdp,pto_cdp_detalle2.id_rubro ";

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
        $totalRecords = $obj['filas'];
        $totalRecordsFilter = $obj['filas'];

        $data[] = [
            "id_rubro" => $obj['id_rubro'],
            "cod_pptal" => $obj['cod_pptal'],
            "saldo_final" => $obj['saldo_final'],
        ];
    }
}
$datos = [
    "data" => $data,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecordsFilter
];

echo json_encode($datos);
