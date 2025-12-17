<?php

/**
 * Verificación CLI - Módulo de Documentos Electrónicos
 */

echo "\n";
echo "================================================================================\n";
echo "  VERIFICACIÓN - MÓDULO DE DOCUMENTOS ELECTRÓNICOS\n";
echo "================================================================================\n\n";

// Incluir autoloader
$autoloaderPath = __DIR__ . '/../../../../config/autoloader.php';
include $autoloaderPath;

// Verificar clases
$clases = [
    'App\\DocumentoElectronico\\TaxxaService',
    'App\\DocumentoElectronico\\DocumentBuilder',
    'App\\DocumentoElectronico\\DocumentRepository',
    'App\\DocumentoElectronico\\DocumentoElectronicoService'
];

echo "1. VERIFICACIÓN DE CLASES\n";
echo "-------------------------\n";

$errores = 0;
foreach ($clases as $clase) {
    if (class_exists($clase)) {
        $reflection = new ReflectionClass($clase);
        $metodos = count($reflection->getMethods(ReflectionMethod::IS_PUBLIC));
        echo "✅ OK - " . basename(str_replace('\\', '/', $clase)) . " ({$metodos} métodos)\n";
    } else {
        echo "❌ ERROR - No se encontró: {$clase}\n";
        $errores++;
    }
}

echo "\n2. VERIFICACIÓN DE ENDPOINTS\n";
echo "----------------------------\n";

$endpoints = [
    'enviar_factura.php' => __DIR__ . '/../enviar_factura.php',
    'enviar_factura_venta.php' => __DIR__ . '/../enviar_factura_venta.php'
];

foreach ($endpoints as $nombre => $ruta) {
    if (file_exists($ruta)) {
        $contenido = file_get_contents($ruta);
        $lineas = count(file($ruta));

        if (strpos($contenido, 'DocumentoElectronicoService') !== false) {
            echo "✅ OK - {$nombre} refactorizado ({$lineas} líneas)\n";
        } else {
            echo "⚠️  WARNING - {$nombre} no refactorizado\n";
            $errores++;
        }
    } else {
        echo "❌ ERROR - No encontrado: {$nombre}\n";
        $errores++;
    }
}

echo "\n3. VERIFICACIÓN DE BACKUPS\n";
echo "-------------------------\n";

$backups = [
    'enviar_factura_backup_20251216.php',
    'enviar_factura_venta_backup_20251216.php'
];

foreach ($backups as $backup) {
    $ruta = __DIR__ . '/../' . $backup;
    if (file_exists($ruta)) {
        $size = number_format(filesize($ruta) / 1024, 2);
        echo "✅ OK - {$backup} ({$size} KB)\n";
    } else {
        echo "⚠️  WARNING - No encontrado: {$backup}\n";
    }
}

echo "\n4. VERIFICACIÓN DE ENTORNO\n";
echo "--------------------------\n";

$phpVersion = phpversion();
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "✅ OK - PHP {$phpVersion}\n";
} else {
    echo "❌ ERROR - PHP {$phpVersion} (se requiere 7.4+)\n";
    $errores++;
}

if (extension_loaded('pdo')) {
    echo "✅ OK - Extensión PDO\n";
} else {
    echo "❌ ERROR - PDO no disponible\n";
    $errores++;
}

if (extension_loaded('curl')) {
    echo "✅ OK - Extensión cURL\n";
} else {
    echo "❌ ERROR - cURL no disponible\n";
    $errores++;
}

echo "\n";
echo "================================================================================\n";
if ($errores == 0) {
    echo "  ✅ VERIFICACIÓN EXITOSA - Todo configurado correctamente\n";
} else {
    echo "  ❌ SE ENCONTRARON {$errores} ERROR(ES)\n";
}
echo "================================================================================\n\n";

echo "Próximos pasos:\n";
echo "  1. Probar envío de documento en desarrollo\n";
echo "  2. Revisar logs generados (log_envio_*.txt)\n";
echo "  3. Validar con Taxxa en ambiente de pruebas\n\n";
