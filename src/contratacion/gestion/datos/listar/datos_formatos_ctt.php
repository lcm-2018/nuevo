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
                `ctt_formatos_doc_rel`.`id_relacion`
                , `ctt_formatos_doc`.`descripcion`
                , `tb_tipo_bien_servicio`.`tipo_bn_sv`
                , `tb_tipo_compra`.`tipo_compra`
            FROM
                `ctt_formatos_doc_rel`
                INNER JOIN `ctt_formatos_doc` 
                    ON (`ctt_formatos_doc_rel`.`id_formato` = `ctt_formatos_doc`.`id_fdoc`)
                INNER JOIN `tb_tipo_bien_servicio` 
                    ON (`ctt_formatos_doc_rel`.`id_tipo_bn_sv` = `tb_tipo_bien_servicio`.`id_tipo_b_s`)
                INNER JOIN `tb_tipo_compra` 
                    ON (`tb_tipo_bien_servicio`.`id_tipo` = `tb_tipo_compra`.`id_tipo`)";

    $rs = $cmd->query($sql);
    $formatos = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
if (!empty($formatos)) {
    foreach ($formatos as $form) {
        $id_form = $form['id_relacion'];
        $borrar = $descargar = null;

        if ($permisos->PermisosUsuario($opciones, 5301, 4) || $id_rol == 1) {
            $borrar = '<a value="' . $id_form . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 borrar" title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
        }
        if ($permisos->PermisosUsuario($opciones, 5301, 3) || $id_rol == 1) {
            $descargar = '<a value="' . $id_form . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 descargar" title="Descargar formato"><span class="fas fa-download"></span></a>';
        }
        $data[] = [
            'id' => $id_form,
            'formato' => $form['descripcion'],
            'tp_ctt' => $form['tipo_compra'] . ' -> ' . $form['tipo_bn_sv'],
            'botones' => '<div class="text-center">' . $borrar . $descargar . '</div>',
        ];
    }
} else {
    $data = [];
}

$datos = ['data' => $data];

echo json_encode($datos);
