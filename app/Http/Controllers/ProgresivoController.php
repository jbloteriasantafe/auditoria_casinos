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
use Illuminate\Support\Facades\Storage;

class ProgresivoController extends Controller
{
  private static $atributos = [
    'id_progresivo' => 'ID del progresivo',
    'nombre' => 'Nombre del Progresivo',
    'porc_recuperacion' => '% de recuperacion',
    'id_casino' => 'Casino donde esta el progresivo',
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
    if($request->id_casino === null) return array();

    $where_casino = false;
    $where_nombre = false;
    if($request->id_casino != 0){
      $casino = Casino::find($request->id_casino);
      //El casino no existe.
      if($casino === null) return array();
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
           })
    ->where('progresivo.es_individual','=',0);

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
    "select maq.id_maquina as id_maquina,
            maq.nro_admin as nro_admin,
            maq.marca_juego as marca_juego,
            CONCAT(maq.nro_admin,cas.codigo) as nombre,
            ifnull(isla.nro_isla,'-') as isla,
            ifnull(sec.descripcion,'-') as sector
    from maquina as maq
    join casino as cas on (maq.id_casino = cas.id_casino)
    left join isla on (maq.id_isla = isla.id_isla)
    left join sector as sec on (isla.id_sector = sec.id_sector)
    where maq.deleted_at is NULL";

