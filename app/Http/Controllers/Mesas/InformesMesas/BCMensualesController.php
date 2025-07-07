<?php

namespace App\Http\Controllers\Mesas\InformesMesas;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\Mesas\Importaciones\ImportadorController;
use App\Mesas\JuegoMesa;
use App\Mesas\Moneda;
use App\Casino;
use App\Mesas\ImportacionDiariaMesas;
use App\Mesas\DetalleImportacionDiariaMesas;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Dompdf\Dompdf;
use PDF;

class BCMensualesController extends Controller
{
  public function obtenerDatosGraficos(Request $request){
    $monthNames_N = [".-." => 0,"Enero" => 1, "Febrero" => 2, "Marzo" => 3, "Abril" => 4, "Mayo" => 5, "Junio" => 6,
      "Julio" => 7, "Agosto" => 8, "Septiembre" => 9, "Octubre" => 10, "Noviembre" => 11, "Diciembre" => 12];

    $fecha = explode('-',$request->fecha);
    $anio = null;
    $nombre_mes = null;
    $nro_mes = null;
    if(count($fecha) < 2){
      $hoy_m_1mes = Carbon::now()->subMonths(1);
      $anio = $hoy_m_1_mes->format('Y');
      $nro_mes = $hoy_m_1_mes->format('m');
    }else{
      $anio = $fecha[0];
      $nro_mes = $monthNames_N[$fecha[1]];
    }

    $por_moneda = [];
    foreach(Moneda::all() as $moneda){
      $por_moneda[] = $this->mensualPorMonedaPorJuego($request->id_casino,$moneda->id_moneda,[$anio,$nro_mes]);
    }
    $ret = [];
    foreach($por_moneda as $moneda){
      $m = new \StdClass;
      $total_por_sigla = [];
      $m->moneda = $moneda['moneda'];

      foreach($moneda['juegos'] as $j){//Agrupo por sigla
        $siglas = $j->siglas_juego;
        if(!array_key_exists($siglas,$total_por_sigla)){
          $total_por_sigla[$siglas] = 0;
        }
        $total_por_sigla[$siglas]+=$j->utilidad;
      }

      $total_por_nombre = [];
      foreach($total_por_sigla as $sigla => $utilidad){//Agrupo por nombre si se puede
        $jm = JuegoMesa::whereNull('deleted_at')->where('siglas','=',$sigla)
        ->where('id_casino','=',$request->id_casino)->first();
        if(is_null($jm)) $total_por_nombre[$sigla]            = $utilidad;
        else             $total_por_nombre[$jm->nombre_juego] = $utilidad;
      }

      $m->utilidad = $total_por_nombre;
      $ret[] = $m;
    }
    return['por_moneda' => $ret];
  }
  
  public function obtenerInformeMesas(){
    $beneficios = DB::table('importacion_diaria_mesas as idm')
    ->selectRaw('YEAR(fecha) as anio,MONTH(fecha) as mes,id_casino,COUNT(distinct id_moneda) as monedas,COUNT(distinct CONCAT(id_moneda,"-",DAY(fecha))) as dias_importados,0 as tiene_beneficio_mensual,1 as estado')
    ->whereNull('deleted_at')
    ->groupBy(DB::raw('YEAR(idm.fecha),MONTH(idm.fecha),idm.id_casino'))
    ->orderByRaw('anio desc,mes desc')
    ->get()
    ->transform(function(&$idm){
      $dias_mes = cal_days_in_month(CAL_GREGORIAN,$idm->mes,$idm->anio);
      $idm->tiene_beneficio_mensual = ($idm->monedas*$dias_mes) == $idm->dias_importados;
      return $idm;
    });
        
    if($beneficios->count() > 0){
      $primer_beneficio = $beneficios->last();
      $ultimo_beneficio = $beneficios->first();
      
      $primer_fecha = date('Y-m-d',strtotime($primer_beneficio->anio.'-'.$primer_beneficio->mes.'-01'));//Hago asi para que se complete el 0 si es un mes del 1-9 (01, 02, etc)
      $ultima_fecha = date('Y-m-d',strtotime($ultimo_beneficio->anio.'-'.$ultimo_beneficio->mes.'-01'));
      
      $casinos = Casino::all();
      for($f=$primer_fecha;$f<=$ultima_fecha;$f=date('Y-m-d',strtotime($f.' +1 months'))){
        $exp_f = array_map(function($s){return intval($s);},explode('-',$f));
        foreach($casinos as $c){
          $existe = $beneficios->search(function($b) use (&$exp_f,&$c){
            return $c->id_casino == $b->id_casino 
            && $exp_f[0] == $b->anio
            && $exp_f[1] == $b->mes;
          });
          if($existe === false){
            $b = new \stdClass();
            $b->anio = $exp_f[0];
            $b->mes  = $exp_f[1];
            $b->id_casino = $c->id_casino;
            $b->tiene_beneficio_mensual = 0;
            $b->estado = 0;
            $beneficios->push($b);
          }
        }
      }
    }

    $beneficios_x_casino = $beneficios->sortByDesc(function($b){
      return [$b->id_casino,$b->anio,$b->mes];
    })->groupBy('id_casino');
    
    UsuarioController::getInstancia()->agregarSeccionReciente('Informes Contables Mesas' ,'informesMesas');
    return view('seccionInformesMesas',compact('beneficios_x_casino'));
  }
  
