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
class Detalles
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
        if (isset($array['search']) && $array['search'] != '') {
            $where .= " AND (`e`.`no_documento` LIKE '%{$array['search']}%' OR CONCAT_WS (' ',`e`.`nombre1`,`nombre2`,`apellido1`,`apellido2`) LIKE '%{$array['search']}%' 
                        OR `nce`.`descripcion_carg` LIKE '%{$array['search']}%')";
        }


        $sql = "WITH 
                    `bsp` AS 
                        (SELECT `id_empleado`,`val_bsp` FROM `nom_liq_bsp` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
                    `ces` AS
                        (SELECT `id_empleado`,`val_cesantias`,`val_icesantias` FROM `nom_liq_cesantias` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
                    `com` AS
                        (SELECT `id_empleado`,`val_compensa` FROM `nom_liq_compesatorio` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
                    `dcto` AS 
                        (SELECT
                            `nom_otros_descuentos`.`id_empleado`, SUM(`nom_liq_descuento`.`valor`) AS `valor`
                        FROM
                            `nom_liq_descuento`
                            INNER JOIN `nom_otros_descuentos` 
                            ON (`nom_liq_descuento`.`id_dcto` = `nom_otros_descuentos`.`id_dcto`)
                        WHERE (`nom_liq_descuento`.`estado` = 1 AND `nom_liq_descuento`.`id_nomina` = :id_nomina)),
                    `liq` AS 
                        (SELECT `id_empleado`,`dias_liq`,`val_liq_dias`,`val_liq_auxt`,`aux_alim`,`g_representa`,`horas_ext` FROM `nom_liq_dlab_auxt` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
                    `emb` AS 
                        (SELECT
                            `nom_embargos`.`id_empleado`, SUM(`nom_liq_embargo`.`val_mes_embargo`) AS `valor`
                        FROM
                            `nom_liq_embargo`
                            INNER JOIN `nom_embargos` 
                            ON (`nom_liq_embargo`.`id_embargo` = `nom_embargos`.`id_embargo`)
                        WHERE (`nom_liq_embargo`.`id_nomina` = :id_nomina AND `nom_liq_embargo`.`estado` = 1)),
                    `inc` AS
                        (SELECT
                            `nom_incapacidad`.`id_empleado` , SUM(`nom_liq_incap`.`pago_empresa` + `nom_liq_incap`.`pago_eps` + `nom_liq_incap`.`pago_arl`) AS `valor`, SUM(`nom_liq_incap`.`dias_liq`) AS `dias`
                        FROM
                            `nom_liq_incap`
                            INNER JOIN `nom_incapacidad` 
                            ON (`nom_liq_incap`.`id_incapacidad` = `nom_incapacidad`.`id_incapacidad`)
                        WHERE (`nom_liq_incap`.`estado` = 1 AND `nom_liq_incap`.`id_nomina` = :id_nomina)
                        GROUP BY `nom_incapacidad`.`id_empleado`),
                    `lib` AS 
                        (SELECT
                            `nom_libranzas`.`id_empleado`, SUM(`nom_liq_libranza`.`val_mes_lib`) AS `valor`
                        FROM
                            `nom_liq_libranza`
                            INNER JOIN `nom_libranzas` 
                            ON (`nom_liq_libranza`.`id_libranza` = `nom_libranzas`.`id_libranza`)
                        WHERE (`nom_liq_libranza`.`id_nomina` = :id_nomina AND `nom_liq_libranza`.`estado` = 1)
                        GROUP BY `nom_libranzas`.`id_empleado`),
                    `rfte` AS
                        (SELECT `id_empleado`,`val_ret` FROM `nom_retencion_fte` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
                    `luto` AS 
                        (SELECT
                            `nom_licencia_luto`.`id_empleado`, SUM(`nom_liq_licluto`.`val_liq`) AS `valor`, SUM(`nom_liq_licluto`.`dias_licluto`) AS `dias`
                        FROM
                            `nom_liq_licluto`
                            INNER JOIN `nom_licencia_luto` 
                            ON (`nom_liq_licluto`.`id_licluto` = `nom_licencia_luto`.`id_licluto`)
                        WHERE (`nom_liq_licluto`.`estado` = 1
                            AND `nom_liq_licluto`.`id_nomina` = :id_nomina)
                        GROUP BY `nom_licencia_luto`.`id_empleado`),
                    `lmp` AS 
                        (SELECT
                            `nom_licenciasmp`.`id_empleado`, `nom_liq_licmp`.`val_liq`, `nom_liq_licmp`.`dias_liqs`
                        FROM
                            `nom_liq_licmp`
                            INNER JOIN `nom_licenciasmp` 
                            ON (`nom_liq_licmp`.`id_licmp` = `nom_licenciasmp`.`id_licmp`)
                        WHERE (`nom_liq_licmp`.`estado` = 1
                            AND `nom_liq_licmp`.`id_nomina` = :id_nomina)),
                    `lcnr` AS
                        (SELECT
                            `nom_licenciasnr`.`id_empleado`, SUM(`nom_liq_licnr`.`dias_licnr`) AS `dias`
                        FROM
                            `nom_liq_licnr`
                            INNER JOIN `nom_licenciasnr` 
                                ON (`nom_liq_licnr`.`id_licnr` = `nom_licenciasnr`.`id_licnr`)
                        WHERE (`nom_liq_licnr`.`estado` = 1 AND `nom_liq_licnr`.`id_nomina` = :id_nomina)
                        GROUP BY `nom_licenciasnr`.`id_empleado`),
                    `pris` AS 
                        (SELECT `id_empleado`,`val_liq_ps` FROM `nom_liq_prima` WHERE `estado` = 1 AND `id_nomina` = :id_nomina),
                    `prin` AS
                        (SELECT `id_empleado`,`val_liq_pv` FROM `nom_liq_prima_nav` WHERE `estado` = 1 AND `id_nomina` = :id_nomina),
                    `segs` AS
                        (SELECT `id_empleado`,`aporte_salud_emp`,`aporte_pension_emp`,`aporte_solidaridad_pensional` FROM `nom_liq_segsocial_empdo` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
                    `sind` AS 
                        (SELECT
                            `nom_cuota_sindical`.`id_empleado`, `nom_liq_sindicato_aportes`.`val_aporte`
                        FROM
                            `nom_liq_sindicato_aportes`
                            INNER JOIN `nom_cuota_sindical` 
                            ON (`nom_liq_sindicato_aportes`.`id_cuota_sindical` = `nom_cuota_sindical`.`id_cuota_sindical`)
                        WHERE (`nom_liq_sindicato_aportes`.`id_nomina` = :id_nomina AND `nom_liq_sindicato_aportes`.`estado` = 1)),
                    `vac` AS 
                        (SELECT
                            `nom_vacaciones`.`id_empleado`, `nom_liq_vac`.`val_liq`, `nom_liq_vac`.`val_prima_vac`, `nom_liq_vac`.`val_bon_recrea`, `nom_liq_vac`.`dias_liqs`
                        FROM
                            `nom_liq_vac`
                            INNER JOIN `nom_vacaciones` 
                            ON (`nom_liq_vac`.`id_vac` = `nom_vacaciones`.`id_vac`)
                        WHERE (`nom_liq_vac`.`estado` = 1 AND `nom_liq_vac`.`id_nomina` = :id_nomina)),
                    `sal` AS 
                        (SELECT `id_empleado`,`sal_base`,`id_contrato`,`val_liq` FROM `nom_liq_salario` WHERE `id_nomina` = :id_nomina AND `estado` = 1)
                SELECT 
                    `e`.`id_empleado`
                    , `ts`.`nom_sede` AS `sede`
                    , `e`.`no_documento`
                    , CONCAT_WS (' ',`e`.`nombre1`,`nombre2`,`apellido1`,`apellido2`) AS `nombre`
                    , `nce`.`descripcion_carg`
                    , `sal`.`sal_base`
                    , IFNULL(`bsp`.`val_bsp`,0) AS `val_bsp`
                    , IFNULL(`ces`.`val_cesantias`,0) AS `val_cesantias`
                    , IFNULL(`ces`.`val_icesantias`,0) AS `val_icesantias`
                    , IFNULL(`com`.`val_compensa`,0) AS `val_compensa`
                    , IFNULL(`dcto`.`valor`,0) AS `valor_dcto`
                    , IFNULL(CAST(`inc`.`dias` AS UNSIGNED),0) AS `dias_incapacidad`
                    , CAST(IFNULL(`lmp`.`dias_liqs`,0) + IFNULL(`luto`.`dias`,0) + IFNULL(`lcnr`.`dias`,0) AS UNSIGNED) AS `dias_licencias`
                    , IFNULL(CAST(`vac`.`dias_liqs` AS UNSIGNED),0) AS `dias_vacaciones`
                    , 0 AS `dias_otros`
                    , IFNULL(CAST(`liq`.`dias_liq` AS UNSIGNED),0) AS `dias_lab`
                    , IFNULL(`liq`.`val_liq_dias`,0) AS `valor_laborado`
                    , IFNULL(`liq`.`val_liq_auxt`,0) AS `aux_tran`
                    , IFNULL(`liq`.`aux_alim`,0) AS `aux_alim`
                    , IFNULL(`liq`.`g_representa`,0) AS `g_representa`
                    , IFNULL(`liq`.`horas_ext` ,0) AS `horas_ext`
                    , IFNULL(`emb`.`valor`,0) AS `valor_embargo`
                    , IFNULL(`inc`.`valor`,0) AS `valor_incap`
                    , IFNULL(`lib`.`valor`,0) AS `valor_libranza`
                    , IFNULL(`luto`.`valor`,0) AS `valor_luto`
                    , IFNULL(`lmp`.`val_liq`,0) AS  `valor_mp`
                    , IFNULL(`pris`.`val_liq_ps`,0) AS `valor_ps` 
                    , IFNULL(`prin`.`val_liq_pv`,0) AS `valor_pv`
                    , IFNULL(`segs`.`aporte_salud_emp`,0) AS `valor_salud`
                    , IFNULL(`segs`.`aporte_pension_emp`,0) AS `valor_pension`
                    , IFNULL(`segs`.`aporte_solidaridad_pensional`,0) AS `val_psolidaria`
                    , IFNULL(`sind`.`val_aporte`,0) AS `valor_sind`
                    , IFNULL(`vac`.`val_liq`,0) AS `valor_vacacion`
                    , IFNULL(`vac`.`val_prima_vac`,0) AS `val_prima_vac`
                    , IFNULL(`vac`.`val_bon_recrea`,0) AS `val_bon_recrea`
                    , IFNULL(`rfte`.`val_ret`,0) AS `val_retencion`
                FROM `nom_empleado` `e`
                    INNER JOIN `nom_cargo_empleado` `nce` ON (`e`.`cargo` = `nce`.`id_cargo`)
                    INNER JOIN `sal` ON (`sal`.`id_empleado` = `e`.`id_empleado`)
                    INNER JOIN `tb_sedes` `ts` ON (`ts`.`id_sede` = `e`.`sede_emp`)
                    LEFT JOIN `bsp` ON (`bsp`.`id_empleado` = `e`.`id_empleado`)
                    LEFT JOIN `ces` ON (`ces`.`id_empleado` = `e`.`id_empleado`)
                    LEFT JOIN `com` ON (`com`.`id_empleado` = `e`.`id_empleado`)
                    LEFT JOIN `dcto` ON (`dcto`.`id_empleado` = `e`.`id_empleado`)
                    LEFT JOIN `liq` ON (`liq`.`id_empleado` = `e`.`id_empleado`)
                    LEFT JOIN `emb` ON (`emb`.`id_empleado` = `e`.`id_empleado`)
                    LEFT JOIN `rfte` ON (`rfte`.`id_empleado` = `e`.`id_empleado`)
                    LEFT JOIN `inc` ON (`inc`.`id_empleado` = `e`.`id_empleado`)
                    LEFT JOIN `lib` ON (`lib`.`id_empleado` = `e`.`id_empleado`)
                    LEFT JOIN `luto` ON (`luto`.`id_empleado` = `e`.`id_empleado`)
                    LEFT JOIN `lmp` ON (`lmp`.`id_empleado` = `e`.`id_empleado`)
                    LEFT JOIN `lcnr` ON (`lcnr`.`id_empleado` = `e`.`id_empleado`)
                    LEFT JOIN `pris` ON (`pris`.`id_empleado` = `e`.`id_empleado`)
                    LEFT JOIN `prin` ON (`prin`.`id_empleado` = `e`.`id_empleado`)
                    LEFT JOIN `segs` ON (`segs`.`id_empleado` = `e`.`id_empleado`)
                    LEFT JOIN `sind` ON (`sind`.`id_empleado` = `e`.`id_empleado`)
                    LEFT JOIN `vac` ON (`vac`.`id_empleado` = `e`.`id_empleado`)
                WHERE (1 = 1 $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id_nomina', $array['id_nomina'], PDO::PARAM_INT);
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
        if (isset($array['search']) && $array['search'] != '') {
            $where .= " AND (`e`.`no_documento` LIKE '%{$array['search']}%' OR CONCAT_WS (' ',`e`.`nombre1`,`nombre2`,`apellido1`,`apellido2`) LIKE '%{$array['search']}%' 
                        OR `nce`.`descripcion_carg` LIKE '%{$array['search']}%')";
        }


        $sql = "WITH
                    `sal` AS 
                        (SELECT `id_empleado`,`sal_base`,`id_contrato`,`val_liq` FROM `nom_liq_salario` WHERE `id_nomina` = :id_nomina AND `estado` = 1)
                    SELECT 
                        COUNT(*) AS `total`
                    FROM `nom_empleado` `e`
                        INNER JOIN `nom_cargo_empleado` `nce` ON (`e`.`cargo` = `nce`.`id_cargo`)
                        INNER JOIN `sal` ON (`sal`.`id_empleado` = `e`.`id_empleado`)
                    WHERE (1 = 1 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id_nomina', $array['id_nomina'], PDO::PARAM_INT);
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

        $sql = "WITH
                    `sal` AS 
                        (SELECT `id_empleado`,`sal_base`,`id_contrato`,`val_liq` FROM `nom_liq_salario` WHERE `id_nomina` = :id_nomina AND `estado` = 1)
                    SELECT 
                        COUNT(*) AS `total`
                    FROM `nom_empleado` `e`
                        INNER JOIN `nom_cargo_empleado` `nce` ON (`e`.`cargo` = `nce`.`id_cargo`)
                        INNER JOIN `sal` ON (`sal`.`id_empleado` = `e`.`id_empleado`)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id_nomina', $array['id_nomina'], PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($registro) ? $registro['total'] : 0;
    }
}
