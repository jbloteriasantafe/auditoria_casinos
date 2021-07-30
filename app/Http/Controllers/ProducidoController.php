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
    // vvv Comentario viejo que sirve vvv
    //La denominacion carga es la que se utilizo para convertir a plata al momento de importar, la denominacion sale de la que tenia la maquina al importarte que no necesariamente es la misma al momento de validar producido
    //para santa fe y melincue que se importa en pesos, la denominacion de carga es 1, por lo que no afecta, pero en rosario, que se importa en creditos, si importa y tiene q ser distinto de 1
    //conclusion, si es de Santa Fe queda en plata, porque la denominacion es 1, si es de rosario se hace un cambio previo para cambiarlo a creditos
    
    //Lo paso a creditos, en Rosario se usa "denominacion_carga" porque se toma la que esta al momento al importar
    //En SantaFe/Mel uso la de la maquina (tiene denominacion_carga = 1 por defecto)
    //Como la division es de izquierda a derecha, termina pasando a creditos
    //x/denominacion_carga/denominacion
    //Si es de SantaFe Melinque => x/1/denominacion = x/denominacion
    //Si es de Rosario => x/denominacion_carga/1 = x/denominacion_carga

    //Se hizo de esta forma porque pensaba que era el cuello de botella (funcionaba MUUUY lento). Despues descubri que el cuello de botella
    //era el join complejo de contador_horario, le termine agregando un indice y lo hizo MUCHO mas rapido. Es totalmente razonable
    //(y mas mantenible) devolver los valores brutos para cada atributo y a estos calculos hacerlos en PHP, ahora creo que ya quedo asi y
    //no quiero tocarlo por ahora - Octavio 29 Junio 2021
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
    ->selectRaw('p.id_casino as casino, dp.valor as producido, dp.id_detalle_producido,
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
    $inicial = ContadorHorario::where([['fecha','=',$p->fecha ],['id_casino','=',$p->id_casino],['id_tipo_moneda','=',$p->id_tipo_moneda]])->get()->first();
    $fecha_fin = date("Y-m-d", strtotime($p->fecha." +1 days"));
    $final   = ContadorHorario::where([['fecha','=',$fecha_fin],['id_casino','=',$p->id_casino],['id_tipo_moneda','=',$p->id_tipo_moneda]])->get()->first();
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
        AjusteProducido::where('id_detalle_producido',$d->id_detalle_producido)->delete();
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

  public function ajustarProducido($id_producido){//valido en vista que se pueda cargar.
      //Son las maquinas que efectivamente dan diferencia junto con el valor que difiere
      $diferencias = $this->obtenerDiferencias($id_producido);

      // Los ajustes producidos guardan el "calculado" (diferencia contadores finales e iniciales) y el de sistema (importado)
      // y la diferencia entre estos dos.
      // Se les crean cuando se abre por primera vez el producido, uno para cada diferencia. Pese el nombre, estos no guardan
      // el ajuste si no la historia de que hubo una diferencia. El ajuste se guarda directamente sobre los detalle_contadores
      // y el detalle_producido (ver guardarAjuste).
      // A las diferencias se fija si las puede ajustar automaticamente. Si no puede, las devuelve como respuesta para ajustar manualmente.
      // @TODO: Habria que renombrar a ajuste_producido diferencia_detalle_producido o algo por el estilo... o directamente denormalizarlo
      // en el detalle_producido mismo, creo yo. - Octavio 29 Junio 2021
      $producido = Producido::find($id_producido);
      if($producido->ajustes_producido->count() == 0){
        $diferencias_filtradas = [];//Tal vez se lo pueda meter adentro, no estoy seguro como afecta el scoping la ultima linea
        DB::transaction(function() use (&$diferencias,&$diferencias_filtradas){
          foreach ($diferencias as $diff) {
            $diferencia_ajuste = new AjusteProducido;
            $diferencia_ajuste->producido_calculado  = $diff->delta;
            $diferencia_ajuste->producido_sistema    = $diff->producido;
            $diferencia_ajuste->diferencia           = $diff->diferencia;
            $diferencia_ajuste->id_detalle_producido = $diff->id_detalle_producido;
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
          if(!is_null($contador_final)){
            $contador_final->cerrado = 1;
            $contador_final->save();
          }
        });
      }

      return ['producidos_con_diferencia' => $diferencias,
              'tipos_ajuste' => TipoAjuste::all(),
              'validado' => ['estaValidado' => $producido->validado],
              'fecha_produccion' => $producido->fecha,
              'moneda' => $producido->tipo_moneda];
  }

  // genera los ajustes automaticos que pueden ser calculados numericamente de forma automatica
  public function probarAjusteAutomatico($diff){
    $dif = json_decode(json_encode($diff),true);//stdClass->array, asi lo espera recalcularDiferencia
    $contadores = ['coinin','coinout','jackpot','progresivo'];
    //si 1 algun contador final es menor que el inicial -> posible vuelta de contadores
    //si TODOS los contadores finales son menores a los iniciales (o cero) -> posible reset
    //  (esto es por ejemplo si tenemos iniciales 23 32 0 0 y finales 1 3 0 0, hay que agarra los 0)
    //@SPEED: Chequeable por query de BD
    $posible_reset_contadores = true;
    foreach($contadores as $idx => $c){
      $contador_final  = $c.'_final';
      $contador_inicio = $c.'_inicio';
      $final_menor_que_inicio = $dif[$contador_final] < $dif[$contador_inicio];
      $posible_reset_contadores = $posible_reset_contadores && 
        (//Si no chequeo que el final sea distinto de cero, agarra la falta de contadores como RESET (0, 0, 0, 0)
           ($final_menor_que_inicio  && $dif[$contador_final] != 0)
        || ($dif[$contador_inicio] == 0 && $dif[$contador_final] == 0)
      );
      if($final_menor_que_inicio && fmod($dif['diferencia'],1000000) == 0){
        //Le suma la vuelta de contadores, la diferencia esta en plata, lo paso a creditos
        $vuelta = abs($dif['diferencia']/$dif['denominacion']);
        $dif[$contador_final] += $vuelta;
        $diferencia = $this->recalcularDiferencia($dif);
        $dif[$contador_final] -= $vuelta;//Lo vuelvo al original
        if($diferencia == 0){
          $detalle_producido = DetalleProducido::find($dif['id_detalle_producido']);
          $detalle_producido->id_tipo_ajuste = 1;
          $detalle_producido->save();
          return true;
        }
      }
    }
    if($posible_reset_contadores){
      //Se le suma los iniciales, si esto explica la diferencia (da 0 recalculada), lo seteamos.
      //Sino, siguio contando despues del reset y lo tienen que ver los auditores manualmente
      foreach($contadores as $c) $dif[$c.'_final'] += $dif[$c.'_inicio'];
      $diferencia = $this->recalcularDiferencia($dif);
      //Lo devuelvo al valor original por si se agrega algun otro ajuste automatico... en principio superflua esta linea
      foreach($contadores as $c) $dif[$c.'_final'] -= $dif[$c.'_inicio'];
      if($diferencia == 0){//Reset de contadores _NO_ afecta nada en la BD (solo el tipo de ajuste). Ver tabla abajo.
        $detalle_producido = DetalleProducido::find($dif['id_detalle_producido']);
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
      'denominacion'       => 'required|numeric',
      'coinin_inicio'     => 'required|integer',//Los contadores vienen EN CREDITOS
      'coinin_final'       => 'required|integer',
      'coinout_inicio'    => 'required|integer',
      'coinout_final'      => 'required|integer',
      'jackpot_inicio'    => 'required|integer',
      'jackpot_final'      => 'required|integer',
      'progresivo_inicio' => 'required|integer',
      'progresivo_final'   => 'required|integer',
      'id_detalle_producido'        => 'required|exists:detalle_producido,id_detalle_producido',
      'id_detalle_contador_inicial' => 'nullable|exists:detalle_contador_horario,id_detalle_contador_horario',
      'id_detalle_contador_final'   => 'nullable|exists:detalle_contador_horario,id_detalle_contador_horario',
      'producido' => ['required','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],//producido es EN PLATA
      'id_tipo_ajuste' => 'required|exists:tipo_ajuste,id_tipo_ajuste',
      'observacion' => 'nullable'
    ], array(), self::$atributos)->after(function($validator){})->validate();

    $detalle_final     = DetalleContadorHorario::find($request['id_detalle_contador_final']);
    $detalle_producido = DetalleProducido::find($request['id_detalle_producido']);
    $contadores        = $this->contadoresDeProducido($detalle_producido->id_producido);
    if($detalle_final == null){
      $detalle_final = new DetalleContadorHorario;
      $detalle_final->id_contador_horario = $contadores['final']->id_contador_horario;
      $detalle_final->id_maquina = $detalle_producido->id_maquina;
    }
    $detalle_inicio    = DetalleContadorHorario::find($request['id_detalle_contador_inicial']);
    if($detalle_inicio == null){
      $detalle_inicio = new DetalleContadorHorario;
      $detalle_inicio->id_contador_horario = $contadores['inicial']->id_contador_horario;
      $detalle_inicio->id_maquina = $detalle_producido->id_maquina;
    }

    //Tabla de guia
    // Contadores iniciales, finales y el producido se usan para calcular la diferencia
    // Solamente si esta da 0 se guarda el ajuste
    // La ultima columna indica que se guarda en la base de datos
    // La observación y el tipo de ajuste se guarda en todas, independientemente del tipo

    // TIPO DE AJUSTE                     | INICIALES | FINALES   | PRODUCIDO | SOBREESCRIBE?
    //-------------------------------------------------------------------------------------------------------
    //(1) Vuelta de Contadores            | BD        | $request  | BD        | NADA
    //(2) Reset de Contadores             | BD        | $request  | BD        | NADA
    //(3) Cambio/Falta contadores finales | BD        | $request  | BD        | FINALES
    //(4) Error producido                 | BD        | BD        | $request  | PRODUCIDO
    //(5) Cambio de contadores iniciales  | $request  | BD        | BD        | INICIALES
    //(6) Multiples ajustes               | $request  | $request  | $request  | INICIALES, FINALES, PRODUCIDO

    // $detalle_producido->valor es el producido INFORMADO POR EL CASINO en el producido (no tiene errores de vuelta de contadores, reset, etc)
    //@BUG: Si se modifican los contadores iniciales, no puede ser que den diferencias para atras???????
    if(in_array($request['id_tipo_ajuste'],[1,2,3,4])){
      $request['coinin_inicio']     = $detalle_inicio->coinin / $request['denominacion'];//Estan en plata, los paso a creditos
      $request['coinout_inicio']    = $detalle_inicio->coinout / $request['denominacion'];
      $request['jackpot_inicio']    = $detalle_inicio->jackpot / $request['denominacion'];
      $request['progresivo_inicio'] = $detalle_inicio->progresivo / $request['denominacion'];
    }
    if(in_array($request['id_tipo_ajuste'],[4,5])){
      $request['coinin_final']     = $detalle_final->coinin / $request['denominacion'];
      $request['coinout_final']    = $detalle_final->coinout / $request['denominacion'];
      $request['jackpot_final']    = $detalle_final->jackpot / $request['denominacion'];
      $request['progresivo_final'] = $detalle_final->progresivo / $request['denominacion'];
    }
    if(in_array($request['id_tipo_ajuste'],[1,2,3,5])){//Queda mas claro en un IF aparte esto
      $request['producido'] = $detalle_producido->valor;
    }

    $diferencia = $this->recalcularDiferencia($request);
    if($diferencia != 0) return ['todas_ajustadas' => 0,'hay_diferencia' => 1];
      
    $producido = $detalle_producido->producido;
    DB::beginTransaction();
    try{
      if(in_array($request['id_tipo_ajuste'],[5,6])){//Le guardo el contador inicial
        //Aca antes redondeaba a 2 digitos coinin_in, etc antes de asignar, nose porque. Me parece superfluo. MySQL ya lo hace solo
        $detalle_inicio->coinin     = $request['coinin_inicio']     * $request['denominacion'];//Se guarda en plata
        $detalle_inicio->coinout    = $request['coinout_inicio']    * $request['denominacion'];
        $detalle_inicio->jackpot    = $request['jackpot_inicio']    * $request['denominacion'];
        $detalle_inicio->progresivo = $request['progresivo_inicio'] * $request['denominacion'];
        //Si el casino es Rosario, se tiene que cargar la denominacion de carga
        if($producido->id_casino == 3) $detalle_inicio->denominacion_carga = $request['denominacion'];
        $detalle_inicio->save();//Solo guardo si entro al IF, nose porque pero así lo hacia antes
      }
      if(in_array($request['id_tipo_ajuste'],[3,6])){//Le guardo el contador final
        $detalle_final->coinin     = $request['coinin_final']     * $request['denominacion'];
        $detalle_final->coinout    = $request['coinout_final']    * $request['denominacion'];
        $detalle_final->jackpot    = $request['jackpot_final']    * $request['denominacion'];
        $detalle_final->progresivo = $request['progresivo_final'] * $request['denominacion'];
        if($producido->id_casino == 3) $detalle_final->denominacion_carga = $request['denominacion'];
        $detalle_final->save();
      }
      if(in_array($request['id_tipo_ajuste'],[4,6])){//Le guardo el producido
        $producido->valor -= $detalle_producido->valor;//Le saco al total lo que tenia
        $detalle_producido->valor = $request['producido'];
        $producido->valor += $detalle_producido->valor;//Le agrego al total el nuevo valor
        $producido->save();
      }

      //Se agregan las observaciones, estas son independientes del tipo de ajuste
      $detalle_producido->observacion    = $request['observacion'];
      $detalle_producido->id_tipo_ajuste = $request['id_tipo_ajuste'];
      $detalle_producido->save();

      //Me fijo si faltan ajustes
      $faltan_ajustes = DB::table('producido as p')
      ->join('detalle_producido as dp','dp.id_producido','=','p.id_producido')
      //Con este JOIN me quedo solo con los detalles que tenian diferencias al momento de ajustar
      ->join('ajuste_producido as ap','ap.id_detalle_producido','=','dp.id_detalle_producido')
      //Si les falta el tipo de ajuste, faltan ajustar
      ->whereNull('dp.id_tipo_ajuste')->where('p.id_producido','=',$producido->id_producido)->get()->count() > 0;

      if(!$faltan_ajustes){ //Si no faltan, valido el producido y cierro el contador final
        $producido->validado = 1 ;
        $producido->save();
        $contador_horario = $detalle_final->contador_horario;
        $contador_horario->cerrado = 1; // si valido el producido el contador tambien se cierra
        $contador_horario->save();
      }
      DB::commit();
    }
    catch(Exception $e){
      DB::rollBack();
      throw $e;
    }

    return ['todas_ajustadas' => $faltan_ajustes? 0 : 1,'hay_diferencia' => 0];
  }
  // Crea la planilla del producido total del dia, con todas las maquinas que dieron diferencia
  // junto a los ajustes , ya sean automaticos o manual
  public function generarPlanillaDiferencias($id_producido){
    $resultados = DB::table('detalle_producido')
    ->join('ajuste_producido','detalle_producido.id_detalle_producido','=','ajuste_producido.id_detalle_producido')
    ->leftJoin('tipo_ajuste','detalle_producido.id_tipo_ajuste','=','tipo_ajuste.id_tipo_ajuste')
    ->join('maquina', 'maquina.id_maquina','=','detalle_producido.id_maquina')
    ->where('detalle_producido.id_producido',$id_producido)
    ->select('maquina.nro_admin as nro_maquina','ajuste_producido.producido_calculado as prod_calc','ajuste_producido.producido_sistema as prod_sist',
             'ajuste_producido.diferencia as diferencia','tipo_ajuste.descripcion as d','detalle_producido.valor as prod_calc_operado',
             'detalle_producido.observacion as obs')
    ->orderBy('nro_maquina','asc')
    ->get();

    $producido = Producido::find($id_producido);
    $pro = new \stdClass();
    $pro->casinoNom   = $producido->casino->nombre;
    $pro->tipo_moneda = $producido->tipo_moneda->descripcion;
    if($pro->tipo_moneda == 'ARS')      $pro->tipo_moneda = 'Pesos';
    else if($pro->tipo_moneda == 'USB') $pro->tipo_moneda = 'Dólares';
    $pro->fecha_prod = implode('-',array_reverse(explode('-',$producido->fecha)));

    $ajustes = array();
    $MTMobservaciones= array();
    foreach($resultados as $resultado){
      $res = new \stdClass();
      $res->maquina           = $resultado->nro_maquina;
      $res->descripcion       = $resultado->d;
      $res->calculado         = number_format($resultado->prod_calc, 2, ",", ".");
      $res->sistema           = number_format($resultado->prod_sist, 2, ",", ".");
      $res->dif               = number_format($resultado->diferencia, 2, ",", ".");
      $res->calculado_operado = number_format($resultado->prod_calc_operado, 2, ",", ".");
      $ajustes[] = $res;
      // agrego a una lista todas aquellas mtm con observaciones para ser motrada en otra tabla
      if ($resultado->obs!=""){
        $resObs = new \stdClass();
        $resObs->maquina = $resultado->nro_maquina;
        $resObs->observacion = $resultado->obs;
        $MTMobservaciones[] = $resObs;
      }
    };

    $view = View::make('planillaProducidosDiferencias',compact('ajustes','pro','MTMobservaciones'));

    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();

    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }

  //Devuelvo TODOS los producidos (distintos de 0)
  public function generarPlanillaProducido($id_producido){
    $resultados = DB::table('detalle_producido')
    ->leftJoin('tipo_ajuste','detalle_producido.id_tipo_ajuste','=','tipo_ajuste.id_tipo_ajuste')
    ->join('maquina', 'maquina.id_maquina','=','detalle_producido.id_maquina')
    ->where('detalle_producido.id_producido',$id_producido)
    ->select('maquina.nro_admin as nro_maquina','detalle_producido.valor')
    ->orderBy('nro_maquina','asc')
    ->where('detalle_producido.valor','<>',0)
    ->get();

    $producido = Producido::find($id_producido);
    $pro = new \stdClass();
    $pro->casinoNom   = $producido->casino->nombre;
    $pro->tipo_moneda = $producido->tipo_moneda->descripcion;
    if($pro->tipo_moneda == 'ARS')      $pro->tipo_moneda = 'Pesos';
    else if($pro->tipo_moneda == 'USB') $pro->tipo_moneda = 'Dólares';
    $pro->fecha_prod = implode('-',array_reverse(explode('-',$producido->fecha)));
    $pro->valor      = number_format($producido->valor,2,',','.');
    $detalles = [];

    foreach($resultados as $resultado){
      $res = new \stdClass();
      $res->maquina = $resultado->nro_maquina;
      $res->valor   = number_format($resultado->valor, 2, ",", ".");
      $detalles[] = $res;
    };

    $view = View::make('planillaProducidos',compact('detalles','pro'));

    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();

    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
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

  //Contadores en en creditos, producido en plata, se usa en probarAjusteAutomatico y guardarAjuste
  private function recalcularDiferencia($arr){
    $valor_inicio = $arr['coinin_inicio'] - $arr['coinout_inicio'] - $arr['jackpot_inicio'] - $arr['progresivo_inicio'];//credito
    $valor_final  = $arr['coinin_final']  - $arr['coinout_final']  - $arr['jackpot_final']  - $arr['progresivo_final'];//credito
    $delta        = $valor_final - $valor_inicio;//credito - credito
    $diferencia   = round($delta*$arr['denominacion'], 2) - $arr['producido']; //plata - plata
    return round($diferencia,2);
  }
}
