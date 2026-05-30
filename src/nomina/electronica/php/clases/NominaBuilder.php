<?php

namespace Src\Nomina\Electronica\Php\Clases;

/**
 * Builder para construcción del documento jPayroll de nómina electrónica
 * Utiliza el patrón Builder con encadenamiento fluido, igual que DocumentBuilder.
 *
 * Estructura JSON resultante para Taxxa (classTaxxa.fjPayrollAdd):
 * {
 *   "wEnvironment": "prod",
 *   "tcalculatedsince": "2025-01-01",
 *   "tcalculateduntil": "2025-01-31",
 *   "tissued": "2025-01-31",
 *   "jemployer": {...},
 *   "aWorkers": { "cedula_NombreEmpleado_NE_id": { ...datos empleado... } }
 * }
 */
class NominaBuilder
{
    private $payroll = [];
    private $workers = [];

    /**
     * Tipos de horas extra reconocidos por Taxxa
     */
    private const TIPOS_HORA_EXTRA = [
        1 => 'HED',
        2 => 'HEN',
        3 => 'HRN',
        4 => 'HEDDF',
        5 => 'HRDDF',
        6 => 'HENDF',
        7 => 'HRNDF',
    ];

    /**
     * Reinicia el builder para reutilización
     * @return self
     */
    public function reset(): self
    {
        $this->payroll = [];
        $this->workers = [];
        return $this;
    }

    /**
     * Establece el entorno de envío
     * @param string $environment 'prod' | 'pruebas'
     * @return self
     */
    public function setEnvironment(string $environment = 'prod'): self
    {
        $this->payroll['wEnvironment'] = $environment;
        return $this;
    }

    /**
     * Establece el período de la nómina
     * @param string $since Fecha inicio del período (Y-m-d)
     * @param string $until Fecha fin del período (Y-m-d)
     * @param string $issued Fecha de emisión del documento (Y-m-d)
     * @return self
     */
    public function setPeriodo(string $since, string $until, string $issued): self
    {
        $this->payroll['tcalculatedsince'] = $since;
        $this->payroll['tcalculateduntil'] = $until;
        $this->payroll['tissued']          = $issued;
        return $this;
    }

    /**
     * Establece los datos del empleador
     * @param array $empresa Datos de la empresa (retornados por NominaRepository::getEmpresaData)
     * @return self
     */
    public function setEmployer(array $empresa): self
    {
        $this->payroll['jemployer'] = [
            'sbusinessname'       => $empresa['nombre'],
            'spersonnamefirst'    => $empresa['nombre'],
            'spersonnamesothers'  => '',
            'spersonsurname'      => $empresa['nombre'],
            'spersonsurnameothers'=> '',
            'wdoctype'            => 'NIT',
            'sDocID'              => $empresa['nit'],
            'jcontact'            => [
                'jAddress' => [
                    'wCountrycode' => $empresa['codigo_pais'],
                    'sStateCode'   => $empresa['codigo_departamento'],
                    'sCityCode'    => $empresa['codigo_departamento'] . $empresa['codigo_municipio'],
                    'sStreet'      => $empresa['direccion'],
                ]
            ]
        ];
        return $this;
    }

