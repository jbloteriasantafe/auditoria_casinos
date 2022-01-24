<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Maquina;
use App\Sector;
use App\Casino;
use App\Isla;
use App\EstadoRelevamiento;
use App\RelevamientoAmbiental;
use App\DetalleRelevamientoAmbiental;
use App\Turno;
use App\Usuario;
use App\DatoGeneralidad;
use App\Clima;
use App\Temperatura;
use App\EventoControlAmbiental;
use Validator;
use View;
use Dompdf\Dompdf;
use PDF;
use App\Http\Controllers\Turnos\TurnosController;

class RelevamientoAmbientalController extends Controller
{
  private static $atributos = [];

  public function buscarTodo(){
      $uc = UsuarioController::getInstancia();
      $usuario = $uc->quienSoy()['usuario'];
      $casinos = $usuario->casinos;

      $fiscalizadores = [];
      foreach($casinos as $c){
        $fiscalizadores[$c->id_casino] = [];
        $fs = $uc->obtenerFiscalizadores($c->id_casino,$usuario->id_usuario);
        foreach($fs as $f){
          $fiscalizadores[$c->id_casino][] = ['id_usuario' => $f->id_usuario,'nombre' => $f->nombre];
        }
      }

      UsuarioController::getInstancia()->agregarSeccionReciente('Relevamiento Control Ambiental' , 'relevamientosControlAmbiental');

      return view('seccionRelevamientosAmbientalMaquinas',[ 
        'casinos' => $casinos,
        'estados' => EstadoRelevamiento::all(),
        'fiscalizadores' => $fiscalizadores
      ])->render();
  }

  public function buscarRelevamientosAmbiental(Request $request){
    $reglas = Array();
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

    foreach ($usuario->casinos as $casino) {
      $casinos[] = $casino->id_casino;
    }

    if(!empty($request->fecha_generacion)){
      $fecha_desde = $request->fecha_generacion . ' 00:00:00';
      $fecha_hasta = $request->fecha_generacion . ' 23:59:59';
      $reglas[]=['relevamiento_ambiental.fecha_generacion','>=',$fecha_desde];
      $reglas[]=['relevamiento_ambiental.fecha_generacion','<=',$fecha_hasta];
    }

    if($request->casino!=0){
      $reglas[]=['casino.id_casino', '=', $request->casino];
    }
    if(!empty($request->estadoRelevamiento)){
      $reglas[] = ['estado_relevamiento.id_estado_relevamiento' , '=' , $request->estadoRelevamiento];
    }

    $sort_by = $request->sort_by;
    $resultados=DB::table('relevamiento_ambiental')
    ->select('relevamiento_ambiental.*'   , 'casino.nombre as casino', 'estado_relevamiento.descripcion as estado')
      ->join('casino' , 'relevamiento_ambiental.id_casino' , '=' , 'casino.id_casino')
      ->join('estado_relevamiento' , 'relevamiento_ambiental.id_estado_relevamiento' , '=' , 'estado_relevamiento.id_estado_relevamiento')
      ->when($sort_by,function($query) use ($sort_by){
                      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                  })
      ->where($reglas)
      ->where('id_tipo_relev_ambiental' , '=', 0)
      ->whereIn('casino.id_casino' , $casinos)
      ->paginate($request->page_size);

    return $resultados;
  }

