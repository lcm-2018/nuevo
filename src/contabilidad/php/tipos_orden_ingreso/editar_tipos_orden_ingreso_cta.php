<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo '<script>window.location.replace("../../../index.php");</script>';
    exit();
}
include '../../../../config/autoloader.php';
$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

use Config\Clases\Logs;
use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
//Permisos: 1-Consultar,2-Crear,3-Editar,4-Eliminar,5-Anular,6-Imprimir

$oper = isset($_POST['oper']) ? $_POST['oper'] : exit('Acción no permitida');
$fecha_crea = date('Y-m-d H:i:s');
$id_usr_crea = $_SESSION['id_user'];
$res = [];

try {
    $cmd = \Config\Clases\Conexion::getConexion();

    if (
        ($permisos->PermisosUsuario($opciones, 5512, 2) && $oper == 'add' && $_POST['id_tipo_ingreso_cta'] == -1) ||
        ($permisos->PermisosUsuario($opciones, 5512, 3) && $oper == 'add' && $_POST['id_tipo_ingreso_cta'] != -1) ||
        ($permisos->PermisosUsuario($opciones, 5512, 4) && $oper == 'del') || $id_rol == 1
    ) {

        $id_tipo_ingreso = $_POST['id_tipo_ingreso'];

        if ($id_tipo_ingreso > 0) {
            if ($oper == 'add') {
                $id = $_POST['id_tipo_ingreso_cta'];
                $id_cta = $_POST['id_txt_cta_con'] ? $_POST['id_txt_cta_con'] : 'NULL';
                $fec_vig = $_POST['txt_fec_vig'] ? "'" . $_POST['txt_fec_vig'] . "'" : 'NULL';
                $estado = $_POST['sl_estado_cta'];

                if ($id == -1) {
                    $sql = "INSERT INTO far_orden_ingreso_tipo_cta(id_tipo_ingreso,id_cuenta,fecha_vigencia,estado,id_usr_crea,fec_creacion)  
                            VALUES($id_tipo_ingreso,$id_cta,$fec_vig,$estado,$id_usr_crea,'$fecha_crea')";
                    $rs = $cmd->query($sql);

                    if ($rs) {
                        $res['mensaje'] = 'ok';
                        Logs::guardaLog("INSERT INTO far_orden_ingreso_tipo_cta(id_tipo_ingreso,id_cuenta,fecha_vigencia,estado,id_usr_crea) VALUES($id_tipo_ingreso,$id_cta,$fec_vig,$estado,$id_usr_crea)");
                        $sql_i = 'SELECT LAST_INSERT_ID() AS id';
                        $rs = $cmd->query($sql_i);
                        $obj = $rs ? $rs->fetch() : null;
                        $res['id'] = $obj['id'] ?? null;
                    } else {
                        $res['mensaje'] = $cmd->errorInfo()[2];
                    }
                } else {
                    $sql = "UPDATE far_orden_ingreso_tipo_cta 
                            SET id_cuenta=$id_cta,fecha_vigencia=$fec_vig,estado=$estado
                            WHERE id_tipo_ingreso_cta=" . $id;
                    $rs = $cmd->query($sql);

                    if ($rs) {
                        $res['mensaje'] = 'ok';
                        Logs::guardaLog("UPDATE far_orden_ingreso_tipo_cta SET id_cuenta=$id_cta,fecha_vigencia=$fec_vig,estado=$estado WHERE id_tipo_ingreso_cta=$id");
                        $res['id'] = $id;
                    } else {
                        $res['mensaje'] = $cmd->errorInfo()[2];
                    }
                }
            }

            if ($oper == 'del') {
                $id = $_POST['id'];
                $sql = "DELETE FROM far_orden_ingreso_tipo_cta WHERE id_tipo_ingreso_cta=" . $id;
                $rs = $cmd->query($sql);
                if ($rs) {
                    $consulta = "DELETE FROM far_orden_ingreso_tipo_cta WHERE id_tipo_ingreso_cta = $id";
                    Logs::guardaLog($consulta);
                    $res['mensaje'] = 'ok';
                } else {
                    $res['mensaje'] = $cmd->errorInfo()[2];
                }
            }
        } else {
            $res['mensaje'] = 'Primero debe guardar el Tipo de Orden de Ingreso';
        }
    } else {
        $res['mensaje'] = 'El Usuario del Sistema no tiene Permisos para esta Acción';
    }

    $cmd = null;
} catch (PDOException $e) {
    $res['mensaje'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
echo json_encode($res);
