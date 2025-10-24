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
                `rel`.`id_relacion`
                , `rel`.`user1`
                , `sus`.`num_documento`
                , CONCAT_WS(`sus`.`nombre1`, `sus`.`nombre2`
                , `sus`.`apellido1`, `sus`.`apellido2`) AS `nombre`
            FROM
                `ctt_relacion_user` AS `rel`
                INNER JOIN `seg_usuarios_sistema` AS `sus` 
                    ON (`rel`.`user_rel` = `sus`.`id_usuario`)
            WHERE (`rel`.`user1` = $id)";
    $rs = $cmd->query($sql);
    $relaciones = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
if (!empty($relaciones)) {
    foreach ($relaciones as $r) {
        $id = $r['id_relacion'];
        $editar = $borrar = null;
        $editar = '<a value="' . $id . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 editar" title="Editar"><span class="fas fa-pencil-alt"></span></a>';
        $borrar = '<a value="' . $id . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 borrar" title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
        $data[] = [
            'id' => $id,
            'documento' => $r['num_documento'],
            'nombre' => $r['nombre'],
            'botones' => '<div class="text-center">' . $editar . $borrar . '</div>',
        ];
    }
} else {
    $data = [];
}

$datos = ['data' => $data];

echo json_encode($datos);
