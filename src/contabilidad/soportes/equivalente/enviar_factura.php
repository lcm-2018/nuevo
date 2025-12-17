<?php

/**
 * Envío de Documento Soporte Electrónico (Refactorizado)
 * Utiliza la nueva arquitectura modular y reutilizable
 * 
 * Este archivo reemplaza la lógica anterior con una implementación
 * más limpia, mantenible y extensible
 */

session_start();

// Validación de sesión
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}

// Validación de entrada
$id_facno = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id_facno) {
    echo json_encode(['value' => 'Error', 'msg' => 'ID de factura inválido']);
    exit('Acción no permitida');
}

// Autoloader
include '../../../../config/autoloader.php';

// Importar clases necesarias
use App\DocumentoElectronico\DocumentoElectronicoService;
use Config\Clases\Conexion;

try {
    // Obtener conexión a BD
    $conexion = Conexion::getConexion();

    // Crear instancia del servicio
    $service = new DocumentoElectronicoService($conexion, $_SESSION['id_user']);

    // Enviar documento soporte
    $resultado = $service->enviarDocumentoSoporte($id_facno);

    // Retornar respuesta
    echo json_encode($resultado);
} catch (PDOException $e) {
    $errorMsg = $e->getCode() == 2002
        ? 'Sin Conexión a Mysql (Error: 2002)'
        : 'Error de base de datos: ' . $e->getMessage();

    echo json_encode([
        'value' => 'Error',
        'msg' => $errorMsg
    ]);
} catch (Exception $e) {
    echo json_encode([
        'value' => 'Error',
        'msg' => 'Error inesperado: ' . $e->getMessage()
    ]);
} finally {
    // Cerrar conexión
    $conexion = null;
}

exit;
