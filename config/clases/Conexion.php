<?php

namespace Config\Clases;

use PDO;
use PDOException;

class Conexion
{
    private static $instancia = null;
    private $conexion;
    private $bd_driver;
    private $bd_servidor;
    private $bd_base;
    private $bd_usuario;
    private $bd_clave;
    private $charset;

    private function __construct()
    {
        // Cargar variables de entorno desde .env
        Env::cargar();

        $this->bd_driver   = Env::get('DB_DRIVER', 'mysql');
        $this->bd_servidor = Env::get('DB_HOST', 'localhost:3308');
        $this->bd_base     = Env::get('DB_NAME', 'ipiales');
        $this->bd_usuario  = Env::get('DB_USER', 'root');
        $this->bd_clave    = Env::get('DB_PASS', '12345');
        $this->charset     = "charset=" . Env::get('DB_CHARSET', 'utf8');

        try {
            $dsn = "$this->bd_driver:host=$this->bd_servidor;dbname=$this->bd_base;{$this->charset}";
            $this->conexion = new PDO($dsn, $this->bd_usuario, $this->bd_clave);
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
