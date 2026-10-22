<?php

namespace Config\Clases;

class Env
{
    private static $loaded = false;

    /**
     * Carga las variables del archivo .env a $_ENV, $_SERVER y getenv()
     *
     * @param string|null $path Ruta al directorio que contiene el .env
     */
    public static function cargar(?string $path = null): void
    {
        if (self::$loaded) {
            return;
        }

        $baseDir = $path !== null ? $path : dirname(__DIR__, 2);
        $filePath = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR . '.env';

        if (!file_exists($filePath) || !is_readable($filePath)) {
            self::$loaded = true;
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            self::$loaded = true;
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignorar líneas vacías o comentarios
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            // Separar clave y valor
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Quitar comillas simples o dobles que envuelvan el valor
                $len = strlen($value);
                if (
                    $len >= 2 && (
                        ($value[0] === '"' && $value[$len - 1] === '"') ||
                        ($value[0] === "'" && $value[$len - 1] === "'")
                    )
                ) {
                    $value = substr($value, 1, -1);
                }

                // Registrar en el entorno solo si no está previamente definido
                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }

        self::$loaded = true;
    }

    /**
     * Obtiene una variable de entorno con valor por defecto
     *
     * @param string $key Clave de la variable
     * @param mixed $default Valor por defecto si no existe
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        self::cargar();

        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }

        return $default;
    }
}
