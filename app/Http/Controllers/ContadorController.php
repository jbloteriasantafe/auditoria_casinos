<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ContadorHorario;
use App\DetalleContadorHorario;
use Validator;
use Illuminate\Support\Facades\DB;
use App\TipoMoneda;
use App\Http\Controllers\UsuarioController;

class ContadorController extends Controller
{
  private static $atributos = [
  ];
  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new ContadorController();
    }
    return self::$instance;
  }

  // eliminarContador elimina los detalles contadores y por ultimo el contador horarios
  public function eliminarContador($id_contador){
    Validator::make(['id_contador' => $id_contador]
                   ,['id_contador' => 'required|exists:contador_horario,id_contador_horario']
                   , array(), self::$atributos)->after(function($validator){
                   })->sometimes('id_contador','exists:contador_horario,id_contador_horario',function($input){
                      $cont = ContadorHorario::find($input['id_contador']);
                      return !$cont->cerrado;
                   })->validate();
    $pdo = DB::connection('mysql')->getPdo();

    $query = sprintf(" DELETE FROM detalle_contador_horario
                       WHERE id_contador_horario = '%d'
                       ",$id_contador);

    $pdo->exec($query);

    $query = sprintf(" DELETE FROM contador_horario
                       WHERE id_contador_horario = '%d'
                       ",$id_contador);
    $pdo->exec($query);
  }
  // modificarContador modifica los detalles contador, asocaciado a un unico contador horario
  // se ajusta de acuerdo a un tipo de ajuste y se asocia al mismo
  public function modificarContador(Request $request){
    Validator::make($request->all(), [
                    'detalles' => 'nullable',
                    'detalles.*.id_contador_horario' => 'required|exists:contador_horario,id_contador_horario',
                    'detalles.*.id_detalle_contador_horario' => 'required|exists:detalle_contador_horario,id_detalle_contador_horario',
                    'detalles.*.coinin' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
                    'detalles.*.coinout' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
                    'detalles.*.jackpot' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
                    'detalles.*.progresivo' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
                    'detalles.*.id_tipo_ajuste' => 'nullable|exists:tipo_ajuste,id_tipo_ajuste'
                  ], array(), self::$atributos)->after(function($validator){
                   })->validate();

    foreach($request->detalles as $det){
      $detalle = DetalleContadorHorario::find($det['id_detalle_contador_horario']);
      $detalle->coinin = $det['coinin'];
      $detalle->coinout = $det['coinout'];
      $detalle->jackpot = $det['jackpot'];
      $detalle->progresivo = $det['progresivo'];
      $detalle->save();
      if($det['id_tipo_ajuste'] != null){
        $detalle->tipo_ajuste()->associate($det['id_tipo_ajuste']);
      }else{
        $detalle->tipo_ajuste()->dissociate();
      }
    }
  }

  // estaCerrado  retorna los contadores cerrados que coinciden con los parametros
  // se considera cerrado el contador horario cuando se ya se valido el mismo, es decir, todos los detalles contadores
  // del contador horario, fueron validados
  public function estaCerrado($fecha,$id_casino,$tipo_moneda){
    //cerrado significa que se haya validado el producido de la misma fecha en cuestion
    $contadores= ContadorHorario::where([['fecha' , '=' , $fecha],['id_casino' , '=' , $id_casino] , ['id_tipo_moneda' , '=' , $tipo_moneda->id_tipo_moneda]])->get();
    $error=array();
    if($contadores->count() == 1){
      foreach ($contadores as $contador) {
          if ($contador->cerrado !=1) {
              $error[]=$contador;
          }
      }
    }else {
      $error[] = 'Mas de un contador para el casino , fecha y tipo moneda';
    }

    return $error;
  }

  // estaCerradoMaquina verifica para una maquina en una fecha, si el contador horario asociado esta cerrad
  // tambien verifica si tiene importado los contadores y sus respectivo detalle contador
  public function estaCerradoMaquina($fecha,$id_maquina){
    $resultado = DetalleContadorHorario::join('contador_horario' , 'detalle_contador_horario.id_contador_horario' , '=' , 'contador_horario.id_contador_horario')
                                         ->where([['contador_horario.fecha' ,$fecha],['detalle_contador_horario.id_maquina' , $id_maquina]])
                                         ->get();
    if($resultado->count() == 1){
      $cerrado = $resultado[0]->contador_horario->cerrado;
      $detalle = $resultado[0];
      $importado = 1;
    }else{
      $detalle= null;
      $cerrado = 0;
      $importado = 0;
    }
    return ['importado' => $importado , 'cerrado' => $cerrado, 'detalle' =>$detalle];
  }
}
