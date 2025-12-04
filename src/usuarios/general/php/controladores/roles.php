<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'] ?? exit('Acceso no permitido');
$busca = $_POST['search'] ?? '';
$id = $_POST['id'] ?? 0;

include_once '../../../../../config/autoloader.php';

use Src\Usuarios\General\Php\Clases\Roles;

$Roles = new Roles();

$res['status'] = ' error';
$res['msg'] = 'Acción no válida.';
$dt = 'si';
switch ($action) {
    case 'form':
        $res['msg'] = $Roles->getFormulario($id);
        break;
    case 'form2':
        $res['msg'] = $Roles->getFormularioPermisos($id);
        break;
    case 'json':
        $res['data'] = $Roles->getPermisosRolesJSON($id);
        $dt = 'si';
        break;
    case 'opcion':
        $dt = explode('|', $_POST['id']);
        $id_opcion = $dt[0];
        $col_idx = $dt[1];
        $nuevo_estado = $dt[2];
        $id_rol = $_POST['id_rol'];
        $dt = $Roles->setPermisoRol($id_rol, $id_opcion, $col_idx, $nuevo_estado);
        break;
    case 'add':
        $dt = $Roles->addRegistro($_POST);
        break;
    case 'edit':
        $dt = $Roles->editRegistro($_POST);
        break;
    case 'del':
        $dt = $Roles->delRegistro($id);
        break;
    default:
        $dt = 'Acción no válida.';
        break;
}
if ($dt === 'si') {
    $res['status'] = 'ok';
} else {
    $res['msg'] = $dt;
}

echo json_encode($res);
