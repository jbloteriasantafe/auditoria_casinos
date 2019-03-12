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
    //descomentar si es necesario crear los informes de fisc del pasado que no hayan sido creados
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
                ->orderBy('informe_fiscalizadores.fecha','desc')
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
                        ->orderBy('informe_fiscalizadores.fecha','desc')
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

    $turnos_sin_minimo = DB::table('relevamiento_apuestas_mesas')
                                  ->select('nro_turno','id_estado_relevamiento')
                                  ->where('cumplio_minimo','=',0)
                                  ->where('fecha','=',$informe->fecha)
                                  ->where('id_casino','=',$informe->id_casino)
                                  ->get();

    $mesasRelevadasAbiertas = DB::table('detalle_relevamiento_apuestas' )
                                  ->select('detalle_relevamiento_apuestas.id_mesa_de_panio',DB::raw('COUNT(detalle_relevamiento_apuestas.id_mesa_de_panio) as cantidad_relevadas'))
                                  ->join('relevamiento_apuestas_mesas','detalle_relevamiento_apuestas.id_relevamiento_apuestas',
                                          '=','relevamiento_apuestas_mesas.id_relevamiento_apuestas')
                                  ->where('relevamiento_apuestas_mesas.fecha', '=', $informe->fecha)
                                  ->where( 'detalle_relevamiento_apuestas.id_estado_mesa',
                                          '=',1)
                                  ->groupBy('detalle_relevamiento_apuestas.id_mesa_de_panio')
                                  ->orderBy('detalle_relevamiento_apuestas.id_mesa_de_panio','asc')
                                  ->distinct('detalle_relevamiento_apuestas.id_mesa_de_panio')
                                  ->get();

                                  $mesasImportadasAbiertas = [];
   // $mesasImportadasAbiertas=DB::table('detalle_importacion_diaria_mesas')
   //                                  ->select('detalle_importacion_diaria_mesas.id_mesa_de_panio',DB::raw('COUNT(detalle_importacion_diaria_mesas.id_mesa_de_panio) as cantidad_abiertas'))
   //                                  ->join('importacion_diaria_mesas','detalle_importacion_diaria_mesas.id_importacion_diaria_mesas',
   //                                        '=', 'importacion_diaria_mesas.id_importacion_diaria_mesas')
   //                                  ->where('importacion_diaria_mesas.fecha','=',$informe->fecha)
   //                                  ->where('detalle_importacion_diaria_mesas.utilidad', '<>', 0)
   //                                  ->groupBy('detalle_importacion_diaria_mesas.id_mesa_de_panio')
   //                                  ->orderBy('detalle_importacion_diaria_mesas.id_mesa_de_panio','asc')
   //                                  ->distinct('detalle_importacion_diaria_mesas.id_mesa_de_panio')
   //                                  ->get();
  //La cant de mesas relevadas como abiertas coincide con la cant de Mesas
  //importadas con utilidad !=0

    if($mesasImportadasAbiertas == $mesasRelevadasAbiertas){
      $relevamientos_incorrectos='false';
    }else {
      $relevamientos_incorrectos='true';
    }


    $array_t = '';
    $hay_rels_sin_visar = 0;
    foreach ($turnos_sin_minimo as $t) {
      $array_t = $array_t.' - '.$t->nro_turno;
      if($t->id_estado_relevamiento != 4){
        $hay_rels_sin_visar = 1;
      }
    }

    $rel->relevamientos_incorrectos=$relevamientos_incorrectos;
    $rel->turnos_no_cumplen = $array_t;
    $rel->hay_rels_sin_visar = $hay_rels_sin_visar;
    $controllerCA = new ABMCCierreAperturaController;
    $mesas_con_diferencia =$controllerCA->obtenerMesasConDiferencias($informe->fecha);
    $rel->mesas_con_diferencia = $mesas_con_diferencia;
    //
    $aperturas = Apertura::where('fecha','=',$informe->fecha)
                            ->where('id_estado_cierre','=',3)//visado
                            ->get();
    $asociados = CierreApertura::where('fecha_produccion','=',$informe->fecha)
                                 ->where('id_casino','=',$informe->id_casino)
                                 ->get();
    //
    $rel->aperturas_sin_validar = count($aperturas) - count($asociados);

    $view = View::make('InformesFiscalizadores.informeDiarioFiscalizadores', compact('rel'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view);
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $informe->casino->codigo."/".$informe->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf->stream('sorteoAperturas.pdf', Array('Attachment'=>0));
  }

}
