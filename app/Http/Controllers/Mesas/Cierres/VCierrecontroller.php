<?php

namespace App\Http\Controllers\Mesas\Cierres;

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
use App\Mesas\Cierre;
use App\Mesas\DetalleCierre;
use App\Mesas\EstadoCierre;

use App\Http\Controllers\UsuarioController;
//validacion de cierres
class VCierreController extends Controller
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
    $this->middleware(['tiene_permiso:m_buscar_cierres']);
  }

  //en esta
  public function validarCierre(Request $request){
    $validator=Validator::make($request->all(), [
      'id_cierre' => 'exists:cierre_mesa,id_cierre_mesa',
      'observacion' => 'nullable|max:200',

     ],array(),self::$atributos)->after(function ($validator){})->validate();

       $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
       $cierre = Cierre::find($request->id_cierre);
    if($user->usuarioTieneCasino($cierre->id_casino)){

      $cierre->estado_cierre()->associate(3);//VISADO
      $cierre->observacion = $request->observacion;
      $cierre->save();
      return response()->json(['ok' => true], 200);
    }else{
      $val = new Validator;
      $val->errors()->add('autorizacion', 'No está autorizado para realizar esta accion.');

      return ['errors' => $val->messages()->toJson()];
    }
  }



}
