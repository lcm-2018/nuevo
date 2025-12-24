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
    $where .= " AND far_medicamento_cum.cum LIKE '%" . $_POST['search']['value'] . "%'";
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    //Consulta el total de registros de la tabla
    $sql = "SELECT COUNT(*) AS total FROM far_medicamento_cum WHERE id_med=" . $_POST['id_articulo'];
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecords = $total['total'];

    //Consulta el total de registros aplicando el filtro
    $sql = "SELECT COUNT(*) AS total FROM far_medicamento_cum WHERE id_med=" . $_POST['id_articulo'] . $where;
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecordsFilter = $total['total'];

    //Consulta los datos para listarlos en la tabla
    $sql = "SELECT far_medicamento_cum.id_cum,far_medicamento_cum.cum,far_medicamento_cum.ium,
	            far_laboratorios.nom_laboratorio,far_medicamento_cum.reg_invima,far_medicamento_cum.fec_invima,
                CASE far_medicamento_cum.estado_invima WHEN 1 THEN 'VIGENTE' WHEN 2 THEN 'PROCESO DE RENOVACION' WHEN 3 THEN 'NO VIGENTE' ELSE '' END AS estado_invima,
                far_presentacion_comercial.nom_presentacion,
                IF(far_medicamento_cum.estado=1,'ACTIVO','INACTIVO') AS estado
            FROM far_medicamento_cum
            INNER JOIN far_laboratorios ON (far_laboratorios.id_lab=far_medicamento_cum.id_lab)
            INNER JOIN far_presentacion_comercial ON (far_presentacion_comercial.id_prescom=far_medicamento_cum.id_prescom)
            WHERE far_medicamento_cum.id_med=" . $_POST['id_articulo'] . $where . " ORDER BY $col $dir $limit";

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
        $id = $obj['id_cum'];
        //Permite crear botones en la cuadricula si tiene permisos de 3-Editar,4-Eliminar
        if ($permisos->PermisosUsuario($opciones, 5002, 3) || $id_rol == 1) {
            $editar = '<a value="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow btn_editar" title="Editar"><span class="fas fa-pencil-alt "></span></a>';
        }
        if ($permisos->PermisosUsuario($opciones, 5002, 4) || $id_rol == 1) {
            $eliminar =  '<a value="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow btn_eliminar" title="Eliminar"><span class="fas fa-trash-alt "></span></a>';
        }
        $data[] = [
            "id_cum" => $id,
            "cum" => $obj['cum'],
            "ium" => $obj['ium'],
            "nom_laboratorio" => $obj['nom_laboratorio'],
            "reg_invima" => $obj['reg_invima'],
            "fec_invima" => $obj['fec_invima'],
            "estado_invima" => $obj['estado_invima'],
            "nom_presentacion" => $obj['nom_presentacion'],
            "estado" => $obj['estado'],
            "botones" => '<div class="text-center">' . $editar . $eliminar . '</div>',
        ];
    }
}
$datos = [
    "data" => $data,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecordsFilter
];

echo json_encode($datos);
