<?php

use Config\Clases\Logs;
use Src\Common\Php\Clases\Permisos;

if (isset($_POST['tipo'])) {
    session_start();

    include '../../../../config/autoloader.php';
    include '../common/funciones_generales.php';
    include '../common/funciones_kardex.php';

    $id_rol = $_SESSION['rol'];
    $id_user = $_SESSION['id_user'];

    $permisos = new Permisos();
    $opciones = $permisos->PermisoOpciones($id_user);
    //Permisos: 1-Consultar,2-Crear,3-Editar,4-Eliminar,5-Anular,6-Imprimir

    try {

        if ($permisos->PermisosUsuario($opciones, 5009, 2) || $permisos->PermisosUsuario($opciones, 5009, 3) || $id_rol == 1) {

            $idlot = isset($_POST['art']) ? implode(",", $_POST['art']) : '';
            $res['mensaje'] = 'Error';

            if ($idlot != '') {
                $tipo = $_POST['tipo'];
                $iding = $_POST['id_ing'];
                $idegr = $_POST['id_egr'];
                $idtra = $_POST['id_tra'];
                $idegr_r = $_POST['id_egr_r'];
                $iding_r = 0;
                $iddev = 0;
                $fecini = $_POST['fec_ini'];

                set_time_limit(0);
                $res = array();

                $cmd = \Config\Clases\Conexion::getConexion();;

                $cmd->beginTransaction();

                recalcular_kardex($cmd, $idlot, $tipo, $iding, $idegr, $idtra, $iding_r, $idegr_r, $iddev, $fecini);

                /*Cuenta cuantos errores ocurrieron al ejecutar el script*/
                $errores = error_get_last();
                if (!$errores) {
                    $cmd->commit();
                    $res['mensaje'] = 'ok';
                    $consulta = "Recalcula el Kardex desde la fecha : " . $fecini . " hasta la fecha : " . date('Y-m-d H:i:s');
                    Logs::guardaLog($consulta);
                } else {
                    $res['mensaje'] = 'Error de Ejecución de Proceso';
                    $cmd->rollBack();
                }
            } else {
                $res['mensaje'] = 'Debe seleccionar un registro para reclacular kardex';
            }
        } else {
            $res['mensaje'] = 'El Usuario del Sistema no tiene Permisos para esta Acción';
        }

        $cmd = null;
    } catch (PDOException $e) {
        $res['mensaje'] = $e->getCode();
    }
    echo json_encode($res);
}
