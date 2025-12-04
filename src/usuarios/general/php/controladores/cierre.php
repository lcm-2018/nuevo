<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'] ?? exit('Acceso no permitido');
$busca = $_POST['search'] ?? '';
$id = $_POST['id'] ?? 0;

include_once '../../../../../config/autoloader.php';

use Src\Usuarios\General\Php\Clases\Cierre;

$Cierre = new Cierre();

$res['status'] = ' error';
$res['msg'] = 'Acción no válida.';
$dt = 'si';
switch ($action) {
    case 'form':
        $res['msg'] = $Cierre->getFormularioCierreModulos();
        break;
    case 'json':
        $res['data'] = $Cierre->getMeseJSON($id);
        $dt = 'si';
        break;
    case 'opcion':
        $dt = explode('|', $_POST['id']);
        $id_modulo = $dt[0];
        $mes_idx = $dt[1];
        $nuevo_estado = $dt[2];
        // $id_rol = $_POST['id_rol']; // Not needed anymore
        $dt = $Cierre->setCierrePeriodo($id_modulo, $mes_idx, $nuevo_estado);
        break;
    case 'form_fecha':
        $res['msg'] = $Cierre->getFormularioFechaSesion();
        break;
    case 'add_fecha':
        $fecha = $_POST['fecha'];
        $dt = $Cierre->setFechaSesion($fecha);
        break;
    case 'form_vigencia':
        $res['msg'] = $Cierre->getFormularioVigencia();
        break;
    case 'add_vigencia':
        $anio = $_POST['anio'];
        $dt = $Cierre->setVigencia($anio);
        break;
}
if ($dt === 'si') {
    $res['status'] = 'ok';
} else {
    $res['msg'] = $dt;
}

echo json_encode($res);
