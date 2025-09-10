CREATE TABLE `nom_terceros`(  
  `id_tn` INT NOT NULL AUTO_INCREMENT,
  `id_tercero_api` INT,
  `id_tipo` INT,
  `id_user_reg` INT UNSIGNED,
  `fec_reg` DATETIME,
  PRIMARY KEY (`id_tn`),
  FOREIGN KEY (`id_tercero_api`) REFERENCES `tb_terceros`(`id_tercero_api`),
  FOREIGN KEY (`id_tipo`) REFERENCES `nom_categoria_tercero`(`id_cat`),
  FOREIGN KEY (`id_user_reg`) REFERENCES `seg_usuarios_sistema`(`id_usuario`)
) ENGINE=INNODB CHARSET=utf8 COLLATE=utf8_general_ci;

ALTER TABLE `nom_salarios_basico`   
  ADD COLUMN `id_contrato` INT NULL AFTER `id_empleado`,
  ADD FOREIGN KEY (`id_contrato`) REFERENCES `nom_contratos_empleados`(`id_contrato_emp`) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE `nom_tipo_rubro`   
  CHANGE `tipo` `tipo` TINYINT(1) DEFAULT 2  NULL  COMMENT '2: Solo Público, 1: Público y privado';
CREATE TABLE `nom_terceros_novedad`(  
  `id_novedad` INT NOT NULL AUTO_INCREMENT,
  `id_empleado` INT,
  `id_tercero` INT,
  `fec_inicia` DATE,
  `fec_fin` DATE,
  `id_riesgo` INT,
  `id_user_reg` INT UNSIGNED,
  `fec_reg` DATETIME,
  `id_user_act` INT UNSIGNED,
  `fec_act` DATETIME,
  PRIMARY KEY (`id_novedad`),
  FOREIGN KEY (`id_empleado`) REFERENCES `nom_empleado`(`id_empleado`),
  FOREIGN KEY (`id_tercero`) REFERENCES `nom_terceros`(`id_tn`),
  FOREIGN KEY (`id_riesgo`) REFERENCES `nom_riesgos_laboral`(`id_rlab`),
  FOREIGN KEY (`id_user_reg`) REFERENCES `seg_usuarios_sistema`(`id_usuario`),
  FOREIGN KEY (`id_user_act`) REFERENCES `seg_usuarios_sistema`(`id_usuario`)
);

ALTER TABLE `nom_categoria_tercero`   
  ADD COLUMN `tipo` CHAR(2) NULL  COMMENT 'SS: Para Seguridad Social' AFTER `descripcion`
  
ALTER TABLE `nom_embargos`  
  DROP FOREIGN KEY `nom_embargos_ibfk_2`,
  ADD FOREIGN KEY (`id_juzgado`) REFERENCES `nom_terceros`(`id_tn`);
  
ALTER TABLE `nom_cuota_sindical`  
  DROP FOREIGN KEY `nom_cuota_sindical_ibfk_2`,
  ADD FOREIGN KEY (`id_sindicato`) REFERENCES `nom_terceros`(`id_tn`);
  
ALTER TABLE `nom_horas_ex_trab`   
  ADD COLUMN `estado` TINYINT(1) DEFAULT 0  NULL  COMMENT '0:liquidadas, 1:sin liquidar' AFTER `tipo`;

