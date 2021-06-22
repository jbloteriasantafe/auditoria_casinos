<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Producido;
use App\Casino;
use App\TipoMoneda;
use App\Maquina;
use App\DetalleProducido;
use App\AjusteProducido;
use App\DetalleContadorHorario;
use App\ContadorHorario;
use View;
use Dompdf\Dompdf;
use App\TipoAjuste;
use App\Http\Controllers\FormatoController;
use Illuminate\Validation\Rule;

class ProducidoController extends Controller
{
  private static $instance;

  private static $atributos=[];

  private function obtenerDiferencias($id_producido,$id_maquina = null){
    //la denominacion carga es la que se utilizo para convertir a plata al momento de importar, la denominacion sale de la que tenia la maquina al importarte que no necesariamente es la misma al momento de validar producido
    //para santa fe y melincue que se importa en pesos, la denominacion de carga es 1, por lo que no afecta, pero en rosario, que se importa en creditos, si importa y tiene q ser distinto de 1
    //conclusion, si es de Santa Fe queda en plata, porque la denominacion es 1, si es de rosario se hace un cambio previo para cambiarlo a creditos
    
    //Lo paso a creditos, en Rosario se usa "denominacion_carga" porque se toma la que esta al momento al importar
    //En SantaFe/Mel uso la de la maquina (tiene denominacion_carga = 1 por defecto)
    //Como la division es de izquierda a derecha, termina pasando a creditos
    //x/denominacion_carga/denominacion
    //Si es de SantaFe Melinque => x/1/denominacion = x/denominacion
    //Si es de Rosario => x/denominacion_carga/1 = x/denominacion_carga

    $a_credito_ini = 'IFNULL(dc_ini.denominacion_carga,1.0)/IF(p.id_casino = 3,1.0,m.denominacion)';
    $coinin_ini = 'IFNULL(dc_ini.coinin,.0)/'.$a_credito_ini;
    $coinout_ini = 'IFNULL(dc_ini.coinout,.0)/'.$a_credito_ini;
    $jackpot_ini = 'IFNULL(dc_ini.jackpot,.0)/'.$a_credito_ini;
    $progresivo_ini = 'IFNULL(dc_ini.progresivo,.0)/'.$a_credito_ini;
    $a_credito_fin = 'IFNULL(dc_fin.denominacion_carga,1.0)/IF(p.id_casino = 3,1.0,m.denominacion)';
    $coinin_fin = 'IFNULL(dc_fin.coinin,.0)/'.$a_credito_fin;
    $coinout_fin = 'IFNULL(dc_fin.coinout,.0)/'.$a_credito_fin;
    $jackpot_fin = 'IFNULL(dc_fin.jackpot,.0)/'.$a_credito_fin;
    $progresivo_fin = 'IFNULL(dc_fin.progresivo,.0)/'.$a_credito_fin;

    //plata para santa fe y credito para rosario
    $valor_inicio = sprintf('%s - %s - %s - %s',$coinin_ini,$coinout_ini,$jackpot_ini,$progresivo_ini);
    $valor_final  = sprintf('%s - %s - %s - %s',$coinin_fin,$coinout_fin,$jackpot_fin,$progresivo_fin);
    //Se pasa a plata para comparar 
    $delta        = sprintf('ROUND(m.denominacion*((%s) - (%s)),2)',$valor_final,$valor_inicio);//denominacion * (creditos-creditos) = plata
    $diferencia   = sprintf('ROUND((%s)-dp.valor,2)',$delta);//plata - plata
    $valor_cred   = 'dp.valor/m.denominacion'; //(Al parecer) El dp.valor esta siempre en plata independiente del casino

    $retorno = DB::table('producido as p')
    ->join('detalle_producido as dp','dp.id_producido','=','p.id_producido')
    ->join('maquina as m','m.id_maquina','=','dp.id_maquina')
    ->join('contador_horario as cont_ini',function($j){
      return $j->on('cont_ini.fecha','=','p.fecha')
      ->on('cont_ini.id_casino','=','p.id_casino')->on('cont_ini.id_tipo_moneda','=','p.id_tipo_moneda');
    })
    ->join('contador_horario as cont_fin',function($j){
      return $j->on('cont_fin.fecha','=',DB::raw('DATE_ADD(p.fecha,INTERVAL 1 DAY)'))
      ->on('cont_fin.id_casino','=','p.id_casino')->on('cont_fin.id_tipo_moneda','=','p.id_tipo_moneda');
    })
    ->leftJoin('detalle_contador_horario as dc_ini',function($j){
      return $j->on('dc_ini.id_contador_horario','=','cont_ini.id_contador_horario')->on('dc_ini.id_maquina','=','m.id_maquina');
    })
    ->leftJoin('detalle_contador_horario as dc_fin',function($j){
      return $j->on('dc_fin.id_contador_horario','=','cont_fin.id_contador_horario')->on('dc_fin.id_maquina','=','m.id_maquina');
    })
    ->where('p.id_producido','=',$id_producido)->whereNull('dp.id_tipo_ajuste')
    ->orderBy('m.nro_admin','asc')
    ->selectRaw('p.id_casino as casino, dp.valor as producido_dinero, dp.id_detalle_producido,
    m.nro_admin, m.id_maquina, m.denominacion,
    dc_ini.id_detalle_contador_horario as id_detalle_contador_inicial,'.$coinin_ini.' as coinin_inicio,'.$coinout_ini.' as coinout_inicio,'.$jackpot_ini.' as jackpot_inicio,'.$progresivo_ini.' as progresivo_inicio,
    dc_fin.id_detalle_contador_horario as id_detalle_contador_final,  '.$coinin_fin.' as coinin_final,'.$coinout_fin.' as coinout_final,'.$jackpot_fin.' as jackpot_final,'.$progresivo_fin.' as progresivo_final,
    '.$delta.' as delta,'.$diferencia.' as diferencia,'.$valor_cred.' as producido_cred')
    ->whereRaw($diferencia.' <> 0');

    //El valor opcional de id_maquina es para solo obtener una fila, hace mas rapido el request
    if(!is_null($id_maquina)) $retorno = $retorno->where('m.id_maquina','=',$id_maquina);

    return $retorno->get();
  }

  private function contadoresDeProducido($id_producido){
    $p = Producido::find($id_producido);
    $inicial = ContadorHorario::where([['fecha','=',$p->fecha ],['id_casino','=',$p->id_casino],['id_tipo_moneda','=',$p->id_tipo_moneda]])->get();
    $fecha_fin = date("Y-m-d", strtotime($p->fecha." +1 days"));
    $final   = ContadorHorario::where([['fecha','=',$fecha_fin],['id_casino','=',$p->id_casino],['id_tipo_moneda','=',$p->id_tipo_moneda]])->get();
    return ['inicial' => $inicial, 'final' => $final];
  }

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new ProducidoController();
    }
    return self::$instance;
  }

  public function buscarTodo(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Producidos' ,'producidos') ;
    return view('seccionProducidos' , ['casinos' => $usuario->casinos, 'producidos' => [],'monedas' => TipoMoneda::all()]);
  }

  // buscarProducidos
  public function buscarProducidos(Request $request){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    
    $casinos = [];
    foreach ($usuario->casinos as $casino) $casinos[] = $casino->id_casino;

    $reglas = [];
    
    if($request->id_casino != null)      $reglas[] = ['p.id_casino','=',$request->id_casino];
    if($request->fecha_inicio != null)   $reglas[] = ['p.fecha','>=',$request->fecha_inicio];
    if($request->fecha_fin != null)      $reglas[] = ['p.fecha','<=',$request->fecha_fin];
    if($request->id_tipo_moneda != null) $reglas[] = ['p.id_tipo_moneda','=',$request->id_tipo_moneda];
    if($request->validado  != '-')       $reglas[] = ['p.validado','=',$request->validado];
    
    $sort_by = $request->sort_by;
    if(empty($sort_by)) $sort_by = ['columna' => 'p.fecha','orden' => 'desc'];

    $resultado = DB::table('producido as p')
    ->select('p.id_producido','p.fecha','p.validado as producido_validado','tm.descripcion as moneda','cas.nombre as casino')
    ->selectRaw('IF(COUNT(distinct cont_ini.id_contador_horario) <> 1,
                      "Cantidad incorrecta de contador",
                      IF(SUM(cont_ini.cerrado) = 0,"Contador sin cerrar",NULL)
                    ) as error_contador_ini')
    ->selectRaw('IF(COUNT(distinct cont_fin.id_contador_horario) <> 1,
                      "Cantidad incorrecta de contador",
                      NULL
                    ) as error_contador_fin')
    ->selectRaw('IF(COUNT(distinct sec.id_sector) <> COUNT(distinct r_val.id_sector),
                      "No todos los sectores estan relevados o validados",
                      NULL
                    ) as error_relevamientos')
    ->join('tipo_moneda as tm','tm.id_tipo_moneda','=','p.id_tipo_moneda')
    ->join('casino as cas','cas.id_casino','=','p.id_casino')
    ->leftJoin('contador_horario as cont_ini',function($j){
      return $j->on('cont_ini.fecha','=','p.fecha')
      ->on('cont_ini.id_casino','=','p.id_casino')->on('cont_ini.id_tipo_moneda','=','p.id_tipo_moneda');
    })
    ->leftJoin('contador_horario as cont_fin',function($j){
      return $j->on('cont_fin.fecha','=',DB::raw('DATE_ADD(p.fecha,INTERVAL 1 DAY)'))
      ->on('cont_fin.id_casino','=','p.id_casino')->on('cont_fin.id_tipo_moneda','=','p.id_tipo_moneda');
    })
    ->leftJoin('sector as sec','sec.id_casino','=','p.id_casino')
    ->leftJoin('relevamiento as r_val',function($j){//Relevamientos validados
      return $j->on('r_val.id_sector','=','sec.id_sector')
      ->on('r_val.fecha','=',DB::raw('DATE_ADD(p.fecha,INTERVAL 1 DAY)'))
      ->where('r_val.backup','=','0')->whereIn('r_val.id_estado_relevamiento',[4,7]);
    })
    ->where($reglas)->whereIn('p.id_casino',$casinos)
    ->groupBy('p.id_producido','p.fecha','p.validado','tm.descripcion','cas.nombre')
    ->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    });

    return $resultado->paginate($request->page_size);
  }

  // eliminarProducido elimina el producido y los detalles producidos asociados
  public function eliminarProducido($id_producido,$validar = true){
    if($validar) Validator::make(['id_producido' => $id_producido],
    ['id_producido' => 'required|exists:producido,id_producido'],
    [], self::$atributos)->sometimes('id_producido','exists:producido,id_producido',function($input){
      $prod = Producido::find($input['id_producido']);
      return !$prod->validado;
    })->validate();
    
    DB::transaction(function() use ($id_producido){
      $prod = Producido::find($id_producido);
      foreach($prod->detalles as $d){
        $a = $d->ajuste_producido;
        if(!is_null($a)) $a->delete();
        $d->delete();
      }
      $prod->delete();
    });
  }

  // datosAjusteMTM obitne los producidos con las maquinas que dan diferencias
  // con la informacion necesaria para ser evaluados por el auditor
  public function datosAjusteMTM($id_maquina,$id_producido){
    return ['producidos_con_diferencia' => $this->obtenerDiferencias($id_producido,$id_maquina),
            'tipos_ajuste' => TipoAjuste::all()];
  }

  // ajustarProducido
  public function ajustarProducido($id_producido){//valido en vista que se pueda cargar.
      //Son las maquinas que efectivamente dan diferencia junto con el valor que difiere
      $diferencias = $this->obtenerDiferencias($id_producido);

      // Si no tiene ajustes y hay diferencias, le crea los ajustes. (primera vez que se abre el producido) 
      // Se fija si lo puede ajustar automaticoamente
      // Si no puede, lo devuelve como respuesta para ajustar manualmente
      $producido = Producido::find($id_producido);
      if($producido->ajustes_producido->count() == 0){
        $diferencias_filtradas = [];//Tal vez se lo pueda meter adentro, no estoy seguro como afecta el scoping la ultima linea
        DB::transaction(function() use ($diff,&$diferencias,&$diferencias_filtradas){
          foreach ($diferencias as $diff) {
            $diferencia_ajuste = new AjusteProducido;
            $diferencia_ajuste->producido_calculado  = $diff['delta'];
            $diferencia_ajuste->producido_sistema    = $diff['producido_dinero'];
            $diferencia_ajuste->diferencia           = $diff['diferencia'];
            $diferencia_ajuste->id_detalle_producido = $diff['id_detalle_producido'];
            $diferencia_ajuste->save();

            // veo si puedo hacer vuelta de contadores automaticamente
            if(!$this->probarAjusteAutomatico($diff)){//Si no hay ajuste, lo devuelvo como diferencia
              $diferencias_filtradas[]=$diff;
            }
          }
          $diferencias = $diferencias_filtradas;
        });
      }

      if(count($diferencias) == 0) {
        DB::transaction(function() use ($producido,$id_producido){
          $producido->validado = 1;
          $producido->save();
          //Siempre hay contador final? puede ser nulo?
          $contador_final = $this->contadoresDeProducido($id_producido)['final'];
          $contador_final->cerrado= 1;
          $contador_final->save();
        });
      }

      return ['producidos_con_diferencia' => $diferencias,
              'tipos_ajuste' => TipoAjuste::all(),
              'validado' => ['estaValidado' => $producido->validado],
              'fecha_produccion' => $producido->fecha,
              'moneda' => $producido->tipo_moneda];
  }

  private function recalcularDiferencia($arr){
    $valor_inicio = $arr['coinin_inicio'] - $arr['coinout_inicio'] - $arr['jackpot_inicio'] - $arr['progresivo_inicio'];//credito
    $valor_final  = $arr['coinin_final']  - $arr['coinout_final']  - $arr['jackpot_final']  - $arr['progresivo_final'];//credito
    $delta        = $valor_final - $valor_inicio;//credito - credito
    $diferencia   = round($delta*$arr['denominacion'], 2) - $arr['producido_dinero']; //plata - plata
    return round($diferencia,2);
  }

  // genera los ajustes automaticos que pueden ser calculados numericamente de forma automatica
  public function probarAjusteAutomatico($diff){
    return false;//DESHABILITO EL AJUSTE AUTOMATICO, HAY QUE PROBARLO. ESTA MAL CREO COMO ESTA

    $contadores = ['coinin','coinout','jackpot','progresivo'];

    if(fmod($arreglo_diferencia['diferencia'],1000000) == 0){
      foreach($contadores as $c){
        $contador_final  = $c.'_final';
        $contador_inicio = $c.'_inicio';
        //En principio si final > inicio y le sumamos, seria invariante y no haria nada
        //@SPEED: Tal vez se puede hacer por DB?
        if($contador_final < $contador_inicio){
          $diff[$contador_final] += 100000000;//Le suma una vuelta de contadores
          $diferencia = $this->recalcularDiferencia($diff);
          $diff[$contador_final] -= 100000000;//Lo vuelvo al original
          if($diferencia == 0){
            $detalle_producido = DetalleProducido::find($diff['id_detalle_producido']);
            $detalle_producido->id_tipo_ajuste = 1;
            $detalle_producido->save();
            return true;
          }
        }
      }
    }
    //si TODOS los contadores finales son menores o iguales a los iniciales -> posible reset
    //@SPEED: Tambien chequeable por DB
    $posible_reset_contadores = true;
    foreach($contadores as $c){
      $posible_reset_contadores = $posible_reset_contadores && ($diff[$c.'_final'] <= $diff[$c.'_inicio']);
    }
    if($posible_reset_contadores){
      //No estoy seguro porque a los finales les suma los iniciales...
      //No seria equivalente a poner los iniciales en 0? ver recalcularDiferencia
      foreach($contadores as $c) $diff[$t.'_final'] += $diff[$t.'_inicial'];
      $diferencia = $this->recalcularDiferencia($diff);
      foreach($contadores as $c) $diff[$t.'_final'] -= $diff[$t.'_inicial'];
      if($diferencia == 0){
        $detalle_producido = DetalleProducido::find($diff['id_detalle_producido']);
        $detalle_producido->id_tipo_ajuste = 2;
        $detalle_producido->save();
        return true;
      }
    }
    return false;
  }

  // guardarAjuste guarda el ajuste realizado por el auditor
  // solo se guarda si luego del ajuste , la diferencia es nula, es decir, es correto el ajuste
  public function guardarAjuste(Request $request){
      Validator::make($request->all(), [
              'producidos_ajustados' => 'nullable',
              'producidos_ajustados.*.coinin_inicial' => 'required|integer',
              'producidos_ajustados.*.coinin_final' => 'required|integer',
              'producidos_ajustados.*.coinout_inicial' => 'required|integer',
              'producidos_ajustados.*.coinout_final' => 'required|integer',
              'producidos_ajustados.*.jackpot_inicial' => 'required|integer',
              'producidos_ajustados.*.jackpot_final' => 'required|integer',
              'producidos_ajustados.*.progresivo_inicial' => 'required|integer',
              'producidos_ajustados.*.progresivo_final' => 'required|integer',
              'producidos_ajustados.*.id_detalle_producido' => 'required|exists:detalle_producido,id_detalle_producido',
              'producidos_ajustados.*.id_detalle_contador_inicial' => 'nullable|exists:detalle_contador_horario,id_detalle_contador_horario',
              'producidos_ajustados.*.id_detalle_contador_final' => 'nullable|exists:detalle_contador_horario,id_detalle_contador_horario',
              'producidos_ajustados.*.producido' => ['required','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
              'producidos_ajustados.*.prodObservaciones' => 'nullable',
              'estado' => 'required',//3 finalizado, 2 pausa
      ], array(), self::$atributos)->after(function($validator){})->validate();

      $resultados = [];
      $errores    = [];
      $estado     = 0;

      if($request->estado != 3){
        return [//Deprecado
          'estado'    => $estado,
          'resueltas' => $resultados,
          'errores'   => $errores,
        ];
      }

      foreach ($request->producidos_ajustados as $detalle_ajustado){
        $detalle_final     = DetalleContadorHorario::find($detalle_ajustado['id_detalle_contador_final']) ;
        $detalle_inicio    = DetalleContadorHorario::find($detalle_ajustado['id_detalle_contador_inicial']) ;
        $detalle_producido = DetalleProducido::find($detalle_ajustado['id_detalle_producido']);
        $producido         = Producido::find($detalle_producido->id_producido);
        $casino            = $producido->id_casino;
        //se agregan las observaciones, estas son independientes del tipo de ajuste, el propio del detalle producido
        $detalle_producido->observacion = $detalle_ajustado['prodObservaciones'];

        switch ($detalle_ajustado['id_tipo_ajuste']) {
          case 1: // vuelta contadores
          case 2: // reset contadores
            if($this->validarDiferenciaConFinalesModificados($detalle_ajustado['coinin_final'], $detalle_ajustado['coinout_final'], $detalle_ajustado['jackpot_final'], $detalle_ajustado['progresivo_final'], 
                                                              $detalle_inicio, $detalle_producido, $detalle_ajustado['denominacion'])){
              $detalle_producido->id_tipo_ajuste = $detalle_ajustado['id_tipo_ajuste'];
              $detalle_producido->save();
              $resultados[] = $detalle_ajustado['id_maquina'];
            }else{
              $errores[] = $detalle_ajustado['id_maquina'];
            }
            break;
          case 3://sin contadores finales - cambi contadores finales
              if($this->validarDiferenciaConFinalesModificados($detalle_ajustado['coinin_final'], $detalle_ajustado['coinout_final'], $detalle_ajustado['jackpot_final'], $detalle_ajustado['progresivo_final'], 
                                                               $detalle_inicio, $detalle_producido, $detalle_ajustado['denominacion'])){
                if($detalle_final == null){
                  $detalle_final = new DetalleContadorHorario;
                  $detalle_final->id_contador_horario = $request->id_contador_final;
                  $detalle_final->id_maquina = $detalle_ajustado['id_maquina'];
                }
                $detalle_final->coinin = round($detalle_ajustado['coinin_final'] * $detalle_ajustado['denominacion'] , 2 );
                $detalle_final->coinout = round($detalle_ajustado['coinout_final'] *  $detalle_ajustado['denominacion'] , 2 );
                $detalle_final->jackpot = round($detalle_ajustado['jackpot_final'] * $detalle_ajustado['denominacion'] , 2 );
                $detalle_final->progresivo = round($detalle_ajustado['progresivo_final'] *  $detalle_ajustado['denominacion'] , 2 );
                // si el casino es de rosario se carga la denominacion de carga
                if($producido->id_casino==3){
                  $detalle_final->denominacion_carga=$detalle_ajustado['denominacion'];
                }

                $detalle_final->save();

                $detalle_producido->id_tipo_ajuste= $detalle_ajustado['id_tipo_ajuste'];
                $detalle_producido->save() ;
                $resultados[]=$detalle_ajustado['id_maquina'];
              }else {
                $errores[]=$detalle_ajustado['id_maquina'];
              }

              break;
          case 4://error / cambio
              if($this->validarDiferenciaConProducidoModificados($detalle_inicio, $detalle_final,$detalle_ajustado['producido'] , $detalle_ajustado['denominacion'])){
                $detalle_producido->valor=$detalle_ajustado['producido'];
                $detalle_producido->id_tipo_ajuste= $detalle_ajustado['id_tipo_ajuste'] ;
                $detalle_producido->save() ;
                $resultados[]=$detalle_ajustado['id_maquina'];
              }else {
                $errores[]=$detalle_ajustado['id_maquina'];
              }

              break;
          case 5://sin contadores iniciales o Cambio
              if($this->validarDiferenciaConInicialesModificados($detalle_ajustado['coinin_inicial'], $detalle_ajustado['coinout_inicial'], $detalle_ajustado['jackpot_inicial'],$detalle_ajustado['progresivo_inicial'] ,$detalle_final,$detalle_producido , $detalle_ajustado['denominacion']) ){
                if($detalle_inicio == null){
                  $detalle_inicio = new DetalleContadorHorario;
                  $detalle_inicio->id_maquina = $detalle_ajustado['id_maquina'];
                  $detalle_inicio->id_contador_horario = $request->id_contador_inicial;
                }
                $detalle_inicio->coinin = round($detalle_ajustado['coinin_inicial'] * $detalle_ajustado['denominacion'] , 2 );
                $detalle_inicio->coinout = round($detalle_ajustado['coinout_inicial'] * $detalle_ajustado['denominacion'] , 2 );
                $detalle_inicio->jackpot = round($detalle_ajustado['jackpot_inicial'] * $detalle_ajustado['denominacion'] , 2 );
                $detalle_inicio->progresivo = round($detalle_ajustado['progresivo_inicial'] * $detalle_ajustado['denominacion'] , 2 );
                // si el casino es de rosario, se tiene que cargar la denominacion de carga
                if($producido->id_casino==3){
                  $detalle_inicio->denominacion_carga=$detalle_ajustado['denominacion'];
                }
                $detalle_inicio->save();

                $detalle_producido->id_tipo_ajuste= $detalle_ajustado['id_tipo_ajuste'] ;
                $detalle_producido->save();
                $resultados[]=$detalle_ajustado['id_maquina'];
              }else {
                $errores[]=$detalle_ajustado['id_maquina'];
              }

              break;

          case 6: //validar con todo lo nuevo
              if($detalle_inicio == null ){
                $detalle_inicio = new DetalleContadorHorario;
                $detalle_inicio->id_maquina = $detalle_ajustado['id_maquina'];
                $detalle_inicio->id_contador_horario = $request->id_contador_inicial;
              }
              if($detalle_final == null){
                $detalle_final = new DetalleContadorHorario;
                $detalle_final->id_contador_horario = $request->id_contador_final;
                $detalle_final->id_maquina = $detalle_ajustado['id_maquina'];
              }
              $detalle_inicio->coinin =round( $detalle_ajustado['coinin_inicial'] * $detalle_ajustado['denominacion'] , 2 );
              $detalle_inicio->coinout = round($detalle_ajustado['coinout_inicial'] * $detalle_ajustado['denominacion'] , 2 );
              $detalle_inicio->jackpot = round($detalle_ajustado['jackpot_inicial'] * $detalle_ajustado['denominacion'] , 2 );
              $detalle_inicio->progresivo = round($detalle_ajustado['progresivo_inicial'] * $detalle_ajustado['denominacion'] , 2 );
              // si el casino es de rosario, se tiene que cargar la denominacion de carga
              if($producido->id_casino==3){
                $detalle_inicio->denominacion_carga=$detalle_ajustado['denominacion'];
              }
              $detalle_inicio->save();

              $detalle_final->coinin = round($detalle_ajustado['coinin_final'] * $detalle_ajustado['denominacion'] , 2 );
              $detalle_final->coinout = round($detalle_ajustado['coinout_final'] * $detalle_ajustado['denominacion'] , 2 );
              $detalle_final->jackpot = round($detalle_ajustado['jackpot_final'] * $detalle_ajustado['denominacion'] , 2 );
              $detalle_final->progresivo = round($detalle_ajustado['progresivo_final'] * $detalle_ajustado['denominacion'] , 2 );
              // si el casino es de rosario, se tiene que cargar la denominacion de carga
              if($producido->id_casino==3){
                $detalle_final->denominacion_carga=$detalle_ajustado['denominacion'];
              }
              $detalle_final->save();

              $detalle_producido->id_tipo_ajuste= $detalle_ajustado['id_tipo_ajuste'] ;
              //es posible que dentro del multiple ajuste se cambie el valor del producido
              $detalle_producido->valor=$detalle_ajustado['producido'];
              $detalle_producido->save() ;
              $resultados[]=$detalle_ajustado['id_maquina'];
              break;
          default:
              break;
        }
      }
      //DETERMINAR ESTADO
      $estado = $this->determinarEstadoAjuste($request->id_producido);
      if($estado == 3){ //si todas las diferencias estan ajustadas contadores finales quedan validados y producido validado
          $producido = Producido::find($request->id_producido);
          $producido->validado = 1 ;
          $producido->save();

          $contador_horario = $detalle_final->contador_horario;
          $contador_horario->cerrado = 1; // si valido el producido el contador tambien se cierra
          $contador_horario->save();
      }else{
        $estado = 1;// validacion finalizada para esta mtm
      }
  }

  // generarPlanilla crea la planilla del producido total del dia, con todas las maquinas que dieron diferencia
  // junto a los ajustes , ya sean automaticos o manual
  public function generarPlanilla($id_producido){
    $producido = Producido::find($id_producido);
    $resultados = DB::table('detalle_producido')->join('ajuste_producido','detalle_producido.id_detalle_producido','=','ajuste_producido.id_detalle_producido')
                                        ->leftJoin('tipo_ajuste','detalle_producido.id_tipo_ajuste','=','tipo_ajuste.id_tipo_ajuste')
                                        ->join('maquina', 'maquina.id_maquina','=','detalle_producido.id_maquina')
                                        ->where('detalle_producido.id_producido',$id_producido)
                                        ->select('maquina.nro_admin as nro_maquina','ajuste_producido.producido_calculado as prod_calc',
                                        'ajuste_producido.producido_sistema as prod_sist','ajuste_producido.diferencia as diferencia','tipo_ajuste.descripcion as d','detalle_producido.valor as prod_calc_operado', 'detalle_producido.observacion as obs')
                                        ->orderBy('nro_maquina','asc')
                                        ->get();

    $pro= new \stdClass();
    $pro->casinoNom = $producido->casino->nombre;
    $pro->tipo_moneda = $producido->tipo_moneda->descripcion;
    if($pro->tipo_moneda == 'ARS'){
      $pro->tipo_moneda = 'Pesos';
    }
    else{
      $pro->tipo_moneda = 'Dólares';
    }
    $año = $producido->fecha[0].$producido->fecha[1].$producido->fecha[2].$producido->fecha[3];
    $mes = $producido->fecha[5].$producido->fecha[6];
    $dia = $producido->fecha[8].$producido->fecha[9];
    $pro->fecha_prod = $dia."-".$mes."-".$año;

    $ajustes = array();
    $MTMobservaciones= array();
    foreach($resultados as $resultado){
      $res = new \stdClass();
      $res->maquina = $resultado->nro_maquina;
      $res->calculado = number_format($resultado->prod_calc, 2, ",", ".");
      $res->sistema = number_format($resultado->prod_sist, 2, ",", ".");
      $res->dif = number_format($resultado->diferencia, 2, ",", ".");
      $res->descripcion = $resultado->d;
      $res->calculado_operado=number_format($resultado->prod_calc_operado, 2, ",", ".");
      $ajustes[] = $res;
      // agrego a una lista todas aquellas mtm con observaciones para ser motrada en otra tabla
      if ($resultado->obs!=""){
        $resObs=new \stdClass();
        $resObs->maquina = $resultado->nro_maquina;
        $resObs->observacion=$resultado->obs;
        $MTMobservaciones[]=$resObs;
      }
    };

    $view = View::make('planillaProducidos',compact('ajustes','pro','MTMobservaciones'));

    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();

    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }

  private function validarDiferenciaConFinalesModificados($coinin,$coinout,$jackpot,$progresivo,$detalle_inicial,$detalle_producido, $denominacion){
      $valor_inicio = $detalle_inicial->coinin - $detalle_inicial->coinout - $detalle_inicial->jackpot - $detalle_inicial->progresivo;//plata
      $valor_final  = $coinin - $coinout - $jackpot - $progresivo;//esto es creditos
      $delta = round(($valor_final * $denominacion) , 2 )  - $valor_inicio ;
      $diferencia= round($delta , 2) - $detalle_producido->valor;
      $diferencia_final= round(($diferencia), 2);
      if ($diferencia_final == 0) {
        return true;
      }else {
        return false;
      }
  }

  private function validarDiferenciaConInicialesModificados($coinin,$coinout,$jackpot,$progresivo,$detalle_final,$detalle_producido , $denominacion){
    $valor_final = $detalle_final->coinin - $detalle_final->coinout - $detalle_final->jackpot - $detalle_final->progresivo;// plata
    $valor_inicio = $coinin - $coinout - $jackpot - $progresivo; //creditos
    $delta = $valor_final - round(($valor_inicio*$denominacion) , 2) ;
    $diferencia= round($delta, 2) - $detalle_producido->valor;
    $diferencia_final= round(($diferencia), 2);
    if ($diferencia_final == 0) {
      return true;
    }else {
      return false;
    }
  }

  private function validarDiferenciaConProducidoModificados($detalle_inicial,$detalle_final, $valor_producido, $denominacion){//deberia hacerlo en creditos ?
    $valor_final = $detalle_final->coinin - $detalle_final->coinout - $detalle_final->jackpot - $detalle_final->progresivo; //plata
    $valor_inicio = $detalle_inicial->coinin - $detalle_inicial->coinout - $detalle_inicial->jackpot - $detalle_inicial->progresivo; //plata
    $delta = $valor_final - $valor_inicio ; //plata
    $diferencia= round($delta, 2) - $valor_producido; //plata
    $diferencia_final= round(($diferencia), 2);//plata
    if ($diferencia_final == 0) {
      return true;
    }else {
      return false;
    }
  }

  public function determinarEstadoAjuste($id_producido){//determinar si el ajuste fue guarda temporal o esta completo
    $producido = Producido::find($id_producido);
    $faltantes=0;
    foreach ($producido->ajustes_producido as $diferencia) {
        if($diferencia->detalle_producido->id_tipo_ajuste == null){
          $faltantes++;
        }
    }
    if($faltantes == 0){
      $estado = 3 ; //no existen mas diferencias
    }else {
      $estado =  2;
    }
    return $estado;
  }

  public function checkEstado($id_producido){// 1 -> listo para ajustar, 0 -> falta algo para poder ajustar
    $producido = Producido::find($id_producido);
    $retorno=1;
    $fecha_inicio = $producido->fecha;
    $fecha_fin=date('Y-m-d' , strtotime($producido->fecha. ' + 1 days'));

    $cerrado= ContadorController::getInstancia()->estaCerrado($fecha_inicio,$producido->id_casino,$producido->tipo_moneda);
    if(!empty($cerrado)){
      $retorno= 0;
    }
    $validado=RelevamientoController::getInstancia()->estaValidado($fecha_fin,$producido->id_casino ,$producido->tipo_moneda);
    if(!empty($validado)){
      $retorno= 0;
    }
    return ['estado' => $retorno , 'id_casino' => $producido->id_casino];
  }

  public function estaValidadoMaquina($fecha,$id_maquina){
    $resultado = DetalleProducido::join('producido' , 'producido.id_producido','=','detalle_producido.id_producido')
                                    ->where([['producido.fecha' , $fecha],['detalle_producido.id_maquina' , $id_maquina]])
                                    ->get();
   if($resultado->count() == 1){
      $validado = $resultado[0]->producido->validado;
      $detalle = $resultado[0];
      $importado = 1;
   }else {
      $detalle = null;
      $validado = 0;
      $importado = 0;
   }
   return ['importado' => $importado , 'validado' => $validado, 'detalle' =>$detalle];
  }
}