    /**
     * Agrega un empleado con toda su información de nómina al documento.
     *
     * @param array $empleado      Fila de NominaRepository::getDetallesEmpleados
     * @param array $horasExtra    Horas extra de este empleado (filtradas)
     * @param array|null $bancaria Datos bancarios del empleado
     * @param string $tipoRef      Prefijo (ej: 'NE')
     * @param string $numero       Sufijo con año+mes+consecutivo (ej: '202501001')
     * @param int $indice          Índice del empleado en el lote (para key apayrollinfo)
     * @return self
     */
    public function addWorker(
        array $empleado,
        array $horasExtra,
        ?array $bancaria,
        string $tipoRef,
        string $numero,
        int $indice
    ): self {
        $id          = $empleado['id_empleado'];
        $workerKey   = $empleado['no_documento'] . '_' . $empleado['nombre1'] . '_' . $empleado['apellido1'] . '_NE_' . $id;
        $idne        = $tipoRef . '-' . $numero;
        $indicene    = strtolower($tipoRef) . $numero;

        // --- Información de pago ---
        $paymentInfo = $this->buildPaymentInfo($bancaria);

        // --- Horas extra ---
        $workTimeDetails = $this->buildWorkTimeDetails($horasExtra);
        $valHoEx = array_sum(array_column($horasExtra, 'val_liq'));

        // --- Devengados ---
        $aIncomes = $this->buildIncomes($empleado, $valHoEx);

        // --- Deducciones ---
        $aDeductions = $this->buildDeductions($empleado);

        // --- Totales ---
        $devengado = floatval(
            $empleado['valor_laborado']
            + $empleado['aux_tran']
            + $empleado['aux_alim']
            + $empleado['g_representa']
            + $empleado['valor_viatico']
            + $empleado['valor_incap']
            + $empleado['valor_mp']
            + $empleado['valor_luto']
            + $empleado['valor_ps']
            + $empleado['valor_pv']
            + $empleado['valor_vacacion']
            + $empleado['val_compensa']
            + $empleado['val_bsp']
            + $valHoEx
        );

        $psolidaria = floatval($empleado['val_psolidaria']);
        $deducciones = floatval(
            $empleado['valor_embargo']
            + $empleado['valor_libranza']
            + $empleado['valor_sind']
            + $empleado['valor_salud']
            + $empleado['valor_pension']
            + $psolidaria
            + $empleado['val_retencion']
        );

        // --- Contrato ---
        $aContract = $this->buildContract($empleado);

        // --- Armar worker ---
        $this->workers[$workerKey] = [
            'wdoctype'             => $empleado['codigo_ne'],
            'sDocId'               => $empleado['no_documento'],
            'sworkercode'          => (string)$id,
            'spersonnamefirst'     => $empleado['nombre1'],
            'lpersonnamesothers'   => !empty($empleado['nombre2']) ? $empleado['nombre2'] : '-',
            'spersonsurname'       => $empleado['apellido1'],
            'lpersonsurnameothers' => $empleado['apellido2'] ?? '',
            'jcontact'             => [
                'semail'   => $empleado['correo'] ?? '',
                'jaddress' => [
                    'wCountrycode' => 'CO',
                    'sStateCode'   => $empleado['codigo_departamento'],
                    'sCityCode'    => $empleado['codigo_departamento'] . $empleado['codigo_municipio'],
                    'sstreet'      => $empleado['direccion'] ?? '',
                ]
            ],
            'apayrollinfo' => [
                'NE-' . $indice => [
                    'xnotes'           => base64_encode('Nómina electrónica'),
                    'sreference'       => $idne,
                    'sprefix'          => $tipoRef,
                    'ssuffix'          => $numero,
                    'ndaysworked'      => intval($empleado['dias_lab']),
                    'ntotalincomes'    => $devengado,
                    'ntotaldeductions' => $deducciones,
                    'nperiodbasesalary'=> floatval($empleado['valor_laborado']),
                    'npayable'         => $devengado - $deducciones,
                    'aIncomes'         => $aIncomes,
                    'aDeductions'      => $aDeductions,
                    'aWorkTimeDetails' => $workTimeDetails,
                ]
            ],
            'aContract'    => $aContract,
            'aPaymentInfo' => [$paymentInfo],
        ];

        return $this;
    }

    /**
     * Construye el array jPayroll completo listo para enviar
     * @return array
     */
    public function build(): array
    {
        $this->payroll['aWorkers'] = $this->workers;
        return $this->payroll;
    }

    // =========================================================================
    // Métodos privados de construcción
    // =========================================================================

    /**
     * Construye la información de pago (banco, cuenta, método)
     */
    private function buildPaymentInfo(?array $bancaria): array
    {
        if ($bancaria) {
            return [
                'spaymentform'   => $bancaria['forma_pago'] ?? null,
                'spaymentmethod' => $bancaria['codigo'] ?? null,
                'sbankname'      => $bancaria['nom_banco'] ?? null,
                'sbankaccounttype'=> $bancaria['tipo_cta'] ?? null,
                'sbankaccountno' => $bancaria['cuenta_bancaria'] ?? null,
                'lpaymentdates'  => date('Y-m-d'),
            ];
        }
        return [
            'spaymentform'    => null,
            'spaymentmethod'  => null,
            'sbankname'       => null,
            'sbankaccounttype'=> null,
            'sbankaccountno'  => null,
            'lpaymentdates'   => null,
        ];
    }

