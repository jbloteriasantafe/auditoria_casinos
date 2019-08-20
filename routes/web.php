<?php
use Illuminate\Http\Request;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
/***********
genérico
***********/
/*NOTIF*/
Route::get('/marcarComoLeidaNotif',function(){
  $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
  $usuario['usuario']->unreadNotifications->markAsRead();
});


Route::get('generico',function(){
    return view('generico ');
});
/***********
Index
***********/
Route::get('/',function(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    return view('seccionInicio' ,['ultimas_visitadas' =>$usuario->secciones_recientes]);
});
// Route::get('/',function(){
//     return view('inicioNuevo');
// });
Route::get('login',function(){
    return view('index');
});
Route::get('inicio',function(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    return view('seccionInicio' ,['ultimas_visitadas' =>$usuario->secciones_recientes]);
});

Route::get('configCuenta','UsuarioController@configUsuario');
Route::post('configCuenta/modificarPassword','UsuarioController@modificarPassword');
Route::post('configCuenta/modificarImagen','UsuarioController@modificarImagen');
Route::post('configCuenta/modificarDatos','UsuarioController@modificarDatos');
Route::post('logout','AuthenticationController@logout');
Route::post('login','AuthenticationController@login');
/***********
Log Actividades
***********/
Route::get('logActividades','LogController@buscarTodo')->middleware('tiene_permiso:ver_seccion_logs_actividades');
Route::get('logActividades/buscarLogActividades','LogController@buscarLogActividades');
Route::get('logActividades/obtenerLogActividad/{id}','LogController@obtenerLogActividad');
/***********
Progresivos
***********/
Route::get('progresivos','ProgresivoController@buscarTodos')->middleware('tiene_permiso:ver_seccion_progresivos');
Route::post('progresivos/buscarProgresivos','ProgresivoController@buscarProgresivos');
Route::get('progresivos/buscarMaquinas/{id_casino}','ProgresivoController@buscarMaquinas');
Route::get('progresivos/obtenerProgresivo/{id_progresivo}','ProgresivoController@obtenerProgresivo');
Route::post('progresivos/crearProgresivo','ProgresivoController@crearProgresivo');
Route::post('progresivos/modificarProgresivo/{id_progresivo}','ProgresivoController@modificarProgresivo');
Route::delete('progresivos/eliminarProgresivo/{id_progresivo}','ProgresivoController@eliminarProgresivo');
Route::post('progresivos/crearProgresivosIndividuales','ProgresivoController@crearProgresivosIndividuales');
Route::post('progresivos/buscarProgresivosIndividuales','ProgresivoController@buscarProgresivosIndividuales');
Route::post('progresivos/modificarProgresivosIndividuales','ProgresivoController@modificarProgresivosIndividuales');
/***********
Casinos
***********/
Route::group(['prefix' => 'casinos','middleware' => 'tiene_permiso:ver_seccion_casinos'], function () {
  Route::get('/','CasinoController@buscarTodo');
  Route::get('/obtenerCasino/{id?}','CasinoController@obtenerCasino');
  Route::post('/guardarCasino','CasinoController@guardarCasino');
  Route::get('/obtenerTurno/{id}','CasinoController@obtenerTurno');
  Route::post('/modificarCasino','CasinoController@modificarCasino');
  Route::delete('/eliminarCasino/{id}','CasinoController@eliminarCasino');
  Route::get('/get', 'CasinoController@getAll');
  Route::get('/getCasinos', 'CasinoController@getParaUsuario');
  Route::get('/getMeses/{id_casino}', 'CasinoController@meses');
  Route::get('/getFichas','CasinoController@getFichas');
});



