<?php

namespace App\Http\Controllers\Bingo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class InformeContoller extends Controller
{
    public function generarPlanilla($mm, $aaaa, $id_casino){

    $sumarecaudado = $this->sumarRecaudado($mm, $aaaa, $id_casino);
    $sumapremiolinea = $this->sumarPremioLinea($mm, $aaaa, $id_casino);
    $sumapremiobingo = $this->sumarPremioBingo($mm, $aaaa, $id_casino);

    $beneficio = $sumarecaudado - ($sumapremiolinea + $sumapremiobingo);
      

    }

    protected function sumarRecaudado($mm, $aaaa, $id_casino){
      $resultado = DB::table('bingo_importacion')
                      ->select('fecha','recaudado')
                      ->where('bingo_importacion.id_casino','=',$id_casino)
                      ->whereYear('fecha','=',$aaaa)
                      ->whereMonth('fecha','=',$mm)
                      ->orderBy('fecha','asc')
                      ->get();

                      return $reusltado;
    }

    protected function sumarPremioLinea($mm, $aaaa, $id_casino){
      $resultado = DB::table('bingo_importacion')
                      ->select('fecha','premio_linea')
                      ->where('bingo_importacion.id_casino','=',$id_casino)
                      ->whereYear('fecha','=',$aaaa)
                      ->whereMonth('fecha','=',$mm)
                      ->orderBy('fecha','asc')
                      ->get();

                      return $reusltado;
    }

    protected function sumarPremioBingo($mm, $aaaa, $id_casino){
      $resultado = DB::table('bingo_importacion')
                      ->select('fecha','premio_bingo')
                      ->where('bingo_importacion.id_casino','=',$id_casino)
                      ->whereYear('fecha','=',$aaaa)
                      ->whereMonth('fecha','=',$mm)
                      ->orderBy('fecha','asc')
                      ->get();

                      return $reusltado;
    }


}
