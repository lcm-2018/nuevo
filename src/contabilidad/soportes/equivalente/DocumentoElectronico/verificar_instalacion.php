<?php

/**
 * Script de Verificación - Módulo de Documentos Electrónicos
 * 
 * Este script verifica que:
 * 1. El autoloader funciona correctamente
 * 2. Todas las clases se pueden cargar
 * 3. No hay errores de sintaxis
 */

echo "<h1>🔍 Verificación del Módulo de Documentos Electrónicos</h1>";
echo "<hr>";

// Incluir autoloader
include '../../../config/autoloader.php';

// ============================================================================
// Verificar clases
// ============================================================================

$clases_requeridas = [
    'App\\DocumentoElectronico\\TaxxaService' => 'TaxxaService.php',
    'App\\DocumentoElectronico\\DocumentBuilder' => 'DocumentBuilder.php',
    'App\\DocumentoElectronico\\DocumentRepository' => 'DocumentRepository.php',
    'App\\DocumentoElectronico\\DocumentoElectronicoService' => 'DocumentoElectronicoService.php'
];

$errores = [];
$warnings = [];
$success = [];

echo "<h2>1. Verificación de Clases</h2>";

foreach ($clases_requeridas as $clase => $archivo) {
    echo "<div style='padding: 5px; margin: 5px 0;'>";

    if (class_exists($clase)) {
        echo "✅ <strong style='color: green;'>OK</strong> - Clase encontrada: <code>{$clase}</code>";
        $success[] = $clase;

        // Verificar métodos públicos
        $reflection = new ReflectionClass($clase);
        $metodos = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        echo " <small style='color: #666;'>(" . count($metodos) . " métodos públicos)</small>";
    } else {
        echo "❌ <strong style='color: red;'>ERROR</strong> - Clase no encontrada: <code>{$clase}</code>";
        $errores[] = "Clase no encontrada: {$clase} (archivo: {$archivo})";
    }

    echo "</div>";
}

echo "<hr>";

// ============================================================================
// Verificar archivos refactorizados
// ============================================================================

echo "<h2>2. Verificación de Endpoints Refactorizados</h2>";

$endpoints = [
    'enviar_factura.php' => __DIR__ . '/../enviar_factura.php',
    'enviar_factura_venta.php' => __DIR__ . '/../enviar_factura_venta.php'
];

foreach ($endpoints as $nombre => $ruta) {
    echo "<div style='padding: 5px; margin: 5px 0;'>";

    if (file_exists($ruta)) {
        $contenido = file_get_contents($ruta);
        $lineas = count(file($ruta));

        // Verificar que es la versión refactorizada
        if (strpos($contenido, 'DocumentoElectronicoService') !== false) {
            echo "✅ <strong style='color: green;'>OK</strong> - <code>{$nombre}</code> está refactorizado";
            echo " <small style='color: #666;'>({$lineas} líneas)</small>";
            $success[] = $nombre;
        } else {
            echo "⚠️ <strong style='color: orange;'>WARNING</strong> - <code>{$nombre}</code> no parece estar refactorizado";
            $warnings[] = "{$nombre} no está usando DocumentoElectronicoService";
        }
    } else {
        echo "❌ <strong style='color: red;'>ERROR</strong> - Archivo no encontrado: <code>{$nombre}</code>";
        $errores[] = "Archivo no encontrado: {$nombre}";
    }

    echo "</div>";
}

echo "<hr>";

// ============================================================================
// Verificar backups
// ============================================================================

echo "<h2>3. Verificación de Backups</h2>";

$backups = [
    'enviar_factura_backup_20251216.php',
    'enviar_factura_venta_backup_20251216.php'
];

