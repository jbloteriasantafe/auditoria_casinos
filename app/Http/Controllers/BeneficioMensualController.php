<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\BeneficioMensual;
use App\Casino;
/*
  LOGICA DE ESTIDISTCAS CUANDO SE OBTIENE BENEFICIO.
  "TABLERO CONTROL"
*/

class BeneficioMensualController extends Controller
{
  private static $atributos = [];

  private static $instance;

  public static function getInstancia(){
      if (!isset(self::$instance)){
          self::$instance = new BeneficioMensualController();
      }
      return self::$instance;
  }

  public function buscarTodoPorCasino(){
    $casinos = Casino::all();
    UsuarioController::getInstancia()->agregarSeccionReciente('Estasdísticas Por Casino','estadisticasPorCasino');
    return view('seccionEstadisticasPorCasino')->with('casinos',$casinos);
  }

  public function buscarTodoInteranuales(){
    $casinos = Casino::all();
    UsuarioController::getInstancia()->agregarSeccionReciente('Estadísticas Interanuales','interanuales');
    return view('seccionEstadisticasInteranuales')->with('casinos',$casinos);
  }

  public function buscarTodoGenerales(){
    UsuarioController::getInstancia()->agregarSeccionReciente('Estadísticas Generales','estadisticasGenerales');
    return view('seccionEstadisticasGenerales');
  }

  public function cargarEstadisticasGenerales(Request $request){

        $fecha_inicio=$request->fecha_desde;
        $fecha_fin=$request->fecha_hasta;

        $validator=Validator::make($request->all(), [
          'fecha_desde' => 'required|date',
          'fecha_hasta' => 'nullable|date|before:first day of next month',
        ])->after(function ($validator){
            //VALIDACION DE FECHA
            $fecha_inicio=date_create($validator->getData()['fecha_desde']);
            $fecha_fin=date_create($validator->getData()['fecha_hasta']);

            if($fecha_inicio > $fecha_fin && $fecha_fin != ""){
              $validator->errors()->add('fecha_fin', 'La fecha final es mayor a la fecha de inicio.');
            }
        });
        $validator->validate();

        //un beneficio por dia, actividad y tipo de moneda;
        $beneficios_melincue  = BeneficioMensual::where([['id_casino' , '=' , 1] , ['id_tipo_moneda'  , '=' , 1] ,['anio_mes', '>=', $fecha_inicio], ['anio_mes', '<=', $fecha_fin]])->orderBy('anio_mes', 'asc')->get();
        $beneficios_stafe  = BeneficioMensual::where([['id_casino' , '=' , 2] , ['id_tipo_moneda'  , '=' , 1] ,['anio_mes', '>=', $fecha_inicio], ['anio_mes', '<=', $fecha_fin]])->orderBy('anio_mes', 'asc')->get();
        $beneficios_rosario_ars  = BeneficioMensual::where([['id_casino' , '=' , 3] , ['id_tipo_moneda'  , '=' , 1] ,['anio_mes', '>=', $fecha_inicio], ['anio_mes', '<=', $fecha_fin]])->orderBy('anio_mes', 'asc')->get();
        $beneficios_rosario_dol  = BeneficioMensual::where([['id_casino' , '=' , 3] , ['id_tipo_moneda'  , '=' , 2] ,['anio_mes', '>=', $fecha_inicio], ['anio_mes', '<=', $fecha_fin]])->orderBy('anio_mes', 'asc')->get();

        $resultadosRosario_ars = $this->calcularBrutoYCanon($fecha_inicio,$fecha_fin,$beneficios_rosario_ars);

        $resultadosRosario_dol = $this->calcularBrutoYCanon($fecha_inicio,$fecha_fin,$beneficios_rosario_dol);

        $resultadosMelincue = $this->calcularBrutoYCanon($fecha_inicio,$fecha_fin,$beneficios_melincue);

        $resultadosSantaFe = $this->calcularBrutoYCanon($fecha_inicio,$fecha_fin,$beneficios_stafe);

        return [
          'resultadosMelincue' => $resultadosMelincue,
          'resultadosSantaFe' => $resultadosSantaFe,
          'resultadosRosario_ars' => $resultadosRosario_ars,
          'resultadosRosario_dol' => $resultadosRosario_dol,];
  }
  //retorna bruto y canon total, y tambien por mes
  public function calcularBrutoYCanon($fecha_inicio, $fecha_fin ,$beneficios){//por mes calcula bruto y canon total (suma de los juegos)
    $meses=array();
    $fecha_actual=$fecha_inicio;
    $bruto_mensual=0;
    $canon_mensual=0;
    $bruto=0;
    $canon=0;
    $i=0;
    while ($fecha_actual <= $fecha_fin){
      if (isset($beneficios[$i]) && $fecha_actual == $beneficios[$i]->anio_mes) {
        $bruto+=$beneficios[$i]->bruto;
        $canon+=$beneficios[$i]->canon;
        $bruto_mensual+=$beneficios[$i]->bruto;
        $canon_mensual+=$beneficios[$i]->canon;
        $i++;
      }else {
        $meses[]=['fecha' => $fecha_actual ,'canon' => $canon_mensual, 'bruto'=> $bruto_mensual];
        $bruto_mensual=0;
        $canon_mensual=0;
        // $melincue=['fecha' => $fecha_actual ,'actividad' =>  '','canon' => '', 'bruto'=> ''];
        $fecha_actual = date('Y-m-d' , strtotime($fecha_actual . ' + 1 months'));
      }
    }
    return ['bruto' => $bruto, 'canon' => $canon, 'meses' => $meses];

  }

