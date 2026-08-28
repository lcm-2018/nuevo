<?php

namespace Src\Contrata\Php\Clases;

use Config\Clases\Conexion;
use PDO;
use PDOException;
use Exception;

class Contratos
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::getConexion();
    }

    /**
     * T2.3: Registrar auditoría
     * Captura IP, User Agent y usuario que realiza la acción para trazabilidad legal.
     */
    public function registrarAuditoria($id_contrato, $accion, $id_usuario, $detalles_json = null)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $timestamp = date('Y-m-d H:i:s');

        try {
            $sql = "INSERT INTO cnt_auditoria (id_contrato, accion, ip_origen, user_agent, timestamp, id_usuario, detalles_json) 
                    VALUES (:id_contrato, :accion, :ip, :ua, :ts, :user, :detalles)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                ':id_contrato' => $id_contrato,
                ':accion' => $accion,
                ':ip' => $ip,
                ':ua' => $user_agent,
                ':ts' => $timestamp,
                ':user' => $id_usuario,
                ':detalles' => $detalles_json
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Error Auditoria: " . $e->getMessage());
            return false;
        }
    }

    /**
     * T2.2: Crear versión inmutable
     * Genera un hash SHA-256 del contenido para garantizar que no ha sido alterado.
     */
    public function crearVersion($id_contrato, $version_num, $contenido_html, $id_usuario)
    {
        $fecha = date('Y-m-d H:i:s');
        $hash = hash('sha256', $id_contrato . $version_num . $contenido_html . $fecha);

        try {
            $sql = "INSERT INTO cnt_contrato_versiones (id_contrato, version_num, contenido_html, hash_integridad, fecha_version, id_usuario_crea) 
                    VALUES (:id, :ver, :html, :hash, :fecha, :user)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                ':id' => $id_contrato,
                ':ver' => $version_num,
                ':html' => $contenido_html,
                ':hash' => $hash,
                ':fecha' => $fecha,
                ':user' => $id_usuario
            ]);
            
            // Auditar la creación de versión
            $this->registrarAuditoria($id_contrato, "CREACION_VERSION_$version_num", $id_usuario, json_encode(['hash' => $hash]));
            
            return true;
        } catch (PDOException $e) {
            error_log("Error Version: " . $e->getMessage());
            return false;
        }
    }

    /**
     * T2.1: CRUD Básico
     */
    public function getContratos()
    {
        try {
            $sql = "SELECT * FROM cnt_contratos ORDER BY id_contrato DESC";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function addContrato($data)
    {
        try {
            $this->conexion->beginTransaction();
            
            $sql = "INSERT INTO cnt_contratos (numero_contrato, id_tercero, estado, fecha_inicio, fecha_fin, valor) 
                    VALUES (:num, :tercero, :estado, :inicio, :fin, :valor)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                ':num' => $data['numero_contrato'] ?? uniqid('CNT-'),
                ':tercero' => $data['id_tercero'],
                ':estado' => 'Borrador',
                ':inicio' => $data['fecha_inicio'] ?? date('Y-m-d'),
                ':fin' => $data['fecha_fin'] ?? null,
                ':valor' => $data['valor'] ?? 0
            ]);
            
            $id_contrato = $this->conexion->lastInsertId();
            
            // Crear versión 1.0 por defecto
            $contenido = $data['contenido_html'] ?? '<p>Contrato vacío</p>';
            $this->crearVersion($id_contrato, '1.0', $contenido, $data['id_usuario']);
            
            $this->conexion->commit();
            return 'si';
        } catch (PDOException $e) {
            $this->conexion->rollBack();
            return 'Error: ' . $e->getMessage();
        }
    }

    /**
     * T4.1 & T4.2: Firmar Contrato Electrónicamente
     * Cambia el estado a Firmado, bloqueando futuras ediciones y registrando metadatos legales.
     */
    public function firmarContrato($id_contrato, $id_usuario, $credencial_hash)
    {
        try {
            $this->conexion->beginTransaction();

            // 1. Validar estado actual
            $stmt = $this->conexion->prepare("SELECT estado FROM cnt_contratos WHERE id_contrato = :id");
            $stmt->execute([':id' => $id_contrato]);
            $estado = $stmt->fetchColumn();

            if ($estado === 'Firmado' || $estado === 'Anulado') {
                throw new Exception("El contrato no se puede firmar porque está en estado: " . $estado);
            }

            // 2. Cambiar estado a Firmado (T4.2: Bloquea edición a nivel de base de datos y UI)
            $stmtUpdate = $this->conexion->prepare("UPDATE cnt_contratos SET estado = 'Firmado' WHERE id_contrato = :id");
            $stmtUpdate->execute([':id' => $id_contrato]);

            // 3. Registrar auditoría de firma (T4.1: Atribución legal)
            $metadata = json_encode([
                'credencial_hash' => $credencial_hash,
                'metodo' => 'Firma Electrónica Interna (Ley 527/99)',
                'timestamp_servidor' => time()
            ]);
            $this->registrarAuditoria($id_contrato, 'FIRMA_ELECTRONICA', $id_usuario, $metadata);

            $this->conexion->commit();
            return 'si';
        } catch (Exception $e) {
            $this->conexion->rollBack();
            return 'Error: ' . $e->getMessage();
        }
    }

    /**
     * T4.3 & T4.4: Generar PDF y Certificado de Trazabilidad usando Dompdf
     */
    public function generarPDF($id_contrato)
    {
        // 1. Obtener datos del contrato y la última versión
        $stmt = $this->conexion->prepare("SELECT c.numero_contrato, v.contenido_html, v.hash_integridad, v.version_num 
                                          FROM cnt_contratos c 
                                          JOIN cnt_contrato_versiones v ON c.id_contrato = v.id_contrato 
                                          WHERE c.id_contrato = :id ORDER BY v.id_version DESC LIMIT 1");
        $stmt->execute([':id' => $id_contrato]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return false;
        }

        // 2. Obtener historial de auditoría para el certificado (T4.4)
        $stmtAud = $this->conexion->prepare("SELECT * FROM cnt_auditoria WHERE id_contrato = :id ORDER BY id_auditoria ASC");
        $stmtAud->execute([':id' => $id_contrato]);
        $auditorias = $stmtAud->fetchAll(PDO::FETCH_ASSOC);

        // Construir HTML del Certificado de Trazabilidad
        $htmlAuditoria = "<h3>Certificado de Trazabilidad (Ley 527 de 1999)</h3><table border='1' cellpadding='5' cellspacing='0' width='100%'>";
        $htmlAuditoria .= "<tr><th>Fecha</th><th>Acción</th><th>IP Origen</th><th>Usuario ID</th><th>Hash/Metadata</th></tr>";
        foreach ($auditorias as $aud) {
            $det = htmlspecialchars($aud['detalles_json']);
            $htmlAuditoria .= "<tr>
                <td>{$aud['timestamp']}</td>
                <td>{$aud['accion']}</td>
                <td>{$aud['ip_origen']}</td>
                <td>{$aud['id_usuario']}</td>
                <td style='font-size: 9px; word-break: break-all;'>{$det}</td>
            </tr>";
        }
        $htmlAuditoria .= "</table>";

        // Ensamblar documento final
        $htmlCompleto = "
            <html>
            <head><style>body { font-family: sans-serif; } .page-break { page-break-after: always; }</style></head>
            <body>
                <div style='text-align: right; color: gray; font-size: 10px;'>Contrato No. {$data['numero_contrato']} | Versión {$data['version_num']}</div>
                {$data['contenido_html']}
                <div class='page-break'></div>
                {$htmlAuditoria}
                <br>
                <div style='font-size: 10px; color: #555;'>Hash de Integridad del Documento: <b>{$data['hash_integridad']}</b></div>
            </body>
            </html>
        ";

        // Instanciar Dompdf
        require_once $_SERVER['DOCUMENT_ROOT'] . '/nuevo/vendor/autoload.php'; // Ajustar según ruta real del autoloader de vendor
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($htmlCompleto);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // T4.3: Generar archivo PDF
        return $dompdf->output();
    }
}
