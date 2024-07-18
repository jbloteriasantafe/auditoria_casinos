<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\MaquinaAPedido;
use Illuminate\Support\Facades\DB;
use App\Casino;
use App\Maquina;
use Validator;
use App\Http\Controllers\RelevamientoController;

class MaquinaAPedidoController extends Controller
{
  private static $instance;
  private static $atributos = [];

  public static function getInstancia() {
    self::$instance = self::$instance ?? (new self());
    return self::$instance;
  }

  public function buscarTodo(){
    UsuarioController::getInstancia()->agregarSeccionReciente('MTM a Pedido' , 'mtm_a_pedido');
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    return view('seccionMtmAPedido' , ['casinos' => $usuario->casinos]);
  }

  public function buscarTodoInforme(){//vista: informeRelevamiento
    UsuarioController::getInstancia()->agregarSeccionReciente('Estadísticas de Relevamiento' , 'estadisticas_relevamientos');
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $es_superusuario = $usuario->es_superusuario;
    $casinos = $es_superusuario? Casino::all() : $usuario->casinos;
    return view('seccionEstadisticasRelevamientos' , [
      'casinos' => $casinos,
      'es_superusuario' => $es_superusuario,
      'maquinas' => Maquina::whereIn('id_casino',$casinos->pluck('id_casino'))
      ->select('id_maquina','nro_admin','id_casino')
      ->orderBy('id_casino','asc')
      ->orderBy('nro_admin','asc')
      ->get(),
      'contadores' => RelevamientoController::getInstancia()->contadores()->count()
    ]);
  }

  public function obtenerMtmAPedido($fecha,$id_sector){
    return DB::table('maquina_a_pedido')->join('maquina','maquina_a_pedido.id_maquina','=','maquina.id_maquina')
    ->join('isla','maquina.id_isla','=','isla.id_isla')
    ->where('maquina_a_pedido.fecha','=',$fecha)
    ->where('isla.id_sector','=',$id_sector)
    ->count();
  }

  public function obtenerFechasMtmAPedido(Request $request){
    $maquina = Maquina::where([['nro_admin','=',$request->nro_admin],['id_casino','=',$request->id_casino]])->first();
    if(is_null($maquina)){
      return ['maquina' => null,'fechas' => [],'casino' => null];
    }
    $fechas  = MaquinaAPedido::where([['id_maquina','=',$maquina->id_maquina],['fecha','>=',$request->fecha_inicio]])->get();
    return ['maquina' => $maquina,'fechas' => $fechas,'casino' => (is_null($maquina)? null : $maquina->casino)];
  }

  public function buscarMTMaPedido(Request $request){
    $reglas = [];
    if(isset($request->nro_admin)){
      $reglas[]=['maquina.nro_admin', 'like' ,'%'.$request->nro_admin.'%'];
    }
    if(isset($request->id_sector)){
      $reglas[]=['sector.id_sector', '=' , $request->id_sector];
    }
    if(isset($request->nro_isla)){
      $reglas[]=['isla.nro_isla', '=' , $request->nro_isla];
    }
    if(isset($request->fecha_inicio)){
      $reglas[]=['maquina_a_pedido.fecha', '>=' , $request->fecha_inicio];
    }
    if(isset($request->fecha_fin)){
      $reglas[]=['maquina_a_pedido.fecha', '<=' , $request->fecha_fin];
    }
    
    $id_casinos = UsuarioController::getInstancia()->quienSoy()['usuario']->casinos->pluck('id_casino');
    $sort_by = $request->sort_by;
    return DB::table('maquina_a_pedido')
    ->select('maquina_a_pedido.*' , 'isla.nro_isla' , 'casino.nombre', 'sector.descripcion' , 'maquina.nro_admin')
    ->join('maquina', 'maquina_a_pedido.id_maquina', '=', 'maquina.id_maquina')
    ->join('isla', 'isla.id_isla', '=', 'maquina.id_isla')
    ->join('casino', 'casino.id_casino', '=', 'maquina.id_casino')
    ->join('sector', 'sector.id_sector', '=', 'isla.id_sector')
    ->whereIn('casino.id_casino',$id_casinos)
    ->where($reglas)
    ->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    })
    ->paginate($request->page_size);
  }

  public function guardarMtmAPedido(Request $request){
    Validator::make($request->all(),[
      'nro_admin' => 'required|integer|exists:maquina,nro_admin,deleted_at,NULL',
      'id_casino' => 'required|integer|exists:casino,id_casino,deleted_at,NULL',
      'fecha_inicio' => 'required|date|after:yesterday',
      'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
    ], [
      'required' => 'El valor es requerido',
      'integer' => 'El valor tiene que ser un número',
      'exists' => 'El valor es inexistente',
      'date' => 'El valor tiene que ser una fecha en formato YYYY-MM-DD',
      'fecha_inicio.after' => 'La fecha de inicio tiene que ser a partir de hoy',
      'fecha_fin.after_or_equal' => 'La fecha final tiene que ser posterior o igual a la de inicio',
    ], self::$atributos)->after(function($validator){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $u = UsuarioController::getInstancia()->quienSoy()['usuario'];
      if(!$u->usuarioTieneCasino($data['id_casino'])){
        return $validator->errors()->add('id_casino','No tiene los privilegios');
      }
      if(Maquina::where([['id_casino','=',$data['id_casino']],['nro_admin','=',$data['nro_admin']]])->count() != 1){
        return $validator->errors()->add('nro_admin','No existe esa maquina');
      }
    })->validate();
    
    return DB::transaction(function() use ($request){
      $inicio = new \DateTimeImmutable($request->fecha_inicio);
      $fin    = new \DateTimeImmutable(isset($request->fecha_fin)? $request->fecha_fin : $request->fecha_inicio);
      $dias   = $fin->diff($inicio)->format('%a');
      $m = Maquina::where([['nro_admin','=',$request->nro_admin],['id_casino','=',$request->id_casino]])->first();
      
      $fechas = [];
      for($i=0;$i<=$dias;$i++){
        $fechas[] = $inicio->add(\DateInterval::createFromDateString("$i days"))->format('Y-m-d');
      }
      
      $ya_existen = MaquinaAPedido::where('id_maquina','=',$m->id_maquina)->whereIn('fecha',$fechas)->get()->keyBy('fecha');
      
      foreach($fechas as $f){
        if($ya_existen->has($f)) continue;
        $mtm_a_pedido = new MaquinaAPedido;
        $mtm_a_pedido->fecha      = $f;
        $mtm_a_pedido->id_maquina = $m->id_maquina;
        $mtm_a_pedido->save();
      }

      return 1;
    });
  }

  public function crearPedidoEn($id_maquina,$dias,$id_relevamiento){
    $mtm_a_pedido= new MaquinaAPedido;
    $mtm_a_pedido->fecha=   date('Y-m-d', strtotime(date('Y-m-d') . ' + ' . $dias . ' days'));
    $mtm_a_pedido->id_maquina= $id_maquina;
    $mtm_a_pedido->relevamiento()->associate($id_relevamiento);
    $mtm_a_pedido->save();
  }

  public function eliminarMTMAPedido(Request $request , $id){
    $mtm = MaquinaAPedido::find($id);
    $mtm->relevamiento()->dissociate();
    $MTM_a_pedido=MaquinaAPedido::destroy($id);
    return $MTM_a_pedido;
  }
}