/***********
Expedientes
***********/
Route::get('expedientes','ExpedienteController@buscarTodo')->middleware('tiene_permiso:ver_seccion_expedientes');
Route::get('expedientes/obtenerExpediente/{id}','ExpedienteController@obtenerExpediente');
Route::post('expedientes/guardarExpediente','ExpedienteController@guardarExpediente');
Route::post('expedientes/modificarExpediente','ExpedienteController@modificarExpediente');
Route::delete('expedientes/eliminarExpediente/{id}','ExpedienteController@eliminarExpediente');
Route::post('expedientes/buscarExpedientes','ExpedienteController@buscarExpedientes');
Route::get('expedientes/buscarExpedientePorNumero/{busqueda}','ExpedienteController@buscarExpedientePorNumero');
Route::get('expedientes/tiposMovimientos/{id_expediente}','ExpedienteController@tiposMovimientos');
/***********
Usuarios
***********/
Route::get('usuarios','UsuarioController@buscarTodo')->middleware('tiene_permiso:ver_seccion_usuarios');
Route::post('usuarios/buscar','UsuarioController@buscarUsuarios');
Route::get('usuarios/buscar/{id}','UsuarioController@buscarUsuario');
Route::get('usuarios/quienSoy' ,'UsuarioController@quienSoy');
Route::post('usuarios/guardarUsuario','UsuarioController@guardarUsuario');
Route::post('usuarios/modificarUsuario','UsuarioController@modificarUsuario');
Route::delete('usuarios/eliminarUsuario','UsuarioController@eliminarUsuario');
Route::get('usuarios/imagen','UsuarioController@leerImagenUsuario');
Route::get('usuarios/buscarUsuariosPorNombre/{nombre}','UsuarioController@buscarUsuariosPorNombre');
Route::get('usuarios/buscarUsuariosPorNombre/{nombre}/relevamiento/{id_relevamiento}','UsuarioController@buscarUsuariosPorNombreYRelevamiento');
Route::get('usuarios/buscarUsuariosPorNombreYCasino/{id_casino}/{nombre}','UsuarioController@buscarUsuariosPorNombreYCasino');
Route::get('usuarios/usuarioTienePermisos','AuthenticationController@usuarioTienePermisos');
Route::post('usuarios/reestablecerContraseña','UsuarioController@reestablecerContraseña');
/***********
Roles y permisos
***********/
Route::post('roles/buscar','RolController@buscarRoles');
Route::post('permisos/buscar','PermisoController@buscarPermisos');
Route::get('roles','RolController@buscarTodo')->middleware('tiene_permiso:ver_seccion_roles_permisos');
Route::post('permiso/guardar','PermisoController@guardarPermiso');
Route::post('rol/guardar','RolController@guardarRol');
Route::post('rol/modificar','RolController@modificarRol');
Route::post('permiso/modificar','PermisoController@modificarPermiso');
Route::get('permiso/getAll','PermisoController@getAll');
Route::get('rol/getAll','RolController@getAll');
Route::post('permiso/buscarPermisosPorRoles',"PermisoController@buscarPermisosPorRoles");
/***********
Borrar permiso
***********/
Route::delete('permiso/{id}','PermisoController@eliminarPermiso');
Route::delete('rol/{id}','RolController@eliminarRol');
Route::get('rol/{id}','RolController@getRol');
Route::get('permiso/{id}','PermisoController@getPermiso');
/***********
Juegos
***********/
Route::get('juegos','JuegoController@buscarTodo')->middleware('tiene_permiso:ver_seccion_juegos');
Route::get('juegos/obtenerJuego/{id?}','JuegoController@obtenerJuego');
Route::post('juegos/guardarJuego','JuegoController@guardarJuego');
Route::post('juegos/modificarJuego','JuegoController@modificarJuego');
Route::delete('juegos/eliminarJuego/{id}','JuegoController@eliminarJuego');
Route::get('juegos/obtenerTablasDePago/{id}','JuegoController@obtenerTablasDePago');
Route::get('juego/buscarJuegos/{busqueda}','JuegoController@buscarJuegoPorCodigoYNombre');
Route::post('juegos/buscar','JuegoController@buscarJuegos');
/***********
PackJuego
***********/
Route::get('packJuegos','PackJuegoController@buscarTodo')->middleware('tiene_permiso:ver_seccion_juegos');
Route::get('packJuego/buscarPackJuegos/{busqueda}','PackJuegoController@buscarPackJuegoPorNombre');
Route::POST('packJuegos/buscar','PackJuegoController@buscar');
Route::get('packJuegos/obtenerPackJuego/{id}','PackJuegoController@obtenerPackJuego');
Route::get('packJuegos/obtenerJuegos/{id}','PackJuegoController@obtenerJuegosDePack');
Route::post('packJuego/guardarPackJuego','PackJuegoController@guardarPackJuego');
Route::post('packJuego/modificarPackJuego','PackJuegoController@modificarPackJuego');
Route::post('packJuego/asociarPackJuego','PackJuegoController@asociarPackJuego');
Route::post('packJuego/asociarMtmJuegosPack','PackJuegoController@asociarMtmJuegosPack');
Route::delete('packJuegos/eliminarPackJuego/{id}','PackJuegoController@eliminarPack');
Route::get('packJuegos/obtenerJuegosMTM/{id_maquina}','PackJuegoController@obtenerJuegosDePackMTM');
/***********
Disposiciones
***********/
Route::get('disposiciones','DisposicionController@buscarTodoDisposiciones')->middleware('tiene_permiso:ver_seccion_disposiciones');
Route::get('resoluciones','ResolucionController@buscarTodoResoluciones');
Route::post('resoluciones/buscar','ResolucionController@buscarResolucion')->middleware('tiene_permiso:ver_seccion_resoluciones');
Route::post('disposiciones/buscar','DisposicionController@buscarDispocisiones');
/***********
Notas
***********/
Route::get('notas','NotaController@buscarTodoNotas')->middleware('tiene_permiso:ver_seccion_resoluciones');
Route::post('notas/buscar','NotaController@buscarNotas')->middleware('tiene_permiso:ver_seccion_resoluciones');
Route::get('notas/consulta-nota/{id}','NotaController@consultaMovimientosNota');
Route::delete('notas/eliminar-nota/{id}','NotaController@eliminarNotaCompleta');
 /***********
    GLI soft
 ************/
 Route::get('certificadoSoft','GliSoftController@buscarTodo')->middleware('tiene_permiso:ver_seccion_glisoft');
 Route::post('glisofts/guardarGliSoft','GliSoftController@guardarGliSoft');
 Route::get('glisofts/pdf/{id}','GliSoftController@leerArchivoGliSoft');
 Route::get('glisofts/obtenerGliSoft/{id}','GliSoftController@obtenerGliSoft');
 Route::delete('glisofts/eliminarGliSoft/{id}','GliSoftController@eliminarGLI');
 Route::post('glisoft/buscarGliSoft','GliSoftController@buscarGliSofts');
 Route::post('glisofts/modificarGliSoft','GliSoftController@modificarGliSoft');
 Route::get('glisofts/buscarGliSoftsPorNroArchivo/{nro_archivo}','GliSoftController@buscarGliSoftsPorNroArchivo');
/***********
GliHards
***********/
Route::get('certificadoHard','GliHardController@buscarTodo')->middleware('tiene_permiso:ver_seccion_glihard');
Route::post('glihards/buscarGliHard','GliHardController@buscarGliHard');
Route::get('glihards/pdf/{id}','GliHardController@leerArchivoGliHard');
Route::get('glihards/obtenerGliHard/{id}','GliHardController@obtenerGliHard');
Route::post('glihards/guardarGliHard','GliHardController@guardarGliHard');
Route::post('glihards/modificarGliHard','GliHardController@modificarGliHard');
Route::delete('glihards/eliminarGliHard/{id}','GliHardController@eliminarGliHard');
Route::get('glihards/buscarGliHardsPorNroArchivo/{nro_archivo}','GliHardController@buscarGliHardsPorNroArchivo');
/**********
Formulas
***********/
Route::get('formulas','FormulaController@buscarTodo')->middleware('tiene_permiso:ver_seccion_formulas');
Route::get('formulas/buscarFormulaPorCampos/{input}','FormulaController@buscarPorCampos');
Route::get('formulas/buscarFormulas','FormulaController@buscarFormula');
Route::post('formulas/guardarFormula','FormulaController@guardarFormula');
Route::get('formulas/obtenerFormula/{id}','FormulaController@obtenerFormula');
Route::post('formulas/modificarFormula','FormulaController@modificarFormula');
Route::post('formulas/asociarMaquinas','FormulaController@asociarMaquinas');
Route::delete('formulas/eliminarFormula/{id}','FormulaController@eliminarFormula');
/***********
Seccion MTM
************/
Route::get('maquinas','MTMController@buscarTodo')->middleware('tiene_permiso:ver_seccion_maquinas');
Route::get('maquinas/{id}','MTMController@buscarTodo');
Route::post('maquinas/guardarMaquina', 'MTMController@guardarMaquina');
Route::post('maquinas/modificarMaquina', 'MTMController@modificarMaquina');
Route::post('maquinas/buscarMaquinas', 'MTMController@buscarMaquinas');
Route::get('maquinas/obtenerMTM/{id}', 'MTMController@obtenerMTM');
Route::get('maquinas/obtenerMTMReducido/{id}', 'MTMController@obtenerMTMReducido');
Route::get('/maquinas/obtenerConfiguracionMaquina/{id}', 'MTMController@obtenerConfiguracionMaquina');
Route::get('maquinas/obtenerMTMEnCasino/{casino}/{id}', 'MTMController@obtenerMTMEnCasino');
Route::get('maquinas/obtenerMTMEnCasinoMovimientos/{id_casino}/{id_mov}/{id_maq}','MTMController@obtenerMTMEnCasinoMovimientos');
Route::delete('maquinas/eliminarMaquina/{id}', 'MTMController@eliminarMTM');
Route::get('maquinas/buscarMaquinaPorNumeroMarcaYModelo/{casino?}/{busqueda}','MTMController@buscarMaquinaPorNumeroMarcaYModelo');
// Route::get('maquinas/buscarMaquinaPorNumeroMarcaYModelo/{busqueda}/casino/{casino}','MTMController@buscarMaquinaPorNumeroMarcaYModelo');
Route::post('maquinas/cargaMasiva', 'LectorCSVController@cargaMasivaMaquinas');
Route::get('maquinas/buscarMarcas/{marca}', 'MTMController@buscarMarcas');
Route::get('maquinas/obtenerMTMMovimientos/{id_casino}/{id_tipo}/{id_mov}/{admin}','MTMController@obtenerMTMMovimientos');
Route::get('maquinas/getMoneda/{nro}','MTMController@getMoneda');

