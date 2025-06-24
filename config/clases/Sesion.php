<?php

namespace Config\Clases;

use DateTime;
use DateTimeZone;

/**
 * Clase Sesion
 * Maneja las sesiones de usuario y proporciona métodos para acceder a los datos de la sesión.
 */

class Sesion
{
    private static function iniciarSesion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private static function obtener($clave)
    {
        self::iniciarSesion();
        return $_SESSION[$clave] ?? null;
    }

    public static function IdUser()
    {
        return self::obtener('id_user');
    }

    public static function User()
    {
        return self::obtener('user');
    }

    public static function Login()
    {
        return self::obtener('login');
    }

    public static function Rol()
    {
        return self::obtener('rol');
    }

    public static function Caracter()
    {
        return self::obtener('caracter');
    }

    public static function IdVigencia()
    {
        return self::obtener('id_vigencia');
    }

    public static function Vigencia()
    {
        return self::obtener('vigencia');
    }

    public static function NitIPS()
    {
        return self::obtener('nit_emp');
    }

    public static function Pto()
    {
        return self::obtener('pto');
    }

    /**
     * Devuelve la fecha y hora actual en la zona horaria de Bogotá.
     * @return DateTime
     */
    public static function Hoy()
    {
        $date = new DateTime('now', new DateTimeZone('America/Bogota'));
        return $date->format('Y-m-d H:i:s');
    }
    /**
     * Devuelve la fecha actual en la zona horaria de Bogotá.
     * @return DateTime
     */
    public static function _Hoy()
    {
        $date = new DateTime('now', new DateTimeZone('America/Bogota'));
        return $date->format('Y-m-d');
    }

    public static function FechaFinal($mes)
    {
        $year = self::Vigencia();
        $fecha = new DateTime("$year-$mes-01");
        $fecha->modify('last day of this month');
        return $fecha->format('Y-m-d');
    }
}
