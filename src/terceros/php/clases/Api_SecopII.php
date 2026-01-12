<?php

namespace Src\Terceros\Php\Clases;

use Config\Clases\Conexion;
use Config\Clases\Sesion;
use Config\Clases\Logs;

use PDO;
use PDOException;

/**
 * Clase para interactuar con la API de SECOP II
 * 
 * Esta clase proporciona métodos para consultar información de contratos
 * desde el portal de datos abiertos del gobierno colombiano (datos.gov.co)
 */
class Api_SecopII
{
    private $conexion;

    /**
     * URL base del dataset de Contratos Electrónicos SECOP II
     */
    private const API_URL = "https://www.datos.gov.co/resource/jbjy-vk9h.json";

    /**
     * Token de la aplicación para autenticación (opcional)
     */
    private $appToken = null;

    /**
     * Constructor de la clase
     *
     * @param PDO|null $conexion Conexión a la base de datos
     */
    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion();
    }

    /**
     * Establece el token de la aplicación para autenticación con la API
     *
     * @param string $token Token de la aplicación
     * @return void
     */
    public function setAppToken(string $token): void
    {
        $this->appToken = $token;
    }

    /**
     * Consulta un contrato en SECOP II por su ID
     *
     * @param string $idContrato ID del contrato a consultar (ej: "CO1.PCCNTR.7198442")
     * @param string|null $campo Campo específico a retornar (ej: 'urlproceso', 'nombre_entidad'). Si es null retorna todos los datos.
     * @return mixed Retorna los datos del contrato, el valor del campo específico, o un array con el error
     */
    public function consultarContratoPorId(string $idContrato, ?string $campo = null)
    {
        $params = http_build_query([
            "id_contrato" => $idContrato
        ]);

        $fullUrl = self::API_URL . "?" . $params;

        $resultado = $this->ejecutarConsulta($fullUrl);

        // Si se especificó un campo y no hay error, retornar solo ese campo
        if ($campo !== null && !isset($resultado['error'])) {
            if (isset($resultado[$campo])) {
                return $resultado[$campo];
            } else {
                return ['error' => "El campo '$campo' no existe en la respuesta"];
            }
        }

        return $resultado;
    }

    /**
     * Consulta contratos en SECOP II por número de documento del proveedor
     *
     * @param string $nit Número de identificación del proveedor
     * @return array Retorna los contratos encontrados o un array con el error
     */
    public function consultarContratosPorNit(string $nit): array
    {
        $params = http_build_query([
            "nit_del_proveedor_adjudicado" => $nit
        ]);

        $fullUrl = self::API_URL . "?" . $params;

        return $this->ejecutarConsulta($fullUrl, false);
    }

    /**
     * Consulta contratos en SECOP II por nombre de la entidad
     *
     * @param string $nombreEntidad Nombre de la entidad contratante
     * @param int $limit Número máximo de resultados a retornar
     * @return array Retorna los contratos encontrados o un array con el error
     */
    public function consultarContratosPorEntidad(string $nombreEntidad, int $limit = 50): array
    {
        $params = http_build_query([
            "\$where" => "nombre_entidad like '%" . $nombreEntidad . "%'",
            "\$limit" => $limit
        ]);

        $fullUrl = self::API_URL . "?" . $params;

        return $this->ejecutarConsulta($fullUrl, false);
    }

    /**
     * Consulta contratos en SECOP II con filtros personalizados
     *
     * @param array $filtros Array asociativo con los filtros a aplicar
     * @param int $limit Número máximo de resultados a retornar
     * @param int $offset Número de registros a saltar (para paginación)
     * @return array Retorna los contratos encontrados o un array con el error
     */
    public function consultarContratosConFiltros(array $filtros, int $limit = 50, int $offset = 0): array
    {
        $filtros["\$limit"] = $limit;
        $filtros["\$offset"] = $offset;

        $params = http_build_query($filtros);
        $fullUrl = self::API_URL . "?" . $params;

        return $this->ejecutarConsulta($fullUrl, false);
    }

    /**
     * Ejecuta la consulta HTTP a la API de SECOP II
     *
     * @param string $url URL completa para la consulta
     * @param bool $returnFirst Si es true, retorna solo el primer resultado
     * @return array Datos de la consulta o error
     */
    private function ejecutarConsulta(string $url, bool $returnFirst = true): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // Headers de la petición
        $headers = [
            "Accept: application/json",
        ];

        // Agregar token si está disponible
        if ($this->appToken !== null) {
            $headers[] = "X-App-Token: " . $this->appToken;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Ejecución
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['error' => 'Error en cURL: ' . $error];
        }

        curl_close($ch);

        // Procesamiento de la respuesta
        if ($httpCode === 200) {
            $data = json_decode($response, true);

            if (!empty($data)) {
                return $returnFirst ? $data[0] : $data;
            } else {
                return ['error' => 'No se encontró información para los parámetros suministrados'];
            }
        } else {
            return ['error' => "Error del servidor (Código $httpCode): " . $response];
        }
    }

    /**
     * Guarda la información de un contrato de SECOP II en la base de datos local
     *
     * @param array $contrato Datos del contrato obtenidos de la API
     * @return string Retorna 'si' si se guardó correctamente, mensaje de error en caso contrario
     */
    public function guardarContrato(array $contrato): string
    {
        try {
            // Verificar si el contrato ya existe
            $existe = $this->existeContrato($contrato['id_contrato'] ?? '');
            if ($existe) {
                return 'El contrato ya existe en la base de datos local.';
            }

            $sql = "INSERT INTO `tb_contratos_secop` 
                        (`id_contrato`, `nombre_entidad`, `nit_entidad`, `objeto_contrato`, 
                         `tipo_contrato`, `modalidad_contratacion`, `valor_contrato`, 
                         `nit_proveedor`, `nombre_proveedor`, `fecha_firma`, 
                         `fecha_inicio`, `fecha_fin`, `estado_contrato`, 
                         `id_user_reg`, `fec_reg`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $contrato['id_contrato'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(2, $contrato['nombre_entidad'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(3, $contrato['nit_de_la_entidad'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(4, $contrato['objeto_del_contrato'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(5, $contrato['tipo_de_contrato'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(6, $contrato['modalidad_de_contratacion'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(7, $contrato['valor_del_contrato'] ?? 0, PDO::PARAM_STR);
            $stmt->bindValue(8, $contrato['nit_del_proveedor_adjudicado'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(9, $contrato['proveedor_adjudicado'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(10, $contrato['fecha_de_firma'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(11, $contrato['fecha_de_inicio_del_contrato'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(12, $contrato['fecha_de_fin_del_contrato'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(13, $contrato['estado_contrato'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(14, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(15, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->execute();

            $id = $this->conexion->lastInsertId();
            if ($id > 0) {
                Logs::guardaLog("INSERT INTO tb_contratos_secop (id_contrato: {$contrato['id_contrato']})");
                return 'si';
            } else {
                return 'No se insertó el registro';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    /**
     * Verifica si un contrato ya existe en la base de datos local
     *
     * @param string $idContrato ID del contrato a verificar
     * @return bool True si existe, false en caso contrario
     */
    public function existeContrato(string $idContrato): bool
    {
        try {
            $sql = "SELECT COUNT(*) AS `total` FROM `tb_contratos_secop` WHERE `id_contrato` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $idContrato, PDO::PARAM_STR);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            unset($stmt);
            return ($data['total'] ?? 0) > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Obtiene un contrato de la base de datos local por su ID
     *
     * @param string $idContrato ID del contrato
     * @return array Datos del contrato o array vacío
     */
    public function getContratoLocal(string $idContrato): array
    {
        try {
            $sql = "SELECT * FROM `tb_contratos_secop` WHERE `id_contrato` = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $idContrato, PDO::PARAM_STR);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            unset($stmt);
            return $data ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Obtiene los contratos guardados localmente para un DataTable
     *
     * @param int $start Índice de inicio para la paginación
     * @param int $length Número de registros a mostrar
     * @param string $val_busca Valor de búsqueda
     * @param string $col Columna para ordenar
     * @param string $dir Dirección de ordenamiento (asc/desc)
     * @return array Registros encontrados
     */
    public function getContratosDT(int $start, int $length, string $val_busca, string $col, string $dir): array
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "WHERE (`id_contrato` LIKE '%$val_busca%' 
                        OR `nombre_entidad` LIKE '%$val_busca%' 
                        OR `objeto_contrato` LIKE '%$val_busca%' 
                        OR `nombre_proveedor` LIKE '%$val_busca%'
                        OR `nit_proveedor` LIKE '%$val_busca%')";
        }

        $limit = "";
        if ($length != -1) {
            $limit = "LIMIT $start, $length";
        }

        $sql = "SELECT * FROM `tb_contratos_secop` $where ORDER BY $col $dir $limit";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
        return $datos ?: [];
    }

    /**
     * Obtiene el total de registros filtrados para paginación DataTable
     *
     * @param string $val_busca Valor de búsqueda
     * @return int Total de registros filtrados
     */
    public function getRegistrosFilter(string $val_busca): int
    {
        $where = '';
        if ($val_busca != '') {
            $val_busca = trim($val_busca);
            $where = "WHERE (`id_contrato` LIKE '%$val_busca%' 
                        OR `nombre_entidad` LIKE '%$val_busca%' 
                        OR `objeto_contrato` LIKE '%$val_busca%' 
                        OR `nombre_proveedor` LIKE '%$val_busca%'
                        OR `nit_proveedor` LIKE '%$val_busca%')";
        }

        $sql = "SELECT COUNT(*) AS `total` FROM `tb_contratos_secop` $where";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        $stmt->closeCursor();
        unset($stmt);
        return (int)$data;
    }

    /**
     * Obtiene el total de registros de contratos guardados localmente
     *
     * @return int Total de registros
     */
    public function getRegistrosTotal(): int
    {
        $sql = "SELECT COUNT(*) AS `total` FROM `tb_contratos_secop`";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        $stmt->closeCursor();
        unset($stmt);
        return (int)$data;
    }

    /**
     * Elimina un contrato de la base de datos local
     *
     * @param int $id ID del registro en la base de datos local
     * @return string Retorna 'si' si se eliminó correctamente, mensaje de error en caso contrario
     */
    public function eliminarContrato(int $id): string
    {
        try {
            $sql = "DELETE FROM `tb_contratos_secop` WHERE `id` = ?";
            $consulta = "DELETE FROM `tb_contratos_secop` WHERE `id` = $id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                Logs::guardaLog($consulta);
                return 'si';
            } else {
                return 'No se eliminó el registro';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    /**
     * Actualiza la información de un contrato desde la API de SECOP II
     *
     * @param string $idContrato ID del contrato a actualizar
     * @return string Retorna 'si' si se actualizó correctamente, mensaje de error en caso contrario
     */
    public function actualizarContratoDesdeApi(string $idContrato): string
    {
        // Consultar la API
        $contrato = $this->consultarContratoPorId($idContrato);

        if (isset($contrato['error'])) {
            return $contrato['error'];
        }

        try {
            $sql = "UPDATE `tb_contratos_secop` 
                    SET `nombre_entidad` = ?, 
                        `nit_entidad` = ?, 
                        `objeto_contrato` = ?, 
                        `tipo_contrato` = ?, 
                        `modalidad_contratacion` = ?, 
                        `valor_contrato` = ?, 
                        `nit_proveedor` = ?, 
                        `nombre_proveedor` = ?, 
                        `fecha_firma` = ?, 
                        `fecha_inicio` = ?, 
                        `fecha_fin` = ?, 
                        `estado_contrato` = ?,
                        `id_user_act` = ?,
                        `fec_act` = ?
                    WHERE `id_contrato` = ?";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(1, $contrato['nombre_entidad'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(2, $contrato['nit_de_la_entidad'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(3, $contrato['objeto_del_contrato'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(4, $contrato['tipo_de_contrato'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(5, $contrato['modalidad_de_contratacion'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(6, $contrato['valor_del_contrato'] ?? 0, PDO::PARAM_STR);
            $stmt->bindValue(7, $contrato['nit_del_proveedor_adjudicado'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(8, $contrato['proveedor_adjudicado'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(9, $contrato['fecha_de_firma'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(10, $contrato['fecha_de_inicio_del_contrato'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(11, $contrato['fecha_de_fin_del_contrato'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(12, $contrato['estado_contrato'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(13, Sesion::IdUser(), PDO::PARAM_INT);
            $stmt->bindValue(14, Sesion::Hoy(), PDO::PARAM_STR);
            $stmt->bindValue(15, $idContrato, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return 'si';
            } else {
                return 'No se realizó ningún cambio';
            }
        } catch (PDOException $e) {
            return 'Error SQL: ' . $e->getMessage();
        }
    }

    /**
     * Consulta y guarda un contrato directamente desde la API de SECOP II
     *
     * @param string $idContrato ID del contrato a consultar y guardar
     * @return array Retorna los datos del contrato guardado o error
     */
    public function importarContrato(string $idContrato): array
    {
        // Consultar la API
        $contrato = $this->consultarContratoPorId($idContrato);

        if (isset($contrato['error'])) {
            return $contrato;
        }

        // Guardar en la base de datos local
        $resultado = $this->guardarContrato($contrato);

        if ($resultado === 'si') {
            return [
                'success' => true,
                'mensaje' => 'Contrato importado correctamente',
                'contrato' => $contrato
            ];
        } else {
            return [
                'success' => false,
                'error' => $resultado
            ];
        }
    }

    /**
     * Formatea los valores monetarios del contrato
     *
     * @param float|string $valor Valor a formatear
     * @return string Valor formateado
     */
    public static function formatearValor($valor): string
    {
        return '$' . number_format((float)$valor, 0, ',', '.');
    }

    /**
     * Formatea una fecha ISO a formato legible
     *
     * @param string|null $fecha Fecha en formato ISO
     * @return string Fecha formateada (d/m/Y)
     */
    public static function formatearFecha(?string $fecha): string
    {
        if (empty($fecha)) {
            return 'N/A';
        }

        try {
            $dt = new \DateTime($fecha);
            return $dt->format('d/m/Y');
        } catch (\Exception $e) {
            return $fecha;
        }
    }
}
