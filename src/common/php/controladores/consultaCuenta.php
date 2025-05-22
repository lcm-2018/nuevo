<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit();
}

include_once '../../../../config/autoloader.php';

use Src\Common\Php\Clases\Cuentas;

$data = new Cuentas();
$data = $data->getCuentas($_POST['search'], 2);

echo json_encode($data);
