<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : exit('Acción no definida.');
$id     = $_POST['id'] ?? 0;

include_once '../../../../../config/autoloader.php';

use Src\Contratos\Minutas\Php\Clases\Minutas;

$clase = new Minutas();
$res   = ['status' => 'error', 'msg' => ''];

switch ($action) {
    case 'form':
        $res['status'] = 'ok';
        $res['msg']    = $clase->getFormulario($id);
        break;
    case 'add':
        $data = $clase->addMinuta($_POST);
        if ($data === 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'edit':
        $data = $clase->editMinuta($_POST);
        if ($data === 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'del':
        $data = $clase->delMinuta($id);
        if ($data === 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    default:
        $res['msg'] = 'Acción no válida.';
        break;
}

echo json_encode($res);
