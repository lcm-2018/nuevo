<?php

namespace Src\Nomina\Liquidado\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Sesion;
use PDO;
use Src\Nomina\Empleados\Php\Clases\Valores_Liquidacion;

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

        if (isset($array['id_empleado'])) {
            $where .= " AND `e`.`id_empleado` = {$array['id_empleado']}";
        }

        if (isset($array['search']) && $array['search'] != '') {
            $where .= " AND (`e`.`no_documento` LIKE '%{$array['search']}%' OR CONCAT_WS (' ',`e`.`nombre1`,`nombre2`,`apellido1`,`apellido2`) LIKE '%{$array['search']}%' OR `cargo`.`descripcion_carg` LIKE '%{$array['search']}%')";
        }

        $sql = "WITH 
                    `bsp` AS 
                        (SELECT `id_empleado`,`val_bsp`, `fec_corte` AS `corte_bsp` FROM `nom_liq_bsp` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
                    `ces` AS
                        (SELECT `id_empleado`,`val_cesantias`,`val_icesantias`, `corte` AS `corte_ces`, `cant_dias` AS `dias_ces` FROM `nom_liq_cesantias` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
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
                        WHERE (`nom_liq_embargo`.`id_nomina` = :id_nomina AND `nom_liq_embargo`.`estado` = 1)
                        GROUP BY `nom_embargos`.`id_empleado`),
                    `inc` AS
                        (SELECT
                            `nom_incapacidad`.`id_empleado`, SUM(`nom_liq_incap`.`pago_empresa`) AS `pago_empresa`, SUM(`nom_liq_incap`.`pago_empresa` + `nom_liq_incap`.`pago_eps` + `nom_liq_incap`.`pago_arl`) AS `valor`, SUM(`nom_liq_incap`.`dias_liq`) AS `dias`
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
                        (SELECT `id_empleado`,`val_ret`, `base` AS `base_ret` FROM `nom_retencion_fte` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
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
                        (SELECT `id_empleado`,`val_liq_ps`, `cant_dias` AS `dias_ps`, `corte` AS `corte_ps` FROM `nom_liq_prima` WHERE `estado` = 1 AND `id_nomina` = :id_nomina),
                    `prin` AS
                        (SELECT `id_empleado`,`val_liq_pv`, `cant_dias` AS `dias_pn`, `corte` AS `corte_pn` FROM `nom_liq_prima_nav` WHERE `estado` = 1 AND `id_nomina` = :id_nomina),
                    `segs` AS
                        (SELECT `id_empleado`,`aporte_salud_emp`,`aporte_pension_emp`, `aporte_solidaridad_pensional`, `aporte_salud_empresa`, `aporte_pension_empresa`, `aporte_rieslab` FROM `nom_liq_segsocial_empdo` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
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
                            `nom_vacaciones`.`id_empleado`, `nom_liq_vac`.`val_liq`, `nom_liq_vac`.`val_prima_vac`, `nom_liq_vac`.`val_bon_recrea`, `nom_vacaciones`.`dias_habiles`, `nom_vacaciones`.`corte` AS `corte_vac`, `nom_vacaciones`.`dias_inactivo`
                        FROM
                            `nom_liq_vac`
                            INNER JOIN `nom_vacaciones` 
                            ON (`nom_liq_vac`.`id_vac` = `nom_vacaciones`.`id_vac`)
                        WHERE (`nom_liq_vac`.`estado` = 1 AND `nom_liq_vac`.`id_nomina` = :id_nomina)),
                    `sal` AS 
                        (SELECT `id_empleado`,`sal_base`,`id_contrato`,`val_liq`, `metodo_pago` FROM `nom_liq_salario` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
                    `cargo` AS 
                        (SELECT
                            `nom_liq_salario`.`id_empleado`
                            , `nom_cargo_empleado`.`descripcion_carg`
                            , `nom_cargo_empleado`.`tipo_cargo`
                        FROM
                            `nom_liq_salario`
                            INNER JOIN `nom_contratos_empleados` 
                                ON (`nom_liq_salario`.`id_contrato` = `nom_contratos_empleados`.`id_contrato_emp`)
                            LEFT JOIN `nom_cargo_empleado` 
                                ON (`nom_contratos_empleados`.`id_cargo` = `nom_cargo_empleado`.`id_cargo`)
                        WHERE (`nom_liq_salario`.`id_nomina` = :id_nomina AND `nom_liq_salario`.`estado` = 1)),
                    `paraf` AS 
                        (SELECT `id_empleado`,`val_sena`,`val_icbf`,`val_comfam` FROM `nom_liq_parafiscales` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
                    `nom` AS
                        (SELECT `id_nomina`, `mes`, `tipo`, `estado` FROM `nom_nominas` WHERE `id_nomina` = :id_nomina),
                    `indem` AS
                        (SELECT
                            `nom_indemniza_vac`.`id_empleado`
                            , `nom_liq_indemniza_vac`.`val_liq` AS `val_indemniza`
                        FROM
                            `nom_liq_indemniza_vac`
                            INNER JOIN `nom_indemniza_vac` 
                                ON (`nom_liq_indemniza_vac`.`id_indemnizacion` = `nom_indemniza_vac`.`id_indemniza`)
                        WHERE (`nom_liq_indemniza_vac`.`estado` = 1
                            AND `nom_liq_indemniza_vac`.`id_nomina` = :id_nomina)),
                    `ccosto` AS
                        (SELECT `id_empleado`, GROUP_CONCAT(`id_ccosto` SEPARATOR ',') AS `id_ccosto` FROM `nom_ccosto_empleado` GROUP BY `id_empleado`)
                SELECT 
                    `e`.`id_empleado`
                    , `ts`.`nom_sede` AS `sede`
                    , `e`.`no_documento`
                    , CONCAT_WS (' ',`e`.`nombre1`,`nombre2`,`apellido1`,`apellido2`) AS `nombre`
                    , `cargo`.`descripcion_carg`
                    , `cargo`.`tipo_cargo`
                    , `sal`.`sal_base`
                    , `sal`.`metodo_pago`
                    , `sal`.`id_contrato`
                    , IFNULL(`bsp`.`val_bsp`,0) AS `val_bsp`
                    , IFNULL(`bsp`.`corte_bsp`, CURDATE()) AS `corte_bsp`
                    , IFNULL(`ces`.`val_cesantias`,0) AS `val_cesantias`
                    , IFNULL(`ces`.`corte_ces`, CURDATE()) AS `corte_ces`
                    , IFNULL(`ces`.`val_icesantias`,0) AS `val_icesantias`
                    , IFNULL(`ces`.`dias_ces`,0) AS `dias_ces`
                    , IFNULL(`com`.`val_compensa`,0) AS `val_compensa`
                    , IFNULL(`indem`.`val_indemniza`,0) AS `val_indemniza`
                    , IFNULL(`dcto`.`valor`,0) AS `valor_dcto`
                    , IFNULL(CAST(`inc`.`dias` AS UNSIGNED),0) AS `dias_incapacidad`
                    , CAST(IFNULL(`lmp`.`dias_liqs`,0) + IFNULL(`luto`.`dias`,0) + IFNULL(`lcnr`.`dias`,0) AS UNSIGNED) AS `dias_licencias`
                    , 0 AS `dias_otros`
                    , IFNULL(CAST(`liq`.`dias_liq` AS UNSIGNED),0) AS `dias_lab`
                    , IFNULL(`liq`.`val_liq_dias`,0) AS `valor_laborado`
                    , IFNULL(`liq`.`val_liq_auxt`,0) AS `aux_tran`
                    , IFNULL(`liq`.`aux_alim`,0) AS `aux_alim`
                    , IFNULL(`liq`.`g_representa`,0) AS `g_representa`
                    , IFNULL(`liq`.`horas_ext` ,0) AS `horas_ext`
                    , IFNULL(`emb`.`valor`,0) AS `valor_embargo`
                    , IFNULL(`inc`.`valor`,0) AS `valor_incap`
                    , IFNULL(`inc`.`pago_empresa`,0) AS `pago_empresa`
                    , IFNULL(`lib`.`valor`,0) AS `valor_libranza`
                    , IFNULL(`luto`.`valor`,0) AS `valor_luto`
                    , IFNULL(`lmp`.`val_liq`,0) AS  `valor_mp`
                    , IFNULL(`pris`.`val_liq_ps`,0) AS `valor_ps`
                    , IFNULL(`pris`.`dias_ps`,0) AS `dias_ps`
                    , IFNULL(`pris`.`corte_ps`, CURDATE()) AS `corte_ps`
                    , IFNULL(`prin`.`val_liq_pv`,0) AS `valor_pv`
                    , IFNULL(`prin`.`dias_pn`,0) AS `dias_pn`
                    , IFNULL(`prin`.`corte_pn`, CURDATE()) AS `corte_pn`
                    , IFNULL(`segs`.`aporte_salud_emp`,0) AS `valor_salud`
                    , IFNULL(`segs`.`aporte_pension_emp`,0) AS `valor_pension`
                    , IFNULL(`segs`.`aporte_solidaridad_pensional`,0) AS `val_psolidaria`
                    , IFNULL(`segs`.`aporte_salud_empresa`,0) AS `val_salud_empresa`
                    , IFNULL(`segs`.`aporte_pension_empresa`,0) AS `val_pension_empresa`
                    , IFNULL(`segs`.`aporte_rieslab`,0) AS `val_rieslab`
                    , IFNULL(`sind`.`val_aporte`,0) AS `valor_sind`
                    , IFNULL(`vac`.`val_liq`,0) AS `valor_vacacion`
                    , IFNULL(`vac`.`val_prima_vac`,0) AS `val_prima_vac`
                    , IFNULL(`vac`.`val_bon_recrea`,0) AS `val_bon_recrea`
                    , IFNULL(CAST(`vac`.`dias_habiles` AS UNSIGNED),0) AS `dias_vacaciones`
                    , IFNULL(`vac`.`corte_vac`, CURDATE()) AS `corte_vac`
                    , IFNULL(CAST(`vac`.`dias_inactivo` AS UNSIGNED),0) AS `dias_inactivo`
                    , IFNULL(`rfte`.`val_ret`,0) AS `val_retencion`
                    , IFNULL(`rfte`.`base_ret`,0) AS `base_retencion`
                    , IFNULL(`paraf`.`val_sena`,0) AS `val_sena`
                    , IFNULL(`paraf`.`val_icbf`,0) AS `val_icbf`
                    , IFNULL(`paraf`.`val_comfam`,0) AS `val_comfam`
                    , `nom`.`mes` AS `mes`
                    , `nom`.`tipo` AS `codigo_nomina`
                    , `nom`.`estado` AS `estado_nomina`
                    , IFNULL(`ccosto`.`id_ccosto`,21) AS `id_ccosto`
                FROM `nom_empleado` `e`
                    INNER JOIN `sal` ON (`sal`.`id_empleado` = `e`.`id_empleado`)
                    INNER JOIN `tb_sedes` `ts` ON (`ts`.`id_sede` = `e`.`sede_emp`)
                    LEFT JOIN `cargo` ON (`cargo`.`id_empleado` = `e`.`id_empleado`)
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
                    LEFT JOIN `paraf` ON (`paraf`.`id_empleado` = `e`.`id_empleado`)
                    LEFT JOIN `indem` ON (`indem`.`id_empleado` = `e`.`id_empleado`)
                    LEFT JOIN `ccosto` ON (`ccosto`.`id_empleado` = `e`.`id_empleado`)
                    JOIN `nom`
                WHERE (1 = 1 $where)
                ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id_nomina', $array['id_nomina'], PDO::PARAM_INT);
        $stmt->execute();
        $datos = isset($array['id_empleado']) ? $stmt->fetch(PDO::FETCH_ASSOC) : $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);

        return !empty($datos) ? $datos : [];
    }

    public function getAporteSocial($id_nomina)
    {
        $sql = "SELECT
                    IF(`nca`.`tipo_cargo` = 1,'admin','oper') AS `cargo`,
                    CASE `tipo_valor`.`tipo`
                    WHEN 1 THEN 'eps'
                        WHEN 2 THEN 'afp'
                        WHEN 3 THEN 'arl'
                        WHEN 4 THEN 'sena'
                        WHEN 5 THEN 'icbf'
                        WHEN 6 THEN 'caja'
                        ELSE 'otro'
                    END AS `tipo`,
                    CASE `tipo_valor`.`tipo`
                        WHEN 1 THEN `nlse`.`id_eps`
                        WHEN 2 THEN `nlse`.`id_afp`
                        WHEN 3 THEN `nlse`.`id_arl`
                        ELSE 1
                    END AS `id`,
                    SUM(CASE `tipo_valor`.`tipo`
                            WHEN 1 THEN `nlse`.`aporte_salud_empresa`
                            WHEN 2 THEN `nlse`.`aporte_pension_empresa`
                            WHEN 3 THEN `nlse`.`aporte_rieslab`
                            WHEN 4 THEN `nlp`.`val_sena`
                            WHEN 5 THEN `nlp`.`val_icbf`
                            WHEN 6 THEN `nlp`.`val_comfam`
                        END) AS `valor`,
                    SUM(CASE `tipo_valor`.`tipo`
                            WHEN 1 THEN `nlse`.`aporte_salud_emp`
                            WHEN 2 THEN `nlse`.`aporte_pension_emp` + IFNULL(`nlse`.`aporte_solidaridad_pensional`, 0)
                            ELSE 0
                        END) AS `valor_emp`
                FROM 
                    `nom_liq_salario` AS `nls`
                    INNER JOIN `nom_contratos_empleados` AS `nce`
                        ON `nce`.`id_contrato_emp` = `nls`.`id_contrato`
                    INNER JOIN `nom_cargo_empleado` AS `nca`
                        ON `nca`.`id_cargo` = `nce`.`id_cargo`
                    LEFT JOIN `nom_liq_segsocial_empdo` AS `nlse`
                        ON `nlse`.`id_empleado` = `nls`.`id_empleado` AND `nlse`.`id_nomina` = `nls`.`id_nomina` AND `nlse`.`estado` = 1
                    LEFT JOIN `nom_liq_parafiscales` AS `nlp`
                        ON `nlp`.`id_empleado` = `nls`.`id_empleado` AND `nlp`.`id_nomina` = `nls`.`id_nomina` AND `nlp`.`estado` = 1
                    CROSS JOIN (
                        SELECT 1 AS `tipo` UNION ALL SELECT 2 UNION ALL SELECT 3 
                        UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6
                    ) AS `tipo_valor`
                WHERE 
                    `nls`.`id_nomina` = :id_nomina AND `nls`.`estado` = 1
                    AND ((`tipo_valor`.`tipo` IN (1, 2, 3) AND `nlse`.`id_empleado` IS NOT NULL)
                        OR (`tipo_valor`.`tipo` IN (4, 5, 6) AND `nlp`.`id_empleado` IS NOT NULL))
                GROUP BY 
                    `tipo_valor`.`tipo`, `nca`.`tipo_cargo`,
                    CASE `tipo_valor`.`tipo`
                        WHEN 1 THEN `nlse`.`id_eps`
                        WHEN 2 THEN `nlse`.`id_afp`
                        WHEN 3 THEN `nlse`.`id_arl`
                        ELSE 1
                    END
                ORDER BY `nca`.`tipo_cargo` ASC,`tipo_valor`.`tipo` ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id_nomina', $id_nomina, PDO::PARAM_INT);
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
            $where .= " AND (`e`.`no_documento` LIKE '%{$array['search']}%' OR CONCAT_WS (' ',`e`.`nombre1`,`nombre2`,`apellido1`,`apellido2`) LIKE '%{$array['search']}%' OR `cargo`.`descripcion_carg` LIKE '%{$array['search']}%')";
        }


        $sql = "WITH
                    `sal` AS 
                        (SELECT `id_empleado`,`sal_base`,`id_contrato`,`val_liq` FROM `nom_liq_salario` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
                    `cargo` AS 
                        (SELECT
                            `nom_liq_salario`.`id_empleado`
                            , `nom_cargo_empleado`.`descripcion_carg`
                        FROM
                            `nom_liq_salario`
                            INNER JOIN `nom_contratos_empleados` 
                            ON (`nom_liq_salario`.`id_contrato` = `nom_contratos_empleados`.`id_contrato_emp`)
                            LEFT JOIN `nom_cargo_empleado` 
                            ON (`nom_contratos_empleados`.`id_cargo` = `nom_cargo_empleado`.`id_cargo`)
                        WHERE (`nom_liq_salario`.`id_nomina` = :id_nomina AND `nom_liq_salario`.`estado` = 1))
                    SELECT 
                        COUNT(*) AS `total`
                    FROM `nom_empleado` `e`
                        INNER JOIN `cargo` ON (`cargo`.`id_empleado` = `e`.`id_empleado`)
                        INNER JOIN `sal` ON (`sal`.`id_empleado` = `e`.`id_empleado`)
                    WHERE (1 = 1 $where)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id_nomina', $array['id_nomina'], PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
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
                        (SELECT `id_empleado`,`sal_base`,`id_contrato`,`val_liq` FROM `nom_liq_salario` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
                    `cargo` AS 
                        (SELECT
                            `nom_liq_salario`.`id_empleado`
                            , `nom_cargo_empleado`.`descripcion_carg`
                        FROM
                            `nom_liq_salario`
                            INNER JOIN `nom_contratos_empleados` 
                            ON (`nom_liq_salario`.`id_contrato` = `nom_contratos_empleados`.`id_contrato_emp`)
                            LEFT JOIN `nom_cargo_empleado` 
                            ON (`nom_contratos_empleados`.`id_cargo` = `nom_cargo_empleado`.`id_cargo`)
                        WHERE (`nom_liq_salario`.`id_nomina` = :id_nomina AND `nom_liq_salario`.`estado` = 1))
                    SELECT 
                        COUNT(*) AS `total`
                    FROM `nom_empleado` `e`
                        INNER JOIN `cargo` ON (`cargo`.`id_empleado` = `e`.`id_empleado`)
                        INNER JOIN `sal` ON (`sal`.`id_empleado` = `e`.`id_empleado`)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id_nomina', $array['id_nomina'], PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return !empty($registro) ? $registro['total'] : 0;
    }

    public function getFormulario($id_empleado, $id_nomina, $item)
    {
        $datos = $this->getRegistrosDT(1, -1, ['id_empleado' => $id_empleado, 'id_nomina' => $id_nomina], 1, 'ASC');
        $param = (new Valores_Liquidacion($this->conexion))->getRegistro($id_nomina, $id_empleado);
        $estado = $datos['estado_nomina'];
        $uno = $item == 1 ? 'show' : '';
        $dos = $item == 2 ? 'show' : '';
        $tres = $item == 3 ? 'show' : '';
        $cuatro = $item == 4 ? 'show' : '';
        $boton1 = $estado == 1 ? '<button type="button" class="btn btn-primary btn-sm" id="btnGuardarSalarios">Guardar y Reliquidar</button>' : '';
        $boton2 = $estado == 1 ? '<button type="button" class="btn btn-primary btn-sm" id="btnGuardarPretaciones">Guardar y Reliquidar</button>' : '';
        $boton3 = $estado == 1 ? '<button type="button" class="btn btn-primary btn-sm" id="btnGuardarParafiscales">Guardar</button>' : '';
        $boton4 = $estado == 1 ? '<button type="button" class="btn btn-primary btn-sm" id="btnGuardarDctos">Guardar</button>' : '';
        $dataid = base64_encode($id_empleado . '|' . $id_nomina);
        $html =
            <<<HTML
                <div>
                    <div class="shadow text-center rounded">
                        <div class="rounded-top py-2" style="background-color: #16a085 !important;">
                            <h5 style="color: white;" class="mb-0"><b>EMPLEADO:</b> {$datos['nombre']}</h5>
                        </div>
                        <div class="p-3">
                            <input type="hidden" id="id_empleado" name="id_empleado" value="{$id_empleado}">
                            <div class="accordion" id="acordeonDetallesNom">
                                <div class="text-end mb-2">
                                    <button type="button" class="btn btn-outline-warning btn-sm" id="btnImprimir" title="Imprimir desprendible de nómina" data-id="{$dataid}"><i class="fas fa-print"></i></button>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button sombra  bg-head-button border" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                            Sueldos y Salarios.
                                        </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse {$uno}" data-bs-parent="#acordeonDetallesNom">
                                        <div class="accordion-body">
                                            <form id="formSalariosLiq">
                                                <table class="table table-striped table-bordered table-sm table-hover align-middle shadow w-100">
                                                    <thead>
                                                        <tr>
                                                            <th class="bg-sofia">CONCEPTO</th>
                                                            <th class="bg-sofia">VALOR</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td class="ps-4 text-start">Compensatorio</td>
                                                            <td class="text-end pe-4">{$datos['val_compensa']}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">Laborado</td>
                                                            <td class="text-end"><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="valor_laborado" value="{$datos['valor_laborado']}"></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">Auxilio de Transporte</td>
                                                            <td class="text-end"><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="aux_tran" value="{$datos['aux_tran']}"></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">Auxilio de Alimentación</td>
                                                            <td class="text-end"><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="alimentacion" value="{$datos['aux_alim']}"></td>
                                                        </tr>
                                                        
                                                        <tr>
                                                            <td class="ps-4 text-start">Gastos de Representación</td>
                                                            <td class="text-end"><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="g_representa" value="{$datos['g_representa']}"></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">Horas extras</td>
                                                            <td class="text-end pe-4">{$datos['horas_ext']}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">Incapacidad</td>
                                                            <td class="text-end pe-4">{$datos['valor_incap']}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">Licencia por luto</td>
                                                            <td class="text-end pe-4">{$datos['valor_luto']}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">Licencia materna / paterna</td>
                                                            <td class="text-end"><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="valor_mp" value="{$datos['valor_mp']}"></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </form>
                                            <div class="mt-3 text-center">
                                                {$boton1}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button sombra bg-head-button border collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                            Prestaciones Sociales.
                                        </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse {$dos}" data-bs-parent="#acordeonDetallesNom">
                                        <div class="accordion-body">
                                            <form id="formPrestacionesLiq">
                                                <input type="hidden" name="id_contrato" value="{$datos['id_contrato']}">
                                                <input type="hidden" name="metodo_pago" value="{$datos['metodo_pago']}">
                                                <input type="hidden" name="mes" value="{$datos['mes']}">
                                                <input type="hidden" name="dias_lab" value="{$datos['dias_lab']}">

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <table class="table table-striped table-bordered table-sm table-hover align-middle shadow w-100">
                                                            <thead>
                                                                <tr>
                                                                    <th class="bg-sofia">CONCEPTO</th>
                                                                    <th class="bg-sofia">VALOR</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr>
                                                                    <th class="bg-sofia" colspan="2">Valores de liquidación</th>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Salario básico mensual</td>
                                                                    <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="salario" value="{$param['salario']}"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Salario Mínimo Mensual Legal Vigente <strong>(SMMLV)</strong></td>
                                                                    <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="smmlv" value="{$param['smmlv']}"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Auxilio de Transporte</td>
                                                                    <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="aux_trans" value="{$param['aux_trans']}"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Auxilio de Alimentación</td>
                                                                    <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="aux_alim" value="{$param['aux_alim']}"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Unidad de Valor Tributario <strong>(UVT)</strong></td>
                                                                    <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="uvt" value="{$param['uvt']}"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Base Bonificación Servicios Prestados</td>
                                                                    <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="base_bsp" value="{$param['base_bsp']}"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Base Auxilio de Alimentación</td>
                                                                    <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="base_alim" value="{$param['base_alim']}"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Gastos de Representación</td>
                                                                    <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="grep" value="{$param['grep']}"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Promedio de horas extras</td>
                                                                    <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="prom_horas" value="{$param['prom_horas']}"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Bonificación Servicios Prestados <strong>(Anterior)</strong></td>
                                                                    <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="bsp_ant" value="{$param['bsp_ant']}"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Prima de Servicios <strong>(Anterior)</strong></td>
                                                                    <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="pri_ser_ant" value="{$param['pri_ser_ant']}"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Prima de Vacaciones <strong>(Anterior)</strong></td>
                                                                    <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="pri_vac_ant" value="{$param['pri_vac_ant']}"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Prima de Navidad <strong>(Anterior)</strong></td>
                                                                    <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="pri_nav_ant" value="{$param['pri_nav_ant']}"></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <table class="table table-striped table-bordered table-sm table-hover align-middle shadow w-100">
                                                            <thead>
                                                                <tr>
                                                                    <th class="bg-sofia">CONCEPTO</th>
                                                                    <th class="bg-sofia">VALOR</th>
                                                                    <th class="bg-sofia">CORTE</th>
                                                                    <th class="bg-sofia">DIAS</th>
                                                                </tr>
                                                                <tr>
                                                                    <th class="bg-sofia" colspan="4">Valores Liquidados</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Bonificación Servicios Prestados <strong>(Actual)</strong></td>
                                                                    <td><input type="number" min="0" class="no-focus text-end border-0 rounded pe-1 w-100" name="valor_bsp" value="{$datos['val_bsp']}"></td>
                                                                    <td title="Fecha de corte"><input type="date" class="no-focus text-end border-0 rounded pe-1 w-100" name="corte_bsp" value="{$datos['corte_bsp']}"></td>
                                                                    <td>-</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Prima de Servicios <strong>(Actual)</strong></td>
                                                                    <td><input type="number" min="0" class="no-focus text-end border-0 rounded pe-1 w-100" name="valor_ps" value="{$datos['valor_ps']}"></td>
                                                                    <td title="Fecha de corte"><input type="date" class="no-focus text-end border-0 rounded pe-1 w-100" name="corte_ps" value="{$datos['corte_ps']}"></td>
                                                                    <td title="Dias liquidados"><input type="number" min="0" class="no-focus text-end border-0 rounded pe-1 w-100" name="dias_ps" value="{$datos['dias_ps']}"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Prima de Navidad <strong>(Actual)</strong></td>
                                                                    <td><input type="number" min="0" class="no-focus text-end border-0 rounded pe-1 w-100" name="valor_pv" value="{$datos['valor_pv']}"></td>
                                                                    <td title="Fecha de corte"><input type="date" class="no-focus text-end border-0 rounded pe-1 w-100" name="corte_pn" value="{$datos['corte_pn']}"></td>
                                                                    <td title="Dias liquidados"><input type="number" min="0" class="no-focus text-end border-0 rounded pe-1 w-100" name="dias_pn" value="{$datos['dias_pn']}"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Cesantías <strong>(Actual)</strong></td>
                                                                    <td><input type="number" min="0" class="no-focus text-end border-0 rounded pe-1 w-100" name="val_cesantias" value="{$datos['val_cesantias']}"></td>
                                                                    <td title="Fecha de corte"><input type="date" class="no-focus text-end border-0 rounded pe-1 w-100" name="corte_ces" value="{$datos['corte_ces']}"></td>
                                                                    <td title="Dias liquidados"><input type="number" min="0" class="no-focus text-end border-0 rounded pe-1 w-100" name="dias_ces" value="{$datos['dias_ces']}"></td>
                                                                </tr>
                                                                 <tr>
                                                                    <td class="ps-4 text-start">Interes Cesantías <strong>(Actual)</strong></td>
                                                                    <td><input type="number" min="0" class="no-focus text-end border-0 rounded pe-1 w-100" name="val_icesantias" value="{$datos['val_icesantias']}"></td>
                                                                    <td></td>
                                                                    <td></td>
                                                                </tr>

                                                            </tbody>
                                                        </table>
                                                        <table class="table table-striped table-bordered table-sm table-hover align-middle shadow w-100">
                                                            <thead>
                                                                <tr>
                                                                    <th class="bg-sofia" colspan="2">VACACIONES</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Vacaciones <strong>(Actual)</strong></td>
                                                                    <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="valor_vacacion" value="{$datos['valor_vacacion']}"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Prima de vacaciones <strong>(Actual)</strong></td>
                                                                    <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="val_prima_vac" value="{$datos['val_prima_vac']}"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-4 text-start">Bonificación de recreación <strong>(Actual)</strong></td>
                                                                    <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="val_bon_recrea" value="{$datos['val_bon_recrea']}"></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </form>
                                            <div class="mt-3 text-center">
                                                {$boton2}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button sombra bg-head-button border collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                            Seguridad Social y Parafiscales.
                                        </button>
                                    </h2>
                                    <div id="collapseThree" class="accordion-collapse collapse {$tres}" data-bs-parent="#acordeonDetallesNom">
                                        <div class="accordion-body">
                                            <form id="formParafiscalesLiq">
                                                <table class="table table-striped table-bordered table-sm table-hover align-middle shadow w-100">
                                                    <thead>
                                                        <tr>
                                                            <th class="bg-sofia">CONCEPTO</th>
                                                            <th class="bg-sofia">VALOR</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td class="ps-4 text-start">Salud empleado <strong>4%</strong></td>
                                                            <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="valor_salud" value="{$datos['valor_salud']}"></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">Salud patronal <strong>8.5%</strong></td>
                                                            <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="val_salud_empresa" value="{$datos['val_salud_empresa']}"></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">Pensión empleado <strong>4%</strong></td>
                                                            <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="valor_pension" value="{$datos['valor_pension']}"></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">Pensión patronal <strong>12%</strong></td>
                                                            <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="val_pension_empresa" value="{$datos['val_pension_empresa']}"></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">Pensión solidaria</td>
                                                            <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="val_pension_solidaria" value="{$datos['val_psolidaria']}"></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">Riesgo Laboral</td>
                                                            <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="val_riesgo_laboral" value="{$datos['val_rieslab']}"></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">SENA <strong>2%</strong></td>
                                                            <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="val_sena" value="{$datos['val_sena']}"></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">ICBF <strong>3%</strong></td>
                                                            <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="val_icbf" value="{$datos['val_icbf']}"></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">Caja Compensación <strong>4%</strong></td>
                                                            <td><input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" name="val_caja_compensacion" value="{$datos['val_comfam']}"></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </form>
                                            <div class="mt-3 text-center">
                                                {$boton3}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button sombra bg-head-button border collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                            Deducciones.
                                        </button>
                                    </h2>
                                    <div id="collapseFour" class="accordion-collapse collapse {$cuatro}" data-bs-parent="#acordeonDetallesNom">
                                        <div class="accordion-body">
                                            <form id="formDctosLiq">
                                                <table id="tableDctosLiq" class="table table-striped table-bordered table-sm table-hover align-middle shadow w-100">
                                                    <thead>
                                                        <tr>
                                                            <th class="bg-sofia">CONCEPTO</th>
                                                            <th class="bg-sofia">VALOR</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td class="ps-4 text-start">Libranzas</td>
                                                            <td class="pe-4 text-end">{$datos['valor_libranza']}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">Embargos</td>
                                                            <td class="pe-4 text-end">{$datos['valor_embargo']}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">Sindicato</td>
                                                            <td class="pe-4 text-end">{$datos['valor_sind']}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">Otros Descuentos</td>
                                                            <td class="pe-4 text-end">{$datos['valor_dcto']}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">Base de retención en la fuente</td>
                                                            <td>
                                                                <input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" id="base_retencion" name="base_retencion" value="{$datos['base_retencion']}">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4 text-start">Valor retención</td>
                                                            <td>
                                                                <input type="number" class="no-focus text-end border-0 rounded pe-1 w-100" id="valor_retencion" name="valor_retencion" value="{$datos['val_retencion']}">
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </form>
                                            <div class="mt-3 text-center">
                                                {$boton4}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-end pb-3 px-3">
                            <a type="button" class="btn btn-secondary  btn-sm" data-bs-dismiss="modal">Cancelar</a>
                        </div>
                    </div>
                </div>
            HTML;
        return $html;
    }
}
