<?php

// ============================================================================
// AUTOLOADER ESPECÍFICO PARA App\DocumentoElectronico (PSR-4)
// ============================================================================
spl_autoload_register(function ($class) {
    // Namespace base para documentos electrónicos
    $prefix = 'App\\DocumentoElectronico\\';

    // Directorio base donde están las clases
    $baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'contabilidad'
        . DIRECTORY_SEPARATOR . 'soportes' . DIRECTORY_SEPARATOR . 'equivalente'
        . DIRECTORY_SEPARATOR . 'DocumentoElectronico' . DIRECTORY_SEPARATOR;

    // Verificar si la clase usa el namespace que manejamos
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No es nuestro namespace, dejar que otro autoloader lo maneje
        return;
    }

    // Obtener el nombre de la clase relativo
    $relative_class = substr($class, $len);

    // Reemplazar namespace separators con directory separators y agregar .php
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';

    // Si el archivo existe, requerirlo
    if (file_exists($file)) {
        require_once $file;
    }
});

// ============================================================================
// AUTOLOADER ESPECÍFICO PARA Src\Nomina\Electronica\Php\Clases (PSR-4)
// ============================================================================
spl_autoload_register(function ($class) {
    $prefix  = 'Src\\Nomina\\Electronica\\Php\\Clases\\';
    $baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'nomina'
        . DIRECTORY_SEPARATOR . 'electronica' . DIRECTORY_SEPARATOR . 'php'
        . DIRECTORY_SEPARATOR . 'clases' . DIRECTORY_SEPARATOR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// ============================================================================
// AUTOLOADER GENÉRICO (Original)
// ============================================================================
spl_autoload_register(function ($class) {
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $baseDir = dirname(__DIR__);

    $file = $baseDir . DIRECTORY_SEPARATOR . $path . '.php';

    if (file_exists($file)) {
        require_once $file;
    } else {
        // Ayuda para depurar si algo falla
        echo "❌ No se encontró la clase: $class<br>";
        echo "🔎 Ruta buscada: $file<br>";
    }
});

/*
<?php

spl_autoload_register(function ($class) {
    // 1. $path = 'Config/Clases/Plantilla'
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    
    // 2. $baseDir = '/var/www/html/demo'
    $baseDir = dirname(__DIR__);

    // 3. Separamos el nombre del archivo (clase) de los directorios
    
    // $className = 'Plantilla'
    $className = basename($path);
    
    // $namespaceDir = 'Config/Clases'
    $namespaceDir = dirname($path);

    // 4. Convertimos a minúsculas SOLO los directorios
    // $lowercaseDir = 'config/clases'
    $lowercaseDir = strtolower($namespaceDir);

    // 5. Re-ensamblamos la ruta final
    // Manejamos el caso de clases sin namespace (donde dirname es '.')
    if ($namespaceDir === '.') {
        $file = $baseDir . DIRECTORY_SEPARATOR . $className . '.php';
    } else {
        $file = $baseDir . DIRECTORY_SEPARATOR . $lowercaseDir . DIRECTORY_SEPARATOR . $className . '.php';
    }

    // 6. Verificamos y cargamos
    if (file_exists($file)) {
        require_once $file;
    } else {
        // Ayuda para depurar si algo falla
        echo "❌ No se encontró la clase: $class<br>";
        echo "🔎 Ruta buscada: $file<br>";
    }
});
*/