<?php

use Src\Common\Php\Clases\Combos;

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
}

echo json_encode($res);
