<?php
namespace App\Http\Controllers\Mesas\InformeFiscalizadores;

use Auth;
use Session;
use Illuminate\Http\Request;
use Response;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use View;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\UsuarioController;

use App\Usuario;
use App\Casino;
use App\SecRecientes;
use App\Http\Controllers\RolesPermissions\RoleFinderController;
use App\Mesas\RelevamientoApuestas;
use App\Http\Controllers\Mesas\Cierres\ABMCCierreAperturaController;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Dompdf\Dompdf;

use PDF;
use App\Mesas\InformeFiscalizadores;
use App\Mesas\ImportacionDiariaMesas;
use App\Mesas\DetalleImportacionDiariaMesas;

use App\Mesas\Cierre;
use App\Mesas\Apertura;
use App\Mesas\CierreApertura;
use App\Mesas\ApuestaMinimaJuego;
use App\Mesas\MinApInforme;
use App\Mesas\MesasSorteadas;


use App\Http\Controllers\Mesas\InformeFiscalizadores\GenerarInformesFiscalizadorController;

use Carbon\Carbon;

use Exception;

class BCInformesController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware(['tiene_permiso:m_ver_seccion_informe_fiscalizadores']);
  }

  public function index(){
    // $pep = new ABMCCierreAperturaController;
    // $pep->revivirElPasado();
    $uc = new UsuarioController;
    $uc->agregarSeccionReciente('Informes Diarios Fiscalizaciones','informeDiarioBasico');
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = $user->casinos;
    return view('InformesFiscalizadores.informeDiario',['casinos'=>$casinos]);

  }



  public function filtros(Request $request)
{
  // $this->actualizarAll();
  // dd('ok');
  $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
  $cas = array();

  if(!empty($request->id_casino) && $request->id_casino != 0){
    $cas[]= $request->id_casino;
  }else{
    foreach ($user->casinos as $cass) {
      $cas[]=$cass->id_casino;
    }
  }
  if(!empty( $request->sort_by)){
    $sort_by = $request->sort_by;
  }else{

      $sort_by = ['columna' => 'informe_fiscalizadores.fecha','orden','desc'];
  }

  if(empty($request->fecha)){
    $resultados = DB::table('informe_fiscalizadores')
              ->select('informe_fiscalizadores.id_informe_fiscalizadores','informe_fiscalizadores.fecha',
                        'casino.nombre'
                      )
              ->join('casino','casino.id_casino','=','informe_fiscalizadores.id_casino')
              ->whereIn('informe_fiscalizadores.id_casino',$cas)
              ->whereNull('informe_fiscalizadores.deleted_at')
              ->when($sort_by,function($query) use ($sort_by){
                              return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                          })
              ->paginate($request->page_size);
  }else{
    $fecha=explode("-", $request->fecha);
    $resultados = DB::table('informe_fiscalizadores')
                      ->select('informe_fiscalizadores.id_informe_fiscalizadores','informe_fiscalizadores.fecha',
                                'casino.nombre'
                              )
                      ->join('casino','casino.id_casino','=','informe_fiscalizadores.id_casino')
                      ->whereIn('informe_fiscalizadores.id_casino',$cas)
                      ->whereNull('informe_fiscalizadores.deleted_at')
                      ->whereYear('informe_fiscalizadores.fecha' , '=', $fecha[0])
                      ->whereMonth('informe_fiscalizadores.fecha','=', $fecha[1])
                      ->whereDay('informe_fiscalizadores.fecha','=', $fecha[2])
                      ->when($sort_by,function($query) use ($sort_by){
                                      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                  })
                      ->paginate($request->page_size);
  }
  return ['informes' => $resultados];
}

