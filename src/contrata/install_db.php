<?php
require_once '../../config/autoloader.php';
use Config\Clases\Conexion;

try {
    $conexion = Conexion::getConexion();
    
    // 1. cnt_contratos
    $sql1 = "CREATE TABLE IF NOT EXISTS `cnt_contratos` (
        `id_contrato` int(11) NOT NULL AUTO_INCREMENT,
        `numero_contrato` varchar(100) NOT NULL,
        `id_tercero` int(11) NOT NULL,
        `estado` enum('Borrador','Revisión','Aprobado','Firmado','Anulado') NOT NULL DEFAULT 'Borrador',
        `fecha_inicio` date DEFAULT NULL,
        `fecha_fin` date DEFAULT NULL,
        `valor` decimal(15,2) DEFAULT '0.00',
        PRIMARY KEY (`id_contrato`),
        UNIQUE KEY `idx_numero_contrato` (`numero_contrato`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    // 2. cnt_contrato_versiones
    $sql2 = "CREATE TABLE IF NOT EXISTS `cnt_contrato_versiones` (
        `id_version` int(11) NOT NULL AUTO_INCREMENT,
        `id_contrato` int(11) NOT NULL,
        `version_num` varchar(20) NOT NULL,
        `contenido_html` longtext NOT NULL,
        `hash_integridad` varchar(64) NOT NULL COMMENT 'SHA-256 del contenido',
        `fecha_version` datetime NOT NULL,
        `id_usuario_crea` int(11) NOT NULL,
        PRIMARY KEY (`id_version`),
        CONSTRAINT `fk_version_contrato` FOREIGN KEY (`id_contrato`) REFERENCES `cnt_contratos` (`id_contrato`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    // 3. cnt_auditoria
    $sql3 = "CREATE TABLE IF NOT EXISTS `cnt_auditoria` (
        `id_auditoria` int(11) NOT NULL AUTO_INCREMENT,
        `id_contrato` int(11) NOT NULL,
        `accion` varchar(100) NOT NULL,
        `ip_origen` varchar(45) NOT NULL,
        `user_agent` text,
        `timestamp` datetime NOT NULL,
        `id_usuario` int(11) NOT NULL,
        `detalles_json` text,
        PRIMARY KEY (`id_auditoria`),
        CONSTRAINT `fk_auditoria_contrato` FOREIGN KEY (`id_contrato`) REFERENCES `cnt_contratos` (`id_contrato`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $conexion->exec($sql1);
    echo "Tabla cnt_contratos creada o ya existia.\n";
    $conexion->exec($sql2);
    echo "Tabla cnt_contrato_versiones creada o ya existia.\n";
    $conexion->exec($sql3);
    echo "Tabla cnt_auditoria creada o ya existia.\n";
    
    echo "¡Instalacion de base de datos completada!\n";
} catch (PDOException $e) {
    echo "Error PDO: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error general: " . $e->getMessage() . "\n";
}
