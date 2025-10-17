<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include_once '../../../../../config/autoloader.php';

$buscar = isset($_POST['term']) ? $_POST['term'] : exit('Acción no permitida ***');
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `tbs`.`id_tipo_b_s`
                , `tbs`.`tipo_bn_sv`
                , `ttc`.`tipo_compra`
                , `tbs`.`objeto_definido`
            FROM
                `tb_tipo_bien_servicio` `tbs` 
                INNER JOIN `tb_tipo_compra` `ttc`
                    ON (`tbs`.`id_tipo` = `ttc`.`id_tipo`)
            WHERE (`tbs`.`tipo_bn_sv` LIKE '%$buscar%' OR `ttc`.`tipo_compra` LIKE '%$buscar%')
            ORDER BY `ttc`.`tipo_compra` ASC, `tbs`.`tipo_bn_sv` ASC";
    $rs = $cmd->query($sql);
    $tipo_servicio = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$data = [];
if (!empty($tipo_servicio)) {
    foreach ($tipo_servicio as $ts) {
        $tipo = $ts['tipo_compra'] . ' -> ' . $ts['tipo_bn_sv'];
        // en $tipo buscar el termino y reemplazar por negrita
        $data[] = [
            'id' => $ts['id_tipo_b_s'],
            'label' => $tipo,
            'objeto' => $ts['objeto_definido'],
        ];
    }
} else {
    $data[] = [
        'id' => '0',
        'label' => 'No hay coincidencias...',
        'objeto' => '',
    ];
}
echo json_encode($data);
