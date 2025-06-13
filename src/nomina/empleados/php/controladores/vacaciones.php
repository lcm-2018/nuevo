<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : exit('Acción no definida.');
$id = $_POST['id'];

include_once '../../../../../config/autoloader.php';

use Src\Nomina\Empleados\Php\Clases\Vacaciones;

$Vacaciones = new Vacaciones();
$res['status'] = ' error';
$res['msg'] = 'Acción no válida.';
switch ($action) {
    case 'form':
        $res['status'] = 'ok';
        $res['msg'] = $Vacaciones->getFormulario($id);
        break;
    case 'add':
        $data = $Vacaciones->addRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'edit':
        $data = $Vacaciones->editRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'del':
        $data = $Vacaciones->delRegistro($_POST['id']);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'close':
        break;
    case 'annul':
        $data = $Vacaciones->annulRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'imp':
        $res['status'] = 'ok';
        $res['msg'] = 'Mensaje a imprimir';
        break;
    default:
        break;
}

echo json_encode($res);
