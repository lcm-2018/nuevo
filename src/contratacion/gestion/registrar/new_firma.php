<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}
include_once '../../../../config/autoloader.php';

use Config\Clases\Conexion;
use Config\Clases\Plantilla;

$nomVariable = isset($_POST['txtNomVariable']) ? trim($_POST['txtNomVariable']) : exit('Nombre de variable es requerido');
$idTercero = isset($_POST['id_tercero']) ? $_POST['id_tercero'] : exit('Tercero es requerido');
$cargo = isset($_POST['txtCargo']) ? trim($_POST['txtCargo']) : exit('Cargo es requerido');
$iduser = $_SESSION['id_user'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));

// Validar que se haya subido una imagen
if (!isset($_FILES['fileFirma']) || $_FILES['fileFirma']['error'] !== UPLOAD_ERR_OK) {
    exit('Debe cargar una imagen PNG');
}

// Validar que sea PNG
$fileName = $_FILES['fileFirma']['name'];
$fileTmpName = $_FILES['fileFirma']['tmp_name'];
$fileSize = $_FILES['fileFirma']['size'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($fileExt !== 'png') {
    exit('Solo se permiten imágenes PNG');
}

if ($fileSize > 2097152) { // 2MB
    exit('La imagen debe ser menor a 2MB');
}

// Preparar nombre de archivo: frm_nombre_variable.png
$nombreArchivo = 'frm_' . $nomVariable . '.png';
$rutaDestino = $_SERVER['DOCUMENT_ROOT'] . Plantilla::getHost() . '/assets/images/firmas/';

// Crear directorio si no existe
if (!file_exists($rutaDestino)) {
    mkdir($rutaDestino, 0777, true);
}

$rutaCompleta = $rutaDestino . $nombreArchivo;

try {
    $cmd = Conexion::getConexion();
    $cmd->beginTransaction();

    // 1. Insertar en ctt_variables_forms
    $sql = "INSERT INTO `ctt_variables_forms` (`variable`, `tipo`, `contexto`) VALUES (?, ?, ?)";
    $stmt = $cmd->prepare($sql);
    $tipo = 3; // o el tipo que necesites
    $contexto = 'Firma para documentos de contratación';
    $variable = '${' . $nomVariable . '}';

    $stmt->bindParam(1, $variable, PDO::PARAM_STR);
    $stmt->bindParam(2, $tipo, PDO::PARAM_INT);
    $stmt->bindParam(3, $contexto, PDO::PARAM_STR);
    $stmt->execute();

    $idVariable = $cmd->lastInsertId();

    if ($idVariable == 0) {
        throw new Exception('Error al registrar la variable');
    }

    // 2. Insertar en ctt_firmas
    $sql = "INSERT INTO ctt_firmas (id_variable, id_tercero_api, cargo, nom_imagen, id_user_reg, fec_reg) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $cmd->prepare($sql);
    $cargoUpper = mb_strtoupper($cargo);

    $stmt->bindParam(1, $idVariable, PDO::PARAM_INT);
    $stmt->bindParam(2, $idTercero, PDO::PARAM_INT);
    $stmt->bindParam(3, $cargoUpper, PDO::PARAM_STR);
    $stmt->bindParam(4, $nombreArchivo, PDO::PARAM_STR);
    $stmt->bindParam(5, $iduser, PDO::PARAM_INT);
    $stmt->bindValue(6, $date->format('Y-m-d H:i:s'));
    $stmt->execute();

    $idFirma = $cmd->lastInsertId();

    if ($idFirma == 0) {
        throw new Exception('Error al registrar la firma');
    }

    // 3. Mover la imagen
    if (!move_uploaded_file($fileTmpName, $rutaCompleta)) {
        throw new Exception('Error al guardar la imagen');
    }

    $cmd->commit();
    echo '1';
} catch (Exception $e) {
    if ($cmd->inTransaction()) {
        $cmd->rollBack();
    }
    // Si la transacción falla, eliminar la imagen si se guardó
    if (file_exists($rutaCompleta)) {
        unlink($rutaCompleta);
    }
    echo 'Error: ' . $e->getMessage();
} catch (PDOException $e) {
    if ($cmd->inTransaction()) {
        $cmd->rollBack();
    }
    // Si la transacción falla, eliminar la imagen si se guardó
    if (file_exists($rutaCompleta)) {
        unlink($rutaCompleta);
    }
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
