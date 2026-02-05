<?php

namespace Src\Common\Php\Clases;

use Config\Clases\Conexion;
use PDO;
use PDOException;

/**
 * Clase para gestionar de nomina de los empleados Liquidado.
 *
 * Esta clase permite realizar operaciones CRUD,
 * incluyendo la obtención de registros, adición, edición y eliminación.
 */
class Reportes
{
    private $conexion;

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    public function getFirmas($modulo, $fecha, $doc = '')
    {
        $where = '';
        if (!($doc == '' || $doc == null)) {
            $where .= " AND `ctb_fuente`.`cod` = '$doc'";
        }
        try {
            $sql = "SELECT
                        `fin_maestro_doc`.`control_doc`
                        , `fin_maestro_doc`.`id_doc_fte`
                        , `fin_maestro_doc`.`costos`
                        , `ctb_fuente`.`nombre`
                        , `tb_terceros`.`nom_tercero`
                        , `tb_terceros`.`nit_tercero`
                        , `tb_terceros`.`genero`
                        , `fin_respon_doc`.`cargo`
                        , `fin_respon_doc`.`tipo_control`
                        , `fin_tipo_control`.`descripcion` AS `nom_control`
                        , `fin_respon_doc`.`fecha_ini`
                        , `fin_respon_doc`.`fecha_fin`
                    FROM
                        `fin_respon_doc`
                        INNER JOIN `fin_maestro_doc` 
                            ON (`fin_respon_doc`.`id_maestro_doc` = `fin_maestro_doc`.`id_maestro`)
                        INNER JOIN `ctb_fuente` 
                            ON (`ctb_fuente`.`id_doc_fuente` = `fin_maestro_doc`.`id_doc_fte`)
                        INNER JOIN `tb_terceros` 
                            ON (`fin_respon_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
                        INNER JOIN `fin_tipo_control` 
                            ON (`fin_respon_doc`.`tipo_control` = `fin_tipo_control`.`id_tipo`)
                    WHERE (`fin_maestro_doc`.`id_modulo` = :modulo $where
                        AND `fin_respon_doc`.`fecha_fin` >= :fecha 
                        AND `fin_respon_doc`.`fecha_ini` <= :fecha
                        AND `fin_respon_doc`.`estado` = 1
                        AND `fin_maestro_doc`.`estado` = 1)";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(':modulo', $modulo, PDO::PARAM_INT);
            $stmt->bindValue(':fecha', $fecha, PDO::PARAM_STR);
            $stmt->execute();
            $resultados = $stmt->fetchAll();
            $stmt->closeCursor();
            unset($stmt);
            if (!empty($resultados)) {
                $resultados = array_column($resultados, null, 'tipo_control');
            }
            return $resultados;
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    public function getFormFirmas($elabora = [], $modulo, $fecha, $doc = '')
    {
        $html = '';
        $firmas = $this->getFirmas($modulo, $fecha, $doc);
        if (empty($firmas)) {
            return $html;
        } else {
            $nombre_responsable = isset($firmas[4]) ? $firmas[4]['nom_tercero'] : '';
            $cargo_responsable = isset($firmas[4]) ? $firmas[4]['cargo'] : '';

            $html =
                <<<HTML
                    <div style="text-align: center; margin-top: 50px; margin-bottom: 20px;">
                        <div>___________________________________</div>
                        <div>{$nombre_responsable}</div>
                        <div>{$cargo_responsable}</div>
                    </div>
                HTML;
            return $html;
        }
    }
}
