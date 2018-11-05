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
                  ->join('mesa_de_panio','mesa_de_panio.id_mesa_de_panio','=','apertura_mesa.id_mesa_de_panio')
                  ->join('casino','mesa_de_panio.id_casino','=','casino.id_casino')
                  ->join('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
                  ->whereMonth('apertura_mesa.fecha', $date->month)
                  ->whereYear('apertura_mesa.fecha',$date->year)
                  ->whereIn('mesa_de_panio.id_casino',$casinos)
                  ->orderBy('fecha' , 'DESC')
                  ->get();

    $juegos = JuegoMesa::whereIn('id_casino',$casinos)->with('casino')->get();
    $fichas = Ficha::all();

    return  view('CierresAperturas.CierresAperturas', ['aperturas' => $apertura,
                             'juegos' => $juegos,
                             'casinos' => $cas,
                             'fichas' => $fichas,
                             'es_superusuario' => UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->es_superusuario;
                            ]);
  }

  public function getApertura($id){//agregar nombre juego
    $apertura = Apertura::find($id);
    $c=array();
    $moneda =$apertura->mesa->moneda;
    if(!empty($apertura)){


      if(isset($apertura->cierre_apertura)){
        $conjunto = $apertura->cierre_apertura;
        $cierre = $conjunto->cierre;
        $detalles = DB::table('detalle_apertura')
                        ->select(
                                  'detalle_apertura.id_detalle_apertura',
                                  'detalle_apertura.cantidad_ficha',
                                  'detalle_apertura.id_ficha',
                                  DB::raw(  'SUM(detalle_apertura.cantidad_ficha * ficha.valor_ficha) as monto_ficha_apertura'),
                                  'ficha.valor_ficha',
                                  'ficha.id_ficha',
                                  'detalle_cierre.*'
                                )
                        ->join('ficha','ficha.id_ficha','=','detalle_apertura.id_ficha')
                        ->leftJoin('detalle_cierre','detalle_cierre.id_detalle_cierre','=','detalle_apertura.id_detalle_cierre')
                        ->where('detalle_apertura.id_apertura_mesa','=',$id)
                        ->where('ficha.id_moneda','=',$moneda->id_moneda)
                        ->groupBy('detalle_apertura.id_detalle_apertura','ficha.id_ficha','detalle_cierre.id_detalle_cierre')->get();
        // $detalles = DB::table('detalle_cierre')
        //                 ->select(
        //                           'detalle_apertura.id_detalle_apertura',
        //                           'detalle_apertura.cantidad_ficha',
        //                           'detalle_apertura.id_ficha',
        //                           DB::raw(  'SUM(detalle_apertura.cantidad_ficha * ficha.valor_ficha) as monto_ficha_apertura'),
        //                           'ficha.valor_ficha',
        //                           'ficha.id_ficha',
        //                           'detalle_cierre.*'
        //                         )
        //                 ->leftJoin('ficha','ficha.id_ficha','=','detalle_cierre.id_ficha')
        //                 ->leftJoin('detalle_apertura','detalle_apertura.id_ficha','=','ficha.id_ficha')
        //                 ->where('detalle_apertura.id_apertura_mesa','=',$id)
        //                 ->where('detalle_cierre.id_cierre_mesa','=',$cierre->id_cierre_mesa)
        //                 ->where('ficha.id_moneda','=',$moneda->id_moneda)
        //                 ->union($left)
        //                 ->groupBy('detalle_apertura.id_detalle_apertura','ficha.id_ficha','detalle_cierre.id_detalle_cierre')
        //                 ->get();
        // $detalles = DB::table('ficha')
        //                 ->select(
        //                           'detalle_apertura.id_detalle_apertura',
        //                           'detalle_apertura.cantidad_ficha',
        //                           'detalle_apertura.id_ficha',
        //                           DB::raw(  'SUM(detalle_apertura.cantidad_ficha * ficha.valor_ficha) as monto_ficha_apertura'),
        //                           'ficha.valor_ficha',
        //                           'ficha.id_ficha',
        //                           'detalle_cierre.*'
        //                         )
        //                 ->crossJoin('detalle_cierre')
        //                 ->crossJoin('detalle_apertura')
        //                 ->where('detalle_apertura.id_apertura_mesa','=',$id)
        //                 ->where('detalle_cierre.id_cierre_mesa','=',$cierre->id_cierre_mesa)
        //                 ->where('ficha.id_moneda','=',$moneda->id_moneda)
        //                 ->orWhere('ficha.id_ficha','=','detalle_cierre.id_ficha')
        //                 ->orWhere('detalle_apertura.id_ficha','=','ficha.id_ficha')
        //                 ->distinct('id_detalle_apertura','id_ficha','id_detalle_cierre')
        //                 ->groupBy('detalle_apertura.id_detalle_apertura','ficha.id_ficha','detalle_cierre.id_detalle_cierre')
        //                 ->get();



      }else{
        $cierre = null;
        $detalles = DB::table('ficha')
                        ->select(
                                  'detalle_apertura.id_detalle_apertura',
                                  'detalle_apertura.cantidad_ficha',
                                  DB::raw(  'SUM(detalle_apertura.cantidad_ficha * ficha.valor_ficha) as monto_ficha_apertura'),
                                  'ficha.valor_ficha',
                                  'ficha.id_ficha',
                                  'detalle_apertura.id_detalle_cierre as id_join'
                                )
                        ->leftJoin('detalle_apertura','ficha.id_ficha','=','detalle_apertura.id_ficha')
                        ->where('detalle_apertura.id_apertura_mesa','=',$id)
                        ->where('ficha.id_moneda','=',$moneda->id_moneda)
                        ->groupBy('detalle_apertura.id_detalle_apertura','ficha.id_ficha')
                        ->get();
      }



      return response()->json(['apertura' => $apertura,
                              'cierre' => $cierre,
                              'detalles' => $detalles,
                              'estado' => $apertura->estado,
                              'fiscalizador' => $apertura->fiscalizador,
                              'cargador' => $apertura->cargador,
                              'mesa' => $apertura->mesa,
                              'tipo_mesa' => $apertura->mesa->tipo_mesa,
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
    id_mesa_panio: $('#filtroMesa').val(),
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
      $filtros[]= ['mesa_de_panio.id_juego','=',$request->id_juego];
    }
    if(!empty($request->id_casino) && $request->id_casino != 0){
      $cas[]= $request->id_casino;
    }else{
      foreach ($user->casinos as $cass) {
        $cas[]=$cass->id_casino;
      }
    }

    if(empty($request->fecha)){
      $resultados = DB::table('apertura_mesa')->join('mesa_de_panio','apertura_mesa.id_mesa_de_panio','=','mesa_de_panio.id_mesa_de_panio')
                              ->join('casino','casino.id_casino','=','mesa_de_panio.id_casino')
                              ->leftJoin('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
                              ->where($filtros)
                              ->orderBy('apertura_mesa.fecha','desc')
                              ->take(31)
                              ->get();
    }else{
      $fecha=explode("-", $request->fecha);
      $resultados = DB::table('apertura_mesa')->join('mesa_de_panio','apertura_mesa.id_mesa_de_panio','=','mesa_de_panio.id_mesa_de_panio')
                              ->join('casino','casino.id_casino','=','mesa_de_panio.id_casino')
                              ->leftJoin('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
                              ->where($filtros)
                              ->whereYear('apertura_mesa.fecha' , '=', $fecha[0])
                              ->whereMonth('apertura_mesa.fecha','=', $fecha[1])
                              ->orderBy('apertura_mesa.fecha','desc')
                              ->take(31)
                              ->get();
    }
    return response()->json(['apertura' => $resultados], 200);
  }

  public function buscarIDMesasAperturasDelDia($fecha,$id_casino){
    $ids = DB::table('apertura_mesa')->select('id_mesa_de_panio')
                                ->where('fecha','=',$fecha)
                                ->where('id_casino','=',$id_casino)->get();
    return $ids;
  }
}
