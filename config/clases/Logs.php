<?php

namespace Config\Clases;

use Config\Clases\Plantilla;

use DateTime;
use DateTimeZone;
use Exception;

class Logs
{

    public function __construct() {}

    public static function  guardaLog($sql)
    {
        //determinar la ruta en local o en el servidor

        $root = $_SERVER['DOCUMENT_ROOT'];
        $url = $root . Plantilla::getHost() . '/logs/';
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Verificar y crear directorio con mejor manejo de errores
        if (!file_exists($url)) {
            if (!mkdir($url, 0777, true)) {
                error_log("Logs.php - No se pudo crear el directorio: $url");
                return; // Salir silenciosamente si no se puede crear
            }
        }

        // Verificar si el directorio es escribible
        if (!is_writable($url)) {
            error_log("Logs.php - El directorio no tiene permisos de escritura: $url");
            return; // Salir silenciosamente
        }

        $fecha = new DateTime('now', new DateTimeZone('America/Bogota'));
        $id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : 'Desconocido';
        $usuario = isset($_SESSION['user']) ? $_SESSION['user'] : 'Desconocido';

        // Construir ruta del archivo usando '/'
        $archivo = $url . $fecha->format('Y') . $fecha->format('m') . '.log';

        $cadena = "[{$fecha->format('Y-m-d H:i:s')}] Usuario: $id_user-$usuario, 127.0.0.1, SQL: $sql;" . PHP_EOL;

        // Intentar escribir al archivo con manejo de errores
        $resultado = @file_put_contents($archivo, $cadena, FILE_APPEND | LOCK_EX);

        if ($resultado === false) {
            $error = error_get_last();
            $errorMsg = $error ? $error['message'] : 'Error desconocido';
            error_log("Logs.php - Error al escribir: $archivo - $errorMsg");
            // No mostrar error al usuario, solo registrar en error_log del servidor
        }
    }
}
