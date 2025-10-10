<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include_once '../../../../../config/autoloader.php';
$id = isset($_POST['id']) ? $_POST['id'] : exit('Acción no permitida');
$response['status'] = 'ok';
$response['filtro'] = '0';
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT `filtro_adq` FROM `tb_area_c` WHERE `id_area` = $id";
    $rs = $cmd->query($sql);
    $datos = $rs->fetch();
    $area = !empty($datos) ? $datos['filtro_adq'] : 0;
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
if ($area == 0) {
    $response['tipo'] = '0';
} else {
    $response['tipo'] = '1';
    try {
        $cmd = \Config\Clases\Conexion::getConexion();

        $sql = "SELECT
                    `tb_tipo_compra`.`tipo_compra`
                    , `tb_tipo_contratacion`.`tipo_contrato`
                    , `tb_tipo_bien_servicio`.`tipo_bn_sv`
                    , `tb_tipo_bien_servicio`.`id_tipo_b_s`
                FROM
                    `tb_tipo_bien_servicio`
                    INNER JOIN `tb_tipo_contratacion` 
                        ON (`tb_tipo_bien_servicio`.`id_tipo` = `tb_tipo_contratacion`.`id_tipo`)
                    INNER JOIN `tb_tipo_compra` 
                        ON (`tb_tipo_contratacion`.`id_tipo_compra` = `tb_tipo_compra`.`id_tipo`)
                WHERE (`tb_tipo_bien_servicio`.`filtro_adq` = $area)";
        $rs = $cmd->query($sql);
        $valores = $rs->fetch();
        if (!empty($valores)) {
            $response['id'] = $valores['id_tipo_b_s'];
            $response['nombre'] = $valores['tipo_compra'] . ' -> ' . $valores['tipo_contrato'] . ' -> ' . $valores['tipo_bn_sv'];
            $response['filtro'] = $area;
        } else {
            $response['status'] = 'error';
            $response['msg'] = 'No se encontraron datos';
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}
echo json_encode($response);
