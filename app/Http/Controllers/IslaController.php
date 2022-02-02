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
      return view('seccionIslas' , ['casinos' => $usuario->casinos]);
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
    if(!empty($request->id_sector))
      $reglas[]=['isla.id_sector','=',$request->sector];
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
    ->selectRaw('isla.id_isla, isla.nro_isla , isla.codigo , COUNT(id_maquina) as cantidad_maquinas ,sector.descripcion AS sector, casino.id_casino as id_casino, casino.nombre as casino')
    ->leftJoin('maquina','maquina.id_isla','=','isla.id_isla')
    ->join('sector','sector.id_sector','=','isla.id_sector')
    ->join('casino' ,'sector.id_casino' , '=' ,'casino.id_casino')
    ->where($reglas)
    ->whereNull('maquina.deleted_at')
    ->whereNull('isla.deleted_at')
    ->whereIn('isla.id_casino' , $casinos)
    ->groupBy('isla.id_isla');
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

  //Rosario usa el codigo de otra forma... casi poniendole una descripcion nmemotecnica, nose como unificar SantaFe-Mel con Rosario
  public function guardarIsla(Request $request){
    Validator::make($request->all(), [
        'nro_isla' => 'required|integer|max:9999999999',
        'id_sector' => 'required|exists:sector,id_sector',
        'id_casino' => 'required|exists:casino,id_casino',
        'codigo' => 'nullable',
        'maquinas' => 'nullable',
        'maquinas.*' => 'required|exists:maquina,id_maquina'
    ], self::$errores, self::$atributos)->after(function ($validator){
      if($validator->errors()->any()) return;
      $maquinas = $validator->getData()['maquinas'] ?? [];
      $id_casino = $validator->getData()['id_casino'];
      foreach ($maquinas as $id_maquina) {
        $m = Maquina::find($id_maquina);
        if(is_null($m) || $m->id_casino != $id_casino){
          $validator->errors()->add('maquinas',self::$errores['incompatibilidad']);
          return;
        }
      }
      $nro_isla = $validator->getData()['nro_isla'];
      $reglas = [['id_casino' , '=' , $id_casino],['nro_isla' , '=' , $nro_isla]];
      $codigo = $validator->getData()['codigo'] ?? null;
      foreach(Isla::where($reglas)->get() as $i){
        if(!empty($codigo)){
          if(empty($i->codigo)){
            $validator->errors()->add('codigo','Existe otra isla con el mismo N° sin codigo');
          }
          if($i->codigo == $codigo){
            $validator->errors()->add('codigo','El código de subisla ya esta en uso.');
          }
        }
        else{
          if(!empty($i->codigo)){
            $validator->errors()->add('codigo','Existe otra isla con el mismo N° con codigo');
          }
          else{
            $validator->errors()->add('nro_isla','Existe otro número de isla sin codigo.');
          }
        }
      }
    })->validate();

    return DB::transaction(function () use ($request){
      $isla = new Isla;
      $isla->nro_isla  = $request->nro_isla;
      $isla->codigo    = $request->codigo;
      $isla->id_casino = $request->id_casino;
      $isla->id_sector = $request->id_sector;
      $isla->save();
  
      //creo el log de isla para controlar el movimiento
      $this->guardarLogIsla($isla->id_isla, 5); //5 -> estado de relevamiento sin relevar!
      $request_maquinas = $request->maquinas ?? [];
      foreach($request_maquinas as $id_maquina) {
        MTMController::getInstancia()->asociarIsla($id_maquina, $isla->id_isla);
        MovimientoIslaController::getInstancia()->guardar($isla->id_isla, $id_maquina);  //para controlar el movimiento
      }
      return ['isla' => $isla, 'sector' => $isla->sector];
    });
  }

  public function encontrarOCrear($nro_isla , $id_casino){
    $isla=$this->buscarIslaPorNro($nro_isla ,$id_casino);
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

  public function modificarIsla(Request $request){
    Validator::make($request->all(), [
      'id_isla'  => 'required|integer|exists:isla,id_isla',
      'nro_isla' => 'required|integer|max:9999999999',
      'id_sector' => 'required|exists:sector,id_sector',
      'id_casino' => 'required|exists:casino,id_casino',
      'codigo' => 'nullable',
      'maquinas' => 'nullable',
      'maquinas.*' => 'required|exists:maquina,id_maquina',
      'historial' => 'nullable',
      'historial.*.id_log_isla' => 'required | exists:log_isla,id_log_isla',
      'historial.*.id_estado_relevamiento' => 'required | exists:estado_relevamiento,id_estado_relevamiento',
    ], self::$errores, self::$atributos)->after(function ($validator){
      if($validator->errors()->any()) return;
      
      $casinos_acceso = [];
      foreach(UsuarioController::getInstancia()->quienSoy()['usuario']->casinos as $c){
        $casinos_acceso[$c->id_casino] = true;
      }
      $isla = Isla::find($validator->getData()['id_isla']);
      if(!array_key_exists($isla->id_casino,$casinos_acceso)){
        $validator->errors()->add('id_isla',self::$errores['privilegios']);
        return;
      }
      $id_casino = $validator->getData()['id_casino'];
      if(is_null($isla) || $isla->id_casino != $id_casino){//chequeo is_null porque tiene softdeletes
        $validator->errors()->add('id_isla',self::$errores['incompatibilidad']);
        return;
      }

      $historial = $validator->getData()['historial'] ?? [];
      foreach($historial as $idx => $h){//Deprecar LogIsla
        $logisla = LogIsla::find($h['id_log_isla']);
        if($logisla->id_isla != $isla->id_isla){
          $validator->errors()->add('historial',self::$errores['incompatibilidad']);
          return;
        }
      }

      $maquinas = $validator->getData()['maquinas'] ?? [];
      foreach ($maquinas as $id_maquina) {
        $m = Maquina::find($id_maquina);
        if(is_null($m) || $m->id_casino != $id_casino){
          $validator->errors()->add('maquinas',self::$errores['incompatibilidad']);
          return;
        }
      }

      $nro_isla = $validator->getData()['nro_isla'];
      $reglas = [['id_casino' , '=' , $id_casino],['nro_isla' , '=' , $nro_isla],['id_isla','<>',$isla->id_isla]];
      $codigo = $validator->getData()['codigo'] ?? null;
      foreach(Isla::where($reglas)->get() as $i){
        if(!empty($codigo)){
          if(empty($i->codigo)){
            $validator->errors()->add('codigo','Existe otra isla con el mismo N° sin codigo');
          }
          if($i->codigo == $codigo){
            $validator->errors()->add('codigo','El código de subisla ya esta en uso.');
          }
        }
        else{
          if(!empty($i->codigo)){
            $validator->errors()->add('codigo','Existe otra isla con el mismo N° con codigo');
          }
          else{
            $validator->errors()->add('nro_isla','Existe otro número de isla sin codigo.');
          }
        }
      }
    })->validate();

    return DB::transaction(function () use ($request){
      $isla = Isla::find($request->id_isla);
      $isla->nro_isla  = $request->nro_isla;
      $isla->id_casino = $request->id_casino;
      $isla->id_sector = $request->id_sector;
      $isla->codigo    = $request->codigo;
      $isla->save();
  
      if(!empty($request->historial)){
        foreach ($request->historial as $log) {
          $log_isla = LogIsla::find($log['id_log_isla']);
          $log_isla->estado_relevamiento()->associate($log['id_estado_relevamiento']);
          $log_isla->save();
        }
      }
      
      $request_maquinas = $request['maquinas'] ?? [];
      $id_maqs_enviadas = [];//Uso hashmap pq 1) es mas rapido 2) evito duplicados
      foreach($request_maquinas as $m) $id_maqs_enviadas[$m] = true;
      
      $primer_logeo = true;//Bandera para hacer un solo log por mas que cambien muchas maquinas
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
          //Deprecar LogIsla
          if($primer_logeo){ $this->guardarLogIsla($isla->id_isla, 5);}//5 -> estado de relevamiento sin relevar!
          $primer_logeo = false;
        }
      }

      // Enviado - YaEstaban = Maquinas a agregar
      $id_maqs_a_agregar = array_diff_assoc($id_maqs_enviadas,$id_maqs_ya_estaban);
      foreach($id_maqs_a_agregar as $id_maquina => $ignorar) {
        MTMController::getInstancia()->asociarIsla($id_maquina, $isla->id_isla);
        MovimientoIslaController::getInstancia()->guardar($isla->id_isla, $id_maquina);//Deprecar esto tambien...
        if($primer_logeo){  $this->guardarLogIsla($isla->id_isla, 5);}
        $primer_logeo = false;
      }
  
      return ['isla' => $isla , 'sector' => $isla->sector];
    });
  }

  private function guardarLogIsla($id_isla, $id_estado_relevamiento){//Deprecar esto
    $log = new LogIsla;
    $log->isla()->associate($id_isla);
    $log->estado_relevamiento()->associate($id_estado_relevamiento);
    $log->fecha = date("Y-m-d");
    $log->save();
  }
}
