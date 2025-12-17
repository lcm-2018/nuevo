<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}
include '../../../../config/autoloader.php';
$search = isset($_POST['search']) ?  $_POST['search'] : '';
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_pgcp`, `cuenta`,`nombre`,`tipo_dato` 
            FROM `ctb_pgcp` 
            WHERE (`cuenta` LIKE '$search%' OR `nombre` LIKE '$search%') AND `estado` = 1";
    $rs = $cmd->query($sql);
    $cuentas = $rs->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
if (empty($cuentas)) {
    $response[] = [
        'id' => '0',
        'label' => 'No hay coincidencias...',
        'tipo_dato' => '0',
    ];
} else {
    foreach ($cuentas as $c) {
        $response[] = [
            'id' => $c['id_pgcp'],
            'label' => $c['cuenta'] . ' - ' . $c['nombre'],
            'tipo_dato' => $c['tipo_dato'],
        ];
    }
}
echo json_encode($response);

exit;
