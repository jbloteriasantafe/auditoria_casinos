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
    Validator::make($request->all() ,  [
        'cantidad_maquinas' => 'nullable|numeric',
    ], array(), self::$atributos)->validate();

    $reglas = array();
    if(!empty($request->nro_isla))
      $reglas[]=['nro_isla','like','%'.$request->nro_isla.'%'];
    if($request->sector != 0)
      $reglas[]=['sector.id_sector','=',$request->sector];

    if($request->casino==0){
        $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
        $casinos = array();
        foreach($usuario->casinos as $casino){
          $casinos [] = $casino->id_casino;
        }
    }else {
      $casinos[]=$request->casino;
    }

    $sort_by = $request->sort_by;
    $resultados=DB::table('isla')
    ->selectRaw('isla.id_isla, isla.nro_isla , isla.codigo , COUNT(id_maquina) as cantidad_maquinas ,sector.descripcion AS sector, casino.id_casino as id_casino, casino.nombre as casino')
    ->leftJoin('maquina','maquina.id_isla','=','isla.id_isla')
    ->join('sector','sector.id_sector','=','isla.id_sector')
    ->join('casino' ,'sector.id_casino' , '=' ,'casino.id_casino')
    ->when($sort_by,function($query) use ($sort_by){
                    return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                })
    ->where($reglas)
    ->whereNull('maquina.deleted_at')
    ->whereNull('isla.deleted_at')
    ->whereIn('isla.id_casino' , $casinos)
    ->groupBy('isla.id_isla')->paginate($request->page_size);

    return $resultados;
  }

  public function obtenerIsla($id){
    $nivelesNuevo=array();
    $isla = Isla::find($id);
    if($isla->sector != null){
      $sector=$isla->sector;
    }else{
      $sector='';
    };
    if ($isla->maquinas->count() != 0){
      $maquinas= $isla->maquinas;
    }else{
      $maquinas='';
    }

    $casino = $isla->casino;

    $historial= DB::table('log_isla')
                    ->select('log_isla.id_log_isla','log_isla.fecha','estado_relevamiento.descripcion')
                    ->join('estado_relevamiento','estado_relevamiento.id_estado_relevamiento','=','log_isla.id_estado_relevamiento')
                    ->where('log_isla.id_isla','=',$id)
                    ->orderBy('log_isla.fecha','desc')
                    ->take(3)
                    ->get();

    $estados = EstadoRelevamiento::all();
    return ['isla' => $isla ,
            'sector' => $sector ,
            'maquinas' => $maquinas ,
            'historial' => $isla->logs_isla,
            'estados' => $estados,
            'historial' =>$historial,
            'casino' => $casino
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

    Validator::make([
         'id_isla' => $id,
      ] ,
       [
         'id_isla' => 'required|integer|exists:isla,id_isla'
      ] , array(), self::$atributos)->after(function ($validator){
        $isla = Isla::find($validator->getData()['id_isla']);
        foreach ($isla->maquinas as $maquina) {
            if ($maquina->estado_maquina->descripcion == 'Ingreso' && $maquina->estado_maquina->descripcion == 'Reingreso') {
              $validator->errors()->add('id_isla','Existen máquinas habilitadas en la isla.');
            }
        }

        })->validate();

    $isla = Isla::find($id);

    if(!empty($isla->maquinas)){
      foreach($isla->maquinas as $maquina){
        MTMController::getInstancia()->desasociarIsla($maquina->id_maquina, $isla->id_isla);
          MovimientoIslaController::getInstancia()->guardar($isla->id_isla, $maquina['id_maquina']);  //para controlar el movimiento
      }
    }

    $isla->logs_isla()->delete();

    // creo el log de isla para controlar el movimiento
    // LogIslaController::getInstancia()->guardar($isla->id_isla, 5); //5 -> estado de relevamiento sin relevar!

    $isla->delete();

    return ['isla' => $isla];
  }

  public function guardarIsla(Request $request){
    Validator::make($request->all(), [
        'nro_isla' => 'required|integer|max:9999999999',
        'sector' => 'required|exists:sector,id_sector',
        'casino' => 'required|exists:casino,id_casino',
        'codigo' => 'nullable',
        'maquinas' => 'nullable',
        'maquinas.*' => 'required|exists:maquina,id_maquina'
    ], array(), self::$atributos)->after(function ($validator){
        if(isset($validator->getData()['maquinas'])){
            foreach ($validator->getData()['maquinas'] as $maquina) {
              $aux= Maquina::find($maquina);
              if($aux->id_casino != $validator->getData()['casino']){
                   $validator->errors()->add('casino','El casino seleccionado no concuerda con el casino de las maquinas');
              }
            }}
        $islas=Isla::where([['id_casino' , '=' , $validator->getData()['casino']],['nro_isla' , '=' , $validator->getData()['nro_isla']]])->get();

        if($validator->getData()['codigo'] != ''){//estoy creando sub isla
          $reglasBusqueda=array();
          if($validator->getData()['casino'] != 0){
            $reglasBusqueda[]= ['id_casino' , '=' , $validator->getData()['casino']];
          }
          if($validator->getData()['nro_isla'] != ''){
            $reglasBusqueda[]= ['nro_isla' , '=' , $validator->getData()['nro_isla']];
          }
          if($validator->getData()['codigo'] != ''){
            $reglasBusqueda[]= ['codigo', '=' , $validator->getData()['codigo']];
          }
          $islasCodigo=Isla::where($reglasBusqueda)->get();
          if($islasCodigo->count()>=1){
               $validator->errors()->add('codigo','El código de Subisla ya esta en uso.');
          }
          foreach ($islas as $isla) {
            if($isla->codigo == ''){
              $validator->errors()->add('existe','Existe otro número de isla sin codigo.');
            }
          }

        }else { // guardando isla
          if($islas->count() > 0 ){
            $validator->errors()->add('nro_isla','El número de isla ' . $validator->getData()['nro_isla']. ' ya esta en uso.');
          }
        }

    })->validate();

    $isla = new Isla;
    $isla->nro_isla = $request->nro_isla;
    $isla->codigo= $request->codigo;
    $isla->id_casino=$request->casino;
    $isla->id_sector = $request->sector;
    $isla->save();


    //creo el log de isla para controlar el movimiento

    LogIslaController::getInstancia()->guardar($isla->id_isla, 5); //5 -> estado de relevamiento sin relevar!


    if(!empty($request->maquinas)){
      foreach($request->maquinas as $maquina) {
        MTMController::getInstancia()->asociarIsla($maquina, $isla->id_isla);
        MovimientoIslaController::getInstancia()->guardar($isla->id_isla, $maquina);  //para controlar el movimiento
      }
    }

    return ['isla' => $isla , 'sector' => $isla->sector ];
  }

  //guarda isla cuando se crea desde el gestionar maquinas
  public function saveIsla($isla, $maquinas){
    $isla->save();
    if(!empty($maquinas)){
      foreach($maquinas as $maquina) {
        MTMController::getInstancia()->asociarIsla($maquina, $isla->id_isla);
        MovimientoIslaController::getInstancia()->guardar($isla->id_isla, $maquina);  //para controlar el movimiento
      }
    LogIslaController::getInstancia()->guardar($isla->id_isla, 5); //5 -> estado de relevamiento sin relevar!
    }
    return $isla;
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
        'nro_isla' => 'required|integer|max:9999999999',
        'codigo' => 'nullable',
        'sector' => 'required|exists:sector,id_sector',
        'casino' => 'required|exists:casino,id_casino',
        'maquinas' => 'nullable',
        'maquinas.*' => 'required|exists:maquina,id_maquina',
        'historial' => 'nullable',
        'historial.*.id_log_isla' => 'required | exists:log_isla,id_log_isla',
        'historial.*.id_estado_relevamiento' => 'required | exists:estado_relevamiento,id_estado_relevamiento',
    ], array(), self::$atributos)->after(function ($validator){

      if(isset($validator->getData()['maquinas'])){
       foreach ($validator->getData()['maquinas'] as $maquina) {
          $aux= Maquina::find($maquina);
          if($aux->id_casino != $validator->getData()['casino']){
               $validator->errors()->add('casino','El casino seleccionado no concuerda con el casino de las maquinas');
          }
       }
     }
      $islaModificar= Isla::find($validator->getData()['id_isla']);
      $islas=Isla::where([['id_casino' , '=' , $validator->getData()['casino']], ['id_isla' , '!=' , $islaModificar->id_isla],['nro_isla' , '=' , $validator->getData()['nro_isla']]])->get();

      if($validator->getData()['codigo'] != ''){//estoy creando sub isla
        $reglasBusqueda=array();
        $reglasBusqueda[]=['id_isla' , '!=' , $islaModificar->id_isla];

        if($validator->getData()['casino'] != 0){
          $reglasBusqueda[]= ['id_casino' , '=' , $validator->getData()['casino']];
        }

        if($validator->getData()['nro_isla'] != ''){
          $reglasBusqueda[]= ['nro_isla' , '=' , $validator->getData()['nro_isla']];
        }

        if($validator->getData()['codigo'] != ''){
          $reglasBusqueda[]= ['codigo', '=' , $validator->getData()['codigo']];
        }

        $islasCodigo=Isla::where($reglasBusqueda)->get();

        if($islasCodigo->count()>=1){
             $validator->errors()->add('codigo','El código de Subisla ya esta en uso.');
        }
        foreach ($islas as $isla) {
          if($isla->codigo == ''){
            $validator->errors()->add('existe','Existe otro número de isla sin codigo.');
          }
        }

      }else { // guardando isla
        if($islas->count() > 0 ){
          $validator->errors()->add('nro_isla','El número de isla ' . $validator->getData()['nro_isla']. ' ya esta en uso.');
        }
      }
    })->validate();

    $isla = Isla::find($request->id_isla);
    $isla->nro_isla = $request->nro_isla;
    $isla->codigo = $request->codigo;
    $isla->id_sector = $request->sector;
    $isla->save();

    if(!empty($request->historial)){
      foreach ($request->historial as $log) {
        $log_isla = LogIsla::find($log['id_log_isla']);
        $log_isla->estado_relevamiento()->associate($log['id_estado_relevamiento']);
        $log_isla->save();
      }
    }

    $cambios = 0;

    //desasociar maquinas viejas
    foreach ($isla->maquinas as $maquinaActual) {
      if($this->noEstaraMasEnLaIsla($maquinaActual ,$request['maquinas'])) {
        $maquinaActual->isla()->dissociate();
        $maquinaActual->save();
        $razon = "Se eliminó la mtm " . $maquinaActual->nro_admin . " de la isla " . $isla->nro_isla . ".";
        LogMaquinaController::getInstancia()->registrarMovimiento($maquinaActual->id_maquina, $razon,4);//tipo mov cambio layout
        if($cambios ==0){ LogIslaController::getInstancia()->guardar($isla->id_isla, 5);}//5 -> estado de relevamiento sin relevar!
        $cambios++;
      }
    }

    //asociar maquinas nuevas
    if(!empty($request->maquinas)){
      foreach($request->maquinas as $maquina) {
        //maquina es un id , $isla->maquinas es arreglo de instancias de maquina
        if($this->noEstabaEnLaIsla($maquina, $isla->maquinas)){
          MTMController::getInstancia()->asociarIsla($maquina, $isla->id_isla); //el log_maquina se crea en esa fn
          MovimientoIslaController::getInstancia()->guardar($isla->id_isla, $maquina);  //para controlar el movimiento
          if($cambios ==0){ LogIslaController::getInstancia()->guardar($isla->id_isla, 5);}
          $cambios++;
        }
      }
    }

    return ['isla' => $isla , 'sector' => $isla->sector];
  }

  private function noEstaraMasEnLaIsla($maquinaActual ,$maquinas){ //maqActual
    //es de la isla, $maquinas es del request
    $aux= true;

    if(empty($maquinas)){
      return true;
    }

    foreach($maquinas as $mtm) {
      if($mtm == $maquinaActual->id_maquina){
        $aux = false;//sigue estando
      }
    }

    return $aux; //no va a estar más
  }

  private function noEstabaEnLaIsla($maquina, $maquinas){ //maq que esta
    //en el request, maquinas que estaban en la isla
    $aux=true;
    if(empty($maquinas)){
      return true;
    }
    //mtm es instancia de maquina
    foreach ($maquinas as $mtm){
      if($maquina == $mtm->id_maquina){
        $aux=false;//si esta en la isla
      }
    }
    return $aux;//no esta en la isla
  }
}