  public function cargarSeccionEstadisticasPorCasino(Request $request){
        /*
        validacion datos de entrada
        */
        $validator=Validator::make($request->all(), [
          'fecha_desde' => 'required|date',
          'fecha_hasta' => 'required|date|before:first day of next month',
          'id_casino' =>'required|exists:casino,id_casino',
        ])->after(function ($validator){
            $fecha_inicio=date_create($validator->getData()['fecha_desde']);
            $fecha_fin=date_create($validator->getData()['fecha_hasta']);

            if($fecha_inicio >= $fecha_fin){
              $validator->errors()->add('fecha_fin', 'La fecha final es mayor a la fecha de inicio.');
            }
        });
        $validator->validate();

        $fecha_inicio=$request->fecha_desde;
        $fecha_fin=$request->fecha_hasta;
        $casino=$request->id_casino;
        /*
        busqueda en la base de datos
        */

        //1 -> MTM
        $resultados_MTM= BeneficioMensual::where([
           ['anio_mes', '>=', $fecha_inicio],
           ['anio_mes', '<=', $fecha_fin],
           ['id_casino', '=' , $casino],
           ['id_actividad' , '=' , 1],
        ])->orderBy('anio_mes', 'asc')->get();
        //2->mesas
        $resultados_Mesas= BeneficioMensual::where([
           ['anio_mes', '>=', $fecha_inicio],
           ['anio_mes', '<=', $fecha_fin],
           ['id_casino', '=' , $casino],
           ['id_actividad' , '=' , 2],
        ])->orderBy('anio_mes', 'asc')->get();
        //3->bingo
        $resultados_Bingo= BeneficioMensual::where([
           ['anio_mes', '>=', $fecha_inicio],
           ['anio_mes', '<=', $fecha_fin],
           ['id_casino', '=' , $casino],
           ['id_actividad' , '=' , 3],
        ])->orderBy('anio_mes', 'asc')->get();

        $MTMbruto=0;
        $MTMcanon=0;
        $Bingobruto=0;
        $Bingocanon=0;
        $Mesasbruto=0;
        $Mesascanon=0;
        $mesesMesas = array();
        $mesesBingo = array();
        $mesesMTM = array();

        $fecha_actual = $request->fecha_desde;
        $i=0;
          while ($fecha_actual <= $fecha_fin){
                if (isset($resultados_MTM[$i]) && $fecha_actual == $resultados_MTM[$i]->anio_mes) {

                  $mesesMTM[]=['bruto' =>$resultados_MTM[$i]->bruto , 'canon' =>$resultados_MTM[$i]->canon ,'fecha' => $resultados_MTM[$i]->anio_mes];
                  $MTMbruto+=$resultados_MTM[$i]->bruto;
                  $MTMcanon+=$resultados_MTM[$i]->canon;
                  $i++;

                }else {

                  $mesesMTM[]=['fecha' => $fecha_actual ,'canon' => '-', 'bruto'=> '-'];

                }

                $fecha_actual = date('Y-m-d' , strtotime($fecha_actual . ' + 1 months'));

        }

        $fecha_actual = $request->fecha_desde;
        $i=0;
        while ($fecha_actual <= $fecha_fin){
                if (isset($resultados_Mesas[$i]) && $fecha_actual == $resultados_Mesas[$i]->anio_mes) {

                  $mesesMesas[]=['bruto' =>$resultados_Mesas[$i]->bruto , 'canon' =>$resultados_Mesas[$i]->canon ,'fecha' => $resultados_Mesas[$i]->anio_mes];
                  $Mesasbruto+=$resultados_Mesas[$i]->bruto;
                  $Mesascanon+=$resultados_Mesas[$i]->canon;
                  $i++;

                }else {

                  $mesesMesas[]=['fecha' => $fecha_actual ,'canon' => '-', 'bruto'=> '-'];

                }
                $fecha_actual = date('Y-m-d' , strtotime($fecha_actual . ' + 1 months'));
        }

        $fecha_actual = $request->fecha_desde;
        $i=0;
        while ($fecha_actual <= $fecha_fin){
                if (isset($resultados_Bingo[$i]) && $fecha_actual == $resultados_Bingo[$i]->anio_mes) {

                  $mesesBingo[]=['bruto' =>$resultados_Bingo[$i]->bruto , 'canon' =>$resultados_Bingo[$i]->canon ,'fecha' => $resultados_Bingo[$i]->anio_mes];
                  $Bingobruto+=$resultados_Bingo[$i]->bruto;
                  $Bingocanon+=$resultados_Bingo[$i]->canon;
                  $i++;

                }else {

                  $mesesBingo[]=['fecha' => $fecha_actual ,'canon' => '-', 'bruto'=> '-'];

                }
                $fecha_actual = date('Y-m-d' , strtotime($fecha_actual . ' + 1 months'));
        }


        $resultadoMTM = ['bruto' => $MTMbruto , 'canon' => $MTMcanon , 'meses' => $mesesMTM];
        $resultadoBingos=['bruto' => $Bingobruto , 'canon' => $Bingocanon , 'meses' => $mesesBingo];
        $resultadoMesas=['bruto' => $Mesasbruto , 'canon' => $Mesascanon , 'meses' => $mesesMesas];

        return [
            'resultadosMTM' => $resultadoMTM,
            'resultadosBingo' => $resultadoBingos,
            'resultadosMesas' => $resultadoMesas,
        ];

  }

