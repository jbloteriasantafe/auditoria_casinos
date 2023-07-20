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
use App\Mesas\AperturaAPedido;
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
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
          
    $validator = Validator::make($request->all(),[
      'id_apertura_mesa' => 'nullable|exists:apertura_mesa,id_apertura_mesa',
      'fecha' => 'required|date',
      'hora' => 'required|date_format:"H:i"',
      'id_casino' => 'required|exists:casino,id_casino',
      'id_fiscalizador' => 'required|exists:usuario,id_usuario',
      'id_mesa_de_panio' => 'required|exists:mesa_de_panio,id_mesa_de_panio',
      'fichas' => 'required|array',
      'fichas.*.id_ficha' => 'required|exists:ficha,id_ficha',
      'fichas.*.cantidad_ficha' => 'nullable|integer|min:0',
      'id_moneda' => 'required|exists:moneda,id_moneda',
    ], [
      'required' => 'El valor es requerido',
      'exists'   => 'No existe el valor en la base de datos',
      'integer'  => 'El valor tiene que ser un numero entero',
      'min'      => 'El valor tiene que ser positivo',
    ], self::$atributos)->after(function($validator) use ($user){
      
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      
      if(!$user->usuarioTieneCasino($data['id_casino'])){
        return $validator->errors()->add('id_casino','No tiene los privilegios');
      }
      
      $mesa = Mesa::withTrashed()->where('id_mesa_de_panio','=',$data['id_mesa_de_panio'])
      ->where(function($q) use ($data){
        return $q->where('deleted_at','>',$data['fecha'])->orWhereNull('deleted_at');
      })->first();
      
      if(is_null($mesa))
        return $validator->errors()->add('id_mesa_de_panio', 'No existe la mesa.');
    
      if(is_null($data['id_apertura_mesa'])){
        $reglas = [
          ['id_mesa_de_panio','=',$data['id_mesa_de_panio']],
          ['fecha','=',$data['fecha']],
          ['id_moneda','=',$data['id_moneda']],
          ['hora','=',$data['hora']]
        ];
        $ya_existe = Apertura::where($reglas)->get()->count() > 0;
        if($ya_existe){
          $validator->errors()->add('id_mesa_de_panio','Ya existe una apertura para la fecha.');
        }
      }
      else{
        $reglas = [
          ['id_apertura_mesa','=',$data['id_apertura_mesa']],
          ['id_mesa_de_panio','=',$data['id_mesa_de_panio']],
          ['fecha','=',$data['fecha']]
        ];
        $no_existe = Apertura::where($reglas)->get()->count() != 1;
        if($no_existe){
          $validator->errors()->add('id_mesa_de_panio','No existe esa apertura.');
        }
      }
      
      if($validator->errors()->any()) return;
      
      $todos_vacios = true;
      foreach($data['fichas'] as $f){
        $todos_vacios = $todos_vacios && is_null($f['cantidad_ficha']);
        if(!$todos_vacios) break;
      }
      
      if($todos_vacios) foreach($data['fichas'] as $idx => $f){
        $validator->errors()->add("fichas.$idx.cantidad_ficha",'El valor es requerido');
      }
    })->validate();
    
    return DB::transaction(function() use ($request,$user){
      $mesa = Mesa::withTrashed()->find($request->id_mesa_de_panio);
      $apertura = null;
      if(is_null($request->id_apertura_mesa)){
        $apertura = new Apertura;
      }
      else{
        $apertura = Apertura::where([
          ['id_apertura_mesa','=',$request->id_apertura_mesa],
          ['id_mesa_de_panio','=',$request->id_mesa_de_panio],
          ['fecha','=',$request->fecha]
        ])->first();
      }
      
      $apertura->fecha = $request->fecha;
      $apertura->hora  = $request->hora;
      $apertura->total_pesos_fichas_a = 0;
      $apertura->moneda()->associate($request->id_moneda);
      $apertura->fiscalizador()->associate($request->id_fiscalizador);
      $apertura->cargador()->associate($user->id_usuario);
      $apertura->mesa()->associate($request->id_mesa_de_panio);
      $apertura->estado_cierre()->associate(1);//asociar estado cargado
      $apertura->casino()->associate($request->id_casino);
      $apertura->tipo_mesa()->associate($mesa->juego->tipo_mesa->id_tipo_mesa);
      $apertura->save();
      
      foreach ($apertura->detalles as $d) {
        $d->apertura()->dissociate();
        $d->delete();
      }
      
      $total_pesos_fichas_a = 0;
      foreach ($request['fichas'] as $f){
        if(empty($f['cantidad_ficha'])) continue;
        $ficha = new DetalleApertura;
        $ficha->ficha()->associate($f['id_ficha']);
        $ficha->cantidad_ficha = $f['cantidad_ficha'];
        $ficha->apertura()->associate($apertura->id_apertura_mesa);
        $ficha->save();
        $fixa = Ficha::find($f['id_ficha']);
        $total_pesos_fichas_a =($f['cantidad_ficha'])*$fixa->valor_ficha + $total_pesos_fichas_a;
      }
      
      $apertura->total_pesos_fichas_a = $total_pesos_fichas_a;
      $apertura->save();
      return ['apertura' => $apertura];
    });
  }
  
  public function agregarAperturaAPedido(Request $request){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = array();
    foreach($usuario->casinos as $casino){
      $casinos[]=$casino->id_casino;
    }
    $mesa = null;
    $validator=  Validator::make($request->all(),[
      'id_mesa_de_panio' => 'required|exists:mesa_de_panio,id_mesa_de_panio',
      'fecha_inicio'     => 'required|date',
      'fecha_fin'        => 'required|date',
    ], array(), self::$atributos)->after(function($validator) use ($casinos,&$mesa){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $mesa = Mesa::find($data['id_mesa_de_panio']);
      if(!in_array($mesa->juego->id_casino,$casinos)){
        $validator->errors()->add('id_mesa_de_panio','validation.required');
      }
      $fecha_inicio = date('Y-m-d',strtotime($data['fecha_inicio']));
      $fecha_fin    = date('Y-m-d',strtotime($data['fecha_fin']));
      if($fecha_inicio > $fecha_fin){
        $validator->errors()->add('fecha_fin','validation.required');
      }
    })->validate();
    $aap = null;
    DB::transaction(function () use (&$mesa,&$aap,$request){
      $aap = new AperturaAPedido;
      $aap->id_mesa_de_panio = $request->id_mesa_de_panio;
      $aap->fecha_inicio     = $request->fecha_inicio;
      $aap->fecha_fin        = $request->fecha_fin;
      $aap->save();
    });
    return 1;
  }
  
  public function buscarAperturasAPedido(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = array();
    foreach($usuario->casinos as $casino){
      $casinos[]=$casino->id_casino;
    }
    $ret = DB::table('apertura_a_pedido as aap')
    ->select('aap.*','mp.nro_mesa','jm.siglas as juego','c.nombre as casino',DB::raw('IFNULL(m.siglas,"MULTIMONEDA") as moneda'))
    ->join('mesa_de_panio as mp','mp.id_mesa_de_panio','=','aap.id_mesa_de_panio')
    ->leftJoin('moneda as m','m.id_moneda','=','mp.id_moneda')
    ->join('juego_mesa as jm','jm.id_juego_mesa','=','mp.id_juego_mesa')
    ->join('casino as c','c.id_casino','=','jm.id_casino')
    ->whereIn('jm.id_casino',$casinos)
    ->whereNull('mp.deleted_at')->whereNull('jm.deleted_at')
    ->orderBy('aap.fecha_inicio','desc')
    ->orderBy('aap.fecha_fin','desc')
    ->orderBy('jm.siglas','asc')
    ->orderBy('mp.nro_mesa','asc');

    return $ret->get();
  }
  
  public function borrarAperturaAPedido($id_apertura_a_pedido){
    DB::transaction(function () use ($id_apertura_a_pedido){
      AperturaAPedido::find($id_apertura_a_pedido)->delete();
    });
    return 1;
  }
}
