<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\MaquinaAPedido;
use Illuminate\Support\Facades\DB;
use App\Casino;
use App\Maquina;
use Validator;

class MaquinaAPedidoController extends Controller
{
  private static $instance;

  private static $atributos = [
  ];

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new MaquinaAPedidoController();
    }
    return self::$instance;
  }

  public function buscarTodo(){//vista: mtmAPedido
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];


    UsuarioController::getInstancia()->agregarSeccionReciente('MTM a Pedido' , 'mtm_a_pedido');

    return view('seccionMtmAPedido' , ['casinos' => $usuario->casinos]);
  }

  public function buscarTodoInforme(){//vista: informeRelevamiento
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

    UsuarioController::getInstancia()->agregarSeccionReciente('Estadísticas de Relevamiento' , 'estadisticas_relevamientos');
    return view('seccionEstadisticasRelevamientos' , ['casinos' => $usuario->casinos]);
  }



  public function obtenerMtmAPedido($fecha,$id_sector){

    $resultados = DB::table('maquina_a_pedido')->join('maquina','maquina_a_pedido.id_maquina','=','maquina.id_maquina')
                                           ->join('isla','maquina.id_isla','=','isla.id_isla')
                                           ->where('maquina_a_pedido.fecha','=',$fecha)
                                           ->where('isla.id_sector','=',$id_sector)
                                           ->count();
    return ['cantidad' => $resultados];
  }

  public function obtenerFechasMtmAPedido($id_maquina){
    $maquina = Maquina::find($id_maquina);

    $fechas = MaquinaAPedido::where([['id_maquina','=',$id_maquina],['fecha','>=',date('Y-m-d')]])->get();

    return ['maquina' => $maquina,'fechas' => $fechas,'casino' => $maquina->casino];
  }

  public function buscarMTMaPedido(Request $request){
    $reglas = Array();
    if(!empty($request->nro_admin)){
      $reglas[]=['maquina.nro_admin', 'like' ,'%'.$request->nro_admin.'%'];
    }
    if($request->sector !=0){
      $reglas[]=['sector.id_sector', '=' , $request->sector];
    }
    if(!empty($request->isla)){
      $reglas[]=['isla.id_isla', '=' , $request->nro_isla];
    }
    if(!empty($request->fecha_inicio)){
      $reglas[]=['maquina_a_pedido.fecha', '>=' , $request->fecha_inicio];
    }
    if(!empty($request->fecha_fin)){
      $reglas[]=['maquina_a_pedido.fecha', '<=' , $request->fecha_fin];
    }

    if ($request->selectCasinos == 0) {
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $casinos = array();
      foreach($usuario->casinos as $casino){
        $casinos [] = $casino->id_casino;
      }
   }else{
     $casinos[]= $request->selectCasinos;
   }

      $sort_by = $request->sort_by;
      $resultados=DB::table('maquina_a_pedido')
      //
      ->select('maquina_a_pedido.*' , 'isla.nro_isla' , 'casino.nombre', 'sector.descripcion' , 'maquina.nro_admin')
      ->join('maquina', 'maquina_a_pedido.id_maquina', '=', 'maquina.id_maquina')
      ->join('isla', 'isla.id_isla', '=', 'maquina.id_isla')
      ->join('casino', 'casino.id_casino', '=', 'maquina.id_casino')
      ->join('sector', 'sector.id_sector', '=', 'isla.id_sector')
      ->whereIn('casino.id_casino',$casinos)
      ->where($reglas)
      ->when($sort_by,function($query) use ($sort_by){
                      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                  })
      ->paginate($request->page_size);

    return  $resultados;
  }

  public function guardarMtmAPedido(Request $request){
    Validator::make($request->all(),[
        'nro_admin' => 'required|integer|exists:maquina,nro_admin',
        'casino' => 'required|integer|exists:casino,id_casino',
        'fecha_inicio' => 'required|date|after:yesterday',
        'fecha_fin' => 'nullable|date',
    ], array(), self::$atributos)->after(function($validator){
        $fecha_inicio=date_create($validator->getData()['fecha_inicio']);
        $fecha_fin=date_create($validator->getData()['fecha_fin']);
        if($fecha_inicio > $fecha_fin){
            $validator->errors()->add('fecha_fin', 'La fecha final es mayor a la fecha de inicio.');
        }
        $MTM=Maquina::where([['nro_admin' , '=' , $validator->getData()['nro_admin']],['id_casino' , '=' ,$validator->getData()['casino']]])->get();
        if($MTM->count() != 1){
          $validator->errors()->add('cantidad_maquinas', 'Hay más de una maquina con el número de administración.');
        }
    })->validate();
    //calcular la cantidad de dias en el rango de fechas
    $inicio = strtotime($request->fecha_inicio);
    $fin = strtotime($request->fecha_fin);
    $diferencia = $fin - $inicio;
    $dias= ($diferencia / (60 * 60 * 24)) +1 ;
    $MTM=Maquina::where([['nro_admin' , '=' , $request->nro_admin],['id_casino' , '=' ,$request->casino]])->get();
    if(!empty($request->fecha_fin)){
      for ($i=0; $i < $dias ; $i++) {
          $mtm_a_pedido= new MaquinaAPedido;
          $mtm_a_pedido->fecha=   date('Y-m-d', strtotime($request->fecha_inicio. ' + ' . $i . ' days'));
          $mtm_a_pedido->id_maquina= $MTM[0]->id_maquina;
          $mtm_a_pedido->save();
      }
    }else{
      $mtm_a_pedido= new MaquinaAPedido;
      $mtm_a_pedido->fecha=   date('Y-m-d', $inicio);
      $mtm_a_pedido->id_maquina= $MTM[0]->id_maquina;
      $mtm_a_pedido->save();
    }

    return $mtm_a_pedido;
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
