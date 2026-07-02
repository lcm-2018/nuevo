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
        ($permisos->PermisosUsuario($opciones, 3001, 3) && $oper == 'add_bd' && $_POST['id_consulta'] != -1) ||
        ($permisos->PermisosUsuario($opciones, 3001, 3) && $oper == 'del_bd' && $_POST['id_consulta'] != -1) ||
        ($permisos->PermisosUsuario($opciones, 3001, 3) && $oper == 'add_usr' && $_POST['id_consulta'] != -1) ||
        ($permisos->PermisosUsuario($opciones, 3001, 3) && $oper == 'del_usr' && $_POST['id_consulta'] != -1) ||
        ($permisos->PermisosUsuario($opciones, 3001, 4) && $oper == 'del') || $id_rol == 1
    ) {

        if ($oper == 'add') {

            $sensql = ['alter', 'analyze', 'create', 'delete', 'drop',
                'explain', 'grant', 'revoke', 'handler', 'insert',
                'kill', 'lock', 'rename', 'replace', 'reset',
                'show', 'truncate', 'update', 'use '
            ];

            $sentencia = strtolower($_POST['txt_consulta_sql']);
            $error = '';
            foreach ($sensql as $palabra) {
                if (strpos($sentencia, $palabra) !== false) {
                    $error .= ' - ' . $palabra;
                }
            }
        
            if ($error == ''){
                $id = $_POST['id_consulta'] ?? -1;
                $data = [
                    'titulo_consulta' => $_POST['txt_titulo_consulta'],
                    'detalle_consulta' => $_POST['txt_detalle_consulta'],
                    'tipo_bdatos' => $_POST['sl_tipo_bdatos'] ?: 0,
                    'consulta_sql' => $_POST['txt_consulta_sql'],
                    'consulta_sql_group' => $_POST['txt_consulta_sql_group'],
                    'tipo_informe' => $_POST['sl_tipo_informe'] ?: 0,
                    'tipo_consulta' => $_POST['sl_tipo_consulta'] ?: 0,                
                    'tipo_acceso' => $_POST['sl_tipo_acceso'] ?: 0,
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
            } else {
                $res['mensaje'] = 'Palabra prohibida en la consulta SQL:' . $error;
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

        if ($oper == 'add_bd') {
            $id_consulta = isset($_POST['id_consulta']) ? (int)$_POST['id_consulta'] : 0;
            $ok = $model->add_bd($id_consulta, $_POST['id_bdatos']);
            if ($ok) {
                $res['mensaje'] = 'ok';
            } else {
                $res['mensaje'] = 'Error al activar base de datos';
            }
        }
        if ($oper == 'del_bd') {
            $id_consulta = isset($_POST['id_consulta']) ? (int)$_POST['id_consulta'] : 0;
            $ok = $model->delete_bd($id_consulta, $_POST['id_bdatos']);
            if ($ok) {                
                $res['mensaje'] = 'ok';
            } else {
                $res['mensaje'] = 'Error al desactivar base de datos';
            }
        }

        if ($oper == 'add_usr') {
            $id_consulta = isset($_POST['id_consulta']) ? (int)$_POST['id_consulta'] : 0;
            if ($model->search_user($id_consulta, $_POST['id_usuario']) == 0) {
                $ok = $model->add_user($id_consulta, $_POST['id_usuario']);
                if ($ok) {
                    $res['mensaje'] = 'ok';
                } else {
                    $res['mensaje'] = 'Error al adicionar un usuario';
                }
            } else {    
                $res['mensaje'] = 'El usuario ya esta registrado en la consulta';
            }
            
        }
        if ($oper == 'del_usr') {
            $id_consulta = isset($_POST['id_consulta']) ? (int)$_POST['id_consulta'] : 0;
            $ok = $model->delete_user($id_consulta, $_POST['id_usuario']);
            if ($ok) {                
                $res['mensaje'] = 'ok';
            } else {
                $res['mensaje'] = 'Error al eliminar un usuario';
            }
        }

        if ($oper == 'add_param') {
            $id_parametro = $_POST['id_parametro'] ?? -1;
            $data = [
                'id_consulta' => $_POST['id_consulta'],
                'parametro' => $_POST['txt_parametro'],
                'etiqueta' => $_POST['txt_etiqueta'],
                'descripcion' => $_POST['txt_des_parametro'],
                'tipo' => $_POST['sl_tip_parametro'] ?: 0,
                'detalles' => $_POST['txt_det_parametro']
            ];

            if ($id_parametro == -1) {
                $newId = $model->insert_parametro($data);
                if ($newId) {
                    $res['mensaje'] = 'ok';
                    $res['id'] = $newId;
                } else {
                    $res['mensaje'] = 'Error al insertar registro';
                }
            } else {
                $ok = $model->update_parametro((int)$id_parametro, $data);
                if ($ok) {
                    $res['mensaje'] = 'ok';
                    $res['id'] = $id_parametro;
                } else {
                    $res['mensaje'] = 'Error al actualizar registro';
                }
            }
        }

        if ($oper == 'del_param') {
            $id_parametro = isset($_POST['id_parametro']) ? (int)$_POST['id_parametro'] : 0;
            $ok = $model->delete_parametro($id_parametro);
            if ($ok) {
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
