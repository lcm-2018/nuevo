<?php

namespace App\DocumentoElectronico;

use PDO;
use Exception;

/**
 * Servicio principal para envío de documentos electrónicos
 * Orquesta todo el proceso de envío
 */
class DocumentoElectronicoService
{
    private $repository;
    private $taxxaService;
    private $builder;
    private $conexion;
    private $idUser;
    private $errors = [];
    private $warnings = [];

    /**
     * Constructor
     * @param PDO $conexion Conexión a BD
     * @param int $idUser ID del usuario que ejecuta
     */
    public function __construct(PDO $conexion, int $idUser)
    {
        $this->conexion = $conexion;
        $this->repository = new DocumentRepository($conexion);
        $this->builder = new DocumentBuilder();
        $this->idUser = $idUser;
    }

    /**
     * Envía un documento soporte (ReverseInvoice)
     * @param int $idDocumento ID del documento contable
     * @return array Resultado del envío
     */
    public function enviarDocumentoSoporte(int $idDocumento): array
    {
        try {
            // Iniciar transacción
            $this->conexion->beginTransaction();

            // 1. Obtener datos necesarios
            $nonce = $this->repository->getAndUpdateNonce();
            $empresa = $this->repository->getEmpresaData();
            $documento = $this->repository->getDocumentoData($idDocumento, false);
            $unspsc = $this->repository->getUNSPSC($idDocumento);
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
            $soporteExistente = $this->repository->getSoporteExistente($idDocumento);
            $secuencia = intval($resolucion['consecutivo']);
            $idSoporte = null;

            if ($soporteExistente) {
                $dato = explode('-', $soporteExistente['referencia']);
                $secuencia = intval($dato[1]);
                $idSoporte = $soporteExistente['id_soporte'];
            } else {
                // Crear nuevo soporte
                $referencia = $resolucion['prefijo'] . '-' . $secuencia;
                $idSoporte = $this->repository->crearSoporte(
                    $idDocumento,
                    $referencia,
                    date('Y-m-d'),
                    $this->idUser
                );
            }

            // 6. Construir documento
            $documentData = $this->buildDocumentoSoporte($documento, $empresa, $resolucion, $secuencia, $unspsc);

            // 7. Enviar a Taxxa
            $response = $this->taxxaService->sendDocument(
                $documentData,
                $resolucion['entorno'],
                'classTaxxa.fjDocumentExternalAdd'
            );

            // 8. Procesar respuesta
            $result = $this->procesarRespuesta(
                $response,
                $idSoporte,
                $idDocumento,
                $resolucion,
                $secuencia
            );

            // 9. Guardar log
            $this->taxxaService->saveLog('log_envio_' . $idDocumento . '.txt');

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
                'msg' => $e->getMessage(),
                'errors' => $this->errors
            ];
        }
    }

    /**
     * Envía una factura de venta (Invoice)
     * @param int $idDocumento ID del documento contable
     * @return array Resultado del envío
     */
    public function enviarFacturaVenta(int $idDocumento): array
    {
        try {
            // Iniciar transacción
            $this->conexion->beginTransaction();

            // 1. Obtener datos necesarios
            $nonce = $this->repository->getAndUpdateNonce();
            $empresa = $this->repository->getEmpresaData();
            $documento = $this->repository->getDocumentoData($idDocumento, true);
            $resolucion = $this->repository->getResolucion(1, 1);

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
            $soporteExistente = $this->repository->getSoporteExistente($idDocumento, 0);
            $secuencia = intval($resolucion['consecutivo']);
            $isNew = true;
            $idSoporte = null;

            if ($soporteExistente) {
                $dato = explode('-', $soporteExistente['referencia']);
                $secuencia = intval($dato[1]);
                $isNew = false;
                $idSoporte = $soporteExistente['id_soporte'];
            }

            // Si es nuevo, actualizar el consecutivo inmediatamente
            if ($isNew) {
                $this->repository->actualizarConsecutivo(
                    $resolucion['id_resol'],
                    $secuencia + 1,
                    true
                );
            }

            // 6. Construir documento
            $documentData = $this->buildFacturaVenta($documento, $empresa, $resolucion, $secuencia);

            // 7. Enviar a Taxxa
            $response = $this->taxxaService->sendDocument(
                $documentData,
                'prod',
                'classTaxxa.fjDocumentAdd'
            );

            // 8. Procesar respuesta
            $result = $this->procesarRespuestaVenta(
                $response,
                $idDocumento,
                $resolucion,
                $secuencia,
                $isNew
            );

            // 9. Guardar log
            $this->taxxaService->saveLog('log_venta_' . $idDocumento . '.txt');

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
                'msg' => $e->getMessage(),
                'errors' => $this->errors
            ];
        }
    }

    /**
     * Construye el documento soporte para enviar
     * @param array $documento Datos del documento
     * @param array $empresa Datos de la empresa
     * @param array $resolucion Datos de la resolución
     * @param int $secuencia Secuencia actual
     * @param string $unspsc Código UNSPSC
     * @return array Documento listo para enviar
     */
    private function buildDocumentoSoporte(array $documento, array $empresa, array $resolucion, int $secuencia, string $unspsc): array
    {
        // Preparar datos del tercero (vendedor en doc soporte)
        $tercero = [
            'tipo_org' => 1,
            'resp_fiscal' => 'R-99-PN',
            'reg_fiscal' => 1,
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
            'formato_soporte' => true, // Flag para usar formato de soporte
        ];

        // Construir documento
        $this->builder->reset()
            ->setDocumentType('ReverseInvoice')
            ->setBasicInfo([
                'wdocumentsubtype' => '9',
                'wpaymentmeans' => '1',
                'wpaymentmethod' => 'ZZZ',
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
            ->setBuyer($empresa) // La empresa es el comprador
            ->setSeller($tercero) // El tercero es el vendedor
            ->addItem([
                'codigo' => $unspsc,
                'detalle' => $documento['nota'],
                'val_unitario' => $documento['valor_base'],
                'cantidad' => 1,
                'p_iva' => 0,
                'val_iva' => 0,
                'p_dcto' => 0,
                'val_dcto' => 0,
            ]);

        return $this->builder->build();
    }

    /**
     * Construye la factura de venta para enviar
     * @param array $documento Datos del documento
     * @param array $empresa Datos de la empresa
     * @param array $resolucion Datos de la resolución
     * @param int $secuencia Secuencia actual
     * @return array Documento listo para enviar
     */
    private function buildFacturaVenta(array $documento, array $empresa, array $resolucion, int $secuencia): array
    {
        // Preparar datos del tercero (comprador en factura venta)
        $tercero = [
            'tipo_org' => 1,
            'resp_fiscal' => 'R-99-PN',
            'reg_fiscal' => 1,
            'codigo_ne' => 'CC',
            'no_doc' => $documento['nit_tercero'],
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
            'wprovincecode' => $documento['codigo_departamento'] . $documento['codigo_municipio'],
        ];

        // Código UNSPSC para facturas de venta
        $unspsc = !empty($documento['id_ref_ctb']) ? $documento['id_ref_ctb'] : '0001';

        // Construir documento
        $this->builder->reset()
            ->setDocumentType('Invoice')
            ->setBasicInfo([
                'wdocumentsubtype' => '9',
                'wpaymentmeans' => '1',
                'wpaymentmethod' => 'ZZZ',
                'wbusinessregimen' => 1,
                'woperationtype' => '10',
                'sorderreference' => $documento['id_manu'] ?? '',
                'snotetop' => 'Esta factura se asimila a una la Letra de Cambio (Según el artículo 774 C.C)',
                'snotes' => $documento['nota'] ?: ' ',
            ])
            ->setReference($resolucion['prefijo'], $secuencia)
            ->setDates(
                date('Y-m-d', strtotime($documento['fecha_fact'])),
                date('Y-m-d', strtotime($documento['fecha_ven']))
            )
            ->setSeller($empresa) // La empresa es el vendedor
            ->setBuyer($tercero) // El tercero es el comprador
            ->addItem([
                'codigo' => $unspsc,
                'detalle' => $documento['nom_ref'] ?? $documento['nota'],
                'val_unitario' => $documento['valor_base'],
                'cantidad' => 1,
                'p_iva' => 0,
                'val_iva' => 0,
                'p_dcto' => 0,
                'val_dcto' => 0,
                'nunitprice' => true, // Flag para usar nombres de factura venta
            ]);

        // Agregar información extra para factura de venta
        $this->builder->setExtraInfo([
            'jextrainfo' => [
                'xlegalinfo' => ' '
            ]
        ]);

        return $this->builder->build();
    }

    /**
     * Procesa la respuesta de Taxxa para documento soporte
     * @param array $response Respuesta de Taxxa
     * @param int $idSoporte ID del soporte
     * @param int $idDocumento ID del documento
     * @param array $resolucion Datos de resolución
     * @param int $secuencia Secuencia utilizada
     * @return array Resultado procesado
     */
    private function procesarRespuesta(array $response, int $idSoporte, int $idDocumento, array $resolucion, int $secuencia): array
    {
        $numero = $resolucion['prefijo'] . $secuencia;

        if ($response['error'] === 0) {
            // Éxito
            $hash = $response['data']['scufe'] ?? '';
            $referencia = $response['data']['sdocumentreference'] ?? $numero;

            $this->repository->actualizarSoporte($idSoporte, $hash, $referencia, $idDocumento, $this->idUser);
            $this->repository->actualizarConsecutivo($resolucion['id_resol'], $secuencia + 1);
            $this->repository->actualizarNumeroFactura($idDocumento, $numero);

            return [
                'value' => 'ok',
                'msg' => json_encode('Documento enviado correctamente'),
                'data' => $response['data']
            ];
        } else if ($response['error'] === 2) {
            // Documento ya existe, consultar
            try {
                $consulta = $this->taxxaService->getDocument($numero);

                if ($consulta['error'] === 0) {
                    $hash = $consulta['data']['shash'] ?? '';
                    $referencia = $numero;

                    $this->repository->actualizarSoporte($idSoporte, $hash, $referencia, $idDocumento, $this->idUser);

                    return [
                        'value' => 'ok',
                        'msg' => json_encode('Documento ya estaba enviado, información actualizada'),
                        'data' => $consulta['data']
                    ];
                }

                return [
                    'value' => 'Error',
                    'msg' => 'No se pudo enviar la factura electrónica: ' . $consulta['message']
                ];
            } catch (Exception $e) {
                return [
                    'value' => 'Error',
                    'msg' => 'Error al consultar documento existente: ' . $e->getMessage()
                ];
            }
        } else {
            // Error
            return [
                'value' => 'Error',
                'msg' => $this->formatErrorTable($response['message'])
            ];
        }
    }

    /**
     * Procesa la respuesta de Taxxa para factura de venta
     * @param array $response Respuesta de Taxxa
     * @param int $idDocumento ID del documento
     * @param array $resolucion Datos de resolución
     * @param int $secuencia Secuencia utilizada
     * @param bool $isNew Si es nuevo registro
     * @return array Resultado procesado
     */
    private function procesarRespuestaVenta(array $response, int $idDocumento, array $resolucion, int $secuencia, bool $isNew): array
    {
        $numero = $resolucion['prefijo'] . '-' . $secuencia;
        $hash = null;
        $referencia = $numero;

        if ($response['error'] === 0) {
            $hash = $response['data']['scufe'] ?? null;
            $referencia = $response['data']['sdocumentreference'] ?? $numero;
        }

        // Guardar o actualizar soporte
        if ($isNew) {
            $this->repository->crearSoporte($idDocumento, $referencia, date('Y-m-d'), $this->idUser);
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

    /**
     * Obtiene los errores acumulados
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Obtiene las advertencias acumuladas
     * @return array
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