/**********
Islas
**********/
Route::get('islas','IslaController@buscarTodo')->middleware('tiene_permiso:ver_seccion_islas');
Route::get('islas/buscarIslasPorNro/{nro_isla}','IslaController@buscarIslasPorNro');
Route::get('islas/buscarIslaPorCasinoYNro/{id_casino}/{nro_isla}','IslaController@buscarIslaPorCasinoYNro');
Route::post('islas/buscarIslas','IslaController@buscarIslas');
Route::get('islas/obtenerIsla/{id_isla}','IslaController@obtenerIsla');
Route::delete('islas/eliminarIsla/{id_isla}','IslaController@eliminarIsla');
Route::post('islas/modificarIsla','IslaController@modificarIsla');
Route::post('islas/guardarIsla','IslaController@guardarIsla');
Route::get('islas/listarMaquinasPorNroIsla/{nro_isla}/{id_casino?}','IslaController@listarMaquinasPorNroIsla');
Route::post('islas/actualizarListaMaquinas','IslaController@actualizarListaMaquinas');

/**********
Movimientos
***********/
Route::get('movimientos/casinosYMovimientos','LogMovimientoController@casinosYMovimientos');
Route::get('movimientos','LogMovimientoController@buscarTodo')->middleware('tiene_permiso:ver_seccion_movimientos');
Route::post('movimientos/buscarLogsMovimientos','LogMovimientoController@buscarLogsMovimientos');
Route::post('movimientos/generarRelevamientosMovimientosIngreso', 'LogMovimientoController@generarRelevamientosMovimientosIngreso');
Route::post('movimientos/enviarAFiscalizar', 'LogMovimientoController@enviarAFiscalizar');
Route::get('movimientos/obtenerExpediente/{id}','ExpedienteController@obtenerExpediente');
Route::post('movimientos/guardarMaquina', 'MTMController@guardarMaquina');
Route::get('movimientos/guardarLogMovimiento/{id_controlador}/{id_expediente}/{cantidad_maq}','LogMovimientoController@guardarLogMovimiento');
Route::get('movimientos/generarPlanillaMovimientos','LogMovimientoController@generarPlanillaMovimientos');
Route::get('movimientos/generarPlanillaEventualidades','LogMovimientoController@generarPlanillaEventualidades');
Route::post('movimientos/cargaMasiva', 'LectorCSVController@cargaMasivaMaquinas');
Route::post('movimientos/guardarTipoCargaYCantMaq', 'LogMovimientoController@guardarTipoCargaYCantMaq');
Route::get('movimientos/buscarMaquinasMovimiento/{id}','LogMovimientoController@buscarMaquinasMovimiento');
Route::get('movimientos/ValidarMovimiento/{id}', 'LogMovimientoController@ValidarMovimiento');
Route::get('movimientos/ValidarFiscalizacion/{id}', 'LogMovimientoController@ValidarFiscalizacion');
Route::get('movimientos/ValidarMaquinaFiscalizacion/{id}', 'LogMovimientoController@ValidarMaquinaFiscalizacion');
Route::post('movimientos/validarTomaRelevamiento', 'LogMovimientoController@validarTomaRelevamiento');
Route::post('movimientos/guardarRelevamientosMovimientosMaquinas', 'LogMovimientoController@guardarRelevamientosMovimientosMaquinas');
Route::post('movimientos/guardarLogClickMov', 'LogMovimientoController@guardarLogClickMov');
Route::get('movimientos/mostrarMaquinasMovimientoLogClick/{id}','LogMovimientoController@mostrarMaquinasMovimientoLogClick');
Route::post('movimientos/guardarRelevamientosMovimientos','LogMovimientoController@guardarRelevamientosMovimientos');
Route::post('movimientos/bajaMTMs', 'LogMovimientoController@bajaMTMs');
Route::get('movimientos/generarPlanillasRelevamientoMovimiento/{id}','LogMovimientoController@generarPlanillasRelevamientoMovimiento');
Route::get('movimientos/obtenerRelevamientosFiscalizacion/{id}','LogMovimientoController@obtenerRelevamientosFiscalizacion');
Route::get('movimientos/obtenerMTMFiscalizacion/{idMaq}/{idFisc}','LogMovimientoController@obtenerMTMFiscalizacion');
Route::post('movimientos/cargarTomaRelevamiento', 'LogMovimientoController@cargarTomaRelevamiento');
Route::post('movimientos/nuevoLogMovimiento','LogMovimientoController@nuevoLogMovimiento');
Route::post('movimientos/eliminarMovimiento', 'LogMovimientoController@eliminarMovimiento');
Route::post('movimientos/movimientosSinExpediente','LogMovimientoController@movimientosSinExpediente');
Route::get('movimientos/obtenerDatos/{id}','LogMovimientoController@obtenerDatos');
Route::get('movimientos/buscarJuegoMovimientos/{nombre_juego}', 'JuegoController@buscarJuegoMovimientos');
Route::get('movimientos/obtenerMovimiento/{id}','LogMovimientoController@obtenerMovimiento');
Route::get('movimientos/obtenerMTM/{id_maquina}','LogMovimientoController@obtenerMaquina');
Route::get('movimientos/obtenerMaquinasIsla/{id_isla}','LogMovimientoController@obtenerMaquinasIsla');
Route::get('movimientos/obtenerMaquinasSector/{id_sector}','LogMovimientoController@obtenerMaquinasSector');
Route::get('movimientos/maquinasEnviadasAFiscalizar/{id_movimiento}','RelevamientoMovimientoController@maquinasEnviadasAFiscalizar');
Route::get('movimientos/finalizarValidacion/{id}', 'LogMovimientoController@cambiarEstadoFiscalizacionAValidado');


/**********
Relevamientos
***********/

Route::get('relevamientos_movimientos','LogMovimientoController@obtenerFiscalizaciones')->middleware('tiene_permiso:ver_seccion_relevamientos_movimientos');
Route::post('relevamientos_movimientos/buscarFiscalizaciones','FiscalizacionMovController@buscarFiscalizaciones');
Route::get('relevamientos_movimientos/eliminarFiscalizacion/{id}','FiscalizacionMovController@eliminarFiscalizacion');
/**********
Eventualidades ->intervenciones tecnicas
***********/

