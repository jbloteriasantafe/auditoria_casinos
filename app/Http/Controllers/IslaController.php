<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\UsuarioController;
use App\Isla;
use App\Sector;
use App\LogIsla;
use App\Casino;
use App\Progresivo;
use App\Maquina;
use App\EstadoRelevamiento;
use Validator;

/*
  Controlador encargado de manejar las entidades de tipo Isla.
  Alta,Baja,Modificacion,Buscar
*/

class IslaController extends Controller
{
  private static $atributos = ['nro_isla' => 'Número de Isla'];
  private static $errores =       [
    'required' => 'El valor es requerido',
    'integer' => 'El valor no es un numero',
    'numeric' => 'El valor no es un numero',
    'exists' => 'El valor es invalido',
    'array' => 'El valor es invalido',
    'alpha_dash' => 'El valor tiene que ser alfanumérico opcionalmente con guiones',
    'string' => 'El valor tiene que ser una cadena de caracteres',
    'string.min' => 'El valor es muy corto',
    'privilegios' => 'No puede realizar esa acción',
    'incompatibilidad' => 'El valor no puede ser asignado',
  ];

  private static $instance;

  public function buscarTodo(){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      UsuarioController::getInstancia()->agregarSeccionReciente('Islas' , 'islas');
      return view('seccionIslas' , ['usuario' => $usuario,'casinos' => $usuario->casinos]);
  }

  public static function getInstancia(){
    if (!isset(self::$instance)) {
      self::$instance = new IslaController();
    }
    return self::$instance;
  }

  //BUSCA ISLAS BAJO EL CRITERIO "CONTIENE" EL PARAMETRO $nro_isla.
  public function buscarIslaPorCasinoYNro($id_casino , $nro_isla ){
      $casinos= array();
      if($id_casino == 0){
        $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
        foreach($usuario->casinos as $casino){
              $casinos [] = $casino->id_casino;
        }
      }else{
        $casinos[] = $id_casino;
      }
      $busqueda = $nro_isla . '%';
      $resultados=Isla::where('nro_isla','like', $busqueda)->whereIn('id_casino' , $casinos)->get();
      return ['islas' => $resultados];
  }

  public function buscarIslaPorCasinoSectorYNro($id_casino , $id_sector, $nro_isla ){
      $sectores = array();
      if($id_sector == 0){
        $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
        foreach($usuario->casinos as $casino){
          foreach($casino->sectores as $sector){
            $sectores[] = $sector->id_sector;
          }
        }
      }
      else{
        $sectores[] = $id_sector;
      }
      $resultados = $this->buscarIslaPorCasinoYNro($id_casino,$nro_isla,false)['islas']->whereIn('id_sector',$sectores);
      return ['islas' => $resultados];
  }

  public function buscarIslaPorSectorYNro($id_sector,$nro_isla){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $sectores = [];
    foreach($usuario->casinos as $casino){
      foreach($casino->sectores as $sector){
        $sectores[] = $sector->id_sector;
      }
    }
    return ['islas' => Isla::where('nro_isla','like', $nro_isla.'%')->whereIn('id_sector',$sectores)->where('id_sector',$id_sector)->get()];
  }

  //busca UNA ISLA. SI EL NRO DE ISLA COINCIDE EN SU TOTALIDAD
  public function buscarIslaPorNro($nro_isla, $id_casino = 0){
    if($id_casino != 0){
      $resultados=Isla::where([['nro_isla','like',$nro_isla.'%'],['id_casino','=',$id_casino]])->get();
    }else{
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $casinos = array();
      foreach($usuario->casinos as $casino){
        $casinos [] = $casino->id_casino;
      }
      $resultados=Isla::where('nro_isla','like','%'.$nro_isla.'%')->whereIn('id_casino',$casinos)->get();
    }
    return ['islas' => $resultados];
  }