  public function imprimirMensual(Request $request){
    $anio_mes = explode('-',$request->fecha);
    $datos = $this->mensualPorMonedaPorJuego($request->id_casino,$request->id_moneda,$anio_mes);
    
    $casino = Casino::find($request->id_casino);
    $pad = $anio_mes[1] < 10? '0' : '';
    $mes = "{$anio_mes[0]}-{$anio_mes[1]}";
    
    $view = view('Informes.informeMes', compact('datos','casino','mes'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view);
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    
    $dompdf->getCanvas()->page_text(20, 815, $casino->codigo."/".$mes, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf->stream('informe_mensual_'.$casino->codigo."-".$mes.'.pdf', Array('Attachment'=>0));
  }
  
  public function generarPlanillaContable(Request $request){
    $anio_mes = [$request->anio,$request->mes];
    $dias     = [$request->dia1,$request->dia2];
    $pad = $request->mes < 10? '0' : '';
    $mes = "{$request->anio}-$pad{$request->mes}";
    
    $monedas = Moneda::all();
    
    $datos = collect([]);
    foreach($monedas as $m){
      $mensualPorMonedaPorJuego = $this->mensualPorMonedaPorJuego($request->id_casino,$m->id_moneda,$anio_mes,$dias);
      foreach($mensualPorMonedaPorJuego['detalles'] as $d){
        $datos[$d['fecha']] = $datos[$d['fecha']] ?? collect([]);
        $datos[$d['fecha']][$m->siglas] = $d;
      }
      $datos[$mes.'-XX'] = $datos[$mes.'-XX'] ?? collect([]);
      $datos[$mes.'-XX'][$m->siglas] = (array) $mensualPorMonedaPorJuego['total'];
    }
    
    $datos = $datos->sortBy(function($arr,$fecha){ 
      return $fecha;
    });
      
    $casino = Casino::find($request->id_casino);
         
    $view = view('Informes.informeContableMesas', compact('datos','casino','mes','monedas'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view);
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    
    $dompdf->getCanvas()->page_text(20, 815, $casino->codigo."/".$mes, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf->stream('informe_mensual_'.$casino->codigo."-".$mes.'.pdf', Array('Attachment'=>0));
  }
  
  private function mensualPorMonedaPorJuego($id_casino,$id_moneda,$anio_mes,$dias = null){
    if(is_null($dias)) $dias = [1,cal_days_in_month(CAL_GREGORIAN,$anio_mes[1],$anio_mes[0])];
    
    $detalles = ImportacionDiariaMesas::whereYear('fecha','=',$anio_mes[0])
    ->whereMonth('fecha','=',$anio_mes[1])
    ->whereDay('fecha','>=',$dias[0])->whereDay('fecha','<=',$dias[1])
    ->where('id_casino','=',$id_casino)
    ->where('id_moneda','=',$id_moneda)
    ->whereNull('deleted_at')
    ->orderBy('fecha','asc')->get()->toArray();//si no hago toArray me retorna vacio despues...
        
    $total = DB::table('importacion_diaria_mesas as IDM')
    ->whereYear('IDM.fecha','=',$anio_mes[0])->whereMonth('IDM.fecha','=',$anio_mes[1])
    ->whereDay('IDM.fecha','>=',$dias[0])->whereDay('IDM.fecha','<=',$dias[1])
    ->where('IDM.id_casino','=',$id_casino)->where('IDM.id_moneda','=',$id_moneda)
    ->whereNull('IDM.deleted_at')
    ->selectRaw('SUM(IDM.droop) as droop, SUM(IDM.retiros) as retiros, SUM(IDM.utilidad) as utilidad, SUM(IDM.saldo_fichas) as saldo_fichas,
      "--" as hold, 0 as conversion_total')
    ->groupBy(DB::raw('"constant"'))
    ->first();

    if(is_null($total)){//No hay importacion en todo el mes
      $total = new \stdClass;
      $total->droop = 0;
      $total->retiros = 0;
      $total->utilidad = 0;
      $total->saldo_fichas = 0;
      $total->conversion_total = 0;
      $total->mesas = 0;
    }
    
    $total->hold = $total->droop != 0? number_format(($total->utilidad * 100)/$total->droop,3,',','.') : '--';
    foreach($detalles as &$d){
      $d['hold'] = $d['droop'] != 0? number_format(($d['utilidad'] * 100)/$d['droop'],3,',','.') : '--';
    }
    
    $total->mesas = DB::table('importacion_diaria_mesas as IDM')
    ->join('detalle_importacion_diaria_mesas as DIDM','DIDM.id_importacion_diaria_mesas','=','IDM.id_importacion_diaria_mesas')
    ->selectRaw('COUNT(distinct CONCAT(DIDM.siglas_juego,DIDM.nro_mesa)) as mesas')
    ->whereYear('IDM.fecha','=',$anio_mes[0])->whereMonth('IDM.fecha','=',$anio_mes[1])
    ->whereDay('IDM.fecha','>=',$dias[0])->whereDay('IDM.fecha','<=',$dias[1])
    ->where('IDM.id_casino','=',$id_casino)->where('IDM.id_moneda','=',$id_moneda)
    ->where(function($q){
      return $q->whereRaw('IFNULL(DIDM.droop,0) <> 0 OR IFNULL(DIDM.droop_tarjeta,0) <> 0 OR IFNULL(DIDM.reposiciones,0) <> 0
                        OR IFNULL(DIDM.retiros,0) <> 0 OR IFNULL(DIDM.utilidad,0) <> 0 OR IFNULL(DIDM.saldo_fichas,0) <> 0 OR IFNULL(DIDM.propina <> 0,0)');
    })
    ->groupBy(DB::raw('"constant"'))
    ->first();
    $total->mesas = is_null($total->mesas)? 0 : $total->mesas->mesas;

    foreach($detalles as &$d){
      $total->conversion_total += $d['conversion_total'];
      $d['mesas'] = DB::table('detalle_importacion_diaria_mesas as DIDM')
      ->selectRaw('COUNT(distinct CONCAT(DIDM.siglas_juego,DIDM.nro_mesa)) as mesas')
      ->where('id_importacion_diaria_mesas','=',$d['id_importacion_diaria_mesas'])
      ->where(function($q){
        return $q->whereRaw('IFNULL(DIDM.droop,0) <> 0 OR IFNULL(DIDM.droop_tarjeta,0) <> 0 OR IFNULL(DIDM.reposiciones,0) <> 0
                          OR IFNULL(DIDM.retiros,0) <> 0 OR IFNULL(DIDM.utilidad,0) <> 0 OR IFNULL(DIDM.saldo_fichas,0) <> 0 OR IFNULL(DIDM.propina <> 0,0)');
      })
      ->groupBy(DB::raw('"constant"'))
      ->first();
      $d['mesas'] = is_null($d['mesas'])? 0 : $d['mesas']->mesas;
    }

    $juegos = DB::table('importacion_diaria_mesas as IDM')
    ->join('detalle_importacion_diaria_mesas as DIDM','IDM.id_importacion_diaria_mesas','=','DIDM.id_importacion_diaria_mesas')
    ->whereYear('IDM.fecha','=',$anio_mes[0])->whereMonth('IDM.fecha','=',$anio_mes[1])
    ->whereDay('IDM.fecha','>=',$dias[0])->whereDay('IDM.fecha','<=',$dias[1])
    ->where('IDM.id_casino','=',$id_casino)->where('IDM.id_moneda','=',$id_moneda)
    ->whereNull('IDM.deleted_at')->whereNull('DIDM.deleted_at')
    ->selectRaw('DIDM.siglas_juego, DIDM.nro_mesa, SUM(DIDM.utilidad) as utilidad')
    ->groupBy('DIDM.siglas_juego','DIDM.nro_mesa')
    ->orderBy('DIDM.siglas_juego','asc')
    ->orderBy('DIDM.nro_mesa','asc')
    ->get();
    
    $total->abs_utilidad = 0;
    foreach($juegos as &$j){
      $j->abs_utilidad = abs($j->utilidad);
      $total->abs_utilidad += abs($j->abs_utilidad);
    }
    foreach($juegos as &$j){
      $j->porcentaje = $total->abs_utilidad != 0? number_format(100*$j->abs_utilidad/$total->abs_utilidad,3,',','.') : '--';
    }
    $total->porcentaje = number_format(100,3,',','.');

    return [
      'moneda' => Moneda::find($id_moneda)->siglas,
      'juegos' => $juegos,
      'detalles' => $detalles,
      'total' => $total,
    ];
  }
}
