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

Route::group(['prefix' => 'progresivos','middleware' => 'tiene_permiso:ver_seccion_progresivos'], function () {
  Route::get('/','ProgresivoController@buscarTodos');
  Route::post('/buscarProgresivos','ProgresivoController@buscarProgresivos');
  Route::get('/buscarMaquinas/{id_casino}/{nro_admin?}','ProgresivoController@buscarMaquinas');
  Route::get('/obtenerProgresivo/{id_progresivo}','ProgresivoController@obtenerProgresivo');
  Route::get('/obtenerMinimoRelevamientoProgresivo/{id_casino}','RelevamientoProgresivoController@obtenerMinimoRelevamientoProgresivo');
  Route::post('/crearProgresivo','ProgresivoController@crearProgresivo');
  Route::post('/modificarProgresivo/{id_progresivo}','ProgresivoController@modificarProgresivo');
  Route::delete('/eliminarProgresivo/{id_progresivo}','ProgresivoController@eliminarProgresivo');
  Route::post('/crearProgresivosIndividuales','ProgresivoController@crearProgresivosIndividuales');
  Route::post('/buscarProgresivosIndividuales','ProgresivoController@buscarProgresivosIndividuales');
  Route::post('/modificarProgresivosIndividuales','ProgresivoController@modificarProgresivosIndividuales');
  Route::post('/modificarParametrosRelevamientosProgresivo','RelevamientoProgresivoController@modificarParametrosRelevamientosProgresivo');
});
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
Route::get('expedientes/buscarExpedientePorCasinoYNumero/{id_casino}/{busqueda}','ExpedienteController@buscarExpedientePorCasinoYNumero');
Route::get('expedientes/tiposMovimientos/{id_expediente}','ExpedienteController@tiposMovimientos');
Route::get( 'expedientes/obtenerMovimiento/{id}','LogMovimientoController@obtenerMovimiento');
Route::post('expedientes/movimientosSinExpediente','LogMovimientoController@movimientosSinExpediente');
/***********
Usuarios
***********/
Route::get('usuarios','UsuarioController@buscarTodo')->middleware('tiene_permiso:ver_seccion_usuarios');
Route::post('usuarios/buscar','UsuarioController@buscarUsuarios');
Route::get('usuarios/buscar/{id_usuario}','UsuarioController@buscarUsuario');
Route::get('usuarios/quienSoy' ,'UsuarioController@quienSoy');
Route::post('usuarios/guardarUsuario','UsuarioController@guardarUsuario');
Route::delete('usuarios/eliminarUsuario/{id_usuario}','UsuarioController@eliminarUsuario');
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
Route::group(['prefix' => 'juegos','middleware' => 'tiene_permiso:ver_seccion_juegos'], function () {
  Route::get('/','JuegoController@buscarTodo');
  Route::get('/{id}','JuegoController@buscarTodo');
  Route::get('/obtenerJuego/{id?}','JuegoController@obtenerJuego');
  Route::post('/guardarJuego','JuegoController@guardarJuego');
  Route::post('/modificarJuego','JuegoController@modificarJuego');
  Route::delete('/eliminarJuego/{id}','JuegoController@eliminarJuego');
  Route::get('/obtenerTablasDePago/{id}','JuegoController@obtenerTablasDePago');
  Route::get('/buscarJuegos/{busqueda}','JuegoController@buscarJuegoPorCodigoYNombre');
  Route::get('/buscarJuegos/{id_casino}/{busqueda}','JuegoController@buscarJuegoPorCasinoYNombre');
  Route::post('/buscar','JuegoController@buscarJuegos');
});

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
Route::group(['prefix' => 'certificadoSoft','middleware' =>'tiene_permiso:ver_seccion_glisoft'],function(){
  Route::get('/','GliSoftController@buscarTodo');
  Route::get('/{id}','GliSoftController@buscarTodo');
  Route::post('/guardarGliSoft','GliSoftController@guardarGliSoft');
  Route::get('/pdf/{id}','GliSoftController@leerArchivoGliSoft');
  Route::get('/obtenerGliSoft/{id}','GliSoftController@obtenerGliSoft');
  Route::delete('/eliminarGliSoft/{id}','GliSoftController@eliminarGLI');
  Route::post('/buscarGliSoft','GliSoftController@buscarGliSofts');
  Route::post('/modificarGliSoft','GliSoftController@modificarGliSoft');
});
//Lo dejo afuera sin permisos porque se usa en otro modulo, Movimientos...
Route::get('certificadoSoft/buscarGliSoftsPorNroArchivo/{nro_archivo}','GliSoftController@buscarGliSoftsPorNroArchivo');
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
Route::group(['prefix' => 'maquinas','middleware' => 'tiene_permiso:ver_seccion_maquinas'], function () {
  Route::get('/','MTMController@buscarTodo');
  Route::get('/{id}','MTMController@buscarTodo');
  Route::post('/guardarMaquina', 'MTMController@guardarMaquina');
  Route::post('/modificarMaquina', 'MTMController@modificarMaquina');
  Route::post('/buscarMaquinas', 'MTMController@buscarMaquinas');
  Route::delete('/eliminarMaquina/{id}', 'MTMController@eliminarMTM');
  Route::post('/cargaMasiva', 'LectorCSVController@cargaMasivaMaquinas');
});
//Lo necesitan los auditores
Route::get('maquinas/getMoneda/{nro}','MTMController@getMoneda');
//Estos por si las moscas lo pongo ... Son todos GET por lo menos
//Es muy posible que usuarios que no tienen el permiso ver_seccion_maquinas las use
Route::get('maquinas/obtenerMTM/{id}', 'MTMController@obtenerMTM');
Route::get('maquinas/obtenerMTMEnCasino/{casino}/{id}', 'MTMController@obtenerMTMEnCasino');
Route::get('maquinas/buscarMaquinaPorNumeroMarcaYModelo/{casino?}/{busqueda}','MTMController@buscarMaquinaPorNumeroMarcaYModelo');
Route::get('maquinas/obtenerConfiguracionMaquina/{id}', 'MTMController@obtenerConfiguracionMaquina');
Route::get('maquinas/buscarMarcas/{marca}', 'MTMController@buscarMarcas');


