<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit();
}

include_once '../../../../config/autoloader.php';

use Src\Common\Php\Clases\Terceros;

$data = new Terceros();
$data = $data->getTerceros($_POST['search']);

echo json_encode($data);
