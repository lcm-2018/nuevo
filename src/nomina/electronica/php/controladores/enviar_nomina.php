<?php

/**
 * Envío de Nómina Electrónica a Taxxa
 *
 * Entry point HTTP POST para el proceso de nómina electrónica.
 * Sigue el mismo patrón que enviar_factura.php del módulo de documento soporte.
 *
 * POST body (JSON): { "id": <id_nomina> }
 * Response (JSON):  { value, msg, procesados, incorrectos, errores[] }
 */

session_start();

// Validación de sesión
if (!isset($_SESSION['user'])) {
    echo json_encode(['value' => 'Error', 'msg' => 'Sesión no iniciada']);
    exit();
}

// Leer JSON del body
$data = json_decode(file_get_contents('php://input'), true);

// Validación de entrada
$idNomina = isset($data['id']) ? filter_var($data['id'], FILTER_VALIDATE_INT) : false;
if (!$idNomina || $idNomina <= 0) {
    echo json_encode(['value' => 'Error', 'msg' => 'ID de nómina inválido']);
    exit('Acción no permitida');
}

// Año de vigencia desde la sesión
$anio = $_SESSION['vigencia'] ?? date('Y');

// Autoloader
include_once '../../../../../config/autoloader.php';

// Importar clases necesarias
use Src\Nomina\Electronica\Php\Clases\NominaElectronicaService;
use Config\Clases\Conexion;

try {
    // Obtener conexión a BD
    $conexion = Conexion::getConexion();

    // Crear instancia del servicio
    $service = new NominaElectronicaService($conexion, $_SESSION['id_user']);

    // Ejecutar envío
    $resultado = $service->enviarNominaElectronica($idNomina, (string)$anio);

    // Retornar respuesta
    echo json_encode($resultado);

} catch (PDOException $e) {
    $errorMsg = $e->getCode() == 2002
        ? 'Sin Conexión a MySQL (Error: 2002)'
        : 'Error de base de datos: ' . $e->getMessage();

    echo json_encode([
        'value' => 'Error',
        'msg'   => $errorMsg,
    ]);
} catch (Exception $e) {
    echo json_encode([
        'value' => 'Error',
        'msg'   => 'Error inesperado: ' . $e->getMessage(),
    ]);
} finally {
    $conexion = null;
}

exit;
