<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use Response;
use View;
use DateTime;
use Dompdf\Dompdf;

use PDF;
use App\Notifications\NuevoMovimiento;
use App\Notifications\RelevamientoCargado;
use App\Notifications\RelevamientoGenerado;
use App\Notifications\NuevaIntervencionMtm;
use App\Usuario;
use App\LogMovimiento;
use App\Nota;
use App\Expediente;
use App\RelevamientoMovimiento;
use App\EstadoMovimiento;
use App\TomaRelevamientoMovimiento;
use App\TipoMovimiento;
use App\TipoMoneda;
use App\FiscalizacionMov;
use App\Formula;
use App\TipoGabinete;
use App\UnidadMedida;
use App\Maquina;
use App\TipoProgresivo;
use App\Casino;
use App\Isla;
use App\Juego;
use App\TipoMaquina;
use App\EstadoMaquina;


class ModificarTomaController extends Controller
{
  private static $instance;

  public static function getInstancia(){
    if (!isset(self::$instance)) {
      self::$instance = new ModificarTomaController();
    }
    return self::$instance;
  }

  public function modificarTomaRelevada(){
    $validator =Validator::make($request->all(), [
        'id_fiscalizacion_movimiento' => 'required|exists:fiscalizacion_movimiento,id_fiscalizacion_movimiento',
        'id_cargador' => 'nullable|exists:usuario,id_usuario',
        'id_fiscalizador' => 'required|exists:usuario,id_usuario',
        'id_maquina' => 'required|exists:maquina,id_maquina',
        'id_relevamiento' => 'nullable|exists:relevamiento_movimiento,id_relev_mov',
        'contadores' => 'required',
        'contadores.*.nombre' =>'nullable',
        'contadores.*.valor' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'contadores.*.check' => 'nullable',
        //'juego' => 'required |exists: juego, id_juego',
        'juego' => 'required',
        'apuesta_max' => 'required| numeric| max:900000',
        'cant_lineas' => 'required|numeric| max:100000',
        'porcentaje_devolucion' => ['required','regex:/^\d\d?([,|.]\d\d?\d?)?$/'],
        'denominacion' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'cant_creditos' => 'required|numeric| max:100',
        'fecha_sala' => 'required|date',//fecha con dia y hora
        'observaciones' => 'nullable|max:280',
        'mac' => 'nullable | max:100'
    ], array(), self::$atributos)->after(function($validator){
      if($validator->getData()['juego']==0 ){
          $validator->errors()->add('juego', 'No se ha seleccionado el juego.');
      }
      $maquina = Maquina::find($validator->getData()['id_maquina']);
      $aux=1;
      $formula = $maquina->formula;
      $contadores =$validator->getData()['contadores'];
      foreach ($contadores as $cont) {

          switch ($aux)
          {
            case 1:
              if($formula->cont1 !=null && $cont['valor'] == "")
              {
                $validator->errors()->add('contadores', 'No se han cargado todos los contadores.');
              }
              break;
            case 2:
              if($formula->cont2 != null && $cont['valor'] == "")
              {
                $validator->errors()->add('contadores', 'No se han cargado todos los contadores.');
              }
              break;
            case 3:
              if($formula->cont3 !=null && $cont['valor'] == "")
              {
                $validator->errors()->add('contadores', 'No se han cargado todos los contadores.');
              }
              break;
            case 4:
              if($formula->cont4 !=null && $cont['valor'] == "")
              {
                $validator->errors()->add('contadores', 'No se han cargado todos los contadores.');
              }
              break;
            case 5:
              if($formula->cont5 !=null && $cont['valor'] == "")
              {
                $validator->errors()->add('contadores', 'No se han cargado todos los contadores.');
              }
              break;
            case 6:
              if($formula->cont6 !=null && $cont['valor'] == "")
              {
                $validator->errors()->add('contadores', 'No se han cargado todos los contadores.');
              }
              break;
            default:
              # code...
              break;
          }
          $aux++;
        }

    })->validate();
    if(isset($validator))
      {
        if ($validator->fails())
        {
          return [
                'errors' => $validator->getMessageBag()->toArray()
            ];
        }
      }

      $modif = new TomaRelModificada;
      $modif->relevamiento_movimiento->associate($request['id_relevamiento_movimiento']);
      $modif->mac = $request['mac'];
      $modif->valcont1 = $request['valcont1'];
      $modif->valcont2 = $request['valcont2'];
      $modif->valcont3 = $request['valcont3'];
      $modif->valcont4 = $request['valcont4'];
      $modif->valcont5 = $request['valcont5'];
      $modif->valcont6 = $request['valcont6'];
      $modif->valcont7 = $request['valcont7'];
      $modif->valcont8 = $request['valcont8'];
      $modif->juego= $request['juego'];
      $modif->apuesta_max= $request['apuesta_max'];
      $modif->cant_lineas= $request['cant_lineas'];
      $modif->porcentaje_devolucion=$request['porcentaje_devolucion'];
      $modif->denominacion= $request['denominacion'];
      $modif->cant_creditos= $request['cant_creditos'];
      $modif->observaciones_modif= $request['observaciones_modif'];
      $modif->nro_admnin= $request['nro_admnin'];
      $modif->modelo= $request['modelo'];
      $modif->nro_serie = $request['nro_serie'];
      $modif->nro_isla= $request['nro_isla'];
      $modif->marca['marca'];
      // $modif->'id_modificador', //falta hacer la relacion belongs to etc..
      //                            'check1','check2', 'check3', 'check4','check5',
      //                            'check6', 'check7','check8', 'fecha_modif'


  }

}