Route::get('eventualidades','EventualidadController@buscarTodoDesdeFiscalizador')->middleware('tiene_permiso:ver_seccion_eventualidades');
Route::post('eventualidades/buscarPorTipoFechaCasinoTurno','EventualidadController@buscarPorTipoFechaCasinoTurno');
Route::get('eventualidades/crearEventualidad', 'EventualidadController@crearEventualidad');
Route::get('eventualidades/verPlanillaVacia/{id}', 'EventualidadController@verPlanillaVacia');
Route::get('eventualidades/obtenerSectorEnCasino/{id_casino}/{id_sector}','EventualidadController@obtenerSectorEnCasino');
Route::get('eventualidades/obtenerIslaEnCasino/{id_casino}/{nro_isla}','EventualidadController@obtenerIslaEnCasino');
Route::post('eventualidades/CargarYGuardarEventualidad','EventualidadController@CargarYGuardarEventualidad');
Route::get('eventualidades/visualizarEventualidadID/{id_ev}','EventualidadController@visualizarEventualidadID');
Route::get('eventualidades/eliminarEventualidad/{id_ev}', 'EventualidadController@eliminarEventualidad');
Route::get('eventualidades/visado/{id_ev}', 'EventualidadController@validarEventualidad');
Route::post('eventualidades/buscarEventualidadesMTMs', 'LogMovimientoController@buscarEventualidadesMTMs');


/**********
Eventualidades MTM ->intervenciones tecnicas mtm
***********/
Route::get('eventualidadesMTM','LogMovimientoController@todasEventualidadesMTMs')->middleware('tiene_permiso:ver_seccion_eventualidades_MTM');//buscar todo
Route::post('eventualidadesMTM/nuevaEventualidadMTM','LogMovimientoController@nuevaEventualidadMTM');
Route::get('eventualidadesMTM/maquinasACargar/{id_mov}', 'LogMovimientoController@maquinasACargar');
Route::get('eventualidadesMTM/obtenerDatosMTMEv/{id_maq}', 'LogMovimientoController@obtenerDatosMTMEv');
Route::post('eventualidadesMTM/cargarEventualidadMTM', 'LogMovimientoController@cargarEventualidadMTM');
Route::get('eventualidadesMTM/obtenerMTMEv/{id_relevamiento}', 'LogMovimientoController@obtenerMTMEv');
Route::get('eventualidadesMTM/validarEventualidad/{id_log_mov}', 'LogMovimientoController@validarEventualidad');
Route::post('eventualidadesMTM/eliminarEventualidadMTM', 'LogMovimientoController@eliminarEventualidadMTM');
Route::get('eventualidadesMTM/tiposMovIntervMTM', 'LogMovimientoController@tiposMovIntervMTM');
Route::get('eventualidadesMTM/relevamientosEvMTM/{id_movimiento}', 'LogMovimientoController@relevamientosEvMTM');
Route::get('eventualidadesMTM/imprimirEventualidadMTM/{id_mov}/{esNueva}','LogMovimientoController@imprimirEventualidadMTM');
Route::get('eventualidadesMTM/visar/{id_relevamiento}', 'LogMovimientoController@validarRelevamientoEventualidad');
Route::post('eventualidadesMTM/visarConObservacion/', 'LogMovimientoController@validarRelevamientoEventualidadConObserv');


/******
CALENDARIO
******/
 Route::get('calendario_eventos','CalendarioController@calendar');
 Route::post('calendario_eventos/crearEvento','CalendarioController@crearEvento');
 Route::get('calendario_eventos/buscarEventos', 'CalendarioController@buscarEventos');
 Route::post('calendario_eventos/modificarEvento', 'CalendarioController@modificarEvento');
 Route::get('calendario_eventos/eliminarEvento/{id}','CalendarioController@eliminarEvento');
 Route::get('calendario_eventos/verMes/{month}/{year}','CalendarioController@verMes');
 Route::get('calendario_eventos/getEvento/{id}', 'CalendarioController@getEvento');
 Route::get('calendario_eventos/getOpciones', 'CalendarioController@getOpciones');
 Route::post('calendario_eventos/crearTipoEvento', 'CalendarioController@crearTipoEvento');

/***********
Log Isla
************/
Route::get('logIsla/obtenerHistorial/{id_isla}','LogIslaController@obtenerHistorial');

/**********
Sectores
***********/
Route::get('sectores','SectorController@buscarTodo')->middleware('tiene_permiso:ver_seccion_sectores');
Route::get('sectores/obtenerSector/{id_sector}','SectorController@obtenerSector');
Route::delete('sectores/eliminarSector/{id_sector}','SectorController@eliminarSector');
Route::post('sectores/guardarSector','SectorController@guardarSector');
Route::post('sectores/modificarSector','SectorController@modificarSector');
Route::get('sectores/obtenerSectoresPorCasino/{id_casino}','SectorController@obtenerSectoresPorCasino');
/**********
Contadores
***********/
Route::delete('contadores/eliminarContador/{id}','ContadorController@eliminarContador');
Route::delete('producidos/eliminarProducido/{id}','ProducidoController@eliminarProducido');
Route::delete('beneficios/eliminarBeneficio/{id}','BeneficioController@eliminarBeneficio');
Route::get('importaciones','ImportacionController@buscarTodo')->middleware('tiene_permiso:ver_seccion_importaciones');
Route::post('importaciones/buscar','ImportacionController@buscar');
Route::get('importaciones/{id_casino}','ImportacionController@estadoImportacionesDeCasino');
Route::post('importaciones/importarContador','ImportacionController@importarContador');
Route::post('importaciones/importarProducido','ImportacionController@importarProducido');
Route::post('importaciones/importarBeneficio','ImportacionController@importarBeneficio');
Route::get('importaciones/obtenerVistaPrevia/{tipo_importacion}/{id}','ImportacionController@obtenerVistaPrevia');
Route::post('importaciones/previewBeneficios','ImportacionController@previewBeneficios');

Route::get('cotizacion/obtenerCotizaciones/{mes}','CotizacionController@obtenerCotizaciones');
Route::post('cotizacion/guardarCotizacion','CotizacionController@guardarCotizacion');

