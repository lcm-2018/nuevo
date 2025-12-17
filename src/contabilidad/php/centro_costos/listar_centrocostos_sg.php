<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

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

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    //Consulta la Cuanta Vigente
    $sql = "SELECT id_cecsubgrp AS id FROM tb_centrocostos_subgr_cta
	        WHERE estado=1 AND fecha_vigencia<=DATE_FORMAT(NOW(), '%Y-%m-%d') AND id_cencos=" . $_POST['id_cencos'] . " 
            ORDER BY fecha_vigencia DESC LIMIT 1";
    $rs = $cmd->query($sql);
    $cuenta = $rs->fetch();
    $id_vig = isset($cuenta['id']) ? $cuenta['id'] : 0;

    //Consulta el total de registros de la tabla
    $sql = "SELECT COUNT(*) AS total FROM tb_centrocostos_subgr_cta WHERE id_cencos=" . $_POST['id_cencos'];
    $rs = $cmd->query($sql);
    $total = $rs->fetch();
    $totalRecords = $total['total'];
    $totalRecordsFilter = $total['total'];

    //Consulta los datos para listarlos en la tabla
    $sql = "SELECT id_cecsubgrp,fecha_vigencia,   
                IF(estado=1,'ACTIVO','INACTIVO') AS estado
            FROM tb_centrocostos_subgr_cta
            WHERE id_cencos=" . $_POST['id_cencos'] . " ORDER BY $col $dir $limit";

    $rs = $cmd->query($sql);
    $objs = $rs->fetchAll();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$data = [];
if (!empty($objs)) {
    foreach ($objs as $obj) {
        $editar = NULL;
        $eliminar = NULL;
        $id = $obj['id_cecsubgrp'];
        //Permite crear botones en la cuadricula si tiene permisos de 3-Editar,4-Eliminar
        if ($permisos->PermisosUsuario($opciones, 5508, 3) || $id_rol == 1) {
            $editar = '<a value="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow btn_editar" title="Editar"><span class="fas fa-pencil-alt "></span></a>';
        }
        if ($permisos->PermisosUsuario($opciones, 5508, 4) || $id_rol == 1) {
            $eliminar =  '<a value="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow btn_eliminar" title="Eliminar"><span class="fas fa-trash-alt "></span></a>';
        }
        $data[] = [
            "id_cecsubgrp" => $id,
            "fecha_vigencia" => $obj['fecha_vigencia'],
            "vigente" => ($id == $id_vig ? 'X' : ''),
            "estado" => $obj['estado'],
            "botones" => '<div class="text-center centro-vertical">' . $editar . $eliminar . '</div>',
        ];
    }
}
$datos = [
    "data" => $data,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecordsFilter
];

echo json_encode($datos);
