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

function pesos($valor)
{
    return '$ ' . number_format($valor, 0, '', '.');
}

$id_vigencia = $_SESSION['id_vigencia'];

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `ctb_retencion_rango`.`id_rango`
                , `ctb_retencion_tipo`.`tipo`
                , `ctb_retenciones`.`nombre_retencion`
                , `ctb_retencion_rango`.`valor_base`
                , `ctb_retencion_rango`.`valor_tope`
                , `ctb_retencion_rango`.`tarifa`
                , `ctb_retencion_rango`.`estado`
            FROM
                `ctb_retencion_rango`
                INNER JOIN `ctb_retenciones` 
                    ON (`ctb_retencion_rango`.`id_retencion` = `ctb_retenciones`.`id_retencion`)
                INNER JOIN `ctb_retencion_tipo` 
                    ON (`ctb_retenciones`.`id_retencion_tipo` = `ctb_retencion_tipo`.`id_retencion_tipo`)
            WHERE (`ctb_retencion_rango`.`id_vigencia` = $id_vigencia)";
    $rs = $cmd->query($sql);
    $rangos = $rs->fetchAll();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
$data = [];
if (!empty($rangos)) {
    foreach ($rangos as $lp) {
        $boton = $editar = $borrar = null;
        $id_rango = $lp['id_rango'];
        $id_r = base64_encode($id_rango);
        $estado = $lp['estado'];
        if (($permisos->PermisosUsuario($opciones, 5506, 3) || $id_rol == 1)) {
            $editar = '<a text="' . $id_r . '" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow editar"  title="Editar Retención"><span class="fas fa-pencil-alt "></span></a>';
            $st = base64_encode($id_rango . '|' . $estado);
            if ($estado == '1') {
                $title = 'Activo';
                $icono = 'on';
                $color = '#37E146';
            } else {
                $title = 'Inactivo';
                $icono = 'off';
                $color = 'gray';
            }
            $boton = '<a text="' . $st . '" class="btn btn-sm btn-circle estado" title="' . $title . '"><span class="fas fa-toggle-' . $icono . ' fa-2x" style="color:' . $color . ';"></span></a>';
        }
        if ($permisos->PermisosUsuario($opciones, 5506, 4) || $id_rol == 1) {
            $borrar = '<a text="' . $id_r . '" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow borrar"  title="Eliminar"><span class="fas fa-trash-alt "></span></a>';
        }

        if ($estado == 0) {
            $editar =  $borrar = NULL;
        }
        $data[] = [

            'id' => $lp['id_rango'],
            'tipo' => $lp['tipo'],
            'retencion' => $lp['nombre_retencion'],
            'base' => '<div class="text-end">' . pesos($lp['valor_base']) . '</div>',
            'tope' => '<div class="text-end">' . pesos($lp['valor_tope']) . '</div>',
            'tarifa' => $lp['tarifa'],
            'estado' => '<div class="text-center">' . $boton . '</div>',
            'botones' => '<div class="text-center" style="position:relative">' . $editar . $borrar . '</div>',
        ];
    }
}
$datos = [
    'data' => $data
];


echo json_encode($datos);
