<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Producido;
use App\Casino;
use App\Maquina;
use App\DetalleProducido;
use App\AjusteProducido;
use App\DetalleContadorHorario;
use App\ContadorHorario;
use App\AjusteTemporalProducido;
use View;
use Dompdf\Dompdf;
use App\TipoAjuste;
use App\Http\Controllers\FormatoController;


class ProducidoController extends Controller
{
  private static $instance;

  private static $atributos=[];

  private function queryDP($id_producido){
    return DB::table('producido as p')
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
    ->selectRaw('dp.valor as valor_producido, dp.id_detalle_producido,
    m.nro_admin, m.id_maquina, m.denominacion,
    cont_ini.id_contador_horario as id_contador_inicial, 
    cont_fin.id_contador_horario as id_contador_final,
    dc_ini.coinin as coinin_ini, dc_ini.coinout as coinout_ini, dc_ini.jackpot as jackpot_ini, dc_ini.progresivo as progresivo_ini, dc_ini.id_detalle_contador_horario as id_detalle_contador_inicial, dc_ini.denominacion_carga as denominacion_carga_inicial,
    dc_fin.coinin as coinin_fin, dc_fin.coinout as coinout_fin, dc_fin.jackpot as jackpot_fin, dc_fin.progresivo as progresivo_fin, dc_fin.id_detalle_contador_horario as id_detalle_contador_final,   dc_fin.denominacion_carga as denominacion_carga_final');
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
    return view('seccionProducidos' , ['casinos' => $usuario->casinos, 'producidos' => [], 'ultimos' => []]);
  }
  // buscarProducidos
  public function buscarProducidos(Request $request){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    
    $casinos = [];
    foreach ($usuario->casinos as $casino) $casinos[] = $casino->id_casino;

    $reglas = [];
    if($request->validado  != '-') $reglas[] = ['validado','=',$request->validado];
    if($request->id_casino != '0') $reglas[] = ['id_casino','=',$request->id_casino];

    //ultimos producidos cargados en el sistema con el estado de los contadores y archivos asociados.
    $resultados = Producido::whereIn('id_casino',$casinos)->where($reglas)->orderBy('fecha','desc');
    if($request->fecha_inicio != null){
      $fecha_fin = $request->fecha_fin == null ? date('Y-m-d') : $request->fecha_fin;
      $resultados = $resultados->whereBetween('fecha',[$request->fecha_inicio, $fecha_fin])->get();
    }else {
      $resultados = $resultados->take(50)->get();
    }

    $producidos = [];
    foreach($resultados as $resultado){
      $fecha_inicio = $resultado->fecha;
      $fecha_fin = date('Y-m-d' , strtotime($resultado->fecha. ' + 1 days'));

      //cerrado con fecha inicio
      $cerrado = ContadorController::getInstancia()->estaCerrado($fecha_inicio,$resultado->id_casino ,$resultado->tipo_moneda);
      //validado en la fecha fin
      $validado = RelevamientoController::getInstancia()->estaValidado($fecha_fin,$resultado->id_casino, $resultado->tipo_moneda);
      $producidos[] = ['producido' => $resultado ,'cerrado' => $cerrado ,'validado' => $validado ,'casino' =>$resultado->casino , 'tipo_moneda' => $resultado->tipo_moneda];
    }

    if($request->orden == 'asc') $producidos = array_reverse($producidos);
    return ['producidos' => $producidos];
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
      AjusteTemporalProducido::where('id_producido','=',$id_producido)->delete();
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
  // TODO el guardado temporal no esta funcionando, pero no se usa tampoco
  public function datosAjusteMTM($id_maquina,$id_producido){
    $mtm_datos = $this->queryDP($id_producido)->where('m.id_maquina','=',$id_maquina)->get();
    
    //si no trae los datos es porque se guardó temporalmente el ajuste
    if(empty($mtm_datos)){
      $ajusteTemporal = AjusteTemporalProducido::where([['id_producido','=',$id_producido],['id_maquina','=',$id_maquina]])->first();
      $mtm = Maquina::find($id_maquina);
      $conDiferencia = ['id_maquina' => $id_maquina,
                        'nro_admin' => $mtm->nro_admin,
                        'id_detalle_producido' => $ajusteTemporal->id_detalle_producido,
                        'id_detalle_contador_inicial' => $ajusteTemporal->id_detalle_contador_inicial,
                        'id_detalle_contador_final' => $ajusteTemporal->id_detalle_contador_final,
                        'coinin_inicio' => $ajusteTemporal->coinin_ini,
                        'coinout_inicio' => $ajusteTemporal->coinout_ini,
                        'jackpot_inicio' => $ajusteTemporal->jackpot_ini,
                        'progresivo_inicio' => $ajusteTemporal->progresivo_ini,
                        'coinin_final' => $ajusteTemporal->coinin_fin,
                        'coinout_final' =>  $ajusteTemporal->coinout_fin,
                        'jackpot_final' => $ajusteTemporal->jackpot_fin,
                        'progresivo_final' =>$ajusteTemporal->progresivo_fin,
                        'producido_dinero' => $ajusteTemporal->producido_sistema,
                        'denominacion' => $mtm->denominacion,
                        'delta' => $ajusteTemporal->producido_calculado,/*calculado*/
                        'diferencia' => $ajusteTemporal->diferencia,
                        'observacion'=> $ajusteTemporal->observacion];
      return ['producidos_con_diferencia' => $conDiferencia,
              'id_contador_inicial' => $ajusteTemporal->id_contador_inicial,
              'id_contador_final' => $ajusteTemporal->id_contador_final,
              'tipos_ajuste' => TipoAjuste::all()];
    }

    $conDiferencia = []; 
    $id_contador_final = null;
    $id_contador_inicial = null;
    foreach ($mtm_datos as $row) {
        $diferencia = $this->calcularDiferencia(Producido::find($id_producido)->casino->id_casino,
                                                $row->id_maquina,$row->nro_admin,
                                                $row->id_detalle_producido,
                                                $row->id_detalle_contador_inicial,
                                                $row->id_detalle_contador_final,
                                                $row->coinin_ini,$row->coinout_ini,
                                                $row->jackpot_ini,$row->progresivo_ini,
                                                $row->coinin_fin,$row->coinout_fin,
                                                $row->jackpot_fin,$row->progresivo_fin,
                                                $row->valor_producido,$row->denominacion,
                                                $row->denominacion_carga_inicial,$row->denominacion_carga_final);
        if(!empty($diferencia)){
          $conDiferencia[] = $diferencia;
        }
        $id_contador_final   = $row->id_contador_final;
        $id_contador_inicial = $row->id_contador_inicial;
    }
    return ['producidos_con_diferencia' => $conDiferencia,
            'id_contador_inicial' => $id_contador_inicial,
            'id_contador_final' => $id_contador_final,
            'tipos_ajuste' => TipoAjuste::all()];
  }

  // ajustarProducido
  public function ajustarProducido($id_producido){//valido en vista que se pueda cargar.
      $producido = Producido::find($id_producido);

      $resultados = $this->queryDP($id_producido)->get();
      //condiferencia son las maquinas que efectivamente dan diferencia junto con el valor operado que difiere (creo)
      $conDiferencia = [];
      foreach ($resultados as $row) {
          $diferencia = $this->calcularDiferencia($producido->casino->id_casino,$row->id_maquina,$row->nro_admin,
                                                  $row->id_detalle_producido,
                                                  $row->id_detalle_contador_inicial,
                                                  $row->id_detalle_contador_final,
                                                  $row->coinin_ini,$row->coinout_ini,
                                                  $row->jackpot_ini,$row->progresivo_ini,
                                                  $row->coinin_fin,$row->coinout_fin,
                                                  $row->jackpot_fin,$row->progresivo_fin,
                                                  $row->valor_producido,$row->denominacion,
                                                  $row->denominacion_carga_inicial,$row->denominacion_carga_final);

          if(!empty($diferencia)) $conDiferencia[]=$diferencia;

          //al estar deentro del for, solo contara el valor final
          $id_contador_final = $row->id_contador_final;
          $id_contador_inicial = $row->id_contador_inicial;
      }

      //VER SI SE PUEDE OPTIMIZAR- RECORRER DE NUEVO Y GUARDAR LAS DIFERENCIAS TARDA
      //en ajustes_producido se guardan las diferencias entre "producido calculado" y "producido sistema", relacionado a un detalle_producido, el cual tiene la maquina y el producido
      //aca en donde tiene efecto el resultado de calcularDiferencia()
      if($producido->ajustes_producido->count() == 0){
          $conDiferencia2 = [];
          $contadorAjusteAutomatico = 0;
          foreach ($conDiferencia as $diferencia) {
              $diferencia_ajuste = new AjusteProducido;
              $diferencia_ajuste->producido_calculado  = $diferencia['delta'];
              $diferencia_ajuste->producido_sistema    = $diferencia['producido_dinero'];
              $diferencia_ajuste->diferencia           = $diferencia['diferencia'];
              $diferencia_ajuste->id_detalle_producido = $diferencia['id_detalle_producido'];
              $diferencia_ajuste->save();

              // veo si puedo hacer vuelta de contadores automaticamente
              if($this->verAjusteAutomatico($diferencia)){//si no puedo ajustar retorna verdadero
                $conDiferencia2[]=$diferencia;
              }else {
                $contadorAjusteAutomatico++;
              }
          }
          $conDiferencia=$conDiferencia2;
      }

      $id_final = 0;
      if(count($conDiferencia) == 0){
        $producido->validado = 1;
        $producido->save();
        $contador_final = ContadorHorario::find($id_contador_final);
        $contador_final->cerrado= 1;
        $contador_final->save();
        $producido_fin = Producido::where([['producido.fecha'         ,'=', strtotime($producido->fecha. ' + 1 days')] , 
                                           ['producido.id_tipo_moneda','=', $producido->id_tipo_moneda],
                                           ['producido.id_casino'     ,'=', $producido->id_casino]])->first();
        $id_final = ($producido_fin != null) ? $producido_fin->id_producido : 0;
      }

      return ['producidos_con_diferencia' => $conDiferencia,
              'id_contador_final' => $id_contador_final,
              'id_contador_inicial' => $id_contador_inicial,
              'tipos_ajuste' => TipoAjuste::all(),
              'validado' => ['estaValidado' => $producido->validado , 'producido_fin' => $id_final],
              'fecha_produccion' => $producido->fecha,
              'moneda' => $producido->tipo_moneda];
  }

  // calcularDiferencia calcula la diferencia que hay entre el producido calculado a partir de los contadores
  // y el producido importado
  // considera los casos donde no hay contadores importados, en ese caso los setea como 0
  // tiene en cuenta la denominacion para la conversion a dinero, solo en caso del casino de rosario, porque los contadores
  // se importan en creditos
  public function calcularDiferencia($casino,$id_maquina,$nro_admin,$id_detalle_producido , $id_detalle_contador_inicial , $id_detalle_contador_final , $coinin_ini ,$coinout_ini ,$jackpot_ini,$progresivo_ini , $coinin_fin ,$coinout_fin ,$jackpot_fin,$progresivo_fin , $valor_producido, $denominacion,$denominacion_carga_inicial, $denominacion_carga_final){
      $resultado=array();
      //if($id_detalle_contador_final!=null){
      //la denominacion carga es la que se utilizo para convertir a plata al momento de importar, la denominacion sale de la que tenia la maquina al importarte que no necesariamente es la misma al momento de validar producido
      //para santa fe y melincue que se importa en pesos, la denominacion de carga es 1, por lo que no afecta, pero en rosario, que se importa en creditos, si importa y tiene q ser distinto de 1
            //conclusion, si es de Santa Fe queda en plata, porque la denominacion es 1, si es de rosario se hace un cambio previo para cambiarlo a creditos
            if($id_detalle_contador_inicial!=null){
              $coinin_ini=$coinin_ini/$denominacion_carga_inicial;
              $coinout_ini=$coinout_ini/$denominacion_carga_inicial;
              $jackpot_ini=$jackpot_ini/$denominacion_carga_inicial;
              $progresivo_ini=$progresivo_ini/$denominacion_carga_inicial;
            }else{
              $coinin_ini=0;
              $coinout_ini=0;
              $jackpot_ini=0;
              $progresivo_ini=0;
            }

            if($id_detalle_contador_final!=null){
              $coinin_fin=$coinin_fin/$denominacion_carga_final ;
              $coinout_fin= $coinout_fin/$denominacion_carga_final ;
              $jackpot_fin=$jackpot_fin/$denominacion_carga_final ;
              $progresivo_fin=$progresivo_fin/$denominacion_carga_final;
            }else{
              $coinin_fin=0;
              $coinout_fin= 0;
              $jackpot_fin=0;
              $progresivo_fin=0;
            }


            $cantidad=0;

            $valor_inicio= $coinin_ini - $coinout_ini - $jackpot_ini - $progresivo_ini;//plata para santa fe y credito para rosario
            $valor_final= $coinin_fin - $coinout_fin - $jackpot_fin - $progresivo_fin;//plata para santa fe y credito para rosario

            $delta = $valor_final - $valor_inicio;//plata - plata para santa fe //credito- credito para rosario

            if($casino!='3'){
              $diferencia = round($delta, 2) - $valor_producido; //plata - plata
            }else{
              $delta= $delta * $denominacion; //paso a plata con el valor actual de la denominacion
              $diferencia = round($delta, 2) - $valor_producido; //plata - plata
            }

            // si diferencia redondeado con dos, es distinto de cero -> plata --> pasa a credito
            //en este punto se trabaja con la denominacion actual de la maquina, la cual pude no ser la misma que la denomincaicon al momento de la carga
            if(round($diferencia,2) != 0){// si alguno de los campos es null al hacer la division queda 0 -> ver que se termina guardando en la BD
              if($casino!='3'){
                $in_inicio_cred =$coinin_ini / $denominacion;//credito
                $out_inicio_cred = $coinout_ini / $denominacion;//credito
                $jack_ini_cred = $jackpot_ini / $denominacion;//credito
                $prog_ini_cred = $progresivo_ini / $denominacion;//credito
                $in_final_cred = $coinin_fin / $denominacion;//credito
                $out_final_cred = $coinout_fin / $denominacion;//credito
                $jack_final_cred = $jackpot_fin / $denominacion;//credito
                $prog_final_cred =  $progresivo_fin / $denominacion;//credito
                $valor_cred = $valor_producido / $denominacion;//credito
              }else{
                //rosario ya estsa en creditos
                $in_inicio_cred =$coinin_ini ;//credito
                $out_inicio_cred = $coinout_ini ;//credito
                $jack_ini_cred = $jackpot_ini ;//credito
                $prog_ini_cred = $progresivo_ini ;//credito
                $in_final_cred = $coinin_fin;//credito
                $out_final_cred = $coinout_fin ;//credito
                $jack_final_cred = $jackpot_fin ;//credito
                $prog_final_cred =  $progresivo_fin ;//credito
                $valor_cred = $valor_producido /$denominacion; //credito

              }



              $resultado = ['id_maquina' => $id_maquina,                                'nro_admin' => $nro_admin,
                            'id_detalle_producido' => $id_detalle_producido,            'id_detalle_contador_inicial' => $id_detalle_contador_inicial,
                            'id_detalle_contador_final' => $id_detalle_contador_final,  'coinin_inicio' => $in_inicio_cred,
                            'coinout_inicio' => $out_inicio_cred,                       'jackpot_inicio' => $jack_ini_cred,
                            'progresivo_inicio' => $prog_ini_cred,                      'coinin_final' => $in_final_cred,
                            'coinout_final' =>  $out_final_cred,                        'jackpot_final' => $jack_final_cred,
                            'progresivo_final' =>$prog_final_cred,                      'producido_dinero' => $valor_producido,
                            'producido_cred' => $valor_cred,                            'denominacion' => $denominacion ,
                            'delta' => round($delta, 2),                                'diferencia' => round($diferencia, 2),
                            'casino'=>$casino];

            }
    //}

      return $resultado;
  }

  // verAjusteAutomatico genera los ajustes automaticos
  // pueden ser calculados numericamente de forma automatica
  public function verAjusteAutomatico($arreglo_diferencia){
    $numero=$arreglo_diferencia['diferencia']/$arreglo_diferencia['denominacion'];
    while($numero % 10  == 0){
      $numero = intdiv($numero , 10);
    }
    //dd($arreglo_diferencia);
    if(abs($numero)== 1){ //aca concluyo que es multiplo 10
      //si dio vuelta coinin
      if($arreglo_diferencia['coinin_final'] < $arreglo_diferencia['coinin_inicio']){ // si coinin final mas chico que inicial checkeo si dio vuelta

          $coinin = $arreglo_diferencia['coinin_final'] + 100000000;

          $valor_inicio= $arreglo_diferencia['coinin_inicio'] - $arreglo_diferencia['coinout_inicio'] - $arreglo_diferencia['jackpot_inicio'] - $arreglo_diferencia['progresivo_inicio'];//credito
          $valor_final= $coinin - $arreglo_diferencia['coinout_final'] - $arreglo_diferencia['jackpot_final'] - $arreglo_diferencia['progresivo_final'];//credito

          $delta = $valor_final - $valor_inicio;//credito - crecdito
          $diferencia = round(($delta*$arreglo_diferencia['denominacion']), 2) - $arreglo_diferencia['producido_dinero']; //plata - plata

          if(round($diferencia,2) == 0){
            $detalle_producido=DetalleProducido::find($arreglo_diferencia['id_detalle_producido']);
            $detalle_producido->id_tipo_ajuste = 1;
            $detalle_producido->save();
            return false;
          }

      }

      //si dio vuelta coinout
      if($arreglo_diferencia['coinout_final'] < $arreglo_diferencia['coinout_inicio']){

        $coinout = $arreglo_diferencia['coinout_final'] + 100000000;

        $valor_inicio= $arreglo_diferencia['coinin_inicio'] - $arreglo_diferencia['coinout_inicio'] - $arreglo_diferencia['jackpot_inicio'] - $arreglo_diferencia['progresivo_inicio'];//credito
        $valor_final= $arreglo_diferencia['coinin_final'] - $coinout - $arreglo_diferencia['jackpot_final'] - $arreglo_diferencia['progresivo_final'];//credito

        $delta = $valor_final - $valor_inicio;//credito - crecdito
        $diferencia = round(($delta*$arreglo_diferencia['denominacion']), 2) - $arreglo_diferencia['producido_dinero']; //plata - plata

        if(round($diferencia,2) == 0){
          $detalle_producido=DetalleProducido::find($arreglo_diferencia['id_detalle_producido']);
          $detalle_producido->id_tipo_ajuste = 1;
          $detalle_producido->save();
          return false;
        }
      }

      //si dio vuelta jackpot
      if($arreglo_diferencia['jackpot_final'] < $arreglo_diferencia['jackpot_inicio']){

        $jackpot = $arreglo_diferencia['jackpot_final'] + 100000000;

        $valor_inicio= $arreglo_diferencia['coinin_inicio'] - $arreglo_diferencia['coinout_inicio'] - $arreglo_diferencia['jackpot_inicio'] - $arreglo_diferencia['progresivo_inicio'];//credito
        $valor_final= $arreglo_diferencia['coinin_final'] - $arreglo_diferencia['coinout_final'] - $jackpot  - $arreglo_diferencia['progresivo_final'];//credito

        $delta = $valor_final - $valor_inicio;//credito - crecdito
        $diferencia = round(($delta*$arreglo_diferencia['denominacion']), 2) - $arreglo_diferencia['producido_dinero']; //plata - plata

        if(round($diferencia,2) == 0){
          $detalle_producido=DetalleProducido::find($arreglo_diferencia['id_detalle_producido']);
          $detalle_producido->id_tipo_ajuste = 1;
          $detalle_producido->save();
          return false;
        }
      }

      //si dio vuelta progresivo
      if($arreglo_diferencia['progresivo_final'] < $arreglo_diferencia['progresivo_final']){

        $progresivo = $arreglo_diferencia['progresivo_final'] + 100000000;

        $valor_inicio= $arreglo_diferencia['coinin_inicio'] - $arreglo_diferencia['coinout_inicio'] - $arreglo_diferencia['jackpot_inicio'] - $arreglo_diferencia['progresivo_inicio'];//credito
        $valor_final= $arreglo_diferencia['coinin_final'] - $arreglo_diferencia['coinout_final'] - $arreglo_diferencia['jackpot_final']  - $progresivo ;//credito

        $delta = $valor_final - $valor_inicio;//credito - crecdito
        $diferencia = round(($delta*$arreglo_diferencia['denominacion']), 2) - $arreglo_diferencia['producido_dinero']; //plata - plata


        if(round($diferencia,2) == 0){
          $detalle_producido=DetalleProducido::find($arreglo_diferencia['id_detalle_producido']);
          $detalle_producido->id_tipo_ajuste = 1;
          $detalle_producido->save();
          return false;
        }
      }
    }

    if($arreglo_diferencia['coinin_final'] <= $arreglo_diferencia['coinin_inicio']
      && $arreglo_diferencia['coinout_final'] <= $arreglo_diferencia['coinout_inicio']
      && $arreglo_diferencia['jackpot_final'] <= $arreglo_diferencia['jackpot_inicio']
      && $arreglo_diferencia['progresivo_final'] <= $arreglo_diferencia['progresivo_inicio']
    ){//si TODOS los contadores finales son menores o iguales a los iniciales -> posible reset

      $progresivo = $arreglo_diferencia['progresivo_final'] + 100000000;

      $valor_inicio= $arreglo_diferencia['coinin_inicio'] - $arreglo_diferencia['coinout_inicio'] - $arreglo_diferencia['jackpot_inicio'] - $arreglo_diferencia['progresivo_inicio'];//credito
      $valor_final= ($arreglo_diferencia['coinin_final'] + $arreglo_diferencia['coinin_inicio']) - ($arreglo_diferencia['coinout_final'] +  $arreglo_diferencia['coinout_inicio']) - ($arreglo_diferencia['jackpot_final'] +$arreglo_diferencia['jackpot_inicio'])  - ($arreglo_diferencia['progresivo_final'] +$arreglo_diferencia['progresivo_inicio'] ) ;//credito

      $delta = $valor_final - $valor_inicio;//credito - crecdito
      $diferencia = round(($delta*$arreglo_diferencia['denominacion']), 2) - $arreglo_diferencia['producido_dinero']; //plata - plata

      if(round($diferencia,2) == 0){
        $detalle_producido=DetalleProducido::find($arreglo_diferencia['id_detalle_producido']);
        $detalle_producido->id_tipo_ajuste = 2;
        $detalle_producido->save();
        return false;
      }
    }

    return true;
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
      $index=0;
      $resultados=array();
      $errores=array();
      $estado = 0;

      if($request->estado == 3){
        foreach ($request->producidos_ajustados as $detalle_ajustado){
          //consulto si tenia un ajuste guardado como temporal y lo elimino
          //porque se supone que lo esta guardando
          $consultaTemporalesMTM = AjusteTemporalProducido::where([['id_producido','=',$request['id_producido']],['id_maquina','=',$detalle_ajustado['id_maquina']]])->get();
          if(count($consultaTemporalesMTM) > 0){
            try{
              $consultaTemporalesMTM->maquina()->dissociate();
              $consultaTemporalesMTM->producido()->dissociate();
              $consultaTemporalesMTM->tipo_ajuste()->dissociate();
              $consultaTemporalesMTM->delete();
            }catch(Exception $e){
              //no tenia ajuste temporal
            }
          }

          $detalle_final=DetalleContadorHorario::find($detalle_ajustado['id_detalle_contador_final']) ;
          $detalle_inicio=DetalleContadorHorario::find($detalle_ajustado['id_detalle_contador_inicial']) ;
          $detalle_producido = DetalleProducido::find($detalle_ajustado['id_detalle_producido']);
          $producido = Producido::find($detalle_producido->id_producido);
          $casino=$producido->id_casino;
          //se agregan las observaciones, estas son independientes del tipo de ajuste, el propio del detalle producido
          $detalle_producido->observacion=$detalle_ajustado['prodObservaciones'];
          switch ($detalle_ajustado['id_tipo_ajuste']) {
            case 1: // vuelta contadores
                $index++;
                if($this->validarDiferenciaConFinalesModificados($detalle_ajustado['coinin_final'] , $detalle_ajustado['coinout_final'] , $detalle_ajustado['jackpot_final'], $detalle_ajustado['progresivo_final'],$detalle_inicio,$detalle_producido , $detalle_ajustado['denominacion'])){
                  $detalle_producido->id_tipo_ajuste= $detalle_ajustado['id_tipo_ajuste'];
                  $detalle_producido->save();
                  $resultados[]=$detalle_ajustado['id_maquina'];
                }else {
                  $errores[]=$detalle_ajustado['id_maquina'];
                }

                break;
            case 2://reset contadores
                $index++;
                if($this->validarDiferenciaConFinalesModificados($detalle_ajustado['coinin_final'] , $detalle_ajustado['coinout_final'] , $detalle_ajustado['jackpot_final'], $detalle_ajustado['progresivo_final'],$detalle_inicio,$detalle_producido ,$detalle_ajustado['denominacion'])){
                  $detalle_producido->id_tipo_ajuste= $detalle_ajustado['id_tipo_ajuste'];
                  $detalle_producido->save();
                  $resultados[]=$detalle_ajustado['id_maquina'];
                }else {
                  $errores[]=$detalle_ajustado['id_maquina'];
                }
                break;
            case 3://sin contadores finales - cambi contadores finales
                $index++;
                if($this->validarDiferenciaConFinalesModificados($detalle_ajustado['coinin_final'] , $detalle_ajustado['coinout_final'] , $detalle_ajustado['jackpot_final'], $detalle_ajustado['progresivo_final'],$detalle_inicio,$detalle_producido, $detalle_ajustado['denominacion'])){
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
                  if($casino==3){
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
                $index++;
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
                $index++;
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
                  if($casino==3){
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
                $index++;
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
                if($casino==3){
                  $detalle_inicio->denominacion_carga=$detalle_ajustado['denominacion'];
                  }
                $detalle_inicio->save();

                $detalle_final->coinin = round($detalle_ajustado['coinin_final'] * $detalle_ajustado['denominacion'] , 2 );
                $detalle_final->coinout = round($detalle_ajustado['coinout_final'] * $detalle_ajustado['denominacion'] , 2 );
                $detalle_final->jackpot = round($detalle_ajustado['jackpot_final'] * $detalle_ajustado['denominacion'] , 2 );
                $detalle_final->progresivo = round($detalle_ajustado['progresivo_final'] * $detalle_ajustado['denominacion'] , 2 );
                // si el casino es de rosario, se tiene que cargar la denominacion de carga
                if($casino==3){
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
      }elseif ($request->estado == 2) {//esta pausado
        //por mas que no haya puesto todavia una justificación para el ajuste
        //se guarda como temporal (no se valida nada) y se guarda en una tabla aparte
        //todos los datos que se mostraon en pantalla y ocultos para poder reabrir
        //en la proxima oportunidad para ajustar finalmente el producido.

        $id_maquina = $request['producidos_ajustados'][0]['id_maquina'];
        $mtm = Maquina::find($id_maquina);
        $denominacion = $mtm->denominacion;
        $input  = $request['producidos_ajustados'][0];

        $ajusteTemporal = new AjusteTemporalProducido;
        $ajusteTemporal->producido()->associate($request->id_producido);

        if(!empty($input->id_tipo_ajuste)){
          $ajusteTemporal->tipo_ajuste()->associate($input->id_tipo_ajuste);
        }
        $ajusteTemporal->maquina()->associate($id_maquina);
        $ajusteTemporal->id_contador_horario_ini = $request->id_contador_inicial;
        $ajusteTemporal->id_contador_horario_fin = $request->id_contador_final;
        $ajusteTemporal->coinin_ini = $input['coinin_inicial'];
        $ajusteTemporal->coinin_fin = $input['coinin_final'];
        $ajusteTemporal->coinout_ini = $input['coinout_inicial'];
        $ajusteTemporal->coinout_fin = $input['coinout_final'];
        $ajusteTemporal->jackpot_ini = $input['jackpot_inicial'];
        $ajusteTemporal->jackpot_fin = $input['jackpot_final'];
        $ajusteTemporal->progresivo_ini = $input['progresivo_inicial'];
        $ajusteTemporal->progresivo_fin = $input['progresivo_final'];

        $ajusteTemporal->id_detalle_producido = $input['id_detalle_producido'];
        $ajusteTemporal->id_detalle_contador_inicial = $input['id_detalle_contador_inicial'];
        $ajusteTemporal->id_detalle_contador_final = $input['id_detalle_contador_final'];
        $ajusteTemporal->producido_sistema = $input['producido']; //es el producido importado

        //se agrega el campo observacion
        $ajusteTemporal->observacion=$input['prodObservaciones'];


        //calculo el producido calculado
        $valor_inicio= $ajusteTemporal->coinin_ini * $denominacion - $ajusteTemporal->coinout_ini * $denominacion - $ajusteTemporal->ackpot_ini * $denominacion- $ajusteTemporal->progresivo_ini * $denominacion;//plata
        $valor_final= $ajusteTemporal->coinin_fin * $denominacion- $ajusteTemporal->coinout_fin * $denominacion- $ajusteTemporal->jackpot_fin * $denominacion- $ajusteTemporal->progresivo_fin * $denominacion;//plata

        $delta = $valor_final - $valor_inicio;
        //lo asigno
        $ajusteTemporal->producido_calculado = $delta;

        //calculo la DIFERENCIAS
        $ajusteTemporal->diferencia = $delta - $ajusteTemporal->producido_sistema ;
        $ajusteTemporal->save();
        $estado=2;
      }



      return [
        'estado' => $estado,
        'resueltas' => $resultados,
        'errores' => $errores,
      ];

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

  public function validarDiferenciaConFinalesModificados($coinin,$coinout,$jackpot,$progresivo,$detalle_inicial,$detalle_producido, $denominacion){
      $valor_inicio = $detalle_inicial->coinin - $detalle_inicial->coinout - $detalle_inicial->jackpot - $detalle_inicial->progresivo;//plata
      $valor_final = $coinin - $coinout - $jackpot - $progresivo;//esto es creditos
      $delta = round(($valor_final * $denominacion) , 2 )  - $valor_inicio ;
      $diferencia= round($delta , 2) - $detalle_producido->valor;
      $diferencia_final= round(($diferencia), 2);
      if ($diferencia_final == 0) {
        return true;
      }else {
        return false;
      }
  }

  public function validarDiferenciaConInicialesModificados($coinin,$coinout,$jackpot,$progresivo,$detalle_final,$detalle_producido , $denominacion){
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

  public function validarDiferenciaConProducidoModificados($detalle_inicial,$detalle_final, $valor_producido, $denominacion){//deberia hacerlo en creditos ?
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
