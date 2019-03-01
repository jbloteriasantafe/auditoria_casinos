<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\DB;
use App\TipoMoneda;
use App\Beneficio;
use View;
use Dompdf\Dompdf;
use App\AjusteBeneficio;
use App\Producido;
use App\BeneficioMensual;
use App\Porcentaje;

class BeneficioController extends Controller
{
  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new BeneficioController();
    }
    return self::$instance;
  }

  private static $atributos=[];

  public function eliminarBeneficio($id_beneficio){
    Validator::make(['id_beneficio' => $id_beneficio]
                   ,['id_beneficio' => 'required|exists:beneficio,id_beneficio']
                   , array(), self::$atributos)->after(function($validator){
                   })->validate();

    Beneficio::destroy($id_beneficio);
  }

  public function buscarTodo(){

    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Beneficios' ,'beneficios');
    return view('seccionBeneficios',['casinos' => $usuario->casinos,'tipos_moneda' => TipoMoneda::all()]);

  }

  public function buscarBeneficios(Request $request){
    Validator::make($request->all(), [
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after:fecha_desde'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    $query_conditions = " 1 = 1 ";

    if(!empty($request->id_tipo_moneda) && $request->id_tipo_moneda != 0){

      $query_conditions = $query_conditions." AND beneficio.id_tipo_moneda = ".$request->id_tipo_moneda;
    }

    if(!empty($request->fecha_desde)){

      $query_conditions = $query_conditions." AND beneficio.fecha >= '".$request->fecha_desde."'";

    }
    if(!empty($request->fecha_hasta)){

      $query_conditions = $query_conditions." AND beneficio.fecha < '".$request->fecha_hasta."'";

    }

    $casinos = array();
    if($request->id_casino == 0){
      $query_casinos = " (0";
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      foreach($usuario->casinos as $casino){
        $casinos[] = $casino->id_casino;
        $query_casinos = $query_casinos.",".($casino->id_casino);
      }
      $query_casinos = $query_casinos.") ";
    }else{
      $casinos[] = $request->id_casino;
      $query_casinos = " (".($request->id_casino).") ";
    }

    $sort_by = $request->sort_by;
    $offset = ($request->page > 1) ? $request->page*$request->page_size : 0;
    $paginacion = " LIMIT ".$request->page_size." OFFSET ".$offset;
    if($request->sort_by != null){
      $paginacion = " ORDER BY ".$request->sort_by['columna']." ".$request->sort_by['orden'].$paginacion;
    }else{
      $paginacion = " ORDER BY diferencias_mes.anio desc, diferencias_mes.mes desc".$paginacion;
    }

    $select = " SELECT tipo_moneda.descripcion AS tipo_moneda, casino.nombre as casino, diferencias_mes.*";

    $query = "
     FROM (SELECT SUM(CASE WHEN ROUND(diferencia_dia.valor) != 0 THEN 1 ELSE 0 END) AS diferencias_mes, beneficio.id_casino AS id_casino, beneficio.id_tipo_moneda AS id_tipo_moneda, YEAR(beneficio.fecha) AS anio, MONTH(beneficio.fecha) AS mes
           FROM (SELECT beneficio_calculado.id_beneficio AS id_beneficio, ((beneficio_calculado.suma + IFNULL(ajuste_beneficio.valor,0)) - beneficio.valor) AS valor
                 FROM (SELECT beneficio.id_beneficio as id_beneficio, SUM(IFNULL(detalle_producido.valor,0)) AS suma
                       FROM beneficio
                       LEFT JOIN producido ON producido.fecha = beneficio.fecha AND producido.id_casino = beneficio.id_casino AND producido.id_tipo_moneda = beneficio.id_tipo_moneda
                       LEFT JOIN detalle_producido ON detalle_producido.id_producido = producido.id_producido
                       WHERE ".$query_conditions." AND beneficio.id_casino IN ".$query_casinos."
                       GROUP BY beneficio.id_beneficio) AS beneficio_calculado
                 LEFT JOIN ajuste_beneficio ON ajuste_beneficio.id_beneficio = beneficio_calculado.id_beneficio
                 JOIN beneficio ON beneficio.id_beneficio = beneficio_calculado.id_beneficio) AS diferencia_dia
           JOIN beneficio ON beneficio.id_beneficio = diferencia_dia.id_beneficio
           GROUP BY beneficio.id_casino,beneficio.id_tipo_moneda,YEAR(beneficio.fecha),MONTH(beneficio.fecha)) AS diferencias_mes
     JOIN casino ON casino.id_casino = diferencias_mes.id_casino
     JOIN tipo_moneda ON tipo_moneda.id_tipo_moneda = diferencias_mes.id_tipo_moneda ";

    $pdo = DB::connection('mysql')->getPdo();
    $resultados = $pdo->query($select.$query.$paginacion);

    $total = $pdo->query(" SELECT COUNT(*) as cantitad ".$query);

    foreach($total as $tot){
      $total = $tot['cantitad'];
      break;
    }

    $retorno = array();
    foreach($resultados as $row){
      $pos = new \stdClass();
      $pos->mes = $row['mes'];
      $pos->anio = $row['anio'];
      $pos->id_casino = $row['id_casino'];
      $pos->casino = $row['casino'];
      $pos->id_tipo_moneda = $row['id_tipo_moneda'];
      $pos->tipo_moneda = $row['tipo_moneda'];
      $pos->diferencias_mes = $row['diferencias_mes'];
      $aux = BeneficioMensual::where([['id_casino',$row['id_casino']],['id_actividad',1],['id_tipo_moneda',$row['id_tipo_moneda']]])->whereYear('anio_mes',$row['anio'])->whereMonth('anio_mes',$row['mes'])->first();
      $pos->id_beneficio_mensual = ($aux != null) ? $aux->id_beneficio_mensual : null;
      $retorno[]= $pos;
    }

    return ['data' => $retorno,'total' => $total];
  }

  public function obtenerBeneficiosParaValidar(Request $request){
    $resultados = $this->obtenerBeneficiosPorMes($request->id_casino,$request->id_tipo_moneda,$request->anio,$request->mes);

    return ['resultados' => $resultados];
  }
  // cami coments
  //desde el modal de ajustar beneficios,
  //directamente se ajusta desde ahi cada beneficio.
  //y en la pantalla le indica como queda la diferencia, segun los
  //valores que habia recibido cuando abrio el modal.
  //o sea, que si yo ajusto 20 mil veces la misma fecha,
  // a las modificaciones anteriores no las elimina!!! WTF!?
  public function ajustarBeneficio(Request $request){
    Validator::make($request->all(), [
            'valor' => ['nullable','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
            'id_beneficio' => 'required|exists:beneficio,id_beneficio'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    $ajuste = new AjusteBeneficio();
    $ajuste->valor = $request->valor;
    $ajuste->id_beneficio = $request->id_beneficio;
    $ajuste->save();

    return ['ajuste' => $ajuste];
  }

  public function validarBeneficios(Request $request){
    $validator = Validator::make($request->all(), [
            'benficios_ajustados' => 'nullable',
            'benficios_ajustados.*.id_beneficio' => 'required|exists:beneficio,id_beneficio',
            'benficios_ajustados.*.observacion' => 'nullable|max:500'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();
    if(isset($validator))
    {
      if ($validator->fails())
      {
        return [
              'errors' => $v->getMessageBag()->toArray()
          ];
      }
    }
    $errors = null;
    //dd($validator);
    if($request->beneficios_ajustados != null){
      foreach($request->beneficios_ajustados as $beneficio_ajustado){
        $ben = Beneficio::find($beneficio_ajustado['id_beneficio']);
        if($ben != null){
          $fecha = $ben->fecha;
          $ben->observacion = $beneficio_ajustado['observacion'];

          $prod = Producido::where([['fecha',$ben->fecha],['id_casino',$ben->id_casino],['id_tipo_moneda',$ben->id_tipo_moneda]])->first();
          if($prod != null){
            $producido_calculado = $prod->beneficio_calculado; //calcula atributo en el producido sumandole el ajuste reciente

            if($producido_calculado != null && round($producido_calculado - $ben->valor,2) == 0){
              $ben->validado = 1;
            }else{//si no lo valida, largo error
              $errors = new MessageBag;
              $errors->add('id_beneficio', 'No se ajustó el beneficio del día '.$fecha.'. Diferencia de '.round($producido_calculado - $ben->valor,2).'.');
            }
          }else{
            $errors = new MessageBag;
            $errors->add('id_producido', 'No hay producidos cargados para el beneficio del día '.$fecha.'.');
          }

          $ben->save();
        }else{
          $errors = new MessageBag;
          $errors->add('not_found', 'Beneficio del día '.$fecha.' no encontrado.');
        }
      }//fin for each
      if(isset($errors))
      {
        return response()->json($errors->toArray(), 404);

      }

      $ben = Beneficio::find($request->beneficios_ajustados[0]['id_beneficio']);
      $fecha = $ben->fecha;
      $mes = date("n",strtotime($fecha));
      $anio = date("Y",strtotime($fecha));
      // si estan los beneficios para todo el mes cargados y validados, guardo el beneficio mensual correspondiente
      $cant_dias = cal_days_in_month(CAL_GREGORIAN,$mes,$anio);
      $bandera = true;
      $acumulado = 0;

      for($i = 1; $i <= $cant_dias; $i++){ // casino, fecha, tipo_moneda
        $benef = Beneficio::where([['id_casino',$ben->casino->id_casino],['id_tipo_moneda',$ben->tipo_moneda->id_tipo_moneda]])
                          ->whereYear('fecha',$anio)
                          ->whereMonth('fecha',$mes)
                          ->whereDay('fecha',$i)
                          ->first();

        if($benef != null && $benef->validado == 1){
          $acumulado = $acumulado + $benef->valor;
        }
        else{
          $bandera = false;
          $i = $cant_dias;
        }
      }
      if($bandera){
        $beneficio_mensual = new BeneficioMensual;
        $beneficio_mensual->id_casino = $ben->id_casino;
        $beneficio_mensual->id_tipo_moneda = $ben->id_tipo_moneda;
        $beneficio_mensual->id_actividad = 1;
        $beneficio_mensual->anio_mes = ''.$anio.'-'.$mes.'-01'; // Ej: 2017-08-01
        //$porcentaje = Porcentaje::where([['id_casino',$ben->id_casino],['id_actividad',1]])->first();
        //$beneficio_mensual->canon = ($acumulado - iea)*($porcentaje->valor);
        $beneficio_mensual->bruto = $acumulado;
        //$beneficio_mensual->iea = algo;
        $beneficio_mensual->save();
      }else{
        return response()->json("Faltan importar beneficios", 404);
      }

    }
    // TODO gestionar el error en el caso de que no se importaron los producidos
    // ene se caso no va dar error pero tampoco va generar el producido mensual
    return "true";
  }

  public function validarBeneficiosSinProducidos(Request $request){
    $validator = Validator::make($request->all(), [
            'benficios_ajustados' => 'nullable',
            'benficios_ajustados.*.id_beneficio' => 'required|exists:beneficio,id_beneficio',
            'benficios_ajustados.*.observacion' => 'nullable|max:500'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();
    if(isset($validator))
    {
      if ($validator->fails())
      {
        return [
              'errors' => $v->getMessageBag()->toArray()
          ];
      }
    }
    $errors = null;
    //dd($validator);
    if($request->beneficios_ajustados != null){
      foreach($request->beneficios_ajustados as $beneficio_ajustado){
        $ben = Beneficio::find($beneficio_ajustado['id_beneficio']);
        if($ben != null){
          $fecha = $ben->fecha;
          $ben->observacion = $beneficio_ajustado['observacion'];

          $prod = Producido::where([['fecha',$ben->fecha],['id_casino',$ben->id_casino],['id_tipo_moneda',$ben->id_tipo_moneda]])->first();
          if($prod != null){
            $producido_calculado = $prod->beneficio_calculado; //calcula atributo en el producido

            if($producido_calculado != null && round($producido_calculado - $ben->valor,2) == 0){
              $ben->validado = 1;
            }else{//si no lo valida, largo error
              $errors = new MessageBag;
              $errors->add('id_beneficio', 'No se ajustó el beneficio del día '.$fecha.'. Diferencia de '.round($producido_calculado - $ben->valor,2).'.');
            }
          }else{
            $ben->validado = 1;
          }

          $ben->save();
        }else{
          $errors = new MessageBag;
          $errors->add('not_found', 'Beneficio del día '.$fecha.' no encontrado.');
        }
      }//fin for each

      if(isset($errors))
      {
        return response()->json($errors->toArray(), 404);

      }

      $ben = Beneficio::find($request->beneficios_ajustados[0]['id_beneficio']);
      $fecha = $ben->fecha;
      $mes = date("n",strtotime($fecha));
      $anio = date("Y",strtotime($fecha));
      // si estan los beneficios para todo el mes cargados y validados, guardo el beneficio mensual correspondiente
      $cant_dias = cal_days_in_month(CAL_GREGORIAN,$mes,$anio);
      $bandera = true;
      $acumulado = 0;
      for($i = 1; $i <= $cant_dias; $i++){ // casino, fecha, tipo_moneda
        $benef = Beneficio::where([['id_casino',$ben->casino->id_casino],['id_tipo_moneda',$ben->tipo_moneda->id_tipo_moneda]])
                          ->whereYear('fecha',$anio)
                          ->whereMonth('fecha',$mes)
                          ->whereDay('fecha',$i)
                          ->first();
        if($benef != null && $benef->validado == 1){
          $acumulado = $acumulado + $benef->valor;
        }
        else{
          $i = $cant_dias;
        }
      }
     // como se esta intentando validar dias sin producidos, se genera el mensual de todas formas
        $beneficio_mensual = new BeneficioMensual;
        $beneficio_mensual->id_casino = $ben->id_casino;
        $beneficio_mensual->id_tipo_moneda = $ben->id_tipo_moneda;
        $beneficio_mensual->id_actividad = 1;
        $beneficio_mensual->anio_mes = ''.$anio.'-'.$mes.'-01'; // Ej: 2017-08-01
        //$porcentaje = Porcentaje::where([['id_casino',$ben->id_casino],['id_actividad',1]])->first();
        //$beneficio_mensual->canon = ($acumulado - iea)*($porcentaje->valor);
        $beneficio_mensual->bruto = $acumulado;
        //$beneficio_mensual->iea = algo;
        $beneficio_mensual->save();
      
    }
    return "true";
  }

  public function cargarImpuesto(Request $request){
    Validator::make($request->all(), [
            'id_beneficio_mensual' => 'required|exists:beneficio_mensual,id_beneficio_mensual',
            'impuesto' => ['required','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/']
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    $ben = BeneficioMensual::find($request->id_beneficio_mensual);
    $ben->iea = $request->impuesto;
    $porcentaje = Porcentaje::where([['id_casino',$ben->id_casino],['id_actividad',1]])->first();
    $ben->canon = ($ben->bruto - $ben->iea)*$porcentaje->valor;
    $ben->save();

    return $ben;
  }

  private function obtenerBeneficiosPorMes($id_casino,$id_tipo_moneda,$anio,$mes){
    $resultados = DB::table('beneficio')->select('beneficio.id_beneficio as id_beneficio','beneficio.fecha as fecha',
                                                  DB::raw('(CAST(beneficio.valor AS DECIMAL(15,2))) as beneficio'),
                                                  DB::raw('(CAST((SUM(IFNULL(detalle_producido.valor,0)) + IFNULL(ajuste_beneficio.valor,0)) AS DECIMAL(15,2))) AS beneficio_calculado'),
                                                  DB::raw('(CAST(((SUM(IFNULL(detalle_producido.valor,0)) + IFNULL(ajuste_beneficio.valor,0)) - beneficio.valor) AS DECIMAL(15,2))) AS diferencia'),
                                                  DB::raw('CASE WHEN ((IFNULL(producido.id_producido,0)) != 0) THEN 1 ELSE 0 END AS existe_producido'),
                                                  'producido.id_producido as id_producido')
                                        ->leftJoin('producido',function ($leftJoin) use ($id_casino,$id_tipo_moneda,$anio,$mes){
                                          $leftJoin->on('producido.fecha','=','beneficio.fecha')
                                               ->where([['producido.id_casino','=',$id_casino],['producido.id_tipo_moneda','=',$id_tipo_moneda]])
                                               ->whereMonth('producido.fecha',$mes)
                                               ->whereYear('producido.fecha',$anio);
                                          })
                                        ->leftJoin('detalle_producido','detalle_producido.id_producido','=','producido.id_producido')
                                        ->leftJoin('ajuste_beneficio','ajuste_beneficio.id_beneficio','=','beneficio.id_beneficio')
                                        ->where([['beneficio.id_casino',$id_casino],['beneficio.id_tipo_moneda',$id_tipo_moneda]])
                                        ->whereMonth('beneficio.fecha',$mes)
                                        ->whereYear('beneficio.fecha',$anio)
                                        ->groupBy('beneficio.valor','beneficio.fecha','beneficio.id_beneficio','producido.id_producido','ajuste_beneficio.valor')
                                        ->orderBy('beneficio.fecha','asc')
                                        ->get();

    return $resultados;
  }

  /*^^^^
  *|||||
  //con imaginacion son flechas
  *
  * retorna : un array con :
    id_beneficio	822
    fecha	2018-06-01
    beneficio	3465750.37
    beneficio_calculado	3465750.37
    diferencia	0.00
    existe_producido	1


    para cada fecha del mes
  */


  public function generarPlanilla($id_casino,$id_tipo_moneda,$anio,$mes){

    $nombreCasino = DB::table('casino')->select('casino.nombre as nombre')
                                         ->where('casino.id_casino',$id_casino)
                                         ->first();

    $tipoMoneda = DB::table('tipo_moneda')->select('tipo_moneda.descripcion as tipo_moneda')
                                           ->where('tipo_moneda.id_tipo_moneda',$id_tipo_moneda)
                                           ->first();

    $ben = new \stdClass();
    $ben->casino = $nombreCasino->nombre;
    $ben->moneda = $tipoMoneda->tipo_moneda;
    if($ben->moneda == 'ARS'){
         $ben->moneda = 'Pesos';
         }
       else{
         $ben->moneda = 'Dólares';
         }

    switch ($mes) {
         case 1:
             $mesEdit = "Enero";
             break;
         case 2:
             $mesEdit = "Febrero";
             break;
         case 3:
             $mesEdit = "Marzo";
             break;
         case 4:
             $mesEdit = "Abril";
             break;
         case 5:
             $mesEdit = "Mayo";
             break;
         case 6:
             $mesEdit = "Junio";
             break;
         case 7:
             $mesEdit = "Julio";
             break;
         case 8:
             $mesEdit = "Agosto";
             break;
         case 9:
             $mesEdit = "Septiembre";
             break;
         case 10:
             $mesEdit = "Octubre";
             break;
         case 11:
             $mesEdit = "Noviembre";
             break;
         case 12:
             $mesEdit = "Diciembre";
             break;
     }
    $ben->mes = $mesEdit;
    $ben->anio = $anio;

    $resultados = $this->obtenerBeneficiosPorMes($id_casino,$id_tipo_moneda,$anio,$mes);

    $ajustes = array();
    foreach ($resultados as $resultado){//resultado:
      $res = new \stdClass();

      $año = $resultado->fecha[0].$resultado->fecha[1].$resultado->fecha[2].$resultado->fecha[3];
      $mes = $resultado->fecha[5].$resultado->fecha[6];
      $dia = $resultado->fecha[8].$resultado->fecha[9];
      $res->fecha = $dia."-".$mes."-".$año;

      $res->bcalculado = number_format($resultado->beneficio_calculado, 2, ",", ".");
      $res->bimportado = number_format($resultado->beneficio, 2, ",", ".");
      $res->dif = number_format($resultado->diferencia, 2, ",", ".");
      $ajustes[] = $res;
    }

    $view = View::make('planillaBeneficios',compact('ajustes','ben'));

    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();

    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }

}
