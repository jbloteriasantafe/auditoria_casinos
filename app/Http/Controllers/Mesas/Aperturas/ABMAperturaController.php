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
use App\Mesas\Ficha;

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
    'fichas.*.cantidad_ficha' => 'cantidad'
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
      'fichas.*.cantidad_ficha' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?$/'],
      'id_moneda' => 'required|exists:moneda,id_moneda',
    ], array(), self::$atributos)->after(function($validator){
      $mesa = Mesa::find($validator->getData()['id_mesa_de_panio']);
      if(!$mesa->multimoneda && $mesa->id_moneda != $validator->getData()['id_moneda']){
         $validator->errors()->add('id_moneda', 'La moneda elegida no es correcta.');
      }
        if(!empty($validator->getData()['fecha']) &&
            !empty($validator->getData()['id_mesa_de_panio']) &&
            !empty($validator->getData()['hora']) &&
            !empty($validator->getData()['id_moneda'])
          ){
        $ap = Apertura::where([
                                ['fecha','=',$validator->getData()['fecha']],
                                ['id_mesa_de_panio','=',$validator->getData()['id_mesa_de_panio']],
                                ['hora','=',$validator->getData()['hora']],
                                ['id_moneda','=',$validator->getData()['id_moneda']]
                              ])
                              ->get();

        if(count($ap)> 0 ){
          $validator->errors()->add('id_mesa_de_panio','Ya existe una apertura para la fecha.'
                                   );
        }
      }
      $validator = $this->validarFichas($validator);

    })->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
     }
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    if($user->usuarioTieneCasino($request->id_casino)){
      $mesa = Mesa::find($request->id_mesa_de_panio);
      $apertura = new Apertura;
      $apertura->moneda()->associate($request->id_moneda);
      $apertura->fecha =$request->fecha;
      $apertura->hora = $request->hora;
      $apertura->total_pesos_fichas_a = $request->total_pesos_fichas_a;
      $apertura->fiscalizador()->associate($request->id_fiscalizador);
      $apertura->cargador()->associate($user->id_usuario);
      $apertura->mesa()->associate($request->id_mesa_de_panio);
      $apertura->estado_cierre()->associate(1);//asociar estado cargado
      $apertura->casino()->associate($request->id_casino);
      $apertura->tipo_mesa()->associate($mesa->juego->tipo_mesa->id_tipo_mesa);
      $apertura->save();
      $detalles = array();
      $total_pesos_fichas_a = 0;
      foreach ($request['fichas'] as $f) {
        if($f['cantidad_ficha'] != 0){
          $ficha = new DetalleApertura;
          $ficha->ficha()->associate($f['id_ficha']);
          $ficha->cantidad_ficha = $f['cantidad_ficha'];
          $ficha->apertura()->associate($apertura->id_apertura_mesa);
          $ficha->save();
          $fixa = Ficha::find($f['id_ficha']);
          $total_pesos_fichas_a =($f['cantidad_ficha'])*$fixa->valor_ficha + $total_pesos_fichas_a;
        }
      }
      if($total_pesos_fichas_a != $apertura->total_pesos_fichas_a){
        $apertura->total_pesos_fichas_a = $total_pesos_fichas_a;
        $apertura->save();
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
      'hora' => 'required|date_format:"H:i"',
      'total_pesos_fichas_a' =>  ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
      'id_fiscalizador' => 'required|exists:usuario,id_usuario',
      'fichas' => 'required',
      'fichas.*.id_ficha' => 'required|exists:ficha,id_ficha',
      'fichas.*.cantidad_ficha' =>  ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?$/'],
      'id_moneda' => 'required|exists:moneda,id_moneda',
    ], array(), self::$atributos)->after(function($validator){
      $apertura=Apertura::find($validator->getData()['id_apertura']);
      $mesa = $apertura->mesa;
      if(!$mesa->multimoneda && $mesa->id_moneda != $validator->getData()['id_moneda']){
         $validator->errors()->add('id_moneda', 'La moneda elegida no es correcta.');
      }
      $validator = $this->validarFichas($validator);
     })->validate();
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
      $apertura->moneda()->associate($request->id_moneda);
      foreach ($detalles as $d) {
        $d->apertura()->dissociate();
        $d->delete();
      }
      $total_pesos_fichas_a = 0;
      foreach ($request['fichas'] as $f) {
        if($f['cantidad_ficha'] != 0){
          $ficha = new DetalleApertura;
          $ficha->ficha()->associate($f['id_ficha']);
          $ficha->cantidad_ficha = $f['cantidad_ficha'];
          $ficha->apertura()->associate($apertura->id_apertura_mesa);
          $ficha->save();
          $fixa = Ficha::find($f['id_ficha']);
          $total_pesos_fichas_a =($f['cantidad_ficha'])*$fixa->valor_ficha + $total_pesos_fichas_a;
        }
      }
      if($total_pesos_fichas_a != $apertura->total_pesos_fichas_a){
        $apertura->total_pesos_fichas_a = $total_pesos_fichas_a;
        $apertura->save();
      }
      return response()->json(['exito' => 'Apertura Modificada'], 200);
    }else{

      return response()->json(['autorizacion' => 'No está autorizado para realizar esta accion.'], 404);
    }
  }

  private function validarFichas($validator){
    $aux = 0;
    $total_pesos_fichas_a = 0;
    if(!empty($validator->getData()['fichas']) && $validator->getData()['fichas'] != null){
      foreach ($validator->getData()['fichas'] as $detalle) {
        $total_pesos_fichas_a+= $detalle['cantidad_ficha'];
      }

      if($total_pesos_fichas_a == 0){
        $validator->errors()->add('fichas','No ha ingresado los montos.');
      }
      return $validator;

    }
  }

}
