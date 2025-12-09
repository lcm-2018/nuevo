<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Config\Clases\Logs;
use Config\Clases\Conexion;
use Config\Clases\Plantilla;

$id = isset($_POST['id']) ? $_POST['id'] : exit('Acción no permitida');

try {
    $cmd = Conexion::getConexion();

    // Primero obtener la información de la firma para eliminar la imagen y la variable
    $sql = "SELECT 
                `ctt_firmas`.`id_variable`,
                `ctt_firmas`.`nom_imagen`
            FROM `ctt_firmas`
            WHERE `ctt_firmas`.`id` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $id, PDO::PARAM_INT);
    $stmt->execute();
    $firma = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$firma) {
        echo 'Firma no encontrada';
        exit();
    }

    $idVariable = $firma['id_variable'];
    $nomImagen = $firma['nom_imagen'];

    $cmd->beginTransaction();

    // 1. Eliminar de ctt_firmas
    $sql = "DELETE FROM `ctt_firmas` WHERE id = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        try {
            // 2. Eliminar de ctt_variables_forms
            $sql = "DELETE FROM `ctt_variables_forms` WHERE `id_var` = ?";
            $stmt = $cmd->prepare($sql);
            $stmt->bindParam(1, $idVariable, PDO::PARAM_INT);
            $stmt->execute();

            // 3. Eliminar la imagen del servidor
            if (!empty($nomImagen)) {
                $rutaImagen = $_SERVER['DOCUMENT_ROOT'] . Plantilla::getHost() . '/assets/images/firmas/' . $nomImagen;
                if (file_exists($rutaImagen)) {
                    unlink($rutaImagen);
                } else {
                    throw new Exception('No se pudo eliminar la imagen' . $rutaImagen);
                }
            }

            // 4. Guardar logs
            $consultaFirma = "DELETE FROM `ctt_firmas` WHERE `id` = $id";
            $consultaVariable = "DELETE FROM `ctt_variables_forms` WHERE `id_var` = $idVariable";
            Logs::guardaLog($consultaFirma);
            Logs::guardaLog($consultaVariable);

            $cmd->commit();
            echo '1';
        } catch (PDOException $e) {
            $cmd->rollBack();
            echo 'Error: ' . $e->getMessage();
        }
    } else {
        $cmd->rollBack();
        echo 'No se pudo eliminar la firma';
    }

    $cmd = null;
} catch (Exception $e) {
    if ($cmd->inTransaction()) {
        $cmd->rollBack();
    }
    echo 'Error: ' . $e->getMessage();
} catch (PDOException $e) {
    if ($cmd->inTransaction()) {
        $cmd->rollBack();
    }
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
