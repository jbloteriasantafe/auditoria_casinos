<?php

namespace App\Http\Controllers\Mesas\InformesMesas;

use Auth;
use Session;
use Illuminate\Http\Request;
use Response;
use App\Http\Controllers\Controller;

use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

use App\Mesas\ImportacionMensualMesas;
use App\Mesas\DetalleImportacionMensualMesas;
use App\Http\Controllers\Mesas\Importaciones\Mesas\ImportadorController;
use App\Http\Controllers\Mesas\Importaciones\Mesas\MensualController;

use Dompdf\Dompdf;

use PDF;
use View;

use App\Usuario;
use App\Mesas\CSVImporter;
use App\Casino;
use App\Relevamiento;
use App\SecRecientes;
use App\Http\Controllers\RolesPermissions\RoleFinderController;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\Mesas\Mesa;
use App\Mesas\Cierre;
use App\Mesas\CampoModificado;

use App\Mesas\Moneda;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;

use App\Mesas\ImportacionDiariaMesas;
use App\Mesas\DetalleImportacionDiariaMesas;

use App\Mesas\ComandoEnEspera;

use \DateTime;
use \DateInterval;
use Carbon\Carbon;

use App\Http\Controllers\UsuarioController;

class BCInformesController extends Controller
{
  private static $atributos = [
  ];

  /**
   * Create a new controller instance.
   *
   * @return void
   */
   public function __construct()
   {
       $this->middleware(['tiene_permiso:m_bc_diario_mensual']);//rol a definir por gusti-> en ppio AUDITOR
   }


  public function imprimirDiario($id_importacion){
    return "Deprecado";
  }

  public function imprimirMensual($fecha,$id_casino){
    return "Deprecado";
  }

  public function obtenerDatosGraficos(Request $request){
    $monthNames = [".-.","Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
      "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
      ];

    $fecha = explode('-',$request->fecha);
    if($fecha[0] == 0 && $fecha[1] ==0){
      $date = Carbon::now()->subMonths(1)->format("Y-m");
      $mes = Carbon::now()->subMonths(1)->format("n");
      $nombre_mes = $monthNames[$mes] ;
      $nro_mes = $mes;
      $fecha = explode('-',$date);
    }else {
      $nombre_mes =$fecha[1];
      $i = 0;
      foreach ($monthNames as $mmm) {
        if($mmm == $fecha[1]){
          $nro_mes = $i;
          break;
        }
        $i++;
      }
    }

    $importaciones = ImportacionMensualMesas::where('id_casino','=',$request->id_casino)
                                              ->whereYear('fecha_mes','=',$fecha[0])
                                              ->whereMonth('fecha_mes','=',$nro_mes)
                                              ->get();
                //dd($importaciones,$request->id_casino);
    $por_moneda = array();
    foreach ($importaciones as $imp) {
      $total = $imp->total_utilidad_mensual;
      $por_juego = DB::table('importacion_diaria_mesas as IDM')
                        ->select('DIDM.nombre_juego','DIDM.utilidad')
                        ->join('detalle_importacion_diaria_mesas as DIDM',
                                'DIDM.id_importacion_diaria_mesas','=',
                                'IDM.id_importacion_diaria_mesas')
                        ->whereYear('IDM.fecha','=',$fecha[0])
                        ->whereMonth('IDM.fecha','=',$nro_mes)
                        ->where('IDM.id_moneda','=',$imp->id_moneda)
                        ->where('IDM.id_casino','=',$imp->id_casino)
                        ->groupBy('DIDM.nombre_juego','DIDM.utilidad')
                        ->orderBy('nombre_juego','asc')
                        ->get();
                        // dd($por_juego);
      $respuesta = array();
      if(count($por_juego)>0){
        $nombre_juego_anterior = $por_juego->first()->nombre_juego;
        $total_juego = 0;

        foreach ($por_juego as $detalle) {
          if($detalle->nombre_juego != $nombre_juego_anterior){
            // dd($total_juego,$total);
            if($total != 0){
              $div = round((($total_juego * 100)/$total),2);
            }else{
              $div =0;
            }
            //agrego el juego al listado
            $respuesta[] = [
                              'name' => $nombre_juego_anterior,
                              'y' => $div,
                            ];

            $nombre_juego_anterior = $detalle->nombre_juego;
            $total_juego = $detalle->utilidad;
          }else{
            //sino sigo sumando
            $total_juego+= $detalle->utilidad;
          }
        }
        if($total != 0){
          $div = round((($total_juego * 100)/$total),2);
        }else{
          $div =0;
        }
        $respuesta[] = [
                          'name' => $nombre_juego_anterior,
                          'y' => $div,
                        ];
      }
      $por_moneda[] =  $respuesta;
    }

    return['por_moneda' => $por_moneda,'fecha' =>$fecha,'nombre_mes' => $nombre_mes];
  }

}
