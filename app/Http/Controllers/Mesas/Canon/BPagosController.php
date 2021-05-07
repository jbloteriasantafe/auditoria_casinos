<?php

namespace App\Http\Controllers\Mesas\Canon;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Casino;
use App\Mesas\InformeFinalMesas;
use App\Mesas\DetalleInformeFinalMesas;
use App\Http\Controllers\UsuarioController;

class BPagosController extends Controller{

  public function index(){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    return view ('Canon.canon',['casinos'=>$user->casinos]);
  }
  
  private static $instance;
  public static function getInstancia() {
    if(!isset(self::$instance)){
      self::$instance = new BPagosController();
    }
    return self::$instance;
  }

  public function filtros(Request $request){
    $user =UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = array();
    foreach ($user->casinos as $cass) {
      $cas[]=$cass->id_casino;
    }

    $filtros = array();
    if(!empty($request->id_casino) && $request->id_casino != 0){
      $filtros[] = ['DIFM.id_casino','=',$request->id_casino];
    }
    if(!empty($request->mes) && $request->mes != 0){
      $filtros[] = ['DIFM.mes','=',$request->mes];
    }

    $resultados = DB::table('detalle_informe_final_mesas as DIFM')
    ->join('casino','casino.id_casino','=','DIFM.id_casino')
    ->where($filtros)
    ->whereIn('DIFM.id_casino',$cas)
    ->whereNull('DIFM.deleted_at');

    if(!empty($request->fecha)){
      $fecha = explode("-", $request->fecha);
      $resultados = $resultados->whereYear('DIFM.fecha_cobro', '=', $fecha[0])
      ->whereMonth('DIFM.fecha_cobro','=', $fecha[1]);
    }

    
    if(!empty($request->sort_by)){
      $sort_by = $request->sort_by;
      $resultados = $resultados->orderBy($sort_by['columna'],$sort_by['orden']);
    }
    else{
      $resultados = $resultados->orderByRaw('DIFM.anio DESC,DIFM.mes DESC,DIFM.dia_inicio DESC');
    }
    
    return ['pagos' => $resultados->paginate($request->page_size)];
  }

  public function verInformeFinalMesas(Request $request){
    $informe = InformeFinalMesas::where('id_casino','=',$request->id_casino)
    ->where('anio_inicio','=',$request->anio_inicio-1)->whereNull('deleted_at')->first();

    $informe_anterior = $informe->informe_anterior();

    if($informe == null) return response()->json(['error' => 'INFORME NO ENCONTRADO'], 404);

    $actual = $this->calcularValorBaseYCanon($informe_anterior);
    $nuevo = $this->actualizarBaseCanon($actual,$informe_anterior,$informe);

    return ['informe_anterior'  => $informe_anterior,
            'informe'           => $informe,
            'detalles_anterior' => is_null($informe_anterior)? [] : $informe_anterior->detalles()->orderByRaw('anio ASC,mes ASC,dia_inicio ASC')->get(),
            'detalles'          => $informe->detalles()->orderByRaw('anio ASC,mes ASC,dia_inicio ASC')->get(),
            'actual' => $actual,
            'nuevo' => $nuevo
          ];
  }

  public function obtenerPago($id_detalle){
    $detalle = DetalleInformeFinalMesas::find($id_detalle);
    $casino = Casino::find($detalle->id_casino);
    return response()->json([ 'detalle' => $detalle,'casino'=>$casino, 'informe' => $detalle->informe_final_mesas], 200);
  }

  public function obtenerAnios($id_casino){
    $anios = DB::table('informe_final_mesas as ifm')
    ->selectRaw('(ifm.anio_inicio+1) as anio_inicio, (ifm.anio_final+1) as anio_final')
    //join para eliminar anios sin meses cargados
    ->join('detalle_informe_final_mesas as difm','difm.id_informe_final_mesas','=','ifm.id_informe_final_mesas')
    ->where('ifm.id_casino','=',$id_casino)->whereNull('ifm.deleted_at')
    ->whereNull('ifm.base_anterior_dolar')->whereNull('ifm.base_anterior_euro')->distinct()->get();
    return ['anios' => $anios];
  }

