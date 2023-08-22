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
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $casinos = $usuario->casinos;
    $turnos  = Turno::whereIn('id_casino',$casinos->pluck('id_casino')->toArray())->get();
    $monedas = Moneda::all();
    $estados_mesa = EstadoMesa::all();
    return view('Apuestas.apuestas',compact('usuario','casinos','turnos','monedas','estados_mesa'));
  }

  public function filtros(Request $request){
    $filtros = [];
    if(!empty($request->id_turno) && $request->id_turno != 0){
      $filtros[]= ['RA.id_turno','=',$request->id_turno];
    }
    if(!empty($request->id_casino)){
      $filtros[]= ['RA.id_casino','=',$request->id_casino];
    }
    if(!empty($request->fecha)){
      $filtros[]= [DB::raw('DATE(RA.fecha)'),'=',$request->fecha];
    }
    
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = $user->casinos->pluck('id_casino')->toArray();
        
    $sort_by = ['columna' => 'RA.fecha','orden' => 'desc'];
    if(!empty($request->sort_by)){
      $sort_by = $request->sort_by;
    }

    return DB::table('relevamiento_apuestas_mesas as RA')
    ->select(
      'RA.id_relevamiento_apuestas','RA.fecha','RA.id_casino',
      'casino.nombre','turno.nro_turno','RA.id_estado_relevamiento'
    )
    ->join('casino','casino.id_casino','=','RA.id_casino')
    ->join('turno','RA.id_turno','=','turno.id_turno')
    ->where($filtros)
    ->whereIn('RA.id_casino',$casinos)
    ->where('RA.es_backup','=',0)
    ->whereNull('RA.deleted_at')
    ->orderBy($sort_by['columna'] ?? 'RA.fecha',$sort_by['orden'] ?? 'desc')
    ->paginate($request->page_size);
  }
  
  public function obtenerRelevamiento($id_relevamiento){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $relevamiento = RelevamientoApuestas::find($id_relevamiento);
    if(is_null($relevamiento) || !$user->usuarioTieneCasino($relevamiento->id_casino)){
      return null;
    }
    
    $detalles = $relevamiento->detalles()
    ->orderBy('nombre_juego','asc')
    ->orderBy('nro_mesa','asc')
    ->get();
    
    $idx = 0;
    $abiertas_por_juego = $detalles
    ->sortBy('nombre_juego')
    ->groupBy('nombre_juego')
    ->mapWithKeys(function($mesas,$juego) use (&$idx){
      $mesas_abiertas = $mesas->filter(
        function($d){return $d->id_estado_mesa == 1;}
      )->count();
      
      return [
        ($idx++) => compact('juego','mesas_abiertas')
      ];
    });
    
    $abiertas_por_juego->push([
      'juego' => '—TOTAL—',
      'mesas_abiertas' => $abiertas_por_juego->reduce(function($carry,$ab){
        return $carry+$ab['mesas_abiertas'];
      },0)
    ]);
    
    return [
      'relevamiento' => $relevamiento,
      'detalles'=> $detalles,
      'turno' => $relevamiento->turno()->withTrashed()->first(),
      'fiscalizadores' => $relevamiento->fiscalizadores()->withTrashed()->get(),
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
