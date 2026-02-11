<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : exit('Acción no definida.');
$id = $_POST['id'];

include_once '../../../../../config/autoloader.php';

use Src\Nomina\Empleados\Php\Clases\ViaticoNovedades;

$ViaticoNovedades = new ViaticoNovedades();
$res['status'] = 'error';
$res['msg'] = 'Acción no válida.';
switch ($action) {
    case 'form':
        $res['status'] = 'ok';
        $res['msg'] = $ViaticoNovedades->getFormulario($id);
        break;
    case 'get':
        $registro = $ViaticoNovedades->getRegistro($id);
        $res['status'] = 'ok';
        $res['data'] = $registro;
        break;
    case 'add':
        $data = $ViaticoNovedades->addRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'edit':
        $data = $ViaticoNovedades->editRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'del':
        $id_viatico = isset($_POST['id_viatico']) ? $_POST['id_viatico'] : 0;
        $data = $ViaticoNovedades->delRegistro($id, $id_viatico);
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
