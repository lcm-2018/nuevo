<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'] ?? exit('Acceso no permitido');
$busca = $_POST['search'] ?? '';

include_once '../../../../../config/autoloader.php';

use Src\Usuarios\Login\Php\Clases\Usuario;

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
    default:
        break;
}

echo json_encode($res);
