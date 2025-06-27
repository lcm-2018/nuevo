<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'] ?? exit('Acceso no permitido');
$id = $_POST['id'] ?? null;

include_once '../../../../../config/autoloader.php';

use Config\Clases\Sesion;
use Src\Nomina\Empleados\Php\Clases\Empleados;
use Src\Nomina\Horas_extra\Php\Clases\Horas_Extra;
use Src\Common\Php\Clases\CsvExporter;

$Horas_Extra = new Horas_Extra();

$res['status'] = ' error';
$res['msg'] = 'Acción no válida.';
switch ($action) {
    case 'form':
        $res['status'] = 'ok';
        $res['msg'] = $Horas_Extra->getFormulario($id);
        break;
    case 'add':
        $data = $Horas_Extra->addRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'edit':
        $data = $Horas_Extra->editRegistro($_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    case 'del':
        $data = $Horas_Extra->delRegistro($_POST['id']);
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
    case 'csv':
        $res['status'] = 'ok';
        $res['msg'] = 'Descargando archivo CSV...';
        $hoy = Sesion::Hoy();
        $fin = date('Y-m-t', strtotime(strval($hoy)));
        $csv = new CsvExporter('formato_he_' . date('Ymds') . '.csv');
        $hoy_dia = date('Y-m-d', strtotime(strval($hoy)));
        $csv->setHeaders(['CEDULA', 'FECHA_INICIA', 'FECHA_TERMINA', 'HED', 'HEN', 'RN', 'HEDFD', 'DYFD', 'HEDFN', 'DYFN']);
        $csv->setExampleRow(['22222222', $hoy_dia, $fin, '10', '0', '0', '0', '0', '0', '3']);
        $csv->download();
        break;
    case 'upload':
        $data = $Horas_Extra->addMasivo($_FILES, $_POST);
        if ($data == 'si') {
            $res['status'] = 'ok';
        } else {
            $res['msg'] = $data;
        }
        break;
    default:
        break;
}

echo json_encode($res);
