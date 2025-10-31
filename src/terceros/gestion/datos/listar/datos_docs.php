<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include '../../../../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$opciones = $permisos->PermisoOpciones($id_user);

$id_t = isset($_POST['id_t']) ? $_POST['id_t'] : exit('Acción no permitida');


try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `ctt_documentos`.`fec_inicio`
                , `ctt_documentos`.`fec_vig`
                , `ctt_documentos`.`id_soportester`
                , `ctt_soportes_contrato`.`descripcion`
            FROM
                `ctt_documentos`
                INNER JOIN `ctt_soportes_contrato` 
                    ON (`ctt_documentos`.`id_soporte` = `ctt_soportes_contrato`.`id_soporte`)
            WHERE (`ctt_documentos`.`id_tercero` = $id_t)";
    $rs = $cmd->query($sql);
    $docs = $rs->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

if (!empty($docs)) {
    foreach ($docs as $d) {
        $id_doc = $d['id_soportester'];
        $borrar = '';
        if ($d['fec_vig'] > date('Y-m-d')) {
            $estado = '<span class="fas fa-toggle-on fa-lg text-success estado activo" ></span>';
        } else {
            $estado = '<span class="fas fa-toggle-off fa-lg text-secondary estado inactivo"></span>';
        }
        if ($permisos->PermisosUsuario($opciones, 5201, 4) || $id_rol == 1) {
            $borrar = '<a onclick="BorrarDocumentoTercero(' . $id_doc . ')" class="btn btn-outline-warning btn-xs rounded-circle shadow me-1 "  title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
        }
        $data[] = [
            'id_doc' => $id_doc,
            'tipo' => mb_strtoupper($d['descripcion']),
            'fec_inicio' => $d['fec_inicio'],
            'fec_vigencia' => $d['fec_vig'],
            'vigente' => '<div class="text-center">' . $estado . '</div>',
            'doc' => '<div class="text-center"><button text="' . $id_doc . '" class="btn btn-outline-danger btn-xs rounded-circle shadow me-1 descargar" title="Descargar"><span class="far fa-file-pdf"></span></button>' . $borrar . '</div>',
        ];
    }
} else {
    $data = [];
}

$datos = ['data' => $data];

echo json_encode($datos);
