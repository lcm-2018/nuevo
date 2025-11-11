<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
$buscar = mb_strtoupper($_POST['term']);

include '../../../../../config/autoloader.php';

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `tb_terceros`.`id_tercero`
                , `tb_terceros`.`tipo_doc`
                , `tb_terceros`.`nit_tercero`
                , `tb_terceros`.`nom_tercero`
                , `tb_terceros`.`estado`
                , `tb_terceros`.`id_tercero_api`
            FROM
                `tb_terceros`
            WHERE `tb_terceros`.`nit_tercero` LIKE '%$buscar%' 
               OR `tb_terceros`.`nom_tercero` LIKE '%$buscar%'
            ORDER BY `tb_terceros`.`nom_tercero` ASC";
    $rs = $cmd->query($sql);
    $terceros = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$data = [];
foreach ($terceros as $s) {
    $nom_tercero = mb_strtoupper($s['nom_tercero']) . ' -> ' . $s['nit_tercero'];
    $pos = strpos($nom_tercero, $buscar);
    if ($pos !== false) {
        $data[] = [
            'id' => $s['id_tercero_api'],
            'label' => $nom_tercero,
        ];
    }
}

if (empty($data)) {
    $data[] = [
        'id' => '0',
        'label' => 'No hay coincidencias...',
    ];
}
echo json_encode($data);
