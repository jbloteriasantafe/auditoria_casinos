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
Route::get('login',function(){
  return view('index');
});
Route::get('inicio',function(){
  return redirect('/');
});

Route::post('enviarTicket',function(Request $request){
  $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
  $data = array(
    'name'      =>  $usuario->nombre,
    'email'     =>  $usuario->email,
    'subject'   =>  $request->subject,
    'message'   =>  $request->message,
    'ip'        =>  $_SERVER['REMOTE_ADDR'],
  );

  if(!empty($request->attachments)){
    $data['attachments'] = $request->attachments;
  }
  
  set_time_limit(30);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'http://10.1.121.25/osTicket/api/http.php/tickets.json');
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  curl_setopt($ch, CURLOPT_USERAGENT, 'osTicket API Client v1.7');
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:', 'X-API-Key: 14C4C2A8161F6728C74D92C58B6DF990'));
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  $result = curl_exec($ch);
  $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($code != 201) return response()->json($result,422);
  $ticket_id = (int) $result;
  return $ticket_id;
});

Route::group(['prefix' => 'configCuenta'], function () {
  Route::get('/','UsuarioController@configUsuario');
  Route::post('modificarPassword','UsuarioController@modificarPassword');
  Route::post('modificarImagen','UsuarioController@modificarImagen');
  Route::post('modificarDatos','UsuarioController@modificarDatos');
  Route::get('buscarUsuario/{id_usuario}','UsuarioController@buscarUsuario');
});

Route::post('logout','AuthenticationController@logout');
Route::post('login','AuthenticationController@login');

/***********
Log Actividades
***********/
Route::group(['prefix' => 'logActividades','middleware' => 'tiene_permiso:ver_seccion_logs_actividades'], function () {
  Route::get('/','LogController@buscarTodo');
  Route::get('buscarLogActividades','LogController@buscarLogActividades');
  Route::get('obtenerLogActividad/{id}','LogController@obtenerLogActividad');
});
/***********
Progresivos
***********/
Route::group(['prefix' => 'progresivos','middleware' => 'tiene_permiso:ver_seccion_progresivos'], function () {
  Route::get('/','ProgresivoController@buscarTodos');
  Route::post('buscarProgresivos','ProgresivoController@buscarProgresivos');
  Route::get('buscarMaquinas/{id_casino}/{nro_admin?}','ProgresivoController@buscarMaquinas');
  Route::get('obtenerProgresivo/{id_progresivo}','ProgresivoController@obtenerProgresivo');
  Route::get('obtenerMinimoRelevamientoProgresivo/{id_casino}/{id_tipo_moneda}','RelevamientoProgresivoController@obtenerMinimoRelevamientoProgresivo');
  Route::post('crearModificarProgresivo','ProgresivoController@crearModificarProgresivo');
  Route::delete('eliminarProgresivo/{id_progresivo}','ProgresivoController@eliminarProgresivo');
  Route::post('crearProgresivosIndividuales','ProgresivoController@crearProgresivosIndividuales');
  Route::post('modificarParametrosRelevamientosProgresivo','RelevamientoProgresivoController@modificarParametrosRelevamientosProgresivo');
  Route::get('buscarIslaPorCasinoYNro/{id_casino}/{nro_isla}','IslaController@buscarIslaPorCasinoYNro');
  Route::get('listarMaquinasPorNroIsla/{nro_isla}/{id_casino?}','IslaController@listarMaquinasPorNroIsla');
});
/***********
Casinos
***********/
Route::group(['prefix' => 'casinos','middleware' => 'tiene_permiso:ver_seccion_casinos'], function () {
  Route::get('/','CasinoController@buscarTodo');
  Route::get('obtenerCasino/{id?}','CasinoController@obtenerCasino');
  Route::post('guardarCasino','CasinoController@guardarCasino');
  Route::post('modificarCasino','CasinoController@modificarCasino');
  Route::delete('eliminarCasino/{id}','CasinoController@eliminarCasino');
  Route::get('getFichas','CasinoController@getFichas');//@deprecado, casinos.js no se usa mas
});
/***********
Expedientes
***********/
Route::group(['prefix' => 'expedientes'], function () {
  Route::get('/','ExpedienteController@buscarTodo')->middleware('tiene_permiso:ver_seccion_expedientes');
  Route::get('obtenerExpediente/{id}','ExpedienteController@obtenerExpediente');
  Route::post('guardarOmodificarExpediente','ExpedienteController@guardarOmodificarExpediente');
  Route::delete('eliminarExpediente/{id}','ExpedienteController@eliminarExpediente');
  Route::post('buscarExpedientes','ExpedienteController@buscarExpedientes');
  Route::get('obtenerMovimiento/{id}','LogMovimientoController@obtenerMovimiento');
  Route::get('movimientosSinExpediente/{id_casino}','LogMovimientoController@movimientosSinExpediente');
});

/***********
Usuarios
***********/
Route::group(['prefix' => 'usuarios'], function () {
  Route::get('/','UsuarioController@buscarTodo')->middleware('tiene_permiso:ver_seccion_usuarios');
  Route::post('buscar','UsuarioController@buscarUsuarios');
  Route::get('buscar/{id_usuario}','UsuarioController@buscarUsuario');
  Route::post('guardarUsuario','UsuarioController@guardarUsuario');
  Route::delete('eliminarUsuario/{id_usuario}','UsuarioController@eliminarUsuario');
  Route::get('imagen','UsuarioController@leerImagenUsuario');
  Route::get('buscarUsuariosPorNombre/{nombre}','UsuarioController@buscarUsuariosPorNombre');
  Route::get('buscarUsuariosPorNombre/{nombre}/relevamiento/{id_relevamiento}','UsuarioController@buscarUsuariosPorNombreYRelevamiento');
  Route::get('buscarUsuariosPorNombreYCasino/{id_casino}/{nombre}','UsuarioController@buscarUsuariosPorNombreYCasino');
  Route::get('usuarioTienePermisos','AuthenticationController@usuarioTienePermisos');
  Route::post('reestablecerContraseña','UsuarioController@reestablecerContraseña');
});
/***********
Roles y permisos
***********/
Route::group(['prefix' => 'rol'], function () {
  Route::post('guardar','RolController@guardarRol');
  Route::post('modificar','RolController@modificarRol');
  Route::get('getAll','RolController@getAll');
  Route::delete('{id}','RolController@eliminarRol');
  Route::get('{id}','RolController@getRol');
});
Route::get('roles','RolController@buscarTodo')->middleware('tiene_permiso:ver_seccion_roles_permisos');
Route::post('roles/buscar','RolController@buscarRoles');

Route::group(['prefix' => 'permiso'], function () {
  Route::post('guardar','PermisoController@guardarPermiso');
  Route::post('modificar','PermisoController@modificarPermiso');
  Route::get('getAll','PermisoController@getAll');
  Route::post('buscarPermisosPorRoles',"PermisoController@buscarPermisosPorRoles");
  Route::delete('{id}','PermisoController@eliminarPermiso');
  Route::get('{id}','PermisoController@getPermiso');
});
Route::post('permisos/buscar','PermisoController@buscarPermisos');

/***********
Juegos
***********/
Route::group(['prefix' => 'juegos','middleware' => 'tiene_permiso:ver_seccion_juegos'], function () {
  Route::get('/','JuegoController@buscarTodo');
  Route::get('obtenerJuego/{id?}','JuegoController@obtenerJuego');
  Route::post('guardarJuego','JuegoController@guardarJuego');
  Route::post('modificarJuego','JuegoController@modificarJuego');
  Route::delete('eliminarJuego/{id}','JuegoController@eliminarJuego');
  Route::get('obtenerTablasDePago/{id}','JuegoController@obtenerTablasDePago');
  Route::get('buscarJuegos/{busqueda}','JuegoController@buscarJuegoPorCodigoYNombre');
  Route::get('buscarJuegos/{id_casino}/{busqueda}','JuegoController@buscarJuegoPorCasinoYNombre');
  Route::post('buscar','JuegoController@buscarJuegos');
  Route::get('{id}','JuegoController@buscarTodo');
});

