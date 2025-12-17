<?php
$_post = json_decode(file_get_contents('php://input'), true);
$id = $_post['id'];
include '../../../../config/autoloader.php';

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $query = $cmd->prepare("DELETE FROM ctb_doc WHERE id_ctb_doc = ?");
    $query->bindParam(1, $id);
    $query->execute();
    if ($query->rowCount() > 0) {
        include '../../../financiero/reg_logs.php';
        $ruta = '../../../log';
        $consulta = "DELETE FROM ctb_doc WHERE id_ctb_doc = $id";
        RegistraLogs($ruta, $consulta);
        echo 'ok';
    } else {
        echo $query->errorInfo()[2];
    }
    $cmd = null;
} catch (Exception $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
