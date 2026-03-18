<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : exit('Acción no definida.');
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

include_once '../../../../../config/autoloader.php';

use Src\Nomina\Certificaciones\Php\Clases\Certificados;

$Certificados = new Certificados();
$res = ['status' => 'error', 'msg' => 'Acción no procesada.'];

switch ($action) {

    // Abre el formulario de configuración para seleccionar empleado y datos adicionales
    case 'form':
        $id_cert = isset($_POST['id_cert']) ? (int)$_POST['id_cert'] : 0;
        $res['status'] = 'ok';
        $res['msg'] = $Certificados->getFormularioCert($id_cert);
        break;

    // Busca empleados activos (autocomplete)
    case 'buscar_empleados':
        $busca = isset($_POST['busca']) ? trim($_POST['busca']) : '';
        $datos = $Certificados->getEmpleados($busca);
        $res['status'] = 'ok';
        $res['datos'] = $datos;
        break;

    // Obtiene los datos de un empleado específico
    case 'datos_empleado':
        $id_empleado = isset($_POST['id_empleado']) ? (int)$_POST['id_empleado'] : 0;
        $datos = $Certificados->getDatosEmpleado($id_empleado);
        if (!empty($datos)) {
            $res['status'] = 'ok';
            $res['datos'] = $datos;
        } else {
            $res['msg'] = 'No se encontró información del empleado.';
        }
        break;

    // Genera/registra una certificación (y retorna los datos para el PDF/vista)
    case 'generar':
        $id_empleado = isset($_POST['id_empleado']) ? (int)$_POST['id_empleado'] : 0;
        $id_cert     = isset($_POST['id_cert']) ? (int)$_POST['id_cert'] : 0;
        $dirigido_a  = isset($_POST['txtDirigidoA']) ? trim($_POST['txtDirigidoA']) : 'A QUIEN CORRESPONDA';

        if ($id_empleado <= 0) {
            $res['msg'] = 'Debe seleccionar un empleado.';
            break;
        }
        if ($id_cert <= 0) {
            $res['msg'] = 'Tipo de certificado no válido.';
            break;
        }

        $datos_emp = $Certificados->getDatosEmpleado($id_empleado);
        $entidad   = $Certificados->getEntidad();

        if (empty($datos_emp)) {
            $res['msg'] = 'No se encontró información del empleado.';
            break;
        }

        $res['status']   = 'ok';
        $res['empleado'] = $datos_emp;
        $res['entidad']  = $entidad;
        $res['id_cert']  = $id_cert;
        $res['dirigido_a'] = $dirigido_a;
        break;

    default:
        $res['msg'] = 'Acción no válida.';
        break;
}

echo json_encode($res);
