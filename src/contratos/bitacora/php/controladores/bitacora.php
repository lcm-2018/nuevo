<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : exit('Acción no definida.');
$id     = $_POST['id'] ?? 0;

include_once '../../../../../config/autoloader.php';

use Src\Contratos\Bitacora\Php\Clases\Bitacora;

$clase = new Bitacora();
$res   = ['status' => 'error', 'msg' => ''];

switch ($action) {
    case 'form':
        $res['status'] = 'ok';
        $res['msg']    = $clase->getFormulario($id);
        break;
    case 'add':
        $data = $clase->addBitacora($_POST);
        if ($data === 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'edit':
        $data = $clase->editBitacora($_POST);
        if ($data === 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'del':
        $data = $clase->delBitacora($id);
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
