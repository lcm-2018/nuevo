<?php

namespace App\DocumentoElectronico;

use Exception;

/**
 * Servicio para interactuar con la API de Taxxa
 * Maneja autenticación, envío de documentos y consultas
 */
class TaxxaService
{
    private $endpoint;
    private $email;
    private $password;
    private $iNonce;
    private $token = null;
    private $lastResponse = null;
    private $lastRequest = null;

    /**
     * Constructor
     * @param string $endpoint URL del servicio Taxxa
     * @param string $email Email de autenticación
     * @param string $password Contraseña de autenticación
     * @param int $iNonce Nonce actual
     */
    public function __construct(string $endpoint, string $email, string $password, int $iNonce)
    {
        $this->endpoint = $endpoint;
        $this->email = $email;
        $this->password = $password;
        $this->iNonce = $iNonce;
    }

    /**
     * Genera y obtiene el token de autenticación
     * @return string Token de autenticación
     * @throws Exception Si no se puede obtener el token
     */
    public function authenticate(): string
    {
        $payload = [
            "iNonce" => $this->iNonce,
            "jApi" => [
                "sMethod" => "classTaxxa.fjTokenGenerate",
                "jParams" => [
                    "sEmail" => $this->email,
                    "sPass" => $this->password,
                ]
            ]
        ];

        $response = $this->sendRequest($payload);

        if (!isset($response->jret->stoken)) {
            throw new Exception("No se pudo obtener el token de autenticación");
        }

        $this->token = $response->jret->stoken;
        return $this->token;
    }

    /**
     * Envía un documento electrónico a Taxxa
     * @param array $document Datos del documento
     * @param string $environment Entorno (prod/pruebas)
     * @param string $method Método API a utilizar
     * @return array Respuesta del servicio
     * @throws Exception Si el token no está generado
     */
    public function sendDocument(array $document, string $environment = 'prod', string $method = 'classTaxxa.fjDocumentAdd'): array
    {
        if (!$this->token) {
            throw new Exception("Debe autenticarse antes de enviar documentos");
        }

        $payload = [
            "sToken" => $this->token,
            "iNonce" => $this->iNonce,
            'jApi' => [
                'sMethod' => $method,
                'jParams' => [
                    'sEnvironment' => $environment,
                    'jDocument' => $document,
                ]
            ],
        ];

        // Para facturas de venta, agregar parámetros adicionales
        if ($method === 'classTaxxa.fjDocumentAdd') {
            $payload["wVersionUBL"] = 2;
            $payload["wFormat"] = "taxxa.co.dian.document";
        }

        $response = $this->sendRequest($payload);
        return $this->parseResponse($response);
    }

    /**
     * Consulta un documento ya enviado
     * @param string $reference Referencia del documento
     * @return array Datos del documento
     * @throws Exception Si el token no está generado
     */
    public function getDocument(string $reference): array
    {
        if (!$this->token) {
            throw new Exception("Debe autenticarse antes de consultar documentos");
        }

        $payload = [
            'sToken' => $this->token,
            'jApi' => [
                'sMethod' => 'classTaxxa.fjDocumentGet',
                'jParams' => [
                    'sReference' => $reference,
                ]
            ]
        ];

        $response = $this->sendRequest($payload);
        return $this->parseResponse($response);
    }

    /**
     * Envía una petición HTTP a Taxxa
     * @param array $payload Datos a enviar
     * @return mixed Respuesta decodificada
     * @throws Exception Si hay error de conexión
     */
    private function sendRequest(array $payload)
    {
        $this->lastRequest = $payload;
        $jsonData = json_encode($payload);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
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
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("Error de conexión con Taxxa: " . $curlError);
        }

        // Guardar la respuesta sin importar el código HTTP
        $this->lastResponse = $response;

        // Intentar decodificar la respuesta JSON
        $decoded = json_decode($response);

