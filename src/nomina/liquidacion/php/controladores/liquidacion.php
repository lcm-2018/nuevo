<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : exit('Acceso no permitido');
$id = $_POST['id'] ?? null;

include_once '../../../../../config/autoloader.php';

use Src\Nomina\Empleados\Php\Clases\Cesantias;
use Src\Nomina\Empleados\Php\Clases\Empleados;
use Src\Nomina\Liquidacion\Php\Clases\Liquidacion;
use Src\Nomina\Empleados\Php\Clases\Primas;
use Src\Nomina\Empleados\Php\Clases\Vacaciones;

$Liquidacion = new Liquidacion();
$Cesantias = new Cesantias();
$Primas = new Primas();
$Vacaciones = new Vacaciones();

$res['status'] = ' error';
$res['msg'] = 'Acción no válida.';
switch ($action) {
    case 'form':
        $res['msg'] = 'Sin formulario definido.';
        break;
    case 'add':
        switch ($_POST['tipo']) {
            case 2:
                $data = $Liquidacion->addRegistro($_POST);
                break;
            case 4:
                $data = $Vacaciones->addRegistroNoVc($_POST);
                break;
            case 6:
            case 7:
                $data = $Primas->addRegistroPsPn($_POST);
                break;
            case 8:
            case 9:
                $data = $Cesantias->addRegistroN($_POST);
                break;
        }
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'edit':
        $data = $Liquidacion->editRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'del':
        $data = $Liquidacion->delRegistro($_POST['id']);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'list':
        $data = (new Empleados)->getEmpleados();
        $resultado = [];
        if (!empty($data)) {
            foreach ($data as $row) {
                $nombre = trim($row['nombre1'] . ' ' . $row['nombre2'] . ' ' . $row['apellido1'] . ' ' . $row['apellido2'] . ' - ' . $row['no_documento']);
                $resultado[] = [
                    'label'  => $nombre,
                    'id'     => $row['id_empleado']
                ];
            }
        }
        $res = $resultado;
        break;
    default:
        break;
}

echo json_encode($res);
