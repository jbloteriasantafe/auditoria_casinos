ALTER TABLE `detalle_relevamiento_apuestas` ADD `multimoneda` TINYINT NOT NULL AFTER `nombre_juego` ,
ADD `id_moneda` INT NULL AFTER `multimoneda` ,
ADD INDEX ( `id_moneda` ) ;
UPDATE `detalle_relevamiento_apuestas` SET `id_moneda` =1 WHERE `id_moneda` IS NULL;
