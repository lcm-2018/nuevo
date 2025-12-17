<?php

namespace App\DocumentoElectronico\Contratacion;

use App\DocumentoElectronico\DocumentoElectronicoService;
use App\DocumentoElectronico\TaxxaService;
use App\DocumentoElectronico\DocumentBuilder;
use PDO;
use Exception;

/**
 * Servicio extendido para envío de documentos de contratación (no obligados)
 * Extiende el servicio base con lógica específica para contratación
 */
class ContratacionService extends DocumentoElectronicoService
{
    private $contratacionRepo;

    /**
     * Constructor
     * @param PDO $conexion Conexión a BD
     * @param int $idUser ID del usuario que ejecuta
     */
    public function __construct(PDO $conexion, int $idUser)
    {
        parent::__construct($conexion, $idUser);
        $this->contratacionRepo = new ContratacionRepository($conexion);
    }

    /**
     * Envía un documento soporte de contratación (no obligado)
     * @param int $idDocumento ID del documento de contratación
     * @return array Resultado del envío
     */
    public function enviarDocumentoContratacion(int $idDocumento): array
    {
        try {
            // Iniciar transacción
            $this->conexion->beginTransaction();

            // 1. Obtener datos necesarios
            $nonce = $this->repository->getAndUpdateNonce();
            $empresa = $this->repository->getEmpresaData();
            $documento = $this->contratacionRepo->getDocumentoContratacion($idDocumento);
            $detalles = $this->contratacionRepo->getDetallesContratacion($idDocumento);
            $resolucion = $this->repository->getResolucion(1, 2);

            // 2. Validar resolución
            $this->repository->validarResolucion($resolucion);

            // 3. Inicializar servicio Taxxa
            $this->taxxaService = new TaxxaService(
                $empresa['endpoint'],
                $empresa['user_prov'],
                $empresa['pass_prov'],
                $nonce['valor']
            );

            // 4. Autenticar
            $this->taxxaService->authenticate();

            // 5. Verificar si ya existe soporte
            $soporteExistente = $this->contratacionRepo->getSoporteContratacion($idDocumento);
            $secuencia = intval($resolucion['consecutivo']);
            $idSoporte = null;
            $isNew = true;

            if ($soporteExistente) {
                $dato = explode('-', $soporteExistente['referencia']);
                $secuencia = intval($dato[1]);
                $idSoporte = $soporteExistente['id_soporte'];
                $isNew = false;
            }

            // 6. Construir documento
            $documentData = $this->buildDocumentoContratacion(
                $documento,
                $detalles,
                $empresa,
                $resolucion,
                $secuencia
            );

            // 7. Enviar a Taxxa
            $response = $this->taxxaService->sendDocument(
                $documentData,
                $resolucion['entorno'],
                'classTaxxa.fjDocumentExternalAdd'
            );

            // 8. Procesar respuesta
            $result = $this->procesarRespuestaContratacion(
                $response,
                $idDocumento,
                $idSoporte,
                $resolucion,
                $secuencia,
                $isNew
            );

            // 9. Guardar log
            $this->taxxaService->saveLog("log_contratacion_{$idDocumento}.txt");

            // Confirmar transacción
            $this->conexion->commit();

            return $result;
        } catch (Exception $e) {
            // Revertir transacción
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            return [
                'value' => 'Error',
                'msg' => $e->getMessage()
            ];
        }
    }

