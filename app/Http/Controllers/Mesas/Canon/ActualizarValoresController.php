<?php

namespace App\Http\Controllers\Canon;

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
use App\Http\Controllers\UsuarioController;

use App\Usuario;
use App\Casino;
use Carbon\Carbon;
use App\SecRecientes;
use App\Http\Controllers\RolesPermissions\RoleFinderController;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\Mesas\Mesa;
use App\Mesas\Canon;

use App\Mesas\Moneda;
use App\Mesas\ImagenesBunker;
use App\Mesas\DetalleImgBunker;
use App\Mesas\Cierre;
use App\Mesas\ImportacionDiariaMesas;
use App\Mesas\InformeFinalMesas;
use App\Mesas\DetalleInformeFinalMesas;

//validacion de cierres
class ActualizarValoresController extends Controller
{
  private static $atributos = [
    'id_cierre_mesa' => 'Identificacion del Cierre',
    'fecha' => 'Fecha',
    'hora_inicio' => 'Hora de Apertura',
    'hora_fin' => 'Hora del Cierre',
    'total_pesos_fichas_c' => 'Total de pesos en Fichas',
    'total_anticipos_c' => 'Total de Anticipos',
    'id_fiscalizador'=>'Fiscalizador',
    'id_mesa_de_panio'=> 'Mesa de Paño',
    'id_estado_cierre'=>'Estado',
  ];

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
      $this->middleware(['tiene_permiso:m_actualizar_canon']);
  }

  public function verRequisitos($id_casino){
    $casino = Casino::find($id_casino);
    $anio_hoy = date('Y');
    $mes_hoy = date('m');
    $informes = InformeFinalMesas::where('anio_inicio','=',$anio_hoy)
                                    ->where('id_casino','=',$casino->id)
                                    ->get();
    $fecha_inicio = explode('-',$casino->fecha_inicio);
    if(count($informes)==0 && $fecha_inicio[1]==$mes_hoy){

      $importacionesSinValidar = ImportacionMensualMesas::where('id_casino','=',$id_casino)
                                                        ->whereYear('fecha','=',$anio_hoy)
                                                        ->whereMonth('fecha','>=',$fecha_inicio[1])
                                                        ->where('validado','=',0)
                                                        ->get();

      $importacionesSinValid = ImportacionMensualMesas::where('id_casino','=',$id_casino)
                                              ->whereYear('fecha','=',$anio_hoy-1)
                                              ->whereMonth('fecha','<=',$fecha_inicio[1])
                                              ->where('validado','=',0)
                                              ->get();
      if(count($importacionesSinValid)==0 && count($importacionesSinValidar)==0){
        return ['ok' =>'Calculo autorizado.'];
      }
    }else{
      return ['ERROR' =>'Ya se calculó el canon para el corriente periódo.','ok'=>0];
    }
  }


  public function actualizarValores($id_casino){
    $casino = Casino::find($id_casino);
    $meses = $casino->meses;
    $informe = InformeFinalMesas::where('id_casino','=',$id_casino)
                                  ->where('anio_final','=',date('Y'))
                                  ->first();
    $yaEstaCreado = InformeFinalMesas::where('id_casino','=',$id_casino)
                                  ->where('anio_inicio','=',date('Y'))
                                  ->where('anio_final','=',date('Y')+1)
                                  ->first();
    $ok = $this->verRequisitos($id_casino)['ok'];
    if($yaEstaCreado == null && (count($meses) != count($informe->detalles) || $ok == 0)){
      return response()->json([ 'ERROR' => 'No es posible actualizar los valores del canon, aún quedan meses sin pagar.'], 401);
    }
    else{
      $fecha_inicio = explode('-',$casino->fecha_inicio);
      $anio_hoy = date('Y');
      //obtengo el informe anterior
      if($fecha_inicio[0]+1 != date('Y')){
          $informeAnterior = InformeFinalMesas::where('anio_final','=',$anio_hoy)
                                      ->where('id_casino','=',$id_casino)
                                      ->get();
      }else{
        $informeAnterior = [
                            'base_actual_dolar' =>0,
                            'base_actual_euro' => 0,
                            'monto_actual_euro' => 0,
                            'monto_actual_dolar' => 0,
                            'monto_actual_euro' => 0,
                            'detalles' => null,
                            'variacion_total_euro' => 0,
                            'variacion_total_dolar' => 0,
                          ];
      }
      $informeNuevo = new InformeFinalMesas;
      $informeNuevo->anio_inicio = date('Y');
      $informeNuevo->anio_final = date('Y')+1;
      $nueva_base_euro =  (($informeAnterior->variacion_total_euro + 100)/100)*$informeAnterior->base_actual_euro;
      $nueva_base_dolar = (($informeAnterior->variacion_total_dolar + 100)/100)*$informeAnterior->base_actual_dolar;

      $informeNuevo->base_actual_dolar = $nueva_base_dolar;
      $informeNuevo->base_anterior_dolar = $informeAnterior->base_actual_dolar;

      $informeNuevo->base_actual_euro = $nueva_base_euro;
      $informeNuevo->base_anterior_euro = $informeAnterior->base_actual_euro;

      //defino las nuevas bases
      switch ($informeAnterior) {
        case ($nueva_base_dolar > $informeAnterior->base_cobrado_dolar):
          $informeNuevo->base_cobrado_dolar = $nueva_base_dolar;
          break;

        default:
          $informeNuevo->base_cobrado_dolar = $informeAnterior->base_cobrado_dolar;
          break;
      }
      switch ($informeAnterior) {
        case ($nueva_base_euro > $informeAnterior->base_cobrado_euro):
          $informeNuevo->base_cobrado_euro = $nueva_base_euro;
          break;

        default:
          $informeNuevo->base_cobrado_euro = $informeAnterior->base_cobrado_euro;
          break;
      }
      $informeNuevo->casino()->associate($id_casino);
      $informeNuevo->save();

      //actualizar valor del canon
      $canon = $this->actualizarValoresCanon($informeNuevo);

      return response()->json([ 'canon' => $canon,
                                'informeNuevo' => $informeNuevo,
                                'informeAnterior' => $informeAnterior
                              ], 200);
    }

  }

  public function actualizarValoresCanon($informeNuevo){

    $canon = Canon::where('id_casino','=',$informeNuevo->id_casino)
                    ->get()->first();
    $canon->delete();
    $nuevo_canon = new Canon;
    $nuevo_canon->id_casino= $informeNuevo->id_casino;
    $nuevo_canon->periodo_anio_inicio = $informeNuevo->anio_inicio;
    $nuevo_canon->periodo_anio_fin= $informeNuevo->anio_final;
    $nuevo_canon->valor_base_dolar = $informeNuevo->base_cobrado_dolar;
    $nuevo_canon->valor_base_euro = $informeNuevo->base_cobrado_euro;
    $nuevo_canon->valor_real_dolar = $informeNuevo->base_actual_dolar;
    $nuevo_canon->valor_real_euro = $informeNuevo->base_actual_euro;
    $nuevo_canon->save();
    return $nuevo_canon;
  }
  public function crearCanon($informeNuevo){

    $nuevo_canon = new Canon;
    $nuevo_canon->id_casino= $informeNuevo->id_casino;
    $nuevo_canon->periodo_anio_inicio = $informeNuevo->anio_inicio;
    $nuevo_canon->periodo_anio_fin= $informeNuevo->anio_final;
    $nuevo_canon->valor_base_dolar = $informeNuevo->base_cobrado_dolar;
    $nuevo_canon->valor_base_euro = $informeNuevo->base_cobrado_euro;
    $nuevo_canon->valor_real_dolar = $informeNuevo->base_actual_dolar;
    $nuevo_canon->valor_real_euro = $informeNuevo->base_actual_euro;
    $nuevo_canon->save();
    $nuevo_canon->delete();
    return $nuevo_canon;
  }


  public function forzarActualizacion($id_casino,$anio_final){

    $casino = Casino::find($id_casino);
    $meses = $casino->meses;
    $yaEstaCreado = InformeFinalMesas::where('id_casino','=',$id_casino)
                                  ->where('anio_inicio','=',$anio_final)
                                  ->where('anio_final','=',$anio_final+1)
                                  ->first();
                                  //dd($yaEstaCreado,$id_casino,date('Y'),date('Y')+1);
    $ok = $this->verRequisitos($id_casino)['ok'];
    if($yaEstaCreado == null){
      $informe = InformeFinalMesas::where('id_casino','=',$id_casino)
                                    ->where('anio_final','=',$anio_final)
                                    ->first();
        //obtengo el informe anterior
        $informeAnterior = InformeFinalMesas::where('anio_final','=',$anio_final)
                                    ->where('id_casino','=',$id_casino)
                                    ->get()->first();
        if($informeAnterior == null){
          $informeAnterior = [
                              'base_actual_dolar' =>0,
                              'base_actual_euro' => 0,
                              'monto_actual_euro' => 0,
                              'monto_actual_dolar' => 0,
                              'monto_actual_euro' => 0,
                              'detalles' => null,
                              'variacion_total_euro' => 0,
                              'variacion_total_dolar' => 0,
                            ];
        }
        $informeNuevo = new InformeFinalMesas;
        $informeNuevo->anio_inicio = $anio_final;
        $informeNuevo->anio_final = $anio_final+1;
        $nueva_base_euro =  (($informeAnterior->variacion_total_euro + 100)/100)*$informeAnterior->base_actual_euro;
        $nueva_base_dolar = (($informeAnterior->variacion_total_dolar + 100)/100)*$informeAnterior->base_actual_dolar;

        $informeNuevo->base_actual_dolar = $nueva_base_dolar;
        $informeNuevo->base_anterior_dolar = $informeAnterior->base_actual_dolar;

        $informeNuevo->base_actual_euro = $nueva_base_euro;
        $informeNuevo->base_anterior_euro = $informeAnterior->base_actual_euro;

        //defino las nuevas bases
        switch ($informeAnterior) {
          case ($nueva_base_dolar > $informeAnterior->base_cobrado_dolar):
            $informeNuevo->base_cobrado_dolar = $nueva_base_dolar;
            break;

          default:
            $informeNuevo->base_cobrado_dolar = $informeAnterior->base_cobrado_dolar;
            break;
        }
        switch ($informeAnterior) {
          case ($nueva_base_euro > $informeAnterior->base_cobrado_euro):
            $informeNuevo->base_cobrado_euro = $nueva_base_euro;
            break;

          default:
            $informeNuevo->base_cobrado_euro = $informeAnterior->base_cobrado_euro;
            break;
        }
        $informeNuevo->casino()->associate($id_casino);
        $informeNuevo->save();

        //actualizar valor del canon
        $canon = $this->crearCanon($informeNuevo);

        return response()->json([ 'canon' => $canon,
        'informeNuevo' => $informeNuevo,
        'informeAnterior' => $informeAnterior], 200);

    }
    else {
      $canon = Canon::where('periodo_anio_fin','=',$yaEstaCreado->anio_final)
                      ->where('periodo_anio_inicio','=',$yaEstaCreado->anio_inicio)
                      ->where('id_casino','=',$id_casino)
                      ->withTrashed()
                      ->get()->first();
      $informeAnterior = InformeFinalMesas::where('anio_final','=',$anio_final)
                                  ->where('id_casino','=',$id_casino)
                                  ->get()->first();
      return response()->json([ 'canon' => $canon,
      'informeNuevo' => $yaEstaCreado,
      'informeAnterior' => $informeAnterior], 200);
    }
  }
}
