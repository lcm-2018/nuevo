<?php

use Src\Nomina\Liquidado\Php\Clases\Reportes;

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : exit('Acceso no permitido');
$id = $_POST['id'];

include_once '../../../../../config/autoloader.php';

$Reportes = new Reportes();

$res['status'] = ' error';
$res['msg'] = '';
switch ($action) {
    case 'form':
        $res['status'] = 'ok';
        $res['msg'] = $Reportes->getFormulario($id);
        break;
    default:
        break;
}

echo json_encode($res);
