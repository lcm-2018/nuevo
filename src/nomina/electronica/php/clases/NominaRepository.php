<?php

namespace Src\Nomina\Electronica\Php\Clases;

use PDO;
use Exception;

/**
 * Repository para manejar datos de nómina electrónica
 * Encapsula todas las operaciones de base de datos necesarias
 * para el envío de nómina electrónica a Taxxa.
 *
 * Reutiliza la CTE de Detalles.php para obtener todos los valores
 * de cada empleado en una sola consulta eficiente.
 */
class NominaRepository
{
    protected $conexion;

    /**
     * Constructor
     * @param PDO $conexion Conexión a la base de datos
     */
    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    /**
     * Obtiene los datos básicos de una nómina (mes, fecha de liquidación)
     * @param int $idNomina ID de la nómina
     * @return array Datos de la nómina
     * @throws Exception
     */
    public function getNominaData(int $idNomina): array
    {
        try {
            $sql = "SELECT `id_nomina`, `mes`, `fec_reg`
                    FROM `nom_nominas`
                    WHERE `id_nomina` = :id LIMIT 1";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([':id' => $idNomina]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                throw new Exception("No se encontró la nómina con ID: {$idNomina}");
            }

            return $data;
        } catch (\PDOException $e) {
            throw new Exception("Error al obtener datos de nómina: " . $e->getMessage());
        }
    }

    /**
     * Obtiene y actualiza el Nonce para facturación electrónica
     * (mismo mecanismo que el módulo de documento soporte)
     * @return array [valor, id]
     * @throws Exception
     */
    public function getAndUpdateNonce(): array
    {
        try {
            $sql = "SELECT `id_valxvig`, `id_concepto`, `valor`, `concepto`
                    FROM `nom_valxvigencia`
                    INNER JOIN `tb_vigencias`
                        ON (`nom_valxvigencia`.`id_vigencia` = `tb_vigencias`.`id_vigencia`)
                    INNER JOIN `nom_conceptosxvigencia`
                        ON (`nom_valxvigencia`.`id_concepto` = `nom_conceptosxvigencia`.`id_concp`)
                    WHERE `id_concepto` = '4' LIMIT 1";

            $stmt = $this->conexion->query($sql);
            $concec = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$concec) {
                throw new Exception("No se encontró configuración de Nonce");
            }

            $iNonce = intval($concec['valor']);
            $idiNonce = $concec['id_valxvig'];

            // Incrementar el nonce para la siguiente petición
            $sqlUp = "UPDATE `nom_valxvigencia` SET `valor` = :valor WHERE `id_valxvig` = :id";
            $stmt = $this->conexion->prepare($sqlUp);
            $stmt->execute([':valor' => $iNonce + 1, ':id' => $idiNonce]);

            return ['valor' => $iNonce, 'id' => $idiNonce];
        } catch (\PDOException $e) {
            throw new Exception("Error al obtener Nonce: " . $e->getMessage());
        }
    }

    /**
     * Obtiene los datos de la empresa emisora (igual que DocumentRepository)
     * @return array Datos de la empresa con credenciales Taxxa
     * @throws Exception
     */
    public function getEmpresaData(): array
    {
        try {
            $sql = "SELECT
                        `tb_datos_ips`.`id_ips`
                        , `tb_datos_ips`.`nit_ips` AS `nit`
                        , `tb_datos_ips`.`email_ips` AS `correo`
                        , `tb_datos_ips`.`telefono_ips` AS `telefono`
                        , `tb_datos_ips`.`razon_social_fe` AS `nombre`
                        , 'COLOMBIA' AS `nom_pais`
                        , 'CO' AS `codigo_pais`
                        , `tb_departamentos`.`codigo_departamento`
                        , `tb_departamentos`.`nom_departamento`
                        , `tb_municipios`.`codigo_municipio`
                        , `tb_municipios`.`nom_municipio`
                        , `tb_municipios`.`cod_postal`
                        , `tb_datos_ips`.`direccion_ips` AS `direccion`
                        , `tb_datos_ips`.`url_taxxa` AS `endpoint`
                        , `tb_datos_ips`.`sEmail` AS `user_prov`
                        , `tb_datos_ips`.`sPass` AS `pass_prov`
                    FROM `tb_datos_ips`
                        INNER JOIN `tb_municipios`
                            ON (`tb_datos_ips`.`idmcpio` = `tb_municipios`.`id_municipio`)
                        INNER JOIN `tb_departamentos`
                            ON (`tb_municipios`.`id_departamento` = `tb_departamentos`.`id_departamento`)";

            $stmt = $this->conexion->query($sql);
            $empresa = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$empresa) {
                throw new Exception("No se encontró información de la empresa");
            }

            return $empresa;
        } catch (\PDOException $e) {
            throw new Exception("Error al obtener datos de empresa: " . $e->getMessage());
        }
    }

    /**
     * Obtiene todos los valores liquidados de cada empleado en la nómina
     * usando la misma CTE optimizada de Detalles.php.
     *
     * Retorna por cada empleado:
     *   - Datos personales: id_empleado, no_documento, nombre, correo
     *   - Datos laborales: codigo_ne, tip_doc, tip_emp, subt_emp, tip_contrato,
     *                      fech_inicio, fec_retiro, salario_integral, alto_riesgo_pension
     *   - Devengados: valor_laborado, aux_tran, aux_alim, g_representa, horas_ext,
     *                 valor_incap, valor_luto, valor_mp, valor_ps, valor_pv,
     *                 valor_vacacion, val_prima_vac, val_bon_recrea, valor_viatico,
     *                 valor_otros, val_compensa, val_bsp, val_cesantias, val_icesantias
     *   - Deducciones: valor_salud, valor_pension, val_psolidaria, valor_embargo,
     *                  valor_libranza, valor_sind, val_retencion
     *   - Días: dias_lab, dias_ps, dias_pn, dias_ces, dias_vacaciones, dias_incapacidad
     *   - Seguridad social: val_salud_empresa, val_pension_empresa, val_rieslab
     *   - Contrato: sal_base, id_contrato, metodo_pago, mes, codigo_ne
     *   - Bancos: (se consultan en getInfoBancaria)
     *
     * @param int $idNomina ID de la nómina
     * @return array Lista de empleados con todos sus valores
     * @throws Exception
     */
    public function getDetallesEmpleados(int $idNomina): array
    {
        try {
            // CTE optimizada que consolida todos los conceptos en una sola query
            // Basada en Src\Nomina\Liquidado\Php\Clases\Detalles::getRegistrosDT()
            $sql = "WITH
                        `bsp` AS
                            (SELECT `id_empleado`,`val_bsp`, `fec_corte` AS `corte_bsp`
                             FROM `nom_liq_bsp` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
                        `ces` AS
                            (SELECT `id_empleado`,`val_cesantias`,`val_icesantias`, `corte` AS `corte_ces`, `cant_dias` AS `dias_ces`
                             FROM `nom_liq_cesantias` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
                        `com` AS
                            (SELECT `id_empleado`,`val_compensa`
                             FROM `nom_liq_compesatorio` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
                        `odev` AS
                            (SELECT `nod`.`id_empleado`, SUM(`nld`.`valor`) AS `valor`
                             FROM `nom_liq_devengado` AS `nld`
                             INNER JOIN `nom_otros_devengados` AS `nod` ON (`nld`.`id_devengado` = `nod`.`id_devengado`)
                             WHERE `nld`.`estado` = 1 AND `nld`.`id_nomina` = :id_nomina
                             GROUP BY `nod`.`id_empleado`),
                        `dcto` AS
                            (SELECT `nom_otros_descuentos`.`id_empleado`, SUM(`nom_liq_descuento`.`valor`) AS `valor`
                             FROM `nom_liq_descuento`
                             INNER JOIN `nom_otros_descuentos` ON (`nom_liq_descuento`.`id_dcto` = `nom_otros_descuentos`.`id_dcto`)
                             WHERE `nom_liq_descuento`.`estado` = 1 AND `nom_liq_descuento`.`id_nomina` = :id_nomina
                             GROUP BY `nom_otros_descuentos`.`id_empleado`),
                        `liq` AS
                            (SELECT `id_empleado`,`dias_liq`,`val_liq_dias`,`val_liq_auxt`,`aux_alim`,`g_representa`,`horas_ext`
                             FROM `nom_liq_dlab_auxt` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
                        `emb` AS
                            (SELECT `nom_embargos`.`id_empleado`, SUM(`nom_liq_embargo`.`val_mes_embargo`) AS `valor`
                             FROM `nom_liq_embargo`
                             INNER JOIN `nom_embargos` ON (`nom_liq_embargo`.`id_embargo` = `nom_embargos`.`id_embargo`)
                             WHERE `nom_liq_embargo`.`id_nomina` = :id_nomina AND `nom_liq_embargo`.`estado` = 1
                             GROUP BY `nom_embargos`.`id_empleado`),
                        `inc` AS
                            (SELECT `nom_incapacidad`.`id_empleado`,
                                    SUM(`nom_liq_incap`.`pago_empresa`) AS `pago_empresa`,
                                    SUM(`nom_liq_incap`.`pago_empresa` + `nom_liq_incap`.`pago_eps` + `nom_liq_incap`.`pago_arl`) AS `valor`,
                                    SUM(`nom_liq_incap`.`dias_liq`) AS `dias`,
                                    MIN(`nom_liq_incap`.`fec_inicio`) AS `fec_inicio`,
                                    MAX(`nom_liq_incap`.`fec_fin`) AS `fec_fin`,
                                    MIN(`nom_incapacidad`.`id_tipo`) AS `id_tipo`
                             FROM `nom_liq_incap`
                             INNER JOIN `nom_incapacidad` ON (`nom_liq_incap`.`id_incapacidad` = `nom_incapacidad`.`id_incapacidad`)
                             WHERE `nom_liq_incap`.`estado` = 1 AND `nom_liq_incap`.`id_nomina` = :id_nomina
                             GROUP BY `nom_incapacidad`.`id_empleado`),
                        `lib` AS
                            (SELECT `nom_libranzas`.`id_empleado`, SUM(`nom_liq_libranza`.`val_mes_lib`) AS `valor`,
                                    MAX(`nom_libranzas`.`descripcion_lib`) AS `descripcion_lib`
                             FROM `nom_liq_libranza`
                             INNER JOIN `nom_libranzas` ON (`nom_liq_libranza`.`id_libranza` = `nom_libranzas`.`id_libranza`)
                             WHERE `nom_liq_libranza`.`id_nomina` = :id_nomina AND `nom_liq_libranza`.`estado` = 1
                             GROUP BY `nom_libranzas`.`id_empleado`),
                        `viat` AS
                            (SELECT `nom_viaticos`.`id_empleado`, SUM(`nom_liq_viaticos`.`valor`) AS `valor`
                             FROM `nom_liq_viaticos`
                             INNER JOIN `nom_viaticos` ON (`nom_liq_viaticos`.`id_viatico` = `nom_viaticos`.`id_viatico`)
                             WHERE `nom_liq_viaticos`.`id_nomina` = :id_nomina AND `nom_liq_viaticos`.`estado` = 1
                             GROUP BY `nom_viaticos`.`id_empleado`),
                        `rfte` AS
                            (SELECT `id_empleado`,`val_ret`, `base` AS `base_ret`
                             FROM `nom_retencion_fte` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
                        `luto` AS
                            (SELECT `nom_licencia_luto`.`id_empleado`,
                                    SUM(`nom_liq_licluto`.`val_liq`) AS `valor`,
                                    SUM(`nom_liq_licluto`.`dias_licluto`) AS `dias`
                             FROM `nom_liq_licluto`
                             INNER JOIN `nom_licencia_luto` ON (`nom_liq_licluto`.`id_licluto` = `nom_licencia_luto`.`id_licluto`)
                             WHERE `nom_liq_licluto`.`estado` = 1 AND `nom_liq_licluto`.`id_nomina` = :id_nomina
                             GROUP BY `nom_licencia_luto`.`id_empleado`),
                        `lmp` AS
                            (SELECT `nom_licenciasmp`.`id_empleado`,
                                    SUM(`nom_liq_licmp`.`val_liq`) AS `val_liq`,
                                    SUM(`nom_liq_licmp`.`dias_liqs`) AS `dias_liqs`,
                                    MIN(`nom_liq_licmp`.`fec_inicio`) AS `fec_inicio`,
                                    MAX(`nom_liq_licmp`.`fec_fin`) AS `fec_fin`
                             FROM `nom_liq_licmp`
                             INNER JOIN `nom_licenciasmp` ON (`nom_liq_licmp`.`id_licmp` = `nom_licenciasmp`.`id_licmp`)
                             WHERE `nom_liq_licmp`.`estado` = 1 AND `nom_liq_licmp`.`id_nomina` = :id_nomina
                             GROUP BY `nom_licenciasmp`.`id_empleado`),
                        `pris` AS
                            (SELECT `id_empleado`,`val_liq_ps`, `cant_dias` AS `dias_ps`, `corte` AS `corte_ps`
                             FROM `nom_liq_prima` WHERE `estado` = 1 AND `id_nomina` = :id_nomina),
                        `prin` AS
                            (SELECT `id_empleado`,`val_liq_pv`, `cant_dias` AS `dias_pn`, `corte` AS `corte_pn`
                             FROM `nom_liq_prima_nav` WHERE `estado` = 1 AND `id_nomina` = :id_nomina),
                        `segs` AS
                            (SELECT `nlsse`.`id_empleado`,
                                    `nlsse`.`aporte_salud_emp`,
                                    `nlsse`.`aporte_pension_emp`,
                                    `nlsse`.`aporte_solidaridad_pensional`,
                                    `nlsse`.`aporte_salud_empresa`,
                                    `nlsse`.`aporte_pension_empresa`,
                                    `nlsse`.`aporte_rieslab`,
                                    `nlsse`.`porcentaje_ps`
                             FROM `nom_liq_segsocial_empdo` AS `nlsse`
                             WHERE `nlsse`.`id_nomina` = :id_nomina AND `nlsse`.`estado` = 1),
                        `sind` AS
                            (SELECT `nom_cuota_sindical`.`id_empleado`,
                                    SUM(`nom_liq_sindicato_aportes`.`val_aporte`) AS `val_aporte`
                             FROM `nom_liq_sindicato_aportes`
                             INNER JOIN `nom_cuota_sindical` ON (`nom_liq_sindicato_aportes`.`id_cuota_sindical` = `nom_cuota_sindical`.`id_cuota_sindical`)
                             WHERE `nom_liq_sindicato_aportes`.`id_nomina` = :id_nomina AND `nom_liq_sindicato_aportes`.`estado` = 1
                             GROUP BY `nom_cuota_sindical`.`id_empleado`),
                        `vac` AS
                            (SELECT `nom_vacaciones`.`id_empleado`,
                                    SUM(`nom_liq_vac`.`val_liq`) AS `val_liq`,
                                    SUM(`nom_liq_vac`.`val_prima_vac`) AS `val_prima_vac`,
                                    SUM(`nom_liq_vac`.`val_bon_recrea`) AS `val_bon_recrea`,
                                    SUM(`nom_vacaciones`.`dias_habiles`) AS `dias_habiles`,
                                    MAX(`nom_vacaciones`.`corte`) AS `corte_vac`,
                                    MIN(`nom_vacaciones`.`fec_inicio`) AS `vac_fec_inicio`,
                                    MAX(`nom_vacaciones`.`fec_fin`) AS `vac_fec_fin`
                             FROM `nom_liq_vac`
                             INNER JOIN `nom_vacaciones` ON (`nom_liq_vac`.`id_vac` = `nom_vacaciones`.`id_vac`)
                             WHERE `nom_liq_vac`.`estado` = 1 AND `nom_liq_vac`.`id_nomina` = :id_nomina
                             GROUP BY `nom_vacaciones`.`id_empleado`),
                        `sal` AS
                            (SELECT `id_empleado`,`sal_base`,`id_contrato`,`val_liq`,`metodo_pago`
                             FROM `nom_liq_salario` WHERE `id_nomina` = :id_nomina AND `estado` = 1),
                        `hoex` AS
                            (SELECT `nom_horas_ex_trab`.`id_empleado`,
                                    `nom_tipo_horaex`.`codigo`,
                                    `nom_tipo_horaex`.`factor`,
                                    `nom_horas_ex_trab`.`fec_inicio`,
                                    `nom_horas_ex_trab`.`fec_fin`,
                                    `nom_horas_ex_trab`.`hora_inicio`,
                                    `nom_horas_ex_trab`.`hora_fin`,
                                    `nom_horas_ex_trab`.`cantidad_he`,
                                    `nom_liq_horex`.`val_liq`
                             FROM `nom_horas_ex_trab`
                             INNER JOIN `nom_tipo_horaex` ON (`nom_horas_ex_trab`.`id_he` = `nom_tipo_horaex`.`id_he`)
                             INNER JOIN `nom_liq_horex` ON (`nom_liq_horex`.`id_he_lab` = `nom_horas_ex_trab`.`id_he_trab`)
                             WHERE `nom_liq_horex`.`id_nomina` = :id_nomina),
                        `cont` AS
                            (SELECT `nls`.`id_empleado`,
                                    `nce`.`id_contrato_emp`,
                                    `ntc`.`codigo` AS `cod_contrato`,
                                    `nte`.`codigo` AS `tip_emp`,
                                    `nse`.`codigo` AS `subt_emp`,
                                    `nce`.`fech_inicio`,
                                    `nce`.`fec_retiro`,
                                    `ne`.`salario_integral`,
                                    `ne`.`alto_riesgo_pension`
                             FROM `nom_liq_salario` AS `nls`
                             INNER JOIN `nom_contratos_empleados` AS `nce` ON (`nls`.`id_contrato` = `nce`.`id_contrato_emp`)
                             INNER JOIN `nom_tipo_contrato` AS `ntc` ON (`nce`.`tipo_contrato` = `ntc`.`id_tip_contrato`)
                             INNER JOIN `nom_empleado` AS `ne` ON (`nls`.`id_empleado` = `ne`.`id_empleado`)
                             INNER JOIN `nom_tipo_empleado` AS `nte` ON (`ne`.`tipo_empleado` = `nte`.`id_tip_empl`)
                             INNER JOIN `nom_subtipo_empl` AS `nse` ON (`ne`.`subtipo_empleado` = `nse`.`id_sub_emp`)
                             WHERE `nls`.`id_nomina` = :id_nomina AND `nls`.`estado` = 1)
                    SELECT
                        `e`.`id_empleado`
                        , `e`.`no_documento`
                        , `e`.`nombre1`
                        , `e`.`nombre2`
                        , `e`.`apellido1`
                        , `e`.`apellido2`
                        , `e`.`correo`
                        , `e`.`telefono`
                        , `tpd`.`codigo_ne`
                        , `sal`.`sal_base`
                        , `sal`.`id_contrato`
                        , `sal`.`val_liq`
                        , `sal`.`metodo_pago`
                        , `cont`.`cod_contrato`
                        , `cont`.`tip_emp`
                        , `cont`.`subt_emp`
                        , `cont`.`fech_inicio`
                        , `cont`.`fec_retiro`
                        , `cont`.`salario_integral`
                        , `cont`.`alto_riesgo_pension`
                        , `tpd`.`codigo` AS `tip_doc`
                        , `tb_dep`.`codigo_departamento`
                        , `tb_dep`.`nom_departamento`
                        , `tb_mun`.`codigo_municipio`
                        , `tb_mun`.`nom_municipio`
                        , `e`.`direccion`
                        /* Devengados */
                        , IFNULL(`liq`.`dias_liq`, 0)        AS `dias_lab`
                        , IFNULL(`liq`.`val_liq_dias`, 0)    AS `valor_laborado`
                        , IFNULL(`liq`.`val_liq_auxt`, 0)    AS `aux_tran`
                        , IFNULL(`liq`.`aux_alim`, 0)        AS `aux_alim`
                        , IFNULL(`liq`.`g_representa`, 0)    AS `g_representa`
                        , IFNULL(`liq`.`horas_ext`, 0)       AS `horas_ext`
                        , IFNULL(`inc`.`valor`, 0)           AS `valor_incap`
                        , IFNULL(`inc`.`dias`, 0)            AS `dias_incap`
                        , `inc`.`fec_inicio`                 AS `inc_fec_inicio`
                        , `inc`.`fec_fin`                    AS `inc_fec_fin`
                        , IFNULL(`inc`.`id_tipo`, 0)         AS `tipo_incap`
                        , IFNULL(`luto`.`valor`, 0)          AS `valor_luto`
                        , IFNULL(`luto`.`dias`, 0)           AS `dias_luto`
                        , IFNULL(`lmp`.`val_liq`, 0)         AS `valor_mp`
                        , IFNULL(`lmp`.`dias_liqs`, 0)       AS `dias_mp`
                        , `lmp`.`fec_inicio`                 AS `mp_fec_inicio`
                        , `lmp`.`fec_fin`                    AS `mp_fec_fin`
                        , IFNULL(`pris`.`val_liq_ps`, 0)     AS `valor_ps`
                        , IFNULL(`pris`.`dias_ps`, 0)        AS `dias_ps`
                        , IFNULL(`prin`.`val_liq_pv`, 0)     AS `valor_pv`
                        , IFNULL(`prin`.`dias_pn`, 0)        AS `dias_pn`
                        , IFNULL(`vac`.`val_liq`, 0)         AS `valor_vacacion`
                        , IFNULL(`vac`.`val_prima_vac`, 0)   AS `val_prima_vac`
                        , IFNULL(`vac`.`val_bon_recrea`, 0)  AS `val_bon_recrea`
                        , IFNULL(`vac`.`dias_habiles`, 0)    AS `dias_vacaciones`
                        , `vac`.`vac_fec_inicio`
                        , `vac`.`vac_fec_fin`
                        , IFNULL(`viat`.`valor`, 0)          AS `valor_viatico`
                        , IFNULL(`odev`.`valor`, 0)          AS `valor_otros`
                        , IFNULL(`bsp`.`val_bsp`, 0)         AS `val_bsp`
                        , IFNULL(`ces`.`val_cesantias`, 0)   AS `val_cesantias`
                        , IFNULL(`ces`.`val_icesantias`, 0)  AS `val_icesantias`
                        , IFNULL(`ces`.`dias_ces`, 0)        AS `dias_ces`
                        , IFNULL(`com`.`val_compensa`, 0)    AS `val_compensa`
                        /* Deducciones */
                        , IFNULL(`emb`.`valor`, 0)           AS `valor_embargo`
                        , IFNULL(`lib`.`valor`, 0)           AS `valor_libranza`
                        , `lib`.`descripcion_lib`
                        , IFNULL(`sind`.`val_aporte`, 0)     AS `valor_sind`
                        , IFNULL(`segs`.`aporte_salud_emp`, 0)              AS `valor_salud`
                        , IFNULL(`segs`.`aporte_pension_emp`, 0)            AS `valor_pension`
                        , IFNULL(`segs`.`aporte_solidaridad_pensional`, 0)  AS `val_psolidaria`
                        , IFNULL(`segs`.`porcentaje_ps`, 0)                 AS `porcentaje_ps`
                        , IFNULL(`segs`.`aporte_salud_empresa`, 0)          AS `val_salud_empresa`
                        , IFNULL(`segs`.`aporte_pension_empresa`, 0)        AS `val_pension_empresa`
                        , IFNULL(`segs`.`aporte_rieslab`, 0)                AS `val_rieslab`
                        , IFNULL(`rfte`.`val_ret`, 0)        AS `val_retencion`
                        , IFNULL(`dcto`.`valor`, 0)          AS `valor_descuento`
                    FROM `nom_empleado` `e`
                        INNER JOIN `sal`  ON (`sal`.`id_empleado` = `e`.`id_empleado`)
                        INNER JOIN `cont` ON (`cont`.`id_empleado` = `e`.`id_empleado`)
                        INNER JOIN `tb_tipos_documento` AS `tpd` ON (`e`.`tipo_doc` = `tpd`.`id_tipodoc`)
                        INNER JOIN `tb_municipios` AS `tb_mun` ON (`e`.`municipio` = `tb_mun`.`id_municipio`)
                        INNER JOIN `tb_departamentos` AS `tb_dep` ON (`tb_mun`.`id_departamento` = `tb_dep`.`id_departamento`)
                        LEFT JOIN `liq`  ON (`liq`.`id_empleado`  = `e`.`id_empleado`)
                        LEFT JOIN `emb`  ON (`emb`.`id_empleado`  = `e`.`id_empleado`)
                        LEFT JOIN `rfte` ON (`rfte`.`id_empleado` = `e`.`id_empleado`)
                        LEFT JOIN `inc`  ON (`inc`.`id_empleado`  = `e`.`id_empleado`)
                        LEFT JOIN `lib`  ON (`lib`.`id_empleado`  = `e`.`id_empleado`)
                        LEFT JOIN `viat` ON (`viat`.`id_empleado` = `e`.`id_empleado`)
                        LEFT JOIN `luto` ON (`luto`.`id_empleado` = `e`.`id_empleado`)
                        LEFT JOIN `lmp`  ON (`lmp`.`id_empleado`  = `e`.`id_empleado`)
                        LEFT JOIN `pris` ON (`pris`.`id_empleado` = `e`.`id_empleado`)
                        LEFT JOIN `prin` ON (`prin`.`id_empleado` = `e`.`id_empleado`)
                        LEFT JOIN `segs` ON (`segs`.`id_empleado` = `e`.`id_empleado`)
                        LEFT JOIN `sind` ON (`sind`.`id_empleado` = `e`.`id_empleado`)
                        LEFT JOIN `vac`  ON (`vac`.`id_empleado`  = `e`.`id_empleado`)
                        LEFT JOIN `bsp`  ON (`bsp`.`id_empleado`  = `e`.`id_empleado`)
                        LEFT JOIN `ces`  ON (`ces`.`id_empleado`  = `e`.`id_empleado`)
                        LEFT JOIN `com`  ON (`com`.`id_empleado`  = `e`.`id_empleado`)
                        LEFT JOIN `odev` ON (`odev`.`id_empleado` = `e`.`id_empleado`)
                        LEFT JOIN `dcto` ON (`dcto`.`id_empleado` = `e`.`id_empleado`)
                    ORDER BY `e`.`apellido1`, `e`.`nombre1`";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(':id_nomina', $idNomina, PDO::PARAM_INT);
            $stmt->execute();
            $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            return $empleados ?: [];
        } catch (\PDOException $e) {
            throw new Exception("Error al obtener detalles de empleados: " . $e->getMessage());
        }
    }

    /**
     * Obtiene las horas extra detalladas por empleado en la nómina.
     * Se retornan todas para filtrar por empleado en el servicio.
     * @param int $idNomina
     * @return array Lista de horas extra
     */
    public function getHorasExtra(int $idNomina): array
    {
        try {
            $sql = "SELECT
                        `nom_horas_ex_trab`.`id_empleado`
                        , `nom_tipo_horaex`.`codigo`
                        , `nom_tipo_horaex`.`desc_he`
                        , `nom_tipo_horaex`.`factor`
                        , `nom_horas_ex_trab`.`fec_inicio`
                        , `nom_horas_ex_trab`.`fec_fin`
                        , `nom_horas_ex_trab`.`hora_inicio`
                        , `nom_horas_ex_trab`.`hora_fin`
                        , `nom_horas_ex_trab`.`cantidad_he`
                        , `nom_liq_horex`.`val_liq`
                    FROM `nom_horas_ex_trab`
                        INNER JOIN `nom_tipo_horaex` ON (`nom_horas_ex_trab`.`id_he` = `nom_tipo_horaex`.`id_he`)
                        INNER JOIN `nom_liq_horex` ON (`nom_liq_horex`.`id_he_lab` = `nom_horas_ex_trab`.`id_he_trab`)
                    WHERE `nom_liq_horex`.`id_nomina` = :id_nomina
                    ORDER BY `nom_horas_ex_trab`.`id_empleado`, `nom_tipo_horaex`.`id_he`";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(':id_nomina', $idNomina, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            return $result ?: [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Obtiene la información bancaria de los empleados en la nómina
     * @param int $idNomina
     * @return array Datos bancarios indexados por id_empleado
     */
    public function getInfoBancaria(int $idNomina): array
    {
        try {
            $sql = "SELECT
                        `nom_empleado`.`id_empleado`
                        , `nom_liq_salario`.`forma_pago`
                        , `nom_metodo_pago`.`codigo`
                        , `tb_bancos`.`nom_banco`
                        , `tb_tipo_cta`.`tipo_cta`
                        , `nom_empleado`.`cuenta_bancaria`
                        , `nom_liq_salario`.`val_liq`
                    FROM `nom_liq_salario`
                        INNER JOIN `nom_metodo_pago` ON (`nom_liq_salario`.`metodo_pago` = `nom_metodo_pago`.`id_metodo_pago`)
                        INNER JOIN `nom_empleado` ON (`nom_liq_salario`.`id_empleado` = `nom_empleado`.`id_empleado`)
                        INNER JOIN `tb_bancos` ON (`nom_empleado`.`id_banco` = `tb_bancos`.`id_banco`)
                        INNER JOIN `tb_tipo_cta` ON (`nom_empleado`.`tipo_cta` = `tb_tipo_cta`.`id_tipo_cta`)
                    WHERE `nom_liq_salario`.`id_nomina` = :id_nomina AND `nom_liq_salario`.`estado` = 1";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(':id_nomina', $idNomina, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            // Indexar por id_empleado para acceso O(1)
            $indexed = [];
            foreach ($result as $row) {
                $indexed[$row['id_empleado']] = $row;
            }
            return $indexed;
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Obtiene la lista de empleados que ya tienen soporte enviado para este mes/año
     * @param string $mes Mes (01-12)
     * @param string $anio Año
     * @return array IDs de empleados ya enviados
     */
    public function getEmpleadosYaEnviados(string $mes, string $anio): array
    {
        try {
            $sql = "SELECT `id_empleado`
                    FROM `nom_soporte_ne`
                    WHERE `mes` = :mes AND `anio` = :anio";

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([':mes' => $mes, ':anio' => $anio]);
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $stmt->closeCursor();

            return $result ?: [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Obtiene el consecutivo actual para nómina electrónica
     * @return int Consecutivo actual (mínimo 1)
     */
    public function getConsecutivo(): int
    {
        try {
            $sql = "SELECT `consecutivo` FROM `nom_consecutivo_viaticos` LIMIT 1";
            $stmt = $this->conexion->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return !empty($result) ? max(1, intval($result['consecutivo'])) : 1;
        } catch (\PDOException $e) {
            return 1;
        }
    }

    /**
     * Registra el soporte de nómina electrónica exitoso
     * @param int $idEmpleado
     * @param string $hash CUFE/Hash retornado por Taxxa
     * @param string $referencia Referencia del documento (ej: NE-202501001)
     * @param string $mes
     * @param string $anio
     * @param int $idUser
     * @return int ID del soporte creado
     * @throws Exception
     */
    public function registrarSoporte(int $idEmpleado, string $hash, string $referencia, string $mes, string $anio, int $idUser): int
    {
        try {
            $sql = "INSERT INTO `nom_soporte_ne`
                        (`id_empleado`, `shash`, `referencia`, `mes`, `anio`, `id_user_reg`, `fec_reg`)
                    VALUES (:id_empleado, :hash, :referencia, :mes, :anio, :id_user, :fec_reg)";

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                ':id_empleado' => $idEmpleado,
                ':hash'        => $hash,
                ':referencia'  => $referencia,
                ':mes'         => $mes,
                ':anio'        => $anio,
                ':id_user'     => $idUser,
                ':fec_reg'     => date('Y-m-d H:i:s'),
            ]);

            $id = $this->conexion->lastInsertId();
            if (!$id) {
                throw new Exception("No se pudo registrar el soporte de nómina electrónica");
            }

            return (int)$id;
        } catch (\PDOException $e) {
            throw new Exception("Error al registrar soporte: " . $e->getMessage());
        }
    }

    /**
     * Incrementa el consecutivo de nómina electrónica
     * @param int $nuevoConsecutivo
     * @return bool
     * @throws Exception
     */
    public function actualizarConsecutivo(int $nuevoConsecutivo): bool
    {
        try {
            $sql = "UPDATE `nom_consecutivo_viaticos` SET `consecutivo` = :consecutivo";
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute([':consecutivo' => $nuevoConsecutivo]);
        } catch (\PDOException $e) {
            throw new Exception("Error al actualizar consecutivo: " . $e->getMessage());
        }
    }
}
