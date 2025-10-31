<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include '../../../../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$opciones = $permisos->PermisoOpciones($id_user);

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    $sql = "SELECT `id_perfil`,`descripcion` FROM `ctt_perfil_tercero`";
    $rs = $cmd->query($sql);
    $perfiles = $rs->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

if (!empty($perfiles)) {
    foreach ($perfiles as $d) {
        $id_perfil = $d['id_perfil'];
        $borrar = $editar = '';

        if ($permisos->PermisosUsuario($opciones, 5201, 3) || $id_rol == 1) {
            $editar = '<a onclick="EditarPerfilTercero(' . $id_perfil . ')" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 "  title="Editar perfil de tercero"><span class="fas fa-pencil-alt"></span></a>';
        }
        if ($permisos->PermisosUsuario($opciones, 5201, 4) || $id_rol == 1) {
            $borrar = '<a onclick="BorrarPerfilTercero(' . $id_perfil . ')" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 "  title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
        }
        $data[] = [
            'id' => $id_perfil,
            'descripcion' => mb_strtoupper($d['descripcion']),
            'acciones' => '<div class="text-center">' . $editar . $borrar . '</div>',
        ];
    }
} else {
    $data = [];
}

$datos = ['data' => $data];

echo json_encode($datos);