/**********
Islas
**********/
Route::group(['prefix' => 'islas','middleware' => 'tiene_permiso:ver_seccion_islas'], function () {
  Route::get('/','IslaController@buscarTodo');
  Route::post('/buscarIslas','IslaController@buscarIslas');
  Route::post('/guardarIsla','IslaController@guardarIsla');
  Route::post('/modificarIsla','IslaController@modificarIsla');
  Route::delete('/eliminarIsla/{id_isla}','IslaController@eliminarIsla');
  Route::post('/dividirIsla','IslaController@dividirIsla');
  Route::get('/obtenerMTMReducido/{id}', 'MTMController@obtenerMTMReducido');
});
//@HACK: mover estos endpoints al group() donde se use... no es necesario que tengan el prefijo "islas"
Route::get('islas/buscarIslaPorCasinoYNro/{id_casino}/{nro_isla}','IslaController@buscarIslaPorCasinoYNro');
Route::get('islas/buscarIslaPorCasinoSectorYNro/{id_casino}/{id_sector}/{nro_isla}','IslaController@buscarIslaPorCasinoSectorYNro');
Route::get('islas/buscarIslaPorSectorYNro/{id_sector}/{nro_isla}','IslaController@buscarIslaPorSectorYNro');
Route::get('islas/obtenerIsla/{id_isla}','IslaController@obtenerIsla');
Route::get('islas/obtenerIsla/{id_casino}/{id_sector}/{nro_isla}','IslaController@obtenerIslaPorNro');
Route::get('islas/listarMaquinasPorNroIsla/{nro_isla}/{id_casino?}','IslaController@listarMaquinasPorNroIsla');

/**********
Movimientos
***********/
Route::group(['prefix' => 'movimientos','middleware' => 'tiene_permiso:ver_seccion_movimientos'], function () {
  Route::get( '/','LogMovimientoController@movimientos');
  Route::get( '/casinosYMovimientosIngresosEgresos','LogMovimientoController@casinosYMovimientosIngresosEgresos');
  Route::post('/buscarLogsMovimientos','LogMovimientoController@buscarLogsMovimientos');
  Route::post('/enviarAFiscalizar', 'LogMovimientoController@enviarAFiscalizar');
  Route::get( '/obtenerExpediente/{id}','ExpedienteController@obtenerExpediente');
  Route::post('/guardarMaquina', 'MTMController@guardarMaquina');
  Route::post('/cargaMasiva', 'LectorCSVController@cargaMasivaMaquinas');
  Route::post('/guardarTipoCargaYCantMaq', 'LogMovimientoController@guardarTipoCargaYCantMaq');
  Route::get( '/obtenerMaquinasMovimiento/{id}','LogMovimientoController@obtenerMaquinasMovimiento');
  Route::get( '/obtenerFiscalizacionesMovimiento/{id}', 'LogMovimientoController@obtenerFiscalizacionesMovimiento');
  Route::get( '/obtenerRelevamientosFiscalizacion/{id_fiscalizacion_movimiento}','LogMovimientoController@obtenerRelevamientosFiscalizacion');
  Route::get( '/obtenerRelevamientoToma/{id_relevamiento}/{nro_toma?}', 'LogMovimientoController@obtenerRelevamientoToma');
  Route::post('/cargarTomaRelevamiento', 'LogMovimientoController@cargarTomaRelevamiento');
  Route::post('/nuevoLogMovimiento','LogMovimientoController@nuevoLogMovimiento');
  Route::post('/eliminarMovimiento', 'LogMovimientoController@eliminarMovimiento');
  Route::get( '/obtenerDatos/{id}','LogMovimientoController@obtenerDatos');
  Route::get( '/imprimirMovimiento/{id_movimiento}','LogMovimientoController@imprimirMovimiento');
  Route::post('/visarConObservacion', 'LogMovimientoController@visarConObservacion');
  Route::get( '/obtenerMTMEnCasino/{id_casino}/{admin}','MTMController@obtenerMTMEnCasino');
  Route::get( '/obtenerMTM/{id}', 'MTMController@obtenerMTM');
  Route::post('/cargarMaquinasMovimiento','LogMovimientoController@cargarMaquinasMovimiento');
  Route::get( '/obtenerMovimiento/{id}','LogMovimientoController@obtenerMovimiento');
  Route::post('/movimientosSinExpediente','LogMovimientoController@movimientosSinExpediente');
});

/**********
Relevamientos
***********/
Route::group(['prefix' => 'relevamientos_movimientos','middleware' => 'tiene_permiso:ver_seccion_relevamientos_movimientos'], function () {
  Route::get( '/','LogMovimientoController@relevamientosMovimientos');
  Route::get('/{id}','LogMovimientoController@relevamientosMovimientos');
  Route::post('/buscarFiscalizaciones','FiscalizacionMovController@buscarFiscalizaciones');
  Route::get( '/eliminarFiscalizacion/{id}','FiscalizacionMovController@eliminarFiscalizacionParcial');
  Route::get( '/imprimirFiscalizacion/{id}','LogMovimientoController@imprimirFiscalizacion');
  Route::get( '/obtenerRelevamientosFiscalizacion/{id_fiscalizacion_movimiento}','LogMovimientoController@obtenerRelevamientosFiscalizacion');
  Route::get( '/obtenerRelevamientoToma/{id_relevamiento}/{nro_toma?}', 'LogMovimientoController@obtenerRelevamientoToma');
  Route::post('/cargarTomaRelevamiento', 'LogMovimientoController@cargarTomaRelevamiento');
});

