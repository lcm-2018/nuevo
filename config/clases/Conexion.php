<?php

namespace Config\Clases;

use PDO;
use PDOException;

class Conexion
{
    private static $instancia = null;
    private $conexion;
    private $bd_driver = "mysql";
<<<<<<< HEAD
    private $bd_servidor = "localhost";
    private $bd_base = "bd_redsalud";
=======
    private $bd_servidor = "localhost:3308";
    private $bd_base = "cronhis";
>>>>>>> 2a24ed16363670fd4b318493ce628e1d91c26b2d
    private $bd_usuario = "root";
    private $bd_clave = "12345";
    private $charset = "charset=utf8";

    private function __construct()
    {
        try {
            $dsn = "$this->bd_driver:host=$this->bd_servidor;dbname=$this->bd_base;{$this->charset}";
            $this->conexion = new PDO($dsn, $this->bd_usuario, $this->bd_clave);
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
            $this->conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error al conectar con la base de datos: " . $e->getMessage());
        }
    }

    public static function getConexion()
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia->conexion;
    }

    public static function Api()
    {
        return "https://200.7.107.59/api_terceros/";
    }

    public function __destruct()
    {
        if (self::$instancia !== null) {
            self::$instancia->conexion = null;
            self::$instancia = null;
        }
    }
}
