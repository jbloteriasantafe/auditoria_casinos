-- =====================================================================
--  DEPLOY Eventualidades — Esquema + lookup + seed + permisos
--  Correr contra PRODUCCIÓN con:
--     mysql -h <HOST_PROD> -u <USER> -p <BASE_PROD> --default-character-set=utf8mb4 < 01_esquema_eventualidades.sql
--  Es IDEMPOTENTE: se puede correr más de una vez sin romper nada
--  (IF NOT EXISTS / INSERT IGNORE / chequeos de columna).
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) TABLAS NUEVAS
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `procedimiento` (
  `id_procedimiento` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden` int(11) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_procedimiento`),
  UNIQUE KEY `uk_procedimiento_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `casino_tiene_procedimiento` (
  `id_casino` int(11) NOT NULL,
  `id_procedimiento` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_casino`,`id_procedimiento`),
  KEY `fk_ctp_pr` (`id_procedimiento`),
  CONSTRAINT `fk_ctp_ca` FOREIGN KEY (`id_casino`) REFERENCES `casino` (`id_casino`) ON DELETE CASCADE,
  CONSTRAINT `fk_ctp_pr` FOREIGN KEY (`id_procedimiento`) REFERENCES `procedimiento` (`id_procedimiento`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `eventualidad_tiene_procedimiento` (
  `id_eventualidades` int(11) NOT NULL,
  `id_procedimiento` int(11) NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacion` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_eventualidades`,`id_procedimiento`),
  KEY `fk_etp_pr` (`id_procedimiento`),
  CONSTRAINT `fk_etp_ev` FOREIGN KEY (`id_eventualidades`) REFERENCES `eventualidades` (`id_eventualidades`) ON DELETE CASCADE,
  CONSTRAINT `fk_etp_pr` FOREIGN KEY (`id_procedimiento`) REFERENCES `procedimiento` (`id_procedimiento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resumen_diario` (
  `id_resumen_diario` int(11) NOT NULL AUTO_INCREMENT,
  `id_casino` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no_visado',
  `id_usuario_visador` int(11) DEFAULT NULL,
  `fecha_visado` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_resumen_diario`),
  UNIQUE KEY `uk_resumen_casino_fecha` (`id_casino`,`fecha`),
  KEY `fk_rd_us` (`id_usuario_visador`),
  CONSTRAINT `fk_rd_ca` FOREIGN KEY (`id_casino`) REFERENCES `casino` (`id_casino`) ON DELETE CASCADE,
  CONSTRAINT `fk_rd_us` FOREIGN KEY (`id_usuario_visador`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `observacion_resumen_diario` (
  `id_observacion_resumen_diario` int(11) NOT NULL AUTO_INCREMENT,
  `id_resumen_diario` int(11) NOT NULL,
  `id_usuario_generador` int(11) NOT NULL,
  `observacion` varchar(5000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_archivo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_observacion_resumen_diario`),
  KEY `idx_ord_resumen` (`id_resumen_diario`),
  KEY `fk_ord_us` (`id_usuario_generador`),
  CONSTRAINT `fk_ord_rd` FOREIGN KEY (`id_resumen_diario`) REFERENCES `resumen_diario` (`id_resumen_diario`) ON DELETE CASCADE,
  CONSTRAINT `fk_ord_us` FOREIGN KEY (`id_usuario_generador`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `archivo_observacion_eventualidad` (
  `id_archivo_observacion_eventualidad` int(11) NOT NULL AUTO_INCREMENT,
  `id_observacion_eventualidades` int(11) NOT NULL,
  `filename` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_archivo_observacion_eventualidad`),
  KEY `idx_aoe_obs` (`id_observacion_eventualidades`),
  CONSTRAINT `fk_aoe_obs` FOREIGN KEY (`id_observacion_eventualidades`) REFERENCES `observacion_eventualidades` (`id_observacion_eventualidades`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `archivo_observacion_resumen` (
  `id_archivo_observacion_resumen` int(11) NOT NULL AUTO_INCREMENT,
  `id_observacion_resumen_diario` int(11) NOT NULL,
  `filename` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_archivo_observacion_resumen`),
  KEY `idx_aor_obs` (`id_observacion_resumen_diario`),
  CONSTRAINT `fk_aor_obs` FOREIGN KEY (`id_observacion_resumen_diario`) REFERENCES `observacion_resumen_diario` (`id_observacion_resumen_diario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2) LOOKUP: estado 0 = SIN TERMINAR (borradores). El id es 0 sobre una
--    columna AUTO_INCREMENT -> hace falta NO_AUTO_VALUE_ON_ZERO.
-- ---------------------------------------------------------------------
SET @old_sql_mode = @@SESSION.sql_mode;
SET SESSION sql_mode = CONCAT(@@SESSION.sql_mode, ',NO_AUTO_VALUE_ON_ZERO');
INSERT IGNORE INTO `estado_eventualidad` (`id_estado_eventualidad`, `estado_eventualidad`)
VALUES (0, 'SIN TERMINAR');
SET SESSION sql_mode = @old_sql_mode;

-- ---------------------------------------------------------------------
-- 3) SEED del catálogo de 14 procedimientos. UNIQUE(nombre) -> idempotente.
--    Los nombres deben coincidir EXACTO con los del JSON viejo (la migración
--    de las eventualidades viejas matchea por nombre).
-- ---------------------------------------------------------------------
INSERT IGNORE INTO `procedimiento` (`nombre`, `orden`, `activo`) VALUES
('Toma de Contadores',                    10, 1),
('Contadores a Pedido',                   20, 1),
('Toma de Progresivos',                   30, 1),
('Control Ambiental',                     40, 1),
('Control de Layout Total',               50, 1),
('Control de Layout Parcial',             60, 1),
('Egreso y Reingreso de MTM',             70, 1),
('Informes de Turnos Extras',             80, 1),
('Relevamiento Torneo de Poker',          90, 1),
('Bingo Tradicional',                    100, 1),
('Solicitudes de Reemplazo / Licencia',  110, 1),
('Solicitud de Autoexclusión',           120, 1),
('Aperturas de Mesas de Paño',           130, 1),
('Valores de Apuesta de Mesas de Paño',  140, 1);

-- Asignar TODOS los procedimientos activos a TODOS los casinos (estado inicial).
-- PK (id_casino,id_procedimiento) -> idempotente. Luego se ajusta por casino vía el ABM.
INSERT IGNORE INTO `casino_tiene_procedimiento` (`id_casino`, `id_procedimiento`, `activo`)
SELECT c.id_casino, p.id_procedimiento, 1
FROM casino c
CROSS JOIN procedimiento p
WHERE p.activo = 1;

-- ---------------------------------------------------------------------
-- 4) PERMISOS: abm_procedimientos + visar_resumen_diario, a SUPERUSUARIO y ADMINISTRADOR.
--    descripcion es UNIQUE y rol_tiene_permiso tiene PK -> idempotente.
-- ---------------------------------------------------------------------
INSERT IGNORE INTO `permiso` (`descripcion`) VALUES
('abm_procedimientos'),
('visar_resumen_diario');

INSERT IGNORE INTO `rol_tiene_permiso` (`id_rol`, `id_permiso`)
SELECT r.id_rol, p.id_permiso
FROM rol r
CROSS JOIN permiso p
WHERE r.descripcion IN ('SUPERUSUARIO', 'ADMINISTRADOR')
  AND p.descripcion IN ('abm_procedimientos', 'visar_resumen_diario');

-- ---------------------------------------------------------------------
-- 5) Asegurar columnas created_at/updated_at en observacion_eventualidades
--    (el modelo ahora usa timestamps=true para registrar la fecha de cada
--    observación). Se agregan SÓLO si no existen.
-- ---------------------------------------------------------------------
SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name   = 'observacion_eventualidades'
    AND column_name  = 'created_at'
);
SET @ddl = IF(@col_exists = 0,
  'ALTER TABLE observacion_eventualidades ADD COLUMN created_at TIMESTAMP NULL DEFAULT NULL, ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL',
  'DO 0');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================================
--  FIN. Verificación rápida (correr a mano y revisar):
--    SELECT COUNT(*) FROM procedimiento;                            -- 14 (+ los que ya hubiera)
--    SELECT COUNT(*) FROM casino_tiene_procedimiento;               -- 14 * #casinos
--    SELECT * FROM estado_eventualidad ORDER BY id_estado_eventualidad;  -- incluye 0
--    SELECT descripcion FROM permiso WHERE descripcion IN ('abm_procedimientos','visar_resumen_diario');
-- =====================================================================
