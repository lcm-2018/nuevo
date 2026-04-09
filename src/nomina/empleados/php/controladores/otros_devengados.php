<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : exit('Acción no definida.');
$id = $_POST['id'];

include_once '../../../../../config/autoloader.php';

use Src\Nomina\Empleados\Php\Clases\Otros_Devengados;

$Otros_Devengados = new Otros_Devengados();
$res['status'] = ' error';
$res['msg'] = 'Acción no válida.';
switch ($action) {
    case 'form':
        $res['status'] = 'ok';
        $res['msg'] = $Otros_Devengados->getFormulario($id);
        break;
    case 'add':
        $data = $Otros_Devengados->addRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'edit':
        $data = $Otros_Devengados->editRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'del':
        $data = $Otros_Devengados->delRegistro($_POST['id']);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'annul':
        $data = $Otros_Devengados->annulRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'get_tipo':
        $id_tipo = isset($_POST['id_tipo']) ? intval($_POST['id_tipo']) : 0;
        $data    = $Otros_Devengados->getTipoConRubros($id_tipo);
        $res['status'] = 'ok';
        $res['msg']    = $data;
        break;
    default:
        break;
}

echo json_encode($res);
