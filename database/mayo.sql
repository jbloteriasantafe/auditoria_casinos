ALTER TABLE `detalle_relevamiento_apuestas` ADD `multimoneda` TINYINT NOT NULL AFTER `nombre_juego` ,
ADD `id_moneda` INT NULL AFTER `multimoneda` ,
ADD INDEX ( `id_moneda` ) ;
UPDATE `detalle_relevamiento_apuestas` SET `id_moneda` =1 WHERE `id_moneda` IS NULL;

--ver si hace falta ->mepa que no
ALTER TABLE `informe_fiscalizadores` ADD `turnos_sin_minimo` VARCHAR( 200 ) NOT NULL AFTER `id_apuesta_minima_juego` ,
ADD `mesas_relevadas_abiertas` MEDIUMTEXT NOT NULL AFTER `turnos_sin_minimo` ,
ADD `mesas_importadas_abiertas` MEDIUMTEXT NOT NULL AFTER `mesas_relevadas_abiertas` ,
ADD `mesas_con_diferencia` MEDIUMTEXT NOT NULL AFTER `mesas_importadas_abiertas` ,
ADD `ap_sin_validar` INT NOT NULL DEFAULT '0' AFTER `mesas_con_diferencia` ,
ADD `cie_sin_validar` INT NOT NULL DEFAULT '0' AFTER `ap_sin_validar` ,
ADD `aperturas_sorteadas` DOUBLE( 15, 2 ) NOT NULL DEFAULT '0' AFTER `cie_sin_validar` ;



CREATE TABLE IF NOT EXISTS `informe_fiscalizacion_tiene_valor_minimo` (
  `id_informe_fiscalizacion_tiene_valor_minimo` int(11) NOT NULL AUTO_INCREMENT,
  `id_apuesta_minima_juego` int(11) NOT NULL,
  `id_informe_fiscalizadores` int(11) NOT NULL,
  `cantidad_cumplieron` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_informe_fiscalizacion_tiene_valor_minimo`),
  KEY `idx_minimo_apuestas_tiene_fiscc` (`id_apuesta_minima_juego`),
  KEY `idx_informe_tiene_valor_ap_minima` (`id_informe_fiscalizadores`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


--verrr
ALTER TABLE `apuesta_minima_juego` CHANGE `id_apuesta_minima` `id_apuesta_minima_juego` INT( 11 ) NOT NULL AUTO_INCREMENT ;


--
ALTER TABLE `importacion_diaria_mesas` ADD `nombre_csv` VARCHAR(100) NULL DEFAULT NULL AFTER `id_importacion_diaria_mesas`;
ALTER TABLE `importacion_mensual_mesas` ADD `nombre_csv` VARCHAR(100) NULL AFTER `id_importacion_mensual_mesas`;
ALTER TABLE `comando_a_ejecutar` CHANGE `nombre_comando` `nombre_comando` VARCHAR(60) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;

ALTER TABLE `importacion_mensual_mesas` ADD `saldo_fichas_mes` DOUBLE(15,2) NULL AFTER `total_utilidad_mensual`;
ALTER TABLE `importacion_mensual_mesas` CHANGE `diferencias` `diferencias` DOUBLE(15,2) NOT NULL DEFAULT '0';
ALTER TABLE `detalle_importacion_mensual_mesas` CHANGE `utilidad_calculada` `utilidad_calculada_dia` DOUBLE(15,2) NULL DEFAULT NULL;
ALTER TABLE `detalle_importacion_mensual_mesas` ADD `created_at` TIMESTAMP NULL AFTER `reposiciones_dia`, ADD `updated_at` TIMESTAMP NULL AFTER `created_at`, ADD `deleted_at` TIMESTAMP NULL AFTER `updated_at`;



CREATE TABLE IF NOT EXISTS `campo_modificado_mesas` (
  `id_campo_modificado` int(11) NOT NULL AUTO_INCREMENT,
  `id_importacion_diaria_mesas` int(11) NOT NULL,
  `id_entidad` int(11) NOT NULL,
  `nombre_entidad` varchar(30) NOT NULL,
  `nombre_del_campo` varchar(30) NOT NULL,
  `valor_anterior` varchar(50) NOT NULL,
  `valor_nuevo` varchar(50) NOT NULL,
  `id_entidad_extra` int(11) DEFAULT NULL,
  `nombre_entidad_extra` varchar(30) DEFAULT NULL,
  `accion` varchar(15) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_campo_modificado`)
) ENGINE=InnoDB;



