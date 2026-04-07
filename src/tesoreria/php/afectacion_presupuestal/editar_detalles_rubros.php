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

if (($oper === 'add' && !($puedeAdicionar || $puedeEditar)) || ($oper === 'edit' && !$puedeEditar) || ($oper === 'del' && !$puedeEliminar)) {
    $res['mensaje'] = 'El Usuario del Sistema no tiene Permisos para esta Accion';
    echo json_encode($res);
    exit();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $cmd->beginTransaction();

    if ($oper === "add") {
        $id_pto_rad = isset($_POST['hd_id_pto_rad']) ? (int) $_POST['hd_id_pto_rad'] : 0;
        $id_pto_rec = isset($_POST['hd_id_pto_rec']) ? (int) $_POST['hd_id_pto_rec'] : 0;
        $id_tercero_api = isset($_POST['hd_id_tercero_api']) ? (int) $_POST['hd_id_tercero_api'] : 0;
        $id_rubro = isset($_POST['hd_id_txt_rubro']) ? (int) $_POST['hd_id_txt_rubro'] : 0;
        $valorRaw = isset($_POST['txt_valor']) ? trim($_POST['txt_valor']) : '';
        $valor = str_replace(array(',', ' '), '', $valorRaw);
        $valor_liberado = 0;

        if ($id_pto_rad <= 0 || $id_pto_rec <= 0 || $id_rubro <= 0 || $valor === '' || !is_numeric($valor)) {
            throw new RuntimeException('Datos invalidos para registrar el rubro');
        }

        $stmt = $cmd->prepare("SELECT id_cargue FROM pto_cargue WHERE id_cargue = :id_rubro LIMIT 1");
        $stmt->execute([':id_rubro' => $id_rubro]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('El rubro seleccionado no existe o no es valido');
        }

        $stmt = $cmd->prepare("SELECT id_pto_rad FROM pto_rad WHERE id_pto_rad = :id_pto_rad LIMIT 1");
        $stmt->execute([':id_pto_rad' => $id_pto_rad]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('No existe el encabezado RAD asociado');
        }

        $stmt = $cmd->prepare("SELECT id_pto_rec FROM pto_rec WHERE id_pto_rec = :id_pto_rec LIMIT 1");
        $stmt->execute([':id_pto_rec' => $id_pto_rec]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('No existe el encabezado REC asociado');
        }

        $sql = "INSERT INTO pto_rad_detalle (id_pto_rad, id_tercero_api, id_rubro, valor, valor_liberado, id_user_reg, fecha_reg)
                VALUES (:id_pto_rad, :id_tercero_api, :id_rubro, :valor, :valor_liberado, :id_user_reg, :fecha_reg)";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([
            ':id_pto_rad' => $id_pto_rad,
            ':id_tercero_api' => $id_tercero_api,
            ':id_rubro' => $id_rubro,
            ':valor' => $valor,
            ':valor_liberado' => $valor_liberado,
            ':id_user_reg' => $id_usr_crea,
            ':fecha_reg' => $fecha_crea,
        ]);
        $id_pto_rad_det = (int) $cmd->lastInsertId();

        $sql = "INSERT INTO pto_rec_detalle (id_pto_rac, id_pto_rad_detalle, id_tercero_api, valor, valor_liberado, id_user_reg, fecha_reg)
                VALUES (:id_pto_rec, :id_pto_rad_detalle, :id_tercero_api, :valor, :valor_liberado, :id_user_reg, :fecha_reg)";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([
            ':id_pto_rec' => $id_pto_rec,
            ':id_pto_rad_detalle' => $id_pto_rad_det,
            ':id_tercero_api' => $id_tercero_api,
            ':valor' => $valor,
            ':valor_liberado' => $valor_liberado,
            ':id_user_reg' => $id_usr_crea,
            ':fecha_reg' => $fecha_crea,
        ]);

        $stmt = $cmd->prepare("SELECT prd.id_pto_rad_det
                               FROM pto_rad_detalle prd
                               WHERE prd.id_pto_rad_det = :id_pto_rad_det
                               LIMIT 1");
        $stmt->execute([':id_pto_rad_det' => $id_pto_rad_det]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('No fue posible confirmar el detalle registrado');
        }

        $cmd->commit();
        $res['mensaje'] = 'ok';
        $res['id_pto_rad'] = $id_pto_rad;
        $res['id_pto_rec'] = $id_pto_rec;
        $res['id_pto_rad_det'] = $id_pto_rad_det;
    }

    if ($oper === "edit") {
        $cmd->rollBack();
        $res['mensaje'] = 'Accion no implementada';
    }

    if ($oper === "del") {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            throw new RuntimeException('Detalle de rubro no valido');
        }

        $stmt = $cmd->prepare("DELETE FROM pto_rec_detalle WHERE id_pto_rad_detalle = :id");
        $stmt->execute([':id' => $id]);

        $stmt = $cmd->prepare("DELETE FROM pto_rad_detalle WHERE id_pto_rad_det = :id");
        $stmt->execute([':id' => $id]);
        if ($stmt->rowCount() <= 0) {
            throw new RuntimeException('No se encontro el detalle de rubro para eliminar');
        }

        $cmd->commit();
        $res['mensaje'] = 'ok';
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
