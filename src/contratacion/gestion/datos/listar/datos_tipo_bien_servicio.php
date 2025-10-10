<?php

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}

include_once '../../../../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;
use Config\Clases\Conexion;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$cmd = Conexion::getConexion();

try {
    $sql = "SELECT `id_tipo_b_s`, `tipo_compra`, `tipo_bn_sv`
            FROM
                `tb_tipo_bien_servicio`
            INNER JOIN `tb_tipo_compra` 
                ON (`tb_tipo_bien_servicio`.`id_tipo` = `tb_tipo_compra`.`id_tipo`)";
    $rs = $cmd->query($sql);
    $tipobnsv = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
if (!empty($tipobnsv)) {
    foreach ($tipobnsv as $tbs) {
        $borrar = $editar = null;
        $id_tbs = $tbs['id_tipo_b_s'];
        if ($permisos->PermisosUsuario($opciones, 5301, 3) || $id_rol == 1) {
            $editar = '<a value="' . $id_tbs . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 editar" title="Editar"><span class="fas fa-pencil-alt"></span></a>';
        }
        if ($permisos->PermisosUsuario($opciones, 5301, 4) || $id_rol == 1) {
            $borrar = '<a value="' . $id_tbs . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 borrar" title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
        }
        $data[] = [
            'tipo_compra' => $tbs['tipo_compra'],
            'tipo_bs' => $tbs['tipo_bn_sv'],
            'botones' => '<div class="text-center">' . $borrar . $editar . '</div>',
        ];
    }
} else {
    $data = [];
}

$datos = ['data' => $data];

echo json_encode($datos);