/***********
PackJuego
***********/
Route::group(['prefix' => 'packJuego'], function () {
  Route::get('buscarPackJuegos/{busqueda}','PackJuegoController@buscarPackJuegoPorNombre');
  Route::post('guardarPackJuego','PackJuegoController@guardarPackJuego');
  Route::post('modificarPackJuego','PackJuegoController@modificarPackJuego');
  Route::post('asociarPackJuego','PackJuegoController@asociarPackJuego');
  Route::post('asociarMtmJuegosPack','PackJuegoController@asociarMtmJuegosPack');
});
Route::group(['prefix' => 'packJuegos'], function () {
  Route::get('/','PackJuegoController@buscarTodo')->middleware('tiene_permiso:ver_seccion_juegos');
  Route::POST('buscar','PackJuegoController@buscar');
  Route::get('obtenerPackJuego/{id}','PackJuegoController@obtenerPackJuego');
  Route::get('obtenerJuegos/{id}','PackJuegoController@obtenerJuegosDePack');
  Route::delete('eliminarPackJuego/{id}','PackJuegoController@eliminarPack');
  Route::get('obtenerJuegosMTM/{id_maquina}','PackJuegoController@obtenerJuegosDePackMTM');
});
/***********
Disposiciones
***********/
Route::group(['prefix' => 'disposiciones'], function () {
  Route::get('/','DisposicionController@buscarTodoDisposiciones')->middleware('tiene_permiso:ver_seccion_disposiciones');
  Route::post('buscar','DisposicionController@buscarDispocisiones');
});
Route::group(['prefix' => 'resoluciones'], function () {
  Route::get('/','ResolucionController@buscarTodoResoluciones');
  Route::post('buscar','ResolucionController@buscarResolucion')->middleware('tiene_permiso:ver_seccion_resoluciones');
});

/***********
Notas
***********/
Route::group(['prefix' => 'notas'], function () {
  Route::get('/','NotaController@buscarTodoNotas')->middleware('tiene_permiso:ver_seccion_resoluciones');
  Route::post('buscar','NotaController@buscarNotas')->middleware('tiene_permiso:ver_seccion_resoluciones');
  Route::get('consulta-nota/{id}','NotaController@consultaMovimientosNota');
  Route::delete('eliminar-nota/{id}','NotaController@eliminarNotaCompleta');
});

 /***********
    GLI soft
 ************/
Route::group(['prefix' => 'certificadoSoft','middleware' =>'tiene_permiso:ver_seccion_glisoft'],function(){
  Route::get('/','GliSoftController@buscarTodo');
  Route::get('obtenerExpediente/{id}','ExpedienteController@obtenerExpediente');
  Route::get('buscarExpedientePorNumero/{busqueda}','ExpedienteController@buscarExpedientePorNumero');
  Route::post('guardarGliSoft','GliSoftController@guardarGliSoft');
  Route::get('pdf/{id}','GliSoftController@leerArchivoGliSoft');
  Route::get('obtenerGliSoft/{id}','GliSoftController@obtenerGliSoft');
  Route::delete('eliminarGliSoft/{id}','GliSoftController@eliminarGLI');
  Route::post('buscarGliSoft','GliSoftController@buscarGliSofts');
  Route::post('modificarGliSoft','GliSoftController@modificarGliSoft');
  Route::get('{id}','GliSoftController@buscarTodo');
});
//Lo dejo afuera sin permisos porque se usa en otro modulo, Movimientos...
Route::get('certificadoSoft/buscarGliSoftsPorNroArchivo/{nro_archivo}','GliSoftController@buscarGliSoftsPorNroArchivo');
/***********
GliHards
***********/
Route::get('certificadoHard','GliHardController@buscarTodo')->middleware('tiene_permiso:ver_seccion_glihard');
Route::group(['prefix' => 'glihards'], function () {
  Route::post('buscarGliHard','GliHardController@buscarGliHard');
  Route::get('pdf/{id}','GliHardController@leerArchivoGliHard');
  Route::get('obtenerExpediente/{id}','ExpedienteController@obtenerExpediente');
  Route::get('buscarExpedientePorNumero/{busqueda}','ExpedienteController@buscarExpedientePorNumero');
  Route::get('obtenerGliHard/{id}','GliHardController@obtenerGliHard');
  Route::post('guardarGliHard','GliHardController@guardarGliHard');
  Route::post('modificarGliHard','GliHardController@modificarGliHard');
  Route::delete('eliminarGliHard/{id}','GliHardController@eliminarGliHard');
  Route::get('buscarGliHardsPorNroArchivo/{nro_archivo}','GliHardController@buscarGliHardsPorNroArchivo');
});

/**********
Formulas
***********/
Route::group(['prefix' => 'formulas'], function () {
  Route::get('/','FormulaController@buscarTodo')->middleware('tiene_permiso:ver_seccion_formulas');
  Route::get('buscarFormulaPorCampos/{input}','FormulaController@buscarPorCampos');
  Route::get('buscarFormulas','FormulaController@buscarFormula');
  Route::post('guardarFormula','FormulaController@guardarFormula');
  Route::get('obtenerFormula/{id}','FormulaController@obtenerFormula');
  Route::post('modificarFormula','FormulaController@modificarFormula');
  Route::post('asociarMaquinas','FormulaController@asociarMaquinas');
  Route::delete('eliminarFormula/{id}','FormulaController@eliminarFormula');
  Route::get('buscarIslaPorCasinoYNro/{id_casino}/{nro_isla}','IslaController@buscarIslaPorCasinoYNro');
  Route::get('obtenerIsla/{id_isla}','IslaController@obtenerIsla');
});

/***********
Seccion MTM
************/
Route::group(['prefix' => 'maquinas','middleware' => 'tiene_permiso:ver_seccion_maquinas'], function () {
  Route::get('/','MTMController@buscarTodo');
  Route::post('guardarMaquina', 'MTMController@guardarMaquina');
  Route::post('modificarMaquina', 'MTMController@modificarMaquina');
  Route::post('buscarMaquinas', 'MTMController@buscarMaquinas');
  Route::delete('eliminarMaquina/{id}', 'MTMController@eliminarMTM');
  Route::post('cargaMasiva', 'LectorCSVController@cargaMasivaMaquinas');  
  Route::get('obtenerExpediente/{id}','ExpedienteController@obtenerExpediente');
  Route::get('buscarExpedientePorCasinoYNumero/{id_casino}/{busqueda}','ExpedienteController@buscarExpedientePorCasinoYNumero');
  Route::get('{id}','MTMController@buscarTodo');
});
//Estos por si las moscas lo pongo ... Son todos GET por lo menos
//Es muy posible que usuarios que no tienen el permiso ver_seccion_maquinas las use
Route::group(['prefix' => 'maquinas'], function () {
  Route::get('obtenerMTM/{id}', 'MTMController@obtenerMTM');
  Route::get('obtenerMTMEnCasino/{casino}/{id}', 'MTMController@obtenerMTMEnCasino');
  Route::get('buscarMaquinaPorNumeroMarcaYModelo/{casino?}/{busqueda}','MTMController@buscarMaquinaPorNumeroMarcaYModelo');
  Route::get('obtenerConfiguracionMaquina/{id}', 'MTMController@obtenerConfiguracionMaquina');
  Route::get('buscarMarcas/{marca}', 'MTMController@buscarMarcas');
});
/**********
Islas
**********/
Route::group(['prefix' => 'islas','middleware' => 'tiene_permiso:ver_seccion_islas'], function () {
  Route::get('/','IslaController@buscarTodo');
  Route::post('buscarIslas','IslaController@buscarIslas');
  Route::post('guardarIsla','IslaController@guardarIsla');
  Route::post('modificarIsla','IslaController@modificarIsla');
  Route::delete('eliminarIsla/{id_isla}','IslaController@eliminarIsla');
  Route::post('dividirIsla','IslaController@dividirIsla');
  Route::get('obtenerMTMReducido/{id}', 'MTMController@obtenerMTMReducido');
  Route::get('buscarIslotes/{id_casino}','IslaController@buscarIslotes');
  Route::post('asignarIslotes','IslaController@asignarIslotes');
  Route::get('obtenerIsla/{id_isla}','IslaController@obtenerIsla');
  Route::get('listarMaquinasPorNroIsla/{nro_isla}/{id_casino?}','IslaController@listarMaquinasPorNroIsla');
});
/**********
Movimientos
***********/
Route::group(['prefix' => 'movimientos','middleware' => 'tiene_permiso:ver_seccion_movimientos'], function () {
  Route::get('/','LogMovimientoController@movimientos');
  Route::get('casinosYMovimientosIngresosEgresos','LogMovimientoController@casinosYMovimientosIngresosEgresos');
  Route::post('buscarLogsMovimientos','LogMovimientoController@buscarLogsMovimientos');
  Route::post('enviarAFiscalizar', 'LogMovimientoController@enviarAFiscalizar');
  Route::post('guardarMaquina', 'MTMController@guardarMaquina');
  Route::post('cargaMasiva', 'LectorCSVController@cargaMasivaMaquinas');
  Route::post('guardarTipoCargaYCantMaq', 'LogMovimientoController@guardarTipoCargaYCantMaq');
  Route::get('obtenerMaquinasMovimiento/{id}','LogMovimientoController@obtenerMaquinasMovimiento');
  Route::get('obtenerFiscalizacionesMovimiento/{id}', 'LogMovimientoController@obtenerFiscalizacionesMovimiento');
  Route::get('obtenerRelevamientosFiscalizacion/{id_fiscalizacion_movimiento}','LogMovimientoController@obtenerRelevamientosFiscalizacion');
  Route::get('obtenerRelevamientoToma/{id_relevamiento}/{nro_toma?}', 'LogMovimientoController@obtenerRelevamientoToma');
  Route::post('cargarTomaRelevamiento', 'LogMovimientoController@cargarTomaRelevamiento');
  Route::post('nuevoLogMovimiento','LogMovimientoController@nuevoLogMovimiento');
  Route::post('eliminarMovimiento', 'LogMovimientoController@eliminarMovimiento');
  Route::get('obtenerDatos/{id}','LogMovimientoController@obtenerDatos');
  Route::get('imprimirMovimiento/{id_movimiento}','LogMovimientoController@imprimirMovimiento');
  Route::post('visarConObservacion', 'LogMovimientoController@visarConObservacion');
  Route::get('obtenerMTMEnCasino/{id_casino}/{admin}','MTMController@obtenerMTMEnCasino');
  Route::get('obtenerMTM/{id}', 'MTMController@obtenerMTM');
  Route::post('cargarMaquinasMovimiento','LogMovimientoController@cargarMaquinasMovimiento');
  Route::get('obtenerMovimiento/{id}','LogMovimientoController@obtenerMovimiento');
  Route::get('buscarIslaPorCasinoSectorYNro/{id_casino}/{id_sector}/{nro_isla}','IslaController@buscarIslaPorCasinoSectorYNro');
  Route::get('obtenerIsla/{id_isla}','IslaController@obtenerIsla');
  Route::get('obtenerIsla/{id_casino}/{id_sector}/{nro_isla}','IslaController@obtenerIslaPorNro');
});

