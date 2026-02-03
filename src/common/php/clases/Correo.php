<?php

namespace Src\Common\Php\Clases;

require_once dirname(__DIR__, 4) . '/vendor/PHPMailer/src/Exception.php';
require_once dirname(__DIR__, 4) . '/vendor/PHPMailer/src/PHPMailer.php';
require_once dirname(__DIR__, 4) . '/vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Config\Clases\Plantilla;
use Src\Usuarios\Login\Php\Clases\Usuario;

/**
 * Clase Correo - Clase reutilizable para el envío de correos electrónicos
 * 
 * Esta clase proporciona una interfaz simplificada para enviar correos electrónicos
 * con o sin archivos adjuntos utilizando PHPMailer.
 * 
 * @package Src\Common\Php\Clases
 */
class Correo
{
    private $mailer;
    private $empresa;
    private $errores = [];
    private $config = [];

    /**
     * Constructor de la clase Correo
     * 
     * Inicializa PHPMailer con la configuración SMTP por defecto
     */
    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $usuario = new Usuario();
        $this->empresa = $usuario->getEmpresa();
        $this->cargarConfiguracion();
        $this->configurarSMTP();
    }

    /**
     * Carga la configuración SMTP desde el archivo de configuración
     */
    private function cargarConfiguracion()
    {
        $configPath = dirname(__DIR__, 4) . '/config/smtp_config.php';
        if (file_exists($configPath)) {
            $this->config = include $configPath;
        } else {
            $this->config = [
                'host' => 'smtp.gmail.com',
                'puerto' => 587,
                'encriptacion' => 'tls',
                'usuario' => '',
                'password' => '',
                'nombre_remitente' => '',
                'responder_a' => '',
                'debug' => 0,
            ];
        }
    }

    /**
     * Configura los parámetros SMTP del servidor de correo
     * 
     * @param string $host Host del servidor SMTP
     * @param string $usuario Usuario para autenticación
     * @param string $password Contraseña para autenticación
     * @param int $puerto Puerto del servidor (por defecto 587)
     * @param string $encriptacion Tipo de encriptación (tls/ssl)
     * @return self
     */
    public function configurarSMTP($host = null, $usuario = null, $password = null, $puerto = null, $encriptacion = null)
    {
        try {
            // Usar parámetros proporcionados o valores del archivo de configuración
            $host = $host ?: $this->config['host'] ?? 'smtp.gmail.com';
            $usuario = $usuario ?: $this->config['usuario'] ?? '';
            $password = $password ?: $this->config['password'] ?? '';
            $puerto = $puerto ?: $this->config['puerto'] ?? 587;
            $encriptacion = $encriptacion ?: $this->config['encriptacion'] ?? 'tls';
            $debug = $this->config['debug'] ?? 0;

            // Configuración del servidor
            $this->mailer->isSMTP();
            $this->mailer->Host = $host;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $usuario;
            $this->mailer->Password = $password;
            $this->mailer->SMTPSecure = $encriptacion === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $puerto;
            $this->mailer->SMTPDebug = $debug;

            // Opciones SSL para servidores con certificados auto-firmados
            if (isset($this->config['ssl_options']) && !empty($this->config['ssl_options'])) {
                $this->mailer->SMTPOptions = $this->config['ssl_options'];
            }

            // Configuración de codificación
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->Encoding = 'base64';

            // Configurar remitente por defecto
            $nombreRemitente = $this->config['nombre_remitente'] ?? '';
            if (empty($nombreRemitente) && !empty($this->empresa)) {
                $nombreRemitente = $this->empresa['nombre'] ?? 'Sistema de Nómina';
            }

            if (!empty($usuario)) {
                $this->mailer->setFrom($usuario, $nombreRemitente);
            }

            // Configurar Reply-To si está definido
            $responderA = $this->config['responder_a'] ?? '';
            if (!empty($responderA)) {
                $this->mailer->addReplyTo($responderA, $nombreRemitente);
            }
        } catch (Exception $e) {
            $this->errores[] = "Error al configurar SMTP: " . $e->getMessage();
        }

        return $this;
    }

    /**
     * Establece el remitente del correo
     * 
     * @param string $correo Correo electrónico del remitente
     * @param string $nombre Nombre del remitente
     * @return self
     */
    public function setRemitente($correo, $nombre = '')
    {
        try {
            $this->mailer->setFrom($correo, $nombre);
        } catch (Exception $e) {
            $this->errores[] = "Error al establecer remitente: " . $e->getMessage();
        }
        return $this;
    }

    /**
     * Agrega un destinatario al correo
     * 
     * @param string $correo Correo electrónico del destinatario
     * @param string $nombre Nombre del destinatario (opcional)
     * @return self
     */
    public function addDestinatario($correo, $nombre = '')
    {
        try {
            if (filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $this->mailer->addAddress($correo, $nombre);
            } else {
                $this->errores[] = "Correo electrónico inválido: {$correo}";
            }
        } catch (Exception $e) {
            $this->errores[] = "Error al agregar destinatario: " . $e->getMessage();
        }
        return $this;
    }

    /**
     * Agrega múltiples destinatarios al correo
     * 
     * @param array $destinatarios Array de destinatarios [['correo' => '...', 'nombre' => '...']]
     * @return self
     */
    public function addDestinatarios(array $destinatarios)
    {
        foreach ($destinatarios as $dest) {
            $correo = is_array($dest) ? ($dest['correo'] ?? $dest[0] ?? '') : $dest;
            $nombre = is_array($dest) ? ($dest['nombre'] ?? $dest[1] ?? '') : '';
            $this->addDestinatario($correo, $nombre);
        }
        return $this;
    }

    /**
     * Agrega destinatario en copia (CC)
     * 
     * @param string $correo Correo electrónico
     * @param string $nombre Nombre (opcional)
     * @return self
     */
    public function addCopia($correo, $nombre = '')
    {
        try {
            if (filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $this->mailer->addCC($correo, $nombre);
            }
        } catch (Exception $e) {
            $this->errores[] = "Error al agregar copia: " . $e->getMessage();
        }
        return $this;
    }

    /**
     * Agrega destinatario en copia oculta (BCC)
     * 
     * @param string $correo Correo electrónico
     * @param string $nombre Nombre (opcional)
     * @return self
     */
    public function addCopiaOculta($correo, $nombre = '')
    {
        try {
            if (filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $this->mailer->addBCC($correo, $nombre);
            }
        } catch (Exception $e) {
            $this->errores[] = "Error al agregar copia oculta: " . $e->getMessage();
        }
        return $this;
    }

    /**
     * Establece el asunto del correo
     * 
     * @param string $asunto Asunto del correo
     * @return self
     */
    public function setAsunto($asunto)
    {
        $this->mailer->Subject = $asunto;
        return $this;
    }

    /**
     * Establece el cuerpo del correo en HTML
     * 
     * @param string $html Contenido HTML del correo
     * @param string $textoAlternativo Texto alternativo para clientes sin HTML
     * @return self
     */
    public function setCuerpoHTML($html, $textoAlternativo = '')
    {
        $this->mailer->isHTML(true);
        $this->mailer->Body = $html;
        $this->mailer->AltBody = $textoAlternativo ?: strip_tags($html);
        return $this;
    }

    /**
     * Establece el cuerpo del correo en texto plano
     * 
     * @param string $texto Contenido del correo
     * @return self
     */
    public function setCuerpoTexto($texto)
    {
        $this->mailer->isHTML(false);
        $this->mailer->Body = $texto;
        return $this;
    }

    /**
     * Agrega un archivo adjunto desde una ruta en el sistema de archivos
     * 
     * @param string $rutaArchivo Ruta absoluta al archivo
     * @param string $nombreArchivo Nombre con el que se mostrará el archivo (opcional)
     * @return self
     */
    public function addAdjunto($rutaArchivo, $nombreArchivo = '')
    {
        try {
            if (file_exists($rutaArchivo)) {
                $this->mailer->addAttachment($rutaArchivo, $nombreArchivo);
            } else {
                $this->errores[] = "Archivo no encontrado: {$rutaArchivo}";
            }
        } catch (Exception $e) {
            $this->errores[] = "Error al agregar adjunto: " . $e->getMessage();
        }
        return $this;
    }

    /**
     * Agrega un archivo adjunto desde un string (contenido en memoria)
     * 
     * @param string $contenido Contenido binario del archivo
     * @param string $nombreArchivo Nombre del archivo
     * @param string $tipoMime Tipo MIME del archivo (por defecto application/pdf)
     * @return self
     */
    public function addAdjuntoDesdeString($contenido, $nombreArchivo, $tipoMime = 'application/pdf')
    {
        try {
            $this->mailer->addStringAttachment($contenido, $nombreArchivo, 'base64', $tipoMime);
        } catch (Exception $e) {
            $this->errores[] = "Error al agregar adjunto desde string: " . $e->getMessage();
        }
        return $this;
    }

    /**
     * Agrega múltiples archivos adjuntos
     * 
     * @param array $archivos Array de rutas de archivos o ['ruta' => '...', 'nombre' => '...']
     * @return self
     */
    public function addAdjuntos(array $archivos)
    {
        foreach ($archivos as $archivo) {
            if (is_array($archivo)) {
                $this->addAdjunto($archivo['ruta'] ?? $archivo[0], $archivo['nombre'] ?? $archivo[1] ?? '');
            } else {
                $this->addAdjunto($archivo);
            }
        }
        return $this;
    }

    /**
     * Envía el correo electrónico
     * 
     * @return array ['success' => bool, 'message' => string, 'errors' => array]
     */
    public function enviar()
    {
        try {
            // Validar que hay destinatarios
            if (count($this->mailer->getToAddresses()) === 0) {
                return [
                    'success' => false,
                    'message' => 'No se han especificado destinatarios',
                    'errors' => $this->errores
                ];
            }

            // Verificar errores previos
            if (!empty($this->errores)) {
                return [
                    'success' => false,
                    'message' => 'Se encontraron errores durante la configuración',
                    'errors' => $this->errores
                ];
            }

            $this->mailer->send();

            return [
                'success' => true,
                'message' => 'Correo enviado correctamente',
                'errors' => []
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al enviar el correo: ' . $this->mailer->ErrorInfo,
                'errors' => array_merge($this->errores, [$e->getMessage()])
            ];
        }
    }

    /**
     * Limpia la configuración actual para reutilizar la instancia
     * 
     * @return self
     */
    public function limpiar()
    {
        $this->mailer->clearAddresses();
        $this->mailer->clearCCs();
        $this->mailer->clearBCCs();
        $this->mailer->clearAttachments();
        $this->mailer->clearAllRecipients();
        $this->errores = [];
        return $this;
    }

    /**
     * Obtiene los errores acumulados
     * 
     * @return array
     */
    public function getErrores()
    {
        return $this->errores;
    }

    /**
     * Genera el cuerpo HTML con el estilo de la empresa
     * 
     * @param string $titulo Título del correo
     * @param string $contenido Contenido del correo
     * @param string $piePagina Pie de página (opcional)
     * @return string HTML formateado
     */
    public function generarPlantillaHTML($titulo, $contenido, $piePagina = '')
    {
        $nombreEmpresa = $this->empresa['nombre'] ?? 'Sistema de Nómina';
        $piePagina = $piePagina ?: "Este es un correo automático, por favor no responda a este mensaje.";

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$titulo}</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="padding: 20px 0;">
                <table align="center" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #16a085, #1abc9c); padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">{$nombreEmpresa}</h1>
                        </td>
                    </tr>
                    <!-- Título -->
                    <tr>
                        <td style="padding: 30px 40px 10px; text-align: center;">
                            <h2 style="color: #333; margin: 0; font-size: 20px;">{$titulo}</h2>
                        </td>
                    </tr>
                    <!-- Contenido -->
                    <tr>
                        <td style="padding: 20px 40px 30px;">
                            <div style="color: #555; font-size: 14px; line-height: 1.6;">
                                {$contenido}
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px 40px; text-align: center; border-radius: 0 0 8px 8px; border-top: 1px solid #eee;">
                            <p style="color: #888; font-size: 12px; margin: 0;">{$piePagina}</p>
                            <p style="color: #888; font-size: 12px; margin: 10px 0 0;">© {$nombreEmpresa} - Todos los derechos reservados</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Método estático para envío rápido de correos simples
     * 
     * @param string $destinatario Correo del destinatario
     * @param string $asunto Asunto del correo
     * @param string $mensaje Mensaje del correo
     * @param array $adjuntos Array de rutas de archivos adjuntos (opcional)
     * @return array Resultado del envío
     */
    public static function envioRapido($destinatario, $asunto, $mensaje, $adjuntos = [])
    {
        $correo = new self();
        $correo->addDestinatario($destinatario)
            ->setAsunto($asunto)
            ->setCuerpoHTML($correo->generarPlantillaHTML($asunto, $mensaje));

        if (!empty($adjuntos)) {
            $correo->addAdjuntos($adjuntos);
        }

        return $correo->enviar();
    }
}