        // Si el código HTTP no es 200, intentar extraer mensaje de error del JSON
        if ($httpCode !== 200) {
            $errorMessage = "Error HTTP {$httpCode}";

            // Si hay respuesta JSON válida, intentar extraer el mensaje de error
            if ($decoded !== null) {
                // Buscar el mensaje en diferentes ubicaciones posibles
                if (isset($decoded->smessage)) {
                    $errorMessage = $this->formatErrorMessage($decoded->smessage);
                } elseif (isset($decoded->message)) {
                    $errorMessage = $this->formatErrorMessage($decoded->message);
                } elseif (isset($decoded->rerror) && isset($decoded->sdebug1)) {
                    // Intentar decodificar sdebug1 si está en base64
                    $debug1 = base64_decode($decoded->sdebug1);
                    $debugData = json_decode($debug1);

                    if ($debugData !== null && isset($debugData->ErrorMessage)) {
                        $errorMessage = $this->formatErrorMessage($debugData->ErrorMessage);
                    } else {
                        $errorMessage .= " - Error {$decoded->rerror}";
                    }
                }
            }

            throw new Exception($errorMessage);
        }

        return $decoded;
    }

    /**
     * Parsea la respuesta de Taxxa a un formato estandarizado
     * @param mixed $response Respuesta del servicio
     * @return array Array con error, mensaje y datos
     */
    private function parseResponse($response): array
    {
        $result = [
            'error' => 0,
            'message' => '',
            'data' => null
        ];

        if (!isset($response->rerror)) {
            $result['error'] = 999;
            $result['message'] = 'Respuesta inválida del servicio';
            return $result;
        }

        $result['error'] = (int)$response->rerror;

        if ($result['error'] === 0) {
            $result['data'] = isset($response->jret) ? (array)$response->jret : [];
            $result['message'] = 'Operación exitosa';
        } else {
            // Intentar obtener el mensaje de error desde diferentes ubicaciones
            $errorMessage = null;

            // Opción 1: Buscar en smessage directamente
            if (isset($response->smessage)) {
                $errorMessage = $this->formatErrorMessage($response->smessage);
            }

            // Opción 2: Si no hay mensaje o está vacío, buscar en sdebug1
            if (empty($errorMessage) && isset($response->sdebug1)) {
                // Intentar decodificar sdebug1 (puede estar en base64)
                $debug1 = base64_decode($response->sdebug1);
                $debugData = json_decode($debug1);

                if ($debugData !== null) {
                    // Buscar ErrorMessage en el debug decodificado
                    if (isset($debugData->ErrorMessage)) {
                        $errorMessage = $this->formatErrorMessage($debugData->ErrorMessage);
                    } elseif (isset($debugData->StatusMessage)) {
                        $errorMessage = $debugData->StatusMessage;
                    }
                }
            }

            // Opción 3: Si aún no hay mensaje, usar uno genérico
            if (empty($errorMessage)) {
                $errorMessage = isset($response->message)
                    ? $this->formatErrorMessage($response->message)
                    : "Error {$result['error']}: Error desconocido";
            }

            $result['message'] = $errorMessage;

            // Si hay datos de retorno a pesar del error, incluirlos
            if (isset($response->jret)) {
                $result['data'] = (array)$response->jret;
            }
        }

        return $result;
    }

    /**
     * Formatea los mensajes de error de Taxxa
     * Maneja múltiples formatos: string, array, objeto con 'string', etc.
     * @param mixed $message Mensaje o array de mensajes
     * @return string Mensaje formateado
     */
    private function formatErrorMessage($message): string
    {
        // Si es string, retornar directamente
        if (is_string($message)) {
            return $message;
        }

        // Si es un objeto, convertir a array para procesarlo
        if (is_object($message)) {
            $message = (array) $message;
        }

        // Si es array, procesar según su estructura
        if (is_array($message)) {
            // Caso 1: Array con clave 'string' (formato común de Taxxa)
            if (isset($message['string'])) {
                $stringValue = $message['string'];

                // La clave 'string' puede ser: string, array de strings, u objeto
                if (is_string($stringValue)) {
                    return $stringValue;
                } elseif (is_array($stringValue)) {
                    // Si es un array, procesar cada elemento
                    $strings = array_map(function ($item) {
                        if (is_string($item)) {
                            return $item;
                        } elseif (is_object($item) || is_array($item)) {
                            return $this->formatErrorMessage($item); // Recursivo
                        }
                        return (string) $item;
                    }, $stringValue);
                    return implode('<br>', $strings);
                } elseif (is_object($stringValue)) {
                    // Si es un objeto, procesarlo recursivamente
                    return $this->formatErrorMessage($stringValue);
                } else {
                    // Cualquier otro tipo, convertir a string de forma segura
                    return (string) $stringValue;
                }
            }

            // Caso 2: Array simple de mensajes
            if (count($message) > 0) {
                // Filtrar valores nulos y vacíos
                $filtered = array_filter($message, function ($item) {
                    return $item !== null && $item !== '';
                });

                if (count($filtered) > 0) {
                    // Si son strings, unirlos
                    $strings = array_map(function ($item) {
                        if (is_string($item)) {
                            return $item;
                        } elseif (is_object($item) || is_array($item)) {
                            return $this->formatErrorMessage($item); // Recursivo
                        }
                        return '';
                    }, $filtered);

                    return implode('<br>', array_filter($strings));
                }
            }
        }

        // Si no se pudo formatear de ninguna forma, convertir a JSON
        return json_encode($message);
    }

    /**
     * Obtiene la última respuesta recibida
     * @return string|null JSON de la última respuesta
     */
    public function getLastResponse(): ?string
    {
        return $this->lastResponse;
    }

    /**
     * Obtiene la última petición enviada
     * @return array|null Array de la última petición
     */
    public function getLastRequest(): ?array
    {
        return $this->lastRequest;
    }

    /**
     * Guarda el log de la última transacción con rotación automática
     * @param string $filepath Ruta del archivo de log
     * @param int $daysToKeep Días a conservar los logs (por defecto 7)
     */
    public function saveLog(string $filepath = 'loglastsend.txt', int $daysToKeep = 7): void
    {
        // Directorio base para logs
        $logDir = __DIR__ . '/../logs';

        // Crear directorio de logs si no existe
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Organizar por fecha: logs/2026-02-05/log_envio_123.txt
        $fechaActual = date('Y-m-d');
        $logDirFecha = $logDir . '/' . $fechaActual;

        if (!is_dir($logDirFecha)) {
            mkdir($logDirFecha, 0755, true);
        }

        // Construir ruta completa del log
        $logPath = $logDirFecha . '/' . basename($filepath);

        // Crear contenido del log
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'request' => $this->lastRequest,
            'response' => $this->lastResponse
        ];

        // Guardar log
        file_put_contents($logPath, json_encode($log, JSON_PRETTY_PRINT));

        // Limpiar logs antiguos (optimización: solo 1 de cada 10 veces)
        if (rand(1, 10) === 1) {
            $this->cleanOldLogs($logDir, $daysToKeep);
        }
    }

    /**
     * Limpia logs antiguos del directorio
     * @param string $logDir Directorio de logs
     * @param int $daysToKeep Días a conservar
     */
    private function cleanOldLogs(string $logDir, int $daysToKeep): void
    {
        if (!is_dir($logDir)) {
            return;
        }

        $fechaLimite = strtotime("-{$daysToKeep} days");
        $directorios = glob($logDir . '/*', GLOB_ONLYDIR);

        foreach ($directorios as $dir) {
            $nombreDir = basename($dir);

            // Verificar si es un directorio con formato de fecha YYYY-MM-DD
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $nombreDir)) {
                $fechaDir = strtotime($nombreDir);

                // Si el directorio es más antiguo que el límite, eliminarlo
                if ($fechaDir !== false && $fechaDir < $fechaLimite) {
                    $this->deleteDirectory($dir);
                }
            }
        }
    }

    /**
     * Elimina un directorio y todo su contenido
     * @param string $dir Ruta del directorio
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $archivos = array_diff(scandir($dir), ['.', '..']);

        foreach ($archivos as $archivo) {
            $rutaCompleta = $dir . '/' . $archivo;

            if (is_dir($rutaCompleta)) {
                $this->deleteDirectory($rutaCompleta);
            } else {
                unlink($rutaCompleta);
            }
        }

        rmdir($dir);
    }
}