/**********
Relevamientos
***********/
Route::group(['prefix' => 'relevamientos_movimientos','middleware' => 'tiene_permiso:ver_seccion_relevamientos_movimientos'], function () {
  Route::get('/','LogMovimientoController@relevamientosMovimientos');
  Route::post('buscarFiscalizaciones','FiscalizacionMovController@buscarFiscalizaciones');
  Route::get('eliminarFiscalizacion/{id}','FiscalizacionMovController@eliminarFiscalizacionParcial');
  Route::get('imprimirFiscalizacion/{id}','LogMovimientoController@imprimirFiscalizacion');
  Route::get('obtenerRelevamientosFiscalizacion/{id_fiscalizacion_movimiento}','LogMovimientoController@obtenerRelevamientosFiscalizacion');
  Route::get('obtenerRelevamientoToma/{id_relevamiento}/{nro_toma?}', 'LogMovimientoController@obtenerRelevamientoToma');
  Route::post('cargarTomaRelevamiento', 'LogMovimientoController@cargarTomaRelevamiento');
  Route::get('{id}','LogMovimientoController@relevamientosMovimientos');
});

/**********
Eventualidades ->intervenciones tecnicas
***********/
Route::group(['prefix' => 'eventualidades'], function () {
  Route::get('/','EventualidadController@buscarTodoDesdeFiscalizador')->middleware('tiene_permiso:ver_seccion_eventualidades');
  Route::post('buscarPorTipoFechaCasinoTurno','EventualidadController@buscarPorTipoFechaCasinoTurno');
  Route::get('crearEventualidad/{id_casino}', 'EventualidadController@crearEventualidad');
  Route::get('verPlanillaVacia/{id}', 'EventualidadController@verPlanillaVacia');
  Route::get('obtenerSectorEnCasino/{id_casino}/{id_sector}','EventualidadController@obtenerSectorEnCasino');
  Route::get('obtenerIslaEnCasino/{id_casino}/{nro_isla}','EventualidadController@obtenerIslaEnCasino');
  Route::post('CargarYGuardarEventualidad','EventualidadController@CargarYGuardarEventualidad');
  Route::get('visualizarEventualidadID/{id_ev}','EventualidadController@visualizarEventualidadID');
  Route::get('eliminarEventualidad/{id_ev}', 'EventualidadController@eliminarEventualidad');
  Route::get('visado/{id_ev}', 'EventualidadController@validarEventualidad');
  Route::post('buscarEventualidadesMTMs', 'LogMovimientoController@buscarEventualidadesMTMs');
  Route::get('leerArchivoEventualidad/{id}','EventualidadController@leerArchivoEventualidad');
  Route::get('obtenerIsla/{id_isla}','IslaController@obtenerIsla');
});
/**********
Eventualidades MTM ->intervenciones tecnicas mtm
***********/
Route::group(['prefix' => 'eventualidadesMTM','middleware' => 'tiene_permiso:ver_seccion_eventualidades_MTM'], function () {
  Route::get('/','LogMovimientoController@eventualidadesMTM');
  Route::post('nuevaEventualidadMTM','LogMovimientoController@nuevaEventualidadMTM');
  Route::post('cargarTomaRelevamiento', 'LogMovimientoController@cargarTomaRelevamiento');
  Route::get('obtenerRelevamientoToma/{id_relevamiento}/{nro_toma?}', 'LogMovimientoController@obtenerRelevamientoToma');
  Route::post('eliminarEventualidadMTM', 'LogMovimientoController@eliminarEventualidadMTM');
  Route::get('tiposMovIntervMTM', 'LogMovimientoController@tiposMovIntervMTM');
  Route::get('relevamientosEvMTM/{id_movimiento}', 'LogMovimientoController@relevamientosEvMTM');
  Route::get('imprimirEventualidadMTM/{id_mov}','LogMovimientoController@imprimirEventualidadMTM');
  Route::post('visarConObservacion', 'LogMovimientoController@visarConObservacion');
  Route::get('obtenerMTMEnCasino/{id_casino}/{admin}','MTMController@obtenerMTMEnCasino');
  Route::get('obtenerMTM/{id}', 'MTMController@obtenerMTM');
});
/******
CALENDARIO
******/
Route::group(['prefix' => 'calendario_eventos'], function () {
  Route::get('/','CalendarioController@calendar');
  Route::post('crearEvento','CalendarioController@crearEvento');
  Route::get('buscarEventos', 'CalendarioController@buscarEventos');
  Route::post('modificarEvento', 'CalendarioController@modificarEvento');
  Route::get('eliminarEvento/{id}','CalendarioController@eliminarEvento');
  Route::get('verMes/{month}/{year}','CalendarioController@verMes');
  Route::get('getEvento/{id}', 'CalendarioController@getEvento');
  Route::get('getOpciones', 'CalendarioController@getOpciones');
  Route::post('crearTipoEvento', 'CalendarioController@crearTipoEvento');
});
/**********
Sectores
***********/
Route::group(['prefix' => 'sectores'], function () {
  Route::get('/','SectorController@buscarTodo')->middleware('tiene_permiso:ver_seccion_sectores');
  Route::get('obtenerSector/{id_sector}','SectorController@obtenerSector');
  Route::delete('eliminarSector/{id_sector}','SectorController@eliminarSector');
  Route::post('guardarSector','SectorController@guardarSector');
  Route::post('modificarSector','SectorController@modificarSector');
  Route::get('obtenerSectoresPorCasino/{id_casino}','SectorController@obtenerSectoresPorCasino');
  Route::get('buscarIslaPorCasinoYNro/{id_casino}/{nro_isla}','IslaController@buscarIslaPorCasinoYNro');
  Route::get('obtenerIsla/{id_isla}','IslaController@obtenerIsla');
});
/**********
Contadores
***********/
Route::delete('contadores/eliminarContador/{id}','ContadorController@eliminarContador');
Route::delete('producidos/eliminarProducido/{id}','ProducidoController@eliminarProducido');
Route::delete('beneficios/eliminarBeneficios/{id_casino}/{id_tipo_moneda}/{anio}/{mes}','BeneficioController@eliminarBeneficios');

