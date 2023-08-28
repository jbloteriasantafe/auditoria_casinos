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
use App\Http\Controllers\Turnos\TurnosController;

use App\Http\Controllers\Mesas\Apuestas\ABMApuestasController;
use App\Http\Controllers\Mesas\CarpetasHelper;
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
  
  private $carpetasHelper = null;
  public function __construct() {
    $this->carpetasHelper = new CarpetasHelper;
  }
  
  private function generarSiNoExiste($id_casino){
    $hoy = Carbon::now();
    $fechas_sorteadas = []; 
    
    $turnosController = new TurnosController;
    $apuestasController = new ABMApuestasController;
    
    $casino = Casino::find($id_casino);
        
    for($i=0;$i<self::$cantidad_dias_backup;$i++) {
      $f = (clone $hoy)->addDays($i)->format("Y-m-d");     
      $es_backup = $i==0? 0 : 1;
      
      $id_turnos = $turnosController
      ->obtenerTurnosActivos($id_casino,$f)
      ->sortBy('nro_turno')
      ->pluck('id_turno')->toArray();
      
      $id_turnos_con_relev = RelevamientoApuestas::where([
        ['id_casino','=',$id_casino],
        ['es_backup','=',$es_backup],
        ['fecha','=',$f],
        ['created_at','=',(clone $hoy)->format("Y-m-d")]
      ])
      ->whereIn('id_turno',$id_turnos)
      ->select('id_turno')->distinct()->get()->pluck('id_turno')->toArray();
      
      foreach($id_turnos as $idt){
        if(!in_array($idt,$id_turnos_con_relev)){
          $turno = Turno::find($idt);
          $apuestasController->crearRelevamientoApuestas($casino,$turno,$f);
        }
        
        $id_relevamiento_apuestas = RelevamientoApuestas::where([
          ['id_casino','=',$id_casino],
          ['es_backup','=',$es_backup],
          ['fecha','=',$f],
          ['created_at','=',(clone $hoy)->format("Y-m-d")],
          ['id_turno','=',$idt]
        ])->first()->id_relevamiento_apuestas;
        
        $fechas_sorteadas[] = [
          'fecha' => $f,
          'es_backup' => $es_backup,
          'id_turno' => $idt,
          'id_relevamiento_apuestas' => $id_relevamiento_apuestas,
        ];
      }
    }
    
    return $fechas_sorteadas;
  }
  
  public function generarZip($id_casino){
    $fechas_sorteadas = $this->generarSiNoExiste($id_casino);
    
    $casino = Casino::find($id_casino);
    $codigo_casino = $casino->codigo;  
    if(count($fechas_sorteadas) == 0) throw new Exception('No hay fechas sorteadas');  
    
    $abs_files = [];
    foreach($fechas_sorteadas as $idx => $f){
      $turno = Turno::find($f['id_turno']);
      $abs_file = $this->carpetasHelper->APUESTAS(
        "Valores_Minimos_Apuestas-$codigo_casino-{$f['fecha']}_Turno-{$turno->nro_turno}-{$turno->nombre_dia_desde}-a-{$turno->nombre_dia_hasta}.pdf"
      );
      
      $dompdf = $this->generarPlanilla($f['id_relevamiento_apuestas'],$turno,$f['fecha'],$casino);
      
      File::delete($abs_file);
      File::put($abs_file,$dompdf->output());
      $abs_files[] = $abs_file;
    }
    
    $inicio = $fechas_sorteadas[0]['fecha'];
    $fin    = $fechas_sorteadas[count($fechas_sorteadas)-1]['fecha'];
    $nombre_zip     = "Planillas-Apuestas-$codigo_casino-$inicio-al-$fin.zip";
    $abs_nombre_zip = $this->carpetasHelper->APUESTAS($nombre_zip);
    
    File::delete($abs_nombre_zip);
    Zipper::make($abs_nombre_zip)->add($abs_files)->close();
    File::delete($abs_files);
    
    return $nombre_zip;
  }
  
  public function descargarZip($nombre_zip){
    $abs_file = $this->carpetasHelper->APUESTAS($nombre_zip);
    $headers = ['Content-Type' => 'application/octet-stream'];
    return response()->download($abs_file,$nombre_zip,$headers)->deleteFileAfterSend(true);
  }

  //falta agregarle nro de pagina .-
  private function generarPlanilla( $id_relevamiento,
                                    Turno $turno, $fecha_backup, Casino $casino){
    $relevamiento = RelevamientoApuestas::find($id_relevamiento);
    $rel = new \stdClass();
    $datos =$this->obtenerDatosRelevamiento($id_relevamiento);
    $rel->paginas = $datos['paginas'];
    $rel->nro_paginas = $datos['nro_paginas'];
    $rel->totales = $datos['totales'];
    $rel->fecha = $relevamiento->created_at;
    $rel->fecha_backup = $fecha_backup;
    $rel->turno = $turno->nro_turno;
    $hora = explode(':',$relevamiento->hora_propuesta);

    $rel->hora_propuesta = $hora[0].':'.$hora[1];

    $rel->observaciones = '';
    $rel->fiscalizador = '';
    $rel->hora_ejecucion = '__:__';
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
  
  public function obtenerDatosRelevamiento($id_relevamiento){      
    $relevamiento = DB::table('relevamiento_apuestas_mesas as RA')
    ->select('DRA.nombre_juego','DRA.posiciones','DRA.id_detalle_relevamiento_apuestas',
    'DRA.codigo_mesa','DRA.nro_mesa','DRA.minimo','DRA.maximo',
    'estado_mesa.siglas_mesa','DRA.id_moneda','DRA.id_juego_mesa')
    ->join('detalle_relevamiento_apuestas as DRA','DRA.id_relevamiento_apuestas','=','RA.id_relevamiento_apuestas')
    ->leftJoin('estado_mesa','estado_mesa.id_estado_mesa','=','DRA.id_estado_mesa')
    ->where('RA.id_relevamiento_apuestas','=',$id_relevamiento)
    ->orderBy('DRA.nombre_juego','asc')
    ->groupBy(
      'DRA.nombre_juego','DRA.id_detalle_relevamiento_apuestas',
      'DRA.codigo_mesa','DRA.nro_mesa','DRA.posiciones',
      'DRA.minimo','DRA.maximo','estado_mesa.siglas_mesa',
      'DRA.id_moneda'
    )
    ->orderBy('nro_mesa','asc')
    ->get();
    
    //Agrupo las mesas en juegos por orden de aparicion del juego
    $mesas_por_juego = [];
    foreach($relevamiento as $idx => $detalle){
      $minimo = '';
      $maximo = '';
      $estado = '';
      $siglas = 'ARS__/USD__';
      if($detalle->minimo != null){
        $minimo = $detalle->minimo;
        $maximo = $detalle->maximo;
        $estado = $detalle->siglas_mesa;
      }

      if($detalle->id_moneda != null){
        $siglas = Moneda::find($detalle->id_moneda)->siglas;
      }
      
      $nombre_juego = $detalle->nombre_juego;
      if(!array_key_exists($nombre_juego,$mesas_por_juego)){
        $mesas_por_juego[$nombre_juego] = [];
      }

      $mesas_por_juego[$nombre_juego][] = [
        'nombre_juego' => $nombre_juego,
        'codigo_mesa'  => $detalle->codigo_mesa,
        'nro_mesa'     => $detalle->nro_mesa,
        'siglas'       => $siglas,
        'posiciones'   => $detalle->posiciones,
        'minimo'       => $minimo,
        'maximo'       => $maximo,
        'estado'       => $estado
      ]; 
    }
          
    //Desestructuro en mesas individuales, manteniendo el orden anterior
    $mesas_desestructuradas = [];
    foreach($mesas_por_juego as $nombre_juego => $mesas){
      foreach($mesas as $m){
        $mesas_desestructuradas[] = $m;
      }
    }
    
    //Agrupo las mesas en cantidad de filas que pueden ir en una columna
    $MAX_FILAS_POR_COL = 26;
    $COLUMNAS_POR_PAG = 2;
    
    $paginas_de_mesas = array_chunk($mesas_desestructuradas,$COLUMNAS_POR_PAG*$MAX_FILAS_POR_COL);
          
    if(count($paginas_de_mesas) == 0){//No hay mesas
      return [//@TODO: checkear que funciona
        'paginas' => [],
        'nro_paginas' => 1,
        'totales' => []
      ];
    }
    
    //A la ultima pagina le pongo otro limite porque va a tener observaciones, firma y totalizadores
    $ultima_pag = $paginas_de_mesas[count($paginas_de_mesas)-1];
    unset($paginas_de_mesas[count($paginas_de_mesas)-1]);
    
    $F_AGRUPAR_MESAS_EN_JUEGOS = function($mesas){
      $juegos = [];
      foreach($mesas as $m){
        $nombre_juego = $m['nombre_juego'];
        if(!array_key_exists($nombre_juego,$juegos)){
          $juegos[$nombre_juego] = ['juego' => $nombre_juego, 'mesas' => []];
        }
        $juegos[$nombre_juego]['mesas'][] = $m;
      }
      return $juegos;
    };
    
    $F_MESAS_A_COLUMNAS_DE_JUEGOS = function($pag_m) 
      use (&$MAX_FILAS_POR_COL,$F_AGRUPAR_MESAS_EN_JUEGOS){
        $columnas_de_mesas = array_chunk($pag_m,$MAX_FILAS_POR_COL);
        return array_map($F_AGRUPAR_MESAS_EN_JUEGOS,$columnas_de_mesas);
    };
    
    $paginas_de_columnas = array_map($F_MESAS_A_COLUMNAS_DE_JUEGOS,$paginas_de_mesas);
    
    $MAX_FILAS_POR_COL = 20;
    
    $ult_pags = array_chunk($ultima_pag,$COLUMNAS_POR_PAG*$MAX_FILAS_POR_COL);
    $ult_pags_de_cols = array_map($F_MESAS_A_COLUMNAS_DE_JUEGOS,$ult_pags);
    
    $paginas = array_merge($paginas_de_columnas,$ult_pags_de_cols);
    
    //Completo la ultima pagina con columnas vacias para que no se vea rara
    //comparada con la ultima
    {
      $cols_ult_pag = count($paginas[count($paginas)-1]);
      $fill = $COLUMNAS_POR_PAG - $cols_ult_pag;
      $paginas[count($paginas)-1] = array_merge(
        $paginas[count($paginas)-1],
        array_fill(0,$fill,[])
      );
    }
    
    $minimos = null;
    $abiertas = null;
    {
      $mins = (new ABMApuestasController)->minimosCumplidos($id_relevamiento);
      foreach($mins as $m){
        if(!empty($m->cumplieron_minimo)){
          $minimos += $m->cumplieron_minimo;
        }
        if(!empty($m->abiertas)){
          $abiertas += $m->abiertas;
        }
      }
      if(!is_null($abiertas) && is_null($minimos)){
        $minimos = 0;
      }
    }
    
    $totales = [
      [
        'texto' => 'Cantidad De Mesas Abiertas',//Fila vacia separadora
        'val' => $abiertas,
      ],
      [
        'texto' => 'Cantidad De Mesas Con Apuestas Mínimas',//Fila vacia separadora
        'val' => $minimos,
      ]
    ];
    
    return [
      'paginas' => $paginas,
      'nro_paginas' => count($paginas),
      'totales' => $totales,
    ];
  }
}
