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

        if ($httpCode !== 200) {
            throw new Exception("Error HTTP {$httpCode} al conectar con Taxxa");
        }

        $this->lastResponse = $response;
        return json_decode($response);
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
            $result['message'] = $this->formatErrorMessage($response->smessage ?? 'Error desconocido');
        }

        return $result;
    }

    /**
     * Formatea los mensajes de error de Taxxa
     * @param mixed $message Mensaje o array de mensajes
     * @return string Mensaje formateado
     */
    private function formatErrorMessage($message): string
    {
        if (is_string($message)) {
            return $message;
        }

        if (is_array($message)) {
            if (isset($message['string'])) {
                if (is_array($message['string'])) {
                    return implode('; ', $message['string']);
                }
                return $message['string'];
            }
            return implode('; ', $message);
        }

        if (is_object($message)) {
            return json_encode($message);
        }

        return 'Error desconocido';
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
     * Guarda el log de la última transacción
     * @param string $filepath Ruta del archivo de log
     */
    public function saveLog(string $filepath = 'loglastsend.txt'): void
    {
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'request' => $this->lastRequest,
            'response' => $this->lastResponse
        ];
        file_put_contents($filepath, json_encode($log, JSON_PRETTY_PRINT));
    }
}