/************
Relevamientos
************/
Route::get('relevamientos','RelevamientoController@buscarTodo')->middleware('tiene_permiso:ver_seccion_relevamientos');
Route::post('relevamientos/crearRelevamiento','RelevamientoController@crearRelevamiento');
Route::post('relevamientos/cargarRelevamiento','RelevamientoController@cargarRelevamiento');
Route::post('relevamientos/validarRelevamiento','RelevamientoController@validarRelevamiento');
Route::get('relevamientos/obtenerRelevamiento/{id_relevamiento}','RelevamientoController@obtenerRelevamiento');
Route::get('relevamientos/generarPlanilla/{id_relevamiento}','RelevamientoController@generarPlanilla');
Route::get('relevamientos/generarPlanillaValidado/{id_relevamiento}','RelevamientoController@generarPlanillaValidado');
Route::get('relevamientos/existeRelevamiento/{id_sector}','RelevamientoController@existeRelevamiento');
Route::post('relevamientos/usarRelevamientoBackUp','RelevamientoController@usarRelevamientoBackUp');
Route::get('relevamientos/descargarZip/{nombre}','RelevamientoController@descargarZip');
Route::get('relevamientos/obtenerCantidadMaquinasPorRelevamiento/{id_sector}','RelevamientoController@obtenerCantidadMaquinasPorRelevamiento');
Route::get('relevamientos/existeCantidadTemporalMaquinas/{id_sector}/{fecha_desde}/{fecha_hasta}','RelevamientoController@existeCantidadTemporalMaquinas');
Route::post('relevamientos/crearCantidadMaquinasPorRelevamiento','RelevamientoController@crearCantidadMaquinasPorRelevamiento');
Route::get('relevamientos/obtenerCantidadMaquinasRelevamientoHoy/{id_sector}','RelevamientoController@obtenerCantidadMaquinasRelevamiento');
Route::post('relevamientos/eliminarCantidadMaquinasPorRelevamiento','RelevamientoController@eliminarCantidadMaquinasPorRelevamiento');
Route::post('relevamientos/modificarDenominacionYUnidad','RelevamientoController@modificarDenominacionYUnidad');
Route::post('relevamientos/buscarRelevamientos','RelevamientoController@buscarRelevamientos');
Route::get('relevamientos/verRelevamientoVisado/{id_relevamiento}','RelevamientoController@obtenerRelevamientoVisado');
Route::get('relevamientos/chequearRolFiscalizador','UsuarioController@chequearRolFiscalizador');


/* OBTENER FECHA Y HORA ACTUAL */
Route::get('obtenerFechaActual',function(){
  setlocale(LC_TIME, 'es_ES.UTF-8');
  // echo date("d F Y");
  return ['fecha' => strftime("%A, %d de %B de %Y"), 'fechaDate' => date("Y-m-d")];
});


/**************
RELEVAMIENTO PROGRESIVO
**************/
Route::get('relevamientosProgresivo','RelevamientoProgresivoController@buscarTodo'); //->middleware('tiene_permiso:ver_seccion_relevamientos')
Route::get('relevamientosProgresivo/buscarRelevamientosProgresivos','RelevamientoProgresivoController@buscarRelevamientosProgresivos'); //->middleware('tiene_permiso:ver_seccion_relevamientos')
Route::post('relevamientosProgresivo/crearRelevamiento' , 'RelevamientoProgresivoController@crearRelevamientoProgresivos');
Route::post('relevamientosProgresivo/cargarRelevamiento','RelevamientoProgresivoController@cargarRelevamiento');
Route::post('relevamientosProgresivo/validarRelevamiento','RelevamientoProgresivoController@validarRelevamiento');
Route::get('relevamientosProgresivo/obtenerRelevamiento/{id}','RelevamientoProgresivoController@obtenerRelevamiento');
Route::get('relevamientosProgresivo/generarPlanilla/{id_relevamiento_progresivo}','RelevamientoProgresivoController@generarPlanillaProgresivos');

/*******************
  Máquinas a pedir
********************/
Route::get('mtm_a_pedido','MaquinaAPedidoController@buscarTodo')->middleware('tiene_permiso:ver_seccion_mtm_a_pedido');
Route::get('mtm_a_pedido/obtenerMtmAPedido/{fecha}/{id_sector}','MaquinaAPedidoController@obtenerMtmAPedido');
Route::post('mtm_a_pedido/buscarMTMaPedido','MaquinaAPedidoController@buscarMTMaPedido');
Route::post('mtm_a_pedido/guardarMtmAPedido','MaquinaAPedidoController@guardarMtmAPedido');
Route::delete('mtm_a_pedido/eliminarMmtAPedido/{id}','MaquinaAPedidoController@eliminarMTMAPedido');
/*******************
PRODUCIDOS-AJUSTES PRODUCIDO
******************/
Route::get('producidos','ProducidoController@buscarTodo')->middleware('tiene_permiso:ver_seccion_producidos');
Route::get('producidos/buscarProducidos','ProducidoController@buscarProducidos');
Route::get('producidos/generarPlanilla/{id_producido}','ProducidoController@generarPlanilla');
Route::get('producidos/checkEstado/{id}','ProducidoController@checkEstado');
Route::post('producidos/guardarAjusteProducidos','ProducidoController@guardarAjuste');
Route::get('producidos/ajustarProducido/{id_maquina}/{id_producidos}','ProducidoController@datosAjusteMTM');
Route::get('producidos/maquinasProducidos/{id_producido}','ProducidoController@ajustarProducido');

/***********
 Estadisticas
************/
Route::get('estadisticas_relevamientos','MaquinaAPedidoController@buscarTodoInforme' )->middleware('tiene_permiso:ver_seccion_estadisticas_relevamientos');
Route::post('estadisticas_relevamientos/guardarMtmAPedido','MaquinaAPedidoController@guardarMtmAPedido');
Route::post('estadisticas_relevamientos/obtenerUltimosRelevamientosPorMaquina','RelevamientoController@obtenerUltimosRelevamientosPorMaquina');
Route::post('estadisticas_relevamientos/buscarMaquinasSinRelevamientos','RelevamientoController@buscarMaquinasSinRelevamientos');
Route::get('estadisticas_relevamientos/obtenerFechasMtmAPedido/{id}', 'MaquinaAPedidoController@obtenerFechasMtmAPedido');

/**********
 Beneficios
***********/
Route::get('beneficios','BeneficioController@buscarTodo')->middleware('tiene_permiso:ver_seccion_beneficios');
Route::post('beneficios/buscarBeneficios','BeneficioController@buscarBeneficios');
Route::post('beneficios/obtenerBeneficiosParaValidar','BeneficioController@obtenerBeneficiosParaValidar');
Route::post('beneficios/ajustarBeneficio','BeneficioController@ajustarBeneficio');
Route::post('beneficios/validarBeneficios','BeneficioController@validarBeneficios');
Route::post('beneficios/validarBeneficiosSinProducidos','BeneficioController@validarBeneficiosSinProducidos');
Route::get('beneficios/generarPlanilla/{id_casino}/{id_tipo_moneda}/{anio}/{mes}','BeneficioController@generarPlanilla');
Route::post('beneficios/cargarImpuesto','BeneficioController@cargarImpuesto');
/*********
LAYOUT
*********/
Route::get('menu_layout',function(){
    return view('menu_layout');
});
Route::get('layout_total','LayoutController@buscarTodoTotal')->middleware('tiene_permiso:ver_seccion_layout_total');
Route::get('layout_parcial','LayoutController@buscarTodo')->middleware('tiene_permiso:ver_seccion_layout_parcial');
Route::post('/layouts/crearLayoutParcial','LayoutController@crearLayoutParcial');
Route::post('/layouts/usarLayoutBackup' , 'LayoutController@usarLayoutBackup');



