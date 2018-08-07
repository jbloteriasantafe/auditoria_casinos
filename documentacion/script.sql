/* casino */
INSERT INTO casino (nombre,codigo) VALUES ('Melincué','MEL'),('Santa Fe','SFE'),('Rosario','ROS');
/* usuario */
INSERT INTO usuario (user_name,password,nombre,email) VALUES ('nico','nico','Nicolás Fiore','nico@gmail.com');
INSERT INTO usuario (user_name,password,nombre,email) VALUES ('nacho','nacho','Ignacio Gattarelli','nacho@gmail.com');
INSERT INTO usuario (user_name,password,nombre,email) VALUES ('mauro','mauro','Mauro Juarez','mauro@gmail.com');
INSERT INTO usuario (user_name,password,nombre,email) VALUES ('seba','seba','Sebastián Paciuk','seba@gmail.com');
INSERT INTO usuario (user_name,password,nombre,email) VALUES ('javier','javier','Javier Brollo','javier@gmail.com');
INSERT INTO usuario (user_name,password,nombre,email) VALUES ('fisca_mel','fisca_mel','Fiscalizador Melincué','fisca_mel@gmail.com');
INSERT INTO usuario (user_name,password,nombre,email) VALUES ('admin_mel','admin_mel','Administrador Melincué','admin_mel@gmail.com');
INSERT INTO usuario (user_name,password,nombre,email) VALUES ('ctr_fisca_mel','ctr_fisca_mel','Controlador Fiscalización Melincué','ctr_fisca_mel@gmail.com');
INSERT INTO usuario (user_name,password,nombre,email) VALUES ('fisca_sfe','fisca_sfe','Fiscalizador Santa Fe','fisca_mel@gmail.com');
INSERT INTO usuario (user_name,password,nombre,email) VALUES ('admin_sfe','admin_sfe','Administrador Santa Fe','admin_mel@gmail.com');
INSERT INTO usuario (user_name,password,nombre,email) VALUES ('ctr_fisca_sfe','ctr_fisca_sfe','Controlador Fiscalización Santa Fe','ctr_fisca_sfe@gmail.com');
INSERT INTO usuario (user_name,password,nombre,email) VALUES ('fisca_ros','fisca_ros','Fiscalizador Rosario','fisca_ros@gmail.com');
INSERT INTO usuario (user_name,password,nombre,email) VALUES ('admin_ros','admin_ros','Administrador Rosario','admin_ros@gmail.com');
INSERT INTO usuario (user_name,password,nombre,email) VALUES ('ctr_fisca_ros','ctr_fisca_ros','Controlador Fiscalización Rosario','ctr_fisca_ros@gmail.com');
INSERT INTO usuario (user_name,password,nombre,email) VALUES ('audit','audit','Auditor','audit@gmail.com');
/* usuario_tiene_casino */
INSERT INTO usuario_tiene_casino (id_usuario,id_casino) VALUES (1,1),(1,2),(1,3),(2,1),(2,2),(2,3),(3,1),(3,2),(3,3),(4,1),(4,2),(4,3),(5,1),(5,2),(5,3);
INSERT INTO usuario_tiene_casino (id_usuario,id_casino) VALUES (6,1),(7,1),(8,1);
INSERT INTO usuario_tiene_casino (id_usuario,id_casino) VALUES (9,2),(10,2),(11,2);
INSERT INTO usuario_tiene_casino (id_usuario,id_casino) VALUES (12,3),(13,3),(14,3);
INSERT INTO usuario_tiene_casino (id_usuario,id_casino) VALUES (15,1),(15,2),(15,3);
/* rol */
INSERT INTO rol (descripcion) VALUES ('Superusuario');
INSERT INTO rol (descripcion) VALUES ('Administrador');
INSERT INTO rol (descripcion) VALUES ('Auditor');
INSERT INTO rol (descripcion) VALUES ('Controlador Fiscalización');
INSERT INTO rol (descripcion) VALUES ('Fiscalizador');
/* usuario_tiene_rol */
INSERT INTO usuario_tiene_rol (id_usuario,id_rol) VALUES (1,1),(2,1),(3,1),(4,1),(5,1);
INSERT INTO usuario_tiene_rol (id_usuario,id_rol) VALUES (6,5),(7,2),(8,4);
INSERT INTO usuario_tiene_rol (id_usuario,id_rol) VALUES (9,5),(10,2),(11,4);
INSERT INTO usuario_tiene_rol (id_usuario,id_rol) VALUES (12,5),(13,2),(14,4);
INSERT INTO usuario_tiene_rol (id_usuario,id_rol) VALUES (15,3);
/* permiso */
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_progresivos');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_logs_actividades');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_casinos');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_expedientes');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_usuarios');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_roles_permisos');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_disposiciones');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_resoluciones');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_glisoft');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_glihard');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_formulas');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_maquinas');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_islas');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_sectores');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_importaciones');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_relevamientos');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_mtm_a_pedido');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_producidos');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_estadisticas_relevamientos');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_beneficios');
INSERT INTO permiso (descripcion) VALUES ('relevamiento_cargar');
INSERT INTO permiso (descripcion) VALUES ('relevamiento_validar');
INSERT INTO permiso (descripcion) VALUES ('relevamiento_selec_maquinas_por_relevamiento');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_layout');
INSERT INTO permiso (descripcion) VALUES ('ver_seccion_relevamientos_progresivos');
/* rol_tiene_permiso */
INSERT INTO rol_tiene_permiso (id_rol,id_permiso) VALUES (SELECT 1, permiso.id_permiso FROM permiso);
INSERT INTO rol_tiene_permiso (id_rol,id_permiso) VALUES (2,1),(2,4),(2,5),(2,7),(2,8),(2,9),(2,10),(2,11),(2,12),(2,13),(2,14),(2,15),(2,16),(2,17),(2,19);
INSERT INTO rol_tiene_permiso (id_rol,id_permiso) VALUES (3,20),(3,18);
INSERT INTO rol_tiene_permiso (id_rol,id_permiso) VALUES (4,16),(4,17),(4,19),(4,22);
INSERT INTO rol_tiene_permiso (id_rol,id_permiso) VALUES (5,16),(5,21),(5,24),(5,25);
/* Tipo Maquina */
INSERT INTO tipo_maquina (descripcion) VALUES ('Ruleta Electrónica');
INSERT INTO tipo_maquina (descripcion) VALUES ('Juego de Máquinas');
/* Tipo Causa No Toma */
INSERT INTO tipo_causa_no_toma (descripcion) VALUES ('No Anda el Touch');
INSERT INTO tipo_causa_no_toma (descripcion) VALUES ('Máquina en Juego');
INSERT INTO tipo_causa_no_toma (descripcion) VALUES ('Máquina Apagada');
/* Gabinete */
INSERT INTO tipo_gabinete (descripcion) VALUES ('Upright Top Box');
INSERT INTO tipo_gabinete (descripcion) VALUES ('Slant Top');
INSERT INTO tipo_gabinete (descripcion) VALUES ('Bar Top');
INSERT INTO tipo_gabinete (descripcion) VALUES ('Crystal Slant');
/* Tipo Origen */
INSERT INTO tipo_origen (descripcion) VALUES ('Aviso Tecnico a Dirección Casino');
INSERT INTO tipo_origen (descripcion) VALUES ('Relevados en Sala');
INSERT INTO tipo_origen (descripcion) VALUES ('Sistema Concesionario');
/* Tipo Movimiento */
INSERT INTO tipo_movimiento (descripcion) VALUES ('Auditoría');
INSERT INTO tipo_movimiento (descripcion) VALUES ('Egreso Definitivo');
INSERT INTO tipo_movimiento (descripcion) VALUES ('Egreso Por Intervención Técnica');
INSERT INTO tipo_movimiento (descripcion) VALUES ('Egreso Temporal');
INSERT INTO tipo_movimiento (descripcion) VALUES ('Ingreso Inicial');
INSERT INTO tipo_movimiento (descripcion) VALUES ('Modificación');
INSERT INTO tipo_movimiento (descripcion) VALUES ('Reingreso');
/* Certificado Soft */
INSERT INTO gli_soft (nro_archivo, observaciones, id_archivo) VALUES ('CS-34-COL-12-55-122', NULL, NULL);
INSERT INTO gli_soft (nro_archivo, observaciones, id_archivo) VALUES ('MO-73-BAL-15-75-313', NULL, NULL);
/* Certificado Hard */
INSERT INTO gli_hard (nro_archivo, id_archivo) VALUES ('MO-22-BAL-14-07-313',NULL);
/* Expediente */
INSERT INTO expediente (nro_exp_org, nro_exp_interno, nro_exp_control, fecha_iniciacion, iniciador, concepto, ubicacion_fisica, fecha_pase, remitente, destino, nro_folios, tema, anexo, nro_cuerpos, id_casino) VALUES (00302,0145056,6,'2017-03-08','Direccion gral de casinos y bingos', 'CME solicita autorización para dar de alta 5 MTM Bally','P07','2017-03-10','Dpto de Asesoría Jurídica','Direccion Gral de Casinos',28,'MTM','Nota N° 034/2017',1,3);
INSERT INTO expediente (nro_exp_org, nro_exp_interno, nro_exp_control, fecha_iniciacion, iniciador, concepto, ubicacion_fisica, fecha_pase, remitente, destino, nro_folios, tema, anexo, nro_cuerpos, id_casino) VALUES (00302,0138217,3,'2016-08-05','Direccion gral de casinos y bingos', 'Casino de Rosario solicita autorizacion para incorporar 30 MTM','P06','2016-08-16','Dpto de Asesoría Jurídica','Direccion Gral de Casinos',25,'MTM','',0,2);
/* Sector */
INSERT INTO sector (descripcion,id_casino) VALUES ('Sector Fumadores',1),('Sector 1',1),('Sector 2',1),('Sector 3',1);
INSERT INTO sector (descripcion,id_casino) VALUES ('Sector Fumadores',2),('Sector 1',2),('Sector 2',2),('Sector 3',2);
INSERT INTO sector (descripcion,id_casino) VALUES ('Sector Fumadores',3),('Sector Anillo',3),('Sector Subsuelo',3),('Sector Piso 2',3);
/* Isla */
INSERT INTO isla (nro_isla, id_sector, id_casino) VALUES (152,10,3);
INSERT INTO isla (nro_isla, id_sector, id_casino) VALUES (1202,6,2);
INSERT INTO isla (nro_isla, id_sector, id_casino) VALUES (5009,7,2);
INSERT INTO isla (nro_isla, id_sector, id_casino) VALUES (5213,8,2);
/* Estados de la máquina */
INSERT INTO estado_maquina (descripcion) VALUES ('Ingreso');
INSERT INTO estado_maquina (descripcion) VALUES ('Reingreso');
INSERT INTO estado_maquina (descripcion) VALUES ('Egreso Definitivo');
INSERT INTO estado_maquina (descripcion) VALUES ('Egreso Temporal');
INSERT INTO estado_maquina (descripcion) VALUES ('Egreso por Intervención Técnica');
/* Progresivo */
INSERT INTO progresivo (nombre_progresivo, linkeado) VALUES ('Dragon Spin',1);
/* nivel_progresivo */
INSERT INTO nivel_progresivo (nro_nivel,nombre_nivel,porc_oculto,porc_visible,base,maximo,id_progresivo) VALUES (0,'JACKPOT 0',NULL,0.10,20000,NULL,1);
INSERT INTO nivel_progresivo (nro_nivel,nombre_nivel,porc_oculto,porc_visible,base,maximo,id_progresivo) VALUES (1,'JACKPOT 1',NULL,0.15,5000,NULL,1);
INSERT INTO nivel_progresivo (nro_nivel,nombre_nivel,porc_oculto,porc_visible,base,maximo,id_progresivo) VALUES (2,'JACKPOT 2',NULL,0.25,1000,NULL,1);
INSERT INTO nivel_progresivo (nro_nivel,nombre_nivel,porc_oculto,porc_visible,base,maximo,id_progresivo) VALUES (3,'JACKPOT 3',NULL,0.50,500,NULL,1);
INSERT INTO nivel_progresivo (nro_nivel,nombre_nivel,porc_oculto,porc_visible,base,maximo,id_progresivo) VALUES (4,'JACKPOT 4',NULL,1.00,200,NULL,1);
/* Isla_tiene_progresivo */
INSERT INTO isla_tiene_nivel_progresivo (id_isla,id_nivel_progresivo) VALUES (1,1),(1,2),(1,3),(1,4),(1,5);
/* Juego */
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('FU DAO LE 8 LINE','BAL_261852A',1);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft, id_progresivo) VALUES ('Dragon Spin','BAL_254132B',2,1);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('1421 Voyages of Zheng HE','AI020000F0002',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Dragon Journey','AI020000E0004',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Dragons Temple','AI020000F0002',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Game Of The Gods','AI020000C0004',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Golden Eagle','AI020000D0001',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Hot Roulette Enchanted Unicorn','AI020000D0001',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Hot Roulette triple Red Hot 7 25L','AI020000D0001',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Hot Roulette Wolf Run','AI020000D0001',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Juicy Wins','AI020000E0001',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Lady Falconer','AI020000E0001',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Lobstermania 3','AI020000E0004',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Magestic Gorilla','AI020000E0001',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Mythical Warriors: Centaur','AI20000E0001',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Mythical Warriors: Mermaids','AI020000E0001',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Mythical Warriors: Sirens','AI020000F0002',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Oceans Of Gold','AI020000E0001',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Pharaos Fortune','AI020000E0001',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Prince of Thieves','AI020000C0006',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('The Fates','AI020000E0001',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('The Great Winaldo','AI020000D0001',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Winners Choice 2','AI020000D0001',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Wonders Of Africa','AI020000D0001',NULL);
INSERT INTO juego (nombre_juego, cod_identificacion, id_gli_soft) VALUES ('Zillion Gators','AI020000C0007',NULL);
/* Tabla de pago */
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('FDL88_38', 1);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('FDL90_38', 1);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('FDL92_38', 1);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('FDL94_38', 1);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('DragonSpin88', 2);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020001M3JS001', 3);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020005TK1M002', 4);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020001M3KS001', 5);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020005NL8B001', 6);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020005DZAB002', 7);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020001SY2M001', 8);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020002TK7M001', 9);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020001SW1M003', 10);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020005RMEM002', 11);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020005FRAM002', 12);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020001QA5M003', 13);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020005H7WB002', 14);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020005RMBM004', 15);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020005RMCM003', 16);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020005RMDM003', 17);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020005TS8B002', 18);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020005M1JB002', 19);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020001MZ3M001', 20);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020005TM6B003', 21);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020005QI5M002', 22);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020001TN3B002', 23);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020005RX1M002', 24);
INSERT INTO tabla_pago (codigo, id_juego) VALUES ('GI020001NS2B002', 25);
/* Máquina */
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (1487,'Bally','Alpha Pro WAVE','','Créditos','B160772359','',1,1,NULL,NULL,2,2,2,'2017-03-10',NULL,NULL,5,2,1);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (1488,'Bally','Alpha Pro WAVE','','Créditos','B160772372','',1,1,NULL,NULL,2,2,2,'2017-03-10',NULL,NULL,5,2,2);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (1489,'Bally','Alpha Pro WAVE','','Créditos','B160772378','',1,1,NULL,NULL,2,2,2,'2017-03-10',NULL,NULL,5,2,1);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (1490,'Bally','Alpha Pro WAVE','','Créditos','B160772357','',1,1,NULL,NULL,2,2,2,'2017-03-10',NULL,NULL,5,2,2);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (1491,'Bally','Alpha Pro WAVE','','Créditos','B160772356','',1,1,NULL,NULL,2,2,2,'2017-03-10',NULL,NULL,5,2,3);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (469200,'','Ascent','','Créditos','2143739','',0,2,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,6,3,4);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (469300,'','Ascent','','Créditos','2143696','',0,3,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,6,3,5);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (469400,'','Ascent','','Créditos','2143720','',0,2,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,7,4,1);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (469500,'','Ascent','','Créditos','2143740','',0,3,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,7,4,2);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (469600,'','Ascent','','Créditos','2143722','',0,2,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,8,5,4);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (469700,'','Ascent','','Créditos','2143718','',0,3,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,8,5,2);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (469800,'','Ascent','','Créditos','2143742','',0,2,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,9,6,1);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (469900,'','Ascent','','Créditos','2143717','',0,3,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,10,7,3);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (470000,'','Ascent','','Créditos','2143703','',0,2,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,11,8,5);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (470100,'','Ascent','','Créditos','2143701','',0,3,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,12,9,3);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (470200,'','Ascent','','Créditos','2143726','',0,4,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,13,10,1);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (470300,'','Ascent','','Créditos','2143734','',0,2,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,14,11,5);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (470400,'','Ascent','','Créditos','2143736','',0,4,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,15,12,2);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (470500,'','Ascent','','Créditos','2143728','',0,3,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,16,13,2);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (470600,'','Ascent','','Créditos','2143704','',0,4,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,16,13,3);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (470700,'','Ascent','','Créditos','2143705','',0,3,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,17,14,2);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (470800,'','Ascent','','Créditos','2143743','',0,4,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,17,14,1);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (470900,'','Ascent','','Créditos','2143695','',0,4,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,18,15,1);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (471000,'','Ascent','','Créditos','2143735','',0,3,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,19,16,4);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (471100,'','Ascent','','Créditos','2143719','',0,4,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,20,17,2);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (471200,'','Ascent','','Créditos','2143732','',0,3,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,21,18,1);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (471300,'','Ascent','','Créditos','2143721','',0,4,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,21,18,3);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (471400,'','Ascent','','Créditos','2143741','',0,4,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,22,19,4);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (471500,'','Ascent','','Créditos','2143729','',0,4,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,23,20,5);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (471600,'','Ascent','','Créditos','2143700','',0,3,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,24,21,2);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (471700,'','Ascent','','Créditos','2143694','',0,4,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,25,22,5);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (471800,'','Ascent','','Créditos','2143707','',0,3,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,26,23,4);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (471900,'','Ascent','','Créditos','2143706','',0,4,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,26,23,1);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (472000,'','Ascent','','Créditos','2143733','',0,3,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,27,24,2);
INSERT INTO maquina (nro_admin, marca, modelo, desc_marca, unidad_medida, nro_serie, mac, juega_progresivo, id_isla, id_formula, id_gli_hard, id_gli_soft, id_tipo_maquina, id_tipo_gabinete, created_at, updated_at, deleted_at, id_tabla_pago, id_juego, id_estado_maquina) VALUES (472100,'','Ascent','','Créditos','2143716','',0,4,NULL,NULL,NULL,2,2,'2017-05-18',NULL,NULL,28,25,1);
/* Estado relevamiento */
INSERT INTO estado_relevamiento (descripcion) VALUES ('Generado'),('Carga parcial'),('Finalizado'),('Validado');
/* Tipo Ajuste */
INSERT INTO tipo_ajuste(descripcion) VALUES ('Vuelta Coin In');
INSERT INTO tipo_ajuste(descripcion) VALUES ('Vuelta Coin Out');
INSERT INTO tipo_ajuste(descripcion) VALUES ('Vuelta Jackpot');
INSERT INTO tipo_ajuste(descripcion) VALUES ('Vuelta Progresivos');
INSERT INTO tipo_ajuste(descripcion) VALUES ('Reset contadores');
INSERT INTO tipo_ajuste(descripcion) VALUES ('Sin contadores finales');
INSERT INTO tipo_ajuste(descripcion) VALUES ('Error de producido importado');
INSERT INTO tipo_ajuste(descripcion) VALUES ('Cambio de configuración máquina');