  public function crearRelevamientoAmbientalMaquinas(Request $request){
    $usuario_actual = UsuarioController::getInstancia()->quienSoy();
    $fiscalizador = $usuario_actual['usuario'];

    Validator::make($request->all(),[
      'id_casino' => 'required|exists:casino,id_casino',
      'fecha_generacion' => 'required|date|before_or_equal:' . date('Y-m-d H:i:s'),
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    $islas_islotes = null;
    if($request->id_casino == 3){
      $islas_islotes = DB::table('isla')
      ->selectRaw('NULL as id_isla, isla.nro_islote')
      ->whereNotNull('nro_islote')
      ->distinct('nro_islote')
      ->orderBy('nro_islote', 'asc');
    }
    else{
      $islas_islotes = DB::table('isla')
      ->selectRaw('isla.id_isla, NULL as nro_islote')
      ->orderBy('nro_isla', 'asc');
    }

    $islas_islotes = $islas_islotes->where('id_casino','=',$request->id_casino)
    ->whereNotNull('id_sector')
    ->whereNull('deleted_at')->get();

    if($islas_islotes->count() == 0) return ['codigo' => 500]; //error, no existen islas o islotes para relevar.

    //creo y guardo el relevamiento de control ambiental
    DB::transaction(function() use($request,$fiscalizador,$islas_islotes){
      $relevamiento_ambiental = new RelevamientoAmbiental;
      $relevamiento_ambiental->nro_relevamiento_ambiental = DB::table('relevamiento_ambiental')->max('nro_relevamiento_ambiental') + 1;
      $relevamiento_ambiental->fecha_generacion = $request->fecha_generacion;
      $relevamiento_ambiental->id_casino = $request->id_casino;
      $relevamiento_ambiental->id_estado_relevamiento = 1;
      $relevamiento_ambiental->id_tipo_relev_ambiental = 0;
      $relevamiento_ambiental->id_usuario_cargador = $fiscalizador->id_usuario;
      $relevamiento_ambiental->save();

      //guardo los detalles
      foreach($islas_islotes as $i){
        $detalle = new DetalleRelevamientoAmbiental;
        $detalle->id_isla    = $i->id_isla;
        $detalle->nro_islote = $i->nro_islote;
        $detalle->id_relevamiento_ambiental = $relevamiento_ambiental->id_relevamiento_ambiental;
        $detalle->save();
      }

      //creo y guardo los detalles de generalidades (dato_generalidad)
      foreach(["clima","temperatura","evento"] as $tipo){
        $dato_generalidad = new DatoGeneralidad;
        $dato_generalidad->tipo_generalidad = $tipo;
        $dato_generalidad->id_relevamiento_ambiental = $relevamiento_ambiental->id_relevamiento_ambiental;
        $dato_generalidad->save();
      }
    });
    
    return ['codigo' => 200];
  }

  public function generarPlanillaAmbiental($id_relevamiento_ambiental){
    $relevamiento_ambiental = RelevamientoAmbiental::find($id_relevamiento_ambiental);

    $detalles = array();
    $generalidades = array();
    foreach ($relevamiento_ambiental->detalles as $det) {
      $isla = null;
      $id_sector = null;
      $nro_isla = null;
      if($relevamiento_ambiental->id_casino == 3){
        $id_sector = DB::table('isla')->select('id_sector')
        ->where('id_casino','=',$relevamiento_ambiental->casino->id_casino)
        ->where('nro_islote','=',$det->nro_islote)
        ->whereNull('deleted_at')->take(1)
        ->get()->first()->id_sector;
      }
      else{
        $isla = Isla::find($det->id_isla);
        $id_sector = $isla->sector->id_sector;
        $nro_isla = $isla->nro_isla;
      }
      $arr = $det->toArray();
      $arr['id_sector'] = $id_sector;
      $arr['nro_isla']  = $nro_isla;
      $detalles[] = $arr;
    }

    $limite_tabla = DetalleRelevamientoAmbiental::limiteCantidadTurnos();
    foreach ($relevamiento_ambiental->generalidades as $generalidad) {
      //Primer caracter capitalizado
      $g = [ 'tipo_generalidad' => ucfirst($generalidad->tipo_generalidad) ];

      for($i=1;$i<=$limite_tabla;$i++){
        $t = $generalidad->{"turno$i"};
        $g["turno$i"] = NULL;
        if(!is_null($t)){
          $g["turno$i"] = $this->obtenerDescripcionGeneralidad($t, $generalidad->tipo_generalidad);
        }
      }
      $generalidades[] = $g;
    }

    $fiscalizador = Usuario::find($relevamiento_ambiental->id_usuario_fiscalizador);
    $turnos = (new TurnosController)->obtenerTurnosActivos($relevamiento_ambiental->id_casino,$relevamiento_ambiental->fecha_generacion);
    if($turnos->count() == 0){//@HACK!!!
      $turnos_truchos = [];
      for($i=1;$i<=4;$i++){//Si no hay turnos en el sistema vuelvo a un defecto de 4... ups!
        $t = new \stdClass;
        $t->nro_turno = $i;
        $turnos_truchos[$i] = $t;
      }
      $turnos = $turnos_truchos;
    }

    $otros_datos = array(
      'casino' => $relevamiento_ambiental->casino->nombre,
      'fiscalizador' => $fiscalizador ? $fiscalizador->nombre : "",
      'estado' => $relevamiento_ambiental->estado_relevamiento->descripcion,
      'turnos' => $turnos,
    );

    $view = View::make('planillaRelevamientosAmbiental', compact('relevamiento_ambiental', 'detalles', 'generalidades', 'otros_datos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'landscape');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("Helvetica", "normal");
    $codigo = $relevamiento_ambiental->nro_relevamiento_ambiental.'/'.$relevamiento_ambiental->casino->codigo.'/Generado:'.$relevamiento_ambiental->fecha_generacion;
    $dompdf->getCanvas()->page_text(20, 575,$codigo,$font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(765, 575, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf->stream("Relevamiento_Control_Ambiental_" . $relevamiento_ambiental->casino->id_casino . "_" . date('Y-m-d') . ".pdf", Array('Attachment'=>0));
  }

  public function cargarRelevamiento(Request $request,$guardadoTemporal = false){
    if(!$guardadoTemporal){
      Validator::make($request->all(),[
          'id_relevamiento_ambiental' => 'required|exists:relevamiento_ambiental,id_relevamiento_ambiental',
          'id_usuario_fiscalizador' => 'required|exists:usuario,id_usuario',
          'id_casino' => 'required|exists:casino,id_casino',
          'fiscalizador' => 'exists:usuario,id_usuario',
          'fecha_ejecucion' => 'required',
          'observaciones' => 'nullable|string',
          'detalles.*' => 'nullable|array',
          'detalles.*.id_detalle_relevamiento_ambiental' => 'required|numeric',
          'detalles.*.personasTurnos' => 'nullable|array',
          'detalles.*.personasTurnos.*' => 'nullable',
          'detalles.*.personasTurnos.*.valor'=> 'required|numeric|min:0',
          'generalidades.*' => 'nullable|array',
          'generalidades.*.id_dato_generalidad' => 'required|numeric',
          'generalidades.*.datos' => 'nullable|array',
          'generalidades.*.datos.*' => 'nullable',
          'generalidades.*.datos.*.valor'=> 'required|numeric|min:1'
      ], array(
        'detalles.*.personasTurnos.*.valor.numeric' => 'El valor de un nivel no es numerico.'
      ), self::$atributos)->after(function($validator){
        $relevamiento = RelevamientoAmbiental::find($validator->getData()['id_relevamiento_ambiental']);
        $controller = UsuarioController::getInstancia();

        if($validator->getData()['fecha_ejecucion'] < $relevamiento->fecha_generacion){
          $validator->errors()->add('error_fecha_ejecucion', 'La fecha de ejecución no puede ser inferior a la fecha de generación del relevamiento');
        }
        if(!$controller->usuarioTieneCasinoCorrespondiente($validator->getData()['id_usuario_fiscalizador'], $validator->getData()['id_casino'])) {
            $validator->errors()->add('error_usuario_tiene_casino','No existe ningún casino asociado al fiscalizador ingresado');
        }
        if(!$controller->usuarioEsFiscalizador($validator->getData()['id_usuario_fiscalizador'])) {
            $validator->errors()->add('error_usuario_es_fiscalizador','El usuario ingresado no es fiscalizador');
        }
        if(strlen($validator->getData()['observaciones'])>200){
          $validator->errors()->add('error_observaciones', 'La observacion supera los 200 caracteres');
        }
      })->validate();
    }

    DB::transaction(function() use($request,$guardadoTemporal){
      $rel = RelevamientoAmbiental::find($request->id_relevamiento_ambiental);
      $rel->usuario_fiscalizador()->associate($request->id_usuario_fiscalizador);
      $rel->fecha_ejecucion = $request->fecha_ejecucion;
      $rel->estado_relevamiento()->associate($guardadoTemporal? 2 : 3); // id_estado finalizado
      $rel->observacion_carga = $request->observaciones;
      $rel->save();

      $limite_tabla = DetalleRelevamientoAmbiental::limiteCantidadTurnos();
      foreach($request->detalles as $detalle) {
        $unDetalle = DetalleRelevamientoAmbiental::find($detalle['id_detalle_relevamiento_ambiental']);
        for($i=1;$i<=$limite_tabla;$i++){
          $unDetalle->{"turno$i"} = NULL;
          //Doble validacion con is_numeric porque con el guardadoTemporal la validación que es un numero se saltea
          if(array_key_exists($i-1,$detalle['personasTurnos']) && is_numeric($detalle['personasTurnos'][$i-1]['valor'])){//$i-1 porque es un arreglo 0-indexado
            $unDetalle->{"turno$i"} = $detalle['personasTurnos'][$i-1]['valor'];
          }
        }
        $unDetalle->save();
      }

      foreach ($request->generalidades as $generalidad) {
        $unDatoGeneralidad = DatoGeneralidad::find($generalidad['id_dato_generalidad']);
        for($i=1;$i<=$limite_tabla;$i++){
          $unDatoGeneralidad->{"turno$i"} = NULL;
          //Idem
          if(array_key_exists($i-1, $generalidad['datos']) && $generalidad['datos'][$i-1]['valor'] != -1){
            $unDatoGeneralidad->{"turno$i"} = $generalidad['datos'][$i-1]['valor'];
          }
        }
        $unDatoGeneralidad->save();
      }
    });

    return ['codigo' => 200];
  }

  public function guardarTemporalmenteRelevamiento(Request $request){
    return $this->cargarRelevamiento($request,true);
  }

  public function validarRelevamiento(Request $request){
    Validator::make($request->all(),[
        'id_relevamiento_ambiental' => 'required|exists:relevamiento_ambiental,id_relevamiento_ambiental',
        'observacion_validacion' => 'nullable|string',
    ], array(), self::$atributos)->after(function($validator){
      $relevamiento_ambiental_mtm = RelevamientoAmbiental::find($validator->getData()['id_relevamiento_ambiental']);
      if($relevamiento_ambiental_mtm->id_estado_relevamiento != 3){
        $validator->errors()->add('error_estado_relevamiento','El relevamiento debe estar finalizado para validar.');
      }
      if(strlen($validator->getData()['observacion_validacion'])>200){
        $validator->errors()->add('error_observacion_validacion', 'La observacion supera los 200 caracteres');
      }
    })->validate();

    DB::transaction(function() use($request){
      $relevamiento_ambiental_mtm = RelevamientoAmbiental::find($request->id_relevamiento_ambiental);
      $relevamiento_ambiental_mtm->observacion_validacion = $request->observacion_validacion;
      $relevamiento_ambiental_mtm->estado_relevamiento()->associate(4);
      $relevamiento_ambiental_mtm->save();
    });

    return ['codigo' => 200];
  }

  public function eliminarRelevamientoAmbiental ($id_relevamiento_ambiental) {
    $usercontroller = UsuarioController::getInstancia();
    $usuario = $usercontroller->quienSoy()['usuario'];
    $relevamiento_ambiental = RelevamientoAmbiental::find($id_relevamiento_ambiental);
    $casino = Casino::find($relevamiento_ambiental->id_casino);
    $estado = $relevamiento_ambiental->id_estado_relevamiento;

    if($usuario === null || $relevamiento_ambiental === null) return;

    if(!$usercontroller->usuarioTieneCasinoCorrespondiente($usuario->id_usuario, $casino->id_casino)) return;

    DB::transaction(function() use ($id_relevamiento_ambiental, $estado){
      //elimino todos los detalles asociados al relevamiento de control ambiental
      DB::table('detalle_relevamiento_ambiental')
      ->where('id_relevamiento_ambiental', '=', $id_relevamiento_ambiental)
      ->delete();

      //elimino todos los datos de generalidades asociados al relevamiento de control ambiental
      DB::table('dato_generalidad')
      ->where('id_relevamiento_ambiental', '=', $id_relevamiento_ambiental)
      ->delete();

      //finalmente, elimino el relevamiento de control ambiental
      DB::table('relevamiento_ambiental')
      ->where('id_relevamiento_ambiental', '=', $id_relevamiento_ambiental)
      ->delete();
    });

    return ['codigo' => 200];
  }

  public function obtenerRelevamiento($id_relevamiento_ambiental) {
    $relevamiento = RelevamientoAmbiental::findOrFail($id_relevamiento_ambiental);
    $detalles = array();
    $generalidades = array();
    $casino = $relevamiento->casino;
    $cantidad_turnos = (new TurnosController)->obtenerTurnosActivos($relevamiento->id_casino,$relevamiento->fecha_generacion)->count();
    $limite_tabla = DetalleRelevamientoAmbiental::limiteCantidadTurnos();
    $cantidad_turnos = min($cantidad_turnos,$limite_tabla);
    if($cantidad_turnos == 0) $cantidad_turnos = $limite_tabla;//@HACK: nose porque a veces de vuelve 0?

    foreach ($relevamiento->detalles as $detalle) {
      $d = new \stdClass;
      $d->nro_isla_o_islote = $relevamiento->casino->id_casino==3 ? $detalle->nro_islote : (Isla::find($detalle->id_isla))->nro_isla;
      $d->id_detalle_relevamiento_ambiental = $detalle->id_detalle_relevamiento_ambiental;
      $d->cantidad_turnos = $cantidad_turnos;//@HACK: esto esta acoplado asi por el front, cambiar el frontend para que use $cantidad_turnos
      for($i=1;$i<=$limite_tabla;$i++) $d->{"turno$i"} = $detalle->{"turno$i"};
      $detalles[] = $d;
    }

    foreach ($relevamiento->generalidades as $generalidad) {
      $g = new \stdClass;
      $g->id_dato_generalidad = $generalidad->id_dato_generalidad;
      $g->tipo_generalidad = $generalidad->tipo_generalidad;
      for($i=1;$i<=$limite_tabla;$i++) $g->{"turno$i"} = $generalidad->{"turno$i"};
      $generalidades[] = $g;
    }

    return ['detalles' => $detalles,
            'generalidades' => $generalidades,
            'relevamiento' => $relevamiento,
            'casino' => $casino,
            'cantidad_turnos' => $cantidad_turnos,
            'usuario_cargador' => $relevamiento->usuario_cargador,
            'usuario_fiscalizador' => $relevamiento->usuario_fiscalizador];
  }

  private function obtenerDescripcionGeneralidad ($id, $tipo_generalidad) {
    if ($tipo_generalidad == 'clima')
      return (Clima::find($id))['descripcion'];
    else if ($tipo_generalidad == 'temperatura')
      return (Temperatura::find($id))['descripcion'];
    else if ($tipo_generalidad == 'evento')
      return (EventoControlAmbiental::find($id))['descripcion'];
    else 
      return '!!!ERROR!!!';
  }
}
