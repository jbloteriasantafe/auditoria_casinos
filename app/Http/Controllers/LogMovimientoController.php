<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use Response;
use View;
use DateTime;
use Dompdf\Dompdf;

use PDF;
use App\Notifications\NuevoMovimiento;
use App\Notifications\RelevamientoCargado;
use App\Notifications\RelevamientoGenerado;
use App\Notifications\NuevaIntervencionMtm;
use App\Usuario;
use App\LogMovimiento;
use App\Nota;
use App\Expediente;
use App\RelevamientoMovimiento;
use App\EstadoMovimiento;
use App\TomaRelevamientoMovimiento;
use App\TipoMovimiento;
use App\TipoMoneda;
use App\FiscalizacionMov;
use App\Formula;
use App\TipoGabinete;
use App\UnidadMedida;
use App\Maquina;
use App\TipoProgresivo;
use App\Casino;
use App\Isla;
use App\Juego;
use App\TipoMaquina;
use App\EstadoMaquina;
use App\TipoCausaNoTomaProgresivo;
use App\Pozo;


/*
* Este controlador manipula las secciones de:
* Asignacion de movimientos mtm, relevamientos , intervenciones MTM
* Los relevamientos tambien son manipulados en su controlador, pero inician aquí
*/


/*
  ALERTA.ALERTA.ALERTA.ALERTA.ALERTA.ALERTA.ALERTA.ALERTA.ALERTA.ALERTA.ALERTA.
  ALERTA.ALERTA.ALERTA.ALERTA.ALERTA.ALERTA.ALERTA.ALERTA.ALERTA.ALERTA.ALERTA.
  en este controlador se manejan la asignacion de movimientos y las intervenciones MTMs
  hoy 25/10/18 se pretende crear un nuevo tipo de movimiento,el cual contemple la
  intervencion fisica de mtms (la cual NO AFECTA NINGUN ATRIBUTO DE LAS MTMS ->
  no agrega valor)
  para hacerlo, debería crearse este nuevo tipo, configurar por todos lados
  (en los blades y js de movimientos y
  de eventualidades mtm (actual intervencion mtm))
  el tema es que -> la solucion posible es agregar este tipo y un campo de
  observacion en el log movimiento
  (para que el controlador / admin escriba loo que se le cante)

  ---
  descripcion de las funciones que hay:

  primero aparecen todas las funciones de asignacion.
  luego unas para consultar si el movimiento está relacionado con un expediente
  (que se usa en el controller de expedientes)
  mas abajo (ya en el fondo), esta lo de IntervencionesMTM (LLAMADO ASI EN LAS PANTALLAS)
  pero internamente son las eventualidades de MTM-> que por alguna razon el expediente
  no se cargó aún y los fisca tienen la obligacion de relevarlos

*/

/*
  ¿Cómo funciona LA ASIGNACION DE MTMS?
  se puede crear un movimiento desde ASIGNACION (sin expediente), y luego se lo asocia a uno desde la seccion expedientes.
  o bien, el caminito normal es:
  en expediente se crea una nota o disposicion (que en realidad tiene asociada una nota para evitar mas cambios)
  en estos objetos se le asocia en log movimiento. con un tipo.
  luego sigue el paso a paso una vez enviado a moex.
  el tema es que SIEMPRE se crearán primero los RELEVAMIENTOS_MOVIMIENTOS.
  QUE SON 1 POR MTM. que a su vez tiene una toma_relevamiento_movimiento (que se crea cuando el fisca la va a cargar)
  UNA VEZ DETERMINADAS LAS MÁQUINAS QUE PERTENECEN AL MOV.
  se las puede ENVIAR A FISCALIZAR. entonces, como se puede enviarlas por tandas
  se asocian los relevamientos movimientos a una fiscalizacion_movimiento.

  -->en IntervencionesMTM la fiscalizacion_movimiento no se crea, porque no es necesaria.

  paciencia-.
*/


/*
 para agregar la posibilidad de carga de retoma de todos los movimientos:
 se debería crear la fiscalización con #esGFJ


*/
class LogMovimientoController extends Controller
{

  private static $atributos = [
    'id_log_movimiento'=> 'Log Movimiento',
    'id_controlador' => 'Controlador',
    'id_fiscalizador' => 'Fiscalizador',
    'id_cargador' => 'Cargador',
    'cantidad_maquinas' => 'Cantidad de Máquinas',
    'id_estado_movimiento' => 'Estado Movimiento',
    'id_estado_relevamiento' => 'Estado Relevamiento',
    'id_nota' => 'Nota',
    'id_expediente' => 'Expediente',
    'fecha_inicio' => 'Fecha Inicio',
    'bool_nota' =>'EsNota',
    'tipo_movimiento' => 'Tipo de Movimiento',
    'maquinas' => 'Maquinas a relevar',
    'maquinas.*.id_maquina' => 'Maquina',
    'juego'=>'juego'
  ];
  private static $instance;

  public static function getInstancia(){
    if (!isset(self::$instance)) {
      self::$instance = new LogMovimientoController();
    }
    return self::$instance;
  }

