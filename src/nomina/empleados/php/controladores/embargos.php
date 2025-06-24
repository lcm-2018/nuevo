<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : exit('Acción no definida.');
$id = $_POST['id'];

include_once '../../../../../config/autoloader.php';

use Config\Clases\Sesion;
use Src\Nomina\Empleados\Php\Clases\Embargos;
use Src\Nomina\Configuracion\Php\Clases\Parametros;
use Src\Nomina\Empleados\Php\Clases\Empleados;

$Embargos = new Embargos();
$res['status'] = ' error';
$res['msg'] = 'Acción no válida.';
switch ($action) {
    case 'form':
        $res['status'] = 'ok';
        $res['msg'] = $Embargos->getFormulario($id);
        break;
    case 'add':
        $data = $Embargos->addRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'edit':
        $data = $Embargos->editRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'del':
        $data = $Embargos->delRegistro($_POST['id']);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'close':
        break;
    case 'annul':
        $data = $Embargos->annulRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'imp':
        $res['status'] = 'ok';
        $res['msg'] = 'Mensaje a imprimir';
        break;
    case 'dcto':
        $smmlv = Parametros::Smmlv();
        $salario = Empleados::getSalarioBasico($_POST['id_empleado']);
        $valor = 0;
        if ($_POST['tipo'] == '1') {
            $saldo = $salario - $smmlv;
            if ($saldo <= 0) {
                $res['msg'] = 'Salario no embargable';
            } else {
                $res['status'] = 'ok';
                $valor = $saldo * 0.2;
            }
        } else {
            $res['status'] = 'ok';
            $valor = $salario * 0.5;
        }
        $res['valor'] = $valor;
    default:

        break;
}

echo json_encode($res);
