<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Config\Clases\Conexion;
use Config\Clases\Plantilla;

$idFirma = isset($_POST['idFirma']) ? $_POST['idFirma'] : exit('Acción no permitida');
$idVariable = isset($_POST['idVariable']) ? $_POST['idVariable'] : exit('Variable es requerida');
$nomVariable = isset($_POST['txtNomVariable']) ? trim($_POST['txtNomVariable']) : exit('Nombre de variable es requerido');
$idTercero = isset($_POST['id_tercero']) ? $_POST['id_tercero'] : exit('Tercero es requerido');
$cargo = isset($_POST['txtCargo']) ? trim($_POST['txtCargo']) : exit('Cargo es requerido');
$nomImagenActual = isset($_POST['nomImagenActual']) ? $_POST['nomImagenActual'] : '';
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));

$rutaDestino = $_SERVER['DOCUMENT_ROOT'] . Plantilla::getHost() . '/assets/images/firmas/';
$nombreArchivo = $nomImagenActual; // Por defecto, mantener el nombre actual
$cambioImagen = false;

// Verificar si se subió una nueva imagen
if (isset($_FILES['fileFirma']) && $_FILES['fileFirma']['error'] === UPLOAD_ERR_OK) {
    $fileTmpName = $_FILES['fileFirma']['tmp_name'];
    $fileSize = $_FILES['fileFirma']['size'];
    $fileExt = strtolower(pathinfo($_FILES['fileFirma']['name'], PATHINFO_EXTENSION));

    if ($fileExt !== 'png') {
        exit('Solo se permiten imágenes PNG');
    }

    if ($fileSize > 2097152) { // 2MB
        exit('La imagen debe ser menor a 2MB');
    }

    // Preparar nombre de archivo: frm_nombre_variable.png
    $nombreArchivo = 'frm_' . $nomVariable . '.png';
    $cambioImagen = true;
}

try {
    $cmd = Conexion::getConexion();
    $cmd->beginTransaction();

    // 1. Actualizar ctt_variables_forms
    $sql = "UPDATE `ctt_variables_forms` SET `variable` = ? WHERE `id_var` = ?";
    $stmt = $cmd->prepare($sql);
    $stmt->bindParam(1, $nomVariable, PDO::PARAM_STR);
    $stmt->bindParam(2, $idVariable, PDO::PARAM_INT);
    $stmt->execute();

    // 2. Actualizar ctt_firmas
    $sql = "UPDATE `ctt_firmas` SET `id_tercero_api` = ?, `cargo` = ?, `nom_imagen` = ?, `id_user_act` = ?, `fec_act` = ? WHERE `id` = ?";
    $stmt = $cmd->prepare($sql);
    $cargoUpper = mb_strtoupper($cargo);

    $stmt->bindParam(1, $idTercero, PDO::PARAM_INT);
    $stmt->bindParam(2, $cargoUpper, PDO::PARAM_STR);
    $stmt->bindParam(3, $nombreArchivo, PDO::PARAM_STR);
    $stmt->bindParam(4, $iduser, PDO::PARAM_INT);
    $stmt->bindValue(5, $date->format('Y-m-d H:i:s'));
    $stmt->bindParam(6, $idFirma, PDO::PARAM_INT);
    $stmt->execute();

    $cambios = $stmt->rowCount();

    // 3. Si se subió nueva imagen, sobrescribir la anterior
    if ($cambioImagen) {
        $rutaCompleta = $rutaDestino . $nombreArchivo;

        // Crear directorio si no existe
        if (!file_exists($rutaDestino)) {
            mkdir($rutaDestino, 0777, true);
        }

        // Sobrescribir la imagen
        if (!move_uploaded_file($fileTmpName, $rutaCompleta)) {
            throw new Exception('Error al guardar la imagen');
        }

        // Si el nombre cambió, eliminar la imagen antigua
        if ($nombreArchivo !== $nomImagenActual && !empty($nomImagenActual)) {
            $rutaAntigua = $rutaDestino . $nomImagenActual;
            if (file_exists($rutaAntigua)) {
                unlink($rutaAntigua);
            }
        }
    }

    $cmd->commit();
    echo '1';
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
