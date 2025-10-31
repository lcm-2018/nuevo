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
$busco = isset($_POST['term']) ? $_POST['term'] : exit('Acción no permitida');
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    $sql = "SELECT
                `id_actividad`, `cod_actividad`, `descripcion`
            FROM
                `tb_actividades_economicas`
            WHERE `descripcion` LIKE '%$busco%' OR `cod_actividad` LIKE '%$busco%'";
    $rs = $cmd->query($sql);
    $resps = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
foreach ($resps as $rs) {
    $data[] = [
        'id' => $rs['id_actividad'],
        'label' => $rs['cod_actividad'] . ' - ' . $rs['descripcion'],
    ];
}

if (empty($data)) {
    $data[] = [
        'id' => '0',
        'label' => 'No hay coincidencias...',
    ];
}
echo json_encode($data);
