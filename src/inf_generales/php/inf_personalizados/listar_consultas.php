<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$limit = "";
if ($length != -1) {
    $limit = "LIMIT $start, $length";
}
$col = $_POST['order'][0]['column'] + 1;
$dir = $_POST['order'][0]['dir'];

$id_opcion = isset($_POST['id_opcion']) ? $_POST['id_opcion'] : 0;
$where_tc = "WHERE tb_consultas_sql.tipo=1 AND tb_consultas_sql.id_opcion=$id_opcion";
$where = $where_tc;
if (isset($_POST['nombre']) && $_POST['nombre']) {
    $where .= " AND (tb_consultas_sql.nom_consulta LIKE '%" . $_POST['nombre'] . "%' OR 
                     tb_consultas_sql.des_consulta LIKE '%" . $_POST['nombre'] . "%' OR 
                     tb_consultas_sql.consulta LIKE '%" . $_POST['nombre'] . "%')";
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();


    //Consulta el total de registros de la tabla
    $sql = "SELECT COUNT(*) AS total FROM tb_consultas_sql $where_tc";
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecords = $total['total'];

    //Consulta el total de registros aplicando el filtro
    $sql = "SELECT COUNT(*) AS total FROM tb_consultas_sql $where";
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecordsFilter = $total['total'];

    //Consulta los datos para listarlos en la tabla
    $sql = "SELECT id_consulta,nom_consulta FROM tb_consultas_sql    
            $where ORDER BY $col $dir $limit";
    $rs = $cmd->query($sql);
    $objs = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$data = [];
if (!empty($objs)) {
    foreach ($objs as $obj) {
        $id = $obj['id_consulta'];
        $data[] = [
            "id_consulta" => $id,
            "nom_consulta" => mb_strtoupper($obj['nom_consulta'])
        ];
    }
}
$datos = [
    "data" => $data,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecordsFilter
];

echo json_encode($datos);
