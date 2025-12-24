<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'] ?? exit('Acceso no permitido');
$busca = $_POST['search'] ?? '';
$id_user = $_SESSION['id_user'];
$id = $_POST['id'] ?? 0;

include_once '../../../../../config/autoloader.php';

use Src\Usuarios\General\Php\Clases\Users;

$Usuario = new Users();

$res['status'] = ' error';
$res['msg'] = 'Acción no válida';
$dt = 'si';
switch ($action) {
    case 'form1':
        $res['msg'] = $Usuario->getFormUsuario($id);
        break;
    case 'form2':
        $res['msg'] = $Usuario->getFormCambiaClave($id_user);
        break;
    case 'form3':
        $res['msg'] = $Usuario->getPermisosModulos($id);
        break;
    case 'form4':
        $res['msg'] = $Usuario->getPermisosOpciones($_POST);
        break;
    case 'get_permisos_json':
        $res['data'] = $Usuario->getPermisosModulosJSON($id);
        $dt = 'si';
        break;
    case 'get_permisos_opciones_json':
        $res['data'] = $Usuario->getPermisosOpcionesJSON($_POST);
        $dt = 'si';
        break;
    case 'opcion':
        $dt = explode('|', $_POST['id']);
        $id_opcion = $dt[0];
        $col_idx = $dt[1];
        $nuevo_estado = $dt[2];
        $id_user = $_POST['id_user'];
        $dt = $Usuario->setPermisoOpcion($id_user, $id_opcion, $col_idx, $nuevo_estado);
        break;
    case 'pass':
        $dt = $Usuario->editClave($_POST);
        break;
    case 'estado':
        $dt = explode('|', $_POST['id']);
        $id = $dt[0];
        $estado = $dt[1];
        $dt = $Usuario->setEstado($id, $estado);
        break;
    case 'modulo':
        $dt = explode('|', $_POST['id']);
        $_POST['id'] = $dt[0];
        $_POST['estado'] = $dt[1];
        $dt = $dt[1] == 1 ? $Usuario->addRegistroModulo($_POST) : $Usuario->delRegistroModulo($_POST);
        break;
    case 'del':
        $dt = $Usuario->delRegistro($id);
        break;
    case 'add':
        $dt = $Usuario->addRegistro($_POST);
        break;
    case 'edit':
        $dt = $Usuario->editRegistro($_POST);
        break;
    default:
        $dt = 'Acción no válida...';
        break;
}
if ($dt === 'si') {
    $res['status'] = 'ok';
} else {
    $res['msg'] = $dt;
}

echo json_encode($res);
