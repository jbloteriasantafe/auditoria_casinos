<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use View;
use App\Evento;
use App\Usuario;
use App\Rol;
use App\TipoEvento;
use App\Casino;
use App\Notifications\CalendarioEvento;


class CalendarioController extends Controller
{
  private static $instance;

  private static $atributos = [
    'inicio' => 'coso',
    'fin' => 'coso',
    'titulo' => 'coso',
    'descripcion' => 'coso',
    'color'=> 'coso'
  ];


  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new CalendarioController();
    }
    return self::$instance;
  }

  public function verMes($mes , $anio){
    $eventos = Evento::whereMonth('fecha_inicio',$mes)->whereYear('fecha_inicio',$anio)->get();
    $arreglo_eventos= array();
    foreach ($eventos as $evento) {
      $aux = new \stdClass();
      $aux->evento = $evento;
      $aux->tipo_evento = $evento->tipo_evento;
      $arreglo_eventos[]= $aux;
    }
    return ["eventos" => $arreglo_eventos];
  }

/*Retorna todos los eventos del mes actual*/
  public function buscarEventos(){
    $hoyY = date('Y');
    $hoyM = date('m');
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos_usuario = array();

    foreach($usuario->casinos as $casino) {
      $casinos_usuario[] = $casino->id_casino;
    }

    $eventos = DB::table('evento')
                  ->select('evento.*','tipo_evento.descripcion as tipo_evento', 'tipo_evento.color_back as fondo','tipo_evento.color_text as texto')
                  ->join('tipo_evento','tipo_evento.id_tipo_evento','=','evento.id_tipo_evento')
                  ->whereMonth('fecha_inicio',$hoyM)
                  ->whereYear('fecha_inicio',$hoyY)
                  ->whereIn('evento.id_casino', $casinos_usuario)
                  ->get();
    return ['eventos'=>$eventos];
    //return ['m'=> $hoyM,'y'=>$hoyY];
  }

  public function getOpciones(){
    return['roles' => Rol::all(),
     'casinos' => Casino::all(),
      'tipos_eventos' => TipoEvento::all()];
  }

  public function crearEvento(Request $request){
    //falta validar las cosas :))
    Validator::make($request->all(), [
        'inicio' => 'required|date',
        'fin' => 'required|date',
        'titulo' => 'required',
        'descripcion' => 'required',
        'id_casino' => 'required',
        'id_tipo_evento' => 'required',
        'id_rol' =>'required',
        'desde' => '',
        'hasta' => ''
    ], array(), self::$atributos)->after(function ($validator){})->validate();

    $evento = new Evento;
    $evento->fecha_inicio = $request->inicio;
    $evento->fecha_fin = $request->fin;
    $evento->hora_inicio = $request->desde;
    $evento->hora_fin = $request->hasta;
    $evento->titulo = $request->titulo;
    $evento->descripcion = $request->descripcion;
    $evento->casino()->associate($request->id_casino);
    $evento->tipo_evento()->associate($request->id_tipo_evento);
    $evento->save();

    $usuarios = UsuarioController::getInstancia()->obtenerUsuariosRol($request->id_casino, $request->id_rol);
    foreach ($usuarios as $user){
      $u = Usuario::find($user->id_usuario);
      //$u->notify(new CalendarioEvento($evento));
    }

    return ['evento' => $evento,'tipo' => TipoEvento::find($request->id_tipo_evento)];

  }

  public function crearEventoMovimiento($inicio,$fin,$titulo,$descripcion,$idCasino,$idFiscaslizacion){

    $evento = new Evento;
    $evento->fecha_inicio = $inicio;
    $evento->fecha_fin = $fin;
    $evento->titulo = $titulo;
    $evento->descripcion = $descripcion;
    $evento->casino()->associate($idCasino);
    $evento->tipo_evento()->associate(1);
    // se supone que el tipo de evento 1 está destinado a los movimientos de mtm
    $evento->fiscalizacion()->associate($idFiscaslizacion);
    $evento->save();

  }

  public function modificarEvento(Request $request){
    $ev = Evento::find($request['id']);
    $ev->fecha_inicio = $request['inicio'];
    $ev->fecha_fin = $request['fin'];
    $ev->save();
    return ['evento' => $ev];
  }

  public function getEvento($id){
    $ev = Evento::find($id);
    $inicio= $ev->fecha_inicio;
    $fin = $ev->fecha_fin;
    return ['evento'=> $ev, 'casino' => $ev->casino, 'tipo_evento' => $ev->tipo_evento];
  }

  public function eliminarEvento($id){
    Evento::destroy($id);
    return 1;
  }

  public function marcarRealizado(Evento $evento){
    $evento->realizado=1;
    $evento->save();
  }

  public function crearTipoEvento(Request $request){
    Validator::make($request->all(), [
        'descripcion' => 'required|unique:tipo_evento,descripcion',
        'fondo' => 'required|unique:tipo_evento,color_back',
        'texto' => 'required|string'
    ], array(), self::$atributos)->after(function ($validator){})->validate();
    $tipo = new TipoEvento;
    $tipo->descripcion = $request->descripcion;
    $tipo->color_back = $request->fondo;
    $tipo->color_text= $request->texto;
    $tipo->save();
    return $tipo;
  }

}
