<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : exit('Acción no definida.');
$id = $_POST['id'];

include_once '../../../../config/autoloader.php';

use Src\Configuracion\Php\Clases\Configuracion;


$Objeto = new Configuracion();
$res['status'] = ' error';

switch ($action) {
    case 'form':
        $res['status'] = 'ok';
        $res['msg'] = $Objeto->getFormulario($id);
        break;
    case 'add':
        $data = $Objeto->addCargo($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'edit':
        $data = $Objeto->editCargo($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'del':
        $data = $Objeto->delCargo($id);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'close':
        // Aquí puedes agregar la lógica para cerrar el modal o realizar otra acción
        break;
    case 'annul':
        // Aquí puedes agregar la lógica para anular un parámetro
        break;
    default:
        echo json_encode(['error' => 'Acción no válida.']);
        break;
}

echo json_encode($res);
