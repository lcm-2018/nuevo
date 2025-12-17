<?php

/**
 * Envío de Factura de Venta Electrónica (Refactorizado)
 * Utiliza la nueva arquitectura modular y reutilizable
 * 
 * Este archivo reemplaza la lógica anterior con una implementación
 * más limpia, mantenible y extensible
 */

session_start();

// Configuración de errores (solo para desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Validación de sesión
if (!isset($_SESSION['user'])) {
    echo '<script>window.location.replace("../../index.php");</script>';
    exit();
}

// Validación de entrada
$id_facno = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$opcion = filter_input(INPUT_POST, 'tipo', FILTER_VALIDATE_INT) ?? 0;

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

    // Enviar factura de venta
    $resultado = $service->enviarFacturaVenta($id_facno);

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
