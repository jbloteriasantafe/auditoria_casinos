<?php

namespace App\Http\Controllers\Bingo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\DB;
use Validator;

use App\Bingo\SesionBingo;
use App\Bingo\PremioBingo;
use App\Bingo\Canon;

class GestionController extends Controller{

    public function index(){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));

      $casinos=array();
      foreach($usuario['usuario']->casinos as $casino){
        $casinos[]=$casino->id_casino;
      }

      UsuarioController::getInstancia()->agregarSeccionReciente('Bingo' , 'bingo');


      return view('Bingo.gestion', ['casinos' => $casinos]);
    }

    public function buscarPremio(Request $request){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));

      $casinos=array();
      foreach($usuario['usuario']->casinos as $casino){
        $casinos[]=$casino->id_casino;
      }

      $sort_by = $request->sort_by;

      $reglas = array();
      
      if($request->casino!=0){
        $reglas[]=['casino.id_casino', '=', $request->casino];
      }

      $resultados = DB::table('bingo_premio')
                         ->select('bingo_premio.*', 'casino.nombre')
                         ->leftJoin('casino' , 'bingo_premio.id_casino','=','casino.id_casino')
                         ->when($sort_by,function($query) use ($sort_by){
                          return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                        })
                      ->where($reglas)
                      ->paginate($request->page_size);


      UsuarioController::getInstancia()->agregarSeccionReciente('Bingo' ,'bingo');

     return $resultados;
    }

    public function buscarCanon(Request $request){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));

      $casinos=array();
      foreach($usuario['usuario']->casinos as $casino){
        $casinos[]=$casino->id_casino;
      }

      $sort_by = $request->sort_by;

      $resultados = DB::table('bingo_canon')
                         ->select('bingo_canon.*', 'casino.nombre')
                         ->leftJoin('casino' , 'bingo_canon.id_casino','=','casino.id_casino')
                         ->when($sort_by,function($query) use ($sort_by){
                          return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                        })
                      ->paginate($request->page_size);


      UsuarioController::getInstancia()->agregarSeccionReciente('Bingo' ,'bingo');

     return $resultados;
    }

    public function guardarPremio(Request $request){
      //Validación de los datos
      Validator::make($request->all(), [
            'nombre_premio' => 'required',
            'porcentaje_premio' => 'required|numeric',
            'bola_tope' => 'required|numeric',
            'tipo_premio' => 'required|numeric',
            'casino_premio' => 'required|numeric',
        ])->validate();


      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario;

      //Se crea una nueva sesión y se le cargan los datos
      $premio = new PremioBingo;

      $premio->nombre_premio = $request->nombre_premio;
      $premio->porcentaje = $request->porcentaje_premio;
      $premio->bola_tope = $request->bola_tope;
      $premio->tipo_premio = $request->tipo_premio;
      $premio->id_casino = $request->casino_premio;

      $premio->save();


      return ['premio' => $premio];
    }

    public function guardarCanon(Request $request){
      //Validación de los datos
      Validator::make($request->all(), [
            'fecha_inicio' => 'required|date',
            'porcentaje_canon' => 'required|numeric',
            'id_casino' => 'required|numeric',
        ])->validate();


      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario;

      //Se crea una nueva sesión y se le cargan los datos
      $canon = new Canon;

      $canon->fecha_inicio = $request->fecha_inicio;
      $canon->porcentaje = $request->porcentaje_canon;
      $canon->id_casino = $request->id_casino;

      $canon->save();


      return ['canon' => $canon];
    }

    public function modificarPremio(Request $request){
      //Validación de los datos
      Validator::make($request->all(), [
            'nombre_premio' => 'required',
            'porcentaje_premio' => 'required|numeric',
            'bola_tope' => 'required|numeric',
            'tipo_premio' => 'required|numeric',
            'casino_premio' => 'required|numeric',
        ])->validate();

      $premio = PremioBingo::findOrFail($request->id_premio);

      $premio->nombre_premio = $request->nombre_premio;
      $premio->porcentaje = $request->porcentaje_premio;
      $premio->bola_tope = $request->bola_tope;
      $premio->tipo_premio = $request->tipo_premio;
      $premio->id_casino = $request->casino_premio;

      $premio->save();

      return ['premio' => $premio];
    }

    public function modificarCanon(Request $request){
      //Validación de los datos
      Validator::make($request->all(), [
            'fecha_inicio' => 'required|date',
            'porcentaje_canon' => 'required|numeric',
            'id_casino' => 'required|numeric',
        ])->validate();

        $canon = Canon::findOrFail($request->id_canon);

        $canon->fecha_inicio = $request->fecha_inicio;
        $canon->porcentaje = $request->porcentaje_canon;
        $canon->id_casino = $request->id_casino;

        $canon->save();

        return ['canon' => $canon];
    }

    public function eliminarPremio($id){

      $premio = PremioBingo::findorfail($id);

      $premio->delete();

      return ['premio' => $premio];
    }

    public function eliminarCanon($id){

      $canon = Canon::findorfail($id);

      $canon->delete();

      return ['canon' => $canon];
    }

    public function obtenerPremio($id){
      $premio = PremioBingo::findOrFail($id);
      return $premio;
  }

    public function obtenerCanon($id){
      $canon = Canon::findOrFail($id);
      return $canon;
  }
}
