<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

use Config\Clases\Logs;
use Src\Common\Php\Clases\Permisos;
use Src\Analytics\Conf_Consultas\Php\Clases\ConsultasModel;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$fecha_actual = date('Y-m-d H:i:s');

$oper = isset($_POST['oper']) ? $_POST['oper'] : exit('Acción no permitida');
$res = array();

try {    
    $permisos = new Permisos();
    $opciones = $permisos->PermisoOpciones($id_user);
    //Permisos: 1-Consultar,2-Crear,3-Editar,4-Eliminar,5-Anular,6-Imprimir

    $model = new ConsultasModel();

    if (($permisos->PermisosUsuario($opciones, 3001, 2) && $oper == 'add' && $_POST['id_consulta'] == -1) ||
        ($permisos->PermisosUsuario($opciones, 3001, 3) && $oper == 'add' && $_POST['id_consulta'] != -1) ||
        ($permisos->PermisosUsuario($opciones, 3001, 4) && $oper == 'del') || $id_rol == 1
    ) {

        if ($oper == 'add') {
            $id = $_POST['id_consulta'] ?? -1;
            $data = [
                'titulo_consulta' => $_POST['txt_titulo_consulta'],
                'detalle_consulta' => $_POST['txt_detalle_consulta'],
                'tipo_analitica' => $_POST['sl_tipo_analitica'],
                'tipo_bdatos' => $_POST['sl_tipo_bdatos'],
                'consulta_sql' => $_POST['txt_consulta_sql'],
                'consulta_sql_group' => $_POST['txt_consulta_sql_group'],
                'tipo_informe' => $_POST['sl_tipo_informe'],
                'tipo_consulta' => $_POST['sl_tipo_consulta'],
                'tipo_acceso' => $_POST['sl_tipo_acceso'],
                'estado' => $_POST['sl_estado'],
                'id_usr_crea' => $id_user,
                'fec_crea' => $fecha_actual,
            ];

            if ($id == -1) {
                $newId = $model->insert($data);
                if ($newId) {
                    $res['mensaje'] = 'ok';
                    $res['id'] = $newId;
                } else {
                    $res['mensaje'] = 'Error al insertar registro';
                }
            } else {
                $ok = $model->update((int)$id, $data);
                if ($ok) {
                    $res['mensaje'] = 'ok';
                    $res['id'] = $id;
                } else {
                    $res['mensaje'] = 'Error al actualizar registro';
                }
            }
        }

        if ($oper == 'del') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $ok = $model->delete($id);
            if ($ok) {
                Logs::guardaLog("DELETE FROM dash_consultas WHERE id_consulta={$id}");
                $res['mensaje'] = 'ok';
            } else {
                $res['mensaje'] = 'Error al eliminar registro';
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
