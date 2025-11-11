<?php

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