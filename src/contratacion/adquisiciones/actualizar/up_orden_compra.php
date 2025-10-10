<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
$id_orden = isset($_POST['id_orden']) ? $_POST['id_orden'] : exit('Accion no permitida');
$aprobados = $_POST['aprobado'];
$ivas = $_POST['iva'];
$cantidades = $_POST['cantidad'];
$val_unitarios = $_POST['val_unid'];
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$c = 0;
include_once '../../../../../config/autoloader.php';
$id_rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : null;
$id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null;
$permisos = new \Src\Common\Php\Clases\Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$cmd = \Config\Clases\Conexion::getConexion();
try {
    $sql = "UPDATE `far_alm_pedido_detalle`
                SET `aprobado` = ?,`valor` = ?, `iva` = ?
            WHERE `id_ped_detalle` = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $cnt, PDO::PARAM_INT);
    $sql->bindParam(2, $val_un, PDO::PARAM_STR);
    $sql->bindParam(3, $iva, PDO::PARAM_INT);
    $sql->bindParam(4, $id_detalle, PDO::PARAM_INT);
    foreach ($aprobados as $key => $value) {
        $id_detalle = $key;
        $cnt = $cantidades[$key];
        $val_un = $val_unitarios[$key];
        $iva = $ivas[$key];
        if (!($sql->execute())) {
            echo $sql->errorInfo()[2];
            exit();
        } else {
            if ($sql->rowCount() > 0) {
                $c++;
            }
        }
    }
    if ($c > 0) {
        echo 'ok';
    } else {
        echo 'No se ha actualizado ningún registro';
    }
    $cmd = NULL;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
