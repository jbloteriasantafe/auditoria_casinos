<?php

namespace App\Http\Controllers\Canon;

use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use PDF;
use Dompdf\Dompdf;
use View;
use Zipper;
use File;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use App\Plataforma;
use App\Archivo;
use App\Casino;

require_once(app_path('BC_extendido.php'));

function csvstr(array $fields) : string
{
    $f = fopen('php://memory', 'r+');
    if (fputcsv($f, $fields) === false) {
        return false;
    }
    rewind($f);
    $csv_line = stream_get_contents($f);
    return rtrim($csv_line);
}

class CanonInformeController extends Controller
{
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
    
  private $u   = null;
  private $CC  = null;
  public function __construct(){   
    self::$instance = $this; 
    $this->CC = CanonController::getInstancia();
    $this->middleware(function ($request, $next) {
      $this->u = UsuarioController::getInstancia()->quienSoy()['usuario'];
      return $next($request);
    });
  }
  
  private function obtener_para_salida($id_canon,$formatear_decimal = true){
    $data = $this->CC->obtener_arr(compact('id_canon'),false);
    
    foreach(['id_canon','id_canon_variable','id_canon_fijo_mesas','id_canon_fijo_mesas_adicionales','id_archivo','id_casino','created_at','created_id_usuario','deleted_at','deleted_id_usuario','usuario','es_antiguo'] as $k){
      unset($data[$k]);
      foreach(['canon','canon_variable','canon_fijo_mesas','canon_fijo_mesas_adicionales','canon_archivo'] as $arrk){
        foreach(($data[$arrk] ?? []) as $tipo => $_){
          unset($data[$arrk][$tipo][$k]);
        }
      }
    }
    
    $data['canon'] = [[]];
    foreach($data as $k => $v){
      if(is_array($v)) continue;
      $data['canon'][0][$k] = $v;
      unset($data[$k]);
    }

    foreach($data['canon_cuenta'] as &$cc){
      unset($cc['id_canon']);
      unset($cc['id_canon_cuenta']);
      if(!isset($cc['pagos'])) continue;
      foreach($cc['pagos'] as &$cp){
        unset($cp['id_canon']);
        unset($cp['id_canon_cuenta']);
        unset($cp['id_canon_pago']);
        $data['canon_pago'] = $data['canon_pago'] ?? [];
        $data['canon_pago'][] = $cp;
      }
      unset($cc['pagos']);
    }
    
    //@HACK: Solo para MySQl, lo hago así porque se elimino una dependencia con Doctrine... no quiero tocar el composer.json
    $types = DB::table(DB::raw('INFORMATION_SCHEMA.COLUMNS'))
    ->selectRaw("table_name as 'table', column_name as col, data_type as type")
    ->whereIn('table_name',array_keys($data))
    ->get()->groupBy('table')->map(function($tcols){
      return $tcols->keyBy('col')->map(function($T){
        return $T->type;
      });
    });
        
    foreach($data as $tabla => $d){
      foreach($d as $tipo => $vals){
        foreach($vals as $col => $val){
          switch($types[$tabla][$col] ?? null){
            case 'smallint':
            case 'integer':
            case 'int':
            case 'decimal': if($formatear_decimal){
              $data[$tabla][$tipo][$col] = bc_formatear_decimal((string)$val);//number_format castea a float... lo hacemos a pata...
            }break;
            
            case 'bool':
            case 'boolean':
            case 'tinyint':{
              $data[$tabla][$tipo][$col] = intval($val)? 'SÍ' : 'NO';
            }break;
            
            default:{
              $data[$tabla][$tipo][$col] = trim($val);
            }break;
          }
        }
      }
    }
    
    return $data;
  }
  
