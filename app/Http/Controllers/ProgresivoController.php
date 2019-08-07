<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Progresivo;
use App\TipoProgresivo;
use App\Juego;
use App\Pozo;
use App\NivelProgresivo;
use App\Maquina;
use App\Casino;
use Illuminate\Support\Facades\DB;
use Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\TipoProgresivoController;

class ProgresivoController extends Controller
{
  private static $atributos = [
    'nombre_progresivo' => 'Nombre del Progresivo',
    'tipoProgresivo' => 'Tipo de Progresivo',
    'niveles' => 'Niveles de Progresivo',
    'niveles.*.nro_nivel' => 'Nro Nivel',
    'niveles.*.nombre_nivel' => 'Nombre Nivel',
    'niveles.*.porc_oculto' => '% Oculto',
    'niveles.*.porc_visible' => '% Visible',
    'niveles.*.base' => 'Base',
    'niveles.*.maximo' => 'Máximo',
  ];

  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new ProgresivoController();
    }
    return self::$instance;
  }

  public function buscarTodos(){
    $progresivos = Progresivo::all();
    $casinos = Casino::all();
    UsuarioController::getInstancia()->agregarSeccionReciente('Progresivos' , 'progresivos');

    return view('seccionProgresivos', ['progresivos' => $progresivos,'casinos' => $casinos]);
  }


  public function getAll(){
    $todos = Progresivo::all();
    return $todos;
  }

  public function buscarProgresivoPorNombreYTipo($busqueda){
    $resultados = DB::table('progresivo')
      ->select('progresivo.id_progresivo','progresivo.nombre_progresivo','progresivo.linkeado','progresivo.individual')
      ->where('progresivo.nombre_progresivo' , 'like' , '%'.$busqueda.'%')
      ->get();

    return ['resultados' => $resultados];
  }

  public function buscarProgresivos(Request $request){
    $reglas = Array();
    if(!empty($request->nombre_progresivo))
      $reglas[]=['nombre', 'like', '%'.$request->nombre_progresivo.'%'];

    $sort_by = $request->sort_by;
    $resultados=DB::table('progresivo')
    ->select('progresivo.*')
    ->when($sort_by,function($query) use ($sort_by){
                    return $query->orderBy($sort_by);
                })
    ->where($reglas)->paginate($request->page_size);

    return $resultados;
  }

  public function buscarProgresivoLinkeadoPorNombre(Request $request){
      $resultados=Progresivo::where([['nombre_progresivo' , 'like' , '%' . $request->busqueda .'%' ] , ['linkeado' , '=' , 1] , ['individual' , '=' , 0]])->get();
      return ['progresivos' => $resultados];
  }

  public function obtenerProgresivo($id){
    //TODO: agregar columna id_casino a progresivos.
    //Retorno toda la informacion nesteada,
    // nose si hay una forma mejor.
    $progresivo = Progresivo::find($id);
    $maquinas_arr = array();
    $pozos_arr = array();

    if($progresivo != null){
      $pozos = $progresivo->pozos;

      if($pozos != null){
        foreach($pozos as $pozo){
          $pozo_arr = $pozo->toArray();
          $niveles = array();
          if($pozo->niveles != null){
            $niveles = $pozo->niveles->toArray();
          }
          $pozo_arr['niveles']=$niveles;
          $pozos_arr[]=$pozo_arr;
        }
      }

      $maquinas = $progresivo->maquinas;

      if($maquinas != null){
        foreach($maquinas as $maq){
          $maq_arr = $maq->toArray();
          $maq_arr["isla"] = $maq->isla->nro_isla;
          $maq_arr["sector"] = $maq->isla->sector->descripcion;
          $maquinas_arr[]=$maq_arr;
        }
      }

    }

    return ['progresivo' => $progresivo,
           'pozos' => $pozos_arr,
           'maquinas' => $maquinas_arr];
  }

  public function agregarNivel(Request $request,$id_pozo){
    //TODO: validate, ej id_pozo not null
    $nivel = new NivelProgresivo;
    $pozo = Pozo::find($id_pozo);
    if($pozo != null){
        $nivel->nro_nivel = $request->nro_nivel;
        $nivel->nombre_nivel = $request->nombre_nivel;
        $nivel->base = $request->base;
        $nivel->maximo = $request->maximo;
        $nivel->porc_visible = $request->porc_visible;
        $nivel->porc_oculto = $request->porc_oculto;
        $nivel->id_pozo = $id_pozo;
        $nivel->save();
        return $nivel;
    }
    return response()->json(['error' => 'id_pozo']);
  }

  public function modificarNivel(Request $request,$id_nivel_progresivo){
    //TODO: validate id_nivel_progresivo not null
    $nivel = NivelProgresivo::find($id_nivel_progresivo);
    if($nivel != null){
      $nivel->nro_nivel = $request->nro_nivel;
      $nivel->nombre_nivel = $request->nombre_nivel;
      $nivel->base = $request->base;
      $nivel->maximo = $request->maximo;
      $nivel->porc_visible = $request->porc_visible;
      $nivel->porc_oculto = $request->porc_oculto;
      $nivel->save();
      return $nivel;
    }
    return response()->json(['error' => 'id_nivel_progresivo']);
  }

  public function eliminarNivel($id_nivel_progresivo){
    $nivel = NivelProgresivo::find($id_nivel_progresivo);
    if($nivel == null){
      return response()->json(['error' => 'id_nivel_progresivo']);
    }
    $nivel->delete();
    return array();
  }

  public function crearPozo(Request $request){
    $progresivo = Progresivo::find($request->id_progresivo);
    if($progresivo == null){
      return response()->json(['error' => 'id_progresivo']);
    }

    $descripcion = 'Pozo';
    $pozo = new Pozo;
    $pozo->descripcion = $descripcion;
    $pozo->id_progresivo = $progresivo->id_progresivo;
    $pozo->save();
    return $pozo;
  }

  public function modificarPozo(Request $request,$id_pozo){
    //TODO: validate
    $pozo = Pozo::find($id_pozo);
    if($pozo == null){
      return response()->json(['error' => 'id_pozo']);
    }
    $pozo->descripcion = $request->descripcion;
    $pozo->save();
    return $pozo;
  }

  public function obtenerProgresivoPorIdMaquina($id_maquina){
    $maquina= Maquina::find($id_maquina);
    $pozo = $maquina->pozo;
    if($pozo == null ){
      $progresivo = '';
      $niveles_final = array();
    }else {
      $progresivo = $pozo->niveles_progresivo[0]->progresivo;
      foreach ($pozo->niveles_progresivo as $nivel) {
        $niveles_final [] = ['nivel' => $nivel , 'pivot_base' => $nivel->pivot->base]; //se envia el nivel en sí (con base default), y la base personalizada (en tabla pivote)
      }
    }

    return ['progresivo' => $progresivo,
            'niveles' => $niveles_final,
            'pozo' => $pozo];
  }

  /* Se comenta, cambió en la forma de tratar el progresivo
  public function guardarProgresivo(Request $request){
    Validator::make($request->all(), [
        'nombre' => 'required|max:45|unique:progresivo,nombre_progresivo',
        'tipo' => 'required|in:LINKEADO,INDIVIDUAL',
        'maximo' => 'required',
        'porc_recuperacion' => 'nullable',
        'niveles' => 'nullable',
        'pozos' => 'nullable',
        'pozos.*.niveles.*.nro_nivel' => 'nullable|integer',
        'pozos.*.niveles.*.nombre_nivel' => 'required|max:60',
        'pozos.*.niveles.*.porc_oculto' => ['nullable','regex:/^\d\d?([,|.]\d\d?)?$/'],
        'pozos.*.niveles.*.porc_visible' => ['required','regex:/^\d\d?([,|.]\d\d?)?$/'],
        'pozos.*.niveles.*.base' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'pozos.*.niveles.*.maximo' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'pozos.*.maquinas.*.id_maquina' =>  'required',
    ], array(), self::$atributos)->after(function ($validator){
      // por ahora no
    })->validate();

    $progresivo = new Progresivo;
    $progresivo->nombre_progresivo = $request->nombre;
    $progresivo->maximo = $request->maximo;
    $progresivo->porc_recuperacion= $request->porc_recuperacion;

    if($request->tipo == 'LINKEADO'){
        $progresivo->linkeado=1;
        $progresivo->individual=0;

    }else{//caso individual del progresivo
        $progresivo->linkeado=0;
        $progresivo->individual=1;
    }

    $progresivo->save();


    if($progresivo->linkeado==1){//@param: pozos -> arreglo
      $niveles_guardados = array(); //los niveles del primer pozo se toman como niveles por defecto, se guardan y luego se referencian los mismos
      $bandera=false;
      foreach ($request->pozos as $un_pozo) {
        $pozo= new Pozo;
        $pozo->save();
        if(isset($un_pozo['maquinas'])){
          foreach ($un_pozo['maquinas'] as $id_maquina) {
            $maquina = Maquina::find($id_maquina['id_maquina']);
            $maquina->pozo()->associate($pozo->id_pozo);
            $maquina->save();
          }
        }

        if(isset($un_pozo['niveles'])){
          foreach ($un_pozo['niveles'] as $index=>$nivel){
            if(!$bandera){
              $nuevoNivel=NivelProgresivoController::getInstancia()->guardarNivelProgresivo($nivel,$progresivo->id_progresivo);

              $pozo->niveles_progresivo()->syncWithoutDetaching([$nuevoNivel->id_nivel_progresivo => ['base' => $nivel['base']]]);
              $niveles_guardados[$index] = $nuevoNivel;
            }else{
              $pozo->niveles_progresivo()->syncWithoutDetaching([$niveles_guardados[$index]->id_nivel_progresivo => ['base' => $nivel['base']]]);
            }
          }
        }
        $bandera=true;//si ya guarde los niveles "por defecto (los primeros)"
      }

    }else{ // individual
      $pozo= new Pozo;
      $maquinas = array();
      $pozo->save();
      if(isset($request->pozos['maquinas'])){
        foreach ($request->pozos['maquinas'] as $id_maquina) {
          $maquina = Maquina::find($id_maquina);
          $maquina->pozo()->associate($pozo->id_pozo);
          $maquina->save();
        }
      }
      if(isset($request->pozos['niveles'])){
        foreach ($request->pozos['niveles'] as $nivel){
          $nuevoNivel=NivelProgresivoController::getInstancia()->guardarNivelProgresivo($nivel,$progresivo->id_progresivo);
          $pozo->niveles_progresivo()->syncWithoutDetaching([$nuevoNivel->id_nivel_progresivo => ['base' => null]]);
        }
      }
    }

    $this->borrarPozosVacios();

    return ['progresivo' => $progresivo , 'tipo' => $progresivo->tipoProgresivo()];
  }
*/

