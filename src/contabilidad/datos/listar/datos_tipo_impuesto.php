<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include_once '../../../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

// Div de acciones de la lista

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `ctb_retencion_tipo`.`id_retencion_tipo`
                , `ctb_retencion_tipo`.`tipo`
                , `ctb_retencion_tipo`.`id_tercero`
                , `ctb_retencion_tipo`.`estado`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`nit_tercero`
            FROM
                `ctb_retencion_tipo`
                LEFT JOIN `tb_terceros` 
                    ON (`ctb_retencion_tipo`.`id_tercero` = `tb_terceros`.`id_tercero_api`)";
    $rs = $cmd->query($sql);
    $tipos = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
if (!empty($tipos)) {
    foreach ($tipos as $lp) {
        $boton = $editar = $borrar = null;
        $id_tipo = $lp['id_retencion_tipo'];
        $id_tp = base64_encode($id_tipo);
        $estado = $lp['estado'];
        if (($permisos->PermisosUsuario($opciones, 5506, 3) || $id_rol == 1)) {
            $editar = '<a text="' . $id_tp . '" href="javascript:void(0)" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow editar"  title="Editar Tipo Retención"><span class="fas fa-pencil-alt "></span></a>';
            $st = base64_encode($id_tipo . '|' . $estado);
            if ($estado == '1') {
                $title = 'Activo';
                $icono = 'on';
                $color = 'success';
            } else {
                $title = 'Inactivo';
                $icono = 'off';
                $color = 'secondary';
            }
            $boton = '<a text="' . $st . '" href="javascript:void(0)" class="estado" title="' . $title . '"><span class="fas fa-toggle-' . $icono . ' fa-lg text-' . $color . '"></span></a>';
        }
        if ($permisos->PermisosUsuario($opciones, 5506, 4) || $id_rol == 1) {
            $borrar = '<a text="' . $id_tp . '" href="javascript:void(0)" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow borrar"  title="Eliminar"><span class="fas fa-trash-alt "></span></a>';
        }

        if ($estado == 0) {
            $editar =  $borrar = NULL;
        }
        $data[] = [

            'id' => $lp['id_retencion_tipo'],
            'tipo' => $lp['tipo'],
            'tercero' => $lp['nom_tercero'],
            'estado' => '<div class="text-center">' . $boton . '</div>',
            'botones' => '<div class="text-center" style="position:relative">' . $editar . $borrar . '</div>',
        ];
    }
} else {
    $data = [];
}
$datos = [
    'data' => $data
];


echo json_encode($datos);