      /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //:)))))
    }

  public function obtenerDatos($id){
    $log = LogMovimiento::find($id);
    $exp = $log->expediente;
    return ['movimiento' => $log, 'expediente' => $exp];
  }

  public function obtenerMovimiento($id){
    $movimiento = LogMovimiento::find($id);
    return ["movimiento" => $movimiento, "tipo" => $movimiento->tipo_movimiento, "casino" => $movimiento->casino];
  }

  public function buscarTodo(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
    $unidad_medida = UnidadMedida::all();//credito o pesos
    $casinos=array();
    foreach($usuario['usuario']->casinos as $casino){
      $casinos[]=$casino->id_casino;
    }
    $tipos = TipoMaquina::all();
    $monedas = TipoMoneda::all();
    $gabinetes = TipoGabinete::all();
    $estados = EstadoMaquina::all();
    $logs = LogMovimiento::whereIn('id_casino',$casinos)->orderBy('fecha','DESC')->get();
    $casinos=$usuario['usuario']->casinos;
    $tiposMovimientos = TipoMovimiento::all();
    UsuarioController::getInstancia()->agregarSeccionReciente('Asignación Movimientos' ,'movimientos');
    return view('seccionMovimientos',[ 
      'logMovimientos'=>$logs , 'tiposMovimientos' => $tiposMovimientos,'monedas'=>$monedas , 
      'unidades_medida' => $unidad_medida,   'casinos' => $casinos, 'tipos' => $tipos , 
      'gabinetes' => $gabinetes , 'estados' => $estados, 'causasNoTomaProgresivo' => TipoCausaNoTomaProgresivo::all()]);
  }


  public function buscarLogsMovimientos(Request $request){
    //busca logs de movimientos por expediente, por si es nota o no, por fecha, y tipo de movimiento, se tiene en cuenta que el
    //casino es del usuario que está en la session
    $reglas = array();

    if(!empty($request->nro_exp_org))
      $reglas[]=['expediente.nro_exp_org','like', '%'.$request->nro_exp_org.'%'];
    if(!empty($request->nro_exp_interno))
      $reglas[]=['expediente.nro_exp_interno', 'like', '%'.$request->nro_exp_interno.'%'];
    if(!empty($request->nro_exp_control))
      $reglas[]=['expediente.nro_exp_control', '=' , $request->nro_exp_control];

    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    foreach ($usuario->casinos as $casino) {
      $casinos[] = $casino->id_casino;
    }

    if(!empty($request->casino)){
      $casinos = [$request->casino];
    }

    if(isset($request->nro_admin) && $request->nro_admin != ""){
      $reglas[]=['relevamiento_movimiento.nro_admin','like', $request->nro_admin.'%'];
    }

    if(!empty($request->tipo_movimiento)){
      $reglas[]=['log_movimiento.id_tipo_movimiento','=', $request['tipo_movimiento']];
    }

    $sort_by = ['columna' => 'log_movimiento.id_log_movimiento', 'orden' => 'DESC'];
    if(!empty($request->sort_by)){
      $sort_by = $request->sort_by;
    }

    $resultados = DB::table('log_movimiento')
    ->select('log_movimiento.*','expediente.*','casino.*','tipo_movimiento.*','estado_movimiento.descripcion as estado')
    ->join('expediente', 'log_movimiento.id_expediente', '=', 'expediente.id_expediente')
    ->join('casino', 'log_movimiento.id_casino', '=', 'casino.id_casino')
    ->join('tipo_movimiento','log_movimiento.id_tipo_movimiento','=', 'tipo_movimiento.id_tipo_movimiento')
    ->join('estado_movimiento','log_movimiento.id_estado_movimiento','=','estado_movimiento.id_estado_movimiento')
    ->leftJoin('relevamiento_movimiento','relevamiento_movimiento.id_log_movimiento','=','log_movimiento.id_log_movimiento')
    ->where($reglas)
    ->whereIn('log_movimiento.id_casino' , $casinos)
    ->whereNotIn('tipo_movimiento.id_tipo_movimiento',[9]);

    if(!empty($request->fecha)){
      $fecha = explode("-", $request->fecha);
      $resultados = $resultados->whereYear('log_movimiento.fecha' , '=' ,$fecha[0])
      ->whereMonth('log_movimiento.fecha','=', $fecha[1]);
    }
    if(!empty($request->id_log_movimiento)){
      $resultados = $resultados->whereRaw("CAST(log_movimiento.id_log_movimiento as CHAR) regexp ?",'^'.$request->id_log_movimiento);
    }

    $resultados = $resultados->distinct('log_movimiento.id_log_movimiento','expediente.id_expediente','casino.id_casino','tipo_movimiento.id_tipo_movimiento')
    ->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    })
    ->paginate($request->page_size,['log_movimiento.id_log_movimiento','expediente.id_expediente','casino.id_casino','tipo_movimiento.id_tipo_movimiento']);

    $cas = DB::table('casino')
    ->select('casino.id_casino')
    ->whereIn('casino.id_casino' , $casinos)
    ->get();

    return ['logMovimientos' => $resultados, 'casinos' => $cas ];
  }

  public function noEsControlador($id_usuario , $logMov){
    return $logMov->controladores()->where('controlador_movimiento.id_controlador_movimiento',$id_usuario)->count() == 0;
  }

  public function guardarLogMovimientoExpediente($id_expediente,$id_tipo_movimiento){

    $logMovimiento = new LogMovimiento;
    $logMovimiento->fecha= date("Y-m-d");
    $logMovimiento->tiene_expediente = 1;
    $logMovimiento->save();

    $logMovimiento->expediente()->associate($id_expediente);
    $logMovimiento->estado_movimiento()->associate(1);//estado = notificado
    $exp = Expediente::find($id_expediente);
    $logMovimiento->tipo_movimiento()->associate($id_tipo_movimiento);
    $id_usuario = session('id_usuario');
    $logMovimiento->controladores()->attach($id_usuario);
    $logMovimiento->casino()->associate($exp->casinos->first()->id_casino);
    $logMovimiento->save();

    $id_usuario = session('id_usuario');
    // notificaciones
    $usuarios = UsuarioController::getInstancia()->obtenerControladores($logMovimiento->casino->id_casino,$id_usuario);
    foreach ($usuarios as $user){
      $u = Usuario::find($user->id_usuario);
      if($u != null) $u->notify(new NuevoMovimiento($logMovimiento));
    }

    return $logMovimiento;

  }

  public function generarReingreso($id_expediente){
    $logs= LogMovimiento::where('id_expediente','=',$id_expediente)->where('id_tipo_movimiento','<>',8)->get()->first();
    $logs->fecha= date("Y-m-d");
    $logs->tipo_movimiento()->associate(8);//egreso/reingresos
    $logs->save();
    $id_usuario = session('id_usuario');
    if($this->noEsControlador($id_usuario,  $logs)){
      $logs->controladores()->attach($id_usuario);
      $logs->save();
    }

    $usuarios = UsuarioController::getInstancia()->obtenerControladores($logs->casino->id_casino,$id_usuario);
    foreach ($usuarios as $user){
      $u = Usuario::find($user->id_usuario);
     if($u != null) $u->notify(new NuevoMovimiento($logs));
    }
    return $logs;
  }

  //solo cuando es MOVIMIENTO INGRESO
  public function enviarAFiscalizar(Request $request){
    $user_request = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $logMov = null;
    $maquinas = [];
    $validator = Validator::make($request->all(), [
      'id_log_movimiento' => 'required|exists:log_movimiento,id_log_movimiento',
      'maquinas' => 'nullable|array',
      'maquinas.*' => 'required|exists:maquina,id_maquina'
    ], array(), self::$atributos)->after(function ($validator) use ($user_request,&$logMov,&$maquinas){
      if(!$validator->errors()->any()){
        $data = $validator->getData();
        $logMov = LogMovimiento::find($data['id_log_movimiento']);
        if(!$user_request->usuarioTieneCasino($logMov->id_casino)){
          $validator->errors()->add('id_log_movimiento', 'El usuario no puede acceder a ese movimiento.');  
        }
        if(!array_key_exists('maquinas',$data)){
          $validator->errors()->add('maquinas', 'No hay máquinas seleccionadas.'); 
        }
        else foreach($data['maquinas'] as $m){
          $maq = Maquina::find($m);
          if(!$user_request->usuarioTieneCasino($maq->id_casino)){
            $validator->errors()->add('maquinas', 'El usuario no puede acceder a la maquina'.$maq->nro_admin.'.');  
          }
          $maquinas[] = $maq;
        }
      }
    })->validate();

    if(!isset($logMov->fiscalizaciones)){
      $logMov->estado_movimiento()->associate(2);//fiscalizando
    }

    $fiscalizacion = FiscalizacionMovController::getInstancia()->crearFiscalizacion($logMov->id_log_movimiento, false,$request['fecha']);
    foreach($maquinas as $m){
      $m->estado_maquina()->associate(1);//Ingreso
      //crear log maquina
      LogMaquinaController::getInstancia()->registrarMovimiento($m->id_maquina, "MTM enviada a fiscalizar.",1);
      //busco los relevamientos que se crearon para asociarlos a una fiscalizacion
      $relevamiento = RelevamientoMovimiento::where([
        ['id_maquina'       ,'=',$m->id_maquina],
        ['id_log_movimiento','=',$logMov->id_log_movimiento]
      ])->get()->first();
      $relevamiento->fiscalizacion()->associate($fiscalizacion->id_fiscalizacion_movimiento);
      $relevamiento->save();
      // Puede ser que haya agregado progresivos entre la creacion de la maquina y enviar a fiscalizar
      // Por lo que rehago las tomas, lamentablemente se hace asi porque habria que reescribir mucho
      foreach($relevamiento->toma_relevamiento_movimiento as $toma){
        $toma->detalles_relevamiento_progresivo()->delete();
        $toma->delete();
      }
      TomaRelevamientoMovimientoController::getInstancia()->crearTomaRelevamiento($m->id_maquina,$relevamiento->id_relev_mov,[],
      null,null,null,
      null,null,null,
      null,null,null,
      null,null,0);
    }
    $usuarios = UsuarioController::getInstancia()->obtenerFiscalizadores($logMov->casino->id_casino,$user_request->id_usuario);
    foreach ($usuarios as $u){
      $user = Usuario::find($u->id_usuario);
      if($user != null) $user->notify(new RelevamientoGenerado($fiscalizacion));
    }

    $date = date('Y-m-d h:i:s', time());
    $titulo = "Relevamiento Movimientos";
    $descripcion = "El movimiento: ".$logMov->tipo_movimiento->descripcion." con fecha ".$logMov->fecha.", está listo para fiscalizar.";
    CalendarioController::getInstancia()->crearEventoMovimiento($date,$date,$titulo,$descripcion,$logMov->id_casino,$fiscalizacion->id_fiscalizacion_movimiento);
    return 1;
  }

  //para los demás movimientos
  private function enviarAFiscalizar2($id_log_movimiento,$es_reingreso,$fecha){
     //el request envia el log movimiento con las maquinas que se van a relevar efectivamente
     // 'id_log_movimiento', maquinas, maquinas.*.id_maquina
     $logMov = LogMovimiento::find($id_log_movimiento);
     if(!isset($logMov->fiscalizaciones))
     {
       $logMov->estado_movimiento()->associate(2);//fiscalizando
     }
     $fiscalizacion = FiscalizacionMovController::getInstancia()->crearFiscalizacion($logMov->id_log_movimiento,$es_reingreso, $fecha);

     foreach ($logMov->relevamientos_movimientos as $relevamiento) {
        if($relevamiento->fiscalizacion == null){ //por las dudas verifico que sea nulo
           $relevamiento->fiscalizacion()->associate($fiscalizacion->id_fiscalizacion_movimiento);
           $relevamiento->save();
        }
     }
     $id_usuario = session('id_usuario');

     $usuarios = UsuarioController::getInstancia()->obtenerFiscalizadores($logMov->casino->id_casino,$id_usuario);
     foreach ($usuarios as $user){
       $u = Usuario::find($user->id_usuario);

       if($u != null){
       $u->notify(new RelevamientoGenerado($fiscalizacion));}
     }

     $date = date('Y-m-d h:i:s', time());
     $titulo = "Relevamiento Movimientos";
     $descripcion = "El movimiento: ".$logMov->tipo_movimiento->descripcion." con fecha ".$logMov->fecha.", está listo para fiscalizar.";
     CalendarioController::getInstancia()
     ->crearEventoMovimiento($date,$date,$titulo,$descripcion,$logMov->id_casino,$fiscalizacion->id_fiscalizacion_movimiento);

   }

  public function getAll(){
    $todos=LogMovimiento::all();
    return $todos;
  }

  //crear los relevamientos movimientos por cada máquina que el controlador creó  para fiscalizar
  public function guardarRelevamientoMovimientoIngreso($id_log_mov,$id_maquina){
    $logMov = LogMovimiento::find($id_log_mov);
    $logMov->estado_relevamiento()->associate(1);//generado
    $id_usuario = session('id_usuario');
    if($this->noEsControlador($id_usuario,  $logMov)){
      $logMov->controladores()->attach($id_usuario);
    }
    $logMov->cant_maquinas = $logMov->cant_maquinas - 1; // cree una maquina, resto de las que quedan
    $logMov->save();

    $mtm = Maquina::find($id_maquina);
    $this->guardarIslasMovimiento($logMov,$mtm);

    $r = RelevamientoMovimientoController::getInstancia()->crearRelevamientoMovimiento($id_log_mov, $mtm);

    return  $logMov->cant_maquinas;
  }

  //guarda que islas fueron afectadas en el movimiento para que se muestren en
  //los listados
  private function guardarIslasMovimiento($log_mov,$mtm){
    $islas = $log_mov->islas;
    $isla_mtm =$mtm->isla->nro_isla;
    $countiguales = 0;
    if($islas != null){
      $islasArray = explode('-',$islas);
      foreach ($islasArray as $isla) {
        if($isla == $isla_mtm){
          $countiguales++;
        }
      }
      if($countiguales == 0){
        $islas = $islas."-".$mtm->isla->nro_isla."";
      }
    }else{
      $islas = "".$mtm->isla->nro_isla."";
    }
    $log_mov->islas=$islas;
    $log_mov->save();
  }

  private function generarToma2($id_log_movimiento,$maquinas,$fecha){
    $logMov = LogMovimiento::find($id_log_movimiento);
    $logMov->estado_relevamiento()->associate(1);//generado
    $id_usuario = session('id_usuario');
    if($this->noEsControlador($id_usuario,  $logMov)){
      $logMov->controladores()->attach($id_usuario);
      $logMov->save();
    }

    foreach ($maquinas as $maquina) {
      $maq= Maquina::find($maquina['id_maquina']);
      $r = RelevamientoMovimientoController::getInstancia()->crearRelevamientoMovimiento($id_log_movimiento, $maq);
      $this->guardarIslasMovimiento($logMov,$maq);
    }
    $this->enviarAFiscalizar2($id_log_movimiento,true,$fecha);
  }

  //MOVIMIETOS: EGRESO, REINGRESO, CAMBIO LAYOUT

  public function guardarRelevamientosMovimientos(Request $request){
    Validator::make($request->all(), [
        'id_log_movimiento' => 'required|exists:log_movimiento,id_log_movimiento',
        'maquinas' => 'required',
        'maquinas.*.id_maquina' => 'required|exists:maquina,id_maquina',
        'carga_finalizada'=> 'required ',
        'fecha' => 'nullable'
    ], array(), self::$atributos)->after(function ($validator){})->validate();
    if($request['carga_finalizada'] == 'toma2'){
      $this->generarToma2($request['id_log_movimiento'],$request['maquinas'],$request['fecha']);
    }
    $logMov = LogMovimiento::find($request['id_log_movimiento']);
    $logMov->estado_relevamiento()->associate(1);//generado
    $id_usuario = session('id_usuario');
    if($this->noEsControlador($id_usuario,  $logMov)){
      $logMov->controladores()->attach($id_usuario);
      $logMov->save();
    }

    //chequeo si se elimino alguna maquina de la lista
    //ver por que no anda
    if($logMov->id_tipo_movimiento == 7 || $logMov->id_tipo_movimiento == 2){
      foreach ($logMov->relevamientos_movimientos as $rel) {
        if($rel->id_fiscalizacion_movimiento == null &&
          $this->fueEliminada($rel->id_maquina,$request['maquinas'])){

            $rel->maquina()->dissociate();
            $rel->estado_relevamiento()->dissociate();
            $rel->log_movimiento()->dissociate();
            RelevamientoMovimiento::destroy($rel->id_relev_mov);
        }
      }
    }

    foreach ($request['maquinas'] as $maquina) {
      $maq= Maquina::find($maquina['id_maquina']);
      $relevamiento = RelevamientoMovimiento::where([['id_maquina','=', $maq['id_maquina']],['id_log_movimiento','=',$request['id_log_movimiento']]])->get()->first();
      if($relevamiento == null){
        $r = RelevamientoMovimientoController::getInstancia()->crearRelevamientoMovimiento($request['id_log_movimiento'], $maq);
      }
      if($request['es_reingreso']== "true"){
        if(count($relevamiento)<2){
          $r = RelevamientoMovimientoController::getInstancia()->crearRelevamientoMovimiento($request['id_log_movimiento'], $maq);
        }
      }

      $this->guardarIslasMovimiento($logMov,$maq);
    }


    if($request['carga_finalizada'] == 'true' && (!empty($request['maquinas']) || isset($request['maquinas'])))
    {
      $this->enviarAFiscalizar2($request['id_log_movimiento'], $request['es_reingreso'],$request['fecha']);
      if(!isset($logMov->fiscalizaciones))
      {
        $logMov->estado_movimiento()->associate(2);//fiscalizando
      }
    }
    return 1;
  }

  //compara si la maquina id_maquina fue eliminada de $maquinas
  private function fueEliminada($id_maquina,$maquinas){
    $aux = true;
    foreach ($maquinas as $id) {
      if($id_maquina == $id){
        $aux=false;
      }
    }
    return $aux;
  }

  //MOVIMIETOS: DENOMINACION, % DEVOLUCION, JUEGO

  public function datosMaquina($id_maquina){
    $maq = Maquina::find($id_maquina);

    //juegos contiene: id_juego,nombre_juego
    return ['juegos' => $maq->juegos , 'denominacion' => $maq->denominacion, 'porcentaje_devolucion' => $maq->porcentaje_devolucion];
  }

  public function guardarRelevamientosMovimientosMaquinas(Request $req){
      //Auxiliar con un arreglo vacio asi no tengo que chequear exists constantemente
      $maquinas = [];
      $validator = Validator::make($req->all(), [
          'id_log_movimiento' => 'required|exists:log_movimiento,id_log_movimiento',
          'maquinas' => 'nullable',
          'maquinas.*.id_maquina' => 'required|exists:maquina,id_maquina',
          'maquinas.*.id_juego' => 'nullable | exists:juego,id_juego',
          'maquinas.*.porcentaje_devolucion' => ['nullable','regex:/^\d\d?([,|.]\d\d?\d?)?$/'],
          'maquinas.*.denominacion' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
          'maquinas.*.id_unidad_medida' => 'nullable | exists:unidad_medida,id_unidad_medida',
          'carga_finalizada'=> 'required'
      ], array(), self::$atributos)->after(function ($validator) use (&$maquinas){
        $data = $validator->getData();
        if(!$validator->errors()->any()){
          if(array_key_exists('maquinas',$data)){
            foreach ($data['maquinas'] as $maquina) {
              if($this->maquinaDuplicada($data['maquinas'], $maquina['id_maquina'])){
                $validator->errors()->add('id_maquina', 'Se ha cargado más de una vez al menos una máquina.');
              }
              $maquinas[] = $maquina;
            }
          }
        }
      })->validate();
      
      $logMov = LogMovimiento::find($req['id_log_movimiento']);

      $logMov->estado_relevamiento()->associate(1);//generado

      $this->eliminarRelevamientos($logMov->id_log_movimiento);

      $id_usuario = session('id_usuario');
      if($this->noEsControlador($id_usuario,  $logMov)){
        $logMov->controladores()->attach($id_usuario);
        $logMov->save();
      }

      switch ($logMov->id_tipo_movimiento) {
        case 5: //denominacion
          foreach ($maquinas as $maquina)
          {
            $logMov = LogMovimiento::find($logMov->id_log_movimiento);
            // el cambio de denominacion por procedimiento es la denominacion de juego, se comenta esta funcionalidad que afectaba a la mtm
            // MTMController::getInstancia()->modificarDenominacionYUnidad($maquina['id_unidad_medida'],$maquina['denominacion'],$maquina['id_maquina']);
            MTMController::getInstancia()->modificarDenominacionJuego($maquina['denominacion'],$maquina['id_maquina']);
            $maq= Maquina::find($maquina['id_maquina']);
            // TODO evaluar el caso de dos relevamientos para la misma mtm
            if($this->noTieneRelevamientoCreado($maquina['id_maquina'],$req['id_log_movimiento']))
            {
              $r = RelevamientoMovimientoController::getInstancia()->crearRelevamientoMovimiento($req['id_log_movimiento'], $maq);
              $this->guardarIslasMovimiento($logMov,$maq);
            }
          }
          break;
        case 6: //% devolucion
          foreach ($maquinas as $maquina)
          {
            $logMov = LogMovimiento::find($logMov->id_log_movimiento);
            // el cambio de %dev por procedimiento es la denominacion de juego, se comenta esta funcionalidad que afectaba a la mtm
            // MTMController::getInstancia()->modificarDevolucion($maquina['porcentaje_devolucion'],$maquina['id_maquina']);
            MTMController::getInstancia()->modificarDevolucionJuego($maquina['porcentaje_devolucion'],$maquina['id_maquina']);

            $maq= Maquina::find($maquina['id_maquina']);
            if($this->noTieneRelevamientoCreado($maquina['id_maquina'],$req['id_log_movimiento']))
            {
              $r = RelevamientoMovimientoController::getInstancia()->crearRelevamientoMovimiento($req['id_log_movimiento'], $maq);
              $this->guardarIslasMovimiento($logMov,$maq);
            }
          }
          break;
        case 7: //juego
          foreach ($maquinas as $maquina)
          {
            $logMov = LogMovimiento::find($logMov->id_log_movimiento);
            MTMController::getInstancia()->modificarJuegoConDenYPorc($maquina['id_juego'],$maquina['id_maquina'],$maquina['denominacion'],$maquina['porcentaje_devolucion']);
            $maq= Maquina::find($maquina['id_maquina']);
            if($this->noTieneRelevamientoCreado($maquina['id_maquina'],$req['id_log_movimiento']))
            {
              $r = RelevamientoMovimientoController::getInstancia()->crearRelevamientoMovimiento($req['id_log_movimiento'], $maq);
              $this->guardarIslasMovimiento($logMov,$maq);
            }
          }
          break;
        default:
          break;
      }

     if($req['carga_finalizada'] == 'true'){
        $this->enviarAFiscalizar2($req['id_log_movimiento'],"false",$req['fecha']); //false porque no es reingreso
        if(!isset($logMov->fiscalizaciones))
        {
          $logMov->estado_movimiento()->associate(2);//fiscalizando
        }
      }
      $logMov->save();
      return 1;
    }

    private function maquinaDuplicada($maquinas, $id_maquina){
      $aux = 0;
      foreach ($maquinas as $maquina) {
        if($maquina['id_maquina'] == $id_maquina){
          $aux++;
        }
        if($aux>1){
          return true;
        }
      }
      return false;
    }

    private function noTieneRelevamientoCreado($id_maquina,$id_log_movimiento){
      $maquina = RelevamientoMovimiento::where([['id_log_movimiento','=',$id_log_movimiento],['id_maquina','=',$id_maquina]])->get()->first();
      if($maquina==null){
        return true;
      }
      return false;
    }

  //en los fiscalizadores////////////////////////////////////////////////////////
  //solo se mostraran las ultimas 25 fiscalizaciones
  public function obtenerFiscalizaciones($id_casino = 0){
    $casinos= array();
    if($id_casino == 0){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      foreach($usuario->casinos as $casino){
            $casinos [] = $casino->id_casino;
      }
    }else{
      $casinos[] = $id_casino;
    }

    $tiposMovimientos = TipoMovimiento::all();

    UsuarioController::getInstancia()->agregarSeccionReciente('Relevamientos Movimientos','relevamientos_movimientos');

    return view('seccionRelevamientosMovimientos',
    ['casinos' => $usuario->casinos,
    'tipos_movimientos' => $tiposMovimientos,
    'causasNoTomaProgresivo' => TipoCausaNoTomaProgresivo::all()]);
  }

  //para poder realizar la carga de los datos
  public function obtenerRelevamientosFiscalizacion($id_fiscalizacion_movimiento){//@DEPRECATED
    $maquinas = DB::table('fiscalizacion_movimiento')
                  ->select('maquina.id_maquina','maquina.nro_admin','maquina.id_casino','relevamiento_movimiento.id_estado_relevamiento')
                  ->join('relevamiento_movimiento','relevamiento_movimiento.id_fiscalizacion_movimiento','=','fiscalizacion_movimiento.id_fiscalizacion_movimiento')
                  ->join('maquina','maquina.id_maquina','=','relevamiento_movimiento.id_maquina')
                  ->where('fiscalizacion_movimiento.id_fiscalizacion_movimiento','=',$id_fiscalizacion_movimiento)
                  ->get();
    $fiscalizacion = FiscalizacionMov::find($id_fiscalizacion_movimiento);


    $id_usuario = session('id_usuario');
    $user = Usuario::find($id_usuario);
    $casino=$maquinas[0]->id_casino;
    return ['relevamientos' => $maquinas ,'cargador' => $user, 'casino' =>$casino, 'usuario_fiscalizador' => $fiscalizacion->fiscalizador];
  }

  //Formato nuevo de lo mismo de arriba, mas parecido a relevamientosEvMTM
  public function obtenerRelevamientosFiscalizacion2($id_fiscalizacion_movimiento){
    $fiscalizacion = FiscalizacionMov::find($id_fiscalizacion_movimiento);
    $log = $fiscalizacion->log_movimiento;
    $relevamientos = $fiscalizacion->relevamientos_movimientos;
    $relevamientos_arr = array();
    foreach ($relevamientos as $rel) {
      $relevamientos_arr[] =  [
                            'id_relevamiento' => $rel->id_relev_mov,
                            'estado'          => $rel->estado_relevamiento,
                            'nro_admin'       => $rel->maquina->nro_admin,
                            'id_maquina'      => $rel->maquina->id_maquina,
                            'tomas'           => $rel->toma_relevamiento_movimiento()->count()
                          ];
    }
    $id_usuario = session('id_usuario');
    $user = Usuario::find($id_usuario);
    return ['relevamientos' => $relevamientos_arr,'cargador' => $user,'fiscalizador' => $fiscalizacion->fiscalizador,
            'tipo_movimiento' => $log->tipo_movimiento->descripcion, 'sentido' => $log->sentido, 
            'casino' => $log->casino, 'fiscalizacion' => $fiscalizacion];
  }

  public function generarPlanillasRelevamientoMovimiento($id_fiscalizacion_movimiento){
    $fiscalizacionMov = FiscalizacionMov::find($id_fiscalizacion_movimiento);
    $logMov = $fiscalizacionMov->log_movimiento;
    if(!isset($logMov->fiscalizaciones))
    {
      $logMov->estado_movimiento()->associate(2);//fiscalizando
    }
    $logMov->save();
    $casino = $logMov->casino;

    $relevamientos = array();
    $relController = RelevamientoMovimientoController::getInstancia();
    foreach($fiscalizacionMov->relevamientos_movimientos as $idx => $relev){
      $relevamientos[] = $relController->generarPlanillaMaquina(
        $relev,
        $logMov->tipo_movimiento->descripcion,
        $logMov->sentido,
        $casino,
        $fiscalizacionMov->fecha_envio_fiscalizar,
        $fiscalizacionMov->id_estado_relevamiento
      );
    }

    $tipo_planilla = "movimientos";
    $view = View::make('planillaMovimientos', compact('relevamientos','tipo_planilla'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $casino->codigo."/".$fiscalizacionMov->fecha_envio_fiscalizar, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));

  }

  //tipo: si es 1 = es nueva la planilla, si es 2 es que se imprime con la carga completa
  public function imprimirEventualidadMTM($id_log_mov){
    $log = LogMovimiento::find($id_log_mov);
    $casino = $log->casino;

    $relevamientos= array();
    $relController = RelevamientoMovimientoController::getInstancia();
    foreach ($log->relevamientos_movimientos as $idx => $relev) {
      $relevamientos[] = $relController->generarPlanillaMaquina(
        $relev,
        $log->tipo_movimiento->descripcion,
        $log->sentido,
        $casino,
        $log->fecha,
        $log->id_estado_relevamiento
      );
    }

    $tipo_planilla = "intervenciones";
    $view = View::make('planillaMovimientos', compact('relevamientos','tipo_planilla'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $casino->codigo."/".$log->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }

  // 2/03/2020 - Octavio
  // Si alguna vez ocurre que hay progresivos con mas de 6 niveles, no van a ser mas validos muchos de los algoritmos
  // (trate de cambiarlo pero puede ser que se me haya quedado colgado algo en frontend)
  // Esto va a suceder porque si bien se tenia en cuenta que sea expandible en un principio
  // hay dos limitaciones:
  // - Detalle progresivo solo tiene 6 valores (trivial de solucionar, modificar la estructura de la tabla y cambiar los limites)
  // - Los ADM cargaron todos los niveles al reves (al nivel mas alto le asignaron el 1), el resultado es que no 
  //   son homogeneos los progresivos (si un progresivo tiene 4 niveles, su nivel mas alto tiene el valor nro_nivel 1
  //   y si otro tiene 6 niveles, su nivel mas alto tiene el valor nro_nivel 1 tambien).
  // Este ultimo punto no fue considerado mucho a la hora de programar la parte de relevamiento de progresivos
  // porque paso en produccion (aunque los ADM fueron debidamente informados con un manual). 
  // La idea es hacerlo a partir de ahora para que en el caso de que ocurra no haya que cambiar muchas cosas
  // Ej tabla detalle_relevamiento_progresivo
  // nivel1 | nivel2 | nivel3 | nivel4 | nivel5 | nivel6
  // 6      | 5      | 4      | 3      | 2      | 1
  // 4      | 3      | 2      | 1      | null   | null
  // 7      | 6      | 5      | 4      | 3      | 2     <- te quedas corto, agregar una columna nivel7
  // AHORA, si bien CONCEPTUALMENTE los niveles 1 en realidad son los maximos, en la bd estan cargados con nro_nivel = 1
  // por lo que se los puede asignar directamente, pero hay que tener en cuenta lo de la tercer fila 
  // y si en algun momento se hacen informes/pruebas.
  public function cargarTomaRelevamiento(Request $request){
    $validator =Validator::make($request->all(), [
        'id_relev_mov' => 'required|integer|exists:relevamiento_movimiento,id_relev_mov',
        'toma' => 'required|integer|min:0',
        'id_cargador' => 'nullable|integer|exists:usuario,id_usuario',
        'id_fiscalizador' => 'required|integer|exists:usuario,id_usuario',
        'contadores' => 'required',
        'contadores.*.nombre' =>'nullable',
        'contadores.*.valor' => ['required_with:contadores.*.nombre','regex:/^\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'juego' => 'required|integer|exists:juego,id_juego',
        'apuesta_max' => 'required|numeric|max:900000',
        'cant_lineas' => 'required|numeric|max:100000',
        'porcentaje_devolucion' => ['required','regex:/^\d\d?([,|.]\d\d?\d?)?$/'],
        'denominacion' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'cant_creditos' => 'required|numeric|max:100',
        'fecha_sala' => 'required|date',
        'observaciones' => 'nullable|max:280',
        'mac' => 'nullable|max:100',
        'sector_relevado' => 'required',
        'isla_relevada' => 'required',
        'progresivos' => 'sometimes|array',
        'progresivos.*.id_pozo' => 'required|integer|exists:pozo,id_pozo',
        'progresivos.*.id_tipo_causa_no_toma_progresivo' => 'nullable|integer|exists:tipo_causa_no_toma_progresivo,id_tipo_causa_no_toma_progresivo',
        'progresivos.*.niveles' => 'sometimes|array',
        'progresivos.*.niveles.*.id_nivel_progresivo' => 'nullable|integer|exists:nivel_progresivo,id_nivel_progresivo',
        'progresivos.*.niveles.*.val' => 'nullable|numeric|min:0'
    ], array(), self::$atributos)->after(function($validator){
      if(!$validator->errors()->any()){
        if($validator->getData()['juego']==0 ){
            $validator->errors()->add('juego', 'No se ha seleccionado el juego.');
        }
        $data = $validator->getData();
        $relevamiento = RelevamientoMovimiento::find($data['id_relev_mov']);
        $fiscalizacion = $relevamiento->fiscalizacion;
        $fecha_limite_inferior = null;
        $fecha_sala = strtotime($data['fecha_sala']);
        if(!is_null($fiscalizacion)) $fecha_limite_inferior = $fiscalizacion->fecha_envio_fiscalizar;
        else $fecha_limite_inferior = $relevamiento->log_movimiento->fecha;
        $fecha_limite_inferior = strtotime($fecha_limite_inferior);
        if($fecha_sala < $fecha_limite_inferior) $validator->errors()->add('fecha_sala','validation.after');
        if($fecha_sala > time()) $validator->errors()->add('fecha_sala','validation.before');

        $progresivos = [];
        if(array_key_exists('progresivos',$data)) $progresivos = $data['progresivos'];
        foreach($progresivos as $idx_p => $p){
          $pozo = Pozo::find($p['id_pozo']);
          $causaNoToma = $p['id_tipo_causa_no_toma_progresivo'];
          if(is_null($causaNoToma)){
            $valores = [];
            foreach($pozo->niveles as $idx => $n){
              $valores[$n->id_nivel_progresivo] = -$n->nro_nivel;
            }
            foreach($p['niveles'] as $n){
              if(!is_null($n['val'])) $valores[$n['id_nivel_progresivo']] = $n['val'];
            }
            $strprog = 'progresivos.'.$idx_p;
            foreach($valores as $id => $val){
              if($val<0){
                $validator->errors()->add($strprog.'.niveles.'.(-$val-1).'.val','validation.required');
              }
            }
          }
        }
      }
    })->validate();
    $fisFinalizada = false;
    $movFinalizado = false;
    DB::beginTransaction();
    try{ // @TODO: Agregar una forma de guardar temporalmente, estado mov 8, estado rel 2
      $nro_toma = $request->toma <= 0? 1 : $request->toma;
      RelevamientoMovimientoController::getInstancia()->cargarTomaRelevamientoProgs(
        $request->id_relev_mov,
        $nro_toma,
        $request->id_cargador,
        $request->id_fiscalizador,
        $request['fecha_sala'],
        $request['mac'],
        $request['sector_relevado'],
        $request['isla_relevada'],
        $request['contadores'],
        $request['juego'],
        $request['apuesta_max'],
        $request['cant_lineas'] ,
        $request['porcentaje_devolucion'],
        $request['denominacion'],
        $request['cant_creditos'],
        $request['progresivos'],
        $request['observaciones']
      );

      $relevamiento = RelevamientoMovimiento::find($request->id_relev_mov);
      $fiscalizacion = $relevamiento->fiscalizacion;
      $logMov = $relevamiento->log_movimiento;
      $es_intervencion = is_null($fiscalizacion);
      if(!$es_intervencion){
        if($fiscalizacion->relevamientos_movimientos()->count() // 3 = finalizado
        == $fiscalizacion->relevamientos_movimientos()->whereIn('relevamiento_movimiento.id_estado_relevamiento',[3])->count()){
          $fiscalizacion->estado_relevamiento()->associate(3); // Finalizado
          $fisFinalizada = true;
        }
        else{
          $fiscalizacion->estado_relevamiento()->associate(2); // Cargando
        }
        $fiscalizacion->save();
      }

      if($logMov->relevamientos_movimientos()->count() // 3 = finalizado
      == $logMov->relevamientos_movimientos()->whereIn('relevamiento_movimiento.id_estado_relevamiento',[3])->count()){
        $logMov->estado_relevamiento()->associate(3); // Finalizado
        $logMov->estado_movimiento()->associate(1);  // Notificado
        $movFinalizado = true;
      }
      else{
        $logMov->estado_relevamiento()->associate(2); // Cargando
        $logMov->estado_movimiento()->associate(2); // Fiscalizando 
      }
      $logMov->save();

      if($fisFinalizada || $movFinalizado){
        $id_usuario = session('id_usuario');
        $usuarios = UsuarioController::getInstancia()->obtenerControladores($logMov->casino->id_casino, $id_usuario);
        $notificacion = $es_intervencion? 'NuevaIntervencionMTM' : 'RelevamientoCargado'; //Estos son TIPOS de PHP
        foreach ($usuarios as $user){
          $u = Usuario::find($user->id_usuario);
          if($u != null) $u->notify(new $notificacion($fiscalizacion));
        }
        if(!$es_intervencion) CalendarioController::getInstancia()->marcarRealizado($fiscalizacion->evento);
      }
      DB::commit();
    }
    catch(Exception $e){
      DB::rollBack();
      throw $e;
      return ['codigo' => 0];
    }
    return ['fisFinalizada' => $fisFinalizada, 'movFinalizado' => $movFinalizado];
  }

  public function crearPlanillaEventualidades(){// CREAR Y GUARDAR RELEVAMIENTO
    $view = View::make('planillaEventualidades');
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    // $dompdf->getCanvas()->page_text(20, 815, (($rel->nro_relevamiento != null) ? $rel->nro_relevamiento : "AUX")."/".$rel->casinoCod."/".$rel->sector."/".$rel->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf;
  }

  public function crearPlanillaMovimientos(){// CREAR Y GUARDAR RELEVAMIENTO
    $view = View::make('planillaMovimientos');
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    // $dompdf->getCanvas()->page_text(20, 815, (($rel->nro_relevamiento != null) ? $rel->nro_relevamiento : "AUX")."/".$rel->casinoCod."/".$rel->sector."/".$rel->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf;

  }

  //para el controlador///////////////////////////////////////////////////////
  //deberia enviar las cosas suficientes como para poder mostrar para validar
  public function obtenerFiscalizacionesMovimiento($id_log_movimiento){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      foreach($usuario->casinos as $casino){
            $casinos [] = $casino->id_casino;
      }
      $fiscalizaciones = DB::table('log_movimiento')
                          ->select('fiscalizacion_movimiento.*', 'fiscalizacion_movimiento.id_estado_relevamiento as id_estado_fiscalizacion')
                          ->join('fiscalizacion_movimiento','fiscalizacion_movimiento.id_log_movimiento','=','log_movimiento.id_log_movimiento')
                          ->where('log_movimiento.id_log_movimiento','=',$id_log_movimiento)
                          ->whereIn('log_movimiento.id_casino',$casinos)
                          ->get();
      return $fiscalizaciones;
  }

  public function ValidarMaquinaFiscalizacion($id_relevamiento){
    $relev = RelevamientoMovimiento::find($id_relevamiento);
    $fiscalizacionMov = FiscalizacionMov::find($relev->id_fiscalizacion_movimiento);
    $toma = DB::table('relevamiento_movimiento')
                    ->select('maquina.*','toma_relev_mov.*','formula.*','juego.nombre_juego','relevamiento_movimiento.id_estado_relevamiento')
                    ->join('toma_relev_mov', 'toma_relev_mov.id_relevamiento_movimiento','=','relevamiento_movimiento.id_relev_mov')
                    ->join('maquina','maquina.id_maquina','=','relevamiento_movimiento.id_maquina')
                    ->join('formula','formula.id_formula','=', 'maquina.id_formula')
                    ->join('juego','toma_relev_mov.juego','=', 'juego.id_juego')
                    ->where('relevamiento_movimiento.id_relev_mov','=',$id_relevamiento)
                    ->where('toma_relev_mov.toma_reingreso','=',0)
                    ->get()
                    ->first();

    $toma1=null;
    if($fiscalizacionMov->es_reingreso == 1 ){
      $toma1 = FiscalizacionMovController::getInstancia()->buscarTomaEgreso($fiscalizacionMov->id_fiscalizacion_movimiento, $fiscalizacionMov->id_log_movimiento,$relev->id_maquina);
    }else{
      $toma2 = DB::table('relevamiento_movimiento')
                      ->select('maquina.*','toma_relev_mov.*','formula.*','juego.nombre_juego','relevamiento_movimiento.id_estado_relevamiento')
                      ->join('toma_relev_mov', 'toma_relev_mov.id_relevamiento_movimiento','=','relevamiento_movimiento.id_relev_mov')
                      ->join('maquina','maquina.id_maquina','=','relevamiento_movimiento.id_maquina')
                      ->join('formula','formula.id_formula','=', 'maquina.id_formula')
                      ->join('juego','toma_relev_mov.juego','=', 'juego.id_juego')
                      ->where('relevamiento_movimiento.id_relev_mov','=',$id_relevamiento)
                      ->where('toma_relev_mov.toma_reingreso','=',1)
                      ->get()
                      ->first();
        if(count($toma2) == 1){
          $toma1 = $toma2;
        }
    }

    //hacer lo de asignar el coinciden de juego, devolucion, denomicsfgd
    $logMov = LogMovimiento::find($fiscalizacionMov->id_log_movimiento);
    $maquina = Maquina::find($relev->id_maquina);
    $n_juego = Juego::find($maquina->id_juego);

    $coinciden_juego= 1;
    $coinciden_denominacion= 1;
    $coinciden_devolucion= 1;
    switch($logMov->id_tipo_movimiento){
      case 5://denominacion
        if($toma->denominacion != $maquina->denominacion){
          $coinciden_denominacion = 0;
        }
        break;
      case 7://juego
        if($toma->juego !=  $maquina->id_juego){
          $coinciden_juego = 0;
        }
        break;
      case 6://% DEVOLUCION
        if($toma->porcentaje_devolucion !=  $maquina->porcentaje_devolucion){
          $coinciden_devolucion = 0;
        }
        break;
      default:
        break;
    }
    return ['toma' => $toma,
          'toma1' => $toma1,
          'coinciden_denominacion' => $coinciden_denominacion,
          'coinciden_juego' => $coinciden_juego,
          'coinciden_devolucion' => $coinciden_devolucion,
          'n_juego' => $n_juego->nombre_juego,
          'n_denominacion' => $maquina->denominacion,
          'n_devolucion' => $maquina->porcentaje_devolucion,
          'cargador'=> $relev->cargador,
          'fiscalizador'=> $relev->fiscalizador];
  }

  //Busca todas las máquinas que concuerdan con el movimiento hecho
  public function buscarMaquinasMovimiento($id_log_movimiento){

    $maquinas =MTMController::getInstancia()->buscarMaquinasPorLogMovimiento($id_log_movimiento);
    $unidades = DB::table('unidad_medida')->select('unidad_medida.*')->get();
    $maquinasYJuegos = array();
    foreach ($maquinas as $maq) {
        $mtmm=  Maquina::find($maq->id_maquina);
      	$juego_select = $mtmm->juego_activo();
      	$juegos = $mtmm->juegos;
        $maquinasYJuegos[]= ['maquina'=>$maq,'juegos'=> $juegos, 'juego_seleccionado' => $juego_select];
    }

    return ['maquinas' => $maquinasYJuegos,'unidades' => $unidades];
  }

  //cuando busca las maquinas para reingresar, teniendo en cuenta que el tipo de mov en el log mov ya es EGRESO/REINGRESO
  public function buscarMaquinasParaReingreso($busqueda, $id_log_mov){
    $resultados = DB::table('relevamiento_movimiento')
                  ->select('maquina.id_maquina','maquina.nro_admin')
                  ->join('maquina','maquina.id_maquina','=', 'relevamiento_movimiento.id_maquina')
                  ->join('log_movimiento', 'log_movimiento.id_log_movimiento','=', 'relevamiento_movimiento.id_log_movimiento')
                  ->where('maquina.nro_admin','like',$busqueda.'%')
                  ->where('relevamiento_movimiento.id_log_movimiento' , $id_log_mov)
                  ->take(25)
                  ->get();
  }

  private function countMaquinasValidadas($relevamientos_movimientos){
    $contador = 0;
    foreach ($relevamientos_movimientos as $relev) {
      if(($relev->id_estado_relevamiento == 4 || $relev->id_estado_relevamiento == 6)
         ||
         is_null($relev->maquina) //Puede ser borrada la maquina..., creo que esto andaria (sin probar)
      ){
        $contador++;
      }
    }
    return $contador;
  }

  //cada vez que el controlador hace click en el icono que redirecciona a gestionar islas/maquinas:
  public function guardarLogClickMov(Request $req){
    $id_usuario = session('id_usuario');
    $logMov = LogMovimiento::find($req['id_log_movimiento']);
    $logMov->estado_relevamiento()->associate(1);//generado
    if(!isset($logMov->fiscalizaciones))
    {
      $logMov->estado_movimiento()->associate(2);//fiscalizando
    }
    $logMov->save();
    if($this->noEsControlador($id_usuario,  $logMov)){
      $logMov->controladores()->attach($id_usuario);
      $logMov->save();
    }

    LogClicksMovController::getInstancia()->guardar($req['id_log_movimiento']);
    return 1;
  }

  public function mostrarMaquinasMovimientoLogClick($id_log_movimiento){
    $logMov = LogMovimiento::find($id_log_movimiento);
    $maquinas = array();

    $maquinasClick = DB::table('maquina')
                        ->select('maquina.*','isla.*','juego.*')
                        ->join('movimiento_isla','movimiento_isla.id_maquina','=','maquina.id_maquina')
                        ->join('log_clicks_mov','log_clicks_mov.fecha','=','movimiento_isla.fecha')
                        ->join('isla','isla.id_isla','=','maquina.id_isla')
                        ->join('log_movimiento','log_movimiento.id_log_movimiento','=','log_clicks_mov.id_log_movimiento')
                        ->join('juego','juego.id_juego','=','maquina.id_juego')
                        ->where('log_movimiento.id_log_movimiento','=', $id_log_movimiento)
                        ->where('isla.id_casino','=',$logMov->id_casino)
                        ->distinct('maquina.id_maquina')
                        ->get();
    $maquinasPausa = DB::table('relevamiento_movimiento')
                        ->select('maquina.*','isla.*','juego.*')
                        ->join('maquina','relevamiento_movimiento.id_maquina','=','maquina.id_maquina')
                        ->join('isla','isla.id_isla','=','maquina.id_isla')
                        ->join('juego','juego.id_juego','=','maquina.id_juego')
                        ->where('relevamiento_movimiento.id_log_movimiento','=', $id_log_movimiento)
                        ->where('isla.id_casino','=',$logMov->id_casino)
                        ->whereNull('relevamiento_movimiento.id_fiscalizacion_movimiento')
                        ->distinct('maquina.id_maquina')
                        ->get();

    //si no se guardaron maquinas en pausa O si la cantidad de mtm en pausa es menor
    //que la cantidad de click ->lo que quiere decir es que se automatizaron mas
    // despues de la posible pausa y entonces retorna las automatizadas
    if(!empty($maquinasPausa) && count($maquinasPausa) < count($maquinasClick))
    {
      return $maquinasClick;
    }else{//las mtm automatizadas son menor o igual que las de pausa
      return $maquinasPausa;
    }
    //deberia mostrar las maquinas y la isla a la que pertenecen con el cambio hecho
  }

  public function guardarTipoCargaYCantMaq(Request $req){
    $logMov = null;
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    
    Validator::make($req->all(), [
      'id_log_movimiento' => 'required|exists:log_movimiento,id_log_movimiento',
      'tipoCarga' => 'required|integer|in:1,2',
      'cantMaq' => 'nullable|required_if:tipoCarga,1|integer|min:1'
    ], array(), self::$atributos)->after(function ($validator) use (&$logMov,$usuario){
      if(!$validator->errors()->any()){
        $data = $validator->getData();
        $logMov = LogMovimiento::find($data['id_log_movimiento']);
        if(!$usuario->usuarioTieneCasino($logMov->id_casino)){
          $validator->errors()->add('id_casino','El usuario no puede acceder a ese casino.');
        }
      }
    })->validate();
    
    if($this->noEsControlador($usuario->id_usuario,  $logMov)){
      $logMov->controladores()->attach($usuario->id_usuario);
      $logMov->save();
    }
    $logMov->tipo_carga = $req['tipoCarga'];
    $logMov->cant_maquinas= $req['cantMaq'];
    $logMov->estado_movimiento()->associate(8);//cargando
    $logMov->save();
    return $logMov;
  }

  public function bajaMTMs(Request $request){
    Validator::make($request->all(), [
        'maquinas' => 'required',
        'maquinas.*.id_maquina' => 'required|exists:maquina,id_maquina'
    ], array(), self::$atributos)->after(function ($validator){
    })->validate();

    foreach ($request['maquinas'] as $maq) {
      MTMController::getInstancia()->eliminarMTM($maq['id_maquina']);
    }
    return 1;
  }

/////////////////////////////////////////////////////MOVIMIENTOS SIN EXPEDIENTE

  public function casinosYMovimientosIngresosEgresos(){
    $id_usuario = session('id_usuario');
    $cas= UsuarioController::getInstancia()->buscarCasinosDelUsuario($id_usuario);
    $t=TipoMovimiento::whereIn('id_tipo_movimiento',[11,12])->get();
    return ['casinos' =>$cas,
            'tipos_movimientos' => $t];
  }

  // Llamado desde ASIGNACION y desde el nuevo cambio (Enero 2020)
  // es SOLO para INGRESOS INICIALES y EGRESOS DEFINITIVOS
  public function nuevoLogMovimiento(Request $request){
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $validator =Validator::make($request->all(),
    [
      'id_tipo_movimiento' => 'required|exists:tipo_movimiento,id_tipo_movimiento',
      'id_casino' => 'required|exists:casino,id_casino'
    ], array(), self::$atributos)->after(function($validator) use ($user){
      if(!$validator->errors()->any()){
        $data = $validator->getData();
        $tipo_mov = TipoMovimiento::find($data['id_tipo_movimiento']);
        $id_casino = $data['id_casino'];
        // Lo que hago es verificar que no sea una intervencion MTM, 
        // por si en algun futuro agregan otro que no sea uno de estos 2.
        if($tipo_mov->es_intervencion_mtm){
          $validator->errors()->add('id_tipo_movimiento', 'No se permite ese tipo de movimiento con esta operacion.');
        }
        if(!$user->usuarioTieneCasino($id_casino)){
          $validator->errors()->add('id_casino','El usuario no puede acceder a ese casino.');
        }
      }
    })->validate();

    $log = null;
    DB::transaction(function () use ($request, $user,&$log) {
        //creo un expedienteAux para que cuando el movimiento se muestre en la lista no tenga problemas y se muestre
        //hay que crearlo a pata en la base de datos del sistema que funciona con los casinos
        $expedienteAux = Expediente::where(
            [
                ['concepto', '=', 'expediente_auxiliar_para_movimientos'],
                ['id_casino', '=', $request['id_casino']],
            ]
        )->get()->first();

        $logMovimiento = new LogMovimiento;
        $logMovimiento->tiene_expediente = 0;
        $logMovimiento->estado_movimiento()->associate(1); //estado = notificado
        $logMovimiento->tipo_movimiento()->associate($request['id_tipo_movimiento']);
        // Los movimientos de INGRESO INICIAL / EGRESO DEFINITIVO no tienen un sentido
        $logMovimiento->sentido = "---";
        $f = date("Y-m-d");
        $logMovimiento->fecha = $f;
        $logMovimiento->casino()->associate($request['id_casino']);
        $logMovimiento->expediente()->associate($expedienteAux->id_expediente);
        $logMovimiento->save();
        $logMovimiento->controladores()->attach($user->id_usuario);
        $logMovimiento->save();

        $log = DB::table('log_movimiento')
            ->select('log_movimiento.*', 'tipo_movimiento.descripcion', 'casino.id_casino', 'expediente.*')
            ->join('tipo_movimiento', 'tipo_movimiento.id_tipo_movimiento', '=', 'log_movimiento.id_tipo_movimiento')
            ->join('casino', 'casino.id_casino', '=', 'log_movimiento.id_casino')
            ->join('expediente', 'expediente.id_expediente', '=', 'log_movimiento.id_expediente')
            ->where('log_movimiento.id_log_movimiento', '=', $logMovimiento->id_log_movimiento)
            ->get()->first();
    });
    return response()->json($log);
  }

  public function asociarExpediente($id_log_movimiento, $id_expediente){
    $logMovimiento = LogMovimiento::find($id_log_movimiento);
    $logMovimiento->expediente()->associate($id_expediente);
    $logMovimiento->tiene_expediente = 1;
    $logMovimiento->save();
    //Debe asociarselo a las maquinas del movimiento tambien:
    if(isset($logMovimiento->relevamientos_movimientos))
    {
      foreach($logMovimiento->relevamientos_movimientos as $relev){
        $mtm = $relev->maquina;
        MTMController::getInstancia()->asociarExpediente($mtm->id_maquina, $id_expediente);
      }
    }

    return $logMovimiento;
  }

  public function eliminarRelevamientos($id_log_movimiento){
    $log = LogMovimiento::find($id_log_movimiento);
    if(is_null($log)) return 0;

    $relController = RelevamientoMovimientoController::getInstancia();
    DB::transaction(function() use ($log,$relController){
      foreach($log->relevamientos_movimientos as $rel){
        $relController->eliminarRelevamiento($rel->id_relev_mov);
      }
      $log->save();
    });
    return 1;
  }

  
  public function eliminarMov($id_log_movimiento,
    $eliminarConFiscalizaciones = false,$eliminarConRelevamientos = false,$eliminarConExpediente = false)
  {
    $log = LogMovimiento::find($id_log_movimiento);
    if($log->fiscalizaciones()->count() > 0 && !$eliminarConFiscalizaciones) return 0;
    if($log->relevamientos_movimientos()->count() > 0 && !$eliminarConRelevamientos) return 0;
    if($log->tiene_expediente == 1 && !$eliminarConExpediente) return 0;
    DB::transaction(function() use($log){
      $log->tipo_movimiento()->dissociate();
      $log->estado_movimiento()->dissociate();
      $log->expediente()->dissociate();
      $log->controladores()->detach();
      foreach ($log->relevamientos_movimientos as $rel) {
        $rel->log_movimiento()->dissociate();
        $rel->fiscalizacion()->dissociate();
        foreach($rel->toma_relevamiento_movimiento as $toma){
          foreach($toma->detalles_relevamiento_progresivo as $detProg){
            $detProg->delete();
          }
          $toma->delete();
        }
        RelevamientoMovimiento::destroy($rel->id_relev_mov);
      }
      foreach($log->fiscalizaciones as $f){
        $f->delete();
      }
      LogClicksMovController::getInstancia()->eliminar($log->id_log_movimiento);
      LogMovimiento::destroy($log->id_log_movimiento);
    });
    return 1;
  }

  public function eliminarEventualidadMTM(Request $request){
    $validator = Validator::make($request->all(),
    ['id_log_movimiento' => 'required|exists:log_movimiento,id_log_movimiento'], array(), self::$atributos)
    ->after(function($validator){
      if(!$validator->errors()->any()){ //Si el log_movimiento existe
        $data = $validator->getData();
        $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
        $log = LogMovimiento::find($data['id_log_movimiento']);
        if(!$user->usuarioTieneCasino($log->id_casino)){
          $validator->errors()->add('id_casino','El usuario no puede acceder a ese casino.');
        }
      }
    })->validate();
    return $this->eliminarMov($request->id_log_movimiento,true,true,false);
  }

  /*
    Unicamente se usa desde el boton eliminar que aparece en el tipo de mov INGRESO
    si no tiene relevamientos creados o no tiene expediente, se puede eliminar
  */
  public function eliminarMovimiento(Request $request){
    $validator = Validator::make($request->all(),
    ['id_log_movimiento' => 'required|exists:log_movimiento,id_log_movimiento'], array(), self::$atributos)
    ->after(function($validator){
      if(!$validator->errors()->any()){ //Si el log_movimiento existe
        $data = $validator->getData();
        $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
        $log = LogMovimiento::find($data['id_log_movimiento']);
        if(!$user->usuarioTieneCasino($log->id_casino)){
          $validator->errors()->add('id_casino','El usuario no puede acceder a ese casino.');
        }
        if($log->relevamientos_movimientos->count() > 0){
          $validator->errors()->add('id_log_movimiento','El movimiento ya fue enviado a fiscalizar.');
        }
        if($log->tiene_expediente == 1){
          $validator->errors()->add('id_log_movimiento','El movimiento ya tiene asignado un expediente.');
        }
      }
    })->validate();

    return $this->eliminarMov($request->id_log_movimiento,false,false,false);
  }

  public function eliminarMovimientoExpediente($id_log_movimiento){
    return $this->eliminarMov($id_log_movimiento,false,false,true);
  }

  /////////////////////////////////EXPEDIENTES//////////////////////////////////

  public function movimientosSinExpediente(Request $req){
    $logs= DB::table('log_movimiento')
             ->select('log_movimiento.id_log_movimiento','log_movimiento.fecha',
              'tipo_movimiento.descripcion','casino.nombre','casino.id_casino')
              ->join('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=',
              'log_movimiento.id_tipo_movimiento')
              ->join('casino','casino.id_casino','=','log_movimiento.id_casino')
              ->where('log_movimiento.tiene_expediente','=',0)
              ->whereIn('casino.id_casino',$req['id_casino'])
              ->groupBy('tipo_movimiento.descripcion','log_movimiento.id_log_movimiento','log_movimiento.fecha',
               'casino.nombre','casino.id_casino')
              ->orderBy('log_movimiento.fecha','desc')
              ->get();

    return ['logs' => $logs];
  }

  /////////////////////EVENTUALIDADES DE MAQUINA////////////////////////////////
  //INTERVENCIONES MTM

  //se usa en el validar eventualidades
  public function obtenerRelevamientoToma($id_relevamiento,$nro_toma = 1){
    $rel = RelevamientoMovimiento::find($id_relevamiento);

    $mtm = DB::table('maquina')
              ->select('maquina.*','isla.nro_isla','formula.*')
              ->join('isla','isla.id_isla','=','maquina.id_isla')
              ->join('formula','formula.id_formula','=','maquina.id_formula')
              ->join('relevamiento_movimiento','relevamiento_movimiento.id_maquina','=','maquina.id_maquina')
              ->where('relevamiento_movimiento.id_relev_mov','=',$id_relevamiento)
              ->get()
              ->first();


    $maquina = Maquina::find($rel->id_maquina);
    $juegos = $maquina->juegos;

    $toma = null;
    $fecha = null;
    $fisca = null;//fisca que hizp la toma del relevamiento
    $cargador = null;//fisca cargador
    $nombre = null;
    $progresivos = [];

    if($rel->id_fisca != null) $fisca = Usuario::find($rel->id_fisca);
    if($rel->id_cargador != null){
      $cargador = Usuario::find($rel->id_cargador);
    }else{
      $cargador = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    }

    if($nro_toma <= 0) $nro_toma = 1;
    if($rel->toma_relevamiento_movimiento()->count() >= $nro_toma){
      $toma = $rel->toma_relevamiento_movimiento()->orderBy('toma_relev_mov.id_toma_relev_mov','asc')->skip($nro_toma - 1)->first();
      $fecha = $rel->fecha_relev_sala;
      if(!is_null($toma->juego)){
        $nombre = Juego::find($toma->juego)->nombre_juego;
      }
      foreach($toma->detalles_relevamiento_progresivo as $detProg){
        $pozo = $detProg->pozo;
        $prog = $pozo->progresivo;
        $pozo_arr = $pozo->toArray();
        $prog_arr = $prog->toArray();

        $pozo_arr['es_unico'] = $prog->pozos()->count() == 1;
        $pozo_arr['niveles'] = [];
        foreach($pozo->niveles as $nivel){
          $pozo_arr['niveles'][] = $nivel->toArray();
        }
        $pozo_arr['det_rel_prog'] = $detProg->toArray();

        $prog_arr['pozo'] = $pozo_arr;
        $progresivos[] = $prog_arr;
      }
    }


    return ['relevamiento' => $rel,'maquina' => $mtm, 'juegos' => $juegos,'toma' => $toma,
     'fiscalizador' => $fisca,'cargador' => $cargador,
     'tipo_movimiento' =>  $rel->log_movimiento->tipo_movimiento , 'estado' => $rel->estado_relevamiento,
     'fecha' => $fecha, 'nombre_juego' => $nombre,'progresivos' => $progresivos];
  }

  public function buscarEventualidadesMTMs(Request $request){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      Validator::make($request->all(), [
          'id_casino' => 'nullable',
          'fecha' => 'nullable|date_format:Y-m-d',
          'id_tipo_movimiento' => 'nullable|exists:tipo_movimiento,id_tipo_movimiento',
          'id_casino' => 'nullable|exists:casino,id_casino',
          'mtm' => 'nullable|exists:nro_admin,maquina',
          'sentido' => 'nullable|string'
      ], array(), self::$atributos)->after(function ($validator){})->validate();

      $reglas=array();

      if(isset($request->id_tipo_movimiento)){
        $reglas[]=['log_movimiento.id_tipo_movimiento','=', $request->id_tipo_movimiento];
      }

      if(isset($request->nro_admin)){
        $reglas[]=['relevamiento_movimiento.id_tipo_movimiento','=', $request->nro_admin];
      }

      if(isset($request->isla)){
        $reglas[]=['log_movimiento.islas','like' ,'%' . $request->isla . '%'];
      }

      if(isset($request->mtm)){
        $reglas[]=['relevamiento_movimiento.nro_admin','=' , $request->mtm ];
      }

      if(isset($request->sentido)){
        $reglas[]=['log_movimiento.sentido','=',$request->sentido];
      }

      if(isset($request->id_casino)){
        $reglas[] = ['log_movimiento.id_casino','=',$request->id_casino];
      }

      $casinos = array();

      foreach ($usuario->casinos as $casino) {
        $casinos[] = $casino->id_casino;
      }

      $reglas[]=['log_movimiento.tiene_expediente','=',0];

      $resultados= DB::table('log_movimiento')
      ->select('log_movimiento.*','tipo_movimiento.*',
        'estado_movimiento.descripcion as estado_mov_descripcion',
        'estado_relevamiento.descripcion as estado_rel_descripcion',
        'casino.*',
        'tipo_movimiento.*')
      ->join('casino','casino.id_casino','=','log_movimiento.id_casino')
      ->join('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=','log_movimiento.id_tipo_movimiento')
      ->join('estado_movimiento','estado_movimiento.id_estado_movimiento','=','log_movimiento.id_estado_movimiento')
      ->leftJoin('estado_relevamiento','log_movimiento.id_estado_relevamiento','=','estado_relevamiento.id_estado_relevamiento')
      ->leftJoin('relevamiento_movimiento','relevamiento_movimiento.id_log_movimiento','=','log_movimiento.id_log_movimiento')
      ->whereIn('log_movimiento.id_casino',$casinos)
      ->where($reglas)
      ->whereNull('log_movimiento.id_expediente')
      ->where('log_movimiento.tiene_expediente','=', 0);
      if(isset($request->fecha)){
        $fecha=explode("-", $request->fecha);
        $resultados = $resultados->whereYear('log_movimiento.fecha' , '=', $fecha[0])
                      ->whereMonth('log_movimiento.fecha','=', $fecha[1]);
      }
      $sort_by = $request->sort_by;
      $resultados = $resultados->when($sort_by,function($query) use ($sort_by){
        // Como no tiene hora la fecha, si queremos que los cargados mas tarde pero
        // en el mismo dia aparezcan despues, ordenamos por ID.
        return $query->orderBy($sort_by['columna'],$sort_by['orden'])->orderBy('log_movimiento.id_log_movimiento','desc');
      });
      $resultados = $resultados->groupBy('log_movimiento.id_log_movimiento');
      $resultados = $resultados->paginate($request->page_size,['log_movimiento.id_log_movimiento']);

      $tipos = TipoMovimiento::where('puede_reingreso',1)->orWhere('puede_egreso_temporal',1)
      ->where('deprecado',0)->get();
      $esControlador=UsuarioController::getInstancia()->usuarioEsControlador($usuario);

      return ['eventualidades'=>$resultados,
              'esControlador' =>$esControlador,
              'esSuperUsuario' => $usuario->es_superusuario,
              'tiposEventualidadesMTM'=> $tipos,
              'casinos' => $usuario->casinos];

  }

  //al final se va a mostrar estatico, pero si se puede buscar algunos viejos con los filtros
  public function todasEventualidadesMTMs(){//type: get
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Intervenciones MTM' , 'eventualidadesMTM');
    //egreso , cambio_layout , denominacion , % devolucion , juego
    $tipos = TipoMovimiento::whereNotIn('id_tipo_movimiento',[1,8,9])->get();
    return view('eventualidadesMTM',
      [
        'eventualidades'         => [],
        'esControlador'          => $usuario->es_controlador,
        'esSuperUsuario'         => $usuario->es_superusuario,
        'tiposEventualidadesMTM' => $tipos,
        'casinos'                => $usuario->casinos,
        'causasNoTomaProgresivo' => TipoCausaNoTomaProgresivo::all()
      ]
    );
  }

  //suponiendo que me va a enviar un array con los ids de maquina
  public function nuevaEventualidadMTM( Request $request){
    $validator = Validator::make($request->all(), [
        'id_tipo_movimiento' => 'required|exists:tipo_movimiento,id_tipo_movimiento',
        'maquinas' => 'required',
        'maquinas.*.id_maquina' => 'required|exists:maquina,id_maquina',
        'sentido' => ['required','string',Rule::in(['EGRESO TEMPORAL', 'REINGRESO'])],
        'id_casino' => 'required|exists:casino,id_casino'
    ], array(), self::$atributos)->after(function($validator){
      $data = $validator->getData();
      if($data['id_tipo_movimiento']==9){
        $validator->errors()->add('tipo_movimiento', 'No se ha seleccionado el tipo de movimiento.');
      }
      $sentido = $data['sentido'];
      $tipo = TipoMovimiento::find($data['id_tipo_movimiento']);
      //Verifico que el tipo pueda realizar un movimiento en ese sentido
      if($sentido == 'EGRESO TEMPORAL' && !$tipo->puede_egreso_temporal){
        $validator->errors()->add('tipo_movimiento', 'El movimiento seleccionado no se puede realizar en ese sentido.');
      }
      if($sentido == 'REINGRESO' && !$tipo->puede_reingreso){
        $validator->errors()->add('tipo_movimiento', 'El movimiento seleccionado no se puede realizar en ese sentido.');
      }
      $id_casino = $data['id_casino'];
      if(!UsuarioController::getInstancia()->quienSoy()['usuario']->usuarioTieneCasino($id_casino)){
        $validator->errors()->add('id_casino','El usuario no puede acceder a este casino.');
      }
      foreach($data['maquinas'] as $m){
        if(Maquina::find($m['id_maquina'])->id_casino != $id_casino){
          $validator->errors()->add('id_casino','Las maquinas que no corresponden con el casino.');
          break;
        }
      }
    })->validate();

    $logMovimiento = null;
    DB::beginTransaction();
    try{
      $logMovimiento = new LogMovimiento;
      $logMovimiento->fecha= date("Y-m-d");
      $logMovimiento->tiene_expediente = 0;
      $logMovimiento->tipo_movimiento()->associate($request['id_tipo_movimiento']);
      $logMovimiento->estado_movimiento()->associate(6);//creado
      $logMovimiento->estado_relevamiento()->associate(1);//generado
      $logMovimiento->sentido = $request['sentido'];
      $logMovimiento->save();
  
      foreach ($request['maquinas'] as $mtm) {
        $relevamiento = RelevamientoMovimiento::where([['id_maquina','=', $mtm['id_maquina']],['id_log_movimiento','=',$logMovimiento->id_log_movimiento]])->get()->first();
        // Deberia entrar siempre, a menos que mande varias veces la misma maquina
        if($relevamiento == null){
          $maq = Maquina::find($mtm['id_maquina']);
          $relevamiento = RelevamientoMovimientoController::getInstancia()->crearRelevamientoMovimiento($logMovimiento->id_log_movimiento, $maq);
          $this->guardarIslasMovimiento($logMovimiento,$maq);
        }
      }

      $logMovimiento->casino()->associate($request['id_casino']);
      $logMovimiento->save();
      DB::commit();
    }
    catch(Exception $e){
      DB::rollBack();
      return null;
    }

    return $logMovimiento->id_log_movimiento;
  }

  public function cargarEventualidadMTM(Request $request){
    return $this->cargarTomaRelevamiento($request);
  }

  public function tiposMovIntervMTM(){
    $tipos = TipoMovimiento::where('puede_reingreso',1)->orWhere('puede_egreso_temporal',1)
    ->where('deprecado',0)->get();
    return ['tipos_movimientos' => $tipos];
  }

  public function relevamientosEvMTM($id_log_mov){
    $log = LogMovimiento::find($id_log_mov);
    $relevamientos = $log->relevamientos_movimientos;
    $relevamientos_arr = array();
    foreach ($relevamientos as $rel) {
      $relevamientos_arr[] = [
                     'id_relevamiento' => $rel->id_relev_mov,
                     'estado' => $rel->estado_relevamiento,
                     'nro_admin' => $rel->maquina->nro_admin,
                     'id_maquina' => $rel->maquina->id_maquina,
                     'tomas' => $rel->toma_relevamiento_movimiento()->count()
                   ];
    }
    $id_usuario = session('id_usuario');
    $user = Usuario::find($id_usuario);
    return ['relevamientos' => $relevamientos_arr,'cargador' => $user,'fiscalizador' => null,
            'tipo_movimiento' => $log->tipo_movimiento->descripcion, 'sentido' => $log->sentido,
            'casino' => $log->casino];
  }


  // visarConObservacion valida un relevamiento que nace de una intervencion de MTM
  public function visarConObservacion(Request $request){
    $relevMov = null;
    $fisMov = null;
    $logMov = null;
    $id_usuario = session('id_usuario');

    $validator = Validator::make($request->all(), [
      'id_relev_mov' => 'required|exists:relevamiento_movimiento,id_relev_mov',
      'nro_toma' => 'nullable',
      'observacion' => 'nullable|string',
      'estado' => ['required', Rule::in(['valido', 'error']) ]
    ], array(), self::$atributos)->after(function($validator) use (&$logMov,&$relevMov,$id_usuario){
      if(count($validator->errors()) == 0){
        $relevMov = RelevamientoMovimiento::find($validator->getData()['id_relev_mov']);
        $logMov = $relevMov->log_movimiento;
        if(!Usuario::find($id_usuario)->usuarioTieneCasino($logMov->id_casino)){
          $validator->errors->add('id_relev_mov','El usuario no puede acceder a ese movimiento.');
        }
      }
    })->validate();

    DB::beginTransaction();
    try{
      if($this->noEsControlador($id_usuario,$logMov)){
        $logMov->controladores()->attach($id_usuario);
        $logMov->save();
      }
      //a las tomas de los relevamientos las marco como validadas
      RelevamientoMovimientoController::getInstancia()->validarRelevamientoTomaConObservacion($relevMov, $request->estado == 'valido'? 1 : 0, $request->observacion);

      if(!is_null($relevMov->id_fiscalizacion_movimiento)){
        $relss = RelevamientoMovimiento::where('id_fiscalizacion_movimiento','=',$relevMov->id_fiscalizacion_movimiento)
        ->whereIn('id_estado_relevamiento',[4,6])->get();
        $fisMov = $relevMov->fiscalizacion;
        if($fisMov->relevamientos_movimientos()->count() == count($relss)){
          $fisMov->estado_relevamiento()->associate(4);
          $fisMov->save();
          $fisValidada = true;
        }
      }

      $relss = RelevamientoMovimiento::where('id_log_movimiento','=',$logMov->id_log_movimiento)
                ->whereIn('id_estado_relevamiento',[4,6])->get();

      if($logMov->relevamientos_movimientos()->count() == count($relss)){
        $logMov->estado_relevamiento()->associate(4);
        $logMov->estado_movimiento()->associate(4);
        $logMov->save();
        $logValidado = true;
        $map = [
          1  => ['nuevo_estado' => 1, 'texto' => "Ingreso validado."], // Deprecado
          2  => ['nuevo_estado' => 4, 'texto' => "Egreso validado."], // Deprecado
          3  => ['nuevo_estado' => 2, 'texto' => "Reingreso validado."], // Deprecado
          8  => ['nuevo_estado' => 2, 'texto' => "Reingreso validado."], // Deprecado
          9  => ['texto' => "--- validado."], // Deprecado
          11 => ['nuevo_estado' => 1, 'texto' => "Ingreso inicial validado."],
          12 => ['nuevo_estado' => 3, 'texto' => "Egreso definitivo validado."],
          4  => ['texto' => "Cambio de isla validado."],
          5  => ['texto' => "Cambio de denominacion validado."],
          6  => ['texto' => "Cambio de % devolución validado."],
          7  => ['texto' => "Cambio de juego validado."],
          10 => ['texto' => "Actualización de firmware validada."],
        ];
        //Si fue VALIDADO el relevamiento genero un LogMaquina
        if($relevMov->id_estado_relevamiento == 4 && array_key_exists($logMov->id_tipo_movimiento,$map)){
          $maquina = $relevMov->maquina;
          $accion = $map[$logMov->id_tipo_movimiento];
          if(array_key_exists('nuevo_estado',$accion)){
            $maquina->estado_maquina()->associate($accion['nuevo_estado']);
            $maquina->save();
          }
          $razon = $accion['texto']." \n";
          $tomas = $relevMov->toma_relevamiento_movimiento()->orderBy('toma_relev_mov.id_toma_relev_mov','asc')->get();
          $multiples_tomas = $relevMov->toma_relevamiento_movimiento()->count() > 1;
          //Multiples tomas por relevamiento estan deprecadas pero las considero por las dudas.
          foreach($tomas as $idx => $toma){
            if($multiples_tomas) $razon = $razon . "Toma " . ($idx+1) . ": \n";
            $razon = $razon . $toma->observaciones . " \n";
          }
          LogMaquinaController::getInstancia()->registrarMovimiento($maquina->id_maquina, $razon, $logMov->id_tipo_movimiento);
        }
      }
      DB::commit();
    }
    catch(Exception $e){
      DB::rollBack();
      throw $e;
    }
    return ['relError'    => $relevMov->id_estado_relevamiento == 6,
            'relValidado' => $relevMov->id_estado_relevamiento == 4,
            'logValidado' => $logMov->id_estado_relevamiento == 4, 
            'fisValidada' => !is_null($fisMov) && $fisMov->id_estado_relevamiento == 4 ];
  }

  ///////////PARA DENOMINACION Y DEVOLUCION/////////////////////////////////////

  public function obtenerMaquinasSector($id_sector){
      //dado un casino,devuelve sectores que concuerden con el nombre del sector
      $maquinas = Maquina::
                        join('isla','isla.id_isla','=','maquina.id_isla')
                      ->join('sector','sector.id_sector','=','isla.id_sector')
                      ->where('sector.id_sector' , '=' , $id_sector)
                      ->get();

      foreach($maquinas as  $m){
        $m->denominacion= $m->obtenerDenominacion();
        $m->porcentaje_devolucion=$m->obtenerPorcentajeDevolucion();
      }

      $unidades = DB::table('unidad_medida')->select('unidad_medida.*')->get();

      return ['maquinas' => $maquinas,'unidades' => $unidades];
  }

  public function obtenerMaquinasIsla($id_isla){
      //dado un casino,devuelve sectores que concuerden con el nro admin dado
      $maquinas = Maquina::
                        join('isla','isla.id_isla','=','maquina.id_isla')
                      ->where('isla.id_isla' , '=' , $id_isla)
                      ->get();
      // se cambia el valor devuelto de denominacion y % dev por los valores del juego activo
      $maqUI=  array();
      foreach($maquinas as  $m){

        $mtemp = new \stdClass();
        $mtemp->id_maquina = $m->id_maquina;
        $mtemp->nro_admin = $m->nro_admin;
        $mtemp->id_unidad_medida = $m->id_unidad_medida;
        $mtemp->denominacion= $m->obtenerDenominacion();
        $mtemp->porcentaje_devolucion=$m->obtenerPorcentajeDevolucion();
        $mtemp->juego_obj= $m->juego_activo;
        $maqUI[]=$mtemp;
      }

      $unidades = DB::table('unidad_medida')->select('unidad_medida.*')->get();
     return ['maquinas' => $maqUI,'unidades' => $unidades];
  }

  public function obtenerMaquina($id_maquina){
    //dado un casino,devuelve sectores que concuerden con el nombre del sector
    $m = Maquina::Find($id_maquina);

    $m->denominacion= $m->obtenerDenominacion();
    $m->porcentaje_devolucion= $m->obtenerPorcentajeDevolucion();

    $juego_activo= $m->juego_activo;


    $unidades = DB::table('unidad_medida')->select('unidad_medida.*')->get();

    return ['maquina' => $m,'unidades' => $unidades , 'juego_activo' => $juego_activo];
}
}
