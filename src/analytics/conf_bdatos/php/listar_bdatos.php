<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;
use Src\Analytics\Conf_Bdatos\Php\Clases\BdatosModel;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$col = $_POST['order'][0]['column'] + 1;
$dir = $_POST['order'][0]['dir'];

// Filtros de la consulta
$filters = [
    'nombre' => isset($_POST['nombre']) ? trim($_POST['nombre']) : '',
    'estado' => isset($_POST['estado']) ? $_POST['estado'] : '',
];  

try {
    $permisos = new Permisos();
    $opciones = $permisos->PermisoOpciones($id_user);

    $model = new BdatosModel();
    $totalRecords = $model->countAll();
    $totalRecordsFilter = $model->countFiltered($filters);
    $objs = $model->fetchList($filters, $start, $length, $col, $dir);
    
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$editar = NULL;
$eliminar = NULL;
$data = [];
if (!empty($objs)) {
    foreach ($objs as $obj) {
        $id = $obj['id_entidad'];
        /*Permisos del usuario
           3002-Opcion [Configuración][Sedes-Bases de Datos]
            1-Consultar, 2-Adicionar, 3-Modificar, 4-Eliminar, 5-Anular, 6-Imprimir
        */
        if ($permisos->PermisosUsuario($opciones, 3002, 3) || $id_rol == 1) {
            $editar = '<a value="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow btn_editar" title="Editar"><span class="fas fa-pencil-alt "></span></a>';
        }
        if ($permisos->PermisosUsuario($opciones, 3002, 4) || $id_rol == 1) {
            $eliminar =  '<a value="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow btn_eliminar" title="Eliminar"><span class="fas fa-trash-alt "></span></a>';
        }
        $data[] = [
            "id_entidad" => $id,
            "nombre_entidad" => mb_strtoupper($obj['nombre_entidad']),
            "descri_entidad" => mb_strtoupper($obj['descri_entidad']),
            "ip_servidor" => $obj['ip_servidor'],
            "nombre_bd" => $obj['nombre_bd'],
            "puerto_bd" => $obj['puerto_bd'],
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
