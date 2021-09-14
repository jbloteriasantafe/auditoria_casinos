<?php

namespace App\Http\Controllers\Mesas\Apuestas;

use Auth;
use Session;
use Illuminate\Http\Request;
use Response;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use View;
use Illuminate\Support\Facades\Hash;

use App\Usuario;
use App\Casino;
use App\Turno;
use App\SecRecientes;
use App\Http\Controllers\RolesPermissions\RoleFinderController;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\UsuarioController;
use App\Mesas\JuegoMesa;
use App\Mesas\ApuestaMinimaJuego;
use App\Mesas\Moneda;

use App\Http\Controllers\Mesas\Apuestas\ABMApuestasController;
use App\Mesas\RelevamientoApuestas;
use Dompdf\Dompdf;

use PDF;
use Zipper;
use File;
use Carbon\Carbon;
use Exception;

class GenerarPlanillasController extends Controller
{
  private static $atributos = [
    'apuesta_minima' => 'Apuesta Minima',
    'id_juego_mesa'=>'Juego',
  ];

  private static $cantidad_dias_backup = 5;

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
  }

  public function generarRelevamientosApuestas(){
    $permissions = intval( config('permissions.directory'), 8 );

    if(file_exists( public_path().'/Mesas/RelevamientosApuestas')){
      File::deleteDirectory(public_path().'/Mesas/RelevamientosApuestas');
      File::makeDirectory(public_path().'/Mesas/RelevamientosApuestas');
    }else{
      if(!file_exists( public_path().'/Mesas')){
          File::makeDirectory(public_path().'/Mesas');
      }
      File::makeDirectory(public_path().'/Mesas/RelevamientosApuestas');
    }
    $apuestasController = new ABMApuestasController;

    $casinos = Casino::all();
    $arregloRutas = array();
    $fecha_hoy = Carbon::now()->format("Y-m-d");
    foreach ($casinos as $casino) {
      foreach ($casino->turnos as $turno) {
        $arregloRutasTurno = array();
          for ($i=0; $i < self::$cantidad_dias_backup; $i++) {
            $fecha_backup = Carbon::now()->addDays($i)->format("Y-m-d");
            $dia_carbon = Carbon::now()->addDays($i);
            $numeroDia = $dia_carbon->format('w');
            if($numeroDia == 0){
              $numeroDia = 7;
            }
            //si el turno esta el dia de fecha_backup entonces se crea

            //si el dia del turno es uno solo y el $numeroDia justo coincide..
            if($this->esElDiaDelTurno($numeroDia,$turno)){
              //busco si existe el que estoy creando

              if($i>0){
                $backUp = 1;
              }else{
                $backUp = 0;
              }
              $relevamiento = RelevamientoApuestas::where([['id_turno','=',$turno->id_turno],
                                                            ['id_casino','=',$casino->id_casino],
                                                            ['es_backup','=',$backUp],
                                                            ['fecha','=',$fecha_backup],
                                                            ['created_at','=',$fecha_hoy]
                                                          ])->get();
              if(count($relevamiento) == 1){
                $id_relevamiento = $relevamiento->first()->id_relevamiento_apuestas;
              }else{
                if(count($relevamiento) == 0){
                  $id_relevamiento = $apuestasController->crearRelevamientoApuestas($casino,$turno,$fecha_backup);
                }else{
                  $id_relevamiento = $relevamiento->first()->id_relevamiento_apuestas;
                }
              }
              $dompdf = $this->generarPlanilla( $id_relevamiento,$turno, $fecha_backup, $casino);

              $output = $dompdf->output();
              $ruta = public_path()."/Mesas/RelevamientosApuestas/Valores_Minimos_Apuestas-fecha_".$fecha_backup.
                      '_Turno-Nro-'.$turno->nro_turno.'-Dias-'.$turno->nombre_dia_desde.'-a-'.$turno->nombre_dia_hasta.".pdf";


              file_put_contents($ruta, $output);
              $arregloRutasTurno[] = $ruta;
            }
          }
          if(count($arregloRutasTurno)>0 ){
            //lo crea y no lo encuentra
            $nombreZipTurno = public_path().'/Mesas/RelevamientosApuestas/'.'Valores_Minimos_Apuestas_'.$fecha_hoy.'_al_'.$fecha_backup.'-TURNO-Nro-'.
                               $turno->nro_turno.'-Dias-'.$turno->nombre_dia_desde.'-a-'.$turno->nombre_dia_hasta.'.zip';
            Zipper::make($nombreZipTurno)->add($arregloRutasTurno)->close();
            $arregloRutas[] = $nombreZipTurno;
            File::delete($arregloRutasTurno);
          }
        }
        $nombreZip = 'Planillas-Apuestas-'.$casino->codigo
                  .'-'.$fecha_hoy.'-al-'.strftime("%Y-%m-%d", strtotime("$fecha_hoy +".(self::$cantidad_dias_backup-1)." day"))
                  .'.zip';

        Zipper::make(public_path().'/Mesas/RelevamientosApuestas/'.$nombreZip)->add($arregloRutas)->close();
        File::delete($arregloRutas);
        $arregloRutas = array();
      }
    }


    private function esElDiaDelTurno($numeroDia, $turno){
      //hay un unico dia en el turno
      if($turno->dia_desde == $turno->dia_hasta && $numeroDia == $turno->dia_desde){
        return true;
      }

      //el dia que incia el turno es menor que el que termina
      if(($numeroDia >= $turno->dia_desde && $numeroDia <= $turno->dia_hasta) && $turno->dia_desde < $turno->dia_hasta){
        return true;
      }
      //el dia que incia el turno es mayor que el que termina
      if(($numeroDia <= $turno->dia_desde && $numeroDia <= $turno->dia_hasta) && $turno->dia_desde > $turno->dia_hasta){
        return true;
      }
      return false;
    }


    //falta agregarle nro de pagina .-
    private function generarPlanilla( $id_relevamiento,
                                      Turno $turno, $fecha_backup, Casino $casino){
      $relevamiento = RelevamientoApuestas::find($id_relevamiento);
      $rel = new \stdClass();
      //['paginas' => $pagina,'nro_paginas'=>$count_nro_pagina]
      $datos =$this->obtenerDatosRelevamiento($id_relevamiento);

      $rel->paginas = $datos['paginas'];
      $rel->nro_paginas = $datos['nro_paginas'];
      $rel->fecha = $relevamiento->created_at;
      $rel->fecha_backup = $fecha_backup;
      $rel->turno = $turno->nro_turno;
      $hora = explode(':',$relevamiento->hora_propuesta);

      $rel->hora_propuesta = $hora[0].':'.$hora[1];

      $rel->observaciones = '';
      $rel->fiscalizador = '';
      $rel->hora_ejecucion = '__:__';
      //dd($datos['paginas'][1]);
      $view = View::make('Mesas.Planillas.PlanillaRelevamientoDeApuestas', compact('rel'));
      $dompdf = new Dompdf();
      $dompdf->set_paper('A4', 'landscape');
      $dompdf->loadHtml($view);
      $dompdf->render();
      $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
      $dompdf->getCanvas()->page_text(20, 565, $casino->codigo."/".$rel->fecha."/T-".$turno->nro_turno, $font, 10, array(0,0,0));
      $dompdf->getCanvas()->page_text(750, 565, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

      return $dompdf;
    }

    public function obtenerDatosRelevamiento($id_relevamiento)
    {
      $relevamiento = DB::table('relevamiento_apuestas_mesas as RA')
                                    ->select('DRA.nombre_juego','DRA.posiciones',
                                    'DRA.id_detalle_relevamiento_apuestas',
                                    'DRA.codigo_mesa','DRA.nro_mesa','DRA.minimo',
                                    'DRA.maximo','estado_mesa.siglas_mesa',
                                    'DRA.id_moneda')
                                    ->join('detalle_relevamiento_apuestas as DRA',
                                           'DRA.id_relevamiento_apuestas','=',
                                           'RA.id_relevamiento_apuestas')
                                    ->leftJoin('estado_mesa','estado_mesa.id_estado_mesa','=','DRA.id_estado_mesa')
                                    ->where('RA.id_relevamiento_apuestas','=',$id_relevamiento)
                                    ->orderBy('DRA.nombre_juego','asc')
                                    ->groupBy('DRA.nombre_juego',
                                              'DRA.id_detalle_relevamiento_apuestas',
                                              'DRA.codigo_mesa','DRA.nro_mesa',
                                              'DRA.posiciones','DRA.minimo',
                                              'DRA.maximo','estado_mesa.siglas_mesa',
                                              'DRA.id_moneda'
                                              )
                                    ->orderBy('nro_mesa','asc')
                                    ->get();
                //dd($id_relevamiento);
                $mesasporjuego = array();
                $mesas = array();
                $pagina = array();
                $columna = array();
                $izquierda = null;
                $derecha = null;
                $cantidadfilas = 18;
                $aux = 0;
                $aux2 = 0;
                if(count($relevamiento)>0){
                  $nombre_juego_anterior = $relevamiento->first()->nombre_juego;
                  $count_nro_pagina = 1;
                  foreach ($relevamiento as $detalle) {
                    //chequeo si tengo  que crear otro conjunto
                    if($nombre_juego_anterior != $detalle->nombre_juego || $aux == $cantidadfilas){
                        $mesasporjuego[] = [
                                              'juego' => $nombre_juego_anterior,
                                              'mesas' => $mesas,
                                              'filas' => count($mesas),
                                            ];
                        $mesas = array();

                    }

                    //chequeo que la columna $izquierda este vacia y aux = $cantidadfilas

                    if($izquierda == null && $aux == $cantidadfilas){
                      $izquierda = $mesasporjuego;
                      $mesasporjuego = array();
                      $aux = 0;
                    }
                    if($izquierda != null && $derecha == null && $aux2 == (2 * $cantidadfilas)){
                      $derecha = $mesasporjuego;
                      $mesasporjuego = array();

                      $pagina[] = [
                                    'izquierda' => $izquierda,
                                    'derecha' => $derecha,
                                    'count_nro_pagina' => $count_nro_pagina,
                                  ];
                      $izquierda = null;
                      $derecha = null;
                      $count_nro_pagina++;
                      $aux = 0;
                      $aux2 = 0;
                    }
                    if($detalle->minimo == null){
                      $minimo = '';
                      $maximo = '';
                      $estado = '';
                    }else{
                      $minimo = $detalle->minimo;
                      $maximo = $detalle->maximo;
                      $estado = $detalle->siglas_mesa;
                    }

                    if($detalle->id_moneda != null){
                      $siglas = Moneda::find($detalle->id_moneda)->siglas;
                    }
                    else {
                      $siglas = 'ARS__/USD__';
                    }

                    $mesas[] = [
                                  'codigo_mesa' => $detalle->codigo_mesa,
                                  'nro_mesa' => $detalle->nro_mesa,
                                  'siglas' => $siglas,
                                  'posiciones' => $detalle->posiciones,
                                  'minimo' => $minimo,
                                  'maximo' => $maximo,
                                  'estado' => $estado
                                ];

                    $aux++;
                    $aux2++;
                    $nombre_juego_anterior = $detalle->nombre_juego;
                  }
                  $mesasporjuego[] = [
                                        'juego' => $nombre_juego_anterior,
                                        'mesas' => $mesas,
                                        'filas' => count($mesas),
                                      ];
                  if($izquierda == null && $aux <= $cantidadfilas){
                    $izquierda = $mesasporjuego;
                    $mesasporjuego = array();
                    $pagina[] = [
                                  'izquierda' => $izquierda,
                                  'derecha' => $derecha,
                                  'count_nro_pagina' => $count_nro_pagina,
                                ];
                  }
                  if($izquierda != null && $derecha == null && $aux2 > ($cantidadfilas)){
                    $derecha = $mesasporjuego;
                    $mesasporjuego = array();

                    $pagina[] = [
                                  'izquierda' => $izquierda,
                                  'derecha' => $derecha,
                                  'count_nro_pagina' => $count_nro_pagina,
                                ];
                    $izquierda = null;
                    $derecha = null;
                  }
                }else{
                  $mesasporjuego[] = [
                                        'juego' => '-',
                                        'mesas' => array(),
                                        'filas' => 0,
                                      ];
                  $pagina[] = [
                                'izquierda' => $mesasporjuego,
                                'derecha' => null,
                                'count_nro_pagina' => 1,
                              ];
                  $count_nro_pagina = 1;
                }



                return ['paginas' => $pagina,'nro_paginas'=>$count_nro_pagina];
    }


}