    $parametros = array();
    if($id_casino != null){
      $query = $query . " and cas.id_casino = :id_casino";
      $parametros['id_casino'] = $id_casino;
    }
    return DB::select(DB::raw($query),$parametros);
  }

  private function datosMaquinasProgresivo($id_progresivo = null,$limit = null){
    $query =
    "select maq.id_maquina as id_maquina,
            maq.nro_admin as nro_admin,
            maq.marca_juego as marca_juego,
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

    $parametros = array();
    if($id_progresivo != null){
      $query = $query . " and prog.id_progresivo = :id_progresivo";
      $parametros['id_progresivo'] = $id_progresivo;
    }
    if($limit != null){
      $query = $query . " limit :limit";
      $parametros['limit'] = $limit;
    }
    return DB::select(DB::raw($query),$parametros);
  }



  public function buscarMaquinas(Request $request,$id_casino){
    //TODO: Deberia retornar las maquinas que estan dadas de baja,
    //i.e. tienen el parametro deleted_at seteado?
    //Agregarle un progresivo a una maquina borrada...
    if($id_casino === null) return array();
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    if($id_casino == 0){
      if($user->es_superusuario) return $this->datosMaquinasCasino();
      else return array();
    }

    $casino = Casino::find($id_casino);
    if($casino === null || !$user->usuarioTieneCasino($id_casino)){
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
    Validator::make($request->all(),[
      'id_progresivo' => 'required|integer',
      'nombre'        => 'required|max:100',
      'porc_recup'    => 'required|numeric|min:0|max:100',
      'id_casino'     => 'required|integer'
    ],array(),self::$atributos)->after(function ($validator){})->validate();

    if($request->id_progresivo != -1){
      return $this->errorOut(['id_progresivo' => 'Malformado']);
    }

    if(isset($request->maquinas)){
      $valido = $this->checkCasinoMaquinas($request->maquinas,$request->id_casino);
      if(!$valido){
        return $this->errorOut(['id_casino' => 'Maquinas y casino tienen casinos distinto']);
      }
    }

    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    if(!$user->es_superusuario && !$user->usuarioTieneCasino($id_casino)){
      return $this->errorOut(['id_casino' => 'El usuario no puede administrar ese casino']);
    }

    DB::transaction(function() use ($request){
      $progresivo = new Progresivo;
      $progresivo->nombre = $request->nombre;
      $progresivo->porc_recup = $request->porc_recup;
      $progresivo->id_casino = $request->id_casino;
      $progresivo->es_individual = 0;
      $progresivo->save();

      $request->id_progresivo = $progresivo->id_progresivo;
      $this->modificarProgresivoAux($request);
    });
  }

  public function modificarProgresivo(Request $request,$id_progresivo){
    Validator::make($request->all(), [
        'id_progresivo'                         => 'required|integer',
        'nombre'                                => 'required|max:100',
        'porc_recup'                            => 'required|numeric|min:0|max:100',
        'id_casino'                             => 'required|integer',
        'maquinas'                              => 'nullable|array',
        'maquinas.*.id_maquina'                 => 'required_if:maquinas,array|integer',
        'pozos'                                 => 'nullable|array',
        'pozos.*.id_pozo'                       => 'required_if:pozos,array|integer',
        'pozos.*.descripcion'                   => 'required_if:pozos,array|max:45',
        'pozos.*.niveles'                       => 'nullable|array',
        'pozos.*.niveles.*.id_nivel_progresivo' => 'required_if:niveles,array|integer',
        'pozos.*.niveles.*.nro_nivel'           => 'required_if:niveles,array|integer',
        'pozos.*.niveles.*.nombre_nivel'        => 'required_if:niveles,array|max:60',
        'pozos.*.niveles.*.porc_oculto'         => 'nullable|numeric|min:0|max:100',
        'pozos.*.niveles.*.porc_visible'        => 'nullable|numeric|min:0|max:100',
        'pozos.*.niveles.*.base'                => 'nullable|numeric|min:0',
        'pozos.*.niveles.*.maximo'              => 'nullable|numeric|min:0'
    ], array(), self::$atributos)->after(function ($validator){
    })->validate();

    if(isset($request->maquinas)){
      $valido = $this->checkCasinoMaquinas($request->maquinas,$request->id_casino);
      if(!$valido){
        return $this->errorOut(['id_casino' => 'Maquinas y casino tienen casinos distinto']);
      }
    }

    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    if(!$user->es_superusuario && !$user->usuarioTieneCasino($id_casino)){
      return $this->errorOut(['id_casino' => 'El usuario no puede administrar ese casino']);
    }

    DB::transaction(function() use ($request){
      $this->modificarProgresivoAux($request);
    });
  }

  private function modificarProgresivoAux($request){
    $progresivo = Progresivo::with('pozos.niveles','maquinas')
    ->whereIn('id_progresivo',[$request->id_progresivo])->first();

    if($progresivo === null){
      return $this->errorOut(['id_progresivo' => 'Progresivo no existe']);
    }

    $progresivo->nombre = $request->nombre;
    $progresivo->porc_recup = $request->porc_recup;
    $progresivo->save();

    $aux = [];
    if($request->pozos != null){
      $aux = $request->pozos;
    }
    $this->actualizarPozos($progresivo,$aux);

    $aux = [];
    if($request->maquinas != null){
      $aux = $request->maquinas;
    }

    $this->actualizarMaquinas($progresivo,$aux);
  }

  public function crearProgresivosIndividuales(Request $request){
    Validator::make($request->all(), [
        'id_casino'                        => 'required|integer',
        'maquinas'                         => 'required|array',
        'maquinas.*.id_maquina'            => 'required|integer',
        'maquinas.*.maximo'                => 'nullable|numeric|min:0',
        'maquinas.*.base'                  => 'nullable|numeric|min:0',
        'maquinas.*.porc_recup'     => 'required|numeric|min:0|max:100',
        'maquinas.*.porc_visible'          => 'nullable|numeric|min:0|max:100',
        'maquinas.*.porc_oculto'           => 'nullable|numeric|min:0|max:100'
    ], array(), self::$atributos)->after(function ($validator){
    })->validate();

    $valido = $this->checkCasinoMaquinas($request->maquinas,$request->id_casino);
    if(!$valido){
      return $this->errorOut(['id_casino' => 'Maquinas y casino tienen casinos distinto']);
    }

    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    if(!$user->es_superusuario && !$user->usuarioTieneCasino($id_casino)){
      return $this->errorOut(['id_casino' => 'El usuario no puede administrar ese casino']);
    }

    DB::beginTransaction();

    try{
      foreach($request->maquinas as $maq){
        $maq_bd = Maquina::find($maq['id_maquina']);
        $casino_bd = Casino::find($request->id_casino);
        $identificador =
        'IND' .
        $maq_bd->nro_admin .
        $casino_bd->codigo .
        ' ' .
        date("d-m-Y H:i:s");;

        if($maq_bd == null){
          return $this->errorOut(['id_maquina' => 'Maquina no existe.']);
        }

        $individuales = $maq_bd->progresivos()->where('es_individual','=','1')->get()->count();
        if($individuales != 0){
          DB::rollBack();
          return $this->errorOut([ 'es_individual' => $maq['id_maquina'] ]);
        }
        $progresivo = new Progresivo;
        $pozo = new Pozo;
        $nivel_progresivo = new NivelProgresivo;

        $progresivo->porc_recup = $maq['porc_recup'];
        $progresivo->id_casino  = $request->id_casino;
        $progresivo->nombre     = $identificador;
        $progresivo->es_individual = true;

        $progresivo->save();
        $progresivo->maquinas()->sync([$maq['id_maquina']]);
        $progresivo->save();

        $pozo->descripcion       = $identificador;
        $pozo->id_progresivo = $progresivo->id_progresivo;
        $pozo->save();

        $nivel_progresivo->nro_nivel    = 1;
        $nivel_progresivo->nombre_nivel = $identificador;
        $nivel_progresivo->base         = $maq['base'];
        $nivel_progresivo->porc_oculto  = $maq['porc_oculto'];
        $nivel_progresivo->porc_visible = $maq['porc_visible'];
        $nivel_progresivo->maximo       = $maq['maximo'];
        $nivel_progresivo->id_pozo      = $pozo->id_pozo;

        $nivel_progresivo->save();
      }
    }
    catch(Exception $e){
      DB::rollBack();
      throw $e;
    }

    DB::commit();
  }

  private function obtenerIndividuales($id_casino = null,$desde = null,$hasta = null,$not_id_maq = null){
    $query =
    "select
    maqprog.id_maquina as id_maquina,
    prog.id_progresivo as id_progresivo,
    prog.porc_recup as porc_recup,
    pozo.id_pozo as id_pozo,
    niv.id_nivel_progresivo as id_nivel_progresivo,
    niv.base as base,
    niv.porc_oculto as porc_oculto,
    niv.porc_visible as porc_visible,
    niv.maximo as maximo
    from maquina_tiene_progresivo maqprog
    join maquina as maq on (maq.id_maquina = maqprog.id_maquina)
    join progresivo as prog on (maqprog.id_progresivo = prog.id_progresivo)
    join pozo on (prog.id_progresivo = pozo.id_progresivo)
    join nivel_progresivo as niv on (niv.id_pozo = pozo.id_pozo)
    where prog.es_individual = 1
    and maq.id_casino = prog.id_casino";

    $parametros = [];

    if($id_casino != null){
      $query = $query . " and prog.id_casino = :id_casino";
      $parametros['id_casino'] = $id_casino;
    }
    if($desde != null){
      $query = $query . " and maq.nro_admin >= :desde";
      $parametros['desde'] = $desde;
    }
    if($hasta != null){
      $query = $query . " and maq.nro_admin <= :hasta";
      $parametros['hasta'] = $hasta;
    }

    //Menos estas maquinas
    if($not_id_maq != null){
      //Hago una por una porque NOT IN no acepta arreglos...
      //por las comas
      foreach($not_id_maq as $id){
        $query = $query . " and maq.id_maquina <> :id_maquina" . $id;
        $parametros['id_maquina'.$id]=$id;
      }
    }

    return DB::select(DB::raw($query),$parametros);
  }


  public function modificarProgresivosIndividuales(Request $request){
    Validator::make($request->all(), [
        'id_casino'                        => 'required|integer',
        'desde'                            => 'nullable|integer',
        'hasta'                            => 'nullable|integer',
        'maquinas'                         => 'required|array',
        'maquinas.*.id_maquina'            => 'required|integer',
        'maquinas.*.maximo'                => 'nullable|numeric|min:0',
        'maquinas.*.base'                  => 'nullable|numeric|min:0',
        'maquinas.*.porc_recup'     => 'required|numeric|min:0|max:100',
        'maquinas.*.porc_visible'          => 'nullable|numeric|min:0|max:100',
        'maquinas.*.porc_oculto'           => 'nullable|numeric|min:0|max:100'
    ], array(), self::$atributos)->after(function ($validator){
    })->validate();

    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    if(!$user->es_superusuario && !$user->usuarioTieneCasino($id_casino)){
      return $this->errorOut(['id_casino' => 'El usuario no puede administrar ese casino']);
    }

    $valido = $this->checkCasinoMaquinas($request->maquinas,$request->id_casino);
    if(!$valido){
      return $this->errorOut(['id_casino' => 'Maquinas y casino tienen casinos distinto']);
    }

    DB::transaction(function() use ($request){
      $modificados = [];
      $desde = $request->desde;
      $hasta = $request->hasta;
      $id_casino = $request->id_casino;
      foreach($request->maquinas as $maq){
        $maq_bd = Maquina::find($maq['id_maquina']);
        if($maq_bd === null) continue;
        if($maq_bd->id_casino != $id_casino) continue;
        if($request->desde != null && $maq_bd->nro_admin < $desde) continue;
        if($request->hasta != null && $maq_bd->nro_admin > $hasta) continue;
        $progresivo = $maq_bd->progresivos()->where('es_individual','=','1')->first();
        $progresivo->porc_recup = $maq['porc_recup'];
        $progresivo->save();

        $nivel = $progresivo->pozos->first()->niveles->first();
        $nivel->maximo = $maq['maximo'];
        $nivel->base = $maq['base'];
        $nivel->porc_visible = $maq['porc_visible'];
        $nivel->porc_oculto = $maq['porc_oculto'];
        $nivel->save();

        $modificados[] = $maq['id_maquina'];
      }
      //Si no fueron modificados, hay que borrarlos porque quiere decir que no se enviaron
      //en el formulario
      $lista_borrar = $this->obtenerIndividuales($id_casino,$desde,$hasta,$modificados);
      foreach($lista_borrar as $p){
        $this->eliminarProgresivo($p->id_progresivo);
      }
    });
  }



  public function eliminarProgresivo($id_progresivo){
    if($id_progresivo === null) return $this->errorOut(['id_progresivo' => 'Progresivo nulo']);
    $progresivo = Progresivo::find($id_progresivo);
    if($progresivo === null) return $this->errorOut(['id_progresivo' => 'Progresivo no existe']);

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
    if($progresivo === null || $pozos === null) return;

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
    if($pozo === null || $niveles === null) return;

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
    if($progresivo === null || $maquinas === null) return;

    $maquinasAgregadas = [];
    foreach($maquinas as $maq){
      $id = $maq['id_maquina'];
      $maquina_bd = Maquina::find($id);
      if($maquina_bd === null) continue;
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
    if($nivel === null){
      return false;
    }
    $nivel->delete();
    return true;
  }

  public function crearPozo(Request $request){
    $progresivo = Progresivo::find($request->id_progresivo);
    if($progresivo === null){
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
    if($pozo === null){
      return null;
    }
    $pozo->descripcion = $request->descripcion;
    $pozo->save();
    return $pozo;
  }

  public function eliminarPozo($id_pozo){
    $pozo = Pozo::find($id_pozo);
    if($pozo === null){
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

  public function buscarProgresivosIndividuales(Request $request){
    Validator::make($request->all(), [
        'id_casino' => 'required|integer'
    ], array(), self::$atributos)->after(function ($validator){
    })->validate();

    $desde = $request->desde;
    $hasta = $request->hasta;
    $id_casino = $request->id_casino;

    //Checkeo que solo el superusuario puede buscar todo.
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    if($id_casino == 0 && !$user->es_superusuario){
      return array();
    }

    if($id_casino != 0 && !$user->usuarioTieneCasino($id_casino)){
      return array();
    }

    $progresivos = Progresivo::all()->where('es_individual','!=',0);

    $respuesta = [];
    foreach($progresivos as $progresivo){
      $maquina = $this->datosMaquinasProgresivo($progresivo->id_progresivo,1)[0];
      //@TODO: SLOW, podria hacerse todo en una consulta SQL.
      if($desde != null && $maquina->nro_admin < $desde) continue;
      if($hasta != null && $maquina->nro_admin > $hasta) continue;
      $no_pertenece_a_casino = $id_casino != 0 && $progresivo->id_casino != $id_casino;
      if($no_pertenece_a_casino) continue;

      $maq_bd = Maquina::find($maquina->id_maquina);
      $no_pertenece_a_casino = $id_casino != 0 && $maq_bd->id_casino != $id_casino;
      if($no_pertenece_a_casino) continue;

      $progresivo_arr = $progresivo->toArray();
      $progresivo_arr['maquina'] = $maquina;
      $pozo = $progresivo->pozos->first();
      $pozo_arr = $pozo->toArray();
      $progresivo_arr['pozo']=$pozo_arr;
      $nivel = $pozo->niveles->first();
      $nivel_arr = $nivel->toArray();
      $progresivo_arr['pozo']['nivel']=$nivel_arr;
      $respuesta[]=$progresivo_arr;
    }
    return $respuesta;
  }

  public function cargarProgresivos(){
    $query_mel =
    "select 1 as id_casino,p.*
    from progresivos_melinque p";
    $query_sfe =
    "select 2 as id_casino,p.*
    from progresivos_santafe p";
    $query_ros =
    "select 3 as id_casino,p.*
    from progresivos_rosario p";
    $mel = DB::select(DB::raw($query_mel));
    $sfe = DB::select(DB::raw($query_sfe));
    $ros = DB::select(DB::raw($query_ros));
    $progresivos = array_merge($mel,$sfe,$ros);
    DB::transaction(function() use ($progresivos){
      $casinos = Casino::all();
      foreach($progresivos as $idx => $p){
        dump($idx);
        $progresivo_bd = new Progresivo;
        $progresivo_bd->porc_recup = 0;
        $nombre = $p->nombre;
        if($nombre === null){
          $nro_admin = '';
          if($p->nro_admin != null) $nro_admin=$p->nro_admin;

          $nombre =
          'PROG' .
          $nro_admin  .
          $casinos->find($p->id_casino)->codigo .
          ' ' .
          date("d-m-Y H:i:s");
        }

        $progresivo_bd->nombre = $nombre;

        $progresivo_bd->es_individual = $p->es_individual;
        $progresivo_bd->id_casino = $p->id_casino;
        $progresivo_bd->save();

        if($p->nro_admin != null){
          $maq_bd = $casinos->find($p->id_casino)
          ->maquinas()->where('nro_admin','=',$p->nro_admin)->first();
          if($maq_bd != null){
            $progresivo_bd->maquinas()->sync([$maq_bd->id_maquina]);
          }
        }


        $pozo_bd = new Pozo;
        $pozo_bd->descripcion = $nombre;
        $pozo_bd->id_progresivo = $progresivo_bd->id_progresivo;
        $pozo_bd->save();

        $nivel1_bd = new NivelProgresivo;
        $nivel1_bd->nro_nivel = 1;
        $nivel1_bd->nombre_nivel = $p->menu1;
        $nivel1_bd->base = $p->base1;
        $nivel1_bd->porc_oculto = $p->porc_ocu1;
        $nivel1_bd->porc_visible = $p->porc_vis1;
        $nivel1_bd->maximo = $p->Max;//TODO: cambiar esto para que este en el progresivo
        $nivel1_bd->id_pozo = $pozo_bd->id_pozo;
        $nivel1_bd->save();

        $nivel2_bd = new NivelProgresivo;
        $nivel2_bd->nro_nivel = 2;
        $nivel2_bd->nombre_nivel = $p->menu2;
        $nivel2_bd->base = $p->base2;
        $nivel2_bd->porc_oculto = $p->porc_ocu2;
        $nivel2_bd->porc_visible = $p->porc_vis2;
        $nivel2_bd->maximo = $p->Max;//TODO: cambiar esto para que este en el progresivo
        $nivel2_bd->id_pozo = $pozo_bd->id_pozo;
        $nivel2_bd->save();

        $nivel3_bd = new NivelProgresivo;
        $nivel3_bd->nro_nivel = 3;
        $nivel3_bd->nombre_nivel = $p->menu3;
        $nivel3_bd->base = $p->base3;
        $nivel3_bd->porc_oculto = $p->porc_ocu3;
        $nivel3_bd->porc_visible = $p->porc_vis3;
        $nivel3_bd->maximo = $p->Max;//TODO: cambiar esto para que este en el progresivo
        $nivel3_bd->id_pozo = $pozo_bd->id_pozo;
        $nivel3_bd->save();

        $nivel4_bd = new NivelProgresivo;
        $nivel4_bd->nro_nivel = 4;
        $nivel4_bd->nombre_nivel = $p->menu4;
        $nivel4_bd->base = $p->base4;
        $nivel4_bd->porc_oculto = $p->porc_ocu4;
        $nivel4_bd->porc_visible = $p->porc_vis4;
        $nivel4_bd->maximo = $p->Max;//TODO: cambiar esto para que este en el progresivo
        $nivel4_bd->id_pozo = $pozo_bd->id_pozo;
        $nivel4_bd->save();

        $nivel5_bd = new NivelProgresivo;
        $nivel5_bd->nro_nivel = 5;
        $nivel5_bd->nombre_nivel = $p->menu5;
        $nivel5_bd->base = $p->base5;
        $nivel5_bd->porc_oculto = $p->porc_ocu5;
        $nivel5_bd->porc_visible = $p->porc_vis5;
        $nivel5_bd->maximo = $p->Max;//TODO: cambiar esto para que este en el progresivo
        $nivel5_bd->id_pozo = $pozo_bd->id_pozo;
        $nivel5_bd->save();

        $nivel6_bd = new NivelProgresivo;
        $nivel6_bd->nro_nivel = 6;
        $nivel6_bd->nombre_nivel = $p->menu6;
        $nivel6_bd->base = $p->base6;
        $nivel6_bd->porc_oculto = $p->porc_ocu6;
        $nivel6_bd->porc_visible = $p->porc_vis6;
        $nivel6_bd->maximo = $p->Max;//TODO: cambiar esto para que este en el progresivo
        $nivel6_bd->id_pozo = $pozo_bd->id_pozo;
        $nivel6_bd->save();
      }
    });

    return;
  }
}
