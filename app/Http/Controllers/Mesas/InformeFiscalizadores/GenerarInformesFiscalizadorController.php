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
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use App\Usuario;
use App\Casino;
use App\SecRecientes;
use App\Http\Controllers\UsuarioController;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Dompdf\Dompdf;

use PDF;
use App\Mesas\Mesa;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\CierreApertura;
use App\Mesas\InformeFiscalizadores;
use App\Mesas\Cierre;
use App\Mesas\Apertura;
use App\Mesas\DetalleApertura;
use App\Mesas\EstadoCierre;

use App\Mesas\RelevamientoApuestas;
use Carbon\Carbon;

use Exception;

class GenerarInformesFiscalizadorController extends Controller
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

  public function iniciarInformeDiario($cierre_apertura){
    $aperturas = Apertura::where('fecha','=',$cierre_apertura->fecha_produccion)
                            ->where('id_estado_cierre','=',3)//visado
                            ->get();
    $asociados = CierreApertura::where('fecha_produccion','=',$cierre_apertura->fecha_produccion)
                                 ->where('id_casino','=',$cierre_apertura->id_casino)
                                 ->get();

    $informes = InformeFiscalizadores::where('id_casino','=',$cierre_apertura->id_casino)
                                        ->where('fecha','=',$cierre_apertura->fecha_produccion)
                                        ->get();

    //dd([count($informes) == 0 , [count($asociados) , count($aperturas)]]);
    //dd($informes,(count($asociados) == count($aperturas)));
    // NOTE: veo si esta creado, si no lo está: se crea, sino se revisa y se actualiza el estado
    if(count($informes) == 0 && (count($asociados) == count($aperturas))){
      //case: no está
      $abmcontroller = new ABMInformesFiscalizadoresController;
      $informe = $abmcontroller->crearInforme($cierre_apertura->casino, $cierre_apertura->fecha_produccion);
      //dd($informe);
      $this->updateInforme($informe,$aperturas);
    }else{
      //case está
      $informe = $informes->first();
      if($informe != null){
        if((count($asociados) == count($aperturas)) &&
            $informe->pendiente == 1){
          $informe->pendiente = 0;// finalizado
          $this->updateInforme($informe,$aperturas);
        }else{
          $informe->pendiente = 1;
        }
        $informe->save();
      }else{
        $abmcontroller = new ABMInformesFiscalizadoresController;
        $informe = $abmcontroller->crearInforme($cierre_apertura->casino, $cierre_apertura->fecha_produccion);
        //dd($informe);
        $this->updateInforme($informe,$aperturas);
      }
    }
  }

  private function updateInforme($informe,$aperturas){
    $relevamientos_apuestas = RelevamientoApuestas::where('fecha','=',$informe->fecha)
                                                    ->where('id_casino','=',$informe->id_casino)
                                                    ->get();

    $asociados_con_diferencias = CierreApertura::where('fecha_produccion','=',$informe->fecha)
                                 ->where('id_casino','=',$informe->id_casino)
                                 ->where('diferencias','=',1)
                                 ->count();

    $cumplio_minimo =0;
    $ids_rels = array();
    foreach ($relevamientos_apuestas as $rel) {
      if($rel->cumplio_minimo){
        $cumplio_minimo = 1;
      }
      $ids_rels[] = $rel->id_relevamiento_apuestas;
    }

    $cantidad_mesas_abiertas = DB::table('detalle_relevamiento_apuestas')
                                    ->select('id_mesa_de_panio')
                                    ->whereIn('id_relevamiento_apuestas',$ids_rels)
                                    ->where('id_estado_mesa','=',1)
                                    ->distinct('id_mesa_de_panio')
                                    ->count();

    $cantidad_con_minimo =  DB::table('detalle_relevamiento_apuestas')
                                    ->select('id_mesa_de_panio')
                                    ->whereIn('id_relevamiento_apuestas',$ids_rels)
                                    ->where('minimo','=',$informe->apuesta_minima->apuesta_minima)
                                    ->distinct('id_mesa_de_panio')
                                    ->count();
    $cierres = Cierre::where('fecha','=',$informe->fecha)
                      ->where('id_casino','=',$informe->id_casino)
                      ->get();

    $informe->cant_aperturas = count($aperturas);
    $informe->cant_cierres = count($cierres);
    $informe->cant_mesas_abiertas = $cantidad_mesas_abiertas;
    $informe->cantidad_abiertas_con_minimo = $cantidad_con_minimo;
    $informe->cant_mesas_con_diferencia = $asociados_con_diferencias;

    $informe->save();
  }


}


// public function realizarCalculoDiario(){
//
//   $casinos= Casino::all();
//   foreach ($casinos as $casino) {
//     // creo el informe de hoy - si no existe todavía
//
//     //reviso si los anteriores estan finalizados o necesitan actualizarse
//     $pendientes = InformeFiscalizadores::whereIn('id_estado_fiscalizacion',[1,3])->get();
//
//     foreach ($pendientes as $informe) {
//       $this->recalcular($informe);
//     }
//     //por cada uuno de los que obtuve de recien
//     //revisar si todas las aperturass cargas estan validadas para la FECHA
//
//     //empezar a sacar calculos
//
//
//     //meanwhile en la carga de aperturas debo chequear que si se cargó alguna
//     //más y el estado del informe es finalizado -> estado pasa a recalculo
//     //sino sigue en pendiente.
//
//
//   }
// }
//
//
//
// public function recalcular($informe){
//   //tener en cuenta la hora de cierre del dia
//   $aperturas = Apertura::where('fecha','=',$informe->fecha)//falta el where de hora
//                           ->where('id_estado_cierre','=',X)
//                           ->get();
//   $cierres = Cierre::where()->get();
//   //A CIERRE APERTURA AGREGARLE LA FECHA DEL CIERRE Y DE LA AP y si tuvo diferencias
//   $cierres_aperturas = CierreApertura::where('fecha_cierre','=',$informe->fecha)->get();
//   if(count($cierres_aperturas) == count($aperturas)){
//     $informe->estado()->associate(2);// finalizado
//     $informe->cant_aperturas = count($aperturas);
//     $informe->cant_cierres = count($cierres);
//   }else{
//     $informe->estado()->associate(1);
//   }
//   $informe->save();
// }
