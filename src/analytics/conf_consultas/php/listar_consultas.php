<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;
use Src\Analytics\Conf_Consultas\Php\Clases\ConsultasModel;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$col = $_POST['order'][0]['column'] + 1;
$dir = $_POST['order'][0]['dir'];

// Filtros de la consulta
$filters = [
    'titulo' => isset($_POST['titulo']) ? trim($_POST['titulo']) : '',
    'estado' => isset($_POST['estado']) ? $_POST['estado'] : '',
];  

try {
    $permisos = new Permisos();
    $opciones = $permisos->PermisoOpciones($id_user);

    $model = new ConsultasModel();
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
        $id = $obj['id_consulta'];
        /*Permisos del usuario
           3001-Opcion [Configuración][Consultas Analíticas]
            1-Consultar, 2-Adicionar, 3-Modificar, 4-Eliminar, 5-Anular, 6-Imprimir
        */
        if ($permisos->PermisosUsuario($opciones, 3001, 3) || $id_rol == 1) {
            $editar = '<a value="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow btn_editar" title="Editar"><span class="fas fa-pencil-alt "></span></a>';
        }
        if ($permisos->PermisosUsuario($opciones, 3001, 4) || $id_rol == 1) {
            $eliminar =  '<a value="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow btn_eliminar" title="Eliminar"><span class="fas fa-trash-alt "></span></a>';
        }
        $data[] = [
            "id_consulta" => $id,
            "titulo_consulta" => mb_strtoupper($obj['titulo_consulta']),
            "tipo_analitica" => $obj['tipo_analitica'],
            "tipo_bdatosb" => $obj['tipo_bdatosb'],
            "tipo_informe" => $obj['tipo_informe'],
            "tipo_consulta" => $obj['tipo_consulta'],
            "tipo_acceso" => $obj['tipo_acceso'],
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
