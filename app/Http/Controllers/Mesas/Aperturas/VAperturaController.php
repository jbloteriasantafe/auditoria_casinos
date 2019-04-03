<?php

namespace App\Http\Controllers\Mesas\Aperturas;

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
use App\Relevamiento;
use App\SecRecientes;
use App\Http\Controllers\UsuarioController;
use App\Mesas\Mesa;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;
use App\Mesas\Apertura;
use App\Mesas\DetalleApertura;
use App\Mesas\EstadoCierre;
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
    $validator=  Validator::make($request->all(),[
      'id_cierre' => 'required|exists:cierre_mesa,id_cierre_mesa',
      'observacion' => 'nullable',
      'diferencia' => 'required|boolean',
    ], array(), self::$atributos)->after(function($validator){  })->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
     }
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $apertura = Apertura::find($request['id_apertura']);
    if($request->diferencia == 1){
      $apertura->estado_cierre()->associate(2);//VISADO con diferencias
    }else{
      $apertura->estado_cierre()->associate(3);//VISADO
    }
    $apertura->observacion= $request['observacion'];
    $apertura->save();
    $cacontroller = new ABMCCierreAperturaController;
    $cacontroller->asociarAperturaACierre($apertura, $request['id_cierre']);
    return response()->json(['ok' => true], 200);

  }






}
