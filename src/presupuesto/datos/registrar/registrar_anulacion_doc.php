<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include '../../../../config/autoloader.php';

$id = isset($_POST['id_pto_doc']) ? $_POST['id_pto_doc'] : exit('Acceso no disponible');
$tipo = $_POST['tipo'];
$fecha = $_POST['fecha'];
$motivo = $_POST['objeto'];
$id_user = $_SESSION['id_user'];
$table = $tipo == 'cdp' ? 'pto_cdp' : 'pto_crp';
$estado = 0;
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $cmd->beginTransaction();
    $sql = "UPDATE $table 
                SET `estado`= ?, `id_user_anula` = ?, `fecha_anula` = ?, `concepto_anula` = ? 
            WHERE `id_pto_$tipo` = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $estado, PDO::PARAM_INT);
    $sql->bindParam(2, $id_user, PDO::PARAM_INT);
    $sql->bindParam(3, $fecha, PDO::PARAM_STR);
    $sql->bindParam(4, $motivo, PDO::PARAM_STR);
    $sql->bindParam(5, $id, PDO::PARAM_INT);
    $sql->execute();
    if ($sql->rowCount() > 0) {
        if ($tipo == 'cdp') {
            $sql = "UPDATE `ctt_adquisiciones` SET `id_cdp` = NULL WHERE `id_cdp` = ?";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $id, PDO::PARAM_INT);
            $sql->execute();
        }
        if ($tipo == 'crp') {
            $sql = "SELECT
                        `ctb_doc`.`id_ctb_doc`
                    FROM
                        `ctb_doc`
                        INNER JOIN `ctb_fuente` 
                            ON (`ctb_doc`.`id_tipo_doc` = `ctb_fuente`.`id_doc_fuente`)
                    WHERE (`ctb_doc`.`id_crp`  = ?
                        AND `ctb_fuente`.`cod` = ?)";
            $sql = $cmd->prepare($sql);
            $sql->bindParam(1, $id, PDO::PARAM_INT);
            $sql->bindValue(2, 'CXPA', PDO::PARAM_STR);
            $sql->execute();
            $crp_cxpa = $sql->fetch(PDO::FETCH_ASSOC);
            //verificar si es diferente de vacio, o sea que existe el crp_cxpa y si existe  ralizar otra validacion para poder anular, que  en ctb_doc.id_ctb_doc_tipo3  no exista el $crp_cxpa['id_ctb_doc']  y que el ctb_doc sea > 0
            if (!empty($crp_cxpa)) {
                $cxpa = $crp_cxpa['id_ctb_doc'];
                $sql = "SELECT `id_ctb_doc` FROM `ctb_doc` WHERE `id_ctb_doc_tipo3` = ? AND `estado` > 0";
                $sql = $cmd->prepare($sql);
                $sql->bindParam(1, $cxpa, PDO::PARAM_INT);
                $sql->execute();
                $ce_proceso = $sql->fetch(PDO::FETCH_ASSOC);
                if (!empty($ce_proceso)) {
                    $cmd->rollBack();
                    echo 'No se puede anular porque tiene un comprobante de egreso en proceso';
                    exit();
                }
                $sql = "UPDATE `ctb_doc` SET `estado` = ? WHERE `id_crp` = ?";
                $sql = $cmd->prepare($sql);
                $sql->bindParam(1, $estado, PDO::PARAM_INT);
                $sql->bindParam(2, $id, PDO::PARAM_INT);
                $sql->execute();
            }
        }
        $cmd->commit();
        echo 'ok';
    } else {
        if ($cmd->inTransaction()) {
            $cmd->rollBack();
        }
        echo $sql->errorInfo()[2];
    }
} catch (PDOException $e) {
    if (isset($cmd) && $cmd->inTransaction()) {
        $cmd->rollBack();
    }
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
