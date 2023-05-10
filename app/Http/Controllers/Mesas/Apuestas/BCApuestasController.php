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
use DateTime;
use Dompdf\Dompdf;

use PDF;
use View;
use App\Usuario;
use App\Casino;
use App\Turno;
use App\SecRecientes;
use App\Http\Controllers\Mesas\Apuestas\GenerarPlanillasController;
use App\Http\Controllers\UsuarioController;
use App\Mesas\Mesa;
use App\Mesas\Moneda;
use App\Mesas\EstadoMesa;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;
use App\Mesas\RelevamientoApuestas;
use App\Mesas\DetalleRelevamientoApuestas;
use App\Mesas\ApuestaMinimaJuego;
use Carbon\Carbon;

use App\Mesas\ComandoEnEspera;
class BCApuestasController extends Controller
{
  private static $atributos = [
    'id_cierre_mesa' => 'Identificacion del Cierre',
    'fecha' => 'Fecha',
    'hora_inicio' => 'Hora de Apertura',
    'hora_fin' => 'Hora del Cierre',
    'total_pesos_fichas_c' => 'Total de pesos en Fichas',
    'total_anticipos_c' => 'Total de Anticipos',
    'id_fiscalizador'=>'Fiscalizador',
    'id_tipo_cierre'=> 'Tipo de Cierre',
    'id_mesa_de_panio'=> 'Mesa de Paño',
    'id_estado_cierre'=>'Estado',
  ];

