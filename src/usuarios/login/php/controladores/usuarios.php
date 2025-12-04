<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'] ?? exit('Acceso no permitido');
$busca = $_POST['search'] ?? '';
$id_user = $_SESSION['id_user'];
$id = $_POST['id'] ?? 0;

include_once '../../../../../config/autoloader.php';

use Src\Usuarios\General\Php\Clases\Users;

$Usuario = new Users();

$res['status'] = ' error';
$res['msg'] = 'Acción no válida.';
$dt = 'si';
switch ($action) {
    case 'list':
        $data = $Usuario->getUsers($busca);
        $resultado = [];
        if (!empty($data)) {
            foreach ($data as $row) {
                $resultado[] = [
                    'label'  => $row['nombre'] . ' -> ' . $row['num_documento'],
                    'id'     => $row['id_usuario']
                ];
            }
        }
        $res['msg'] = $resultado;
        break;
    case 'form1':
        if ($id == 'A') {
            $id = $id_user;
        }
        $res['msg'] = $Usuario->getFormUsuario($id);
        break;
    case 'form2':
        if ($id == 'A') {
            $id = $id_user;
        }
        $res['msg'] = $Usuario->getFormCambiaClave($id);
        break;
    case 'pass':
        $dt = $Usuario->editClave($_POST);
        break;
    default:
        break;
}
if ($dt === 'si') {
    $res['status'] = 'ok';
} else {
    $res['msg'] = $dt;
}

echo json_encode($res);
