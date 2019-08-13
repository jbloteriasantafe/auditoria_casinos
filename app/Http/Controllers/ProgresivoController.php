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
    'id_progresivo' => 'ID del progresivo',
    'nombre' => 'Nombre del Progresivo',
    'porc_recuperacion' => '% de recuperacion',
    'pozos' => 'Pozos del progresivo',
    'pozos.*.id_pozo' => 'ID del pozo',
    'pozos.*.descripcion' => 'Descripcion del pozo',
    'pozos.*.niveles' => 'Niveles del pozo',
    'pozos.*.niveles.*.id_nivel_progresivo' => 'ID del nivel',
    'pozos.*.niveles.*.nro_nivel' => 'Nro Nivel',
    'pozos.*.niveles.*.nombre_nivel' => 'Nombre Nivel',
    'pozos.*.niveles.*.porc_oculto' => '% Oculto',
    'pozos.*.niveles.*.porc_visible' => '% Visible',
    'pozos.*.niveles.*.base' => 'Base',
    'pozos.*.niveles.*.maximo' => 'Máximo',
    'maquinas.*.id_maquina' => 'ID de la maquina asociada al progresivo'
  ];

  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new ProgresivoController();
    }
    return self::$instance;
  }

  public function getAll(){
    $todos = Progresivo::all();
    return $todos;
  }

  public function buscarTodos(){
    $progresivos = Progresivo::all();
    $casinos = Casino::all();
    UsuarioController::getInstancia()->agregarSeccionReciente('Progresivos' , 'progresivos');
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];

    if($user->es_superusuario){
      return view('seccionProgresivos',
      ['progresivos' => $progresivos,
      'casinos' => $casinos]);
    }
    else{
      $casino = $user->casinos->first();
      return view('seccionProgresivos',
      ['progresivos' => $casino->progresivos,
      'casinos' => $user->casinos]);
    }
  }

  public function buscarProgresivos(Request $request){
    if($request->id_casino == null) return array();

    $where_casino = false;
    $where_nombre = false;
    if($request->id_casino != 0){
      $casino = Casino::find($request->id_casino);
      //El casino no existe.
      if($casino == null) return array();
      $where_casino = true;
    }
    if($request->nombre_progresivo != null &&
       $request->nombre_progresivo != ''){
      $where_nombre = true;
    }

    //Checkeo que solo el superusuario puede buscar todo.
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    if($request->id_casino == 0 && !$user->es_superusuario){
      return array();
    }

    if($request->id_casino != 0 && !$user->usuarioTieneCasino($request->id_casino)){
      return array();
    }

    $sort_by = $request->sort_by;
    $resultados=
    DB::table('progresivo')
    ->select('progresivo.*')
    ->when($sort_by,
           function($query) use ($sort_by){
             return $query->orderBy($sort_by);
           });

    if($where_casino){
      $resultados=$resultados->where('progresivo.id_casino','=',$casino->id_casino);
    }

    if($where_nombre){
      $filtro = '%' . $request->nombre_progresivo . '%';
      $resultados=$resultados->where('progresivo.nombre','like',$filtro);
    }

    return $resultados->paginate($request->page_size);
  }


  private function datosMaquinasCasino($id_casino = null){
    $query =
    "select maq.id_maquina,
            maq.nro_admin,maq.marca_juego,
            CONCAT(maq.nro_admin,cas.codigo) as nombre,
            ifnull(isla.nro_isla,'-') as isla,
            ifnull(sec.descripcion,'-') as sector
    from maquina as maq
    join casino as cas on (maq.id_casino = cas.id_casino)
    left join isla on (maq.id_isla = isla.id_isla)
    left join sector as sec on (isla.id_sector = sec.id_sector)
    where maq.deleted_at is NULL";
    if($id_casino != null){
      $query = $query . " and cas.id_casino = :id_casino";
    }
    return DB::select(DB::raw($query),['id_casino' => $id_casino]);
  }

  private function datosMaquinasProgresivo($id_progresivo = null){
    $query =
    "select maq.id_maquina,
            maq.nro_admin,
            maq.marca_juego,
            CONCAT(maq.nro_admin,cas.codigo) as nombre,
            ifnull(isla.nro_isla,'-') as isla,
            ifnull(sec.descripcion,'-') as sector
    from progresivo as prog
    join maquina_tiene_progresivo as maq_prog on (prog.id_progresivo = maq_prog.id_progresivo)
    join maquina as maq on (maq.id_maquina = maq_prog.id_maquina)
    join casino as cas on (maq.id_casino = cas.id_casino)
    left join isla on (maq.id_isla = isla.id_isla)
    left join sector as sec on (isla.id_sector = sec.id_sector)
    where maq.deleted_at is NULL";
    if($id_progresivo != null){
      $query = $query . " and prog.id_progresivo = :id_progresivo";
    }
    return DB::select(DB::raw($query),['id_progresivo' => $id_progresivo]);
  }

  public function buscarMaquinas(Request $request,$id_casino){
    //TODO: Deberia retornar las maquinas que estan dadas de baja,
    //i.e. tienen el parametro deleted_at seteado?
    //Agregarle un progresivo a una maquina borrada...
    if($id_casino == null) return array();
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    if($id_casino == 0){
      if($user->es_superusuario) return $this->datosMaquinasCasino();
      else return array();
    }

    $casino = Casino::find($id_casino);
    if($casino == null || !$user->usuarioTieneCasino($id_casino)){
        return array();
    }

    return $this->datosMaquinasCasino($casino->id_casino);
  }


  public function obtenerProgresivo($id){
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
        $maquinas_arr = $this->datosMaquinasProgresivo($progresivo->id_progresivo);
      }

    }

    return ['progresivo' => $progresivo,
           'pozos' => $pozos_arr,
           'maquinas' => $maquinas_arr];
  }

  private function errorOut($map){
    return response()->json(['errors' => $map],422);
  }

  public function crearProgresivo(Request $request){
    if($request->id_progresivo == null || $request->id_progresivo != -1){
      return $this->errorOut(['id_progresivo' => 'Nulo o malformado']);
    }
    if($request->id_casino == null || Casino::find($request->id_casino) == null){
      return $this->errorOut(['id_casino' => 'Nulo o no existe']);
    }
    if(isset($request->maquinas)){
      $valido = $this->checkCasinoMaquinas($request->maquinas,$request->id_casino);
      if(!$valido){
        return $this->errorOut(['id_casino' => 'Maquinas y casino tienen casinos distinto']);
      }
    }

    $progresivo = new Progresivo;
    $progresivo->nombre = $request->nombre;
    $progresivo->porc_recup = 0;
    $progresivo->id_casino = $request->id_casino;
    $progresivo->save();
    $request->id_progresivo = $progresivo->id_progresivo;
    return $this->modificarProgresivo($request,$progresivo->id_progresivo,false);
  }

  public function modificarProgresivo(Request $request,$id_progresivo,$check_maquinas = true){
    Validator::make($request->all(), [
        'id_progresivo' => 'required|integer',
        'nombre' => 'required',
        'porc_recup' => 'required',
        'pozos' => 'nullable',
        'maquinas' => 'nullable',
        'pozos.*.id_pozo' => 'required | integer',
        'pozos.*.descripcion' => 'required',
        'pozos.*.niveles' => 'nullable',
        'pozos.*.niveles.*.id_nivel_progresivo' => 'required|integer',
        'niveles.*.nro_nivel' => 'required|integer',
        'niveles.*.nombre_nivel' => 'required',
        'niveles.*.porc_oculto' => 'nullable',
        'niveles.*.porc_visible' => 'nullable',
        'niveles.*.base' => 'nullable',
        'niveles.*.maximo' => 'nullable',
        'maquinas' => 'nullable',
        'maquinas.*.id_maquina' => 'required',
    ], array(), self::$atributos)->after(function ($validator){
    })->validate();

    if($check_maquinas){
      if(isset($request->maquinas)){
        $valido = $this->checkCasinoMaquinas($request->maquinas,$request->id_casino);
        if(!$valido){
          return $this->errorOut(['id_casino' => 'Maquinas y casino tienen casinos distinto']);
        }
      }
    }

    $progresivo = Progresivo::with('pozos.niveles','maquinas')
    ->whereIn('id_progresivo',[$request->id_progresivo])->first();

    if($progresivo == null){
      return $this->errorOut(['id_progresivo' => 'Progresivo no existe']);
    }


    $progresivo->nombre = $request->nombre;
    $progresivo->porc_recup = $request->porc_recup;
    $progresivo->save();

    $aux = [];
    if(isset($request->pozos)){
      $aux = $request->pozos;
    }
    $this->actualizarPozos($progresivo,$aux);

    $aux = [];
    if(isset($request->maquinas)){
      $aux = $request->maquinas;
    }
    $this->actualizarMaquinas($progresivo,$aux);

    return $progresivo->toArray();
  }

  public function eliminarProgresivo($id_progresivo){
    if($id_progresivo == null) return $this->errorOut(['id_progresivo' => 'Progresivo nulo']);
    $progresivo = Progresivo::find($id_progresivo);
    if($progresivo == null) return $this->errorOut(['id_progresivo' => 'Progresivo no existe']);

    $progresivo->maquinas()->sync([]);

    foreach($progresivo->pozos as $pozo){
      foreach($pozo->niveles as $nivel){
        $nivel->delete();
      }
      $pozo->delete();
    }

    $progresivo->delete();
  }

  private function actualizarPozos($progresivo,$pozos){
    if($progresivo == null || $pozos == null) return;

    $pozosAgregOModif = [];
    foreach($pozos as $pozo){
      $pozo_bd = null;

      if($pozo['id_pozo'] != -1){
        $pozo_bd = Pozo::find($pozo['id_pozo']);
      }
      else{
        $pozo_bd = new Pozo;
      }

      if($pozo_bd != null){
        $pozo_bd->descripcion = $pozo['descripcion'];
        $pozo_bd->id_progresivo = $progresivo->id_progresivo;
        $pozo_bd->save();
        $pozosAgregOModif[] = $pozo_bd->id_pozo;
        $niveles_aux = [];
        if(array_key_exists('niveles',$pozo) && $pozo['niveles'] != null){
          $niveles_aux = $pozo['niveles'];
        }
        $this->actualizarNiveles($pozo_bd,$niveles_aux);
      }
    }

    $pozos_bd = $progresivo->pozos;

    foreach($pozos_bd as $p){
      if(!in_array($p->id_pozo,$pozosAgregOModif)){
        $this->actualizarNiveles($p,[]);
        $p->delete();
      }
    }

  }
  private function actualizarNiveles($pozo,$niveles){
    if($pozo == null || $niveles == null) return;

    $nivelesAgregOModif = [];
    foreach($niveles as $nivel){
      $nivel_bd = null;
      if($nivel['id_nivel_progresivo'] != -1){
        $nivel_bd = NivelProgresivo::find($nivel['id_nivel_progresivo']);
      }
      else{
        $nivel_bd = new NivelProgresivo;
      }

      if($nivel_bd != null){
        $nivel_bd->nro_nivel = $nivel['nro_nivel'];
        $nivel_bd->nombre_nivel = $nivel['nombre_nivel'];
        $nivel_bd->base = $nivel['base'];
        $nivel_bd->porc_oculto = $nivel['porc_oculto'];
        $nivel_bd->porc_visible = $nivel['porc_visible'];
        $nivel_bd->maximo = $nivel['maximo'];
        $nivel_bd->id_pozo = $pozo->id_pozo;
        $nivel_bd->save();
        $nivelesAgregOModif[] = $nivel_bd->id_nivel_progresivo;
      }
    }

    $niveles_bd = $pozo->niveles;
    foreach($niveles_bd as $nivel_bd){
      if(!in_array($nivel_bd->id_nivel_progresivo,$nivelesAgregOModif)){
        $nivel_bd->delete();
      }
    }
  }

  private function actualizarMaquinas($progresivo,$maquinas){
    if($progresivo == null || $maquinas == null) return;

    $maquinasAgregadas = [];
    foreach($maquinas as $maq){
      $id = $maq['id_maquina'];
      $maquina_bd = Maquina::find($id);
      if($maquina_bd == null) continue;
      $maquinasAgregadas[] = $id;
    }

    $progresivo->maquinas()->sync($maquinasAgregadas);
    $progresivo->save();
  }

  public function agregarNivel(Request $request,$id_pozo){
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
    return null;
  }

  public function modificarNivel(Request $request,$id_nivel_progresivo){
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
    return null;
  }

  public function eliminarNivel($id_nivel_progresivo){
    $nivel = NivelProgresivo::find($id_nivel_progresivo);
    if($nivel == null){
      return false;
    }
    $nivel->delete();
    return true;
  }

  public function crearPozo(Request $request){
    $progresivo = Progresivo::find($request->id_progresivo);
    if($progresivo == null){
      return null;
    }

    $descripcion = 'Pozo';
    $pozo = new Pozo;
    $pozo->descripcion = $descripcion;
    $pozo->id_progresivo = $progresivo->id_progresivo;
    $pozo->save();
    return $pozo;
  }

  public function modificarPozo(Request $request,$id_pozo){
    $pozo = Pozo::find($id_pozo);
    if($pozo == null){
      return null;
    }
    $pozo->descripcion = $request->descripcion;
    $pozo->save();
    return $pozo;
  }

  public function eliminarPozo($id_pozo){
    $pozo = Pozo::find($id_pozo);
    if($pozo == null){
      return false;
    }

    $niveles = $pozo->niveles;

    foreach($niveles as $nivel){
      $this->eliminarNivel($nivel->id_nivel_progresivo);
    }

    $pozo->delete();

    return true;
  }

  public function checkCasinoMaquinas($maquinas,$id_casino){
    foreach ($maquinas as $maq) {
      $maq_bd = Maquina::find($maq['id_maquina']);
      if($maq_bd->id_casino != $id_casino) return false;
    }
    return true;
  }

  //OLD CODE!!
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

  public function buscarProgresivoLinkeadoPorNombre(Request $request){
      $resultados=Progresivo::where([['nombre_progresivo' , 'like' , '%' . $request->busqueda .'%' ] , ['linkeado' , '=' , 1] , ['individual' , '=' , 0]])->get();
      return ['progresivos' => $resultados];
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

  /*
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
  }*/

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

/*
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
  }*/

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


    public function buscarProgresivoPorNombreYTipo($busqueda){
      $resultados = DB::table('progresivo')
        ->select('progresivo.id_progresivo','progresivo.nombre_progresivo','progresivo.linkeado','progresivo.individual')
        ->where('progresivo.nombre_progresivo' , 'like' , '%'.$busqueda.'%')
        ->get();

      return ['resultados' => $resultados];
    }
}