  private static $cantidad_dias_backup = 5;

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
      $this->middleware(['tiene_permiso:m_consultar_relevamientos_apuestas']);
  }

  public function buscarTodo(){

    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = array();
    $cas = array();
    foreach($user->casinos as $casino){
      $casinos[]=$casino->id_casino;
      $cas[] = $casino;
    }
    $turnos = Turno::whereIn('id_casino',$casinos)->get();
    $monedas = Moneda::all();
    return view('Apuestas.apuestas',['casinos' => $cas,
                                      'turnos' => $turnos,
                                      'monedas' => $monedas,
                                    ]);
  }

  public function filtros(Request $request){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = array();

    $filtros = array();
    if(!empty($request->id_turno) && $request->id_turno != 0){
      $filtros[]= ['RA.id_turno','=',$request->id_turno];
    }

    if(!empty($request->id_casino) && $request->id_casino != 0){
      $cas[]= $request->id_casino;
    }else{
      foreach ($user->casinos as $cass) {
        $cas[]=$cass->id_casino;
      }
    }
    if(!empty( $request->sort_by)){
      $sort_by = $request->sort_by;
    }else{
        $sort_by = ['columna' => 'relevamiento_apuestas.fecha','orden','desc'];
    }

    if(empty($request->fecha)){
      $resultados = DB::table('relevamiento_apuestas_mesas as RA')
                ->select(
                          'RA.id_relevamiento_apuestas',
                          'RA.fecha',
                          'RA.id_casino',
                          'casino.nombre',
                          'turno.nro_turno',
                          'RA.id_estado_relevamiento'
                        )
                ->join('casino','casino.id_casino','=','RA.id_casino')
                ->join('turno','RA.id_turno','=','turno.id_turno')
                ->where($filtros)
                ->whereIn('RA.id_casino',$cas)
                ->where('RA.es_backup','=',0)
                ->distinct('RA.id_relevamiento_apuestas')
                ->orderBy('RA.fecha','desc')
                ->whereNull('RA.deleted_at')
                ->when($sort_by,function($query) use ($sort_by){
                                return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                            })
                ->paginate($request->page_size);
    }else{
      $fecha=explode("-", $request->fecha);
      $resultados = DB::table('relevamiento_apuestas_mesas as RA')
                        ->select(
                                  'RA.id_relevamiento_apuestas',
                                  'RA.fecha',
                                  'RA.id_casino',
                                  'casino.nombre',
                                  'turno.nro_turno',
                                  'RA.id_estado_relevamiento'
                                )
                        ->join('turno','RA.id_turno','=','turno.id_turno')
                        ->join('casino','casino.id_casino','=','RA.id_casino')
                        ->where($filtros)
                        ->whereIn('RA.id_casino',$cas)
                        ->whereNull('RA.deleted_at')
                        ->where('RA.es_backup','=',0)
                        ->whereYear('RA.fecha' , '=', $fecha[0])
                        ->whereMonth('RA.fecha','=', $fecha[1])
                        ->whereDay('RA.fecha','=', $fecha[2])
                        ->orderBy('RA.fecha','desc')
                        ->distinct('RA.id_relevamiento_apuestas')
                        ->when($sort_by,function($query) use ($sort_by){
                                        return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                    })
                        ->paginate($request->page_size);
    }
    return ['apuestas' => $resultados];
  }

  public function obtenerRelevamientoCarga($id_relevamiento){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $estados = EstadoMesa::all();
    $relevamiento = DB::table('relevamiento_apuestas_mesas as RA')
                        ->select('DRA.nombre_juego','DRA.posiciones',
                        'DRA.id_detalle_relevamiento_apuestas',
                        'DRA.codigo_mesa','DRA.nro_mesa','DRA.id_estado_mesa',
                        'DRA.id_moneda','DRA.multimoneda','moneda.descripcion')
                        ->join('detalle_relevamiento_apuestas as DRA',
                               'DRA.id_relevamiento_apuestas','=',
                               'RA.id_relevamiento_apuestas')
                        ->leftJoin('moneda','DRA.id_moneda','=','moneda.id_moneda')
                        ->where('RA.id_relevamiento_apuestas','=',$id_relevamiento)
                        ->orderBy('DRA.nombre_juego','asc')
                        ->groupBy('DRA.nombre_juego',
                        'DRA.id_detalle_relevamiento_apuestas',
                        'DRA.codigo_mesa','DRA.nro_mesa','DRA.posiciones',
                        'DRA.id_estado_mesa','DRA.id_moneda','DRA.multimoneda',
                        'moneda.descripcion'
                        )
                        ->orderBy('nro_mesa','asc')
                        ->get();

      $mesasporjuego = array();
      $mesas = array();
      $nombre_juego_anterior = $relevamiento->first()->nombre_juego;
      foreach ($relevamiento as $detalle) {
        if($nombre_juego_anterior != $detalle->nombre_juego){
            $mesasporjuego[] = [
                                  'juego' => $nombre_juego_anterior,
                                  'mesas' => $mesas,
                                ];
            $mesas = array();
        }
        $mesas[] = [
                      'codigo_mesa' => $detalle->codigo_mesa,
                      'nro_mesa' => $detalle->nro_mesa,
                      'posiciones' => $detalle->posiciones,
                      'id_detalle' => $detalle->id_detalle_relevamiento_apuestas,
                      'id_estado_mesa' => $detalle->id_estado_mesa,
                      'id_moneda' => $detalle->id_moneda,
                      'descripcion' => $detalle->descripcion,
                      'multimoneda' => $detalle->multimoneda,
                    ];


        $nombre_juego_anterior = $detalle->nombre_juego;
      }
      $mesasporjuego[] = [
                            'juego' => $nombre_juego_anterior,
                            'mesas' => $mesas,
                          ];
      $relevamieno = RelevamientoApuestas::find($id_relevamiento);
      return ['mesasporjuego'=> $mesasporjuego,
              'relevamiento' => $relevamieno,
              'estados'=> $estados,
              'fecha' => $relevamieno->fecha,
              'turno' => $relevamieno->turno()->withTrashed()->get()
              ];
  }

  public function obtenerRelevamientoApuesta($id_relevamiento){
    $relevamiento = RelevamientoApuestas::find($id_relevamiento);
    $detalles = array();
    $abiertas = 0;
    foreach ($relevamiento->detalles as $det) {
      $detalles[]= [
                    'detalle' => $det
                    ];

      if($det->id_estado_mesa == 1){
        $abiertas++;
      }
    }
    $estados = EstadoMesa::all();

    $minimo = false;
    if($abiertas > 0){
      $abiertas_por_juego = DB::table('detalle_relevamiento_apuestas as DRA')
                        ->select(
                                  'DRA.nombre_juego',
                                  DB::raw('COUNT(DRA.id_estado_mesa) as cantidad_abiertas')
                                )
                        ->where('DRA.id_estado_mesa','=',1)
                        ->where('DRA.id_relevamiento_apuestas','=',$id_relevamiento)
                        ->groupBy('DRA.nombre_juego',
                                  'DRA.id_estado_mesa')
                        ->orderBy('DRA.nombre_juego','asc')
                        ->get();

    }else{
      $abiertas_por_juego = null;
    }



    return ['relevamiento_apuestas' => $relevamiento,
            'turno' => $relevamiento->turno()->withTrashed()->get(),
            'detalles' => $detalles,
            'estados' => $estados,
            'fiscalizadores' => $relevamiento->fiscalizadores,
            'cargador' => $relevamiento->cargador,
            'total_abiertas' => $abiertas,
            'cumplio_minimo' => $relevamiento->cumplio_minimo,
            'abiertas_por_juego' => $abiertas_por_juego,
           ];
  }

  public function obtenerNombreZip(){
    $fecha_hoy = Carbon::now()->format("Y-m-d");
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = $user->casinos->first();
    $codigo_casino = $cas->codigo;

    $nombreZip = 'Planillas-Apuestas-'.$cas->codigo
              .'-'.$fecha_hoy.'-al-'.strftime("%Y-%m-%d", strtotime("$fecha_hoy +".((self::$cantidad_dias_backup)-1)." day"))
              .'.zip';
     //dd(  public_path().'/Mesas/'.$nombreZip);
    if(file_exists( public_path().'/Mesas/RelevamientosApuestas/'.$nombreZip)){ //C:\xampp\htdocs\agosto\prueba2\blog\
    //if(file_exists( public_path().'\\Mesas\\'.$nombreZip)){ //C:\xampp\htdocs\agosto\prueba2\blog\
      return ['nombre_zip' => $nombreZip];
    }else{
      $enEspera = DB::table('comando_a_ejecutar')
          ->where([['fecha_a_ejecutar','>',Carbon::now()->format('Y:m:d H:i:s')],
                  ['nombre_comando','=','RelevamientoApuestas:generar']
                  ])
          ->get()->count();
      if($enEspera == 0){
        $agrega_comando = new ComandoEnEspera;
        $agrega_comando->nombre_comando = 'RelevamientoApuestas:generar';
        $agrega_comando->fecha_a_ejecutar = Carbon::now()->addMinutes(30)->format('Y:m:d H:i:s');
        $agrega_comando->save();
      }

      return response()->json(['apuesta' => 'Por favor reintente en 15 minutos...'], 404);
    }
  }

  public function descargarZip($nombre){
    //dd($nombre);
    $file = public_path().'/Mesas/RelevamientosApuestas/'. $nombre;
    //$file = public_path().'\\Mesas\\'. $nombre;
    $headers = array('Content-Type' => 'application/octet-stream',);

    return response()->download($file,$nombre,$headers);

  }

  public function buscarRelevamientosBackUp(Request $request){

    $relevamiento = null;
    $validator=  Validator::make($request->all(),[
      'fecha' => 'required|date_format:"Y-m-d"',
      'nro_turno' => 'required|integer',
      'created_at' => 'required|date_format:"Y-m-d"',
    ], array(), self::$atributos)->after(function($validator) use($relevamiento){
      $cas=array();
      $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      foreach ($user->casinos as $cass) {
        $cas[]=$cass->id_casino;
      }

      $fecha = explode('-',$validator->getData()['created_at']);
      $relevamiento = RelevamientoApuestas::whereDay('created_at','=',$fecha[2])
                                            ->whereMonth('created_at','=',$fecha[1])
                                            ->whereYear('created_at','=',$fecha[0])
                                            ->where('id_turno','=',$validator->getData()['nro_turno'])
                                            ->where('fecha','=',$validator->getData()['fecha'])
                                            ->where('es_backup','=','1')
                                            ->whereIn('id_casino',$cas)
                                            ->get();
                                          //  dd($validator->getData()['nro_turno']);
      if(count($relevamiento) != 1){
        $validator->errors()->add('error','No se pudo encontrar un relevamiento.'
                                 );
      }
    })->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
     }

     $cas=array();
     $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
     foreach ($user->casinos as $cass) {
       $cas[]=$cass->id_casino;
     }

     $fecha = explode('-',$request['created_at']);
     $relevamiento = RelevamientoApuestas::whereDay('created_at','=',$fecha[2])
                                           ->whereMonth('created_at','=',$fecha[1])
                                           ->whereYear('created_at','=',$fecha[0])
                                           ->where('id_turno','=',$request['nro_turno'])
                                           ->where('fecha','=',$request['fecha'])
                                           ->where('es_backup','=','1')
                                           ->whereIn('id_casino',$cas)
                                           ->get();
                                          // dd($relevamiento);
     return $this->obtenerRelevamientoCarga($relevamiento->first()->id_relevamiento_apuestas);
  }

  //antes de imprimir la planilla se obliga a que el valor minimo de las apuestas exista.
  public function consultarMinimo(){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $apuestas = ApuestaMinimaJuego::where('id_casino','=',$user->casinos()->first()->id_casino)
                                    ->with('moneda','juego')
                                    ->get()
                                    ->toArray();

    return ['apuestas' => $apuestas];

  }

  public function imprimirPlanilla($id_relevamiento,$vacia = false){
    $relevamiento = RelevamientoApuestas::find($id_relevamiento);
    $rel = new \stdClass();
    //['paginas' => $pagina,'nro_paginas'=>$count_nro_pagina]
    $controller = new GenerarPlanillasController;
    $datos =$controller->obtenerDatosRelevamiento($id_relevamiento);
    $rel->paginas = $datos['paginas'];
    $rel->nro_paginas = $datos['nro_paginas'];
    $rel->totales = $datos['totales'];
    //$rel->totales = ['columna' => null,'totales' => []];//No mostrar rotulos de totales
    $rel->fecha = $relevamiento->created_at;
    $rel->fecha_backup = $relevamiento->fecha;
    $rel->turno = $relevamiento->nro_turno;
    $hora = explode(':',$relevamiento->hora_propuesta);
    $rel->hora_propuesta = $hora[0].':'.$hora[1];

    $rel->observaciones = $relevamiento->observaciones;
    $nombres = [];
    foreach ($relevamiento->fiscalizadores as $f) {
      $nombres[] = $f->nombre;
    }
    $rel->fiscalizador = implode(";",$nombres);
    $rel->hora_ejecucion = $relevamiento->hora_ejecucion;
    if($vacia){
      $this->limpiarRelevamiento($rel);
    }
    $view = View::make('Mesas.Planillas.PlanillaRelevamientoDeApuestas', compact('rel'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'landscape');
    $dompdf->loadHtml($view);
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 565, $relevamiento->casino->codigo."/".$rel->fecha."/T-".$relevamiento->nro_turno, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(750, 565, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf->stream('sorteoAperturas.pdf', Array('Attachment'=>0));
  }
  
  public function imprimirPlanillaVacia($id_relevamiento){
    return $this->imprimirPlanilla($id_relevamiento,true);
  }
  
  private function limpiarRelevamiento(&$rel){
    //Lo hago de esta forma sin indentacion porque sino queda superanidado
    foreach($rel->paginas as &$columnas){
    foreach($columnas as &$juegos){
    if($juegos !== null){
    foreach($juegos as &$j){
    foreach($j['mesas'] as &$m){
      $m['estado'] = '';
      $m['minimo'] = '';
      $m['maximo'] = '';
    }}}}}
    
    foreach($rel->totales as &$t){
      $t['val'] = null;
    }
    
    $rel->observaciones = null;
    $rel->fiscalizador = null;
    $rel->hora_ejecucion = '';
  }
}
