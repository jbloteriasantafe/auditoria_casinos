<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Casino;
use App\Turno;
use App\Mesas\Ficha;
use App\Mesas\FichaTieneCasino;
use Carbon\Carbon;
use Validator;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mesas\Turnos\TurnosController;
use App\Http\Controllers\UsuarioController;
use App\Usuario;

class CasinoController extends Controller
{
  private static $instance;

  private static $atributos = [
      'nombre' => 'Nombre del Casino',
      'codigo' => 'Código del Casino',
    ];

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new CasinoController();
    }
    return self::$instance;
  }

  public function buscarTodo(Request $request){
    $usuario = UsuarioController::getInstancia()->obtenerUsuario($request);
    if($usuario == null){
      return ['error' => 'No se puede acceder a la seccion casinos, usuario inexistente'];
    }

    UsuarioController::getInstancia()->agregarSeccionReciente('Casinos' , 'casinos');
    return view('Casinos.casinos')->with('casinos',$usuario->casinos);
  }

  //se usa en canon mesas
  public function getParaUsuario(){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

    $cas = $user->casinos->all();

    return $cas;
  }

  public function obtenerCasino(Request $request,$id){
    $usuario = UsuarioController::getInstancia()->obtenerUsuario($request);
    if($usuario == null || !$usuario->usuarioTieneCasino($id)){
      return ['error' => 'El usuario no tiene accesso a ese casino'];
    }

    $casino = Casino::find($id);
    $turnos = Turno::whereIn('id_casino',[$id])->get();
    $fichas_casino = $casino->fichas()->get()->toArray();
    $fichas = Ficha::groupBy('id_moneda','id_ficha','valor_ficha','created_at','deleted_at','updated_at')->orderBy('valor_ficha','desc')->get()->toArray();
    return ['casino' => $casino,
    'turnos' => $turnos,
    'fichas_casino' => $fichas_casino,
    'fichas' => $fichas
    ];
  }

  public function obtenerTurno($id){
    $dia_semana = date('w');

    // // desde la base se gestiona al domingo como 7
     if ($dia_semana==0){
       $dia_semana=7;
     }
    $hora_dia = date('H:i:s');

    $entrada_min = Turno::where([['id_casino' , $id] , ['dia_desde' ,'<=', $dia_semana], ['dia_hasta' ,'>=', $dia_semana]])->min('entrada');

    if($hora_dia < $entrada_min){
      if($dia_semana == 1){
        $dia_semana= 7;
      }else {
        $dia_semana = $dia_semana - 1 ;
      }
    }

    $turnos = Turno::where([['id_casino' , $id] , ['dia_desde' ,'<=', $dia_semana], ['dia_hasta' ,'>=', $dia_semana]])->get();
    $retorno= array();
    foreach($turnos as $turno){
      if($turno->entrada >= $turno->salida && ($hora_dia >= $turno->entrada || $hora_dia <= $turno->salida) ){
        $retorno[] = $turno->nro_turno;
      }else{
        if($hora_dia >= $turno->entrada && $hora_dia <= $turno->salida){
          $retorno[] = $turno->nro_turno;
        }
      }
    }

    if(count($retorno) != 1){
      $codigo = 500;
    }else{
      $codigo = 200;

    }
    return ['turno' => $retorno,
            'CODIGO' => $codigo];
  }

  public function guardarCasino(Request $request){
    $usuario = UsuarioController::getInstancia()->obtenerUsuario($request);
    if($usuario == null || !$usuario->es_superusuario){
      return ['error' => 'El usuario no es superusuario, por ende no puede crear casinos'];
    }

    $validator=Validator::make($request->all(), [
      'nombre' => 'required|unique:casino,nombre|max:45',
      'codigo' => 'required|unique:casino,codigo|max:3',
      'turnos' =>'required',
      'turnos.*.nro' => 'required|integer',
      'turnos.*.desde' => ['required','regex:/^[1-7]$/'],
      'turnos.*.hasta' => ['required','regex:/^[1-7]$/'],
      'turnos.*.entrada' => 'required|date_format:H:i',
      'turnos.*.salida' => 'required|date_format:H:i',
      'turnos.*.hora_propuesta' => 'required|date_format:H:i',
      'fecha_inicio' => 'required|date_format:Y-m-d',
      'porcentaje_sorteo_mesas' => 'required|integer|max:100',
      'fichas_pesos' => 'nullable',
      'fichas_pesos.*.id_ficha' => 'required|exists:ficha,id_ficha',
      'fichas_dolares' => 'nullable',
      'fichas_dolares.*.id_ficha' => 'required|exists:ficha,id_ficha',
      'fichas_nuevas' => 'nullable',
      'fichas_nuevas.*.valor_ficha' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
      'fichas_nuevas.*.id_moneda' => 'required|exists:moneda,id_moneda',
    ],array(),self::$atributos)->after(function ($validator){})->validate();
    if(isset($validator)){
      if ($validator->fails()){
        return ['errors' => $validator->messages()->toJson()];
      }
    }

    $casino = new Casino;
    $casino->nombre = $request->nombre;
    $casino->codigo = $request->codigo;
    $casino->fecha_inicio = $request->fecha_inicio;
    $casino->porcentaje_sorteo_mesas = $request->porcentaje_sorteo_mesas;
    $casino->save();

    $tcontroller = new TurnosController;
    foreach ($request['turnos'] as $tt) {
      $tcontroller->guardar($tt,$casino->id_casino);
    }
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $user->casinos()->attach($casino->id_casino);

    $fichas_nuevas = $this->crearFichas($request['fichas_nuevas']);

    //TODO: Hardcodeado
    if(!array_key_exists('fichas_pesos',$request)  && !isset($request['fichas_pesos']) ){
      $request['fichas_pesos'] = array();
    }
    if(!array_key_exists('fichas_dolares',$request) && !isset($request['fichas_dolares']) ){
      $request['fichas_dolares'] = array();
    }

    if(array_key_exists("1",$fichas_nuevas)){
      $request['fichas_pesos'] = array_merge($fichas_nuevas["1"],$request['fichas_pesos']);
    }
    if(array_key_exists("2",$fichas_nuevas)){
      $request['fichas_dolares'] = array_merge($fichas_nuevas["2"],$request['fichas_dolares']);
    }

    $this->asociarFichas($request['fichas_pesos'],$request['fichas_dolares'],$casino->id_casino);

    return ['casino' => $casino];
  }

  public function modificarCasino(Request $request){
    $validator=Validator::make($request->all(), [
      'codigo' => ['required','max:3', Rule::unique('casino')->ignore( $request->id_casino,'id_casino')],
      'turnos' =>'required',
      'turnos.*.nro' => 'required|integer',
      'turnos.*.desde' => ['required','regex:/^[1-7]$/'],
      'turnos.*.hasta' => ['required','regex:/^[1-7]$/'],
      'turnos.*.entrada' => 'required|date_format:H:i',
      'turnos.*.salida' => 'required|date_format:H:i',
      'turnos.*.hora_propuesta' => 'required|date_format:H:i',
      'porcentaje_sorteo_mesas' => 'required|integer|max:100',
      'fichas_pesos' => 'nullable',
      'fichas_pesos.*.id_ficha' => 'required|exists:ficha,id_ficha',
      'fichas_dolares' => 'nullable',
      'fichas_dolares.*.id_ficha' => 'required|exists:ficha,id_ficha',
      'fichas_nuevas' => 'nullable',
      'fichas_nuevas.*.valor_ficha' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
      'fichas_nuevas.*.id_moneda' => 'required|exists:moneda,id_moneda',
    ],array(),self::$atributos)->after(function ($validator){})->validate();

    $casino = Casino::find($request->id_casino);
    $casino->codigo = $request->codigo;
    $casino->porcentaje_sorteo_mesas = $request->porcentaje_sorteo_mesas;
    $casino->save();

    $usuario = UsuarioController::getInstancia()->obtenerUsuario($request);
    if($usuario == null || !$usuario->usuarioTieneCasino($casino->id_casino)){
      return ['error' => 'El usuario no tiene accesso a ese casino'];
    }

    $casino = Casino::find($request->id_casino);
    $casino->codigo = $request->codigo;
    $casino->porcentaje_sorteo_mesas = $request->porcentaje_sorteo_mesas;
    $casino->save();

    $this->asociarTurnos($request->turnos,$casino);

    $fichas_nuevas = $this->crearFichas($request['fichas_nuevas']);


    //TODO: Hardcodeado
    if(!array_key_exists('fichas_pesos',$request) && !isset($request['fichas_pesos'])){
      $request['fichas_pesos'] = array();
    }
    if(!array_key_exists('fichas_dolares',$request) && !isset($request['fichas_dolares'])){
      $request['fichas_dolares'] = array();
    }

    if(array_key_exists("1",$fichas_nuevas)){
      $request['fichas_pesos'] = array_merge($fichas_nuevas["1"],$request['fichas_pesos']);
    }

    if(array_key_exists("2",$fichas_nuevas)){
      $request['fichas_dolares'] = array_merge($fichas_nuevas["2"],$request['fichas_dolares']);
    }

    $this->asociarFichas($request['fichas_pesos'],$request['fichas_dolares'],$casino->id_casino);

    return ['casino' => $casino];
  }

