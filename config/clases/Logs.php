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
        $url1 = '/var/www/html' . Plantilla::getHost() . '/logs/';
        $url2 = 'c:/wamp64/www' . Plantilla::getHost() . '/logs/';
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (!file_exists($url2)) {
            mkdir($url2, 0777, true);
        }

        $fecha = new DateTime('now', new DateTimeZone('America/Bogota'));
        $id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : 'Desconocido';
        $usuario = isset($_SESSION['user']) ? $_SESSION['user'] : 'Desconocido';

        // Construir ruta del archivo usando '/'
        $archivo = $url2 . $fecha->format('Y') . $fecha->format('m') . '.log';

        $cadena = "[{$fecha->format('Y-m-d H:i:s')}] Usuario: $id_user-$usuario, 127.0.0.1, SQL: $sql" . PHP_EOL;

        // Intentar escribir al archivo con manejo de errores
        try {
            if (!file_put_contents($archivo, $cadena, FILE_APPEND | LOCK_EX)) {
                throw new Exception("Error al escribir el log");
            }
        } catch (Exception $e) {
            echo "Error al registrar log: " . $e->getMessage();
        }
    }
}
