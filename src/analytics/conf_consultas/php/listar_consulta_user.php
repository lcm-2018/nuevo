<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Src\Common\Php\Clases\Permisos;
$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$limit = "";
if ($length != -1) {
    $limit = "LIMIT $start, $length";
}
$col = $_POST['order'][0]['column'] + 1;
$dir = $_POST['order'][0]['dir'];

$where = "";
if (isset($_POST['search']['value']) && $_POST['search']['value']) {
    $search = $_POST['search']['value'];
    $where .= " AND CONCAT(US.nombre1,US.nombre2,US.apellido1,US.apellido2) LIKE '%$search%'";
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $id_consulta = isset($_POST['id_consulta']) ? (int)$_POST['id_consulta'] : -1;

    //Consulta el total de registros de la tabla
    $sql = "SELECT COUNT(*) AS total FROM dash_consulta_usr WHERE id_consulta = $id_consulta";
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecords = $total['total'];

    //Consulta el total de registros aplicando el filtro
    $sql = "SELECT COUNT(*) AS total FROM dash_consulta_usr AS CU
            INNER JOIN seg_usuarios_sistema AS US ON (US.id_usuario=CU.id_usuario)
            WHERE id_consulta = $id_consulta $where";
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecordsFilter = $total['total'];

    //Consulta los datos para listarlos en la tabla
    $sql = "SELECT US.id_usuario,US.num_documento,
	                CONCAT_WS(' ',US.nombre1,US.nombre2,US.apellido1,US.apellido2) AS usuario,
                    US.descripcion AS cargo
            FROM dash_consulta_usr AS CU
            INNER JOIN seg_usuarios_sistema AS US ON (US.id_usuario=CU.id_usuario) 
            WHERE CU.id_consulta = $id_consulta $where
            ORDER BY $col $dir $limit";

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
        $eliminar = NULL;
        $id = $obj['id_usuario'];        
        $eliminar =  '<a value="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow btn_eliminar_us" title="Eliminar"><span class="fas fa-trash-alt "></span></a>';
        $data[] = [
            "id_usuario" => $id,
            "num_documento" => $obj['num_documento'],
            "usuario" => $obj['usuario'],                        
            "cargo" => $obj['cargo'],
            "botones" => '<div class="text-center centro-vertical">' . $eliminar . '</div>',
        ];
    }
}
$datos = [
    "data" => $data,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecordsFilter
];

echo json_encode($datos);
