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
    return response()->json(['cierre' => $cierre], 200);
  }

  public function getCierre($id){
    $cierre = Cierre::find($id);
    if(empty($cierre)){
      return response()->json(['error' => 'Cierre no encontrado.'], 404);
    }

    $mesa = Mesa::withTrashed()->find($cierre->id_mesa_de_panio);
    $moneda = $cierre->moneda;
    if(empty($moneda)){
      $moneda = $cierre->mesa->moneda;
    }

    $fecha_cierre = $cierre->created_at;
    if(is_null($fecha_cierre)){
      $fecha_cierre = $cierre->fecha;
    }

    $detallesC = DB::table('ficha as F')->select('DC.monto_ficha','F.valor_ficha','F.id_ficha','DC.id_detalle_cierre')
    ->leftJoin('detalle_cierre as DC',function ($join) use($id){
      $join->on('DC.id_ficha','=','F.id_ficha')->where('DC.id_cierre_mesa','=',$id);
    })
    ->join('ficha_tiene_casino','ficha_tiene_casino.id_ficha','=','F.id_ficha')
    ->where('ficha_tiene_casino.id_casino','=',$cierre->id_casino)
    ->where(function($q) use ($fecha_cierre){
      return $q->where('ficha_tiene_casino.deleted_at','>',$fecha_cierre)->orWhereNull('ficha_tiene_casino.deleted_at');
    })
    ->where(function($q) use ($fecha_cierre){
      return $q->where('ficha_tiene_casino.created_at','<=',$fecha_cierre)->orWhereNotNull('DC.id_ficha');
    })
    ->where('F.id_moneda','=',$moneda->id_moneda)
    ->orderBy('F.valor_ficha','desc')
    ->get();

    //Apertura asociada
    $apertura = null;
    $detalleAP = null;
    if(isset($cierre->cierre_apertura)){
      $apertura = $cierre->cierre_apertura->apertura;
      $id_ap = $apertura->id_apertura_mesa;

      $detalleAP = DB::table('ficha')->select('DA.cantidad_ficha','ficha.valor_ficha','ficha.id_ficha','DA.id_detalle_apertura',
                                    DB::raw('SUM(DA.cantidad_ficha * ficha.valor_ficha) as monto_ficha'))
      ->leftJoin('detalle_apertura as DA',function ($join) use($id_ap){
        $join->on('DA.id_ficha','=','ficha.id_ficha')->where('DA.id_apertura_mesa','=',$id_ap);
      })
      ->join('ficha_tiene_casino','ficha_tiene_casino.id_ficha','=','ficha.id_ficha')
      ->where('ficha_tiene_casino.id_casino','=',$apertura->id_casino)
      ->where(function($q) use ($apertura){
        // Aca antes tambien se verificaba si la ficha estaba deleted_at, lo saque porque no se pueden borrar fichas
        // en el modal de casino, ademas no importa tanto que este la ficha sino que el casino la tenga - Octavio 30/03/21
        return $q->where('ficha_tiene_casino.deleted_at','>',$apertura->fecha)->orWhereNull('ficha_tiene_casino.deleted_at');
      })
      ->where(function($q) use ($apertura){
        return $q->where('ficha_tiene_casino.created_at','<=',$apertura->created_at)->orWhereNotNull('DA.id_ficha');
      })
      ->where('ficha.id_moneda','=',$apertura->id_moneda)
      ->groupBy('DA.cantidad_ficha','ficha.valor_ficha','ficha.id_ficha','DA.id_detalle_apertura')
      ->orderBy('ficha.valor_ficha','desc')->get();
    }

    return ['cierre' => $cierre,'cargador' => $cierre->fiscalizador, 'casino' => $cierre->casino,
            'mesa' => $mesa, 'moneda' => $moneda, 'apertura' => $apertura,
            'detallesC' => $detallesC, 'detalleAP' => $detalleAP,
            'nombre_juego' => JuegoMesa::withTrashed()->find($mesa->id_juego_mesa)->nombre_juego];
  }

  public function filtros(Request $request)
  {
    $filtros = array();
    if(!empty($request->nro_mesa)){
      $filtros[]= ['mesa_de_panio.nro_mesa','like',$request->nro_mesa.'%'];
    }
    if(!empty($request->id_juego)){
      $filtros[]= ['mesa_de_panio.id_juego_mesa','=',$request->id_juego];
    }
    if(!empty($request->id_casino)){
      $filtros[]= ['cierre_mesa.id_casino','=',$request->id_casino];
    }

    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = [];
    foreach ($user->casinos as $cass) {
      $cas[]=$cass->id_casino;
    }
    $sort_by = ['columna' => 'cierre_mesa.fecha','orden','desc'];
    if(!empty( $request->sort_by)){
      $sort_by = $request->sort_by;
    }

    $resultados = DB::table('cierre_mesa')
    ->select(
      'cierre_mesa.id_cierre_mesa as id','cierre_mesa.fecha',
      'casino.nombre as casino','juego_mesa.siglas as juego',
      'moneda.siglas as moneda','mesa_de_panio.nro_mesa',
      'cierre_mesa.id_estado_cierre as estado',
      DB::raw('CONCAT(
        IFNULL(TIME_FORMAT(cierre_mesa.hora_inicio,"%H:%i"),""),
        "-",
        IFNULL(TIME_FORMAT(cierre_mesa.hora_fin,"%H:%i"),"")
      ) as hora'),
      DB::raw('ca.id_cierre_apertura IS NOT NULL as linkeado')
    )
    ->join('mesa_de_panio','mesa_de_panio.id_mesa_de_panio','=','cierre_mesa.id_mesa_de_panio')
    ->join('casino','casino.id_casino','=','mesa_de_panio.id_casino')
    ->leftJoin('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
    ->leftJoin('moneda','moneda.id_moneda','=','cierre_mesa.id_moneda')
    ->leftJoin('cierre_apertura as ca','cierre_mesa.id_cierre_mesa','=','ca.id_cierre_mesa')
    ->where($filtros)
    ->whereIn('cierre_mesa.id_casino',$cas)
    ->whereNull('cierre_mesa.deleted_at');

    if(!empty($request->fecha)){
      $fecha=explode("-", $request->fecha);
      $resultados = $resultados->whereYear('cierre_mesa.fecha' , '=', $fecha[0])
                              ->whereMonth('cierre_mesa.fecha','=', $fecha[1])
                              ->whereDay('cierre_mesa.fecha','=', $fecha[2]);
    }

    $resultados = $resultados->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    })->paginate($request->page_size);
    return $resultados;
  }
}
