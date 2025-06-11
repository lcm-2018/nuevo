<?php

use Src\Common\Php\Clases\Combos;
use Src\Nomina\Empleados\Php\Clases\Empleados;

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit();
}

include_once '../../../../config/autoloader.php';

$res['status'] = 'error';

switch ($_POST['action']) {
    case 'mun':
        $res['status'] = 'ok';
        $res['msg'] = Combos::getMunicipios($_POST['id'], 0);
        break;
    case 'tern':
        $res['status'] = 'ok';
        $res['msg'] = Empleados::getTerceroNomina('', 0, $_POST['id']);
        break;
}

echo json_encode($res);
