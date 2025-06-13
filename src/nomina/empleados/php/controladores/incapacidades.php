<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : exit('Acción no definida.');
$id = $_POST['id'];

include_once '../../../../../config/autoloader.php';

use Src\Nomina\Empleados\Php\Clases\Incapacidades;

$Incapacidades = new Incapacidades();
$res['status'] = ' error';
$res['msg'] = 'Acción no válida.';
switch ($action) {
    case 'form':
        $res['status'] = 'ok';
        $res['msg'] = $Incapacidades->getFormulario($id);
        break;
    case 'add':
        $data = $Incapacidades->addRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'edit':
        $data = $Incapacidades->editRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'del':
        $data = $Incapacidades->delRegistro($_POST['id']);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'close':
        break;
    case 'annul':
        $data = $Incapacidades->annulRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    default:
        break;
}

echo json_encode($res);
