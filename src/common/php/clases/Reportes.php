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
                        , `fin_maestro_doc`.`acumula`
                        , `ctb_fuente`.`nombre`
                        , `ctb_fuente`.`ver_tercero`
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
                $firmas = [];
                foreach ($resultados as $row) {
                    $tipoControl = $row['tipo_control'];
                    if (
                        !isset($firmas[$tipoControl])
                        || $row['fecha_ini'] > $firmas[$tipoControl]['fecha_ini']
                        || (
                            $row['fecha_ini'] === $firmas[$tipoControl]['fecha_ini']
                            && $row['fecha_fin'] > $firmas[$tipoControl]['fecha_fin']
                        )
                    ) {
                        $firmas[$tipoControl] = $row;
                    }
                }
                $resultados = $firmas;
            }
            return $resultados;
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    /**
     * Genera el HTML de firmas para documentos.
     *
     * @param array  $elabora  Datos del elaborador ['nom_tercero' => '', 'cargo' => '']
     * @param int    $modulo   ID del módulo
     * @param string $fecha    Fecha del documento
     * @param string $doc      Código del documento fuente (opcional)
     * @return string HTML de las firmas
     */
    public function getFormFirmas($elabora = [], $modulo, $fecha, $doc = '')
    {
        $html = '';
        $firmas = $this->getFirmas($modulo, $fecha, $doc);
        if (empty($firmas)) {
            return $html;
        }

        // Obtener datos del responsable (tipo_control = 4)
        $nombre_responsable = isset($firmas[4]) ? $firmas[4]['nom_tercero'] : '';
        $cargo_responsable = isset($firmas[4]) ? $firmas[4]['cargo'] : '';

        // Sección del responsable (siempre se muestra)
        $html_responsable = <<<HTML
            <div style="text-align: center; margin-top: 50px; margin-bottom: 20px;">
                <div>___________________________________</div>
                <div>{$nombre_responsable}</div>
                <div>{$cargo_responsable}</div>
            </div>
        HTML;

        // Verificar si el documento tiene control de documentos activo
        $primer_resp = reset($firmas);
        $control = isset($primer_resp['control_doc']) ? $primer_resp['control_doc'] : '';
        $control = !empty($control) && intval($control) !== 0;

        // Si NO tiene control, solo retornar el responsable
        if (!$control) {
            return $html_responsable;
        }

        // --- Tiene control: agregar firmas de Elaboró/Revisó/Aprobó ---
        $lineas = '<div>_______________________</div>';

        // Elaboró (tipo_control = 1, o datos del $elabora como fallback)
        $elaboro_nombre = isset($firmas[1]) ? $firmas[1]['nom_tercero'] : '';
        $elaboro_cargo = isset($firmas[1]) ? $firmas[1]['cargo'] : '';
        // Si no hay firma tipo 1, usar los datos de $elabora
        if (empty($elaboro_nombre) && !empty($elabora)) {
            $elaboro_nombre = isset($elabora['nom_tercero']) ? $elabora['nom_tercero'] : '';
            $elaboro_cargo = isset($elabora['cargo']) ? $elabora['cargo'] : '';
        }

        // Revisó (tipo_control = 2)
        $reviso_nombre = isset($firmas[2]) ? $firmas[2]['nom_tercero'] : '';
        $reviso_cargo = isset($firmas[2]) ? $firmas[2]['cargo'] : '';

        // Aprobó (tipo_control = 3)
        $aprobo_nombre = isset($firmas[3]) ? $firmas[3]['nom_tercero'] : '';
        $aprobo_cargo = isset($firmas[3]) ? $firmas[3]['cargo'] : '';

        // Determinar qué firmas tienen datos
        $firmas_activas = [];
        if (!empty($elaboro_nombre)) {
            $firmas_activas[] = [
                'titulo' => 'Elaboró:',
                'nombre' => $elaboro_nombre,
                'cargo' => $elaboro_cargo
            ];
        }
        if (!empty($reviso_nombre)) {
            $firmas_activas[] = [
                'titulo' => 'Revisó:',
                'nombre' => $reviso_nombre,
                'cargo' => $reviso_cargo
            ];
        }
        if (!empty($aprobo_nombre)) {
            $firmas_activas[] = [
                'titulo' => 'Aprobó:',
                'nombre' => $aprobo_nombre,
                'cargo' => $aprobo_cargo
            ];
        }

        $html_tabla = '';
        if (count($firmas_activas) > 0) {
            $num_firmas = count($firmas_activas);
            $ancho_columna = floor(100 / $num_firmas);

            // Encabezados (Elaboró, Revisó, Aprobó)
            $headtb = '';
            if ($nombre_responsable != '') {
                $headtb = '<tr style="text-align:left">';
                foreach ($firmas_activas as $firma) {
                    $headtb .= "<td style=\"width:{$ancho_columna}%\"><strong>{$firma['titulo']}</strong></td>";
                }
                $headtb .= '</tr>';
            }

            // Celdas con líneas de firma y nombres
            $celdas = '<tr style="text-align:center">';
            foreach ($firmas_activas as $firma) {
                $celdas .= "
                    <td>
                        <br><br>
                        {$lineas}
                        <div>{$firma['nombre']}</div>
                        <div>{$firma['cargo']}</div>
                    </td>";
            }
            $celdas .= '</tr>';

            $html_tabla = <<<HTML
                <div style="text-align: center; padding-top: 25px;">
                    <table style="width:100% !important;font-size: 10px;">
                        {$headtb}
                        {$celdas}
                    </table>
                </div>
            HTML;
        }

        return $html_responsable . $html_tabla;
    }
}
