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

use Exception;
use App\Http\Controllers\UsuarioController;

class InformesController extends Controller
{
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
                     ->where('importacion_mensual_mesas.nombre_csv','not like','no matter.-')
                     ->whereYear('fecha_mes','=',$fecha[0])
                     ->whereMonth('fecha_mes','=',$nro_mes)
                     ->whereIn('casino.id_casino',$casinos)
                     ->where('importacion_mensual_mesas.id_moneda','=',1)
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
                     ->where('importacion_mensual_mesas.nombre_csv','not like','no matter.-')
                     ->whereIn('casino.id_casino',$casinos)
                     ->when($sort_by,function($query) use ($sort_by){
                                     return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                 })
                     ->paginate($request->page_size);
    }

    return ['mensuales' => $mensual];
  }
}
