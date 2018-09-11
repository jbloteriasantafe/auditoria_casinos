<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\UsuarioController;
use App\Isla;
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

  private static $instance;

  public function buscarTodo(){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $casinos = array();
      foreach($usuario->casinos as $casino){
        $casinos [] = $casino->id_casino;
      }

      $casinos= Casino::whereIn('id_casino',$casinos)->get();

      UsuarioController::getInstancia()->agregarSeccionReciente('Islas' , 'islas');

      return view('seccionIslas' , ['casinos' => $casinos]);
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
        $cantidad += $unaIsla->maquinas->count();
        $detalles[] = $aux;
      }
    return ['islas' => $detalles, 'cantidad_maquinas' => $cantidad];
  }

  public function actualizarListaMaquinas(Request $request){
    //este se utiliza cuando se modifica una mauqina usando el modal "DIVIDIR ISLA"
    Validator::make($request->all() , [
        'detalles.*.id_isla' => 'required|integer',
        'detalles.*.maquinas.*.id_maquina' => 'required|integer|exists:maquina,id_maquina',
        'detalles.*.codigo' => 'nullable|alpha_dash',
      ] , array(), self::$atributos)->after(function ($validator){
        $detalles = $validator->getData()['detalles'];
        $codigos = array();
        foreach ($detalles as $index => $detalle){
          $id_casino = $validator->getData()['id_casino'];
          $nro_isla =  $validator->getData()['nro_isla'];

          if(isset($detalle['maquinas'])){

            if($detalle['codigo'] == null){
              $validator->errors()->add('codigo.' . $index ,'El código no puede estar en blanco.');
            }else {
              if(in_array($detalle['codigo'] , $codigos)){
                $validator->errors()->add('duplicado.' . $index ,'El código de subisla ya está tomado.');
              }
              $codigos[] = $detalle['codigo'];
            }

            if($detalle['id_sector'] == null)
              $validator->errors()->add('sector.' . $index ,'El valor del sector no es válido.');

            if($detalle['id_isla'] > 0){
              $isla = Isla::find($detalle['id_isla']);
              if($isla->nro_isla != $nro_isla)
                $validator->errors()->add('sin_id' . $index,'Ocurrió un error.');
            }

          }
        }


      })->validate();

      $contador = 0;

      foreach($request->detalles as $detalle) {
        if(isset($detalle['maquinas'])){
          if($detalle['id_isla'] == 0){
            $isla = new Isla();
            $isla->nro_isla = $request->nro_isla;
            $isla->id_casino = $request->id_casino;
            $isla->id_sector = $detalle['id_sector'];
          }else {
            $isla = Isla::find($detalle['id_isla']);
            $isla->id_sector = $detalle['id_sector'];
          }
          $isla->codigo = $detalle['codigo'];
          $mtmcontroller = MTMController::getInstancia();

          $isla->save();

          foreach ($detalle['maquinas'] as $maquina){
            $mtmcontroller->asociarIsla($maquina['id_maquina'],$isla->id_isla);
          }

          $contador++;
        }
      }//fin foreach

      switch ($contador) {
        case 1://si existe una sola isla "activa" le saco el codigo
          $isla->codigo = null;
          $isla->save();
          break;
        case 0:
          break;
        default:
          break;
      }

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
    ->select(DB::raw('isla.id_isla, isla.nro_isla , isla.codigo , COUNT(id_maquina) as cantidad_maquinas ,sector.descripcion AS sector, casino.id_casino as id_casino, casino.nombre as casino'))
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

    if($isla->niveles_progresivo != null){
        $isla->niveles_progresivo()->detach();
    }

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

  public function modifyIsla($islaModif, $id_isla, $maquinas){

    $unaIsla = Isla::find($id_isla);
    $unaIsla->nro_isla= $islaModif->nro_isla;
    $unaIsla->codigo= $islaModif->codigo;
    $unaIsla->id_casino = $islaModif->id_casino;
    $unaIsla->id_sector = $islaModif->id_sector;
    //desasociar maquinas viejas
    foreach ($unaIsla->maquinas as $maquinaActual) {
      if($this->noEstaraMasEnLaIsla($maquinaActual ,$maquinas)) {
        $maquinaActual->isla()->dissociate();
        $maquinaActual->save();
        $razon = "Se eliminó la mtm " . $maquinaActual->nro_admin ." de la isla " . $unaIsla->nro_isla . ".";
        LogMaquinaController::getInstancia()->registrarMovimiento($maquinaActual->id_maquina, $razon,4);//tipo mov cambio layout
      }
    }

    //asociar maquinas nuevas
    if(!empty($request->maquinas)){
      foreach($request->maquinas as $maquina) {
        if($this->noEstabaEnLaIsla($maquina, $unaIsla->maquinas)){
          MTMController::getInstancia()->asociarIsla($maquina['id_maquina'], $unaIsla->id_isla); //el log_maquina se crea en esa fn
          MovimientoIslaController::getInstancia()->guardar($unaIsla->id_isla, $maquina['id_maquina']);  //para controlar el movimiento
          $razon = "Se agregó la mtm a la isla ". $unaIsla->nro_isla .".";
          LogMaquinaController::getInstancia()->registrarMovimiento($maquina['id_maquina'], $razon,4);//tipo mov cambio layout
        }
      }
    }
    $unaIsla->save();
    LogIslaController::getInstancia()->guardar($unaIsla->id_isla, 5); //5 -> estado de relevamiento sin relevar!
    return $unaIsla;
  }

  public function desasociarMaquinas($id_isla){
    $isla= Isla::find($id_isla);
      foreach ($isla->maquinas as $maquina) {
        $maquina->isla()->dissociate();
        $maquina->save();
        $razon = "La maquina no pertence más a la isla ". ($isla->nro_isla);
        LogMaquinaController::getInstancia()->registrarMovimiento($maquina->id_maquina, $razon,4);//tipo movimiento cambio layout
      }
  }

  //no se usa nunca?
  public function asociarMaquinas($maquinas, $id_isla){
    $isla= Isla::find($id_isla);

      foreach ($maquinas as $maq) {
        $maquina = Maquina::find($maq);
        $maquina->isla()->associate($id_maquina);
        $maquina->save();
        $razon = "La maquina se agregó a la isla ". ($isla->nro_isla);
        LogMaquinaController::getInstancia()->registrarMovimiento($id_maquina, $razon,4);//tipo movimiento cambio layout

      }
  }

}