  public function listarMaquinasPorNroIsla($nro_isla, $id_casino = 0){
      $detalles= array();
      if($id_casino != 0){
        $resultados=Isla::where([['nro_isla','like',$nro_isla],['id_casino','=',$id_casino]])->get();
      }else{
        $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
        $casinos = array();
        foreach($usuario->casinos as $casino){
          $casinos [] = $casino->id_casino;
        }
        $resultados=Isla::where('nro_isla','like',$nro_isla)->whereIn('id_casino',$casinos)->get();
      }
      $cantidad= 0;
      foreach ($resultados as $unaIsla) {
        $aux = new \stdClass;
        $aux->maquinas = $unaIsla->maquinas;
        $aux->id_isla = $unaIsla->id_isla ;
        $aux->id_sector = $unaIsla->id_sector;
        $aux->codigo =  $unaIsla->codigo;
        $aux->nro_isla = $unaIsla->nro_isla;
        $aux->sector = $unaIsla->sector->descripcion;
        $cantidad += $unaIsla->maquinas->count();
        $detalles[] = $aux;
      }
    return ['islas' => $detalles, 'cantidad_maquinas' => $cantidad];
  }

  public function dividirIsla(Request $request){
    Validator::make($request->all() , [
        'id_casino'                        => 'required|integer|exists:casino,id_casino',
        'nro_isla'                         => 'required|integer',
        'subislas'                         => 'required|array',
        'subislas.*.id_isla'               => 'required|integer',
        'subislas.*.id_sector'             => 'required|integer|exists:sector,id_sector',
        'subislas.*.codigo'                => 'nullable|alpha_dash|string|min:1',
        'subislas.*.maquinas'              => 'nullable|array',
        'subislas.*.maquinas.*.id_maquina' => 'required|integer|exists:maquina,id_maquina',
      ] , 
      self::$errores, self::$atributos)->after(function ($validator){
        if($validator->errors()->any()) return;
        $subislas  = $validator->getData()['subislas'];
        $id_casino = $validator->getData()['id_casino'];
        $nro_isla  =  $validator->getData()['nro_isla'];
        if(!UsuarioController::getInstancia()->quienSoy()['usuario']->usuarioTieneCasino($id_casino)){
          $validator->errors()->add('id_casino',self::$errors['privilegios']);
          return;
        }
        $codigos = [];
        foreach ($subislas as $index => $SI){
          //Validaciones de Isla/Sector/Casino
          if($SI['id_isla'] > 0){
            $isla = Isla::find($SI['id_isla']);//Se chequea is_null porque pueden haber sido softdeleteados 
            if(is_null($isla) || $isla->nro_isla != $nro_isla || $isla->id_casino != $id_casino){
              $validator->errors()->add("subislas.$index.id_isla",self::$errores['incompatibilidad']);
            }
          }
          $sector = Sector::find($SI['id_sector']);
          if(is_null($sector) || $sector->id_casino != $id_casino){
            $validator->errors()->add("subislas.$index.id_sector",self::$errores['incompatibilidad']);
          }

          //Validaciones de codigo
          if(empty($SI['codigo'])){
            if(!empty($SI['maquinas'])){//Si no tiene codigos pero si maquinas
              $validator->errors()->add("subislas.$index.codigo",self::$errores['required']);
            }
          }
          else{
            if(array_key_exists($SI['codigo'],$codigos)){
              $validator->errors()->add("subislas.$index.codigo",'Valor repetido');
            }
            else{
              $codigos[$SI['codigo']] = 1;
            }
          }

          //Validaciones de maquinas
          if(!empty($SI['maquinas'])){
            foreach($SI['maquinas'] as $midx => $m){
              $maq = Maquina::find($m['id_maquina']);
              if(is_null($maq) || $maq->id_casino != $id_casino){
                $validator->errors()->add("subislas.$index.maquinas.$midx.id_maquina",self::$errores['incompatibilidad']);
              }
            }
          }
        }
      })->validate();

      DB::transaction(function() use ($request){
        $subislas = [];
        $MTMC = MTMController::getInstancia();
        foreach($request->subislas as $SI) {
          $subislas++;
          if($SI['id_isla'] == 0){
            $isla = new Isla();
            $isla->nro_isla  = $request->nro_isla;
            $isla->id_casino = $request->id_casino;
          }else {
            $isla = Isla::find($SI['id_isla']);
          }
          $isla->id_sector = $SI['id_sector'];
          $isla->codigo    = $SI['codigo'];
          $isla->save();
          $subislas[$isla->id_isla] = true;

          if(!empty($SI['maquinas'])){
            foreach ($SI['maquinas'] as $maquina){
              $MTMC->asociarIsla($maquina['id_maquina'],$isla->id_isla);
            }
          }
          else{//Si no tiene maquinas, la isla se elimina
            $this->eliminarIsla($isla->id_isla);
            unset($subislas[$isla->id_isla]);
          }
        }//fin foreach
  
        if(count($subislas) == 1){//si es una sola isla le saco el codigo porque no hay subislas
          foreach($subislas as $id_isla => $nada){
            $isla = Isla::find($id_isla);
            $isla->codigo = null;
            $isla->save();
          }
        } 
      });

      return ['codigo' => 200];
  }

