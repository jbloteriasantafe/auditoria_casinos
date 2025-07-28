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
})->middleware(['tiene_permiso:usar_tickets']);

Route::group(['prefix' => 'configCuenta'], function () {
  Route::get('/','UsuarioController@configUsuario');
  Route::post('modificarPassword','UsuarioController@modificarPassword');
  Route::post('modificarImagen','UsuarioController@modificarImagen');
  Route::post('modificarDatos','UsuarioController@modificarDatos');
  Route::get('buscarUsuario/{id_usuario}','UsuarioController@buscarUsuario');
  Route::get('imagen','UsuarioController@leerImagenUsuario');
});

Route::get('logout','AuthenticationController@logoutGET');
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
  Route::post('crearModificarProgresivo','ProgresivoController@crearModificarProgresivo');
  Route::delete('eliminarProgresivo/{id_progresivo}','ProgresivoController@eliminarProgresivo');
  Route::post('crearProgresivosIndividuales','ProgresivoController@crearProgresivosIndividuales');
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
Route::group(['prefix' => 'expedientes','middleware' => 'tiene_permiso:ver_seccion_expedientes'], function () {
  Route::get('/','ExpedienteController@buscarTodo');
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
Route::group(['prefix' => 'usuarios','middleware' => 'tiene_permiso:ver_seccion_usuarios'], function () {
  Route::get('/','UsuarioController@buscarTodo');
  Route::post('buscar','UsuarioController@buscarUsuarios');
  Route::get('buscar/{id_usuario}','UsuarioController@buscarUsuario');
  Route::post('guardarUsuario','UsuarioController@guardarUsuario');
  Route::delete('eliminarUsuario/{id_usuario}','UsuarioController@eliminarUsuario');
  Route::post('reestablecerContraseña','UsuarioController@reestablecerContraseña');
  Route::post('buscarPermisosPorRoles',"PermisoController@buscarPermisosPorRoles");
});
/***********
Roles y permisos
***********/
Route::group(['prefix' => 'roles_permisos','middleware' => 'tiene_permiso:ver_seccion_roles_permisos'], function () {
  Route::get('/','RolController@buscarTodo');
  Route::group(['prefix' => 'rol'], function () {
    Route::post('buscar','RolController@buscarRoles');
    Route::post('guardar','RolController@guardarRol');
    Route::post('modificar','RolController@modificarRol');
    Route::get('getAll','RolController@getAll');
    Route::delete('{id}','RolController@eliminarRol');
    Route::get('{id}','RolController@getRol');
  });
  Route::group(['prefix' => 'permiso'], function () {
    Route::post('buscar','PermisoController@buscarPermisos');
    Route::post('guardar','PermisoController@guardarPermiso');
    Route::post('modificar','PermisoController@modificarPermiso');
    Route::get('getAll','PermisoController@getAll');
    Route::delete('{id}','PermisoController@eliminarPermiso');
    Route::get('{id}','PermisoController@getPermiso');
  });
});

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
Route::group(['prefix' => 'packJuegos','middleware' => 'tiene_permiso:ver_seccion_juegos'], function () {
  Route::get('/','PackJuegoController@buscarTodo');
  Route::POST('buscar','PackJuegoController@buscar');
  Route::get('obtenerPackJuego/{id}','PackJuegoController@obtenerPackJuego');
  Route::get('obtenerJuegos/{id}','PackJuegoController@obtenerJuegosDePack');
  Route::delete('eliminarPackJuego/{id}','PackJuegoController@eliminarPack');
  Route::get('obtenerJuegosMTM/{id_maquina}','PackJuegoController@obtenerJuegosDePackMTM');
  Route::get('buscarPackJuegos/{busqueda}','PackJuegoController@buscarPackJuegoPorNombre');
  Route::post('guardarPackJuego','PackJuegoController@guardarPackJuego');
  Route::post('modificarPackJuego','PackJuegoController@modificarPackJuego');
  Route::post('asociarPackJuego','PackJuegoController@asociarPackJuego');
  Route::post('asociarMtmJuegosPack','PackJuegoController@asociarMtmJuegosPack');
  Route::get('obtenerMTMEnCasino/{casino}/{id}', 'MTMController@obtenerMTMEnCasino');
});
/***********
Disposiciones
***********/
Route::group(['prefix' => 'disposiciones','middleware' => 'tiene_permiso:ver_seccion_disposiciones'], function () {
  Route::get('/','DisposicionController@buscarTodoDisposiciones');
  Route::post('buscar','DisposicionController@buscarDispocisiones');
});
Route::group(['prefix' => 'resoluciones','middleware' => 'tiene_permiso:ver_seccion_resoluciones'], function () {
  Route::get('/','ResolucionController@buscarTodoResoluciones');
  Route::post('buscar','ResolucionController@buscarResolucion');
});
//@TODO: hacer un permiso para la seccion notas
Route::group(['prefix' => 'notas','middleware' => 'tiene_permiso:ver_seccion_resoluciones'], function () {
  Route::get('/','NotaController@buscarTodoNotas');
  Route::post('buscar','NotaController@buscarNotas');
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

/***********
GliHards
***********/
Route::group(['prefix' => 'certificadoHard','middleware' =>'tiene_permiso:ver_seccion_glihard'], function () {
  Route::get('/','GliHardController@buscarTodo');
  Route::post('buscarGliHard','GliHardController@buscarGliHard');
  Route::get('pdf/{id}','GliHardController@leerArchivoGliHard');
  Route::get('obtenerExpediente/{id}','ExpedienteController@obtenerExpediente');
  Route::get('buscarExpedientePorNumero/{busqueda}','ExpedienteController@buscarExpedientePorNumero');
  Route::get('obtenerGliHard/{id}','GliHardController@obtenerGliHard');
  Route::post('guardarGliHard','GliHardController@guardarGliHard');
  Route::post('modificarGliHard','GliHardController@modificarGliHard');
  Route::delete('eliminarGliHard/{id}','GliHardController@eliminarGliHard');
});

/**********
Formulas
***********/
Route::group(['prefix' => 'formulas','middleware' =>'tiene_permiso:ver_seccion_formulas'], function () {
  Route::get('/','FormulaController@buscarTodo');
  Route::get('buscarFormulas','FormulaController@buscarFormula');
  Route::post('guardarFormula','FormulaController@guardarFormula');
  Route::get('obtenerFormula/{id}','FormulaController@obtenerFormula');
  Route::post('modificarFormula','FormulaController@modificarFormula');
  Route::post('asociarMaquinas','FormulaController@asociarMaquinas');
  Route::delete('eliminarFormula/{id}','FormulaController@eliminarFormula');
  Route::get('buscarIslaPorCasinoYNro/{id_casino}/{nro_isla}','IslaController@buscarIslaPorCasinoYNro');
  Route::get('obtenerIsla/{id_isla}','IslaController@obtenerIsla');
  Route::get('obtenerConfiguracionMaquina/{id}', 'MTMController@obtenerConfiguracionMaquina');
  Route::get('buscarMaquinaPorNumeroMarcaYModelo/{casino?}/{busqueda}','MTMController@buscarMaquinaPorNumeroMarcaYModelo');
});

/***********
Seccion MTM
************/
Route::group(['prefix' => 'maquinas','middleware' => 'tiene_permiso:ver_seccion_maquinas'], function () {
  Route::get('/','MTMController@buscarTodo');
  Route::post('guardarMaquina', 'MTMController@guardarMaquina');
  Route::post('modificarMaquina', 'MTMController@modificarMaquina');
  Route::post('buscarMaquinas', 'MTMController@buscarMaquinas');
  Route::post('cargaMasiva', 'LectorCSVController@cargaMasivaMaquinas');
  Route::get('obtenerExpediente/{id}','ExpedienteController@obtenerExpediente');
  Route::get('buscarExpedientePorCasinoYNumero/{id_casino}/{busqueda}','ExpedienteController@buscarExpedientePorCasinoYNumero');
  Route::group(['prefix' => 'certificadoHard'],function(){
    Route::get('pdf/{id}','GliHardController@leerArchivoGliHard');
    Route::get('obtenerGliHard/{id}','GliHardController@obtenerGliHard');
    Route::get('buscarGliHardsPorNroArchivo/{nro_archivo}','GliHardController@buscarGliHardsPorNroArchivo');
  });
  Route::get('buscarFormulaPorCampos/{input}','FormulaController@buscarPorCampos');
  Route::get('buscarMarcas/{marca}', 'MTMController@buscarMarcas');
  Route::get('buscarMaquinaPorNumeroMarcaYModelo/{casino?}/{busqueda}','MTMController@buscarMaquinaPorNumeroMarcaYModelo');
  Route::get('obtenerMTM/{id}', 'MTMController@obtenerMTM');
  Route::get('obtenerSectoresPorCasino/{id_casino}','SectorController@obtenerSectoresPorCasino');
  Route::get('{id}','MTMController@buscarTodo');
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
  Route::get('buscarMaquinaPorNumeroMarcaYModelo/{casino?}/{busqueda}','MTMController@buscarMaquinaPorNumeroMarcaYModelo');
  Route::get('obtenerMTM/{id}', 'MTMController@obtenerMTM');
  Route::get('obtenerSectoresPorCasino/{id_casino}','SectorController@obtenerSectoresPorCasino');
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
  Route::get('adjunto/{id_toma}/{id_archivo}','LogMovimientoController@leerAdjuntoDeToma');
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
  Route::get('buscarUsuariosPorNombreYCasino/{id_casino}/{nombre}','UsuarioController@buscarUsuariosPorNombreYCasino');
  Route::get('buscarFormulaPorCampos/{input}','FormulaController@buscarPorCampos');
  Route::get('buscarMarcas/{marca}', 'MTMController@buscarMarcas');
  Route::get('obtenerSectoresPorCasino/{id_casino}','SectorController@obtenerSectoresPorCasino');
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
  Route::get('buscarUsuariosPorNombreYCasino/{id_casino}/{nombre}','UsuarioController@buscarUsuariosPorNombreYCasino');
  Route::get('{id}','LogMovimientoController@relevamientosMovimientos');
});

/**********
Eventualidades ->intervenciones tecnicas
***********/
Route::group(['prefix' => 'eventualidades','middleware' => 'tiene_permiso:ver_seccion_eventualidades'], function () {
  Route::get('/','EventualidadController@buscarTodoDesdeFiscalizador');
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
  Route::get('buscarUsuariosPorNombreYCasino/{id_casino}/{nombre}','UsuarioController@buscarUsuariosPorNombreYCasino');
  Route::get('obtenerMTMEnCasino/{casino}/{id}', 'MTMController@obtenerMTMEnCasino');
  Route::get('obtenerMTM/{id}', 'MTMController@obtenerMTM');
  Route::get('obtenerSector/{id_sector}','SectorController@obtenerSector');
  Route::post('guardarEventualidad','EventualidadController@guardarEventualidad');
  Route::get('pdf/{id}', 'EventualidadController@PDF');
  Route::get('obtenerTurnos/{id_casino}', 'EventualidadController@obtenerTurnos');
  Route::get('ultimas', 'EventualidadController@ultimasIntervenciones');
  Route::post('subirEventualidad', 'EventualidadController@subirEventualidad');
  Route::post('guardarObservacion','EventualidadController@guardarObservacion');
  Route::get('pdfObs/{id}','EventualidadController@PDFObs');
  Route::get('visarEventualidad/{id_eventualidad}','EventualidadController@visarEventualidad');
  Route::post('subirObservacion', 'EventualidadController@subirObservacion');
  Route::get('{evId}/observaciones', 'EventualidadController@getObservaciones');
  Route::get('observacion/{id_ob}','EventualidadController@eliminarObservacion');
  Route::get('visualizarArchivo/{estado}/{id_archivo}','EventualidadController@visualizarArchivo');
  Route::group(['middleware' => 'tiene_rol:superusuario'], function () {
    Route::get('/ponerNombresProcedimientos','EventualidadController@ponerNombresProcedimientos');
  });
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
  Route::get('obtenerMTMEnCasinoHabilitadas/{casino}/{id}', 'MTMController@obtenerMTMEnCasinoHabilitadas');
  Route::get('obtenerMTMEnCasinoEgresadas/{casino}/{id}', 'MTMController@obtenerMTMEnCasinoEgresadas');
  Route::get('adjunto/{id_toma}/{id_archivo}','LogMovimientoController@leerAdjuntoDeToma');
  Route::get('obtenerMTM/{id}', 'MTMController@obtenerMTM');
  Route::get('buscarUsuariosPorNombreYCasino/{id_casino}/{nombre}','UsuarioController@buscarUsuariosPorNombreYCasino');
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
Route::group(['prefix' => 'sectores','middleware' => 'tiene_permiso:ver_seccion_sectores'], function () {
  Route::get('/','SectorController@buscarTodo');
  Route::get('obtenerSector/{id_sector}','SectorController@obtenerSector');
  Route::delete('eliminarSector/{id_sector}','SectorController@eliminarSector');
  Route::post('guardarSector','SectorController@guardarSector');
  Route::post('modificarSector','SectorController@modificarSector');
  Route::get('buscarIslaPorCasinoYNro/{id_casino}/{nro_isla}','IslaController@buscarIslaPorCasinoYNro');
  Route::get('obtenerIsla/{id_isla}','IslaController@obtenerIsla');
});
/**********
Contadores
***********/

Route::group(['prefix' => 'importaciones','middleware' => 'tiene_permiso:ver_seccion_importaciones'], function () {
  Route::get('/','ImportacionController@buscarTodo');
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
  Route::delete('eliminarContador/{id}','ContadorController@eliminarContador');
  Route::delete('eliminarProducido/{id}','ProducidoController@eliminarProducido');
  Route::delete('eliminarBeneficios/{id_casino}/{id_tipo_moneda}/{anio}/{mes}','BeneficioController@eliminarBeneficios');
});

Route::group(['prefix' => 'cotizacion','middleware' => 'tiene_permiso:cotizar_dolar_peso'], function () {
  Route::get('obtenerCotizaciones/{mes}','CotizacionController@obtenerCotizaciones');
  Route::post('guardarCotizacion','CotizacionController@guardarCotizacion');
  Route::get('dolarOficial','CotizacionController@dolarOficial');
});
/************
Relevamientos
************/
Route::group(['prefix' => 'relevamientos','middleware' => 'tiene_permiso:ver_seccion_relevamientos'], function () {
  Route::get('/','RelevamientoController@buscarTodo');
  Route::post('crearRelevamiento','RelevamientoController@crearRelevamiento');
  Route::post('descargarRelevamiento','RelevamientoController@descargarRelevamiento');
  Route::post('cargarRelevamiento','RelevamientoController@cargarRelevamiento');
  Route::post('validarRelevamiento','RelevamientoController@validarRelevamiento');
  Route::get('obtenerRelevamiento/{id_relevamiento}','RelevamientoController@obtenerRelevamiento');
  Route::get('generarPlanilla/{id_relevamiento}','RelevamientoController@generarPlanilla');
  Route::get('generarPlanillaValidado/{id_relevamiento}','RelevamientoController@generarPlanillaValidado');
  Route::get('existeRelevamiento/{id_sector}','RelevamientoController@existeRelevamiento');
  Route::post('usarRelevamientoBackUp','RelevamientoController@usarRelevamientoBackUp');
  Route::get('descargarZip/{nombre}','RelevamientoController@descargarZip');
  Route::get('obtenerCantidadMaquinasPorRelevamiento/{id_sector}','RelevamientoController@obtenerCantidadMaquinasPorRelevamiento');
  Route::post('crearCantidadMaquinasPorRelevamiento','RelevamientoController@crearCantidadMaquinasPorRelevamiento');
  Route::get('obtenerCantidadMaquinasRelevamientoHoy/{id_sector}','RelevamientoController@obtenerCantidadMaquinasRelevamiento');
  Route::post('eliminarCantidadMaquinasPorRelevamiento','RelevamientoController@eliminarCantidadMaquinasPorRelevamiento');
  Route::post('modificarDenominacionYUnidadDetalle','RelevamientoController@modificarDenominacionYUnidadDetalle');
  Route::post('modificarDenominacionYUnidadMTM','RelevamientoController@modificarDenominacionYUnidadMTM');
  Route::post('buscarRelevamientos','RelevamientoController@buscarRelevamientos');
  Route::get('verRelevamientoVisado/{id_relevamiento}','RelevamientoController@obtenerRelevamientoVisado');
  Route::get('chequearRolFiscalizador','UsuarioController@chequearRolFiscalizador');
  Route::get('buscarUsuariosPorNombreYCasino/{id_casino}/{nombre}','UsuarioController@buscarUsuariosPorNombreYCasino');
  Route::get('usuarioTienePermisos','AuthenticationController@usuarioTienePermisos');
  Route::get('obtenerSectoresPorCasino/{id_casino}','SectorController@obtenerSectoresPorCasino');
  Route::get('obtenerMtmAPedido/{fecha}/{id_sector}','MaquinaAPedidoController@obtenerMtmAPedido');
  Route::get('estadisticas_no_toma/{id}','informesController@mostrarEstadisticasNoToma');
  Route::post('calcularEstadoDetalleRelevamiento','RelevamientoController@calcularEstadoDetalleRelevamiento');
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
  Route::get('obtenerSectoresPorCasino/{id_casino}','SectorController@obtenerSectoresPorCasino');
  Route::group(['middleware' => 'tiene_permiso:ver_seccion_progresivos'],function(){
    Route::get('obtenerMinimoRelevamientoProgresivo/{id_casino}/{id_tipo_moneda}','RelevamientoProgresivoController@obtenerMinimoRelevamientoProgresivo');
    Route::post('modificarParametrosRelevamientosProgresivo','RelevamientoProgresivoController@modificarParametrosRelevamientosProgresivo');
  });
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
  Route::get('obtenerGeneralidades','RelevamientoAmbientalController@obtenerGeneralidades');
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
Route::group(['prefix' => 'mtm_a_pedido','middleware' => 'tiene_permiso:ver_seccion_mtm_a_pedido'], function () {
  Route::get('/','MaquinaAPedidoController@buscarTodo');
  Route::post('buscarMTMaPedido','MaquinaAPedidoController@buscarMTMaPedido');
  Route::post('guardarMtmAPedido','MaquinaAPedidoController@guardarMtmAPedido');
  Route::delete('eliminarMmtAPedido/{id}','MaquinaAPedidoController@eliminarMTMAPedido');
  Route::get('obtenerSectoresPorCasino/{id_casino}','SectorController@obtenerSectoresPorCasino');
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
  Route::get('','ProducidoController@buscarTodo');
  Route::post('buscarProducidos','ProducidoController@buscarProducidos');
  Route::get('generarPlanillaDiferencias/{id_producido}','ProducidoController@generarPlanillaDiferencias');
  Route::get('generarPlanillaProducido/{id_producido}','ProducidoController@generarPlanillaProducido');
  Route::post('guardarAjuste','ProducidoController@guardarAjuste');
  Route::get('datosAjusteMTM/{id_maquina}/{id_producidos}','ProducidoController@datosAjusteMTM');
  Route::get('ajustarProducido/{id_producido}','ProducidoController@ajustarProducido');
  Route::post('calcularDiferencia','ProducidoController@calcularDiferenciaHandlePOST');
});

/***********
 Estadisticas
************/
Route::group(['prefix' => 'estadisticas_relevamientos','middleware' => 'tiene_permiso:ver_seccion_estadisticas_relevamientos'],function (){
  Route::get('/','MaquinaAPedidoController@buscarTodoInforme');
  Route::post('guardarMtmAPedido','MaquinaAPedidoController@guardarMtmAPedido');
  Route::post('obtenerUltimosRelevamientosPorMaquina','RelevamientoController@obtenerUltimosRelevamientosPorMaquinaNroAdmin');
  Route::post('buscarMaquinasSinRelevamientos','RelevamientoController@buscarMaquinasSinRelevamientos');
  Route::get('obtenerFechasMtmAPedido', 'MaquinaAPedidoController@obtenerFechasMtmAPedido');
  Route::get('buscarMaquinas/{id_casino}','RelevamientoController@buscarMaquinasPorCasino');
  Route::get('obtenerSectoresPorCasino/{id_casino}','SectorController@obtenerSectoresPorCasino');
});
/**********
 Beneficios
***********/
Route::group(['prefix' => 'beneficios','middleware' => 'tiene_permiso:ver_seccion_beneficios'],function (){
  Route::get('/','BeneficioController@buscarTodo');
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

Route::group(['prefix' => 'layout_parcial','middleware' => 'tiene_permiso:ver_seccion_layout_parcial'],function (){
  Route::get('/','LayoutController@buscarTodo');
  Route::post('crearLayoutParcial','LayoutController@crearLayoutParcial');
  Route::post('usarLayoutBackup' , 'LayoutController@usarLayoutBackup');
  Route::get('existeLayoutParcial/{id_sector}','LayoutController@existeLayoutParcial');
  Route::get('existeLayoutParcialGenerado/{id_sector}','LayoutController@existeLayoutParcialGenerado');

  Route::get('obtenerLayoutParcial/{id}','LayoutController@obtenerLayoutParcial');
  Route::get('obtenerLayoutParcialValidar/{id}','LayoutController@obtenerLayoutParcial');

  Route::post('guardarLayoutParcial' , 'LayoutController@guardarLayoutParcial');
  Route::post('finalizarLayoutParcial','LayoutController@finalizarLayoutParcial');
  Route::post('validarLayoutParcial' , 'LayoutController@validarLayoutParcial');

  Route::get('generarPlanillaLayoutParcial/{id}','LayoutController@generarPlanillaLayoutParcial');
  Route::get('generarPlanillaLayoutParcialCargado/{id}','LayoutController@generarPlanillaLayoutParcialCargado');
  Route::get('descargarLayoutParcialZip/{nombre}','LayoutController@descargarLayoutParcialZip');
  Route::post('buscarLayoutsParciales' , 'LayoutController@buscarLayoutsParciales');
  Route::get('buscarUsuariosPorNombreYCasino/{id_casino}/{nombre}','UsuarioController@buscarUsuariosPorNombreYCasino');
  Route::get('usuarioTienePermisos','AuthenticationController@usuarioTienePermisos');
  Route::get('obtenerSectoresPorCasino/{id_casino}','SectorController@obtenerSectoresPorCasino');
});

Route::group(['prefix' => 'layout_total','middleware' => 'tiene_permiso:ver_seccion_layout_total'], function () {
  Route::get('/','LayoutController@buscarTodoTotal');
  Route::post('crearLayoutTotal','LayoutController@crearLayoutTotal');
  Route::post('buscarLayoutsTotales' , 'LayoutController@buscarLayoutsTotales');
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
  Route::get('buscarUsuariosPorNombreYCasino/{id_casino}/{nombre}','UsuarioController@buscarUsuariosPorNombreYCasino');
  Route::get('obtenerSectoresPorCasino/{id_casino}','SectorController@obtenerSectoresPorCasino');
});

/**************
 Estadisticas
**************/
/******TODA LA SECCCION TABLERO DE CONTROL *******/
Route::get('menu_tablero',function(){
  return view('menu_tablero');
});
Route::group(['prefix' => 'estadisticasGenerales','middleware' => 'tiene_permiso:estadisticas_generales'], function () {
  Route::get('/', 'BeneficioMensualController@buscarTodoGenerales');
  Route::post('/','BeneficioMensualController@cargarEstadisticasGenerales');
});
Route::group(['prefix' => 'estadisticasPorCasino','middleware' => 'tiene_permiso:estadisticas_por_casino'], function () {
  Route::get('/','BeneficioMensualController@buscarTodoPorCasino');
  Route::post('/','BeneficioMensualController@cargarSeccionEstadisticasPorCasino');
});
Route::group(['prefix' => 'interanuales','middleware' => 'tiene_permiso:estadisticas_interanuales'], function () {
  Route::get('/','BeneficioMensualController@buscarTodoInteranuales');
  Route::post('/','BeneficioMensualController@cargaSeccionInteranual');
});
/***********
Informes
***********/
Route::group(['prefix' => 'informeEstadoParque','middleware' => 'tiene_permiso:ver_seccion_estestadoparque'],function(){
  Route::get('/' , 'informesController@obtenerInformeEstadoParque');
  Route::get('obtenerSector/{id_sector}','SectorController@obtenerSector');
  Route::get('obtenerEstadoParqueDeCasino','informesController@obtenerInformeEstadoParqueDeParque');
});

Route::group(['prefix' => 'informeContableMTM','middleware' => 'tiene_permiso:ver_seccion_informecontable'], function () {
  Route::get('/','informesController@buscarTodoInformeContable');//carga pagina
  Route::get('obtenerMTMEnCasino/{casino}/{id}', 'MTMController@obtenerMTMEnCasino');
  Route::get('obtenerInformeContableDeMaquina/{id_maquina}','informesController@obtenerInformeContableDeMaquina');
});

Route::group(['prefix' => 'informesMTM','middleware' => 'tiene_permiso:informes_mtm'], function () {
  Route::get('/','informesController@obtenerUltimosBeneficiosPorCasino');
  Route::get('generarPlanilla','informesController@generarPlanilla');
  Route::get('generarPlanillaIslasMaquinas','informesController@generarPlanillaIslasMaquinas');
});

Route::group(['prefix' => 'informesMesas','middleware' => 'tiene_permiso:informes_mesas'], function () {
  Route::get('/','Mesas\InformesMesas\BCMensualesController@obtenerInformeMesas');
  Route::get('generarPlanilla','Mesas\InformesMesas\BCMensualesController@generarPlanillaContable');
});

Route::group(['prefix' => 'informeSector','middleware' => 'tiene_permiso:ver_seccion_informesector'], function () {
  Route::get('/','informesController@mostrarInformeSector');
  Route::get('obtenerMTMs','informesController@obtenerMTMs');
  Route::post('transaccionEstadoMasivo','MTMController@transaccionEstadoMasivo');
});

Route::group(['prefix' => 'estadisticas_no_toma','middleware' => 'tiene_permiso:ver_seccion_informecontable'], function () {
  Route::get('/','informesController@mostrarEstadisticasNoTomaGenerico');
  Route::get('obtenerEstadisticasNoToma/{id}','informesController@obtenerEstadisticasNoToma');
  Route::get('obtenerMTMEnCasino/{casino}/{id}', 'MTMController@obtenerMTMEnCasino');
  Route::post('obtenerUltimosRelevamientosPorMaquinaNroAdmin','RelevamientoController@obtenerUltimosRelevamientosPorMaquinaNroAdmin');
});

/************************
Prueba Juegos y Progresivos
************************/
Route::group(['prefix' => 'prueba_juegos','middleware' => 'tiene_permiso:ver_seccion_prueba_juegos'],function(){
  Route::get('/','PruebaController@buscarTodo');
  Route::get('pdf/{id_prueba_juego}','PruebaController@obtenerPDF');
  Route::get('obtenerPruebaJuego/{id_prueba_juego}','PruebaController@obtenerPruebaJuego');
  Route::post('guardarPruebaJuego','PruebaController@guardarPruebaJuego');
  Route::get('obtenerSectoresPorCasino/{id_casino}','SectorController@obtenerSectoresPorCasino');
  Route::post('buscarPruebasDeJuego','PruebaController@buscarPruebasDeJuego');
  Route::get('generarPlanillaPruebaDeJuego/{id_prueba_juego}','PruebaController@generarPlanillaPruebaDeJuego');
  Route::post('sortearMaquinaPruebaDeJuego','PruebaController@sortearMaquinaPruebaDeJuego');
});

Route::group(['prefix' => 'prueba_progresivos','middleware' => 'tiene_permiso:ver_seccion_prueba_progresivos'],function(){
  Route::get('/','PruebaController@buscarTodoPruebaProgresivo');
  Route::post('buscarPruebasProgresivo','PruebaController@buscarPruebasProgresivo');
  Route::post('sortearMaquinaPruebaDeProgresivo','PruebaController@sortearMaquinaPruebaDeProgresivo');
  Route::get('generarPlanillaPruebaDeProgresivos/{id_prueba_progresivo}','PruebaController@generarPlanillaPruebaDeProgresivos');
  Route::get('obtenerSectoresPorCasino/{id_casino}','SectorController@obtenerSectoresPorCasino');
});

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
//gestion mesas
Route::group(['prefix' => 'mesas','middleware' => 'tiene_permiso:m_gestionar_mesas'], function () {
  Route::get('/','Mesas\Mesas\BuscarMesasController@getMesas');
  Route::post('buscarMesas','Mesas\Mesas\BuscarMesasController@buscarMesas');
  Route::post('nuevaMesa/{id_casino}','Mesas\Mesas\ABMMesaController@guardar');
  Route::post('modificarMesa/{id_casino}','Mesas\Mesas\ABMMesaController@modificar');
  Route::get('eliminarMesa/{id_casino}/{id_mesa_de_panio}','Mesas\Mesas\ABMMesaController@eliminar');
  Route::get('cargarDatos','Mesas\Mesas\BuscarMesasController@getDatos');
  Route::get('detalleMesa/{id_mesa}','Mesas\Mesas\BuscarMesasController@getMesa');
  Route::get('obtenerDatos/{id_cas}', 'Mesas\Mesas\BuscarMesasController@datosSegunCasino');
});

Route::group(['prefix' => 'cierres','middleware' => 'tiene_permiso:m_buscar_aperturas'], function () {
  Route::get('detalleMesa/{id_mesa}','Mesas\Mesas\BuscarMesasController@getMesa');
  Route::post('filtrosCierres','Mesas\Cierres\BCCierreController@filtros');
  Route::post('guardar', 'Mesas\Cierres\ABMCierreController@guardar');
  Route::post('modificarCierre','Mesas\Cierres\ABMCierreController@modificarCierre');
  Route::get('getCierre/{id_cierre_mesa}', 'Mesas\Cierres\ABMCCierreAperturaController@getCierre');
  Route::get('bajaCierre/{id_cierre}', 'Mesas\Cierres\BCCierreController@eliminarCierre')->middleware(['tiene_permiso:m_eliminar_cierres_y_aperturas']);
  Route::get('buscarFiscalizadores/{id_cas}/{nombre}', 'UsuarioController@buscarFiscaNombreCasino');
  Route::get('obtenerMesas/{id_cas}/{nro_mesa}', 'Mesas\Mesas\BuscarMesasController@buscarMesaPorNroCasino');
  Route::get('obtenerJuegoPorCasino/{id_cas}/{nombreJuego}', 'Mesas\Juegos\BuscarJuegoController@buscarJuegoPorCasinoYNombre');
  Route::post('validar','Mesas\Cierres\VCierrecontroller@validar')->middleware(['tiene_permiso:m_validar_cierres']);
});

Route::group(['prefix' => 'aperturas','middleware' => 'tiene_permiso:m_buscar_aperturas'], function () {
  Route::get('/', 'Mesas\Aperturas\BCAperturaController@buscarTodo');
  Route::get('detalleMesa/{id_mesa}','Mesas\Mesas\BuscarMesasController@getMesa');
  Route::get('obtenerMesas/{id_cas}/{nro_mesa}', 'Mesas\Mesas\BuscarMesasController@buscarMesaPorNroCasino');
  Route::get('quienSoy' ,'UsuarioController@quienSoy');
  Route::get('obtenerMesasPorJuego/{id_juego_mesa}/{nro_mesa}', 'Mesas\Mesas\BuscarMesasController@buscarMesaPorJuego');
  Route::post('agregarAperturaAPedido','Mesas\Aperturas\ABMAperturaController@agregarAperturaAPedido');
  Route::get('buscarAperturasAPedido','Mesas\Aperturas\ABMAperturaController@buscarAperturasAPedido');
  Route::delete('borrarAperturaAPedido/{id_apertura_a_pedido}','Mesas\Aperturas\ABMAperturaController@borrarAperturaAPedido');
  Route::post('filtrosAperturas', 'Mesas\Aperturas\BCAperturaController@filtros');
  Route::get('getApertura/{id_apertura_mesa}', 'Mesas\Cierres\ABMCCierreAperturaController@getApertura');
  Route::post('guardar', 'Mesas\Aperturas\ABMAperturaController@guardar');
  Route::post('modificarApertura','Mesas\Aperturas\ABMAperturaController@modificarApertura');
  Route::get('bajaApertura/{id_apertura}', 'Mesas\Aperturas\BCAperturaController@eliminarApertura')->middleware(['tiene_permiso:m_eliminar_cierres_y_aperturas']);
  Route::post('validarApertura','Mesas\Aperturas\VAperturaController@validarApertura');
  Route::get('obtenerApValidar/{id_apertura}', 'Mesas\Aperturas\BCAperturaController@obtenerApParaValidar');
  Route::get('desvincularApertura/{id_apertura}', 'Mesas\Cierres\ABMCCierreAperturaController@desvincularApertura')->middleware(['tiene_permiso:m_desvincular_aperturas']);
  Route::get('buscarFiscalizadores/{id_cas}/{nombre}', 'UsuarioController@buscarFiscaNombreCasino');
  Route::get('compararCierre/{id_apertura}/{id_cierre}','Mesas\Aperturas\BCAperturaController@obtenerDetallesApCierre');
  Route::get('obtenerAperturasSorteadas','Mesas\Aperturas\ABMCRelevamientosAperturaController@obtenerAperturasSorteadas');
  Route::get('usarBackup','Mesas\Aperturas\ABMCRelevamientosAperturaController@usarBackup');
  Route::get('sortearMesasSiNoHay/{id_casino}', 'Mesas\Aperturas\ABMCRelevamientosAperturaController@sortearMesasSiNoHay');
  Route::get('generarRelevamiento', 'Mesas\Aperturas\ABMCRelevamientosAperturaController@generarRelevamiento');
  Route::get('descargarZip/{nombre}', 'Mesas\Aperturas\ABMCRelevamientosAperturaController@descargarZip');
});

//Sección Juegos
Route::group(['prefix' => 'mesas-juegos','middleware' => 'tiene_permiso:m_gestionar_juegos_mesas'], function () {
  Route::get('/', 'Mesas\Juegos\BuscarJuegoController@buscarTodo');
  Route::post('buscarJuegos', 'Mesas\Juegos\BuscarJuegoController@buscarJuegos');
  Route::post('nuevoJuego', 'Mesas\Juegos\ABMJuegoController@guardar');
  Route::post('modificarJuego', 'Mesas\Juegos\ABMJuegoController@modificarJuego');
  Route::get('obtenerJuego/{id_juego}', 'Mesas\Juegos\ABMJuegoController@obtenerJuego');
  Route::get('bajaJuego/{id}', 'Mesas\Juegos\ABMJuegoController@eliminarJuego');
});
//Mismo permiso porque es una pestaña de la misma vista
Route::group(['prefix' => 'sectores-mesas','middleware' => 'tiene_permiso:m_gestionar_juegos_mesas'], function () {
  Route::get('eliminarSector/{id_sector}','Mesas\Sectores\ABMCSectoresController@eliminarSector');
  Route::post('buscarSectores','Mesas\Sectores\ABMCSectoresController@filtrarSectores');
  Route::post('guardar','Mesas\Sectores\ABMCSectoresController@guardar');
});

Route::group(['prefix' => 'apuestas','middleware' => 'tiene_permiso:m_ver_seccion_apuestas'], function () {
  Route::get('/', 'Mesas\Apuestas\BCApuestasController@buscarTodo');
  Route::post('buscarRelevamientosApuestas', 'Mesas\Apuestas\BCApuestasController@filtros');
  Route::post('generarRelevamientoApuestas', 'Mesas\Apuestas\BCApuestasController@generarYobtenerNombreZip');
  Route::get('descargarZipApuestas/{nombre}', 'Mesas\Apuestas\BCApuestasController@descargarZip');
  Route::get('obtenerRelevamiento/{id_relevamiento}', 'Mesas\Apuestas\BCApuestasController@obtenerRelevamiento');
  Route::post('regenerarBackup','Mesas\Apuestas\ABMApuestasController@regenerarBackup');
  Route::post('cargarRelevamiento','Mesas\Apuestas\ABMApuestasController@cargarRelevamiento');
  Route::get('baja/{id_relevamiento}', 'Mesas\Apuestas\BVApuestasController@eliminar');
  Route::post('validar', 'Mesas\Apuestas\BVApuestasController@validar');
  Route::get('imprimir/{id}','Mesas\Apuestas\BCApuestasController@imprimirPlanilla');
  Route::get('imprimirPlanilla/{id}','Mesas\Apuestas\BCApuestasController@imprimirPlanillaVacia');
  Route::get('consultarMinimo','Mesas\Apuestas\BCApuestasController@consultarMinimo');
  Route::get('obtenerRequerimientos/{id_cas}/{id_moneda}','Mesas\Apuestas\ABMCApuestaMinimaController@obtenerApuestaMinima');
  Route::post('modificarRequerimiento','Mesas\Apuestas\ABMCApuestaMinimaController@modificar');
  Route::get('obtenerRequerimientos/{id_cas}/{id_moneda}','Mesas\Apuestas\ABMCApuestaMinimaController@obtenerApuestaMinima');
  Route::post('modificarRequerimiento','Mesas\Apuestas\ABMCApuestaMinimaController@modificar');
  Route::get('buscarUsuario/{id_usuario}','UsuarioController@buscarUsuario');
  Route::get('buscarFiscalizadores/{id_cas}/{nombre}', 'UsuarioController@buscarFiscaNombreCasino');
  Route::get('buscarTurnos/{nro}','Mesas\Turnos\TurnosController@buscarTurnos');
});

//informes fiscalizadores (compara Cierres contra Aperturas y verifica Apuestas Minimas)
Route::group(['prefix' => 'informeDiarioBasico','middleware' => 'tiene_permiso:m_ver_seccion_informe_fiscalizadores'], function () {
  Route::get('/','Mesas\InformeFiscalizadores\BCInformesController@index');
  Route::post('buscar', 'Mesas\InformeFiscalizadores\BCInformesController@filtros');
  Route::post('buscarInformes','Mesas\InformeFiscalizadores\BCInformesController@filtros');
  Route::get('imprimir/{id_informe_fiscalizacion}','Mesas\InformeFiscalizadores\BCInformesController@imprimirPlanilla');
});

Route::group(['prefix' => 'importacionDiaria','middleware' => 'tiene_permiso:m_ver_seccion_importaciones'],function (){
  Route::get('/','Mesas\Importaciones\ImportadorController@buscarTodo');
  Route::post('importar','Mesas\Importaciones\ImportadorController@importarDiario')->middleware(['tiene_permiso:m_importar']);
  Route::post('importarCierres','Mesas\Importaciones\ImportadorController@importarCierres')->middleware(['tiene_permiso:m_importar']);
  Route::post('filtros','Mesas\Importaciones\ImportadorController@filtros');
  Route::get('verImportacion/{id_imp}/{t_mesa?}','Mesas\Importaciones\ImportadorController@buscarPorTipoMesa');
  Route::get('imprimir/{id}','Mesas\Importaciones\ImportadorController@imprimirDiario');
  Route::post('guardar','Mesas\Importaciones\ImportadorController@guardarImportacionDiaria');
  Route::get('eliminarImportacion/{id_imp}','Mesas\Importaciones\ImportadorController@eliminar');
  Route::get('eliminarImportacionCierres/{id_imp}','Mesas\Importaciones\ImportadorController@eliminarCierres');
  Route::post('ajustarDetalle','Mesas\Importaciones\ImportadorController@ajustarDetalle');
  Route::get('imprimirMensual','Mesas\InformesMesas\BCMensualesController@imprimirMensual');
  Route::get('superuserActualizarTodosLosCierres','Mesas\Importaciones\ImportadorController@superuserActualizarTodosLosCierres');
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
    Route::get('chequearRolFiscalizador','UsuarioController@chequearRolFiscalizador');
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
  Route::get('/noticias','Autoexclusion\AutoexclusionController@indexNoticias');
  Route::delete('eliminarAE/{id_autoexcluido}','Autoexclusion\AutoexclusionController@eliminarAE')->middleware('tiene_permiso:borrar_ae');
  Route::post('agregarAE','Autoexclusion\AutoexclusionController@agregarAE');
  Route::post('subirArchivo','Autoexclusion\AutoexclusionController@subirArchivo');
  Route::get('cambiarEstadoAE/{id}/{id_estado}','Autoexclusion\AutoexclusionController@cambiarEstadoAE');
  Route::get('existeAutoexcluido/{dni}','Autoexclusion\AutoexclusionController@existeAutoexcluido');
  Route::post('buscarAutoexcluidos','Autoexclusion\AutoexclusionController@buscarAutoexcluidos');
  Route::get('buscarAutoexcluido/{id}','Autoexclusion\AutoexclusionController@buscarAutoexcluido');
  Route::get('mostrarArchivo/{id_importacion}/{tipo_archivo}','Autoexclusion\AutoexclusionController@mostrarArchivo');
  Route::get('mostrarFormulario/{id_formulario}','Autoexclusion\AutoexclusionController@mostrarFormulario');
  Route::get('generarSolicitudAutoexclusion/{id}','Autoexclusion\AutoexclusionController@generarSolicitudAutoexclusion');
  Route::get('generarSolicitudFinalizacionAutoexclusion/{id}','Autoexclusion\AutoexclusionController@generarSolicitudFinalizacionAutoexclusion');
  Route::get('generarConstanciaReingreso/{id}','Autoexclusion\AutoexclusionController@generarConstanciaReingreso');
  Route::get('BDCSV','Autoexclusion\AutoexclusionController@BDCSV')->middleware('tiene_permiso:descargar_aes');
  Route::get('{dni?}','Autoexclusion\AutoexclusionController@index');
  Route::post('destruirPapel','Autoexclusion\AutoexclusionController@destruirPapel');
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

Route::group(['prefix' => 'backoffice','middleware' => 'tiene_permiso:informes_mtm'], function () {
  Route::get('/','BackOfficeController@index');
  Route::post('buscar','BackOfficeController@buscar');
  Route::post('descargar','BackOfficeController@descargar');
});

//Secion canon vieja que nunca fue usada, eliminar cuando este funcionando OK la nueva
/*
Route::group(['prefix' => 'canon','middleware' => 'tiene_permiso:m_ver_seccion_canon'],function(){
  Route::get('/','Mesas\Canon\BPagosController@index');
  Route::group(['middleware' => 'tiene_permiso:m_a_pagos'], function () {
    Route::post('crearOModificarPago','Mesas\Canon\APagosController@crearOModificar');
    Route::delete('borrarPago/{id_detalle}','Mesas\Canon\APagosController@borrar');
    Route::post('modificarInformeBase','Mesas\Canon\APagosController@modificarInformeBase');
  });
  Route::group(['middleware' => 'tiene_permiso:m_b_pagos'], function () {
    Route::get('getMesesCuotas/{id_casino}/{anio_inicio}', 'Mesas\Canon\BPagosController@mesesCuotasCanon');
    Route::get('mesesCargados/{id_casino}/{anio_inicio}','Mesas\Canon\BPagosController@mesesCargados');
    Route::post('buscarPagos','Mesas\Canon\BPagosController@filtros');
    Route::get('obtenerPago/{id_detalle}','Mesas\Canon\BPagosController@obtenerPago');
    Route::get('obtenerAnios/{id_casino}','Mesas\Canon\BPagosController@obtenerAnios');
    Route::get('obtenerInformeBase/{id_casino}','Mesas\Canon\BPagosController@obtenerInformeBase');
    Route::post('verInforme','Mesas\Canon\BPagosController@verInformeFinalMesas');
  });
});*/

//Reuso los permisos de la sección vieja
Route::group(['prefix' => 'canon','middleware' => 'tiene_permiso:m_ver_seccion_canon'],function(){
  Route::get('/','\App\Http\Controllers\CanonController@index');
  Route::post('/buscar','\App\Http\Controllers\CanonController@buscar');
  Route::post('/descargar','\App\Http\Controllers\CanonController@descargar');
  Route::get('/descargarPlanillas','\App\Http\Controllers\CanonController@descargarPlanillas');
  Route::get('/obtener','\App\Http\Controllers\CanonController@obtener');
  Route::get('/planilla','\App\Http\Controllers\CanonController@planilla');
  Route::get('/planillaPDF','\App\Http\Controllers\CanonController@planillaPDF');
  Route::get('/planillaDevengado','\App\Http\Controllers\CanonController@planillaDevengado');
  Route::get('/planillaDeterminado','\App\Http\Controllers\CanonController@planillaDeterminado');
  Route::get('/planillaDeterminadoTest','\App\Http\Controllers\CanonController@planillaDeterminadoTest');
  Route::get('/totalesTest','\App\Http\Controllers\CanonController@totalesTest');
  Route::get('/archivo','\App\Http\Controllers\CanonController@archivo');
  Route::group(['middleware' => 'tiene_permiso:m_a_pagos'], function () {
    Route::get('/obtenerConHistorial','\App\Http\Controllers\CanonController@obtenerConHistorial');
    Route::post('/recalcular','\App\Http\Controllers\CanonController@recalcular_req');
    Route::post('/guardar','\App\Http\Controllers\CanonController@guardar');
    Route::post('/adjuntar','\App\Http\Controllers\CanonController@adjuntar');
    Route::get('/cambiarEstado','\App\Http\Controllers\CanonController@cambiarEstado');
    Route::delete('/borrar','\App\Http\Controllers\CanonController@borrar');
    Route::group(['middleware' => 'tiene_rol:superusuario'], function () {
      Route::get('/desborrar','\App\Http\Controllers\CanonController@desborrar');
      Route::post('/valoresPorDefecto','\App\Http\Controllers\CanonController@valoresPorDefecto');
      Route::post('/valoresPorDefecto/ingresar','\App\Http\Controllers\CanonController@valoresPorDefecto_ingresar');
      Route::delete('/valoresPorDefecto/borrar','\App\Http\Controllers\CanonController@valoresPorDefecto_borrar');
      Route::get('/recalcularSaldos','\App\Http\Controllers\CanonController@recalcular_saldos_Req');
    });
  });
});

Route::group(['prefix' => 'informesGenerales'],function(){//@TODO: agregar permiso
  Route::get('/beneficios','InformesGeneralesController@beneficios');
  Route::get('/autoexcluidos','InformesGeneralesController@autoexcluidos');
  Route::get('/producidos','InformesGeneralesController@producidos');
  Route::get('/producidos_semana','InformesGeneralesController@producidos_semana');
});
