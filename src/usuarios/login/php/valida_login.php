<?php
session_start();
include_once '../../../../config/autoloader.php';

use Src\Usuarios\Login\Php\Clases\Usuario;

$usuario = $_POST['txtUser'];
$clave = $_POST['pssClave'];

$user = new Usuario();
$obj = $user->getUser($usuario);
$empresa = $user->getEmpresa();
$vigencia = $user->getvigencia();

$res['status'] = 'error';

if (($obj != null) && $obj['login'] === $usuario && ($obj['clave'] === $clave)) {
    if ($obj['estado'] == 1) {
        $_SESSION['id_user'] = $obj['id_usuario'];
        $_SESSION['user'] = $obj['nombre'];
        $_SESSION['login'] = $obj['login'];
        $_SESSION['rol'] = $obj['id_rol'];
        $_SESSION['navarlat'] = '0';
        $_SESSION['caracter'] = $empresa['caracter'];
        $_SESSION['id_vigencia'] = $vigencia['id_vigencia'];
        $_SESSION['vigencia'] = $vigencia['anio'];
        $_SESSION['nit_emp'] = $empresa['nit'];
        $_SESSION['pto'] = $empresa['tiene_pto'];
        $res['status'] = 'ok';
    } else if ($obj['estado'] == 0) {
        $res['msg'] = 'Usuario inactivo, por favor comuníquese con el administrador del sistema.';
    }
} else {
    $res['msg'] = 'Usuario o contraseña incorrectos.';
}
echo json_encode($res);
