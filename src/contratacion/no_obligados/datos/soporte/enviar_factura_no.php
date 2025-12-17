<?php

/**
 * Envío de Documento Soporte Electrónico - Contratación (No Obligados)
 * Versión Refactorizada
 * 
 * Utiliza la arquitectura modular del sistema de documentos electrónicos
 * extendida específicamente para documentos de contratación
 */

session_start();

// Configuración de errores (solo para desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Validación de sesión
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

// Validación de entrada
$id_facno = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id_facno) {
    echo json_encode([['value' => 'Error', 'msg' => 'ID de documento inválido']]);
    exit('Acción no permitida');
}

// Autoloader
include_once '../../../../../config/autoloader.php';

// Importar clases necesarias
use App\DocumentoElectronico\Contratacion\ContratacionService;
use Config\Clases\Conexion;

try {
    // Obtener conexión a BD
    $conexion = Conexion::getConexion();

    // Crear instancia del servicio de contratación
    $service = new ContratacionService($conexion, $_SESSION['id_user']);

    // Enviar documento de contratación
    $resultado = $service->enviarDocumentoContratacion($id_facno);

    // Retornar respuesta (como array para mantener compatibilidad)
    echo json_encode([$resultado]);
} catch (PDOException $e) {
    $errorMsg = $e->getCode() == 2002
        ? 'Sin Conexión a Mysql (Error: 2002)'
        : 'Error de base de datos: ' . $e->getMessage();

    echo json_encode([[
        'value' => 'Error',
        'msg' => $errorMsg
    ]]);
} catch (Exception $e) {
    echo json_encode([[
        'value' => 'Error',
        'msg' => 'Error inesperado: ' . $e->getMessage()
    ]]);
} finally {
    // Cerrar conexión
    $conexion = null;
}

exit;