//PARCIAL
Route::get('/layouts/existeLayoutParcial/{id_sector}','LayoutController@existeLayoutParcial');
Route::get('/layouts/existeLayoutParcialGenerado/{id_sector}','LayoutController@existeLayoutParcialGenerado');
Route::get('/layouts/randomMaquinas/{id_sector}','LayoutController@randomMaquinas');
Route::get('/layouts/obtenerLayoutParcial/{id}','LayoutController@obtenerLayoutParcial');
Route::get('/layouts/obtenerLayoutParcialValidar/{id}','LayoutController@obtenerLayoutParcialValidar');
Route::get('/layouts/generarPlanillaLayoutParcial/{id}','LayoutController@generarPlanillaLayoutParcial');
Route::get('/layouts/descargarLayoutParcialZip/{nombre}','LayoutController@descargarLayoutParcialZip');
Route::post('/layouts/buscarLayoutsParciales' , 'LayoutController@buscarLayoutsParciales');
Route::post('/layouts/cargarLayoutParcial' , 'LayoutController@cargarLayoutParcial');
Route::post('/layouts/validarLayoutParcial' , 'LayoutController@validarLayoutParcial');

//TOTAL
Route::post('/layouts/crearLayoutTotal','LayoutController@crearLayoutTotal');
Route::post('/layouts/buscarLayoutsTotales' , 'LayoutController@buscarLayoutsTotales');
Route::get('/layouts/descargarLayoutTotalZip/{nombre}','LayoutController@descargarLayoutTotalZip');
Route::get('/layouts/generarPlanillaLayoutTotales/{id}','LayoutController@generarPlanillaLayoutTotales');
Route::get('/layouts/generarPlanillaLayoutTotalesCargado/{id}','LayoutController@generarPlanillaLayoutTotalesCargado');
Route::post('/layouts/cargarLayoutTotal' , 'LayoutController@cargarLayoutTotal');
Route::get('/layouts/descargarLayoutTotalZip/{nombre}','LayoutController@descargarLayoutTotalZip');
Route::get('/layouts/obtenerTotalParaValidar/{id}','LayoutController@obtenerTotalParaValidar');
Route::get('/layouts/obtenerLayoutTotal/{id}','LayoutController@obtenerLayoutTotal');
Route::post('/layouts/validarLayoutTotal' , 'LayoutController@validarLayoutTotal');
Route::post('/layouts/usarLayoutTotalBackup' , 'LayoutController@usarLayoutTotalBackup');

/**************
 Estadisticas
**************/

/******TODA LA SECCCION TABLERO DE CONTROL *******/
Route::get('menu_tablero',function(){
  return view('menu_tablero');
});
Route::get('estadisticasGenerales', 'BeneficioMensualController@buscarTodoGenerales');
Route::get('estadisticasPorCasino','BeneficioMensualController@buscarTodoPorCasino');
Route::get('interanuales','BeneficioMensualController@buscarTodoInteranuales');
Route::post('estadisticasGenerales','BeneficioMensualController@cargarEstadisticasGenerales');
Route::post('estadisticasPorCasino','BeneficioMensualController@cargarSeccionEstadisticasPorCasino');
Route::post('interanuales','BeneficioMensualController@cargaSeccionInteranual');

/***********
Informes
***********/
Route::get('informeEstadoParque' , 'informesController@obtenerInformeEstadoParque');
Route::get('informesMTM/obtenerEstadoParqueDeCasino/{id_casino}','informesController@obtenerInformeEstadoParqueDeParque');

Route::get('informeContableMTM','informesController@buscarTodoInformeContable');//carga pagina
Route::get('obtenerInformeContableDeMaquina/{id_maquina}','informesController@obtenerInformeContableDeMaquina');//informe ultimos 30 dias
// Route::get('informeContableMTM/{id_casino}/{nro_admin}/{fecha?}','informesController@obtenerInformeContableMaquina');//informe de fecha


Route::get('menu_informes',function(){
  return view('menu_informes');
});

//seccion informes mtm (pestaña informes)
Route::get('informesMTM','informesController@obtenerUltimosBeneficiosPorCasino');
Route::get('informesMTM/generarPlanilla/{year}/{mes}/{id_casino}/{id_tipo_moneda}','informesController@generarPlanilla');

Route::get('informesBingo',function(){
    return view('seccionInformesBingo');
});

Route::get('informesJuegos',function(){
    return view('seccionInformesJuegos');
});


Route::get('estadisticas_no_toma','informesController@mostrarEstadisticasNoTomaGenerico');
Route::get('/relevamientos/estadisticas_no_toma/{id}','informesController@mostrarEstadisticasNoToma');
Route::get('estadisticas_no_toma/obtenerEstadisticasNoToma/{id}','informesController@obtenerEstadisticasNoToma');


/************************
Prueba Juegos y Progresivos
************************/
Route::get('prueba_juegos',function(){
    return view('seccionPruebaJuegos');
});
Route::get('prueba_juegos','PruebaController@buscarTodo');
Route::get('prueba_juegos/pdf/{id_prueba_juego}','PruebaController@obtenerPDF');
Route::get('prueba_juegos/obtenerPruebaJuego/{id_prueba_juego}','PruebaController@obtenerPruebaJuego');
Route::post('prueba_juegos/guardarPruebaJuego','PruebaController@guardarPruebaJuego');
Route::post('pruebas/buscarPruebasDeJuego','PruebaController@buscarPruebasDeJuego');
Route::get('pruebas/generarPlanillaPruebaDeJuego/{id_prueba_juego}','PruebaController@generarPlanillaPruebaDeJuego');
Route::post('pruebas/sortearMaquinaPruebaDeJuego','PruebaController@sortearMaquinaPruebaDeJuego');

Route::get('prueba_progresivos','PruebaController@buscarTodoPruebaProgresivo');
Route::post('pruebas/buscarPruebasProgresivo','PruebaController@buscarPruebasProgresivo');
Route::post('pruebas/sortearMaquinaPruebaDeProgresivo','PruebaController@sortearMaquinaPruebaDeProgresivo');
Route::get('pruebas/generarPlanillaPruebaDeProgresivos/{id_prueba_progresivo}','PruebaController@generarPlanillaPruebaDeProgresivos');

/************************
PRUEBAS DE DESARROLLO - AUXILIAR
****************************/

Route::get('prueba',function(){
  return view('prueba');
})->middleware('tiene_permiso:ver_pruebas_desarrollo');

Route::post('prueba/actualizarMaestroRosario', 'LectorCSVController@actualizaMaestroRosario');
Route::post('prueba/actualizarMaestroMelincue','LectorCSVController@actualizarMaestroMelincue');

Route::get('prueba/validador','pruebaController@validador');

Route::get('/prueba/estado_actividades','MenuContadoresController@estado_actividades');

Route::get('pruebaMovimientos',function(){
  return view('pruebaMovimientos');
});

Route::post('pruebaMovimientos/pruebasVarias', 'Mesas\Aperturas\ABMCRelevamientosAperturaController@planillaRosario');

Route::get('listaDatos',function(){
  return view('listaDatos');
});

Route::get('fabricjs',function(){
  return view('fabricjs');
});

Route::get('plano',function(){
  return view('plano');
});

