<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : exit('Acceso no permitido');
$id = $_POST['id'] ?? null;
$id_nomina = $_POST['id_nomina'] ?? null;

include_once '../../../../../config/autoloader.php';

use Config\Clases\Conexion;
use Src\Nomina\Empleados\Php\Clases\Embargos;
use Src\Nomina\Empleados\Php\Clases\Empleados;
use Src\Nomina\Empleados\Php\Clases\Libranzas;
use Src\Nomina\Empleados\Php\Clases\Retenciones;
use Src\Nomina\Empleados\Php\Clases\Seguridad_Social;
use Src\Nomina\Empleados\Php\Clases\Sindicatos;
use Src\Nomina\Liquidado\Php\Clases\Detalles;

$Detalles = new Detalles();

$res['status'] = ' error';
$res['msg'] = 'Acción no válida.';
switch ($action) {
    case 'form':
        $res['status'] = 'ok';
        $res['msg'] = $Detalles->getFormulario($id, $id_nomina);
        break;
    case 'add':

        break;
    case 'edit':
        $option = $_POST['option'];
        $suma = 0;
        $conexion = Conexion::getConexion();
        $conexion->beginTransaction();
        switch ($option) {
            case 3:
                $Seguridad_Social = new Seguridad_Social($conexion);
                $id             = $Seguridad_Social->getRegistroLiq($_POST);
                $_POST['id']    = $id;
                $seso            = $Seguridad_Social->editRegistroLiq($_POST);
                if ($seso == 'si') {
                    $suma++;
                } else if ($seso != 'no') {
                    $conexion->rollBack();
                    exit(json_encode($seso));
                }

                $id             = $Seguridad_Social->getRegistroLiq2($_POST);
                $_POST['id']    = $id;
                $pfis            = $Seguridad_Social->editRegistroLiq2($_POST);
                if ($pfis == 'si') {
                    $suma++;
                } else if ($pfis != 'no') {
                    $conexion->rollBack();
                    exit(json_encode($pfis));
                }
                if ($suma > 0) {
                    $conexion->commit();
                    $res['status'] = 'ok';
                } else {
                    $conexion->rollBack();
                    $res['msg'] = 'No se actualizaron los registros.';
                }
                break;
            case 4:
                $id             = (new Retenciones($conexion))->getRegistroLiq($_POST);
                $_POST['id']    = $id;
                $fte            = (new Retenciones($conexion))->editRegistroLiq($_POST);
                if ($fte == 'si') {
                    $suma++;
                } else if ($fte != 'no') {
                    $conexion->rollBack();
                    exit(json_encode($fte));
                }
                if ($suma > 0) {
                    $conexion->commit();
                    $res['status'] = 'ok';
                } else {
                    $conexion->rollBack();
                    $res['msg'] = 'No se actualizaron los registros.';
                }
                break;
            default:
                $res['msg'] = 'Tipo no válido.';
                break;
        }
        break;
    case 'del':
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
