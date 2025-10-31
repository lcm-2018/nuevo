
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit();
}
include '../../../config/autoloader.php';
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    $sql = "SELECT
                COUNT(tb_municipios.nom_municipio) AS numero
                , tb_municipios.nom_municipio
            FROM
                tb_terceros
                INNER JOIN tb_municipios ON (tb_terceros.id_municipio = tb_municipios.id_municipio)
            GROUP BY tb_municipios.nom_municipio";
    $rs = $cmd->query($sql);
    $obj_terceros = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$data = [];
foreach ($obj_terceros as $obj) {
    $data[] = [
        'numero' => $obj['numero'],
        'municipio' => $obj['nom_municipio'],
    ];
}

if (empty($data)) {
    $data[] = [
        'numero' => '0',
        'municipio' => 'No hay coincidencias...',
    ];
}
echo json_encode($data);
