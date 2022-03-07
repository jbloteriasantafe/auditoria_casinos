<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Expediente;
use App\Casino;
use App\LogMovimiento;
use App\TipoMovimiento;
use App\Nota;
use App\Disposicion;
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

  public function guardarOmodificarExpediente(Request $request){
    Validator::make($request->all(), [
      'id_expediente'    => 'nullable|integer|exists:expediente,id_expediente',
      'nro_exp_org'      => ['required','regex:/^\d\d\d\d\d$/'],
      'nro_exp_interno'  => ['required','regex:/^\d\d\d\d\d\d\d$/'],
      'nro_exp_control'  => ['required','regex:/^\d$/'],
      'fecha_iniciacion' => 'nullable|date',
      'iniciador'        => 'nullable|string|max:250',
      'concepto'         => 'required|string|max:1000',
      'ubicacion_fisica' => 'nullable|string|max:250',
      'fecha_pase'       => 'nullable|date',
      'remitente'        => 'nullable|string|max:250',
      'destino'          => 'nullable|string|max:250',
      'nro_folios'       => 'nullable|integer',
      'tema'             => 'nullable|string|max:250',
      'anexo'            => 'nullable|string|max:250',
      'nro_cuerpos'      => 'required|integer',
      'casinos'          => 'required',
      'casinos.*'        => 'required|integer|exists:casino,id_casino',
      //id_tipo_movimiento(??)
      'resolucion'                       => 'nullable|array',
      'resolucion.*.id_resolucion'       => 'nullable|integer|exists:resolucion,id_resolucion',
      'resolucion.*.nro_resolucion'      => ['required','regex:/^\d\d\d$/'],
      'resolucion.*.nro_resolucion_anio' => ['required','regex:/^\d\d$/'],
      'dispo_cargadas'   => 'nullable|array',
      'dispo_cargadas.*' => 'required|integer|exists:disposicion,id_disposicion',
      'disposiciones'                        => 'nullable',
      'disposiciones.*.nro_disposicion'      => ['required','regex:/^\d\d\d$/'],
      'disposiciones.*.nro_disposicion_anio' => ['required','regex:/^\d\d$/'],
      'disposiciones.*.descripcion'          => 'nullable|string|max:1000',
      'disposiciones.*.fecha'                => 'nullable|date',
      'disposiciones.*.id_tipo_movimiento'   => 'nullable|exists:tipo_movimiento,id_tipo_movimiento',
      'tablaNotas'   => 'nullable|array',//Unificar esto con notas??
      'tablaNotas.*' => 'required|integer|exists:nota,id_nota',
      'notas'                      => 'nullable',
      'notas.*.fecha'              => 'required|date',
      'notas.*.identificacion'     => 'required|string|max:50',
      'notas.*.detalle'            => 'required|string|max:500',
      'notas.*.id_tipo_movimiento' => 'nullable|integer|exists:tipo_movimiento,id_tipo_movimiento',
      'notas_asociadas'                     => 'nullable',
      'notas_asociadas.*.fecha'             => 'required|date',
      'notas_asociadas.*.identificacion'    => 'required|string|max:50',
      'notas_asociadas.*.detalle'           => 'required|string|max:500',
      'notas_asociadas.*.id_log_movimiento' => 'required|integer|exists:log_movimiento,id_log_movimiento',
    ],
    self::$errores, self::$atributos)->after(function ($validator){
      if($validator->errors()->any()) return;
      //@TODO: validar que el id_log_movimiento le pertenezca a algun casino que mando
      $data = $validator->getData();
      $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
      foreach($data['casinos'] as $c){
        if(!$user->usuarioTieneCasino($c)){
          $validator->errors()->add('casinos',self::$errores['privilegios']);
          return;
        }
      }
      $exp = Expediente::where([
        ['nro_exp_org','=',$data['nro_exp_org']],['nro_exp_interno','=',$data['nro_exp_interno']],
        ['nro_exp_control','=',$data['nro_exp_control']],['nro_cuerpos','=',$data['nro_cuerpos']]
      ]);
      if(!is_null($data['id_expediente'])){
        $exp = $exp->where('id_expediente','<>',$data['id_expediente']);
      }
      if($exp->get()->count() > 0){
        $validator->errors()->add('nro_cuerpos', 'Ya existe el expediente indicado');
      }
    })->validate();

    DB::transaction(function() use ($request){
      $expediente = null;
      if(!empty($request->id_expediente)){
        $expediente = Expediente::find($request->id_expediente);
      }
      else{
        $expediente = new Expediente;
      }

      $expediente->nro_exp_org      = $request->nro_exp_org;
      $expediente->nro_exp_interno  = $request->nro_exp_interno;
      $expediente->nro_exp_control  = $request->nro_exp_control;
      $expediente->fecha_iniciacion = $request->fecha_iniciacion;
      $expediente->iniciador        = $request->iniciador;
      $expediente->concepto         = $request->concepto;
      $expediente->ubicacion_fisica = $request->ubicacion_fisica;
      $expediente->fecha_pase       = $request->fecha_pase;
      $expediente->remitente        = $request->remitente;
      $expediente->destino          = $request->destino;
      $expediente->nro_folios       = $request->nro_folios;
      $expediente->tema             = $request->tema;
      $expediente->anexo            = $request->anexo;
      $expediente->nro_cuerpos      = $request->nro_cuerpos;
      $expediente->save();

      $expediente->casinos()->sync($request->casinos ?? []);
      {//@TODO: simplificar esto... se puede inlinear la mayoria creo porque solo se llama aca
        $resoluciones = $request->resolucion ?? [];
        ResolucionController::getInstancia()->updateResolucion($resoluciones,$expediente->id_expediente);
      }
      {
        $dispo_cargadas = $request->dispo_cargadas ?? [];
        $a_borrar = Disposicion::where('id_expediente',$expediente->id_expediente)
        ->whereNotIn('id_disposicion',$dispo_cargadas)->get();
        foreach($a_borrar as $d){
          DisposicionController::getInstancia()->eliminarDisposicion($d->id_disposicion);
        }
        $disposiciones = $request->disposiciones ?? [];
        $exp_dispos = $expediente->disposiciones;
        foreach($disposiciones as $d){
          if(!$this->existeDisposicion($d,$exp_dispos)){
            DisposicionController::getInstancia()->guardarDisposicion($d,$expediente->id_expediente);
          }
        }
      }
      {
        $notas_cargadas = $request->tablaNotas ?? [];
        $a_borrar = Nota::where('id_expediente',$expediente->id_expediente)
        ->where('es_disposicion',0)->whereNotIn('id_nota',$notas_cargadas)->get();
        foreach($a_borrar as $n){
          NotaController::getInstancia()->eliminarNota($n->id_nota);
        }

        $notas = $request->notas ?? [];
        $exp_notas = $expediente->notas;
        $id_casino = $expediente->casinos->first()->id_casino;
        foreach ($notas as $n){
          if(!$this->existeNota($n, $exp_notas)){
            NotaController::getInstancia()->guardarNota($n,$expediente->id_expediente,$id_casino);
          }
        }
    
        $notas_asociadas = $request->notas_asociadas ?? [];
        foreach ($notas_asociadas as $n){
          if(!$this->existeNota($n, $exp_notas)){
            NotaController::getInstancia()->guardarNotaConMovimiento($n,$expediente->id_expediente,$id_casino);
          }
        }
      }
    });
    return 1;
  }
  private function existeNota($nota, $notas){
    foreach ($notas as $n) {
      $fecha = $nota['fecha'] == $n->fecha;
      $ident = $nota['identificacion'] == $n->identificacion;
      $id_tipo_mov = $nota['id_tipo_movimiento'] ?? null;
      $mov   = $id_tipo_mov == $n->id_tipo_movimiento;
      if($fecha && $ident && $mov){
        return true;
      }
    }
    return false;
  }

  private function existeDisposicion($disp,$disposiciones){
    foreach($disposiciones as $d){
      if($disp['nro_disposicion']      == $d['nro_disposicion']
      && $disp['nro_disposicion_anio'] == $d['nro_disposicion_anio']
      && $disp['descripcion']          == $d['descripcion']){
        return true;
      }
    }
    return false;
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
