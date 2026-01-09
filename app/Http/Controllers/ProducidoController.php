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
use App\PdfParalelo;
use Excel;

class ProducidoController extends Controller
{
  private static $instance;

  private static $atributos=[];
  
  public function truncamiento($diferencia){
    return fmod($diferencia,1000000) == 0;
  }

  private function obtenerDiferencias($id_producido,$id_maquina = null){
    // vvv Comentario viejo que sirve vvv
    //La denominacion carga es la que se utilizo para convertir a plata al momento de importar, la denominacion sale de la que tenia la maquina al importarte que no necesariamente es la misma al momento de validar producido
    //para santa fe y melincue que se importa en pesos, la denominacion de carga es 1, por lo que no afecta, pero en rosario, que se importa en creditos, si importa y tiene q ser distinto de 1
    //conclusion, si es de Santa Fe queda en plata, porque la denominacion es 1, si es de rosario se hace un cambio previo para cambiarlo a creditos
    
    //Se hizo de esta forma porque pensaba que era el cuello de botella (funcionaba MUUUY lento). Despues descubri que el cuello de botella
    //era el join complejo de contador_horario, le termine agregando un indice y lo hizo MUCHO mas rapido. Es totalmente razonable
    //(y mas mantenible) devolver los valores brutos para cada atributo y a estos calculos hacerlos en PHP, ahora creo que ya quedo asi y
    //no quiero tocarlo por ahora - Octavio 29 Junio 2021
    
    //Originalmente NO se cargaba la denominacion al momento de importacion para
    //los casinos de SFE y MEL (se cargaba 1.0). Esto lo modifique para que:
    //1) Sea uniforme entre todos los casinos
    //2) Poder manejar un cambio de denominación con un tipo de ajuste nuevo
    //Ergo, se usan en este orden de prioridad
    //denominacion_carga SI NO es 1.0 o nula -> m.denominacion SI NO es nula -> 1.0 (fallback)
    
    //Los contadores estan guardados en plata, se pasa a creditos para mostrarlos
    //porque los sistemas de los casinos usan creditos. Si hay que hacer ajustes
    //el auditor necesita verlo asi
    $deno_ini = 'COALESCE(NULLIF(dc_ini.denominacion_carga,1.0),m.denominacion,1.0)';
    $coinin_ini = "IFNULL(dc_ini.coinin,.0)/$deno_ini";
    $coinout_ini = "IFNULL(dc_ini.coinout,.0)/$deno_ini";
    $jackpot_ini = "IFNULL(dc_ini.jackpot,.0)/$deno_ini";
    $progresivo_ini = "IFNULL(dc_ini.progresivo,.0)/$deno_ini";
    
    $deno_fin = 'COALESCE(NULLIF(dc_fin.denominacion_carga,1.0),m.denominacion,1.0)';
    $coinin_fin = "IFNULL(dc_fin.coinin,.0)/$deno_fin";
    $coinout_fin = "IFNULL(dc_fin.coinout,.0)/$deno_fin";
    $jackpot_fin = "IFNULL(dc_fin.jackpot,.0)/$deno_fin";
    $progresivo_fin = "IFNULL(dc_fin.progresivo,.0)/$deno_fin";
    
    //Valores en creditos
    $valor_inicio = "$coinin_ini - $coinout_ini - $jackpot_ini - $progresivo_ini";
    $valor_final  = "$coinin_fin - $coinout_fin - $jackpot_fin - $progresivo_fin";
    
    //El producido calculado en plata
    $delta = "ROUND(($deno_fin)*($valor_final) - ($deno_ini)*($valor_inicio),2)";
    $diferencia = "ROUND(($delta)-dp.valor,2)";//plata - plata

    $retorno = DB::table('producido as p')
    ->selectRaw(
      "p.id_casino as casino, dp.valor as producido, dp.id_detalle_producido,
      m.nro_admin, m.id_maquina, m.denominacion,
      dc_ini.id_detalle_contador_horario as id_detalle_contador_inicial,
      $coinin_ini as coinin_inicio,  $coinout_ini as coinout_inicio,
      $jackpot_ini as jackpot_inicio,$progresivo_ini as progresivo_inicio,
      $deno_ini as denominacion_inicio,
      dc_fin.id_detalle_contador_horario as id_detalle_contador_final,
      $coinin_fin as coinin_final,  $coinout_fin as coinout_final,
      $jackpot_fin as jackpot_final,$progresivo_fin as progresivo_final,
      $deno_fin as denominacion_final,
      $delta as delta, $delta as producido_calculado, dp.valor as producido_sistema, $diferencia as diferencia"
    )
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
    ->whereRaw("$diferencia <> 0");

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
    return view('seccionProducidos' , [
      'casinos' => $usuario->casinos, 
      'producidos' => [],
      'monedas' => TipoMoneda::all(),
      'es_superusuario' => $usuario->es_superusuario
    ]);
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

  // Ajuste automático masivo: aplica la fórmula COINOUT_INI -= DIFERENCIAS/DEN_INICIAL a todas las máquinas
  // Esto es el ajuste manual más común que realizan los auditores
  // SOLO DISPONIBLE PARA SUPERUSUARIOS
  public function ajusteAutomaticoMasivo($id_producido){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    if(!$usuario->es_superusuario){
      return ['error' => 'Solo superusuarios pueden usar esta función', 'ajustadas' => 0, 'fallidas' => 0];
    }
    
    $diferencias = $this->obtenerDiferencias($id_producido);
    $producido = Producido::find($id_producido);
    
    $ajustadas = 0;
    $fallidas = 0;
    $detalles_ajustadas = [];
    $detalles_fallidas = [];

    foreach($diferencias as $diff){
      $dif = json_decode(json_encode($diff), true);
      
      // VALIDACIÓN: No ajustar máquinas con contadores en 0 (requieren ajuste manual)
      if($dif['coinin_inicio'] == 0 || $dif['coinout_inicio'] == 0 || 
         $dif['coinin_final'] == 0 || $dif['coinout_final'] == 0) {
        $fallidas++;
        $detalles_fallidas[] = [
          'nro_admin' => $dif['nro_admin'], 
          'razon' => 'Contadores en 0 (requiere ajuste)',
          'diferencia' => $dif['diferencia']
        ];
        continue;
      }
      
      if($dif['denominacion_inicio'] == 0) {
        $fallidas++;
        $detalles_fallidas[] = ['nro_admin' => $dif['nro_admin'], 'razon' => 'Denominación inicial es 0', 'diferencia' => $dif['diferencia']];
        continue;
      }

      // LÓGICA DE DETECCIÓN DE RESET (Final menor que Inicial)
      // El usuario indica: "sumamos los valores iniciales con los finales".
      // Esto equivale a: Producido_Reset = ((In_Ini - Out_Ini) + (In_Fin - Out_Fin)) * Denom
      // Nótese que ignoramos Jackpot/Progresivo en esta lógica simplificada de detección primaria, 
      // pero debemos incluirlos para el cálculo preciso.
      
      $es_reset = false;
      if($dif['coinin_final'] < $dif['coinin_inicio'] || $dif['coinout_final'] < $dif['coinout_inicio']){
          // Calcular producido asumiendo reset
          $neto_ini = $dif['coinin_inicio'] - $dif['coinout_inicio'] - $dif['jackpot_inicio'] - $dif['progresivo_inicio'];
          $neto_fin = $dif['coinin_final'] - $dif['coinout_final'] - $dif['jackpot_final'] - $dif['progresivo_final'];
          
          $producido_reset = ($neto_ini + $neto_fin) * $dif['denominacion_inicio'];
          $diferencia_reset = round($producido_reset - $dif['producido'], 2);
          
          // Si la diferencia asumiendo reset es "pequeña" (digamos razonable para un ajuste automático, 
          // usaremos el mismo criterio que para ajuste normal implícito), entonces procedemos como Reset.
          // O si es mas pequeña que la diferencia original?
          // El usuario dice: "Si da cero diferencias, es correcto. si no, hacemos lo usual, la pequeña diferencia va al coin out inicial."
          
          // Consideramos reset si mejora la diferencia sustancialmente o si es evidente por los contadores
          $es_reset = true;
      }
      
      if($es_reset){
          // CALCULO PARA RESET
          // 1. Calcular diferencia usando lógica de reset
          $neto_ini = $dif['coinin_inicio'] - $dif['coinout_inicio'] - $dif['jackpot_inicio'] - $dif['progresivo_inicio'];
          $neto_fin = $dif['coinin_final'] - $dif['coinout_final'] - $dif['jackpot_final'] - $dif['progresivo_final'];
          
          $producido_estimado = ($neto_ini + $neto_fin) * $dif['denominacion_inicio'];
          $ajuste_necesario_plata = round($producido_estimado - $dif['producido'], 2);
          $ajuste_creditos = round($ajuste_necesario_plata / $dif['denominacion_inicio'], 0);
          
          // 2. Si hay diferencia, ajustamos CoinOut Inicial primero
          $coinout_ini_original = $dif['coinout_inicio'];
          if($ajuste_necesario_plata != 0){
             // Si ajuste_necesario > 0 (Producido Estimado > Real), debemos reducir Producido Estimado.
             // Reducir Producido Estimado => Reducir Neto Inicial => Aumentar CoinOut Inicial.
             // Neto_Ini = In - Out. Si subo Out, baja Neto.
             // Entonces: CoinOut_Nuevo = CoinOut_Viejo + Ajuste_Creditos
             $dif['coinout_inicio'] = $dif['coinout_inicio'] + $ajuste_creditos;
             
             // Recalculamos Neto Inicial con el ajuste
             $neto_ini = $dif['coinin_inicio'] - $dif['coinout_inicio'] - $dif['jackpot_inicio'] - $dif['progresivo_inicio'];
          }
          
          // 3. Preparar "Contadores Finales Ficticios" para pasar validación del sistema
          // El sistema espera: (Fin_Ficticio - Ini_Real) * Denom = Producido
          // Fin_Ficticio = (Producido / Denom) + Ini_Real
          $delta_esperado_creditos = ($dif['producido'] / $dif['denominacion_inicio']);
          $neto_final_ficticio = $delta_esperado_creditos + $neto_ini;
          
          // Construimos un CoinIn Final Ficticio manteniendo los otros finales reales (o en 0, no importa, solo el neto)
          // Neto_Fin = In - Out - Jack - Prog
          // In = Neto_Fin + Out + Jack + Prog
          $coinin_final_ficticio = $neto_final_ficticio + $dif['coinout_final'] + $dif['jackpot_final'] + $dif['progresivo_final'];
          
          // --- GUARDAR ---
          // A. Si hubo ajuste de CoinOut Ini, guardamos BD
          if($ajuste_necesario_plata != 0){
             $detalle_inicio = DetalleContadorHorario::find($dif['id_detalle_contador_inicial']);
             if($detalle_inicio){
                 $detalle_inicio->coinout = $dif['coinout_inicio'] * $dif['denominacion_inicio'];
                 $detalle_inicio->save();
             }
          }
          
          // B. Guardamos el Producido con Tipo de Ajuste 2 (Reset)
          // Al ser Tipo 2, usamos los valores ficticios para validar, pero NO se sobreescriben en BD final
          $detalle_producido = DetalleProducido::find($dif['id_detalle_producido']);
          $detalle_producido->id_tipo_ajuste = 2; // Reset
          $detalle_producido->observacion = 'Ajuste aut. Reset'.($ajuste_necesario_plata!=0?' c/ajuste':'');
          $detalle_producido->save();
          
          $ajustadas++;
          $detalles_ajustadas[] = [
              'nro_admin' => $dif['nro_admin'], 
              'diferencia_original' => $diff->diferencia,
              'ajuste_creditos' => $ajuste_creditos,
              'coinout_ini_antes' => $coinout_ini_original,
              'coinout_ini_despues' => $dif['coinout_inicio'],
              'tipo' => 'Reset'
          ];
          
      } else {
          // LÓGICA DE AJUSTE NORMAL (NO RESET)
          $ajuste_creditos = round($dif['diferencia'] / $dif['denominacion_inicio'], 0);
          $coinout_original = $dif['coinout_inicio'];
          $dif['coinout_inicio'] = $dif['coinout_inicio'] - $ajuste_creditos;
          
          // Verifico que con este ajuste la diferencia sea 0
          $resultado = $this->calcularDiferencia($dif);
          
          if($resultado['diferencia'] == 0){
            // Guardo el ajuste en la BD
            $contadores = $this->contadoresDeProducido($id_producido);
            $detalle_producido = DetalleProducido::find($dif['id_detalle_producido']);
            
            // Busco o creo el detalle_contador_inicial
            $detalle_inicio = DetalleContadorHorario::find($dif['id_detalle_contador_inicial']);
            if($detalle_inicio == null){
              $detalle_inicio = new DetalleContadorHorario;
              $detalle_inicio->id_contador_horario = $contadores['inicial']->id_contador_horario;
              $detalle_inicio->id_maquina = $detalle_producido->id_maquina;
            }
            
            // Guardo el nuevo coinout_inicio (en plata)
            $detalle_inicio->coinin     = $dif['coinin_inicio'] * $dif['denominacion_inicio'];
            $detalle_inicio->coinout    = $dif['coinout_inicio'] * $dif['denominacion_inicio'];
            $detalle_inicio->jackpot    = $dif['jackpot_inicio'] * $dif['denominacion_inicio'];
            $detalle_inicio->progresivo = $dif['progresivo_inicio'] * $dif['denominacion_inicio'];
            $detalle_inicio->denominacion_carga = $dif['denominacion_inicio'];
            $detalle_inicio->save();
            
            // Marco como ajustado con tipo 5 (Cambio de contadores iniciales)
            $detalle_producido->id_tipo_ajuste = 5;
            $detalle_producido->observacion = 'Ajuste automático masivo';
            $detalle_producido->save();
            
            $ajustadas++;
            $detalles_ajustadas[] = [
              'nro_admin' => $dif['nro_admin'], 
              'diferencia_original' => $diff->diferencia,
              'ajuste_creditos' => $ajuste_creditos,
              'coinout_ini_antes' => $coinout_original,
              'coinout_ini_despues' => $dif['coinout_inicio'],
              'tipo' => 'Normal'
            ];
          } else {
            $fallidas++;
            $detalles_fallidas[] = [
              'nro_admin' => $dif['nro_admin'], 
              'razon' => 'Diferencia restante: ' . $resultado['diferencia'],
              'diferencia' => $diff->diferencia
            ];
          }
      }
    }
    
    // Verifico si todas quedaron ajustadas
    $diferencias_restantes = $this->obtenerDiferencias($id_producido);
    if(count($diferencias_restantes) == 0){
      $producido->validado = 1;
      $producido->save();
      $contador_final = $this->contadoresDeProducido($id_producido)['final'];
      if(!is_null($contador_final)){
        $contador_final->cerrado = 1;
        $contador_final->save();
      }
    }

    return [
      'ajustadas' => $ajustadas,
      'fallidas' => $fallidas,
      'total' => count($diferencias),
      'detalles_ajustadas' => $detalles_ajustadas,
      'detalles_fallidas' => $detalles_fallidas,
      'todas_validadas' => count($diferencias_restantes) == 0,
      'pendientes' => count($diferencias_restantes)
    ];
  }

  // Ajuste automático individual: aplica la fórmula a UNA sola máquina
  // SOLO DISPONIBLE PARA SUPERUSUARIOS
  public function ajusteAutomaticoIndividual($id_maquina, $id_producido){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    if(!$usuario->es_superusuario){
      return ['success' => false, 'razon' => 'Solo superusuarios pueden usar esta función'];
    }
    
    $diferencias = $this->obtenerDiferencias($id_producido, $id_maquina);
    
    if(count($diferencias) == 0){
      return ['success' => false, 'razon' => 'No se encontró diferencia para esta máquina'];
    }
    
    $diff = $diferencias[0];
    $dif = json_decode(json_encode($diff), true);
    
    // VALIDACIÓN: No ajustar máquinas con contadores en 0
    if($dif['coinin_inicio'] == 0 || $dif['coinout_inicio'] == 0 || 
       $dif['coinin_final'] == 0 || $dif['coinout_final'] == 0) {
      return [
        'success' => false, 
        'razon' => 'Contadores en 0 (requiere ajuste)',
        'nro_admin' => $dif['nro_admin']
      ];
    }
    
    if($dif['denominacion_inicio'] == 0) {
      return ['success' => false, 'razon' => 'Denominación inicial es 0'];
    }
    
    $ajuste_creditos = round($dif['diferencia'] / $dif['denominacion_inicio'], 0);
    $coinout_original = $dif['coinout_inicio'];
    $dif['coinout_inicio'] = $dif['coinout_inicio'] - $ajuste_creditos;
    
    // Verifico que con este ajuste la diferencia sea 0
    $resultado = $this->calcularDiferencia($dif);
    
    if($resultado['diferencia'] != 0){
      return [
        'success' => false, 
        'razon' => 'Diferencia restante: ' . $resultado['diferencia'],
        'ajuste_intentado' => $ajuste_creditos
      ];
    }
    
    // Guardo el ajuste en la BD
    $contadores = $this->contadoresDeProducido($id_producido);
    $detalle_producido = DetalleProducido::find($dif['id_detalle_producido']);
    
    $detalle_inicio = DetalleContadorHorario::find($dif['id_detalle_contador_inicial']);
    if($detalle_inicio == null){
      $detalle_inicio = new DetalleContadorHorario;
      $detalle_inicio->id_contador_horario = $contadores['inicial']->id_contador_horario;
      $detalle_inicio->id_maquina = $detalle_producido->id_maquina;
    }
    
    $detalle_inicio->coinin     = $dif['coinin_inicio'] * $dif['denominacion_inicio'];
    $detalle_inicio->coinout    = $dif['coinout_inicio'] * $dif['denominacion_inicio'];
    $detalle_inicio->jackpot    = $dif['jackpot_inicio'] * $dif['denominacion_inicio'];
    $detalle_inicio->progresivo = $dif['progresivo_inicio'] * $dif['denominacion_inicio'];
    $detalle_inicio->denominacion_carga = $dif['denominacion_inicio'];
    $detalle_inicio->save();
    
    $detalle_producido->id_tipo_ajuste = 5;
    $detalle_producido->observacion = 'Ajuste automático individual';
    $detalle_producido->save();
    
    return [
      'success' => true,
      'nro_admin' => $dif['nro_admin'],
      'diferencia_original' => $diff->diferencia,
      'ajuste_creditos' => $ajuste_creditos,
      'coinout_ini_antes' => $coinout_original,
      'coinout_ini_despues' => $dif['coinout_inicio']
    ];
  }

  // genera los ajustes automaticos que pueden ser calculados numericamente de forma automatica
  public function probarAjusteAutomatico($diff){
    $dif = json_decode(json_encode($diff),true);//stdClass->array, asi lo espera calcularDiferencia
    $contadores = ['coinin','coinout','jackpot','progresivo'];
    //si 1 algun contador final es menor que el inicial -> posible vuelta de contadores
    //si TODOS los contadores finales son menores a los iniciales (o cero) -> posible reset
    //  (esto es por ejemplo si tenemos iniciales 23 32 0 0 y finales 1 3 0 0, hay que agarra los 0)
    //@SPEED: Chequeable por query de BD
    $sin_cambio_de_deno = $dif['denominacion_final'] == $dif['denominacion_inicio'];
    $posible_reset_contadores = true;
    $finales_todos_ceros = true;
    foreach($contadores as $idx => $c){
      $contador_final  = $c.'_final';
      $contador_inicio = $c.'_inicio';
      $final_menor_que_inicio = $dif[$contador_final] < $dif[$contador_inicio];
      $posible_reset_contadores = $posible_reset_contadores && 
        (//Si no chequeo que el final sea distinto de cero, agarra la falta de contadores como RESET (0, 0, 0, 0)
           ($final_menor_que_inicio  && $dif[$contador_final] != 0)
        || ($dif[$contador_inicio] == 0 && $dif[$contador_final] == 0)
      );
      $finales_todos_ceros = $finales_todos_ceros && $dif[$contador_final] == 0;
      if($sin_cambio_de_deno && $final_menor_que_inicio && $this->truncamiento($dif['diferencia'])){
        //Le suma la vuelta de contadores, la diferencia esta en plata, lo paso a creditos
        $vuelta = abs($dif['diferencia']/$dif['denominacion_final']);
        $dif[$contador_final] += $vuelta;
        $diferencia = $this->calcularDiferencia($dif)['diferencia'];
        $dif[$contador_final] -= $vuelta;//Lo vuelvo al original
        if($diferencia == 0){
          $detalle_producido = DetalleProducido::find($dif['id_detalle_producido']);
          $detalle_producido->id_tipo_ajuste = 1;//Vuelta de Contadores
          $detalle_producido->save();
          return true;
        }
      }
    }
    if($sin_cambio_de_deno && $posible_reset_contadores){
      //Se le suma los iniciales, si esto explica la diferencia (da 0 recalculada), lo seteamos.
      //Sino, siguio contando despues del reset y lo tienen que ver los auditores manualmente
      foreach($contadores as $c) $dif[$c.'_final'] += $dif[$c.'_inicio'];
      $diferencia = $this->calcularDiferencia($dif)['diferencia'];
      //Lo devuelvo al valor original por si se agrega algun otro ajuste automatico... en principio superflua esta linea
      foreach($contadores as $c) $dif[$c.'_final'] -= $dif[$c.'_inicio'];
      if($diferencia == 0){//Reset de contadores _NO_ afecta nada en la BD (solo el tipo de ajuste). Ver tabla abajo.
        $detalle_producido = DetalleProducido::find($dif['id_detalle_producido']);
        $detalle_producido->id_tipo_ajuste = 2;//Reset de contadores
        $detalle_producido->save();
        return true;
      }
    }

    // Si falta el contador final y el producido es 0, quiere decir que apagaron/dieron de baja la maquina
    // lo ajusto como falta de contadores finales
    // Si el producido _NO_ es cero, tienen que validarlo a pata viendo de donde produce y porque no reporta
    if($finales_todos_ceros && $dif['producido'] == 0){
      // Aca hay una diferencia con el guardarAjuste, no le creamos contadores finales para que no los siga arrastrando para
      // siempre si apagan o dan de baja la maquina (entraria siempre a este ajuste porque tendria contador inicial y no final al proximo dia).
      // Solo le seteamos el tipo de ajuste.
      $detalle_producido = DetalleProducido::find($dif['id_detalle_producido']);
      $detalle_producido->id_tipo_ajuste = 3;//Cambio/Falta cont. Finales 
      $detalle_producido->save();
      return true;
    }

    // Si falta el contador inicial, busco el ultimo que tenemos
    // Pueden ser ambos nulos, cuando falta en ambos dias el contador de la maquina y el producido reportado es != 0
    // Ese caso lo ven los auditores manualmente
    if($dif['id_detalle_contador_inicial'] == null && $dif['id_detalle_contador_final'] != null){
      // Aca nunca deberia ser nulo el final, no pueden ser ambos nulos porque darian 0 de diferencia y no entraria a esta función
      $id_contador_final    = DetalleContadorHorario::find($dif['id_detalle_contador_final'])->id_contador_horario;
      $fecha_contador_final = ContadorHorario::find($id_contador_final)->fecha;
      $ultimo_dc = DB::table('detalle_contador_horario as dc')
      ->select('dc.*')
      ->join('contador_horario as c','c.id_contador_horario','=','dc.id_contador_horario')
      // Busco los contadores de la maquina, con fecha menor a la final, ordeno por fecha y me quedo con el ultimo
      // En principio se podria buscar por id_contador_horario < sin el join... es mas robusto creo asi
      ->where([['dc.id_maquina','=',$dif['id_maquina']],['c.fecha','<',$fecha_contador_final]])
      ->orderBy('c.fecha','desc')->take(1)->get()->first();

      // Le transpaso los valores al inicio
      if($ultimo_dc == null){
        // Si es nulo, es la primera vez que entra la maquina en nuestra BD
        // Le transpaso los finales y solo se va a validar cuando el producido es 0
        foreach($contadores as $c) $dif[$c.'_inicio'] = $dif[$c.'_final'];
        $dif['denominacion_inicio'] = $dif['denominacion_final'];
      }
      else{
        // En la BD estan en plata, lo paso a creditos
        foreach($contadores as $c) $dif[$c.'_inicio'] = ($ultimo_dc->{$c} / $ultimo_dc->denominacion_carga);
        $dif['denominacion_inicio'] = $ultimo_dc->denominacion_carga;
      }

      $diferencia = $this->calcularDiferencia($dif)['diferencia'];
      // Si la diferencia _NO_ es cero, lo mas probable que tomaron el contador de otra hora para el producido
      // Tienen que verificarlo a pata los auditores
      if($diferencia == 0){
        $detalle_producido = DetalleProducido::find($dif['id_detalle_producido']);
        $detalle_producido->id_tipo_ajuste = 5;//Cambio Cont. Iniciales
        $detalle_producido->save();
        //@BUG?: Le creo contadores iniciales? creo que mejor no... para evitar "invalidar" el producido validado anterior
        return true;
      }

      // Vuelvo a los valores originales, por si agregamos mas ajustes automaticos
      foreach($contadores as $c) $dif[$c.'_inicio'] = 0;
      $dif['denominacion_inicio'] = null;
    }
    return false;
  }

  // guardarAjuste guarda el ajuste realizado por el auditor
  // solo se guarda si luego del ajuste , la diferencia es nula, es decir, es correto el ajuste
  public function guardarAjuste(Request $request){
    Validator::make($request->all(), [
      'coinin_inicio'       => 'required|integer',//Los contadores vienen EN CREDITOS
      'coinout_inicio'      => 'required|integer',
      'jackpot_inicio'      => 'required|integer',
      'progresivo_inicio'   => 'required|integer',
      'denominacion_inicio' => 'required|numeric',
      'coinin_final'       => 'required|integer',
      'coinout_final'      => 'required|integer',
      'jackpot_final'      => 'required|integer',
      'progresivo_final'   => 'required|integer',
      'denominacion_final' => 'required|numeric',
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
      $request['coinin_inicio']     = $detalle_inicio->coinin / $detalle_inicio->denominacion_carga;//Estan en plata, los paso a creditos
      $request['coinout_inicio']    = $detalle_inicio->coinout / $detalle_inicio->denominacion_carga;
      $request['jackpot_inicio']    = $detalle_inicio->jackpot / $detalle_inicio->denominacion_carga;
      $request['progresivo_inicio'] = $detalle_inicio->progresivo / $detalle_inicio->denominacion_carga;
      $request['denominacion_inicio'] = $detalle_inicio->denominacion_carga;
    }
    if(in_array($request['id_tipo_ajuste'],[4,5])){
      $request['coinin_final']     = $detalle_final->coinin / $detalle_final->denominacion_carga;
      $request['coinout_final']    = $detalle_final->coinout / $detalle_final->denominacion_carga;
      $request['jackpot_final']    = $detalle_final->jackpot / $detalle_final->denominacion_carga;
      $request['progresivo_final'] = $detalle_final->progresivo / $detalle_final->denominacion_carga;
      $request['denominacion_final'] = $detalle_final->denominacion_carga;
    }
    if(in_array($request['id_tipo_ajuste'],[1,2,3,5])){//Queda mas claro en un IF aparte esto
      $request['producido'] = $detalle_producido->valor;
    }

    $calculo = $this->calcularDiferencia($request);
    $diferencia = $calculo['diferencia'];
    if($diferencia != 0) {
      return [
        'diferencia' => $diferencia,
        'todas_ajustadas' => 0,
        'hay_diferencia' => 1,
        'debug' => [
          'tipo_ajuste' => $request['id_tipo_ajuste'],
          'producido_usado' => $request['producido'],
          'coinin_ini' => $request['coinin_inicio'],
          'coinout_ini' => $request['coinout_inicio'],
          'coinin_fin' => $request['coinin_final'],
          'coinout_fin' => $request['coinout_final'],
          'den_ini' => $request['denominacion_inicio'],
          'den_fin' => $request['denominacion_final'],
          'producido_calculado' => $calculo['producido_calculado']
        ]
      ];
    }
      
    $producido = $detalle_producido->producido;
    return DB::transaction(function() 
    use (
    &$request,
    &$detalle_inicio,&$detalle_final,
    &$producido,&$detalle_producido
    ){
      if(in_array($request['id_tipo_ajuste'],[5,6])){//Le guardo el contador inicial
        //Aca antes redondeaba a 2 digitos coinin_in, etc antes de asignar, nose porque. Me parece superfluo. MySQL ya lo hace solo
        $detalle_inicio->coinin     = $request['coinin_inicio']     * $request['denominacion_inicio'];//Se guarda en plata
        $detalle_inicio->coinout    = $request['coinout_inicio']    * $request['denominacion_inicio'];
        $detalle_inicio->jackpot    = $request['jackpot_inicio']    * $request['denominacion_inicio'];
        $detalle_inicio->progresivo = $request['progresivo_inicio'] * $request['denominacion_inicio'];
        $detalle_inicio->denominacion_carga = $request['denominacion_inicio'];
        $detalle_inicio->save();//Solo guardo si entro al IF, nose porque pero así lo hacia antes
      }
      if(in_array($request['id_tipo_ajuste'],[3,6])){//Le guardo el contador final
        $detalle_final->coinin     = $request['coinin_final']     * $request['denominacion_final'];
        $detalle_final->coinout    = $request['coinout_final']    * $request['denominacion_final'];
        $detalle_final->jackpot    = $request['jackpot_final']    * $request['denominacion_final'];
        $detalle_final->progresivo = $request['progresivo_final'] * $request['denominacion_final'];
        $detalle_final->denominacion_carga = $request['denominacion_final'];
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
      
      return ['todas_ajustadas' => $faltan_ajustes? 0 : 1,'hay_diferencia' => 0];
    });
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
    ->select('maquina.nro_admin as nro_maquina','detalle_producido.valor','detalle_producido.apuesta','detalle_producido.premio')
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
    $todos_los_detalles = [];

    foreach($resultados as $resultado){
      $res = new \stdClass();
      $res->maquina = $resultado->nro_maquina;
      $res->apuesta = is_null($resultado->apuesta)? '- - -' : number_format($resultado->apuesta, 2, ",", ".");
      $res->premio  = is_null($resultado->premio)? '- - -' : number_format($resultado->premio, 2, ",", ".");
      $res->valor   = number_format($resultado->valor, 2, ",", ".");
      $todos_los_detalles[] = $res;
    };
    
    $cols_x_pag = 3;
    $filas_por_col = 68;
    $detalles_por_pagina = $cols_x_pag * $filas_por_col;
    $paginas_por_pdf = 5;
    $detalles_por_pdf = $paginas_por_pdf*$detalles_por_pagina;
    $cantidad_totales = count($todos_los_detalles);
    $chunked_detalles = array_chunk($todos_los_detalles,$detalles_por_pdf);
    $chunked_compacts = [];
    foreach($chunked_detalles as $chunk){
      $detalles = $chunk;
      $chunked_compacts[] = compact('pro','detalles','cantidad_totales','cols_x_pag','filas_por_col');
    }

    $paginas = ceil($cantidad_totales / $detalles_por_pagina);
    $salida = PdfParalelo::crear(8,'planillaProducidos',$chunked_compacts,"",$paginas_por_pdf,$paginas);

    if($salida['error'] == 0) return response()->file($salida['value'])->deleteFileAfterSend(true);
    return 'Error codigo: '.$salida['error'].'<br>'.implode('<br>',$salida['value']);
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

  public function calcularProducidoAcumulado($fecha,$maquina){//LLamado desde RelevamientoController
    $chs = ContadorHorario::where([
      ['fecha','=',$fecha],['id_casino','=',$maquina->id_casino]
    ])->orderBy('id_tipo_moneda','asc')->get();
    
    foreach($chs as $ch){//busco todas las monedas, me quedo con el primero que encuentre
      $dch = DetalleContadorHorario::where([
        ['id_contador_horario','=',$ch->id_contador_horario],['id_maquina','=',$maquina->id_maquina]
      ])->first();
      if(is_null($dch)) continue;
      //Ya esta denominado
      //return round(($dch->coinin-$dch->coinout-$dch->jackpot-$dch->progresivo)*$dch->denominacion_carga,2);
      return round($dch->coinin-$dch->coinout-$dch->jackpot-$dch->progresivo,2);
    }
    
    return null;    
  }
  //Contadores en en creditos, producido en plata, se usa en probarAjusteAutomatico y guardarAjuste
  private function calcularDiferencia($arr){
    $coinin_inicio = floatval($arr['coinin_inicio'] ?? 0);
    $coinout_inicio = floatval($arr['coinout_inicio'] ?? 0);
    $jackpot_inicio = floatval($arr['jackpot_inicio'] ?? 0);
    $progresivo_inicio = floatval($arr['progresivo_inicio'] ?? 0);
    $denominacion_inicio = floatval($arr['denominacion_inicio'] ?? 1) ?: 1;
    
    $coinin_final = floatval($arr['coinin_final'] ?? 0);
    $coinout_final = floatval($arr['coinout_final'] ?? 0);
    $jackpot_final = floatval($arr['jackpot_final'] ?? 0);
    $progresivo_final = floatval($arr['progresivo_final'] ?? 0);
    $denominacion_final = floatval($arr['denominacion_final'] ?? 1) ?: 1;
    
    $producido = floatval($arr['producido'] ?? 0);
    
    $valor_inicio = $coinin_inicio - $coinout_inicio - $jackpot_inicio - $progresivo_inicio;//credito
    $valor_inicio *= $denominacion_inicio;//credito -> plata
    $valor_final  = $coinin_final  - $coinout_final  - $jackpot_final  - $progresivo_final;//credito
    $valor_final *= $denominacion_final;//credito -> plata
    $producido_calculado = round($valor_final - $valor_inicio,2);//plata - plata
    $diferencia = round($producido_calculado - $producido,2);//plata - plata;
    return compact('producido_calculado','diferencia');
  }
  
  public function calcularDiferenciaHandlePOST(Request $request){
    return $this->calcularDiferencia($request->all());
  }

  // Obtener todas las diferencias en formato tabla para vista completa
  // SOLO DISPONIBLE PARA SUPERUSUARIOS
  public function obtenerTablaDiferencias($id_producido){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    if(!$usuario->es_superusuario){
      return ['error' => 'Solo superusuarios pueden usar esta función'];
    }
    
    $diferencias = $this->obtenerDiferencias($id_producido);
    $producido = Producido::find($id_producido);
    
    $tabla = [];
    foreach($diferencias as $diff){
      // Determinar si es ajustable automáticamente
      $es_reset = ($diff->coinin_final < $diff->coinin_inicio || $diff->coinout_final < $diff->coinout_inicio);
      
      $puede_ajustar = !($diff->coinin_inicio == 0 || $diff->coinout_inicio == 0 || 
                         $diff->coinin_final == 0 || $diff->coinout_final == 0) || $es_reset;
      
      $tabla[] = [
        'id_maquina' => $diff->id_maquina,
        'nro_admin' => $diff->nro_admin,
        'diferencia' => $diff->diferencia,
        'coinin_inicio' => $diff->coinin_inicio,
        'coinout_inicio' => $diff->coinout_inicio,
        'jackpot_inicio' => $diff->jackpot_inicio,
        'progresivo_inicio' => $diff->progresivo_inicio,
        'coinin_final' => $diff->coinin_final,
        'coinout_final' => $diff->coinout_final,
        'jackpot_final' => $diff->jackpot_final,
        'progresivo_final' => $diff->progresivo_final,
        'denominacion_inicio' => $diff->denominacion_inicio,
        'denominacion_final' => $diff->denominacion_final,
        'producido' => $diff->producido,
        'delta' => $diff->delta,
        'id_detalle_producido' => $diff->id_detalle_producido,
        'id_detalle_contador_inicial' => $diff->id_detalle_contador_inicial,
        'id_detalle_contador_final' => $diff->id_detalle_contador_final,
        'puede_ajustar_auto' => $puede_ajustar,
        'es_reset' => $es_reset,
        'razon_no_ajustable' => $puede_ajustar ? null : 'Contadores en 0'
      ];
    }
    
    return [
      'tabla' => $tabla,
      'total' => count($tabla),
      'producido' => [
        'fecha' => $producido->fecha,
        'casino' => $producido->casino->nombre,
        'moneda' => $producido->tipo_moneda->descripcion
      ]
    ];
  }

  // Importar y comparar Excel del concesionario
  // SOLO DISPONIBLE PARA SUPERUSUARIOS
  public function importarExcelConcesionario(Request $request, $id_producido){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    if(!$usuario->es_superusuario){
      return ['error' => 'Solo superusuarios pueden usar esta función'];
    }
    
    if(!$request->hasFile('archivo_excel')){
      return ['error' => 'No se recibió archivo Excel'];
    }
    
    $archivo = $request->file('archivo_excel');
    
    try {
      // TEST MÍNIMO: Solo verificar que podemos responder JSON
      $archivo = $request->file('archivo_excel');
      $archivoPath = $archivo->getRealPath();
      $archivoSize = filesize($archivoPath);
      
      // Leer con maatwebsite/excel - usar all() en vez de toArray()
      $data = Excel::load($archivoPath)->all();
      
      // Convertir a array simple
      $result = [];
      if($data){
        foreach($data as $sheet){
          if(is_iterable($sheet)){
            foreach($sheet as $row){
              if(is_iterable($row)){
                $rowArray = [];
                foreach($row as $cell){
                  $rowArray[] = $cell;
                }
                $result[] = $rowArray;
              }
            }
          }
          break; // Solo primera sheet
        }
      }
      
      $filas = $result;
      $raw_debug = [
        'archivo_size' => $archivoSize,
        'filas_count' => count($filas)
      ];
      
      if(count($filas) > 0){
        $raw_debug['fila_0'] = array_slice($filas[0], 0, 5);
      }
      if(count($filas) > 7){
        $raw_debug['fila_7'] = array_slice($filas[7], 0, 5);
      }
      if(count($filas) > 8){
        $raw_debug['fila_8'] = array_slice($filas[8], 0, 5);
      }
      
      // DEBUG: Ver estructura real de las primeras filas
      $debug_info = [];
      if(count($filas) > 0){
        $debug_info['fila_0_keys'] = array_keys($filas[0]);
        $debug_info['fila_0_values'] = array_slice($filas[0], 0, 5);
      }
      if(count($filas) > 7){
        $debug_info['fila_7_keys'] = array_keys($filas[7]);
        $debug_info['fila_7_values'] = array_slice($filas[7], 0, 5);
      }
      if(count($filas) > 8){
        $debug_info['fila_8_keys'] = array_keys($filas[8]);
        $debug_info['fila_8_values'] = array_slice($filas[8], 0, 5);
      }
      
      // Determinar si los keys son numéricos o nombres
      $keys_are_numeric = isset($filas[0]) && isset($filas[0][0]);
      $keys_are_named = isset($filas[0]) && !$keys_are_numeric;
      $debug_info['keys_numeric'] = $keys_are_numeric;
      $debug_info['keys_named'] = $keys_are_named;
      
      // Buscar inicio de datos dinámicamente
      $inicio_datos = -1;
      $col_admin = -1; // Puede ser índice numérico o nombre de columna
      
      // Iterar primeras 15 filas buscando cabecera
      for($i = 0; $i < min(15, count($filas)); $i++){
        if(!is_array($filas[$i])) continue;
        
        // Verificar columna B (índice 1) primero
        if(isset($filas[$i][1])){
          $txt = trim(strtolower(strval($filas[$i][1])));
          $debug_info[] = "Fila $i Col B: '$txt'";
          
          if(strpos($txt, 'maquina') !== false || strpos($txt, 'máquina') !== false || $txt == 'máquina' || $txt == 'maquina'){
            $inicio_datos = $i + 1;
            $col_admin = 1;
            break;
          }
        }
      }
      
      // Si no encontró en col B, buscar en cualquier columna
      if($inicio_datos == -1){
        for($i = 0; $i < min(15, count($filas)); $i++){
          if(!is_array($filas[$i])) continue;
          foreach($filas[$i] as $k => $celda){
            if($celda === null) continue;
            $txt = trim(strtolower(strval($celda)));
            if(strpos($txt, 'nro admin') !== false || strpos($txt, 'admin') !== false){
              $inicio_datos = $i + 1;
              $col_admin = $k;
              break 2;
            }
          }
        }
      }
      
      // Fallback final
      if($inicio_datos == -1){
         $inicio_datos = 8; // Fila 9 en Excel (índice 8)
         $col_admin = 1;
      }
      
      for($i = $inicio_datos; $i < count($filas); $i++){
        $fila = $filas[$i];
        if(!isset($fila[$col_admin]) || $fila[$col_admin] === '') continue; // Saltar filas vacías
        
        $nro_admin = trim($fila[$col_admin]);
        if(!is_numeric($nro_admin)) continue;
        
        // Casino Rosario agrega 2 dígitos internos al final (ej: 173000 -> 1730)
        // Quitamos los últimos 2 caracteres si el número tiene más de 4 dígitos
        if(strlen($nro_admin) > 4){
          $nro_admin = substr($nro_admin, 0, -2);
        }

        
        $datos_concesionario[$nro_admin] = [
          'nro_admin' => $nro_admin,
          'coinin_inicio' => floatval(str_replace(' ', '', $fila[$col_admin+1] ?? 0)),
          'coinout_inicio' => floatval(str_replace(' ', '', $fila[$col_admin+2] ?? 0)),
          'jackpot_inicio' => floatval(str_replace(' ', '', $fila[$col_admin+3] ?? 0)),
          'progresivo_inicio' => floatval(str_replace(' ', '', $fila[$col_admin+4] ?? 0)),
          'coinin_final' => floatval(str_replace(' ', '', $fila[$col_admin+5] ?? 0)),
          'coinout_final' => floatval(str_replace(' ', '', $fila[$col_admin+6] ?? 0)),
          'jackpot_final' => floatval(str_replace(' ', '', $fila[$col_admin+7] ?? 0)),
          'progresivo_final' => floatval(str_replace(' ', '', $fila[$col_admin+8] ?? 0)),
          'beneficio' => floatval(str_replace(' ', '', $fila[$col_admin+9] ?? 0))
        ];
      }
      
      // Obtener diferencias del sistema
      $diferencias_sistema = $this->obtenerDiferencias($id_producido);
      
      // Comparar
      $comparacion = [];
      foreach($diferencias_sistema as $diff){
        $nro_admin = $diff->nro_admin;
        $excel = isset($datos_concesionario[$nro_admin]) ? $datos_concesionario[$nro_admin] : null;
        
        $item = [
          'nro_admin' => $nro_admin,
          'id_maquina' => $diff->id_maquina,
          'en_sistema' => true,
          'en_excel' => $excel !== null,
          'sistema' => [
            'coinin_inicio' => $diff->coinin_inicio,
            'coinout_inicio' => $diff->coinout_inicio,
            'coinin_final' => $diff->coinin_final,
            'coinout_final' => $diff->coinout_final,
            'diferencia' => $diff->diferencia
          ],
          'excel' => $excel,
          'discrepancias' => []
        ];
        
        if($excel){
          // Comparar valores (Excel está en créditos, sistema también en créditos)
          if($diff->coinin_inicio != $excel['coinin_inicio']) 
            $item['discrepancias'][] = 'COININ_INI';
          if($diff->coinout_inicio != $excel['coinout_inicio']) 
            $item['discrepancias'][] = 'COINOUT_INI';
          if($diff->coinin_final != $excel['coinin_final']) 
            $item['discrepancias'][] = 'COININ_FIN';
          if($diff->coinout_final != $excel['coinout_final']) 
            $item['discrepancias'][] = 'COINOUT_FIN';
        }
        
        $comparacion[] = $item;
      }
      
      return [
        'success' => true,
        'total_excel' => count($datos_concesionario),
        'total_sistema' => count($diferencias_sistema),
        'comparacion' => $comparacion,
        'datos_excel' => $datos_concesionario,
        'debug' => [
          'inicio_datos' => $inicio_datos,
          'col_admin' => $col_admin,
          'filas_leidas' => count($filas),
          'primeras_celdas' => $debug_info
        ]
      ];
      
    } catch(\Exception $e){
      return ['error' => 'Error al procesar Excel: ' . $e->getMessage()];
    }
  }
}
