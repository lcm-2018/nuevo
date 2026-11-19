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
include '../common/funciones_generales.php';

$oper = isset($_POST['oper']) ? $_POST['oper'] : exit('Acción no permitida');
$fecha_crea = date('Y-m-d H:i:s');
$id_usr_crea = $_SESSION['id_user'];
$res = [];

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    if (
        ($permisos->PermisosUsuario($opciones, 5006, 2) && $oper == 'add' && $_POST['id_rem_int'] == -1) ||
        ($permisos->PermisosUsuario($opciones, 5006, 3) && $oper == 'add' && $_POST['id_rem_int'] != -1) ||
        ($permisos->PermisosUsuario($opciones, 5006, 4) && $oper == 'del') || $id_rol == 1
    ) {

        $id_tercero = $_POST['id_tercero'];

        if ($id_tercero > 0) {
            if ($oper == 'add') {
                $id = $_POST['id_rem_int'];                
                $num_remision = $_POST['txt_num_remision'];
                $desc_remision = $_POST['txt_desc_remision'];                

                if ($id == -1) {
                    $sql = "INSERT INTO far_remision_interna(id_tercero,num_remision,desc_remision,fec_creacion,id_usr_crea,estado)  
                        VALUES($id_tercero,'$num_remision','$desc_remision','$fecha_crea',$id_usr_crea,1)";
                    $rs = $cmd->query($sql);

                    if ($rs) {
                        $res['mensaje'] = 'ok';
                        $sql_i = 'SELECT LAST_INSERT_ID() AS id';
                        $rs = $cmd->query($sql_i);
                        $obj = $rs ? $rs->fetch() : null;
                        $res['id'] = $obj['id'] ?? null;
                    } else {
                        $res['mensaje'] = $cmd->errorInfo()[2];
                    }
                } else {
                    $sql = "UPDATE far_remision_interna SET num_remision='$num_remision',desc_remision='$desc_remision'
                            WHERE id_rem_int=" . $id;
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
                $sql = "DELETE FROM far_remision_interna WHERE id_rem_int=" . $id;
                $rs = $cmd->query($sql);
                if ($rs) {
                    $res['mensaje'] = 'ok';
                } else {
                    $res['mensaje'] = $cmd->errorInfo()[2];
                }
            }
            
        } else {
            $res['mensaje'] = 'Primero debe seleccionar un Tercero';
        }
    } else {
        $res['mensaje'] = 'El Usuario del Sistema no tiene Permisos para esta Acción';
    }

    $cmd = null;
} catch (PDOException $e) {
    $res['mensaje'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
echo json_encode($res);
