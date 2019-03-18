<?php
namespace App\Http\Controllers\Mesas\InformesMesas;

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
use App\Http\Controllers\RolesPermissions\RoleFinderController;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Dompdf\Dompdf;

use PDF;
use App\Mesas\Mesa;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;
use App\Mesas\Cierre;
use App\Mesas\Apertura;
use App\Mesas\DetalleApertura;
use App\Mesas\EstadoCierre;
use App\Mesas\TipoCierre;
use App\Mesas\MesasSorteadas;
use Carbon\Carbon;

use App\Http\Controllers\UsuarioController;

use Exception;

//alta BAJA y consulta de mesas sorteadas
class InformesController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
      $this->middleware([ 'tiene_permiso:m_bc_diario_mensual']);
  }

  //se usa en SorteoMesasController
  public function mensualPorCasino(){
  return   view('Informes.informeMes');
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view);
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $cas->codigo."/".$rel->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf->stream('sorteoAperturas.pdf', Array('Attachment'=>0));
  }

  public function mensualPorCasino2(){
    return view('Informes.informeMes');
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view);
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815,'holis', $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    $ruta = "informeMes.pdf";
    file_put_contents($ruta, $dompdf);
    $file = public_path(). '/'.$ruta;
    //$file = public_path().'\\Mesas\\'. $nombre;
    $headers = array('Content-Type' => 'application/octet-stream',);

    return response()->download($file,$ruta,$headers);
  }



  /*

    Mostrarlos una vez que las importaciones hayan sido validadas.

  */
  public function buscarMensuales(){
    $mensual = DB::table('importacion_mensual_mesas')
                   ->select('*')
                   ->join('casino','casino.id_casino','=','importacion_mensual_mesas.id_casino')
                   ->join('moneda','moneda.id_moneda','=','importacion_mensual_mesas.id_moneda')
                   ->where('importacion_mensual_mesas.validado','=',1)
                   ->orderBy('fecha_mes','desc')
                   ->take(12)
                   ->get();


    return view('Informes.seccionInformesMensuales',['mensuales' => $mensual]);
  }

  public function buscarDiarios(){
    $diarios = DB::table('importacion_diaria_mesas')
                  ->join('casino','casino.id_casino','=','importacion_diaria_mesas.id_casino')
                  ->join('moneda','moneda.id_moneda','=','importacion_diaria_mesas.id_moneda')
                  ->where('importacion_diaria_mesas.validado','=',1)
                  ->orderBy('fecha','desc')
                  ->take(31)
                  ->get();

    return view('Informes.seccionInformesDiarios',['diarios' => $diarios]);
  }

  public function filtrarMensuales(Request $request){
    $monthNames = [".-.","Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
      "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
      ];

    $reglas = array();
    $casinos = array();
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    if(!empty($request['id_casino']) || $request['id_casino'] != 0){
      $casinos[] = $request['id_casino'];
    }else{
      foreach ($user->casinos as $cass) {
        $casinos[]=$cass->id_casino;
      }
    }
    if(!empty($request['id_moneda']) || $request['id_moneda'] != 0){
      $reglas[]=['moneda.id_moneda','=',$request['id_moneda']];
    }

    if(!empty( $request->sort_by)){
      $sort_by = $request->sort_by;
    }else{

        $sort_by = ['columna' => 'fecha_mes','orden'=>'desc'];
    }

    if(!empty($request['fecha']) || $request['fecha'] != 0){
      $fecha = explode('-',$request['fecha']);
      $nombre_mes =$fecha[1];
      $i = 0;
      foreach ($monthNames as $mmm) {
        if($mmm == $fecha[1]){
          $nro_mes = $i;
          break;
        }
        $i++;
      }
      $mensual = DB::table('importacion_mensual_mesas')
                     ->select('*')
                     ->join('casino','casino.id_casino','=','importacion_mensual_mesas.id_casino')
                     ->join('moneda','moneda.id_moneda','=','importacion_mensual_mesas.id_moneda')
                     ->where('importacion_mensual_mesas.validado','=',1)
                     ->where($reglas)
                     ->whereYear('fecha_mes','=',$fecha[0])
                     ->whereMonth('fecha_mes','=',$nro_mes)
                     ->whereIn('casino.id_casino',$casinos)
                     ->where('importacion_mensual_mesas.id_moneda','=',1)
                     ->orderBy('fecha_mes','desc')
                     ->when($sort_by,function($query) use ($sort_by){
                                     return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                 })
                     ->paginate($request->page_size);
    }else{
      $mensual = DB::table('importacion_mensual_mesas')
                     ->select('*')
                     ->join('casino','casino.id_casino','=','importacion_mensual_mesas.id_casino')
                     ->join('moneda','moneda.id_moneda','=','importacion_mensual_mesas.id_moneda')
                     ->where('importacion_mensual_mesas.validado','=',1)
                     ->where('importacion_mensual_mesas.id_moneda','=',1)
                     ->where($reglas)
                     ->whereIn('casino.id_casino',$casinos)
                     ->orderBy('fecha_mes','desc')
                     ->when($sort_by,function($query) use ($sort_by){
                                     return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                 })
                     ->paginate($request->page_size);
    }

    return ['mensuales' => $mensual];
  }

  public function filtrarDiarios(Request $request){

    $reglas = array();
    $casinos = array();
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    if(!empty($request['id_casino']) || $request['id_casino'] != 0){
      $casinos[] = $request['id_casino'];
    }else{
      foreach ($user->casinos as $cass) {
        $casinos[]=$cass->id_casino;
      }
    }
    if(!empty($request['id_moneda']) || $request['id_moneda'] != 0){
      $reglas[]=['moneda.id_moneda','=',$request['id_moneda']];
    }

    if(!empty( $request->sort_by)){
      $sort_by = $request->sort_by;
    }else{

        $sort_by = ['columna' => 'importacion_diaria_mesas.fecha','orden'=>'desc'];
    }
    if(!empty($request['fecha']) || $request['fecha'] != 0){

      $fecha = explode('-',$request['fecha']);
      $diarios = DB::table('importacion_diaria_mesas')
                    ->join('casino','casino.id_casino','=','importacion_diaria_mesas.id_casino')
                    ->join('moneda','moneda.id_moneda','=','importacion_diaria_mesas.id_moneda')
                    ->where('importacion_diaria_mesas.validado','=',1)
                    ->where($reglas)
                    ->whereYear('fecha','=',$fecha[0])
                    ->whereMonth('fecha','=',$fecha[1])
                    ->whereDay('fecha','=',$fecha[2])
                    ->whereIn('casino.id_casino',$casinos)
                    ->where('importacion_diaria_mesas.id_moneda','=',1)
                    ->orderBy('fecha','desc')
                    ->when($sort_by,function($query) use ($sort_by){
                                    return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                })
                    ->paginate($request->page_size);
    }else{
    //  dd($casinos,$sort_by,$request->page_size);
      $diarios = DB::table('importacion_diaria_mesas')
                    ->join('casino','casino.id_casino','=','importacion_diaria_mesas.id_casino')
                    ->join('moneda','moneda.id_moneda','=','importacion_diaria_mesas.id_moneda')
                    ->where('importacion_diaria_mesas.validado','=',1)
                    ->where($reglas)
                    ->whereIn('casino.id_casino',$casinos)
                    ->where('importacion_diaria_mesas.id_moneda','=',1)
                    ->orderBy('fecha','desc')
                    ->when($sort_by,function($query) use ($sort_by){
                                    return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                })
                    ->paginate(5);
    }

   return ['diarios' => $diarios];
  }


}
