<?php

namespace Src\Nomina\Liquidado\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Sesion;
use PDO;

/**
 * Clase para gestionar de nomina de los empleados Liquidado.
 *
 * Esta clase permite realizar operaciones CRUD sobre liquidacion de nomina de los empleados,
 * incluyendo la obtención de registros, adición, edición y eliminación de liquidacion de nomina.
 */
class Liquidado
{
    private $conexion;

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    /**
     * Obtiene los datos para la DataTable.
     *
     * @param int $start Índice de inicio para la paginación
     * @param int $length Número de registros a mostrar
     * @param string $array  filtros de búsqueda
     * @param int $col Índice de la columna para ordenar
     * @param string $dir Dirección de ordenamiento (ascendente o descendente) 
     * @return array|[] Retorna un array con los datos 
     */
    public function getRegistrosDT($start, $length, $array, $col, $dir)
    {
        $limit = "";
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }

        $where = '';
        if (!empty($array)) {
            if (isset($array['filter_descripcion']) && $array['filter_descripcion'] != '') {
                $where .= " AND `nom_nominas`.`descripcion` LIKE '%{$array['filter_descripcion']}%'";
            }
            if (isset($array['filter_mes']) && $array['filter_mes'] != '') {
                $where .= " AND `nom_meses`.`nom_mes` LIKE '%{$array['filter_mes']}%'";
            }
            if (isset($array['filter_tipo']) && $array['filter_tipo'] != '') {
                $where .= " AND `nom_nominas`.`tipo` = '{$array['filter_tipo']}'";
            }
            if (isset($array['filter_estado'])) {
                if ($array['filter_estado'] === '') {
                    $where .= " AND `nom_nominas`.`estado` > 0";
                } else if ($array['filter_estado'] == 0) {
                    $where .= " AND `nom_nominas`.`estado` = 0";
                } else if ($array['filter_estado'] == 1) {
                    $where .= " AND `nom_nominas`.`estado` = 1";
                } else if ($array['filter_estado'] == 2) {
                    $where .= " AND `nom_nominas`.`estado` >= 2";
                }
            }
        }
        $vigencia = Sesion::Vigencia();

        $sql = "SELECT
                    `nom_nominas`.`id_nomina`
                    , `nom_nominas`.`descripcion`
                    , `nom_meses`.`nom_mes`
                    , `nom_nominas`.`tipo`
                    , `nom_nominas`.`estado`
                FROM
                    `nom_nominas`
                    INNER JOIN `nom_meses` 
                        ON (`nom_nominas`.`mes` = `nom_meses`.`codigo`)
                WHERE (`nom_nominas`.`vigencia` = '$vigencia' $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);

        return !empty($datos) ? $datos : [];
    }
    /**
     * Obtiene el total de registros filtrados.
     * 
     * @param string $val_busca Valor de búsqueda
     * @return int Total de registros filtrados
     */
    public function getRegistrosFilter($array)
    {
        $where = '';
        if (!empty($array)) {
            if (isset($array['filter_descripcion']) && $array['filter_descripcion'] != '') {
                $where .= " AND `nom_nominas`.`descripcion` LIKE '%{$array['filter_descripcion']}%'";
            }
            if (isset($array['filter_mes']) && $array['filter_mes'] != '') {
                $where .= " AND `nom_meses`.`nom_mes` LIKE '%{$array['filter_mes']}%'";
            }
            if (isset($array['filter_tipo']) && $array['filter_tipo'] != '') {
                $where .= " AND `nom_nominas`.`tipo` = '{$array['filter_tipo']}'";
            }
            if (isset($array['filter_estado'])) {
                if ($array['filter_estado'] === '') {
                    $where .= " AND `nom_nominas`.`estado` > 0";
                } else if ($array['filter_estado'] == 0) {
                    $where .= " AND `nom_nominas`.`estado` = 0";
                } else if ($array['filter_estado'] == 1) {
                    $where .= " AND `nom_nominas`.`estado` = 1";
                } else if ($array['filter_estado'] == 2) {
                    $where .= " AND `nom_nominas`.`estado` >= 2";
                }
            }
        }
        $vigencia = Sesion::Vigencia();

        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_nominas`
                    INNER JOIN `nom_meses` 
                        ON (`nom_nominas`.`mes` = `nom_meses`.`codigo`)
                WHERE (`nom_nominas`.`vigencia` = '$vigencia' $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($registro) ? $registro['total'] : 0;
    }

    /**
     * Obtiene el total de registros.
     * @return int Total de registros
     */

    public function getRegistrosTotal($array)
    {
        $vigencia = Sesion::Vigencia();
        $sql = "SELECT
                    COUNT(*) AS `total`
                FROM
                    `nom_nominas`
                    INNER JOIN `nom_meses` 
                        ON (`nom_nominas`.`mes` = `nom_meses`.`codigo`)
                WHERE (`nom_nominas`.`vigencia` = '$vigencia')";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($registro) ? $registro['total'] : 0;
    }
}
