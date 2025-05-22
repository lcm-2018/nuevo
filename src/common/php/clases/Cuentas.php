<?php

namespace Src\Common\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Sesion;
use PDO;

class Cuentas
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    /**
     * Obtiene los datos de Cuentas de la base de datos
     * @param int $busca El valor a buscar en la base de datos
     * @return array Retorna un array con los datos del formulario
     */
    public function getCuentas($busca)
    {
        $sql = "SELECT 
                    `id_pgcp` AS `id`,CONCAT(`cuenta`,' - ',`nombre`) AS `value`, `tipo_dato` AS `tipo`
                FROM `ctb_pgcp`
                WHERE `estado` = 1 AND (`cuenta` LIKE :busca OR `nombre` LIKE :busca)
                ORDER BY `cuenta` ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resultado = [];
        if (!empty($data)) {
            foreach ($data as $row) {
                $resultado[] = [
                    'label'     => $row['value'],
                    'tipo'      => $row['tipo'],
                    'id'        => $row['id']
                ];
            }
        }
        return $resultado;
    }
}
