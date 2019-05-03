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
use App\Http\Controllers\RolesPermissions\RoleFinderController;

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
use App\Mesas\ApuestaMinimaJuego;
use App\Mesas\MinApInforme;
use App\Mesas\RelevamientoApuestas;
use Carbon\Carbon;
use App\Mesas\MesasSorteadas;
use App\Http\Controllers\Mesas\Cierres\ABMCCierreAperturaController;

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


  //desde ABMCCierreAperturaController // que es cuando se valida la apertura.
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
        //$this->calcularApRelevadas($informes->first(),$aperturas);
        //dd([count($informes) == 0 , [count($asociados) , count($aperturas)]]);
      //dd('nope',$informes,(count($asociados) == count($aperturas)));
      // NOTE: veo si esta creado, si no lo está: se crea, sino se revisa y se actualiza el estado
      if(count($informes) == 0 && (count($asociados) == count($aperturas))){
        //case: no está
        $abmcontroller = new ABMInformesFiscalizadoresController;
        $informe = $abmcontroller->crearInforme($cierre_apertura->casino, $cierre_apertura->fecha_produccion);
        //dd($informe);
        $this->updateInforme($informe,$aperturas);
        $this->calcularApRelevadas($informe,$aperturas);
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

    public function updateInforme($informe,$aperturas){
      $relevamientos_apuestas = RelevamientoApuestas::where('fecha','=',$informe->fecha)
                                                      ->where('id_casino','=',$informe->id_casino)
                                                      ->get();

      $controllerCA = new ABMCCierreAperturaController;
      $mesas_con_diferencia = json_encode($controllerCA->obtenerMesasConDiferencias($informe->fecha));

      $asociados_con_diferencias = CierreApertura::where('fecha_produccion','=',$informe->fecha)
                                   ->where('id_casino','=',$informe->id_casino)
                                   ->where('diferencias','=',1)
                                   ->get()->count();

      $cumplio_minimo =0;
      $ids_rels = array();
      foreach ($relevamientos_apuestas as $rel) {
        if($rel->cumplio_minimo){
          $cumplio_minimo = 1;
        }
        $ids_rels[] = $rel->id_relevamiento_apuestas;
      }
      $turnos_sin_minimo = DB::table('relevamiento_apuestas_mesas')
                                    ->select('nro_turno','id_estado_relevamiento')
                                    ->where('cumplio_minimo','=',0)
                                    ->where('fecha','=',$informe->fecha)
                                    ->where('es_backup','=',0)
                                    ->where('id_casino','=',$informe->id_casino)
                                    ->get();

      $cantidad_mesas_abiertas = DB::table('detalle_relevamiento_apuestas')
                                      ->select('id_mesa_de_panio')
                                      ->whereIn('id_relevamiento_apuestas',$ids_rels)
                                      ->where('id_estado_mesa','=',1)
                                      ->distinct('id_mesa_de_panio')
                                      ->get()->count();

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

      // $cantidad_con_minimo =  DB::table('detalle_relevamiento_apuestas')
      //                                 ->select('id_mesa_de_panio')
      //                                 ->whereIn('id_relevamiento_apuestas',$ids_rels)
      //                                 ->where('minimo','=',$informe->apuesta_minima->apuesta_minim)
      //                                 ->distinct('id_mesa_de_panio')
      //                                 ->get()->count();
      $cierres = Cierre::where('fecha','=',$informe->fecha)
                        ->where('id_casino','=',$informe->id_casino)
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


      $informe->cant_aperturas = count($aperturas);
      $informe->cant_cierres = count($cierres);
      $informe->cant_mesas_abiertas = $cantidad_mesas_abiertas;
      //$informe->cantidad_abiertas_con_minimo = $cantidad_con_minimo;
      $informe->cant_mesas_con_diferencia = $asociados_con_diferencias;
      if($mesas_con_diferencia == 'null'){
        $informe->mesas_con_diferencia = '[{}]';
      }
      else {
        $informe->mesas_con_diferencia = $mesas_con_diferencia;
      }
      $informe->mesas_relevadas_abiertas = json_encode($mesasRelevadasAbiertas);
      $informe->mesas_importadas_abiertas = json_encode($mesasImportadasAbiertas);
      $informe->save();
    }


    public function agregarRelacionValoresApuestas(RelevamientoApuestas $relevamiento){
      //si esta el informe creado ->
      //hay que contar la cantidad de mesas que cumplieron con el minimo de la moneda y asociarla con el minimo.
      $informe = InformeFiscalizadores::where('id_casino','=',$relevamiento->id_casino)
                                          ->where('fecha','=',$relevamiento->fecha)
                                          ->get()->first();

      if($informe != null){
        $minimos = ApuestaMinimaJuego::where('id_casino','=',$relevamiento->id_casino)
                                       ->get();
        foreach($minimos as $minimo){
          $detalles_relevamiento = DB::table('detalle_relevamiento_apuestas as DET')
                                        ->where('DET.id_juego_mesa','=',$minimo->id_juego_mesa)
                                        ->where('DET.id_moneda','=',$minimo->id_moneda)
                                        ->where('DET.minimo','=',$minimo->apuesta_minima)
                                        ->where('id_relevamiento_apuestas','=',$relevamiento->id_relevamiento_apuestas)
                                        ->get();

          $total = count($detalles_relevamiento);
          
          $minap = MinApInforme::where('id_informe_fiscalizadores','=',$informe->id_informe_fiscalizadores)
                                ->where('id_apuesta_minima_juego','=',$minimo->id_apuesta_minima_juego)
                                ->get()->first();
          if($minap == null) {
            MinApInforme::create([ 'id_apuesta_minima_juego' => $minimo->id_apuesta_minima_juego,
                                          'id_informe_fiscalizadores' => $informe->id_informe_fiscalizadores,
                                          'cantidad_cumplieron' => $total,
                                      ]);
          }
          else {
            $minap->cantidad_cumplieron = $total;
          }
        }
      }
    }

    public function calcularApRelevadas($informe,$aperturas)
    {
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
          $informe->aperturas_sorteadas = round(($coinciden * 100)/$aperturas->count(),2);
          $informe->save();
          //dd($informe);
          $sorteadas->delete();
        }
    }

  }