  public function planillaPDF(Request $request){
    $datos = $this->obtener_para_salida($request->id_canon);
    $view = View::make('Canon.planillaSimple', compact('datos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'landscape');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    //$dompdf->getCanvas()->page_text(20, 815, $codigo_casino."/".$fecha, $font, 10, array(0,0,0));
    //$dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    $año_mes = $datos['canon'][0]['año_mes'];
    $casino  = $datos['canon'][0]['operador'];
    $filename = "Canon-$año_mes-$casino.csv";
    return $dompdf->stream($filename, Array('Attachment'=>0));
  }
  
  private static function rawsql_round_bankers($val){
    $abs = "ABS($val)";
    $tru = "TRUNCATE($val,2)";
    return "IF(
      ($abs-$tru) = 0.005,
      $tru+0.01*(($tru*100) % 2),
      ROUND($val,2)
    )";
  }
  
  private static function totalesCanon_prepare($agrupar_concepto_adicionales = 'sc.tipo'){
    static $prepared = null;
    if($prepared === $agrupar_concepto_adicionales){
      return 'temp_subcanons_redondeados_con_totales_con_mensuales';
    }
    
    if($agrupar_concepto_adicionales !== 'sc.tipo'){
      $agrupar_concepto_adicionales = '"'.$agrupar_concepto_adicionales.'"';
    }
    
    $tipos_conceptos_adicionales = DB::table('canon_fijo_mesas_adicionales')
    ->select('tipo')->distinct()
    ->get();
    
    DB::statement('DROP TEMPORARY TABLE IF EXISTS temp_subcanons');
    DB::statement('CREATE TEMPORARY TABLE temp_subcanons
    SELECT
      c.año_mes as año_mes,
      c.id_casino as id_casino,
      IFNULL(sc.concepto,"Total") as concepto,
      IF(sc.concepto IS NULL,6,CASE
        WHEN sc.concepto = "Paños"       THEN 0
        WHEN sc.concepto = "Adicionales" THEN 1
        '.$tipos_conceptos_adicionales->map(function($t){
          return 'WHEN sc.concepto = "'.$t->tipo.'" THEN 1';
        })
        ->implode("\r\n").'
        WHEN sc.concepto = "MTM"         THEN 2
        WHEN sc.concepto = "Bingo"       THEN 3
        WHEN sc.concepto = "JOL"         THEN 5
        ELSE 99999
      END) as orden,
      SUM(sc.beneficio) as beneficio,
      SUM(sc.bruto) as bruto,
      SUM(sc.deduccion) as deduccion,
      SUM(sc.devengado) as devengado,
      SUM(sc.determinado) as determinado,
      '.self::rawsql_round_bankers('SUM(sc.beneficio)').' as red_beneficio,
      '.self::rawsql_round_bankers('SUM(sc.bruto)').' as red_bruto,
      '.self::rawsql_round_bankers('SUM(sc.deduccion)').' as red_deduccion,
      '.self::rawsql_round_bankers('SUM(sc.devengado)').' as red_devengado,
      '.self::rawsql_round_bankers('SUM(sc.determinado)').' as red_determinado,
      MAX(c.devengado_deduccion) as canon_deduccion,
      MAX(c.devengado) as canon_devengado,
      MAX(c.determinado+c.ajuste) as canon_determinado
    FROM canon as c
    JOIN (
      SELECT 
        sc.id_canon,
        CASE
          WHEN tipo = "Maquinas" THEN "MTM"
          WHEN tipo = "Bingo"    THEN "Bingo"
          WHEN tipo = "JOL"      THEN "JOL"
          ELSE tipo
        END as concepto,
        sc.determinado_subtotal as beneficio,
        IF(sc.devengar,sc.devengado_total,NULL) as bruto,
        IF(sc.devengar,sc.devengado_deduccion,NULL) as deduccion,
        IF(sc.devengar,sc.devengado,NULL) as devengado,
        sc.determinado as determinado
      FROM canon_variable as sc
      
      UNION ALL SELECT
        sc.id_canon,
        "Paños" as concepto,
        sc.bruto as beneficio,
        IF(sc.devengar,sc.devengado_total,NULL) as bruto,
        IF(sc.devengar,sc.devengado_deduccion,NULL) as deduccion,
        IF(sc.devengar,sc.devengado,NULL) as devengado,
        sc.determinado as determinado
      FROM canon_fijo_mesas AS sc
      
      UNION ALL SELECT
        sc.id_canon,
        '.$agrupar_concepto_adicionales.' as concepto,
        0 as beneficio,
        IF(sc.devengar,sc.devengado_total,NULL) as bruto,
        IF(sc.devengar,sc.devengado_deduccion,NULL) as deduccion,
        IF(sc.devengar,sc.devengado,NULL) as devengado,
        sc.determinado as determinado
      FROM canon_fijo_mesas_adicionales AS sc
    ) as sc ON sc.id_canon = c.id_canon
    WHERE c.deleted_at IS NULL
    GROUP BY c.año_mes,c.id_casino,sc.concepto
    WITH ROLLUP
    HAVING año_mes IS NOT NULL AND id_casino IS NOT NULL');
    
    DB::statement('DROP TEMPORARY TABLE IF EXISTS temp_subcanons_total');
    DB::statement('CREATE TEMPORARY TABLE temp_subcanons_total
    SELECT
      sc.id_casino,
      sc.año_mes,
      sc.red_beneficio,
      sc.red_bruto,
      sc.red_deduccion,
      sc.red_devengado,
      sc.red_determinado
    FROM temp_subcanons as sc
    WHERE sc.concepto = "Total"');
    
    DB::statement('DROP TEMPORARY TABLE IF EXISTS temp_subcanons_total_red');
    DB::statement('CREATE TEMPORARY TABLE temp_subcanons_total_red
    SELECT
      sc.id_casino,
      sc.año_mes,
      SUM(sc.red_beneficio)   as beneficio,
      SUM(sc.red_bruto)       as bruto,
      SUM(sc.red_deduccion)   as deduccion,
      SUM(sc.red_devengado)   as devengado,
      SUM(sc.red_determinado) as determinado
    FROM temp_subcanons as sc
    WHERE sc.concepto <> "Total"
    GROUP BY sc.id_casino,sc.año_mes');
    
    DB::statement('DROP TEMPORARY TABLE IF EXISTS temp_subcanons_redondeados');
    DB::statement('CREATE TEMPORARY TABLE temp_subcanons_redondeados
    SELECT
      sc.id_casino,
      sc.año_mes,
      sc.concepto,
      sc.orden,
      sc.red_beneficio+(sc.concepto = "Paños")*(T.red_beneficio-Tred.beneficio)   as beneficio,
      sc.red_bruto+(sc.concepto = "Paños")*(T.red_bruto-Tred.bruto)       as bruto,
      sc.red_deduccion+(sc.concepto = "Paños")*(sc.canon_deduccion-Tred.deduccion)   as deduccion,
      sc.red_devengado+(sc.concepto = "Paños")*(sc.canon_devengado-Tred.devengado)   as devengado,
      sc.red_determinado+(sc.concepto = "Paños")*(sc.canon_determinado-Tred.determinado) as determinado
    FROM temp_subcanons as sc
    JOIN temp_subcanons_total as T on T.id_casino = sc.id_casino AND T.año_mes = sc.año_mes
    JOIN temp_subcanons_total_red as Tred ON Tred.id_casino = sc.id_casino AND Tred.año_mes = sc.año_mes
    WHERE sc.concepto <> "Total"');
    
    DB::statement('DROP TEMPORARY TABLE IF EXISTS temp_subcanons_redondeados_con_totales');
    DB::statement('CREATE TEMPORARY TABLE temp_subcanons_redondeados_con_totales
    SELECT *
    FROM temp_subcanons_redondeados');
    DB::statement('INSERT INTO temp_subcanons_redondeados_con_totales
    SELECT 
      id_casino,
      año_mes,
      "Total Físico" as concepto,
      4 as orden,
      SUM(beneficio) as beneficio,
      SUM(bruto) as bruto,
      SUM(deduccion) as deduccion,
      SUM(devengado) as devengado,
      SUM(determinado) as determinado
    FROM temp_subcanons_redondeados
    WHERE concepto IN ("Paños","MTM","Bingo","Adicionales"'.(
      $tipos_conceptos_adicionales->count()? 
      (',"'.$tipos_conceptos_adicionales->pluck('tipo')->implode('","').'"')
      : ''
    ).')
    GROUP BY id_casino,año_mes');
    DB::statement('INSERT INTO temp_subcanons_redondeados_con_totales
    SELECT 
      id_casino,
      año_mes,
      "Total" as concepto,
      6 as orden,
      SUM(beneficio) as beneficio,
      SUM(bruto) as bruto,
      SUM(deduccion) as deduccion,
      SUM(devengado) as devengado,
      SUM(determinado) as determinado
    FROM temp_subcanons_redondeados
    GROUP BY id_casino,año_mes');
    
    DB::statement('DROP TEMPORARY TABLE IF EXISTS temp_subcanons_redondeados_con_totales_con_mensuales');
    DB::statement('CREATE TEMPORARY TABLE temp_subcanons_redondeados_con_totales_con_mensuales
    SELECT * FROM temp_subcanons_redondeados_con_totales');
    DB::statement('INSERT INTO temp_subcanons_redondeados_con_totales_con_mensuales
    SELECT
      0,
      año_mes,
      concepto,
      MAX(orden) as orden,
      SUM(beneficio) as beneficio,
      SUM(bruto) as bruto,
      SUM(deduccion) as deduccion,
      SUM(devengado) as devengado,
      SUM(determinado) as determinado
    FROM temp_subcanons_redondeados_con_totales
    GROUP BY año_mes,concepto');
    
    $prepared = $agrupar_concepto_adicionales;
    return 'temp_subcanons_redondeados_con_totales_con_mensuales';
  }
  public function totalesCanon($año,$mes,$discriminar_adicionales){
    $table = self::totalesCanon_prepare($discriminar_adicionales? 'Adicionales' : 'Paños');
    $año_mes = str_pad($año,4,'0',STR_PAD_LEFT).'-'.str_pad($mes,2,'0',STR_PAD_LEFT).'-01';
    $ret = DB::table($table.' as tc')
    ->select('tc.*',DB::raw('IF(tc.id_casino = 0,"Total",IFNULL(cas.nombre,tc.id_casino)) as casino'))
    ->where('tc.año_mes',$año_mes)
    ->leftJoin('casino as cas','cas.id_casino','=','tc.id_casino')
    ->leftJoin(DB::raw('(
      SELECT 1 as id_casino,1 as orden
      UNION ALL
      SELECT 2 as id_casino,3 as orden
      UNION ALL
      SELECT 3 as id_casino,2 as orden
    ) as orden_cas'),'orden_cas.id_casino','=','tc.id_casino')
    ->orderBy(DB::raw('IFNULL(orden_cas.orden,100+tc.id_casino)*1000+tc.orden'),'asc')
    ->get()->groupBy('casino')->map(function($g){
      return $g->keyBy('concepto')->map(function($obj){
        $ret = ['beneficio' => null,'bruto' => null,'deduccion' => null,'devengado' => null,'determinado' => null];
        foreach($ret as $k => &$v){
          $v = $obj->{$k} ?? null;
          $v = $v === null? null : bc_formatear_decimal($v);
        }
        return $ret;
      });
    })->toArray();
        
    return $ret;
  }
  
  public function planillaDevengado(Request $request,$tipo_presupuesto = 'devengado'){
    if(!isset($request->id_canon)) return;
    
    $c_año_mes = DB::table('canon')
    ->select('año_mes')
    ->whereNull('deleted_at')
    ->where('id_canon',$request->id_canon)
    ->first();
    
    if(empty($c_año_mes)) return;
           
    $año_mes = explode('-',$c_año_mes->año_mes);
    $mes = $año_mes[1].'/'.substr($año_mes[0],2);
    
    $grupos_operadores = DB::table('canon_grupo_operador')
    ->select('id_grupo_operador','nombre')
    ->where(function($q){
      $q->where('es_individual',1)
      ->orWhere('nombre','Total');
    })
    ->orderBy('id_grupo_operador','asc')
    ->get()
    ->keyBy('id_grupo_operador')
    ->map(function($go){return $go->nombre;});
        
    $conceptos = [
      'Paños',
      'MTM',
      'Bingo',
      'Total Físico',
      'JOL',
      'Apuestas Deportivas',
      'Total'
    ];
    
    $grupos = [];
    foreach($grupos_operadores as $igo => $nombre){
      $grupos[$nombre] = [];
      foreach($conceptos as $v){
        $grupos[$nombre][$v] = CanonAgrupamientoController::getInstancia()->obtener(
          $c_año_mes->año_mes,
          $igo,
          1,
          'PDFTotales',
          $v
        );
      }
    }
    
    $tablas = [];
    switch($tipo_presupuesto){
      case 'devengado':{
        $tablas = [
          'Devengado' => 'devengado',
          'Deducción' => 'devengado_deduccion',
          'Bruto' => 'devengado_bruto'
        ];
      }break;
      case 'determinado':{
        $tablas = [
          'Determinado' => 'determinado'
        ];
      }break;
    }
    
    $datos = [];
    foreach($grupos_operadores as $gop){
      $datos[$gop] = [];
      foreach($conceptos as $c){
        $datos[$gop][$c] = [];
        foreach($tablas as $tn => $t){
          $row = $grupos[$gop][$c] ?? (new \stdClass());
          $datos[$gop][$c][$tn] = [
           'pos_red' => $row->{$t.'¬pos_red'} ?? null,
           'err_red' => $row->{$t.'¬err_red'} ?? null,
          ];
        }
      }
    }
    
    $grupos_operadores = $grupos_operadores->values();
    $tablas  = array_keys($tablas);
    $conceptos = array_values($conceptos);
    $view = View::make('Canon.planillaDevengado', compact('tipo_presupuesto','tablas','conceptos','grupos_operadores','mes','datos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    return $dompdf->stream("Devengado-Canon-$mes.pdf", Array('Attachment'=>0));
  }
  
  public function planillaDeterminado(Request $request){
    return $this->planillaDevengado($request,'determinado');
  }
  
  private function planillaInforme(string $planilla,string $tipo,string $sacar,int $id_canon){
    $datos = $this->obtener_para_salida($id_canon);
    $sacable = function($s) use ($sacar){
      return substr($s,0,strlen($sacar)) == $sacar;
    };
    $simplificable = function($s) use ($tipo){
      if(substr($s,0,strlen($tipo)) == $tipo){
        return substr($s,strlen($tipo)+1);//+1 por el guion bajo
      }
      return false;
    };
        
    foreach($datos as $c => $datos_c){
      foreach($datos_c as $tc => $datos_tc){
        unset($datos[$c][$tc]['tipo']);
        foreach($datos_tc as $k => $v){
          if($sacable($k)){
            unset($datos[$c][$tc][$k]);
          }
          $s = $simplificable($k);
          if($s !== false){
            unset($datos[$c][$tc][$k]);
            $datos[$c][$tc][$s] = $v;
          }
        }
      }
    }
    
    $view = View::make($planilla, compact('tipo','datos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    //$dompdf->getCanvas()->page_text(20, 815, $codigo_casino."/".$fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    $año_mes = $datos['canon'][0]['año_mes'];
    $casino  = $datos['canon'][0]['casino'];
    $filename = "Canon-$año_mes-$casino.pdf";
    return $dompdf->stream($filename, Array('Attachment'=>0));
  }
  
   public function planillaInformeCanonPDF(Request $request){
    $fecha_planilla = new \DateTimeImmutable();
    $data = $this->planillaInformeCanonData($request);
    if(is_string($data)) return $data;//String de Error
    if(!is_array($data)) return 'Error inesperado';
    
    
    $timestamp_planilla = $fecha_planilla->format('Y-m-d h:i:s');
    $timestamp_canon = $data['datos_canon']['created_at'];
    $usuario_canon = \App\Usuario::withTrashed()->find($data['datos_canon']['created_id_usuario'])->nombre;
    $usuario_planilla = $this->u->nombre;
    
    $view = View::make('Canon.planillaInformeCanonPDF',$data);
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(30, 818, "Ultima modificación: $timestamp_canon ($usuario_canon)", $font, 6, array(0,0,0));
    $dompdf->getCanvas()->page_text(30, 825, "Planilla generada: $timestamp_planilla ($usuario_planilla)", $font, 6, array(0,0,0));
    //$dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 9, array(0,0,0));
    $tag_fecha_planilla = $fecha_planilla->format('Ymdhis');
    $filename = "Canon-{$data['casino']}-{$data['año']}{$data['mes']}-$tag_fecha_planilla.pdf";
    return $dompdf->stream($filename, Array('Attachment'=>0));
  }
  
  public function planillaInformeCanon(Request $request){
    $data = $this->planillaInformeCanonData($request);
    if(is_string($data)) return $data;//String de Error
    if(!is_array($data)) return 'Error inesperado';
    return View::make('Canon.planillaInformeCanon',$data);
  }
  
  private function planillaInformeCanonData(Request $request){
    $canon = DB::table('canon')
    ->where('id_canon',$request->id_canon)
    ->whereNull('deleted_at')
    ->first();
    if($canon === null) return 'Canon no existente';
    
    $casino = Casino::find($canon->id_casino);
    if($casino === null) return 'Casino no existente';
    $casino = $casino->nombre;
    
    $año_mes_arr = explode('-',$canon->año_mes);
    $año = str_pad(intval($año_mes_arr[0]),4,'0',STR_PAD_LEFT);
    $mes = str_pad(intval($año_mes_arr[1]),2,'0',STR_PAD_LEFT);
    
    $abbr_casinos = collect([
      'Melincué' => 'CME',
      'Santa Fe' => 'CSF',
      'Rosario' => 'CRO',
      'TOTAL' => 'TOTAL',
    ]);
    
    $table = self::totalesCanon_prepare();
    $determinados = DB::table($table.' as tc')
    ->select('tc.concepto','tc.determinado')
    ->leftJoin('casino as cas','cas.id_casino','=','tc.id_casino')
    ->where('tc.año_mes',$canon->año_mes)
    ->where('cas.nombre',$casino)
    ->get()
    ->keyBy('concepto')
    ->map(function($tc){
      return $tc->determinado;
    });
    
    $datos_canon = $this->CC->obtener_arr(['id_canon' => $canon->id_canon]);
    //@TODO: generalizar esto... lo hago manualmente para tener una implementación funcionando
    //medio que si o sí tengo que recalcular todo de vuelta para atras por los redondeos
    $data = [];
    $data['Variable'] = [];
    $data['Variable']['Maquinas']['determinado'] = $determinados['MTM'];
    $data['Variable']['Maquinas']['alicuota'] = $datos_canon['canon_variable']['Maquinas']['alicuota'];
    $data['Variable']['Maquinas']['beneficio'] = bcdiv(//Lo calculo para atras... porque tiene el ajuste agregado...
      $data['Variable']['Maquinas']['determinado'],
      bcdiv($data['Variable']['Maquinas']['alicuota'],'100',6),
      2
    );
    
    $data['Variable']['Bingo']['determinado'] = $determinados['Bingo'];
    $data['Variable']['Bingo']['alicuota'] = $datos_canon['canon_variable']['Bingo']['alicuota'];
    $data['Variable']['Bingo']['beneficio'] = bcdiv(//Lo calculo para atras... porque tiene el ajuste agregado...
      $data['Variable']['Bingo']['determinado'],
      bcdiv($data['Variable']['Bingo']['alicuota'],'100',6),
      2
    );
    
    $data['Variable']['JOL']['determinado'] = @$determinados['JOL'];
    $data['Variable']['JOL']['alicuota'] = @$datos_canon['canon_variable']['JOL']['alicuota'];
    $data['Variable']['JOL']['beneficio'] = @bcdiv(//Lo calculo para atras... porque tiene el ajuste agregado...
      $data['Variable']['JOL']['determinado'],
      bcdiv($data['Variable']['JOL']['alicuota'],'100',6),
      2
    );
    
    $data['Variable']['Total']['determinado'] = bcadd_precise(
      bcadd_precise(
        @$data['Variable']['Maquinas']['determinado'] ?? '0',
        @$data['Variable']['Bingo']['determinado'] ?? '0'
      ),
      @$data['Variable']['JOL']['determinado'] ?? '0'
    );
    $data['Variable']['Total']['alicuota'] = null;
    $data['Variable']['Total']['beneficio'] = bcadd_precise(
      bcadd_precise(
        @$data['Variable']['Maquinas']['beneficio'] ?? '0',
        @$data['Variable']['Bingo']['beneficio'] ?? '0'
      ),
      @$data['Variable']['JOL']['beneficio'] ?? '0'
    );
    {
      $data_tcfm = $datos_canon['canon_fijo_mesas'];
      $data_tcfm = @$data_tcfm[array_keys($data_tcfm)[0]] ?? [];
      $data['Fijo'] = [];
      $data['Valores'] = [//Para la plantilla necesito los valores unificados... en cada tipo de mesa referencio por clave
        'mes' => null,
        'dia' => [],
        'hora' => []
      ];
      {
        $dmon = [];
        $dmon['Euro'] = [
          'valor' => $data_tcfm['valor_euro'] ?? null,
          'fecha_cotizacion' => $data_tcfm['determinado_fecha_cotizacion'] ?? null,
          'cotizacion' => $data_tcfm['determinado_cotizacion_euro'] ?? null,
          'pesos' => $data_tcfm['determinado_valor_euro_cotizado'] ?? null
        ];
        $dmon['Dólar'] = [
          'valor' => $data_tcfm['valor_dolar'] ?? null,
          'fecha_cotizacion' => $data_tcfm['determinado_fecha_cotizacion'] ?? null,
          'cotizacion' => $data_tcfm['determinado_cotizacion_dolar'] ?? null,
          'pesos' => $data_tcfm['determinado_valor_dolar_cotizado'] ?? null
        ];
        $data['Fijo']['Monedas'] = $dmon;
        
        $data['Valores']['mes'] = @bcadd_precise(
          $dmon['Euro']['pesos'],
          $dmon['Dólar']['pesos']
        ) ?? null;
        
        $data['Valores']['dia']['/'.$data_tcfm['dias_valor']] = null;
        $val_diario = @bcmul_precise(
          $data['Valores']['mes'] ?? null,
          $data_tcfm['factor_dias_valor'] ?? null
        ) ?? null;
        
        $kdias = '/'.$data_tcfm['dias_valor'];
        $data['Valores']['dia'][$kdias] = $val_diario;
        
        $data['Fijo']['valor_dia'] = $kdias;
      }
      {
        $data['Fijo']['Mesas'] = [];
        $data['Fijo']['Mesas']['Lunes-Jueves'] = [
          'dias' => $data_tcfm['dias_lunes_jueves'] ?? null,
          'mesas' => $data_tcfm['mesas_lunes_jueves'] ?? null,
        ];
        $data['Fijo']['Mesas']['Viernes-Sábados'] = [
          'dias' => $data_tcfm['dias_viernes_sabados'] ?? null,
          'mesas' => $data_tcfm['mesas_viernes_sabados'] ?? null,
        ];
        $data['Fijo']['Mesas']['Domingos'] = [
          'dias' => $data_tcfm['dias_domingos'] ?? null,
          'mesas' => $data_tcfm['mesas_domingos'] ?? null,
        ];
        $data['Fijo']['Mesas']['Fijos'] = [
          'dias' => $data_tcfm['dias_fijos'] ?? null,
          'mesas' => $data_tcfm['mesas_fijos'] ?? null,
        ];
        $data['Fijo']['Mesas']['Todos'] = [
          'dias' => $data_tcfm['dias_todos'] ?? null,
          'mesas' => $data_tcfm['mesas_todos'] ?? null,
        ];
        $data['Fijo']['Mesas']['Total'] = [
          'mesas' => $data_tcfm['mesas_dias'] ?? null,
          'determinado' => @$determinados['Paños'] ?? '0'
        ];
      }
    }
    {
      $data['Fijo']['Adicionales'] = [];
      $adicionales_total = [
        'horas' => '0',
        'mesas' => '0',
        'determinado' => '0'
      ];
      foreach($datos_canon['canon_fijo_mesas_adicionales'] as $tcfma => $data_tcfma){
        $aux = [
          'horas' => $data_tcfma['horas'],
          'mesas' => $data_tcfma['mesas'],
          'determinado' => $determinados[$tcfma] ?? '0'
        ];
        $adicionales_total['horas'] = bcadd_precise(
          $aux['horas'],
          $adicionales_total['horas']
        );
        $adicionales_total['mesas'] = bcadd_precise(
          $aux['mesas'],
          $adicionales_total['mesas']
        );
        $adicionales_total['determinado'] = bcadd_precise(
          $aux['determinado'],
          $adicionales_total['determinado']
        );
        
        $kdias = '/'.$data_tcfma['dias_mes'];
        $data['Valores']['dia'][$kdias] = bcround_ndigits(bcmul_precise(
          $data['Valores']['mes'] ?? null,
          $data_tcfma['factor_dias_mes'] ?? null
        ),2);
        $aux['valor_dia'] = $kdias;
        
        $khoras = '/'.$data_tcfma['dias_mes'].'/'.$data_tcfma['horas_dia'];
        $data['Valores']['hora'][$khoras] = bcround_ndigits(bcmul_precise(
          $data['Valores']['mes'] ?? null,
          $data_tcfma['factor_horas_mes'] ?? null
        ),2);
        $aux['valor_hora'] = $khoras;
        
        $data['Fijo']['Adicionales'][$tcfma] = $aux;
      }
      $data['Fijo']['Adicionales']['Total'] = $adicionales_total;
      
      $data['Fijo']['determinado'] = bcadd_precise(
        @$data['Fijo']['Mesas']['Total']['determinado'] ?? '0',
        @$data['Fijo']['Adicionales']['Total']['determinado'] ?? '0'
      ) ?? '0';
    }
    
    $data['Canon']['Físico'] = $determinados['Total Físico'];
    $data['Canon']['Online'] = bcsub_precise(
      $determinados['Total'],
      $determinados['Total Físico']
    );
    $data['Canon']['Total']  = $determinados['Total'];
    
    return [
      'data' => $data,
      'casino' => $casino,
      'año' => $año,
      'mes' => $mes,
      'abbr_casinos' => $abbr_casinos,
      'datos_canon' => $datos_canon
    ];
  }
  
  public function descargar(Request $request){
    $data = $this->buscar($request,false);
    
    $conceptos = [
      'MTM','Bingo','JOL','Paños','Adicionales'
    ];
    
    $tipo_valores = [
      'beneficio','bruto','deduccion','devengado','determinado'
    ];
    
    $arreglo_a_csv = [];
    $totales_cache = [];//Si busco para un periodo me devuelve todos los casinos por eso lo cacheo
    foreach($data as $d){
      $año_mes = explode('-',$d->año_mes);
      
      $t = null;
      if(!array_key_exists($d->año_mes,$totales_cache)){
        $totales_cache[$d->año_mes] = $this->totalesCanon(intval($año_mes[0]),intval($año_mes[1]),true);
      }
      $t = $totales_cache[$d->año_mes][$d->casino];//Deberia existir porque buscar() lo devolvio
      
      $fila = [
        'año_mes' => $d->año_mes,
        'casino'  => $d->casino,
      ];
      foreach($tipo_valores as $tval){
        foreach($conceptos as $cncpt){
          $fila[$tval.'_'.$cncpt] = ($t[$cncpt] ?? [])[$tval] ?? '0';
        }
        $fila[$tval] = ($t['Total'] ?? [])[$tval] ?? '0';
      }
      $arreglo_a_csv[] = $fila;
    }
    
    $header = array_keys($arreglo_a_csv[0] ?? []);
    
    $f = fopen('php://memory', 'r+');//https://stackoverflow.com/questions/13108157/php-array-to-csv
    fputcsv($f, $header,',','"',"\\");
    foreach ($arreglo_a_csv as $fila) {
      fputcsv($f, array_values($fila),',','"',"\\");
    }
    rewind($f);
        
    return stream_get_contents($f);
  }
  
  private static function rawsql_ranged($begin,$end,$step=1){
    $aux = $begin;
    $begin = min($begin,$end);
    $end = max($aux,$end);
    
    $ret = "( SELECT $begin as val ";
    for($i=$begin+$step;$i<=$end;$i+=$step){
      $ret.= 'UNION ALL SELECT '.$i.' ';
    }
    return $ret.')';
  }
  
  private static function array_from_pairs($arr1,$arr2){
    $ks = [];
    foreach($arr1 as $k => $_)
      $ks[$k] = 1;
    foreach($arr2 as $k => $_)
      $ks[$k] = 1;
    $ks = array_keys($ks);
    
    $ret = [];
    foreach($ks as $k)
      $ret[$k] = [$arr1[$k] ?? null,$arr2[$k] ?? null];
    
    return $ret;
  }
    
  public function descargarPlanillas(Request $request){    
    $fecha_inicio = [//@TODO: poner en valores por defecto
      'Melincué' => '2007-09-28',
      'Santa Fe' => '2008-08-11',
      'Rosario'  => '2009-10-15',
      'Santa Fe - Melincué' => null
    ];
    
    $primer_fecha = null;
    $casino = $request->casino ?? null;
    if($casino !== null){
      $primer_fecha = $fecha_inicio[$casino] ?? 'XXXX-XX-XX';
    }
    else{
      $primer_fecha = array_reduce($fecha_inicio,function($carry,$f){
        return min($carry,$f ?? $carry);
      },'XXXX-99-99');
    }
    
    $ultima_fecha = date('Y-m-d');
    
    $primer_año = intval(substr($primer_fecha,0,strlen('XXXX')));
    $ultimo_año = intval(substr($ultima_fecha,0,strlen('XXXX')));
    $primer_mes = intval(substr($primer_fecha,strlen('XXXX-'),strlen('XX')));
    $ultimo_mes = intval(substr($ultima_fecha,strlen('XXXX-'),strlen('XX')));
    $planilla  = $request->planilla ?? null;
    
    if($primer_año == 0) return 'Sin configuración de fechas de inicio';
    
    $años = [];
    if($primer_año !== null && $ultimo_año !== null){
      $años = collect(array_reverse(range($primer_año,$ultimo_año,1)));
    }
    
    $año  = $request->año ?? null;
    $año  = $año === null? null : intval($año);
    $años_sql = $año === null?
      self::rawsql_ranged($primer_año,$ultimo_año)
    : self::rawsql_ranged($año-1,$año+1);
    
    $mes  = $request->mes ?? null;
    $mes  = $mes === null? null : intval($mes);
    
    $meses_calendario = collect([null,'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']);
    unset($meses_calendario[0]);
    
    $meses = clone $meses_calendario;
    if($año == $primer_año){//Queda mas legible en dos loops, mas que unirlo
      $_mescheck = $primer_mes ?? 1;
      $_unsetting = true;
      foreach($meses as $mnum => $m){
        if($mnum == $_mescheck){
          $_unsetting = false;
        }
        if($_unsetting){
          unset($meses[$mnum]);
        }
      }
    }
    elseif($año == $ultimo_año){
      $_mescheck = $ultimo_mes ?? 12;
      $_unsetting = false;
      foreach($meses as $mnum => $m){
        if($_unsetting){
          unset($meses[$mnum]);
        }
        if($mnum == $_mescheck){
          $_unsetting = true;
        }
      }
    }
    
    //@SPEED
    //Para los mensuales resulta en 3 veces la cantidad de querys necesarias
    //Ej pido Marzo 2023
    //-> deberia solo obtener Febrero 2023, Marzo 2023, Abril 2023
    //-> devuelve Febrero 2022,23,24, Marzo 2022,23,24, Abril 2022,23,24
    //No me molesto en arreglarlo porque no creo que ralentize mucho
    $meses_sql = null;
    if($mes === null){
      $meses_sql = self::rawsql_ranged(1,12);
    }
    else{
      $_prev = ($mes <= 1? 13 : $mes)-1;
      $_prox = ($mes >= 12? 0 : $mes)+1;
      $meses_sql = "(
        SELECT $_prev as val
        UNION ALL 
        SELECT $mes as val
        UNION ALL
        SELECT $_prox as val
      )";
    }
    
    $casinos_sql = null;
    if($planilla == 'participacion'){
      $casinos_sql = '(
        SELECT 2 as id_casino,"Santa Fe" as casino,"SFE" as abbr,"CSF" as codigo,"BPLAY" as plataforma
        UNION ALL
        SELECT 1 as id_casino,"Santa Fe - Melincué" as casino,"SFE-MEL" as abbr,"CSF-CME" as codigo,"BPLAY" as plataforma
        UNION ALL
        SELECT 2 as id_casino,"Santa Fe - Melincué" as casino,"SFE-MEL" as abbr,"CSF-CME" as codigo,"BPLAY" as plataforma
        UNION ALL
        SELECT 3 as id_casino,"Rosario" as casino,"ROS" as abbr,"CRO" as codigo,"CCO" as plataforma
        UNION ALL
        SELECT 1 as id_casino,"Total" as casino,"TOTAL" as abbr,"TOTAL" as codigo,"BPLAY" as plataforma
        UNION ALL
        SELECT 2 as id_casino,"Total" as casino,"TOTAL" as abbr,"TOTAL" as codigo,"BPLAY" as plataforma
        UNION ALL
        SELECT 3 as id_casino,"Total" as casino,"TOTAL" as abbr,"TOTAL" as codigo,"CCO" as plataforma
      )';
    }
    else{
      $casinos_sql = '(
        SELECT 1 as id_casino,"Melincué" as casino,"MEL" as abbr,"CME" as codigo,"BPLAY" as plataforma
        UNION ALL
        SELECT 2 as id_casino,"Santa Fe" as casino,"SFE" as abbr,"CSF" as codigo,"BPLAY" as plataforma
        UNION ALL
        SELECT 3 as id_casino,"Rosario" as casino,"ROS" as abbr,"CRO" as codigo,"CCO" as plataforma
        UNION ALL
        SELECT 1 as id_casino,"Total" as casino,"TOTAL" as abbr,"TOTAL" as codigo,"BPLAY" as plataforma
        UNION ALL
        SELECT 2 as id_casino,"Total" as casino,"TOTAL" as abbr,"TOTAL" as codigo,"BPLAY" as plataforma
        UNION ALL
        SELECT 3 as id_casino,"Total" as casino,"TOTAL" as abbr,"TOTAL" as codigo,"CCO" as plataforma
      )';
    }
        
    $abbr_casinos = DB::table(DB::raw($casinos_sql.' as cas'))
    ->select('casino','codigo')->distinct()
    ->get()
    ->keyBy('casino')
    ->map(function($v){
      return $v->codigo;
    });
    $casinos = $abbr_casinos->keys();
    $casinos_sin_total = $casinos->filter(function($c){return $c != 'Total';});
    $abbr_casinos = $abbr_casinos->toArray();
    $plataformas = Plataforma::orderBy('id_plataforma','asc')->get();
    $relacion_plat_cas = ['CCO' => 'Rosario','BPLAY' => 'Santa Fe - Melincué'];
    
    $planillas = [
      'evolucion_historica' => 'Evolución Historica',
      'canon_total' => 'Canon Total',
      'canon_fisico_online' => 'Canon Físico-On Line',
      'participacion' => 'Particip. % Resultado CF-JOL',
      'actualizacion_valores' => 'Actualización Valores Mesas',
      'evolucion_cotizacion' => 'Evolución Cotizacion'
    ];
    $planillas_botones = [
      '' => ['planilla'],
      'evolucion_historica' => ['planilla'],
      'canon_total' => ['planilla','año'],
      'canon_fisico_online' => ['planilla','año'],
      'participacion' => ['planilla','año'],
      'actualizacion_valores' => ['planilla','casino','año'],
      'evolucion_cotizacion' => ['planilla','casino']
    ];
    $botones = [];
    $botones_elegidos = true;{
      $_pbot = $planillas_botones[$planilla] ?? [];
      $breaker = false;
      foreach($_pbot as $_p){
        switch($_p){
          case 'planilla':{
            $botones['planilla'] = self::array_from_pairs(array_keys($planillas),array_values($planillas));
          }break;
          case 'casino':{
            $botones['casino'] = self::array_from_pairs($casinos_sin_total,$casinos_sin_total);
          }break;
          case 'año':{
            $_años = $años->toArray();
            $botones['año'] = self::array_from_pairs(array_map(function($a){
              return $a;
            },$_años),$_años);
          }break;
          case 'mes':{
            $_meses = $meses->reverse();
            $botones['mes'] = self::array_from_pairs($_meses->keys(),$_meses->values());
          }break;
        }
        if(($request[$_p] ?? null) === null){//Falta elegir algun boton para la planilla, asi que corto aca
          $botones_elegidos = false;
          break;
        }
      }
    }
        
    $data = collect([]);
    $data_anual = collect([]);
    $tipos_variables_fisicos = ['Maquinas','Bingo'];
    $tipos_variables_online = ['JOL'];
    $tipos_fijos_mesas = DB::table('canon_fijo_mesas')
    ->select('tipo')->distinct()->get()->pluck('tipo')->values()->toArray();
    $tipos_fijos_mesas_adicionales = DB::table('canon_fijo_mesas_adicionales')
    ->select('tipo')->distinct()->get()->pluck('tipo')->values()->toArray();
                
    $q = DB::table(DB::raw($meses_sql.' as mes'))
    ->crossJoin(DB::raw($años_sql.' as año'))
    ->crossJoin(DB::raw($casinos_sql.' as cas'))
    ->leftJoin('canon as c',function($j){
      return $j->whereNull('c.deleted_at')
      ->on('c.id_casino','=','cas.id_casino')
      ->on(DB::raw('YEAR(c.año_mes)'),'=','año.val')
      ->on(DB::raw('MONTH(c.año_mes)'),'=','mes.val');
    })
    ->leftJoin('canon as c_yoy',function($j){
      return $j->whereNull('c_yoy.deleted_at')
      ->on('c_yoy.id_casino','=','cas.id_casino')
      ->on(DB::raw('YEAR(c_yoy.año_mes)'),'=',DB::raw('(año.val-1)'))
      ->on(DB::raw('MONTH(c_yoy.año_mes)'),'=','mes.val');
    })
    ->leftJoin('canon as c_mom',function($j){
      return $j->whereNull('c_mom.deleted_at')
      ->on('c_mom.id_casino','=','cas.id_casino')
      ->on(DB::raw('YEAR(c_mom.año_mes)'),'=', DB::raw('IF(
        mes.val<>1,
        año.val,
        año.val-1
      )'))
      ->on(DB::raw('MONTH(c_mom.año_mes)'),'=',DB::raw('IF(
        mes.val<>1,
        mes.val-1,
        12
      )'));
    })
    ->where(DB::raw('1'),'=',DB::raw($botones_elegidos? '1' : '0'));
    
    if($casino !== null){
      $q = $q->where('cas.casino','=',$casino);
    }
    
    $fisicos = [];
    $online  = [];
    $variables = [];
    $fijos = [];
    $fijos_adicionales = [];
    foreach($tipos_variables_fisicos as $tidx => $t){
      $alias = 'c_fis_v'.$tidx;
      $q = $q->leftJoin('canon_variable as '.$alias,function($j) use ($alias,$t){
        return $j->on($alias.'.id_canon','=','c.id_canon')
        ->where($alias.'.tipo','=',$t);
      });
      $q = $q->leftJoin('canon_variable as '.$alias.'_yoy',function($j) use ($alias,$t){
        return $j->on($alias.'_yoy.id_canon','=','c_yoy.id_canon')
        ->where($alias.'_yoy.tipo','=',$t);
      });
      $fisicos[] = $alias;
      $variables[] = $alias;
    }
    
    foreach($tipos_variables_online as $tidx => $t){
      $alias = 'c_ol_v'.$tidx;
      $q = $q->leftJoin('canon_variable as '.$alias,function($j) use ($alias,$t){
        return $j->on($alias.'.id_canon','=','c.id_canon')
        ->where($alias.'.tipo','=',$t);
      });
      $q = $q->leftJoin('canon_variable as '.$alias.'_yoy',function($j) use ($alias,$t){
        return $j->on($alias.'_yoy.id_canon','=','c_yoy.id_canon')
        ->where($alias.'_yoy.tipo','=',$t);
      });
      $online[] = $alias;
      $variables[] = $alias;
    }
    
    foreach($tipos_fijos_mesas as $tidx => $t){
      $alias = 'c_fis_mf'.$tidx;
      $q = $q->leftJoin('canon_fijo_mesas as '.$alias,function($j) use ($alias,$t){
        return $j->on($alias.'.id_canon','=','c.id_canon')
        ->where($alias.'.tipo','=',$t);
      });
      $q = $q->leftJoin('canon_fijo_mesas as '.$alias.'_yoy',function($j) use ($alias,$t){
        return $j->on($alias.'_yoy.id_canon','=','c_yoy.id_canon')
        ->where($alias.'_yoy.tipo','=',$t);
      });
      $fisicos[] = $alias;
      $fijos[] = $alias;
    }
    
    foreach($tipos_fijos_mesas_adicionales as $tidx => $t){
      $alias = 'c_fis_mfa'.$tidx;
      $q = $q->leftJoin('canon_fijo_mesas_adicionales as '.$alias,function($j) use ($alias,$t){
        return $j->on($alias.'.id_canon','=','c.id_canon')
        ->where($alias.'.tipo','=',$t);
      });
      $q = $q->leftJoin('canon_fijo_mesas_adicionales as '.$alias.'_yoy',function($j) use ($alias,$t){
        return $j->on($alias.'_yoy.id_canon','=','c_yoy.id_canon')
        ->where($alias.'_yoy.tipo','=',$t);
      });
      $fisicos[] = $alias;
      $fijos_adicionales[] = $alias;
    }
    
    $canon_fisico = 'ROUND('.implode('+',array_map(function($t){
      return "IFNULL(SUM($t.determinado),0)";
    },$fisicos)).'+AVG(c.ajuste),2)';
    
    $canon_online = 'ROUND('.implode('+',array_map(function($t){
      return "IFNULL(SUM($t.determinado),0)";
    },$online)).',2)';
          
    if($planilla == 'evolucion_historica'){
      $sel_aggr = 'SUM(c.devengado) as devengado,
      SUM(c.determinado+c.ajuste) as canon,
      SUM(c_yoy.devengado) as yoy_devengado,
      SUM(c_yoy.determinado+c_yoy.ajuste) as yoy_canon,
      SUM(c_mom.devengado) as mom_devengado,
      SUM(c_mom.determinado+c_mom.ajuste) as mom_canon,
      ROUND(100*(SUM(c.devengado)/NULLIF(SUM(c_yoy.devengado),0)-1),2) as variacion_anual_devengado,
      ROUND(100*(SUM(c.devengado)/NULLIF(SUM(c_mom.devengado),0)-1),2) as variacion_mensual_devengado,
      ROUND(100*(SUM(c.determinado+c.ajuste)/NULLIF(SUM(c_yoy.determinado+c_yoy.ajuste),0)-1),2) as variacion_anual_canon,
      ROUND(100*(SUM(c.determinado+c.ajuste)/NULLIF(SUM(c_mom.determinado+c_mom.ajuste),0)-1),2) as variacion_mensual_canon,
      (SUM(c.determinado)-SUM(c.devengado)) as diferencia,
      ROUND(100*(1-SUM(c.devengado)/NULLIF(SUM(c.determinado),0)),2) as variacion_sobre_devengado';
    }
    elseif($planilla == 'actualizacion_valores' || $planilla == 'evolucion_cotizacion'){
      $bruto = '('.implode('+',array_map(function($t){
        return "IFNULL(SUM({$t}.bruto),0)";
      },$fijos)).')';
      $bruto_yoy = '('.implode('+',array_map(function($t){
        return "IFNULL(SUM({$t}_yoy.bruto),0)";
      },$fijos)).')';
      
      $fecha_cotizacion = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}.determinado_fecha_cotizacion";
      },$fijos)).'))';
      $fecha_cotizacion_yoy = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}_yoy.determinado_fecha_cotizacion";
      },$fijos)).'))';
      
      $cotizacion_euro = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}.determinado_cotizacion_euro";
      },$fijos)).'))';
      $cotizacion_dolar = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}.determinado_cotizacion_dolar";
      },$fijos)).'))';
      $cotizacion_euro_yoy = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}_yoy.determinado_cotizacion_euro";
      },$fijos)).'))';
      $cotizacion_dolar_yoy = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}_yoy.determinado_cotizacion_dolar";
      },$fijos)).'))';
      
      $valor_euro = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}.valor_euro";
      },$fijos)).'))';
      $valor_dolar = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}.valor_dolar";
      },$fijos)).'))';
      $valor_euro_yoy = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}_yoy.valor_euro";
      },$fijos)).'))';
      $valor_dolar_yoy = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}_yoy.valor_dolar";
      },$fijos)).'))';
      
      $bruto_euro = "($bruto/2/$cotizacion_euro)";
      $bruto_dolar = "($bruto/2/$cotizacion_dolar)";
      $bruto_euro_yoy = "($bruto_yoy/2/$cotizacion_euro_yoy)";
      $bruto_dolar_yoy = "($bruto_yoy/2/$cotizacion_dolar_yoy)";
      $variacion_cotizacion_euro = "100*($cotizacion_euro/$cotizacion_euro_yoy - 1)";
      $variacion_cotizacion_dolar = "100*($cotizacion_dolar/$cotizacion_dolar_yoy - 1)";
      $variacion_euro = "100*($bruto_euro/$bruto_euro_yoy-1)";
      $variacion_dolar = "100*($bruto_dolar/$bruto_dolar_yoy-1)";
      
      $sel_aggr = "
        $bruto as bruto,
        $bruto_yoy as bruto_yoy,
        $fecha_cotizacion as fecha_cotizacion,
        $fecha_cotizacion_yoy as fecha_cotizacion_yoy,
        $cotizacion_euro as cotizacion_euro,
        $cotizacion_dolar as cotizacion_dolar,
        $cotizacion_euro_yoy as cotizacion_euro_yoy,
        $cotizacion_dolar_yoy as cotizacion_dolar_yoy,
        ROUND($bruto_euro,2) as bruto_euro,
        ROUND($bruto_dolar,2) as bruto_dolar,
        ROUND($bruto_euro_yoy,2) as bruto_euro_yoy,
        ROUND($bruto_dolar_yoy,2) as bruto_dolar_yoy,
        ROUND($variacion_cotizacion_euro,3) as variacion_cotizacion_euro,
        ROUND($variacion_cotizacion_dolar,3) as variacion_cotizacion_dolar,
        ROUND($variacion_euro,3) as variacion_euro,
        ROUND($variacion_dolar,3) as variacion_dolar,
        $valor_euro as valor_euro,
        $valor_dolar as valor_dolar,
        $valor_euro_yoy as valor_euro_yoy,
        $valor_dolar_yoy as valor_dolar_yoy
      ";
    }
    elseif($planilla == 'canon_total'){
      $sel_aggr = 'SUM(c.determinado+c.ajuste) as canon_total,
      100*(SUM(c.determinado+c.ajuste)/NULLIF(SUM(c_yoy.determinado+c.ajuste),0)-1) as variacion_anual,
      100*(SUM(c.determinado+c.ajuste)/NULLIF(SUM(c_mom.determinado+c.ajuste),0)-1) as variacion_mensual';
    }
    elseif($planilla == 'canon_fisico_online'){
      $sel_aggr = $canon_fisico.' as canon_fisico,
      '.$canon_online.' as canon_online,
      SUM(c.determinado+c.ajuste) as canon_total,
      100*(SUM(c.determinado+c.ajuste)/NULLIF(SUM(c_yoy.determinado+c_yoy.ajuste),0)-1) as variacion_anual,
      100*(SUM(c.determinado+c.ajuste)/NULLIF(SUM(c_mom.determinado+c_mom.ajuste),0)-1) as variacion_mensual';
    }
    elseif($planilla == 'participacion'){
      $ganancia_total_variable = '('.implode('+',array_map(function($t){
        return "IFNULL(SUM($t.determinado_subtotal),0)";//Con el impuesto sacado
      },$variables)).')';
      
      $ganancia_online_variable = '('.implode('+',array_map(function($t){
        return "IFNULL(SUM($t.determinado_subtotal),0)";//Con el impuesto sacado
      },$online)).')';
            
      $ganancia_fisico_fijo = '('.implode('+',array_map(function($t){
        return "IFNULL(SUM($t.bruto),0)";//Con el impuesto sacado
      },$fijos)).')';
            
      $ganancia_online = "($ganancia_online_variable)";
      $ganancia_total  = "($ganancia_total_variable+$ganancia_fisico_fijo)";
      $ganancia_fisico = "($ganancia_total-$ganancia_online)";
      
      $porcentaje_fisico = "ROUND(100*$ganancia_fisico/NULLIF($ganancia_total,0),2)";
      $porcentaje_online = "ROUND(100*$ganancia_online/NULLIF($ganancia_total,0),2)";
    
      $ganancia_cco = '('.implode('+',array_map(function($t){
        return "IFNULL(SUM(IF(cas.plataforma = 'CCO',$t.determinado_subtotal,0)),0)";
      },$online)).')';
      
      $ganancia_bplay = '('.implode('+',array_map(function($t){
        return "IFNULL(SUM(IF(cas.plataforma = 'BPLAY',$t.determinado_subtotal,0)),0)";
      },$online)).')';
      
      $porcentaje_CCO = "ROUND(100*$ganancia_cco/NULLIF($ganancia_online,0),2)";
      $porcentaje_BPLAY = "ROUND(100*$ganancia_bplay/NULLIF($ganancia_online,0),2)";
      //Como son dos porcentajes el rounding uno siempre compensa tal que sea 100 la suma
      $sel_aggr = "$canon_online as canon_online,
      $porcentaje_fisico as porcentaje_fisico,
      $porcentaje_online as porcentaje_online,
      $porcentaje_CCO as porcentaje_CCO,
      $porcentaje_BPLAY as porcentaje_BPLAY";
    }
    else {
      $sel_aggr = 'NULL as no_sel';
    }
    
    $data = (clone $q)
    ->selectRaw('
      cas.casino,
      cas.codigo,
      cas.abbr,
      año.val as año,
      mes.val as mes,
      '.$sel_aggr
    )
    ->groupBy(DB::raw('cas.casino,cas.codigo,cas.abbr,año.val,mes.val'))
    ->orderBy('año','asc')
    ->orderBy('mes','asc')
    ->get()
    ->merge(//Por casino por mes
      (clone $q)
      ->selectRaw('
        cas.casino,
        cas.codigo,
        cas.abbr,
        0 as año,
        mes.val as mes,
        '.$sel_aggr
      )
      ->groupBy(DB::raw('cas.casino,cas.codigo,cas.abbr,mes.val'))
      ->orderBy('mes','asc')
      ->get()
    )
    ->merge(//Por casino por año
      (clone $q)
      ->selectRaw('
        cas.casino,
        cas.codigo,
        cas.abbr,
        año.val as año,
        0 as mes,
        '.$sel_aggr
      )
      ->groupBy(DB::raw('cas.casino,cas.codigo,cas.abbr,año.val'))
      ->orderBy('año','asc')
      ->get()
    )
    ->merge(//Por casino
      (clone $q)
      ->selectRaw('
        cas.casino,
        cas.codigo,
        cas.abbr,
        0 as año,
        0 as mes,
        '.$sel_aggr
      )
      ->groupBy(DB::raw('cas.casino,cas.codigo,cas.abbr'))
      ->get()
    )
    ->transform(function($d) use ($fecha_inicio){
      $fini = $fecha_inicio[$d->casino] ?? null;
      $d->fecha_inicio = $fini;
      if($fini !== null && $d->año > 0 && $d->mes > 0){
        $d->rel_mes = ($d->mes-intval(substr($fini,strlen('XXXX-'),2)))%12;
        $d->rel_mes += $d->rel_mes < 0? 12 : 0;
        
        $aux = new \DateTime("{$d->año}-{$d->mes}-01");
        $aux->modify('-'.$d->rel_mes.' months');
        $aux = intval($aux->format('Y'))-intval(substr($fini,0,strlen('XXXX')));
        
        $d->rel_mes += 1;//Lo paso a [1,12]
        $d->rel_año = $aux+1;//Base-1
        
        if($d->rel_año <= 0){//Si es anterior a la fecha de inicio lo nulifico
          $d->rel_mes = null;
          $d->rel_año = null;
        }
      }
      else{
        $d->rel_mes = null;
        $d->rel_año = null;
      }
      return $d;
    })
    ->groupBy('casino')
    ->map(function($d_cas){
      return $d_cas->groupBy('año')
      ->map(function($d_cas_año){
        return $d_cas_año->keyBy('mes');
      });
    });
    
    $parametros = $request->all();
    return View::make('Canon.planillaPlanillas',compact(
      'fecha_inicio',
      'primer_fecha','ultima_fecha',
      'primer_año','ultimo_año',
      'primer_mes','ultimo_mes',
      'botones','botones_elegidos','parametros','data','data_plataformas','años_planilla','años','año','año_anterior','meses','meses_calendario','planillas','planilla','es_anual','casinos','abbr_casinos','plataformas','relacion_plat_cas'));
  }
}