CREATE TABLE `nom_tipo_liquidacion`(  
  `id_tipo` INT NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(5),
  `descripcion` VARCHAR(200),
  `fec_reg` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tipo`)
) ENGINE=INNODB CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT  INTO `nom_tipo_liquidacion`
	(`id_tipo`,`codigo`,`descripcion`,`fec_reg`) VALUES 
(1,'IN','INICIAL','2025-06-25 09:25:30'),(2,'N','MENSUAL EMPLEADOS','2025-06-25 09:25:41'),(3,'PS','PRESTACIONES SOCIALES','2025-06-25 09:25:54'),(4,'VC','VACACIONES','2025-06-25 09:26:24'),(5,'RA','RETROACTIVA DE NOMINAS','2025-06-25 09:26:51'),(6,'PV','PRIMA DE SERVICIOS','2025-06-25 09:27:07'),(7,'PN','PRIMA DE NAVIDAD','2025-06-25 09:27:23'),(8,'CE','CESANTÍAS','2025-06-25 09:27:26'),(9,'IC','INTERÉS A CESANTÍAS','2025-06-25 09:27:39');

CREATE TABLE `nom_tipo_novedad`(  
  `id_tipo` INT NOT NULL AUTO_INCREMENT,
  `descripcion` VARCHAR(100),
  PRIMARY KEY (`id_tipo`)
) ENGINE=INNODB CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT  INTO `nom_tipo_novedad`
	(`id_tipo`,`descripcion`) 
VALUES (1,'INCAPACIDAD'),(2,'VACACIONES'),(3,'LICENCIAS'),(4,'OTROS');

CREATE TABLE `nom_calendar_novedad`(  
  `id_calendario` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_empleado` INT,
  `id_tipo` INT,
  `id_novedad` INT,
  `fecha` DATE,
  `id_user_reg` INT UNSIGNED,
  `fec_reg` DATETIME,
  `id_user_act` INT UNSIGNED,
  `fec_act` DATETIME,
  PRIMARY KEY (`id_calendario`),
  FOREIGN KEY (`id_empleado`) REFERENCES `nom_empleado`(`id_empleado`),
  FOREIGN KEY (`id_tipo`) REFERENCES `nom_tipo_novedad`(`id_tipo`),
  FOREIGN KEY (`id_user_reg`) REFERENCES `seg_usuarios_sistema`(`id_usuario`),
  FOREIGN KEY (`id_user_act`) REFERENCES `seg_usuarios_sistema`(`id_usuario`)
) ENGINE=INNODB CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `nom_liq_incap`   
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `tipo_liq`;

ALTER TABLE `nom_liq_vac`   
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '0:anulada, 1:Válido' AFTER `tipo_liq`;

ALTER TABLE `nom_liq_licmp`   
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `tipo_liq`;

ALTER TABLE `nom_liq_licluto`   
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1: Válido, 0:Anulado' AFTER `anio_licluto`;

ALTER TABLE `nom_liq_licnr`   
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1: Válido, 0:Anulado' AFTER `anio_licnr`;

ALTER TABLE `nom_liq_indemniza_vac`   
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `vigencia`;

ALTER TABLE `nom_liq_indemniza_vac`   
  ADD COLUMN `dias_liq` INT NULL AFTER `id_indemnizacion`;
ALTER TABLE `nom_liq_bsp`   
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `id_nomina`;
  
ALTER TABLE `nom_liq_cesantias`
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `id_nomina`;
  
ALTER TABLE `nom_liq_compesatorio`
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `id_nomina`;
  
ALTER TABLE `nom_liq_contrato_emp`
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `tot_dias_vac`;
  
ALTER TABLE `nom_liq_descuento`
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `id_nomina`;
  
ALTER TABLE `nom_liq_dias_lab`
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `id_nomina`;
  
ALTER TABLE `nom_liq_dlab_auxt`
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `id_nomina`;
  
ALTER TABLE `nom_liq_embargo`
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `id_nomina`;
  
ALTER TABLE `nom_liq_empleado`
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `vigencia`;
  
ALTER TABLE `nom_liq_horex`
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `id_nomina`;
  
ALTER TABLE `nom_liq_libranza`
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `id_nomina`;
  
ALTER TABLE `nom_liq_parafiscales`
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `id_nomina`;
  
ALTER TABLE `nom_liq_prestaciones_sociales`
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `id_nomina`;
  
ALTER TABLE `nom_liq_prima`
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `id_nomina`;
  
ALTER TABLE `nom_liq_prima_nav`
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `id_nomina`;
  
