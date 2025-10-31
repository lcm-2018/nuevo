<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include '../../../../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$opciones = $permisos->PermisoOpciones($id_user);
$id_t = isset($_POST['id_t']) ? $_POST['id_t'] : exit('Acción no permitida');
//API URL
$api = \Config\Clases\Conexion::Api();
$url = $api . 'terceros/datos/res/lista/actv_econ/' . $id_t;
$ch = curl_init($url);
//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$result = curl_exec($ch);
curl_close($ch);
$actvidades = json_decode($result, true);
if (!empty($actvidades)) {
    foreach ($actvidades as $a) {
        $idae = $a['id_actvtercero'];
        $estado = $a['estado'] == '1' ? '<span class="fas fa-toggle-on fa-lg activo text-success"></span>' : '<span class="fas fa-toggle-off fa-lg inactivo text-secondary"></span>';
        $data[] = [
            'codigo' => '<div class="text-center">' . $a['codigo_ciiu'] . '</div>',
            'descripcion' => mb_strtoupper($a['descripcion']),
            'fec_inicio' => $a['fec_inicio'],
            'estado' => '<div class="text-center">' . $estado . '</div>',
        ];
    }
} else {
    $data = [];
}

$datos = ['data' => $data];

echo json_encode($datos);
