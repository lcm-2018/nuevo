<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo '<script>window.location.replace("../../../index.php");</script>';
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

    if (($permisos->PermisosUsuario($opciones, 5001, 2) && $oper == 'add' && $_POST['id_laboratorio'] == -1) ||
        ($permisos->PermisosUsuario($opciones, 5001, 3) && $oper == 'add' && $_POST['id_laboratorio'] != -1) ||
        ($permisos->PermisosUsuario($opciones, 5001, 4) && $oper == 'del') || $id_rol == 1
    ) {

        if ($oper == 'add') {
            $id = $_POST['id_lab'];
            $nom_laboratorio = $_POST['txt_nom_laboratorio'];

            if ($id == -1) {
                $sql = "INSERT INTO far_laboratorios(nom_laboratorio) VALUES('$nom_laboratorio')";
                $rs = $cmd->query($sql);

                if ($rs) {
                    $res['mensaje'] = 'ok';
                    $sql_i = 'SELECT LAST_INSERT_ID() AS id';
                    $rs = $cmd->query($sql_i);
                    $obj = $rs->fetch();
                    $res['id'] = $obj['id'];

                    $proceso = "Se Registró Laboratorio Id: " . $obj['id'] . ", Nombre: " . $nom_laboratorio;
                    Logs::guardaLog($proceso);
                } else {
                    $res['mensaje'] = $cmd->errorInfo()[2];
                }
            } else {
                $sql = "UPDATE far_laboratorios SET nom_laboratorio='$nom_laboratorio' WHERE id_lab=" . $id;
                $rs = $cmd->query($sql);

                if ($rs) {
                    $res['mensaje'] = 'ok';
                    $res['id'] = $id;

                    $proceso = "Se Modificó Laboratorio Id: " . $id . ", Nombre: " . $nom_laboratorio;
                    Logs::guardaLog($proceso);
                } else {
                    $res['mensaje'] = $cmd->errorInfo()[2];
                }
            }
        }

        if ($oper == 'del') {
            $id = $_POST['id'];
            $sql = "SELECT id_lab,nom_laboratorio FROM far_laboratorios WHERE id_lab=" . $id;
            $rs = $cmd->query($sql);
            $obj = $rs->fetch();            

            $sql = "DELETE FROM far_laboratorios WHERE id_lab=" . $id;
            $rs = $cmd->query($sql);
            if ($rs) {
                $res['mensaje'] = 'ok';

                $proceso = "Se Eliminó Laboratorio Id: " . $obj['id_lab'] . ", Nombre: " . $obj['nom_laboratorio'];
                Logs::guardaLog($proceso);                
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
