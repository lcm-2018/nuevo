<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'] ?? exit('Acceso no permitido');
$id = $_POST['id'] ?? null;

include_once '../../../../config/autoloader.php';

use Src\Documentos\Php\Clases\Detalles;

$Detalles = new Detalles();

$res['status'] = ' error';
$res['msg'] = '';
switch ($action) {
    case 'form':
        $data = 'si';
        $idD = $_POST['idD'] ?? 0;
        $res['msg'] = $Detalles->getFormulario($id, $idD);
        break;
    case 'add':
        $data = $Detalles->addRegistro($_POST);
        break;
    case 'edit':
        $data = $Detalles->editRegistro($_POST);
        break;
    case 'del':
        $data = $Detalles->delRegistro($_POST['id']);
        break;
    case 'estado':
        $dt = explode('|', $_POST['id']);
        $id = $dt[0];
        $estado = $dt[1];
        $data = $Detalles->setEstado($id, $estado);
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
