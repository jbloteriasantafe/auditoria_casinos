<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\InformeControlAmbiental;
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
    $reglas = array();
    $casinos = array();
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

    if(!empty($request['id_casino']) || $request['id_casino'] != 0){
      $casinos[] = $request['id_casino'];
    }else {
      foreach ($user->casinos as $cass) {
        $casinos[]=$cass->id_casino;
      }
    }

    if(!empty( $request->sort_by)){
      $sort_by = $request->sort_by;
    }else {

        $sort_by = ['columna' => 'informe_control_ambiental.fecha','orden'=>'desc'];
    }

    if(!empty($request['fecha']) || $request['fecha'] != 0){

      $fecha = explode('-',$request['fecha']);
      $diarios = DB::table('informe_control_ambiental')
                    ->join('casino','casino.id_casino','=','informe_control_ambiental.id_casino')
                    ->where($reglas)
                    ->whereYear('fecha','=',$fecha[0])
                    ->whereMonth('fecha','=',$fecha[1])
                    ->whereDay('fecha','=',$fecha[2])
                    ->whereIn('casino.id_casino',$casinos)
                    ->when($sort_by,function($query) use ($sort_by){
                                    return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                })
                    ->paginate($request->page_size);
    }else{
      $diarios = DB::table('informe_control_ambiental')
                    ->join('casino','casino.id_casino','=','informe_control_ambiental.id_casino')
                    ->where($reglas)
                    ->whereIn('casino.id_casino',$casinos)
                    ->when($sort_by,function($query) use ($sort_by){
                                    return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                })
                    ->paginate($request->page_size);
    }

   return ['diarios' => $diarios];
  }

  public function crearInformeControlAmbiental($relevamiento_ambiental_mtm, $relevamiento_ambiental_mesas) {
    DB::transaction(function() use($relevamiento_ambiental_mtm, $relevamiento_ambiental_mesas){
      $informe_ambiental = new InformeControlAmbiental;
      $informe_ambiental->id_casino = $relevamiento_ambiental_mtm->id_casino;
      $informe_ambiental->fecha = $relevamiento_ambiental_mtm->fecha_generacion;
      $informe_ambiental->nro_informe_control_ambiental = DB::table('informe_control_ambiental')->max('nro_informe_control_ambiental') + 1;
      $informe_ambiental->id_relevamiento_ambiental_maquinas = $relevamiento_ambiental_mtm->id_relevamiento_ambiental;
      $informe_ambiental->id_relevamiento_ambiental_mesas = $relevamiento_ambiental_mesas->id_relevamiento_ambiental;

      $informe_ambiental->save();
    });
  }

  public function imprimir($id_informe) {
    $informe = InformeControlAmbiental::find($id_informe);
    $casino = Casino::find($informe->id_casino);

    $detalles_informe_mtm = array();
    $detalles_informe_maq = array();
    $distribuciones_globales_mtm = array();

    $detalles_relevamientos_mtm = DB::table('detalle_relevamiento_ambiental')
                          ->join('isla','isla.id_isla','=','detalle_relevamiento_ambiental.id_isla')
                          ->join('sector','sector.id_sector','=','isla.id_sector')
                          ->where('id_relevamiento_ambiental','=', $informe->id_relevamiento_ambiental_maquinas)
                          ->get();

    //creo un detalle MTM por cada sector:
    foreach ($casino->sectores as $sector) {
      $flag_hay_detalles_sector = 0;
      $totalizador_sector = 0;
      $totales_sector = array();
      $porcentajes_sector = array();

      //creo un array totales y porcentajes de sector por cada turno existente:
      for ($i=1; $i<=sizeof($casino->turnos); $i++) {
        $total = 0;

        if      ($i==1) $turno = 'turno1';
        else if ($i==2) $turno = 'turno2';
        else if ($i==3) $turno = 'turno3';
        else if ($i==4) $turno = 'turno4';
        else if ($i==5) $turno = 'turno5';
        else if ($i==6) $turno = 'turno6';
        else if ($i==7) $turno = 'turno7';
        else            $turno = 'turno8';

        foreach ($detalles_relevamientos_mtm as $d) {
          if ($d->id_sector == $sector->id_sector) {
            if      ($i==1) $total += $d->turno1;
            else if ($i==2) $total += $d->turno2;
            else if ($i==3) $total += $d->turno3;
            else if ($i==4) $total += $d->turno4;
            else if ($i==5) $total += $d->turno5;
            else if ($i==6) $total += $d->turno6;
            else if ($i==7) $total += $d->turno7;
            else            $total += $d->turno8;

            $flag_hay_detalles_sector = 1;
          }
        }

        $totalizador_sector += $total;
        //creo un item de totales_sector y lo añado al array:
        $t = array(
          'turno' => $turno,
          'total' => $total
        );
        $totales_sector[] = $t;
      }

      foreach ($totales_sector as $t) {
        //creo un item de porcentajes_sector y lo añado al array:
        $p = array(
          'turno' => $t['turno'],
          'porcentaje' => ($flag_hay_detalles_sector) ? number_format($t['total']*100 / $totalizador_sector ,2) : 0 //división por cero
        );
        $porcentajes_sector[] = $p;
      }

      //genero un detalle MTM:
      $detalle_informe_mtm = array(
        'totales_sector' => $totales_sector,
        '$porcentajes_sector' => $porcentajes_sector,
        'flag_hay_detalles_sectores' => $flag_hay_detalles_sector
      );

      //añado el detalle MTM al array de detalles:
      $detalles_informe_mtm[] = $detalle_informe_mtm;
    }

    for ($i=1; $i<=sizeof($casino->turnos); $i++) {
      $distribucion = 0;

      foreach ($totales_sector as $t) {
        for ($i=0; $i<sizeof($t); $i++) {
        //  if ($t[$i]['turno'] == 'turno' . $i+1)
        //    $distribucion += $t['total'];
        }
      }

      $dist = array(
        'turno' => 'turno' . $i,
        'distribucion' => $distribucion
      );

      $distribuciones_globales_mtm[] = $dist;
    }

    //dd(sizeof($totales_sector));
    dd($distribuciones_globales_mtm);


    $otros_datos = array(
      'fecha_produccion' => date("d-m-Y", strtotime($informe->fecha)),
      'cantidad_turnos' => sizeof($casino->turnos)
    );

    $view = view('planillaInformesControlAmbiental', compact(['$detalles_informe_mtm','$detalles_informe_maq','otros_datos']));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view);
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $casino->codigo."/".$informe->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf->stream('informe_diario_'.$casino->codigo.'_'.$informe->fecha.'.pdf', Array('Attachment'=>0));
  }

}