public function guardarProgresivo(Request $request){
  Validator::make($request->all(), [
      'nombre' => 'required|max:45',
      'tipo' => 'required|in:LINKEADO,INDIVIDUAL',
      'maximo' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
      'porc_recuperacion' => 'nullable',
      'niveles' => 'required',
      'pozos' => 'nullable',
      'niveles.*.nro_nivel' => 'nullable|integer',
      'niveles.*.nombre_nivel' => 'required|max:60',
      'niveles.*.porc_oculto' => ['nullable','regex:/^\d\d?([,|.]\d\d?)?$/'],
      'niveles.*.porc_visible' => ['required','regex:/^\d\d?([,|.]\d\d?)?$/'],
      'niveles.*.base' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
      'niveles.*.maximo' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
      'pozos.*.maquinas.*.id_maquina' =>  'required',
  ], array(), self::$atributos)->after(function ($validator){
    // TODO evaluar el valor maximo si es por progresivo y / o por nivel
  })->validate();

  $casinos = Usuario::find(session('id_usuario'))->casinos;

  $progresivo = new Progresivo;
  $progresivo->nombre_progresivo = $request->nombre;
  $progresivo->maximo = $request->maximo;
  // la creacion es solo para un casino, de tener varios, solo se crea para el primero
  // esta aclaracion solo impcata para el superusuario que es el unico con multiples casinos
  $progresivo->id_casino=$casino[0]->id_casino;
  //$progresivo->porc_recuperacion= $request->porc_recuperacion; dato que no se esta relevando

  if($request->tipo == 'LINKEADO'){
      $progresivo->linkeado=1;

  }else{//caso individual del progresivo
      $progresivo->linkeado=0;
  }

  $progresivo->save();


  if($progresivo->linkeado==1){//@param: pozos -> arreglo
    $niveles_guardados = array(); //los niveles del primer pozo se toman como niveles por defecto, se guardan y luego se referencian los mismos
    $bandera=false;
    foreach ($request->pozos as $un_pozo) {
      $pozo= new Pozo;
      $pozo->save();
      if(isset($un_pozo['maquinas'])){
        foreach ($un_pozo['maquinas'] as $id_maquina) {
          $maquina = Maquina::find($id_maquina['id_maquina']);
          $maquina->pozo()->associate($pozo->id_pozo);
          $maquina->save();
        }
      }

      if(isset($un_pozo['niveles'])){
        foreach ($un_pozo['niveles'] as $index=>$nivel){
          if(!$bandera){
            $nuevoNivel=NivelProgresivoController::getInstancia()->guardarNivelProgresivo($nivel,$progresivo->id_progresivo);

            $pozo->niveles_progresivo()->syncWithoutDetaching([$nuevoNivel->id_nivel_progresivo => ['base' => $nivel['base']]]);
            $niveles_guardados[$index] = $nuevoNivel;
          }else{
            $pozo->niveles_progresivo()->syncWithoutDetaching([$niveles_guardados[$index]->id_nivel_progresivo => ['base' => $nivel['base']]]);
          }
        }
      }
      $bandera=true;//si ya guarde los niveles "por defecto (los primeros)"
    }

  }else{ // individual
    $pozo= new Pozo;
    $maquinas = array();
    $pozo->save();
    if(isset($request->pozos['maquinas'])){
      foreach ($request->pozos['maquinas'] as $id_maquina) {
        $maquina = Maquina::find($id_maquina);
        $maquina->pozo()->associate($pozo->id_pozo);
        $maquina->save();
      }
    }
    if(isset($request->pozos['niveles'])){
      foreach ($request->pozos['niveles'] as $nivel){
        $nuevoNivel=NivelProgresivoController::getInstancia()->guardarNivelProgresivo($nivel,$progresivo->id_progresivo);
        $pozo->niveles_progresivo()->syncWithoutDetaching([$nuevoNivel->id_nivel_progresivo => ['base' => null]]);
      }
    }
  }

  $this->borrarPozosVacios();

  return ['progresivo' => $progresivo , 'tipo' => $progresivo->tipoProgresivo()];
}

  public function modificarProgresivo(Request $request){
    Validator::make($request->all(), [
        'id_progresivo' => 'required|exists:progresivo,id_progresivo',
        'nombre_progresivo' => 'nullable|max:45',
        'tipo' => 'required|in:INDIVIDUAL,LINKEADO',
        'niveles' => 'nullable',
        'niveles.*.id_nivel_progresivo' => 'nullable|exists:nivel_progresivo,id_nivel_progresivo',
        'niveles.*.nro_nivel' => 'required|integer',
        'niveles.*.nombre_nivel' => 'required|max:60',
        'niveles.*.porc_visible' => ['required','regex:/^\d\d?([,|.]\d\d?)?$/'],
        'niveles.*.porc_oculto' => ['nullable','regex:/^\d\d?([,|.]\d\d?)?$/'],
        'niveles.*.base' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'niveles.*.maximo' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
    ], array(), self::$atributos)->after(function ($validator){
      // por ahora no
    })->validate();

    $progresivo = Progresivo::find($request->id_progresivo);

    $progresivo->nombre_progresivo = $request->nombre;

    if($request->tipo  == "LINKEADO"){
        $progresivo->linkeado=1;
        $progresivo->individual=0;
    }
    else{
      $progresivo->linkeado=0;
      $progresivo->individual=1;
    }

    $progresivo->save();
    //copiado de modifcar gestion
    if($progresivo->linkeado==1){// @param: pozos -> arreglo

      $niveles_guardados = array(); //los niveles del primer pozo se toman como niveles por defecto, se guardan y luego se referencian los mismos
      $bandera=false;

        foreach ($request->pozos as $un_pozo) {
          $pozo= new Pozo;
          $pozo->save();
          if(isset($un_pozo['maquinas'])) {
            foreach ($un_pozo['maquinas'] as $id_maquina) {
              $maquina = Maquina::find($id_maquina['id_maquina']);
              $maquina->pozo()->associate($pozo->id_pozo);
              $maquina->save();
            }
          }

        if(!empty($un_pozo['niveles'])){
          if (isset($un_pozo['niveles'])) {
            foreach ($un_pozo['niveles'] as $index=>$nivel){
              if(!$bandera && !isset($nivel['id_nivel'])){
                $nuevoNivel=NivelProgresivoController::getInstancia()->guardarNivelProgresivo($nivel,$progresivo->id_progresivo);
                $pozo->niveles_progresivo()->syncWithoutDetaching([$nuevoNivel->id_nivel_progresivo => ['base' => $nivel['base']]]);
                $niveles_guardados[$index] = $nuevoNivel;
              }else if(!$bandera){
                $pozo->niveles_progresivo()->syncWithoutDetaching([$nivel['id_nivel'] => ['base' => $nivel['base']]]);
              }else {
                $pozo->niveles_progresivo()->syncWithoutDetaching([$niveles_guardados[$index]->id_nivel_progresivo => ['base' => $nivel['base']]]);

              }
            }
          }
        }
        $bandera=true;//si ya guarde los niveles "por defecto (los primeros)"
      }

    }else{// individual
      //eliminar pozos vacios ?
      $pozo= new Pozo;
      $maquinas = array();
      $pozo->save();
      if(!empty($request->pozos['maquinas'])){
        foreach ($request->pozos['maquinas'] as $id_maquina) {
          $maquina = Maquina::find($id_maquina['id_maquina']);
          $maquina->pozo()->associate($pozo->id_pozo);
          $maquina->save();
        }
      }else{

      }
      if(!empty($request->pozos['niveles'])){
        foreach ($request->pozos['niveles'] as $index=>$nivel){
          if($nivel['id_nivel'] == ''){
            $nuevoNivel=NivelProgresivoController::getInstancia()->guardarNivelProgresivo($nivel,$progresivo->id_progresivo);
            $pozo->niveles_progresivo()->syncWithoutDetaching([$nuevoNivel->id_nivel_progresivo => ['base' => $nivel['base']]]);
            $niveles_guardados[$index] = $nuevoNivel;
          }else {
            $pozo->niveles_progresivo()->syncWithoutDetaching([$nivel['id_nivel'] => ['base' => $nivel['base']]]);
          }
        }
      }
    }

    $this->borrarPozosVacios();

    return ['progresivo' => $progresivo , 'tipo' => $request->id_tipo_progresivo];
  }

  private function existeNivelProgresivo($id,$niveles){
    $result = false;
    for($i = 0;$i<count($niveles);$i++){
      if($id == $niveles[$i]['id_nivel_progresivo']){
        $result = true;
        break;
      }
    }
    return $result;
  }

  public function existenNivelSuperior($id_pozo){//true si base > 10000
      $pozo = Pozo::find($id_pozo);
      $bases = array();
      foreach ($pozo->niveles_progresivo as $nivel) {
          $base = $nivel->pivot->base != null ? $nivel->pivot->base : $nivel->base;
          if($base >= 10000){
            return true;
          }
      }

      return false;
  }

  public function eliminarProgresivo($id){
    $progresivo = Progresivo::find($id);
    $niveles = $progresivo->niveles;

    if(!empty($niveles)){
      foreach($niveles as $nivel){
        if(!empty($nivel->pozos)){
            foreach ($nivel->pozos as $pozo) { //por pozo saco las maquinas y niveles asociadas
              $pozo->niveles_progresivo()->detach();
              foreach ($pozo->maquinas as $maquina) {
                $maquina->pozo()->dissociate();
                $maquina->save();
              }
              $pozo->delete();
            }
        }
        NivelProgresivoController::getInstancia()->eliminarNivelProgresivo($nivel->id_nivel_progresivo,$progresivo->tipoProgresivo());
      }

    }

    if(!empty($progresivo->pruebas_progresivo)){
      foreach($progresivo->pruebas_progresivo as $prueba){
        $prueba->delete();
      }

    }

    $progresivo = Progresivo::destroy($id);

    return ['progresivo' => $progresivo];
  }

  /*******
  METODOS DE GESTIONAR MAQUINA
  CREACION Y MODIFICACION DESDE VISTA MAQUIANS. INVOCADO POR MODIFICARMAQUINA Y CREARMAQUINA (MTMCONTROLLER)
  *******/

  public function guardarProgresivo_gestionarMaquina($p,$MTM){

    Validator::make(
        [
          'nombre' => $p['nombre_progresivo'],
          'tipo' =>  $p['id_tipo'],
          'maximo' => $p['maximo'],
          'porc_recuperacion' =>$p['porcentaje_recuperacion'] ,
          'pozos' =>  $p['pozos'],
        ]
        , [
        'nombre' => 'required|max:45|unique:progresivo,nombre_progresivo',
        'tipo' => 'required|in:LINKEADO,INDIVIDUAL',
        'maximo' => 'nullable',
        'porc_recuperacion' => 'nullable',
        'pozos' => 'nullable',
        'pozos.*.niveles.*.nro_nivel' => 'nullable|integer',
        'pozos.*.niveles.*.nombre_nivel' => 'required|max:60',
        'pozos.*.niveles.*.porc_oculto' => ['nullable','regex:/^\d\d?([,|.]\d\d?)?$/'],
        'pozos.*.niveles.*.porc_visible' => ['required','regex:/^\d\d?([,|.]\d\d?)?$/'],
        'pozos.*.niveles.*.base' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'pozos.*.niveles.*.maximo' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'pozos.*.maquinas.*.id_maquina' =>  'required',
    ], array(), self::$atributos)->after(function ($validator){
      // por ahora no
    })->validate();

    $progresivo = new Progresivo;
    $progresivo->nombre_progresivo = $p['nombre_progresivo'];
    $progresivo->maximo = $p['maximo'];
    $progresivo->porc_recuperacion= $p['porcentaje_recuperacion'];

    if($p['id_tipo'] == 'LINKEADO'){
        $progresivo->linkeado=1;
        $progresivo->individual=0;
    }else{//caso individual del progresivo
        $progresivo->linkeado=0;
        $progresivo->individual=1;
    }

    $progresivo->save();

    if($progresivo->linkeado==1){// @param: pozos -> arreglo
      $niveles_guardados = array(); //los niveles del primer pozo se toman como niveles por defecto, se guardan y luego se referencian los mismos
      $bandera=false;

        foreach ($p['pozos'] as $un_pozo) {
          $pozo= new Pozo;
          $pozo->save();
          foreach ($un_pozo['maquinas'] as $id_maquina) {

              $maquina = $id_maquina['id_maquina'] == 0 ?   Maquina::find($MTM->id_maquina)  :   Maquina::find($id_maquina['id_maquina']);
              $maquina->pozo()->associate($pozo->id_pozo);
              $maquina->save();
          }

          if(!empty($un_pozo['niveles'])){
            foreach ($un_pozo['niveles'] as $index=>$nivel){
              if(!$bandera){
                $nuevoNivel=NivelProgresivoController::getInstancia()->guardarNivelProgresivo($nivel,$progresivo->id_progresivo);
                $pozo->niveles_progresivo()->syncWithoutDetaching([$nuevoNivel->id_nivel_progresivo => ['base' => $nivel['base']]]);
                $niveles_guardados[$index] = $nuevoNivel;
              }else{
                $pozo->niveles_progresivo()->syncWithoutDetaching([$niveles_guardados[$index]->id_nivel_progresivo => ['base' => $nivel['base']]]);
              }
            }
          }
          $bandera=true;//si ya guarde los niveles "por defecto (los primeros)"
      }

    }else{// individual
      $pozo= new Pozo;
      $maquinas = array();
      $pozo->save();
      if(!empty($p['pozos']['maquinas'])){
        foreach ($p['pozos']['maquinas'] as $id_maquina) {
          $maquina = Maquina::find($id_maquina);
          $maquina->pozo()->associate($pozo->id_pozo);
          $maquina->save();
        }
      }
      if(!empty($p['pozos']['niveles'])){
        foreach ($p['pozos']['niveles'] as $nivel){
          $nuevoNivel=NivelProgresivoController::getInstancia()->guardarNivelProgresivo($nivel,$progresivo->id_progresivo);
          $pozo->niveles_progresivo()->syncWithoutDetaching([$nuevoNivel->id_nivel_progresivo => ['base' => null]]);
        }
      }
    }

    $this->borrarPozosVacios();

    return $progresivo;
  }

  public function modificarProgresivo_gestionarMaquina($p){

    Validator::make(
        [
          'nombre' => $p['nombre_progresivo'],
          'tipo' =>  $p['id_tipo'],
          'maximo' => $p['maximo'],
          'porc_recuperacion' =>$p['porcentaje_recuperacion'] ,
          'pozos' =>  $p['pozos'],
        ]
        , [
        'nombre' => 'required|max:45',
        'tipo' => 'required|in:LINKEADO,INDIVIDUAL',
        'maximo' => 'nullable',
        'porc_recuperacion' => 'nullable',
        'pozos' => 'nullable',
        'pozos.*.niveles.*.nro_nivel' => 'nullable|integer',
        'pozos.*.niveles.*.nombre_nivel' => 'required|max:60',
        'pozos.*.niveles.*.porc_oculto' => ['nullable','regex:/^\d\d?([,|.]\d\d?)?$/'],
        'pozos.*.niveles.*.porc_visible' => ['required','regex:/^\d\d?([,|.]\d\d?)?$/'],
        'pozos.*.niveles.*.base' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'pozos.*.niveles.*.maximo' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'pozos.*.maquinas.*.id_maquina' =>  'required',
    ], array(), self::$atributos)->after(function ($validator){
      // por ahora no
    })->validate();

    $progresivo = Progresivo::find($p['id_progresivo']);
    $progresivo->nombre_progresivo = $p['nombre_progresivo'];
    $progresivo->maximo = $p['maximo'];
    $progresivo->porc_recuperacion= $p['porcentaje_recuperacion'];

    if($p['id_tipo'] == 'LINKEADO'){
        $progresivo->linkeado=1;
        $progresivo->individual=0;
    }else{//caso individual del progresivo
        $progresivo->linkeado=0;
        $progresivo->individual=1;
    }

    $progresivo->save();

    if($progresivo->linkeado==1){// @param: pozos -> arreglo

      $niveles_guardados = array(); //los niveles del primer pozo se toman como niveles por defecto, se guardan y luego se referencian los mismos
      $bandera=false;

        foreach ($p['pozos'] as $un_pozo) {
          $pozo= new Pozo;
          $pozo->save();
          foreach ($un_pozo['maquinas'] as $id_maquina) {
              $maquina = Maquina::find($id_maquina);
              $maquina->pozo()->associate($pozo->id_pozo);
              $maquina->save();
          }

        if(!empty($un_pozo['niveles'])){
          foreach ($un_pozo['niveles'] as $index=>$nivel){
            if(!$bandera && $nivel['id_nivel'] == 'undefined'){
              $nuevoNivel=NivelProgresivoController::getInstancia()->guardarNivelProgresivo($nivel,$progresivo->id_progresivo);
              $pozo->niveles_progresivo()->syncWithoutDetaching([$nuevoNivel->id_nivel_progresivo => ['base' => $nivel['base']]]);
              $niveles_guardados[$index] = $nuevoNivel;
            }else if(!$bandera){
              $pozo->niveles_progresivo()->syncWithoutDetaching([$nivel['id_nivel'] => ['base' => $nivel['base']]]);
            }else {
              $pozo->niveles_progresivo()->syncWithoutDetaching([$niveles_guardados[$index]->id_nivel_progresivo => ['base' => $nivel['base']]]);

            }
          }
        }
        $bandera=true;//si ya guarde los niveles "por defecto (los primeros)"
      }

    }else{// individual
      $pozo= new Pozo;
      $maquinas = array();
      $pozo->save();
      if(!empty($p['pozos']['maquinas'])){
        foreach ($p['pozos']['maquinas'] as $id_maquina) {
          $maquina = Maquina::find($id_maquina);
          $maquina->pozo()->associate($pozo->id_pozo);
          $maquina->save();
        }
      }
      if(!empty($p['pozos']['niveles'])){
        foreach ($p['pozos']['niveles'] as $nivel){
          if($nivel['id_nivel'] == 'undefined'){
            $nuevoNivel=NivelProgresivoController::getInstancia()->guardarNivelProgresivo($nivel,$progresivo->id_progresivo);
            $pozo->niveles_progresivo()->syncWithoutDetaching([$nuevoNivel->id_nivel_progresivo => ['base' => $nivel['base']]]);
            $niveles_guardados[$index] = $nuevoNivel;
          }else {
            $pozo->niveles_progresivo()->syncWithoutDetaching([$nivel['id_nivel'] => ['base' => $nivel['base']]]);
          }
        }
      }
    }

    $this->borrarPozosVacios();

    return $progresivo;
  }

  public function borrarPozosVacios(){
    $pozos=Pozo::all();
    foreach($pozos as $key => $pozo) {
        if($pozo->maquinas->count() == 0){
          $pozo->niveles_progresivo()->detach();
          $pozo->delete();
        }
    }

  }
}
