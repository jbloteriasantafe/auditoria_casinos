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
    $controllerDiarias = new ImportadorController;

    $importacion = ImportacionDiariaMesas::find($id_importacion);
    $importaciones = ImportacionDiariaMesas::where('id_casino','=',$importacion->id_casino)
                                              ->where('fecha','=',$importacion->fecha)
                                              ->orderBy('id_moneda','asc')
                                              ->get();

    $rta = array();
    $rta2 = collect([]);

    foreach ($importaciones as $imp) {
      $controllerDiarias->actualizarTotalesImpDiaria($imp->id_importacion_diaria_mesas);
      $modificaciones=collect([]);
      $modificaciones = CampoModificado::where('id_importacion_diaria_mesas',
                                        '=',$imp->id_importacion_diaria_mesas)
                                        ->get()->all();
                                        
      foreach ($modificaciones as $mod) {
        if($mod->nombre_entidad == 'Detalle Importación'){
          $impMod= DetalleImportacionDiariaMesas::find($mod->id_entidad);

          if($mod->nombre_del_campo == 'efectivo'){
            $nuevo='droop';
          }
          else{
            $nuevo= $mod->nombre_del_campo;
          }

          $rta2->push( ['mesa'=> $impMod->nro_mesa,
                    'juego' => $impMod->nombre_juego,
                    'campo_modificado' => $mod->nombre_del_campo,
                    'entidad' => $mod->nombre_entidad,
                    'valor_anterior' => $mod->valor_anterior,
                    'valor_nuevo' =>$mod->valor_nuevo]);

        }
        if($mod->nombre_entidad == 'Cierre de Mesa'){
          $cMod= Cierre::find($mod->id_entidad);
          $rta2->push( ['mesa'=> $cMod->mesa->nro_mesa,
                    'juego' => $cMod->mesa->juego->nombre_juego,
                    'campo_modificado' => $mod->nombre_del_campo,
                    'entidad' => $mod->nombre_entidad,
                    'valor_anterior' => $mod->valor_anterior,
                    'valor_nuevo' => $mod->valor_nuevo ]);

        }
      }

      $respuesta = DetalleImportacionDiariaMesas::where('id_importacion_diaria_mesas',
                                          '=',$imp->id_importacion_diaria_mesas)
                                          ->get()->sortBy('nro_mesa')->all();
      $rta[] = ['importacion'=> $imp,
                'detalles' => $respuesta];
    }
    $rta2 = $rta2->sortBy('mesa');
    $casino = $importacion->casino;
    $view = view('Informes.informeDiario', compact(['rta','rta2','casino']));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view);
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $importacion->casino->codigo."/".$importacion->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf->stream('informe_diario_'.$importacion->casino->codigo.'_'.$importacion->fecha.'.pdf', Array('Attachment'=>0));
  }

  public function imprimirMensual($fecha,$id_casino){
    $date = explode('-',$fecha);
    $meses = ImportacionMensualMesas::whereMonth('fecha_mes','=',$date[1])
                                      ->whereYear('fecha_mes','=',$date[0])
                                      ->where('id_casino','=',$id_casino)
                                      ->get();
    $mensualController = new MensualController;
    $por_moneda = array();
    foreach ($meses as $mes) {
      //$mensualController->actualizarTotales($mes->id_importacion_mensual_mesas);
      $fecha = explode('-',$mes->fecha_mes);
      $por_juego = $this->obtenerTotalesPorJuego($fecha, $mes->total_utilidad_mensual,$mes->id_moneda, $mes->id_casino);

      $por_moneda[] = [
                        'moneda' => $mes->moneda->siglas,
                        'totales_moneda' => $mes,
                        'casino' => $mes->casino->nombre,
                        'juegos' => $por_juego,
                        'detalles' => $mes->detalles,
                        'mes' => $mes->mes,
                      ];
    }

    $uno = $meses->first();

    $view= view('Informes.informeMes', compact('por_moneda'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view);
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $uno->casino->codigo."/".$uno->mes, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf->stream('informe_mensual_'.$uno->casino->codigo."-".$uno->mes.'.pdf', Array('Attachment'=>0));
  }

  private function obtenerTotalesPorJuego($fecha,$total, $id_importacion,$id_casino){
    $por_juego = DB::table('importacion_diaria_mesas as IDM')
                      ->select('DIDM.nombre_juego','DIDM.utilidad')
                      ->join('detalle_importacion_diaria_mesas as DIDM',
                              'DIDM.id_importacion_diaria_mesas','=',
                              'IDM.id_importacion_diaria_mesas')
                      ->whereYear('IDM.fecha','=',$fecha[0])
                      ->whereMonth('IDM.fecha','=',$fecha[1])
                      ->where('IDM.id_casino','=',$id_casino)
                      ->where('IDM.id_moneda','=',$id_importacion)
                      ->groupBy('DIDM.nombre_juego','DIDM.utilidad')
                      ->orderBy('nombre_juego','asc')
                      ->get();
    $respuesta = array();
    $nombre_juego_anterior = $por_juego->first()->nombre_juego;
    $total_juego = 0;
    //dd($por_juego);
    foreach ($por_juego as $detalle) {
      if($detalle->nombre_juego != $nombre_juego_anterior){
        if($total != 0){
          $div = round((($total_juego * 100)/$total),2);
        }else{
          $div =0;
        }
        $respuesta[] = [
                          'nombre_juego' => $nombre_juego_anterior,
                          'porcentaje' => $div,
                          'total' => $total_juego
                        ];
        $nombre_juego_anterior = $detalle->nombre_juego;
        $total_juego = $detalle->utilidad;
      }else{
        $total_juego+= $detalle->utilidad;
      }
    }
    if($total != 0){
      $div = round((($total_juego * 100)/$total),2);
    }else{
      $div =0;
    }
    $respuesta[] = [
                    'nombre_juego' => $nombre_juego_anterior,
                    'porcentaje' => $div,
                    'total' => $total_juego
                    ];
    return $respuesta;
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
