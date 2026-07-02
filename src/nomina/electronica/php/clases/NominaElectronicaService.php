<?php

namespace Src\Nomina\Electronica\Php\Clases;

use PDO;
use Exception;
use App\DocumentoElectronico\TaxxaService;

/**
 * Servicio principal para envío de nómina electrónica a Taxxa
 *
 * Orquesta todo el proceso de envío usando el mismo patrón que
 * DocumentoElectronicoService del módulo de documento soporte.
 *
 * Flujo:
 *  1. getAndUpdateNonce()
 *  2. getEmpresaData()
 *  3. getNominaData()        → mes, fecha de liquidación
 *  4. Calcular período       → fec_i, fec_f según el mes
 *  5. getDetallesEmpleados() → todos los valores en una sola CTE
 *  6. getEmpleadosYaEnviados() → filtrar ya procesados
 *  7. getInfoBancaria()      → datos bancarios indexados
 *  8. getHorasExtra()        → horas extra indexadas por empleado
 *  9. authenticate()         → TaxxaService → token JWT
 * 10. Para cada empleado pendiente (pago > 0):
 *     a. NominaBuilder::addWorker()
 *     b. TaxxaService::sendPayroll()
 *     c. registrarSoporte() + actualizarConsecutivo()
 * 11. saveLog()
 * 12. Retornar resumen
 */
class NominaElectronicaService
{
    private $repository;
    private $builder;
    private $taxxaService;
    private $conexion;
    private $idUser;

    /** @var string Prefijo de referencia para documentos de nómina */
    private const TIPO_REF = 'NE';

    /** @var string Entorno de envío */
    private const ENTORNO = 'prod';

    /**
     * Constructor
     * @param PDO $conexion
     * @param int $idUser ID del usuario que ejecuta el proceso
     */
    public function __construct(PDO $conexion, int $idUser)
    {
        $this->conexion = $conexion;
        $this->idUser = $idUser;
        $this->repository = new NominaRepository($conexion);
        $this->builder = new NominaBuilder();
    }

