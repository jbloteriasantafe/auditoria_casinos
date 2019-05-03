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
