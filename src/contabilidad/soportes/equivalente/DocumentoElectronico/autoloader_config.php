<?php

/**
 * Autoloader Actualizado - Configuración para DocumentoElectronico
 * 
 * Agregar este código al archivo: config/autoloader.php
 * O incluir este archivo desde autoloader.php
 */

// ============================================================================
// AUTOLOADER PARA NAMESPACE App\DocumentoElectronico
// ============================================================================

spl_autoload_register(function ($class) {
    // Namespace base para documentos electrónicos
    $prefix = 'App\\DocumentoElectronico\\';

    // Directorio base donde están las clases
    $base_dir = __DIR__ . '/../src/contabilidad/soportes/equivalente/DocumentoElectronico/';

    // Verificar si la clase usa el namespace que manejamos
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No es nuestro namespace, dejar que otro autoloader lo maneje
        return;
    }

    // Obtener el nombre de la clase relativo
    $relative_class = substr($class, $len);

    // Reemplazar namespace separators con directory separators
    // y agregar .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Si el archivo existe, requerirlo
    if (file_exists($file)) {
        require $file;
    }
});

// ============================================================================
// VERIFICACIÓN DE INSTALACIÓN (Opcional - solo para debug)
// ============================================================================

// Descomentar para verificar que el autoloader funciona correctamente
/*
function verificarInstalacionDocumentosElectronicos() {
    $clases_requeridas = [
        'App\\DocumentoElectronico\\TaxxaService',
        'App\\DocumentoElectronico\\DocumentBuilder',
        'App\\DocumentoElectronico\\DocumentRepository',
        'App\\DocumentoElectronico\\DocumentoElectronicoService'
    ];
    
    $errores = [];
    
    foreach ($clases_requeridas as $clase) {
        if (!class_exists($clase)) {
            $errores[] = "Clase no encontrada: {$clase}";
        }
    }
    
    if (empty($errores)) {
        echo "✅ Todas las clases de DocumentoElectronico están disponibles\n";
        return true;
    } else {
        echo "❌ Errores encontrados:\n";
        foreach ($errores as $error) {
            echo "  - {$error}\n";
        }
        return false;
    }
}

// Ejecutar verificación
verificarInstalacionDocumentosElectronicos();
*/

// ============================================================================
// EJEMPLO DE USO
// ============================================================================

/*
// Después de incluir este autoloader, puedes usar:

use App\DocumentoElectronico\DocumentoElectronicoService;
use App\DocumentoElectronico\TaxxaService;
use App\DocumentoElectronico\DocumentBuilder;

// Las clases se cargarán automáticamente
$service = new DocumentoElectronicoService($conexion, $userId);
*/
