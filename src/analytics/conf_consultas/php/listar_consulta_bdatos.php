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

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $id_consulta = isset($_POST['id_consulta']) ? (int)$_POST['id_consulta'] : -1;

    //Consulta el total de registros de la tabla
    $sql = "SELECT COUNT(*) AS total FROM dash_bdatos";
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecords = $total['total'];
    $totalRecordsFilter = $total['total'];

    //Consulta los datos para listarlos en la tabla
    $sql = "SELECT BD.id_bdatos,BD.nombre_entidad,BD.nombre_bd,
                IF(CB.id_bdatos IS NULL, 0, 1) AS estado
            FROM dash_bdatos AS BD
            LEFT JOIN dash_consulta_bd AS CB ON (BD.id_bdatos = CB.id_bdatos AND CB.id_consulta = $id_consulta)
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
        $boton = NULL;
        $id = $obj['id_bdatos'];        
        //Coloca Activar o Inactivar la base de datos para la consulta
        if ($obj['estado'] == 1){
            $boton = '<a value="' . $id . '" class="btn btn-link p-0 m-0 btn_inactivar" title="Inactivar"><span class="fas fa-toggle-on fa-sm text-success"></span></a>';
        } else {
            $boton = '<a value="' . $id . '" class="btn btn-link p-0 m-0 btn_activar" title="Activar"><span class="fas fa-toggle-off fa-sm text-secondary"></span></a>';
        }        
        $data[] = [
            "id_bdatos" => $id,
            "nombre_bd" => $obj['nombre_bd'],
            "nombre_entidad" => $obj['nombre_entidad'],                        
            "botones" => '<div class="text-center centro-vertical">' . $boton . '</div>',
        ];
    }
}
$datos = [
    "data" => $data,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecordsFilter
];

echo json_encode($datos);