Route::group(['prefix' => 'importaciones','middleware' => 'tiene_permiso:ver_seccion_importaciones'], function () {
  Route::get('/','ImportacionController@buscarTodo')->middleware('tiene_permiso:ver_seccion_importaciones');
  //Lo necesitan los auditores
  Route::get('getCasinos/{nro_admin}','MTMController@getCasinos');
  Route::get('getMoneda/{id_casino}/{nro_admin}','MTMController@getMoneda');
  Route::post('buscar','ImportacionController@buscar');
  Route::get('{id_casino}/{fecha_busqueda?}/{orden?}','ImportacionController@estadoImportacionesDeCasino');
  Route::post('importarContador','ImportacionController@importarContador');
  Route::post('importarProducido','ImportacionController@importarProducido');
  Route::post('importarBeneficio','ImportacionController@importarBeneficio');
  Route::post('previewBeneficios','ImportacionController@previewBeneficios');
  Route::post('previewProducidos','ImportacionController@previewProducidos');
  Route::post('previewContadores','ImportacionController@previewContadores');
});
Route::group(['prefix' => 'cotizacion'], function () {
  Route::get('obtenerCotizaciones/{mes}','CotizacionController@obtenerCotizaciones');
  Route::post('guardarCotizacion','CotizacionController@guardarCotizacion');
});
/************
Relevamientos
************/
Route::group(['prefix' => 'relevamientos'], function () {
  Route::get('/','RelevamientoController@buscarTodo')->middleware('tiene_permiso:ver_seccion_relevamientos');
  Route::post('crearRelevamiento','RelevamientoController@crearRelevamiento');
  Route::post('cargarRelevamiento','RelevamientoController@cargarRelevamiento');
  Route::post('validarRelevamiento','RelevamientoController@validarRelevamiento');
  Route::get('obtenerRelevamiento/{id_relevamiento}','RelevamientoController@obtenerRelevamiento');
  Route::get('generarPlanilla/{id_relevamiento}','RelevamientoController@generarPlanilla');
  Route::get('generarPlanillaValidado/{id_relevamiento}','RelevamientoController@generarPlanillaValidado');
  Route::get('existeRelevamiento/{id_sector}','RelevamientoController@existeRelevamiento');
  Route::post('usarRelevamientoBackUp','RelevamientoController@usarRelevamientoBackUp');
  Route::get('descargarZip/{nombre}','RelevamientoController@descargarZip');
  Route::get('obtenerCantidadMaquinasPorRelevamiento/{id_sector}','RelevamientoController@obtenerCantidadMaquinasPorRelevamiento');
  Route::get('existeCantidadTemporalMaquinas/{id_sector}/{fecha_desde}/{fecha_hasta}','RelevamientoController@existeCantidadTemporalMaquinas');
  Route::post('crearCantidadMaquinasPorRelevamiento','RelevamientoController@crearCantidadMaquinasPorRelevamiento');
  Route::get('obtenerCantidadMaquinasRelevamientoHoy/{id_sector}','RelevamientoController@obtenerCantidadMaquinasRelevamiento');
  Route::post('eliminarCantidadMaquinasPorRelevamiento','RelevamientoController@eliminarCantidadMaquinasPorRelevamiento');
  Route::post('modificarDenominacionYUnidad','RelevamientoController@modificarDenominacionYUnidad');
  Route::post('buscarRelevamientos','RelevamientoController@buscarRelevamientos');
  Route::get('verRelevamientoVisado/{id_relevamiento}','RelevamientoController@obtenerRelevamientoVisado');
  Route::get('chequearRolFiscalizador','UsuarioController@chequearRolFiscalizador');
});
/* OBTENER FECHA Y HORA ACTUAL */
Route::get('obtenerFechaActual',function(){
  setlocale(LC_TIME, 'es_ES.UTF-8');
  return ['fecha' => strftime("%A, %d de %B de %Y"), 'fechaDate' => date("Y-m-d")];
});
/**************
RELEVAMIENTO PROGRESIVO
**************/
Route::group(['prefix' => 'relevamientosProgresivo','middleware' => 'tiene_permiso:ver_seccion_relevamientos_progresivos'], function () {
  Route::get('/','RelevamientoProgresivoController@buscarTodo');
  Route::post('buscarRelevamientosProgresivos','RelevamientoProgresivoController@buscarRelevamientosProgresivos');
  Route::post('crearRelevamiento' , 'RelevamientoProgresivoController@crearRelevamientoProgresivos');
  Route::post('cargarRelevamiento','RelevamientoProgresivoController@cargarRelevamiento');
  Route::post('guardarRelevamiento','RelevamientoProgresivoController@guardarRelevamiento');
  Route::post('validarRelevamiento','RelevamientoProgresivoController@validarRelevamiento');
  Route::get('obtenerRelevamiento/{id}','RelevamientoProgresivoController@obtenerRelevamiento');
  Route::get('generarPlanilla/{id_relevamiento_progresivo}/{sin?}','RelevamientoProgresivoController@generarPlanillaProgresivos');
  Route::get('eliminarRelevamientoProgresivo/{id_relevamiento_progresivo}','RelevamientoProgresivoController@eliminarRelevamientoProgresivo');
});
/******************************************************
RELEVAMIENTOS CONTROL AMBIENTAL - MÁQUINAS TRAGAMONEDAS
******************************************************/
Route::group(['prefix' => 'relevamientosControlAmbiental','middleware' => 'tiene_permiso:ver_seccion_relevamientos_control_ambiental'], function () {
  Route::get('/','RelevamientoAmbientalController@buscarTodo');
  Route::get('buscarRelevamientosAmbiental','RelevamientoAmbientalController@buscarRelevamientosAmbiental');
  Route::get('generarPlanilla/{id_relevamiento_ambiental}','RelevamientoAmbientalController@generarPlanillaAmbiental');
  Route::get('eliminarRelevamientoAmbiental/{id_relevamiento_ambiental}','RelevamientoAmbientalController@eliminarRelevamientoAmbiental');
  Route::get('obtenerRelevamiento/{id}','RelevamientoAmbientalController@obtenerRelevamiento');
  Route::get('obtenerGeneralidades','UsuarioController@obtenerOpcionesGeneralidades');
  Route::post('crearRelevamiento' , 'RelevamientoAmbientalController@crearRelevamientoAmbientalMaquinas');
  Route::post('cargarRelevamiento','RelevamientoAmbientalController@cargarRelevamiento');
  Route::post('guardarTemporalmenteRelevamiento','RelevamientoAmbientalController@guardarTemporalmenteRelevamiento');
  Route::post('validarRelevamiento','RelevamientoAmbientalController@validarRelevamiento');
});
/**********************************************
RELEVAMIENTOS CONTROL AMBIENTAL - MESAS DE PAÑO
**********************************************/
Route::group(['prefix' => 'relevamientosControlAmbientalMesas','middleware' => 'tiene_permiso:ver_seccion_relevamientos_control_ambiental'], function () {
  Route::get('/','RelevamientoAmbientalMesasController@buscarTodo');
  Route::get('buscarRelevamientosAmbiental','RelevamientoAmbientalMesasController@buscarRelevamientosAmbiental');
  Route::get('generarPlanilla/{id_relevamiento_ambiental}','RelevamientoAmbientalMesasController@generarPlanillaAmbiental');
  Route::get('eliminarRelevamientoAmbiental/{id_relevamiento_ambiental}','RelevamientoAmbientalMesasController@eliminarRelevamientoAmbiental');
  Route::get('obtenerRelevamiento/{id}','RelevamientoAmbientalMesasController@obtenerRelevamiento');
  Route::post('crearRelevamiento' , 'RelevamientoAmbientalMesasController@crearRelevamientoAmbientalMesas');
  Route::post('cargarRelevamiento','RelevamientoAmbientalMesasController@cargarRelevamiento');
  Route::post('guardarTemporalmenteRelevamiento','RelevamientoAmbientalMesasController@guardarTemporalmenteRelevamiento');
  Route::post('validarRelevamiento','RelevamientoAmbientalMesasController@validarRelevamiento');
});
/*************************
INFORMES CONTROL AMBIENTAL
*************************/
Route::group(['prefix' => 'informeControlAmbiental','middleware' => 'tiene_permiso:ver_seccion_informes_control_ambiental'], function () {
  Route::get('/','InformeControlAmbientalController@buscarTodo');
  Route::get('buscarInformesControlAmbiental','InformeControlAmbientalController@buscarInformesControlAmbiental');
  Route::get('imprimir/{id_casino}/{fecha}','InformeControlAmbientalController@imprimir');
});
/*******************
  Máquinas a pedir
********************/
Route::group(['prefix' => 'mtm_a_pedido'], function () {
  Route::get('/','MaquinaAPedidoController@buscarTodo')->middleware('tiene_permiso:ver_seccion_mtm_a_pedido');
  Route::get('obtenerMtmAPedido/{fecha}/{id_sector}','MaquinaAPedidoController@obtenerMtmAPedido');
  Route::post('buscarMTMaPedido','MaquinaAPedidoController@buscarMTMaPedido');
  Route::post('guardarMtmAPedido','MaquinaAPedidoController@guardarMtmAPedido');
  Route::delete('eliminarMmtAPedido/{id}','MaquinaAPedidoController@eliminarMTMAPedido');
});


