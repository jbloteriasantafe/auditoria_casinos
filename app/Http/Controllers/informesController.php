<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\TipoMoneda;
use App\Casino;
use App\LogMaquina;
use App\Beneficio;
use View;
use Dompdf\Dompdf;
use App\Maquina;
use App\DetalleRelevamiento;
use App\EstadoMaquina;
use App\Cotizacion;
use App\Isla;
use App\TipoCausaNoToma;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\BeneficioController;

class informesController extends Controller
{
  private static $atributos = ['nro_admin' => 'Número de administración',
                               'id_casino' => 'ID de Casino'];

    /*
      CONTROLADOR ENCARGADO DE OBTENER DATOS
      PARA PANTALLAS DE INFORMES
    */

  public function generarPlanilla(int $anio,int $mes,int $id_casino,int $tipo_moneda){
    $condicion = [['b.id_casino','=',$id_casino],['b.id_tipo_moneda','=',$tipo_moneda],
                  [DB::raw('YEAR(b.fecha)'),'=',$anio],[DB::raw('MONTH(b.fecha)'),'=',$mes]];

    //@TODO: extender cotizacion a N monedas. Si agregas peso con convertivilidad 1 por defecto simplificaria bastante codigo
    $beneficios = DB::table('beneficio as b')
    ->select('b.fecha as fecha_iso',
      DB::raw('0 as cantidad_maquinas'),
      DB::raw('DATE_FORMAT(b.fecha,"%d-%m-%Y") as fecha'),
      DB::raw('FORMAT(b.coinin,2,"es_AR")  as apostado'),
      DB::raw('FORMAT(b.coinout,2,"es_AR") as premios'),
      DB::raw('FORMAT(b.jackpot,2,"es_AR") as pmayores'),
      DB::raw('FORMAT(b.valor,2,"es_AR")   as beneficio'),
      DB::raw('IF(cot.valor IS NULL,"-",FORMAT(cot.valor,3,"es_AR"))         as cotizacion'),//Para dolares
      DB::raw('IF(cot.valor IS NULL,"-",FORMAT(b.valor*cot.valor,2,"es_AR")) as beneficioPesos')//Para dolares
    )
    ->leftJoin('cotizacion as cot','cot.fecha','=','b.fecha')//No deberiamos usar la ultima cotizacion cargada si la de la fecha no esta?
    ->where($condicion)->orderBy('b.fecha','asc')->get();

    $condicion_p = [['p.id_casino','=',$id_casino],['p.id_tipo_moneda','=',$tipo_moneda],['dp.valor','<>',0]];

    foreach($beneficios as $b){
      //Esto en realidad es un limite inferior porque al momento de importar si falta la maquina en el sistema, se ignora la fila
      $maquinas = DB::table('producido as p')
      ->selectRaw('COUNT(distinct dp.id_maquina) as cantidad_maquinas')
      ->join('detalle_producido as dp','dp.id_producido','=','p.id_producido')
      ->where($condicion_p)->where('p.fecha','=',$b->fecha_iso)->groupBy("p.id_producido")->first();
      if($maquinas) $b->cantidad_maquinas = $maquinas->cantidad_maquinas;
    }

    $sum = DB::table('beneficio as b')->select('c.nombre as casino','tm.descripcion as tipoMoneda',
        DB::raw('0 as cantidad_maquinas'),
        DB::raw('FORMAT(SUM(b.coinin),2,"es_AR")  as totalApostado'),
        DB::raw('FORMAT(SUM(b.coinout),2,"es_AR") as totalPremios'),
        DB::raw('FORMAT(SUM(b.jackpot),2,"es_AR") as totalPmayores'),
        DB::raw('FORMAT(SUM(b.valor),2,"es_AR")   as totalBeneficio'),
        DB::raw('FORMAT(SUM(b.valor*IFNULL(cot.valor,0)),2,"es_AR") as totalBeneficioPesos')//Para dolares
    )
    ->join('casino as c','c.id_casino','=','b.id_casino')
    ->join('tipo_moneda as tm','tm.id_tipo_moneda','=','b.id_tipo_moneda')
    ->leftJoin('cotizacion as cot','cot.fecha','=','b.fecha')
    ->where($condicion)->groupBy('c.nombre','tm.descripcion')->first();

    $condicion_p = [['p.id_casino','=',$id_casino],['p.id_tipo_moneda','=',$tipo_moneda],['dp.valor','<>',0],
                    [DB::raw('YEAR(p.fecha)'),'=',$anio],[DB::raw('MONTH(p.fecha)'),'=',$mes]];

    //Esto en realidad es un limite inferior porque al momento de importar si falta la maquina en el sistema, se ignora la fila
    $maquinas = DB::table('producido as p')
    ->selectRaw('COUNT(distinct dp.id_maquina) as cantidad_maquinas')
    ->join('detalle_producido as dp','dp.id_producido','=','p.id_producido')
    ->where($condicion_p)->groupBy(DB::raw("'constant'"))->first();//Agrupo por una constante porque quiero contar todo
    if($maquinas) $sum->cantidad_maquinas = $maquinas->cantidad_maquinas;

    $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    if(array_key_exists($mes-1,$meses)) $mes = $meses[$mes-1];
    $sum->mes = $mes . '';//to String

    if($tipo_moneda == 2)      $sum->tipoMoneda = 'US$';
    else if($tipo_moneda == 1) $sum->tipoMoneda = '$';
    else return "Moneda no soportada";

    $desde_hasta = null;
    $view = View::make('planillaInformesMTM',compact('beneficios','sum','desde_hasta'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();

    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }

  private function generarPlanillaNroAdmins(int  $anio,int $mes,int $id_casino,int $tipo_moneda,array $nro_admins){
    $condicion = [['p.id_casino','=',$id_casino],['p.id_tipo_moneda','=',$tipo_moneda],
    [DB::raw('YEAR(p.fecha)'),'=',$anio],[DB::raw('MONTH(p.fecha)'),'=',$mes]];

    $suma_a = 'SUM(IF(p.apuesta IS NULL OR m.id_maquina IS NULL,NULL,IFNULL(dp.apuesta,0)))';
    $suma_p = 'SUM(IF(p.premio IS NULL OR m.id_maquina IS NULL,NULL,IFNULL(dp.premio,0)))';;
    $suma_v = 'SUM(IF(m.id_maquina IS NULL,0,IFNULL(dp.valor,0)))';
    $suma_cotizada = 'SUM(IF(m.id_maquina IS NULL,0,IFNULL(dp.valor,0)*IFNULL(cot.valor,0)))';

    $beneficios = DB::table('producido as p')
    ->select(
      DB::raw('COUNT(distinct m.id_maquina) as cantidad_maquinas'),
      DB::raw('DATE_FORMAT(p.fecha,"%d-%m-%Y") as fecha'),
      DB::raw('FORMAT('.$suma_a.',2,"es_AR") as apostado'),
      DB::raw('FORMAT('.$suma_p.',2,"es_AR") as premios'),
      DB::raw('"" as pmayores'),
      DB::raw('FORMAT('.$suma_v.',2,"es_AR") as beneficio'),
      DB::raw('IF(cot.valor IS NULL,"-",FORMAT(cot.valor,3,"es_AR")) as cotizacion'),//Para dolares
      DB::raw('IF(cot.valor IS NULL,"-",FORMAT('.$suma_cotizada.',2,"es_AR")) as beneficioPesos')//Para dolares
    )
    ->leftJoin('cotizacion as cot','cot.fecha','=','p.fecha')
    ->leftJoin('detalle_producido as dp',function($j){
      return $j->on('dp.id_producido','=','p.id_producido')->where('dp.valor','<>',0);
    })
    ->leftJoin('maquina as m',function($j) use ($nro_admins){
      $j->on('m.id_maquina','=','dp.id_maquina');
      if(is_null($nro_admins)) return;
      return $j->whereIn('m.nro_admin',$nro_admins);
    })
    ->where($condicion)->where('dp.valor','<>',0)->groupBy('p.id_producido')->orderBy('p.fecha','asc')->get();

    $sum = DB::table('producido as p')
    ->select('c.nombre as casino','tm.descripcion as tipoMoneda',
      DB::raw('COUNT(distinct m.id_maquina) as cantidad_maquinas'),
      DB::raw('FORMAT('.$suma_a.',2,"es_AR") as totalApostado'),
      DB::raw('FORMAT('.$suma_p.',2,"es_AR") as totalPremios'),
      DB::raw('"" as totalPmayores'),
      DB::raw('FORMAT('.$suma_v.',2,"es_AR") as totalBeneficio'),
      DB::raw('FORMAT('.$suma_cotizada.',2,"es_AR") as totalBeneficioPesos')//Para dolares
    )
    ->join('casino as c','c.id_casino','=','p.id_casino')
    ->join('tipo_moneda as tm','tm.id_tipo_moneda','=','p.id_tipo_moneda')
    ->leftJoin('cotizacion as cot','cot.fecha','=','p.fecha')
    ->leftJoin('detalle_producido as dp',function($j){
      return $j->on('dp.id_producido','=','p.id_producido')->where('dp.valor','<>',0);
    })
    ->leftJoin('maquina as m',function($j) use ($nro_admins){
      $j->on('m.id_maquina','=','dp.id_maquina');
      if(is_null($nro_admins)) return;
      return $j->whereIn('m.nro_admin',$nro_admins);
    })
    ->where($condicion)->groupBy('c.nombre','tm.descripcion')->first();

    $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    if(array_key_exists($mes-1,$meses)) $mes = $meses[$mes-1];
    $sum->mes = $mes . '';//to String

    if($tipo_moneda == 2)      $sum->tipoMoneda = 'US$';
    else if($tipo_moneda == 1) $sum->tipoMoneda = '$';
    else return "Moneda no soportada";

    //@WARNING: espera que nro_admins este ordenado ascendentemente
    $rangos = [];
    $current_min = +INF;
    foreach($nro_admins as $idx => $n){
      if($idx == 0){//Primera vez en el loop
        $current_min = $n;
        continue;
      }
      if($n == ($nro_admins[$idx-1]+1)){//Si es mas grande por 1, sigue estando OK el rango
        continue;
      }
      //Se rompio el rango
      $rangos[] = $current_min.'-'.$nro_admins[$idx-1];
      $current_min = $n;
    }
    $desde_hasta = implode(',',$rangos);
    $view = View::make('planillaInformesMTM',compact('beneficios','sum','desde_hasta'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();

    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }

  public function generarPlanillaMaquinas(int $anio,int $mes,int $id_casino,int $tipo_moneda,int $maqmenor,int $maqmayor){
    $reglas = [['id_casino','=',$id_casino]];
    if($maqmenor != -1) $reglas[] = ['nro_admin','>=',$maqmenor];
    if($maqmayor != -1) $reglas[] = ['nro_admin','<=',$maqmayor];
    $maqs = Maquina::where($reglas)->orderBy('nro_admin','asc')->pluck('nro_admin')->toArray();
    return $this->generarPlanillaNroAdmins($anio,$mes,$id_casino,$tipo_moneda,$maqs);
  }
  
  public function generarPlanillaIsla(int $anio,int $mes,int $id_casino,int $tipo_moneda,int $nro_isla){
    $maqs = Isla::where([['id_casino','=',$id_casino],['nro_isla','=',$nro_isla]])
    ->get()->first()->maquinas()->orderBy('nro_admin','asc')->pluck('nro_admin')->toArray();
    return $this->generarPlanillaNroAdmins($anio,$mes,$id_casino,$tipo_moneda,$maqs);
  }

  public function obtenerUltimosBeneficiosPorCasino(){
    BeneficioController::initViews();
    //@HACK @TODO: generalizar a N casinos y N monedas
    $beneficios = DB::table('v_diferencia_mes as vdm')
    ->select('vdm.*','bm.id_beneficio_mensual',DB::raw('"1" as estado'))
    ->join('casino as c','c.id_casino','=','vdm.id_casino')
    ->join('tipo_moneda as tm','tm.id_tipo_moneda','=','vdm.id_tipo_moneda')
    ->leftJoin('beneficio_mensual as bm',function($j){
      return $j->on('bm.id_casino','=','vdm.id_casino')->on('bm.id_tipo_moneda','=','vdm.id_tipo_moneda')
      ->on('bm.anio_mes','=',DB::raw('MAKEDATE(vdm.anio,vdm.mes)'))->where('bm.id_actividad','=',1);
    })->orderBy(DB::raw('MAKEDATE(vdm.anio,vdm.mes)'),'desc')->get();
    $beneficios_x_casino = [1 => [],2 => [],3 => []];
    foreach($beneficios as $b){
      $beneficios_x_casino[$b->id_casino][] = $b;
    }

    $beneficios_mensuales_sin_beneficios = DB::table('beneficio_mensual as bm')
    ->select('bm.*',DB::raw('"0" as estado'),DB::raw('YEAR(bm.anio_mes) as anio'),DB::raw('MONTH(bm.anio_mes) as mes'))
    ->leftJoin('beneficio as b',function($j){
      return $j->on('bm.id_casino','=','b.id_casino')->on('bm.id_tipo_moneda','=','b.id_tipo_moneda')
      ->on('bm.anio_mes','=',DB::raw('MAKEDATE(YEAR(b.fecha),MONTH(b.fecha))'));
    })
    ->where('bm.id_actividad',1)->whereNull('b.id_beneficio')->orderBy('bm.anio_mes','desc')->get();
    foreach($beneficios_mensuales_sin_beneficios as $b){
      $beneficios_x_casino[$b->id_casino][] = $b;
    }
    foreach($beneficios_x_casino as $bcasino){
      usort($bcasino,function($a,$b){
        if(intval($a->anio) > intval($b->anio)) return true;
        if(intval($a->mes)  > intval($b->mes))  return true;
        if($a->id_beneficio_mensual > $b->id_beneficio_mensual) return true;
        return false;
      });
    }

    UsuarioController::getInstancia()->agregarSeccionReciente('Informes MTM' ,'informesMTM');

    return view('seccionInformesMTM',['beneficios_mel' => $beneficios_x_casino[1],
                    'beneficios_sfe' => $beneficios_x_casino[2],
                    'beneficios_ros' => $beneficios_x_casino[3]]);
  }

  public function obtenerInformeEstadoParque(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = array();
    foreach($usuario->casinos as $casino){
      $casinos [] = $casino;
    }
    UsuarioController::getInstancia()->agregarSeccionReciente('Informe Estado de Parque','informeEstadoParque');

    return view('seccionInformeEstadoParque' , ['casinos' => $casinos]);
  }

  public function obtenerInformeEstadoParqueDeParque($id_casino){
    //funcion que devuelve cantidad de maquinas total del casino y a su vez maquinas separadas por sector . Tambien separadas en habilitadas y deshabilitadas
    $casino = Casino::find($id_casino);

    $estados_habilitados = EstadoMaquina::where('descripcion' , 'Ingreso')
                                          ->orWhere('descripcion' , 'Reingreso')
                                          ->orWhere('descripcion' , 'Eventualidad Observada')
                                          ->get();

    foreach ($estados_habilitados as $key => $estado) {
      $estados_habilitados[$key] = $estado->id_estado_maquina;
    }

    $cantidad = DB::table('maquina')->select(DB::raw('COUNT(id_maquina) as cantidad'))
    
                                              ->where('id_casino' , $casino->id_casino)
                                              ->whereNull('maquina.deleted_at')
                                              ->first();

    $cantidad_habilitadas = DB::table('maquina')->select(DB::raw('COUNT(id_maquina) as cantidad'))
                                                  ->where('id_casino' , $casino->id_casino)->whereIn('id_estado_maquina', $estados_habilitados)
                                                  ->whereNull('maquina.deleted_at')
                                                  ->first();
    $cantidad_deshabilitadas = $cantidad->cantidad - $cantidad_habilitadas->cantidad;
    $maquina_no_asignadas = DB::table('maquina')
                              ->select(DB::raw('count(*) as cantidad'))
                              ->where('maquina.id_casino' , $casino->id_casino)
                              ->whereNull('maquina.deleted_at')
                              ->whereNull('maquina.id_isla')
                              ->first();

    $islas=DB::table("isla")
                ->where("isla.id_casino","=",$id_casino)
                ->join("sector","isla.id_sector","=","sector.id_sector")
                ->whereNotNull("sector.deleted_at")
                ->whereNull("isla.deleted_at")
                ->get();
    $islas_no_asignadas =0;
    
    foreach($islas as $i){
      $isl=Isla::Find($i->id_isla);
      if ($isl->cantidad_maquinas>0){
        $islas_no_asignadas= $islas_no_asignadas+1;
      }
    }  
    
    $sectores = array();
    foreach ($casino->sectores as $sector) {
      $sectores[] =  ['id_sector' =>  $sector->id_sector, 'descripcion' => $sector->descripcion, 'cantidad' => $sector->cantidad_maquinas];
    }

    return ['casino' => $casino ,'sectores' => $sectores, 'totales' =>['total_casino' => $cantidad->cantidad,
                                                                      'total_no_asignadas' => $maquina_no_asignadas->cantidad,
                                                                      'islas_no_asignadas' => $islas_no_asignadas,
                                                                      'total_habilitadas'  => $cantidad_habilitadas->cantidad,
                                                                      'total_deshabilitadas' => $cantidad_deshabilitadas]
          ];
  }

  public function buscarTodoInformeContable(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = array();
    foreach($usuario->casinos as $casino){
      $casinos [] = $casino;
    }
    UsuarioController::getInstancia()->agregarSeccionReciente('Informe Contable MTM' , 'informeContableMTM');

    return view('contable_mtm', ['casinos' => $casinos]);
  }

  public function obtenerInformeContableAzar($id_casino){
        $nro_maquina = $this->obtenerMaquinaAlAzar($id_casino);
        $informe = $this->obtenerInformeContableDeMaquina($nro_maquina,$id_casino);
        return $informe;
  }

  public function obtenerInformeContableDeMaquina($id_maquina){
    //modficar para que tome ultimos dias con datos, no solo los ultimos dias
    Validator::make([
         'id_maquina' => $id_maquina,
       ],
       [
         'id_maquina' => 'required|exists:maquina,id_maquina' ,
       ] , array(), self::$atributos)->after(function ($validator){

    })->validate();

    $maquina = Maquina::find($id_maquina);
    $sector = isset($maquina->isla->sector) ? $sector = $maquina->isla->sector->descripcion : $sector = "-";
    $fecha= date('Y-m-d');//hoy
    //No tiene sentido mostrar el producido del dia de hoy porque siempre se carga
    //Con un delay de un dia, empezamos desde ayer.
    $fecha=date('Y-m-d' , strtotime($fecha . ' - 1 days')); 
    $fin = true;
    $i= 0;
    $suma = 0;
    $datos = $arreglo = array();
    
    //@HACK: Hay logs repetidos con ids distintos por algun motivo...
    //Los agrupamos, para eso necesitamos cada campo, menos el id_log_maquina
    //y retornaba duplicados! no cambiar sin saber esto
    
    $columnas_str = "";
    $columnas = array_filter(Schema::getColumnListing('log_maquina'),function($x){return $x != "id_log_maquina";});
    $columnas = array_map(function($x){return 'l.'.$x;},$columnas);
    $columnas_str = implode(',',$columnas);
    $columnas = Schema::getColumnListing('tipo_movimiento');
    $columnas = array_map(function($x){return 't.'.$x;},$columnas);
    $columnas_str = $columnas_str.','.implode(',',$columnas);
    $logs = DB::table('log_maquina as l')
    ->selectRaw("GROUP_CONCAT(DISTINCT(l.id_log_maquina) separator '/') as ids_logs_maquinas,".$columnas_str)
    ->join('tipo_movimiento as t','l.id_tipo_movimiento','=','t.id_tipo_movimiento')
    ->where('l.id_maquina','=',$id_maquina)
    ->groupBy(DB::raw($columnas_str))
    ->orderBy('l.fecha','desc')->get()->toArray();

    usort($logs,function($a,$b){
      //Comparo primero por fecha y si son iguales por el id mas chico.
      //Se simplificaria si tuviera hora minuto segundo...
      $fecha_a = strtotime($a->fecha);
      $fecha_b = strtotime($a->fecha);
      if($fecha_a < $fecha_b) return 1;
      else if($fecha_a > $fecha_b) return -1;

      $ids_a = explode('/',$a->ids_logs_maquinas);
      $ids_b = explode('/',$b->ids_logs_maquinas);
      $smallest_a = $ids_a[0];
      foreach($ids_a as $ida){
        if($ida < $smallest_a) $smallest_a = $ida;
      }
      foreach($ids_b as $idb){
        if($idb < $smallest_a) return -1;
      }
      return 1;
    });

    while($fin){
      $estado = $this->checkEstadoMaquina($fecha, $maquina->id_maquina);
      $aux= new \stdClass();
      $valor = 0;
      if($estado['estado_producido']['detalle']!= null) $valor = $estado['estado_producido']['detalle']->valor;
      $suma+= $valor;
      $datos[] = ['valor' => $valor, 'fecha' => strftime('%d %b %y' ,  strtotime($fecha))];
      $arreglo[] = $estado;//suma total
      $fecha=date('Y-m-d' , strtotime($fecha . ' - 1 days'));

      //condiciones finalizacion
      $i++;
      if($i == 15) $fin = false;
    }
    $fechax = Carbon::now()->format('Y-m-d');
    $detalles_5 = DB::table('detalle_relevamiento')
    ->select('detalle_relevamiento.*','maquina.nro_admin','relevamiento.*')
    ->join('maquina','maquina.id_maquina','=','detalle_relevamiento.id_maquina')
    ->join('relevamiento','relevamiento.id_relevamiento','=','detalle_relevamiento.id_relevamiento')
    ->where('maquina.id_maquina','=',$id_maquina)
    ->where('relevamiento.fecha_carga','<>',$fechax)
    ->orderBy('relevamiento.fecha_carga','desc')
    ->take(5)->get();

    $juego = $maquina->juego_activo;
    return ['arreglo' => array_reverse($arreglo),
            'datos' => array_reverse($datos),
            'nro_admin' => $maquina->nro_admin  ,
            'marca' => $maquina->marca,
            'casino' => $maquina->casino->nombre,
            'moneda' => $maquina->tipoMoneda,
            'isla' => 
            [
              'nro_isla' =>  (is_null($maquina->isla))? null: $maquina->isla->nro_isla , 
              'codigo' => (is_null($maquina->isla))? null: $maquina->isla->codigo
            ],
            'sector' => $sector,
            'juego' => $juego->nombre_juego,
            'producido' => $suma,
            'movimientos' => $logs,
            'denominacion_juego' => $maquina->obtenerDenominacion(),
            'porcentaje_devolucion' => $maquina->obtenerPorcentajeDevolucion(),
            'relevamientos' => $detalles_5,
            'tipos_causa_no_toma' => TipoCausaNoToma::all()
            ];
  }

  public function checkEstadoMaquina($fecha , $id_maquina){
      //checkeo el estado de la maquina para un dia determinado
      //CERRADO(PRODUCIDO AJUSTADO/VALIDADO), VALIDADO(RELEVACION VALIDADA) Y RELEVADO(TUVO RELEVAMIENTO PARA DICHO DIA)
      $estado_contadores = ContadorController::getInstancia()->estaCerradoMaquina($fecha,$id_maquina);

      $estado_producido = ProducidoController::getInstancia()->estaValidadoMaquina($fecha,$id_maquina);

      $estado_relevamiento = RelevamientoController::getInstancia()->estaRelevadoMaquina($fecha,$id_maquina);

      return ['estado_contadores' => $estado_contadores,
              'estado_relevamiento' => $estado_relevamiento,
              'estado_producido' => $estado_producido];
      //contador SE MUESTRA POR PANTALLA YA QUE NO SIEMPRE EXISTE RELEVAMIENTO PARA ESA MAQUINA EN ESA FECHA
  }

  public function obtenerMaquinaAlAzar($id_casino){
    $resultado = DB::table('maquina')->select('nro_admin')
                          ->where('maquina.id_casino' , $id_casino)
                          ->inRandomOrder()
                          ->first();
    return $resultados['nro_admin'];
  }

  //BUSCA TODA LA INFORMACION PARA CARGAR MODAL
  public function mostrarEstadisticasNoToma($id_mtm){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
    $casinos=array();
    foreach($usuario['usuario']->casinos as $casino){
      $casinos[]=$casino;
    }
    $mtm = Maquina::find($id_mtm);
    // $casinos = Casino::all();
    return view('informe_no_toma', ['casinos' => $casinos, 'nro_admin' => $mtm->nro_admin, 'casino' => $mtm->id_casino, 'nombre'=> $mtm->casino->nombre]);
  }

  public function mostrarEstadisticasNoTomaGenerico(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
    $casinos=array();
    foreach($usuario['usuario']->casinos as $casino){
      $casinos[]=$casino;
    }
    //$mtm = Maquina::find($id_mtm);
    // $casinos = Casino::all();
    return view('informe_no_toma', ['casinos' => $casinos, 'nro_admin' => null, 'casino' =>null, 'nombre'=> null]);
  }

  public function obtenerEstadisticasNoToma($id){
    $maquina = Maquina::find($id);
    $aux= new \stdClass();
    $aux->nro_admin=$maquina->nro_admin;
    $aux->marca = $maquina->marca;
    $aux->juego = $maquina->juego_activo->nombre_juego;
    $aux->casino = $maquina->casino->nombre;
    $aux->sector = $maquina->isla->sector->descripcion;
    $aux->nro_isla = $maquina->isla->nro_isla;
    $aux->codigo = $maquina->isla->codigo;
    $aux->denominacion = $maquina->denominacion;
    $aux->porcentaje_devolucion = $maquina->porcentaje_devolucion;
    $aux->id_casino=$maquina->id_casino;


    $resultados = DB::table('detalle_relevamiento')->select('relevamiento.fecha','tipo_causa_no_toma.descripcion','tipo_causa_no_toma.codigo')
                                        ->join('relevamiento','relevamiento.id_relevamiento','=','detalle_relevamiento.id_relevamiento')
                                        ->join('sector','sector.id_sector','=','relevamiento.id_sector')
                                        ->join('casino','casino.id_casino','=','sector.id_casino')
                                        ->join('tipo_causa_no_toma','detalle_relevamiento.id_tipo_causa_no_toma','=','tipo_causa_no_toma.id_tipo_causa_no_toma')
                                        ->where([['id_maquina','=',$id],['backup','=',0]])
                                        ->whereNotNull('detalle_relevamiento.id_tipo_causa_no_toma')
                                        ->orderBy('relevamiento.fecha', 'DESC')
                                        ->get();

    return ['maquina' => $aux , 'resultados' => $resultados];

  }

  public function mostrarInformeSector(){
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Informe Sector' ,'informeSector');
    return view('seccionInformesSectores',
    [
      'es_admin' => $user->es_administrador || $user->es_superusuario,
      'estados' => EstadoMaquina::all()
    ]);
  }
  public function obtenerMTMs(){
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $casinos = $user->casinos;
    $sectores = [];
    foreach($casinos as $c){
      foreach($c->sectores as $s){
          $sectores[]=$s;
      }
      $sin_asignar = new \stdClass();
      //Le asigno como id, el negativo del casino
      //Como es uno solo por casino, esta garantizado a que sea distinto
      $sin_asignar->id_sector = "SIN_ASIGNAR_".$c->id_casino;
      $sin_asignar->descripcion = "SIN ASIGNAR";
      $sin_asignar->id_casino = $c->id_casino;
      $sin_asignar->cantidad_maquinas = null;
      $sin_asignar->deleted_at = null;
      $sectores[]=$sin_asignar;
    }
    
    $islas = [];
    foreach($casinos as $c){
      $sin_asignar = new \stdClass();
      //Creo una isla especial para asignar las maquinas sin isla.
      $sin_asignar->id_isla = "SIN_ISLA_".$c->id_casino;
      $sin_asignar->nro_isla = "SIN ISLA";
      $sin_asignar->codigo = "SIN ISLA";
      $sin_asignar->cantidad_maquinas = null;
      $sin_asignar->id_casino = $c->id_casino;
      $sin_asignar->id_sector = "SIN_ASIGNAR_".$c->id_casino;
      $sin_asignar->deleted_at = null;
      $islas[] = $sin_asignar;
      foreach($c->islas as $i){
        $aux = $i->toArray();
        //Si no tiene sector, lo enlazo con SIN ASIGNAR
        if(is_null($i->id_sector)) $i->id_sector = "SIN_ASIGNAR_".$c->id_casino;
        $islas[] = $i;
      }
    }
    $expresion_estado = 'IFNULL(estado.descripcion,"") as estado_descripcion,IF(m.deleted_at is NULL,"0","1") as borrada';
    $maquinas = DB::table('maquina as m')
    ->selectRaw('m.*,i.id_sector,'.$expresion_estado)
    ->leftJoin('estado_maquina as estado','m.id_estado_maquina','=','estado.id_estado_maquina')
    ->join('isla as i','m.id_isla','=','i.id_isla')
    ->whereNotNull('i.id_sector')
    ->orderBy('m.nro_admin','asc')->get()->toArray();

    //Necesito sacar la columna de isla de maquina, la otra que queda era listar todas a pata.
    $columnas_str = "";
    $columnas = Schema::getColumnListing('maquina');
    foreach($columnas as $col){
      if($col != "id_isla"){
        $columnas_str .= ", m.".$col;
      }
    }
    $m_sin_isla = DB::table('maquina as m')
    ->selectRaw('CONCAT("SIN_ASIGNAR_",m.id_casino) as id_sector,CONCAT("SIN_ISLA_",m.id_casino) as id_isla,'.$expresion_estado.$columnas_str)
    ->leftJoin('estado_maquina as estado','m.id_estado_maquina','=','estado.id_estado_maquina')
    ->whereNull('m.id_isla')
    ->orderBy('m.nro_admin','asc')->get()->toArray();

    $m_sin_sector = DB::table('maquina as m')
    ->selectRaw('CONCAT("SIN_ASIGNAR_",m.id_casino) as id_sector,m.*,'.$expresion_estado)
    ->leftJoin('estado_maquina as estado','m.id_estado_maquina','=','estado.id_estado_maquina')
    ->join('isla as i','m.id_isla','=','i.id_isla')
    ->whereNull('i.id_sector')
    ->orderBy('m.nro_admin','asc')->get()->toArray();

    $todas = array_merge($maquinas,$m_sin_isla,$m_sin_sector);

    $keylist = ['id_casino','nombre'];
    $filter = function($v,$idx) use (&$keylist){
      $ret = [];
      foreach($keylist as $k){
        $ret[$k] = $v->{$k};
      }
      return $ret;
    };
    $casinos = $casinos->map($filter);
    $keylist = ['id_sector','descripcion','id_casino'];
    $sectores = collect($sectores)->map($filter);//->only(['id_sector','descripcion','id_casino'])->all();
    $keylist = ['id_isla','nro_isla','id_sector','id_casino'];
    $islas = collect($islas)->map($filter);
    $keylist = ['id_maquina','nro_admin','id_estado_maquina','estado_descripcion','id_isla','id_sector','id_casino','borrada'];
    $todas = collect($todas)->map($filter);
    return [
      'casinos' => $casinos, 
      'sectores' => $sectores, 
      'islas' => $islas, 
      'maquinas' => $todas
    ];
  }
}
