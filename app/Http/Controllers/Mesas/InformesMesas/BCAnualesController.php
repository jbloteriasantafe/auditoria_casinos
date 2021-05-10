<?php

namespace App\Http\Controllers\Mesas\InformesMesas;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Mesas\Mesa;

class BCAnualesController extends Controller
{
  public function buscarPorAnioCasinoMoneda(Request $request){
    $validator=  Validator::make($request->all(),[
      'anio' => 'required',
      'id_casino' => 'required|exists:casino,id_casino',
      'id_moneda' => 'required|exists:moneda,id_moneda',
      'id_casino2' => 'nullable|exists:casino,id_casino',
      'id_moneda2' => 'nullable|exists:moneda,id_moneda',
    ], [], [])->after(function($validator){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $id_casino  = $data['id_casino'];
      $id_moneda  = $data['id_moneda'];
      $id_casino2 = $data['id_casino2'];
      $id_moneda2 = $data['id_moneda2'];

      if(((!empty($id_casino2) && empty($id_moneda2))) || (!empty($id_casino2) && empty($id_moneda2))){
        $validator->errors()->add('id_casino2','Seleccione un casino Y una moneda.');
        $validator->errors()->add('id_moneda2','Seleccione un casino Y una moneda.');
        return;
      }

      if($id_casino == $id_casino2 && $id_moneda == $id_moneda2){
        return $validator->errors()->add('id_casino2','Elija otro casino y/o otra moneda.');
      }

      $sin_mesas = Mesa::where([['id_moneda','=',$id_moneda],['id_casino','=',$id_casino]])
      ->orWhere('multimoneda','=',1)->get()->count() == 0;
      if($sin_mesas){
        $validator->errors()->add('id_moneda','No existen mesas para la moneda y casino seleccionado.');
      }

      if(!empty($id_moneda2)){
        $sin_mesas = Mesa::where([['id_moneda','=',$id_moneda2],['id_casino','=',$id_casino2]])
        ->orWhere('multimoneda','=',1)->get()->count() == 0;
        if($sin_mesas){
          $validator->errors()->add('id_moneda2','No existen mesas para la moneda seleccionada.');
          return;
        }  
      }
    })->validate();

    $casino1 = DB::table('importacion_diaria_mesas')
    ->selectRaw('YEAR(fecha) as anio,MONTH(fecha) as mes,SUM(utilidad) as total_utilidad_mensual')
    ->whereYear('fecha',$request->anio)
    ->groupBy(DB::raw('YEAR(fecha),MONTH(fecha)'))
    ->orderBy(DB::raw('MONTH(fecha)'), 'ASC')
    ->whereNull('deleted_at');

    $casino2 = clone $casino1;
    $casino1 = $casino1->where('id_casino','=',$request->id_casino)->where('id_moneda','=',$request->id_moneda)->get();

    if(!empty($request->id_casino2)){
      $casino2 = $casino2->where('id_casino','=',$request->id_casino2)->where('id_moneda','=',$request->id_moneda2)->get();
    }
    else{
      $casino2 = [];
    }

    return ['casino1' => $casino1,'casino2' => $casino2];
  }
}
