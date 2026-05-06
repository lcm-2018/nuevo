<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}
include '../../../../../config/autoloader.php';

$id_dependiente = isset($_POST['idDependiente']) ? $_POST['idDependiente'] : 0;
$id_tercero = isset($_POST['idTercero']) ? $_POST['idTercero'] : 0;
$id_tipo_doc = isset($_POST['slcTipoDocs']) ? $_POST['slcTipoDocs'] : 0;
$num_doc = isset($_POST['txtNumDoc']) ? $_POST['txtNumDoc'] : '';
$nombre_completo = isset($_POST['txtNombreCompleto']) ? $_POST['txtNombreCompleto'] : '';
$id_tipo_dependiente = isset($_POST['slcCalidadDependiente']) ? $_POST['slcCalidadDependiente'] : 0;
$id_user = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha = $date->format('Y-m-d H:i:s');

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    if ($id_dependiente > 0) {
        // Update
        $sql = "SELECT id_dependiente FROM tb_terceros_dependientes WHERE id_tercero_api = ? AND no_documento = ? AND id_dependiente != ?";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([$id_tercero, $num_doc, $id_dependiente]);
        if ($stmt->rowCount() > 0) {
            echo 'El número de documento ya se encuentra registrado para otro dependiente.';
            exit();
        }

        $sql = "UPDATE tb_terceros_dependientes SET 
                    id_tipo_doc = ?, 
                    no_documento = ?, 
                    nombre_completo = ?, 
                    id_tipo_dependiente = ?, 
                    id_user_act = ?, 
                    fec_act = ? 
                WHERE id_dependiente = ?";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([
            $id_tipo_doc,
            $num_doc,
            $nombre_completo,
            $id_tipo_dependiente,
            $id_user,
            $fecha,
            $id_dependiente
        ]);

        if ($stmt->rowCount() > 0 || $stmt->errorCode() == '00000') {
            echo 'ok';
        } else {
            echo 'No se actualizó ningún registro.';
        }
    } else {
        // Insert
        $sql = "SELECT id_dependiente FROM tb_terceros_dependientes WHERE id_tercero_api = ? AND no_documento = ?";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([$id_tercero, $num_doc]);
        if ($stmt->rowCount() > 0) {
            echo 'El dependiente ya se encuentra registrado.';
            exit();
        }

        $sql = "INSERT INTO tb_terceros_dependientes (id_tercero_api, id_tipo_doc, no_documento, nombre_completo, id_tipo_dependiente, estado, id_user_reg, fec_reg) 
                VALUES (?, ?, ?, ?, ?, 1, ?, ?)";
        $stmt = $cmd->prepare($sql);
        $stmt->execute([
            $id_tercero,
            $id_tipo_doc,
            $num_doc,
            $nombre_completo,
            $id_tipo_dependiente,
            $id_user,
            $fecha
        ]);

        if ($stmt->rowCount() > 0) {
            echo 'ok';
        } else {
            echo 'No se guardó el registro.';
        }
    }
    
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
