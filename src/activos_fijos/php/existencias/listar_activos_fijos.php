<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo '<script>window.location.replace("../../../index.php");</script>';
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

//Estados de Activos: 1-Activo, 2-Para mantenimiento, 3-En Mantenimiento, 4-Inactivo, 5-Dado de Baja
$where_usr = " WHERE HV.id_articulo=" . $_POST['id_articulo'] . " AND HV.estado IN (1,2,3,4)";
if (isset($_POST['id_sede']) && $_POST['id_sede']) {
    $where_usr .= " AND HV.id_sede=" . $_POST['id_sede'];
}
if (isset($_POST['id_area']) && $_POST['id_area']) {
    $where_usr .= " AND HV.id_area=" . $_POST['id_area'];
}

$where = $where_usr;
if (isset($_POST['search']['value']) && $_POST['search']['value']) {
    $search = $_POST['search']['value'];
    $where .= " AND (HV.placa LIKE '%$search%' OR HV.num_serial LIKE '%$search%' OR HV.des_activo LIKE '%$search%')";
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    //Consulta el total de registros de la tabla
    $sql = "SELECT COUNT(*) AS total FROM acf_hojavida AS HV $where_usr";
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecords = $total['total'];

    //Consulta el total de registros aplicando el filtro
    $sql = "SELECT COUNT(*) AS total FROM acf_hojavida AS HV $where";
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecordsFilter = $total['total'];

    //Consulta los datos para listarlos en la tabla
    $sql = "SELECT HV.id_activo_fijo,HV.placa,HV.num_serial,
                HV.des_activo,MA.descripcion nom_marca,HV.valor,
                SE.nom_sede,AR.nom_area,
                CONCAT_WS(' ',US.apellido1,US.apellido2,US.nombre1,US.nombre2) AS nom_responsable,
                CASE HV.estado_general WHEN 1 THEN 'BUENO' WHEN 2 THEN 'REGULAR' WHEN 3 THEN 'MALO' 
                        WHEN 4 THEN 'SIN SERVICIO' END AS nom_estado_general
            FROM acf_hojavida AS HV
            INNER JOIN acf_marca MA ON (MA.id = HV.id_marca)
            LEFT JOIN tb_sedes SE ON (SE.id_sede=HV.id_sede)
            LEFT JOIN far_centrocosto_area AR ON (AR.id_area=HV.id_area)
            LEFT JOIN seg_usuarios_sistema AS US ON (US.id_usuario=HV.id_responsable)"
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
        $data[] = [
            "id_activo_fijo" => $obj['id_activo_fijo'],
            "placa" => $obj['placa'],
            "num_serial" => $obj['num_serial'],
            "des_activo" => $obj['des_activo'],            
            "nom_marca" => $obj['nom_marca'],
            "valor" => $obj['valor'],
            "nom_sede" => $obj['nom_sede'],
            "nom_area" => $obj['nom_area'],
            "nom_responsable" => $obj['nom_responsable'],
            "nom_estado_general" => $obj['nom_estado_general']
        ];
    }
}
$datos = [
    "data" => $data,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecordsFilter
];

echo json_encode($datos);
