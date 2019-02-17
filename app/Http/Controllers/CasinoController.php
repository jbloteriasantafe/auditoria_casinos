<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Casino;
use App\Turno;

class CasinoController extends Controller
{
  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new CasinoController();
    }
    return self::$instance;
  }

  public function buscarTodo(){
    $casinos = Casino::all();

    UsuarioController::getInstancia()->agregarSeccionReciente('Casinos' , 'casinos');
    return view('seccionCasinos')->with('casinos',$casinos);
  }

  public function obtenerCasino($id){
    $casino = Casino::find($id);
    return ['casino' => $casino];
  }

  public function guardarCasino(Request $request){
    $this->validate($request, [
      'nombre' => 'required|unique:casino,nombre|max:45',
      'codigo' => 'required|unique:casino,codigo|max:3',
    ]);
    $casino = new Casino;
    $casino->nombre = $request->nombre;
    $casino->codigo = $request->codigo;
    $casino->save();
    return ['casino' => $casino];
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

  public function modificarCasino(Request $request){
    $this->validate($request, [
      'nombre' => ['required','max:45', Rule::unique('casino')->ignore( $request->id_casino,'id_casino')],
      'codigo' => ['required','max:3', Rule::unique('casino')->ignore( $request->id_casino,'id_casino')],
    ]);
    $casino = Casino::find($request->id_casino);
    $casino->nombre = $request->nombre;
    $casino->codigo = $request->codigo;
    $casino->save();
    return ['casino' => $casino];
  }

  public function eliminarCasino($id){
    $casino = Casino::destroy($id);
    return ['casino' => $casino];
  }

  public function getAll(){
    $todos=Casino::all();
    return $todos;
  }

}