Route::group(['prefix' => 'alertas_contadores', 'middleware' => 'tiene_permiso:ver_seccion_contadores'],function(){
  Route::get('/','AlertasContadoresController@buscarTodo');
  Route::post('buscarPolleos','AlertasContadoresController@buscarPolleos');
  Route::get('obtenerDetalles/{id_polleo}','AlertasContadoresController@obtenerDetalles');
  Route::get('obtenerDetalleCompleto/{id_polleo}/{nro_admin}','AlertasContadoresController@obtenerDetalleCompleto');
  Route::post('importarPolleos','AlertasContadoresController@importarPolleos');
});

/*******************
PRODUCIDOS-AJUSTES PRODUCIDO
******************/
Route::group(['prefix' => 'producidos','middleware' => 'tiene_permiso:ver_seccion_producidos'],function (){
  Route::get('','ProducidoController@buscarTodo')->middleware('tiene_permiso:ver_seccion_producidos');
  Route::post('buscarProducidos','ProducidoController@buscarProducidos');
  Route::get('generarPlanillaDiferencias/{id_producido}','ProducidoController@generarPlanillaDiferencias');
  Route::get('generarPlanillaProducido/{id_producido}','ProducidoController@generarPlanillaProducido');
  Route::post('guardarAjuste','ProducidoController@guardarAjuste');
  Route::get('datosAjusteMTM/{id_maquina}/{id_producidos}','ProducidoController@datosAjusteMTM');
  Route::get('ajustarProducido/{id_producido}','ProducidoController@ajustarProducido');
});

/***********
 Estadisticas
************/
Route::group(['prefix' => 'estadisticas_relevamientos'],function (){
  Route::get('/','MaquinaAPedidoController@buscarTodoInforme' )->middleware('tiene_permiso:ver_seccion_estadisticas_relevamientos');
  Route::post('guardarMtmAPedido','MaquinaAPedidoController@guardarMtmAPedido');
  Route::post('obtenerUltimosRelevamientosPorMaquina','RelevamientoController@obtenerUltimosRelevamientosPorMaquina');
  Route::post('obtenerUltimosRelevamientosPorMaquinaNroAdmin','RelevamientoController@obtenerUltimosRelevamientosPorMaquinaNroAdmin');
  Route::post('buscarMaquinasSinRelevamientos','RelevamientoController@buscarMaquinasSinRelevamientos');
  Route::get('obtenerFechasMtmAPedido/{id}', 'MaquinaAPedidoController@obtenerFechasMtmAPedido');
  Route::get('buscarMaquinas/{id_casino}','RelevamientoController@buscarMaquinasPorCasino');
});
/**********
 Beneficios
***********/
Route::group(['prefix' => 'beneficios','middleware' => 'tiene_permiso:ver_seccion_beneficios'],function (){
  Route::get('/','BeneficioController@buscarTodo')->middleware('tiene_permiso:ver_seccion_beneficios');
  Route::post('buscarBeneficios','BeneficioController@buscarBeneficios');
  Route::post('obtenerBeneficiosParaValidar','BeneficioController@obtenerBeneficiosParaValidar');
  Route::post('ajustarBeneficio','BeneficioController@ajustarBeneficio');
  Route::post('validarBeneficios','BeneficioController@validarBeneficios');
  Route::post('validarBeneficiosSinProducidos','BeneficioController@validarBeneficiosSinProducidos');
  Route::get('generarPlanilla/{id_casino}/{id_tipo_moneda}/{anio}/{mes}','BeneficioController@generarPlanilla');
  Route::get('generarPlanillaDiferenciasProducido/{id_producido}','ProducidoController@generarPlanillaDiferencias');
  Route::post('cargarImpuesto','BeneficioController@cargarImpuesto');
});

/*********
LAYOUT
*********/
Route::get('menu_layout',function(){
    return view('menu_layout');
});

//PARCIAL
Route::get('layout_parcial','LayoutController@buscarTodo')->middleware('tiene_permiso:ver_seccion_layout_parcial');
Route::group(['prefix' => 'layouts'],function (){
  Route::post('crearLayoutParcial','LayoutController@crearLayoutParcial');
  Route::post('usarLayoutBackup' , 'LayoutController@usarLayoutBackup');
  Route::get('existeLayoutParcial/{id_sector}','LayoutController@existeLayoutParcial');
  Route::get('existeLayoutParcialGenerado/{id_sector}','LayoutController@existeLayoutParcialGenerado');
  Route::get('obtenerLayoutParcial/{id}','LayoutController@obtenerLayoutParcial');
  Route::get('obtenerLayoutParcialValidar/{id}','LayoutController@obtenerLayoutParcialValidar');
  Route::get('generarPlanillaLayoutParcial/{id}','LayoutController@generarPlanillaLayoutParcial');
  Route::get('descargarLayoutParcialZip/{nombre}','LayoutController@descargarLayoutParcialZip');
  Route::post('buscarLayoutsParciales' , 'LayoutController@buscarLayoutsParciales');
  Route::post('cargarLayoutParcial' , 'LayoutController@cargarLayoutParcial');
  Route::post('validarLayoutParcial' , 'LayoutController@validarLayoutParcial');
});

//TOTAL
Route::get('layout_total','LayoutController@buscarTodoTotal')->middleware('tiene_permiso:ver_seccion_layout_total');
Route::group(['prefix' => 'layouts','middleware' => 'tiene_permiso:ver_seccion_layout_total'], function () {
  Route::post('crearLayoutTotal','LayoutController@crearLayoutTotal');
  Route::post('buscarLayoutsTotales' , 'LayoutController@buscarLayoutsTotales');
  Route::get('descargarLayoutTotalZip/{nombre}','LayoutController@descargarLayoutTotalZip');
  Route::get('generarPlanillaLayoutTotales/{id}','LayoutController@generarPlanillaLayoutTotales');
  Route::get('generarPlanillaLayoutTotalesCargado/{id}','LayoutController@generarPlanillaLayoutTotalesCargado');
  Route::post('guardarLayoutTotal','LayoutController@guardarLayoutTotal');
  Route::post('cargarLayoutTotal' , 'LayoutController@cargarLayoutTotal');
  Route::get('descargarLayoutTotalZip/{nombre}','LayoutController@descargarLayoutTotalZip');
  Route::get('obtenerLayoutTotal/{id}','LayoutController@obtenerLayoutTotal');
  Route::post('validarLayoutTotal' , 'LayoutController@validarLayoutTotal');
  Route::post('usarLayoutTotalBackup' , 'LayoutController@usarLayoutTotalBackup');
  Route::get('islasLayoutTotal/{id_layout_total}','LayoutController@islasLayoutTotal');
  Route::delete('eliminarLayoutTotal/{id_layout_total}','LayoutController@eliminarLayoutTotal');
  Route::get('obtenerTurno/{id}','CasinoController@obtenerTurno');
  Route::get('obtenerSectoresPorCasino/{id_casino}','SectorController@obtenerSectoresPorCasino');
  Route::get('obtenerMTMsEnIsla/{id_casino}/{nro_isla}/{nro_admin}','LayoutController@obtenerMTMsEnIsla');
  Route::get('buscarIslaPorSectorYNro/{id_sector}/{nro_isla}','IslaController@buscarIslaPorSectorYNro');
});


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
Route::get('informeContableMTM','informesController@buscarTodoInformeContable');//carga pagina
Route::get('obtenerInformeContableDeMaquina/{id_maquina}','informesController@obtenerInformeContableDeMaquina');//informe ultimos 30 dias

