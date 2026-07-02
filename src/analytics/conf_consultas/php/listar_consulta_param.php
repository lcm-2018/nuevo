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

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $id_consulta = isset($_POST['id_consulta']) ? (int)$_POST['id_consulta'] : -1;

    $sql = "SELECT id_parametro,parametro,etiqueta,tipo
                ,CASE tipo WHEN 1 THEN 'Texto' WHEN 2 THEN 'Número' WHEN 3 THEN 'Fecha' WHEN 4 THEN 'Lista' END AS nom_tipo
        FROM dash_consulta_param
        WHERE id_consulta = $id_consulta 
        ORDER BY parametro ASC";

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
        $id = $obj['id_parametro'];        
        $editar = '<a value="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow btn_editar_param" title="Editar"><span class="fas fa-pencil-alt "></span></a>';
        $eliminar =  '<a value="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow btn_eliminar_param" title="Eliminar"><span class="fas fa-trash-alt "></span></a>';
        $data[] = [
            "id_parametro" => $id,
            "parametro" => $obj['parametro'],
            "etiqueta" => $obj['etiqueta'],
            "tipo" => $obj['tipo'],
            "nom_tipo" => $obj['nom_tipo'],
            "botones" => '<div class="text-center centro-vertical">' . $editar . $eliminar . '</div>',
        ];
    }
}
echo json_encode(["data" => $data]);
