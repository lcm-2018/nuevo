<?php
$sessionStarted = false;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    $sessionStarted = true;
}
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include_once '../../../../../config/autoloader.php';
$id_rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : null;
$id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null;
$permisos = new \Src\Common\Php\Clases\Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$cmd = \Config\Clases\Conexion::getConexion();

$id_relacion = isset($_POST['id']) ? (int) $_POST['id'] : exit('Acceso no disponible');

try {
    
    $sql = "SELECT
                `ctt_formatos_doc_rel`.`id_relacion`
                , `ctt_formatos_doc`.`descripcion`
                ,  `tb_tipo_bien_servicio`.`tipo_bn_sv`
            FROM
                `ctt_formatos_doc_rel`
                INNER JOIN `ctt_formatos_doc` 
                    ON (`ctt_formatos_doc_rel`.`id_formato` = `ctt_formatos_doc`.`id_fdoc`)
                INNER JOIN `tb_tipo_bien_servicio` 
                    ON (`ctt_formatos_doc_rel`.`id_tipo_bn_sv` = `tb_tipo_bien_servicio`.`id_tipo_b_s`)
            WHERE `ctt_formatos_doc_rel`.`id_relacion` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $id_relacion, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
// ir a '../../../adquisiciones/soportes/$idbs.docx, tomar el formato y descargarlo
if (isset($data)) {
    $file = '../../../adquisiciones/soportes/' . $data['id_relacion'] . '.docx';
    if (file_exists($file)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $data['tipo_bn_sv'] . ' - ' . $data['descripcion'] . '.docx"');
        readfile($file);
    } else {
        echo 'El archivo no existe';
    }
}
