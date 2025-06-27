<?php

namespace Src\Common\Php\Clases;

use Config\Clases\Conexion;
use PDO;

class Combos
{

    public function __construct() {}

    /**
     * Obtiene los datos de las Sedes de una empresa de la base de datos
     * @param int $busca El valor a buscar en la base de datos
     * @return array Retorna un array con los datos del formulario
     */
    public  static function getSedes($id)
    {
        $sql = "SELECT `id_sede`, `nom_sede` FROM `tb_sedes` WHERE `estado` = 1 ORDER BY `nom_sede` ASC";
        return (new self())->setConsulta($sql, $id);
    }

    public  static function getTiposDocumento($id)
    {
        $sql = "SELECT `id_tipodoc`,`descripcion` FROM `tb_tipos_documento`
                ORDER BY `descripcion` ASC";
        return (new self())->setConsulta($sql, $id);
    }

    public  static function getPaises($id)
    {
        $sql = "SELECT `id_pais`,`nom_pais` FROM `tb_paises`
                WHERE `id_pais` > 0
                ORDER BY 
                    CASE WHEN `nom_pais` = 'COLOMBIA' THEN 0 ELSE 1 END,
                    `nom_pais` ASC";
        return (new self())->setConsulta($sql, $id);
    }

    public  static function getDepartamentos($id)
    {
        $sql = "SELECT `id_departamento`,`nom_departamento` FROM `tb_departamentos`
                WHERE `id_departamento` > 0
                ORDER BY `nom_departamento` ASC";
        return (new self())->setConsulta($sql, $id);
    }

    public  static function getMunicipios($dpto, $id)
    {
        $sql = "SELECT `id_municipio`,`nom_municipio` FROM `tb_municipios`
                WHERE `id_municipio` > 0 AND `id_departamento` = {$dpto}
                ORDER BY `nom_municipio` ASC";
        return (new self())->setConsulta($sql, $id);
    }

    public  static function getBancos($id)
    {
        $sql = "SELECT `id_banco`, `nom_banco` FROM `tb_bancos`
                ORDER BY `nom_banco` ASC";
        return (new self())->setConsulta($sql, $id);
    }

    public  static function getCentrosCosto($id)
    {
        $sql = "SELECT `id_centro`, `nom_centro`FROM `tb_centrocostos`
                WHERE `id_centro`> 0 AND `es_pasivo` = 0
                ORDER BY `nom_centro` ASC ";
        return (new self())->setConsulta($sql, $id);
    }

    public  static function getMeses($id = 0)
    {
        $sql = "SELECT
                    `codigo`,`nom_mes`
                FROM `nom_meses`
                ORDER BY `codigo` ASC";
        return (new self())->setConsulta($sql, $id);
    }

    public  static function getCategoriaTercero($id = 0, $cat = '')
    {
        $where = '';
        if ($cat != '') {
            $where = " AND `tipo` = '$cat'";
        }
        $sql = "SELECT
                    `id_cat`,`descripcion`
                FROM `nom_categoria_tercero`
                WHERE 1 = 1 $where
                ORDER BY `descripcion` ASC";
        return (new self())->setConsulta($sql, $id);
    }

    public  static function getTipoLiquidacion($id = 2)
    {
        $sql = "SELECT
                    `id_tipo`,`descripcion`
                FROM `nom_tipo_liquidacion`
                WHERE `id_tipo` > 1
                ORDER BY 
                    (`descripcion` = 'MENSUAL EMPLEADOS') DESC, `descripcion`";
        return (new self())->setConsulta($sql, $id);
    }

    public function setConsulta($sql, $id)
    {
        $sql = $sql;
        $stmt = Conexion::getConexion()->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resultado = '';
        if (!empty($data)) {
            return (new self())->setOption($data, $id);
        }
        return $resultado;
    }

    public static function setOption($array, $id)
    {
        $headers = array_keys($array[0]);
        $resultado = '<option value="0" class="text-muted">-- Seleccionar --</option>';
        foreach ($array as $row) {
            $slc = $row[$headers[0]] == $id ? 'selected' : '';
            $resultado .= '<option value="' . $row[$headers[0]] . '" ' . $slc . '>' . mb_strtoupper($row[$headers[1]]) . '</option>';
        }
        return $resultado;
    }
}