  public function buscarIslas(Request $request){
    $reglas = array();
    if(!empty($request->nro_isla))
      $reglas[]=['isla.nro_isla','like',$request->nro_isla.'%'];
    if(!empty($request->id_sector) && is_numeric($request->id_sector))
      $reglas[]=['isla.id_sector','=',$request->id_sector];
      
    if(!empty($request->id_casino))
      $reglas[]=['isla.id_casino','=',$request->id_casino];

    foreach(UsuarioController::getInstancia()->quienSoy()['usuario']->casinos as $casino){
      $casinos [] = $casino->id_casino;
    }

    $sort_by = [
      'columna' => 'isla.nro_isla',
      'orden' => 'asc',
    ];
    if(!empty($request->sort_by)) $sort_by = $request->sort_by;

    $resultados=DB::table('isla')
    ->selectRaw('isla.id_isla, isla.nro_isla , isla.codigo , COUNT(id_maquina) as cantidad_maquinas ,IFNULL(sector.descripcion,"- SIN SECTOR -") AS sector,casino.id_casino as id_casino,IFNULL(casino.nombre,"- SIN CASINO -") as casino')
    ->leftJoin('maquina',function($j){
      return $j->on('maquina.id_isla','=','isla.id_isla')->whereNull('maquina.deleted_at');
    })
    ->leftJoin('sector',function($j){
      return $j->on('sector.id_sector','=','isla.id_sector')->whereNull('sector.deleted_at');
    })
    ->leftJoin('casino',function($j){
      return $j->on('casino.id_casino','=','sector.id_casino')->whereNull('casino.deleted_at');
    })
    ->where($reglas)
    ->whereNull('isla.deleted_at')
    ->whereIn('isla.id_casino' , $casinos)
    ->groupBy('isla.id_isla');
    if(!empty($request->id_sector) && is_numeric($request->id_sector)){
      $reglas[]=['isla.id_sector','=',$request->id_sector];
    }
    if(!empty($request->id_sector) && $request->id_sector == "SIN_SECTOR"){
      $resultados = $resultados->whereNull('isla.id_sector');
    }
    //is_null porque puede mandar 0, ver empty() en los DOC de PHP
    if(!is_null($request->cantidad_maquinas))
      $resultados = $resultados->havingRaw('COUNT(id_maquina) <= ?',[$request->cantidad_maquinas]);

    return $resultados->orderBy($sort_by['columna'],$sort_by['orden'])
    ->paginate($request->page_size);
  }

  public function obtenerIsla($id){
    $isla = Isla::find($id);
    return [
      'isla'      => $isla ,
      'sector'    => $isla->sector ,
      'maquinas'  => $isla->maquinas ,
      'historial' => $isla->logs_isla()->orderBy('log_isla.fecha','desc')->take(3)->get(),
      'estados'   => EstadoRelevamiento::all(),
      'casino'    => $isla->casino
    ];
  }

  public function obtenerIslaPorNro($id_casino,$id_sector,$nro_isla){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $acceso = $usuario->casinos()->where('casino.id_casino','=',$id_casino)->count();
    if($acceso == 0) return [];

    $isla = Isla::where([['id_casino','=',$id_casino],
                        ['id_sector','=',$id_sector],
                        ['nro_isla','=',$nro_isla]])->first();
                        
    if(is_null($isla)) return [];
    return $this->obtenerIsla($isla->id_isla);
  }

