<?php

namespace App\Http\Controllers\Mesas\Canon;

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
use Carbon\Carbon;
use App\SecRecientes;
use App\Http\Controllers\RolesPermissions\RoleFinderController;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\Mesas\Mesa;
use App\Mesas\Moneda;
use App\Mesas\ImagenesBunker;
use App\Mesas\DetalleImgBunker;
use App\Mesas\Cierre;
use App\Mesas\ImportacionDiariaMesas;
use App\Mesas\InformeFinalMesas;
use App\Mesas\DetalleInformeFinalMesas;
use App\Http\Controllers\UsuarioController;


class BPagosController extends Controller
{
  private static $atributos = [

  ];

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware(['tiene_permiso:m_b_pagos']);
  }

  public function filtros(Request $request){
    $user =UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = array();

    $filtros = array();

    if(!empty($request->id_casino) && $request->id_casino != 0){
      $cas[]= $request->id_casino;
    }else{
      foreach ($user->casinos as $cass) {
        $cas[]=$cass->id_casino;
      }
    }
    if(!empty($request->mes) && $request->mes != 0){
      $filtros[] = ['mes_casino.nro_mes','=',$request->mes];
    }
    if(!empty( $request->sort_by)){
      $sort_by = $request->sort_by;
    }else{
        $sort_by = ['columna' => 'DIFM.fecha_cobro','orden','desc'];
    }
    //dd($filtros);
    if(empty($request->fecha)){
      $resultados = DB::table('detalle_informe_final_mesas as DIFM')
                        ->join('casino','casino.id_casino','=','DIFM.id_casino')
                        ->join('mes_casino','mes_casino.id_mes_casino','=','DIFM.id_mes_casino')
                        ->where($filtros)
                        ->whereIn('DIFM.id_casino',$cas)
                        ->when($sort_by,function($query) use ($sort_by){
                                        return $query->orderBy($sort_by['columna'],
                                        $sort_by['orden']);
                                    })
                        ->paginate($request->page_size);
    }else{
      $fecha=explode("-", $request->fecha);
      $resultados = DB::table('detalle_informe_final_mesas as DIFM')
                        ->join('casino','casino.id_casino','=','DIFM.id_casino')
                        ->join('mes_casino','mes_casino.id_mes_casino','=','DIFM.id_mes_casino')
                        ->where($filtros)
                        ->whereYear('DIFM.fecha_cobro', '=', $fecha[0])
                        ->whereMonth('DIFM.fecha_cobro','=', $fecha[1])
                        ->whereIn('DIFM.id_casino',$cas)
                        ->when($sort_by,function($query) use ($sort_by){
                                        return $query->orderBy($sort_by['columna'],
                                        $sort_by['orden']);
                                    })
                        ->paginate($request->page_size);
    }
    return ['pagos' => $resultados];
  }


  public function verInformeFinalMesas(Request $request){

    //siempre manda la tablita de como quedó/como se calculó el ultimo canon
    //hay 3 opciones. el periodo que busca es el actual. => manda el mismo informe (a)
    //los anios a comparar son consecutivos -> 1 solo informe_final_mesas (b)
    //son na ke ve (c)

    $ultimo_informe = InformeFinalMesas::where('id_casino','=',$request->id_casino)
                                    ->where('anio_inicio','=',date('Y')-1)
                                    ->first();
    $iii= $request->anio_inicio;
    if($iii+1 == $request->anio_final && $request->anio_inicio != date('Y')-1){ //(a)
      //busco 1 solo informe_final_mesas
    //  dd('uyi');
      $informe = InformeFinalMesas::where('id_casino','=',$request->id_casino)
                                      ->where('anio_inicio','=',$request->anio_inicio)
                                      ->where('anio_final','=',$request->anio_final)
                                      ->first();
      if($informe == null){
        return response()->json(['error' => 'INFORME NO ENCONTRADO'], 404);
      }else{
        return response()->json(['ultimo_informe'=>$ultimo_informe,
                'informe' => $informe,
                'detalles' => $informe->detalles->sortBy('nro_cuota')
              ], 200);
      }
    }else{

      if($request->anio_inicio++ != $request->anio_final && $request->anio_inicio != date('Y')-1){//(c)
        //dd('fdh');
        //busco dos y creo un nuevo informe_final_mesas
        $primero = InformeFinalMesas::where('id_casino','=',$request->id_casino)
                                        ->where('anio_inicio','=',$request->anio_inicio)
                                        ->first();

        $segundo = InformeFinalMesas::where('id_casino','=',$request->id_casino)
                                        ->where('anio_inicio','=',$request->anio_final)
                                        ->first();

        if($primero != null && $segundo != null){//creo el informe a partir de esos datos..
          $informe = $this->crearComparado($primero,$segundo);

          return response()->json(['ultimo_informe'=>$ultimo_informe,
                  'informe' => $informe[0],
                  'detalles' => $informe[1] //son los detalles
                ], 200);
        }else{
            return response()->json(['error' => 'INFORME NO ENCONTRADO'], 404);
        }
      }else{ //(b)

        if($ultimo_informe != null){
          return response()->json(['ultimo_informe'=>$ultimo_informe,
                  'informe' => $ultimo_informe,
                  'detalles' => $ultimo_informe->detalles->sortBy('nro_cuota')
                ], 200);
        }else{
          return response()->json(['error' => 'INFORME NO ENCONTRADO'], 404);
        }
      }
    }
    return response()->json(['error' => 'FATALITY'], 404);
  }


  public function crearComparado($primero,$segundo){
    $new = new InformeFinalMesas;
    $new->id_casino = $primero->id_casino;
    $new->anio_inicio = $primero->anio_inicio;
    $new->anio_final = $segundo->anio_inicio;
    $new->base_anterior_dolar = $primero->base_actual_dolar;
    $new->base_anterior_euro = $primero->base_actual_euro;
    $new->base_actual_dolar = $segundo->base_actual_dolar;
    $new->base_actual_euro = $segundo->base_actual_euro;
    $new->base_cobrado_dolar = 0;
    $new->base_cobrado_euro = 0;

    $detalles = array();

    foreach ($segundo->detalles as $dd) {
      $d = $this->buscarParaElMismoMes($dd->id_mes_casino ,$primero);
      $newdet = new DetalleInformeFinalMesas;
      $newdet->total_pagado = 0;
      $newdet->impuestos = 0;
      $newdet->fecha_cobro = '1999-01-01';
      $newdet->total_mes_anio_anterior = $d->total_mes_actual;
      $newdet->total_mes_actual = $dd->total_mes_actual;
      $newdet->cotizacion_euro_anterior = $d->cotizacion_euro_actual;
      $newdet->cotizacion_dolar_actual = $dd->cotizacion_dolar_actual;
      $newdet->cotizacion_euro_actual = $dd->cotizacion_euro_actual;
      $newdet->cotizacion_dolar_anterior = $d->cotizacion_dolar_actual;
      $newdet->id_casino = $primero->id_casino;
      $newdet->id_mes_casino = $d->id_mes_casino;

      $detalles[] = $newdet;//si hay error ->pasarlo a collection.-
    }

    $rta = [$new,$detalles];
    return $rta;

  }

  private function buscarParaElMismoMes($id_mes_casino ,$primero){
    foreach ($primero->detalles as $dd) {
      if($dd->id_mes_casino == $id_mes_casino){
        return $dd;
      }
    }
  }

  public function obtenerPago($id_detalle){
    $detalle = DetalleInformeFinalMesas::find($id_detalle);
    $casino = Casino::find($detalle->id_casino);
    return response()->json([ 'detalle' => $detalle,'casino'=>$casino, 'informe' => $detalle->informe_final_mesas], 200);
  }

  public function obtenerAnios($id_casino){
    $anios = DB::table('informe_final_mesas')
                        ->select('informe_final_mesas.anio_inicio',
                                  'informe_final_mesas.anio_final'
                              )
                        ->where('id_casino','=',$id_casino)
                        ->get();
    return response()->json([ 'anios' => $anios], 200);
  }





}
