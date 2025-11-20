<?php

namespace Src\Usuarios\Login\Php\Clases;

use Config\Clases\Conexion;
use PDO;

class Usuario
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    /**
     * Obtiene los datos del usuario por su nombre de usuario.
     *
     * @param string $usuario Nombre del usuario
     * @return array|null Retorna un array con los datos del usuario o null si no existe
     */
    public function getUser($usuario)
    {
        $sql = "SELECT 
                    `id_usuario`, `login` ,`clave` , CONCAT(`nombre1`, ' ', `apellido1`) as `nombre` ,`id_rol` , `estado` 
                FROM `seg_usuarios_sistema`  
                WHERE `login` = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(1, $usuario, PDO::PARAM_STR);
        $stmt->execute();

        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);

        return $datos ?: null;
    }

    public function getUsers($busca)
    {
        $sql = "SELECT 
                    `id_usuario`, CONCAT_WS(' ',`nombre1`, `nombre2`, `apellido1`, `apellido2`) AS `nombre`, `num_documento`
                FROM `seg_usuarios_sistema`  
                WHERE (`nombre1` LIKE '%{$busca}%' OR `nombre2` LIKE '%{$busca}%' OR `apellido1` LIKE '%{$busca}%' OR `apellido2` LIKE '%{$busca}%' OR `num_documento` LIKE '%{$busca}%') AND `estado` = '1'";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':busca', $busca, PDO::PARAM_STR);
        $stmt->execute();

        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);

        return $datos ?: [];
    }

    public function getEmpresa()
    {
        $sql = "SELECT
                    `nit_ips` AS `nit` , `dv`, `razon_social_ips` AS `nombre` , `caracter` , `tiene_pto`, `exonera_aportes`, `redondeo_nomina` AS `redondeo` 
                FROM
                    `tb_datos_ips`";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $result;
    }

    public function getvigencia()
    {
        $sql = "SELECT 
                    `id_vigencia`, `anio` FROM  `tb_vigencias` 
                WHERE `id_vigencia` = (SELECT MAX(`id_vigencia`) FROM `tb_vigencias`)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }

    public function getFormPerfilUsuario($id_user)
    {
        return "<div>Formulario del Perfil de Usuario xd: {$id_user}</div>";
    }
}
