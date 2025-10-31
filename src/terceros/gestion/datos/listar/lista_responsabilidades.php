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
    
    $sql = "SELECT
                `id_responsabilidad`, `codigo`, `descripcion`
            FROM
                `tb_responsabilidades_tributarias`";
    $rs = $cmd->query($sql);
    $respsonsabilidades = $rs->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$data = [];
if (!empty($respsonsabilidades)) {
    foreach ($respsonsabilidades as $r) {
        $editar = $borrar = null;
        if ($permisos->PermisosUsuario($opciones, 5201, 3) || $id_rol == 1) {
            $editar = $editar = '<button onclick="FormResponsabilidad(' . $r['id_responsabilidad'] . ')" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1" title="Editar"><span class="fas fa-pencil-alt"></span></button>';
        }
        if ($permisos->PermisosUsuario($opciones, 5201, 4) || $id_rol == 1) {
            $borrar = '<button onclick="BorrarResponsabilidad(' . $r['id_responsabilidad'] . ')" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1" title="Borrar"><span class="fas fa-trash-alt"></span></button>';
        }
        $data[] = [
            'id' => $r['id_responsabilidad'],
            'codigo' => $r['codigo'],
            'descripcion' => $r['descripcion'],
            'botones' => '<div class="text-center">' . $editar . $borrar . '</div>'
        ];
    }
}

$datos = ['data' => $data];

echo json_encode($datos);