foreach ($backups as $backup) {
    $ruta = __DIR__ . '/../' . $backup;
    echo "<div style='padding: 5px; margin: 5px 0;'>";

    if (file_exists($ruta)) {
        $size = filesize($ruta);
        echo "✅ <strong style='color: green;'>OK</strong> - Backup creado: <code>{$backup}</code>";
        echo " <small style='color: #666;'>(" . number_format($size / 1024, 2) . " KB)</small>";
        $success[] = $backup;
    } else {
        echo "⚠️ <strong style='color: orange;'>WARNING</strong> - Backup no encontrado: <code>{$backup}</code>";
        $warnings[] = "Backup no encontrado: {$backup}";
    }

    echo "</div>";
}

echo "<hr>";

// ============================================================================
// Verificar versión de PHP
// ============================================================================

echo "<h2>4. Verificación de Entorno</h2>";

echo "<div style='padding: 5px; margin: 5px 0;'>";
$phpVersion = phpversion();
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "✅ <strong style='color: green;'>OK</strong> - PHP versión: <code>{$phpVersion}</code>";
    $success[] = "PHP version compatible";
} else {
    echo "❌ <strong style='color: red;'>ERROR</strong> - PHP versión: <code>{$phpVersion}</code> (se requiere 7.4+)";
    $errores[] = "PHP version incompatible: {$phpVersion}";
}
echo "</div>";

echo "<div style='padding: 5px; margin: 5px 0;'>";
if (extension_loaded('pdo')) {
    echo "✅ <strong style='color: green;'>OK</strong> - Extensión PDO disponible";
    $success[] = "PDO disponible";
} else {
    echo "❌ <strong style='color: red;'>ERROR</strong> - Extensión PDO no disponible";
    $errores[] = "PDO no disponible";
}
echo "</div>";

echo "<div style='padding: 5px; margin: 5px 0;'>";
if (extension_loaded('curl')) {
    echo "✅ <strong style='color: green;'>OK</strong> - Extensión cURL disponible";
    $success[] = "cURL disponible";
} else {
    echo "❌ <strong style='color: red;'>ERROR</strong> - Extensión cURL no disponible";
    $errores[] = "cURL no disponible";
}
echo "</div>";

echo "<hr>";

// ============================================================================
// Resumen
// ============================================================================

echo "<h2>📊 Resumen de Verificación</h2>";

echo "<div style='background: #e8f5e9; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<strong style='color: green;'>✅ Éxitos:</strong> " . count($success);
echo "</div>";

if (!empty($warnings)) {
    echo "<div style='background: #fff3e0; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong style='color: orange;'>⚠️ Advertencias:</strong> " . count($warnings);
    echo "<ul>";
    foreach ($warnings as $warning) {
        echo "<li>{$warning}</li>";
    }
    echo "</ul>";
    echo "</div>";
}

if (!empty($errores)) {
    echo "<div style='background: #ffebee; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong style='color: red;'>❌ Errores:</strong> " . count($errores);
    echo "<ul>";
    foreach ($errores as $error) {
        echo "<li>{$error}</li>";
    }
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #e8f5e9; padding: 20px; margin: 20px 0; border-radius: 5px; text-align: center;'>";
    echo "<h3 style='color: green; margin: 0;'>✅ ¡Todas las verificaciones pasaron exitosamente!</h3>";
    echo "<p>El módulo de Documentos Electrónicos está correctamente instalado y configurado.</p>";
    echo "</div>";
}

// ============================================================================
// Próximos pasos
// ============================================================================

echo "<hr>";
echo "<h2>🚀 Próximos Pasos</h2>";
echo "<ol>";
echo "<li><strong>Probar en desarrollo:</strong> Intentar enviar un documento de prueba</li>";
echo "<li><strong>Revisar logs:</strong> Los logs se guardan en <code>log_envio_*.txt</code></li>";
echo "<li><strong>Comparar JSON:</strong> Verificar que los documentos generados sean idénticos</li>";
echo "<li><strong>Validar con Taxxa:</strong> Usar ambiente de pruebas primero</li>";
echo "</ol>";

echo "<hr>";
echo "<p style='text-align: center; color: #666;'>";
echo "Verificación completada - " . date('Y-m-d H:i:s');
echo "</p>";
