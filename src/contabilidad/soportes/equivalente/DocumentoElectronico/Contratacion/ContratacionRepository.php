<?php

namespace App\DocumentoElectronico\Contratacion;

use App\DocumentoElectronico\DocumentRepository;
use PDO;
use Exception;

/**
 * Repository extendido para documentos de contratación (no obligados)
 * Extiende el repository base con métodos específicos para contratación
 */
class ContratacionRepository extends DocumentRepository
{
    /**
     * Obtiene los datos del documento de contratación (no obligado)
     * @param int $idDoc ID del documento
     * @return array Datos del documento de contratación
     * @throws Exception
     */
    public function getDocumentoContratacion(int $idDoc): array
    {
        try {
            $sql = "SELECT
                        `ctt_fact_noobligado`.`id_facturano` AS `id_ctb_doc`
                        , `ctt_fact_noobligado`.`id_tercero_no` AS `id_tercero`
                        , `ctt_fact_noobligado`.`fec_compra` AS `fecha_fact`
                        , `ctt_fact_noobligado`.`fec_vence` AS `fecha_ven`
                        , `ctt_fact_noobligado`.`observaciones` AS `detalle`
                        , `ctt_fact_noobligado`.`observaciones` AS `nota`
                        , `ctt_fact_noobligado`.`met_pago` AS `met_pago`
                        , `ctt_fact_noobligado`.`forma_pago` AS `forma_pago`
                        , `tb_terceros`.`nit_tercero`
                        , `tb_terceros`.`nom_tercero`
                        , `tb_terceros`.`email`
                        , `tb_terceros`.`tel_tercero`
                        , `tb_municipios`.`codigo_municipio`
                        , `tb_municipios`.`nom_municipio`
                        , `tb_municipios`.`cod_postal`
                        , `tb_departamentos`.`codigo_departamento`
                        , `tb_departamentos`.`nom_departamento`
                        , `tb_terceros`.`dir_tercero`
                        , `tb_terceros`.`procedencia`
                        , `tb_terceros`.`tipo_org`
                        , `tb_terceros`.`reg_fiscal`
                        , `tb_terceros`.`resp_fiscal`
                        , `tb_tipos_documento`.`codigo_ne` AS `codigo_ne`
                    FROM
                        `ctt_fact_noobligado`
                        INNER JOIN `tb_terceros`
                            ON (`ctt_fact_noobligado`.`id_tercero_no` = `tb_terceros`.`id_tercero_api`)
                        INNER JOIN `tb_municipios`
                            ON (`tb_terceros`.`id_municipio` = `tb_municipios`.`id_municipio`)
                        INNER JOIN `tb_departamentos`
                            ON (`tb_municipios`.`id_departamento` = `tb_departamentos`.`id_departamento`)
                        LEFT JOIN `tb_tipos_documento`
                            ON (`tb_terceros`.`tipo_doc` = `tb_tipos_documento`.`id_tipodoc`)
                    WHERE (`ctt_fact_noobligado`.`id_facturano` = :id) LIMIT 1";

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([':id' => $idDoc]);
            $documento = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$documento) {
                throw new Exception("No se encontró el documento de contratación con ID: {$idDoc}");
            }

            return $documento;
        } catch (\PDOException $e) {
            throw new Exception("Error al obtener datos del documento de contratación: " . $e->getMessage());
        }
    }

    /**
     * Obtiene los detalles (ítems) del documento de contratación
     * @param int $idDoc ID del documento
     * @return array Array de ítems
     */
    public function getDetallesContratacion(int $idDoc): array
    {
        try {
            $sql = "SELECT
                        `codigo`, `detalle`, `val_unitario`, `cantidad`, 
                        `p_iva`, `val_iva`, `p_dcto`, `val_dcto`
                    FROM
                        `ctt_fact_noobligado_det`
                    WHERE (`id_fno` = :id)";

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([':id' => $idDoc]);
            $detalles = $stmt->fetchAll();

            return $detalles ?: [];
        } catch (\PDOException $e) {
            throw new Exception("Error al obtener detalles: " . $e->getMessage());
        }
    }

    /**
     * Busca si ya existe un soporte para este documento de contratación
     * Usa tipo=1 para documentos de contratación
     * @param int $idDoc ID del documento
     * @return array|null Datos del soporte o null
     */
    public function getSoporteContratacion(int $idDoc): ?array
    {
        try {
            $sql = "SELECT `id_soporte`, `referencia` 
                    FROM `seg_soporte_fno` 
                    WHERE `id_factura_no` = :id 
                    AND `tipo` = 1
                    LIMIT 1";

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([':id' => $idDoc]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    /**
     * Crea un nuevo registro de soporte para contratación (tipo=1)
     * @param int $idDoc ID del documento
     * @param string $referencia Referencia
     * @param string $fecha Fecha
     * @param int $idUser ID del usuario
     * @return int ID del soporte creado
     * @throws Exception
     */
    public function crearSoporteContratacion(int $idDoc, string $referencia, string $fecha, int $idUser): int
    {
        try {
            $sql = "INSERT INTO `seg_soporte_fno` 
                        (`id_factura_no`, `referencia`, `fecha`, `id_user_reg`, `fec_reg`, `tipo`) 
                    VALUES (:id_doc, :referencia, :fecha, :id_user, :fec_reg, 1)";

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                ':id_doc' => $idDoc,
                ':referencia' => $referencia,
                ':fecha' => $fecha,
                ':id_user' => $idUser,
                ':fec_reg' => date('Y-m-d H:i:s')
            ]);

            $id = $this->conexion->lastInsertId();

            if (!$id) {
                throw new Exception("No se pudo registrar el soporte de contratación");
            }

            return $id;
        } catch (\PDOException $e) {
            throw new Exception("Error al crear soporte de contratación: " . $e->getMessage());
        }
    }
}
