<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ContadorHorario;
use App\DetalleContadorHorario;
use Validator;
use Illuminate\Support\Facades\DB;
use App\TipoMoneda;
use App\Http\Controllers\UsuarioController;

class ContadorController extends Controller
{
  private static $atributos = [
  ];
  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new ContadorController();
    }
    return self::$instance;
  }

  // eliminarContador elimina los detalles contadores y por ultimo el contador horarios
  public function eliminarContador($id_contador){
    Validator::make(['id_contador' => $id_contador]
                   ,['id_contador' => 'required|exists:contador_horario,id_contador_horario']
                   , array(), self::$atributos)->after(function($validator){
                   })->sometimes('id_contador','exists:contador_horario,id_contador_horario',function($input){
                      $cont = ContadorHorario::find($input['id_contador']);
                      return !$cont->cerrado;
                   })->validate();
    $pdo = DB::connection('mysql')->getPdo();

    $query = sprintf(" DELETE FROM detalle_contador_horario
                       WHERE id_contador_horario = '%d'
                       ",$id_contador);

    $pdo->exec($query);

    $query = sprintf(" DELETE FROM contador_horario
                       WHERE id_contador_horario = '%d'
                       ",$id_contador);
    $pdo->exec($query);
  }
  // modificarContador modifica los detalles contador, asocaciado a un unico contador horario
  // se ajusta de acuerdo a un tipo de ajuste y se asocia al mismo
  public function modificarContador(Request $request){
    Validator::make($request->all(), [
                    'detalles' => 'nullable',
                    'detalles.*.id_contador_horario' => 'required|exists:contador_horario,id_contador_horario',
                    'detalles.*.id_detalle_contador_horario' => 'required|exists:detalle_contador_horario,id_detalle_contador_horario',
                    'detalles.*.coinin' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
                    'detalles.*.coinout' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
                    'detalles.*.jackpot' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
                    'detalles.*.progresivo' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
                    'detalles.*.id_tipo_ajuste' => 'nullable|exists:tipo_ajuste,id_tipo_ajuste'
                  ], array(), self::$atributos)->after(function($validator){
                   })->validate();

    foreach($request->detalles as $det){
      $detalle = DetalleContadorHorario::find($det['id_detalle_contador_horario']);
      $detalle->coinin = $det['coinin'];
      $detalle->coinout = $det['coinout'];
      $detalle->jackpot = $det['jackpot'];
      $detalle->progresivo = $det['progresivo'];
      $detalle->save();
      if($det['id_tipo_ajuste'] != null){
        $detalle->tipo_ajuste()->associate($det['id_tipo_ajuste']);
      }else{
        $detalle->tipo_ajuste()->dissociate();
      }
    }
  }

  // estaCerrado  retorna los contadores cerrados que coinciden con los parametros
  // se considera cerrado el contador horario cuando se ya se valido el mismo, es decir, todos los detalles contadores
  // del contador horario, fueron validados
  public function estaCerrado($fecha,$id_casino,$tipo_moneda){
    //cerrado significa que se haya validado el producido de la misma fecha en cuestion
    $contadores= ContadorHorario::where([['fecha' , '=' , $fecha],['id_casino' , '=' , $id_casino] , ['id_tipo_moneda' , '=' , $tipo_moneda->id_tipo_moneda]])->get();
    $error=array();
    if($contadores->count() == 1){
      foreach ($contadores as $contador) {
          if ($contador->cerrado !=1) {
              $error[]=$contador;
          }
      }
    }else {
      $error[] = 'Mas de un contador para el casino , fecha y tipo moneda';
    }

    return $error;
  }

  public function obtenerEstadoUltimosContadores(){
    // fecha, contadores_importados,cantidad_relevamientos_cargados,cantidad_relevamientos,validado
    $resultado = array();
    $casinos = Usuario::find(session('id_usuario'))->casinos;
    foreach($casinos as $casino){
      //DB::
    }

    return $resultado;
  }

  // estaCerradoMaquina verifica para una maquina en una fecha, si el contador horario asociado esta cerrad
  // tambien verifica si tiene importado los contadores y sus respectivo detalle contador
  public function estaCerradoMaquina($fecha,$id_maquina){
    $resultado = DetalleContadorHorario::join('contador_horario' , 'detalle_contador_horario.id_contador_horario' , '=' , 'contador_horario.id_contador_horario')
                                         ->where([['contador_horario.fecha' ,$fecha],['detalle_contador_horario.id_maquina' , $id_maquina]])
                                         ->get();
    if($resultado->count() == 1){
      $cerrado = $resultado[0]->contador_horario->cerrado;
      $detalle = $resultado[0];
      $importado = 1;
    }else{
      $detalle= null;
      $cerrado = 0;
      $importado = 0;
    }
    return ['importado' => $importado , 'cerrado' => $cerrado, 'detalle' =>$detalle];
  }

  public function buscarTodo(){
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    return view('seccionContadores',['casinos' => $user->casinos,'tipo_monedas' => TipoMoneda::all()]);
  }

  public function buscarContadores(Request $request){
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $cas = [];
    foreach($user->casinos as $c) $cas[] = $c->id_casino;

    $reglas = [];
    if($request->id_casino != "") $reglas[] = ['ch.id_casino','=',$request->id_casino];
    if($request->id_tipo_moneda != "") $reglas[] = ['ch.id_tipo_moneda','=',$request->id_tipo_moneda];
    if($request->fecha_desde != "") $reglas[] = ['ch.fecha','>=',$request->fecha_desde];
    if($request->fecha_hasta != "") $reglas[] = ['ch.fecha','<=',$request->fecha_hasta];
    $sort_by = ['columna' => 'ch.id_contador_horario','orden' => 'desc'];
    if(!empty($request->sort_by)) $sort_by = $request->sort_by;

    $resultados = DB::table('contador_horario as ch')
    ->select('ch.id_contador_horario','ch.fecha','c.nombre as casino','tm.descripcion as moneda',DB::raw('RAND()>0.5 as alertas_validadas'))
    ->join('casino as c','c.id_casino','=','ch.id_casino')
    ->join('tipo_moneda as tm','tm.id_tipo_moneda','=','ch.id_tipo_moneda')
    ->whereIn('ch.id_casino',$cas)->where($reglas)
    ->orderBy($sort_by['columna'],$sort_by['orden'])
    ->paginate($request->page_size);
    return $resultados;
  }

  public function obtenerDetalles($id_contador_horario){
    $detalles = DB::table('detalle_contador_horario')
    ->select('maquina.nro_admin','detalle_contador_horario.id_detalle_contador_horario')
    ->join('maquina','maquina.id_maquina','=','detalle_contador_horario.id_maquina')
    ->where('id_contador_horario',$id_contador_horario)->get();
    return ['detalles' => $detalles,'alertas' => 9999999];
  }

  public function obtenerDetalleCompleto($id_detalle_contador_horario){
    //@STUB: tal vez guardar los demas horarios en un CSV y consultarlos aca, total es algo que se consultaria 1 sola vez
    //Si guardamos el CSV que mandan ellos, tendrian que mandarlo ordenado por NRO_ADMIN y luego por HORA para hacer la busqueda eficiente.
    $detalles = DB::table('detalle_contador_horario as dch')
    ->selectRaw('"07:00" as hora,IFNULL(dch.isla,"SIN INF.") as isla, dch.coinin, dch.coinout, dch.jackpot, dch.progresivo')
    ->where('dch.id_detalle_contador_horario',$id_detalle_contador_horario)->get();
    $alertas = [
      [
        'hora' => '9:99', 'descripcion' => 'TEST!'
      ],
      [
        'hora' => '99:09', 'descripcion' => '......TEST!'
      ]
    ];
    return ['estado' => 'SIN DETALLES','detalles' => $detalles,'alertas' => $alertas,'observaciones' => 'OBSERVACIONES TEST'];
  }
}
