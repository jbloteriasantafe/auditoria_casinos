<?php

namespace App\Http\Controllers\Bingo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Bingo\ReporteEstado;
use App\Bingo\ImportacionBingo;
use App\Http\Controllers\UsuarioController;

use Illuminate\Support\Facades\DB;
use View;
use Dompdf\Dompdf;
class InformeController extends Controller
{
    public function index(){
      //otengo los informes para cada casino
      $informe_ros = $this->obtenerInforme(3);
      $informe_sfe = $this->obtenerInforme(2);
      $informe_mel = $this->obtenerInforme(1);

      UsuarioController::getInstancia()->agregarSeccionReciente('Informe Bingo' , 'informe-bingo');


      return view('Bingo.informe', compact('informe_ros','informe_sfe','informe_mel'));
    }
    public function generarPlanilla($fecha, $id_casino, $valor = ''){
      //obtengo mes y año a partir de la fecha
      $mm = substr($fecha,5,2);
      $aaaa =  substr($fecha,0,4);


      //obtengo las importaciones para el casino, mes y año dado
      $importaciones = ImportacionBingo::where('id_casino','=',$id_casino)
                                              ->whereYear('fecha',$aaaa)
                                              ->whereMonth('fecha', $mm)
                                              ->orderBy('fecha','asc')
                                              ->get();

      //obtengo todas las importaciones con distintos días
      $importaciones_dias = $importaciones->unique('fecha')->all();
      //collection to array
      $importaciones = $importaciones->all();
      //variable  para guardar las lineas ha imprimir en la planilla
      $resultado_importaciones = array();
      //recorro el array que tiene las fechas diarias de importaciones
      foreach ($importaciones_dias as $dias) {
        //por cada partida de la misma fecha, sumo los totales de la sesión
        $sumarecaudado = $sumapremiobingo = $sumapremiolinea = $sumabeneficio = 0;
        foreach ($importaciones as $importacion) {
          if($dias->fecha == $importacion->fecha){
            $sumarecaudado += $importacion->recaudado;
            $sumapremiobingo += $importacion->premio_bingo;
            $sumapremiolinea += $importacion->premio_linea;
          }
        }
        //creo un array auxiliar para guardar los datos hasta meterlos en resultado_importacioens
        $resultado =  new \stdClass();
        // $resultado->fecha = $dias->fecha;
        $resultado->fecha = substr($dias->fecha,8,2).'-'.substr($dias->fecha,5,2).'-'.substr($dias->fecha,0,4); //acomodo la fecha
        $resultado->recaudado = $sumarecaudado;
        $resultado->premio_bingo = $sumapremiobingo;
        $resultado->premio_linea = $sumapremiolinea;
        $resultado->beneficio =  $sumarecaudado - ($sumapremiobingo + $sumapremiolinea);
        $resultado_importaciones [] = $resultado;
      }


      //suma la fila completa de  lo recaudado, premio linea y premio bingo
      $sumarecaudado = $sumapremiobingo = $sumapremiolinea = 0;
        foreach ($resultado_importaciones as $importacion) {
          $sumarecaudado += $importacion->recaudado;
          $sumapremiobingo += $importacion->premio_bingo;
          $sumapremiolinea += $importacion->premio_linea;
        }

      //obtiene el beneficio
      $beneficio = $sumarecaudado - ($sumapremiobingo + $sumapremiolinea);

      //asigna nombre al casino según id
      $casino;
      if($importaciones[0]->id_casino == 3) $casino = 'Rosario';
      if($importaciones[0]->id_casino == 2) $casino = 'Santa Fe';
      if($importaciones[0]->id_casino == 1) $casino = 'Melincué';

      //otiene el nombre del mes
      $mes = $this->obtenerMes($mm);

      //si tiene %% los reemplazo por //
      $valor = str_replace("&", "/", $valor);

      //pasa la vista a pdf
      $view = View::make('Bingo.planillaInforme', compact('resultado_importaciones','sumarecaudado','sumapremiobingo','sumapremiolinea','beneficio','casino','mes','valor'));
      $dompdf = new Dompdf();
      $dompdf->set_paper('A4', 'portrait');
      $dompdf->set_option('chroot',public_path());
      $dompdf->loadHtml($view->render());
      $dompdf->render();
      $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");

      //retorno para descargar
      // if($valor != ''){
      //   return $dompdf->stream($casino .'-'. $mm .'-'. $aaaa. '.pdf');
      // }else{ //retorno apertura de pdf
        return $dompdf->stream($casino .'-'. $mm .'-'. $aaaa. '.pdf', Array('Attachment'=>0));
      // }

    }

    protected function obtenerInforme($id_casino){
      //obtengo los reportes de estado asociados a cada casino
      $apto_informe = ReporteEstado::where([['id_casino','=',$id_casino],['importacion','=','1']])->orderBy('fecha_sesion','desc')->get()->all();

      // dd($apto_informe);
      //obtengo mes y año de la primer ocurrencia para comenzar a recorrer
      $fecha_cero = substr($apto_informe[0]->fecha_sesion,0,7);
      //array auxiliar para guardar todos los posibles informes,
      //sólo se guardaran si el mes se completó...ésto sucede si existe un reporte con fecha de mes siguente
      $informe = array();

      //agregar último

      $ultimo = $apto_informe[0];
      $informe_auxiliar = new \stdClass();
      $informe_auxiliar->id_casino = $id_casino;
      $informe_auxiliar->fecha_completa = $ultimo->fecha_sesion;
      $informe_auxiliar->fecha_informe = $this->obtenerMes(substr($ultimo->fecha_sesion,5,2))." ".substr($ultimo->fecha_sesion,0,4);
      $informe_auxiliar->estado = 0;

      $informe [] = $informe_auxiliar;

      //bandera auxiliar para saber el estado del informe--> 1 OK (informe disponible), 0 NO (informe no disponible);
      $estado = 1;
      //recorro los reportes obtenidos para el casino
      foreach ($apto_informe as $linea) {
          //clase auxiliar para guardar los datos necesarios para asignar al array con los posibles informes
          $informe_auxiliar = new \stdClass();
          $informe_auxiliar->id_casino = $id_casino;
          $informe_auxiliar->fecha_completa = $linea->fecha_sesion;
          //obtengo la fecha de la siguiente linea para comparar
          $fecha_linea = substr($linea->fecha_sesion,0,7);
          //si el reporte no esta visado, cambio el valor del estado
          if($linea->visado !=1) $estado = 0;
          //si la fecha no coincide--> cambió de mes, guardo los datos del mes anterior
          if( $fecha_cero != $fecha_linea){
            //obtengo la fecha del informe --> nombre de mes + año
            $informe_auxiliar->fecha_informe = $this->obtenerMes(substr($linea->fecha_sesion,5,2))." ".substr($linea->fecha_sesion,0,4);
            //asigno el estado
            $informe_auxiliar->estado = $estado;
            //restablezco el estado para el siguente mes
            $estado = 1;
            //guardo el mes
            $informe [] = $informe_auxiliar;
            //asigno como nueva fecha cero la del actual
            $fecha_cero = substr($linea->fecha_sesion,0,7);
          }
      }


      return $informe;
    }

    protected function obtenerMes($mes_num){ // @params $mes_numer integer. Devuleve el mes correspondiente a un entero . @return String
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

}