  public function eliminarIsla($id){
    Validator::make(//@TODO: Validar que el usuario tiene el casino para eliminar la isla
      ['id_isla' => $id],
      ['id_isla' => 'required|integer|exists:isla,id_isla'],
      self::$errores, self::$atributos
    )->after(function ($validator){
      if($validator->errors()->any()) return;
      $isla = Isla::find($validator->getData()['id_isla']);
      $casinos_acceso = [];
      foreach(UsuarioController::getInstancia()->quienSoy()['usuario']->casinos as $c){
        $casinos_acceso[$c->id_casino] = true;
      }
      if(!array_key_exists($isla->id_casino,$casinos_acceso)){
        $validator->errors()->add('id_isla',self::$errores['privilegios']);
      }
      foreach ($isla->maquinas as $maquina) {
        if ($maquina->estado_maquina->descripcion == 'Ingreso' && $maquina->estado_maquina->descripcion == 'Reingreso') {
          $validator->errors()->add('id_isla','Existen máquinas habilitadas en la isla.');
        }
      }
    })->validate();

    $isla = Isla::find($id);
  
    foreach($isla->maquinas as $maquina){
      MTMController::getInstancia()->desasociarIsla($maquina->id_maquina, $isla->id_isla);
      MovimientoIslaController::getInstancia()->guardar($isla->id_isla, $maquina['id_maquina']);  //para controlar el movimiento
    }

    $isla->logs_isla()->delete();
    $isla->delete();
    return 1;
  }

  public function encontrarOCrear($nro_isla , $id_casino){
    $isla = $this->buscarIslaPorNro($nro_isla ,$id_casino);
    if(count($isla['islas'])==0){
      $isla=new Isla;
      $isla->nro_isla=$nro_isla;
      $isla->id_casino=$id_casino;
      $isla->save();
      return $isla;
    }else {
      return $isla['islas'][0];
    }
  }

  
  public function guardarIsla(Request $request){
    $this->validar_crear_o_modificar($request,false);

    return DB::transaction(function () use ($request){
      return $this->crear_o_modificar(
        null,$request->id_casino,$request->id_sector,
        $request->nro_isla,$request->codigo,
        [],
        $request->maquinas ?? []
      );
    });
  }

  public function modificarIsla(Request $request){
    $this->validar_crear_o_modificar($request,true);

    return DB::transaction(function () use ($request){
      return $this->crear_o_modificar(
        $request->id_isla,$request->id_casino,$request->id_sector,
        $request->nro_isla,$request->codigo,
        $request->historial ?? [],
        $request->maquinas  ?? []
      );
    });
  }