ALTER TABLE `detalle_importacion_diaria_mesas`
  DROP `hold`;

  ALTER TABLE `detalle_importacion_mensual_mesas`
  DROP `droop`,
  DROP `hold`;









--ver si lo de ficha_tiene_casino ya estaba


CREATE TABLE IF NOT EXISTS `ficha_tiene_casino` (
  `id_ficha_tiene_casino` int(11) NOT NULL AUTO_INCREMENT,
  `id_ficha` int(11) NOT NULL,
  `id_casino` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_ficha_tiene_casino`),
  KEY `idx_ficha_tiene_casino` (`id_ficha`),
  KEY `idx_casino_con_ficha_tiene_casino` (`id_casino`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=76 ;

--
-- Volcado de datos para la tabla `ficha_tiene_casino`
--

INSERT INTO `ficha_tiene_casino` (`id_ficha_tiene_casino`, `id_ficha`, `id_casino`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 28, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(2, 10, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(3, 9, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(4, 8, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(5, 7, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(6, 30, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(7, 6, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(8, 5, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(9, 32, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(10, 4, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(11, 3, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(12, 34, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(13, 36, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(14, 2, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(15, 1, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(16, 29, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(17, 11, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(18, 25, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(19, 24, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(20, 23, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(21, 31, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(22, 21, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(23, 20, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(24, 33, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(25, 18, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(26, 17, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(27, 35, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(28, 37, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(29, 27, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(30, 14, 3, '2018-05-02 09:57:03', '2019-05-02 09:57:03', NULL),
(31, 28, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(32, 10, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(33, 9, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(34, 8, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(35, 7, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(36, 30, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(37, 6, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(38, 5, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(39, 32, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(40, 4, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(41, 3, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(42, 34, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(43, 36, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(44, 2, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(45, 1, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(46, 29, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(47, 11, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(48, 25, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(49, 24, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(50, 23, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(51, 31, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(52, 21, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(53, 20, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(54, 33, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(55, 18, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(56, 17, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(57, 35, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(58, 37, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(59, 27, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(60, 14, 2, '2018-05-02 09:57:03', '2019-05-02 09:57:38', NULL),
(61, 28, 1, '2018-05-02 09:57:03', '2019-05-02 09:58:10', NULL),
(62, 10, 1, '2018-05-02 09:57:03', '2019-05-02 09:58:10', NULL),
(63, 9, 1, '2018-05-02 09:57:03', '2019-05-02 09:58:10', NULL),
(64, 8, 1, '2018-05-02 09:57:03', '2019-05-02 09:58:10', NULL),
(65, 7, 1, '2018-05-02 09:57:03', '2019-05-02 09:58:10', NULL),
(66, 30, 1, '2018-05-02 09:57:03', '2019-05-02 09:58:10', NULL),
(67, 6, 1, '2018-05-02 09:57:03', '2019-05-02 09:58:10', NULL),
(68, 5, 1, '2018-05-02 09:57:03', '2019-05-02 09:58:10', NULL),
(69, 32, 1, '2018-05-02 09:57:03', '2019-05-02 09:58:10', NULL),
(70, 4, 1, '2018-05-02 09:57:03', '2019-05-02 09:58:10', NULL),
(71, 3, 1, '2018-05-02 09:57:03', '2019-05-02 09:58:10', NULL),
(72, 34, 1, '2018-05-02 09:57:03', '2019-05-02 09:58:10', NULL),
(73, 36, 1, '2018-05-02 09:57:03', '2019-05-02 09:58:10', NULL),
(74, 2, 1, '2018-05-02 09:57:03', '2019-05-02 09:58:10', NULL),
(75, 1, 1, '2018-05-02 09:57:03', '2019-05-02 09:58:10', NULL);
