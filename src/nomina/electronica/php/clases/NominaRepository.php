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
     *                      fec_inicio, fec_fin, salario_integral, alto_riesgo_pension
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
            // 1. Consulta base: obtener información personal, laboral y contractual de los empleados liquidados
            $sqlBase = "SELECT
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
                            , `ntc`.`codigo_netc` AS `cod_contrato`
                            , `nte`.`codigo` AS `tip_emp`
                            , `nse`.`codigo` AS `subt_emp`
                            , `nce`.`fec_inicio`
                            , `nce`.`fec_fin`
                            , `e`.`salario_integral`
                            , `e`.`alto_riesgo_pension`
                            , `tpd`.`codigo` AS `tip_doc`
                            , `tb_dep`.`codigo_departamento`
                            , `tb_dep`.`nom_departamento`
                            , `tb_mun`.`codigo_municipio`
                            , `tb_mun`.`nom_municipio`
                            , `e`.`direccion`
                        FROM `nom_liq_salario` AS `sal`
                            INNER JOIN `nom_empleado` AS `e` ON (`sal`.`id_empleado` = `e`.`id_empleado`)
                            INNER JOIN `nom_contratos_empleados` AS `nce` ON (`sal`.`id_contrato` = `nce`.`id_contrato_emp`)
                            INNER JOIN `tb_tipos_documento` AS `tpd` ON (`e`.`tipo_doc` = `tpd`.`id_tipodoc`)
                            INNER JOIN `tb_municipios` AS `tb_mun` ON (`e`.`municipio` = `tb_mun`.`id_municipio`)
                            INNER JOIN `tb_departamentos` AS `tb_dep` ON (`tb_mun`.`id_departamento` = `tb_dep`.`id_departamento`)
                            INNER JOIN `nom_tipo_contrato` AS `ntc` ON (`e`.`tipo_contrato` = `ntc`.`id_tip_contrato`)
                            INNER JOIN `nom_tipo_empleado` AS `nte` ON (`e`.`tipo_empleado` = `nte`.`id_tip_empl`)
                            INNER JOIN `nom_subtipo_empl` AS `nse` ON (`e`.`subtipo_empleado` = `nse`.`id_sub_emp`)
                        WHERE `sal`.`id_nomina` = :id_nomina AND `sal`.`estado` = 1
                        ORDER BY `e`.`apellido1`, `e`.`nombre1`";

            $stmt = $this->conexion->prepare($sqlBase);
            $stmt->bindValue(':id_nomina', $idNomina, PDO::PARAM_INT);
            $stmt->execute();
            $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (empty($empleados)) {
                return [];
            }

            // 2. Consultar cada sub-tabla de conceptos por separado filtrando por la nómina actual
            $subQueries = [
                'bsp' => "SELECT `id_empleado`, `val_bsp`, `fec_corte` AS `corte_bsp` FROM `nom_liq_bsp` WHERE `id_nomina` = :id_nomina AND `estado` = 1",
                'ces' => "SELECT `id_empleado`,`val_cesantias`,`val_icesantias`, `corte` AS `corte_ces`, `cant_dias` AS `dias_ces` FROM `nom_liq_cesantias` WHERE `id_nomina` = :id_nomina AND `estado` = 1",
                'com' => "SELECT `id_empleado`,`val_compensa` FROM `nom_liq_compesatorio` WHERE `id_nomina` = :id_nomina AND `estado` = 1",
                'odev' => "SELECT `nod`.`id_empleado`, SUM(`nld`.`valor`) AS `valor` FROM `nom_liq_devengado` AS `nld` INNER JOIN `nom_otros_devengados` AS `nod` ON (`nld`.`id_devengado` = `nod`.`id_devengado`) WHERE `nld`.`estado` = 1 AND `nld`.`id_nomina` = :id_nomina GROUP BY `nod`.`id_empleado`",
                'dcto' => "SELECT `nom_otros_descuentos`.`id_empleado`, SUM(`nom_liq_descuento`.`valor`) AS `valor` FROM `nom_liq_descuento` INNER JOIN `nom_otros_descuentos` ON (`nom_liq_descuento`.`id_dcto` = `nom_otros_descuentos`.`id_dcto`) WHERE `nom_liq_descuento`.`estado` = 1 AND `nom_liq_descuento`.`id_nomina` = :id_nomina GROUP BY `nom_otros_descuentos`.`id_empleado`",
                'liq' => "SELECT `id_empleado`,`dias_liq`,`val_liq_dias`,`val_liq_auxt`,`aux_alim`,`g_representa`,`horas_ext` FROM `nom_liq_dlab_auxt` WHERE `id_nomina` = :id_nomina AND `estado` = 1",
                'emb' => "SELECT `nom_embargos`.`id_empleado`, SUM(`nom_liq_embargo`.`val_mes_embargo`) AS `valor` FROM `nom_liq_embargo` INNER JOIN `nom_embargos` ON (`nom_liq_embargo`.`id_embargo` = `nom_embargos`.`id_embargo`) WHERE `nom_liq_embargo`.`id_nomina` = :id_nomina AND `nom_liq_embargo`.`estado` = 1 GROUP BY `nom_embargos`.`id_empleado`",
                'inc' => "SELECT `nom_incapacidad`.`id_empleado`, SUM(`nom_liq_incap`.`pago_empresa`) AS `pago_empresa`, SUM(`nom_liq_incap`.`pago_empresa` + `nom_liq_incap`.`pago_eps` + `nom_liq_incap`.`pago_arl`) AS `valor`, SUM(`nom_liq_incap`.`dias_liq`) AS `dias`, MIN(`nom_liq_incap`.`fec_inicio`) AS `fec_inicio`, MAX(`nom_liq_incap`.`fec_fin`) AS `fec_fin`, MIN(`nom_incapacidad`.`id_tipo`) AS `id_tipo` FROM `nom_liq_incap` INNER JOIN `nom_incapacidad` ON (`nom_liq_incap`.`id_incapacidad` = `nom_incapacidad`.`id_incapacidad`) WHERE `nom_liq_incap`.`estado` = 1 AND `nom_liq_incap`.`id_nomina` = :id_nomina GROUP BY `nom_incapacidad`.`id_empleado`",
                'lib' => "SELECT `nom_libranzas`.`id_empleado`, SUM(`nom_liq_libranza`.`val_mes_lib`) AS `valor`, MAX(`nom_libranzas`.`descripcion_lib`) AS `descripcion_lib` FROM `nom_liq_libranza` INNER JOIN `nom_libranzas` ON (`nom_liq_libranza`.`id_libranza` = `nom_libranzas`.`id_libranza`) WHERE `nom_liq_libranza`.`id_nomina` = :id_nomina AND `nom_liq_libranza`.`estado` = 1 GROUP BY `nom_libranzas`.`id_empleado`",
                'viat' => "SELECT `nom_viaticos`.`id_empleado`, SUM(`nom_liq_viaticos`.`valor`) AS `valor` FROM `nom_liq_viaticos` INNER JOIN `nom_viaticos` ON (`nom_liq_viaticos`.`id_viatico` = `nom_viaticos`.`id_viatico`) WHERE `nom_liq_viaticos`.`id_nomina` = :id_nomina AND `nom_liq_viaticos`.`estado` = 1 GROUP BY `nom_viaticos`.`id_empleado`",
                'rfte' => "SELECT `id_empleado`,`val_ret`, `base` AS `base_ret` FROM `nom_retencion_fte` WHERE `id_nomina` = :id_nomina AND `estado` = 1",
                'luto' => "SELECT `nom_licencia_luto`.`id_empleado`, SUM(`nom_liq_licluto`.`val_liq`) AS `valor`, SUM(`nom_liq_licluto`.`dias_licluto`) AS `dias` FROM `nom_liq_licluto` INNER JOIN `nom_licencia_luto` ON (`nom_liq_licluto`.`id_licluto` = `nom_licencia_luto`.`id_licluto`) WHERE `nom_liq_licluto`.`estado` = 1 AND `nom_liq_licluto`.`id_nomina` = :id_nomina GROUP BY `nom_licencia_luto`.`id_empleado`",
                'lmp' => "SELECT `nom_licenciasmp`.`id_empleado`, SUM(`nom_liq_licmp`.`val_liq`) AS `val_liq`, SUM(`nom_liq_licmp`.`dias_liqs`) AS `dias_liqs`, MIN(`nom_licenciasmp`.`fec_inicio`) AS `fec_inicio`, MAX(`nom_licenciasmp`.`fec_fin`) AS `fec_fin` FROM `nom_liq_licmp` INNER JOIN `nom_licenciasmp` ON (`nom_liq_licmp`.`id_licmp` = `nom_licenciasmp`.`id_licmp`) WHERE `nom_liq_licmp`.`estado` = 1 AND `nom_liq_licmp`.`id_nomina` = :id_nomina GROUP BY `nom_licenciasmp`.`id_empleado`",
                'pris' => "SELECT `id_empleado`,`val_liq_ps`, `cant_dias` AS `dias_ps`, `corte` AS `corte_ps` FROM `nom_liq_prima` WHERE `estado` = 1 AND `id_nomina` = :id_nomina",
                'prin' => "SELECT `id_empleado`,`val_liq_pv`, `cant_dias` AS `dias_pn`, `corte` AS `corte_pn` FROM `nom_liq_prima_nav` WHERE `estado` = 1 AND `id_nomina` = :id_nomina",
                'segs' => "SELECT `nlsse`.`id_empleado`, `nlsse`.`aporte_salud_emp`, `nlsse`.`aporte_pension_emp`, `nlsse`.`aporte_solidaridad_pensional`, `nlsse`.`aporte_salud_empresa`, `nlsse`.`aporte_pension_empresa`, `nlsse`.`aporte_rieslab`, `nlsse`.`porcentaje_ps` FROM `nom_liq_segsocial_empdo` AS `nlsse` WHERE `nlsse`.`id_nomina` = :id_nomina AND `nlsse`.`estado` = 1",
                'sind' => "SELECT `nom_cuota_sindical`.`id_empleado`, SUM(`nom_liq_sindicato_aportes`.`val_aporte`) AS `val_aporte` FROM `nom_liq_sindicato_aportes` INNER JOIN `nom_cuota_sindical` ON (`nom_liq_sindicato_aportes`.`id_cuota_sindical` = `nom_cuota_sindical`.`id_cuota_sindical`) WHERE `nom_liq_sindicato_aportes`.`id_nomina` = :id_nomina AND `nom_liq_sindicato_aportes`.`estado` = 1 GROUP BY `nom_cuota_sindical`.`id_empleado`",
                'vac' => "SELECT `nom_vacaciones`.`id_empleado`, SUM(`nom_liq_vac`.`val_liq`) AS `val_liq`, SUM(`nom_liq_vac`.`val_prima_vac`) AS `val_prima_vac`, SUM(`nom_liq_vac`.`val_bon_recrea`) AS `val_bon_recrea`, SUM(`nom_vacaciones`.`dias_habiles`) AS `dias_habiles`, MAX(`nom_vacaciones`.`corte`) AS `corte_vac`, MIN(`nom_vacaciones`.`fec_inicio`) AS `vac_fec_inicio`, MAX(`nom_vacaciones`.`fec_fin`) AS `vac_fec_fin` FROM `nom_liq_vac` INNER JOIN `nom_vacaciones` ON (`nom_liq_vac`.`id_vac` = `nom_vacaciones`.`id_vac`) WHERE `nom_liq_vac`.`estado` = 1 AND `nom_liq_vac`.`id_nomina` = :id_nomina GROUP BY `nom_vacaciones`.`id_empleado`"
            ];

            $data = [];
            foreach ($subQueries as $key => $sqlSub) {
                $stmt = $this->conexion->prepare($sqlSub);
                $stmt->bindValue(':id_nomina', $idNomina, PDO::PARAM_INT);
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                $data[$key] = [];
                foreach ($rows as $row) {
                    $data[$key][$row['id_empleado']] = $row;
                }
            }

            // 3. Consolidar y mapear los datos de cada empleado
            $resultado = [];
            foreach ($empleados as $emp) {
                $id = $emp['id_empleado'];

                $liq = $data['liq'][$id] ?? null;
                $inc = $data['inc'][$id] ?? null;
                $luto = $data['luto'][$id] ?? null;
                $lmp = $data['lmp'][$id] ?? null;
                $pris = $data['pris'][$id] ?? null;
                $prin = $data['prin'][$id] ?? null;
                $vac = $data['vac'][$id] ?? null;
                $viat = $data['viat'][$id] ?? null;
                $odev = $data['odev'][$id] ?? null;
                $bsp = $data['bsp'][$id] ?? null;
                $ces = $data['ces'][$id] ?? null;
                $com = $data['com'][$id] ?? null;

                $emb = $data['emb'][$id] ?? null;
                $lib = $data['lib'][$id] ?? null;
                $sind = $data['sind'][$id] ?? null;
                $segs = $data['segs'][$id] ?? null;
                $rfte = $data['rfte'][$id] ?? null;
                $dcto = $data['dcto'][$id] ?? null;

                $resultado[] = array_merge($emp, [
                    /* Devengados */
                    'dias_lab' => intval($liq['dias_liq'] ?? 0),
                    'valor_laborado' => floatval($liq['val_liq_dias'] ?? 0),
                    'aux_tran' => floatval($liq['val_liq_auxt'] ?? 0),
                    'aux_alim' => floatval($liq['aux_alim'] ?? 0),
                    'g_representa' => floatval($liq['g_representa'] ?? 0),
                    'horas_ext' => floatval($liq['horas_ext'] ?? 0),
                    'valor_incap' => floatval($inc['valor'] ?? 0),
                    'dias_incap' => intval($inc['dias'] ?? 0),
                    'inc_fec_inicio' => $inc['fec_inicio'] ?? null,
                    'inc_fec_fin' => $inc['fec_fin'] ?? null,
                    'tipo_incap' => intval($inc['id_tipo'] ?? 0),
                    'valor_luto' => floatval($luto['valor'] ?? 0),
                    'dias_luto' => intval($luto['dias'] ?? 0),
                    'valor_mp' => floatval($lmp['val_liq'] ?? 0),
                    'dias_mp' => intval($lmp['dias_liqs'] ?? 0),
                    'mp_fec_inicio' => $lmp['fec_inicio'] ?? null,
                    'mp_fec_fin' => $lmp['fec_fin'] ?? null,
                    'valor_ps' => floatval($pris['val_liq_ps'] ?? 0),
                    'dias_ps' => intval($pris['dias_ps'] ?? 0),
                    'valor_pv' => floatval($prin['val_liq_pv'] ?? 0),
                    'dias_pn' => intval($prin['dias_pn'] ?? 0),
                    'valor_vacacion' => floatval($vac['val_liq'] ?? 0),
                    'val_prima_vac' => floatval($vac['val_prima_vac'] ?? 0),
                    'val_bon_recrea' => floatval($vac['val_bon_recrea'] ?? 0),
                    'dias_vacaciones' => intval($vac['dias_habiles'] ?? 0),
                    'vac_fec_inicio' => $vac['vac_fec_inicio'] ?? null,
                    'vac_fec_fin' => $vac['vac_fec_fin'] ?? null,
                    'valor_viatico' => floatval($viat['valor'] ?? 0),
                    'valor_otros' => floatval($odev['valor'] ?? 0),
                    'val_bsp' => floatval($bsp['val_bsp'] ?? 0),
                    'val_cesantias' => floatval($ces['val_cesantias'] ?? 0),
                    'val_icesantias' => floatval($ces['val_icesantias'] ?? 0),
                    'dias_ces' => intval($ces['dias_ces'] ?? 0),
                    'val_compensa' => floatval($com['val_compensa'] ?? 0),
                    /* Deducciones */
                    'valor_embargo' => floatval($emb['valor'] ?? 0),
                    'valor_libranza' => floatval($lib['valor'] ?? 0),
                    'descripcion_lib' => $lib['descripcion_lib'] ?? null,
                    'valor_sind' => floatval($sind['val_aporte'] ?? 0),
                    'valor_salud' => floatval($segs['aporte_salud_emp'] ?? 0),
                    'valor_pension' => floatval($segs['aporte_pension_emp'] ?? 0),
                    'val_psolidaria' => floatval($segs['aporte_solidaridad_pensional'] ?? 0),
                    'porcentaje_ps' => floatval($segs['porcentaje_ps'] ?? 0),
                    'val_salud_empresa' => floatval($segs['aporte_salud_empresa'] ?? 0),
                    'val_pension_empresa' => floatval($segs['aporte_pension_empresa'] ?? 0),
                    'val_rieslab' => floatval($segs['aporte_rieslab'] ?? 0),
                    'val_retencion' => floatval($rfte['val_ret'] ?? 0),
                    'valor_descuento' => floatval($dcto['valor'] ?? 0),
                ]);
            }

            return $resultado;
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
                      AND `nom_liq_horex`.`estado` = 1
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
                        LEFT JOIN `tb_bancos` ON (`nom_empleado`.`id_banco` = `tb_bancos`.`id_banco`)
                        LEFT JOIN `tb_tipo_cta` ON (`nom_empleado`.`tipo_cta` = `tb_tipo_cta`.`id_tipo_cta`)
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
                ':hash' => $hash,
                ':referencia' => $referencia,
                ':mes' => $mes,
                ':anio' => $anio,
                ':id_user' => $idUser,
                ':fec_reg' => date('Y-m-d H:i:s'),
            ]);

            $id = $this->conexion->lastInsertId();
            if (!$id) {
                throw new Exception("No se pudo registrar el soporte de nómina electrónica");
            }

            return (int) $id;
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

    // =========================================================================
    // Métodos de detalle (una fila por registro, indexadas por id_empleado)
    // Permiten enviar libranzas, embargos y sindicatos de forma individual a Taxxa
    // =========================================================================

    /**
     * Retorna todas las libranzas liquidadas en la nómina.
     * Cada fila representa una libranza individual (banco + valor).
     * El resultado viene indexado como array de arrays por id_empleado.
     *
     * @param int $idNomina
     * @return array  [id_empleado => [ ['entidad'=>..., 'valor'=>...], ... ], ...]
     */
    public function getLibranzasDetalle(int $idNomina): array
    {
        try {
            $sql = "SELECT
                        `nom_libranzas`.`id_empleado`,
                        `tb_bancos`.`nom_banco`         AS `entidad`,
                        SUM(`nom_liq_libranza`.`val_mes_lib`) AS `valor`
                    FROM `nom_liq_libranza`
                        INNER JOIN `nom_libranzas`
                            ON (`nom_liq_libranza`.`id_libranza` = `nom_libranzas`.`id_libranza`)
                        INNER JOIN `tb_bancos`
                            ON (`nom_libranzas`.`id_banco` = `tb_bancos`.`id_banco`)
                    WHERE `nom_liq_libranza`.`id_nomina` = :id_nomina
                      AND `nom_liq_libranza`.`estado` = 1
                    GROUP BY `nom_libranzas`.`id_empleado`,
                             `nom_libranzas`.`id_libranza`,
                             `tb_bancos`.`nom_banco`
                    ORDER BY `nom_libranzas`.`id_empleado`,
                             `nom_libranzas`.`descripcion_lib` ASC";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(':id_nomina', $idNomina, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $indexed = [];
            foreach ($rows as $row) {
                $indexed[$row['id_empleado']][] = $row;
            }
            return $indexed;
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Retorna todos los embargos liquidados en la nómina.
     * Cada fila representa un embargo individual (juzgado + valor).
     *
     * @param int $idNomina
     * @return array  [id_empleado => [ ['descripcion'=>..., 'valor'=>...], ... ], ...]
     */
    public function getEmbargosDetalle(int $idNomina): array
    {
        try {
            $sql = "SELECT
                        `nom_embargos`.`id_empleado`,
                        COALESCE(`tb_terceros`.`nom_tercero`, 'Embargo') AS `descripcion`,
                        SUM(`nom_liq_embargo`.`val_mes_embargo`) AS `valor`
                    FROM `nom_liq_embargo`
                        INNER JOIN `nom_embargos`
                            ON (`nom_liq_embargo`.`id_embargo` = `nom_embargos`.`id_embargo`)
                        INNER JOIN `nom_terceros`
                            ON (`nom_embargos`.`id_juzgado` = `nom_terceros`.`id_tn`)
                        LEFT JOIN `tb_terceros`
                            ON (`nom_terceros`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                    WHERE `nom_liq_embargo`.`id_nomina` = :id_nomina
                      AND `nom_liq_embargo`.`estado` = 1
                    GROUP BY `nom_embargos`.`id_empleado`,
                             `nom_embargos`.`id_juzgado`,
                             `tb_terceros`.`nom_tercero`
                    ORDER BY `nom_embargos`.`id_empleado`,
                             `tb_terceros`.`nom_tercero` ASC";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(':id_nomina', $idNomina, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $indexed = [];
            foreach ($rows as $row) {
                $indexed[$row['id_empleado']][] = $row;
            }
            return $indexed;
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Retorna todos los aportes sindicales liquidados en la nómina.
     * Cada fila representa un sindicato individual (nombre + valor).
     *
     * @param int $idNomina
     * @return array  [id_empleado => [ ['sindicato'=>..., 'valor'=>...], ... ], ...]
     */
    public function getSindicatosDetalle(int $idNomina): array
    {
        try {
            $sql = "SELECT
                        `nom_cuota_sindical`.`id_empleado`,
                        COALESCE(`tb_terceros`.`nom_tercero`, 'Sindicato') AS `sindicato`,
                        SUM(`nom_liq_sindicato_aportes`.`val_aporte`) AS `valor`
                    FROM `nom_liq_sindicato_aportes`
                        INNER JOIN `nom_cuota_sindical`
                            ON (`nom_liq_sindicato_aportes`.`id_cuota_sindical` = `nom_cuota_sindical`.`id_cuota_sindical`)
                        INNER JOIN `nom_terceros`
                            ON (`nom_cuota_sindical`.`id_sindicato` = `nom_terceros`.`id_tn`)
                        LEFT JOIN `tb_terceros`
                            ON (`nom_terceros`.`id_tercero_api` = `tb_terceros`.`id_tercero_api`)
                    WHERE `nom_liq_sindicato_aportes`.`id_nomina` = :id_nomina
                      AND `nom_liq_sindicato_aportes`.`estado` = 1
                    GROUP BY `nom_cuota_sindical`.`id_empleado`,
                             `nom_terceros`.`id_tn`,
                             `tb_terceros`.`nom_tercero`
                    ORDER BY `nom_cuota_sindical`.`id_empleado`,
                             `tb_terceros`.`nom_tercero` ASC";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(':id_nomina', $idNomina, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $indexed = [];
            foreach ($rows as $row) {
                $indexed[$row['id_empleado']][] = $row;
            }
            return $indexed;
        } catch (\PDOException $e) {
            return [];
        }
    }
}
