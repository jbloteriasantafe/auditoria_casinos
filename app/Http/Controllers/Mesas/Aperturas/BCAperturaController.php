<?php

namespace App\Http\Controllers\Mesas\Aperturas;

use Auth;
use Session;
use Illuminate\Http\Request;
use Response;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;

use App\Usuario;
use App\Casino;
use App\SecRecientes;

use App\Http\Controllers\UsuarioController;

use App\Mesas\Mesa;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;
use App\Mesas\Cierre;
use App\Mesas\CierreApertura;
use App\Mesas\Moneda;
use App\Mesas\Apertura;
use App\Mesas\DetalleCierre;
use App\Mesas\EstadoCierre;
use App\Mesas\Ficha;
use App\Mesas\DetalleApertura;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

//busqueda y consulta de cierres
class BCAperturaController extends Controller
{
  private static $atributos = [
    'id_cierre_mesa' => 'Identificacion del Cierre',
    'fecha' => 'Fecha',
    'hora_inicio' => 'Hora de Apertura',
    'hora_fin' => 'Hora del Cierre',
    'total_pesos_fichas_c' => 'Total de pesos en Fichas',
    'total_anticipos_c' => 'Total de Anticipos',
    'id_fiscalizador'=>'Fiscalizador',
    'id_mesa_de_panio'=> 'Mesa de Paño',
    'id_estado_cierre'=>'Estado',
    'id_detalle_apertura' => 'Detalles de la Apertura',
    'id_detalle_cierre' => 'Detalle del Cierre'
  ];

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware(['tiene_permiso:m_buscar_aperturas']);
  }

  public function eliminarApertura($id){
    //VER CONDICIONES PARA QUE SE PUEDA BORRAR UN CIERRE
    $apertura = Apertura::find($id);
    $apertura->delete();
    $cya = CierreApertura::where('id_apertura_mesa','=',$apertura->id_apertura_mesa)->get()->first();
    if($cya != null){
      $cya->delete();
      //chequear que el informe diario se actualice.- FALTASs
    }
    //return ['cierre' => $cierre];
    //return 1;
    return response()->json(['apertura' => $apertura], 200);
  }

  public function buscarTodo(){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = array();
    $cas = array();
    foreach($user->casinos as $casino){
      $casinos[]=$casino->id_casino;
      $cas[] = $casino;
    }
    $date = \Carbon\Carbon::today();

    $apertura = DB::table('apertura_mesa')
                  ->select('apertura_mesa.id_apertura_mesa','apertura_mesa.hora',
                            'apertura_mesa.id_estado_cierre','apertura_mesa.fecha',
                            'casino.nombre','juego_mesa.siglas as nombre_juego',
                            'moneda.siglas as siglas_moneda','mesa_de_panio.nro_mesa'
                          )
                  ->join('mesa_de_panio','mesa_de_panio.id_mesa_de_panio','=','apertura_mesa.id_mesa_de_panio')
                  ->join('casino','mesa_de_panio.id_casino','=','casino.id_casino')
                  ->join('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
                  ->leftJoin('moneda','moneda.id_moneda','=','apertura_mesa.id_moneda')
                  ->whereMonth('apertura_mesa.fecha', $date->month)
                  ->whereYear('apertura_mesa.fecha',$date->year)
                  ->whereIn('mesa_de_panio.id_casino',$casinos)
                  ->whereNull('apertura_mesa.deleted_at')
                  ->orderBy('fecha' , 'DESC')
                  ->get();

    $juegos = JuegoMesa::whereIn('id_casino',$casinos)->with('casino')->get();
    $fichas = Ficha::all();
    $monedas = Moneda::all();
    return  view('CierresAperturas.CierresAperturas', ['aperturas' => $apertura,
                             'juegos' => $juegos,
                             'casinos' => $cas,
                             'fichas' => $fichas,
                             'monedas' => $monedas,
                             'es_superusuario' =>$user->es_superusuario
                            ]);
  }

  public function getApertura($id){//agregar nombre juego
    $apertura = Apertura::find($id);
    $c=array();
    if(!empty($apertura->moneda)){
      $moneda =$apertura->moneda;
    }else{
      $moneda = $apertura->mesa->moneda;
    }
    if(!empty($apertura)){
      if(isset($apertura->cierre_apertura)){
        $conjunto = $apertura->cierre_apertura;
        $cierre = $conjunto->cierre;

        //
        // $first = DB::table('apertura_mesa')
        //             ->select(
        //                        'detalle_apertura.id_detalle_apertura',
        //                        'detalle_apertura.cantidad_ficha',
        //                        DB::raw(  'SUM(detalle_apertura.cantidad_ficha * ficha.valor_ficha) as monto_ficha_apertura'),
        //                        'ficha.valor_ficha',
        //                        'detalle_cierre.monto_ficha',
        //                        'detalle_cierre.id_detalle_cierre'
        //               )
        //               ->join('detalle_apertura',function ($join) use($apertura){
        //                     $join->on('detalle_apertura.id_apertura_mesa', '=', 'apertura_mesa.id_apertura_mesa')
        //                     ->where('apertura_mesa.id_apertura_mesa', '=', $apertura->id_apertura_mesa);
        //                   })
        //               ->rightJoin('ficha',function ($join) use($moneda){
        //                     $join->on('ficha.id_ficha', '=', 'detalle_apertura.id_ficha')
        //                     ->where('ficha.id_moneda', '=',$moneda->id_moneda);
        //                   })
        //               ->leftJoin('detalle_cierre','detalle_cierre.id_detalle_cierre' ,'=' ,'detalle_apertura.id_detalle_cierre')
        //               ->groupBy('detalle_apertura.id_detalle_apertura','ficha.id_ficha','detalle_cierre.id_detalle_cierre', 'detalle_apertura.cantidad_ficha','ficha.valor_ficha','detalle_cierre.monto_ficha');
        //
        // $detalles = DB::table('detalle_cierre')//('cierre_mesa')
        //             ->select(
        //                        'detalle_apertura.id_detalle_apertura',
        //                        'detalle_apertura.cantidad_ficha',
        //                        DB::raw('SUM(detalle_apertura.cantidad_ficha * ficha.valor_ficha) as monto_ficha_apertura'),
        //                        'ficha.valor_ficha',
        //                        'detalle_cierre.monto_ficha',
        //                        'detalle_cierre.id_detalle_cierre'
        //               )
        //               // ->join('detalle_cierre',function ($join) use($cierre){
        //               //       $join->on('detalle_cierre.id_cierre_mesa', '=', 'cierre_mesa.id_cierre_mesa')
        //               //       ->where('cierre_mesa.id_cierre_mesa', '=', $cierre->id_cierre_mesa);
        //               //     })
        //
        //               ->join('ficha',function ($join) use($moneda){ //y hacer right join
        //                     $join->on('ficha.id_ficha', '=', 'detalle_cierre.id_ficha')
        //                     ->where('ficha.id_moneda', '=',$moneda->id_moneda);
        //                   })
        //               ->leftJoin('detalle_apertura','detalle_cierre.id_detalle_cierre' ,'=' ,'detalle_apertura.id_detalle_cierre')
        //               ->where('detalle_cierre.id_cierre_mesa', '=', $cierre->id_cierre_mesa) //borrar este si se deja lo comentado
        //               ->groupBy('detalle_apertura.id_detalle_apertura','ficha.id_ficha','detalle_cierre.id_detalle_cierre', 'detalle_apertura.cantidad_ficha','ficha.valor_ficha','detalle_cierre.monto_ficha')
        //               ->union($first)
        //               ->orderBy('valor_ficha','desc')->get();
        //
        //               //aunque así trae duplicados de fichas de $first
        //


      }else{
        $cierre = null;
      }
        $detalles = DB::table('ficha')
                            ->select('DA.id_detalle_apertura',
                                     'ficha.id_ficha',
                                     'DA.cantidad_ficha',
                                      DB::raw(  'SUM(DA.cantidad_ficha * ficha.valor_ficha) as monto_ficha'),
                                      'ficha.valor_ficha')
                            ->leftJoin('detalle_apertura as DA',function ($join) use($id){
                                  $join->on('DA.id_ficha','=','ficha.id_ficha')
                                  ->where('DA.id_apertura_mesa','=',$id);
                                })
                            ->where('ficha.id_moneda','=',$moneda->id_moneda)
                            ->whereNull('ficha.deleted_at')
                            ->groupBy('DA.id_detalle_apertura',
                                      'ficha.id_ficha',
                                       'DA.cantidad_ficha',
                                       'ficha.valor_ficha')
                            ->orderBy('ficha.valor_ficha','desc')
                            ->get();

      return response()->json(['apertura' => $apertura,
                              'cierre' => $cierre,
                              'detalles' => $detalles,
                              'estado' => $apertura->estado,
                              'fiscalizador' => $apertura->fiscalizador,
                              'cargador' => $apertura->cargador,
                              'mesa' => $apertura->mesa,
                              'tipo_mesa' => $apertura->tipo_mesa,
                              'juego' => $apertura->mesa->juego,
                              'casino' => $apertura->casino,
                              'moneda' => $moneda,
                            ], 200);
    }else{
      return response()->json(['error' => 'Apertura no encontrado.'], 404);
    }
  }

  public function obtenerDetallesApCierre($id_apertura,$id_cierre, $id_moneda){
    // if($id_apertura == null || $id_cierre == null || $id_moneda == null){
    //   return response()->json(['error' => 'NULL pointer exception.'], 522);
    // }else{
    //   $first = DB::table('detalle_apertura')
    //               ->select(
    //                 'detalle_apertura.id_detalle_apertura',
    //                          'detalle_apertura.cantidad_ficha',
    //                          DB::raw(  'SUM(detalle_apertura.cantidad_ficha * ficha.valor_ficha) as monto_ficha_apertura'),
    //                          'ficha.valor_ficha',
    //                          'ficha.id_ficha',
    //                          'detalle_cierre.monto_ficha',
    //                          'detalle_cierre.id_detalle_cierre'
    //                 )
    //                 ->join('ficha','ficha.id_ficha', '=', 'detalle_apertura.id_ficha')
    //                 ->leftJoin('detalle_cierre',function ($join) use($id_cierre) {
    //                             $join->on('detalle_cierre.id_ficha' ,'=' ,'ficha.id_ficha')
    //                             ->where('detalle_cierre.id_cierre_mesa', '=', $id_cierre);
    //                           })
    //                 ->where('detalle_apertura.id_apertura_mesa', '=', $id_apertura)
    //                 ->where('ficha.id_moneda', '=',$id_moneda)
    //                 ->groupBy('detalle_apertura.id_detalle_apertura','ficha.id_ficha','detalle_cierre.id_detalle_cierre', 'detalle_apertura.cantidad_ficha','ficha.valor_ficha','detalle_cierre.monto_ficha');
    //   $detalles = DB::table('detalle_cierre')
    //               ->select(
    //                 'detalle_apertura.id_detalle_apertura',
    //                          'detalle_apertura.cantidad_ficha',
    //                          DB::raw(  'SUM(detalle_apertura.cantidad_ficha * ficha.valor_ficha) as monto_ficha_apertura'),
    //                          'ficha.valor_ficha',
    //                          'ficha.id_ficha',
    //                          'detalle_cierre.monto_ficha',
    //                          'detalle_cierre.id_detalle_cierre'
    //                 )
    //                 ->join('ficha','ficha.id_ficha', '=', 'detalle_cierre.id_ficha')
    //                 ->leftJoin('detalle_apertura',function ($join)use($id_apertura) {
    //                             $join->on('ficha.id_ficha' ,'=' ,'detalle_apertura.id_ficha')
    //                             ->where('detalle_apertura.id_apertura_mesa', '=', $id_apertura);
    //                           })
    //                 ->where('detalle_cierre.id_cierre_mesa', '=', $id_cierre)
    //                 ->where('ficha.id_moneda', '=',$id_moneda)
    //                 ->groupBy('detalle_apertura.id_detalle_apertura','ficha.id_ficha','detalle_cierre.id_detalle_cierre', 'detalle_apertura.cantidad_ficha','ficha.valor_ficha','detalle_cierre.monto_ficha')
    //                 ->union($first)
    //                 ->orderBy('valor_ficha','desc')
    //                 ->get();
    //   $cierre = Cierre::find($id_cierre);
    //
    //   return ['detalles_join' => $detalles,
    //           'cierre' => $cierre,
    //           'casino' => $cierre->casino,
    //           'cargador' => $cierre->fiscalizador,
    //           'tipo_mesa'=> $cierre->tipo_mesa,
    //          ];
    if($id_apertura == null || $id_cierre == null || $id_moneda == null){
      return response()->json(['error' => 'NULL pointer exception.'], 522);
    }else{
      $apertura = Apertura::find($id_apertura);
      $cierre = Cierre::find($id_cierre);

      $fichas = Ficha::where('id_moneda','=',$id_moneda)
                      ->where('created_at','<=',$apertura->created_at)
                      ->orderBy('valor_ficha','desc')
                      ->withTrashed()
                      ->get();

      return ['fichas' => $fichas,
              'detalles_apertura' => $apertura->detalles->sortByDesc('ficha_valor'),
              'detalles_cierre' => $cierre->detalles->sortByDesc('ficha_valor'),
              'cierre' => $cierre,
              'casino' => $cierre->casino,
              'cargador' => $cierre->fiscalizador,
              'tipo_mesa'=> $cierre->tipo_mesa,
             ];
    }
  }


  public function obtenerApParaValidar($id){
    $apertura = Apertura::find($id);

    $moneda =$apertura->mesa->moneda;
    if(!empty($apertura)){

        $detalles = DB::table('ficha')
                        ->select(
                                  'detalle_apertura.id_detalle_apertura',
                                  'detalle_apertura.cantidad_ficha',
                                  DB::raw(  'SUM(detalle_apertura.cantidad_ficha * ficha.valor_ficha) as monto_ficha_apertura'),
                                  'ficha.valor_ficha',
                                  'ficha.id_ficha'
                                )
                        ->leftJoin('detalle_apertura','ficha.id_ficha','=','detalle_apertura.id_ficha')
                        ->where('detalle_apertura.id_apertura_mesa','=',$id)
                        ->where('ficha.id_moneda','=',$moneda->id_moneda)
                        ->groupBy('detalle_apertura.id_detalle_apertura','ficha.id_ficha','detalle_apertura.cantidad_ficha','ficha.valor_ficha')
                        ->orderBy('ficha.valor_ficha','desc')
                        ->get();

                        //fechas de los cierres que puede hacer join
        $cierres = DB::table('cierre_mesa')
                          ->select('cierre_mesa.id_cierre_mesa','cierre_mesa.fecha','moneda.siglas','cierre_mesa.hora_inicio','cierre_mesa.hora_fin')
                          ->join('cierre_apertura','cierre_mesa.id_cierre_mesa','=','cierre_apertura.id_cierre_mesa','left outer')
                          ->join('moneda','cierre_mesa.id_moneda','=','moneda.id_moneda')
                          ->where('cierre_mesa.id_mesa_de_panio','=',$apertura->id_mesa_de_panio)
                          ->where('cierre_mesa.fecha','<',$apertura->fecha)
                          ->whereNull('cierre_apertura.id_cierre_apertura')
                          ->whereNull('cierre_mesa.deleted_at')
                          ->orderBy('fecha' , 'DESC')
                          ->take(15)
                          ->get();


      return response()->json(['apertura' => $apertura,
                              'fechas_cierres' => $cierres,
                              'detalles' => $detalles,
                              'estado' => $apertura->estado,
                              'fiscalizador' => $apertura->fiscalizador,
                              'cargador' => $apertura->cargador,
                              'mesa' => $apertura->mesa,
                              'tipo_mesa' => $apertura->tipo_mesa,
                              'juego' => $apertura->mesa->juego,
                              'casino' => $apertura->casino,
                              'moneda' => $moneda,
                            ], 200);
    }else{
      return response()->json(['error' => 'Apertura no encontrado.'], 404);
    }

  }


  /*
  * FORMDATA
  *fecha: $('#B_fecha').val(),
    nro_mesa: $('#filtroMesa').val(),
    id_juego:$('#selectJuego').val(),
    id_casino: $('#selectCas').val(),
  *
  */
  public function filtros(Request $request)
  {
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = array();

    $filtros = array();
    if(!empty($request->nro_mesa)){
      $filtros[]= ['mesa_de_panio.nro_mesa','like','%'.$request->nro_mesa.'%'];
    }
    if(!empty($request->id_juego) && $request->id_juego!= 0){
      $filtros[]= ['mesa_de_panio.id_juego_mesa','=',$request->id_juego];
    }
    if(!empty($request->id_casino) && $request->id_casino != 0){
      $cas[]= $request->id_casino;
    }else{
      foreach ($user->casinos as $cass) {
        $cas[]=$cass->id_casino;
      }
    }
      $sort_by = $request->sort_by;


    if(!empty( $request->sort_by)){
      $sort_by = $request->sort_by;
    }else{

        $sort_by = ['columna' => 'apertura_mesa.fecha','orden','desc'];
    }

    if(empty($request->fecha)){
      $resultados = DB::table('apertura_mesa')
                ->select('apertura_mesa.id_apertura_mesa','apertura_mesa.hora',
                          'apertura_mesa.id_estado_cierre','apertura_mesa.fecha',
                          'casino.nombre','juego_mesa.siglas as nombre_juego',
                          'moneda.siglas as siglas_moneda','mesa_de_panio.nro_mesa'
                        )
                ->join('mesa_de_panio','apertura_mesa.id_mesa_de_panio','=','mesa_de_panio.id_mesa_de_panio')
                ->join('casino','casino.id_casino','=','mesa_de_panio.id_casino')
                ->leftJoin('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
                ->leftJoin('moneda','moneda.id_moneda','=','apertura_mesa.id_moneda')
                ->where($filtros)
                ->whereNull('apertura_mesa.deleted_at')
                ->whereIn('apertura_mesa.id_casino',$cas)
                ->orderBy('apertura_mesa.fecha','desc')
                ->when($sort_by,function($query) use ($sort_by){
                                return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                            })
                ->paginate($request->page_size);
    }else{
      $fecha=explode("-", $request->fecha);
      $resultados = DB::table('apertura_mesa')
                        ->select('apertura_mesa.id_apertura_mesa','apertura_mesa.hora',
                                  'apertura_mesa.id_estado_cierre','apertura_mesa.fecha',
                                  'casino.nombre','juego_mesa.siglas as nombre_juego',
                                  'moneda.siglas as siglas_moneda','mesa_de_panio.nro_mesa'
                                )
                        ->join('mesa_de_panio','apertura_mesa.id_mesa_de_panio','=','mesa_de_panio.id_mesa_de_panio')
                        ->join('casino','casino.id_casino','=','mesa_de_panio.id_casino')
                        ->leftJoin('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
                        ->leftJoin('moneda','moneda.id_moneda','=','apertura_mesa.id_moneda')
                        ->where($filtros)
                        ->whereIn('apertura_mesa.id_casino',$cas)
                        ->whereYear('apertura_mesa.fecha' , '=', $fecha[0])
                        ->whereMonth('apertura_mesa.fecha','=', $fecha[1])
                        ->whereDay('apertura_mesa.fecha','=', $fecha[2])
                        ->whereNull('apertura_mesa.deleted_at')
                        ->orderBy('apertura_mesa.fecha','desc')
                        ->take(31)
                        ->when($sort_by,function($query) use ($sort_by){
                                        return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                    })
                        ->paginate($request->page_size);
    }
    return ['apertura' => $resultados];
  }

  public function buscarIDMesasAperturasDelDia($fecha,$id_casino){
    $ids = DB::table('apertura_mesa')->select('id_mesa_de_panio')
                                ->where('fecha','=',$fecha)
                                ->where('id_casino','=',$id_casino)->get();
    return $ids;
  }
}
