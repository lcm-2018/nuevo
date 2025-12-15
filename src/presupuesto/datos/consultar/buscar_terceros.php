<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `id_tercero_api`, `nit_tercero`, `nom_tercero`
            FROM
                `tb_terceros`";
    $rs = $cmd->query($sql);
    $terceros = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$data = [];
$buscar = mb_strtoupper($_POST['term']);
if ($buscar == '%%') {
    foreach ($terceros as $s) {
        $nom_tercero = mb_strtoupper($s['nom_tercero']);
        $data[] = [
            'id' => $s['id_tercero_api'],
            'label' => $nom_tercero,
        ];
    }
} else {
    foreach ($terceros as $s) {
        $nom_tercero = mb_strtoupper($s['nom_tercero'] . ' -> ' . $s['nit_tercero']);
        $pos = strpos($nom_tercero, $buscar);
        $nit = strpos($s['nit_tercero'], $buscar);
        if ($pos !== false || $nit !== false) {
            $data[] = [
                'id' => $s['id_tercero_api'],
                'label' => $nom_tercero,
            ];
        }
    }
}

if (empty($data)) {
    $data[] = [
        'id' => '0',
        'label' => 'No hay coincidencias...',
    ];
}
echo json_encode($data);