public function imprimirPlanilla($id_informe){
  $informe = InformeFiscalizadores::find($id_informe);

  $rel = new \stdClass();
  $rel->informe = $informe;
  $mesas_con_diferencia =json_decode($informe->mesas_con_diferencia);
  $fecha_maxima = Carbon::parse($informe->fecha)->addDays(150)->format("Y-m-d");
  $fecha_informe = Carbon::parse($informe->fecha)->format("Y-m-d");
  //dd($fecha_maxima ,$fecha_informe);
  if($fecha_maxima >= $fecha_informe){
    $turnos_sin_minimo = DB::table('relevamiento_apuestas_mesas')
                                  ->select('nro_turno','id_estado_relevamiento')
                                  ->where('cumplio_minimo','=',0)
                                  ->where('fecha','=',$informe->fecha)
                                  ->where('es_backup','=',0)
                                  ->where('id_casino','=',$informe->id_casino)
                                  ->get();

    $mesasRelevadasAbiertas = DB::table('detalle_relevamiento_apuestas' )
                                  ->select('detalle_relevamiento_apuestas.id_mesa_de_panio')
                                  ->join('relevamiento_apuestas_mesas','detalle_relevamiento_apuestas.id_relevamiento_apuestas',
                                          '=','relevamiento_apuestas_mesas.id_relevamiento_apuestas')
                                  ->where('relevamiento_apuestas_mesas.fecha', '=', $informe->fecha)
                                  ->where( 'detalle_relevamiento_apuestas.id_estado_mesa',
                                          '=',1)
                                  ->groupBy('detalle_relevamiento_apuestas.id_mesa_de_panio')
                                  ->orderBy('detalle_relevamiento_apuestas.id_mesa_de_panio','asc')
                                  ->distinct('detalle_relevamiento_apuestas.id_mesa_de_panio')
                                  ->get();

    $array_t = '';
    $hay_rels_sin_visar = 0;
    $cant_turnos = 0;
    foreach ($turnos_sin_minimo as $t) {
      if($cant_turnos <= 4){
        $array_t = $array_t.' - '.$t->nro_turno;
      }
      if($cant_turnos == 5){
        $array_t = $array_t.'...';
      }

      if($t->id_estado_relevamiento != 4){
        $hay_rels_sin_visar = 1;
      }
      $cant_turnos++;
    }

    $controllerCA = new ABMCCierreAperturaController;
    $mesas_con_diferencia = json_encode($controllerCA->obtenerMesasConDiferencias($informe->fecha));
    //
    $aperturas = Apertura::where('fecha','=',$informe->fecha)
                            ->where('id_estado_cierre','=',1)
                            ->where('id_casino','=',$informe->id_casino)
                            ->get()->count();

    $aperturas_totales = Apertura::where('fecha','=',$informe->fecha)
                            ->where('id_casino','=',$informe->id_casino)
                            ->get()->count();

    $cierres_totales= Cierre::where('fecha','=',$informe->fecha)
                             ->where('id_casino','=',$informe->id_casino)
                             ->get()->count();
    $cierres = Cierre::where('fecha','=',$informe->fecha)
                             ->where('id_estado_cierre','=',1)
                             ->where('id_casino','=',$informe->id_casino)
                             ->get()->count();

    $informe->turnos_sin_minimo = $array_t;
    $informe->mesas_relevadas_abiertas = json_encode($mesasRelevadasAbiertas);
    $informe->mesas_con_diferencia = $mesas_con_diferencia;
    $informe->ap_sin_validar =  $aperturas;
    $informe->cie_sin_validar = $cierres;
    $informe->cant_mesas_con_diferencia = count(json_decode($mesas_con_diferencia));
    $informe->cant_aperturas = $aperturas_totales;
    $informe->cant_cierres = $cierres_totales;
    //dd($informe);
    $informe->save();

  }
  if(count($informe->minimos) == 0){
    $this->asociarMinimos($informe);
  }
  $rel->minimos = $informe->minimos()->get()->all();


  $view = View::make('InformesFiscalizadores.informeDiarioFiscalizadores', compact('rel'));
  $dompdf = new Dompdf();
  $dompdf->set_paper('A4', 'portrait');
  $dompdf->loadHtml($view);
  $dompdf->render();
  $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
  $dompdf->getCanvas()->page_text(20, 815, $informe->casino->codigo."/".$informe->fecha, $font, 10, array(0,0,0));
  $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
  return $dompdf->stream('fiscalizacion-'.$informe->casino->codigo."-".$informe->fecha.'.pdf', Array('Attachment'=>0));
}

public function asociarMinimos($informe){
  $informeController = new GenerarInformesFiscalizadorController;
  $relevamientos = RelevamientoApuestas::where([
                                                ['id_casino','=',$informe->id_casino],
                                                ['fecha','=',$informe->fecha]
                                               ])->get();

  foreach ($relevamientos as $rel) {
    $informeController->agregarRelacionValoresApuestas($rel);
  }
}


