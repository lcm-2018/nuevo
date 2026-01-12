<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

include_once '../config/autoloader.php';

use Config\Clases\Plantilla;
use Config\Clases\Sesion;

$host = Plantilla::getHost();
$nombre_usuario = Sesion::User();
$vigencia = Sesion::Vigencia();
$hoy = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha_actual = $hoy->format('d/m/Y');
$hora_actual = $hoy->format('h:i A');

function consultarContratoSecop($idContrato)
{
    // 1. URL base del dataset de Contratos Electrónicos
    $url = "https://www.datos.gov.co/resource/jbjy-vk9h.json";

    // 2. Parámetros de consulta (ID del contrato)
    $params = http_build_query([
        "id_contrato" => $idContrato
    ]);

    $fullUrl = $url . "?" . $params;

    // 3. Configuración de cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // 4. Headers (IMPORTANTE: Agrega tu App Token aquí si lo tienes)
    $headers = [
        "Accept: application/json",
        // "X-App-Token: TU_TOKEN_AQUI" // Descomenta esta línea cuando tengas tu token
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // 5. Ejecución
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        return ['error' => 'Error en cURL: ' . curl_error($ch)];
    }

    curl_close($ch);

    // 6. Procesamiento de la respuesta
    if ($httpCode === 200) {
        $data = json_decode($response, true);

        // La API devuelve un array. Verificamos si encontramos el contrato.
        if (!empty($data)) {
            return $data[0]; // Retornamos el primer resultado encontrado
        } else {
            return ['error' => 'No se encontró información para el ID suministrado: ' . $idContrato];
        }
    } else {
        return ['error' => "Error del servidor (Código $httpCode): " . $response];
    }
}

// --- MODO DE USO ---
$idBusqueda = "CO1.PCCNTR.7198442";
$resultado = consultarContratoSecop($idBusqueda);

if (isset($resultado['error'])) {
    echo "Hubo un problema: " . $resultado['error'];
} else {
    echo "<h1>Información del Contrato</h1>";
    echo "<b>Entidad:</b> " . $resultado['nombre_entidad'] . "<br>";
    echo "<b>Objeto:</b> " . $resultado['objeto_del_contrato'] . "<br>";
    echo json_encode($resultado);
}
