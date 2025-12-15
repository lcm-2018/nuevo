<?php

namespace Src\Common\Php\Clases;

use Config\Clases\Conexion;
use PDO;

class Terceros
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    /**
     * Obtiene el formulario de un tercero.
     * @param int $busca El valor a buscar en la base de datos
     * @return array Retorna un array con los datos del formulario
     */
    public function getTerceros($busca = NULL, $tipo = NULL)
    {
        $where = '';

        if ($tipo !== NULL) {
            $where = " AND `tb_rel_tercero`.`id_tipo_tercero` = :tipo ";
        }
        if ($busca !== NULL) {
            $where .= " AND `tb_terceros`.`id_tercero_api` LIKE :busca OR `nom_tercero` LIKE :busca OR `nit_tercero` LIKE :busca";
        }
        $sql = "SELECT 
                    `tb_terceros`.`id_tercero_api` AS `id`
                    , `tb_terceros`.`nom_tercero` AS `value`
                    , `tb_terceros`.`nit_tercero` AS `cedula` 
                FROM `tb_terceros`
                    LEFT JOIN `tb_rel_tercero` 
                        ON (`tb_terceros`.`id_tercero_api` = `tb_rel_tercero`.`id_tercero_api`)
                WHERE `estado` = 1 AND `tb_terceros`.`id_tercero_api` IS NOT NULL $where
                GROUP BY `tb_terceros`.`id_tercero_api`
                ORDER BY `nom_tercero` ASC";
        $stmt = $this->conexion->prepare($sql);
        if ($busca !== NULL) {
            $stmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
        }
        if ($tipo !== NULL) {
            $stmt->bindValue(':tipo', $tipo, PDO::PARAM_INT);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        $resultado = [];
        if (!empty($data)) {
            foreach ($data as $row) {
                $resultado[] = [
                    'label'  => $row['value'],
                    'cedula' => $row['cedula'],
                    'id'     => $row['id']
                ];
            }
        }
        return $resultado;
    }


    public function setNombre($nombre)
    {
        $nombre = trim($nombre);
        $nombre = str_replace('-', '', $nombre);
        $nombre = str_replace('.', '', $nombre);
        $nombre = str_replace('  ', ' ', $nombre);
        $nombre = mb_strtoupper($nombre, 'UTF-8');
        return $nombre;
    }

    public function getTercero($id)
    {
        $sql = "SELECT
                    `tb_terceros`.`nit_tercero`
                    , `tb_terceros`.`nom_tercero`
                    , `tb_terceros`.`dir_tercero`
                    , `tb_terceros`.`tel_tercero`
                    , `tb_municipios`.`nom_municipio`
                    , `tb_departamentos`.`nom_departamento`
                FROM
                    `tb_terceros`
                    INNER JOIN `tb_municipios` 
                        ON (`tb_terceros`.`id_municipio` = `tb_municipios`.`id_municipio`)
                    INNER JOIN `tb_departamentos` 
                        ON (`tb_municipios`.`id_departamento` = `tb_departamentos`.`id_departamento`)
                WHERE (`tb_terceros`.`id_tercero_api` = :id) LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $data;
    }
}
