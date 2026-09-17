<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
$id_conciliacion = isset($_POST['id_conciliacion']) ? $_POST['id_conciliacion'] : exit('Acceso no disponible');
$ids_libaux = isset($_POST['ids_libaux']) ? $_POST['ids_libaux'] : [];
$opc = $_POST['opc'];
$vigencia = $_SESSION['vigencia'];
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha2 = $date->format('Y-m-d H:i:s');
include '../../../../config/autoloader.php';
use Config\Clases\Logs;
$response['status'] = 'error';

if (empty($ids_libaux) || !is_array($ids_libaux)) {
    $response['msg'] = 'No se recibieron datos para procesar';
    echo json_encode($response);
    exit();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    // Genero la fecha de marca conciderando el periodo y el mes de id_conciliación 
    try {
        $sql = "SELECT 
                    LAST_DAY(STR_TO_DATE(CONCAT(vigencia, '-', mes, '-01'), '%Y-%m-%d')) AS fecha_marca
                FROM 
                    tes_conciliacion where id_conciliacion =$id_conciliacion;";
        $rs = $cmd->query($sql);
        $fecham = $rs->fetch(PDO::FETCH_ASSOC);
        $fecha_marca = $fecham['fecha_marca'];
    } catch (PDOException $e) {
        $response['msg'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
        echo json_encode($response);
        exit();
    }
    
    $cmd->beginTransaction();
    $errores = 0;

    if ($opc == 1) {
        $query = "INSERT INTO `tes_conciliacion_detalle`
                    (`id_concilia`,`id_ctb_libaux`,`fecha_marca`,`id_user_reg`,`fec_reg`)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $cmd->prepare($query);
        
        foreach ($ids_libaux as $id_libaux) {
            $chk = $cmd->query("SELECT id_ctb_libaux FROM tes_conciliacion_detalle WHERE id_concilia = $id_conciliacion AND id_ctb_libaux = $id_libaux");
            if ($chk->rowCount() == 0) {
                $stmt->bindParam(1, $id_conciliacion, PDO::PARAM_INT);
                $stmt->bindParam(2, $id_libaux, PDO::PARAM_INT);
                $stmt->bindParam(3, $fecha_marca);
                $stmt->bindParam(4, $iduser, PDO::PARAM_INT);
                $stmt->bindParam(5, $fecha2);
                if (!$stmt->execute()) {
                    $errores++;
                } else {
                    Logs::guardaLog("INSERT INTO `tes_conciliacion_detalle` (`id_concilia`,`id_ctb_libaux`,`fecha_marca`,`id_user_reg`,`fec_reg`) VALUES ($id_conciliacion, $id_libaux, '$fecha_marca', $iduser, '$fecha2')");
                }
            }
        }
    } else {
        $query = "DELETE FROM `tes_conciliacion_detalle` WHERE `id_concilia` = ? AND `id_ctb_libaux` = ?";
        $stmt = $cmd->prepare($query);
        include '../../../financiero/reg_logs.php';
        $ruta = '../../../log';
        foreach ($ids_libaux as $id_libaux) {
            $stmt->bindParam(1, $id_conciliacion, PDO::PARAM_INT);
            $stmt->bindParam(2, $id_libaux, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                $errores++;
            } else {
                if ($stmt->rowCount() > 0) {
                    $consulta = "DELETE FROM `tes_conciliacion_detalle` WHERE `id_concilia` = $id_conciliacion AND `id_ctb_libaux` = $id_libaux";
                    RegistraLogs($ruta, $consulta);
                }
            }
        }
    }

    if ($errores == 0) {
        $cmd->commit();
        $response['status'] = 'ok';
    } else {
        $cmd->rollBack();
        $response['msg'] = 'Ocurrieron errores al procesar algunos registros.';
    }

    $query = "SELECT
                `tes_conciliacion_detalle`.`id_concilia`
                , SUM(`ctb_libaux`.`debito`) AS `debito`
                , SUM(`ctb_libaux`.`credito`) AS `credito`
            FROM
                `tes_conciliacion_detalle`
                INNER JOIN `ctb_libaux` 
                    ON (`tes_conciliacion_detalle`.`id_ctb_libaux` = `ctb_libaux`.`id_ctb_libaux`)
            WHERE `tes_conciliacion_detalle`.`id_concilia` = ?";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $id_conciliacion, PDO::PARAM_INT);
    $query->execute();
    $saldos = $query->fetch();
    $response['debito'] = !empty($saldos) ? $saldos['debito'] : '0';
    $response['credito'] = !empty($saldos) ? $saldos['credito'] : '0';
    $cmd = null;
} catch (PDOException $e) {
    if ($cmd->inTransaction()) {
        $cmd->rollBack();
    }
    $response['msg'] = $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
echo json_encode($response);
