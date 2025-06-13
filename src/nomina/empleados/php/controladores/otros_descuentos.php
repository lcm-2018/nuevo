<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : exit('Acción no definida.');
$id = $_POST['id'];

include_once '../../../../../config/autoloader.php';

use Src\Nomina\Empleados\Php\Clases\Otros_Descuentos;

$Otros_Descuentos = new Otros_Descuentos();
$res['status'] = ' error';
$res['msg'] = 'Acción no válida.';
switch ($action) {
    case 'form':
        $res['status'] = 'ok';
        $res['msg'] = $Otros_Descuentos->getFormulario($id);
        break;
    case 'add':
        $data = $Otros_Descuentos->addRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'edit':
        $data = $Otros_Descuentos->editRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'del':
        $data = $Otros_Descuentos->delRegistro($_POST['id']);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'close':
        break;
    case 'annul':
        $data = $Otros_Descuentos->annulRegistro($_POST);
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
