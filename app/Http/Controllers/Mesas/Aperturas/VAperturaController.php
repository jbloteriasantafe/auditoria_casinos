<?php

namespace App\Http\Controllers\Mesas\Aperturas;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Http\Controllers\UsuarioController;
use App\Mesas\Apertura;
use App\Mesas\Cierre;
use App\Http\Controllers\Mesas\Cierres\ABMCCierreAperturaController;

//validacion de cierres
class VAperturaController extends Controller
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
      $this->middleware(['tiene_permiso:m_validar_aperturas']);
  }


  public function validarApertura(Request $request){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $validator=  Validator::make($request->all(),[
      'id_apertura_mesa' => 'required|exists:apertura_mesa,id_apertura_mesa,deleted_at,NULL',
      'id_cierre_mesa' => 'required|exists:cierre_mesa,id_cierre_mesa,deleted_at,NULL',
      'observaciones' => 'nullable|string|max:200',
      'diferencia' => 'required|boolean',
    ], [
      'required' => 'El valor es requerido',
      'exists' => 'El valor es invalido',
      'max' => 'El valor supera el limite',
    ], self::$atributos)->after(function($validator) use ($user){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $apertura = Apertura::find($data['id_apertura_mesa']);
      $cierre   = Cierre::find($data['id_cierre_mesa']);
      if($apertura->id_casino != $cierre->id_casino){
        return $validator->errors()->add('id_cierre_mesa','El cierre no corresponde');
      }
      if(!$user->usuarioTieneCasino($apertura->id_casino)){
        return $validator->errors()->add('id_casino','No tiene los privilegios');
      }
      //@DUDA: comparar por id_ficha o por valor_ficha?
      $detallesA = collect($apertura->detalles->toArray())->keyBy('id_ficha');
      $detallesC = collect($cierre->detalles->toArray())->keyBy('id_ficha');
      $hay_diferencia = false;
      foreach($detallesA as $id_ficha => $da){
        $ca = $da['cantidad_ficha'];
        $cc = array_key_exists($id_ficha,$detallesC)? $detallesC[$id_ficha]['cantidad_ficha'] : 0;
        $hay_diferencia = $hay_diferencia || $ca != $cc;
      }
      foreach($detallesC as $id_ficha => $dc){
        $cc = $dc['cantidad_ficha'];
        $ca = array_key_exists($id_ficha,$detallesA)? $detallesA[$id_ficha]['cantidad_ficha'] : 0;
        $hay_diferencia = $hay_diferencia || $ca != $cc;
      }
      $hay_diferencia += 0;
      $diferencia = $data['diferencia'];
      if($hay_diferencia != $diferencia){
        return $validator->errors()->add('diferencia',"Esperaba $hay_diferencia recibio $diferencia");
      }
    })->validate();
    
    return DB::transaction(function() use ($request,$user){
      $apertura = Apertura::find($request['id_apertura_mesa']);
      if($request->diferencia == 1){
        $apertura->estado_cierre()->associate(2);//VISADO con diferencias
      }else{
        $apertura->estado_cierre()->associate(3);//VISADO
      }
      $apertura->observacion = $request['observacion'];
      $apertura->save();
      $cacontroller = new ABMCCierreAperturaController;
      $cacontroller->asociarAperturaACierre($apertura, $request['id_cierre_mesa']);
      return response()->json(['ok' => true], 200);
    });
  }
}
