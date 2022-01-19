<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\RelevamientoAmbiental;
use App\Casino;
use Dompdf\Dompdf;

class InformeControlAmbientalController extends Controller
{

  private static $instance;

  public static function getInstancia() {
      if (!isset(self::$instance)) {
          self::$instance = new InformeControlAmbientalController();
      }
      return self::$instance;
  }

  public function buscarTodo(){
    return view ('seccionInformesControlAmbiental',['casinos'=>UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->casinos]);
  }

  public function buscarInformesControlAmbiental(Request $request){
    $casinos = array();
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    foreach($user->casinos as $c){
      $casinos[] = $c->id_casino;
    }

    $reglas = [];
    if(!empty($request->id_casino)){
      $reglas[] = ['c.id_casino','=',$request->id_casino];
    }
 
    if(!empty( $request->sort_by)){
      $sort_by = $request->sort_by;
    }else {
      $sort_by = ['columna' => 'fecha','orden'=>'desc'];
    }

    $ret = DB::table('relevamiento_ambiental as ra')
    ->selectRaw('DATE(ra.fecha_generacion) as fecha,ra.id_casino,c.nombre as casino')
    ->join('casino as c','c.id_casino','=','ra.id_casino')
    ->where($reglas)
    ->whereIn('ra.id_casino',$casinos)
    ->groupBy(DB::raw('DATE(ra.fecha_generacion), ra.id_casino'))
    ->orderBy($sort_by['columna'],$sort_by['orden'])
    ->paginate($request->page_size);

    return ['diarios' => $ret];
  }

  public function imprimir($id_casino,$fecha) {
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    if(!$user->usuarioTieneCasino($id_casino)) return '';
    
    $detalles_relevamientos_mtm = DB::table('detalle_relevamiento_ambiental')
    ->join('isla','isla.id_isla','=','detalle_relevamiento_ambiental.id_isla')
    ->join('sector','sector.id_sector','=','isla.id_sector')
    ->join('relevamiento_ambiental','relevamiento_ambiental.id_relevamiento_ambiental','=','detalle_relevamiento_ambiental.id_relevamiento_ambiental')
    ->where(DB::raw('DATE(relevamiento_ambiental.fecha_generacion)'),'=', $fecha)
    ->where('sector.id_casino','=',$id_casino)
    ->get();

    $detalles_relevamientos_mesas = DB::table('detalle_relevamiento_ambiental')
    ->join('mesa_de_panio','mesa_de_panio.id_mesa_de_panio','=','detalle_relevamiento_ambiental.id_mesa_de_panio')
    ->join('sector_mesas','sector_mesas.id_sector_mesas','=','mesa_de_panio.id_sector_mesas')
    ->join('relevamiento_ambiental','relevamiento_ambiental.id_relevamiento_ambiental','=','detalle_relevamiento_ambiental.id_relevamiento_ambiental')
    ->where(DB::raw('DATE(relevamiento_ambiental.fecha_generacion)'),'=', $fecha)
    ->where('sector_mesas.id_casino','=',$id_casino)
    ->get();
    
    //@HACK: obtener la cantidad de turnos al momento de generacion...
    $TURNOS_TOTALES = 8;//@HACK: hardlimit
    $total_por_turno = [];

    $sectores_mtm = [];
    $total_por_turno_mtm = [];

    $sectores_mesas = [];
    $total_por_turno_mesas = [];

    for($t=1;$t<=8;$t++){
      $total_por_turno[$t]       = 0;
      $total_por_turno_mtm[$t]   = 0;
      $total_por_turno_mesas[$t] = 0;
    }

    foreach($detalles_relevamientos_mtm as $d){
      $id_sector = $d->id_sector;
      if(!array_key_exists($id_sector,$sectores_mtm)){
        $sectores_mtm[$id_sector] = [
          'sector' => $d->descripcion,
          'turnos' => [],
          'total_sector' => 0
        ];
      }
      $s = &$sectores_mtm[$id_sector];
      for($t=1;$t<=$TURNOS_TOTALES;$t++){ 
        $ocupacion = $d->{'turno'.$t};
        if(!array_key_exists($t,$s['turnos'])) $s['turnos'][$t] = 0;
        $s['turnos'][$t] += $ocupacion;
        $s['total_sector'] += $ocupacion;
        $total_por_turno[$t] += $ocupacion;
        $total_por_turno_mtm[$t] += $ocupacion;
      }
    }

    $sectores_mtm['TOTAL'] = [
      'sector' => 'TOTAL',
      'turnos' => $total_por_turno_mtm,
      'total_sector' => array_reduce($total_por_turno_mtm,function($total,$i){ return $total+$i; },0),
    ];

    foreach($detalles_relevamientos_mesas as $d){//Lo mismo que en MTM
      $id_sector = $d->id_sector_mesas;
      if(!array_key_exists($id_sector,$sectores_mesas)){
        $sectores_mesas[$id_sector] = [
          'sector' => $d->descripcion,
          'turnos' => [],
          'total_sector' => 0
        ];
      }
      $s = &$sectores_mesas[$id_sector];
      for($t=1;$t<=$TURNOS_TOTALES;$t++){ 
        $ocupacion = $d->{'turno'.$t};
        if(!array_key_exists($t,$s['turnos'])) $s['turnos'][$t] = 0;
        $s['turnos'][$t] += $ocupacion;
        $s['total_sector'] += $ocupacion;
        $total_por_turno[$t] += $ocupacion;
        $total_por_turno_mesas[$t] += $ocupacion;
      }
    }

    $sectores_mesas['TOTAL'] = [
      'sector' => 'TOTAL',
      'turnos' => $total_por_turno_mesas,
      'total_sector' => array_reduce($total_por_turno_mesas,function($total,$i){ return $total+$i; },0),
    ];

    $casino = Casino::find($id_casino);
    $otros_datos = array(
      'fecha_produccion' => date("d-m-Y", strtotime($fecha)),
      'cantidad_turnos' => $TURNOS_TOTALES,//@HACK
      'casino' => $casino,
    );

    $view = view('planillaInformesControlAmbiental', compact(['sectores_mtm','sectores_mesas','total_por_turno','otros_datos']));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view);
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $casino->codigo."/".$fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf->stream('informe_diario_'.$casino->codigo.'_'.$fecha.'.pdf', Array('Attachment'=>0));
  }
}
