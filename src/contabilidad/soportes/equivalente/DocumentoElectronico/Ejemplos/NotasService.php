<?php

/**
 * Ejemplo de extensión del sistema para Notas Crédito y Débito
 * 
 * Este archivo demuestra cómo extender la arquitectura base
 * para soportar otros tipos de documentos electrónicos
 */

namespace App\DocumentoElectronico\Ejemplos;

use App\DocumentoElectronico\DocumentoElectronicoService;
use PDO;
use Exception;

/**
 * Extensión del servicio para manejar Notas Crédito y Débito
 */
class NotasService extends DocumentoElectronicoService
{
    /**
     * Envía una Nota Crédito
     * @param int $idNota ID de la nota crédito
     * @param string $referenciaFactura Referencia de la factura original
     * @param string $cufeFactura CUFE de la factura original
     * @param string $fechaFactura Fecha de la factura original
     * @param int $motivoDevolucion Código del motivo (1-6)
     * @return array Resultado del envío
     */
    public function enviarNotaCredito(
        int $idNota,
        string $referenciaFactura,
        string $cufeFactura,
        string $fechaFactura,
        int $motivoDevolucion = 1
    ): array {
        try {
            $this->conexion->beginTransaction();

            // 1. Obtener datos
            $nonce = $this->repository->getAndUpdateNonce();
            $empresa = $this->repository->getEmpresaData();
            $nota = $this->obtenerNotaData($idNota);
            $resolucion = $this->repository->getResolucion(1, 1);

            // 2. Validaciones
            $this->repository->validarResolucion($resolucion);
            $this->validarNotaCredito($nota, $referenciaFactura);

            // 3. Autenticar
            $this->taxxaService = new \App\DocumentoElectronico\TaxxaService(
                $empresa['endpoint'],
                $empresa['user_prov'],
                $empresa['pass_prov'],
                $nonce['valor']
            );
            $this->taxxaService->authenticate();

            // 4. Construir documento
            $documentData = $this->buildNotaCredito(
                $nota,
                $empresa,
                $resolucion,
                $referenciaFactura,
                $cufeFactura,
                $fechaFactura,
                $motivoDevolucion
            );

            // 5. Enviar
            $response = $this->taxxaService->sendDocument(
                $documentData,
                'prod',
                'classTaxxa.fjDocumentAdd'
            );

            // 6. Procesar respuesta
            $result = $this->procesarRespuestaVenta(
                $response,
                $idNota,
                $resolucion,
                intval($resolucion['consecutivo']),
                true
            );

            $this->taxxaService->saveLog("log_nota_credito_{$idNota}.txt");
            $this->conexion->commit();

            return $result;
        } catch (Exception $e) {
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
     * Envía una Nota Débito
     * Similar a Nota Crédito pero con tipo de documento diferente
     */
    public function enviarNotaDebito(
        int $idNota,
        string $referenciaFactura,
        string $cufeFactura,
        string $fechaFactura,
        int $motivoCargo = 1
    ): array {
        try {
            $this->conexion->beginTransaction();

            // Similar a nota crédito pero con DebitNote
            $nonce = $this->repository->getAndUpdateNonce();
            $empresa = $this->repository->getEmpresaData();
            $nota = $this->obtenerNotaData($idNota);
            $resolucion = $this->repository->getResolucion(1, 1);

            $this->repository->validarResolucion($resolucion);

            $this->taxxaService = new \App\DocumentoElectronico\TaxxaService(
                $empresa['endpoint'],
                $empresa['user_prov'],
                $empresa['pass_prov'],
                $nonce['valor']
            );
            $this->taxxaService->authenticate();

            $documentData = $this->buildNotaDebito(
                $nota,
                $empresa,
                $resolucion,
                $referenciaFactura,
                $cufeFactura,
                $fechaFactura,
                $motivoCargo
            );

            $response = $this->taxxaService->sendDocument(
                $documentData,
                'prod',
                'classTaxxa.fjDocumentAdd'
            );

            $result = $this->procesarRespuestaVenta(
                $response,
                $idNota,
                $resolucion,
                intval($resolucion['consecutivo']),
                true
            );

            $this->taxxaService->saveLog("log_nota_debito_{$idNota}.txt");
            $this->conexion->commit();

            return $result;
        } catch (Exception $e) {
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
     * Construye una Nota Crédito
     */
    private function buildNotaCredito(
        array $nota,
        array $empresa,
        array $resolucion,
        string $referenciaFactura,
        string $cufeFactura,
        string $fechaFactura,
        int $motivoDevolucion
    ): array {
        $tercero = $this->preparaTerceroData($nota);
        $secuencia = intval($resolucion['consecutivo']);

        $motivosNC = [
            1 => 'Devolución parcial de los bienes y/o no aceptación parcial del servicio',
            2 => 'Anulación de factura electrónica',
            3 => 'Rebaja total aplicada',
            4 => 'Descuento total aplicado',
            5 => 'Rescisión: nulidad por falta de requisitos',
            6 => 'Otros'
        ];

        $this->builder->reset()
            ->setDocumentType('CreditNote')
            ->setBasicInfo([
                'wdocumentsubtype' => '9',
                'wpaymentmeans' => '1',
                'wpaymentmethod' => 'ZZZ',
                'wbusinessregimen' => 1,
                'woperationtype' => '10',
                'snotes' => $motivosNC[$motivoDevolucion] ?? 'Nota Crédito',
            ])
            ->setReference($resolucion['prefijo'], $secuencia)
            ->setDates(
                date('Y-m-d'),
                date('Y-m-d', strtotime('+30 days'))
            )
            ->setSeller($empresa)
            ->setBuyer($tercero)
            ->addItem([
                'codigo' => $nota['codigo_producto'] ?? '0001',
                'detalle' => $nota['descripcion'] ?? 'Nota Crédito',
                'val_unitario' => $nota['valor_base'],
                'cantidad' => 1,
                'p_iva' => $nota['porcentaje_iva'] ?? 0,
                'val_iva' => $nota['valor_iva'] ?? 0,
                'p_dcto' => 0,
                'val_dcto' => 0,
                'nunitprice' => true,
            ])
            ->setExtraInfo([
                'jbillingreference' => [
                    'sbillingreferenceid' => $referenciaFactura,
                    'sbillingreferenceissuedate' => $fechaFactura,
                    'sbillingreferenceuuid' => $cufeFactura
                ]
            ]);

        return $this->builder->build();
    }

    /**
     * Construye una Nota Débito
     */
    private function buildNotaDebito(
        array $nota,
        array $empresa,
        array $resolucion,
        string $referenciaFactura,
        string $cufeFactura,
        string $fechaFactura,
        int $motivoCargo
    ): array {
        $tercero = $this->preparaTerceroData($nota);
        $secuencia = intval($resolucion['consecutivo']);

        $motivosND = [
            1 => 'Intereses',
            2 => 'Gastos por cobrar',
            3 => 'Cambio del valor',
            4 => 'Otros'
        ];

        $this->builder->reset()
            ->setDocumentType('DebitNote')
            ->setBasicInfo([
                'wdocumentsubtype' => '9',
                'wpaymentmeans' => '1',
                'wpaymentmethod' => 'ZZZ',
                'wbusinessregimen' => 1,
                'woperationtype' => '10',
                'snotes' => $motivosND[$motivoCargo] ?? 'Nota Débito',
            ])
            ->setReference($resolucion['prefijo'], $secuencia)
            ->setDates(
                date('Y-m-d'),
                date('Y-m-d', strtotime('+30 days'))
            )
            ->setSeller($empresa)
            ->setBuyer($tercero)
            ->addItem([
                'codigo' => $nota['codigo_producto'] ?? '0001',
                'detalle' => $nota['descripcion'] ?? 'Nota Débito',
                'val_unitario' => $nota['valor_base'],
                'cantidad' => 1,
                'p_iva' => $nota['porcentaje_iva'] ?? 0,
                'val_iva' => $nota['valor_iva'] ?? 0,
                'p_dcto' => 0,
                'val_dcto' => 0,
                'nunitprice' => true,
            ])
            ->setExtraInfo([
                'jbillingreference' => [
                    'sbillingreferenceid' => $referenciaFactura,
                    'sbillingreferenceissuedate' => $fechaFactura,
                    'sbillingreferenceuuid' => $cufeFactura
                ]
            ]);

        return $this->builder->build();
    }

    /**
     * Obtiene datos de la nota desde BD
     */
    private function obtenerNotaData(int $idNota): array
    {
        // Implementar según estructura de BD
        $sql = "SELECT * FROM notas WHERE id_nota = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':id' => $idNota]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Prepara datos del tercero
     */
    private function preparaTerceroData(array $nota): array
    {
        return [
            'tipo_org' => 1,
            'resp_fiscal' => 'R-99-PN',
            'reg_fiscal' => 1,
            'codigo_ne' => 'CC',
            'no_doc' => $nota['nit_tercero'],
            'nombre' => $nota['nom_tercero'],
            'correo' => $nota['email'],
            'telefono' => $nota['telefono'],
            'codigo_pais' => 'CO',
            'codigo_dpto' => $nota['codigo_departamento'],
            'nom_departamento' => $nota['nom_departamento'],
            'codigo_municipio' => $nota['codigo_municipio'],
            'nom_municipio' => $nota['nom_municipio'],
            'cod_postal' => $nota['cod_postal'],
            'direccion' => $nota['direccion'],
            'wprovincecode' => $nota['codigo_departamento'] . $nota['codigo_municipio'],
        ];
    }

    /**
     * Valida que la nota crédito sea válida
     */
    private function validarNotaCredito(array $nota, string $referenciaFactura): void
    {
        if (empty($referenciaFactura)) {
            throw new Exception("Debe especificar la referencia de la factura original");
        }

        if ($nota['valor_base'] <= 0) {
            throw new Exception("El valor de la nota debe ser mayor a cero");
        }

        // Más validaciones según reglas de negocio
    }
}

// ============================================================================
// EJEMPLO DE USO
// ============================================================================

/*

// Crear servicio extendido
$notasService = new NotasService($conexion, $_SESSION['id_user']);

// Enviar Nota Crédito
$resultado = $notasService->enviarNotaCredito(
    idNota: 123,
    referenciaFactura: 'FV-1001',
    cufeFactura: 'abc123def456...',
    fechaFactura: '2025-12-01',
    motivoDevolucion: 1
);

if ($resultado['value'] === 'ok') {
    echo "Nota Crédito enviada correctamente";
} else {
    echo "Error: " . $resultado['msg'];
}

// Enviar Nota Débito
$resultado = $notasService->enviarNotaDebito(
    idNota: 124,
    referenciaFactura: 'FV-1001',
    cufeFactura: 'abc123def456...',
    fechaFactura: '2025-12-01',
    motivoCargo: 1
);

*/
