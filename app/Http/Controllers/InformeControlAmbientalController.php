<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\RelevamientoAmbiental;
use App\DetalleRelevamientoAmbiental;
use App\Casino;
use Dompdf\Dompdf;
use App\Http\Controllers\Turnos\TurnosController;

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

    //hardlimit en la tabla, lo obtengo dinamicamente
    $TURNOS_TOTALES = DetalleRelevamientoAmbiental::limiteCantidadTurnos();

    $detalles_relevamientos_mtm = DB::table('detalle_relevamiento_ambiental');
    if($id_casino == 3){
      //Como hay varias islas con el mismo nro_islote, hago una subquery para que me devuelva una sola fila por id_sector-nro_islote
      //Si un islote esta en mas de un sector (porque en la isla i1 tiene el sector A y en la isla i2 tiene el sector B), se rompe todo
      //Terminaria contando para ambos sectores... medio raro. Deberia prevenirse esto desde el frontend/backend de gestion de Islas
      //-- Octavio 24 de Enero 2022
      $detalles_relevamientos_mtm = $detalles_relevamientos_mtm->join(DB::raw(
        '(
          SELECT distinct i.id_sector, i.nro_islote
          FROM isla as i
          WHERE i.deleted_at IS NULL
        ) as isla'
      ),'isla.nro_islote','=','detalle_relevamiento_ambiental.nro_islote');
    }
    else{
      $detalles_relevamientos_mtm = $detalles_relevamientos_mtm->join('isla','isla.id_isla','=','detalle_relevamiento_ambiental.id_isla');
    }

    $aux_turnos = array_map(function($i){return "SUM(IFNULL(turno$i,0)) as turno$i";},range(1,$TURNOS_TOTALES));
    $group_turnos = implode(',',$aux_turnos);
    $aux_turnos = array_map(function($i){return "SUM(IFNULL(turno$i,0))";},range(1,$TURNOS_TOTALES));
    $group_turnos .= ',('.implode('+',$aux_turnos).') as total';

    $detalles_relevamientos_mtm = $detalles_relevamientos_mtm
    ->join('sector','sector.id_sector','=','isla.id_sector')
    ->join('relevamiento_ambiental','relevamiento_ambiental.id_relevamiento_ambiental','=','detalle_relevamiento_ambiental.id_relevamiento_ambiental')
    ->where(DB::raw('DATE(relevamiento_ambiental.fecha_generacion)'),'=', $fecha)
    ->where('sector.id_casino','=',$id_casino);

    $por_sector_mtm = (clone $detalles_relevamientos_mtm)
    ->selectRaw("sector.id_sector, sector.descripcion, $group_turnos")
    ->groupBy('sector.id_sector')
    ->get()
    ->merge(
      (clone $detalles_relevamientos_mtm)
      ->selectRaw("'TOTAL' as id_sector,'TOTAL' as descripcion, $group_turnos")
      ->groupBy('sector.id_casino')
      ->get()
    );

    $estado_mtm = RelevamientoAmbiental::where([
      ['id_casino','=',$id_casino],[DB::raw('DATE(fecha_generacion)'),'=',$fecha],['id_tipo_relev_ambiental','=',0]
    ])->get()->first();
    if(!is_null($estado_mtm)) $estado_mtm = $estado_mtm->estado_relevamiento->descripcion;

    $detalles_relevamientos_mesas = DB::table('detalle_relevamiento_ambiental')
    ->join('mesa_de_panio','mesa_de_panio.id_mesa_de_panio','=','detalle_relevamiento_ambiental.id_mesa_de_panio')
    ->join('sector_mesas','sector_mesas.id_sector_mesas','=','mesa_de_panio.id_sector_mesas')
    ->join('relevamiento_ambiental','relevamiento_ambiental.id_relevamiento_ambiental','=','detalle_relevamiento_ambiental.id_relevamiento_ambiental')
    ->where(DB::raw('DATE(relevamiento_ambiental.fecha_generacion)'),'=', $fecha)
    ->where('sector_mesas.id_casino','=',$id_casino);

    $por_sector_mesas = (clone $detalles_relevamientos_mesas)
    ->selectRaw("sector_mesas.id_sector_mesas as id_sector, sector_mesas.descripcion, $group_turnos")
    ->groupBy('sector_mesas.id_sector_mesas')
    ->get()
    ->merge(
      (clone $detalles_relevamientos_mesas)
      ->selectRaw("'TOTAL' as id_sector,'TOTAL' as descripcion, $group_turnos")
      ->groupBy('sector_mesas.id_casino')
      ->get()
    );

    $estado_mesas = RelevamientoAmbiental::where([
      ['id_casino','=',$id_casino],[DB::raw('DATE(fecha_generacion)'),'=',$fecha],['id_tipo_relev_ambiental','=',1]
    ])->get()->first();
    if(!is_null($estado_mesas)) $estado_mesas = $estado_mesas->estado_relevamiento->descripcion;

    $turnos = (new TurnosController)->obtenerTurnosActivos($id_casino,$fecha)->take($TURNOS_TOTALES);
  
    $extrar_totales = function($turnos,&$por_sector){
      $sectores = [];
      if(is_null($por_sector)) return $sectores;

      foreach($por_sector as $x){
        $s = &$sectores[$x->id_sector];
        $s = [
          'sector' => $x->descripcion,
          'turnos' => [],
          'total_sector' => $x->total,
        ];
        foreach($turnos as $i => $t){
          $s['turnos'][$t->nro_turno] = $x->{'turno'.($i+1)};
        }
      }
      return $sectores;
    };

    $sectores_mtm   = $extrar_totales($turnos,$por_sector_mtm);
    $sectores_mesas = $extrar_totales($turnos,$por_sector_mesas);

    //Totalizo
    $total_por_turno = $sectores_mtm['TOTAL']['turnos'];
    foreach($sectores_mesas['TOTAL']['turnos'] as $nro_turno => $ocupacion){
      $total_por_turno[$nro_turno] += $ocupacion;
    }
    $total = array_reduce($total_por_turno,function($total,$i){ return $total+$i; },0);

    $casino = Casino::find($id_casino);
    $otros_datos = array(
      'fecha_produccion' => date("d-m-Y", strtotime($fecha)),
      'cantidad_turnos' => $turnos->count(),
      'casino' => $casino,
      'estado_mtm' => $estado_mtm,
      'estado_mesas' => $estado_mesas,
    );

    $view = view('planillaInformesControlAmbiental', compact(['sectores_mtm','sectores_mesas','total_por_turno','total','otros_datos']));
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
