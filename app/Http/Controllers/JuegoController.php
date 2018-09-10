<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Juego;
use App\TablaPago;
use App\Casino;
use App\GliSoft;
use App\Maquina;
use Validator;

class JuegoController extends Controller
{
  private static $atributos = [
    'nombre_juego' => 'Nombre de Juego',
    'cod_identificacion' => 'Código de Identificación',
    'tablasDePago.*.codigo' => 'Código de Identificación',
  ];

  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new JuegoController();
    }
    return self::$instance;
  }

  public function buscarTodo(){
    $uc = UsuarioController::getInstancia();
    $casinos = Casino::all();
    $uc->agregarSeccionReciente('Juegos','juegos');
    return view('seccionJuegos' , ['casinos' => $casinos]);
  }

  public function obtenerJuego($id){
    $juego = Juego::find($id);
    $maquinas= array();
    foreach ($juego->maquinas_juegos as $key => $mtm) {
      $maquina = new \stdClass();
      $maquina->id_maquina = $mtm->id_maquina;
      $maquina->id_casino = $mtm->id_casino;
      $maquina->nro_admin = $mtm->nro_admin;
      $maquina->porcentaje_devolucion =  $mtm->pivot->porcentaje_devolucion;
      $maquina->denominacion = $mtm->pivot->denominacion;
      $maquinas[] = $maquina;
    }
    $tabla = TablaPago::where('id_juego', '=', $id)->get();
    return ['juego' => $juego , 'tablasDePago' => $tabla, 'maquinas' => $maquinas];
  }

  public function encontrarOCrear($juego){
        $resultado=$this->buscarJuegoPorNombre($juego);
        if(count($resultado)==0){
            $juegoNuevo=new Juego;
            $juegoNuevo->nombre_juego=trim($juego);
            $juegoNuevo->save();
        }else{
            $juegoNuevo=$resultado[0];
        }
        return $juegoNuevo;
  }

  public function validarJuegoTemporal(Request $request){

    Validator::make($request->all() , [
      'id_juego' => 'nullable|integer|exists:juego,id_juego',
      'nombre_juego' => 'required',
      'id_tabla_pago' => 'nullable|integer|exists:juego,id_juego',
      'codigo_tabla_pago' => 'required',
    ])->after(function ($validator) {
        if($validator->getData()['id_juego'] != 0){
         $res= Juego::where('id_juego' , '=' ,$validator->getData()['id_juego'])->get();
         if($res[0]->nombre_juego !=  $validator->getData()['nombre_juego']){
           $validator->errors()->add('nombre_juego','El nombre del Juego ya está tomado');
         }
        }else{

        }
    })->validate();

  }

  public function guardarJuego(Request $request){
      //nombre de la var en js, para unique nombre de la tabla, nombre del campo que debe ser unico
    Validator::make($request->all(), [
      'nombre_juego' => 'required|unique:juego,nombre_juego|max:100',
      'cod_identificacion' => ['nullable','regex:/^\d?\w(.|-|_|\d|\w)*$/','unique:juego,cod_identificacion','max:100'],
      'tabla_pago.*' => 'nullable',
      'tabla_pago.*.id_tabla_pago' => 'nullable',
      'tabla_pago.*.codigo' => 'required',
      'maquinas.*' => 'nullable',
      'maquinas.*.nro_admin' => 'required',
      'maquinas.*.id_maquina' => 'required',
      'maquinas.*.denominacion' => 'nullable',
      'maquinas.*.porcentaje' => 'nullable',
      'id_progresivo' => 'nullable',
    ], array(), self::$atributos)->validate();

    $juego = new Juego;
    $juego->nombre_juego = $request->nombre_juego;
    //$juego->cod_identificacion= $request->cod_identificacion;
    $juego->save();

    if(isset($request->maquinas)){
      foreach ($request->maquinas as $maquina) {
        if($maquina['id_maquina'] == 0){
          $mtm = Maquina::where([['id_casino' , $maquina['id_casino']] , ['nro_admin' , $maquina['nro_admin']]])->first();
        }else {
          $mtm = Maquina::find($maquina['id_maquina']);
        }
        if($mtm != null){
          $mtm->juegos()->syncWithoutDetaching([$juego->id_juego => ['denominacion' => $maquina['denominacion'] ,'porcentaje_devolucion' => $maquina['porcentaje']]]);
        }
      }
    }

    if(!empty($request->tabla_pago)){
      foreach ($request->tabla_pago as $tabla){
        TablaPagoController::getInstancia()->guardarTablaPago($tabla,$juego->id_juego);
      }
    }

    return ['juego' => $juego];
  }

  public function guardarJuego_gestionarMaquina($nombre_juego,$arreglo_tablas){
    //funcion encargada de crear juego si este fue creado en "GESTIONAR MÁQUINA"
    Validator::make(['nombre_juego' => $nombre_juego], [
      'nombre_juego' => 'required|unique:juego,nombre_juego|max:100',
    ], array(), self::$atributos)->validate();

    $juego = new Juego;
    $juego->nombre_juego = $nombre_juego;
    $juego->save();

    if(!empty($arreglo_tablas)){//si no viene vacio
      foreach ($arreglo_tablas as $tabla){
        TablaPagoController::getInstancia()->guardarTablaPago($tabla,$juego->id_juego);
      }
    }

    return $juego;
  }

  public function modificarJuego(Request $request){

    Validator::make($request->all(), [
      'nombre_juego' => 'required|max:100',
      'cod_identificacion' => ['nullable','regex:/^\d?\w(.|-|_|\d|\w)*$/','max:100'],
      'tabla_pago.*' => 'nullable',
      'tabla_pago.*.id_tabla_pago' => 'nullable',
      'tabla_pago.*.codigo' => 'required',
      'maquinas.*' => 'nullable',
      'maquinas.*.nro_admin' => 'required',
      'maquinas.*.id_maquina' => 'required',
      'maquinas.*.denominacion' => 'nullable',
      'maquinas.*.porcentaje' => 'nullable',
      'id_progresivo' => 'nullable',
    ], array(), self::$atributos)->after(function ($validator) {

        if($validator->getData()['id_juego'] != 0){

        }

    })->validate();

    $juego = Juego::find($request->id_juego);
    $juego->nombre_juego= $request->nombre_juego;
    //$juego->cod_identificacion= $request->cod_identificacion;
    $juego->save();

    if(isset($request->tabla_pago)){
      foreach ($juego->tablasPago as $tabla) {
        $tabla->delete();
      };
      foreach ($request->tabla_pago as $key => $tabla) {
        TablaPagoController::getInstancia()->guardarTablaPago($tabla,$juego->id_juego);
      };
    }
    if(isset($request->maquinas)){
      $juego->maquinas_juegos()->detach();
      foreach ($request->maquinas as $maquina){
        if ($maquina['id_maquina'] == 0) {
          $mtm = Maquina::where([['id_casino' , $maquina['id_casino']],['nro_admin', $maquina['nro_admin']]])->first();
        }else {
          $mtm = Maquina::find($maquina['id_maquina']);
        }
        $mtm->juegos()->syncWithoutDetaching([$juego->id_juego => ['denominacion' => $maquina['denominacion'] ,'porcentaje_devolucion' => $maquina['porcentaje']]]);
      }
    }

    return ['juego' => $juego];
  }

  private function existeTablaPago($id,$tablas){
    $result = false;
    for($i = 0;$i<count($tablas);$i++){
      if($id == $tablas[$i]['id_tabla_pago']){
        $result = true;
        break;
      }
    }
    return $result;
  }

  public function eliminarJuego($id){
    $juego = Juego::find($id);
    foreach ($juego->tablasPago as $tabla) {
      TablaPagoController::getInstancia()->eliminarTablaPago($tabla->id_tabla_pago);
    }
    $juego->delete();
    return ['juego' => $juego];
  }

  public function getAll(){
    $todos=Juego::all();
    return $todos;
  }

  //busca juegos bajo el criterio "contiene". @param nombre_juego, cod_identificacion
  public function buscarJuegoPorCodigoYNombre($busqueda){
    $resultados=Juego::where('nombre_juego' , 'like' , $busqueda . '%')->get();
                      //->orWhere('cod_identificacion' , 'like' , $busqueda . '%')->get();

    return ['resultados' => $resultados];
  }

  //busca UN juego que coincida con el nombre  @param $nombre_juego
  public function buscarJuegoPorNombre($nombre_juego){
    $resultado=Juego::where('nombre_juego' , '=' , trim($nombre_juego))->get();
    return $resultado;
  }

  public function buscarJuegoMovimientos($nombre_juego){
    $resultado=Juego::where('nombre_juego' , 'like' , '%' .$nombre_juego.'%')->get();
    return ['juegos' =>$resultado];
  }


  public function buscarJuegos(Request $request){
    $reglas=array();
    if(!empty($request->nombreJuego) ){
      $reglas[]=['juego.nombre_juego', 'like' , '%' . $request->nombreJuego  .'%'];
    }
    if(!empty($request->codigoId)){
      $reglas[]=['gli_soft.cod_identificacion', 'like' , '%' . $request->codigoId  .'%'];
    }

    $sort_by = $request->sort_by;

    $resultados=DB::table('juego')->select('juego.*')
    ->leftJoin('gli_soft','gli_soft.id_gli_soft','=','juego.id_gli_soft')
    ->when($sort_by,function($query) use ($sort_by){
                    return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                })
    ->where($reglas)->paginate($request->page_size);

    return $resultados;
  }

  public function desasociarGLI($id_gli_soft){
     $juegos=GliSoft::find($id_gli_soft)->juegos;
      foreach ($juegos as $juego) {
        $juego->GliSoft()->dissociate();
        $juego->save();
      }
  }

  public function asociarGLI($listaJuegos , $id_gli_soft){
    foreach ($listaJuegos as $id_juego) {
       $juego=Juego::find($id_juego);
       $juego->GliSoft()->associate($id_gli_soft);
       $juego->save();
      }
  }

  public function obtenerTablasDePago($id){
    $juego=Juego::find($id);
    if($juego != null){
    return['tablasDePago' => $juego->tablasPago];
  }else{
    return['tablasDePago' => null];
  }
  }

}