<?php

namespace Src\Common\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Sesion;
use PDO;

class Rubros
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    /**
     * Obtiene los datos de rubros de la base de datos
     * @param int $busca El valor a buscar en la base de datos
     * @return array Retorna un array con los datos del formulario
     */
    public function getRubros($busca, $tipo)
    {
        $vigencia = Sesion::IdVigencia();
        $sql = "SELECT
                `pto_cargue`.`id_cargue` AS `id`
                , CONCAT(`pto_cargue`.`cod_pptal`, ' - ',`pto_cargue`.`nom_rubro`) AS `value`
                , `pto_cargue`.`tipo_dato` AS `tipo`
            FROM
                `pto_cargue`
                INNER JOIN `pto_presupuestos` 
                    ON (`pto_cargue`.`id_pto` = `pto_presupuestos`.`id_pto`)
            WHERE (`pto_presupuestos`.`id_tipo` = :tpr
                AND `pto_presupuestos`.`id_vigencia` = :vigencia
                AND (`pto_cargue`.`cod_pptal` LIKE :busca
                OR `pto_cargue`.`nom_rubro` LIKE :busca))";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
        $stmt->bindValue(':vigencia', $vigencia, PDO::PARAM_INT);
        $stmt->bindValue(':tpr', $tipo, PDO::PARAM_INT);
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