  private function validar_crear_o_modificar($request,$modificando){
    $validacion = [
      'nro_isla' => 'required|integer|max:9999999999',
      'id_sector' => 'required|exists:sector,id_sector',
      'id_casino' => 'required|exists:casino,id_casino',
      'codigo' => 'nullable|alpha_dash|string|min:1',
      'maquinas' => 'nullable',
      'maquinas.*' => 'required|exists:maquina,id_maquina',
    ];
    if($modificando){
      $validacion = array_merge($validacion,[
        'id_isla'  => 'required|integer|exists:isla,id_isla',
        'historial' => 'nullable',
        'historial.*.id_log_isla' => 'required|exists:log_isla,id_log_isla',
        'historial.*.id_estado_relevamiento' => 'required|exists:estado_relevamiento,id_estado_relevamiento',
      ]);
    }

    Validator::make($request->all(),$validacion,self::$errores,self::$atributos)->after(function ($validator) use($modificando){
      if($validator->errors()->any()) return;
      
      $casinos_acceso = [];
      foreach(UsuarioController::getInstancia()->quienSoy()['usuario']->casinos as $c){
        $casinos_acceso[$c->id_casino] = true;
      }

      $id_casino = $validator->getData()['id_casino'];
      if(!array_key_exists($id_casino,$casinos_acceso)){
        $validator->errors()->add('id_casino',self::$errores['privilegios']);
        return;
      }

      $sector = Sector::find($validator->getData()['id_sector']);
      if(is_null($sector) || $sector->id_casino != $id_casino){//Verifico que no este softdeleted y que pertenezca al casino
        $validator->errors()->add('id_sector',self::$errores['incompatibilidad']);
        return;
      }

      $maquinas = $validator->getData()['maquinas'] ?? [];
      foreach ($maquinas as $id_maquina) {
        $m = Maquina::find($id_maquina);
        if(is_null($m) || $m->id_casino != $id_casino){
          $validator->errors()->add('maquinas',self::$errores['incompatibilidad']);
          return;
        }
      }

      $reglas = [['id_casino' , '=' , $id_casino],['nro_isla' , '=' , $validator->getData()['nro_isla']]];
      if($modificando){
        $isla = Isla::find($validator->getData()['id_isla']);
        if(is_null($isla) || $isla->id_casino != $id_casino){
          $validator->errors()->add('id_isla',self::$errores['incompatibilidad']);
          return;
        }
        $reglas[] = ['id_isla','<>',$isla->id_isla];

        $historial = $validator->getData()['historial'] ?? [];
        foreach($historial as $idx => $h){//@Deprecar LogIsla
          $logisla = LogIsla::find($h['id_log_isla']);
          if($logisla->id_isla != $isla->id_isla){
            $validator->errors()->add('historial',self::$errores['incompatibilidad']);
            return;
          }
        }
      }

      $codigo = $validator->getData()['codigo'] ?? null;
      //Rosario usa el codigo de otra forma... casi poniendole una descripcion nmemotecnica, nose como unificar SantaFe-Mel con Rosario
      foreach(Isla::where($reglas)->get() as $i){
        if(!empty($codigo)){
          if(empty($i->codigo)){
            $validator->errors()->add('codigo','Existe otra isla con el mismo N° sin codigo');
          }
          if($i->codigo == $codigo){
            $validator->errors()->add('codigo','El código de subisla ya esta en uso');
          }
        }
        else{
          if(!empty($i->codigo)){
            $validator->errors()->add('codigo','Existe otra isla con el mismo N° con codigo');
          }
          else{
            $validator->errors()->add('nro_isla','El N° de isla ya esta en uso');
          }
        }
      }
    })->validate();
  }

  private function crear_o_modificar($id_isla,$id_casino,$id_sector,$nro_isla,$codigo,$historial,$maquinas){
    $isla = null;
    if(empty($id_isla)){
      $isla = new Isla;
    }
    else{
      $isla = Isla::find($id_isla);
    }

    $isla->nro_isla  = $nro_isla;
    $isla->id_casino = $id_casino;
    $isla->id_sector = $id_sector;
    $isla->codigo    = $codigo;
    $isla->save();

    foreach ($historial as $log) {
      $log_isla = LogIsla::find($log['id_log_isla']);
      $log_isla->estado_relevamiento()->associate($log['id_estado_relevamiento']);
      $log_isla->save();
    }

    $cambio = false;//Bandera para saber si cambio una maquina
    {
      $id_maqs_enviadas = [];//Uso hashmap pq 1) es mas rapido 2) evito duplicados
      foreach($maquinas as $m) $id_maqs_enviadas[$m] = true;
      
      $id_maqs_ya_estaban = [];//Maquinas que ya estaban asociadas y las enviaron de vuelta
      foreach ($isla->maquinas as $m) {
        if(array_key_exists($m->id_maquina,$id_maqs_enviadas)){
          $id_maqs_ya_estaban[$m->id_maquina] = true;
        }
        else{//Si no esta en lo que envio, lo disasocio
          $m->isla()->dissociate();
          $m->save();
          $razon = "Se eliminó la mtm " . $m->nro_admin . " de la isla " . $isla->nro_isla . ".";
          LogMaquinaController::getInstancia()->registrarMovimiento($m->id_maquina, $razon,4);//tipo mov cambio layout
          $cambio = true;
        }
      }

      // Enviado - YaEstaban = Maquinas a agregar
      $id_maqs_a_agregar = array_diff_assoc($id_maqs_enviadas,$id_maqs_ya_estaban);
      foreach($id_maqs_a_agregar as $id_maquina => $ignorar) {
        MTMController::getInstancia()->asociarIsla($id_maquina, $isla->id_isla);
        MovimientoIslaController::getInstancia()->guardar($isla->id_isla, $id_maquina);//Deprecar esto tambien...
        $cambio = true;
      }
    }

    if($cambio){//Deprecar LogIsla
      $log = new LogIsla;
      $log->isla()->associate($isla->id_isla);
      $log->estado_relevamiento()->associate(5);//5 -> estado de relevamiento sin relevar!
      $log->fecha = date("Y-m-d");
      $log->save();
    }

    return ['isla' => $isla , 'sector' => $isla->sector];
  }

