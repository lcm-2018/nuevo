<?php

spl_autoload_register(function ($class) {
    // Convertir el namespace en una ruta de archivo
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    
    // Ruta base del proyecto (puede cambiar según tu estructura)
    $baseDir = dirname(__DIR__); // Esto te da /nuevo desde /nuevo/config

    $file = $baseDir . DIRECTORY_SEPARATOR . $path . '.php';

    if (file_exists($file)) {
        require_once $file;
    } else {
        // Ayuda para depurar si algo falla
        echo "❌ No se encontró la clase: $class<br>";
        echo "🔎 Ruta buscada: $file<br>";
    }
});