Route::group(['prefix' => 'informesMTM'], function () {
  Route::get('/','informesController@obtenerUltimosBeneficiosPorCasino');
  Route::get('generarPlanilla','informesController@generarPlanilla');
  Route::get('generarPlanillaIslasMaquinas','informesController@generarPlanillaIslasMaquinas');
  Route::get('obtenerEstadoParqueDeCasino/{id_casino}','informesController@obtenerInformeEstadoParqueDeParque');
});


Route::get('informesBingo',function(){
  return view('seccionInformesBingo');
});
Route::get('informesJuegos',function(){
  return view('seccionInformesJuegos');
});

Route::group(['prefix' => 'informeSector','middleware' => ['tiene_permiso:ver_seccion_informesector']], function () {
  Route::get('/','informesController@mostrarInformeSector')->middleware('tiene_permiso:ver_seccion_informesector');
  Route::get('obtenerMTMs','informesController@obtenerMTMs');
  Route::post('transaccionEstadoMasivo','MTMController@transaccionEstadoMasivo');
});

Route::group(['prefix' => 'estadisticas_no_toma'], function () {
  Route::get('/','informesController@mostrarEstadisticasNoTomaGenerico');
  Route::get('obtenerEstadisticasNoToma/{id}','informesController@obtenerEstadisticasNoToma');
});
Route::get('/relevamientos/estadisticas_no_toma/{id}','informesController@mostrarEstadisticasNoToma');
/************************
Prueba Juegos y Progresivos
************************/
Route::group(['prefix' => 'prueba_juegos'],function(){
  Route::get('/',function(){
    return view('seccionPruebaJuegos');
  });
  Route::get('/','PruebaController@buscarTodo');
  Route::get('pdf/{id_prueba_juego}','PruebaController@obtenerPDF');
  Route::get('obtenerPruebaJuego/{id_prueba_juego}','PruebaController@obtenerPruebaJuego');
  Route::post('guardarPruebaJuego','PruebaController@guardarPruebaJuego');
});

Route::group(['prefix' => 'pruebas'],function(){
  Route::post('buscarPruebasDeJuego','PruebaController@buscarPruebasDeJuego');
  Route::get('generarPlanillaPruebaDeJuego/{id_prueba_juego}','PruebaController@generarPlanillaPruebaDeJuego');
  Route::post('sortearMaquinaPruebaDeJuego','PruebaController@sortearMaquinaPruebaDeJuego');
  Route::post('buscarPruebasProgresivo','PruebaController@buscarPruebasProgresivo');
  Route::post('sortearMaquinaPruebaDeProgresivo','PruebaController@sortearMaquinaPruebaDeProgresivo');
  Route::get('generarPlanillaPruebaDeProgresivos/{id_prueba_progresivo}','PruebaController@generarPlanillaPruebaDeProgresivos');
});

Route::get('prueba_progresivos','PruebaController@buscarTodoPruebaProgresivo');

/************************
PRUEBAS DE DESARROLLO - AUXILIAR
****************************/

Route::get('fabricjs',function(){
  return view('fabricjs');
});

Route::get('plano',function(){
  return view('plano');
});

Route::get('calendario_eventos',function(){
  return view('calendar');
});

/*SECCION MESAS DE PAÑO*/
Route::get('usuarios/buscarFiscalizadores/{id_cas}/{nombre}', 'UsuarioController@buscarFiscaNombreCasino');

//gestion mesas
Route::group(['prefix' => 'mesas'], function () {
  Route::get('/','Mesas\Mesas\BuscarMesasController@getMesas');
  Route::post('buscarMesas','Mesas\Mesas\BuscarMesasController@buscarMesas');
  Route::post('nuevaMesa/{id_casino}','Mesas\Mesas\ABMMesaController@guardar');
  Route::post('modificarMesa/{id_casino}','Mesas\Mesas\ABMMesaController@modificar');
  Route::get('eliminarMesa/{id_casino}/{id_mesa_de_panio}','Mesas\Mesas\ABMMesaController@eliminar');
  Route::get('cargarDatos','Mesas\Mesas\BuscarMesasController@getDatos');
  Route::get('detalleMesa/{id_mesa}','Mesas\Mesas\BuscarMesasController@getMesa');
  Route::get('obtenerMesasApertura/{id_cas}/{nro_mesa}', 'Mesas\Mesas\BuscarMesasController@buscarMesaPorNroCasino');
  Route::get('obtenerDatos/{id_cas}', 'Mesas\Mesas\BuscarMesasController@datosSegunCasino');
});

Route::group(['prefix' => 'cierres'], function () {
  Route::post('filtrosCierres','Mesas\Cierres\BCCierreController@filtros');
  Route::post('guardar', 'Mesas\Cierres\ABMCierreController@guardar');
  Route::post('modificarCierre','Mesas\Cierres\ABMCierreController@modificarCierre');
  Route::get('obtenerCierres/{id_cierre}', 'Mesas\Cierres\BCCierreController@getCierre');
  Route::get('bajaCierre/{id_cierre}', 'Mesas\Cierres\BCCierreController@eliminarCierre')->middleware(['tiene_permiso:m_eliminar_cierres_y_aperturas']);
});
Route::get('mesas/obtenerMesasCierre/{id_cas}/{nro_mesa}', 'Mesas\Mesas\BuscarMesasController@buscarMesaPorNroCasino');

Route::group(['prefix' => 'aperturas'], function () {
  Route::get('/', 'Mesas\Aperturas\BCAperturaController@buscarTodo');
  Route::get('quienSoy' ,'UsuarioController@quienSoy');
  Route::get('obtenerMesasPorJuego/{id_juego_mesa}/{nro_mesa}', 'Mesas\Mesas\BuscarMesasController@buscarMesaPorJuego');
  Route::post('agregarAperturaAPedido','Mesas\Aperturas\ABMAperturaController@agregarAperturaAPedido');
  Route::get('buscarAperturasAPedido','Mesas\Aperturas\ABMAperturaController@buscarAperturasAPedido');
  Route::delete('borrarAperturaAPedido/{id_apertura_a_pedido}','Mesas\Aperturas\ABMAperturaController@borrarAperturaAPedido');
  Route::post('filtrosAperturas', 'Mesas\Aperturas\BCAperturaController@filtros');
  Route::get('obtenerAperturas/{id_apertura}', 'Mesas\Aperturas\BCAperturaController@getApertura');
  Route::post('guardarApertura', 'Mesas\Aperturas\ABMAperturaController@guardar');
  Route::post('modificarApertura','Mesas\Aperturas\ABMAperturaController@modificarApertura');
  Route::get('bajaApertura/{id_apertura}', 'Mesas\Aperturas\BCAperturaController@eliminarApertura')->middleware(['tiene_permiso:m_eliminar_cierres_y_aperturas']);
  Route::post('generarRelevamiento', 'Mesas\Aperturas\ABMCRelevamientosAperturaController@generarRelevamiento');
  Route::post('validarApertura','Mesas\Aperturas\VAperturaController@validarApertura');
  Route::get('obtenerApValidar/{id_apertura}', 'Mesas\Aperturas\BCAperturaController@obtenerApParaValidar');
  Route::get('desvincularApertura/{id_apertura}', 'Mesas\Cierres\ABMCCierreAperturaController@desvincularApertura')->middleware(['tiene_permiso:m_desvincular_aperturas']);
});
Route::get('sorteo-aperturas/descargarZip/{nombre}', 'Mesas\Aperturas\ABMCRelevamientosAperturaController@descargarZip');
Route::get('compararCierre/{id_apertura}/{id_cierre}/{id_moneda}','Mesas\Aperturas\BCAperturaController@obtenerDetallesApCierre');

