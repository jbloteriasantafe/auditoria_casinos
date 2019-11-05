<?php

namespace App\Http\Controllers\Mesas\Cierres;

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
use App\Mesas\DetalleCierre;
use App\Mesas\EstadoCierre;
use App\Mesas\Ficha;
use App\Mesas\DetalleApertura;
use App\Mesas\CierreApertura;

//busqueda y consulta de cierres
class BCCierreController extends Controller
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

  ];

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware(['tiene_permiso:m_buscar_cierres']);
  }

  public function eliminarCierre($id){
    //VER CONDICIONES PARA QUE SE PUEDA BORRAR UN CIERRE
    $cierre = Cierre::find($id);
    $cierre->delete();
    //return ['cierre' => $cierre];
    //return 1;
    return response()->json(['cierre' => $cierre], 200);
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

    $cierres = DB::table('cierre_mesa')
                  ->join('mesa_de_panio','mesa_de_panio.id_mesa_de_panio','=','cierre_mesa.id_mesa_de_panio')
                  ->join('casino','mesa_de_panio.id_casino','=','casino.id_casino')
                  ->join('moneda','moneda.id_moneda','=','cierre_mesa.id_moneda')
                  ->whereMonth('cierre_mesa.fecha', $date->month)
                  ->whereYear('cierre_mesa.fecha',$date->year)
                  ->whereIn('mesa_de_panio.id_casino',$casinos)
                  ->whereNull('cierre_mesa.deleted_at')
                  ->orderBy('fecha' , 'desc')->first()
                  ->get();

    $estados = EstadoCierre::all();
    $juegos = JuegoMesa::whereIn('id_casino',$casinos)->get();
    $fichas = Ficha::all();

    return  view('CierresAperturas.CierresAperturas', ['cierres' => $cierres,
                             'estado_cierre' => $estados,
                             'juegos' => $juegos,
                             'casinos' => $cas,
                             'fichas' => $fichas,
                             'es_superusuario' => $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->es_superusuario,
                            ]);
  }

  public function getCierre($id){
    $cierre = Cierre::find($id);
    $mesa = Mesa::withTrashed()->find($cierre->id_mesa_de_panio);
    if(!empty($cierre->moneda)){
      $moneda =$cierre->moneda;
    }else{
      $moneda = $cierre->mesa->moneda;
    }

    if(!empty($cierre)){
    if($cierre->created_at == null){
      $fecha_cierre = $cierre->fecha;
    }
    else{
      $fecha_cierre = $cierre->created_at;
    }
    $first = DB::table('ficha as F')
                        ->select('DC.monto_ficha','F.valor_ficha','F.id_ficha',
                                  'DC.id_detalle_cierre')
                        ->leftJoin('detalle_cierre as DC',function ($join) use($id){
                              $join->on('DC.id_ficha','=','F.id_ficha')
                              ->where('DC.id_cierre_mesa','=',$id);
                            })
                        ->join('ficha_tiene_casino','ficha_tiene_casino.id_ficha','=','F.id_ficha')
                        ->where('ficha_tiene_casino.id_casino','=',$cierre->id_casino)
                        ->where('ficha_tiene_casino.deleted_at','>',$fecha_cierre)
                        ->where('ficha_tiene_casino.created_at','<=',$fecha_cierre)
                        ->where('F.id_moneda','=',$moneda->id_moneda)
                        //->where('F.deleted_at','>',$fecha_cierre)
                        ->orderBy('F.valor_ficha','desc');

    $detalles = DB::table('ficha as F')
                        ->select('DC.monto_ficha','F.valor_ficha','F.id_ficha',
                                  'DC.id_detalle_cierre')
                        ->leftJoin('detalle_cierre as DC',function ($join) use($id){
                              $join->on('DC.id_ficha','=','F.id_ficha')
                              ->where('DC.id_cierre_mesa','=',$id);
                            })
                        ->join('ficha_tiene_casino','ficha_tiene_casino.id_ficha','=','F.id_ficha')
                        ->where('ficha_tiene_casino.id_casino','=',$cierre->id_casino)
                        ->whereNull('ficha_tiene_casino.deleted_at')
                        ->where('ficha_tiene_casino.created_at','<=',$fecha_cierre)
                        ->where('F.id_moneda','=',$moneda->id_moneda)
                        ->whereNull('F.deleted_at')
                        ->orderBy('F.valor_ficha','desc')
                        ->union($first)
                        ->orderBy('valor_ficha','desc')
                        ->get();



    $juegoCI = JuegoMesa::withTrashed()->find($mesa->id_juego_mesa);
    //$juegoCI = $cierre->mesa->juego;

    //Apertura asociada
    $conjunto = null;
    $apertura = null;
    $detalleAP = null;
    if(isset($cierre->cierre_apertura)){
      $conjunto = $cierre->cierre_apertura;
      $apertura = $conjunto->apertura;
      $juegoAP = $juego;
      $id_ap=$apertura->id_apertura_mesa;
      $first = DB::table('ficha')
                          ->select('DA.id_detalle_apertura',
                                   'ficha.id_ficha',
                                   'DA.cantidad_ficha',
                                    DB::raw(  'SUM(DA.cantidad_ficha * ficha.valor_ficha) as monto_ficha'),
                                    'ficha.valor_ficha')
                          ->leftJoin('detalle_apertura as DA',function ($join) use($id_ap){
                                $join->on('DA.id_ficha','=','ficha.id_ficha')
                                ->where('DA.id_apertura_mesa','=',$id_ap);
                              })
                          ->join('ficha_tiene_casino','ficha_tiene_casino.id_ficha','=','ficha.id_ficha')
                          ->where('ficha_tiene_casino.id_casino','=',$apertura->id_casino)
                          ->where('ficha_tiene_casino.deleted_at','>',$apertura->fecha)
                          ->where('ficha_tiene_casino.created_at','<=',$apertura->created_at)
                          ->where('ficha.id_moneda','=',$apertura->id_moneda)
                          //->where('ficha.deleted_at','>',$apertura->fecha)
                          ->groupBy('DA.id_detalle_apertura',
                                    'ficha.id_ficha',
                                     'DA.cantidad_ficha',
                                     'ficha.valor_ficha')
                          ->orderBy('ficha.valor_ficha','desc');
      $detalleAP = DB::table('ficha')
                          ->select('DA.id_detalle_apertura',
                                   'ficha.id_ficha',
                                   'DA.cantidad_ficha',
                                    DB::raw(  'SUM(DA.cantidad_ficha * ficha.valor_ficha) as monto_ficha'),
                                    'ficha.valor_ficha')
                          ->leftJoin('detalle_apertura as DA',function ($join) use($id_ap){
                                $join->on('DA.id_ficha','=','ficha.id_ficha')
                                ->where('DA.id_apertura_mesa','=',$id_ap);
                              })
                          ->join('ficha_tiene_casino','ficha_tiene_casino.id_ficha','=','ficha.id_ficha')
                          ->where('ficha_tiene_casino.id_casino','=',$apertura->id_casino)
                          ->whereNull('ficha_tiene_casino.deleted_at')
                          ->where('ficha_tiene_casino.created_at','<=',$apertura->created_at)
                          ->where('ficha.id_moneda','=',$apertura->id_moneda)
                          ->whereNull('ficha.deleted_at')
                          ->groupBy('DA.id_detalle_apertura',
                                    'ficha.id_ficha',
                                     'DA.cantidad_ficha',
                                     'ficha.valor_ficha')
                          ->orderBy('ficha.valor_ficha','desc')
                          ->union($first)
                          ->orderBy('valor_ficha','desc')
                          ->get();
    }
    return response()->json(['cierre' => $cierre,
            'cargador' => $cierre->fiscalizador,
            'casino' => $cierre->casino,
            'mesa' => $mesa,
            'detallesC' => $detalles,//detalles del cierre
            'apertura' => $apertura,
            'detalleAP' => $detalleAP,
            'nombre_juego' => $juegoCI->nombre_juego,
            'moneda' => $moneda,

          ], 200);
    }else{
      return response()->json(['error' => 'Cierre no encontrado.'], 404);
    }
  }

  public function filtros(Request $request)
  {
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = array();

    $filtros = array();
    if(!empty($request->nro_mesa)){
      $filtros[]= ['mesa_de_panio.nro_mesa','like','%'.$request->nro_mesa.'%'];
    }
    if(!empty($request->id_juego)){
      $filtros[]= ['mesa_de_panio.id_juego_mesa','=',$request->id_juego];
    }
    if(!empty($request->id_casino)){
      $cas[] = $request->id_casino;
    }else{
      foreach ($user->casinos as $cass) {
        $cas[]=$cass->id_casino;
      }
    }
    if(!empty( $request->sort_by)){
          $sort_by = $request->sort_by;
        }else{
          $sort_by = ['columna' => 'cierre_mesa.fecha','orden','desc'];
        }
        if(empty($request->fecha)){
          $resultados = DB::table('cierre_mesa')
                                  ->select('cierre_mesa.id_cierre_mesa','cierre_mesa.hora_inicio',
                                            'cierre_mesa.hora_fin','cierre_mesa.fecha',
                                            'casino.nombre','juego_mesa.siglas as nombre_juego',
                                            'moneda.siglas as siglas_moneda','mesa_de_panio.nro_mesa',
                                            'cierre_mesa.id_estado_cierre'
                                          )
                                  ->join('mesa_de_panio','mesa_de_panio.id_mesa_de_panio','=','cierre_mesa.id_mesa_de_panio')
                                  ->join('casino','casino.id_casino','=','mesa_de_panio.id_casino')
                                  ->leftJoin('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
                                  ->leftJoin('moneda','moneda.id_moneda','=','cierre_mesa.id_moneda')
                                  ->leftJoin('cierre_apertura','cierre_mesa.id_cierre_mesa','=','cierre_apertura.id_cierre_mesa')
                                  ->where($filtros)
                                  ->whereIn('cierre_mesa.id_casino',$cas)
                                  ->whereNull('cierre_mesa.deleted_at')
                                  ->when($sort_by,function($query) use ($sort_by){
                                                  return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                              })
                                  ->paginate($request->page_size);
        }else{
          $fecha=explode("-", $request->fecha);
          $resultados = DB::table('cierre_mesa')
                                  ->select('cierre_mesa.id_cierre_mesa','cierre_mesa.hora_inicio',
                                            'cierre_mesa.hora_fin','cierre_mesa.fecha',
                                            'casino.nombre','juego_mesa.siglas as nombre_juego',
                                            'moneda.siglas as siglas_moneda','mesa_de_panio.nro_mesa',
                                            'cierre_mesa.id_estado_cierre'
                                          )
                                  ->join('mesa_de_panio','cierre_mesa.id_mesa_de_panio','=','mesa_de_panio.id_mesa_de_panio')
                                  ->join('casino','casino.id_casino','=','mesa_de_panio.id_casino')
                                  ->leftJoin('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
                                  ->leftJoin('moneda','moneda.id_moneda','=','cierre_mesa.id_moneda')
                                  ->leftJoin('cierre_apertura','cierre_mesa.id_cierre_mesa','=','cierre_apertura.id_cierre_mesa')
                                  ->where($filtros)
                                  ->whereNull('cierre_mesa.deleted_at')
                                  ->whereIn('cierre_mesa.id_casino',$cas)
                                  ->whereYear('cierre_mesa.fecha' , '=', $fecha[0])
                                  ->whereMonth('cierre_mesa.fecha','=', $fecha[1])
                                  ->whereDay('cierre_mesa.fecha','=', $fecha[2])
                                  ->when($sort_by,function($query) use ($sort_by){
                                                  return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                              })
                                  ->paginate($request->page_size);
        }

        return ['cierre' => $resultados];
  }

}
