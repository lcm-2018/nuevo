-- ============================================================
-- Registro del nuevo módulo: Contratos Públicos (ID = 58)
-- en las tablas de seguridad del sistema
-- ============================================================

-- 1. Insertar el módulo
INSERT IGNORE INTO `seg_modulos` (`id_modulo`, `nom_modulo`)
VALUES (58, 'Contratos Públicos');

-- 2. Insertar las opciones del módulo
--    Rango: 5801 - 5899 (coherente con el patrón del sistema)
INSERT IGNORE INTO `seg_opciones` (`id_opcion`, `id_modulo`, `nom_opcion`) VALUES
(5801, 58, '[Contratos][Configuración]'),
(5802, 58, '[Contratos][Procesos]'),
(5803, 58, '[Contratos][Contratos]'),
(5804, 58, '[Contratos][Minutas]'),
(5805, 58, '[Contratos][Aprobaciones]'),
(5806, 58, '[Contratos][Auditoría]'),
(5807, 58, '[Contratos][Bitácora]'),
(5808, 58, '[Contratos][PDF]'),
(5899, 58, '[Contratos][Informes Personalizados]');

-- 3. Dar acceso total al rol administrador (id_rol = 1 / id_usuario = 1)
--    Si en tu sistema el admin no necesita registros en seg_rol_usuario
--    porque ya pasa la condición id_rol == 1, este paso es opcional.
--    De todas formas lo insertamos para tenerlo documentado.
INSERT IGNORE INTO `seg_permisos_modulos` (`id_usuario`, `id_modulo`, `id_per_mod`)
SELECT `id_usuario`, 58, 1
FROM `seg_usuarios`
WHERE `id_rol` = 1;

-- ============================================================
-- Verificación
-- ============================================================
SELECT id_modulo, nom_modulo FROM seg_modulos WHERE id_modulo = 58;
SELECT id_opcion, nom_opcion FROM seg_opciones WHERE id_modulo = 58 ORDER BY id_opcion;
