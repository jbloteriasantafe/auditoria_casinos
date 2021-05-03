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
use App\MesCasino;
use Carbon\Carbon;
use App\SecRecientes;
use App\Http\Controllers\RolesPermissions\RoleFinderController;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\Mesas\Mesa;
use App\Mesas\Moneda;
use App\Mesas\ImagenesBunker;
use App\Mesas\DetalleImgBunker;
use App\Mesas\Canon;
use App\Mesas\ImportacionDiariaMesas;
use App\Mesas\InformeFinalMesas;
use App\Mesas\DetalleInformeFinalMesas;
use App\Mesas\ImportacionMensualMesas;
use App\Mesas\DetalleImportacionMensualMesas;

use App\Http\Controllers\Mesas\Importaciones\Mesas\MensualController;
use App\Http\Controllers\UsuarioController;

class APagosController extends Controller
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
  public function __construct(){
    $this->middleware(['tiene_permiso:m_a_pagos']);
  }

  public function borrar($id_detalle){
    $ret = 0;
    DB::transaction(function() use ($id_detalle,&$ret){
      $d = DetalleInformeFinalMesas::find($id_detalle);
      if(is_null($d)){
        $ret = -1;
        return;
      }
      $d->delete();
      $i = $d->informe_final_mesas;
      if(is_null($i)){
        $ret = -2;
        return;
      }
      if($i->detalles()->count() == 0) $i->delete();
    });
    return $ret;
  }

  public function crearOModificar(Request $request){
    $detalle = null;
    $informe = null;
    $validator = Validator::make($request->all(),[
      'id_detalle_informe_final_mesas' => 'nullable|exists:detalle_informe_final_mesas,id_detalle_informe_final_mesas',
      'id_casino' => 'required|exists:casino,id_casino',
      'anio_inicio' => 'required|integer',
      'anio' => 'required|integer',
      'mes' => 'required|integer',
      'dia_inicio' => 'required|integer',
      'dia_fin' => 'required|integer',
      'fecha_pago' => 'required|date',
      'cotizacion_dolar' => ['required','regex:/^[0-9]*[,|.]?[0-9]{0,3}$/'],
      'cotizacion_euro' =>  ['required','regex:/^[0-9]*[,|.]?[0-9]{0,3}$/'],
      'total_pago_pesos' =>  ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/']
    ], array(), self::$atributos)->after(function($validator) use(&$detalle,&$informe){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
      if(!$user->usuarioTieneCasino($data['id_casino'])){
        $validator->errors()->add('id_casino','No puede acceder a este casino');
        return;
      }
      if($data['cotizacion_dolar'] == 0) return $validator->errors()->add('cotizacion_dolar','validation.regex');
      if($data['cotizacion_euro'] == 0) return $validator->errors()->add('cotizacion_euro','validation.regex');
      if($validator->errors()->any()) return;

      //Validar que la fecha tenga sentido
      $anio = $data['anio'];
      $mes = $data['mes'];
      $dia_inicio = $data['dia_inicio'];
      $dia_fin = $data['dia_fin'];
      if($dia_inicio > $dia_fin){
        $validator->errors()->add('dia_inicio','Dia de inicio invalido');
        return;
      }
      $finicio = date_create_from_format('Y-m-d',$anio.'-'.$mes.'-'.$dia_inicio);
      if($finicio->format('Y') != $anio || $finicio->format('m') != $mes || $finicio->format('d') != $dia_inicio){
        $validator->errors()->add('dia_inicio','Fecha invalida');
      }
      $ffin = date_create_from_format('Y-m-d',$anio.'-'.$mes.'-'.$dia_fin);
      if($ffin->format('Y') != $anio    || $ffin->format('m') != $mes    || $ffin->format('d') != $dia_fin){
        $validator->errors()->add('dia_fin','Fecha invalida');
      }

      if($validator->errors()->any()) return;

      $reglas_intercalado = [['id_casino','=',$data['id_casino']],['anio','=',$anio],['mes','=',$mes]];
      if(!is_null($data['id_detalle_informe_final_mesas'])){
        $detalle = DetalleInformeFinalMesas::find($data['id_detalle_informe_final_mesas']);
        $informe = $detalle->informe_final_mesas;
        if($data['anio_inicio'] != $informe->anio_inicio){
          $validator->errors()->add('anio_inicio','Año invalido para el informe');
        }

        if($anio < $informe->anio_inicio || $anio > $informe->anio_final){
          $validator->errors()->add('anio','Año invalido para el informe');
        }
        if($validator->errors()->any()) return;

        $reglas_intercalado[] = ['id_detalle_informe_final_mesas','<>',$data['id_detalle_informe_final_mesas']];
      }

      //Validar que no haya ya un detalle con esos dias
      $detalle_intercalado = DetalleInformeFinalMesas::where($reglas_intercalado)
      ->whereNull('deleted_at')
      ->where(function($q) use ($dia_inicio,$dia_fin){
        return $q->where('dia_inicio','<=',$dia_fin)->orWhere('dia_fin','>=',$dia_inicio);
      })->count() > 0;
      if($detalle_intercalado){
        $validator->errors()->add('dia_inicio','Ya se encuentra cargado un detalle para este periodo');
      }
    })->validate();

    DB::transaction(function() use ($request,&$detalle,&$informe){
      if(is_null($detalle)) $detalle = new DetalleInformeFinalMesas;
      if(is_null($informe)){
        $informe = InformeFinalMesas::where('id_casino',$request->id_casino)
        ->where('anio_inicio',$request->anio_inicio)->first();
        if(is_null($informe)){
          $informe = new InformeFinalMesas;
          $informe->id_casino = $request->id_casino;
          $informe->anio_inicio = $request->anio_inicio;
          //Practicamente siempre pasa el año, a menos que el casino inicie el 1 de enero
          $pasa_el_año = count(ABMCCanonController::getInstancia()->mesesCuotasCanon($request,$request->id_casino)['meses']) >= 13;
          $informe->anio_final = $request->anio_inicio + ($pasa_el_año? 1 : 0);
          $informe->base_actual_dolar = 0;
          $informe->base_actual_euro  = 0;
          $informe->save();
        }
      }
      $detalle->id_informe_final_mesas = $informe->id_informe_final_mesas;
      $detalle->id_casino = $request->id_casino;
      $detalle->dia_inicio = $request->dia_inicio;
      $detalle->dia_fin = $request->dia_fin;
      $detalle->mes = $request->mes;
      $detalle->anio = $request->anio;
      $detalle->fecha_cobro = $request->fecha_pago;
      $detalle->cotizacion_dolar_actual = $request->cotizacion_dolar;
      $detalle->cotizacion_euro_actual  = $request->cotizacion_euro;
      $detalle->total_mes_actual        = $request->total_pago_pesos;
      $detalle->save();
    });
    return ['informe' => $informe,'detalle' => $detalle];
  }
}