/**********
Eventualidades ->intervenciones tecnicas
***********/

Route::get('eventualidades','EventualidadController@buscarTodoDesdeFiscalizador')->middleware('tiene_permiso:ver_seccion_eventualidades');
Route::post('eventualidades/buscarPorTipoFechaCasinoTurno','EventualidadController@buscarPorTipoFechaCasinoTurno');
Route::get('eventualidades/crearEventualidad/{id_casino}', 'EventualidadController@crearEventualidad');
Route::get('eventualidades/verPlanillaVacia/{id}', 'EventualidadController@verPlanillaVacia');
Route::get('eventualidades/obtenerSectorEnCasino/{id_casino}/{id_sector}','EventualidadController@obtenerSectorEnCasino');
Route::get('eventualidades/obtenerIslaEnCasino/{id_casino}/{nro_isla}','EventualidadController@obtenerIslaEnCasino');
Route::post('eventualidades/CargarYGuardarEventualidad','EventualidadController@CargarYGuardarEventualidad');
Route::get('eventualidades/visualizarEventualidadID/{id_ev}','EventualidadController@visualizarEventualidadID');
Route::get('eventualidades/eliminarEventualidad/{id_ev}', 'EventualidadController@eliminarEventualidad');
Route::get('eventualidades/visado/{id_ev}', 'EventualidadController@validarEventualidad');
Route::post('eventualidades/buscarEventualidadesMTMs', 'LogMovimientoController@buscarEventualidadesMTMs');
Route::get('eventualidades/leerArchivoEventualidad/{id}','EventualidadController@leerArchivoEventualidad');

/**********
Eventualidades MTM ->intervenciones tecnicas mtm
***********/
Route::group(['prefix' => 'eventualidadesMTM','middleware' => 'tiene_permiso:ver_seccion_eventualidades_MTM'], function () {
  Route::get( '/','LogMovimientoController@eventualidadesMTM');
  Route::post('/nuevaEventualidadMTM','LogMovimientoController@nuevaEventualidadMTM');
  Route::post('/cargarTomaRelevamiento', 'LogMovimientoController@cargarTomaRelevamiento');
  Route::get( '/obtenerRelevamientoToma/{id_relevamiento}/{nro_toma?}', 'LogMovimientoController@obtenerRelevamientoToma');
  Route::post('/eliminarEventualidadMTM', 'LogMovimientoController@eliminarEventualidadMTM');
  Route::get( '/tiposMovIntervMTM', 'LogMovimientoController@tiposMovIntervMTM');
  Route::get( '/relevamientosEvMTM/{id_movimiento}', 'LogMovimientoController@relevamientosEvMTM');
  Route::get( '/imprimirEventualidadMTM/{id_mov}','LogMovimientoController@imprimirEventualidadMTM');
  Route::post('/visarConObservacion', 'LogMovimientoController@visarConObservacion');
  Route::get( '/obtenerMTMEnCasino/{id_casino}/{admin}','MTMController@obtenerMTMEnCasino');
  Route::get( '/obtenerMTM/{id}', 'MTMController@obtenerMTM');
});


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
Route::delete('beneficios/eliminarBeneficios/{id_casino}/{id_tipo_moneda}/{anio}/{mes}','BeneficioController@eliminarBeneficios');
Route::delete('beneficios/eliminarBeneficio/{id}','BeneficioController@eliminarBeneficio');
Route::get('importaciones','ImportacionController@buscarTodo')->middleware('tiene_permiso:ver_seccion_importaciones');
Route::post('importaciones/buscar','ImportacionController@buscar');
Route::get('importaciones/{id_casino}/{fecha_busqueda?}/{orden?}','ImportacionController@estadoImportacionesDeCasino');
Route::post('importaciones/importarContador','ImportacionController@importarContador');
Route::post('importaciones/importarProducido','ImportacionController@importarProducido');
Route::post('importaciones/importarBeneficio','ImportacionController@importarBeneficio');
Route::post('importaciones/previewBeneficios','ImportacionController@previewBeneficios');
Route::post('importaciones/previewProducidos','ImportacionController@previewProducidos');
Route::post('importaciones/previewContadores','ImportacionController@previewContadores');

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
  return ['fecha' => strftime("%A, %d de %B de %Y"), 'fechaDate' => date("Y-m-d")];
});


/**************
RELEVAMIENTO PROGRESIVO
**************/
Route::group(['prefix' => 'relevamientosProgresivo','middleware' => 'tiene_permiso:ver_seccion_relevamientos_progresivos'], function () {
  Route::get('/','RelevamientoProgresivoController@buscarTodo');
  Route::get('/buscarRelevamientosProgresivos','RelevamientoProgresivoController@buscarRelevamientosProgresivos');
  Route::post('/crearRelevamiento' , 'RelevamientoProgresivoController@crearRelevamientoProgresivos');
  Route::post('/cargarRelevamiento','RelevamientoProgresivoController@cargarRelevamiento');
  Route::post('/guardarRelevamiento','RelevamientoProgresivoController@guardarRelevamiento');
  Route::post('/validarRelevamiento','RelevamientoProgresivoController@validarRelevamiento');
  Route::get('/obtenerRelevamiento/{id}','RelevamientoProgresivoController@obtenerRelevamiento');
  Route::get('/generarPlanilla/{id_relevamiento_progresivo}','RelevamientoProgresivoController@generarPlanillaProgresivos');
  Route::get('/eliminarRelevamientoProgresivo/{id_relevamiento_progresivo}','RelevamientoProgresivoController@eliminarRelevamientoProgresivo');
});