    /**
     * Construye el documento de contratación para enviar
     * @param array $documento Datos del documento
     * @param array $detalles Ítems del documento
     * @param array $empresa Datos de la empresa
     * @param array $resolucion Datos de la resolución
     * @param int $secuencia Secuencia actual
     * @return array Documento listo para enviar
     */
    private function buildDocumentoContratacion(
        array $documento,
        array $detalles,
        array $empresa,
        array $resolucion,
        int $secuencia
    ): array {
        // Preparar datos del tercero (vendedor en doc soporte)
        $tercero = [
            'tipo_org' => $documento['tipo_org'] ?? 1,
            'resp_fiscal' => $documento['resp_fiscal'] ?? 'R-99-PN',
            'reg_fiscal' => $documento['reg_fiscal'] ?? 1,
            'no_doc' => $documento['nit_tercero'],
            'nit' => $documento['nit_tercero'],
            'nombre' => str_replace('-', '', trim($documento['nom_tercero'])),
            'correo' => $documento['email'],
            'telefono' => $documento['tel_tercero'],
            'codigo_pais' => 'CO',
            'codigo_dpto' => $documento['codigo_departamento'],
            'nom_departamento' => $documento['nom_departamento'],
            'codigo_municipio' => $documento['codigo_municipio'],
            'nom_municipio' => $documento['nom_municipio'],
            'cod_postal' => $documento['cod_postal'],
            'direccion' => $documento['dir_tercero'],
            'formato_soporte' => true,
        ];

        // Construir documento
        $this->builder->reset()
            ->setDocumentType('ReverseInvoice')
            ->setBasicInfo([
                'wdocumentsubtype' => '9',
                'wpaymentmeans' => $documento['met_pago'] ?? '1',
                'wpaymentmethod' => $documento['forma_pago'] ?? 'ZZZ',
                'yreversebuyerseller' => 'N',
                'yaiu' => 'N',
                'wdocumenttypecode' => '05',
                'idocprecision' => 2,
                'spaymentid' => $documento['nota'],
                'yisresident' => 'Y',
                'sinvoiceperiod' => '1',
                'rdocumenttemplate' => 30884303,
            ])
            ->setReference($resolucion['prefijo'], $secuencia)
            ->setDates(
                date('Y-m-d', strtotime($documento['fecha_fact'])),
                date('Y-m-d', strtotime($documento['fecha_ven']))
            )
            ->setBuyer($empresa)
            ->setSeller($tercero);

        // Agregar detalles (ítems)
        foreach ($detalles as $item) {
            $this->builder->addItem([
                'codigo' => $item['codigo'] ?? '85101604',
                'detalle' => $item['detalle'] ?? 'Servicio',
                'val_unitario' => $item['val_unitario'],
                'cantidad' => $item['cantidad'],
                'p_iva' => $item['p_iva'] ?? 0,
                'val_iva' => $item['val_iva'] ?? 0,
                'p_dcto' => $item['p_dcto'] ?? 0,
                'val_dcto' => $item['val_dcto'] ?? 0,
            ]);
        }

        return $this->builder->build();
    }

    /**
     * Procesa la respuesta de Taxxa para documento de contratación
     * @param array $response Respuesta de Taxxa
     * @param int $idDocumento ID del documento
     * @param int|null $idSoporte ID del soporte (si existe)
     * @param array $resolucion Datos de resolución
     * @param int $secuencia Secuencia utilizada
     * @param bool $isNew Si es nuevo registro
     * @return array Resultado procesado
     */
    private function procesarRespuestaContratacion(
        array $response,
        int $idDocumento,
        ?int $idSoporte,
        array $resolucion,
        int $secuencia,
        bool $isNew
    ): array {
        $numero = $resolucion['prefijo'] . '-' . $secuencia;
        $hash = null;
        $referencia = $numero;

        if ($response['error'] === 0) {
            $hash = $response['data']['scufe'] ?? null;
            $referencia = $response['data']['sdocumentreference'] ?? $numero;
        }

        // Guardar o actualizar soporte
        try {
            if ($isNew) {
                $this->contratacionRepo->crearSoporteContratacion(
                    $idDocumento,
                    $referencia,
                    date('Y-m-d'),
                    $this->idUser
                );
            } else {
                // Actualizar soporte existente
                $this->repository->actualizarSoporte(
                    $idSoporte,
                    $hash ?? '',
                    $referencia,
                    $idDocumento,
                    $this->idUser
                );
            }

            // Actualizar consecutivo si es nuevo y exitoso
            if ($isNew && $response['error'] === 0) {
                $this->repository->actualizarConsecutivo(
                    $resolucion['id_resol'],
                    $secuencia + 1,
                    false
                );
            }
        } catch (Exception $e) {
            // Log pero no fallar
        }

        if ($hash !== null && $response['error'] === 0) {
            return [
                'value' => 'ok',
                'msg' => json_encode('Documento enviado correctamente'),
                'data' => $response['data']
            ];
        } else {
            return [
                'value' => 'Error',
                'msg' => $this->formatErrorTable($response['message'])
            ];
        }
    }

    /**
     * Formatea errores en tabla HTML
     * @param string $message Mensaje de error
     * @return string HTML formateado
     */
    private function formatErrorTable(string $message): string
    {
        if (strpos($message, ';') !== false) {
            $errors = explode(';', $message);
            $html = '<table>';
            foreach ($errors as $error) {
                $html .= '<tr><td>' . trim($error) . '</td></tr>';
            }
            $html .= '</table>';
            return $html;
        }
        return $message;
    }
}
