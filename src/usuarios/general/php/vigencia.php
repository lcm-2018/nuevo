<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit();
}

$id_vigencia =  isset($_POST['id']) ? $_POST['id'] : exit('Acceso denegado');
$vigencia    =  $_POST['texto'];

$_SESSION['vigencia'] = $vigencia;
$_SESSION['id_vigencia'] = $id_vigencia;
echo json_encode(['status' => 'ok']);;
exit();
