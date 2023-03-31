<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Progresivo;
use App\Juego;
use App\Pozo;
use App\NivelProgresivo;
use App\Maquina;
use App\Casino;
use App\TipoMoneda;
use Illuminate\Support\Facades\DB;
use Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ProgresivoController extends Controller
{
  private static $instance;
  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new ProgresivoController();
    }
    return self::$instance;
  }

  public function buscarTodos(){
    UsuarioController::getInstancia()->agregarSeccionReciente('Progresivos' , 'progresivos');
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $SU = $user->es_superusuario;
    return view('seccionProgresivos',[
      'casinos'         => $SU? Casino::all() : $user->casinos,
      'tipo_monedas'    => TipoMoneda::all(),
      'es_superusuario' => $SU
    ]);
  }

  public function buscarProgresivos(Request $request){
    $reglas = [];
    if(isset($request->id_casino)){
      $reglas[]=['progresivo.id_casino','=',$request->id_casino];
    }
    if(isset($request->id_tipo_moneda)){
      $reglas[]=['progresivo.id_tipo_moneda','=',$request->id_tipo_moneda];
    }
    if($request->nombre_progresivo != null && $request->nombre_progresivo != ''){
      $reglas[]=['progresivo.nombre','like','%'.$request->nombre_progresivo.'%'];
    }
    if(isset($request->es_individual)){
      $reglas[]=['progresivo.es_individual','=',$request->es_individual];
    }
        
    $casinos_ids = [];
    {
      $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
      $casinos = $user->es_superusuario? Casino::all() : $user->casinos;
      foreach($casinos as $c) $casinos_ids[] = $c->id_casino;
    }
    
    $sort_by = $request->sort_by ?? ['columna' => 'progresivo.id_progresivo','orden' => 'desc'];
    if($sort_by['columna'] == 'maquinas' || $sort_by['columna'] == 'islas'){
      $col = $sort_by['columna'] == 'maquinas'? 'maquina.nro_admin' : 'isla.nro_isla';
      $sort_by['columna'] = DB::raw("MIN($col)");
    }
    $EMPTY_CONSTANT = '-SIN-';
    $lista_group = function($col,$nombre) use ($EMPTY_CONSTANT){
      return "GROUP_CONCAT(DISTINCT(IFNULL($col ,'$EMPTY_CONSTANT')) ORDER BY $col ASC SEPARATOR ',') as $nombre";
    };
    $resultados = DB::table('progresivo')
    ->select('progresivo.*','casino.nombre as casino','tipo_moneda.descripcion as moneda')
    ->selectRaw($lista_group('maquina.nro_admin','maquinas'))
    ->selectRaw($lista_group('isla.nro_isla','islas'))
    ->selectRaw($lista_group('sector.descripcion','sectores'))
    ->leftjoin('maquina_tiene_progresivo','progresivo.id_progresivo','=','maquina_tiene_progresivo.id_progresivo')
    ->leftjoin('maquina','maquina_tiene_progresivo.id_maquina','=','maquina.id_maquina')
    ->leftjoin('isla','maquina.id_isla','=','isla.id_isla')
    ->leftjoin('sector','isla.id_sector','=','sector.id_sector')
    ->join('casino','casino.id_casino','=','progresivo.id_casino')
    ->join('tipo_moneda','tipo_moneda.id_tipo_moneda','=','progresivo.id_tipo_moneda')
    ->when($sort_by, function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    })
    ->whereIn('progresivo.id_casino',$casinos_ids)
    ->whereNull('progresivo.deleted_at');
    
    $DESIMPLIFY_INT_RANGES = function($comma_list) use ($EMPTY_CONSTANT) {
      if($comma_list == $EMPTY_CONSTANT) return $comma_list;
      $newlist = [];
      foreach(explode(",",$comma_list) as $range_or_int){
        $init_range = null;
        $end_range  = null;
        $pos_hyphen = strpos($range_or_int,"-");
        if($pos_hyphen === false && ctype_digit($range_or_int)){
          $init_range = intval($range_or_int);
          $end_range  = $init_range;
        }
        else if($pos_hyphen !== false){
          $range = explode("-",$range_or_int);
          if(count($range) == 2 && ctype_digit($range[0]) && ctype_digit($range[1])){
            $init_range = intval($range[0]);
            $end_range  = intval($range[1]);
          }
          else continue;//Ignore invalid ranges
        }
        else continue;//Ignore invalid ranges
        
        if($init_range > $end_range){//swap
          $t          = $end_range;
          $end_range  = $init_range;
          $init_range = $t;
        }
        for($i=$init_range;$i<=$end_range;$i++){
          $newlist[] = $i;
        }
      }
      return $newlist;
    };
    
    if(isset($request->maquinas)){
      if($request->maquinas == $EMPTY_CONSTANT){
        $resultados = $resultados->whereNull('maquina.nro_admin');
      }
      else{//TODO: Agregar soporte de rangos
        $resultados = $resultados->whereIn('maquina.nro_admin',$DESIMPLIFY_INT_RANGES($request->maquinas));
      }
    }
    
    if(isset($request->islas)){
      if($request->islas == $EMPTY_CONSTANT){
        $resultados = $resultados->whereNull('isla.nro_isla');
      }
      else{//TODO: Agregar soporte de rangos
        $resultados = $resultados->whereIn('isla.nro_isla',$DESIMPLIFY_INT_RANGES($request->islas));
      }
    }

    if(isset($request->sectores)){
      if($request->sectores == $EMPTY_CONSTANT){
        $resultados = $resultados->whereNull('sector.descripcion');
      }
      else{
        $sectores_busqueda = explode(',',$request->sectores);
        $resultados->where(function($q) use ($sectores_busqueda){
          foreach($sectores_busqueda as $s){
            $q->orWhere('sector.descripcion','LIKE','%'.$s.'%');
          }
        });
      }
    }

    $resultados = $resultados->where($reglas);
    $resultados = $resultados->groupBy('progresivo.id_progresivo');
    $resultados = $resultados->paginate($request->page_size);
    $SIMPLIFY_INT_RANGES = function($comma_list) use ($EMPTY_CONSTANT){
      if($comma_list == $EMPTY_CONSTANT) return $comma_list;
      $list = array_map(function($i){return intval($i);},explode(",",$comma_list));
      $init_range = $list[0];
      $end_range  = $list[0];
      $list = array_slice($list,1);
      $newlist = [];
      foreach($list as $i){
        if($i != ($end_range+1)){
          $newlist[] = [$init_range,$end_range];
          $init_range = $i;
        }
        $end_range = $i;
      }
      $newlist[] = [$init_range,$end_range];
      $newlist = array_map(function($r){
        return $r[0]!=$r[1]? $r[0].'-'.$r[1] : $r[0];
      },$newlist);
      return implode(",",$newlist);
    };
    $resultados->getCollection()->transform(function($obj) use ($SIMPLIFY_INT_RANGES){
      $obj->maquinas = $SIMPLIFY_INT_RANGES($obj->maquinas);
      $obj->islas    = $SIMPLIFY_INT_RANGES($obj->islas);
      return $obj;
    });
    return $resultados;
  }

  public function buscarMaquinas(Request $request,$id_casino,$nro_admin=null){
    $user   = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $casino = Casino::find($id_casino);
    $invalido = $id_casino === null || ($id_casino == 0 && !$user->es_superusuario) 
               ||  $casino === null || !$user->usuarioTieneCasino($id_casino);
    if($invalido) return [];
      
    $resultados = DB::table('maquina as maq')
    ->selectRaw("maq.id_maquina as id_maquina, maq.nro_admin as nro_admin, maq.marca_juego as marca_juego, maq.id_casino as id_casino,
    CONCAT(maq.nro_admin,cas.codigo) as nombre, ifnull(isla.nro_isla,'-') as isla, ifnull(sec.descripcion,'-') as sector")
    ->join('casino as cas','maq.id_casino','=','cas.id_casino')
    ->leftJoin('isla','maq.id_isla','=','isla.id_isla')
    ->leftJoin('sector as sec','isla.id_sector','=','sec.id_sector')
    ->where('cas.id_casino','=',$id_casino)
    ->whereNull('maq.deleted_at');
    if($nro_admin != null){
      $resultados = $resultados->whereRaw('CAST(maq.nro_admin as CHAR) LIKE ?',[strval($nro_admin).'%']);
    }
    return ['maquinas' => $resultados->get()];
  }


  public function obtenerProgresivo($id){
    $ret = ['progresivo' => Progresivo::find($id),'pozos' => [],'maquinas' => []];
    if($ret['progresivo'] == null) return $ret;

    $pozos = $ret['progresivo']->pozos ?? [];
    $pozos_arr = [];
    foreach($pozos as $pozo){
      $niveles = $pozo->niveles()->orderBy('nro_nivel','asc')->get() ?? [];
      $parr = $pozo->toArray();
      $parr['niveles'] = $niveles;
      $pozos_arr[] = $parr;
    }
    $ret['pozos'] = $pozos_arr;

    $ret['maquinas'] = DB::table('progresivo as prog')
    ->selectRaw("maq.id_maquina as id_maquina, maq.nro_admin as nro_admin, maq.marca_juego as marca_juego,
    CONCAT(maq.nro_admin,cas.codigo) as nombre, ifnull(isla.nro_isla,'-') as isla, ifnull(sec.descripcion,'-') as sector")
    ->join('maquina_tiene_progresivo as maq_prog','prog.id_progresivo','=','maq_prog.id_progresivo')
    ->join('maquina as maq','maq.id_maquina','=','maq_prog.id_maquina')
    ->join('casino as cas','maq.id_casino','=','cas.id_casino')
    ->leftJoin('isla','maq.id_isla','=','isla.id_isla')
    ->leftJoin('sector as sec','isla.id_sector','=','sec.id_sector')
    ->where('prog.id_progresivo','=',$ret['progresivo']->id_progresivo)
    ->whereNull('maq.deleted_at')->get();

    return $ret;
  }

  public function crearModificarProgresivo(Request $request){
    Validator::make($request->all(),[
      'id_progresivo'                         => 'nullable|integer|exists:progresivo,id_progresivo,deleted_at,NULL',
      'es_individual'                         => 'required|integer|min:0|max:1',
      'nombre'                                => 'required_if:es_individual,0|max:100',
      'porc_recup'                            => 'required|numeric|min:0|max:100',
      'id_casino'                             => 'required|integer|exists:casino,id_casino,deleted_at,NULL',
      'id_tipo_moneda'                        => 'required|integer|exists:tipo_moneda,id_tipo_moneda',
      'maquinas'                              => 'nullable|array',
      'maquinas.*.id_maquina'                 => 'required_if:maquinas,array|integer|exists:maquina,id_maquina,deleted_at,NULL',
      'pozos'                                 => 'nullable|array',
      'pozos.*.id_pozo'                       => 'nullable|integer|exists:pozo,id_pozo,deleted_at,NULL',
      'pozos.*.descripcion'                   => 'required_if:pozos,array|max:45',
      'pozos.*.niveles'                       => 'nullable|array',
      'pozos.*.niveles.*.id_nivel_progresivo' => 'nullable|integer|exists:nivel_progresivo,id_nivel_progresivo,deleted_at,NULL',
      'pozos.*.niveles.*.nro_nivel'           => 'required_if:niveles,array|integer',
      'pozos.*.niveles.*.nombre_nivel'        => 'required_if:niveles,array|max:60',
      'pozos.*.niveles.*.porc_oculto'         => 'nullable|numeric|min:0|max:100',
      'pozos.*.niveles.*.porc_visible'        => 'required_if:niveles,array|numeric|min:0|max:100',
      'pozos.*.niveles.*.base'                => 'required_if:niveles,array|numeric|min:0',
      'pozos.*.niveles.*.maximo'              => 'nullable|numeric|min:0',
    ],[],[])->after(function ($validator){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
      if(!$user->es_superusuario && !$user->usuarioTieneCasino($data['id_casino'])){
        return $validator->errors()->add('id_casino','El usuario no puede administrar ese casino');
      }
      if(!empty($data['maquinas']) && !$this->checkCasinoMaquinas($data['maquinas'],$data['id_casino'])){
        return $validator->errors()->add('id_casino', 'Las maquinas no pertenecen al casino');
      }
      if($data['es_individual'] == 1 && count($data['maquinas'] ?? []) > 1){
        return $validator->errors()->add('maquinas','La cantidad de maquinas no corresponde con el tipo de progresivo');
      }
      foreach(($data['maquinas'] ?? []) as $idx => $m){
        $maq_bd = Maquina::find($m['id_maquina']);
        $individuales = $maq_bd->progresivos()->where('es_individual','=','1');
        if(isset($data['id_progresivo'])){
          $individuales = $individuales->where('progresivo.id_progresivo','<>',$data['id_progresivo']);
        }
        if($data['es_individual'] == 1 && $individuales->get()->count() > 0){
          return $validator->errors()->add('es_individual',"Maquina $maq_bd->nro_admin ya tiene un progresivo individual");
        }
      }
    })->validate();
    
    $controller = $this;
    DB::transaction(function() use ($request,$controller){
      $pozos = array_map(function($p){
        $p['id_pozo'] = isset($p['id_pozo'])? $p['id_pozo'] : null;
        $p['niveles'] = array_map(function($n){
          $n['id_nivel_progresivo'] = isset($n['id_nivel_progresivo'])? $n['id_nivel_progresivo'] : null;
          return $n;
        },$p['niveles'] ?? []);
        return $p;
      },$request->pozos ?? []);
      $maquinas = array_map(function($m){return $m['id_maquina'];},$request->maquinas ?? []);
      $controller->internal_crearModificarProgresivo(
        isset($request->id_progresivo)? $request->id_progresivo : null,
        $request->id_casino,$request->id_tipo_moneda,
        $request->nombre ?? '',$request->porc_recup,$pozos,
        $request->es_individual? true : false,$maquinas
      );
    });
  }
  
  public function crearProgresivosIndividuales(Request $request){
    Validator::make($request->all(), [
      'id_casino'               => 'required|integer|exists:casino,id_casino,deleted_at,NULL',
      'id_tipo_moneda'          => 'required|integer|exists:tipo_moneda,id_tipo_moneda',         
      'maquinas'                => 'required|array',
      'maquinas.*.id_maquina'   => 'required|integer|exists:maquina,id_maquina,deleted_at,NULL',
      'maquinas.*.maximo'       => 'nullable|numeric|min:0',
      'maquinas.*.base'         => 'nullable|numeric|min:0',
      'maquinas.*.porc_recup'   => 'required|numeric|min:0|max:100',
      'maquinas.*.porc_visible' => 'nullable|numeric|min:0|max:100',
      'maquinas.*.porc_oculto'  => 'nullable|numeric|min:0|max:100'
    ],[],[])->after(function ($validator){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
      if(!$user->es_superusuario && !$user->usuarioTieneCasino($data['id_casino'])){
        return $validator->errors()->add('id_casino','El usuario no puede administrar ese casino');
      }
      if(!empty($data['maquinas']) && !$this->checkCasinoMaquinas($data['maquinas'],$data['id_casino'])){
        return $validator->errors()->add('id_casino', 'Las maquinas no pertenecen al casino');
      }
      foreach($data['maquinas'] as $m){
        $maq_bd = Maquina::find($m['id_maquina']);
        $individuales = $maq_bd->progresivos()->where('es_individual','=','1')->get()->count();
        if($individuales > 0){
          return $validator->errors()->add('es_individual',"Maquina $maq_bd->nro_admin ya tiene un progresivo individual");
        }
      }
    })->validate();
    
    $controller = $this;
    DB::transaction(function() use ($request,$controller){
      foreach($request->maquinas as $m){
        $nivel = [
          'id_nivel_progresivo' => null,
          'nro_nivel'    => 1,
          'nombre_nivel' => '1',
          'base'         => $m['base'],
          'maximo'       => $m['maximo'],
          'porc_visible' => $m['porc_visible'],
          'porc_oculto'  => $m['porc_oculto'],
        ];
        $pozo = [
          'id_pozo'     => null,
          'descripcion' => 'Pozo',
          'niveles'     => [$nivel]
        ];
        $controller->internal_crearModificarProgresivo(
          null,//id_progresivo
          $request->id_casino,
          $request->id_tipo_moneda,
          "",//nombre: No importa, se sobreescribe
          $m['porc_recup'],
          [$pozo],
          true,//es_individual
          [$m['id_maquina']]
        );
      }
    });
  }
  
  private function findOrCreate($class,$id){
    if($id !== null) return $class::find($id);
    return new $class();
  }
  
  private function internal_crearModificarProgresivo(
      int $id_progresivo = null,
      int $id_casino,
      int $id_tipo_moneda,
      string $nombre,
      float $porc_recup,
      array $pozos,
      bool $es_individual,
      array $id_maqs
  ){
    $progresivo = $this->findOrCreate(Progresivo::class,$id_progresivo);
    $progresivo->nombre         = $nombre;
    $progresivo->porc_recup     = $porc_recup;
    $progresivo->id_casino      = $id_casino;
    $progresivo->id_tipo_moneda = $id_tipo_moneda;
    $progresivo->es_individual  = $es_individual;
    if($es_individual){
      $maq = count($id_maqs ?? []) > 0? Maquina::find($id_maqs[0]) : null;
      $cas = Casino::find($id_casino);
      $nro_admin = $maq? $maq->nro_admin : '-SMAQ-';
      $cod_cas   = $cas? $cas->codigo    : '-SCAS-';
      $progresivo->nombre = "IND$nro_admin$cod_cas";
    }
    $progresivo->save();
    
    {//Actualizo los pozos
      $ids_recibidos = [];
      foreach($pozos as $p){
        $pozo_bd = $this->findOrCreate(Pozo::class,$p['id_pozo']);
        $pozo_bd->descripcion   = $p['descripcion'];
        $pozo_bd->id_progresivo = $progresivo->id_progresivo;
        $pozo_bd->save();
        $ids_recibidos[] = $pozo_bd->id_pozo;
        $this->actualizarNiveles($pozo_bd,$p['niveles']);//Lo hago en una función aparte para evitar anidar mucho
      }
      foreach($progresivo->pozos()->whereNotIn('pozo.id_pozo',$ids_recibidos)->get() as $pelim){
        $pelim->niveles()->delete();
        $pelim->delete();
      }
    }
    
    $progresivo->maquinas()->sync($id_maqs);
    $progresivo->save();
  }
  
  private function actualizarNiveles($pozo,$niveles){
    $ids_recibidos = [];
    foreach($niveles as $nivel){
      $nivel_bd = $this->findOrCreate(NivelProgresivo::class,$nivel['id_nivel_progresivo']);
      foreach(['nro_nivel','nombre_nivel','base','porc_oculto','porc_visible','maximo'] as $attr){
        $nivel_bd->{$attr} = $nivel[$attr] ?? null;
      }
      $nivel_bd->id_pozo = $pozo->id_pozo;
      $nivel_bd->save();
      $ids_recibidos[] = $nivel_bd->id_nivel_progresivo;
    }
    $pozo->niveles()->whereNotIn('nivel_progresivo.id_nivel_progresivo',$ids_recibidos)->delete();
  }

  public function eliminarProgresivo($id_progresivo){
    Validator::make(
      ['id_progresivo' => $id_progresivo],
      ['id_progresivo' => 'required|integer|exists:progresivo,id_progresivo,deleted_at,NULL'],
      ['required' => 'El id_progresivo es requerido','exists' => 'El progresivo no existe'],
      []
    )->after(function ($validator){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
      $id_casino = Progresivo::find($validator->getData()['id_progresivo'])->casino->id_casino;
      if(!$user->es_superusuario && !$user->usuarioTieneCasino($id_casino)){
        return $validator->errors()->add('id_casino','El usuario no puede administrar ese casino');
      }
    })->validate();
    
    //NO se desenlazan las maquinas, sino perdemos esa información si hay que restaurarlo del softdelete
    $progresivo = Progresivo::find($id_progresivo);
    foreach($progresivo->pozos as $pozo){
      foreach($pozo->niveles as $nivel){
        $nivel->delete();
      }
      $pozo->delete();
    }
    $progresivo->delete();
  }

  public function checkCasinoMaquinas($maquinas,$id_casino){
    $cant_maquinas = count($maquinas);
    if($cant_maquinas == 0) return true;
    $ids = array_map(function($m){return $m['id_maquina'];},$maquinas ?? []);
    $cant_en_bd = Maquina::where('id_casino','=',$id_casino)->whereIn('id_maquina',$ids)->count();
    return $cant_maquinas == $cant_en_bd;
  }

  public function obtenerProgresivoPorIdMaquina($id_maquina){
    //ESTO ESTA ASI POR QUE EN LAYOUT PARCIAL ESPERA ESTA RESPUESTA (LayoutController::obtenerLayoutParcial)
    //NO BORRAR SIN VER BIEN PORQUE (no tuve tiempo de revisarlo)
    return ['progresivo' => '','niveles' => [],'pozo' => null];
  }
}
