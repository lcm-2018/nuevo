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
    $sql = "SELECT * FROM ctt_modalidad";
    $rs = $cmd->query($sql);
    $modalidad = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$data = [];

if (!empty($modalidad)) {
    foreach ($modalidad as $mod) {
        $id_mod = $mod['id_modalidad'];
        $borrar = null;

        if ($permisos->PermisosUsuario($opciones, 5301, 4) || $id_rol == 1) {
            $borrar = '<a value="' . $id_mod . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 borrar" title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
        }
        $data[] = [
            'modalidad' => $mod['modalidad'],
            'botones' => '<div class="text-center">' . $borrar . '</div>',
        ];
    }
}

$datos = ['data' => $data];

echo json_encode($datos);
