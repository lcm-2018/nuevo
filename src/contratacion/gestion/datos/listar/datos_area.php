<?php

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}

include_once '../../../../../config/autoloader.php';

use Config\Clases\Conexion;

$cmd = Conexion::getConexion();

$id = isset($_POST['id_user']) ? $_POST['id_user'] : 0;
try {
    $sql = "SELECT
                `ctt_area_user`.`id_resp`
                , `ctt_area_user`.`id_user`
                , `ctt_area_user`.`id_area`
                , `tb_area_c`.`area`
                , `ctt_area_user`.`estado`
            FROM
                `ctt_area_user`
                INNER JOIN `tb_area_c` 
                    ON (`ctt_area_user`.`id_area` = `tb_area_c`.`id_area`)
            WHERE `ctt_area_user`.`id_user` = $id";
    $rs = $cmd->query($sql);
    $areas = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
if (!empty($areas)) {
    foreach ($areas as $a) {
        $id = $a['id_resp'];
        $editar = $borrar = null;
        $editar = '<a value="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 editar" title="Editar"><span class="fas fa-pencil-alt"></span></a>';
        $borrar = '<a value="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 borrar" title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
        $estado = $a['estado'] == 1 ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>';
        $data[] = [
            'id' => $id,
            'area' => $a['area'],
            'estado' => '<div class="text-center">' . $estado . '</div>',
            'botones' => '<div class="text-center">' . $editar . $borrar . '</div>',
        ];
    }
} else {
    $data = [];
}

$datos = ['data' => $data];

echo json_encode($datos);