    /**
     * Ejecuta el proceso completo de envío de nómina electrónica
     *
     * @param int $idNomina ID de la nómina a procesar
     * @param string $anio Año de vigencia (ej: '2025')
     * @return array Resultado: {value, msg, procesados, incorrectos, errores[]}
     */
    public function enviarNominaElectronica(int $idNomina, string $anio): array
    {
        $procesado = 0;
        $incorrectos = 0;
        $errores = [];

        try {
            // 1. Nonce (se actualiza fuera de transacción, igual que documento soporte)
            $nonce = $this->repository->getAndUpdateNonce();

            // 2. Datos de la empresa
            $empresa = $this->repository->getEmpresaData();

            // 3. Datos de la nómina
            $nomina = $this->repository->getNominaData($idNomina);
            $mes = $nomina['mes'];   // '01' .. '12'
            $fecLiq = date('Y-m-d', strtotime($nomina['fec_reg']));

            // 4. Calcular período del mes
            [$fecInicio, $fecFin] = $this->calcularPeriodo($anio, $mes);

            // 5. Todos los detalles de empleados (CTE unificada)
            $detalles = $this->repository->getDetallesEmpleados($idNomina);

            if (empty($detalles)) {
                return [
                    'value' => 'ok',
                    'msg' => 'No hay empleados liquidados en esta nómina',
                    'procesados' => 0,
                    'incorrectos' => 0,
                    'errores' => [],
                ];
            }

            // 6. Empleados ya enviados este mes/año → no reenviar
            $yaEnviados = $this->repository->getEmpleadosYaEnviados($mes, $anio);

            // 7. Datos bancarios (indexados por id_empleado)
            $bancaria = $this->repository->getInfoBancaria($idNomina);

            // 8. Horas extra (indexadas por id_empleado)
            $todasHorasExtra = $this->repository->getHorasExtra($idNomina);
            $horasExtraIdx = $this->indexarPorEmpleado($todasHorasExtra);

            // 8b. Detalle de libranzas, embargos y sindicatos (indexados por id_empleado)
            $libranzasIdx = $this->repository->getLibranzasDetalle($idNomina);
            $embargosIdx = $this->repository->getEmbargosDetalle($idNomina);
            $sindicatosIdx = $this->repository->getSindicatosDetalle($idNomina);

            // 9. Autenticar en Taxxa
            $this->taxxaService = new TaxxaService(
                $empresa['endpoint'],
                $empresa['user_prov'],
                $empresa['pass_prov'],
                $nonce['valor']
            );
            $this->taxxaService->authenticate();

            // Preparar fecha e índice del loop
            $hoy = date('Y-m-d');
            $indice = 1;

            // 10. Procesar cada empleado
            foreach ($detalles as $empleado) {
                $idEmp = $empleado['id_empleado'];

                // Saltar si ya fue enviado
                if (in_array($idEmp, $yaEnviados)) {
                    continue;
                }

                // Saltar si el pago neto es 0 o negativo
                if (floatval($empleado['val_liq']) <= 0) {
                    continue;
                }

                // Obtener horas extra de este empleado
                $hoexEmpleado = $horasExtraIdx[$idEmp] ?? [];

                // Obtener datos bancarios
                $bancariaEmpleado = $bancaria[$idEmp] ?? null;

                // Obtener consecutivo actual
                $consecutivo = $this->repository->getConsecutivo();
                $numero = $anio . $mes . str_pad($consecutivo, 3, '0', STR_PAD_LEFT);

                // Construir el jPayroll para ESTE empleado
                $this->builder->reset()
                    ->setEnvironment(self::ENTORNO)
                    ->setPeriodo($fecInicio, $fecFin, $hoy)
                    ->setEmployer($empresa)
                    ->addWorker(
                        $empleado,
                        $hoexEmpleado,
                        $bancariaEmpleado,
                        self::TIPO_REF,
                        $numero,
                        $indice,
                        $libranzasIdx[$idEmp] ?? [],
                        $embargosIdx[$idEmp] ?? [],
                        $sindicatosIdx[$idEmp] ?? []
                    );

                $jPayroll = $this->builder->build();

                // Enviar a Taxxa
                $response = $this->sendPayroll($jPayroll);

                // Procesar respuesta
                $indicene = strtolower(self::TIPO_REF) . $numero;

                if (isset($response['rerror']) && $response['rerror'] == 0) {
                    // Éxito: guardar hash y referencia
                    $shash = $response['aresult'][$indicene]['shash'] ?? '';
                    $sreference = $response['aresult'][$indicene]['sreference'] ?? (self::TIPO_REF . '-' . $numero);

                    try {
                        $this->repository->registrarSoporte(
                            $idEmp,
                            $shash,
                            $sreference,
                            $mes,
                            $anio,
                            $this->idUser
                        );
                        $this->repository->actualizarConsecutivo($consecutivo + 1);
                        $procesado++;
                        $indice++;
                    } catch (Exception $e) {
                        $errores[] = "Empleado #{$idEmp}: Error al guardar soporte - " . $e->getMessage();
                    }
                } else {
                    // Error de Taxxa
                    $incorrectos++;
                    $msgError = $this->extraerMensajeError($response);
                    // Si no había smessage útil, adjuntar la respuesta cruda para diagnóstico
                    if ($msgError === 'Error desconocido') {
                        $msgError .= ' | Respuesta: ' . json_encode($response, JSON_UNESCAPED_UNICODE);
                    }
                    $errores[] = "Empleado #{$idEmp} ({$empleado['nombre1']} {$empleado['apellido1']}): "
                        . "Error " . ($response['rerror'] ?? '?') . " - " . $msgError;
                }
            }

            // 11. Guardar log
            try {
                $this->taxxaService->saveLog('log_nomina_' . $idNomina . '.txt');
            } catch (Exception $logError) {
                // No interrumpir el proceso por fallo de log
            }

            return [
                'value' => 'ok',
                'msg' => "Se procesaron <b>{$procesado}</b> soporte(s) de nómina electrónica",
                'procesados' => $procesado,
                'incorrectos' => $incorrectos,
                'errores' => $errores,
            ];

        } catch (Exception $e) {
            // Guardar log de error si el servicio ya fue inicializado
            if (isset($this->taxxaService)) {
                try {
                    $this->taxxaService->saveLog('log_nomina_' . $idNomina . '_error.txt');
                } catch (Exception $logError) {
                    // Ignorar
                }
            }

            return [
                'value' => 'Error',
                'msg' => $e->getMessage(),
                'procesados' => $procesado,
                'incorrectos' => $incorrectos,
                'errores' => $errores,
            ];
        }
    }