  public function cargaSeccionInteranual(Request $request){
    $validator=Validator::make($request->all(), [
      'fecha_desde' => 'required|numeric',
      'fecha_hasta' => 'required|numeric',
      'id_casino' =>'required|exists:casino,id_casino',
    ])->after(function ($validator){

    });
    $validator->validate();
    //X es primer año, se compara contra el segundo Y
    $resultados_x = BeneficioMensual::where('id_casino' , '=' , $request->id_casino)->whereYear('anio_mes' ,'=' , $request->fecha_desde)->orderBy('anio_mes', 'asc')->get();
    $resultados_y = BeneficioMensual::where('id_casino' , '=' , $request->id_casino)->whereYear('anio_mes' ,'=' , $request->fecha_hasta)->orderBy('anio_mes', 'asc')->get();

    $resultados_x_mtm = BeneficioMensual::where([['id_casino' , '=' , $request->id_casino] , ['id_actividad' , '=' ,1 ]])->whereYear('anio_mes' ,'=' , $request->fecha_desde)->orderBy('anio_mes', 'asc')->get();
    $resultados_x_mesas = BeneficioMensual::where([['id_casino' , '=' , $request->id_casino] , ['id_actividad' , '=' ,2 ]])->whereYear('anio_mes' ,'=' , $request->fecha_desde)->orderBy('anio_mes', 'asc')->get();
    $resultados_x_bingo = BeneficioMensual::where([['id_casino' , '=' , $request->id_casino] , ['id_actividad' , '=' ,3 ]])->whereYear('anio_mes' ,'=' , $request->fecha_desde)->orderBy('anio_mes', 'asc')->get();
    $resultados_y_mtm = BeneficioMensual::where([['id_casino' , '=' , $request->id_casino] , ['id_actividad' , '=' ,1 ]])->whereYear('anio_mes' ,'=' , $request->fecha_hasta)->orderBy('anio_mes', 'asc')->get();
    $resultados_y_mesas = BeneficioMensual::where([['id_casino' , '=' , $request->id_casino] , ['id_actividad' , '=' ,2 ]])->whereYear('anio_mes' ,'=' , $request->fecha_hasta)->orderBy('anio_mes', 'asc')->get();
    $resultados_y_bingo = BeneficioMensual::where([['id_casino' , '=' , $request->id_casino] , ['id_actividad' , '=' ,3 ]])->whereYear('anio_mes' ,'=' , $request->fecha_hasta)->orderBy('anio_mes', 'asc')->get();


    $fecha_inicio_x = $request->fecha_desde  . '-01-01';
    $fecha_inicio_y = $request->fecha_hasta  . '-01-01';
    $fecha_fin_x = $request->fecha_desde  . '-12-31';
    $fecha_fin_y = $request->fecha_hasta  . '-12-31';

    //Todo año x
    $año_x = $this->calcularBrutoYCanon($fecha_inicio_x, $fecha_fin_x , $resultados_x);
    $año_x_mtm = $this->calcularBrutoYCanon($fecha_inicio_x, $fecha_fin_x , $resultados_x_mtm);
    $año_x_mesas = $this->calcularBrutoYCanon($fecha_inicio_x, $fecha_fin_x , $resultados_x_mesas);
    $año_x_bingo = $this->calcularBrutoYCanon($fecha_inicio_x, $fecha_fin_x , $resultados_x_bingo);

    //Todo año y
    $año_y = $this->calcularBrutoYCanon($fecha_inicio_y, $fecha_fin_y , $resultados_y);
    $año_y_mtm = $this->calcularBrutoYCanon($fecha_inicio_y, $fecha_fin_y , $resultados_y_mtm);
    $año_y_mesas = $this->calcularBrutoYCanon($fecha_inicio_y, $fecha_fin_y , $resultados_y_mesas);
    $año_y_bingo = $this->calcularBrutoYCanon($fecha_inicio_y, $fecha_fin_y , $resultados_y_bingo);


    //porcentajes bruto y canon
    for ($i=0; $i <count($año_x['meses']) ; $i++) {

      if( ($año_x['meses'][$i]['bruto']) != 0 && ($año_y['meses'][$i]['bruto'])!=0){

        $porcentaje_bruto[] = (( $año_x['meses'][$i]['bruto']/ $año_y['meses'][$i]['bruto'] ) - 1) *100;
        $porcentaje_canon[] = (( $año_x['meses'][$i]['canon'] / $año_y['meses'][$i]['canon'] ) - 1) *100;

      }else {
        $porcentaje_bruto[] = null;
        $porcentaje_canon[] = null;
      }

      if( ($año_x_mtm['meses'][$i]['canon']) != 0 && ($año_y_mtm['meses'][$i]['canon'])!=0){
        $porcentaje_mtm [] = (($año_x_mtm['meses'][$i]['canon'] / $año_y_mtm['meses'][$i]['canon']) - 1) *100;
      }else {
        $porcentaje_mtm [] = null;
      }


      if ( ($año_x_mesas['meses'][$i]['canon']) != 0 && ($año_y_mesas['meses'][$i]['canon'])!=0) {
        $porcentaje_mesas[]=(($año_x_mesas['meses'][$i]['canon'] / $año_y_mesas['meses'][$i]['canon']) - 1) *100;
      }else {
        $porcentaje_mesas[]=null;
      }

      if ( ($año_x_bingo['meses'][$i]['canon']) != 0 && ($año_y_bingo['meses'][$i]['canon'])!=0) {
        $porcentaje_bingo[]=(($año_x_bingo['meses'][$i]['canon'] / $año_y_bingo['meses'][$i]['canon']) - 1) *100;
      }else {
        $porcentaje_bingo[]= null;
      }

    }//fin for

    return [ 'porcentajesMTM' => $porcentaje_mtm,
             'porcentajesBingo' => $porcentaje_bingo,
             'porcentajesMesas' =>  $porcentaje_mesas,
             'porcentajesBruto' => $porcentaje_bruto,
             'porcentajesCanon' => $porcentaje_canon,
             'resultadosMTM' => ['año_x' => $año_y_mtm, 'año_y' => $año_y_mtm],
             'resultadosBingo' => ['año_x' => $año_x_bingo, 'año_y' => $año_y_bingo],
             'resultadosMesas' =>  ['año_x' => $año_x_mesas, 'año_y' => $año_y_mesas],
             'resultadosX' => $año_x,
             'resultadosY' => $año_y];

  }

  public function obtenerUltimosBeneficiosPorCasino(Request $request){

      $beneficios_mel = DB::table('beneficio_mensual')->where([['id_casino',1],['id_actividad',1]])->orderBy('fecha','desc')->get();
      $beneficios_sfe = DB::table('beneficio_mensual')->where([['id_casino',2],['id_actividad',1]])->orderBy('fecha','desc')->get();
      $beneficios_ros = DB::table('beneficio_mensual')->where([['id_casino',3],['id_actividad',1]])->orderBy('fecha','desc')
                           ->join('tipo_moneda','beneficio_mensual.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda')->get();

      return ['beneficios_mel' => $beneficios_mel,
              'beneficios_sfe' => $beneficios_sfe,
              'beneficios_ros' => $beneficios_ros];
  }
}
