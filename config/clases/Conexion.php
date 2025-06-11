<?php

namespace Config\Clases;

use PDO;
use PDOException;

class Conexion
{
    private static $instancia = null;
    private $conexion;

    private $bd_driver = "mysql";
    private $bd_servidor = "localhost";
    private $bd_base = "bd_cronhis_af";
    private $bd_usuario = "root";
    private $bd_clave = "12345";
    private $charset = "charset=utf8";

    private function __construct()
    {
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

    public function __destruct()
    {
        if (self::$instancia !== null) {
            self::$instancia->conexion = null;
            self::$instancia = null;
        }
    }
}