/******************************************************
RELEVAMIENTOS CONTROL AMBIENTAL - MÁQUINAS TRAGAMONEDAS
******************************************************/
Route::group(['prefix' => 'relevamientosControlAmbiental','middleware' => 'tiene_permiso:ver_seccion_relevamientos_control_ambiental'], function () {
  Route::get('/','RelevamientoAmbientalController@buscarTodo');
  Route::get('/buscarRelevamientosAmbiental','RelevamientoAmbientalController@buscarRelevamientosAmbiental');
  Route::get('/generarPlanilla/{id_relevamiento_ambiental}','RelevamientoAmbientalController@generarPlanillaAmbiental');
  Route::get('/eliminarRelevamientoAmbiental/{id_relevamiento_ambiental}','RelevamientoAmbientalController@eliminarRelevamientoAmbiental');
  Route::get('/obtenerRelevamiento/{id}','RelevamientoAmbientalController@obtenerRelevamiento');
  Route::get('/obtenerGeneralidades','UsuarioController@obtenerOpcionesGeneralidades');
  Route::post('/crearRelevamiento' , 'RelevamientoAmbientalController@crearRelevamientoAmbientalMaquinas');
  Route::post('/cargarRelevamiento','RelevamientoAmbientalController@cargarRelevamiento');
  Route::post('/guardarTemporalmenteRelevamiento','RelevamientoAmbientalController@guardarTemporalmenteRelevamiento');
  Route::post('/validarRelevamiento','RelevamientoAmbientalController@validarRelevamiento');
});

/**********************************************
RELEVAMIENTOS CONTROL AMBIENTAL - MESAS DE PAÑO
**********************************************/
Route::group(['prefix' => 'relevamientosControlAmbientalMesas','middleware' => 'tiene_permiso:ver_seccion_relevamientos_control_ambiental'], function () {
  Route::get('/','RelevamientoAmbientalMesasController@buscarTodo');
  Route::get('/buscarRelevamientosAmbiental','RelevamientoAmbientalMesasController@buscarRelevamientosAmbiental');
  Route::get('/generarPlanilla/{id_relevamiento_ambiental}','RelevamientoAmbientalMesasController@generarPlanillaAmbiental');
  Route::get('/eliminarRelevamientoAmbiental/{id_relevamiento_ambiental}','RelevamientoAmbientalMesasController@eliminarRelevamientoAmbiental');
  Route::get('/obtenerRelevamiento/{id}','RelevamientoAmbientalMesasController@obtenerRelevamiento');
  Route::post('/crearRelevamiento' , 'RelevamientoAmbientalMesasController@crearRelevamientoAmbientalMesas');
  Route::post('/cargarRelevamiento','RelevamientoAmbientalMesasController@cargarRelevamiento');
  Route::post('/guardarTemporalmenteRelevamiento','RelevamientoAmbientalMesasController@guardarTemporalmenteRelevamiento');
  Route::post('/validarRelevamiento','RelevamientoAmbientalMesasController@validarRelevamiento');
});

/*************************
INFORMES CONTROL AMBIENTAL
*************************/
Route::group(['prefix' => 'informeControlAmbiental','middleware' => 'tiene_permiso:ver_seccion_informes_control_ambiental'], function () {
  Route::get('/','InformeControlAmbientalController@buscarTodo');
  Route::get('/buscarInformesControlAmbiental','InformeControlAmbientalController@buscarInformesControlAmbiental');
  Route::get('/imprimir/{id_casino}/{fecha}','InformeControlAmbientalController@imprimir');
});

/*******************
  Máquinas a pedir
********************/
Route::get('mtm_a_pedido','MaquinaAPedidoController@buscarTodo')->middleware('tiene_permiso:ver_seccion_mtm_a_pedido');
Route::get('mtm_a_pedido/obtenerMtmAPedido/{fecha}/{id_sector}','MaquinaAPedidoController@obtenerMtmAPedido');
Route::post('mtm_a_pedido/buscarMTMaPedido','MaquinaAPedidoController@buscarMTMaPedido');
Route::post('mtm_a_pedido/guardarMtmAPedido','MaquinaAPedidoController@guardarMtmAPedido');
Route::delete('mtm_a_pedido/eliminarMmtAPedido/{id}','MaquinaAPedidoController@eliminarMTMAPedido');

Route::group(['prefix' => 'alertas_contadores', 'middleware' => 'tiene_permiso:ver_seccion_contadores'],function(){
  Route::get('/','AlertasContadoresController@buscarTodo');
  Route::post('/buscarPolleos','AlertasContadoresController@buscarPolleos');
  Route::get('/obtenerDetalles/{id_polleo}','AlertasContadoresController@obtenerDetalles');
  Route::get('/obtenerDetalleCompleto/{id_polleo}/{nro_admin}','AlertasContadoresController@obtenerDetalleCompleto');
  Route::post('/importarPolleos','AlertasContadoresController@importarPolleos');
});

/*******************
PRODUCIDOS-AJUSTES PRODUCIDO
******************/
Route::group(['prefix' => 'producidos','middleware' => 'tiene_permiso:ver_seccion_producidos'],function (){
  Route::get('','ProducidoController@buscarTodo')->middleware('tiene_permiso:ver_seccion_producidos');
  Route::post('/buscarProducidos','ProducidoController@buscarProducidos');
  Route::get('/generarPlanillaDiferencias/{id_producido}','ProducidoController@generarPlanillaDiferencias');
  Route::get('/generarPlanillaProducido/{id_producido}','ProducidoController@generarPlanillaProducido');
  Route::post('/guardarAjuste','ProducidoController@guardarAjuste');
  Route::get('/datosAjusteMTM/{id_maquina}/{id_producidos}','ProducidoController@datosAjusteMTM');
  Route::get('/ajustarProducido/{id_producido}','ProducidoController@ajustarProducido');
});

