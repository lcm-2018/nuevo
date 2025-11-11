<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'] ?? exit('Acceso no permitido');
$busca = $_POST['search'] ?? '';
$id_user = $_SESSION['id_user'];

include_once '../../../../../config/autoloader.php';

use Src\Usuarios\Login\Php\Clases\Usuario;

$Usuario = new Usuario();

$res['status'] = ' error';
$res['msg'] = 'Acción no válida.';

switch ($action) {
    case 'list':
        $data = (new Usuario)->getUsers($busca);
        $resultado = [];
        if (!empty($data)) {
            foreach ($data as $row) {
                $resultado[] = [
                    'label'  => $row['nombre'] . ' -> ' . $row['num_documento'],
                    'id'     => $row['id_usuario']
                ];
            }
        }
        $res = $resultado;
        break;
    case 'form1':
        $res['status'] = 'ok';
        $res['msg'] = $Usuario->getFormPerfilUsuario($id_user);
        break;
    case 'form2':

        break;
    case 'form3':

        break;
    default:
        break;
}

echo json_encode($res);
