<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    exit(json_encode(['status' => 'error', 'msg' => 'Sesión no válida']));
}

$action = isset($_POST['action']) ? $_POST['action'] : exit(json_encode(['status' => 'error', 'msg' => 'Acción no definida.']));

include_once '../../../../config/autoloader.php';

use Src\Contrata\Php\Clases\Contratos;

$Contratos = new Contratos();
$res = ['status' => 'error', 'msg' => 'Acción no válida.'];

switch ($action) {
    case 'list':
        $res['status'] = 'ok';
        $res['data'] = $Contratos->getContratos();
        break;
        
    case 'add':
        $_POST['id_usuario'] = $_SESSION['id_usuario'] ?? 1;
        $data = $Contratos->addContrato($_POST);
        if ($data === 'si') {
            $res['status'] = 'ok';
            $res['msg'] = 'Contrato creado exitosamente y versión 1.0 guardada de forma inmutable.';
        } else {
            $res['msg'] = $data;
        }
        break;
        
    case 'sign':
        $id_usuario = $_SESSION['id_usuario'] ?? 1;
        $credencial_hash = hash('sha256', $_POST['password_firma'] ?? '');
        $data = $Contratos->firmarContrato($_POST['id_contrato'], $id_usuario, $credencial_hash);
        
        if ($data === 'si') {
            $res['status'] = 'ok';
            $res['msg'] = 'El contrato ha sido firmado electrónicamente con éxito.';
        } else {
            $res['msg'] = $data;
        }
        break;

    case 'pdf':
        $pdfOutput = $Contratos->generarPDF($_POST['id_contrato'] ?? $_GET['id_contrato'] ?? 0);
        if ($pdfOutput !== false) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="contrato_'.$_POST['id_contrato'].'.pdf"');
            echo $pdfOutput;
            exit;
        } else {
            $res['msg'] = 'No se pudo generar el PDF del contrato.';
        }
        break;

    case 'edit':
    case 'diff':
    case 'annul':
        $res['msg'] = "La accion '$action' está pendiente de implementación.";
        break;
}

header('Content-Type: application/json');
echo json_encode($res);

