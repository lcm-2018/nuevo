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

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$vista = "SELECT dash_consultas.id_consulta,dash_consultas.titulo_consulta,dash_consultas.tipo_acceso,dash_consulta_usr.id_usuario
        FROM dash_consultas
        LEFT JOIN dash_consulta_usr ON (dash_consulta_usr.id_consulta = dash_consultas.id_consulta AND dash_consulta_usr.id_usuario=$id_user)
        WHERE dash_consultas.estado = 1 AND (dash_consultas.tipo_acceso = 1 OR dash_consulta_usr.id_usuario IS NOT NULL OR $id_rol = 1)";

$where = " WHERE 1=1";
if (isset($_POST['id']) && $_POST['id']) {
    $where .= " AND id_consulta = " . intval($_POST['id']);
}
if (isset($_POST['nombre']) && $_POST['nombre']) {
    $where .= " AND titulo_consulta LIKE '%" . $_POST['nombre'] . "%'";
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    //Consulta el total de registros de la tabla
    $sql = "SELECT COUNT(*) AS total FROM ($vista) AS t";
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecords = $total['total'];

    //Consulta el total de registros aplicando el filtro
    $sql = "SELECT COUNT(*) AS total FROM ($vista) AS t $where";
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecordsFilter = $total['total'];

    //Consulta los datos para listarlos en la tabla
    $sql = "SELECT id_consulta,titulo_consulta FROM ($vista) AS t $where ORDER BY $col $dir $limit";
    $rs = $cmd->query($sql);
    $objs = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$acceder = NULL;
$data = [];
if (!empty($objs)) {
    foreach ($objs as $obj) {
        $id = $obj['id_consulta'];     
        $acceder =  '<a value="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow btn_acceder" title="Acceder"><span class="fas fa-images"></span></a>';   
        $data[] = [
            "id_consulta" => $id,
            "titulo_consulta" => mb_strtoupper($obj['titulo_consulta']),
            "botones" => '<div class="text-center">' . $acceder . '</div>'
        ];
    }
}
$datos = [
    "data" => $data,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecordsFilter
];

echo json_encode($datos);
