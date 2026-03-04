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

    /**
     * Genera el HTML del encabezado del reporte (logo, empresa, estilos CSS).
     *
     * @param array $empresa Datos de la empresa ['nombre' => '', 'nit' => '', 'dig_ver' => '']
     * @return string HTML del encabezado con estilos
     */
    public function getEncabezado($empresa)
    {
        return <<<HTML
        <style>
            /* Estilos para la pantalla */
            .watermark {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                font-size: 100px;
                color: rgba(255, 0, 0, 0.2);
                z-index: 1000;
                pointer-events: none;
                white-space: nowrap;
            }

            .bordeado {
                border-collapse: collapse;
            }

            .bordeado th,
            .bordeado td {
                border: 1px solid #ccc;
                padding-left: 5px;
                padding-right: 5px;
            }

            /* Estilos específicos para la impresión */
            @media print {
                body {
                    position: relative;
                    margin: 0;
                    padding: 0;
                }

                .watermark {
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%) rotate(-45deg);
                    font-size: 100px;
                    color: rgba(255, 0, 0, 0.2);
                    z-index: -1;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    page-break-inside: auto;
                }

                thead {
                    display: table-header-group;
                }

                tfoot {
                    display: table-footer-group;
                }

                tbody {
                    display: table-row-group;
                }

                tfoot tr {
                    page-break-inside: avoid;
                    padding-bottom: 50px;
                    width: 100%;
                    text-align: center;
                }

                tr {
                    page-break-inside: avoid;
                }
            }
        </style>
        <table style="width:100% !important;" border="0">
            <tr>
                <td style="width:25%">
                    <label>
                        <img src="../../assets/images/logo.png" width="150">
                    </label>
                </td>
                <td style="text-align:center">
                    <strong>{$empresa['nombre']}</strong>
                    <div>NIT {$empresa['nit']} - {$empresa['dig_ver']}</div>
                </td>
            </tr>
        </table>
        HTML;
    }

    /**
     * Obtiene la configuración del documento (responsable, control, costos, acumula, etc.).
     *
     * @param int    $modulo ID del módulo
     * @param string $fecha  Fecha del documento
     * @param string $doc    Código del documento fuente (opcional)
     * @return array Configuración del documento
     */
    public function getConfigDoc($modulo, $fecha, $doc = '')
    {
        $responsables = $this->getFirmas($modulo, $fecha, $doc);
        if (!is_array($responsables)) {
            $responsables = [];
        }
        $primer_resp = !empty($responsables) ? reset($responsables) : [];

        $nom_respon   = isset($responsables[4]) ? $responsables[4]['nom_tercero'] : '';
        $cargo_respon = isset($responsables[4]) ? $responsables[4]['cargo'] : '';
        $gen_respon   = isset($responsables[4]) ? $responsables[4]['genero'] : '';
        $control      = isset($primer_resp['control_doc']) ? $primer_resp['control_doc'] : '';
        $control      = !empty($control) && intval($control) !== 0;

        $ver_costos  = isset($primer_resp['costos']) ? ($primer_resp['costos'] != 1) : true;
        $ver_acumula = isset($primer_resp['acumula']) ? ($primer_resp['acumula'] == 1) : false;
        $ver_tercero = isset($primer_resp['ver_tercero']) ? $primer_resp['ver_tercero'] : 0;

        $where = '';
        if ($ver_acumula) {
            $where = "AND `pto_cargue`.`tipo_dato` = 1";
        }

        return [
            'nom_respon'   => $nom_respon,
            'cargo_respon' => $cargo_respon,
            'gen_respon'   => $gen_respon,
            'control'      => $control,
            'ver_costos'   => $ver_costos,
            'ver_acumula'  => $ver_acumula,
            'ver_tercero'  => $ver_tercero,
            'where'        => $where,
        ];
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
                        , `fin_maestro_doc`.`line_table`
                        , `fin_maestro_doc`.`line_firma`
                        , `fin_maestro_doc`.`ver_head`
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
                        INNER JOIN `fin_tipo_control` 
                            ON (`fin_respon_doc`.`tipo_control` = `fin_tipo_control`.`id_tipo`)
                        LEFT JOIN `tb_terceros` 
                            ON (`fin_respon_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
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
     * @param string $tercero  Nombre del tercero que recibe (opcional)
     * @return string HTML de las firmas
     */
    public function getFormFirmas($elabora = [], $modulo = 0, $fecha = '', $doc = '', $tercero = '')
    {
        $html = '';
        $firmas = $this->getFirmas($modulo, $fecha, $doc);
        if (empty($firmas) || !is_array($firmas)) {
            return $html;
        }

        // Obtener primer elemento para campos generales
        $primer_resp = reset($firmas);

        // Configuración visual de firmas
        $line_table  = isset($primer_resp['line_table'])  ? intval($primer_resp['line_table'])  : 1;
        $line_firma  = isset($primer_resp['line_firma'])  ? intval($primer_resp['line_firma'])  : 1;
        $ver_head    = isset($primer_resp['ver_head'])    ? intval($primer_resp['ver_head'])    : 1;
        $ver_tercero = isset($primer_resp['ver_tercero']) ? intval($primer_resp['ver_tercero']) : 0;
        $control     = isset($primer_resp['control_doc']) ? $primer_resp['control_doc'] : '';
        $control     = !empty($control) && intval($control) !== 0;

        // Datos del responsable (tipo_control = 4)
        $nombre_responsable = isset($firmas[4]) ? $firmas[4]['nom_tercero'] : '';
        $cargo_responsable  = isset($firmas[4]) ? $firmas[4]['cargo'] : '';

        // Sección del tercero que recibe
        $terRecibeCell = '';
        $responsableWidth = '100%';
        if ($ver_tercero == 1 && !empty($tercero)) {
            $terRecibeCell = "
                <td style=\"width:50%; text-align:center; vertical-align:top;\">
                    <div>___________________________________</div>
                    <div>{$tercero}</div>
                    <div>RECIBE CC/NIT:</div>
                </td>";
            $responsableWidth = '50%';
        }

        // Sección del responsable (siempre se muestra)
        $html_responsable = <<<HTML
            <div style="text-align: center; padding-top: 60px; font-size: 13px;">
                <table style="width:100%; border-collapse: collapse;">
                    <tr>
                        <td style="width:{$responsableWidth}; text-align:center; vertical-align:top;">
                            <div>___________________________________</div>
                            <div>{$nombre_responsable}</div>
                            <div>{$cargo_responsable}</div>
                        </td>
                        {$terRecibeCell}
                    </tr>
                </table>
            </div>
        HTML;

        // Si NO tiene control, solo retornar el responsable
        if (!$control) {
            return $html_responsable;
        }

        // --- Tiene control: agregar firmas de Elaboró/Revisó/Aprobó ---
        $lineas = $line_firma == 1 ? '<div>_______________________</div>' : '';

        // Elaboró (tipo_control = 1, con fallback de $elabora)
        $elaboro_nombre = isset($firmas[1]) ? $firmas[1]['nom_tercero'] : '';
        $elaboro_cargo  = isset($firmas[1]) ? $firmas[1]['cargo'] : '';
        if (empty($elaboro_nombre) && !empty($elabora)) {
            $elaboro_nombre = isset($elabora['nom_tercero']) ? $elabora['nom_tercero'] : '';
            $elaboro_cargo  = isset($elabora['cargo']) ? $elabora['cargo'] : '';
        }

        // Revisó (tipo_control = 2)
        $reviso_nombre = isset($firmas[2]) ? $firmas[2]['nom_tercero'] : '';
        $reviso_cargo  = isset($firmas[2]) ? $firmas[2]['cargo'] : '';

        // Aprobó (tipo_control = 3)
        $aprobo_nombre = isset($firmas[3]) ? $firmas[3]['nom_tercero'] : '';
        $aprobo_cargo  = isset($firmas[3]) ? $firmas[3]['cargo'] : '';

        // Determinar qué firmas existen (se muestran aunque el tercero esté en blanco)
        $firmas_activas = [];
        if (!empty($elaboro_nombre) || isset($firmas[1])) {
            $firmas_activas[] = [
                'titulo' => 'Elaboró:',
                'nombre' => $elaboro_nombre,
                'cargo'  => $elaboro_cargo
            ];
        }
        if (isset($firmas[2])) {
            $firmas_activas[] = [
                'titulo' => 'Revisó:',
                'nombre' => $reviso_nombre,
                'cargo'  => $reviso_cargo
            ];
        }
        if (isset($firmas[3])) {
            $firmas_activas[] = [
                'titulo' => 'Aprobó:',
                'nombre' => $aprobo_nombre,
                'cargo'  => $aprobo_cargo
            ];
        }

        $html_tabla = '';
        if (count($firmas_activas) > 0) {
            $num_firmas = count($firmas_activas);
            $ancho_columna = floor(100 / $num_firmas);

            // Encabezados - controlados por ver_head
            $headtb = '';
            if ($ver_head == 1) {
                $headtb = '<tr style="text-align:left">';
                foreach ($firmas_activas as $firma) {
                    $headtb .= "<td style=\"width:{$ancho_columna}%\"><strong>{$firma['titulo']}</strong></td>";
                }
                $headtb .= '</tr>';
            }

            // Bordes - controlados por line_table
            $td_style    = $line_table == 1 ? 'border: 1px solid #ccc;' : 'border: none;';
            $table_border = $line_table == 1 ? 'border: 1px solid #ccc;' : 'border: none;';

            // Celdas con líneas de firma y nombres
            $celdas = '<tr style="text-align:center">';
            foreach ($firmas_activas as $firma) {
                $celdas .= "
                    <td style=\"{$td_style}\">
                        <br><br>
                        {$lineas}
                        <div>{$firma['nombre']}</div>
                        <div>{$firma['cargo']}</div>
                    </td>";
            }
            $celdas .= '</tr>';

            $html_tabla = <<<HTML
                <div style="text-align: center; padding-top: 25px;">
                    <table class="bg-light" style="width:100% !important;font-size: 10px; border-collapse: collapse; {$table_border}">
                        {$headtb}
                        {$celdas}
                    </table>
                </div>
            HTML;
        }

        return $html_responsable . $html_tabla;
    }
}
