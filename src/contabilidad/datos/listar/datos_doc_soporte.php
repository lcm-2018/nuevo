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
/*
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "";
    $rs = $cmd->query($sql);
    $documentos = $rs->fetchAll();
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}*/
$documentos = [];
$data = [];
if (!empty($documentos)) {
    foreach ($documentos as  $doc) {
        $boton = $editar = $borrar = null;
        if (($permisos->PermisosUsuario($opciones, 5510, 3) || $id_rol == 1)) {
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
            'ref' => $lp['tipo'],
            'inicia' => $lp['nombre_retencion'],
            'vence' => '<div class="text-end">' . pesos($lp['valor_base']) . '</div>',
            'tipo_doc' => '<div class="text-end">' . pesos($lp['valor_tope']) . '</div>',
            'num_doc' => $lp['tarifa'],
            'nombre' => '<div class="text-center">' . $boton . '</div>',
            'botones' => '<div class="text-center" style="position:relative">' . $editar . $borrar . '</div>',
        ];
    }
}
$datos = [
    'data' => $data
];


echo json_encode($datos);