    /**
     * Construye el detalle de horas extra para Taxxa
     * @param array $horasExtra Horas extra filtradas para este empleado
     */
    private function buildWorkTimeDetails(array $horasExtra): array
    {
        $details = [];
        foreach ($horasExtra as $he) {
            $codigo = intval($he['codigo']);
            if (!isset(self::TIPOS_HORA_EXTRA[$codigo])) {
                continue;
            }
            $details[] = [
                'wWorktimeCode' => self::TIPOS_HORA_EXTRA[$codigo],
                'nquantity'     => floatval($he['cantidad_he']),
                'nPaid'         => floatval($he['val_liq']),
                'nRateDelta'    => floatval($he['factor']),
                'tSince'        => $he['fec_inicio'] . 'T' . ($he['hora_inicio'] ?? '00:00:00'),
                'tUntil'        => $he['fec_fin'] . 'T' . ($he['hora_fin'] ?? '00:00:00'),
            ];
        }
        return $details;
    }

    /**
     * Construye el array de devengados (aIncomes) del empleado
     */
    private function buildIncomes(array $e, float $valHoEx): array
    {
        $aIncomes = [];

        // Prima de servicios
        if (floatval($e['valor_ps']) > 0) {
            $aIncomes[] = [
                'wIncomeCode' => 'Primas',
                'nAmount'     => floatval($e['valor_ps']),
                'nPagoNS'     => 0,
                'nPagoS'      => floatval($e['valor_ps']),
                'nQuantity'   => intval($e['dias_ps']),
            ];
        }

        // Cesantías
        if (floatval($e['val_cesantias']) > 0) {
            $aIncomes[] = [
                'wIncomeCode'    => 'Cesantias',
                'nAmount'        => floatval($e['val_cesantias']),
                'nPagoIntereses' => floatval($e['val_icesantias']),
                'nPercentage'    => 12,
            ];
        }

        // Auxilio de transporte
        if (floatval($e['aux_tran']) > 0) {
            $aIncomes[] = [
                'wIncomeCode'         => 'Transporte',
                'nAuxilioTransporte'  => floatval($e['aux_tran']),
                'nViaticoManuAlojS'   => null,
                'nViaticoManuAlojNS'  => floatval($e['valor_viatico']) > 0 ? floatval($e['valor_viatico']) : null,
            ];
        }

        // Auxilio de alimentación
        if (floatval($e['aux_alim']) > 0) {
            $aIncomes[] = [
                'wIncomeCode' => 'Auxilio',
                'nAuxilioS'   => floatval($e['aux_alim']),
                'nAuxilioNS'  => null,
            ];
        }

        // Incapacidad
        if (floatval($e['valor_incap']) > 0) {
            $aIncomes[] = [
                'wIncomeCode' => 'Incapacidad',
                'nAmount'     => floatval($e['valor_incap']),
                'sTipo'       => intval($e['tipo_incap']),
                'nQuantity'   => intval($e['dias_incap']),
                'tSince'      => $e['inc_fec_inicio'],
                'tUntil'      => $e['inc_fec_fin'],
            ];
        }

        // Licencia maternidad/paternidad
        if (floatval($e['valor_mp']) > 0) {
            $aIncomes[] = [
                'wIncomeCode' => 'LicenciaMP',
                'tSince'      => $e['mp_fec_inicio'],
                'tUntil'      => $e['mp_fec_fin'],
                'nAmount'     => floatval($e['valor_mp']),
                'nQuantity'   => intval($e['dias_mp']),
            ];
        }

        // Licencia por luto
        if (floatval($e['valor_luto']) > 0) {
            $aIncomes[] = [
                'wIncomeCode' => 'LicenciaR',
                'nAmount'     => floatval($e['valor_luto']),
                'nQuantity'   => intval($e['dias_luto']),
                'tSince'      => null,
                'tUntil'      => null,
            ];
        }

        // Vacaciones
        if (floatval($e['valor_vacacion']) > 0) {
            $aIncomes[] = [
                'wIncomeCode' => 'VacacionesComunes',
                'nAmount'     => floatval($e['valor_vacacion']),
                'nQuantity'   => intval($e['dias_vacaciones']),
                'tSince'      => $e['vac_fec_inicio'] ?? null,
                'tUntil'      => $e['vac_fec_fin'] ?? null,
            ];
        }

        // Bonificación servicios prestados (BSP)
        if (floatval($e['val_bsp']) > 0) {
            $aIncomes[] = [
                'wIncomeCode'     => 'Bonificacion',
                'nBonificacionS'  => floatval($e['val_bsp']),
                'nBonificacionNS' => null,
            ];
        } else {
            // Campo requerido por Taxxa aunque sea null
            $aIncomes[] = [
                'wIncomeCode'     => 'Bonificacion',
                'nBonificacionS'  => null,
                'nBonificacionNS' => null,
            ];
        }

        // Viáticos (si no se incluyeron en Transporte)
        if (floatval($e['valor_viatico']) > 0 && floatval($e['aux_tran']) == 0) {
            $aIncomes[] = [
                'wIncomeCode'        => 'Transporte',
                'nAuxilioTransporte' => null,
                'nViaticoManuAlojS'  => null,
                'nViaticoManuAlojNS' => floatval($e['valor_viatico']),
            ];
        }

        return $aIncomes;
    }