/*calendario*/
Route::get('calendario_eventos',function(){
    return view('calendar');
});


//nuevo buscador de usuarios para la seccion de USUARIOS
Route::get('usuarios/get/{id}','UsuarioController@buscarUsuarioSecUsuarios');

Route::get('error','RelevamientoController@crearPlanillaValidado');


/*SECCION MESAS DE PAÑO*/
Route::get('usuarios/buscarFiscalizadores/{id_cas}/{nombre}', 'UsuarioController@buscarFiscaNombreCasino');

//gestion mesas
Route::get('/mesas','Mesas\Mesas\BuscarMesasController@getMesas');
Route::post('mesas/buscarMesas','Mesas\Mesas\BuscarMesasController@buscarMesas');
Route::post('mesas/nuevaMesa/{id_casino}','Mesas\Mesas\ABMMesaController@guardar');
Route::post('mesas/modificarMesa/{id_casino}','Mesas\Mesas\ABMMesaController@modificar');
Route::get('mesas/eliminarMesa/{id_casino}/{id_mesa_de_panio}','Mesas\Mesas\ABMMesaController@eliminar');
Route::get('mesas/cargarDatos','Mesas\Mesas\BuscarMesasController@getDatos');
Route::get('mesas/detalleMesa/{id_mesa}','Mesas\Mesas\BuscarMesasController@getMesa');
Route::get('mesas/obtenerMesasApertura/{id_cas}/{nro_mesa}', 'Mesas\Mesas\BuscarMesasController@buscarMesaPorNroCasino');
Route::get('mesas/obtenerDatos/{id_cas}', 'Mesas\Mesas\BuscarMesasController@datosSegunCasino');

//gestion cierres y aperturas

  //Cierres
  Route::get('/cierres', 'Mesas\Cierres\BCCierreController@buscarTodo');
  Route::post('cierres/filtrosCierres','Mesas\Cierres\BCCierreController@filtros');
  Route::post('cierres/guardar', 'Mesas\Cierres\ABMCierreController@guardar');
  Route::post('cierres/modificarCierre','Mesas\Cierres\ABMCierreController@modificarCierre');
  Route::get('cierres/obtenerCierres/{id_cierre}', 'Mesas\Cierres\BCCierreController@getCierre');
  Route::get('cierres/bajaCierre/{id_cierre}', 'Mesas\Cierres\BCCierreController@eliminarCierre')->middleware(['tiene_permiso:m_eliminar_cierres_y_aperturas']);
  Route::get('mesas/obtenerMesasCierre/{id_cas}/{nro_mesa}', 'Mesas\Mesas\BuscarMesasController@buscarMesaPorNroCasino');

  //Aperturas
  Route::get('/aperturas', 'Mesas\Aperturas\BCAperturaController@buscarTodo');
  Route::post('aperturas/filtrosAperturas', 'Mesas\Aperturas\BCAperturaController@filtros');
  Route::get('aperturas/obtenerAperturas/{id_apertura}', 'Mesas\Aperturas\BCAperturaController@getApertura');
  Route::post('aperturas/guardarApertura', 'Mesas\Aperturas\ABMAperturaController@guardar');
  Route::post('aperturas/modificarApertura','Mesas\Aperturas\ABMAperturaController@modificarApertura');
  Route::get('aperturas/bajaApertura/{id_apertura}', 'Mesas\Aperturas\BCAperturaController@eliminarApertura')->middleware(['tiene_permiso:m_eliminar_cierres_y_aperturas']);
  Route::post('aperturas/generarRelevamiento', 'Mesas\Aperturas\ABMCRelevamientosAperturaController@generarRelevamiento');
  Route::get('sorteo-aperturas/descargarZip/{nombre}', 'Mesas\Aperturas\ABMCRelevamientosAperturaController@descargarZip');
  Route::get('compararCierre/{id_apertura}/{id_cierre}/{id_moneda}','Mesas\Aperturas\BCAperturaController@obtenerDetallesApCierre');
  Route::post('aperturas/validarApertura','Mesas\Aperturas\VAperturaController@validarApertura');
  Route::get('aperturas/obtenerApValidar/{id_apertura}', 'Mesas\Aperturas\BCAperturaController@obtenerApParaValidar');
  Route::get('aperturas/desvincularApertura/{id_apertura}', 'Mesas\Cierres\ABMCCierreAperturaController@desvincularApertura')->middleware(['tiene_permiso:m_desvincular_aperturas']);



//Sección Juegos
Route::get('/juegosMesa', 'Mesas\Juegos\BuscarJuegoController@buscarTodo');
Route::post('mesas-juegos/buscarJuegos', 'Mesas\Juegos\BuscarJuegoController@buscarJuegos');
Route::post('mesas-juegos/nuevoJuego', 'Mesas\Juegos\ABMJuegoController@guardar');
Route::post('mesas-juegos/modificarJuego', 'Mesas\Juegos\ABMJuegoController@modificarJuego');
Route::get('mesas-juegos/obtenerJuego/{id_juego}', 'Mesas\Juegos\ABMJuegoController@obtenerJuego');
Route::get('mesas-juegos/obtenerJuegoPorCasino/{id_cas}/{nombreJuego}', 'Mesas\Juegos\BuscarJuegoController@buscarJuegoPorCasinoYNombre');
Route::get('mesas-juegos/bajaJuego/{id}', 'Mesas\Juegos\ABMJuegoController@eliminarJuego');

  //sectores mesasPanio
  Route::post('sectores-mesas/nuevoSector','Mesas\Sectores\ABMCSectoresController@guardar');
  Route::get('sectores-mesas/obtenerSector/{id_sector}','Mesas\Sectores\ABMCSectoresController@obtenerSector');
  Route::post('sectores-mesas/modificarSector/{id_sector}','Mesas\Sectores\ABMCSectoresController@modificarSector');
  Route::get('sectores-mesas/eliminarSector/{id_sector}','Mesas\Sectores\ABMCSectoresController@eliminarSector');
  Route::post('sectores-mesas/buscarSectores','Mesas\Sectores\ABMCSectoresController@filtrarSectores');
  Route::post('sectores-mesas/guardar','Mesas\Sectores\ABMCSectoresController@guardar');


  //apuestas
  Route::get('/apuestas', 'Mesas\Apuestas\BCApuestasController@buscarTodo');
  Route::post('/apuestas/buscarRelevamientosApuestas', 'Mesas\Apuestas\BCApuestasController@filtros');
  Route::post('apuestas/generarRelevamientoApuestas', 'Mesas\Apuestas\BCApuestasController@obtenerNombreZip');
  Route::get('apuestas/descargarZipApuestas/{nombre}', 'Mesas\Apuestas\BCApuestasController@descargarZip');
  Route::get('apuestas/obtenerDatos/{id_relevamiento}', 'Mesas\Apuestas\BCApuestasController@obtenerRelevamientoCarga');
  Route::post('apuestas/cargarRelevamiento','Mesas\Apuestas\ABMApuestasController@cargarRelevamiento');
  Route::get('apuestas/relevamientoCargado/{id_relevamiento}', 'Mesas\Apuestas\BCApuestasController@obtenerRelevamientoApuesta');
  Route::get('apuestas/baja/{id_relevamiento}', 'Mesas\Apuestas\BVApuestasController@eliminar');
  Route::post('apuestas/validar', 'Mesas\Apuestas\BVApuestasController@validar');
  Route::post('apuestas/obtenerRelevamientoBackUp', 'Mesas\Apuestas\BCApuestasController@buscarRelevamientosBackUp');
  Route::get('apuestas/imprimir/{id}','Mesas\Apuestas\BCApuestasController@imprimirPlanilla');
  Route::get('turnos/buscarTurnos/{nro}','Mesas\Turnos\TurnosController@buscarTurnos');
  Route::get('apuestas/consultarMinimo','Mesas\Apuestas\BCApuestasController@consultarMinimo');


  Route::get('apuestas/obtenerRequerimientos/{id_cas}/{id_moneda}','Mesas\Apuestas\ABMCApuestaMinimaController@obtenerApuestaMinima');
  Route::post( 'apuestas/modificarRequerimiento','Mesas\Apuestas\ABMCApuestaMinimaController@modificar');