/***********
 Estadisticas
************/
Route::get('estadisticas_relevamientos','MaquinaAPedidoController@buscarTodoInforme' )->middleware('tiene_permiso:ver_seccion_estadisticas_relevamientos');
Route::post('estadisticas_relevamientos/guardarMtmAPedido','MaquinaAPedidoController@guardarMtmAPedido');
Route::post('estadisticas_relevamientos/obtenerUltimosRelevamientosPorMaquina','RelevamientoController@obtenerUltimosRelevamientosPorMaquina');
Route::post('estadisticas_relevamientos/obtenerUltimosRelevamientosPorMaquinaNroAdmin','RelevamientoController@obtenerUltimosRelevamientosPorMaquinaNroAdmin');
Route::post('estadisticas_relevamientos/buscarMaquinasSinRelevamientos','RelevamientoController@buscarMaquinasSinRelevamientos');
Route::get('estadisticas_relevamientos/obtenerFechasMtmAPedido/{id}', 'MaquinaAPedidoController@obtenerFechasMtmAPedido');
Route::get('estadisticas_relevamientos/buscarMaquinas/{id_casino}','RelevamientoController@buscarMaquinasPorCasino');
/**********
 Beneficios
***********/

Route::group(['prefix' => 'beneficios','middleware' => 'tiene_permiso:ver_seccion_beneficios'],function (){
  Route::get('/','BeneficioController@buscarTodo')->middleware('tiene_permiso:ver_seccion_beneficios');
  Route::post('/buscarBeneficios','BeneficioController@buscarBeneficios');
  Route::post('/obtenerBeneficiosParaValidar','BeneficioController@obtenerBeneficiosParaValidar');
  Route::post('/ajustarBeneficio','BeneficioController@ajustarBeneficio');
  Route::post('/validarBeneficios','BeneficioController@validarBeneficios');
  Route::post('/validarBeneficiosSinProducidos','BeneficioController@validarBeneficiosSinProducidos');
  Route::get('/generarPlanilla/{id_casino}/{id_tipo_moneda}/{anio}/{mes}','BeneficioController@generarPlanilla');
  Route::get('/generarPlanillaDiferenciasProducido/{id_producido}','ProducidoController@generarPlanillaDiferencias');
  Route::post('/cargarImpuesto','BeneficioController@cargarImpuesto');
});

/*********
LAYOUT
*********/
Route::get('menu_layout',function(){
    return view('menu_layout');
});
Route::get('layout_parcial','LayoutController@buscarTodo')->middleware('tiene_permiso:ver_seccion_layout_parcial');
Route::post('/layouts/crearLayoutParcial','LayoutController@crearLayoutParcial');
Route::post('/layouts/usarLayoutBackup' , 'LayoutController@usarLayoutBackup');

//PARCIAL
Route::get('/layouts/existeLayoutParcial/{id_sector}','LayoutController@existeLayoutParcial');
Route::get('/layouts/existeLayoutParcialGenerado/{id_sector}','LayoutController@existeLayoutParcialGenerado');
Route::get('/layouts/obtenerLayoutParcial/{id}','LayoutController@obtenerLayoutParcial');
Route::get('/layouts/obtenerLayoutParcialValidar/{id}','LayoutController@obtenerLayoutParcialValidar');
Route::get('/layouts/generarPlanillaLayoutParcial/{id}','LayoutController@generarPlanillaLayoutParcial');
Route::get('/layouts/descargarLayoutParcialZip/{nombre}','LayoutController@descargarLayoutParcialZip');
Route::post('/layouts/buscarLayoutsParciales' , 'LayoutController@buscarLayoutsParciales');
Route::post('/layouts/cargarLayoutParcial' , 'LayoutController@cargarLayoutParcial');
Route::post('/layouts/validarLayoutParcial' , 'LayoutController@validarLayoutParcial');

