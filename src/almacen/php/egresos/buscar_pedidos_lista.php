<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
include '../common/funciones_generales.php';

$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$limit = "";
if ($length != -1) {
    $limit = "LIMIT $start, $length";
}
$col = $_POST['order'][0]['column'] + 1;
$dir = $_POST['order'][0]['dir'];
$idusr = $_SESSION['id_user'];
$idrol = $_SESSION['rol'];

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    $cmd->exec("DROP TEMPORARY TABLE IF EXISTS tmp_egresos");
    $sql = "CREATE TEMPORARY TABLE tmp_egresos AS
            SELECT EED.id_ped_detalle,SUM(EED.cantidad) AS cantidad
            FROM far_orden_egreso_detalle AS EED
            INNER JOIN far_orden_egreso AS EE ON (EE.id_egreso = EED.id_egreso)
            WHERE EE.estado <> 0 AND EED.id_ped_detalle IS NOT NULL
            GROUP BY EED.id_ped_detalle";
    $cmd->exec($sql);

    $where_usr = " WHERE PP.estado=2";
    if (isset($_POST['ped_parcial']) && $_POST['ped_parcial']) {
        $where_usr .= " AND PP.id_pedido NOT IN 
                        (SELECT far_cec_pedido_detalle.id_pedido
                        FROM far_cec_pedido_detalle
                        LEFT JOIN tmp_egresos ON (tmp_egresos.id_ped_detalle=far_cec_pedido_detalle.id_ped_detalle)
                        GROUP BY far_cec_pedido_detalle.id_pedido
                        HAVING SUM(IF(far_cec_pedido_detalle.cantidad>IFNULL(tmp_egresos.cantidad,0),1,0))=0)";
    } else {
        $where_usr .= " AND PP.id_pedido NOT IN 
                        (SELECT far_cec_pedido_detalle.id_pedido 
                        FROM far_cec_pedido_detalle 
                        INNER JOIN tmp_egresos ON (tmp_egresos.id_ped_detalle=far_cec_pedido_detalle.id_ped_detalle))";
    }

    if ($idrol != 1) {
        $where_usr .= " AND PP.id_bodega IN (SELECT id_bodega FROM seg_bodegas_usuario WHERE id_usuario=$idusr)";
    }

    $where = $where_usr;
    if (isset($_POST['num_pedido']) && $_POST['num_pedido']) {
        $where .= " AND PP.num_pedido='" . $_POST['num_pedido'] . "'";
    }
    if (isset($_POST['fec_ini']) && $_POST['fec_ini'] && isset($_POST['fec_fin']) && $_POST['fec_fin']) {
        $where .= " AND PP.fec_pedido BETWEEN '" . $_POST['fec_ini'] . "' AND '" . $_POST['fec_fin'] . "'";
    }

    //Consulta el total de registros de la tabla
    $sql = "SELECT COUNT(*) AS total FROM far_cec_pedido AS PP" . $where_usr;
    $rs = $cmd->query($sql);
    $total = $rs ? $rs->fetch() : null;
    $totalRecords = $total['total'] ?? 0;

    //Consulta el total de registros aplicando el filtro
    $sql = "SELECT COUNT(*) AS total FROM far_cec_pedido AS PP" . $where;
    $rs = $cmd->query($sql);
    $total = $rs ? $rs->fetch() : null;
    $totalRecordsFilter = $total['total'] ?? 0;

    //Consulta los datos para listarlos en la tabla
    $sql = "SELECT PP.*,
                CC.nom_centro,SE.nom_sede,BO.nombre AS nom_bodega
            FROM far_cec_pedido AS PP
            INNER JOIN tb_centrocostos AS CC ON (CC.id_centro = PP.id_cencosto)      
            INNER JOIN tb_sedes AS SE ON (SE.id_sede = PP.id_sede)
            INNER JOIN far_bodegas AS BO ON (BO.id_bodega = PP.id_bodega)"
        . $where . " ORDER BY $col $dir $limit";
    $rs = $cmd->query($sql);
    $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$data = [];
if (!empty($objs)) {
    foreach ($objs as $obj) {
        $id = $obj['id_pedido'];
        $imprimir =  '<a value="' . $id . '" class="btn btn-outline-success btn-xs rounded-circle me-1 shadow btn_imprimir" title="imprimir"><span class="fas fa-print "></span></a>';
        $data[] = [
            "id_pedido" => $id,
            "num_pedido" => $obj['num_pedido'],
            "fec_pedido" => $obj['fec_pedido'],
            "detalle" => $obj['detalle'],
            "id_cencosto" => $obj['id_cencosto'],
            "nom_centro" => $obj['nom_centro'],
            "id_sede" => $obj['id_sede'],
            "nom_sede" => $obj['nom_sede'],
            "id_bodega" => $obj['id_bodega'],
            "nom_bodega" => $obj['nom_bodega'],
            "botones" => '<div class="text-center">' . $imprimir . '</div>'
        ];
    }
}
$datos = [
    "data" => $data,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecordsFilter
];

echo json_encode($datos);