  public function buscarIslotes($id_casino){
    //@TODO: Agregar un CHECK de BD que no permita tener el mismo nro_islote en 2 sectores??
    $sectores = DB::table('sector')
    ->selectRaw('sector.id_sector,sector.descripcion')
    ->leftJoin('isla',function($j){
      return $j->on('isla.id_sector','=','sector.id_sector')->whereNull('isla.deleted_at');
    })
    ->where('sector.id_casino','=',$id_casino)
    ->whereNull('sector.deleted_at')
    ->orderBy(DB::raw('MIN(isla.orden)'),'asc')
    ->groupBy('sector.id_sector')
    ->get();

    /* Los objetos no estan garantizados de estar preservar su orden por key en JSON y Javascript, por lo que 
       me re ordena el orden de los islotes, por eso duplico los campos como key y como valor en el mismo objeto
       y despues lo convierto a arreglo antes de retornar. Octavio Garcia Aguirre 2022-02-15  */
    $sector_islotes_arr = [];
    foreach($sectores as $s){
      $sector_islotes_arr[$s->id_sector] = [
        'id_sector'   => $s->id_sector,
        'descripcion' => $s->descripcion,
        'islotes'     => []
      ];
    }
    $sector_islotes_arr['SIN_SECTOR'] = [
      'id_sector'   => 'SIN_SECTOR',
      'descripcion' => 'SIN_SECTOR',
      'islotes'     => []
    ];

    $islotes_islas = DB::table('isla')
    ->selectRaw('sector.id_sector,isla.nro_islote,GROUP_CONCAT(distinct isla.nro_isla ORDER BY isla.orden ASC SEPARATOR ",") as islas')
    ->join('sector',function($j){//No se si mostrar las que tienen o no sector (el "join" en vez de "leftJoin" hace un NOP todo el algoritmo de SIN_SECTOR porque siempre lo borra)
      return $j->on('sector.id_sector','=','isla.id_sector')->whereNull('sector.deleted_at');
    })
    ->where('isla.id_casino','=',$id_casino)
    /*->whereRaw('EXISTS (
      SELECT m.id_maquina FROM maquina m WHERE m.id_isla = isla.id_isla AND m.deleted_at IS NULL LIMIT 1
    )')*/ //No se si mostrar la isla si tiene maquinas o no... es a requerimiento eso...
    ->whereNull('isla.deleted_at')
    ->groupBy(DB::raw('sector.id_sector,isla.nro_islote'))
    ->orderBy(DB::raw('MIN(isla.orden)'), 'asc')
    ->get();
    foreach($islotes_islas as $idx => $ii){
      $sector_islotes_arr[$ii->id_sector ?? 'SIN_SECTOR']['islotes'][] = 
      [
        'nro_islote' => $ii->nro_islote ?? 'SIN_NRO_ISLOTE',
        'islas'      => explode(',',$ii->islas),
      ];
    }
    if(count($sector_islotes_arr['SIN_SECTOR']['islotes']) == 0){//No muestro el SIN_SECTOR si no es necesario
      unset($sector_islotes_arr['SIN_SECTOR']);
    }
    return array_values($sector_islotes_arr);//Le saco el indice para que no me reordene
  }