//informes fiscalizadores
Route::get('/informeDiarioBasico','Mesas\InformeFiscalizadores\BCInformesController@index');
Route::post('informeDiarioBasico/buscar', 'Mesas\InformeFiscalizadores\BCInformesController@filtros');
Route::post('/informeDiarioBasico/buscarInformes','Mesas\InformeFiscalizadores\BCInformesController@filtros');
Route::get('informeDiarioBasico/imprimir/{id_informe_fiscalizacion}','Mesas\InformeFiscalizadores\BCInformesController@imprimirPlanilla');

//importaciones
Route::get('/importacionDiaria','Mesas\Importaciones\Mesas\ImportadorController@buscarTodo');
Route::post('importacionDiaria/importar','Mesas\Importaciones\Mesas\ImportadorController@importarDiario');
Route::post('importacionDiaria/filtros','Mesas\Importaciones\Mesas\ImportadorController@filtros');
Route::get('importacionDiaria/verImportacion/{id_imp}/{t_mesa}','Mesas\Importaciones\Mesas\ImportadorController@buscarPorTipoMesa');
Route::post('importacionDiaria/guardar','Mesas\Importaciones\Mesas\ImportadorController@guardarObservacion');
Route::get('importacionDiaria/eliminarImportacion/{id_imp}','Mesas\Importaciones\Mesas\ImportadorController@eliminar');
Route::post('importacionMensual/importar','Mesas\Importaciones\Mesas\MensualController@importarMensual');
Route::post('importacionMensual/filtros','Mesas\Importaciones\Mesas\MensualController@filtros');
Route::get('importacionMensual/verImportacion/{id_imp}','Mesas\Importaciones\Mesas\MensualController@buscar');
Route::post('importacionMensual/guardar','Mesas\Importaciones\Mesas\MensualController@guardarObservacion');
Route::get('importacionMensual/eliminarImportacion/{id_imp}','Mesas\Importaciones\Mesas\MensualController@eliminar');

//informes
Route::get('/informeAnual',function(){
  $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    return view('Informes.seccionInformesAnuales',['casinos'=>$usuario->casinos]);});
Route::post('/informeAnual/obtenerDatos','Mesas\InformesMesas\BCAnualesController@buscarPorAnioCasinoMoneda');
Route::get('/informeDiario','Mesas\InformesMesas\IndexController@indexDiarios');
Route::get('informeDiario/imprimir/{id_imp}','Mesas\InformesMesas\BCInformesController@imprimirDiario');
Route::post('informeDiario/buscar','Mesas\InformesMesas\InformesController@filtrarDiarios');
Route::get('/informeMensual','Mesas\InformesMesas\IndexController@indexMensuales');
Route::post('informeMensual/buscar','Mesas\InformesMesas\InformesController@filtrarMensuales');
Route::post('informeMensual/obtenerDatos','Mesas\InformesMesas\BCInformesController@obtenerDatosGraficos');
Route::get('informeMensual/imprimir/{fecha}/{id_casino}','Mesas\InformesMesas\BCInformesController@imprimirMensual');

Route::get('informeDiario/getDatos/{id}','Mesas\InformesMesas\ModificarInformeDiarioController@obtenerDatosAModificar');
Route::get('informeDiario/getDatosImportacion/{id}','Mesas\InformesMesas\ModificarInformeDiarioController@obtenerDatosDetalle');
Route::post('informeDiario/almacenarDatos','Mesas\InformesMesas\ModificarInformeDiarioController@almacenarDatos');



Route::group(['middleware' => ['tiene_permiso:m_abmc_canon']], function () {
  Route::get('/canon','Mesas\Canon\IndexController@index');
  Route::post('canon/modificar','Mesas\Canon\ABMCCanonController@modificar');
  Route::get('canon/obtenerCanon/{id_cas}','Mesas\Canon\ABMCCanonController@obtenerCanon');

});

Route::group(['middleware' => ['tiene_permiso:m_actualizar_canon']], function () {
  Route::get('canon/generarTablaActualizacion1/{id}/{anio}','Mesas\Canon\ActualizarValoresController@forzarActualizacion');
});

Route::group(['middleware' => ['tiene_permiso:m_a_pagos']], function () {
  Route::post('canon/guardarPago','Mesas\Canon\APagosController@crear');
  Route::post('canon/modificarPago','Mesas\Canon\APagosController@modificar');

});

Route::group(['middleware' => ['tiene_permiso:m_b_pagos']], function () {
  Route::post('canon/buscarPagos','Mesas\Canon\BPagosController@filtros');
  Route::get('canon/obtenerPago/{id_detalle}','Mesas\Canon\BPagosController@obtenerPago');
  Route::get('canon/obtenerAnios/{id_casino}','Mesas\Canon\BPagosController@obtenerAnios');
  Route::post('canon/verInforme','Mesas\Canon\BPagosController@verInformeFinalMesas');

});

Route::group(['middleware' => ['tiene_permiso:m_abmc_img_bunker']], function () {
  Route::post('solicitudImagenes/buscar','Mesas\Bunker\ABMCImgBunkerController@filtros');
  Route::get('solicitudImagenes/obtenerMesas/{id}', 'Mesas\Bunker\ABMCImgBunkerController@obtenerBunker');
  Route::get('solicitudImagenes/hayCoincidencia/{drop}/{id_detalle}', 'Mesas\Bunker\ABMCImgBunkerController@consultarDiferencias');
  Route::post('solicitudImagenes/sorteoFechasMesas', 'Mesas\Bunker\ABMCImgBunkerController@altaImgsBunker');
  Route::post('solicitudImagenes/guardar','Mesas\Bunker\ABMCImgBunkerController@cargar');
  Route::get('/solicitudImagenes','Mesas\Bunker\ABMCImgBunkerController@index');
});
