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
    "select maq.id_maquina as id,
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
    if($id_casino != null){
      $query = $query . " and cas.id_casino = :id_casino";
    }
    return DB::select(DB::raw($query),['id_casino' => $id_casino]);
  }

  private function datosMaquinasProgresivo($id_progresivo = null){
    $query =
    "select maq.id_maquina as id,
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
    if($id_progresivo != null){
      $query = $query . " and prog.id_progresivo = :id_progresivo";
    }
    return DB::select(DB::raw($query),['id_progresivo' => $id_progresivo]);
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

    $progresivo = new Progresivo;
    $progresivo->nombre = $request->nombre;
    $progresivo->porc_recup = $request->porc_recup;
    $progresivo->id_casino = $request->id_casino;
    $progresivo->save();

    $request->id_progresivo = $progresivo->id_progresivo;
    return $this->modificarProgresivo($request,$progresivo->id_progresivo,false);
  }

  public function modificarProgresivo(Request $request,$id_progresivo,$check_maquinas = true){
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
        'pozos.*.niveles.*.maximo'              => 'nullable|numeric||min:0'
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

    return $this->obtenerProgresivo($progresivo->id_progresivo);
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
}
