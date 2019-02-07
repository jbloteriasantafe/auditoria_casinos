<?php

namespace App\Http\Controllers\Mesas\Apuestas;

use Auth;
use Session;
use Illuminate\Http\Request;
use Response;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

use App\Usuario;
use App\Casino;
use App\Turno;
use App\SecRecientes;

use App\Mesas\Mesa;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;
use App\Mesas\RelevamientoApuestas;
use App\Mesas\DetalleRelevamientoApuestas;
use App\Mesas\ApuestaMinimaJuego;
use DateTime;
use Dompdf\Dompdf;
use App\Http\Controllers\UsuarioController;
use PDF;
use View;

class ABMApuestasController extends Controller
{
  private static $atributos = [
    'detalles.*.id_estado_mesa' => 'ESTADO',
    'fecha' => 'FECHA',
    'hora' => 'HORA',
    'detalles.*.minimo' => 'MINIMO',
    'detalles.*.maximo' => 'MAXIMO',
    'id_relevamiento_apuestas' => 'RELEVAMIENTO',
    'detalles.*.id_detalle' => 'FILA',
  ];

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
      $this->middleware(['tiene_permiso:m_abm_relevamiento_apuestas']);
  }

  public function crearRelevamientoApuestas(Casino $casino, Turno $turno, $fecha){

    $relevamiento = new RelevamientoApuestas;
    $relevamiento->fecha = $fecha;
    $relevamiento->casino()->associate($casino->id_casino);
    $relevamiento->hora_propuesta = $turno->hora_propuesta;
    $relevamiento->turno()->associate($turno->id_turno);
    $relevamiento->nro_turno = $turno->nro_turno;
    $relevamiento->estado()->associate(1);//generado
    if($fecha != date('Y-m-d')){
      $relevamiento->es_backup = 1;
    }else {
      $relevamiento->es_backup = 0;
    }
    $relevamiento->created_at = date('Y-m-d');
    $relevamiento->save();

    $mesas  = $casino->mesas;
    foreach ($mesas as $mesa){
      $detalle = new DetalleRelevamientoApuestas;
      $detalle->mesa()->associate($mesa->id_mesa_de_panio);
      $detalle->tipo_mesa()->associate($mesa->juego->id_tipo_mesa);
      $detalle->nro_mesa = $mesa->nro_mesa;
      $detalle->codigo_mesa = $mesa->codigo_mesa;
      $detalle->juego()->associate($mesa->id_juego_mesa);
      $detalle->nombre_juego = $mesa->juego->nombre_juego;
      $detalle->posiciones = $mesa->juego->posiciones;
      $detalle->estado()->associate(2);//cerrada
      $detalle->relevamiento()->associate($relevamiento->id_relevamiento_apuestas);
      $detalle->save();
    }
    return $relevamiento->id_relevamiento_apuestas;
    //return $this->generarPlanilla($relevamiento->id_relevamiento_apuestas)->stream('sorteoAperturas.pdf', Array('Attachment'=>0));
  //  return $this->generarPlanilla(35)->stream('sorteoAperturas.pdf', Array('Attachment'=>0));

  }


  private function generarPlanilla($id_relevamiento){
    $relevamiento = RelevamientoApuestas::find($id_relevamiento);

    $rel = new \stdClass();
    //['paginas' => $pagina,'nro_paginas'=>$count_nro_pagina]
    $datos =$this->obtenerDatosRelevamiento($id_relevamiento);
    $rel->paginas = $datos['paginas'];
    $rel->nro_paginas = $datos['nro_paginas'];
    $rel->fecha = $relevamiento->fecha;
    $rel->turno = $relevamiento->turno->nro_turno;
    $hora = explode(':',$relevamiento->hora_propuesta);
    $rel->hora_propuesta = $hora[0].':'.$hora[1];


    // dd($rel);

    $view = View::make('Mesas.Planillas.PlanillaRelevamientoDeApuestas', compact('rel'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'landscape');
    $dompdf->loadHtml($view);
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $relevamiento->casino->codigo."/".$rel->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf;
  }

  private function obtenerDatosRelevamiento($id_relevamiento){

        $relevamiento = DB::table('relevamiento_apuestas_mesas as RA')
                            ->select('DRA.nombre_juego','DRA.posiciones',
                            'DRA.id_detalle_relevamiento_apuestas',
                            'DRA.codigo_mesa','DRA.nro_mesa')
                            ->join('detalle_relevamiento_apuestas as DRA',
                                   'DRA.id_relevamiento_apuestas','=',
                                   'RA.id_relevamiento_apuestas')
                            ->where('RA.id_relevamiento_apuestas','=',$id_relevamiento)
                            ->orderBy('DRA.nombre_juego','asc')
                            ->groupBy('DRA.nombre_juego',
                            'DRA.id_detalle_relevamiento_apuestas',
                            'DRA.codigo_mesa','DRA.nro_mesa','DRA.posiciones'
                            )
                            ->orderBy('nro_mesa','asc')
                            ->get();

        $mesasporjuego = array();
        $mesas = array();
        $pagina = array();
        $columna = array();
        $izquierda = null;
        $derecha = null;
        $cantidadfilas = 20;
        $aux = 0;
        $aux2 = 0;
        $nombre_juego_anterior = $relevamiento->first()->nombre_juego;
        $count_nro_pagina = 1;
        foreach ($relevamiento as $detalle) {
          //chequeo si tengo  que crear otro conjunto
          if($nombre_juego_anterior != $detalle->nombre_juego || $aux == $cantidadfilas){
              $mesasporjuego[] = [
                                    'juego' => $nombre_juego_anterior,
                                    'mesas' => $mesas,
                                    'filas' => count($mesas),
                                  ];
              $mesas = array();

          }

          //chequeo que la columna $izquierda este vacia y aux = $cantidadfilas

          if($izquierda == null && $aux == $cantidadfilas){
            $izquierda = $mesasporjuego;
            $mesasporjuego = array();
            $aux = 0;
          }
          if($izquierda != null && $derecha == null && $aux2 == (2 * $cantidadfilas)){
            $derecha = $mesasporjuego;
            $mesasporjuego = array();

            $pagina[] = [
                          'izquierda' => $izquierda,
                          'derecha' => $derecha,
                          'count_nro_pagina' => $count_nro_pagina,
                        ];
            $izquierda = null;
            $derecha = null;
            $count_nro_pagina++;
            $aux = 0;
            $aux2 = 0;
          }

          $mesas[] = [
                        'codigo_mesa' => $detalle->codigo_mesa,
                        'nro_mesa' => $detalle->nro_mesa,
                        'posiciones' => $detalle->posiciones,

                      ];

          $aux++;
          $aux2++;
          $nombre_juego_anterior = $detalle->nombre_juego;
        }
        $mesasporjuego[] = [
                              'juego' => $nombre_juego_anterior,
                              'mesas' => $mesas,
                              'filas' => count($mesas),
                            ];
        if($izquierda == null && $aux <= $cantidadfilas){
          $izquierda = $mesasporjuego;
          $mesasporjuego = array();
          $pagina[] = [
                        'izquierda' => $izquierda,
                        'derecha' => $derecha,
                        'count_nro_pagina' => $count_nro_pagina,
                      ];
        }
        if($izquierda != null && $derecha == null && $aux2 > ($cantidadfilas)){
          $derecha = $mesasporjuego;
          $mesasporjuego = array();

          $pagina[] = [
                        'izquierda' => $izquierda,
                        'derecha' => $derecha,
                        'count_nro_pagina' => $count_nro_pagina,
                      ];
          $izquierda = null;
          $derecha = null;
        }



        return ['paginas' => $pagina,'nro_paginas'=>$count_nro_pagina];
  }



  public function cargarRelevamiento(Request $request){
    $validator=  Validator::make($request->all(),[
      'hora' => 'required|date_format:"H:i"',
      'observaciones' => 'nullable',
      'fiscalizadores' => 'required',
      //'fiscalizadores.*.id_fiscalizador' => 'required|exists:users,id',
      'detalles' => 'required',
      'detalles.*.id_detalle' => 'required|exists:detalle_relevamiento_apuestas,id_detalle_relevamiento_apuestas',
      'detalles.*.minimo' => ['required_if:detalles.*.id_estado_mesa,1',
                              'regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
      'detalles.*.maximo' => ['required_if:detalles.*.id_estado_mesa,1',
                              'regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
      'detalles.*.id_estado_mesa' => 'required|exists:estado_mesa,id_estado_mesa',
    ], array(), self::$atributos)->after(function($validator){

    })->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
     }

    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $detalle = DetalleRelevamientoApuestas::find($request['detalles'][0]['id_detalle']);
    if($user->usuarioTieneCasino($detalle->relevamiento->id_casino)){
      foreach ($request['detalles'] as $det) {
        $detalle = DetalleRelevamientoApuestas::find($det['id_detalle']);
        $detalle->minimo = $det['minimo'];
        $detalle->maximo = $det['maximo'];
        $detalle->estado()->associate($det['id_estado_mesa']);
        $detalle->save();
      }
      $relevamiento = $detalle->relevamiento;
      $relevamiento->observaciones = $request->observaciones;
      $relevamiento->hora_ejecucion = $request->hora;

      $relevamiento->cargador()->associate($user->id);
      $relevamiento->estado()->associate(3);//finalizado = carga completa
      $relevamiento->es_backup = 0;
      $relevamiento->save();
      $fiscas = array_unique($request->fiscalizadores);
      if(!empty($fiscas) && count($relevamiento->fiscalizadores) > 0){
        $relevamiento->fiscalizadores()->sync($fiscas);
      }else{
        $relevamiento->fiscalizadores()->detach();
        $relevamiento->fiscalizadores()->sync($fiscas);
      }

      $this->verificarMinimoApuestas($relevamiento);

      $this->eliminarRelevamientosFecha($relevamiento->fecha,$relevamiento->id_turno,$relevamiento->id_relevamiento_apuestas,$relevamiento->casino);
      return response()->json(['exito' => 'Relevamiento cargado!'], 200);
    }else{
      return ['errors' => ['autorizacion' => 'No está autorizado para realizar esta accion.']];
    }
  }

  public function verificarMinimoApuestas($relevamiento){
    $minimos = ApuestaMinimaJuego::where('id_casino','=',$relevamiento->id_casino)
                                  ->get();
    foreach($minimos as $minimo){
      $detalles_relevamiento = DetalleRelevamientoApuestas::where('id_juego_mesa','=',$minimo->id_juego_mesa)
                                                            //->where('id_moneda','=',$minimo->id_moneda)
                                                            ->where('minimo','=',$minimo->apuesta_minima)
                                                            ->get();
      if(count($detalles_relevamiento) >= $minimo->cantidad_requerida){
        $relevamiento->cumplio_minimo = 1;
      }else{
        $relevamiento->cumplio_minimo = 0;
      }

    }
  }



  public function eliminarRelevamientosFecha($fecha,$turno,$id_relevamiento_apuestas,$casino){
    $relevamientos = RelevamientoApuestas::where([['id_turno','=',$turno],
                                                  ['id_casino','=',$casino->id_casino],
                                                  ['fecha','=',$fecha]
                                                ])
                                                ->whereNotIn('id_relevamiento_apuestas',[$id_relevamiento_apuestas])
                                                ->get();
                                            //dd($relevamientos);
    foreach ($relevamientos as $rel) {
      foreach ($rel->detalles as $det) {
        $det->relevamiento()->dissociate();
        $det->mesa()->dissociate();
        $det->estado()->dissociate();
        $det->tipo_mesa()->dissociate();
        $det->juego()->dissociate();
        $det->delete();
      }
      $rel->casino()->dissociate();
      $rel->turno()->dissociate();
      $rel->estado()->dissociate();
      $rel->delete();
    }
  }

}
