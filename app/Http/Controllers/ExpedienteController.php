<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Expediente;
use App\Casino;
use App\LogMovimiento;
use App\TipoMovimiento;
use App\Nota;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Http\Controllers\ExpedienteController;

class ExpedienteController extends Controller
{
  private static $atributos = [
    'nro_exp_org' => 'Nro Expediente Organización',
    'nro_exp_interno' => 'Nro Expediente Interno',
    'nro_exp_control' => 'Nro Expediente Control',
    'fecha_pase' => 'Fecha de Pase',
    'fecha_iniciacion' => 'Fecha de Iniciación',
    'iniciador' => 'Iniciador',
    'remitente' => 'Remitente',
    'concepto' => 'Concepto',
    'ubicacion_fisica' => 'Ubicación Física',
    'destino' => 'Destino',
    'nro_folios' => 'Nro Folios',
    'tema' => 'Tema',
    'anexo' => 'Anexo',
    'nro_cuerpos' => 'Nro Cuerpo',
    'id_casino' => 'Casino',
    'resolucion' => 'Resolución',
    'resolucion.nro_resolucion' => 'Nro Resolución',
    'resolucion.nro_resolucion_anio' => 'Nro Resolución Año',
    'disposiciones' => 'Disposiciones',
    'disposiciones.*.nro_disposicion' => 'Nro Disposición',
    'disposiciones.*.nro_disposicion_anio' => 'Nro Disposición Año',
    'id_tipo_movimiento' => 'Tipo de Movimiento'
  ];
  private static $errores =       [
    'required' => 'El valor es requerido',
    'integer' => 'El valor no es un numero',
    'numeric' => 'El valor no es un numero',
    'exists' => 'El valor es invalido',
    'array' => 'El valor es invalido',
    'alpha_dash' => 'El valor tiene que ser alfanumérico opcionalmente con guiones',
    'regex' => 'El formato es incorrecto',
    'string' => 'El valor tiene que ser una cadena de caracteres',
    'string.min' => 'El valor es muy corto',
    'privilegios' => 'No puede realizar esa acción',
    'incompatibilidad' => 'El valor no puede ser asignado',
  ];

  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new ExpedienteController();
    }
    return self::$instance;
  }

  public function buscarTodo(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $tipos_movimientos = TipoMovimiento::whereIn('id_tipo_movimiento',[1,2,3,4,5,6,7])->get();
    UsuarioController::getInstancia()->agregarSeccionReciente('Expedientes' , 'expedientes');
    return view('seccionExpedientes' , ['casinos' => $usuario->casinos,'tipos_movimientos' => $tipos_movimientos]);
  }

  public function obtenerExpediente($id){
    $expediente = Expediente::find($id);

    
    $tipo_descripcion = 'GROUP_CONCAT(DISTINCT(tipo_movimiento.descripcion) ORDER BY tipo_movimiento.descripcion ASC SEPARATOR ", ")';
    $notasMovimiento = DB::table('expediente')
                ->select('nota.*')
                ->selectRaw($tipo_descripcion.' as movimiento')
                ->join('nota', 'nota.id_expediente', '=', 'expediente.id_expediente')
                ->join('logmov_tipomov','logmov_tipomov.id_log_movimiento','=','nota.id_log_movimiento')
                ->join('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=','logmov_tipomov.id_tipo_movimiento')
                ->whereNull('nota.id_tipo_movimiento')
                ->where('expediente.id_expediente','=',$id)
                ->where('nota.es_disposicion',0)
                ->groupBY('nota.id_nota');

    //Estas son legacy de cuando habia 1 solo tipo por movimiento
    $notasMovimiento2 = DB::table('expediente')
    ->select('nota.*','tipo_movimiento.descripcion as movimiento')
    ->join('nota', 'nota.id_expediente', '=', 'expediente.id_expediente')
    ->join('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=','nota.id_tipo_movimiento')
    ->whereNotNull('nota.id_tipo_movimiento')
    ->where('expediente.id_expediente','=',$id)
    ->where('nota.es_disposicion',0);

    $notasMovimiento = $notasMovimiento->union($notasMovimiento2)->orderBy('fecha','asc')->get();

    $notas = DB::table('expediente')
                ->select('nota.*', 'expediente.tema')
                ->join('nota', 'nota.id_expediente', '=', 'expediente.id_expediente')
                ->where('expediente.id_expediente','=',$id)
                ->whereNull('nota.id_log_movimiento')
                ->whereNull('nota.id_tipo_movimiento')
                ->where('nota.es_disposicion','=',0)
                ->orderBy('nota.fecha','DESC')
                ->get();

    $disposiciones = DB::table('disposicion')
                          ->select('disposicion.*','tipo_movimiento.id_tipo_movimiento as id_tipo_movimiento','tipo_movimiento.descripcion as descripcion_movimiento')
                          ->leftJoin('nota','nota.id_nota','=','disposicion.id_nota')
                          ->leftJoin('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=','nota.id_tipo_movimiento')
                          ->where('disposicion.id_expediente','=',$id)
                          ->get();


    return ['expediente' => $expediente,
            'casinos' => $expediente->casinos,
            'resolucion' => $expediente->resoluciones,
            'disposiciones' => $disposiciones,
            'notas' => $notas,
            'notasConMovimientos' =>$notasMovimiento
          ];
  }

  public function guardarExpediente(Request $request){

    Validator::make($request->all(), [
        'nro_exp_org' => ['required','regex:/^\d\d\d\d\d$/'],
        'nro_exp_interno' => ['required','regex:/^\d\d\d\d\d\d\d$/','unique:expediente,nro_exp_interno'],
        'nro_exp_control' => ['required','regex:/^\d$/'],
        'fecha_iniciacion' => 'nullable|date',
        'fecha_pase' => 'nullable|date',
        'iniciador' => 'nullable|max:250',
        'concepto' => 'nullable|max:250',
        'ubicacion_fisica' => 'nullable|max:250',
        'remitente' => 'nullable|max:250',
        'destino' => 'nullable|max:250',
        'nro_folios' => 'nullable|integer',
        'tema' => 'nullable|max:250',
        'anexo' => 'nullable|max:250',
        'nro_cuerpos' => 'required|integer',
        'casinos' => 'required',
        'resolucion' => 'nullable',
        'resolucion.*.nro_resolucion' => ['required_with:resolucion','regex:/^\d\d\d$/'],
        'resolucion.*.nro_resolucion_anio' => ['required_with:resolucion','regex:/^\d\d$/'],
        'disposiciones' => 'nullable',
        'disposiciones.*.nro_disposicion' => ['required','regex:/^\d\d\d$/'],
        'disposiciones.*.nro_disposicion_anio' => ['required','regex:/^\d\d$/'],
        'notas'  => 'nullable',
        'notas.*.fecha'=>'required|date',
        'notas.*.identificacion'=>'required',
        'notas.*.detalle'=>'required',
        'notas.*.id_tipo_movimiento' => 'nullable|integer',
        'notas.*.nro_disposicion'=> 'nullable|integer',
        'notas.*.nro_disposicion_anio'=> 'nullable|integer',
        'notas.*.descripcion_disposicion'=> 'nullable',
        'notas_asociadas'  => 'nullable',
        'notas_asociadas.*.fecha'=>'required|date',
        'notas_asociadas.*.identificacion'=>'required',
        'notas_asociadas.*.detalle'=>'required',
        'notas_asociadas.*.id_log_movimiento' => 'required | exists:log_movimiento,id_log_movimiento',
    ], self::$errores, self::$atributos)->after(function ($validator){


      //validar que sea unico en conjunto con el nro_cuerpo

      $expedientes=Expediente::where([ ['nro_cuerpos' , '=' , $validator->getData()['nro_cuerpos']], ['nro_exp_interno', '=' , $validator->getData()['nro_exp_interno']]])->get();
      if($expedientes->count() > 0){
        $validator->errors()->add('nro_cuerpos', 'Ya existe un expediente con el número de expediente interno y cuerpo indicado.');
      }
    })->validate();

    $expediente = new Expediente;
    DB::transaction(function () use ($request,&$expediente){
      $expediente->nro_exp_org = $request->nro_exp_org;
      $expediente->nro_exp_interno = $request->nro_exp_interno;
      $expediente->nro_exp_control = $request->nro_exp_control;
      $expediente->fecha_iniciacion = $request->fecha_iniciacion;
      $expediente->fecha_pase = $request->fecha_pase;
      $expediente->iniciador = $request->iniciador;
      if(!empty($request->concepto)){
          $expediente->concepto = $request->concepto;
      }
      $expediente->ubicacion_fisica = $request->ubicacion_fisica;
      $expediente->remitente = $request->remitente;
      $expediente->destino = $request->destino;
      $expediente->nro_folios = $request->nro_folios;
      $expediente->tema = $request->tema;
      $expediente->anexo = $request->anexo;
      $expediente->nro_cuerpos = $request->nro_cuerpos;
      $expediente->save();
  
      foreach ($request['casinos'] as $id_casino) {
        $expediente->casinos()->attach(intval($id_casino));
      }
      $expediente->save();
    
      if(!empty($request->resolucion)){
        foreach($request->resolucion as $res){
          ResolucionController::getInstancia()->guardarResolucion($res,$expediente->id_expediente);
        }
  
      }
      if(!empty($request->disposiciones)){
        foreach ($request->disposiciones as $disp){
          DisposicionController::getInstancia()->guardarDisposicion($disp,$expediente->id_expediente);
        }
      }
  
      if(!empty($request->notas)){
        foreach ($request->notas as $nota){
          NotaController::getInstancia()->guardarNota($nota,$expediente->id_expediente,  $expediente->casinos->first()->id_casino);
        }
      }
      if(!empty($request->notas_asociadas)){
        foreach ($request->notas_asociadas as $nota){
          NotaController::getInstancia()->guardarNotaConMovimiento($nota,$expediente->id_expediente,  $expediente->casinos->first()->id_casino);
        }
      }
    });

    return ['expediente' => $expediente , 'casinos' => $expediente->casinos];
  }

  public function modificarExpediente(Request $request){
    Validator::make($request->all(), [
        'id_expediente' => 'required|exists:expediente,id_expediente',
        'nro_exp_org' => ['required','regex:/^\d\d\d\d\d$/'],
        'nro_exp_interno' => ['required','regex:/^\d\d\d\d\d\d\d$/'],
        'nro_exp_control' => ['required','regex:/^\d$/'],
        'fecha_iniciacion' => 'nullable|date',
        'fecha_pase' => 'nullable|date',
        'iniciador' => 'nullable|max:250',
        'concepto' => 'nullable|max:250',
        'ubicacion_fisica' => 'nullable|max:250',
        'remitente' => 'nullable|max:250',
        'destino' => 'nullable|max:250',
        'nro_folios' => 'nullable|integer',
        'tema' => 'nullable|max:250',
        'anexo' => 'nullable|max:250',
        'nro_cuerpos' => 'required|integer',
        'casinos' => 'required',
        'resolucion' => 'nullable',
        'resolucion.*.nro_resolucion' => ['required_with:resolucion','regex:/^\d\d\d$/'],
        'resolucion.*.nro_resolucion_anio' => ['required_with:resolucion','regex:/^\d\d$/'],
        'disposiciones' => 'nullable',
        'disposiciones.*.nro_disposicion' => ['required','regex:/^\d\d\d$/'],
        'disposiciones.*.nro_disposicion_anio' => ['required','regex:/^\d\d$/'],
        'notas'  => 'nullable',
        'notas.*.fecha'=>'required|date',
        'notas.*.identificacion'=>'required',
        'notas.*.detalle'=>'required',
        'notas.*.id_tipo_movimiento' => 'nullable|exists:tipo_movimiento,id_tipo_movimiento',
        'notas_asociadas'  => 'nullable',
        'notas_asociadas.*.fecha'=>'required|date',
        'notas_asociadas.*.identificacion'=>'required',
        'notas_asociadas.*.detalle'=>'required',
        'notas_asociadas.*.id_log_movimiento' => 'required | exists:log_movimiento,id_log_movimiento',
        'tablaNotas' => 'nullable|array',
        'tablaNotas.*' => 'required|integer|exists:nota,id_nota'
    ], self::$errores, self::$atributos)->after(function ($validator){

      $expediente=Expediente::find($validator->getData()['id_expediente']);
      if($expediente->nro_exp_interno != $validator->getData()['nro_exp_interno']){ // si cambió checkeo que sea unico
            $exp = Expediente::where('nro_exp_interno','=',$validator->getData()['nro_exp_interno'])->get();
            if($exp->count() > 0){
                $validator->errors()->add('nro_exp_interno', 'Ya existe un expediente con el número de expediente interno indicado.');
            }
      }

    })->validate();

    if(isset($validator))
    {
      if ($validator->fails())
      {
        return [
              'errors' => $v->getMessageBag()->toArray()
          ];
      }
    }

    DB::transaction(function() use ($request){
      $expediente = Expediente::find($request->id_expediente);
      $expediente->nro_exp_org = $request->nro_exp_org;
      $expediente->nro_exp_interno = $request->nro_exp_interno;
      $expediente->nro_exp_control = $request->nro_exp_control;
      $expediente->fecha_iniciacion = $request->fecha_iniciacion;
      $expediente->fecha_pase = $request->fecha_pase;
      $expediente->iniciador = $request->iniciador;
      if(!empty($request->concepto)){
          $expediente->concepto = $request->concepto;
      }
      $expediente->ubicacion_fisica = $request->ubicacion_fisica;
      $expediente->remitente = $request->remitente;
      $expediente->destino = $request->destino;
      $expediente->nro_folios = $request->nro_folios;
      $expediente->tema = $request->tema;
      $expediente->anexo = $request->anexo;
      $expediente->nro_cuerpos = $request->nro_cuerpos;
      $expediente->casinos()->detach();
  
      $expediente->casinos()->sync($request['casinos']);
      $expediente->save();
  
      //tablaNotas contiene todas las notas que existian - o sea con // ID
      {
        $listita = array();
        if(isset($request->tablaNotas)){
          foreach ($request->tablaNotas as $tn) {
            if(ctype_digit($tn)){
              $listita[] = $tn;
            }
          }
        }
        $notas_a_eliminar = Nota::whereNotIn('id_nota',$listita)
              ->where('id_expediente',$expediente->id_expediente)
              ->where('es_disposicion',0)->get();
        foreach($notas_a_eliminar as $nota){
          NotaController::getInstancia()->eliminarNota($nota->id_nota);
        }
      }
  
      //chequeo si recibe notas y movimientos nuevos
  
      if(!empty($request->notas)){
        foreach ($request->notas as $nota){
          if(!$this->existeNota($nota, $expediente->notas)){
            NotaController::getInstancia()->guardarNota($nota,$expediente->id_expediente, $expediente->casinos->first()->id_casino);
          }
        }
      }
  
      //notas para asociar
      if(!empty($request->notas_asociadas)){
        foreach ($request->notas_asociadas as $nota){
          NotaController::getInstancia()->guardarNotaConMovimiento($nota,$expediente->id_expediente,  $expediente->casinos->first()->id_casino);
        }
      }
  
      $disposiciones = $expediente->disposiciones;
      if(!empty($disposiciones)){ //si no estan vacias las disposiciones del expediente actual
        foreach($disposiciones as $disposicion){ //por cada dispósicion del Expediente actual
          if(!$this->existeIdDisposicion($disposicion,$request->dispo_cargadas)){//chequea que exista la disposiciones en el request
            DisposicionController::getInstancia()->eliminarDisposicion($disposicion->id_disposicion); //si no esta en el request la elimina
          }
        }
      }
      
      if(!empty($request->disposiciones)){
        foreach($request->disposiciones as $disposicion){
          if(!$this->existeDisposicion($disposicion,$expediente->disposiciones)
            && !empty($disposicion['nro_disposicion']) && !empty($disposicion['nro_disposicion_anio'])){
            DisposicionController::getInstancia()->guardarDisposicion($disposicion,$expediente->id_expediente);
          }
        }
      }
  
      ResolucionController::getInstancia()->updateResolucion($request->resolucion,$expediente->id_expediente);
    });

    $expediente = Expediente::find($request->id_expediente);

    return ['expediente' => $expediente , 'casinos' => $expediente->casinos];
  }


  public function existeLogMovimiento($log,$movimientos_existentes)
  {
    $result=false;
    for($i = 0;$i<count($movimientos_existentes);$i++){
      if($movimientos_existentes[$i]['id_log_movimiento'] == $log->id_log_movimiento){
         $result = true;
        break;
      }
    }
    return $result;
  }

  public function existeNota($nota, $notas){
    $result=false;
    foreach ($notas as $note) {
      if($nota['fecha'] == $note->fecha
      && $nota['identificacion'] == $note->identificacion ){
         $result = true;
        break;
      }
    }
    return $result;
  }

  /*
    una disposicion y el request de disposiciones
  */
  public function existeIdDisposicion($disp,$disposiciones){
    $result = false;
    for($i = 0;$i<count($disposiciones);$i++){
      if($disp->id_disposicion == $disposiciones[$i]){

        $result = true;
        break;
      }
    }
    return $result;
  }

  public function existeDisposicion($disp,$disposiciones){
    $result = false;
    for($i = 0;$i<count($disposiciones);$i++){
      if($disp['nro_disposicion'] == $disposiciones[$i]['nro_disposicion']
      && $disp['nro_disposicion_anio'] == $disposiciones[$i]['nro_disposicion_anio']
      && $disp['descripcion'] == $disposiciones[$i]['descripcion']){

        $result = true;
        break;
      }
    }
    return $result;
  }

  public function eliminarExpediente($id){
    $expediente = Expediente::find($id);
    //primero chequeo que se pueda eliminar los los LogMovimientos que tenga
    //sino no se puede eliminar el expediente
    $logs=$expediente->log_movimientos;
    if(isset($logs[0])){
      foreach ($expediente->log_movimientos as $log)
      {
        $bool = LogMovimientoController::getInstancia()->eliminarMovimientoExpediente($log->id_log_movimiento);
        if(!$bool){//no se eliminó
          return 0;
        }
      }
    }

    $resoluciones = $expediente->resoluciones;
    if(!empty($resoluciones)){
      foreach($resoluciones as $resolucion){
        ResolucionController::getInstancia()->eliminarResolucion($resolucion->id_resolucion);
      }
    }

    $disposiciones = $expediente->disposiciones;
    if(!empty($disposiciones)){
      foreach($disposiciones as $disposicion){
        DisposicionController::getInstancia()->eliminarDisposicion($disposicion->id_disposicion);
      }
    }
    $notas = $expediente->notas;
    //dd($notas);
    if(!empty($notas)){
      foreach($notas as $nota){
        NotaController::getInstancia()->eliminarNota($nota->id_nota);
      }
    }



    $expediente->maquinas()->detach();
    $expediente->casinos()->detach();
    $expediente = Expediente::destroy($id);
    return ['expediente' => $expediente];
  }

  public function buscarExpedientes(Request $request){
    $reglas = Array();
    if(isset($request->nro_exp_org))
      $reglas[]=['nro_exp_org','like', '%'.$request->nro_exp_org.'%'];
    if(isset($request->nro_exp_interno))
      $reglas[]=['nro_exp_interno', 'like', '%'.$request->nro_exp_interno.'%'];
    if(isset($request->nro_exp_control))
      $reglas[]=['nro_exp_control', '=' , $request->nro_exp_control];

    if(isset($request->ubicacion))
      $reglas[]=['ubicacion', 'like', '%'.$request->ubicacion.'%'];
    if(isset($request->remitente))
      $reglas[]=['remitente', 'like', '%'.$request->remitente.'%'];
    if(isset($request->concepto))
      $reglas[]=['concepto', 'like', '%'.$request->concepto.'%'];
    if(isset($request->tema))
      $reglas[]=['tema', 'like', '%'.$request->tema.'%'];
    if(isset($request->destino))
      $reglas[]=['destino', 'like', '%'.$request->destino.'%'];
    if(isset($request->nota))
      $reglas[]=['nota.identificacion', 'like', '%'.$request->nota.'%'];

      if($request->id_casino==0){
        $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
        $casinos = array();
        foreach($usuario->casinos as $casino){
          $casinos[] = $casino->id_casino;
        }
      }else {
        $casinos[]=$request->id_casino;
      }


      $sort_by = $request->sort_by;
      if($sort_by['columna'] == "expediente.nro_expediente"){
          $string_busqueda = "expediente.nro_exp_org " . $sort_by['orden'] . ",expediente.nro_exp_interno " . $sort_by['orden'];
      }else{
          $string_busqueda =  $sort_by['columna'] ." " . $sort_by['orden'];
      }


      if(!isset($request->fecha_inicio)){
          $resultados=DB::table('expediente')
          ->select('expediente.*','casino.*')
          ->join('expediente_tiene_casino','expediente_tiene_casino.id_expediente','=','expediente.id_expediente')
          ->join('casino', 'expediente_tiene_casino.id_casino', '=', 'casino.id_casino')
          ->leftJoin('nota','nota.id_expediente','=','expediente.id_expediente')
          ->whereIn('casino.id_casino',$casinos)
          ->where($reglas)
          ->where('expediente.concepto','<>','expediente_auxiliar_para_movimientos')
          ->distinct('expediente.id_expediente')
          ->when($sort_by,function($query) use ($string_busqueda){
                          return $query->orderByRaw($string_busqueda);
                      })
          ->paginate($request->page_size);
      }else{
          $fecha=explode("-", $request['fecha_inicio']);
          $resultados=DB::table('expediente')
          ->select('expediente.*','casino.*')
          ->join('expediente_tiene_casino','expediente_tiene_casino.id_expediente','=','expediente.id_expediente')
          ->join('casino', 'expediente_tiene_casino.id_casino', '=', 'casino.id_casino')
          ->leftJoin('nota','nota.id_expediente','=','expediente.id_expediente')
          ->where($reglas)
          ->where('expediente.concepto','<>','expediente_auxiliar_para_movimientos')
          ->whereIn('casino.id_casino',$casinos)
          ->whereYear('fecha_iniciacion' , '=' ,$fecha[0])
          ->distinct('expediente.id_expediente')
          ->whereMonth('fecha_iniciacion','=', $fecha[1])
          ->when($sort_by,function($query) use ($sort_by){
                          return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                      })

          ->paginate($request->page_size);
    }

    return ['expedientes' => $resultados];
  }

  public function getAll(){
    $todos=Expediente::all();
    return $todos;
  }

  public function buscarExpedientePorNumero($busqueda){
    $arreglo=explode("-", $busqueda);
    $reglas=array();

    if(isset($arreglo[0])){
      $reglas[]=['nro_exp_org', 'like' , '%' . $arreglo[0] . '%'];
    }
    if(isset($arreglo[1])){
      $reglas[]=['nro_exp_interno', 'like' , '%' . $arreglo[1] . '%'];
    }
    if(isset($arreglo[2])){
      $reglas[]=['nro_exp_control', 'like' , '%' . $arreglo[2] . '%'];
    }

    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));

    $expedientes=array();
    foreach($usuario['usuario']->casinos as $casino){
      $casinos [] = $casino->id_casino;
    }

    $expedientes= DB::table('expediente')
                      ->select('expediente.*')
                      ->join('expediente_tiene_casino','expediente_tiene_casino.id_expediente','=','expediente.id_expediente')
                      ->join('casino', 'expediente_tiene_casino.id_casino', '=', 'casino.id_casino')
                      ->where('expediente.concepto','<>','expediente_auxiliar_para_movimientos')
                      ->where($reglas)
                      ->whereIn('casino.id_casino' , $casinos)->get();

    $resultado = array();

    foreach ($expedientes as $expediente) {
      $auxiliar =  new \stdClass();
      $auxiliar->id_expediente = $expediente->id_expediente;
      $auxiliar->concatenacion = $expediente->nro_exp_org . '-' . $expediente->nro_exp_interno .'-' . $expediente->nro_exp_control;
      $resultado[] = $auxiliar;
    }

    return ['resultados' => $resultado];
  }

  public function buscarExpedientePorCasinoYNumero($id_casino,$busqueda){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $acceso = $usuario->casinos()->where('usuario_tiene_casino.id_casino',$id_casino)->count();
    if($acceso == 0) return ['resultados' => []];

    $arreglo=explode("-", $busqueda);
    $reglas=array();
    if(isset($arreglo[0])){
      $reglas[]=['expediente.nro_exp_org', 'like' , '%' . $arreglo[0] . '%'];
    }
    if(isset($arreglo[1])){
      $reglas[]=['expediente.nro_exp_interno', 'like' , '%' . $arreglo[1] . '%'];
    }
    if(isset($arreglo[2])){
      $reglas[]=['expediente.nro_exp_control', 'like' , '%' . $arreglo[2] . '%'];
    }

    $expedientes=Casino::find($id_casino)->expedientes()->where($reglas)->get();
    $resultado = [];
    foreach ($expedientes as $expediente) {
      $auxiliar =  new \stdClass();
      $auxiliar->id_expediente = $expediente->id_expediente;
      $auxiliar->concatenacion = $expediente->concatenacion;
      $resultado[] = $auxiliar;
    }
    return ['resultados' => $resultado];
  }
}
