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
use App\SecRecientes;
use App\Http\Controllers\UsuarioController;
use App\Mesas\Mesa;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;
use App\Mesas\Cierre;
use App\Mesas\DetalleCierre;
use App\Mesas\Ficha;

class ABMCierreController extends Controller
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

  public function guardar(Request $request){
    $validator=  Validator::make($request->all(),[
      'fecha' => 'required|date',
      'hora_inicio' => 'nullable|date_format:"H:i"',
      'hora_fin' => 'nullable|date_format:"H:i"',
      'total_pesos_fichas_c' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
      'total_anticipos_c' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
      'id_fiscalizador' => 'required|exists:usuario,id_usuario',
      'id_mesa_de_panio' => 'required|exists:mesa_de_panio,id_mesa_de_panio',
      'fichas' => 'required',
      'id_juego_mesa'=> 'required|exists:juego_mesa,id_juego_mesa',
      'fichas.*.id_ficha' => 'required|exists:ficha,id_ficha',
      'fichas.*.monto_ficha' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
      'id_moneda' => 'required|exists:moneda,id_moneda',
    ], array(), self::$atributos)->after(function($validator){
      $mesa = Mesa::find($validator->getData()['id_mesa_de_panio']);
      if(!$mesa->multimoneda && $mesa->id_moneda != $validator->getData()['id_moneda']){
         $validator->errors()->add('id_moneda', 'La moneda elegida no es correcta.');
      }
      $filtros = [
                    ['fecha','=',$validator->getData()['fecha']],
                    ['id_mesa_de_panio','=',$validator->getData()['id_mesa_de_panio']],
                    ['hora_fin','=',$validator->getData()['hora_fin']],
                    ['id_moneda','=',$validator->getData()['id_moneda']]
                  ];
      if(!empty($validator->getData()['fecha']) &&
          !empty($validator->getData()['id_mesa_de_panio']) &&
          !empty($validator->getData()['hora_fin']) &&
          !empty($validator->getData()['id_moneda'])
        ){
        $yaExiste = Cierre::where($filtros)
                            ->get();
        if(count($yaExiste) != 0){
          $validator->errors()->add('id_mesa_de_panio', 'Ya existe un cierre para la mesa con esos datos.');
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
      $cierre = new Cierre;
      $mesa = Mesa::find($request->id_mesa_de_panio);
      $cierre->fecha =$request->fecha;
      $cierre->hora_inicio = $request->hora_inicio;
      $cierre->hora_fin = $request->hora_fin;
      $cierre->total_pesos_fichas_c = $request->total_pesos_fichas_c;
      $cierre->total_anticipos_c = $request->total_anticipos_c;
      $cierre->tipo_mesa()->associate($mesa->juego->tipo_mesa->id_tipo_mesa);
      $cierre->casino()->associate($request->id_casino);
      $cierre->fiscalizador()->associate($request->id_fiscalizador);
      $cierre->mesa()->associate($request->id_mesa_de_panio);
      $cierre->moneda()->associate($request->id_moneda);
      $cierre->save();
      $detalles = array();
      foreach ($request->fichas as $f) {
          if($f['monto_ficha'] != 0){
          $ficha = new DetalleCierre;
          //dd($ficha);
          $ficha->ficha()->associate($f['id_ficha']);
          $ficha->monto_ficha = $f['monto_ficha'];
          $ficha->cierre()->associate($cierre->id_cierre_mesa);
          $ficha->save();
          $detalles[] = $ficha;
        }
      }
     return ['cierre' => $cierre,'detalles' => $detalles];
    }else{
      $val = new Validator;
      $val->errors()->add('autorizacion', 'No está autorizado para realizar esta accion.');

      return ['errors' => $val->messages()->toJson()];
    }
  }

  public function obtenerCierre($id){
    $cierre = Cierre::find($id);

    return ['cierre' => $cierre ,
            'estado' => $cierre->estado,
            'fiscalizador' => $cierre->fiscalizador,
            'mesa' => $cierre->mesa];
  }

  public function modificarCierre(Request $request){
    $validator=  Validator::make($request->all(),[
      'id_cierre_mesa' => 'required|exists:cierre_mesa,id_cierre_mesa',
      //'fecha' => 'required|date',
      'hora_inicio' => 'nullable|date_format:"H:i"',
      'hora_fin' => 'nullable|date_format:"H:i"',
      'total_pesos_fichas_a' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
      'total_anticipos_c' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
      'id_fiscalizador' => 'required|exists:usuario,id_usuario',
      'fichas' => 'required',
      'fichas.*.id_ficha' => 'required|exists:ficha,id_ficha',
      'fichas.*.monto_ficha' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'], //en realidad es monto lo que esta recibiendo
      'id_moneda' => 'required|exists:moneda,id_moneda',
    ], array(), self::$atributos)->after(function($validator){
      $mesa = Mesa::find($validator->getData()['id_mesa_de_panio']);
      if(!$mesa->multimoneda && $mesa->id_moneda != $validator->getData()['id_moneda']){
         $validator->errors()->add('id_moneda', 'La moneda elegida no es correcta.');
      }
      $validator = $this->validarFichas($validator);
    })->validate();
    if(isset($validator)){
      if($validator->fails()){
        return ['errors' => $validator->messages()->toJson()];
      }
    }

    $cierre = Cierre::find($request->id_cierre_mesa);
    $cierre->fecha =$request->fecha;
    $cierre->hora_inicio = $request->hora_inicio;
    $cierre->hora_fin = $request->hora_fin;
    $cierre->total_pesos_fichas_c = $request->total_pesos_fichas_a;
    $cierre->total_anticipos_c = $request->total_anticipos_c;
    $cierre->moneda()->associate($request->id_moneda);
    $cierre->fiscalizador()->associate($request->id_fiscalizador);
    $cierre->save();
    $detalles = array();
    $detallesC = $cierre->detalles;
    foreach ($detallesC as $d) {
      $d->cierre()->dissociate();
      $d->delete();
    }
    foreach ($request->fichas as $f) {
      if($f['monto_ficha'] != 0){
        $ficha = new DetalleCierre;
        $ficha->ficha()->associate($f['id_ficha']);
        $ficha->monto_ficha = $f['monto_ficha'];
        $ficha->save();
        $detalles[] = $ficha;
      }
    }
   return ['cierre' => $cierre,'detalles' => $detalles];
  }

  private function validarFichas($validator){
    if(!empty($validator->getData()['fichas']) || $validator->getData()['fichas'] != null){
      foreach ($validator->getData()['fichas'] as $detalle) {
        $ficha = Ficha::find($detalle['id_ficha']);
        $division = $detalle['monto_ficha'] / $ficha->valor_ficha ;
        if(($detalle['monto_ficha']-$division * $ficha->valor_ficha) != 0){
          $validator->errors()->add('monto_ficha','El monto no es múltiplo del valor.'
                                   );
          break;
        }
      }
      return $validator;
    }
  }
}