//Sección Juegos
Route::group(['prefix' => 'mesas-juegos'], function () {
  Route::post('buscarJuegos', 'Mesas\Juegos\BuscarJuegoController@buscarJuegos');
  Route::post('nuevoJuego', 'Mesas\Juegos\ABMJuegoController@guardar');
  Route::post('modificarJuego', 'Mesas\Juegos\ABMJuegoController@modificarJuego');
  Route::get('obtenerJuego/{id_juego}', 'Mesas\Juegos\ABMJuegoController@obtenerJuego');
  Route::get('obtenerJuegoPorCasino/{id_cas}/{nombreJuego}', 'Mesas\Juegos\BuscarJuegoController@buscarJuegoPorCasinoYNombre');
  Route::get('bajaJuego/{id}', 'Mesas\Juegos\ABMJuegoController@eliminarJuego');
});
Route::get('/juegosMesa', 'Mesas\Juegos\BuscarJuegoController@buscarTodo');

Route::group(['prefix' => 'sectores-mesas'], function () {
  Route::post('nuevoSector','Mesas\Sectores\ABMCSectoresController@guardar');
  Route::get('obtenerSector/{id_sector}','Mesas\Sectores\ABMCSectoresController@obtenerSector');
  Route::post('modificarSector/{id_sector}','Mesas\Sectores\ABMCSectoresController@modificarSector');
  Route::get('eliminarSector/{id_sector}','Mesas\Sectores\ABMCSectoresController@eliminarSector');
  Route::post('buscarSectores','Mesas\Sectores\ABMCSectoresController@filtrarSectores');
  Route::post('guardar','Mesas\Sectores\ABMCSectoresController@guardar');
});

Route::group(['prefix' => 'apuestas'], function () {
  Route::get('/', 'Mesas\Apuestas\BCApuestasController@buscarTodo');
  Route::post('buscarRelevamientosApuestas', 'Mesas\Apuestas\BCApuestasController@filtros');
  Route::post('generarRelevamientoApuestas', 'Mesas\Apuestas\BCApuestasController@obtenerNombreZip');
  Route::get('descargarZipApuestas/{nombre}', 'Mesas\Apuestas\BCApuestasController@descargarZip');
  Route::get('obtenerDatos/{id_relevamiento}', 'Mesas\Apuestas\BCApuestasController@obtenerRelevamientoCarga');
  Route::post('cargarRelevamiento','Mesas\Apuestas\ABMApuestasController@cargarRelevamiento');
  Route::get('relevamientoCargado/{id_relevamiento}', 'Mesas\Apuestas\BCApuestasController@obtenerRelevamientoApuesta');
  Route::get('baja/{id_relevamiento}', 'Mesas\Apuestas\BVApuestasController@eliminar');
  Route::post('validar', 'Mesas\Apuestas\BVApuestasController@validar');
  Route::post('obtenerRelevamientoBackUp', 'Mesas\Apuestas\BCApuestasController@buscarRelevamientosBackUp');
  Route::get('imprimir/{id}','Mesas\Apuestas\BCApuestasController@imprimirPlanilla');
  Route::get('consultarMinimo','Mesas\Apuestas\BCApuestasController@consultarMinimo');
  Route::get('obtenerRequerimientos/{id_cas}/{id_moneda}','Mesas\Apuestas\ABMCApuestaMinimaController@obtenerApuestaMinima');
  Route::post('modificarRequerimiento','Mesas\Apuestas\ABMCApuestaMinimaController@modificar');
  Route::get('obtenerRequerimientos/{id_cas}/{id_moneda}','Mesas\Apuestas\ABMCApuestaMinimaController@obtenerApuestaMinima');
  Route::post('modificarRequerimiento','Mesas\Apuestas\ABMCApuestaMinimaController@modificar');
  Route::get('buscarUsuario/{id_usuario}','UsuarioController@buscarUsuario');
});
Route::get('turnos/buscarTurnos/{nro}','Mesas\Turnos\TurnosController@buscarTurnos');

//informes fiscalizadores (compara Cierres contra Aperturas y verifica Apuestas Minimas)
Route::group(['prefix' => 'informeDiarioBasico','middleware' => 'tiene_permiso:m_ver_seccion_informe_fiscalizadores'], function () {
  Route::get('/','Mesas\InformeFiscalizadores\BCInformesController@index');
  Route::post('buscar', 'Mesas\InformeFiscalizadores\BCInformesController@filtros');
  Route::post('buscarInformes','Mesas\InformeFiscalizadores\BCInformesController@filtros');
  Route::get('imprimir/{id_informe_fiscalizacion}','Mesas\InformeFiscalizadores\BCInformesController@imprimirPlanilla');
});

Route::group(['prefix' => 'importacionDiaria','middleware' => 'tiene_permiso:m_ver_seccion_importaciones'],function (){
  Route::get('/','Mesas\Importaciones\Mesas\ImportadorController@buscarTodo');
  Route::post('importar','Mesas\Importaciones\Mesas\ImportadorController@importarDiario')->middleware(['tiene_permiso:m_importar']);
  Route::post('importarCierres','Mesas\Importaciones\Mesas\ImportadorController@importarCierres')->middleware(['tiene_permiso:m_importar']);
  Route::post('filtros','Mesas\Importaciones\Mesas\ImportadorController@filtros');
  Route::get('verImportacion/{id_imp}/{t_mesa?}','Mesas\Importaciones\Mesas\ImportadorController@buscarPorTipoMesa');
  Route::get('imprimir/{id}','Mesas\Importaciones\Mesas\ImportadorController@imprimirDiario');
  Route::post('guardar','Mesas\Importaciones\Mesas\ImportadorController@guardarImportacionDiaria');
  Route::get('eliminarImportacion/{id_imp}','Mesas\Importaciones\Mesas\ImportadorController@eliminar');
  Route::post('ajustarDetalle','Mesas\Importaciones\Mesas\ImportadorController@ajustarDetalle');
  Route::get('imprimirMensual','Mesas\Importaciones\Mesas\ImportadorController@imprimirMensual');
});

Route::group(['prefix' => 'informeAnual','middleware' => 'tiene_permiso:m_bc_anuales'],function (){
  Route::get('/',function(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    return view('Informes.seccionInformesAnuales',['casinos'=>$usuario->casinos]);
  });
  Route::post('obtenerDatos','Mesas\InformesMesas\BCAnualesController@buscarPorAnioCasinoMoneda');
});

Route::group(['prefix' => 'informeMensual','middleware' => 'tiene_permiso:m_bc_diario_mensual'],function(){
  Route::get('/',function(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    return view('Informes.seccionInformesMensuales',['casinos'=>$usuario->casinos]);
  });
  Route::post('obtenerDatos','Mesas\InformesMesas\BCMensualesController@obtenerDatosGraficos');
});


Route::get('canon','Mesas\Canon\BPagosController@index')->middleware(['tiene_permiso:m_ver_seccion_canon']);
Route::group(['prefix' => 'canon','middleware' => ['tiene_permiso:m_a_pagos']], function () {
  Route::post('crearOModificarPago','Mesas\Canon\APagosController@crearOModificar');
  Route::delete('borrarPago/{id_detalle}','Mesas\Canon\APagosController@borrar');
  Route::post('modificarInformeBase','Mesas\Canon\APagosController@modificarInformeBase');
});
Route::group(['prefix' => 'canon','middleware' => ['tiene_permiso:m_b_pagos']], function () {
  Route::get('getMesesCuotas/{id_casino}/{anio_inicio}', 'Mesas\Canon\BPagosController@mesesCuotasCanon');
  Route::get('mesesCargados/{id_casino}/{anio_inicio}','Mesas\Canon\BPagosController@mesesCargados');
  Route::post('buscarPagos','Mesas\Canon\BPagosController@filtros');
  Route::get('obtenerPago/{id_detalle}','Mesas\Canon\BPagosController@obtenerPago');
  Route::get('obtenerAnios/{id_casino}','Mesas\Canon\BPagosController@obtenerAnios');
  Route::get('obtenerInformeBase/{id_casino}','Mesas\Canon\BPagosController@obtenerInformeBase');
  Route::post('verInforme','Mesas\Canon\BPagosController@verInformeFinalMesas');
});

