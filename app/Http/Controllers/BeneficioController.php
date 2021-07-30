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
use App\Casino;

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

  //@BUG: Que pasa si hay mas de 1 ajuste beneficio?? (ver comentario mas abajo)
  //@TODO: convertir ajuste_beneficio.valor y beneficio.valor a DECIMAL(15,2) para evitar castear tanto
  private static $view_diferencia_dia = "CREATE OR REPLACE VIEW v_diferencia_dia AS
  SELECT b.id_beneficio,b.fecha,b.id_casino,b.id_tipo_moneda,p.id_producido,
         CAST(b.valor AS DECIMAL(15,2)) as beneficio,
         CAST((IFNULL(p.valor,0) + IFNULL(ab.valor,0)) AS DECIMAL(15,2)) as beneficio_calculado,
         CAST((IFNULL(p.valor,0) + IFNULL(ab.valor,0) - b.valor) AS DECIMAL(15,2)) AS diferencia
  FROM beneficio as b
  LEFT JOIN producido as p on (p.fecha = b.fecha AND p.id_casino = b.id_casino AND p.id_tipo_moneda = b.id_tipo_moneda)
  LEFT JOIN ajuste_beneficio as ab ON ab.id_beneficio = b.id_beneficio";

  private static $view_diferencia_mes = "CREATE OR REPLACE VIEW v_diferencia_mes AS
  SELECT b.id_casino,b.id_tipo_moneda,YEAR(b.fecha) AS anio,MONTH(b.fecha) AS mes,
         IFNULL(SUM(ROUND(dd.diferencia,2) <> 0.00),0) AS diferencias_mes
  FROM beneficio as b
  LEFT JOIN v_diferencia_dia as dd on (dd.id_beneficio = b.id_beneficio)
  GROUP BY b.id_casino,b.id_tipo_moneda,YEAR(b.fecha),MONTH(b.fecha)
  ORDER BY NULL";

  public static function initViews(){
    DB::beginTransaction();
    try{
      DB::statement(self::$view_diferencia_dia);
      DB::statement(self::$view_diferencia_mes);
    }
    catch(\Exception $e){
      DB::rollback();
      throw $e;
    } 
  } 

  public function eliminarBeneficios($id_casino,$id_tipo_moneda,$anio,$mes){//@TODO: validar acceso a casinos del usuario
    $bens = Beneficio::where([['id_casino','=',$id_casino],['id_tipo_moneda','=',$id_tipo_moneda]])
    ->whereYear('fecha',$anio)->whereMonth('fecha',$mes)->get();
    DB::beginTransaction();
    try{
      foreach($bens as $b){
        $this->eliminarBeneficio($b->id_beneficio,false);
      }
    }
    catch(Exception $e){
      DB::rollBack();
      throw $e;
    }
    DB::commit();
    return 1;
  }

  public function eliminarBeneficio($id_beneficio,$validar = true){
    if($validar) Validator::make(['id_beneficio' => $id_beneficio]
    ,['id_beneficio' => 'required|exists:beneficio,id_beneficio']
    , array(), self::$atributos)->sometimes('id_beneficio','exists:beneficio,id_beneficio',function($input){
      $ben = Beneficio::find($input['id_beneficio']);
      return !$ben->validado;
    })->validate();

    DB::transaction(function() use ($id_beneficio){
      $ben = Beneficio::find($id_beneficio);
      $ab = $ben->ajuste_beneficio;
      if(!is_null($ab)) $ab->delete();
      $ben->delete();
    });
  }

  public function buscarTodo(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Beneficios' ,'beneficios');
    return view('seccionBeneficios',['casinos' => $usuario->casinos,'tipos_moneda' => TipoMoneda::all()]);
  }

  public function buscarBeneficios(Request $request){
    $casinos = [];
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    foreach($usuario->casinos as $c) $casinos[] = $c->id_casino;

    $reglas = [];
    if(!empty($request->id_casino))      $reglas[] = ['vdm.id_casino','=',$request->id_casino];
    if(!empty($request->id_tipo_moneda)) $reglas[] = ['vdm.id_tipo_moneda','=',$request->id_tipo_moneda];
    if(!empty($request->fecha_desde)){
      $f = explode('-',$request->fecha_desde);
      $reglas[] = ['vdm.anio','>=',$f[0]];
      $reglas[] = ['vdm.mes','>=',$f[1]];
    }
    if(!empty($request->fecha_hasta)){
      $f = explode('-',$request->fecha_hasta);
      $reglas[] = ['vdm.anio','<=',$f[0]];
      $reglas[] = ['vdm.mes','<=',$f[1]];
    }

    self::initViews();
    return DB::table('v_diferencia_mes as vdm')
    ->select('tm.descripcion as tipo_moneda','c.nombre as casino','vdm.*','bm.id_beneficio_mensual')
    ->join('casino as c','c.id_casino','=','vdm.id_casino')
    ->join('tipo_moneda as tm','tm.id_tipo_moneda','=','vdm.id_tipo_moneda')
    ->leftJoin('beneficio_mensual as bm',function($j){
      return $j->on('bm.id_casino','=','vdm.id_casino')->on('bm.id_tipo_moneda','=','vdm.id_tipo_moneda')
      ->on('bm.anio_mes','=',DB::raw('MAKEDATE(vdm.anio,vdm.mes)'))->where('bm.id_actividad','=',1);
    })
    ->whereIn('vdm.id_casino',$casinos)->where($reglas)
    ->orderBy(DB::raw('MAKEDATE(vdm.anio,vdm.mes)'),'desc')->paginate($request->page_size);
  }

  public function obtenerBeneficiosParaValidar(Request $request){
    $resultados = $this->obtenerBeneficiosPorMes($request->id_casino,$request->id_tipo_moneda,$request->anio,$request->mes);

    return ['resultados' => $resultados];
  }

  public function ajustarBeneficio(Request $request){
    Validator::make($request->all(), [
      'valor' => ['nullable','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
      'id_beneficio' => 'required|exists:beneficio,id_beneficio'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    // Aca antes generaba un ajuste nuevo por cada vez que tocaba ajustar... si hay algun bug en beneficios viejos es 
    // probablemente por eso. Octavio 2021-07-06

    $ab = Beneficio::find($request->id_beneficio)->ajuste_beneficio;
    if(is_null($ab)){
      $ab = new AjusteBeneficio;
      $ab->valor = 0;
      $ab->id_beneficio = $request->id_beneficio;
    }

    DB::transaction(function() use (&$ab,$request){
      $ab->valor = $request->valor;
      $ab->save();
    });

    return ['ajuste' => $ab];
  }

  public function validarBeneficios(Request $request){
    $validator = Validator::make($request->all(), [
      'beneficios_ajustados' => 'nullable',
      'beneficios_ajustados.*.id_beneficio' => 'required|exists:beneficio,id_beneficio',
      'beneficios_ajustados.*.observacion' => 'nullable|max:500'
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

    if($request->beneficios_ajustados != null){
      foreach($request->beneficios_ajustados as $beneficio_ajustado){
        $ben = Beneficio::find($beneficio_ajustado['id_beneficio']);
        if($ben != null){
          $fecha = $ben->fecha;
          $ben->observacion = $beneficio_ajustado['observacion'];

          $prod = Producido::where([['fecha',$ben->fecha],['id_casino',$ben->id_casino],['id_tipo_moneda',$ben->id_tipo_moneda]])->first();
          if($prod != null){
            $producido_calculado = $prod->beneficio_calculado; //calcula atributo en el producido sumandole el ajuste reciente
            $diff = $producido_calculado - $ben->valor;
            $diff_round = round($diff,2);
            if(!is_null($producido_calculado) && $diff_round == 0.00){
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
        $beneficio_mensual->anio_mes = ''.$anio.'-'.$mes.'-01';
        $beneficio_mensual->bruto = $acumulado;
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
      'beneficios_ajustados' => 'nullable',
      'beneficios_ajustados.*.id_beneficio' => 'required|exists:beneficio,id_beneficio',
      'beneficios_ajustados.*.observacion' => 'nullable|max:500'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    $errors = null;
    if($request->beneficios_ajustados != null){
      foreach($request->beneficios_ajustados as $beneficio_ajustado){
        $ben = Beneficio::find($beneficio_ajustado['id_beneficio']);
        if($ben != null){
          $fecha = $ben->fecha;
          $ben->observacion = $beneficio_ajustado['observacion'];

          $prod = Producido::where([['fecha',$ben->fecha],['id_casino',$ben->id_casino],['id_tipo_moneda',$ben->id_tipo_moneda]])->first();
          if($prod != null){
            $producido_calculado = $prod->beneficio_calculado; //calcula atributo en el producido

            if(!is_null($producido_calculado) && round($producido_calculado - $ben->valor,2) == 0){
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
        return response()->json($errors->toArray(), 422);
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
      $beneficio_mensual->bruto = $acumulado;
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
    $resultados =  DB::table('v_diferencia_dia')
    ->select('id_beneficio','fecha','id_producido','beneficio','beneficio_calculado','diferencia')
    ->where([['id_casino','=',$id_casino],['id_tipo_moneda','=',$id_tipo_moneda]])
    ->whereMonth('fecha',$mes)
    ->whereYear('fecha',$anio)
    ->orderBy('fecha','asc')->get();
    return $resultados;
  }
  
  public function generarPlanilla($id_casino,$id_tipo_moneda,$anio,$mes){
    $ben = new \stdClass();
    $ben->casino = Casino::find($id_casino)->nombre;
    $ben->moneda = TipoMoneda::find($id_tipo_moneda)->descripcion;
    if($ben->moneda == 'ARS') $ben->moneda = 'Pesos';
    else if($ben->moneda == 'USD') $ben->moneda = 'Dólares';

    $meses = ["0","Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
    $ben->mes = $mes;
    if(array_key_exists($mes,$meses)) $ben->mes = $meses[$mes];
    $ben->anio = $anio;

    $resultados = $this->obtenerBeneficiosPorMes($id_casino,$id_tipo_moneda,$anio,$mes);

    $ajustes = [];
    foreach ($resultados as $resultado){//resultado:
      $res = new \stdClass();
      $res->fecha      = implode('-',array_reverse(explode('-',$resultado->fecha)));
      $res->bcalculado = number_format($resultado->beneficio_calculado, 2, ",", ".");
      $res->bimportado = number_format($resultado->beneficio, 2, ",", ".");
      $res->dif        = number_format($resultado->diferencia, 2, ",", ".");
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