  public function asignarIslotes(Request $request){
    Validator::make($request->all() , [
      'id_casino'                        => 'required|integer|exists:casino,id_casino',
      'sectores'                         => 'required|array',
      'sectores.*.id_sector'             => 'required|integer|exists:sector,id_sector',
      'sectores.*.islotes'               => 'nullable|array',
      'sectores.*.islotes.*.nro_islote'  => 'nullable|integer',
      'sectores.*.islotes.*.islas'       => 'nullable|array',
      'sectores.*.islotes.*.islas.*'     => 'required|integer|exists:isla,nro_isla',
    ], 
    self::$errores, self::$atributos)->after(function ($validator){
      if($validator->errors()->any()) return;//Retorno temprano si hay errores de que no existen en la BD
      $id_casino = $validator->getData()['id_casino'];
      if(!UsuarioController::getInstancia()->quienSoy()['usuario']->usuarioTieneCasino($id_casino)){
        $validator->errors()->add('id_casino',self::$errors['privilegios']);
        return;
      }
      $sectores = $validator->getData()['sectores'];
      {
        $id_sectores_request = array_map(function($s){return $s['id_sector'];},$sectores);
        $c1 = Sector::where('id_casino','=',$id_casino);
        $c2 = (clone $c1)->whereIn('id_sector',$id_sectores_request)->get()->count();
        $c1 = $c1->get()->count();
        //La primera condición detecta que se enviaron menos sectores de los que hay en la BD
        //La segunda condición detecta que se enviaron mas sectores (repetidos o algunos softdeletedes)
        if(($c1 != $c2) || ($c1 != count($id_sectores_request))){
          $validator->errors()->add('sectores','Sectores incorrectos');
          return;
        }
      }
      {
        $islas = [];
        foreach($sectores as $s){
          $islotes = $s['islotes'] ?? [];
          foreach($islotes as $islote){
            $islas = array_merge($islas,$islote['islas'] ?? []);
          }
        }
        //Lo hago asi por el temita de las subislas (pueden haber mas de 1 isla con el mismo nro pero distinto codigo)
        $c1 = DB::table('isla')->select('nro_isla')->distinct()->whereNull('deleted_at')->where('id_casino','=',$id_casino)->whereNotNull('id_sector');
        $c2 = (clone $c1)->whereIn('nro_isla',$islas)->get()->count();
        $c1 = $c1->get()->count();
        if(($c1 != $c2) || ($c1 != count($islas))){
          $validator->errors()->add('islas','Islas incorrectas');
          return;
        }
      }
    })->validate();
    return DB::transaction(function() use ($request){
      //Lo paso asi para eliminar islotes duplicados, se le asigna el orden del primer aparición
      //No se me ocurre una forma de intuitiva de evitar islotes duplicados desde el frontend/validate
      $sectores = [];
      foreach($request['sectores'] as $sector){
        $id_sector = $sector['id_sector'];
        if(!array_key_exists($id_sector,$sectores)) $sectores[$id_sector] = [];

        foreach($sector['islotes'] as $islote){
          $nro_islote = $islote['nro_islote'];
          if(!array_key_exists($nro_islote,$sectores[$id_sector])) $sectores[$id_sector][$nro_islote] = [];
          
          foreach($islote['islas'] as $nro_isla){
            if(!array_key_exists($nro_isla,$sectores[$id_sector][$nro_islote])){
              $sectores[$id_sector][$nro_islote][$nro_isla] = true;
            }
          }
        }
      }
      $orden = 0;
      foreach($sectores as $id_sector => $islotes){
        foreach($islotes as $nro_islote => $islas){
          foreach($islas as $nro_isla => $ignorar){
            $subislas = Isla::where([
              ['id_casino','=',$request->id_casino],
              ['nro_isla','=',$nro_isla],
            ])->get();
            foreach($subislas as $si){//Si, cuatro iteraciones...
              $si->id_sector  = $id_sector == ""? null : $id_sector;
              $si->nro_islote = $nro_islote == ""? null : $nro_islote;
              $si->orden      = $orden;
              $si->save();
              $orden++;
            }
          }
        }
      }
      return 1;
    });
  }
}