public function actualizarAll(){
  $informes = InformeFiscalizadores::all();

  foreach ($informes as $informe) {

    $turnos_sin_minimo = DB::table('relevamiento_apuestas_mesas')
                                  ->select('nro_turno','id_estado_relevamiento')
                                  ->where('cumplio_minimo','=',0)
                                  ->where('fecha','=',$informe->fecha)
                                  ->where('es_backup','=',0)
                                  ->where('id_casino','=',$informe->id_casino)
                                  ->get();

    $mesasRelevadasAbiertas = DB::table('detalle_relevamiento_apuestas' )
                                  ->select('detalle_relevamiento_apuestas.id_mesa_de_panio')
                                  ->join('relevamiento_apuestas_mesas','detalle_relevamiento_apuestas.id_relevamiento_apuestas',
                                          '=','relevamiento_apuestas_mesas.id_relevamiento_apuestas')
                                  ->where('relevamiento_apuestas_mesas.fecha', '=', $informe->fecha)
                                  ->where( 'detalle_relevamiento_apuestas.id_estado_mesa',
                                          '=',1)
                                  ->groupBy('detalle_relevamiento_apuestas.id_mesa_de_panio')
                                  ->orderBy('detalle_relevamiento_apuestas.id_mesa_de_panio','asc')
                                  ->distinct('detalle_relevamiento_apuestas.id_mesa_de_panio')
                                  ->get();


   $mesasImportadasAbiertas = DB::table('detalle_importacion_diaria_mesas')
                                    ->select('detalle_importacion_diaria_mesas.id_mesa_de_panio')
                                    ->join('importacion_diaria_mesas','detalle_importacion_diaria_mesas.id_importacion_diaria_mesas',
                                           '=', 'importacion_diaria_mesas.id_importacion_diaria_mesas')
                                    ->where('importacion_diaria_mesas.fecha','=',$informe->fecha)
                                    ->where('detalle_importacion_diaria_mesas.utilidad', '<>', 0)
                                    ->groupBy('detalle_importacion_diaria_mesas.id_mesa_de_panio')
                                    ->orderBy('detalle_importacion_diaria_mesas.id_mesa_de_panio','asc')
                                    ->distinct('detalle_importacion_diaria_mesas.id_mesa_de_panio')
                                    ->get();
    $array_t = '';
    $hay_rels_sin_visar = 0;
    $cant_turnos = 0;
    foreach ($turnos_sin_minimo as $t) {
      if($cant_turnos <= 4){
        $array_t = $array_t.' - '.$t->nro_turno;
      }
      if($cant_turnos == 5){
        $array_t = $array_t.'...';
      }

      if($t->id_estado_relevamiento != 4){
        $hay_rels_sin_visar = 1;
      }
      $cant_turnos++;
    }

    $controllerCA = new ABMCCierreAperturaController;
    $mesas_con_diferencia = json_encode($controllerCA->obtenerMesasConDiferencias($informe->fecha));
    //
    $aperturas = Apertura::where('fecha','=',$informe->fecha)
                            ->where('id_estado_cierre','=',1)
                            ->where('id_casino','=',$informe->id_casino)
                            ->get();

    $cierres = Cierre::where('fecha','=',$informe->fecha)
                             ->where('id_estado_cierre','=',1)
                             ->where('id_casino','=',$informe->id_casino)
                             ->get()->count();

    $informe->turnos_sin_minimo = $array_t;
    $informe->mesas_relevadas_abiertas = $mesasRelevadasAbiertas;
    $informe->mesas_importadas_abiertas = $mesasImportadasAbiertas;
    if($mesas_con_diferencia == 'null'){
      $informe->mesas_con_diferencia = '{}';
    }
    else {
      $informe->mesas_con_diferencia = $mesas_con_diferencia;
    }
    $informe->ap_sin_validar =  $aperturas->count();
    $informe->cie_sin_validar = $cierres;
    //dd($informe);
    $informe->save();

    $sorteadas = MesasSorteadas::where('fecha_backup','=', $informe->fecha)
                                ->where('id_casino','=',$informe->id_casino)
                                ->get()->first();
    if(isset($sorteadas)){
      //dd($sorteadas->mesas);
      $coinciden = 0;
      $mesas_sorteadas = $sorteadas->mesas;
      foreach ($mesas_sorteadas['ruletasDados'] as $mesa) {
        $apertura = $aperturas->where('id_mesa_de_panio',$mesa['id_mesa_de_panio']);
        if($apertura->first()!== null){
          $coinciden++;
        }
      }
      foreach ($mesas_sorteadas['cartas'] as $mesa) {
        $apertura = $aperturas->where('id_mesa_de_panio',$mesa['id_mesa_de_panio']);
        if($apertura->first() !== null){
          $coinciden++;
        }
      }
      //dd((($coinciden * 100)/$aperturas->count()),$coinciden);
      if($aperturas->count() != 0) {
        $informe->aperturas_sorteadas = round(($coinciden * 100)/$aperturas->count(),2);
      }
      else {
        $informe->aperturas_sorteadas = 0;
      }
      $informe->save();
      //dd($informe);
      //$sorteadas->delete();
    }

  }
}

}
