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
// Div de acciones de la lista
$vigencia = $_SESSION['vigencia'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `id_doc_fuente`, `cod`, `nombre`, `contab`, `tesor`, `estado`
            FROM
                `ctb_fuente`";
    $rs = $cmd->query($sql);
    $lista = $rs->fetchAll();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
if (!empty($lista)) {
    foreach ($lista as $lp) {
        $cerrar = $editar = $borrar = $mas = null;
        $id_ctb = $lp['id_doc_fuente'];
        if (($permisos->PermisosUsuario($opciones, 5505, 1) || $id_rol == 1) && ($lp['estado'] == 1)) {
            $mas = '<button id ="mas_' . $id_ctb . '" value="' . $id_ctb . '" onclick="masDocFuente(' . $id_ctb . ')" class="btn btn-outline-info btn-xs rounded-circle me-1 shadow"  title="Parametrización de acciones"><span class="fas fa-cogs "></span></button>';
        }
        if (($permisos->PermisosUsuario($opciones, 5505, 3) || $id_rol == 1) && ($lp['estado'] == 1)) {
            $editar = '<button id ="editar_' . $id_ctb . '" value="' . $id_ctb . '" onclick="editarDocFuente(' . $id_ctb . ')" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow"  title="Editar_' . $id_ctb . '"><span class="fas fa-pencil-alt "></span></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 5505, 4) || $id_rol == 1) {
            if ($lp['estado'] == 1) {
                $borrar = '<button value="' . $id_ctb . '" onclick="eliminarDocFuente(' . $id_ctb . ')" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow "  title="Eliminar"><span class="fas fa-trash-alt "></span></button>';
                $cerrar = '<button value="' . $id_ctb . '" class="btn btn-outline-warning btn-xs rounded-circle me-1 shadow" onclick="cerrarFuente(' . $id_ctb . ')" title="Desactivar Fuente"><span class="fas fa-unlock "></span></button>';
            } else {
                $cerrar = '<button value="' . $id_ctb . '" class="btn btn-outline-secondary btn-xs rounded-circle me-1 shadow" onclick="abrirFuente(' . $id_ctb . ')" title="Activar Fuente"><span class="fas fa-lock "></span></button>';
            }
        }
        if ($lp['estado'] == 1) {
            $estado = '<span class="badge badge-success">Activa</span>';
        } else {
            $estado = '<span class="badge badge-secondary">Inactiva</span>';
        }
        $data[] = [

            'cod' => $lp['cod'],
            'nombre' => $lp['nombre'],
            'contab' => '<div class="text-center">' . $lp['contab'] . '</div>',
            'tesor' => '<div class="text-center">' . $lp['tesor'] . '</div>',
            'cxpagar' => '',
            'estado' => '<div class="text-center">' . $estado . '</div>',
            'botones' => '<div class="text-center" style="position:relative">' . $editar . $mas . $cerrar . $borrar . '</div>',
        ];
    }
} else {
    $data = [];
}
$cmd = null;
$datos = ['data' => $data];


echo json_encode($datos);
