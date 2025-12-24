<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

use Config\Clases\Logs;
use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
//Permisos: 1-Consultar,2-Crear,3-Editar,4-Eliminar,5-Anular,6-Imprimir

$oper = isset($_POST['oper']) ? $_POST['oper'] : exit('Acción no permitida');
$fecha_crea = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha_ope = date('Y-m-d H:i:s');
$id_usr_ope = $_SESSION['id_user'];
$res = array();

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    if (($permisos->PermisosUsuario($opciones, 5016, 2) && $oper == 'add' && $_POST['id_prescomercial'] == -1) ||
        ($permisos->PermisosUsuario($opciones, 5016, 3) && $oper == 'add' && $_POST['id_prescomercial'] != -1) ||
        ($permisos->PermisosUsuario($opciones, 5016, 4) && $oper == 'del') || $id_rol == 1
    ) {

        if ($oper == 'add') {
            $id = $_POST['id_prescomercial'];
            $nom_presentacion = $_POST['txt_nom_prescomercial'];
            $cantidad = $_POST['txt_cantidad'] ? $_POST['txt_cantidad'] : 1;

            if ($id == -1) {
                $sql = "INSERT INTO far_presentacion_comercial(nom_presentacion,cantidad,id_usr_crea) 
                        VALUES('$nom_presentacion',$cantidad,$id_usr_ope)";
                $rs = $cmd->query($sql);

                if ($rs) {
                    $res['mensaje'] = 'ok';
                    $sql_i = 'SELECT LAST_INSERT_ID() AS id';
                    $rs = $cmd->query($sql_i);
                    $obj = $rs->fetch();
                    $res['id'] = $obj['id'];
                } else {
                    $res['mensaje'] = $cmd->errorInfo()[2];
                }
            } else {
                $sql = "UPDATE far_presentacion_comercial 
                        SET nom_presentacion='$nom_presentacion',cantidad=$cantidad 
                        WHERE id_prescom=" . $id;
                $rs = $cmd->query($sql);

                if ($rs) {
                    $res['mensaje'] = 'ok';
                    $res['id'] = $id;
                } else {
                    $res['mensaje'] = $cmd->errorInfo()[2];
                }
            }
        }

        if ($oper == 'del') {
            $id = $_POST['id'];
            $sql = "DELETE FROM far_presentacion_comercial WHERE id_prescom=" . $id;
            $rs = $cmd->query($sql);
            if ($rs) {
                Logs::guardaLog($sql);
                $res['mensaje'] = 'ok';
            } else {
                $res['mensaje'] = $cmd->errorInfo()[2];
            }
        }
    } else {
        $res['mensaje'] = 'El Usuario del Sistema no tiene Permisos para esta Acción';
    }

    $cmd = null;
} catch (PDOException $e) {
    $res['mensaje'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
echo json_encode($res);
