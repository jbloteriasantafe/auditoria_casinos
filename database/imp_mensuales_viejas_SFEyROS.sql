-- phpMyAdmin SQL Dump
-- version 4.0.10deb1ubuntu0.1
-- http://www.phpmyadmin.net
--
-- Servidor: localhost
-- Tiempo de generación: 05-05-2019 a las 03:13:27
-- Versión del servidor: 5.5.62-0ubuntu0.14.04.1
-- Versión de PHP: 5.5.9-1ubuntu4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de datos: `bdmesas`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `importacion_mensual_mesas`
--

CREATE TABLE IF NOT EXISTS `importacion_mensual_mesas` (
  `id_importacion_mensual_mesas` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_csv` varchar(100) DEFAULT NULL,
  `fecha_mes` date DEFAULT NULL,
  `id_casino` int(11) DEFAULT NULL,
  `id_moneda` int(11) DEFAULT NULL,
  `total_utilidad_mensual` double(15,2) DEFAULT NULL,
  `saldo_fichas_mes` double(15,2) DEFAULT NULL,
  `cotizacion_dolar` double(2,2) DEFAULT NULL,
  `cotizacion_euro` double(4,4) DEFAULT NULL,
  `diferencias` double(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `validado` tinyint(4) NOT NULL DEFAULT '0',
  `observacion` varchar(200) DEFAULT NULL,
  `utilidad_calculada` double(15,2) DEFAULT NULL,
  `retiros_mes` double(15,2) DEFAULT NULL,
  `reposiciones_mes` double(15,2) DEFAULT NULL,
  `total_drop_mensual` double(15,2) DEFAULT NULL,
  PRIMARY KEY (`id_importacion_mensual_mesas`),
  KEY `fk_imp_mens_mesas_casino_idx` (`id_casino`),
  KEY `fk_imp_mens_mesas_moneda_idx` (`id_moneda`),
  KEY `id_importacion_mensual_mesas` (`id_importacion_mensual_mesas`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=845 ;

--
-- Volcado de datos para la tabla `importacion_mensual_mesas`
--

INSERT INTO `importacion_mensual_mesas` (`id_importacion_mensual_mesas`, `nombre_csv`, `fecha_mes`, `id_casino`, `id_moneda`, `total_utilidad_mensual`, `saldo_fichas_mes`, `cotizacion_dolar`, `cotizacion_euro`, `diferencias`, `created_at`, `updated_at`, `deleted_at`, `validado`, `observacion`, `utilidad_calculada`, `retiros_mes`, `reposiciones_mes`, `total_drop_mensual`) VALUES
(389, 'no matter.-', '2012-08-01', 2, 1, 5805640.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:29', '2019-04-19 03:28:29', NULL, 1, 'Autogenerado a partir de informes finales', 5805640.75, 0.00, 0.00, 0.00),
(390, 'no matter.-', '2012-09-01', 2, 1, 4758565.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:29', '2019-04-19 03:28:29', NULL, 1, 'Autogenerado a partir de informes finales', 4758565.25, 0.00, 0.00, 0.00),
(391, 'no matter.-', '2012-10-01', 2, 1, 5383125.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:29', '2019-04-19 03:28:29', NULL, 1, 'Autogenerado a partir de informes finales', 5383125.00, 0.00, 0.00, 0.00),
(392, 'no matter.-', '2012-11-01', 2, 1, 4993671.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:29', '2019-04-19 03:28:29', NULL, 1, 'Autogenerado a partir de informes finales', 4993671.50, 0.00, 0.00, 0.00),
(393, 'no matter.-', '2012-12-01', 2, 1, 4387493.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:29', '2019-04-19 03:28:29', NULL, 1, 'Autogenerado a partir de informes finales', 4387493.75, 0.00, 0.00, 0.00),
(394, 'no matter.-', '2012-01-01', 2, 1, 6075652.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:29', '2019-04-19 03:28:29', NULL, 1, 'Autogenerado a partir de informes finales', 6075652.25, 0.00, 0.00, 0.00),
(395, 'no matter.-', '2012-02-01', 2, 1, 5856852.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:29', '2019-04-19 03:28:29', NULL, 1, 'Autogenerado a partir de informes finales', 5856852.00, 0.00, 0.00, 0.00),
(396, 'no matter.-', '2012-03-01', 2, 1, 5144217.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:29', '2019-04-19 03:28:29', NULL, 1, 'Autogenerado a partir de informes finales', 5144217.25, 0.00, 0.00, 0.00),
(397, 'no matter.-', '2012-04-01', 2, 1, 4554957.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:29', '2019-04-19 03:28:29', NULL, 1, 'Autogenerado a partir de informes finales', 4554957.75, 0.00, 0.00, 0.00),
(398, 'no matter.-', '2012-05-01', 2, 1, 6582380.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:29', '2019-04-19 03:28:29', NULL, 1, 'Autogenerado a partir de informes finales', 6582380.75, 0.00, 0.00, 0.00),
(399, 'no matter.-', '2012-06-01', 2, 1, 6470463.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 6470463.00, 0.00, 0.00, 0.00),
(400, 'no matter.-', '2012-07-01', 2, 1, 5601730.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 5601730.75, 0.00, 0.00, 0.00),
(401, 'no matter.-', '2013-08-01', 2, 1, 7066913.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 7066913.50, 0.00, 0.00, 0.00),
(402, 'no matter.-', '2013-09-01', 2, 1, 5612749.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 5612749.75, 0.00, 0.00, 0.00),
(403, 'no matter.-', '2013-10-01', 2, 1, 4761647.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 4761647.25, 0.00, 0.00, 0.00),
(404, 'no matter.-', '2013-11-01', 2, 1, 5107180.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 5107180.00, 0.00, 0.00, 0.00),
(405, 'no matter.-', '2013-12-01', 2, 1, 5587433.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 5587433.75, 0.00, 0.00, 0.00),
(406, 'no matter.-', '2013-01-01', 2, 1, 5825067.90, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 5825067.90, 0.00, 0.00, 0.00),
(407, 'no matter.-', '2013-02-01', 2, 1, 4610696.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 4610696.25, 0.00, 0.00, 0.00),
(408, 'no matter.-', '2013-03-01', 2, 1, 5810558.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 5810558.75, 0.00, 0.00, 0.00),
(409, 'no matter.-', '2013-04-01', 2, 1, 5808825.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 5808825.25, 0.00, 0.00, 0.00),
(410, 'no matter.-', '2013-05-01', 2, 1, 6327413.81, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 6327413.81, 0.00, 0.00, 0.00),
(411, 'no matter.-', '2013-06-01', 2, 1, 7067528.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 7067528.00, 0.00, 0.00, 0.00),
(412, 'no matter.-', '2013-07-01', 2, 1, 6997450.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 6997450.25, 0.00, 0.00, 0.00),
(413, 'no matter.-', '2014-08-01', 2, 1, 7835650.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 7835650.50, 0.00, 0.00, 0.00),
(414, 'no matter.-', '2014-09-01', 2, 1, 7145189.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 7145189.25, 0.00, 0.00, 0.00),
(415, 'no matter.-', '2014-10-01', 2, 1, 6826875.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 6826875.50, 0.00, 0.00, 0.00),
(416, 'no matter.-', '2014-11-01', 2, 1, 7283144.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 7283144.00, 0.00, 0.00, 0.00),
(417, 'no matter.-', '2014-12-01', 2, 1, 6953916.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 6953916.25, 0.00, 0.00, 0.00),
(418, 'no matter.-', '2014-01-01', 2, 1, 7204523.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 7204523.25, 0.00, 0.00, 0.00),
(419, 'no matter.-', '2014-02-01', 2, 1, 7369400.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 7369400.25, 0.00, 0.00, 0.00),
(420, 'no matter.-', '2014-03-01', 2, 1, 7792662.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 7792662.25, 0.00, 0.00, 0.00),
(421, 'no matter.-', '2014-04-01', 2, 1, 8755725.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 8755725.00, 0.00, 0.00, 0.00),
(422, 'no matter.-', '2014-05-01', 2, 1, 8275323.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 8275323.00, 0.00, 0.00, 0.00),
(423, 'no matter.-', '2014-06-01', 2, 1, 7158682.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 7158682.00, 0.00, 0.00, 0.00),
(424, 'no matter.-', '2014-07-01', 2, 1, 8396952.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 8396952.25, 0.00, 0.00, 0.00),
(425, 'no matter.-', '2015-08-01', 2, 1, 9373903.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 9373903.75, 0.00, 0.00, 0.00),
(426, 'no matter.-', '2015-09-01', 2, 1, 6552939.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 6552939.25, 0.00, 0.00, 0.00),
(427, 'no matter.-', '2015-10-01', 2, 1, 8699292.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 8699292.25, 0.00, 0.00, 0.00),
(428, 'no matter.-', '2015-11-01', 2, 1, 8902319.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 8902319.25, 0.00, 0.00, 0.00),
(429, 'no matter.-', '2015-12-01', 2, 1, 8035102.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 8035102.50, 0.00, 0.00, 0.00),
(430, 'no matter.-', '2015-01-01', 2, 1, 8325888.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 8325888.75, 0.00, 0.00, 0.00),
(431, 'no matter.-', '2015-02-01', 2, 1, 9898711.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 9898711.50, 0.00, 0.00, 0.00),
(432, 'no matter.-', '2015-03-01', 2, 1, 9096210.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 9096210.25, 0.00, 0.00, 0.00),
(433, 'no matter.-', '2015-04-01', 2, 1, 8300559.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 8300559.75, 0.00, 0.00, 0.00),
(434, 'no matter.-', '2015-05-01', 2, 1, 12328023.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 12328023.00, 0.00, 0.00, 0.00),
(435, 'no matter.-', '2015-06-01', 2, 1, 11194957.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 11194957.75, 0.00, 0.00, 0.00),
(436, 'no matter.-', '2015-07-01', 2, 1, 9996671.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 9996671.25, 0.00, 0.00, 0.00),
(437, 'no matter.-', '2016-08-01', 2, 1, 10767762.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 10767762.50, 0.00, 0.00, 0.00),
(438, 'no matter.-', '2016-09-01', 2, 1, 10629856.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 10629856.25, 0.00, 0.00, 0.00),
(439, 'no matter.-', '2016-10-01', 2, 1, 11993921.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 11993921.25, 0.00, 0.00, 0.00),
(440, 'no matter.-', '2016-11-01', 2, 1, 11165637.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 11165637.50, 0.00, 0.00, 0.00),
(441, 'no matter.-', '2016-12-01', 2, 1, 9474584.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 9474584.25, 0.00, 0.00, 0.00),
(442, 'no matter.-', '2016-01-01', 2, 1, 11709973.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 11709973.75, 0.00, 0.00, 0.00),
(443, 'no matter.-', '2016-02-01', 2, 1, 11140332.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 11140332.50, 0.00, 0.00, 0.00),
(444, 'no matter.-', '2016-03-01', 2, 1, 11922678.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 11922678.25, 0.00, 0.00, 0.00),
(445, 'no matter.-', '2016-04-01', 2, 1, 11795153.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 11795153.00, 0.00, 0.00, 0.00),
(446, 'no matter.-', '2016-05-01', 2, 1, 11309443.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 11309443.75, 0.00, 0.00, 0.00),
(447, 'no matter.-', '2016-06-01', 2, 1, 12095599.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 12095599.50, 0.00, 0.00, 0.00),
(448, 'no matter.-', '2016-07-01', 2, 1, 14328233.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 14328233.75, 0.00, 0.00, 0.00),
(449, 'no matter.-', '2017-08-01', 2, 1, 13543257.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 13543257.50, 0.00, 0.00, 0.00),
(450, 'no matter.-', '2017-09-01', 2, 1, 13558649.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 13558649.75, 0.00, 0.00, 0.00),
(451, 'no matter.-', '2017-10-01', 2, 1, 14154715.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 14154715.00, 0.00, 0.00, 0.00),
(452, 'no matter.-', '2017-11-01', 2, 1, 10238219.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 10238219.75, 0.00, 0.00, 0.00),
(453, 'no matter.-', '2017-12-01', 2, 1, 9363726.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 9363726.25, 0.00, 0.00, 0.00),
(454, 'no matter.-', '2017-01-01', 2, 1, 15845331.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 15845331.25, 0.00, 0.00, 0.00),
(455, 'no matter.-', '2017-02-01', 2, 1, 11631762.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 11631762.50, 0.00, 0.00, 0.00),
(456, 'no matter.-', '2017-03-01', 2, 1, 16709750.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 16709750.00, 0.00, 0.00, 0.00),
(457, 'no matter.-', '2017-04-01', 2, 1, 11813897.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 11813897.50, 0.00, 0.00, 0.00),
(458, 'no matter.-', '2017-05-01', 2, 1, 15000337.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 15000337.00, 0.00, 0.00, 0.00),
(459, 'no matter.-', '2017-06-01', 2, 1, 14092262.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 14092262.25, 0.00, 0.00, 0.00),
(460, 'no matter.-', '2017-07-01', 2, 1, 16578027.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 16578027.50, 0.00, 0.00, 0.00),
(461, 'no matter.-', '2018-08-01', 2, 1, 17956437.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 17956437.50, 0.00, 0.00, 0.00),
(462, 'no matter.-', '2018-09-01', 2, 1, 15944749.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 15944749.50, 0.00, 0.00, 0.00),
(463, 'no matter.-', '2018-10-01', 2, 1, 18814031.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 18814031.25, 0.00, 0.00, 0.00),
(464, 'no matter.-', '2018-11-01', 2, 1, 14455944.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 14455944.75, 0.00, 0.00, 0.00),
(465, 'no matter.-', '2018-12-01', 2, 1, 14157907.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 14157907.00, 0.00, 0.00, 0.00),
(466, 'no matter.-', '2018-01-01', 2, 1, 18067841.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 18067841.25, 0.00, 0.00, 0.00),
(467, 'no matter.-', '2018-02-01', 2, 1, 13206608.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 13206608.50, 0.00, 0.00, 0.00),
(468, 'no matter.-', '2018-03-01', 2, 1, 16286184.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 16286184.25, 0.00, 0.00, 0.00),
(469, 'no matter.-', '2018-04-01', 2, 1, 19458076.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 19458076.50, 0.00, 0.00, 0.00),
(470, 'no matter.-', '2018-05-01', 2, 1, 14664492.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:30', '2019-04-19 03:28:30', NULL, 1, 'Autogenerado a partir de informes finales', 14664492.50, 0.00, 0.00, 0.00),
(471, 'no matter.-', '2018-06-01', 2, 1, 16263380.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 16263380.00, 0.00, 0.00, 0.00),
(472, 'no matter.-', '2018-07-01', 2, 1, 17454470.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 17454470.00, 0.00, 0.00, 0.00),
(473, 'no matter.-', '2011-10-01', 3, 1, 3255928.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 3255928.75, 0.00, 0.00, 0.00),
(474, 'no matter.-', '2011-11-01', 3, 1, 2317809.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2317809.00, 0.00, 0.00, 0.00),
(475, 'no matter.-', '2011-12-01', 3, 1, 2133637.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2133637.00, 0.00, 0.00, 0.00),
(476, 'no matter.-', '2011-01-01', 3, 1, 1900497.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 1900497.50, 0.00, 0.00, 0.00),
(477, 'no matter.-', '2011-02-01', 3, 1, 2347176.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2347176.75, 0.00, 0.00, 0.00),
(478, 'no matter.-', '2011-03-01', 3, 1, 1953501.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 1953501.00, 0.00, 0.00, 0.00),
(479, 'no matter.-', '2011-04-01', 3, 1, 2306781.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2306781.75, 0.00, 0.00, 0.00),
(480, 'no matter.-', '2011-05-01', 3, 1, 2209538.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2209538.75, 0.00, 0.00, 0.00),
(481, 'no matter.-', '2011-06-01', 3, 1, 2022249.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2022249.75, 0.00, 0.00, 0.00),
(482, 'no matter.-', '2011-07-01', 3, 1, 2752043.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2752043.25, 0.00, 0.00, 0.00),
(483, 'no matter.-', '2011-08-01', 3, 1, 2706305.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2706305.25, 0.00, 0.00, 0.00),
(484, 'no matter.-', '2011-09-01', 3, 1, 3183769.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 3183769.50, 0.00, 0.00, 0.00),
(485, 'no matter.-', '2012-10-01', 3, 1, 3446178.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 3446178.50, 0.00, 0.00, 0.00),
(486, 'no matter.-', '2012-11-01', 3, 1, 2350589.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2350589.50, 0.00, 0.00, 0.00),
(487, 'no matter.-', '2012-12-01', 3, 1, 1778136.75, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 1778136.75, 0.00, 0.00, 0.00),
(488, 'no matter.-', '2012-01-01', 3, 1, 2766163.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2766163.00, 0.00, 0.00, 0.00),
(489, 'no matter.-', '2012-02-01', 3, 1, 2035461.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2035461.25, 0.00, 0.00, 0.00),
(490, 'no matter.-', '2012-03-01', 3, 1, 1891065.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 1891065.00, 0.00, 0.00, 0.00),
(491, 'no matter.-', '2012-04-01', 3, 1, 2489389.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2489389.50, 0.00, 0.00, 0.00),
(492, 'no matter.-', '2012-05-01', 3, 1, 2847142.25, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2847142.25, 0.00, 0.00, 0.00),
(493, 'no matter.-', '2012-06-01', 3, 1, 3429129.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 3429129.50, 0.00, 0.00, 0.00),
(494, 'no matter.-', '2012-07-01', 3, 1, 3441191.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 3441191.50, 0.00, 0.00, 0.00),
(495, 'no matter.-', '2012-08-01', 3, 1, 3108619.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 3108619.00, 0.00, 0.00, 0.00),
(496, 'no matter.-', '2012-09-01', 3, 1, 2944476.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2944476.50, 0.00, 0.00, 0.00),
(497, 'no matter.-', '2013-10-01', 3, 1, 3668951.50, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 3668951.50, 0.00, 0.00, 0.00),
(498, 'no matter.-', '2013-11-01', 3, 1, 2541144.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2541144.00, 0.00, 0.00, 0.00),
(499, 'no matter.-', '2013-12-01', 3, 1, 1425700.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 1425700.00, 0.00, 0.00, 0.00),
(500, 'no matter.-', '2013-01-01', 3, 1, 2342394.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2342394.00, 0.00, 0.00, 0.00),
(501, 'no matter.-', '2013-02-01', 3, 1, 1621765.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 1621765.00, 0.00, 0.00, 0.00),
(502, 'no matter.-', '2013-03-01', 3, 1, 2953965.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2953965.00, 0.00, 0.00, 0.00),
(503, 'no matter.-', '2013-04-01', 3, 1, 2462592.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2462592.00, 0.00, 0.00, 0.00),
(504, 'no matter.-', '2013-05-01', 3, 1, 2746660.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2746660.00, 0.00, 0.00, 0.00),
(505, 'no matter.-', '2013-06-01', 3, 1, 1960317.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 1960317.00, 0.00, 0.00, 0.00),
(506, 'no matter.-', '2013-07-01', 3, 1, 2964400.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2964400.00, 0.00, 0.00, 0.00),
(507, 'no matter.-', '2013-08-01', 3, 1, 3948604.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 3948604.00, 0.00, 0.00, 0.00),
(508, 'no matter.-', '2013-09-01', 3, 1, 3414396.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 3414396.00, 0.00, 0.00, 0.00),
(509, 'no matter.-', '2014-10-01', 3, 1, 3662944.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 3662944.00, 0.00, 0.00, 0.00),
(510, 'no matter.-', '2014-11-01', 3, 1, 3250614.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 3250614.00, 0.00, 0.00, 0.00),
(511, 'no matter.-', '2014-12-01', 3, 1, 3485122.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 3485122.00, 0.00, 0.00, 0.00),
(512, 'no matter.-', '2014-01-01', 3, 1, 2561151.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2561151.00, 0.00, 0.00, 0.00),
(513, 'no matter.-', '2014-02-01', 3, 1, 4602935.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 4602935.00, 0.00, 0.00, 0.00),
(514, 'no matter.-', '2014-03-01', 3, 1, 2862488.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2862488.00, 0.00, 0.00, 0.00),
(515, 'no matter.-', '2014-04-01', 3, 1, 2648641.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2648641.00, 0.00, 0.00, 0.00),
(516, 'no matter.-', '2014-05-01', 3, 1, 3143545.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 3143545.00, 0.00, 0.00, 0.00),
(517, 'no matter.-', '2014-06-01', 3, 1, 3557633.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 3557633.00, 0.00, 0.00, 0.00),
(518, 'no matter.-', '2014-07-01', 3, 1, 2819758.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 2819758.00, 0.00, 0.00, 0.00),
(519, 'no matter.-', '2014-08-01', 3, 1, 4106309.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 4106309.00, 0.00, 0.00, 0.00),
(520, 'no matter.-', '2014-09-01', 3, 1, 5002263.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 5002263.00, 0.00, 0.00, 0.00),
(521, 'no matter.-', '2015-10-01', 3, 1, 3327623.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 3327623.00, 0.00, 0.00, 0.00),
(522, 'no matter.-', '2015-11-01', 3, 1, 3537344.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 3537344.00, 0.00, 0.00, 0.00),
(523, 'no matter.-', '2015-12-01', 3, 1, 4753832.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 4753832.00, 0.00, 0.00, 0.00),
(524, 'no matter.-', '2015-01-01', 3, 1, 3756511.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 3756511.00, 0.00, 0.00, 0.00),
(525, 'no matter.-', '2015-02-01', 3, 1, 4136778.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 4136778.00, 0.00, 0.00, 0.00),
(526, 'no matter.-', '2015-03-01', 3, 1, 4139829.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 4139829.00, 0.00, 0.00, 0.00),
(527, 'no matter.-', '2015-04-01', 3, 1, 4552005.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 4552005.00, 0.00, 0.00, 0.00),
(528, 'no matter.-', '2015-05-01', 3, 1, 4069711.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 4069711.00, 0.00, 0.00, 0.00),
(529, 'no matter.-', '2015-06-01', 3, 1, 4525111.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 4525111.00, 0.00, 0.00, 0.00),
(530, 'no matter.-', '2015-07-01', 3, 1, 4693045.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 4693045.00, 0.00, 0.00, 0.00),
(531, 'no matter.-', '2015-08-01', 3, 1, 6381922.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 6381922.00, 0.00, 0.00, 0.00),
(532, 'no matter.-', '2015-09-01', 3, 1, 5966823.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 5966823.00, 0.00, 0.00, 0.00),
(533, 'no matter.-', '2016-10-01', 3, 1, 6727593.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 6727593.00, 0.00, 0.00, 0.00),
(534, 'no matter.-', '2016-11-01', 3, 1, 6100117.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 6100117.00, 0.00, 0.00, 0.00),
(535, 'no matter.-', '2016-12-01', 3, 1, 4215590.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 4215590.00, 0.00, 0.00, 0.00),
(536, 'no matter.-', '2016-01-01', 3, 1, 4758407.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 4758407.00, 0.00, 0.00, 0.00),
(537, 'no matter.-', '2016-02-01', 3, 1, 4581628.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 4581628.00, 0.00, 0.00, 0.00),
(538, 'no matter.-', '2016-03-01', 3, 1, 5222440.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 5222440.00, 0.00, 0.00, 0.00),
(539, 'no matter.-', '2016-04-01', 3, 1, 4179030.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 4179030.00, 0.00, 0.00, 0.00),
(540, 'no matter.-', '2016-05-01', 3, 1, 6510285.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:31', '2019-04-19 03:28:31', NULL, 1, 'Autogenerado a partir de informes finales', 6510285.00, 0.00, 0.00, 0.00),
(541, 'no matter.-', '2016-06-01', 3, 1, 6850150.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 6850150.00, 0.00, 0.00, 0.00),
(542, 'no matter.-', '2016-07-01', 3, 1, 5768416.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 5768416.00, 0.00, 0.00, 0.00),
(543, 'no matter.-', '2016-08-01', 3, 1, 5649075.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 5649075.00, 0.00, 0.00, 0.00),
(544, 'no matter.-', '2016-09-01', 3, 1, 6900075.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 6900075.00, 0.00, 0.00, 0.00),
(545, 'no matter.-', '2017-10-01', 3, 1, 8993940.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 8993940.00, 0.00, 0.00, 0.00),
(546, 'no matter.-', '2017-11-01', 3, 1, 6290275.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 6290275.00, 0.00, 0.00, 0.00),
(547, 'no matter.-', '2017-12-01', 3, 1, 6231450.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 6231450.00, 0.00, 0.00, 0.00),
(548, 'no matter.-', '2017-01-01', 3, 1, 4814960.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 4814960.00, 0.00, 0.00, 0.00),
(549, 'no matter.-', '2017-02-01', 3, 1, 7538950.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 7538950.00, 0.00, 0.00, 0.00),
(550, 'no matter.-', '2017-03-01', 3, 1, 5428420.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 5428420.00, 0.00, 0.00, 0.00),
(551, 'no matter.-', '2017-04-01', 3, 1, 5176795.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 5176795.00, 0.00, 0.00, 0.00),
(552, 'no matter.-', '2017-05-01', 3, 1, 5480340.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 5480340.00, 0.00, 0.00, 0.00),
(553, 'no matter.-', '2017-06-01', 3, 1, 4466595.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 4466595.00, 0.00, 0.00, 0.00),
(554, 'no matter.-', '2017-07-01', 3, 1, 6730690.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 6730690.00, 0.00, 0.00, 0.00),
(555, 'no matter.-', '2017-08-01', 3, 1, 7728050.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 7728050.00, 0.00, 0.00, 0.00),
(556, 'no matter.-', '2017-09-01', 3, 1, 7737655.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 7737655.00, 0.00, 0.00, 0.00),
(557, 'no matter.-', '2018-10-01', 3, 1, 21809715.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 21809715.00, 0.00, 0.00, 0.00),
(558, 'no matter.-', '2018-11-01', 3, 1, 8476570.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 8476570.00, 0.00, 0.00, 0.00),
(559, 'no matter.-', '2018-12-01', 3, 1, 75772945.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 75772945.00, 0.00, 0.00, 0.00),
(560, 'no matter.-', '2018-01-01', 3, 1, 9577125.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 9577125.00, 0.00, 0.00, 0.00),
(561, 'no matter.-', '2018-02-01', 3, 1, 8769690.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 8769690.00, 0.00, 0.00, 0.00),
(562, 'no matter.-', '2018-03-01', 3, 1, 8207405.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 8207405.00, 0.00, 0.00, 0.00),
(563, 'no matter.-', '2018-04-01', 3, 1, 8733525.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 8733525.00, 0.00, 0.00, 0.00),
(564, 'no matter.-', '2018-05-01', 3, 1, 8961695.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 8961695.00, 0.00, 0.00, 0.00),
(565, 'no matter.-', '2018-06-01', 3, 1, 10467180.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 10467180.00, 0.00, 0.00, 0.00),
(566, 'no matter.-', '2018-07-01', 3, 1, 8088390.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 8088390.00, 0.00, 0.00, 0.00),
(567, 'no matter.-', '2018-08-01', 3, 1, 11108785.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 11108785.00, 0.00, 0.00, 0.00),
(568, 'no matter.-', '2018-09-01', 3, 1, 11115030.00, 0.00, NULL, NULL, 0.00, '2019-04-19 03:28:32', '2019-04-19 03:28:32', NULL, 1, 'Autogenerado a partir de informes finales', 11115030.00, 0.00, 0.00, 0.00);

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `importacion_mensual_mesas`
--
ALTER TABLE `importacion_mensual_mesas`
  ADD CONSTRAINT `fk_imp_mens_mesas_casino` FOREIGN KEY (`id_casino`) REFERENCES `casino` (`id_casino`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_imp_mens_mesas_moneda` FOREIGN KEY (`id_moneda`) REFERENCES `moneda` (`id_moneda`) ON DELETE NO ACTION ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