  public function obtenerInformeBase($id_casino){
    $d = DetalleInformeFinalMesas::where('id_casino','=',$id_casino)
    ->orderBy('anio','asc')->orderBy('mes','asc')->orderBy('dia_inicio','asc')
    ->orderBy('id_detalle_informe_final_mesas','asc')->first();
    return is_null($d)? null : $d->informe_final_mesas;
  }

  private function actualizarBaseCanon($ret,$i,$i2){
    $total  = is_null($i)?      0.0 : $i->medio_total_euro;
    $total2 = is_null($i2)?     0.0 : $i2->medio_total_euro;

    $base = empty($total)? INF : $ret['euro']['base']*($total2/$total);
    $ret['euro']['canon'] = max($ret['euro']['base'],$base);
    $ret['euro']['base'] = $base;

    $total  = is_null($i)?      0.0 : $i->medio_total_dolar;
    $total2 = is_null($i2)?     0.0 : $i2->medio_total_dolar;

    $base = empty($total)? INF : $ret['dolar']['base']*($total2/$total);
    $ret['dolar']['canon'] = max($ret['dolar']['base'],$base);
    $ret['dolar']['base'] = $base;

    foreach($ret as $midx => $moneda){
      foreach($moneda as $vidx => $val){
        $ret[$midx][$vidx] = is_infinite($val)? 'Infinity' : $val;
      }
    }

    return $ret;
  }

  private function calcularValorBaseYCanon($informe){
    $ret = ['euro' => ['base' => 0,'canon' => 0],'dolar' => ['base' => 0,'canon' => 0]];
    if(is_null($informe)) return $ret;
    $i = $this->obtenerInformeBase($informe->id_casino);
    $ret['euro']['base']   = $i->base_anterior_euro;
    $ret['euro']['canon']  = $i->base_anterior_euro;
    $ret['dolar']['base']  = $i->base_anterior_dolar;
    $ret['dolar']['canon'] = $i->base_anterior_dolar;
    while($i->id_informe_final_mesas != $informe->id_informe_final_mesas){
      $i2 = $i->informe_proximo();
      $ret = $this->actualizarBaseCanon($ret,$i,$i2);
      $i = $i2;
    }
    return $ret;
  }


  public function mesesCuotasCanon($id_casino,$anio_inicio){
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    if($usuario == null || !$usuario->usuarioTieneCasino($id_casino)){
      return ['error' => 'El usuario no tiene accesso a ese casino'];
    }

    $generar_cuota = function($fecha_inicio,$fecha_fin,$nro_cuota) use ($id_casino){
      return (object)[
        'nro_cuota'  => $nro_cuota,
        'nro_mes'    => intval($fecha_fin->format('m')),
        'dia_inicio' => intval($fecha_inicio->format('d')),
        'dia_fin'    => intval($fecha_fin->format('d'))
      ];
    };

    $casino = Casino::find($id_casino);
    $inicio = explode('-',$casino->fecha_inicio);
    $fecha = new \DateTime($anio_inicio.'-'.$inicio[1].'-'.$inicio[2]);
    $cuotas = [];
    for($nro_cuota = 1;$nro_cuota < 13;$nro_cuota++){
      $desde = $fecha;
      $hasta = (clone $fecha)->modify('last day of');
      $cuotas[] = $generar_cuota($desde,$hasta,$nro_cuota);
      $fecha->modify('+1 month')->modify('first day of');
    }

    $fecha = new \DateTime($casino->fecha_inicio);
    //Si empezo iniciado el mes se le agrega una cuota mas con los dias que le faltaron
    if($fecha->format('d') != '1'){
      $desde = (clone $fecha)->modify('first day of');
      $hasta = (clone $fecha)->modify('-1 day');
      $cuotas[] = $generar_cuota($desde,$hasta,13);
    }

    return ['casino' => $casino, 'meses' => $cuotas];
  }

  public function mesesCargados($id_casino,$anio_inicio){
    $r = DB::table('informe_final_mesas as ifm')->select('difm.*')
    ->join('detalle_informe_final_mesas as difm','ifm.id_informe_final_mesas','=','difm.id_informe_final_mesas')
    ->whereNull('ifm.deleted_at')->whereNull('difm.deleted_at')->where('ifm.id_casino','=',$id_casino)
    ->where('ifm.anio_inicio','=',$anio_inicio)
    ->orderBy('difm.anio','asc')->orderBy('difm.mes','asc')->orderBy('difm.dia_inicio','asc')->get();
    return $r; 
  }
}
