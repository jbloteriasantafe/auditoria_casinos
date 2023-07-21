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
  public function __construct(){
    $this->middleware(['tiene_permiso:m_buscar_cierres']);
  }

  public function validar(Request $request){
    $user = null;
    $cierre = null;
    $validator = Validator::make($request->all(), [
      'id_cierre_mesa' => 'exists:cierre_mesa,id_cierre_mesa',
      'observacion' => 'nullable|max:200',
    ],[
      'max' => 'Supera el limite.'
    ],self::$atributos)->after(function ($validator) use (&$user,&$cierre){
      $data = $validator->getData();
      $user   = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $cierre = Cierre::find($data['id_cierre_mesa']);
      if(!$user->usuarioTieneCasino($cierre->id_casino))
        return $validator->errors()->add('autorizacion', 'No está autorizado para realizar esta accion.');
      if($cierre->id_estado_cierre != 1)
        return $validator->errors()->add('id_cierre_mesa', 'No se puede validar ese cierre.');
    })->validate();
    
    $cierre->estado_cierre()->associate(3);//VISADO
    $cierre->observacion = $request->observacion;
    $cierre->save();
    return 1;
  }
}
