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
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    
    $validator =  Validator::make($request->all(),[
      'id_cierre_mesa' => 'nullable|exists:cierre_mesa,id_cierre_mesa',
      'fecha' => 'required|date',
      'hora_inicio' => 'required|date_format:"H:i"',
      'hora_fin' => 'required|date_format:"H:i"',
      'total_anticipos_c' => 'nullable|numeric|min:0',
      'id_cargador' => 'required|exists:usuario,id_usuario',
      'id_mesa_de_panio' => 'required|exists:mesa_de_panio,id_mesa_de_panio',
      'fichas' => 'required|array',
      'fichas.*.id_ficha' => 'required|exists:ficha,id_ficha',
      'fichas.*.monto_ficha' => 'nullable|numeric|min:0',
      'id_moneda' => 'required|exists:moneda,id_moneda',
    ],[
      'required' => 'El valor es requerido',
      'exists'   => 'No existe el valor en la base de datos',
      'numeric'  => 'El valor tiene que ser numerico',
      'min'      => 'El valor tiene que ser positivo',
      'regex'    => 'El formato es incorrecto'
    ], self::$atributos)->after(function($validator) use ($user){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      
      $mesa = Mesa::withTrashed()->where('id_mesa_de_panio','=',$data['id_mesa_de_panio'])
      ->where(function($q) use ($data){
        return $q->where('deleted_at','>',$data['fecha'])->orWhereNull('deleted_at');
      })->first();
      
      if(is_null($mesa))
        return $validator->errors()->add('id_mesa_de_panio', 'No existe la mesa.');
      
      if(!$user->usuarioTieneCasino($mesa->id_casino)){
        return $validator->errors()->add('id_casino','No tiene los privilegios');
      }
        
      if(!$mesa->multimoneda && $mesa->id_moneda != $data['id_moneda']){
        return $validator->errors()->add('id_moneda', 'La moneda elegida no es correcta.');
      }
      
      if(is_null($data['id_cierre_mesa'] ?? null)){
        $reglas = [
          ['id_mesa_de_panio','=',$data['id_mesa_de_panio']],
          ['fecha','=',$data['fecha']],
          ['id_moneda','=',$data['id_moneda']],
          ['hora_fin','=',$data['hora_fin']]
        ];
        $ya_existe = Cierre::where($reglas)->get()->count() > 0;
        if($ya_existe){
          return $validator->errors()->add('id_mesa_de_panio','Ya existe un cierre para la fecha.');
        }
      }
      else{
        $reglas = [
          ['id_cierre_mesa','=',$data['id_cierre_mesa']],
          ['id_mesa_de_panio','=',$data['id_mesa_de_panio']],
          ['fecha','=',$data['fecha']]
        ];
        $no_existe = Cierre::where($reglas)->get()->count() != 1;
        if($no_existe){
          return $validator->errors()->add('id_mesa_de_panio','No existe ese cierre.');
        }
      }
      
      $todos_vacios = true;
      foreach($data['fichas'] as $f){
        $todos_vacios = $todos_vacios && is_null($f['monto_ficha']);
        if(!$todos_vacios) break;
      }
      
      if($todos_vacios) foreach($data['fichas'] as $idx => $f){
        $validator->errors()->add("fichas.$idx.monto_ficha",'El valor es requerido');
      }
      
      foreach($data['fichas'] as $idx => $f){
        $ficha = Ficha::withTrashed()->find($f['id_ficha']);
        $div = $f['monto_ficha'] / $ficha->valor_ficha;
        if(($div-floor($div)) != 0){
          $validator->errors()->add("fichas.$idx.monto_ficha",'El monto no es múltiplo del valor.');
        }
      }
    })->validate();

    return DB::transaction(function() use ($request){
      $cierre = null;
      if(is_null($request->id_cierre_mesa ?? null)){
        $cierre = new Cierre;
      }
      else{
        $cierre = Cierre::where([
          ['id_cierre_mesa','=',$request->id_cierre_mesa],
          ['id_mesa_de_panio','=',$request->id_mesa_de_panio],
          ['fecha','=',$request->fecha]
        ])->first();
      }
      
      $mesa = Mesa::withTrashed()->find($request->id_mesa_de_panio);
      $cierre->fecha       = $request->fecha;
      $cierre->hora_inicio = $request->hora_inicio;
      $cierre->hora_fin    = $request->hora_fin;
      $cierre->total_pesos_fichas_c = 0;
      $cierre->total_anticipos_c    = $request->total_anticipos_c ?? 0;
      $cierre->tipo_mesa()->associate($mesa->juego->tipo_mesa->id_tipo_mesa);
      $cierre->casino()->associate($request->id_casino);
      $cierre->fiscalizador()->associate($request->id_cargador);
      $cierre->mesa()->associate($request->id_mesa_de_panio);
      $cierre->moneda()->associate($request->id_moneda);
      $cierre->save();
      
      foreach ($cierre->detalles as $d) {
        $d->cierre()->dissociate();
        $d->delete();
      }
      
      $total_pesos_fichas_c = 0;
      foreach ($request->fichas as $f) {
        if(empty($f['monto_ficha'])) continue;
        $ficha = new DetalleCierre;
        $ficha->ficha()->associate($f['id_ficha']);
        $ficha->monto_ficha = $f['monto_ficha'] ?? 0;
        $ficha->cierre()->associate($cierre->id_cierre_mesa);
        $ficha->save();
        $total_pesos_fichas_c+=$ficha->monto_ficha;
      }
      
      $cierre->total_pesos_fichas_c = $total_pesos_fichas_c;
      $cierre->save();
      return ['cierre' => $cierre];
    });
  }

  public function obtenerCierre($id){
    $cierre = Cierre::find($id);

    return ['cierre' => $cierre ,
            'estado' => $cierre->estado,
            'fiscalizador' => $cierre->fiscalizador,
            'mesa' => $cierre->mesa];
  }
}
