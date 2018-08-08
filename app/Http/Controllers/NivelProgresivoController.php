<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\NivelProgresivo;

class NivelProgresivoController extends Controller
{
  private static $instance;

  public static function getInstancia() {
      if (!isset(self::$instance)) {
          self::$instance = new NivelProgresivoController();
      }
      return self::$instance;
  }

  public function eliminarNivelProgresivo($id){
    //elimina solo nivel
    $nivel_progresivo = NivelProgresivo::destroy($id);
    return ['nivel_progresivo' => $nivel_progresivo];
  }

  public function guardarNivelProgresivo($niv,$id_progresivo){
    $nivel = new NivelProgresivo;
    $nivel->nro_nivel = $niv['nro_nivel'];
    $nivel->nombre_nivel = $niv['nombre_nivel'];
    $nivel->porc_oculto = ((empty($niv['porc_oculto']))?$niv['porc_oculto']:str_replace(",",".",$niv['porc_oculto']));
    $nivel->porc_visible = ((empty($niv['porc_visible']))?$niv['porc_visible']:str_replace(",",".",$niv['porc_visible']));
    $nivel->base = ((empty($niv['base']))?$niv['base']:str_replace(",",".",$niv['base']));
    $nivel->progresivo()->associate($id_progresivo);
    $nivel->save();
    return $nivel;
  }

  public function modificarNivelProgresivo($niv){
    $nivel = NivelProgresivo::find($niv['id_nivel_progresivo']);
    $nivel->nro_nivel = $niv['nro_nivel'];
    $nivel->nombre_nivel = $niv['nombre_nivel'];
    $nivel->porc_oculto = ((empty($niv['porc_oculto']))?$niv['porc_oculto']:str_replace(",",".",$niv['porc_oculto']));
    $nivel->porc_visible = ((empty($niv['porc_visible']))?$niv['porc_visible']:str_replace(",",".",$niv['porc_visible']));
    $nivel->base = ((empty($niv['base']))?$niv['base']:str_replace(",",".",$niv['base']));
    $nivel->save();
    return $nivel;
  }

}
