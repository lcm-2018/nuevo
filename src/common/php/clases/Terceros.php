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
    public function getTerceros($busca)
    {
        $sql = "SELECT `id_tercero_api` AS `id`, `nom_tercero` AS `value`, `nit_tercero` AS `cedula` FROM `tb_terceros` 
                WHERE (`id_tercero_api` LIKE :busca OR `nom_tercero` LIKE :busca  OR `nit_tercero` LIKE :busca ) AND `estado` = 1 AND `id_tercero_api` IS NOT NULL
                ORDER BY `nom_tercero` ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
}
