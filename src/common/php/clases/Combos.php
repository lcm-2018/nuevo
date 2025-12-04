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

    public  static function getCentrosCostoxSede($id, $id_sede = 0, $id_cc = 0)
    {
        $where = '';
        if ($id_sede > 0) {
            $where =  " AND `far_centrocosto_area`.`id_sede` = $id_sede";
        }
        if ($id_cc > 0) {
            $where =  " AND `far_centrocosto_area`.`id_centrocosto` = $id_cc";
        }
        $sql = "SELECT
                    `far_centrocosto_area`.`id_area`,`tb_centrocostos`.`nom_centro`
                FROM
                    `far_centrocosto_area`
                    INNER JOIN `tb_centrocostos` 
                        ON (`far_centrocosto_area`.`id_centrocosto` = `tb_centrocostos`.`id_centro`)
                WHERE `tb_centrocostos`.`es_pasivo` = 0 $where
                ORDER BY `tb_centrocostos`.`nom_centro` ASC";
        return (new self())->setConsulta($sql, $id);
    }

    public  static function getTiposDocumento($id)
    {
        $sql = "SELECT `id_tipo_doc`,`descripcion_tipo_doc` FROM `tb_tipo_documento`
                ORDER BY `descripcion_tipo_doc` ASC";
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

    public  static function getModalidad($id = 0)
    {
        $sql = "SELECT 
                    `id_modalidad`, `modalidad`
                FROM `ctt_modalidad`    
                ORDER BY `modalidad`";
        return (new self())->setConsulta($sql, $id);
    }
    public  static function getEstadoAdq($id = 0)
    {
        $sql = "SELECT
                    `id`,`descripcion`
                FROM `ctt_estado_adq`
                WHERE `filtro` = 1
                ORDER BY `descripcion` ASC";
        return (new self())->setConsulta($sql, $id);
    }

    public  static function getModulos($id = 0)
    {
        $sql = "SELECT
                    `id_modulo`,`nom_modulo`
                FROM `seg_modulos` 
                WHERE `id_modulo` >= 50 AND `estado` = 1
                ORDER BY `nom_modulo` ASC";
        return (new self())->setConsulta($sql, $id);
    }

    public  static function getDocumentoFuente($id = 0, $ctb = 0, $tes = 0)
    {
        $where = '';
        if ($ctb != 0) {
            $where .= " AND `contab` = $ctb";
        }
        if ($tes != 0) {
            $where .= " AND `tesor` = $tes";
        }
        $sql = "SELECT
                    `id_doc_fuente`, CONCAT_WS(' - ',`cod`,`nombre`),`contab`,`tesor`
                FROM `ctb_fuente`
                WHERE `estado` = 1 $where
                ORDER BY `cod` ASC";
        return (new self())->setConsulta($sql, $id);
    }

    public  static function getTipoControl($id = 0)
    {
        $sql = "SELECT
                    `id_tipo`, `descripcion`
                FROM `fin_tipo_control`
                ORDER BY `descripcion` ASC";
        return (new self())->setConsulta($sql, $id);
    }

    public  static function getRolUser($id = 0)
    {
        $sql = "SELECT `id_rol`,`nom_rol` FROM `seg_rol` WHERE `id_rol` > 0 ORDER BY `nom_rol` ASC";
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
    public  static function getMetodoPago($id = 47)
    {
        $sql = "SELECT `codigo`,`metodo` FROM `nom_metodo_pago` ORDER BY `metodo` ASC";
        return (new self())->setConsulta($sql, $id);
    }

    public  static function getArea($id)
    {
        $sql = "SELECT `id_area`,`area` FROM `tb_area_c` ORDER BY `area` ASC";
        return (new self())->setConsulta($sql, $id);
    }

    public  static function getVigencias($id)
    {
        $sql = "SELECT `id_vigencia`, `anio` FROM `tb_vigencias` ORDER BY `anio` DESC";
        return (new self())->setConsulta($sql, $id);
    }

    public function setConsulta($sql, $id)
    {
        $sql = $sql;
        $stmt = Conexion::getConexion()->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
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
