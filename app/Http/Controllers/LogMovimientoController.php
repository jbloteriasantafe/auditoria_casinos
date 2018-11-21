<?php

namespace App\Http\Controllers;

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

  public function obtenerDatos($id){
    $log = LogMovimiento::find($id);
    $exp = $log->expediente;
    return ['cantidad' => $log->cant_maquinas, 'tipo'=>$log->tipo_carga , 'expediente' => $exp];
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
    $tipo_progresivos = ['LINKEADO', 'INDIVIDUAL'];
    $estados = EstadoMaquina::all();
    $logs = LogMovimiento::whereIn('id_casino',$casinos)->orderBy('fecha','DESC')->get();
    $casinos=$usuario['usuario']->casinos;
    $tiposMovimientos = TipoMovimiento::whereIn('id_tipo_movimiento',[1,2,4,5,6,7,8])->get();
    UsuarioController::getInstancia()->agregarSeccionReciente('Asignación Movimientos' ,'movimientos');
    return view('seccionMovimientos',['logMovimientos'=>$logs , 'tiposMovimientos' => $tiposMovimientos,'monedas'=>$monedas , 'unidades_medida' => $unidad_medida,   'casinos' => $casinos, 'tipos' => $tipos , 'gabinetes' => $gabinetes , 'tipo_progresivos' => $tipo_progresivos, 'estados' => $estados]);
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

    if(!empty($request->casino)){
      $casinos[] = $request->casino;
    }else {
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      foreach ($usuario->casinos as $casino) {
        $casinos[] = $casino->id_casino;
      }
    }

    if(isset($request->nro_admin) && $request->nro_admin != ""){
      $reglas[]=['relevamiento_movimiento.nro_admin','like', $request->nro_admin.'%'];
    }

    if(!empty($request->tipo_movimiento))
      $reglas[]=['log_movimiento.id_tipo_movimiento','=', $request['tipo_movimiento']];

    if(!empty($request->sort_by)){
      $sort_by = $request->sort_by;
    }else{
      $sort_by = ['columna' => 'log_movimiento.fecha', 'orden' => 'DESC'];
    }
      //busca en logs con expedientes
      if(empty($request->fecha)){
          $resultados=DB::table('log_movimiento')
                    ->select('log_movimiento.*','expediente.*','casino.*','tipo_movimiento.*')
                    ->join('expediente', 'log_movimiento.id_expediente', '=', 'expediente.id_expediente')
                    ->join('casino', 'log_movimiento.id_casino', '=', 'casino.id_casino')
                    ->join('tipo_movimiento','log_movimiento.id_tipo_movimiento','=', 'tipo_movimiento.id_tipo_movimiento')
                    ->leftJoin('relevamiento_movimiento','relevamiento_movimiento.id_log_movimiento','=','log_movimiento.id_log_movimiento')
                    ->where($reglas)
                    ->whereIn('log_movimiento.id_casino' , $casinos)
                    ->whereNotIn('tipo_movimiento.id_tipo_movimiento',[9])
                    ->distinct('log_movimiento.id_log_movimiento','expediente.id_expediente','casino.id_casino','tipo_movimiento.id_tipo_movimiento')
                    ->when($sort_by,function($query) use ($sort_by){
                                    return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                })
                    ->paginate($request->page_size,['log_movimiento.id_log_movimiento','expediente.id_expediente','casino.id_casino','tipo_movimiento.id_tipo_movimiento']);
      }else{
          $fecha=explode("-", $request->fecha);
          $resultados=DB::table('log_movimiento')
          ->select('log_movimiento.*','expediente.*','casino.*','tipo_movimiento.*')
          ->join('expediente', 'log_movimiento.id_expediente', '=', 'expediente.id_expediente')
          ->join('casino', 'log_movimiento.id_casino', '=', 'casino.id_casino')
          ->join('tipo_movimiento','log_movimiento.id_tipo_movimiento','=', 'tipo_movimiento.id_tipo_movimiento')
          ->leftJoin('relevamiento_movimiento','relevamiento_movimiento.id_log_movimiento','=','log_movimiento.id_log_movimiento')
          ->where($reglas)
          ->whereIn('log_movimiento.id_casino' , $casinos)
          ->whereNotIn('tipo_movimiento.id_tipo_movimiento',[9])
          ->whereYear('log_movimiento.fecha' , '=' ,$fecha[0])
          ->whereMonth('log_movimiento.fecha','=', $fecha[1])
          ->distinct('log_movimiento.id_log_movimiento','expediente.id_expediente','casino.id_casino','tipo_movimiento.id_tipo_movimiento')
          ->when($sort_by,function($query) use ($sort_by){
                          return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                      })

          ->paginate($request->page_size,['log_movimiento.id_log_movimiento','expediente.id_expediente','casino.id_casino','tipo_movimiento.id_tipo_movimiento']);
        }

        $cas = DB::table('casino')
                  ->select('casino.id_casino')
                  ->whereIn('casino.id_casino' , $casinos)
                  ->get();

    return ['logMovimientos' => $resultados, 'casinos' => $cas ];


  }

  public function noEsControlador($id_usuario , $logMov){
    $resultado = true;
    foreach ($logMov->controladores as $controlador) {
      if($controlador->id_usuario == $id_usuario){
        $resultado = false;
        break;
      }
    }
    return $resultado;
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
    //el request envia el log movimiento con las maquinas que se van a relevar efectivamente
    // 'id_log_movimiento', maquinas, maquinas.*.id_maquina
    if(!empty($request['maquinas'])){
      $logMov = LogMovimiento::find($request['id_log_movimiento']);
      if(!isset($logMov->fiscalizaciones))
      {
        $logMov->estado_movimiento()->associate(2);//fiscalizando
      }
      $fiscalizacion = FiscalizacionMovController::getInstancia()->crearFiscalizacion($logMov->id_log_movimiento, false,$request['fecha']);
      foreach ($request['maquinas'] as $maq) {
        $maquina = Maquina::find($maq);
        $maquina->estado_maquina()->associate(1);//Ingreso
        //crear log maquina
        LogMaquinaController::getInstancia()->registrarMovimiento($maquina->id_maquina, "MTM enviada a fiscalizar.",1);
        //busco los relevamientos que se crearon para asociarlos a una fiscalizacion
        $relevamiento = RelevamientoMovimiento::where([['id_maquina','=', $maq],['id_log_movimiento','=',$request['id_log_movimiento']]])->get()->first();
        $relevamiento->fiscalizacion()->associate($fiscalizacion->id_fiscalizacion_movimiento);
        $relevamiento->save();
      }

      $id_usuario = session('id_usuario');
      $usuarios = UsuarioController::getInstancia()->obtenerFiscalizadores($logMov->casino->id_casino,$id_usuario );
      foreach ($usuarios as $user){
        $u = Usuario::find($user->id_usuario);
       if($u != null)  $u->notify(new RelevamientoGenerado($fiscalizacion));
      }

      $date = date('Y-m-d h:i:s', time());
      $titulo = "Relevamiento Movimientos";
      $descripcion = "El movimiento: ".$logMov->tipo_movimiento->descripcion." con fecha ".$logMov->fecha.", está listo para fiscalizar.";
      CalendarioController::getInstancia()->crearEventoMovimiento($date,$date,$titulo,$descripcion,$logMov->id_casino,$fiscalizacion->id_fiscalizacion_movimiento);
      return 1;
    }
    return response()->json(['maquinas' => 'No hay máquinas seleccionadas.'], 422);

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
      $validator = Validator::make($req->all(), [
          'id_log_movimiento' => 'required|exists:log_movimiento,id_log_movimiento',
          'maquinas' => 'required',
          'maquinas.*.id_maquina' => 'required|exists:maquina,id_maquina',
          'maquinas.*.id_juego' => 'nullable | exists:juego,id_juego',
          'maquinas.*.porcentaje_devolucion' => ['nullable','regex:/^\d\d?([,|.]\d\d?\d?)?$/'],
          'maquinas.*.denominacion' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
          'maquinas.*.id_unidad_medida' => 'nullable | exists:unidad_medida,id_unidad_medida',
          'carga_finalizada'=> 'required'
      ], array(), self::$atributos)->after(function ($validator){
        foreach ($validator->getData()['maquinas'] as $maquina) {
          if($this->maquinaDuplicada($validator->getData()['maquinas'], $maquina['id_maquina'])){
            $validator->errors()->add('id_maquina', 'Se ha cargado más de una vez al menos una máquina.');
          }
        }
      })->validate();
      if(isset($validator))
      {
        if ($validator->fails())
        {
          return [
                'errors' => $v->getMessageBag()->toArray()
            ];
        }
      }


      $logMov = LogMovimiento::find($req['id_log_movimiento']);

      $logMov->estado_relevamiento()->associate(1);//generado

      $id_usuario = session('id_usuario');
      if($this->noEsControlador($id_usuario,  $logMov)){
        $logMov->controladores()->attach($id_usuario);
        $logMov->save();
      }

      switch ($logMov->id_tipo_movimiento) {
        case 5: //denominacion
          foreach ($req['maquinas'] as $maquina)
          {
            $logMov = LogMovimiento::find($logMov->id_log_movimiento);
            MTMController::getInstancia()->modificarDenominacionYUnidad($maquina['id_unidad_medida'],$maquina['denominacion'],$maquina['id_maquina']);

            $maq= Maquina::find($maquina['id_maquina']);
            if($this->noTieneRelevamientoCreado($maquina['id_maquina'],$req['id_log_movimiento']))
            {
              $r = RelevamientoMovimientoController::getInstancia()->crearRelevamientoMovimiento($req['id_log_movimiento'], $maq);
              $this->guardarIslasMovimiento($logMov,$maq);
            }
          }
          break;
        case 6: //% devolucion
          foreach ($req['maquinas'] as $maquina)
          {
            $logMov = LogMovimiento::find($logMov->id_log_movimiento);
            MTMController::getInstancia()->modificarDevolucion($maquina['porcentaje_devolucion'],$maquina['id_maquina']);
            $maq= Maquina::find($maquina['id_maquina']);
            if($this->noTieneRelevamientoCreado($maquina['id_maquina'],$req['id_log_movimiento']))
            {
              $r = RelevamientoMovimientoController::getInstancia()->crearRelevamientoMovimiento($req['id_log_movimiento'], $maq);
              $this->guardarIslasMovimiento($logMov,$maq);
            }
          }
          break;
        case 7: //juego
          foreach ($req['maquinas'] as $maquina)
          {
            $logMov = LogMovimiento::find($logMov->id_log_movimiento);
            MTMController::getInstancia()->modificarJuego($maquina['id_juego'],$maquina['id_maquina']);
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
      $aux =0;
      foreach ($maquinas as $maquina) {
        if($maquina['id_maquina'] == $id_maquina){
          $aux++;
        }
      }
      if($aux >1){
        return true;
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

    $resultados = DB::table('fiscalizacion_movimiento')
                      ->select('fiscalizacion_movimiento.*','tipo_movimiento.*','casino.nombre')//,'estado_relevamiento.*'
                      ->join('log_movimiento','log_movimiento.id_log_movimiento','=', 'fiscalizacion_movimiento.id_log_movimiento')
                      ->join('casino','casino.id_casino','=','log_movimiento.id_casino')
                      ->join('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=', 'log_movimiento.id_tipo_movimiento')
                      ->whereIn('log_movimiento.id_casino',$casinos)
                      ->where('log_movimiento.id_expediente','<>','null')
                      ->orderBy('fiscalizacion_movimiento.fecha_envio_fiscalizar','desc')
                      ->take(25)
                      ->get();

    $tiposMovimientos = TipoMovimiento::all();
  //  return $resultados;
    UsuarioController::getInstancia()->agregarSeccionReciente('Relevamientos Movimientos','relevamientos_movimientos');

    return view('seccionRelevamientosMovimientos',['fiscalizaciones' => $resultados ,'tipos_movimientos' => $tiposMovimientos]);
  }

  //para poder realizar la carga de los datos
  public function obtenerRelevamientosFiscalizacion($id_fiscalizacion_movimiento){

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

  public function obtenerMTMFiscalizacion($id_maquina, $id_fiscalizacion){
    $mtm = DB::table('maquina')
              ->select('maquina.*','isla.nro_isla','formula.*')
              ->leftJoin('isla','isla.id_isla','=','maquina.id_isla')
              ->leftJoin('formula','formula.id_formula','=','maquina.id_formula')
              ->leftJoin('relevamiento_movimiento','relevamiento_movimiento.id_maquina','=','maquina.id_maquina')
              ->join('fiscalizacion_movimiento','fiscalizacion_movimiento.id_fiscalizacion_movimiento','=','relevamiento_movimiento.id_fiscalizacion_movimiento')
              ->where('fiscalizacion_movimiento.id_fiscalizacion_movimiento','=',$id_fiscalizacion)
              ->where('maquina.id_maquina','=',$id_maquina)
              ->get()
              ->first();


    $juegos = (Maquina::find($id_maquina))->juegos;
    $relevamiento = RelevamientoMovimiento::where([['id_fiscalizacion_movimiento','=',$id_fiscalizacion],['id_maquina','=',$id_maquina]])->get()->first();
    $toma=null;
    $fisca = null;
    $fecha = null;
    $fiscalizacion = FiscalizacionMov::find($id_fiscalizacion);
    $fisca = $fiscalizacion->fiscalizador;
    $nombre= null;
    if(isset($relevamiento->toma_relevamiento_movimiento)){
      $toma=$relevamiento->toma_relevamiento_movimiento;
      $fecha = $relevamiento->fecha_relev_sala;
      $nombre= Juego::find($toma->juego)->nombre_juego;
    }

    return ['maquina' => $mtm, 'juegos'=> $juegos,'toma'=>$toma, 'fiscalizador'=> $fisca, 'fecha' => $fecha, 'nombre_juego' => $nombre];
  }

  public function generarPlanillasRelevamientoMovimiento($id_fiscalizacion_movimiento){
    $fiscalizacionMov = FiscalizacionMov::find($id_fiscalizacion_movimiento);
    $logMov = $fiscalizacionMov->log_movimiento;
    if(!isset($logMov->fiscalizaciones))
    {
      $logMov->estado_movimiento()->associate(2);//fiscalizando
    }
    $logMov->save();
    $tipoMovimiento = $logMov->tipo_movimiento->descripcion;
    $casino = $logMov->casino;
    $relevamientos = array();
    $count = 0;

    foreach($fiscalizacionMov->relevamientos_movimientos as $relev_mov){
      $relevamientos[] = RelevamientoMovimientoController::getInstancia()->generarPlanillaMaquina($relev_mov,$tipoMovimiento, $casino, $fiscalizacionMov->fecha_envio_fiscalizar,$fiscalizacionMov->id_estado_relevamiento,$count++,$fiscalizacionMov->es_reingreso);
    }

    $toma=null;
    if($tipoMovimiento != 'EGRESO/REINGRESOS'){
      $toma=1;
    }else{
      $toma=2;
    }
    $view = View::make('planillaMovimientos', compact('relevamientos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $toma."/".$casino->codigo."/".$fiscalizacionMov->fecha_envio_fiscalizar, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));

  }

  public function cargarTomaRelevamiento(Request $request){
    $validator =Validator::make($request->all(), [
        'id_fiscalizacion_movimiento' => 'required|exists:fiscalizacion_movimiento,id_fiscalizacion_movimiento',
        'id_cargador' => 'nullable|exists:usuario,id_usuario',
        'id_fiscalizador' => 'required|exists:usuario,id_usuario',
        'id_maquina' => 'required|exists:maquina,id_maquina',
        'id_relevamiento' => 'nullable|exists:relevamiento_movimiento,id_relev_mov',
        'contadores' => 'required',
        'contadores.*.nombre' =>'nullable',
        'contadores.*.valor' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        //'juego' => 'required |exists: juego, id_juego',
        'juego' => 'required',
        'sectorRelevadoCargar' => 'required',
        'isla_relevada' => 'required',
        'apuesta_max' => 'required| numeric| max:900000',
        'cant_lineas' => 'required|numeric| max:100000',
        'porcentaje_devolucion' => ['required','regex:/^\d\d?([,|.]\d\d?\d?)?$/'],
        'denominacion' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'cant_creditos' => 'required|numeric| max:100',
        'fecha_sala' => 'required|date',//fecha con dia y hora
        'observaciones' => 'nullable|max:280',
        'mac' => 'nullable | max:100'
    ], array(), self::$atributos)->after(function($validator){
      if($validator->getData()['juego']==0 ){
          $validator->errors()->add('juego', 'No se ha seleccionado el juego.');
      }
      $maquina = Maquina::find($validator->getData()['id_maquina']);
      $aux=1;
      $formula = $maquina->formula;
      $contadores =$validator->getData()['contadores'];
      //PARA VALIDAR SI ESTAN TODOS LOS CONTADORES CARGADOS QUE TIENE LA MTM EN LA FORMULA
      foreach ($contadores as $cont) {

          switch ($aux)
          {
            case 1:
              if($formula->cont1 !=null && $cont['valor'] == "")
              {
                $validator->errors()->add('contadores', 'No se han cargado todos los contadores.');
              }
              break;
            case 2:
              if($formula->cont2 != null && $cont['valor'] == "")
              {
                $validator->errors()->add('contadores', 'No se han cargado todos los contadores.');
              }
              break;
            case 3:
              if($formula->cont3 !=null && $cont['valor'] == "")
              {
                $validator->errors()->add('contadores', 'No se han cargado todos los contadores.');
              }
              break;
            case 4:
              if($formula->cont4 !=null && $cont['valor'] == "")
              {
                $validator->errors()->add('contadores', 'No se han cargado todos los contadores.');
              }
              break;
            case 5:
              if($formula->cont5 !=null && $cont['valor'] == "")
              {
                $validator->errors()->add('contadores', 'No se han cargado todos los contadores.');
              }
              break;
            case 6:
              if($formula->cont6 !=null && $cont['valor'] == "")
              {
                $validator->errors()->add('contadores', 'No se han cargado todos los contadores.');
              }
              break;
            default:
              # code...
              break;
          }
          $aux++;
        }

    })->validate();//FIN VALIDACION DEL REQUEST

     if(isset($validator))
      {
        if ($validator->fails())
        {
          return [
                'errors' => $v->getMessageBag()->toArray()
            ];
        }
     }

    $fiscalizacion = FiscalizacionMov::find($request->id_fiscalizacion_movimiento);

    if(!empty($request->id_log_movimiento)){
      $logMov = LogMovimiento::find($request->id_log_movimiento);
    }else{
      $logMov = $fiscalizacion->log_movimiento;
    }

    if($fiscalizacion->id_estado_relevamiento == 1){//si estaba generado pasa a cargando
      if(!isset($logMov->fiscalizaciones))
      {
        $logMov->estado_movimiento()->associate(2);//fiscalizando
      }
      $fiscalizacion->estado_relevamiento()->associate(2);
    }



    if(!isset($fiscalizacion->cargador)){
      $fiscalizacion->cargador()->associate($request->id_cargador);
      $fiscalizacion->fiscalizador()->associate( $request->id_fiscalizador);
    }

    RelevamientoMovimientoController::getInstancia()->cargarTomaRelevamiento($request->id_maquina ,
    $request['contadores'],
    $request['juego'] ,
    $request['apuesta_max'],
    $request['cant_lineas'] ,
    $request['porcentaje_devolucion'],
    $request['denominacion'] ,
    $request['cant_creditos'],
    $request['fecha_sala'],
    $request['observaciones'],
    $request['isla_relevada'],
    $request['sectorRelevadoCargar'],
    $request->id_fiscalizacion_movimiento,
    $request->id_cargador,
    $request->id_fiscalizador, $request['mac']);

    if($this->cargaFinalizada($fiscalizacion))
    {//si existe una toma de relevamiento por cada relevamiento -> finalizado
      $logMov->id_estado_movimiento= 3;//fiscalizado
      $logMov->estado_relevamiento()->associate(3);//finalizado ==cargado
      $fiscalizacion->estado_relevamiento()->associate(3);
      $id_usuario = session('id_usuario');
      $usuarios = UsuarioController::getInstancia()->obtenerControladores($logMov->casino->id_casino, $id_usuario);
      foreach ($usuarios as $user){
        $u = Usuario::find($user->id_usuario);
       if($u != null) $u->notify(new RelevamientoCargado($fiscalizacion));
      }
      CalendarioController::getInstancia()->marcarRealizado($fiscalizacion->evento);
    }
      $fiscalizacion->save();
      $logMov->save();

    return ['codigo'=>1];

  }

  private function cargaFinalizada($fiscalizacion){
    foreach ($fiscalizacion->relevamientos_movimientos as $relevamiento) {
      if(!isset($relevamiento->toma_relevamiento_movimiento)){
        return false;
      }
    }
    return true;
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

  public function generarPlanillaMovimientos(){
    $dompdf = $this->crearPlanillaMovimientos();

    return $dompdf->stream("Movimientos.pdf", Array('Attachment'=>0));
  }

  //para el controlador///////////////////////////////////////////////////////
  //deberia enviar las cosas suficientes como para poder mostrar para validar
  public function ValidarMovimiento($id_log_movimiento){
    // $logMov = LogMovimiento::find($id_log_movimiento);
    // if($logMov->id_tipo_movimiento != 8){
    //   return $logMov->fiscalizaciones;
    // }else {//no se si funciona en todos los casos
      $fiscalizaciones = DB::table('log_movimiento')
                          ->select('fiscalizacion_movimiento.*', 'fiscalizacion_movimiento.id_estado_relevamiento as id_estado_fiscalizacion')
                          ->join('fiscalizacion_movimiento','fiscalizacion_movimiento.id_log_movimiento','=','log_movimiento.id_log_movimiento')
                          ->whereIn('fiscalizacion_movimiento.id_estado_relevamiento',[3,4,5,6])
                          //->orWhere('fiscalizacion_movimiento.es_reingreso','=',1)
                          ->where('log_movimiento.id_log_movimiento','=',$id_log_movimiento)
                          ->get();
      return $fiscalizaciones;
    //}
  }

  public function ValidarFiscalizacion($id_fiscalizacion_movimiento){
    $fiscalizacionMov = FiscalizacionMov::find($id_fiscalizacion_movimiento);
    $maquinas = DB::table('fiscalizacion_movimiento')
                    ->select('relevamiento_movimiento.id_maquina','maquina.nro_admin',
                    'fiscalizacion_movimiento.id_estado_relevamiento as id_estado_fiscalizacion',
                    'relevamiento_movimiento.id_relev_mov','relevamiento_movimiento.id_estado_relevamiento')
                    ->join('relevamiento_movimiento', 'relevamiento_movimiento.id_fiscalizacion_movimiento','=','fiscalizacion_movimiento.id_fiscalizacion_movimiento')
                    ->join('maquina','relevamiento_movimiento.id_maquina','=','maquina.id_maquina')
                    ->where('fiscalizacion_movimiento.id_fiscalizacion_movimiento','=',$id_fiscalizacion_movimiento)
                    ->get();
    return ['Maquinas' => $maquinas];
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
                    ->get()
                    ->first();

    $toma1=null;
    if($fiscalizacionMov->es_reingreso == 1 ){
      $toma1 = FiscalizacionMovController::getInstancia()->buscarTomaEgreso($fiscalizacionMov->id_fiscalizacion_movimiento, $fiscalizacionMov->id_log_movimiento,$relev->id_maquina);
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
                  // ->groupBy('relevamiento_movimiento.id_relev_mov')
                  // ->havingRaw('COUNT(*) < 2') //para que traiga solo las que todavia NO furon relevadas por 2da vez
                  ->take(25)
                  ->get();
  }



  //valida las tomas de los relevamientos y ejecutar las acciones sobre ellas
  public function validarTomaRelevamiento(Request $request){
    //el request contiene id_relev_mov
    $id_usuario = session('id_usuario');
    $relev_mov = RelevamientoMovimiento::find($request->id_relev_mov);
    $fiscalizacion = FiscalizacionMov::find($relev_mov->id_fiscalizacion_movimiento);
    $logMov = LogMovimiento::find($fiscalizacion->id_log_movimiento);
    $id_usuario = session('id_usuario');
    if($this->noEsControlador($id_usuario,  $logMov)){
      $logMov->controladores()->attach($id_usuario);
      $logMov->save();
    }

    //creo un array para guardar el id_isla para cambiar el estado de relevamiento del log isla
    $islas = array();

    //a las tomas de los relevamientos las marco como validadas
    $razon = RelevamientoMovimientoController::getInstancia()->validarRelevamientoToma($relev_mov, $request->validado);//retorna las observaciones de la toma
    $maquina = $relev_mov->maquina;


      //cambio el estado de la máquina
      switch ($logMov->id_tipo_movimiento) {
        case 1: // ingreso
        //esto se cambió en el comit "no se"
            $maquina->estado_maquina()->associate(1);
            $maquina->save();
            //crear log maquina
            LogMaquinaController::getInstancia()->registrarMovimiento($maquina->id_maquina, "Ingreso validado. Observaciones: ".$razon,$logMov->id_tipo_movimiento);
            break;
        case 2: //egreso
            $maquina->estado_maquina()->associate(4); 	///Egreso Temporal
            $maquina->save();
            LogMaquinaController::getInstancia()->registrarMovimiento($maquina->id_maquina, "Egreso validado. Observaciones: ".$razon,$logMov->id_tipo_movimiento);
            break;
        case 3://reingreso
            $maquina->estado_maquina()->associate(2);//reingreso
            $maquina->save();
            LogMaquinaController::getInstancia()->registrarMovimiento($maquina->id_maquina, "Reingreso validado. Observaciones: ".$razon,$logMov->id_tipo_movimiento);
            break;
        case 4: //cambio layout
            //cambiar estado en isla->log_isla //lo hace el controlador desde la
            // seccion gestionar islas porque sino es muy lento y no es seguro encontrar el log.
            //el estado de la maquina no cambia
            //  $islas[] = $maquina->id_isla;
            //el log maquina se creo en IslaController
            LogMaquinaController::getInstancia()->registrarMovimiento($maquina->id_maquina, "Cambio de isla validado. Observaciones: ".$razon,$logMov->id_tipo_movimiento);
            break;
        case 5: //denominacion
            //crear log maquina
            LogMaquinaController::getInstancia()->registrarMovimiento($maquina->id_maquina, "Cambio de denominacion validado. Observaciones: ".$razon,$logMov->id_tipo_movimiento);
            break;
        case 6: //% devolucion
            LogMaquinaController::getInstancia()->registrarMovimiento($maquina->id_maquina, "Cambio de % devolución validado. Observaciones: ".$razon,$logMov->id_tipo_movimiento);
            break;
        case 7: //juego
            LogMaquinaController::getInstancia()->registrarMovimiento($maquina->id_maquina, "Cambio de juego validado. Observaciones: ".$razon,$logMov->id_tipo_movimiento);
            break;
        case 8: //egreso/reingreso
            $maquina->estado_maquina()->associate(2);//reingreso
            $maquina->save();
            LogMaquinaController::getInstancia()->registrarMovimiento($maquina->id_maquina, "Reingreso validado. Observaciones: ".$razon,$logMov->id_tipo_movimiento);
            break;
        default:

        break;

      }

      
      return ['id_estado_relevamiento'=> $relev_mov->id_estado_relevamiento];
  }


  public function cambiarEstadoFiscalizacionAValidado($id_fiscalizacion){
    $fiscalizacion = FiscalizacionMov::find($id_fiscalizacion);
    $logMov = LogMovimiento::find($fiscalizacion->id_log_movimiento);
    if($this->countMaquinasValidadas($fiscalizacion->relevamientos_movimientos) ==
       count($fiscalizacion->relevamientos_movimientos)){
      if(isset($logMov->fiscalizaciones))
      {
        $logMov->estado_movimiento()->associate(4);//validado -- visadooooo lpm!!!MOEXX
        $fiscalizacion->estado_relevamiento()->associate(4);
        $fiscalizacion->save();
      }

    }else{
      if(!isset($logMov->fiscalizaciones))
      {
        $logMov->estado_movimiento()->associate(5);//error
      }
    }
    $logMov->save();
    return 1;
  }


  private function countMaquinasValidadas($relevamientos_movimientos){
    $contador = 0;
    foreach ($relevamientos_movimientos as $relev) {
      if($relev->id_estado_relevamiento == 4 || $relev->id_estado_relevamiento == 6){
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
                        ->distinct('maquina.id_maquina')
                        ->get();
    $maquinasPausa = DB::table('relevamiento_movimiento')
                        ->select('maquina.*','isla.*','juego.*')
                        ->join('maquina','relevamiento_movimiento.id_maquina','=','maquina.id_maquina')
                        ->join('isla','isla.id_isla','=','maquina.id_isla')
                        ->join('juego','juego.id_juego','=','maquina.id_juego')
                        ->where('relevamiento_movimiento.id_log_movimiento','=', $id_log_movimiento)
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
    $id_usuario = session('id_usuario');
    $logMov = LogMovimiento::find($req['id_log_movimiento']);
    if($this->noEsControlador($id_usuario,  $logMov)){
      $logMov->controladores()->attach($id_usuario);
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

  public function casinosYMovimientos(){
    $id_usuario = session('id_usuario');
    $cas= UsuarioController::getInstancia()->buscarCasinosDelUsuario($id_usuario);
    $t=TipoMovimiento::whereNotIn('id_tipo_movimiento',[3,8,9])->get();
    return ['casinos' =>$cas,
            'tipos_movimientos' => $t];
  }


  public function nuevoLogMovimiento(Request $request){
    $validator =Validator::make($request->all(),
    [
      'id_tipo_movimiento' => 'required|exists:tipo_movimiento,id_tipo_movimiento',
      'casino' => 'required|exists:casino,id_casino'
    ], array(), self::$atributos)->after(function($validator){})->validate();

    if(isset($validator))
      {
        if ($validator->fails())
        {
          return [
                'errors' => $v->getMessageBag()->toArray()
            ];
        }
      }

      $id_usuario = session('id_usuario');
      //$id_casino = UsuarioController::getInstancia()->buscarCasinoDelUsuario($id_usuario);

      //creo un expedienteAux para que cuando el movimiento se muestre en la lista no tenga problemas y se muestre
      //hay que crearlo a pata en la base de datos del sistema que funciona con los casinos
      $expedienteAux = Expediente::where([['concepto','=','expediente_auxiliar_para_movimientos'],['id_casino','=',$request['casino']]])->get()->first();

      $logMovimiento = new LogMovimiento;
      $logMovimiento->tiene_expediente = 0;
      $logMovimiento->estado_movimiento()->associate(1);//estado = notificado
      $logMovimiento->tipo_movimiento()->associate($request['id_tipo_movimiento']);
      $f = date("Y-m-d");
      $logMovimiento->fecha = $f;
      $logMovimiento->casino()->associate($request['casino']);
      $logMovimiento->expediente()->associate($expedienteAux->id_expediente);
      $logMovimiento->save();
      $logMovimiento->controladores()->attach($id_usuario);
      $logMovimiento->save();

      $log = DB::table('log_movimiento')
                  ->select('log_movimiento.*','tipo_movimiento.descripcion','casino.id_casino','expediente.*')
                  ->join('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=','log_movimiento.id_tipo_movimiento')
                  ->join('casino','casino.id_casino','=','log_movimiento.id_casino')
                  ->join('expediente','expediente.id_expediente','=','log_movimiento.id_expediente')
                  ->where('log_movimiento.id_log_movimiento','=',$logMovimiento->id_log_movimiento)
                  ->get()->first();

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

  /*
    Unicamente se usa desde el boton eliminar que aparece en el tipo de mov
    INGRESO
    Recibe id del movimiento,
    si no tiene relevamientos creados o no tiene expediente, se puede eliminar
  */
  public function eliminarMovimiento(Request $req)
  {
    $log = LogMovimiento::find($req['id_log_movimiento']);

    if(isset($log->relevamientos_movimientos[0]) || $log->tiene_expediente == 1)
    {
      return 0;//dd('El movimiento ya fue enviado a fiscalizar o tiene asignado un expediente.');
    }else{
      $log->tipo_movimiento()->dissociate();
      $log->estado_movimiento()->dissociate();
      $log->expediente()->dissociate();
      $log->controladores()->detach();
      LogClicksMovController::getInstancia()->eliminar($log->id_log_movimiento);
      //$log->log_clicks_movs()->detach();
      LogMovimiento::destroy($log->id_log_movimiento);

      return 1;
    }
  }

  public function eliminarEventualidadMTM(Request $req)
  {
    $log = LogMovimiento::find($req['id_log_movimiento']);

    $log->tipo_movimiento()->dissociate();
    $log->estado_movimiento()->dissociate();
    $log->expediente()->dissociate();
    $log->controladores()->detach();
    foreach ($log->relevamientos_movimientos as $rel) {
      $rel->log_movimiento()->dissociate();
      RelevamientoMovimiento::destroy($rel->id_relev_mov);
    }
    LogMovimiento::destroy($log->id_log_movimiento);

    return 1;

  }

  public function eliminarMovimientoExpediente($id_log_movimiento)
  {
    $log = LogMovimiento::find($id_log_movimiento);
    $fiscalizaciones = $log->fiscalizaciones;
    if(isset($fiscalizaciones[0])){
      return 0;
    }else{
      //eliminar relevamientos
      $rels=$log->relevamientos_movimientos;
      if(isset($rels[0]))
      {
        foreach ($rels as $rel) {
          RelevamientoMovimientoController::getInstancia()->eliminarRelevamiento($rel->id_relev_mov);
        }
      }
      LogClicksMovController::getInstancia()->eliminar($log->id_log_movimiento);
      $log->tipo_movimiento()->dissociate();
      $log->estado_movimiento()->dissociate();
      $log->expediente()->dissociate();
      $log->controladores()->detach();
      LogMovimiento::destroy($log->id_log_movimiento);
      return 1;
    }
    return 0;

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
              ->get();
    return ['logs' => $logs];
  }

  /////////////////////EVENTUALIDADES DE MAQUINA////////////////////////////////
  //INTERVENCIONES MTM

  //se usa en el validar eventualidades
  public function obtenerMTMEv($id_relevamiento){
    $rel = RelevamientoMovimiento::find($id_relevamiento);

    $mtm = DB::table('maquina')
              ->select('maquina.*','isla.nro_isla','formula.*')
              ->join('isla','isla.id_isla','=','maquina.id_isla')
              ->join('formula','formula.id_formula','=','maquina.id_formula')
              ->join('relevamiento_movimiento','relevamiento_movimiento.id_maquina','=','maquina.id_maquina')
              ->where('relevamiento_movimiento.id_relev_mov','=',$id_relevamiento)
              ->get()
              ->first();


    $juegos = (Maquina::find($rel->id_maquina))->juegos;

    $toma=null;
    $fecha = null;
    $fisca = null;//fisca que hizp la toma del relevamiento
    $cargador = null;//fisca cargador
    $nombre= null;

    if($rel->id_fisca != null) $fisca = Usuario::find($rel->id_fisca);
    if($rel->id_cargador != null){
      $cargador = Usuario::find($rel->id_cargador);
    }else{
      $cargador = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    }

    if(isset($rel->toma_relevamiento_movimiento)){
      $toma=$rel->toma_relevamiento_movimiento;
      $fecha = $rel->fecha_relev_sala;
      $nombre= Juego::find($toma->juego)->nombre_juego;
    }

    return ['maquina' => $mtm, 'juegos'=> $juegos,'toma'=>$toma,
     'fiscalizador'=> $fisca,'cargador'=> $cargador,
     'tipo_movimiento' =>  $rel->log_movimiento->tipo_movimiento ,
     'fecha' => $fecha, 'nombre_juego' => $nombre];

  }

  //al final se va a mostrar estatico, pero si se puede buscar algunos viejos con los filtros
  public function buscarEventualidadesMTMs(Request $request){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

      Validator::make($request->all(), [
          'id_casino' => 'nullable',
          'fecha' => 'nullable|date',
          'id_tipo_movimiento' => 'nullable|exists:tipo_movimiento,id_tipo_movimiento'
      ], array(), self::$atributos)->after(function ($validator){})->validate();

      $reglas=array();

      if(isset($request->id_tipo_movimiento)){
        $reglas[]=['log_movimiento.id_tipo_movimiento','=', $request->id_tipo_movimiento];
      }

      if(isset($request->nro_admin)){
        $reglas[]=['relevamiento_movimiento.id_tipo_movimiento','=', $request->nro_admin];
      }

      $casinos = array();

      if(isset($request->id_casino)){
        $casinos[] = $request->id_casino;
      }else {
        foreach ($usuario->casinos as $casino) {
          $casinos[] = $casino->id_casino;
        }
      }

      if(empty($reglas) && !isset($request->fecha)){
        return $this->todasEventualidadesMTMs();
      }

      $reglas[]=['log_movimiento.tiene_expediente','=',0];
      // $reglas[]=['log_movimiento.id_expediente','is',null]; hay que usar wherenull

      $resultados = null;
      if(!isset($request->fecha)){
        $resultados= DB::table('log_movimiento')
                        ->select('log_movimiento.*','tipo_movimiento.*',
                          'estado_movimiento.descripcion as estado_descripcion',
                          'casino.*',
                          'tipo_movimiento.*')
                        ->join('casino','casino.id_casino','=','log_movimiento.id_casino')
                        ->join('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=','log_movimiento.id_tipo_movimiento')
                        ->join('estado_movimiento','estado_movimiento.id_estado_movimiento','=','log_movimiento.id_estado_movimiento')
                        ->leftJoin('relevamiento_movimiento','relevamiento_movimiento.id_log_movimiento','=','log_movimiento.id_log_movimiento')
                        ->where($reglas)
                        ->whereNull('log_movimiento.id_expediente')
                        ->where('log_movimiento.tiene_expediente','=', 0)
                        ->whereIn('log_movimiento.id_casino',$casinos)
                        ->distinct('log_movimiento.id_log_movimiento')
                        ->orderBy('log_movimiento.fecha','DES')
                        ->take(30)
                        ->get();
      }else{
        $fecha=explode("-", $request->fecha);
        $resultados= DB::table('log_movimiento')
                        ->select('log_movimiento.*','tipo_movimiento.*',
                          'estado_movimiento.descripcion as estado_descripcion',
                          'casino.*',
                          'tipo_movimiento.*')
                        ->join('casino','casino.id_casino','=','log_movimiento.id_casino')
                        ->join('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=','log_movimiento.id_tipo_movimiento')
                        ->join('estado_movimiento','estado_movimiento.id_estado_movimiento','=','log_movimiento.id_estado_movimiento')
                        ->leftJoin('relevamiento_movimiento','relevamiento_movimiento.id_log_movimiento','=','log_movimiento.id_log_movimiento')
                        ->where($reglas)
                        ->whereNull('log_movimiento.id_expediente')
                        ->whereIn('log_movimiento.id_casino',$casinos)
                        ->where('log_movimiento.tiene_expediente','=', 0)
                        ->whereYear('log_movimiento.fecha' , '=', $fecha[0])
                        ->whereMonth('log_movimiento.fecha','=', $fecha[1])
                        ->distinct('log_movimiento.id_log_movimiento')
                        ->orderBy('log_movimiento.fecha','DES')
                        ->take(30)
                        ->get();
      }

      $tipos = TipoMovimiento::all();
      $casinos = $usuario->casinos;
      $esControlador=UsuarioController::getInstancia()->usuarioEsControlador($usuario);


      return ['eventualidades'=>$resultados,
              'esControlador' =>$esControlador,/*$esControlador*/
              'tiposEventualidadesMTM'=> $tipos,
              'casinos' => $casinos];

  }

  //al final se va a mostrar estatico, pero si se puede buscar algunos viejos con los filtros
  public function todasEventualidadesMTMs(){//type: get

    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = array();
    foreach ($usuario->casinos as $casino) {
      $casinos[] = $casino->id_casino;
    }

    $resultados= DB::table('log_movimiento')
                    ->select('log_movimiento.*','tipo_movimiento.*',
                      'estado_movimiento.descripcion as estado_descripcion',
                      'casino.*',
                      'tipo_movimiento.*')
                    ->join('casino','casino.id_casino','=','log_movimiento.id_casino')
                    ->join('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=','log_movimiento.id_tipo_movimiento')
                    ->join('estado_movimiento','estado_movimiento.id_estado_movimiento','=','log_movimiento.id_estado_movimiento')
                    ->whereNull('log_movimiento.id_expediente')
                    ->where('log_movimiento.tiene_expediente','=',0)
                    ->whereIn('log_movimiento.id_casino',$casinos)
                    ->orderBy('log_movimiento.fecha','DES')
                    ->take(30)
                    ->get();

      $tipos = TipoMovimiento::whereNotIn('id_tipo_movimiento',[1,8,9])->get();//egreso , cambio_layout , denominacion , % devolucion , juego
      $casinos = $usuario->casinos;
      $esControlador=UsuarioController::getInstancia()->usuarioEsControlador($usuario);

      UsuarioController::getInstancia()->agregarSeccionReciente('Intervenciones MTM' , 'eventualidadesMTM');

      return view('eventualidadesMTM',
                  ['eventualidades'=>$resultados,
                   'esControlador' => $esControlador/*$esControlador*/,
                   'tiposEventualidadesMTM'=> $tipos,
                   'casinos' => $casinos]);

  }

  //para generar una nueva eventualidad por los fiscalizadores, en la cual afectan
  // a los datos de la maquina, como juego %dev.. etc
  public function maquinasACargar($id , $fecha=null){
    $log = LogMovimiento::find($id);
    $tipos = TipoMovimiento::whereNotIn('id_tipo_movimiento',[1,8])->get();
    $id_usuario = session('id_usuario');
    $user = Usuario::find($id_usuario); //es el usuario que se va a mostrar como que esta cargando
    // a la hora de cargar la eventualidad

    if($fecha != null){
      //sirve para ambos formatos de fecha
      $string = 1;
      if (DateTime::createFromFormat('Y-m-d G:i:s', $fecha) !== FALSE) {
        $string = "relevamiento_movimiento.fecha_relev_sala = '" . $fecha . "'";
      }
      if (DateTime::createFromFormat('Y-m-d', $fecha) !== FALSE) {
        $string = "DATE(relevamiento_movimiento.fecha_relev_sala) = " . $fecha;
      }
      $maquinas = DB::table('log_movimiento')
        ->select('relevamiento_movimiento.id_relev_mov','relevamiento_movimiento.id_estado_relevamiento','maquina.id_maquina','maquina.nro_admin','maquina.id_casino','relevamiento_movimiento.id_estado_relevamiento')
        ->join('relevamiento_movimiento','relevamiento_movimiento.id_log_movimiento','=','log_movimiento.id_log_movimiento')
        ->join('maquina','maquina.id_maquina','=','relevamiento_movimiento.id_maquina')
        ->where('log_movimiento.id_log_movimiento','=',$id)
        ->whereRaw($string)
        ->get();
    }else{
      $maquinas = DB::table('log_movimiento')
        ->select('relevamiento_movimiento.id_relev_mov','relevamiento_movimiento.id_estado_relevamiento','maquina.id_maquina','maquina.nro_admin','maquina.id_casino','relevamiento_movimiento.id_estado_relevamiento')
        ->join('relevamiento_movimiento','relevamiento_movimiento.id_log_movimiento','=','log_movimiento.id_log_movimiento')
        ->join('maquina','maquina.id_maquina','=','relevamiento_movimiento.id_maquina')
        ->where('log_movimiento.id_log_movimiento','=',$id)
        ->get();
    }

    return ['tiposEventualidadesMTM'=>$tipos,
            'cantidad_maquinas'=> $log->cant_maquinas,
            'casino'=> $log->id_casino,
            'fiscalizador_carga'=> $user,
            'relevamientos' => $maquinas];
  }

  //suponiendo que me va a enviar un array con los ids de maquina
  public function nuevaEventualidadMTM( Request $request){
    $validator =Validator::make($request->all(), [
        'id_tipo_movimiento' => 'required|exists:tipo_movimiento,id_tipo_movimiento',
        'maquinas' => 'required'
    ], array(), self::$atributos)->after(function($validator){

        if($validator->getData()['id_tipo_movimiento']==9){
          $validator->errors()->add('tipo_movimiento', 'No se ha seleccionado el tipo de movimiento.');
        }



      })->validate();

     if(isset($validator))
      {
        if ($validator->fails())
        {
          return [
                'errors' => $v->getMessageBag()->toArray()
            ];
        }
     }

    $logMovimiento = new LogMovimiento;
    $logMovimiento->fecha= date("Y-m-d");
    $logMovimiento->tiene_expediente = 0;
    $logMovimiento->tipo_movimiento()->associate($request['id_tipo_movimiento']);
    $logMovimiento->estado_movimiento()->associate(6);//creado
    $logMovimiento->estado_relevamiento()->associate(1);//generado
    $logMovimiento->save();



    foreach ($request['maquinas'] as $mtm) {
      $relevamiento = RelevamientoMovimiento::where([['id_maquina','=', $mtm['id_maquina']],['id_log_movimiento','=',$logMovimiento->id_log_movimiento]])->get()->first();
      if($relevamiento == null){
        $maq =Maquina::find($mtm['id_maquina']);
       RelevamientoMovimientoController::getInstancia()->crearRelevamientoMovimiento($logMovimiento->id_log_movimiento, $maq);
       $this->guardarIslasMovimiento($logMovimiento,$maq);
      }
    }


    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    foreach ($usuario->casinos as $casino) {
      $casinos[] = $casino->id_casino;
    }
    $casino = Casino::find($casinos[0]);

    $logMovimiento->casino()->associate($casinos[0]);
    $logMovimiento->save();

    // $view = View::make('planillaEventualidadesMTMs', compact('rels'));
    // $dompdf = new Dompdf();
    // $dompdf->set_paper('A4', 'portrait');
    // $dompdf->loadHtml($view->render());
    // $dompdf->render();
    // $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    // $dompdf->getCanvas()->page_text(20, 815, $casino->codigo."/".$logMovimiento->fecha, $font, 10, array(0,0,0));
    // $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    //
    // return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
    return $logMovimiento->id_log_movimiento;

  }

  public function cargarEventualidadMTM(Request $request){
    // Se cambia el validador para permitir ser nullos los datos, ya que cierta eventualiadades no ofrece la informacion suficente
    $validator =Validator::make($request->all(), [
        'id_log_movimiento' => 'required|exists:log_movimiento,id_log_movimiento',
        'id_cargador' => 'nullable|exists:usuario,id_usuario',
        'id_fiscalizador' => 'required|exists:usuario,id_usuario',
        'id_maquina' => 'required|exists:maquina,id_maquina',
        'juego' => 'required',
        'apuesta_max' => 'nullable| numeric| max:900000',
        'cant_lineas' => 'nullable|numeric| max:100000',
        'porcentaje_devolucion' => ['nullable','regex:/^\d\d?([,|.]\d\d?\d?)?$/'],
        'denominacion' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'cant_creditos' => 'nullable|numeric| max:100',
        'fecha_sala' => 'required|date',//fecha con dia y hora
        'observaciones' => 'nullable|max:280',
        'mac' => 'nullable | max:100',
        'sectorRelevadoEv' => 'required',
        'islaRelevadaEv' => 'required'

    ], array(), self::$atributos)->after(function($validator){
        if($validator->getData()['juego']==0 ){
            $validator->errors()->add('juego', 'No se ha seleccionado el juego.');
        }

        $log = LogMovimiento::find($validator->getData()['id_log_movimiento']);



        $maquina = Maquina::find($validator->getData()['id_maquina']);
        $aux=1;
        $formula = $maquina->formula;
        $contadores =$validator->getData()['contadores'];
        //por cada contador valido que esté cargado si es que la formula tenía
        //ese contador
        /*
        foreach ($contadores as $cont)
        {
            switch ($aux)
            {
              case 1:
                if($formula->cont1 !=null && $cont['valor'] == "")
                {
                  $validator->errors()->add('contadores', 'No se han cargado todos los contadores.');
                }
                break;
              case 2:
                if($formula->cont2 != null && $cont['valor'] == "")
                {
                  $validator->errors()->add('contadores', 'No se han cargado todos los contadores.');
                }
                break;
              case 3:
                if($formula->cont3 !=null && $cont['valor'] == "")
                {
                  $validator->errors()->add('contadores', 'No se han cargado todos los contadores.');
                }
                break;
              case 4:
                if($formula->cont4 !=null && $cont['valor'] == "")
                {
                  $validator->errors()->add('contadores', 'No se han cargado todos los contadores.');
                }
                break;
              case 5:
                if($formula->cont5 !=null && $cont['valor'] == "")
                {
                  $validator->errors()->add('contadores', 'No se han cargado todos los contadores.');
                }
                break;
              case 6:
                if($formula->cont6 !=null && $cont['valor'] == "")
                {
                  $validator->errors()->add('contadores', 'No se han cargado todos los contadores.');
                }
                break;
              default:
                # code...
                break;
            }
            $aux++;
        }
        */
      })->validate();

     if(isset($validator))
      {
        if ($validator->fails())
        {
          return [
                'errors' => $v->getMessageBag()->toArray()
            ];
        }
     }

     $log = LogMovimiento::find($request['id_log_movimiento']);
     //$log->tipo_movimiento()->associate($request['tipo_movimiento']);
     $cant_rels =count($log->relevamientos_movimientos);


     RelevamientoMovimientoController::getInstancia()->cargarTomaRelevamientoEv( $request['id_maquina'] , $request['contadores'],
      $request['juego'] , $request['apuesta_max'], $request['cant_lineas'], $request['porcentaje_devolucion'], $request['denominacion'] ,
      $request['cant_creditos'], $request['fecha_sala'], $request['observaciones'],
      $request['id_cargador'], $request['id_fiscalizador'], $request['mac'],$request['id_log_movimiento'],
      $request['sectorRelevadoEv'],$request['islaRelevadaEv']
      );

      $id_usuario = session('id_usuario');

     if($this->cargaFinalizadaEvMTM($log)){
       $log->estado_movimiento()->associate(1);//notificado
       $log->estado_relevamiento()->associate(3);//finalizado (de cargar)
       // notificaciones
       $usuarios = UsuarioController::getInstancia()->obtenerControladores($log->id_casino,$id_usuario);
       foreach ($usuarios as $user){
         $u = Usuario::find($user->id_usuario);
        if($u != null)  $u->notify(new NuevaIntervencionMTM($log));
       }
     }else{
       $log->estado_movimiento()->associate(8);//cargando
       $log->estado_relevamiento()->associate(2);
     }
     $log->save();

     return 1;
  }

  private function cargaFinalizadaEvMTM($logMovimiento){
    foreach ($logMovimiento->relevamientos_movimientos as $relevamiento) {
      if(!isset($relevamiento->toma_relevamiento_movimiento)){
        return false;
      }
    }
    return true;
  }

  public function tiposMovIntervMTM(){
    $tipos = TipoMovimiento::whereIn('id_tipo_movimiento',[1,2,3,4,5,6,7])->get();
    return ['tipos_movimientos' => $tipos];
  }

  //tipo: si es 1 = es nueva la planilla, si es 2 es que se imprime con la carga completa
  public function imprimirEventualidadMTM($id_log_mov, $tipo){

    $rels= array();
    $log = LogMovimiento::find($id_log_mov);
    $i = 0;//count($log->relevamientos_movimientos) - 1;
    $casino = $log->casino;
    foreach ($log->relevamientos_movimientos as $relev) {
      $rels[] = RelevamientoMovimientoController::getInstancia()->relevamientosIntervencionesMTM($relev->id_maquina,$i,$log->id_log_movimiento,$log->tipo_movimiento->descripcion, $tipo, $casino);
      $i++;
    }

    $view = View::make('planillaEventualidadesMTMs', compact('rels'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $casino->codigo."/".$log->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }

  public function relevamientosEvMTM($id_log_mov){
    $log = LogMovimiento::find($id_log_mov);
    $relevamientos = $log->relevamientos_movimientos;
    $maquinas = array();
    foreach ($relevamientos as $rel) {
      $maquinas[] = ['id_relevamiento' => $rel->id_relev_mov,
                     'nro_admin' => $rel->maquina->nro_admin,
                     'id_maquina' => $rel->maquina->id_maquina
                   ];
    }
    $id_usuario = session('id_usuario');
    $user = Usuario::find($id_usuario);
    return ['maquinas'=>$maquinas,'fiscalizador_carga'=> $user,'tipo_movimiento' => $log->tipo_movimiento->descripcion,
            'casino' => $log->casino];
  }

  public function obtenerDatosMTMEv($id_relev_mov){
    $relev = RelevamientoMovimiento::find($id_relev_mov);
    $mtm= Maquina::find($relev->id_maquina);
    $f = $mtm->formula;
    return ['mtm' => $mtm, 'formula'=> $f, 'juegos'=> $mtm->juegos,
     'isla'=> $mtm->isla];
  }

  //no se usa como pretendía, pero se usa
  public function validarEventualidad($id_movimiento){
    $logMovimiento = LogMovimiento::find($id_movimiento);
    return ['maquinas'=> $logMovimiento->relevamientos_movimientos];
  }

  public function validarRelevamientoEventualidad($id_relev_mov){
    //el request contiene id_relev_mov,los datos del relev_mov (), $validado (1 o 0)
    $id_usuario = session('id_usuario');
    $relev_mov = RelevamientoMovimiento::find($id_relev_mov);
    $logMov = LogMovimiento::find($relev_mov->id_log_movimiento);
    $id_usuario = session('id_usuario');
    if($this->noEsControlador($id_usuario,  $logMov)){
      $logMov->controladores()->attach($id_usuario);
      $logMov->save();
    }
    //a las tomas de los relevamientos las marco como validadas
    $razon = RelevamientoMovimientoController::getInstancia()->validarRelevamientoToma($relev_mov, 1);//retorna las observaciones de la toma
    $maquina = $relev_mov->maquina;
    $relss = RelevamientoMovimiento::where('id_log_movimiento','=',$logMov->id_log_movimiento)
              ->where('id_estado_relevamiento','=',4 )->get();
    //dd([count($logMov->relevamientos_movimientos),count($relss)]);
    if(count($logMov->relevamientos_movimientos) == count($relss)){
      $logMov->estado_relevamiento()->associate(4);
      $logMov->estado_movimiento()->associate(4);
      $logMov->save();
    }

    return ['id_estado_relevamiento'=> $relev_mov->id_estado_relevamiento];
  }

  ///////////PARA DENOMINACION Y DEVOLUCION/////////////////////////////////////

  public function obtenerMaquinasSector($id_sector){
      //dado un casino,devuelve sectores que concuerden con el nombre del sector
      $maquinas = DB::table('maquina')
                      ->select('maquina.*')
                      ->join('isla','isla.id_isla','=','maquina.id_isla')
                      ->join('sector','sector.id_sector','=','isla.id_sector')
                      ->where('sector.id_sector' , '=' , $id_sector)
                      ->get();

      $unidades = DB::table('unidad_medida')->select('unidad_medida.*')->get();

      return ['maquinas' => $maquinas,'unidades' => $unidades];
  }

  public function obtenerMaquinasIsla($id_isla){
      //dado un casino,devuelve sectores que concuerden con el nro admin dado
      $maquinas = DB::table('maquina')
                      ->select('maquina.*')
                      ->join('isla','isla.id_isla','=','maquina.id_isla')
                      ->where('isla.id_isla' , '=' , $id_isla)
                      ->get();

      $unidades = DB::table('unidad_medida')->select('unidad_medida.*')->get();
     return ['maquinas' => $maquinas,'unidades' => $unidades];
  }

  ///////////PRUEBAS////////////////////////////////////////////////////////////

  public function pruebasVarias(Request $req){
    //  NotaController::getInstancia()->guardarNota($req);

    return $this->guardarRelevamientoMovimientoIngreso(624,4666);
    //return ExpedienteController::getInstancia()->obtenerExpediente(1747);

  }


}
