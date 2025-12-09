<?php

use Src\Common\Php\Clases\Permisos;

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}

require_once '../../../../../config/autoloader.php';

$permisos = new Permisos();

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$opciones = $permisos->PermisoOpciones($id_user);

$id_adq = isset($_POST['id_adq']) ? $_POST['id_adq'] : exit('Acción no permitida');

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT 
                `ctt_clasificador_bs`.`id_clas`,
                `ctt_clasificador_bs`.`id_adq`,
                `ctt_clasificador_bs`.`id_unspsc`,
                `tb_codificacion_unspsc`.`codigo`,
                `tb_codificacion_unspsc`.`descripcion`
            FROM 
                `ctt_clasificador_bs`
            INNER JOIN `tb_codificacion_unspsc` 
                ON (`ctt_clasificador_bs`.`id_unspsc` = `tb_codificacion_unspsc`.`id_codificacion`)
            WHERE `ctt_clasificador_bs`.`id_adq` = ?
            ORDER BY `ctt_clasificador_bs`.`id_clas` ASC";

    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $id_adq, PDO::PARAM_INT);
    $stmt->execute();
    $clasificadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    unset($stmt);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$data = [];
if (!empty($clasificadores)) {
    foreach ($clasificadores as $cls) {
        $editar = '';
        $borrar = '';

        if ($permisos->PermisosUsuario($opciones, 5302, 3) || $id_rol == 1) {
            $editar = '<a value="' . $cls['id_clas'] . '" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow editar" title="Editar"><span class="fas fa-pencil-alt"></span></a>';
        }
        if ($permisos->PermisosUsuario($opciones, 5302, 4) || $id_rol == 1) {
            $borrar = '<a value="' . $cls['id_clas'] . '" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow borrar" title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
        }

        $data[] = [
            'id_clas' => $cls['id_clas'],
            'codigo' => $cls['codigo'],
            'descripcion' => $cls['descripcion'],
            'botones' => '<div class="text-center">' . $editar . $borrar . '</div>',
        ];
    }
}
$datos = ['data' => $data];

echo json_encode($datos);
