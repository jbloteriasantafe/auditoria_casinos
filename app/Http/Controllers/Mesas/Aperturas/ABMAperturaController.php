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
use App\SecRecientes;

use App\Mesas\Mesa;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;
use App\Mesas\Apertura;
use App\Mesas\DetalleApertura;
use App\Mesas\EstadoCierre;
use App\Mesas\TipoCierre;
use App\Http\Controllers\Mesas\Cierres\ABMCCierreAperturaController;
use App\Http\Controllers\UsuarioController;


class ABMAperturaController extends Controller
{
  private static $atributos = [
    'id_cierre_mesa' => 'Identificacion del Cierre',
    'fecha' => 'Fecha',
    'hora_inicio' => 'Hora de Apertura',
    'hora_fin' => 'Hora del Cierre',
    'total_pesos_fichas_c' => 'Total de pesos en Fichas',
    'total_anticipos_c' => 'Total de Anticipos',
    'id_fiscalizador'=>'Fiscalizador',
    'id_tipo_cierre'=> 'Tipo de Cierre',
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
      $this->middleware(['tiene_permiso:m_gestionar_aperturas']);
  }

  public function guardar(Request $request){
    $validator=  Validator::make($request->all(),[
      'fecha' => 'required|date',
      'hora' => 'required|date_format:"H:i"',
      'id_casino' => 'required|exists:casino,id_casino',
      'total_pesos_fichas_a' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
      'id_fiscalizador' => 'required|exists:usuario,id_usuario',
      'id_mesa_de_panio' => 'required|exists:mesa_de_panio,id_mesa_de_panio',
      'fichas' => 'required',
      'fichas.*.id_ficha' => 'required|exists:ficha,id_ficha',
      'fichas.*.cantidad_ficha' => ['nullable'],
    ], array(), self::$atributos)->after(function($validator){  })->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
     }
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    if($user->usuarioTieneCasino($request->id_casino)){
      $mesa = Mesa::find($request->id_mesa_de_panio);
      $apertura = new Apertura;
      $apertura->fecha =$request->fecha;
      $apertura->hora = $request->hora;
      $apertura->total_pesos_fichas_a = $request->total_pesos_fichas_a;
      $apertura->fiscalizador()->associate($request->id_fiscalizador);
      $apertura->cargador()->associate($user->id_usuario);
      $apertura->mesa()->associate($request->id_mesa_de_panio);
      $apertura->estado_cierre()->associate(1);//asociar estado cargado
      $apertura->casino()->associate($request->id_casino);
      $apertura->tipo_mesa()->associate($mesa->tipo_mesa->id_tipo_mesa);
      $apertura->save();
      $detalles = array();
      foreach ($request->fichas as $f) {
        $ficha = new DetalleApertura;
        $ficha->ficha()->associate($f['id_ficha']);
        $ficha->cantidad_ficha = $f['cantidad_ficha'];
        $ficha->apertura()->associate($apertura->id_apertura_mesa);
        $ficha->save();
        $detalles[] = $ficha;
      }

      //$cacontroller = new ABMCCierreAperturaController;
      //$cacontroller->asociarAperturaACierre($apertura);


     return ['apertura' => $apertura,'detalles' => $detalles];
    }else{
       return response()->json(['errors' => ['autorizacion' => 'No está autorizado para realizar esta accion.'],404]);
    }
  }

  public function modificarApertura(Request $request){
    $validator=  Validator::make($request->all(),[
      'id_apertura' => 'required|exists:apertura_mesa,id_apertura_mesa',
      'hora' => 'required|date_format:"H:i:s"',
      'total_pesos_fichas_a' =>  ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
      'id_fiscalizador' => 'required|exists:usuario,id_usuario',
      'fichas' => 'required',
      'fichas.*.id_ficha' => 'required|exists:ficha,id_ficha',
      'fichas.*.cantidad_ficha' =>  ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
    ], array(), self::$atributos)->after(function($validator){  })->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
     }
    $apertura = Apertura::find($request->id_apertura);
    $user =  UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    if($user->usuarioTieneCasino($apertura->casino->id_casino)){
      $apertura->hora = $request->hora;
      $apertura->total_pesos_fichas_a = $request->total_pesos_fichas_a;
      $apertura->fiscalizador()->associate($request->id_fiscalizador);
      $apertura->save();
      $detalles = $apertura->detalles;

      foreach ($detalles as $d) {
        $d->apertura()->dissociate();
        $d->delete();
      }
      foreach ($apertura->detalles as $f) {
        $ficha = new DetalleApertura;
        $ficha->ficha()->associate($f['id_ficha']);
        $ficha->cantidad_ficha = $f['cantidad_ficha'];
        $ficha->apertura()->associate($apertura->id_apertura_mesa);
        $ficha->save();
      }
       return response()->json(['exito' => 'Apertura Modificada'], 200);
    }else{

      return response()->json(['autorizacion' => 'No está autorizado para realizar esta accion.'], 404);
    }
  }



}
