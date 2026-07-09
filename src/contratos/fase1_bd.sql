-- ============================================================
-- FASE 1 — Infraestructura de Base de Datos
-- Módulo: Contratación Pública
-- Autor: Módulo nuevo (reemplaza lógica de src/contratacion)
-- Fecha: 2026-07-08
-- 
-- TABLAS EXISTENTES QUE SE REUTILIZAN (NO recrear):
--   ctt_modalidad   → ya existe, se reutiliza tal cual
--   ctt_estado_adq  → ya existe para adquisiciones, NO es la misma
--
-- CONVENCIÓN: Todas las tablas nuevas usan prefijo ctt_
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ============================================================
-- TABLA 1.1: ctt_tipo_proceso
-- Catálogo de tipos de proceso (Bien, Servicio, Consultoría...)
-- ============================================================
CREATE TABLE IF NOT EXISTS `ctt_tipo_proceso` (
    `id_tipo_proceso`   INT(11)         NOT NULL AUTO_INCREMENT,
    `nombre`            VARCHAR(150)    NOT NULL,
    `descripcion`       TEXT            DEFAULT NULL,
    `activo`            TINYINT(1)      NOT NULL DEFAULT 1,
    `id_user_reg`       INT(11)         NOT NULL,
    `fec_reg`           DATE            NOT NULL,
    `id_user_act`       INT(11)         DEFAULT NULL,
    `fec_act`           DATE            DEFAULT NULL,
    PRIMARY KEY (`id_tipo_proceso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Catálogo de tipos de proceso de contratación';

-- Datos iniciales
INSERT IGNORE INTO `ctt_tipo_proceso` (`id_tipo_proceso`, `nombre`, `descripcion`, `activo`, `id_user_reg`, `fec_reg`) VALUES
(1, 'BIEN',          'Adquisición de bienes tangibles',                1, 1, CURDATE()),
(2, 'SERVICIO',      'Prestación de servicios profesionales o técnicos',1, 1, CURDATE()),
(3, 'CONSULTORÍA',   'Trabajos de consultoría e interventoría',         1, 1, CURDATE()),
(4, 'OBRA',          'Ejecución de obra pública',                       1, 1, CURDATE()),
(5, 'CONCESIÓN',     'Contratos de concesión',                          1, 1, CURDATE());

-- ============================================================
-- TABLA 1.2: ctt_estado_proceso
-- Catálogo de estados para procesos y contratos
-- permite_edicion: 1 = se puede editar, 0 = bloqueado
-- ============================================================
CREATE TABLE IF NOT EXISTS `ctt_estado_proceso` (
    `id_estado`         INT(11)         NOT NULL AUTO_INCREMENT,
    `nombre`            VARCHAR(80)     NOT NULL,
    `color_badge`       VARCHAR(40)     NOT NULL DEFAULT 'secondary'
                        COMMENT 'Clase Bootstrap: primary, success, warning, danger, info, secondary, dark',
    `permite_edicion`   TINYINT(1)      NOT NULL DEFAULT 1
                        COMMENT '1=editable, 0=bloqueado',
    `orden`             INT(3)          NOT NULL DEFAULT 1
                        COMMENT 'Orden de transición de estados',
    PRIMARY KEY (`id_estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Estados del ciclo de vida del contrato';

-- Datos iniciales — flujo: 1→2→3→4 / anulado=5
INSERT IGNORE INTO `    ` (`id_estado`, `nombre`, `color_badge`, `permite_edicion`, `orden`) VALUES
(1, 'BORRADOR',    'secondary', 1, 1),
(2, 'EN REVISIÓN', 'warning',   0, 2),
(3, 'APROBADO',    'success',   0, 3),
(4, 'FINALIZADO',  'primary',   0, 4),
(5, 'ANULADO',     'danger',    0, 0);

-- ============================================================
-- TABLA 1.3: ctt_procesos_new
-- Proceso de contratación pública (cabecera)
-- NOTA: Prefijo "_new" para no colisionar con tablas antiguas
--       Se puede renombrar a ctt_procesos cuando se confirme
--       que el módulo antiguo será desactivado.
-- ============================================================
CREATE TABLE IF NOT EXISTS `ctt_procesos_new` (
    `id_proceso`        INT(11)         NOT NULL AUTO_INCREMENT,
    `codigo_proceso`    VARCHAR(20)     NOT NULL
                        COMMENT 'Código único auto-generado: CTT-YYYY-NNN',
    `objeto`            TEXT            NOT NULL
                        COMMENT 'Objeto del proceso de contratación',
    `id_tipo_proceso`   INT(11)         NOT NULL,
    `id_modalidad`      INT(11)         NOT NULL
                        COMMENT 'FK a ctt_modalidad existente',
    `id_area`           INT(11)         DEFAULT NULL
                        COMMENT 'FK a far_centrocosto_area o tb_area según configuración',
    `id_vigencia`       INT(11)         NOT NULL
                        COMMENT 'Año fiscal de la sesión',
    `id_estado`         INT(11)         NOT NULL DEFAULT 1,
    `observaciones`     TEXT            DEFAULT NULL,
    `id_user_reg`       INT(11)         NOT NULL,
    `fec_reg`           DATE            NOT NULL,
    `id_user_act`       INT(11)         DEFAULT NULL,
    `fec_act`           DATE            DEFAULT NULL,
    PRIMARY KEY (`id_proceso`),
    UNIQUE KEY `uk_codigo_proceso` (`codigo_proceso`),
    KEY `idx_estado_proceso` (`id_estado`),
    KEY `idx_vigencia_proceso` (`id_vigencia`),
    CONSTRAINT `fk_proc_tipo`    FOREIGN KEY (`id_tipo_proceso`) REFERENCES `ctt_tipo_proceso` (`id_tipo_proceso`),
    CONSTRAINT `fk_proc_estado`  FOREIGN KEY (`id_estado`)       REFERENCES `ctt_estado_proceso` (`id_estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Proceso de contratación pública — cabecera principal';

-- ============================================================
-- TABLA 1.4: ctt_contratos_new
-- Contrato vinculado a un proceso de contratación
-- NOTA: La tabla ctt_contratos existente es para órdenes de
--       compra del módulo de adquisiciones. Esta es diferente.
-- ============================================================
CREATE TABLE IF NOT EXISTS `ctt_contratos_new` (
    `id_contrato`           INT(11)         NOT NULL AUTO_INCREMENT,
    `id_proceso`            INT(11)         NOT NULL,
    `codigo_contrato`       VARCHAR(25)     NOT NULL
                            COMMENT 'Código único: CTT-CON-YYYY-NNN',
    `objeto_contrato`       TEXT            NOT NULL,
    `id_contratista`        INT(11)         DEFAULT NULL
                            COMMENT 'FK a tb_terceros.id_tercero_api',
    `valor_inicial`         DECIMAL(18,2)   DEFAULT 0.00,
    `valor_final`           DECIMAL(18,2)   DEFAULT 0.00
                            COMMENT 'Incluye adiciones/modificaciones',
    `fecha_inicio`          DATE            DEFAULT NULL,
    `fecha_fin`             DATE            DEFAULT NULL,
    `id_estado`             INT(11)         NOT NULL DEFAULT 1,
    `id_user_reg`           INT(11)         NOT NULL,
    `fec_reg`               DATE            NOT NULL,
    `id_user_act`           INT(11)         DEFAULT NULL,
    `fec_act`               DATE            DEFAULT NULL,
    PRIMARY KEY (`id_contrato`),
    UNIQUE KEY `uk_codigo_contrato` (`codigo_contrato`),
    KEY `idx_proceso_contrato` (`id_proceso`),
    KEY `idx_estado_contrato` (`id_estado`),
    CONSTRAINT `fk_con_proceso` FOREIGN KEY (`id_proceso`) REFERENCES `ctt_procesos_new` (`id_proceso`),
    CONSTRAINT `fk_con_estado`  FOREIGN KEY (`id_estado`)  REFERENCES `ctt_estado_proceso` (`id_estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Contratos del proceso de contratación pública';

-- ============================================================
-- TABLA 1.5: ctt_minutas
-- Versionamiento INMUTABLE de minutas contractuales
-- REGLA: Nunca UPDATE sobre esta tabla. Solo INSERT + desactivar anterior.
-- El campo es_activa indica cuál versión está vigente.
-- ============================================================
CREATE TABLE IF NOT EXISTS `ctt_minutas` (
    `id_minuta`         INT(11)         NOT NULL AUTO_INCREMENT,
    `id_contrato`       INT(11)         NOT NULL,
    `version`           INT(6)          NOT NULL DEFAULT 1
                        COMMENT 'Número de versión auto-incremental por contrato',
    `contenido`         LONGTEXT        NOT NULL
                        COMMENT 'Cuerpo completo de la minuta en HTML/texto',
    `hash_sha256`       VARCHAR(64)     NOT NULL
                        COMMENT 'SHA-256 del contenido para verificar integridad',
    `es_activa`         TINYINT(1)      NOT NULL DEFAULT 1
                        COMMENT '1=versión vigente, 0=versión histórica',
    `id_user_reg`       INT(11)         NOT NULL,
    `fec_reg`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- SIN fec_act ni id_user_act por diseño: esta tabla es INMUTABLE
    PRIMARY KEY (`id_minuta`),
    UNIQUE KEY `uk_contrato_version` (`id_contrato`, `version`),
    KEY `idx_minuta_activa` (`id_contrato`, `es_activa`),
    CONSTRAINT `fk_min_contrato` FOREIGN KEY (`id_contrato`) REFERENCES `ctt_contratos_new` (`id_contrato`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Versiones inmutables de minutas contractuales';

-- ============================================================
-- TABLA 1.6: ctt_auditoria_new
-- Log automático de auditoría — TODAS las operaciones críticas
-- datos_antes y datos_despues almacenan JSON del registro
-- ============================================================
CREATE TABLE IF NOT EXISTS `ctt_auditoria_new` (
    `id_log`            BIGINT(20)      NOT NULL AUTO_INCREMENT,
    `id_contrato`       INT(11)         DEFAULT NULL
                        COMMENT 'NULL cuando la acción es sobre un proceso sin contrato',
    `id_proceso`        INT(11)         DEFAULT NULL,
    `accion`            VARCHAR(60)     NOT NULL
                        COMMENT 'CREAR, EDITAR, ELIMINAR, CAMBIO_ESTADO, VER_VERSION, DESCARGA_PDF, APROBAR, RECHAZAR',
    `tabla_afectada`    VARCHAR(80)     NOT NULL,
    `id_registro`       INT(11)         DEFAULT NULL,
    `datos_antes`       JSON            DEFAULT NULL
                        COMMENT 'Estado del registro ANTES de la modificación',
    `datos_despues`     JSON            DEFAULT NULL
                        COMMENT 'Estado del registro DESPUÉS de la modificación',
    `ip_usuario`        VARCHAR(45)     DEFAULT NULL
                        COMMENT 'IPv4 o IPv6 del cliente',
    `id_user`           INT(11)         NOT NULL,
    `fec_hora`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_log`),
    KEY `idx_aud_contrato`  (`id_contrato`),
    KEY `idx_aud_proceso`   (`id_proceso`),
    KEY `idx_aud_user`      (`id_user`),
    KEY `idx_aud_fec`       (`fec_hora`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Log de auditoría automático del módulo de contratación';

-- ============================================================
-- TABLA 1.7: ctt_bitacora
-- Observaciones jurídicas y comentarios sobre el contrato
-- INMUTABLE: Solo INSERT, nunca UPDATE ni DELETE
-- ============================================================
CREATE TABLE IF NOT EXISTS `ctt_bitacora` (
    `id_nota`           INT(11)         NOT NULL AUTO_INCREMENT,
    `id_contrato`       INT(11)         NOT NULL,
    `tipo`              ENUM('COMENTARIO','OBSERVACION_JURIDICA','ALERTA','DECISION')
                        NOT NULL DEFAULT 'COMENTARIO',
    `descripcion`       TEXT            NOT NULL,
    `id_user`           INT(11)         NOT NULL,
    `fec_hora`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- SIN UPDATE/DELETE por diseño: la bitácora es inmutable
    PRIMARY KEY (`id_nota`),
    KEY `idx_bit_contrato` (`id_contrato`),
    KEY `idx_bit_tipo`     (`tipo`),
    CONSTRAINT `fk_bit_contrato` FOREIGN KEY (`id_contrato`) REFERENCES `ctt_contratos_new` (`id_contrato`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bitácora jurídica inmutable del contrato';

-- ============================================================
-- TABLA 1.8: ctt_aprobaciones
-- Flujo de aprobación multinivel por contrato
-- ============================================================
CREATE TABLE IF NOT EXISTS `ctt_aprobaciones` (
    `id_aprobacion`     INT(11)         NOT NULL AUTO_INCREMENT,
    `id_contrato`       INT(11)         NOT NULL,
    `nivel`             INT(3)          NOT NULL DEFAULT 1
                        COMMENT '1=primer nivel, 2=segundo nivel...',
    `id_user_aprobador` INT(11)         NOT NULL
                        COMMENT 'FK a tb_users o la tabla de usuarios del sistema',
    `estado`            ENUM('PENDIENTE','APROBADO','RECHAZADO')
                        NOT NULL DEFAULT 'PENDIENTE',
    `comentario`        TEXT            DEFAULT NULL,
    `fec_hora`          DATETIME        DEFAULT NULL
                        COMMENT 'Fecha/hora de la decisión (NULL si sigue pendiente)',
    `id_user_reg`       INT(11)         NOT NULL
                        COMMENT 'Quién creó la solicitud de aprobación',
    `fec_reg`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_aprobacion`),
    KEY `idx_apr_contrato` (`id_contrato`),
    KEY `idx_apr_estado`   (`estado`),
    CONSTRAINT `fk_apr_contrato` FOREIGN KEY (`id_contrato`) REFERENCES `ctt_contratos_new` (`id_contrato`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Flujo de aprobación multinivel de contratos';

-- ============================================================
-- VISTAS ÚTILES (opcionales pero recomendadas)
-- ============================================================

-- Vista: contratos con estado legible y proceso asociado
CREATE OR REPLACE VIEW `v_contratos_new` AS
SELECT
    `cn`.`id_contrato`,
    `cn`.`codigo_contrato`,
    `cn`.`objeto_contrato`,
    `cn`.`valor_inicial`,
    `cn`.`valor_final`,
    `cn`.`fecha_inicio`,
    `cn`.`fecha_fin`,
    `ep`.`nombre`       AS `estado`,
    `ep`.`color_badge`,
    `ep`.`permite_edicion`,
    `pn`.`codigo_proceso`,
    `pn`.`objeto`       AS `objeto_proceso`,
    `md`.`modalidad`,
    `cn`.`id_user_reg`,
    `cn`.`fec_reg`
FROM `ctt_contratos_new`    AS `cn`
INNER JOIN `ctt_estado_proceso` AS `ep` ON `cn`.`id_estado`   = `ep`.`id_estado`
INNER JOIN `ctt_procesos_new`   AS `pn` ON `cn`.`id_proceso`  = `pn`.`id_proceso`
LEFT  JOIN `ctt_modalidad`      AS `md` ON `pn`.`id_modalidad`= `md`.`id_modalidad`;

-- Vista: versión activa de minuta por contrato
CREATE OR REPLACE VIEW `v_minuta_activa` AS
SELECT
    `m`.`id_minuta`,
    `m`.`id_contrato`,
    `m`.`version`,
    `m`.`hash_sha256`,
    `m`.`id_user_reg`,
    `m`.`fec_reg`,
    LEFT(`m`.`contenido`, 300) AS `preview`
FROM `ctt_minutas` AS `m`
WHERE `m`.`es_activa` = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- FIN DEL SCRIPT
-- Ejecutar en phpMyAdmin o consola MySQL:
--   mysql -u root -p nombre_base_datos < fase1_bd.sql
-- ============================================================