    /**
     * Construye el array de deducciones (aDeductions) del empleado
     */
    private function buildDeductions(array $e): array
    {
        $aDeductions = [];

        // Embargo
        if (floatval($e['valor_embargo']) > 0) {
            $aDeductions[] = [
                'wDeductionCode' => 'EmbargoFiscal',
                'nAmount'        => floatval($e['valor_embargo']),
            ];
        }

        // Cuota sindical
        if (floatval($e['valor_sind']) > 0) {
            $aDeductions[] = [
                'wDeductionCode' => 'Sindicato',
                'nAmount'        => floatval($e['valor_sind']),
                'nPercentage'    => null,
            ];
        }

        // Fondo de solidaridad pensional
        $psolidaria = floatval($e['val_psolidaria']);
        if ($psolidaria > 0) {
            $pPS = floatval($e['porcentaje_ps']);
            if ($pPS > 0) {
                $psolida = ($psolidaria * 0.5) / $pPS;
                $psolidb = $psolidaria - $psolida;
                $pPSa    = 0.50;
                $pPSb    = $pPS - 0.50;
                $aDeductions[] = [
                    'wDeductionCode' => 'FondoSP',
                    'nPercentage'    => number_format($pPSa, 2, '.', ''),
                    'nDeduccionsp'   => $psolida,
                    'nDeduccionSub'  => $psolidb,
                    'nPorcentajeSub' => number_format($pPSb, 2, '.', ''),
                ];
            } else {
                $aDeductions[] = [
                    'wDeductionCode' => 'FondoSP',
                    'nPercentage'    => null,
                    'nDeduccionsp'   => $psolidaria,
                    'nDeduccionSub'  => null,
                    'nPorcentajeSub' => null,
                ];
            }
        }

        // Libranza
        if (floatval($e['valor_libranza']) > 0) {
            $desc = $e['descripcion_lib'] ?? '';
            $aDeductions[] = [
                'wDeductionCode' => 'Libranza',
                'nAmount'        => floatval($e['valor_libranza']),
                'sDescription'   => $desc,
                'xDescription'   => !empty($desc) ? base64_encode($desc) : null,
            ];
        }

        // Salud (obligatorio)
        $aDeductions[] = [
            'wDeductionCode' => 'Salud',
            'nAmount'        => floatval($e['valor_salud']),
            'nPercentage'    => 4,
        ];

        // Pensión (obligatorio)
        $aDeductions[] = [
            'wDeductionCode' => 'FondoPension',
            'nAmount'        => floatval($e['valor_pension']),
            'nPercentage'    => 4,
        ];

        // Retención en la fuente
        if (floatval($e['val_retencion']) > 0) {
            $aDeductions[] = [
                'wDeductionCode' => 'RetencionFuente',
                'nAmount'        => floatval($e['val_retencion']),
            ];
        }

        return $aDeductions;
    }

    /**
     * Construye la información del contrato del empleado
     */
    private function buildContract(array $e): array
    {
        return [
            [
                'nsalarybase'          => floatval($e['valor_laborado']),
                'wcontracttype'        => mb_strtoupper($e['cod_contrato'] ?? ''),
                'tcontractsince'       => $e['fech_inicio'] ?? null,
                'tcontractuntil'       => !empty($e['fec_retiro']) ? $e['fec_retiro'] : null,
                'wpayrollperiod'       => '5',
                'wdianemployeetype'    => $e['tip_emp'] ?? '01',
                'wdianemployeesubtype' => $e['subt_emp'] ?? '00',
                'bAltoRiesgoPension'   => ($e['alto_riesgo_pension'] == '1'),
                'bSalarioIntegral'     => ($e['salario_integral'] == '1'),
            ]
        ];
    }
}
