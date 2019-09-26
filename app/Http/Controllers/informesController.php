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
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class informesController extends Controller
{

  private static $atributos = ['nro_admin' => 'Número de administración',
                               'id_casino' => 'ID de Casino'];

    /*
      CONTROLADOR ENCARGADO DE OBTENER DATOS
      PARA PANTALLAS DE INFORMES
    */

  public function obtenerMes($mes_num){ // @params $mes_numer integer. Devuleve el mes correspondiente a un entero . @return String
    switch ($mes_num) {
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
    return $mesEdit;
  }

  public function generarPlanilla($year,$mes,$id_casino,$tipo_moneda){

    $condicion = [['beneficio.id_casino','=',$id_casino],['beneficio.id_tipo_moneda','=',$tipo_moneda]];

    $resultados = DB::table('beneficio')->select('fecha','cantidad_maquinas','coinin','coinout','jackpot','valor','promedio_por_maquina','porcentaje_devolucion')
                                        ->where($condicion)
                                        ->whereYear('fecha','=',$year)
                                        ->whereMonth('fecha','=',$mes)
                                        ->orderBy('fecha','asc')
                                        ->get();

    $devuelveSumas = DB::table('beneficio')->select(DB::raw('SUM(coinin) as total_apostado'),
                                                    DB::raw('SUM(coinout) as total_premios'),
                                                    DB::raw('SUM(jackpot) as total_pmayores'),
                                                    DB::raw('SUM(valor) as total_beneficio'),
                                                    DB::raw('ROUND(AVG(promedio_por_maquina),2) as total_promedio'),
                                                    DB::raw('ROUND(AVG(porcentaje_devolucion),2) as total_devolucion'),
                                                    'casino.nombre as nombreCasino','tipo_moneda.descripcion as moneda')
                                           ->join('casino','casino.id_casino','=','beneficio.id_casino')
                                           ->join('tipo_moneda','tipo_moneda.id_tipo_moneda','=','beneficio.id_tipo_moneda')
                                           ->where($condicion)
                                           ->whereYear('fecha','=',$year)
                                           ->whereMonth('fecha','=',$mes)
                                           ->groupBy('casino.nombre','tipo_moneda.descripcion')->first();
                                          //  dd($devuelveSumas);

   $mesEdit = $this->obtenerMes($mes);

    $ajustes = $this->generarAjustes($resultados,$tipo_moneda);

    $sum= $this->generarSuma($devuelveSumas,$tipo_moneda,$mesEdit);
   
    
    
    if ($id_casino==3 && $tipo_moneda==2 ){
      $sum->totalBeneficioPesos= end($ajustes)->beneficioPesosTotal;
      $view = View::make('planillaInformesMTMdolar',compact('ajustes','sum'));
    }else{
      $view = View::make('planillaInformesMTM',compact('ajustes','sum'));
    }
    

    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();

    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));

  }

  private function generarAjustes($resultados,$id_moneda){
    if ($id_moneda!=2){
      foreach($resultados as $resultado){
        $res = new \stdClass();
        $año = $resultado->fecha[0].$resultado->fecha[1].$resultado->fecha[2].$resultado->fecha[3];
        $mes = $resultado->fecha[5].$resultado->fecha[6];
        $dia = $resultado->fecha[8].$resultado->fecha[9];
        $res->fecha = $dia."-".$mes."-".$año;
        $res->maq = $resultado->cantidad_maquinas;
        $res->apostado = number_format($resultado->coinin, 2, ",", ".");
        $res->premios = number_format($resultado->coinout, 2, ",", ".");
        $res->pmayores = number_format($resultado->jackpot, 2, ",", ".");
        $res->beneficio = number_format($resultado->valor, 2, ",", ".");
        $res->prom = $resultado->promedio_por_maquina;
        $res->dev = $resultado->porcentaje_devolucion;
        $ajustes[] = $res;
      };
      return $ajustes;
    }

    
    return $this->generarAjusteCotizado($resultados);
    
  }


  private function generarAjusteCotizado($resultados){
    $beneficioPesosTotal=0;
    //trabajar el caso de rosasrio
    foreach($resultados as $resultado){
      $res = new \stdClass();
      $cotizacion=Cotizacion::Find($resultado->fecha);
      
      $año = $resultado->fecha[0].$resultado->fecha[1].$resultado->fecha[2].$resultado->fecha[3];
      $mes = $resultado->fecha[5].$resultado->fecha[6];
      $dia = $resultado->fecha[8].$resultado->fecha[9];
      $res->fecha = $dia."-".$mes."-".$año;
      $res->maq = $resultado->cantidad_maquinas;
      $res->apostado = number_format($resultado->coinin, 2, ",", ".");
      $res->premios = number_format($resultado->coinout, 2, ",", ".");
      $res->pmayores = number_format($resultado->jackpot, 2, ",", ".");
      $res->beneficioDolares = number_format($resultado->valor, 2, ",", ".");

      $res->prom = $resultado->promedio_por_maquina;
      $res->dev = $resultado->porcentaje_devolucion;

      if (!$cotizacion){
        $res->cotizacion="-";
        $res->beneficioPesos="-";
        $res->beneficioPesosTotal=number_format($beneficioPesosTotal, 2, ",", ".");;
      }else{
        $res->cotizacion=number_format($cotizacion->valor, 3, ",", ".");
        $valorConv=$resultado->valor * $cotizacion->valor;
        $res->beneficioPesos=number_format($valorConv, 2, ",", ".");
        $beneficioPesosTotal=$beneficioPesosTotal + $valorConv;
        $res->beneficioPesosTotal= number_format($beneficioPesosTotal, 2, ",", ".");
      }
      $ajustes[] = $res;
    };
    return $ajustes;
  }

  
  private function generarSuma($devuelveSumas,$id_moneda,$mesEdit){
    if ($id_moneda!=2){
      $sum = new \stdClass();
      $sum->totalApostado = number_format($devuelveSumas->total_apostado, 2, ",", ".");
      $sum->totalPremios = number_format($devuelveSumas->total_premios, 2, ",", ".");
      $sum->totalPmayores = number_format($devuelveSumas->total_pmayores, 2, ",", ".");
      $sum->totalBeneficio = number_format($devuelveSumas->total_beneficio, 2, ",", ".");
      $sum->totalProm = $devuelveSumas->total_promedio;
      $sum->totalDev = $devuelveSumas->total_devolucion;
      $sum->mes = $mesEdit;
      $sum->casino = $devuelveSumas->nombreCasino;
  
      if($devuelveSumas->moneda == 'ARS'){
            $devuelveSumas->moneda = '$';
            }
            else{
              $devuelveSumas->moneda = 'US$';
      }
  
      $sum->tipoMoneda = $devuelveSumas->moneda;
      return $sum;
    }

    return $this->generarSumaCotizado($devuelveSumas,$id_moneda,$mesEdit);
    
    

  }

  private function generarSumaCotizado($devuelveSumas,$id_casino,$mesEdit){
    // TODO remplazar valores debido al copiar petgar
    $sum = new \stdClass();
    $sum->totalApostado = number_format($devuelveSumas->total_apostado, 2, ",", ".");
    $sum->totalPremios = number_format($devuelveSumas->total_premios, 2, ",", ".");
    $sum->totalPmayores = number_format($devuelveSumas->total_pmayores, 2, ",", ".");
    $sum->totalBeneficioDolares = number_format($devuelveSumas->total_beneficio, 2, ",", ".");
    $sum->totalProm = $devuelveSumas->total_promedio;
    $sum->totalDev = $devuelveSumas->total_devolucion;
    $sum->mes = $mesEdit;
    $sum->casino = $devuelveSumas->nombreCasino;


    if($devuelveSumas->moneda == 'ARS'){
          $devuelveSumas->moneda = '$';
          }
          else{
            $devuelveSumas->moneda = 'US$';
    }

    $sum->tipoMoneda = $devuelveSumas->moneda;

    return $sum;
  }

  public function obtenerUltimosBeneficiosPorCasino(){

      $beneficios_mel = DB::table('beneficio_mensual')->where([['id_casino',1],['id_actividad',1]])->orderBy('anio_mes','desc')->get();
      $beneficios_sfe = DB::table('beneficio_mensual')->where([['id_casino',2],['id_actividad',1]])->orderBy('anio_mes','desc')->get();
      $beneficios_ros = DB::table('beneficio_mensual')->where([['id_casino',3],['id_actividad',1]])->orderBy('anio_mes','desc')->get();
                           //->join('tipo_moneda','beneficio_mensual.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda')->get();

      $ajustesSF = array();

      foreach($beneficios_sfe as $resultado){
        $resSF = new \stdClass();
        $año = $resultado->anio_mes[0].$resultado->anio_mes[1].$resultado->anio_mes[2].$resultado->anio_mes[3];
        $mes = $resultado->anio_mes[5].$resultado->anio_mes[6];
        $casino = $resultado->id_casino;
        $tipoMoneda = $resultado->id_tipo_moneda;
        $verifica = $this->verficaCarga($año,$mes,$casino,$tipoMoneda);

        $mesEdit = $this->obtenerMes($mes);

        $resSF->anio_mes = $mesEdit." ".$año;
        $resSF->casino = $casino;
        $resSF->anio = $año;
        $resSF->mes = $mes;
        $resSF->moneda = $tipoMoneda;
        $resSF->estado = $verifica;
        $ajustesSF[] = $resSF;
      };

      $ajustesMEL = array();
      foreach($beneficios_mel as $resultado){
        $resML = new \stdClass();
        $año = $resultado->anio_mes[0].$resultado->anio_mes[1].$resultado->anio_mes[2].$resultado->anio_mes[3];
        $mes = $resultado->anio_mes[5].$resultado->anio_mes[6];
        $casino = $resultado->id_casino;
        $tipoMoneda = $resultado->id_tipo_moneda;
        $verifica = $this->verficaCarga($año,$mes,$casino,$tipoMoneda);

        $mesEdit = $this->obtenerMes($mes);

        $resML->anio_mes = $mesEdit." ".$año;
        $resML->casino = $casino;
        $resML->anio = $año;
        $resML->mes = $mes;
        $resML->moneda = $tipoMoneda;
        $resML->estado = $verifica;
        $ajustesML[] = $resML;
      };

      $ajustesROS = array();
      foreach($beneficios_ros as $resultado){
        $resROS = new \stdClass();
        $año = $resultado->anio_mes[0].$resultado->anio_mes[1].$resultado->anio_mes[2].$resultado->anio_mes[3];
        $mes = $resultado->anio_mes[5].$resultado->anio_mes[6];
        $casino = $resultado->id_casino;
        $tipoMoneda = $resultado->id_tipo_moneda;
        $verifica = $this->verficaCarga($año,$mes,$casino,$tipoMoneda);

        $mesEdit = $this->obtenerMes($mes);

        $resROS->anio_mes = $mesEdit." ".$año;
        $resROS->casino = $casino;
        $resROS->anio = $año;
        $resROS->mes = $mes;
        $resROS->moneda = $tipoMoneda;
        if($resultado->id_tipo_moneda == '1'){
              $resROS->moneda = '$';
              }
              else{
                $resROS->moneda = 'U$S';
        }
        $resROS->id_tipo_moneda=$resultado->id_tipo_moneda;
        $resROS->estado = $verifica;
        $ajustesROS[] = $resROS;
      };
      UsuarioController::getInstancia()->agregarSeccionReciente('Informes MTM' ,'informesMTM');

      return view('seccionInformesMTM',['beneficios_mel' => $ajustesML,
                      'beneficios_sfe' => $ajustesSF,
                      'beneficios_ros' => $ajustesROS]);
  }

  public function verficaCarga($year,$mes,$id_casino,$tipo_moneda){

      $condicion = [['beneficio.id_casino','=',$id_casino],['beneficio.id_tipo_moneda','=',$tipo_moneda]];

      $devuelveSumas = DB::table('beneficio')->select(DB::raw('SUM(coinin) as total_apostado'),
                                                      DB::raw('SUM(coinout) as total_premios'),
                                                      DB::raw('SUM(jackpot) as total_pmayores'),
                                                      DB::raw('SUM(valor) as total_beneficio'),
                                                      DB::raw('ROUND(AVG(promedio_por_maquina),2) as total_promedio'),
                                                      DB::raw('ROUND(AVG(porcentaje_devolucion),2) as total_devolucion'),
                                                      'casino.nombre as nombreCasino','tipo_moneda.descripcion as moneda')
                                             ->join('casino','casino.id_casino','=','beneficio.id_casino')
                                             ->join('tipo_moneda','tipo_moneda.id_tipo_moneda','=','beneficio.id_tipo_moneda')
                                             ->where($condicion)
                                             ->whereYear('fecha','=',$year)
                                             ->whereMonth('fecha','=',$mes)
                                             ->groupBy('casino.nombre','tipo_moneda.descripcion')->first();

      if($devuelveSumas == null){
        return 0;
      }
      else{
        return 1;
      }

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
    // if(is_numeric($id_casino)){
    //   $pdo = DB::connection('mysql')->getPdo();
    //   $string_query = sprintf("SELECT count(*) as cantidad from (SELECT isla.id_isla FROM maquina join isla on isla.id_isla = maquina.id_isla where isla.id_sector is null and maquina.id_casino =%d GROUP by isla.id_isla) as sub_tabla" , $id_casino );
    //   $resultados = $pdo->query($string_query);
    // }
    // foreach ($resultados as $resultado) { //siempre devuelve un solo resultado, ya que es un count(*)
    //   $islas_no_asignadas = $resultado;
    // }
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
      //$aux = DB::table('maquina')->select(DB::raw('count(maquina.id_maquina) as cantidad'))->join('isla' , 'maquina.id_isla' , '=' , 'isla.id_isla' )->where([['maquina.id_casino' , $casino->id_casino] , ['isla.id_sector' , $sector->id_sector]])->first();
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
    
    //Hay logs repetidos con ids distintos por algun motivo...
    //Los agrupamos, para eso necesitamos cada campo, menos el id_log_maquina
    //Antes se hacia asi y  retornaba duplicados! no cambiar 
    //Sin saber esto
    /*$logs = LogMaquina::join('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=','log_maquina.id_tipo_movimiento')
    ->where('id_maquina' , $id_maquina)->orderBy('fecha', 'desc')->get();*/
    
    $columnas_str = "";
    $columnas = Schema::getColumnListing('log_maquina');
    foreach($columnas as $col){
      if($col != "id_log_maquina"){
        $columnas_str .= ", l.".$col;
      }
    }
    $columnas = Schema::getColumnListing('tipo_movimiento');
    foreach($columnas as $col){
      $columnas_str .= ", t.".$col;
    }

    $query="SELECT 
    GROUP_CONCAT(DISTINCT(l.id_log_maquina) separator '/') as ids_logs_maquinas
    "
    .
    $columnas_str
    .
    "
    from log_maquina l
    join tipo_movimiento t on (l.id_tipo_movimiento = t.id_tipo_movimiento)
    where l.id_maquina = :id_maquina
    GROUP BY
    " 
    .
    substr($columnas_str,1)//saco la coma
    ."
    order by l.fecha desc";
    
    //dump($query);
    
    $parametros = ['id_maquina' => $id_maquina];
    $logs = DB::select(DB::raw($query),$parametros);
            
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
    ->where('relevamiento.fecha_carga','<>',$fechax)//$fechax->year().'-'.$fechax->month().'-'.$fechax->day())
    ->orderBy('relevamiento.fecha_carga','desc')
    ->take(5)->get();

    return ['arreglo' => array_reverse($arreglo),
            'datos' => array_reverse($datos),
            'nro_admin' => $maquina->nro_admin  ,
            'marca' => $maquina->marca,
            'casino' => $maquina->casino->nombre,
            'isla' => ['nro_isla' =>  $maquina->isla->nro_isla , 'codigo' => $maquina->isla->codigo],
            'sector' => $sector,
            'juego' => $maquina->juego_activo->nombre_juego,
            'producido' => $suma,
            'movimientos' => $logs,
            'denominacion_juego' => $maquina->denominacion_juego,
            'porcentaje_devolucion' => $maquina->porcentaje_devolucion,
            'relevamientos' => $detalles_5,
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

}