Route::group(['prefix' => 'solicitudImagenes','middleware' => ['tiene_permiso:m_abmc_img_bunker']], function () {
  Route::get('/','Mesas\Bunker\ABMCImgBunkerController@index');
  Route::post('buscar','Mesas\Bunker\ABMCImgBunkerController@filtros');
  Route::get('obtenerMesas/{id}', 'Mesas\Bunker\ABMCImgBunkerController@obtenerBunker');
  Route::get('hayCoincidencia/{drop}/{id_detalle}', 'Mesas\Bunker\ABMCImgBunkerController@consultarDiferencias');
  Route::post('sorteoFechasMesas', 'Mesas\Bunker\ABMCImgBunkerController@altaImgsBunker');
  Route::post('guardar','Mesas\Bunker\ABMCImgBunkerController@cargar');
});

//BINGO
Route::group(['prefix' => 'bingo'],function(){
  Route::group(['middleware' => ['tiene_permiso:ver_seccion_sesion_relevamientos']],function(){
    Route::get('/','Bingo\SesionesController@index');
    Route::get('buscarSesion','Bingo\SesionesController@buscarSesion');
    Route::post('guardarSesion','Bingo\SesionesController@guardarSesion');
    Route::delete('eliminarSesion/{id}','Bingo\SesionesController@eliminarSesion');
    Route::get('obtenerSesion/{id}','Bingo\SesionesController@obtenerSesion');
    Route::post('guardarCierreSesion','Bingo\SesionesController@guardarCierreSesion');
    Route::post('guardarRelevamiento','Bingo\SesionesController@guardarRelevamiento');
    Route::post('reAbrirSesion/{id}','Bingo\SesionesController@reAbrirSesion');
    Route::post('modificarCierreSesion','Bingo\SesionesController@modificarCierreSesion');
    Route::post('modificarSesion','Bingo\SesionesController@modificarSesion');
    Route::get('generarPlanillaSesion','Bingo\SesionesController@generarPlanillaSesion');
    Route::get('generarPlanillaCierreSesion','Bingo\SesionesController@generarPlanillaCierreSesion');
    Route::get('generarPlanillaRelevamiento','Bingo\SesionesController@generarPlanillaRelevamiento');
    Route::delete('eliminarPartida/{id}','Bingo\SesionesController@eliminarPartida');
  });
  Route::group(['middleware' => ['tiene_permiso:bingo_ver_gestion']],function(){
    Route::get('gestionBingo','Bingo\GestionController@index');
    Route::get('buscarPremio','Bingo\GestionController@buscarPremio');
    Route::get('buscarCanon','Bingo\GestionController@buscarCanon');
    Route::post('guardarPremio','Bingo\GestionController@guardarPremio');
    Route::post('guardarCanon','Bingo\GestionController@guardarCanon');
    Route::delete('eliminarPremio/{id}','Bingo\GestionController@eliminarPremio');
    Route::delete('eliminarCanon/{id}','Bingo\GestionController@eliminarCanon');
    Route::get('obtenerPremio/{id}','Bingo\GestionController@obtenerPremio');
    Route::get('obtenerCanon/{id}','Bingo\GestionController@obtenerCanon');
    Route::post('modificarPremio','Bingo\GestionController@modificarPremio');
    Route::post('modificarCanon','Bingo\GestionController@modificarCanon');
  });
  Route::group(['middleware' => ['tiene_permiso:importar_bingo']],function(){
    Route::get('importarRelevamiento','Bingo\ImportacionController@index');
    Route::get('buscarRelevamiento','Bingo\ImportacionController@buscarRelevamiento');
    Route::delete('eliminarImportacion/{id}','Bingo\ImportacionController@eliminarImportacion');
    Route::post('guardarImportacion','Bingo\ImportacionController@guardarImportacion');
    Route::get('obtenerImportacionCompleta/{id}','Bingo\ImportacionController@obtenerImportacionCompleta');
  });
  Route::group(['middleware' => ['tiene_permiso:reporte_diferencia_bingo']], function () {
    Route::get('reportesDiferencia','Bingo\ReportesController@reportesDiferencia');
    Route::get('buscarReportesDiferencia','Bingo\ReportesController@buscarReportesDiferencia');
    Route::get('obtenerDiferencia/{id}','Bingo\ReportesController@obtenerDiferencia');
    Route::post('guardarReporteDiferencia','Bingo\ReportesController@guardarReporteDiferencia');
  });
  Route::group(['middleware' => ['tiene_permiso:informes_bingos']], function () {
    Route::get('generarPlanillaInforme/{fecha}/{id_casino}/{valor?}','Bingo\InformeController@generarPlanilla');
    Route::get('informe','Bingo\InformeController@index');
  });
});
/************
AUTOEXCLUSIÓN
*************/
Route::group(['prefix' => 'autoexclusion','middleware' => 'tiene_permiso:ver_seccion_ae_alta'], function () {
  Route::get('/','Autoexclusion\AutoexclusionController@index');
  Route::delete('eliminarAE/{id_autoexcluido}','Autoexclusion\AutoexclusionController@eliminarAE');
  Route::post('agregarAE','Autoexclusion\AutoexclusionController@agregarAE');
  Route::post('subirArchivo','Autoexclusion\AutoexclusionController@subirArchivo');
  Route::get('cambiarEstadoAE/{id}/{id_estado}','Autoexclusion\AutoexclusionController@cambiarEstadoAE');
  Route::get('existeAutoexcluido/{dni}','Autoexclusion\AutoexclusionController@existeAutoexcluido');
  Route::get('buscarAutoexcluidos','Autoexclusion\AutoexclusionController@buscarAutoexcluidos');
  Route::get('buscarAutoexcluido/{id}','Autoexclusion\AutoexclusionController@buscarAutoexcluido');
  Route::get('mostrarArchivo/{id_importacion}/{tipo_archivo}','Autoexclusion\AutoexclusionController@mostrarArchivo');
  Route::get('mostrarFormulario/{id_formulario}','Autoexclusion\AutoexclusionController@mostrarFormulario');
  Route::get('generarSolicitudAutoexclusion/{id}','Autoexclusion\AutoexclusionController@generarSolicitudAutoexclusion');
  Route::get('generarSolicitudFinalizacionAutoexclusion/{id}','Autoexclusion\AutoexclusionController@generarSolicitudFinalizacionAutoexclusion');
  Route::get('generarConstanciaReingreso/{id}','Autoexclusion\AutoexclusionController@generarConstanciaReingreso');
  Route::get('BDCSV','Autoexclusion\AutoexclusionController@BDCSV');
  Route::get('{dni?}','Autoexclusion\AutoexclusionController@index');
});

Route::group(['prefix' => 'informesAutoexcluidos','middleware' => 'tiene_permiso:ver_seccion_ae_informes_listado'], function () {
  Route::get('/','Autoexclusion\InformesAEController@todo');
  Route::get('buscarAutoexcluidos','Autoexclusion\InformesAEController@buscarAutoexcluidos');
  Route::get('verFoto/{id_autoexcluido}','Autoexclusion\InformesAEController@verFoto');
});

Route::group(['prefix' => 'galeriaImagenesAutoexcluidos','middleware' => 'tiene_permiso:ver_seccion_ae_informes_galeria'], function () {
  Route::get('getPathsFotosAutoexcluidos','Autoexclusion\GaleriaImagenesAutoexcluidosController@getPathsFotosAutoexcluidos');
  Route::get('getDatosUnAutoexcluido/{nro_dni}','Autoexclusion\GaleriaImagenesAutoexcluidosController@getDatosUnAutoexcluido');
  Route::get('mostrarArchivo/{id_importacion}/{tipo_archivo}','Autoexclusion\AutoexclusionController@mostrarArchivo');
  Route::get('{dni?}','Autoexclusion\GaleriaImagenesAutoexcluidosController@todo');
});

Route::group(['prefix' =>'API'],function(){
  Route::group(['prefix' => 'AE'],function(){
    Route::get('/',function(){//Para probar el acceso
      return 1;
    });
    Route::get('fechas/{DNI}','Autoexclusion\APIAEController@fechas');
    Route::get('finalizar/{DNI}','Autoexclusion\APIAEController@finalizar');
    Route::post('agregar','Autoexclusion\APIAEController@agregar');
  });
});