    // =========================================================================
    // Métodos privados
    // =========================================================================

    /**
     * Envía el jPayroll a Taxxa usando classTaxxa.fjPayrollAdd
     * @param array $jPayroll
     * @return array Respuesta cruda de Taxxa (decodificada)
     * @throws Exception
     */
    private function sendPayroll(array $jPayroll): array
    {
        $payload = [
            'sToken' => $this->taxxaService->getToken(),
            'jApi' => [
                'sMethod' => 'classTaxxa.fjPayrollAdd',
                'jParams' => [
                    'bAsync' => false,
                    'jPayroll' => $jPayroll,
                ]
            ]
        ];

        $endpoint = $this->taxxaService->getEndpoint();
        $jsonData = json_encode($payload);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("Error de conexión con Taxxa: " . $curlError);
        }

        $decoded = json_decode($response, true);

        if ($decoded === null) {
            throw new Exception("Respuesta inválida de Taxxa (no JSON): " . substr($response, 0, 200));
        }

        return $decoded;
    }

    /**
     * Calcula las fechas de inicio y fin del período de nómina según el mes
     * @param string $anio
     * @param string $mes
     * @return array [fec_inicio, fec_fin]
     * @throws Exception
     */
    private function calcularPeriodo(string $anio, string $mes): array
    {
        $dia = '01';

        switch ($mes) {
            case '01':
            case '03':
            case '05':
            case '07':
            case '08':
            case '10':
            case '12':
                return ["{$anio}-{$mes}-{$dia}", "{$anio}-{$mes}-31"];

            case '02':
                $bis = date('L', strtotime("{$anio}-01-01")) ? '29' : '28';
                return ["{$anio}-{$mes}-{$dia}", "{$anio}-{$mes}-{$bis}"];

            case '04':
            case '06':
            case '09':
            case '11':
                return ["{$anio}-{$mes}-{$dia}", "{$anio}-{$mes}-30"];

            default:
                throw new Exception("Mes inválido: {$mes}");
        }
    }

    /**
     * Indexa un array de filas por id_empleado
     * Soporta múltiples filas por empleado (retorna array de arrays)
     */
    private function indexarPorEmpleado(array $rows): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $idEmp = $row['id_empleado'];
            $indexed[$idEmp][] = $row;
        }
        return $indexed;
    }

    /**
     * Extrae el mensaje de error de la respuesta de Taxxa.
     * smessage puede ser un string simple o un array anidado
     * (ej: {"aWorkers": {"key": ["mensaje de validación"]}}).
     * Se serializa con json_encode para no perder el detalle.
     */
    private function extraerMensajeError(array $response): string
    {
        if (!empty($response['smessage'])) {
            $msg = $response['smessage'];
            if (is_string($msg)) {
                return $msg;
            }
            if (is_array($msg)) {
                // Serializar el array completo para ver el error real de Taxxa
                return json_encode($msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
        }
        return 'Error desconocido';
    }
}
