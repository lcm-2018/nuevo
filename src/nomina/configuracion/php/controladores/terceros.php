<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : exit('Acción no definida.');
$id = $_POST['id'];
$_POST['id_vigencia'] = $_SESSION['id_vigencia'];

include_once '../../../../../config/autoloader.php';

use Src\Nomina\Configuracion\Php\Clases\Terceros;


$Terceros = new Terceros();
$res['status'] = ' error';
$res['msg'] = 'Acción no válida.';

switch ($action) {
    case 'form':
        $res['status'] = 'ok';
        $res['msg'] = $Terceros->getFormulario($id);
        break;
    case 'add':
        $data = $Terceros->addTerceroNomina($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'edit':
        break;
    case 'del':
        break;
    case 'close':
        break;
    case 'annul':
        break;
    default:
        break;
}

echo json_encode($res);
