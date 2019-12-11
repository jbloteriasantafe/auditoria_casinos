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
use App\CantidadPersonas;
use App\Turno;
use App\Usuario;
use App\InformeControlAmbiental;
use App\Mesas\Mesa;
use Validator;
use View;
use Dompdf\Dompdf;
use PDF;

class RelevamientoAmbientalMesasController extends Controller
{
  private static $atributos = [];
  private static $instance;

  public function buscarTodo(){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $casinos = $usuario->casinos;
      $estados = EstadoRelevamiento::all();
      $fiscalizadores = $this->obtenerFiscalizadores($casinos,$usuario);
      UsuarioController::getInstancia()->agregarSeccionReciente('Relevamiento Control Ambiental' , 'relevamientosControlAmbiental');

      return view('seccionRelevamientosAmbientalMesas',
      [ 'casinos' => $casinos,
        'estados' => $estados,
        "fiscalizadores" => $fiscalizadores
      ]
      )->render();
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
      ->where('id_tipo_relev_ambiental' , '=', 1)
      ->whereIn('casino.id_casino' , $casinos)
      ->paginate($request->page_size);

    return $resultados;
  }

  public function crearRelevamientoAmbientalMesas(Request $request){
    $usuario_actual = UsuarioController::getInstancia()->quienSoy();
    $fiscalizador = $usuario_actual['usuario'];

    Validator::make($request->all(),[
        'id_casino' => 'required|exists:casino,id_casino',
        'fecha_generacion' => 'required|date|before_or_equal:' . date('Y-m-d H:i:s'),
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    $mesas = DB::table('mesa_de_panio') ->where('id_casino','=',$request->id_casino)
                                        ->where('id_sector_mesas', '!=', NULL)
                                        ->where('deleted_at','=',NULL)
                                        ->orderBy('nro_admin', 'asc')
                                        ->get();

    //creo los detalles
    $detalles = array();
    foreach($mesas as $mesa){
      $detalle = new DetalleRelevamientoAmbiental;
      $detalle->id_mesa_de_panio = $mesa->id_mesa_de_panio;

      $detalles[] = $detalle;
    }


     if(!empty($detalles)){
       //creo y guardo el relevamiento de control ambiental
       DB::transaction(function() use($request,$fiscalizador,$detalles){
         $relevamiento_ambiental = new RelevamientoAmbiental;
         $relevamiento_ambiental->nro_relevamiento_ambiental = DB::table('relevamiento_ambiental')->max('nro_relevamiento_ambiental') + 1;
         $relevamiento_ambiental->fecha_generacion = $request->fecha_generacion;
         $relevamiento_ambiental->id_casino = $request->id_casino;
         $relevamiento_ambiental->id_estado_relevamiento = 1;
         $relevamiento_ambiental->id_tipo_relev_ambiental = 1;
         $relevamiento_ambiental->id_usuario_cargador = $fiscalizador->id_usuario;
         $relevamiento_ambiental->save();

         //guardo los detalles
         foreach($detalles as $detalle){
            $detalle->id_relevamiento_ambiental = DB::table('relevamiento_ambiental')->max('id_relevamiento_ambiental');
            $detalle->save();
         }
       });

      }else{
       return ['codigo' => 500]; //error, no existen mesas para relevar.
     }

    return ['codigo' => 200];
  }

  public function generarPlanillaAmbiental($id_relevamiento_ambiental){
    $rel = RelevamientoAmbiental::find($id_relevamiento_ambiental);

    $dompdf = $this->crearPlanillaAmbiental($rel);

    return $dompdf->stream("Relevamiento_Control_Ambiental_" . $rel->casino->id_casino . "_" . date('Y-m-d') . ".pdf", Array('Attachment'=>0));

  }

  public function crearPlanillaAmbiental($relevamiento_ambiental){
    $detalles = array();
    $hayMesas = array();

    foreach ($relevamiento_ambiental->detalles as $det) {
      $mesa = Mesa::find($det->id_mesa_de_panio);
      $id_sector = $mesa->sector->id_sector_mesas;
      $nombre = $relevamiento_ambiental->casino->id_casino == 2 ? $mesa->nombre . ' ' . $mesa->nro_mesa : $mesa->nombre;

      $detalle = array(
        'id_sector' => $id_sector,
        'nombre' => $nombre,
        'turno1' => $det->turno1,
        'turno2' => $det->turno2,
        'turno3' => $det->turno3,
        'turno4' => $det->turno4,
        'turno5' => $det->turno5,
        'turno6' => $det->turno6,
        'turno7' => $det->turno7,
        'turno8' => $det->turno8
      );

      $detalles[] = $detalle;
    }

    foreach ($relevamiento_ambiental->casino->sectores_mesas as $sector) {
      $mesas_sector = DB::table('mesa_de_panio')  ->where('id_sector_mesas', '=', $sector->id_sector_mesas)
                                                  ->where('deleted_at','=',NULL)
                                                  ->get();

      $hay = sizeof($mesas_sector) >= 1 ? true : false;

      $hayMesa = array(
        'id_sector_mesas' => $sector->id_sector_mesas,
        'hay' => $hay
      );

      $hayMesas[] = $hayMesa;
    }

    $otros_datos = array(
      'casino' => $relevamiento_ambiental->casino->nombre,
      'fiscalizador' => ($relevamiento_ambiental->id_usuario_fiscalizador != NULL) ? (Usuario::find($relevamiento_ambiental->id_usuario_fiscalizador)->nombre) : "",
      'estado' => EstadoRelevamiento::find($relevamiento_ambiental->id_estado_relevamiento)->descripcion,
      'hayMesas' => $hayMesas
    );

    $view = View::make('planillaRelevamientosAmbientalMesas', compact('relevamiento_ambiental', 'detalles', 'otros_datos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 820, $relevamiento_ambiental->nro_relevamiento_ambiental . "/" . $relevamiento_ambiental->casino->codigo, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(765, 575, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf;
  }

  public function validarRelevamiento(Request $request){
    Validator::make($request->all(),[
        'id_relevamiento_ambiental' => 'required|exists:relevamiento_ambiental,id_relevamiento_ambiental',
        'observacion_validacion' => 'nullable|string',
    ], array(), self::$atributos)->after(function($validator){
      $relevamiento_ambiental_mesas = RelevamientoAmbiental::find($validator->getData()['id_relevamiento_ambiental']);
      if($relevamiento_ambiental_mesas->id_estado_relevamiento != 3){
        $validator->errors()->add('error_estado_relevamiento','El relevamiento debe estar finalizado para validar.');
      }
      if(strlen($validator->getData()['observacion_validacion'])>200){
        $validator->errors()->add('error_observacion_validacion', 'La observacion supera los 200 caracteres');
      }
    })->validate();

    DB::transaction(function() use($request){
      $relevamiento_ambiental_mesas = RelevamientoAmbiental::find($request->id_relevamiento_ambiental);
      $relevamiento_ambiental_mesas->observacion_validacion = $request->observacion_validacion;
      $relevamiento_ambiental_mesas->estado_relevamiento()->associate(4);
      $relevamiento_ambiental_mesas->save();

      //como el relevamiento de control ambiental de mesas para la fecha ya esta visado en este punto,
      //si el relevamiento de control ambiental de MTM de la fecha tambien esta visado,
      //entonces es posible generar un informe diario:
      $relevamiento_ambiental_mtm = DB::table('relevamiento_ambiental')
                                      ->where('id_tipo_relev_ambiental', '=', 0) //relevamientos de control ambiental MTM
                                      ->where('id_casino','=',$relevamiento_ambiental_mesas->id_casino) //mismo casino
                                      ->where('id_estado_relevamiento','=', 4) //estado visado
                                      ->where('fecha_generacion','=', $relevamiento_ambiental_mesas->fecha_generacion) //fechas coincidentes
                                      ->get();

      if ($relevamiento_ambiental_mtm->first() != NULL)
        InformeControlAmbientalController::getInstancia()->crearInformeControlAmbiental($relevamiento_ambiental_mtm->first(), $relevamiento_ambiental_mesas);
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

    DB::transaction(function() use ($id_relevamiento_ambiental, $estado) {
      //si se trata de un relevamiento visado (estado = 4), me fijo si hay un informe generado asociado.
      //Si hay, primero elimino ese informe.
      if ($estado == 4) {
        $informe = InformeControlAmbiental::where([['id_relevamiento_ambiental_mesas', $id_relevamiento_ambiental]])->first();

        if (sizeof($informe) == 1) {
          DB::table('informe_control_ambiental')
          ->where('id_informe_control_ambiental', '=', $informe->id_informe_control_ambiental)
          ->delete();
        }
      }

        //elimino todos los detalles asociados al relevamiento de control ambiental
      DB::table('detalle_relevamiento_ambiental')
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
    $casino = $relevamiento->casino;
    $cantidad_turnos = sizeof($casino->turnos);

    foreach ($relevamiento->detalles as $detalle) {
      $mesa = Mesa::find($detalle->id_mesa_de_panio);
      $d = new \stdClass;

      $d->nombre = $relevamiento->id_casino == 2 ? $mesa->nombre . ' ' . $mesa->nro_mesa : $mesa->nombre;
      $d->id_detalle_relevamiento_ambiental = $detalle->id_detalle_relevamiento_ambiental;
      $d->cantidad_turnos = sizeof($casino->turnos);
      $d->turno1 = $detalle->turno1;
      $d->turno2 = $detalle->turno2;
      $d->turno3 = $detalle->turno3;
      $d->turno4 = $detalle->turno4;
      $d->turno5 = $detalle->turno5;
      $d->turno6 = $detalle->turno6;
      $d->turno7 = $detalle->turno7;
      $d->turno8 = $detalle->turno8;

      $detalles[]=$d;
    }

    return ['detalles' => $detalles,
            'relevamiento' => $relevamiento,
            'casino' => $casino,
            'cantidad_turnos' => $cantidad_turnos,
            'usuario_cargador' => $relevamiento->usuario_cargador,
            'usuario_fiscalizador' => $relevamiento->usuario_fiscalizador];
  }

  public function cargarRelevamiento(Request $request,$validar = true){
    if($validar){
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
          'detalles.*.personasTurnos.*.valor'=> 'required|numeric|min:0'
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

    DB::transaction(function() use($request){
      $rel = RelevamientoAmbiental::find($request->id_relevamiento_ambiental);
      $rel->usuario_fiscalizador()->associate($request->id_usuario_fiscalizador);
      $rel->fecha_ejecucion = $request->fecha_ejecucion;
      $rel->estado_relevamiento()->associate(3); // id_estado finalizado
      $rel->observacion_carga = $request->observaciones;
      $rel->save();

      foreach($request->detalles as $detalle) {
        $unDetalle = DetalleRelevamientoAmbiental::find($detalle['id_detalle_relevamiento_ambiental']);

        $unDetalle->turno1 = array_key_exists(0, $detalle['personasTurnos']) ? $detalle['personasTurnos'][0]['valor'] : NULL;
        $unDetalle->turno2 = array_key_exists(1, $detalle['personasTurnos']) ? $detalle['personasTurnos'][1]['valor'] : NULL;
        $unDetalle->turno3 = array_key_exists(2, $detalle['personasTurnos']) ? $detalle['personasTurnos'][2]['valor'] : NULL;
        $unDetalle->turno4 = array_key_exists(3, $detalle['personasTurnos']) ? $detalle['personasTurnos'][3]['valor'] : NULL;
        $unDetalle->turno5 = array_key_exists(4, $detalle['personasTurnos']) ? $detalle['personasTurnos'][4]['valor'] : NULL;
        $unDetalle->turno6 = array_key_exists(5, $detalle['personasTurnos']) ? $detalle['personasTurnos'][5]['valor'] : NULL;
        $unDetalle->turno7 = array_key_exists(6, $detalle['personasTurnos']) ? $detalle['personasTurnos'][6]['valor'] : NULL;
        $unDetalle->turno8 = array_key_exists(7, $detalle['personasTurnos']) ? $detalle['personasTurnos'][7]['valor'] : NULL;
        $unDetalle->save();
      }
    });

    return ['codigo' => 200];
  }

  public function guardarTemporalmenteRelevamiento(Request $request){

    DB::transaction(function() use($request){
      $rel = RelevamientoAmbiental::find($request->id_relevamiento_ambiental);
      $rel->usuario_fiscalizador()->associate($request->id_usuario_fiscalizador);
      $rel->fecha_ejecucion = $request->fecha_ejecucion;
      $rel->estado_relevamiento()->associate(2); // id_estado cargando
      $rel->observacion_carga = $request->observaciones;
      $rel->save();

      foreach($request->detalles as $detalle) {
        $unDetalle = DetalleRelevamientoAmbiental::find($detalle['id_detalle_relevamiento_ambiental']);

        $unDetalle->turno1 = array_key_exists(0, $detalle['personasTurnos']) && $detalle['personasTurnos'][0] != NULL && is_numeric($detalle['personasTurnos'][0]['valor']) ?
          $detalle['personasTurnos'][0]['valor'] : NULL;
        $unDetalle->turno2 = array_key_exists(1, $detalle['personasTurnos']) && $detalle['personasTurnos'][1] != NULL && is_numeric($detalle['personasTurnos'][1]['valor']) ?
          $detalle['personasTurnos'][1]['valor'] : NULL;
        $unDetalle->turno3 = array_key_exists(2, $detalle['personasTurnos']) && $detalle['personasTurnos'][2] != NULL && is_numeric($detalle['personasTurnos'][2]['valor']) ?
          $detalle['personasTurnos'][2]['valor'] : NULL;
        $unDetalle->turno4 = array_key_exists(3, $detalle['personasTurnos']) && $detalle['personasTurnos'][3] != NULL && is_numeric($detalle['personasTurnos'][3]['valor']) ?
          $detalle['personasTurnos'][3]['valor'] : NULL;
        $unDetalle->turno5 = array_key_exists(4, $detalle['personasTurnos']) && $detalle['personasTurnos'][4] != NULL && is_numeric($detalle['personasTurnos'][4]['valor']) ?
          $detalle['personasTurnos'][4]['valor'] : NULL;
        $unDetalle->turno6 = array_key_exists(5, $detalle['personasTurnos']) && $detalle['personasTurnos'][5] != NULL && is_numeric($detalle['personasTurnos'][5]['valor']) ?
          $detalle['personasTurnos'][5]['valor'] : NULL;
        $unDetalle->turno7 = array_key_exists(6, $detalle['personasTurnos']) && $detalle['personasTurnos'][6] != NULL && is_numeric($detalle['personasTurnos'][6]['valor']) ?
          $detalle['personasTurnos'][6]['valor'] : NULL;
        $unDetalle->turno8 = array_key_exists(7, $detalle['personasTurnos']) && $detalle['personasTurnos'][7] != NULL && is_numeric($detalle['personasTurnos'][7]['valor']) ?
          $detalle['personasTurnos'][7]['valor'] : NULL;

        $unDetalle->save();
      }
    });

    return ['codigo' => 200];
  }

  private function obtenerFiscalizadores($casinos,$user){
    $controller = UsuarioController::getInstancia();
    $fiscalizadores = array();

    foreach($casinos as $c){
      $cas = array();
      $fs = $controller->obtenerFiscalizadores($c->id_casino,$user->id_usuario);

      foreach($fs as $f){
        $cas[] = array(
                      'id_usuario' => $f->id_usuario,
                      'nombre' => $f->nombre
                      );
      }
      $fiscalizadores[$c->id_casino] = $cas;
    }

    return $fiscalizadores;
  }
}
