<?php

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}

include_once '../../../../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;
use Config\Clases\Conexion;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$cmd = Conexion::getConexion();

try {
    $sql = "SELECT 
                `ctt_firmas`.`id`,
                `ctt_variables_forms`.`variable`,
                `tb_terceros`.`nom_tercero`,
                `ctt_firmas`.`cargo`,
                `ctt_firmas`.`nom_imagen`
            FROM
                `ctt_firmas`
            INNER JOIN `ctt_variables_forms` 
                ON (`ctt_firmas`.`id_variable` = `ctt_variables_forms`.`id_var`)
            INNER JOIN `tb_terceros` 
                ON (`ctt_firmas`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
            ORDER BY `ctt_variables_forms`.`variable`";
    $rs = $cmd->query($sql);
    $firmas = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

if (!empty($firmas)) {
    foreach ($firmas as $firma) {
        $id_firma = $firma['id'];
        $editar = $borrar = null;

        if ($permisos->PermisosUsuario($opciones, 5301, 3) || $id_rol == 1) {
            $editar = '<a value="' . $id_firma . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 editarFirma" title="Editar"><span class="fas fa-pencil-alt"></span></a>';
        }
        if ($permisos->PermisosUsuario($opciones, 5301, 4) || $id_rol == 1) {
            $borrar = '<a value="' . $id_firma . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 borrarFirma" title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
        }

        $data[] = [
            'id' => $id_firma,
            'variable' => $firma['variable'],
            'responsable' => $firma['nom_tercero'] . ' - ' . $firma['cargo'],
            'botones' => '<div class="text-center">' . $borrar . $editar . '</div>',
        ];
    }
} else {
    $data = [];
}

$datos = ['data' => $data];

echo json_encode($datos);