private function asociarTurnos($turnos, $casino){
    $turnos_anteriores = $casino->turnos;
    $array_nuevos = array();
    //update or create
    foreach ($turnos as $t) {
      $tuur = Turno::updateOrCreate(['nro_turno' => $t['nro'],   'dia_desde' => $t['desde'],
                                     'dia_hasta' => $t['hasta'], 'entrada'   => $t['entrada'],
                                     'salida' => $t['salida'],   'hora_propuesta' => $t['hora_propuesta'],
                                     'id_casino' => $casino->id_casino]);
      $array_nuevos[] = $tuur->id_turno;
    }
    //y despues las que tenia left outer join las nuevas con collections las eliminadas
    $filtered = $turnos_anteriores->whereNotIn('id_turno', $array_nuevos);

    foreach ($filtered as $turno) {
      $turno->delete();//softdelete
    }
  }

  public function eliminarCasino($id){
    $usuario = UsuarioController::getInstancia()->obtenerUsuario($request);
    if($usuario == null || !$usuario->es_superusuario){
      return ['error' => 'El usuario no es superusuario, por ende no puede crear casinos'];
    }

    $casino = Casino::destroy($id);
    return ['casino' => $casino];
  }

  public function getAll(){
    $usuario = UsuarioController::getInstancia()->obtenerUsuario($request);
    if($usuario == null || !$usuario->es_superusuario){
      return ['error' => 'El usuario no es superusuario, por ende no puede crear casinos'];
    }
    $todos=Casino::all();
    return $todos;
  }

  public function getFichas(Request $request){
    $fichas = Ficha::groupBy('id_moneda','id_ficha','valor_ficha','created_at','deleted_at','updated_at')->orderBy('valor_ficha','asc')->get()->toArray();
    return ['fichas' => $fichas];
  }

  public function asociarFichas($fichas_pesos,$fichas_dolares,$id_casino){
    $casino = Casino::find($id_casino);
    //si el casino tenia asociadas fichas
    if($casino->fichas->count() >0){
      $fichas_anteriores = $casino->fichas;
      $array_nuevas = array();
      //update or create
      if(!empty($fichas_pesos)){
        foreach ($fichas_pesos as $ficha) {
          $array_nuevas[] = $ficha['id_ficha'];
          FichaTieneCasino::updateOrCreate(['id_casino' => $id_casino, 'id_ficha' => $ficha['id_ficha']]);
        }
      }
      if(!empty($fichas_dolares)){
        foreach ($fichas_dolares as $ficha) {
          $array_nuevas[] = $ficha['id_ficha'];
          FichaTieneCasino::updateOrCreate(['id_casino' => $id_casino, 'id_ficha' => $ficha['id_ficha']]);
        }
      }
      //y despues las que tenia left outer join las nuevas con collections las eliminadas
      $filtered = $fichas_anteriores->whereNotIn('id_ficha', $array_nuevas);

      foreach ($filtered as $ficha) {
        $ficha->delete();
      }
    }else{
      //si no
      if(!empty($fichas_pesos)){
        foreach ($fichas_pesos as $ficha) {
          $f = new FichaTieneCasino;
          $f->ficha()->associate($ficha['id_ficha']);
          $f->casino()->associate($id_casino);
          $f->save();
        }
      }
      if(!empty($fichas_dolares)){
        foreach ($fichas_dolares as $ficha) {
          $f = new FichaTieneCasino;
          $f->ficha()->associate($ficha['id_ficha']);
          $f->casino()->associate($id_casino);
          $f->save();
        }
      }
    }

  }
  public function crearFichas($fichas_nuevas){
    $fichas = array();
    if(!empty($fichas_nuevas)){
      foreach ($fichas_nuevas as $ficha) {
        $f = new Ficha;
        $valor = $ficha['valor_ficha'];
        $id_moneda = $ficha['id_moneda'];
        $f->valor_ficha = $valor;
        $f->moneda()->associate($id_moneda);
        $f->save();

        if(!array_key_exists($id_moneda,$fichas)){
          $fichas[$id_moneda] = array();
        }

        $ficha_map = array(
          "id_moneda" => $id_moneda,
          "valor_ficha" => $valor,
          "id_ficha" => $f->id_ficha
        );

        $fichas[$id_moneda][] = $ficha_map;
      }
    }

    return $fichas;
  }
}
