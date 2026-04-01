<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include '../../../../config/autoloader.php';

use Src\Common\Php\Clases\Permisos;

$oper = isset($_POST['oper']) ? $_POST['oper'] : exit('Accion no permitida');
$fecha_crea = date('Y-m-d H:i:s');
$id_usr_crea = (int) $_SESSION['id_user'];
$id_rol = (int) $_SESSION['rol'];
$res = array();

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_usr_crea);

$puedeAdicionar = $id_rol === 1 || $permisos->PermisosUsuario($opciones, 5401, 2) || $permisos->PermisosUsuario($opciones, 5601, 2);
$puedeEditar = $id_rol === 1 || $permisos->PermisosUsuario($opciones, 5401, 3) || $permisos->PermisosUsuario($opciones, 5601, 3);
$puedeEliminar = $id_rol === 1 || $permisos->PermisosUsuario($opciones, 5401, 4) || $permisos->PermisosUsuario($opciones, 5601, 4);

if (($oper === 'add' && !$puedeAdicionar) || ($oper === 'edit' && !$puedeEditar) || ($oper === 'del' && !$puedeEliminar)) {
    $res['mensaje'] = 'El Usuario del Sistema no tiene Permisos para esta Accion';
    echo json_encode($res);
    exit();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $cmd->beginTransaction();

    if ($oper === "add") {
        $fecha = isset($_POST['txt_fecha']) ? trim($_POST['txt_fecha']) : '';
        $id_manu = isset($_POST['txt_id_manu']) ? (int) $_POST['txt_id_manu'] : 0;
        $id_tercero_api = isset($_POST['hd_id_tercero_api']) ? (int) $_POST['hd_id_tercero_api'] : 0;
        $objeto = isset($_POST['txt_objeto']) ? trim($_POST['txt_objeto']) : '';
        $id_ctb_doc = isset($_POST['hd_id_ctb_doc']) ? (int) $_POST['hd_id_ctb_doc'] : 0;
        $num_factura = 0;
        $estado = 2;
        $tipo_movimiento = 4;

        if ($fecha === '' || $id_manu <= 0 || $id_ctb_doc <= 0) {
            throw new RuntimeException('Datos incompletos para registrar la afectacion presupuestal');
        }

        $sql = "INSERT INTO pto_rad (fecha, id_manu, id_tercero_api, objeto, num_factura, estado, id_user_reg, fecha_reg, tipo_movimiento, id_ctb_doc)
                VALUES (:fecha, :id_manu, :id_tercero_api, :objeto, :num_factura, :estado, :id_user_reg, :fecha_reg, :tipo_movimiento, :id_ctb_doc)";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([
            ':fecha' => $fecha,
            ':id_manu' => $id_manu,
            ':id_tercero_api' => $id_tercero_api,
            ':objeto' => $objeto,
            ':num_factura' => $num_factura,
            ':estado' => $estado,
            ':id_user_reg' => $id_usr_crea,
            ':fecha_reg' => $fecha_crea,
            ':tipo_movimiento' => $tipo_movimiento,
            ':id_ctb_doc' => $id_ctb_doc,
        ]);
        $id_pto_rad = (int) $cmd->lastInsertId();

        $sql = "INSERT INTO pto_rec (fecha, id_manu, id_tercero_api, objeto, num_factura, estado, id_user_reg, fecha_reg, tipo_movimiento, id_ctb_doc)
                VALUES (:fecha, :id_manu, :id_tercero_api, :objeto, :num_factura, :estado, :id_user_reg, :fecha_reg, :tipo_movimiento, :id_ctb_doc)";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([
            ':fecha' => $fecha,
            ':id_manu' => $id_manu,
            ':id_tercero_api' => $id_tercero_api,
            ':objeto' => $objeto,
            ':num_factura' => $num_factura,
            ':estado' => $estado,
            ':id_user_reg' => $id_usr_crea,
            ':fecha_reg' => $fecha_crea,
            ':tipo_movimiento' => $tipo_movimiento,
            ':id_ctb_doc' => $id_ctb_doc,
        ]);
        $id_pto_rec = (int) $cmd->lastInsertId();

        $cmd->commit();
        $res['mensaje'] = 'ok';
        $res['id'] = $id_pto_rad;
        $res['id2'] = $id_pto_rec;
    }

    if ($oper === "edit") {
        $id_pto_rad = isset($_POST['hd_id_pto_rad']) ? (int) $_POST['hd_id_pto_rad'] : 0;
        $id_pto_rec = isset($_POST['hd_id_pto_rec']) ? (int) $_POST['hd_id_pto_rec'] : 0;
        $fecha = isset($_POST['txt_fecha']) ? trim($_POST['txt_fecha']) : '';
        $id_manu = isset($_POST['txt_id_manu']) ? (int) $_POST['txt_id_manu'] : 0;
        $id_tercero_api = isset($_POST['hd_id_tercero_api']) ? (int) $_POST['hd_id_tercero_api'] : 0;
        $objeto = isset($_POST['txt_objeto']) ? trim($_POST['txt_objeto']) : '';
        $id_ctb_doc = isset($_POST['hd_id_ctb_doc']) ? (int) $_POST['hd_id_ctb_doc'] : 0;
        $num_factura = 0;
        $estado = 2;
        $tipo_movimiento = 4;

        if ($id_pto_rad <= 0 || $id_pto_rec <= 0 || $fecha === '' || $id_manu <= 0 || $id_ctb_doc <= 0) {
            throw new RuntimeException('Datos incompletos para actualizar la afectacion presupuestal');
        }

        $sql = "UPDATE pto_rad
                SET fecha = :fecha,
                    id_manu = :id_manu,
                    id_tercero_api = :id_tercero_api,
                    objeto = :objeto,
                    num_factura = :num_factura,
                    estado = :estado,
                    id_user_act = :id_user_act,
                    fecha_act = :fecha_act,
                    tipo_movimiento = :tipo_movimiento,
                    id_ctb_doc = :id_ctb_doc
                WHERE id_pto_rad = :id_pto_rad";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([
            ':fecha' => $fecha,
            ':id_manu' => $id_manu,
            ':id_tercero_api' => $id_tercero_api,
            ':objeto' => $objeto,
            ':num_factura' => $num_factura,
            ':estado' => $estado,
            ':id_user_act' => $id_usr_crea,
            ':fecha_act' => $fecha_crea,
            ':tipo_movimiento' => $tipo_movimiento,
            ':id_ctb_doc' => $id_ctb_doc,
            ':id_pto_rad' => $id_pto_rad,
        ]);

        $sql = "UPDATE pto_rec
                SET fecha = :fecha,
                    id_manu = :id_manu,
                    id_tercero_api = :id_tercero_api,
                    objeto = :objeto,
                    num_factura = :num_factura,
                    estado = :estado,
                    id_user_act = :id_user_act,
                    fecha_act = :fecha_act,
                    tipo_movimiento = :tipo_movimiento,
                    id_ctb_doc = :id_ctb_doc
                WHERE id_pto_rec = :id_pto_rec";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([
            ':fecha' => $fecha,
            ':id_manu' => $id_manu,
            ':id_tercero_api' => $id_tercero_api,
            ':objeto' => $objeto,
            ':num_factura' => $num_factura,
            ':estado' => $estado,
            ':id_user_act' => $id_usr_crea,
            ':fecha_act' => $fecha_crea,
            ':tipo_movimiento' => $tipo_movimiento,
            ':id_ctb_doc' => $id_ctb_doc,
            ':id_pto_rec' => $id_pto_rec,
        ]);

        $cmd->commit();
        $res['mensaje'] = 'ok';
    }

    if ($oper === "del") {
        $cmd->rollBack();
        $res['mensaje'] = 'Accion no implementada';
    }

    $cmd = null;
} catch (Throwable $e) {
    if (isset($cmd) && $cmd instanceof PDO && $cmd->inTransaction()) {
        $cmd->rollBack();
    }
    $res['mensaje'] = $e instanceof PDOException && $e->getCode() == 2002
        ? 'Sin Conexion a Mysql (Error: 2002)'
        : $e->getMessage();
}
echo json_encode($res);