//TOTAL
Route::get('layout_total','LayoutController@buscarTodoTotal')->middleware('tiene_permiso:ver_seccion_layout_total');
Route::group(['prefix' => 'layouts','middleware' => 'tiene_permiso:ver_seccion_layout_total'], function () {
  Route::post('/crearLayoutTotal','LayoutController@crearLayoutTotal');
  Route::post('/buscarLayoutsTotales' , 'LayoutController@buscarLayoutsTotales');
  Route::get('/descargarLayoutTotalZip/{nombre}','LayoutController@descargarLayoutTotalZip');
  Route::get('/generarPlanillaLayoutTotales/{id}','LayoutController@generarPlanillaLayoutTotales');
  Route::get('/generarPlanillaLayoutTotalesCargado/{id}','LayoutController@generarPlanillaLayoutTotalesCargado');
  Route::post('/guardarLayoutTotal','LayoutController@guardarLayoutTotal');
  Route::post('/cargarLayoutTotal' , 'LayoutController@cargarLayoutTotal');
  Route::get('/descargarLayoutTotalZip/{nombre}','LayoutController@descargarLayoutTotalZip');
  Route::get('/obtenerLayoutTotal/{id}','LayoutController@obtenerLayoutTotal');
  Route::post('/validarLayoutTotal' , 'LayoutController@validarLayoutTotal');
  Route::post('/usarLayoutTotalBackup' , 'LayoutController@usarLayoutTotalBackup');
  Route::get('/islasLayoutTotal/{id_layout_total}','LayoutController@islasLayoutTotal');
  Route::delete('/eliminarLayoutTotal/{id_layout_total}','LayoutController@eliminarLayoutTotal');
  Route::get('/obtenerTurno/{id}','CasinoController@obtenerTurno');
  Route::get('/obtenerSectoresPorCasino/{id_casino}','SectorController@obtenerSectoresPorCasino');
  Route::get('/obtenerMTMsEnIsla/{id_casino}/{nro_isla}/{nro_admin}','LayoutController@obtenerMTMsEnIsla');
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
Route::get('informesMTM/obtenerEstadoParqueDeCasino/{id_casino}','informesController@obtenerInformeEstadoParqueDeParque');

Route::get('informeContableMTM','informesController@buscarTodoInformeContable');//carga pagina
Route::get('obtenerInformeContableDeMaquina/{id_maquina}','informesController@obtenerInformeContableDeMaquina');//informe ultimos 30 dias

//seccion informes mtm (pestaña informes)
Route::get('informesMTM','informesController@obtenerUltimosBeneficiosPorCasino');
Route::get('informesMTM/generarPlanilla/{year}/{mes}/{id_casino}/{id_tipo_moneda}','informesController@generarPlanilla');
Route::get('informesMTM/generarPlanillaMaquinas/{year}/{mes}/{id_casino}/{id_tipo_moneda}/{maqmenor}/{maqmayor}','informesController@generarPlanillaMaquinas');
Route::get('informesMTM/generarPlanillaIsla/{year}/{mes}/{id_casino}/{id_tipo_moneda}/{nro_isla}','informesController@generarPlanillaIsla');

Route::get('informesBingo',function(){
    return view('seccionInformesBingo');
});

Route::get('informesJuegos',function(){
    return view('seccionInformesJuegos');
});

Route::group(['prefix' => 'informeSector','middleware' => ['tiene_permiso:ver_seccion_informesector']], function () {
  Route::get('/','informesController@mostrarInformeSector')->middleware('tiene_permiso:ver_seccion_informesector');
  Route::get('/obtenerMTMs','informesController@obtenerMTMs');
  Route::post('/transaccionEstadoMasivo','MTMController@transaccionEstadoMasivo');
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
  Route::post('cierres/filtrosCierres','Mesas\Cierres\BCCierreController@filtros');
  Route::post('cierres/guardar', 'Mesas\Cierres\ABMCierreController@guardar');
  Route::post('cierres/modificarCierre','Mesas\Cierres\ABMCierreController@modificarCierre');
  Route::get('cierres/obtenerCierres/{id_cierre}', 'Mesas\Cierres\BCCierreController@getCierre');
  Route::get('cierres/bajaCierre/{id_cierre}', 'Mesas\Cierres\BCCierreController@eliminarCierre')->middleware(['tiene_permiso:m_eliminar_cierres_y_aperturas']);
  Route::get('mesas/obtenerMesasCierre/{id_cas}/{nro_mesa}', 'Mesas\Mesas\BuscarMesasController@buscarMesaPorNroCasino');

  //Aperturas
  Route::get('/aperturas', 'Mesas\Aperturas\BCAperturaController@buscarTodo');
  Route::get('/aperturas/obtenerMesasPorJuego/{id_juego_mesa}/{nro_mesa}', 'Mesas\Mesas\BuscarMesasController@buscarMesaPorJuego');

  Route::post('/aperturas/agregarAperturaAPedido','Mesas\Aperturas\ABMAperturaController@agregarAperturaAPedido');
  Route::get('/aperturas/buscarAperturasAPedido','Mesas\Aperturas\ABMAperturaController@buscarAperturasAPedido');
  Route::delete('/aperturas/borrarAperturaAPedido/{id_apertura_a_pedido}','Mesas\Aperturas\ABMAperturaController@borrarAperturaAPedido');
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

//informes fiscalizadores (compara Cierres contra Aperturas y verifica Apuestas Minimas)
Route::group(['prefix' => 'informeDiarioBasico','middleware' => 'tiene_permiso:m_ver_seccion_informe_fiscalizadores'], function () {
  Route::get('/','Mesas\InformeFiscalizadores\BCInformesController@index');
  Route::post('/buscar', 'Mesas\InformeFiscalizadores\BCInformesController@filtros');
  Route::post('//buscarInformes','Mesas\InformeFiscalizadores\BCInformesController@filtros');
  Route::get('/imprimir/{id_informe_fiscalizacion}','Mesas\InformeFiscalizadores\BCInformesController@imprimirPlanilla');
});

Route::group(['prefix' => 'importacionDiaria','middleware' => 'tiene_permiso:m_ver_seccion_importaciones'],function (){
  Route::get('/','Mesas\Importaciones\Mesas\ImportadorController@buscarTodo');
  Route::post('/importar','Mesas\Importaciones\Mesas\ImportadorController@importarDiario')->middleware(['tiene_permiso:m_importar']);
  Route::post('/importarCierres','Mesas\Importaciones\Mesas\ImportadorController@importarCierres')->middleware(['tiene_permiso:m_importar']);
  Route::post('/filtros','Mesas\Importaciones\Mesas\ImportadorController@filtros');
  Route::get('/verImportacion/{id_imp}/{t_mesa?}','Mesas\Importaciones\Mesas\ImportadorController@buscarPorTipoMesa');
  Route::get('/imprimir/{id}','Mesas\Importaciones\Mesas\ImportadorController@imprimirDiario');
  Route::post('/guardar','Mesas\Importaciones\Mesas\ImportadorController@guardarImportacionDiaria');
  Route::get('/eliminarImportacion/{id_imp}','Mesas\Importaciones\Mesas\ImportadorController@eliminar');
  Route::post('/ajustarDetalle','Mesas\Importaciones\Mesas\ImportadorController@ajustarDetalle');
  Route::get('/imprimirMensual/{fecha}/{id_casino}','Mesas\Importaciones\Mesas\ImportadorController@imprimirMensual');
});

Route::group(['prefix' => 'informeAnual','middleware' => 'tiene_permiso:m_bc_anuales'],function (){
  Route::get('/',function(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    return view('Informes.seccionInformesAnuales',['casinos'=>$usuario->casinos]);
  });
  Route::post('/obtenerDatos','Mesas\InformesMesas\BCAnualesController@buscarPorAnioCasinoMoneda');
});

Route::group(['prefix' => 'informeMensual','middleware' => 'tiene_permiso:m_bc_diario_mensual'],function(){
  Route::get('/',function(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    return view('Informes.seccionInformesMensuales',['casinos'=>$usuario->casinos]);
  });
  Route::post('obtenerDatos','Mesas\InformesMesas\BCMensualesController@obtenerDatosGraficos');
});


Route::get('/canon','Mesas\Canon\BPagosController@index')->middleware(['tiene_permiso:m_ver_seccion_canon']);
Route::group(['prefix' => 'canon','middleware' => ['tiene_permiso:m_a_pagos']], function () {
  Route::post('/crearOModificarPago','Mesas\Canon\APagosController@crearOModificar');
  Route::delete('/borrarPago/{id_detalle}','Mesas\Canon\APagosController@borrar');
  Route::post('/modificarInformeBase','Mesas\Canon\APagosController@modificarInformeBase');
});
Route::group(['prefix' => 'canon','middleware' => ['tiene_permiso:m_b_pagos']], function () {
  Route::get('/getMesesCuotas/{id_casino}/{anio_inicio}', 'Mesas\Canon\BPagosController@mesesCuotasCanon');
  Route::get('/mesesCargados/{id_casino}/{anio_inicio}','Mesas\Canon\BPagosController@mesesCargados');
  Route::post('/buscarPagos','Mesas\Canon\BPagosController@filtros');
  Route::get('/obtenerPago/{id_detalle}','Mesas\Canon\BPagosController@obtenerPago');
  Route::get('/obtenerAnios/{id_casino}','Mesas\Canon\BPagosController@obtenerAnios');
  Route::get('/obtenerInformeBase/{id_casino}','Mesas\Canon\BPagosController@obtenerInformeBase');
  Route::post('/verInforme','Mesas\Canon\BPagosController@verInformeFinalMesas');
});

Route::group(['middleware' => ['tiene_permiso:m_abmc_img_bunker']], function () {
  Route::post('solicitudImagenes/buscar','Mesas\Bunker\ABMCImgBunkerController@filtros');
  Route::get('solicitudImagenes/obtenerMesas/{id}', 'Mesas\Bunker\ABMCImgBunkerController@obtenerBunker');
  Route::get('solicitudImagenes/hayCoincidencia/{drop}/{id_detalle}', 'Mesas\Bunker\ABMCImgBunkerController@consultarDiferencias');
  Route::post('solicitudImagenes/sorteoFechasMesas', 'Mesas\Bunker\ABMCImgBunkerController@altaImgsBunker');
  Route::post('solicitudImagenes/guardar','Mesas\Bunker\ABMCImgBunkerController@cargar');
  Route::get('/solicitudImagenes','Mesas\Bunker\ABMCImgBunkerController@index');
});

//BINGO
Route::group(['middleware' => ['tiene_permiso:ver_seccion_sesion_relevamientos']], function () {
  Route::get('bingo','Bingo\SesionesController@index');
  Route::get('bingo/buscarSesion','Bingo\SesionesController@buscarSesion');
  Route::post('bingo/guardarSesion','Bingo\SesionesController@guardarSesion');
  Route::delete('bingo/eliminarSesion/{id}','Bingo\SesionesController@eliminarSesion');
  Route::get('bingo/obtenerSesion/{id}','Bingo\SesionesController@obtenerSesion');
  Route::post('bingo/guardarCierreSesion','Bingo\SesionesController@guardarCierreSesion');
  Route::post('bingo/guardarRelevamiento','Bingo\SesionesController@guardarRelevamiento');
  Route::post('bingo/reAbrirSesion/{id}','Bingo\SesionesController@reAbrirSesion');
  Route::post('bingo/modificarCierreSesion','Bingo\SesionesController@modificarCierreSesion');
  Route::post('bingo/modificarSesion','Bingo\SesionesController@modificarSesion');
  Route::get('bingo/generarPlanillaSesion','Bingo\SesionesController@generarPlanillaSesion');
  Route::get('bingo/generarPlanillaCierreSesion','Bingo\SesionesController@generarPlanillaCierreSesion');
  Route::get('bingo/generarPlanillaRelevamiento','Bingo\SesionesController@generarPlanillaRelevamiento');
  Route::delete('bingo/eliminarPartida/{id}','Bingo\SesionesController@eliminarPartida');
});
Route::group(['middleware' => ['tiene_permiso:bingo_ver_gestion']], function () {
  Route::get('bingo/gestionBingo','Bingo\GestionController@index');
  Route::get('bingo/buscarPremio','Bingo\GestionController@buscarPremio');
  Route::get('bingo/buscarCanon','Bingo\GestionController@buscarCanon');
  Route::post('bingo/guardarPremio','Bingo\GestionController@guardarPremio');
  Route::post('bingo/guardarCanon','Bingo\GestionController@guardarCanon');
  Route::delete('bingo/eliminarPremio/{id}','Bingo\GestionController@eliminarPremio');
  Route::delete('bingo/eliminarCanon/{id}','Bingo\GestionController@eliminarCanon');
  Route::get('bingo/obtenerPremio/{id}','Bingo\GestionController@obtenerPremio');
  Route::get('bingo/obtenerCanon/{id}','Bingo\GestionController@obtenerCanon');
  Route::post('bingo/modificarPremio','Bingo\GestionController@modificarPremio');
  Route::post('bingo/modificarCanon','Bingo\GestionController@modificarCanon');
});
Route::group(['middleware' => ['tiene_permiso:importar_bingo']], function () {
  Route::get('bingo/importarRelevamiento','Bingo\ImportacionController@index');
  Route::get('bingo/buscarRelevamiento','Bingo\ImportacionController@buscarRelevamiento');
  Route::delete('bingo/eliminarImportacion/{id}','Bingo\ImportacionController@eliminarImportacion');
  Route::post('bingo/guardarImportacion','Bingo\ImportacionController@guardarImportacion');
  Route::get('bingo/obtenerImportacionCompleta/{id}','Bingo\ImportacionController@obtenerImportacionCompleta');
  Route::get('bingo/obtenerImportacionSimple/{fecha}/{casino}','Bingo\ImportacionController@obtenerImportacionSimple');
});
Route::group(['middleware' => ['tiene_permiso:reporte_estado_bingo']], function () {
  Route::get('bingo/reportesEstado','Bingo\ReportesController@reportesEstado');
  Route::get('bingo/buscarEstado','Bingo\ReportesController@buscarEstado');
});
Route::group(['middleware' => ['tiene_permiso:reporte_diferencia_bingo']], function () {
  Route::get('bingo/reportesDiferencia','Bingo\ReportesController@reportesDiferencia');
  Route::get('bingo/buscarReportesDiferencia','Bingo\ReportesController@buscarReportesDiferencia');
  Route::get('bingo/obtenerDiferencia/{id}','Bingo\ReportesController@obtenerDiferencia');
  Route::post('bingo/guardarReporteDiferencia','Bingo\ReportesController@guardarReporteDiferencia');
});
Route::group(['middleware' => ['tiene_permiso:informes_bingos']], function () {
  Route::get('bingo/generarPlanillaInforme/{fecha}/{id_casino}/{valor?}','Bingo\InformeController@generarPlanilla');
  Route::get('bingo/informe','Bingo\InformeController@index');
});

/************
AUTOEXCLUSIÓN
*************/
Route::group(['prefix' => 'autoexclusion','middleware' => 'tiene_permiso:ver_seccion_ae_alta'], function () {
  Route::delete('/eliminarAE/{id_autoexcluido}','Autoexclusion\AutoexclusionController@eliminarAE');
  Route::get('/','Autoexclusion\AutoexclusionController@index');
  Route::post('/agregarAE','Autoexclusion\AutoexclusionController@agregarAE');
  Route::post('/subirArchivo','Autoexclusion\AutoexclusionController@subirArchivo');
  Route::get('/cambiarEstadoAE/{id}/{id_estado}','Autoexclusion\AutoexclusionController@cambiarEstadoAE');
  Route::get('/existeAutoexcluido/{dni}','Autoexclusion\AutoexclusionController@existeAutoexcluido');
  Route::get('/buscarAutoexcluidos','Autoexclusion\AutoexclusionController@buscarAutoexcluidos');
  Route::get('/buscarAutoexcluido/{id}','Autoexclusion\AutoexclusionController@buscarAutoexcluido');
  Route::get('/mostrarArchivo/{id_importacion}/{tipo_archivo}','Autoexclusion\AutoexclusionController@mostrarArchivo');
  Route::get('/mostrarFormulario/{id_formulario}','Autoexclusion\AutoexclusionController@mostrarFormulario');
  Route::get('/generarSolicitudAutoexclusion/{id}','Autoexclusion\AutoexclusionController@generarSolicitudAutoexclusion');
  Route::get('/generarSolicitudFinalizacionAutoexclusion/{id}','Autoexclusion\AutoexclusionController@generarSolicitudFinalizacionAutoexclusion');
  Route::get('/generarConstanciaReingreso/{id}','Autoexclusion\AutoexclusionController@generarConstanciaReingreso');
  Route::get('/BDCSV','Autoexclusion\AutoexclusionController@BDCSV');
  Route::get('/{dni?}','Autoexclusion\AutoexclusionController@index');
});

Route::group(['prefix' => 'informesAutoexcluidos','middleware' => 'tiene_permiso:ver_seccion_ae_informes_listado'], function () {
  Route::get('/','Autoexclusion\InformesAEController@todo');
  Route::get('/buscarAutoexcluidos','Autoexclusion\InformesAEController@buscarAutoexcluidos');
  Route::get('/verFoto/{id_autoexcluido}','Autoexclusion\InformesAEController@verFoto');
});

Route::group(['prefix' => 'galeriaImagenesAutoexcluidos','middleware' => 'tiene_permiso:ver_seccion_ae_informes_galeria'], function () {
  Route::get('/getPathsFotosAutoexcluidos','Autoexclusion\GaleriaImagenesAutoexcluidosController@getPathsFotosAutoexcluidos');
  Route::get('/getDatosUnAutoexcluido/{nro_dni}','Autoexclusion\GaleriaImagenesAutoexcluidosController@getDatosUnAutoexcluido');
  Route::get('/{dni?}','Autoexclusion\GaleriaImagenesAutoexcluidosController@todo');
  Route::get('/mostrarArchivo/{id_importacion}/{tipo_archivo}','Autoexclusion\AutoexclusionController@mostrarArchivo');
});

Route::group(['prefix' =>'API/'],function(){
  Route::group(['prefix' => 'AE'],function(){
    Route::get('/',function(){//Para probar el acceso
      return 1;
    });
    Route::get('/fechas/{DNI}','Autoexclusion\APIAEController@fechas');
    Route::get('/finalizar/{DNI}','Autoexclusion\APIAEController@finalizar');
    Route::post('/agregar','Autoexclusion\APIAEController@agregar');
  });
});