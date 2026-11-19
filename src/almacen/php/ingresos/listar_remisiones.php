<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';


use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
include '../common/funciones_generales.php';

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
    $where .= " AND (num_remision LIKE '%$search%' OR desc_remision LIKE '%$search%')";
}
if (isset($_POST['estado']) && $_POST['estado']) {
    $where .= " AND estado = " . $_POST['estado'];
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    //Consulta el total de registros de la tabla
    $sql = "SELECT COUNT(*) AS total FROM far_remision_interna WHERE id_tercero=" . $_POST['id_tercero'];
    $rs = $cmd->query($sql);
    $total = $rs ? $rs->fetch() : null;
    $totalRecords = $total['total'] ?? 0;

    //Consulta el total de registros aplicando el filtro
    $sql = "SELECT COUNT(*) AS total FROM far_remision_interna WHERE id_tercero=" . $_POST['id_tercero'] . $where;
    $rs = $cmd->query($sql);
    $total = $rs ? $rs->fetch() : null;
    $totalRecordsFilter = $total['total'] ?? 0;

    //Consulta los datos para listarlos en la tabla
    $sql = "SELECT id_rem_int,num_remision,desc_remision,
		        DATE_FORMAT(fec_creacion, '%Y-%m-%d') AS fec_creacion,
		        CASE estado WHEN 1 THEN 'Activa' WHEN 2 THEN 'Cerrada' END AS estado
            FROM far_remision_interna
            WHERE id_tercero=" . $_POST['id_tercero'] . $where . " ORDER BY $col $dir $limit";
    $rs = $cmd->query($sql);
    $objs = $rs->fetchAll();
    $rs->closeCursor();

    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$editar = NULL;
$eliminar = NULL;
$data = [];
if (!empty($objs)) {
    foreach ($objs as $obj) {
        $id = $obj['id_rem_int'];
        //Permite crear botones en la cuadricula si tiene permisos de 1-Consultar,2-Crear,3-Editar,4-Eliminar,5-Anular,6-Imprimir
        if ($permisos->PermisosUsuario($opciones, 5006, 3) || $id_rol == 1) {
            $editar = '<a value="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow btn_editar" title="Editar"><span class="fas fa-pencil-alt "></span></a>';
        }
        if ($permisos->PermisosUsuario($opciones, 5006, 4) || $id_rol == 1) {
            $eliminar =  '<a value="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow btn_eliminar" title="Eliminar"><span class="fas fa-trash-alt "></span></a>';
        }
        $data[] = [
            "id_rem_int" => $id,
            "fec_creacion" => $obj['fec_creacion'],
            "num_remision" => $obj['num_remision'],
            "desc_remision" => $obj['desc_remision'],            
            "estado" => $obj['estado'],
            "botones" => '<div class="text-center">' . $editar . $eliminar . '</div>'
        ];
    }
}
$datos = [
    "data" => $data,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecordsFilter
];

echo json_encode($datos);
