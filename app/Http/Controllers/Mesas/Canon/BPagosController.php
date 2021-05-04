<?php

namespace App\Http\Controllers\Mesas\Canon;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Casino;
use App\Mesas\InformeFinalMesas;
use App\Mesas\DetalleInformeFinalMesas;
use App\Http\Controllers\UsuarioController;

class BPagosController extends Controller{
  public function __construct(){
    $this->middleware(['tiene_permiso:m_b_pagos']);
  }

  public function filtros(Request $request){
    $user =UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = array();
    foreach ($user->casinos as $cass) {
      $cas[]=$cass->id_casino;
    }

    $filtros = array();
    if(!empty($request->id_casino) && $request->id_casino != 0){
      $filtros[] = ['DIFM.id_casino','=',$request->id_casino];
    }
    if(!empty($request->mes) && $request->mes != 0){
      $filtros[] = ['DIFM.mes','=',$request->mes];
    }

    $resultados = DB::table('detalle_informe_final_mesas as DIFM')
    ->join('casino','casino.id_casino','=','DIFM.id_casino')
    ->where($filtros)
    ->whereIn('DIFM.id_casino',$cas)
    ->whereNull('DIFM.deleted_at');

    if(!empty($request->fecha)){
      $fecha = explode("-", $request->fecha);
      $resultados = $resultados->whereYear('DIFM.fecha_cobro', '=', $fecha[0])
      ->whereMonth('DIFM.fecha_cobro','=', $fecha[1]);
    }

    
    if(!empty($request->sort_by)){
      $sort_by = $request->sort_by;
      $resultados = $resultados->orderBy($sort_by['columna'],$sort_by['orden']);
    }
    else{
      $resultados = $resultados->orderByRaw('DIFM.anio DESC,DIFM.mes DESC,DIFM.dia_inicio DESC');
    }
    
    return ['pagos' => $resultados->paginate($request->page_size)];
  }

  public function verInformeFinalMesas(Request $request){
    $informe = InformeFinalMesas::where('id_casino','=',$request->id_casino)
    ->where('anio_inicio','=',$request->anio_inicio)->whereNull('deleted_at')->first();

    $informe_anterior = InformeFinalMesas::where('id_casino','=',$request->id_casino)
    ->where('anio_inicio','=',$request->anio_inicio-1)->whereNull('deleted_at')->first();

    if($informe == null) return response()->json(['error' => 'INFORME NO ENCONTRADO'], 404);
  
    return ['informe_anterior'  => $informe_anterior,
            'informe'           => $informe,
            'detalles_anterior' => $informe_anterior->detalles()->orderByRaw('anio ASC,mes ASC,dia_inicio ASC')->get(),
            'detalles'          => $informe->detalles()->orderByRaw('anio ASC,mes ASC,dia_inicio ASC')->get()];
  }

  public function obtenerPago($id_detalle){
    $detalle = DetalleInformeFinalMesas::find($id_detalle);
    $casino = Casino::find($detalle->id_casino);
    return response()->json([ 'detalle' => $detalle,'casino'=>$casino, 'informe' => $detalle->informe_final_mesas], 200);
  }

  public function obtenerAnios($id_casino){
    $anios = DB::table('informe_final_mesas as ifm')->select('ifm.anio_inicio', 'ifm.anio_final')
    //join para eliminar anios sin meses cargados
    ->join('detalle_informe_final_mesas as difm','difm.id_informe_final_mesas','=','ifm.id_informe_final_mesas')
    ->where('ifm.id_casino','=',$id_casino)->distinct()->get();
    return ['anios' => $anios];
  }
}
