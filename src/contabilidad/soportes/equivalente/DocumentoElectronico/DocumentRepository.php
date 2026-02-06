<?php

namespace App\DocumentoElectronico;

use PDO;
use Exception;
use Src\Common\Php\Clases\Valores;

/**
 * Repository para manejar datos relacionados con documentos electrónicos
 * Encapsula todas las operaciones de base de datos
 */
class DocumentRepository
{
    private $conexion;

    /**
     * Constructor
     * @param PDO $conexion Conexión a la base de datos
     */
    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    /**
     * Obtiene y actualiza el nonce para facturación
     * @return array [valor, id]
     * @throws Exception
     */
    public function getAndUpdateNonce(): array
    {
        try {
            $sql = "SELECT 
                        `id_valxvig`, `id_concepto`, `valor`,`concepto`
                    FROM
                        `nom_valxvigencia`
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

            // Actualizar el nonce
            $sql = "UPDATE `nom_valxvigencia` SET `valor` = :valor WHERE `id_valxvig` = :id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                ':valor' => $iNonce + 1,
                ':id' => $idiNonce
            ]);

            return ['valor' => $iNonce, 'id' => $idiNonce];
        } catch (\PDOException $e) {
            throw new Exception("Error al obtener Nonce: " . $e->getMessage());
        }
    }

    /**
     * Obtiene los datos de la empresa
     * @return array Datos de la empresa
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
                        , `tb_departamentos`.`codigo_departamento` AS `codigo_dpto`
                        , `tb_departamentos`.`nom_departamento` AS `nom_departamento`
                        , `tb_departamentos`.`nom_departamento` AS `nombre_dpto`
                        , `tb_municipios`.`codigo_municipio`
                        , `tb_municipios`.`nom_municipio`
                        , `tb_municipios`.`cod_postal`
                        , `tb_datos_ips`.`direccion_ips` AS `direccion`
                        , `tb_datos_ips`.`url_taxxa` AS `endpoint`
                        , '2' AS `tipo_organizacion`
                        , 'R-99-PN' AS `resp_fiscal`
                        , '2' AS `reg_fiscal`
                        , `tb_datos_ips`.`sEmail` AS `user_prov`
                        , `tb_datos_ips`.`sPass` AS `pass_prov`
                    FROM
                        `tb_datos_ips`
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
     * Obtiene los datos de la factura/documento contable
     * @param int $idDoc ID del documento
     * @param bool $isVenta Si es factura de venta (true) o soporte (false)
     * @return array Datos del documento
     * @throws Exception
     */
    public function getDocumentoData(int $idDoc, bool $isVenta = false): array
    {
        try {
            $sql = "SELECT
                        `ctb_doc`.`id_ctb_doc`
                        , `ctb_doc`.`id_tercero`
                        " . ($isVenta ? ", `ctb_doc`.`id_manu`, `ctb_doc`.`id_ref_ctb`" : "") . "
                        , `ctb_factura`.`fecha_fact`
                        , `ctb_factura`.`fecha_ven`
                        , `ctb_factura`.`valor_pago`
                        , `ctb_factura`.`valor_iva`
                        , `ctb_factura`.`valor_base`
                        , `ctb_doc`.`detalle`
                        , `ctb_factura`.`detalle` AS `nota`
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
                        " . ($isVenta ? ", `ctb_referencia`.`nombre` AS `nom_ref`" : "") . "
                    FROM
                        `ctb_factura`
                        INNER JOIN `ctb_doc` 
                            ON (`ctb_factura`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                        INNER JOIN `tb_terceros`
                            ON (`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
                        INNER JOIN `tb_municipios`
                            ON (`tb_terceros`.`id_municipio` = `tb_municipios`.`id_municipio`)
                        INNER JOIN `tb_departamentos`
                            ON (`tb_municipios`.`id_departamento` = `tb_departamentos`.`id_departamento`)
                        " . ($isVenta ? "LEFT JOIN `ctb_referencia` ON (`ctb_doc`.`id_ref_ctb` = `ctb_referencia`.`id_ctb_referencia`)" : "") . "
                    WHERE (`ctb_doc`.`id_ctb_doc` = :id) LIMIT 1";

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([':id' => $idDoc]);
            $documento = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$documento) {
                throw new Exception("No se encontró el documento con ID: {$idDoc}");
            }

            return $documento;
        } catch (\PDOException $e) {
            throw new Exception("Error al obtener datos del documento: " . $e->getMessage());
        }
    }

    /**
     * Obtiene el código UNSPSC para documento soporte
     * @param int $idDoc ID del documento
     * @return string Código UNSPSC
     */
    public function getUNSPSC(int $idDoc): string
    {
        $config = Valores::getOwnerConfig();

        try {
            $sql = "SELECT
                        `ctt_clasificacion_bn_sv`.`cod_unspsc` AS `id_unspsc`
                    FROM
                        `ctb_factura`
                        INNER JOIN `ctb_doc` 
                            ON (`ctb_factura`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                        INNER JOIN `pto_crp` 
                            ON (`ctb_doc`.`id_crp` = `pto_crp`.`id_pto_crp`)
                        INNER JOIN `pto_cdp` 
                            ON (`pto_crp`.`id_cdp` = `pto_cdp`.`id_pto_cdp`)
                        INNER JOIN `ctt_adquisiciones` 
                            ON (`ctt_adquisiciones`.`id_cdp` = `pto_cdp`.`id_pto_cdp`)
                        INNER JOIN `ctt_adquisicion_detalles` 
                            ON (`ctt_adquisicion_detalles`.`id_adquisicion` = `ctt_adquisiciones`.`id_adquisicion`)
                        INNER JOIN `ctt_clasificacion_bn_sv` 
                            ON (`ctt_adquisicion_detalles`.`id_bn_sv` = `ctt_clasificacion_bn_sv`.`id_b_s`)
                    WHERE (`ctb_factura`.`id_ctb_doc` = :id) LIMIT 1";

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([':id' => $idDoc]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Por defecto retornar código genérico si no existe
            return $result['id_unspsc'] ?? $config['codigo_unsp'];
        } catch (\PDOException $e) {
            // En caso de error, retornar código genérico
            return $config['codigo_unsp'];
        }
    }

    /**
     * Obtiene la resolución de facturación activa
     * @param int $idEmpresa ID de la empresa
     * @param int $tipo Tipo de documento (1=venta, 2=soporte)
     * @return array Datos de la resolución
     * @throws Exception
     */
    public function getResolucion(int $idEmpresa, int $tipo = 2): array
    {
        try {
            if ($tipo === 1) {
                // Para facturas de venta, los datos vienen de tb_datos_ips
                $sql = "SELECT 
                            1 AS `id_resol`
                            , 1 As `id_empresa`
                            , `resolucion_edian` AS `no_resol`
                            , `prefijo_edian` AS `prefijo`
                            , `num_efacturactual` AS `consecutivo`
                            , `num_efacturafin` AS `fin_concecutivo`
                            , `fec_inicio_res` AS `fec_inicia`
                            , `fec_vence_res` AS `fec_termina`
                            , 1 AS `tipo`
                            , 'prod' AS `entorno`
                        FROM `tb_datos_ips`";
            } else {
                // Para documentos soporte
                $sql = "SELECT
                            `id_resol`, `id_empresa`, `no_resol`, `prefijo`, `consecutivo`, 
                            `fin_concecutivo`, `fec_inicia`, `fec_termina`, `tipo`, `entorno`
                        FROM
                            `nom_resoluciones`
                        WHERE `id_resol` = (
                            SELECT MAX(`id_resol`) 
                            FROM `nom_resoluciones` 
                            WHERE `id_empresa` = :id_empresa AND `tipo` = :tipo
                        )";
            }
            $stmt = $this->conexion->prepare($sql);
            if ($tipo !== 1) {
                $stmt->execute([':id_empresa' => $idEmpresa, ':tipo' => $tipo]);
            } else {
                $stmt->execute();
            }
            $resolucion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resolucion || empty($resolucion['prefijo'])) {
                throw new Exception("No se ha registrado una resolución de facturación");
            }

            return $resolucion;
        } catch (\PDOException $e) {
            throw new Exception("Error al obtener resolución: " . $e->getMessage());
        }
    }

    /**
     * Valida que la resolución esté vigente y tenga consecutivos disponibles
     * @param array $resolucion Datos de la resolución
     * @throws Exception Si la resolución no es válida
     */
    public function validarResolucion(array $resolucion): void
    {
        $fechaActual = strtotime(date('Y-m-d H:i:s'));
        $fechaMax = strtotime($resolucion['fec_termina']);

        if ($fechaActual > $fechaMax) {
            throw new Exception("La fecha máxima de emisión de la resolución ha expirado");
        }

        $secuencia = intval($resolucion['consecutivo']);
        if ($secuencia > $resolucion['fin_concecutivo']) {
            throw new Exception("La secuencia de la resolución ha llegado al consecutivo máximo autorizado");
        }
    }

    /**
     * Busca si ya existe un soporte para este documento
     * @param int $idDoc ID del documento
     * @param int $tipo Tipo (0=venta, otro=soporte)
     * @return array|null Datos del soporte o null
     */
    public function getSoporteExistente(int $idDoc, int $tipo = 0): ?array
    {
        try {
            $sql = "SELECT `id_soporte`, `referencia` 
                    FROM `seg_soporte_fno` 
                    WHERE `id_factura_no` = :id";

            if ($tipo === 0) {
                $sql .= " AND `tipo` = 0";
            }

            $sql .= " LIMIT 1";

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([':id' => $idDoc]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    /**
     * Crea un nuevo registro de soporte
     * @param int $idDoc ID del documento
     * @param string $referencia Referencia
     * @param string $fecha Fecha
     * @param int $idUser ID del usuario
     * @return int ID del soporte creado
     * @throws Exception
     */
    public function crearSoporte(int $idDoc, string $referencia, string $fecha, int $idUser): int
    {
        try {
            $sql = "INSERT INTO `seg_soporte_fno` 
                        (`id_factura_no`, `referencia`, `fecha`, `id_user_reg`, `fec_reg`) 
                    VALUES (:id_doc, :referencia, :fecha, :id_user, :fec_reg)";

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
                throw new Exception("No se pudo registrar el soporte de la factura");
            }

            return $id;
        } catch (\PDOException $e) {
            throw new Exception("Error al crear soporte: " . $e->getMessage());
        }
    }

    /**
     * Actualiza un soporte existente con hash y referencia
     * @param int $idSoporte ID del soporte
     * @param string $hash CUFE/Hash
     * @param string $referencia Referencia
     * @param int $idDoc ID del documento
     * @param int $idUser ID del usuario
     * @return bool True si se actualizó
     * @throws Exception
     */
    public function actualizarSoporte(int $idSoporte, string $hash, string $referencia, int $idDoc, int $idUser): bool
    {
        try {
            $sql = "UPDATE `seg_soporte_fno` 
                    SET `id_factura_no` = :id_doc,
                        `shash` = :hash, 
                        `referencia` = :referencia, 
                        `fecha` = :fecha, 
                        `id_user_reg` = :id_user, 
                        `fec_reg` = :fec_reg 
                    WHERE `id_soporte` = :id_soporte";

            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute([
                ':id_doc' => $idDoc,
                ':hash' => $hash,
                ':referencia' => $referencia,
                ':fecha' => date('Y-m-d'),
                ':id_user' => $idUser,
                ':fec_reg' => date('Y-m-d H:i:s'),
                ':id_soporte' => $idSoporte
            ]);
        } catch (\PDOException $e) {
            throw new Exception("Error al actualizar soporte: " . $e->getMessage());
        }
    }

    /**
     * Actualiza el consecutivo de la resolución
     * @param int $idResolucion ID de la resolución
     * @param int $nuevoConsecutivo Nuevo consecutivo
     * @param bool $isVenta Si es factura de venta
     * @return bool True si se actualizó
     * @throws Exception
     */
    public function actualizarConsecutivo(int $idResolucion, int $nuevoConsecutivo, bool $isVenta = false): bool
    {
        try {
            if ($isVenta) {
                $sql = "UPDATE `tb_datos_ips` SET `num_efacturactual` = :consecutivo";
                $params = [':consecutivo' => $nuevoConsecutivo];
            } else {
                $sql = "UPDATE `nom_resoluciones` SET `consecutivo` = :consecutivo WHERE `id_resol` = :id";
                $params = [':consecutivo' => $nuevoConsecutivo, ':id' => $idResolucion];
            }

            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            throw new Exception("Error al actualizar consecutivo: " . $e->getMessage());
        }
    }

    /**
     * Actualiza el número de documento en la factura
     * @param int $idDoc ID del documento
     * @param string $numero Número completo (prefijo-consecutivo)
     * @return bool True si se actualizó
     * @throws Exception
     */
    public function actualizarNumeroFactura(int $idDoc, string $numero): bool
    {
        try {
            $sql = "UPDATE `ctb_factura` SET `num_doc` = :numero WHERE `id_ctb_doc` = :id";
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute([':numero' => $numero, ':id' => $idDoc]);
        } catch (\PDOException $e) {
            throw new Exception("Error al actualizar número de factura: " . $e->getMessage());
        }
    }

    /**
     * Limpia soportes sin referencia válida
     * @param string $prefijo Prefijo de referencia
     */
    public function limpiarSoportesInvalidos(string $prefijo): void
    {
        try {
            $sql = "DELETE FROM `seg_soporte_fno` 
                    WHERE (`referencia` IS NULL OR `referencia` = :referencia)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([':referencia' => $prefijo . '-0']);
        } catch (\PDOException $e) {
            // No lanzar excepción, es opcional
        }
    }
}
