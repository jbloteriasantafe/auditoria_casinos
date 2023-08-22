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
    $relevamiento->cumplio_minimo = 0;
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
      $detalle->id_moneda = $mesa->id_moneda;
      $detalle->multimoneda = $mesa->multimoneda;
      $detalle->relevamiento()->associate($relevamiento->id_relevamiento_apuestas);
      $detalle->save();
    }
    return $relevamiento->id_relevamiento_apuestas;
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
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $validator=  Validator::make($request->all(),[
      'relevamiento.id_relevamiento_apuestas' => 'required|exists:relevamiento_apuestas_mesas,id_relevamiento_apuestas,deleted_at,NULL',
      'relevamiento.hora_ejecucion' => 'required|date_format:"H:i"',
      'relevamiento.observaciones' => 'nullable|string',
      'fiscalizadores' => 'required|array',
      'fiscalizadores.*.id_usuario' => 'required|exists:usuario,id_usuario',
      'detalles' => 'required|array',
      'detalles.*.id_detalle_relevamiento_apuestas' => 'required|exists:detalle_relevamiento_apuestas,id_detalle_relevamiento_apuestas',
      'detalles.*.id_estado_mesa' => 'required|exists:estado_mesa,id_estado_mesa',
      'detalles.*.id_moneda' => 'nullable|exists:moneda,id_moneda|required_if:detalles.*.id_estado_mesa,1',
      'detalles.*.minimo' => 'nullable|integer|min:1|required_with:detalles.*.maximo|required_if:detalles.*.id_estado_mesa,1',
      'detalles.*.maximo' => 'nullable|integer|min:1|required_with:detalles.*.minimo|required_if:detalles.*.id_estado_mesa,1',
    ], [
      'required' => 'El valor es requerido',
      'required_if' => 'El valor es requerido',
      'required_with' => 'El valor es requerido',
      'exists' => 'El valor es invalido',
      'date_format' => 'Formato incorrecto',
      'integer' => 'El valor tiene que ser un numero entero',
      'min' => 'El valor tiene que ser positivo',
    ], self::$atributos)->after(function($validator) use ($user){
      if($validator->errors()->any()) return;
      
      $data = $validator->getData();
      $rel = RelevamientoApuestas::find($data['relevamiento']['id_relevamiento_apuestas']);
      if(!$user->usuarioTieneCasino($rel->id_casino)){
        return $validator->errors()->add('relevamiento.id_relevamiento_apuestas_mesas','No está autorizado para realizar esta accion.');
      }
      
      $ids_detalles = array_map(
        function($d){return $d['id_detalle_relevamiento_apuestas'];},
        $data['detalles']
      );
      
      $mando_todos = $rel->detalles()
      ->whereNotIn('id_detalle_relevamiento_apuestas',$ids_detalles)
      ->count() == 0;
      
      if(!$mando_todos){
        return $validator->errors()->add('detalles','Faltan detalles en el formulario');
      }
      
      foreach($data['fiscalizadores'] as $f){
        if(!Usuario::find($f['id_usuario'])->usuarioTieneCasino($rel->id_casino)){
          return $validator->errors()->add("fiscalizadores.$i.id_usuario",'No está autorizado para realizar esta accion.');
        }
      }
      
      foreach ($data['detalles'] as $i => $fila) {
        if($fila['id_estado_mesa'] == 1 && $fila['maximo'] < $fila['minimo']){
          $validator->errors()->add('detalles.'.$i.'.maximo', 'Es menor que el mínimo');
        }
      }
    })->validate();
    
    return DB::transaction(function() use ($request,$user){
      $rel = RelevamientoApuestas::find($request['relevamiento']['id_relevamiento_apuestas']);
      $rel->observaciones = $request['relevamiento']['observaciones'];
      $rel->hora_ejecucion = $request['relevamiento']['hora_ejecucion'];

      $rel->cargador()->associate($user->id_usuario);
      $rel->estado()->associate(3);//finalizado = carga completa
      $rel->es_backup = 0;
      $rel->save();
      
      $fiscas = array_map(function($f){return $f['id_usuario'];},$request['fiscalizadores']);
      $rel->fiscalizadores()->sync(array_unique($fiscas));
      
      foreach ($request['detalles'] as $det) {
        $detalle = DetalleRelevamientoApuestas::find($det['id_detalle_relevamiento_apuestas']);
        $detalle->minimo = $det['minimo'];
        $detalle->maximo = $det['maximo'];
        $detalle->estado()->associate($det['id_estado_mesa']);
        $detalle->id_moneda = $det['id_moneda'] ?? null;
        $detalle->save();
      }
      
      $this->verificarMinimoApuestas($rel);

      $this->eliminarRelevamientosFecha($rel->fecha,$rel->id_turno,$rel->id_relevamiento_apuestas,$rel->casino);
      return response()->json(['exito' => 'Relevamiento cargado!'], 200);
    });
  }

  // Este metodo antes de hoy estaba MUY MAL, 
  // por lo que si se quiere usar cumplio_minimo
  // hay que usar una query de UPDATE para arreglar los relevamientos viejos
  // Octavio 2023-02-24
  public function verificarMinimoApuestas($relevamiento){
    $minimos = $this->minimosCumplidos($relevamiento->id_relevamiento);
    $cumplio_minimo = 1;
    foreach($minimos as $m){
      if(!is_null($m->requeridas) && ($m->requeridas > $m->cumplieron_minimo)){
        $cumplio_minimo = 0;
        break;
      }
    }
    $relevamiento->cumplio_minimo = $cumplio_minimo;
    $relevamiento->save();
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
  
  public function minimosCumplidos($id_relevamiento){
    return DB::table('relevamiento_apuestas_mesas as r')
    ->selectRaw('
      d.id_juego_mesa,
      d.id_moneda,
      COUNT(distinct d.id_mesa_de_panio) as abiertas, 
      SUM(d.minimo <= IFNULL(ap.apuesta_minima,0)) as cumplieron_minimo,
      MAX(ap.cantidad_requerida) as requeridas'
    )
    ->join('detalle_relevamiento_apuestas as d','d.id_relevamiento_apuestas','=','r.id_relevamiento_apuestas')
    ->leftJoin('apuesta_minima_juego as ap',function($q){
      return $q->on('ap.id_casino','=','r.id_casino')
      ->on('ap.created_at','<=','r.fecha')
      ->on(function($q2){
        return $q2->where('ap.deleted_at','>=','r.fecha')->orWhereNull('ap.deleted_at');
      })
      ->on('ap.id_juego_mesa','=','d.id_juego_mesa')
      ->on('ap.id_moneda','=','d.id_moneda');
    })
    ->where('d.id_estado_mesa','=',1)//mesas abiertas
    ->where('r.id_relevamiento_apuestas','=',$id_relevamiento)
    ->groupBy('d.id_juego_mesa','d.id_moneda')->get();
  }
  
  public function regenerarBackup(Request $request){
    $mañana = date('Y-m-d',strtotime("+ 1 day"));
    $rels = null;
    $validator = Validator::make($request->all(),[
      'id_casino'        => 'required|exists:casino,id_casino,deleted_at,NULL',
      'fecha_generacion' => "required|date|before:$mañana",
      'fecha'            => "required|date|before:$mañana",
    ], [
      'required' => 'El valor es requerido',
      'exists'   => 'El valor es invalido',
      'date'     => 'Tiene que tener formato AAAA-MM-DD',
      'before'   => 'El valor supera el limite',
    ], self::$atributos)->after(function($validator) use (&$rels){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      if($data['fecha_generacion'] > $data['fecha']){
        return $validator->errors()->add('fecha_generacion','El valor supera el limite');
      }
      $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
      
      if(!$user->usuarioTieneCasino($data['id_casino'])){
        return $validator->errors()->add('id_casino','No tiene los privilegios');
      }
      
      $reglas = [
        ['id_casino','=',$data['id_casino']],
        ['fecha','=',$data['fecha']],
      ];
      
      $ya_existen = RelevamientoApuestas::where($reglas)
      ->where('es_backup','=',0)->count() > 0;
      if($ya_existen){
        return $validator->errors()->add('errores_generales','Ya existen relevamientos para esa fecha');
      }
      
      $rels = RelevamientoApuestas::where($reglas)
      ->where('created_at','=',$data['fecha_generacion'])
      ->where('es_backup','=',1)->get();
      if(count($rels) == 0){
        return $validator->errors()->add('errores_generales','No existen relevamientos con esos parametros');
      }
    })->validate();
    
    return DB::transaction(function() use (&$rels){
      foreach($rels as $r){
        $r->es_backup = 0;
        $r->save();
      }
      return 1;
    });
  }
}