ALTER TABLE `nom_liq_salario`
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `id_nomina`;
  
ALTER TABLE `nom_liq_segsocial_empdo`
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `id_nomina`;
  
ALTER TABLE `nom_liq_sindicato_aportes`
  ADD COLUMN `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '1:Válido, 0:Anulado' AFTER `id_nomina`;
  
ALTER TABLE `nom_liq_horex`   
  DROP COLUMN `mes_he`, 
  DROP COLUMN `anio_he`, 
  DROP COLUMN `tipo_liq`, 
  ADD COLUMN `id_user_reg` INT UNSIGNED NULL AFTER `val_liq`,
  ADD COLUMN `id_user_act` INT UNSIGNED NULL AFTER `fec_reg`,
  ADD FOREIGN KEY (`id_user_reg`) REFERENCES `seg_usuarios_sistema`(`id_usuario`),
  ADD FOREIGN KEY (`id_user_act`) REFERENCES `seg_usuarios_sistema`(`id_usuario`);

ALTER TABLE `nom_horas_ex_trab`   
  CHANGE `estado` `estado` TINYINT(1) DEFAULT 1  NULL  COMMENT '2:liquidadas, 1:sin liquidar';
  
ALTER TABLE `nom_liq_incap`   
  ADD COLUMN `id_user_reg` INT UNSIGNED NULL AFTER `estado`,
  ADD COLUMN `id_user_act` INT UNSIGNED NULL AFTER `fec_reg`,
  ADD FOREIGN KEY (`id_user_reg`) REFERENCES `seg_usuarios_sistema`(`id_usuario`),
  ADD FOREIGN KEY (`id_user_act`) REFERENCES `seg_usuarios_sistema`(`id_usuario`);

ALTER TABLE `nom_liq_incap` DROP FOREIGN KEY `nom_liq_incap_ibfk_1`;
ALTER TABLE `nom_liq_incap` ADD CONSTRAINT `nom_liq_incap_ibfk_1` FOREIGN KEY (`id_arl`) REFERENCES `nom_terceros`(`id_tn`);

ALTER TABLE `nom_liq_incap` DROP FOREIGN KEY `nom_liq_incap_ibfk_2`;
ALTER TABLE `nom_liq_incap` ADD CONSTRAINT `nom_liq_incap_ibfk_2` FOREIGN KEY (`id_eps`) REFERENCES `nom_terceros`(`id_tn`);

ALTER TABLE `nom_liq_vac`   
  DROP COLUMN `id_contrato`, 
  DROP COLUMN `fec_inicio`, 
  DROP COLUMN `fec_fin`, 
  DROP COLUMN `val_diavac`, 
  DROP COLUMN `val_bsp`, 
  DROP COLUMN `mes_vac`, 
  DROP COLUMN `anio_vac`, 
  DROP COLUMN `tipo_liq`, 
  ADD COLUMN `sal_base` DECIMAL(15,2) NULL AFTER `id_vac`,
  ADD COLUMN `g_rep` DECIMAL(15,2) NULL AFTER `sal_base`,
  ADD COLUMN `aux_tra` DECIMAL(15,2) NULL AFTER `g_rep`,
  ADD COLUMN `aux_alim` DECIMAL(15,2) NULL AFTER `aux_tra`,
  ADD COLUMN `bsp_ant` DECIMAL(15,2) NULL AFTER `aux_alim`,
  ADD COLUMN `psv_ant` DECIMAL(15,2) NULL AFTER `bsp_ant`,
  ADD COLUMN `id_user_reg` INT UNSIGNED NULL AFTER `estado`,
  ADD COLUMN `id_user_act` INT UNSIGNED NULL AFTER `fec_reg`;
  
ALTER TABLE `nom_liq_licmp`   
  DROP COLUMN `fec_inicio`, 
  DROP COLUMN `fec_fin`, 
  DROP COLUMN `mes_lic`, 
  DROP COLUMN `anio_lic`, 
  DROP COLUMN `tipo_liq`, 
  ADD COLUMN `id_user_reg` INT UNSIGNED NULL AFTER `estado`,
  ADD COLUMN `id_user_act` INT UNSIGNED NULL AFTER `fec_reg`,
  ADD FOREIGN KEY (`id_user_reg`) REFERENCES `seg_usuarios_sistema`(`id_usuario`),
  ADD FOREIGN KEY (`id_user_act`) REFERENCES `seg_usuarios_sistema`(`id_usuario`);
  
ALTER TABLE `nom_licenciasmp`   
  CHANGE `tipo` `tipo` CHAR(1) CHARSET utf8mb3 COLLATE utf8mb3_general_ci NULL  COMMENT '1:materna 2:Paterna';

ALTER TABLE `nom_liq_licnr`   
  DROP COLUMN `fec_inicio`, 
  DROP COLUMN `fec_fin`, 
  DROP COLUMN `mes_licnr`, 
  DROP COLUMN `anio_licnr`, 
  ADD COLUMN `id_user_reg` INT UNSIGNED NULL AFTER `estado`,
  ADD COLUMN `id_user_act` INT UNSIGNED NULL AFTER `fec_reg`;

ALTER TABLE `nom_liq_licluto`   
  DROP COLUMN `fec_inicio`, 
  DROP COLUMN `fec_fin`, 
  DROP COLUMN `mes_licluto`, 
  DROP COLUMN `anio_licluto`, 
  ADD COLUMN `id_user_reg` INT UNSIGNED NULL AFTER `estado`,
  ADD COLUMN `id_user_act` INT UNSIGNED NULL AFTER `fec_reg`,
  ADD FOREIGN KEY (`id_user_reg`) REFERENCES `seg_usuarios_sistema`(`id_usuario`),
  ADD FOREIGN KEY (`id_user_act`) REFERENCES `seg_usuarios_sistema`(`id_usuario`);

ALTER TABLE `nom_liq_indemniza_vac`   
  DROP COLUMN `mes`, 
  DROP COLUMN `vigencia`, 
  CHANGE `id_user_reg` `id_user_reg` INT UNSIGNED NULL,
  CHANGE `id_user_at` `id_user_act` INT UNSIGNED NULL,
  ADD FOREIGN KEY (`id_user_reg`) REFERENCES `seg_usuarios_sistema`(`id_usuario`),
  ADD FOREIGN KEY (`id_user_act`) REFERENCES `seg_usuarios_sistema`(`id_usuario`);

CREATE TABLE `nom_valores_liquidacion`(  
  `id_valor` INT NOT NULL,
  `smmlv` DECIMAL(15,2),
  `aux_trans` DECIMAL(15,2),
  `aux_alim` DECIMAL(15,2),
  `uvt` DECIMAL(15,2),
  `base_bsp` DECIMAL(15,2),
  `base_alim` DECIMAL(15,2),
  `min_vital` DECIMAL(15,2),
  `salario` DECIMAL(15,2),
  `tiene_grep` TINYINT(1),
  `prom_horas` DECIMAL(15,2),
  `bsp_ant` DECIMAL(15,2),
  `pri_ser_ant` DECIMAL(15,2),
  `pri_vac_ant` DECIMAL(15,2),
  `pri_nav_ant` DECIMAL(15,2),
  `id_user_reg` INT UNSIGNED,
  `fec_reg` DATETIME,
  `id_user_act` INT UNSIGNED,
  `fec_act` DATETIME,
  `id_nomina` INT,
  PRIMARY KEY (`id_valor`),
  FOREIGN KEY (`id_nomina`) REFERENCES `nom_nominas`(`id_nomina`),
  FOREIGN KEY (`id_user_reg`) REFERENCES `seg_usuarios_sistema`(`id_usuario`),
  FOREIGN KEY (`id_user_act`) REFERENCES `seg_usuarios_sistema`(`id_usuario`)
) ENGINE=INNODB CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `nom_valores_liquidacion`   
  ADD COLUMN `id_empleado` INT NULL AFTER `id_valor`,
  ADD FOREIGN KEY (`id_empleado`) REFERENCES `nom_empleado`(`id_empleado`);



ALTER TABLE `nom_liq_bsp`   
  ADD COLUMN `fec_corte` DATE NULL AFTER `val_bsp`,
  CHANGE `id_user_reg` `id_user_reg` INT UNSIGNED NULL,
  ADD COLUMN `id_user_act` INT UNSIGNED NULL AFTER `fec_reg`,
  ADD COLUMN `fec_act` DATETIME NULL AFTER `id_user_act`,
  ADD FOREIGN KEY (`id_user_reg`) REFERENCES `seg_usuarios_sistema`(`id_usuario`),
  ADD FOREIGN KEY (`id_user_act`) REFERENCES `seg_usuarios_sistema`(`id_usuario`);

UPDATE `nom_liq_bsp` SET  `fec_corte` = CONCAT(`anio`,`mes`,'01');

ALTER TABLE `nom_liq_bsp`   
  DROP COLUMN `mes`, 
  DROP COLUMN `anio`);
  
ALTER TABLE `nom_liq_salario`   
  ADD COLUMN `id_contrato` INT NULL AFTER `sal_base`,
  ADD FOREIGN KEY (`id_contrato`) REFERENCES `nom_contratos_empleados`(`id_contrato_emp`);

ALTER TABLE `nom_valores_liquidacion`   
  CHANGE `id_valor` `id_valor` INT NOT NULL AUTO_INCREMENT;

ALTER TABLE `nom_valores_liquidacion`   
  ADD COLUMN `grep` DECIMAL(15,2) NULL AFTER `salario`;

ALTER TABLE `nom_liq_bsp`   
  ADD COLUMN `tipo` CHAR(1) DEFAULT 'S'   NULL  COMMENT 'M: Manual, S: Sistema, P:Pagada manual' AFTER `fec_corte`;
  
ALTER TABLE `nom_liq_segsocial_empdo`   
  DROP COLUMN `mes`, 
  DROP COLUMN `anio`, 
  DROP COLUMN `tipo_liq`, 
  ADD COLUMN `id_user_reg` INT UNSIGNED NULL AFTER `aporte_rieslab`,
  ADD COLUMN `id_user_act` INT UNSIGNED NULL AFTER `fec_reg`,
  ADD FOREIGN KEY (`id_user_reg`) REFERENCES `seg_usuarios_sistema`(`id_usuario`),
  ADD FOREIGN KEY (`id_user_act`) REFERENCES `seg_usuarios_sistema`(`id_usuario`);

ALTER TABLE `nom_liq_parafiscales`   
  DROP COLUMN `mes_pfis`, 
  DROP COLUMN `anio_pfis`, 
  DROP COLUMN `tipo_liq`, 
  ADD COLUMN `id_user_reg` INT UNSIGNED NULL AFTER `fec_reg`,
  ADD COLUMN `id_user_act` INT UNSIGNED NULL AFTER `fec_act`,
  ADD FOREIGN KEY (`id_user_reg`) REFERENCES `seg_usuarios_sistema`(`id_usuario`),
  ADD FOREIGN KEY (`id_user_act`) REFERENCES `seg_usuarios_sistema`(`id_usuario`);

ALTER TABLE `nom_liq_prestaciones_sociales`   
  DROP COLUMN `mes_prestaciones`, 
  DROP COLUMN `anio_prestaciones`, 
  DROP COLUMN `tipo_liq`, 
  ADD COLUMN `id_user_reg` INT UNSIGNED NULL AFTER `val_bonifica_recrea`,
  ADD COLUMN `id_user_act` INT UNSIGNED NULL AFTER `fec_reg`;