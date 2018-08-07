CREATE DATABASE  IF NOT EXISTS `bdMTM` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `bdMTM`;
-- MySQL dump 10.13  Distrib 5.7.13, for linux-glibc2.5 (x86_64)
--
-- Host: localhost    Database: bdMTM
-- ------------------------------------------------------
-- Server version	5.7.17-0ubuntu0.16.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `archivo`
--

DROP TABLE IF EXISTS `archivo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `archivo` (
  `id_archivo` int(11) NOT NULL AUTO_INCREMENT,
  `archivo` longblob,
  `nombre_archivo` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id_archivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `archivo`
--

LOCK TABLES `archivo` WRITE;
/*!40000 ALTER TABLE `archivo` DISABLE KEYS */;
/*!40000 ALTER TABLE `archivo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `casino`
--

DROP TABLE IF EXISTS `casino`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `casino` (
  `id_casino` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(45) NOT NULL,
  `codigo` varchar(45) NOT NULL,
  PRIMARY KEY (`id_casino`),
  UNIQUE KEY `nombre_UNIQUE` (`nombre`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `casino`
--

LOCK TABLES `casino` WRITE;
/*!40000 ALTER TABLE `casino` DISABLE KEYS */;
INSERT INTO `casino` VALUES (1,'Santa Fe','SFE'),(2,'Rosario','ROS'),(3,'Melincué','MEL');
/*!40000 ALTER TABLE `casino` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `denominacion_activa`
--

DROP TABLE IF EXISTS `denominacion_activa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `denominacion_activa` (
  `id_maquina` int(11) NOT NULL,
  `id_juego` int(11) NOT NULL,
  `id_tabla_pago` int(11) NOT NULL,
  PRIMARY KEY (`id_maquina`,`id_juego`,`id_tabla_pago`),
  KEY `fk_denominacion_activa_juego_idx` (`id_juego`),
  KEY `fk_denominacion_activa_tabla_pago_idx` (`id_tabla_pago`),
  CONSTRAINT `fk_denominacion_activa_juego` FOREIGN KEY (`id_juego`) REFERENCES `juego` (`id_juego`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_denominacion_activa_maquina` FOREIGN KEY (`id_maquina`) REFERENCES `maquina` (`id_maquina`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_denominacion_activa_tabla_pago` FOREIGN KEY (`id_tabla_pago`) REFERENCES `tabla_pago` (`id_tabla_pago`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `denominacion_activa`
--

LOCK TABLES `denominacion_activa` WRITE;
/*!40000 ALTER TABLE `denominacion_activa` DISABLE KEYS */;
/*!40000 ALTER TABLE `denominacion_activa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_log`
--

DROP TABLE IF EXISTS `detalle_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_log` (
  `id_detalle_log` int(11) NOT NULL AUTO_INCREMENT,
  `campo` varchar(45) NOT NULL,
  `valor` varchar(45) DEFAULT NULL,
  `id_log` int(11) NOT NULL,
  PRIMARY KEY (`id_detalle_log`),
  KEY `fk_detalle_log_log1_idx` (`id_log`),
  CONSTRAINT `fk_detalle_log_log1` FOREIGN KEY (`id_log`) REFERENCES `log` (`id_log`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_log`
--

LOCK TABLES `detalle_log` WRITE;
/*!40000 ALTER TABLE `detalle_log` DISABLE KEYS */;
INSERT INTO `detalle_log` VALUES (1,'nro_exp_org','00001',1),(2,'nro_exp_interno','0000001',1),(3,'nro_exp_control','1',1);
/*!40000 ALTER TABLE `detalle_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `disposicion`
--

DROP TABLE IF EXISTS `disposicion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disposicion` (
  `id_disposicion` int(11) NOT NULL AUTO_INCREMENT,
  `nro_disposicion` varchar(3) NOT NULL,
  `nro_disposicion_anio` varchar(2) NOT NULL,
  `id_expediente` int(11) NOT NULL,
  PRIMARY KEY (`id_disposicion`),
  KEY `fk_disposicion_expediente1_idx` (`id_expediente`),
  CONSTRAINT `fk_disposicion_expediente1` FOREIGN KEY (`id_expediente`) REFERENCES `expediente` (`id_expediente`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disposicion`
--

LOCK TABLES `disposicion` WRITE;
/*!40000 ALTER TABLE `disposicion` DISABLE KEYS */;
/*!40000 ALTER TABLE `disposicion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expediente`
--

DROP TABLE IF EXISTS `expediente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expediente` (
  `id_expediente` int(11) NOT NULL AUTO_INCREMENT,
  `nro_exp_org` varchar(5) NOT NULL,
  `nro_exp_interno` varchar(7) NOT NULL,
  `nro_exp_control` tinyint(1) NOT NULL,
  `fecha_iniciacion` date DEFAULT NULL,
  `iniciador` varchar(250) DEFAULT NULL,
  `concepto` varchar(250) DEFAULT NULL,
  `ubicacion_fisica` varchar(250) DEFAULT NULL,
  `fecha_pase` date DEFAULT NULL,
  `remitente` varchar(250) DEFAULT NULL,
  `destino` varchar(250) DEFAULT NULL,
  `nro_folios` int(11) DEFAULT NULL,
  `tema` varchar(250) DEFAULT NULL,
  `anexo` varchar(250) DEFAULT NULL,
  `nro_cuerpos` int(11) DEFAULT NULL,
  `id_casino` int(11) NOT NULL,
  PRIMARY KEY (`id_expediente`),
  UNIQUE KEY `nro_exp_interno_UNIQUE` (`nro_exp_interno`),
  KEY `fk_expediente_casino1_idx` (`id_casino`),
  CONSTRAINT `fk_expediente_casino1` FOREIGN KEY (`id_casino`) REFERENCES `casino` (`id_casino`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expediente`
--

LOCK TABLES `expediente` WRITE;
/*!40000 ALTER TABLE `expediente` DISABLE KEYS */;
INSERT INTO `expediente` VALUES (1,'00001','0000001',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1);
/*!40000 ALTER TABLE `expediente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expediente_tiene_gli_hard`
--

DROP TABLE IF EXISTS `expediente_tiene_gli_hard`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expediente_tiene_gli_hard` (
  `id_gli_hard` int(11) NOT NULL,
  `id_expediente` int(11) NOT NULL,
  PRIMARY KEY (`id_gli_hard`,`id_expediente`),
  KEY `fk_gli_hard_has_expediente_expediente1_idx` (`id_expediente`),
  KEY `fk_gli_hard_has_expediente_gli_hard1_idx` (`id_gli_hard`),
  CONSTRAINT `fk_gli_hard_has_expediente_expediente1` FOREIGN KEY (`id_expediente`) REFERENCES `expediente` (`id_expediente`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_gli_hard_has_expediente_gli_hard1` FOREIGN KEY (`id_gli_hard`) REFERENCES `gli_hard` (`id_gli_hard`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expediente_tiene_gli_hard`
--

LOCK TABLES `expediente_tiene_gli_hard` WRITE;
/*!40000 ALTER TABLE `expediente_tiene_gli_hard` DISABLE KEYS */;
/*!40000 ALTER TABLE `expediente_tiene_gli_hard` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expediente_tiene_gli_soft`
--

DROP TABLE IF EXISTS `expediente_tiene_gli_soft`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expediente_tiene_gli_soft` (
  `id_expediente` int(11) NOT NULL,
  `id_gli_soft` int(11) NOT NULL,
  PRIMARY KEY (`id_expediente`,`id_gli_soft`),
  KEY `fk_expediente_has_gli_soft_gli_soft1_idx` (`id_gli_soft`),
  KEY `fk_expediente_has_gli_soft_expediente1_idx` (`id_expediente`),
  CONSTRAINT `fk_expediente_has_gli_soft_expediente1` FOREIGN KEY (`id_expediente`) REFERENCES `expediente` (`id_expediente`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_expediente_has_gli_soft_gli_soft1` FOREIGN KEY (`id_gli_soft`) REFERENCES `gli_soft` (`id_gli_soft`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expediente_tiene_gli_soft`
--

LOCK TABLES `expediente_tiene_gli_soft` WRITE;
/*!40000 ALTER TABLE `expediente_tiene_gli_soft` DISABLE KEYS */;
/*!40000 ALTER TABLE `expediente_tiene_gli_soft` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `formula`
--

DROP TABLE IF EXISTS `formula`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `formula` (
  `id_formula` int(11) NOT NULL AUTO_INCREMENT,
  `cont1` varchar(45) DEFAULT NULL,
  `operador1` varchar(1) DEFAULT NULL,
  `cont2` varchar(45) DEFAULT NULL,
  `operador2` varchar(1) DEFAULT NULL,
  `cont3` varchar(45) DEFAULT NULL,
  `operador3` varchar(1) DEFAULT NULL,
  `cont4` varchar(45) DEFAULT NULL,
  `operador4` varchar(1) DEFAULT NULL,
  `cont5` varchar(45) DEFAULT NULL,
  `operador5` varchar(1) DEFAULT NULL,
  `cont6` varchar(45) DEFAULT NULL,
  `operador6` varchar(1) DEFAULT NULL,
  `cont7` varchar(45) DEFAULT NULL,
  `operador7` varchar(1) DEFAULT NULL,
  `cont8` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_formula`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `formula`
--

LOCK TABLES `formula` WRITE;
/*!40000 ALTER TABLE `formula` DISABLE KEYS */;
INSERT INTO `formula` VALUES (1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `formula` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gli_hard`
--

DROP TABLE IF EXISTS `gli_hard`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gli_hard` (
  `id_gli_hard` int(11) NOT NULL AUTO_INCREMENT,
  `nro_archivo` varchar(45) DEFAULT NULL,
  `resultado_evaluacion` varchar(45) DEFAULT NULL,
  `id_archivo` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_gli_hard`),
  UNIQUE KEY `nro_archivo_UNIQUE` (`nro_archivo`),
  UNIQUE KEY `id_archivo_UNIQUE` (`id_archivo`),
  KEY `fk_gli_hard_archivo1_idx` (`id_archivo`),
  CONSTRAINT `fk_gli_hard_archivo1` FOREIGN KEY (`id_archivo`) REFERENCES `archivo` (`id_archivo`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gli_hard`
--

LOCK TABLES `gli_hard` WRITE;
/*!40000 ALTER TABLE `gli_hard` DISABLE KEYS */;
INSERT INTO `gli_hard` VALUES (1,NULL,NULL,NULL);
/*!40000 ALTER TABLE `gli_hard` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gli_soft`
--

DROP TABLE IF EXISTS `gli_soft`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gli_soft` (
  `id_gli_soft` int(11) NOT NULL AUTO_INCREMENT,
  `nro_archivo` varchar(45) DEFAULT NULL,
  `observaciones` varchar(150) DEFAULT NULL,
  `resultado_evaluacion` varchar(45) DEFAULT NULL,
  `id_archivo` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_gli_soft`),
  UNIQUE KEY `id_archivo_UNIQUE` (`id_archivo`),
  UNIQUE KEY `nro_archivo_UNIQUE` (`nro_archivo`),
  KEY `fk_gli_soft_archivo1_idx` (`id_archivo`),
  CONSTRAINT `fk_gli_soft_archivo1` FOREIGN KEY (`id_archivo`) REFERENCES `archivo` (`id_archivo`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gli_soft`
--

LOCK TABLES `gli_soft` WRITE;
/*!40000 ALTER TABLE `gli_soft` DISABLE KEYS */;
INSERT INTO `gli_soft` VALUES (1,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `gli_soft` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `isla`
--

DROP TABLE IF EXISTS `isla`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `isla` (
  `id_isla` int(11) NOT NULL AUTO_INCREMENT,
  `nro_isla` int(11) NOT NULL,
  `cant_maquinas` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_isla`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `isla`
--

LOCK TABLES `isla` WRITE;
/*!40000 ALTER TABLE `isla` DISABLE KEYS */;
/*!40000 ALTER TABLE `isla` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `isla_tiene_progresivo`
--

DROP TABLE IF EXISTS `isla_tiene_progresivo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `isla_tiene_progresivo` (
  `id_progresivo` int(11) NOT NULL,
  `id_isla` int(11) NOT NULL,
  PRIMARY KEY (`id_progresivo`,`id_isla`),
  KEY `fk_progresivo_has_isla_isla1_idx` (`id_isla`),
  KEY `fk_progresivo_has_isla_progresivo1_idx` (`id_progresivo`),
  CONSTRAINT `fk_progresivo_has_isla_isla1` FOREIGN KEY (`id_isla`) REFERENCES `isla` (`id_isla`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_progresivo_has_isla_progresivo1` FOREIGN KEY (`id_progresivo`) REFERENCES `progresivo` (`id_progresivo`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `isla_tiene_progresivo`
--

LOCK TABLES `isla_tiene_progresivo` WRITE;
/*!40000 ALTER TABLE `isla_tiene_progresivo` DISABLE KEYS */;
/*!40000 ALTER TABLE `isla_tiene_progresivo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `juego`
--

DROP TABLE IF EXISTS `juego`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `juego` (
  `id_juego` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_juego` varchar(100) DEFAULT NULL,
  `cod_identificacion` varchar(100) DEFAULT NULL,
  `nro_niv_progresivos` int(11) DEFAULT NULL,
  `id_gli_soft` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_juego`),
  KEY `fk_juego_gli_soft1_idx` (`id_gli_soft`),
  CONSTRAINT `fk_juego_gli_soft1` FOREIGN KEY (`id_gli_soft`) REFERENCES `gli_soft` (`id_gli_soft`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `juego`
--

LOCK TABLES `juego` WRITE;
/*!40000 ALTER TABLE `juego` DISABLE KEYS */;
/*!40000 ALTER TABLE `juego` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `layout`
--

DROP TABLE IF EXISTS `layout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `layout` (
  `id_layout` int(11) NOT NULL AUTO_INCREMENT,
  `sala` varchar(45) DEFAULT NULL,
  `nro_sector` int(11) DEFAULT NULL,
  `desc_sector` varchar(45) DEFAULT NULL,
  `id_casino` int(11) NOT NULL,
  PRIMARY KEY (`id_layout`),
  KEY `fk_layout_casino1_idx` (`id_casino`),
  CONSTRAINT `fk_layout_casino1` FOREIGN KEY (`id_casino`) REFERENCES `casino` (`id_casino`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `layout`
--

LOCK TABLES `layout` WRITE;
/*!40000 ALTER TABLE `layout` DISABLE KEYS */;
/*!40000 ALTER TABLE `layout` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `layout_tiene_maquina`
--

DROP TABLE IF EXISTS `layout_tiene_maquina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `layout_tiene_maquina` (
  `id_layout` int(11) NOT NULL,
  `id_maquina` int(11) NOT NULL,
  PRIMARY KEY (`id_layout`,`id_maquina`),
  KEY `fk_layout_has_maquina_maquina1_idx` (`id_maquina`),
  KEY `fk_layout_has_maquina_layout1_idx` (`id_layout`),
  CONSTRAINT `fk_layout_has_maquina_layout1` FOREIGN KEY (`id_layout`) REFERENCES `layout` (`id_layout`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_layout_has_maquina_maquina1` FOREIGN KEY (`id_maquina`) REFERENCES `maquina` (`id_maquina`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `layout_tiene_maquina`
--

LOCK TABLES `layout_tiene_maquina` WRITE;
/*!40000 ALTER TABLE `layout_tiene_maquina` DISABLE KEYS */;
/*!40000 ALTER TABLE `layout_tiene_maquina` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `accion` varchar(45) NOT NULL,
  `tabla` varchar(45) NOT NULL,
  `id_entidad` int(11) NOT NULL,
  PRIMARY KEY (`id_log`),
  KEY `fk_log_usuario1_idx` (`id_usuario`),
  CONSTRAINT `fk_log_usuario1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log`
--

LOCK TABLES `log` WRITE;
/*!40000 ALTER TABLE `log` DISABLE KEYS */;
INSERT INTO `log` VALUES (1,4,'2017-04-21 12:01:33','alta','expediente',1);
/*!40000 ALTER TABLE `log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maquina`
--

DROP TABLE IF EXISTS `maquina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maquina` (
  `id_maquina` int(11) NOT NULL AUTO_INCREMENT,
  `nro_admin` int(11) DEFAULT NULL,
  `marca` varchar(45) DEFAULT NULL,
  `modelo` varchar(60) DEFAULT NULL,
  `desc_marca` varchar(100) DEFAULT NULL,
  `unidad_medida` varchar(45) DEFAULT NULL,
  `nro_serie` varchar(100) DEFAULT NULL,
  `mac` varchar(100) DEFAULT NULL,
  `juega_progresivo` tinyint(1) DEFAULT NULL,
  `id_isla` int(11) DEFAULT NULL,
  `id_formula` int(11) DEFAULT NULL,
  `id_gli_hard` int(11) DEFAULT NULL,
  `id_gli_soft` int(11) DEFAULT NULL,
  `id_tipo_maquina` int(11) NOT NULL,
  `id_casino` int(11) NOT NULL,
  `id_tipo_gabinete` int(11) NOT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` date DEFAULT NULL,
  `deleted_at` date DEFAULT NULL,
  PRIMARY KEY (`id_maquina`),
  UNIQUE KEY `nro_admin_UNIQUE` (`nro_admin`),
  KEY `fk_maquina_isla1_idx` (`id_isla`),
  KEY `fk_maquina_formula1_idx` (`id_formula`),
  KEY `fk_maquina_gli_hard1_idx` (`id_gli_hard`),
  KEY `fk_maquina_gli_soft1_idx` (`id_gli_soft`),
  KEY `fk_maquina_table11_idx` (`id_tipo_maquina`),
  KEY `fk_maquina_casino1_idx` (`id_casino`),
  KEY `fk_maquina_tipo_gabinete1_idx` (`id_tipo_gabinete`),
  CONSTRAINT `fk_maquina_casino1` FOREIGN KEY (`id_casino`) REFERENCES `casino` (`id_casino`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_maquina_formula1` FOREIGN KEY (`id_formula`) REFERENCES `formula` (`id_formula`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_maquina_gli_hard1` FOREIGN KEY (`id_gli_hard`) REFERENCES `gli_hard` (`id_gli_hard`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_maquina_gli_soft1` FOREIGN KEY (`id_gli_soft`) REFERENCES `gli_soft` (`id_gli_soft`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_maquina_isla1` FOREIGN KEY (`id_isla`) REFERENCES `isla` (`id_isla`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_maquina_table11` FOREIGN KEY (`id_tipo_maquina`) REFERENCES `tipo_maquina` (`id_tipo_maquina`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_maquina_tipo_gabinete1` FOREIGN KEY (`id_tipo_gabinete`) REFERENCES `tipo_gabinete` (`id_tipo_gabinete`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maquina`
--

LOCK TABLES `maquina` WRITE;
/*!40000 ALTER TABLE `maquina` DISABLE KEYS */;
INSERT INTO `maquina` VALUES (1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,1,1,1,1,NULL,NULL,NULL);
/*!40000 ALTER TABLE `maquina` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maquina_juega_progresivo`
--

DROP TABLE IF EXISTS `maquina_juega_progresivo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maquina_juega_progresivo` (
  `id_maquina` int(11) NOT NULL,
  `id_juego` int(11) NOT NULL,
  `id_progresivo` int(11) NOT NULL,
  PRIMARY KEY (`id_maquina`,`id_juego`,`id_progresivo`),
  KEY `fk_maquina_juega_progresivo_juego_idx` (`id_juego`),
  KEY `fk_maquina_juega_progresivo_progresivo_idx` (`id_progresivo`),
  CONSTRAINT `fk_maquina_juega_progresivo_juego` FOREIGN KEY (`id_juego`) REFERENCES `juego` (`id_juego`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_maquina_juega_progresivo_maquina` FOREIGN KEY (`id_maquina`) REFERENCES `maquina` (`id_maquina`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_maquina_juega_progresivo_progresivo` FOREIGN KEY (`id_progresivo`) REFERENCES `progresivo` (`id_progresivo`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maquina_juega_progresivo`
--

LOCK TABLES `maquina_juega_progresivo` WRITE;
/*!40000 ALTER TABLE `maquina_juega_progresivo` DISABLE KEYS */;
/*!40000 ALTER TABLE `maquina_juega_progresivo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimiento`
--

DROP TABLE IF EXISTS `movimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimiento` (
  `id_movimiento` int(11) NOT NULL AUTO_INCREMENT,
  `id_maquina` int(11) NOT NULL,
  `fecha` datetime DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `coinin` double(15,2) DEFAULT NULL,
  `coinout` double(15,2) DEFAULT NULL,
  `jackpot` double(15,2) DEFAULT NULL,
  `denominacion_base` decimal(4,2) DEFAULT NULL,
  `porcentaje_devolucion` decimal(4,2) DEFAULT NULL,
  `apuesta_maxima` int(11) DEFAULT NULL,
  `cant_creditos` int(11) DEFAULT NULL,
  `validado` tinyint(1) DEFAULT NULL,
  `id_tipo_origen` int(11) NOT NULL,
  `id_tipo_movimiento` int(11) NOT NULL,
  PRIMARY KEY (`id_movimiento`),
  KEY `fk_movimiento_tipo_maquina1_idx` (`id_maquina`),
  KEY `fk_movimiento_tipo_origen1_idx` (`id_tipo_origen`),
  KEY `fk_movimiento_tipo_movimiento1_idx` (`id_tipo_movimiento`),
  CONSTRAINT `fk_movimiento_tipo_maquina1` FOREIGN KEY (`id_maquina`) REFERENCES `maquina` (`id_maquina`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_movimiento_tipo_movimiento1` FOREIGN KEY (`id_tipo_movimiento`) REFERENCES `tipo_movimiento` (`id_tipo_movimiento`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_movimiento_tipo_origen1` FOREIGN KEY (`id_tipo_origen`) REFERENCES `tipo_origen` (`id_tipo_origen`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimiento`
--

LOCK TABLES `movimiento` WRITE;
/*!40000 ALTER TABLE `movimiento` DISABLE KEYS */;
/*!40000 ALTER TABLE `movimiento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nivel_progresivo`
--

DROP TABLE IF EXISTS `nivel_progresivo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nivel_progresivo` (
  `id_nivel_progresivo` int(11) NOT NULL AUTO_INCREMENT,
  `nro_nivel` int(11) NOT NULL,
  `nombre_nivel` varchar(60) DEFAULT NULL,
  `porc_oculto` decimal(4,2) DEFAULT NULL,
  `porc_visible` decimal(4,2) DEFAULT NULL,
  `base` decimal(10,2) DEFAULT NULL,
  `maximo` decimal(10,2) DEFAULT NULL,
  `id_progresivo` int(11) NOT NULL,
  PRIMARY KEY (`id_nivel_progresivo`),
  KEY `fk_nivel_progresivo_progresivo1_idx` (`id_progresivo`),
  CONSTRAINT `fk_nivel_progresivo_progresivo1` FOREIGN KEY (`id_progresivo`) REFERENCES `progresivo` (`id_progresivo`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nivel_progresivo`
--

LOCK TABLES `nivel_progresivo` WRITE;
/*!40000 ALTER TABLE `nivel_progresivo` DISABLE KEYS */;
/*!40000 ALTER TABLE `nivel_progresivo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permiso`
--

DROP TABLE IF EXISTS `permiso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permiso` (
  `id_permiso` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_permiso`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permiso`
--

LOCK TABLES `permiso` WRITE;
/*!40000 ALTER TABLE `permiso` DISABLE KEYS */;
INSERT INTO `permiso` VALUES (1,'consulta_log_actividades');
/*!40000 ALTER TABLE `permiso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `progresivo`
--

DROP TABLE IF EXISTS `progresivo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `progresivo` (
  `id_progresivo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_progresivo` varchar(45) DEFAULT NULL,
  `id_tipo_progresivo` int(11) NOT NULL,
  PRIMARY KEY (`id_progresivo`),
  KEY `fk_progresivo_tipo_progresivo1_idx` (`id_tipo_progresivo`),
  CONSTRAINT `fk_progresivo_tipo_progresivo1` FOREIGN KEY (`id_tipo_progresivo`) REFERENCES `tipo_progresivo` (`id_tipo_progresivo`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `progresivo`
--

LOCK TABLES `progresivo` WRITE;
/*!40000 ALTER TABLE `progresivo` DISABLE KEYS */;
/*!40000 ALTER TABLE `progresivo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resolucion`
--

DROP TABLE IF EXISTS `resolucion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resolucion` (
  `id_resolucion` int(11) NOT NULL AUTO_INCREMENT,
  `nro_resolucion` varchar(3) NOT NULL,
  `nro_resolucion_anio` varchar(2) NOT NULL,
  `id_expediente` int(11) NOT NULL,
  PRIMARY KEY (`id_resolucion`),
  KEY `fk_resolucion_expediente1_idx` (`id_expediente`),
  CONSTRAINT `fk_resolucion_expediente1` FOREIGN KEY (`id_expediente`) REFERENCES `expediente` (`id_expediente`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resolucion`
--

LOCK TABLES `resolucion` WRITE;
/*!40000 ALTER TABLE `resolucion` DISABLE KEYS */;
/*!40000 ALTER TABLE `resolucion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol`
--

DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rol` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol`
--

LOCK TABLES `rol` WRITE;
/*!40000 ALTER TABLE `rol` DISABLE KEYS */;
INSERT INTO `rol` VALUES (1,'Superusuario'),(2,'Administrador'),(3,'Fiscalizador'),(4,'Control');
/*!40000 ALTER TABLE `rol` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol_tiene_permiso`
--

DROP TABLE IF EXISTS `rol_tiene_permiso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rol_tiene_permiso` (
  `id_rol` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL,
  PRIMARY KEY (`id_rol`,`id_permiso`),
  KEY `fk_rol_has_permiso_permiso1_idx` (`id_permiso`),
  KEY `fk_rol_has_permiso_rol1_idx` (`id_rol`),
  CONSTRAINT `fk_rol_has_permiso_permiso1` FOREIGN KEY (`id_permiso`) REFERENCES `permiso` (`id_permiso`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_rol_has_permiso_rol1` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol_tiene_permiso`
--

LOCK TABLES `rol_tiene_permiso` WRITE;
/*!40000 ALTER TABLE `rol_tiene_permiso` DISABLE KEYS */;
INSERT INTO `rol_tiene_permiso` VALUES (1,1);
/*!40000 ALTER TABLE `rol_tiene_permiso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tabla_pago`
--

DROP TABLE IF EXISTS `tabla_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tabla_pago` (
  `id_tabla_pago` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(45) NOT NULL,
  `denominacion_base` decimal(4,2) DEFAULT NULL,
  `porc_devolucion_min` decimal(4,2) DEFAULT NULL,
  `porc_devolucion_max` decimal(4,2) DEFAULT NULL,
  `id_juego` int(11) NOT NULL,
  PRIMARY KEY (`id_tabla_pago`),
  KEY `fk_tabla_pago_juego1_idx` (`id_juego`),
  CONSTRAINT `fk_tabla_pago_juego1` FOREIGN KEY (`id_juego`) REFERENCES `juego` (`id_juego`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tabla_pago`
--

LOCK TABLES `tabla_pago` WRITE;
/*!40000 ALTER TABLE `tabla_pago` DISABLE KEYS */;
/*!40000 ALTER TABLE `tabla_pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_gabinete`
--

DROP TABLE IF EXISTS `tipo_gabinete`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_gabinete` (
  `id_tipo_gabinete` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(45) NOT NULL,
  PRIMARY KEY (`id_tipo_gabinete`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_gabinete`
--

LOCK TABLES `tipo_gabinete` WRITE;
/*!40000 ALTER TABLE `tipo_gabinete` DISABLE KEYS */;
INSERT INTO `tipo_gabinete` VALUES (1,'Upright Top Box'),(2,'Slant Top'),(3,'Bar Top');
/*!40000 ALTER TABLE `tipo_gabinete` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_maquina`
--

DROP TABLE IF EXISTS `tipo_maquina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_maquina` (
  `id_tipo_maquina` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(45) NOT NULL,
  PRIMARY KEY (`id_tipo_maquina`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_maquina`
--

LOCK TABLES `tipo_maquina` WRITE;
/*!40000 ALTER TABLE `tipo_maquina` DISABLE KEYS */;
INSERT INTO `tipo_maquina` VALUES (1,'Ruleta Electrónica'),(2,'Juego de Máquinas');
/*!40000 ALTER TABLE `tipo_maquina` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_movimiento`
--

DROP TABLE IF EXISTS `tipo_movimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_movimiento` (
  `id_tipo_movimiento` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(45) NOT NULL,
  PRIMARY KEY (`id_tipo_movimiento`),
  UNIQUE KEY `tipo_movimiento_UNIQUE` (`descripcion`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_movimiento`
--

LOCK TABLES `tipo_movimiento` WRITE;
/*!40000 ALTER TABLE `tipo_movimiento` DISABLE KEYS */;
INSERT INTO `tipo_movimiento` VALUES (1,'Auditoría'),(2,'Egreso Definitivo'),(3,'Egreso Por Intervención Técnica'),(4,'Egreso Temporal'),(5,'Ingreso Inicial'),(6,'Modificación'),(7,'Reingreso');
/*!40000 ALTER TABLE `tipo_movimiento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_origen`
--

DROP TABLE IF EXISTS `tipo_origen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_origen` (
  `id_tipo_origen` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(45) NOT NULL,
  PRIMARY KEY (`id_tipo_origen`),
  UNIQUE KEY `tipo_origen_UNIQUE` (`descripcion`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_origen`
--

LOCK TABLES `tipo_origen` WRITE;
/*!40000 ALTER TABLE `tipo_origen` DISABLE KEYS */;
INSERT INTO `tipo_origen` VALUES (1,'Aviso Tecnico a Dirección Casino'),(2,'Relevados en Sala'),(3,'Sistema Concesionario');
/*!40000 ALTER TABLE `tipo_origen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_progresivo`
--

DROP TABLE IF EXISTS `tipo_progresivo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_progresivo` (
  `id_tipo_progresivo` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(45) NOT NULL,
  PRIMARY KEY (`id_tipo_progresivo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_progresivo`
--

LOCK TABLES `tipo_progresivo` WRITE;
/*!40000 ALTER TABLE `tipo_progresivo` DISABLE KEYS */;
INSERT INTO `tipo_progresivo` VALUES (1,'Linkeado'),(2,'Progresivo'),(3,'StandAlone');
/*!40000 ALTER TABLE `tipo_progresivo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(45) NOT NULL,
  `password` varchar(45) NOT NULL,
  `nombre` varchar(45) DEFAULT NULL,
  `email` varchar(60) NOT NULL,
  `token` varchar(45) DEFAULT NULL,
  `imagen` longblob,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `user_name_UNIQUE` (`user_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,'nico','nico','Nicolás Fiore','nico@gmail.com',NULL,NULL),(2,'nacho','nacho','Ignacio Gattarelli','nacho@gmail.com',NULL,NULL),(3,'mauro','mauro','Mauro Juarez','mauro@gmail.com',NULL,NULL),(4,'seba','seba','Sebastián Paciuk','seba@gmail.com','2aac40d93d87ebfc25637232c69b8f23f050b97e',NULL),(5,'javier','javier','Javier Brollo','javier@gmail.com',NULL,NULL);
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario_tiene_casino`
--

DROP TABLE IF EXISTS `usuario_tiene_casino`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario_tiene_casino` (
  `id_usuario` int(11) NOT NULL,
  `id_casino` int(11) NOT NULL,
  PRIMARY KEY (`id_usuario`,`id_casino`),
  KEY `fk_usuario_has_casino_casino1_idx` (`id_casino`),
  KEY `fk_usuario_has_casino_usuario1_idx` (`id_usuario`),
  CONSTRAINT `fk_usuario_has_casino_casino1` FOREIGN KEY (`id_casino`) REFERENCES `casino` (`id_casino`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_usuario_has_casino_usuario1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_tiene_casino`
--

LOCK TABLES `usuario_tiene_casino` WRITE;
/*!40000 ALTER TABLE `usuario_tiene_casino` DISABLE KEYS */;
INSERT INTO `usuario_tiene_casino` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(1,2),(2,2),(3,2),(4,2),(5,2),(1,3),(2,3),(3,3),(4,3),(5,3);
/*!40000 ALTER TABLE `usuario_tiene_casino` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario_tiene_rol`
--

DROP TABLE IF EXISTS `usuario_tiene_rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario_tiene_rol` (
  `id_usuario` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL,
  PRIMARY KEY (`id_usuario`,`id_rol`),
  KEY `fk_usuario_has_rol_rol1_idx` (`id_rol`),
  KEY `fk_usuario_has_rol_usuario1_idx` (`id_usuario`),
  CONSTRAINT `fk_usuario_has_rol_rol1` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_usuario_has_rol_usuario1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_tiene_rol`
--

LOCK TABLES `usuario_tiene_rol` WRITE;
/*!40000 ALTER TABLE `usuario_tiene_rol` DISABLE KEYS */;
INSERT INTO `usuario_tiene_rol` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(1,2),(2,2),(3,2),(4,2),(5,2),(1,3),(2,3),(3,3),(4,3),(5,3),(1,4),(2,4),(3,4),(4,4),(5,4);
/*!40000 ALTER TABLE `usuario_tiene_rol` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-04-25 12:19:59
