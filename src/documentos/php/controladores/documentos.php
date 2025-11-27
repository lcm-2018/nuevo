<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'] ?? exit('Acceso no permitido');
$id = $_POST['id'] ?? null;

include_once '../../../../config/autoloader.php';

use Src\Documentos\Php\Clases\Documentos;

$Documentos = new Documentos();

$res['status'] = ' error';
$res['msg'] = '';
switch ($action) {
    case 'form':
        $data = 'si';
        $res['msg'] = $Documentos->getFormulario($id);
        break;
    case 'add':
        $data = $Documentos->addRegistro($_POST);
        break;
    case 'edit':
        $data = $Documentos->editRegistro($_POST);
        break;
    case 'del':
        $data = $Documentos->delRegistro($_POST['id']);
        break;
    case 'estado':
        $dt = explode('|', $_POST['id']);
        $id = $dt[0];
        $estado = $dt[1];
        $data = $Documentos->setEstado($id, $estado);
        break;
    default:
        break;
}
if ($data == 'si') {
    $res['status'] = 'ok';
} else {
    $res['msg'] = $data;
}
echo json_encode($res);
