<?php

namespace App\Http\Controllers\Mesas\Importaciones\Mesas;

use Auth;
use Session;
use Illuminate\Http\Request;
use Response;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;

use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;

use App\Usuario;
use App\Mesas\CSVImporter;
use App\Casino;
use App\Relevamiento;
use App\SecRecientes;
use App\Http\Controllers\RolesPermissions\RoleFinderController;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\Mesas\Mesa;
use App\Mesas\Moneda;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;
use App\Mesas\Cierre;

use App\Mesas\ImportacionDiariaMesas;
use App\Mesas\DetalleImportacionDiariaMesas;

use App\Mesas\ComandoEnEspera;

use \DateTime;
use \DateInterval;
use Carbon\Carbon;

use Dompdf\Dompdf;
use PDF;

class ImportadorController extends Controller
{
  private static $atributos = [
    'id_mesa_de_panio' => 'Identificacion de la mesa',
    'nro_mesa' => 'Número de Mesa',
    'nombre' => 'Nombre de Mesa',
    'descripcion' => 'Descripción',
    'id_tipo_mesa' => 'Tipo de Mesa',
    'id_juego_mesa' => 'Juego de Mesa',
    'id_casino' => 'Casino',
    'id_moneda' => 'Moneda',
    'id_sector_mesas' => 'Sector',
    'nombre_juego' => 'Nombre de Juego',
    'cod_identificacion' => 'Código de Identificación',
    'siglas' => 'Código de Identificación',
  ];

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware(['tiene_permiso:m_importar']);//rol a definir por gusti-> en ppio AUDITOR
  }

  public function buscarTodo(){
    $casinos = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->casinos;
    $monedas = Moneda::all();
    return view('Importaciones.importacionDiaria',['casinos'=>$casinos,'moneda'=>$monedas]);
  }

  public function buscar($id_importacion){
   $importacion = ImportacionDiariaMesas::find($id_importacion);
   return [
             'importacion' => $importacion,
             'casino' => $importacion->casino,
             'detalles' => $importacion->detalles()->get(),
             'moneda' => $importacion->moneda
           ];
 }
 public function buscarPorTipoMesa($id_importacion,$t_mesa = null){
  //Si no manda mesa, retorno las que no se encontraron su tipo
  $importacion =  ImportacionDiariaMesas::find($id_importacion);
  $detalles = [];
  foreach($importacion->detalles as $d){
    $juego = $d->juego_mesa();
    $tipo_mesa = is_null($juego)? null : $juego->tipo_mesa;
    if(is_null($tipo_mesa)){
      if(is_null($t_mesa)) $detalles[] = $d;
    }
    else if ($tipo_mesa->descripcion == $t_mesa) $detalles[] = $d;
  }
  $detalles = collect($detalles)->map(function($v,$idx){
    $cierre = $v->cierre;
    $estado_cierre = is_null($cierre)? 'SIN RELEVAR' : $cierre->estado_cierre->descripcion;
    
    $cierre_anterior = $v->cierre_anterior;
    $estado_cierre_anterior = is_null($cierre_anterior)? 'SIN RELEVAR' : $cierre_anterior->estado_cierre->descripcion;

    $v = $v->toArray();
    $v['estado_cierre'] = $estado_cierre;
    $v['estado_cierre_anterior'] = $estado_cierre_anterior;
    return $v;
  });
  return ['importacion' => $importacion,'casino' => $importacion->casino,'detalles' => $detalles,'moneda' => $importacion->moneda];
}

public function importarDiario(Request $request){
    $validator =  Validator::make($request->all(),[
      'id_casino' => 'required|exists:casino,id_casino',
      'id_moneda' => 'required|exists:moneda,id_moneda',
      'fecha' => 'required|date',
      'cotizacion_diaria' => ['nullable','required_if:id_moneda,2','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
      'archivo' => 'required|file',
    ], array(), self::$atributos)->after(function($validator){
      if($validator->errors()->any()) return;
      $fecha = $validator->getData()['fecha'];
      if($fecha >= date('Y-m-d')){
        $validator->errors()->add('fecha', 'No es posible importar una fecha futura.');
        return;
      }
    })->validate();

    DB::transaction(function() use ($request,&$importacion){
      $id_casino = $request->id_casino;
      $id_moneda = $request->id_moneda;
      $fecha     = $request->fecha;

      $misma_fecha = ImportacionDiariaMesas::where([
        ['id_casino','=',$id_casino],['id_moneda','=',$id_moneda],['fecha','=',$fecha]]
      )->whereNull('deleted_at')->get();
      if(count($misma_fecha) > 0){
        foreach($misma_fecha as $imp){
          $imp->detalles()->delete();
          $imp->delete();
        }
      }

      $importacion = new ImportacionDiariaMesas;
      $importacion->fecha = $fecha;
      $importacion->moneda()->associate($id_moneda);
      $importacion->casino()->associate($id_casino);
      if(!empty($request->cotizacion_diaria)){
        $importacion->cotizacion = str_replace(',','.',$request->cotizacion_diaria);
      }
      $importacion->validado = 0;
      $importacion->save();
      $iid = $importacion->id_importacion_diaria_mesas;
  
      $pdo = DB::connection('mysql')->getPdo();
      DB::connection()->disableQueryLog();
      $path = $request->archivo->getRealPath();
      /*
        row_1 nombre juegos
        row_2 nro_mesa
        row_3 drop
        row_4 utilidad
        row_5 fill//reposiciones
        row_6 credit//retiros
      */
      $query = sprintf("LOAD DATA local INFILE '%s'
      INTO TABLE filas_csv_mesas_bingos
      FIELDS TERMINATED BY ';'
      OPTIONALLY ENCLOSED BY '\"'
      ESCAPED BY '\"'
      LINES TERMINATED BY '\\n'
      IGNORE 1 LINES
      (@0,@1,@2,@3,@4,@5)
      SET id_archivo = '%d',
          row_1      = @0,
          row_2      = @1,
          row_3      = CAST(REPLACE(REPLACE(@2,'.',''),',','.') as DECIMAL(15,2)),
          row_4      = CAST(REPLACE(REPLACE(@3,'.',''),',','.') as DECIMAL(15,2)),
          row_5      = CAST(REPLACE(REPLACE(@4,'.',''),',','.') as DECIMAL(15,2)),
          row_6      = CAST(REPLACE(REPLACE(@5,'.',''),',','.') as DECIMAL(15,2))",
          $path,$iid, $fecha
      );

      $pdo->exec($query);

      //@HACK: saldo_fichas calculado a pata hasta que lo manden en el archivo
      $crea_detalles = sprintf("INSERT INTO detalle_importacion_diaria_mesas
        (id_importacion_diaria_mesas, siglas_juego, nro_mesa, droop, utilidad, reposiciones, retiros, 
        saldo_fichas, 
        diferencia_cierre, utilidad_calculada)
        SELECT 
        csv.id_archivo, csv.row_1, csv.row_2, csv.row_3, csv.row_4, csv.row_5, csv.row_6,
        csv.row_4 - (csv.row_3 + csv.row_5 - csv.row_6),
        NULL,NULL
        FROM filas_csv_mesas_bingos as csv
        WHERE csv.id_archivo = '%d' AND csv.row_1 <> '' AND csv.row_2 <> '' AND SUBSTR(csv.row_1,0,7) <> 'TOTALES';",
        $iid);

      $pdo->exec($crea_detalles);

      $setea_totales = sprintf("UPDATE importacion_diaria_mesas i,
      (
        SELECT SUM(d.droop) as droop    , SUM(d.utilidad) as utilidad, SUM(d.reposiciones) as reposiciones,
               SUM(d.retiros) as retiros, SUM(d.saldo_fichas) as saldo_fichas
        FROM detalle_importacion_diaria_mesas d
        WHERE d.id_importacion_diaria_mesas = '%d'
        GROUP BY d.id_importacion_diaria_mesas
      ) total
      SET i.total_diario              = IFNULL(total.droop,0)       , i.utilidad_diaria_total = IFNULL(total.utilidad,0),
          i.total_diario_reposiciones = IFNULL(total.reposiciones,0), i.total_diario_retiros  = IFNULL(total.retiros,0),
          i.saldo_diario_fichas       = IFNULL(total.saldo_fichas,0)
      WHERE i.id_importacion_diaria_mesas = '%d'",$iid,$iid);

      $pdo->exec($setea_totales);
  
      $importacion->nombre_csv = $request->archivo->getClientOriginalName();
      $importacion->save();

      //$this->calcularDiffIDM($iid);
      //DB::table('filas_csv_mesas_bingos')->where('id_archivo','=',$importacion->id_importacion_diaria_mesas)->delete();
    });
    return 1;
  }

  //fecha casino moneda
  public function filtros(Request $request){
    $fecha = isset($request->fecha)? $request->fecha : date('Y-m-d');
    $fecha = new \DateTime($fecha);
    $fecha->modify('first day of this month');
    $fecha = $fecha->format('Y-m-d');

    $mes = date('m',strtotime($fecha));
    $arreglo = [];
    $reglas = [['id_moneda','=',$request->id_moneda],['id_casino','=',$request->id_casino]];
    while(date('m',strtotime($fecha)) == $mes){
      $importacion = ImportacionDiariaMesas::where($reglas)->where('fecha','=',$fecha)->whereNull('deleted_at')->first();
      $tiene_cierre = false;
      if(!is_null($importacion)){
        $tiene_cierre = true;
        $detalles = $importacion->detalles()->orderBy('siglas_juego','asc')->orderBy('nro_mesa','asc')->get();
        foreach($detalles as $d){
          if(!$tiene_cierre) break;
          $tiene_cierre &= !is_null($d->cierre) && !is_null($d->cierre_anterior);
        }
      }
      $arreglo[] = ["fecha" => $fecha,"importacion" => $importacion,"tiene_cierre" => $tiene_cierre];
      $fecha = date('Y-m-d' , strtotime($fecha . ' + 1 days'));
    }

    if($request->sort_by["columna"] == "fecha" && $request->sort_by["orden"] == "desc"){
      $arreglo = array_reverse($arreglo);
    }

    return ["importaciones" => $arreglo,
            "casino" => Casino::find($request->id_casino)->nombre, 
            "moneda" => Moneda::find($request->id_moneda)->siglas];
  }

  public function guardarObservacion(Request $request){
    $validator = Validator::make($request->all(),[
      'id_importacion' => 'required|exists:importacion_diaria_mesas,id_importacion_diaria_mesas',
      'observacion' => 'nullable'
    ], array(), self::$atributos)->after(function($validator){  })->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
     }

    $importacion = ImportacionDiariaMesas::find($request->id_importacion);
    $importacion->observacion  = $request->observacion;
    $importacion->validado = 1;
    $importacion->save();
    return response()->json(['ok' => true], 200);
  }

  /*
  * Busca las imp. diarias no validadas, que tengan diferencias y recalcula las diferencias
  */
  public function calcularDiffIDM($id_importacion_diaria_mesas = null){
    //Si nos manda el ID hacemos el procedimiento solo para esa importación, sino para todas.
    $datos = DB::table('importacion_diaria_mesas as imp')
    ->select('imp.id_importacion_diaria_mesas','cierre_mesa.id_cierre_mesa')
    ->join('cierre_mesa', function($join){
      $join->on('cierre_mesa.id_mesa_de_panio','=','det.id_mesa_de_panio')
      ->on('cierre_mesa.fecha','=','det.fecha');
    })
    ->where('imp.diferencias','<>',0)->where('imp.validado','=','0')->whereNull('imp.deleted_at');
    if(!is_null($id_importacion_diaria_mesas)) $datos = $datos->where('imp.id_importacion_diaria_mesas','=',$id_importacion_diaria_mesas);
    $datos = $datos->orderBy('imp.fecha','asc')->get();
    //por cada importacion
    foreach ($datos as $imp){
      $detalles = DetalleImportacionDiariaMesas::where('id_importacion_diaria_mesas','=',$imp->id_importacion_diaria_mesas)->get();
      $cierre = Cierre::find($imp->id_cierre_mesa);
      //busco el cierre anterior
      $last_cierre = Cierre::where('fecha','<',$cierre->fecha)->where('id_mesa_de_panio','=',$cierre->id_mesa_de_panio)
      ->orderBy('fecha','desc')->get()->first();
      $diferencias = 0;
      foreach($detalles as $d){
        //Setea los cierres al detalle y recalcula la utilidad
        $diferencias += $this->calcularDifCierresImp($cierre,$last_cierre,$d) > 0;
      }
      $importacion = ImportacionDiariaMesas::find($imp->id_importacion_diaria_mesas);
      $imp->diferencias = $diferencias;
      $imp->save();
      $this->actualizarTotalesImpDiaria($importacion->id_importacion_diaria_mesas);
    }
  }

  //Setea los cierres al detalle y recalcula la utilidad
  private function calcularDifCierresImp($cierre,$last_cierre,&$detalle_importacion){   
    $dif_cierres      = 0;
    $id_ultimo_cierre = null;
    if(!is_null($last_cierre)){//Encontramos un cierre anterior al que tiene
      $dif_cierres      = $cierre->total_pesos_fichas_c - $last_cierre->total_pesos_fichas_c;
      $id_ultimo_cierre = $last_cierre->id_cierre_mesa;
    }
    $detalle_importacion->saldo_fichas       = $dif_cierres;
    //UTILIDAD CALCULADA = (Cx+1 - Cx) + DROP - FILL + CREDIT
    $calculado  = $dif_cierres + $detalle_importacion->droop - $detalle_importacion->reposiciones + $detalle_importacion->retiros;
    $diferencia = abs($calculado - $detalle_importacion->utilidad);
    $detalle_importacion->utilidad_calculada = $calculado;
    $detalle_importacion->diferencia_cierre  = $diferencia;
    $detalle_importacion->id_ultimo_cierre   = $id_ultimo_cierre;
    $detalle_importacion->id_cierre_mesa     = $cierre->id_cierre_mesa;
    $detalle_importacion->save();
    return $diferencia;
  }

  public function actualizarTotalesImpDiaria($id_importacion_diaria_mesas){
    $imp = ImportacionDiariaMesas::find($id_importacion_diaria_mesas);
    $total_diario = 0 ;
    $diferencias = 0;
    $utilidad_diaria_calculada = 0;
    $utilidad_diaria_total = 0;
    $saldo_diario_fichas = 0;
    $total_diario_retiros = 0;
    $total_diario_reposiciones = 0;
    foreach ($imp->detalles as $datos_mesa) {
      $total_diario+= $datos_mesa->droop;
      $diferencias+= $datos_mesa->diferencia_cierre;
      $utilidad_diaria_calculada+= $datos_mesa->utilidad_calculada;
      $utilidad_diaria_total+= $datos_mesa->utilidad;
      $saldo_diario_fichas+= $datos_mesa->saldo_fichas;
      $total_diario_retiros+= $datos_mesa->retiros;
      $total_diario_reposiciones+= $datos_mesa->reposiciones;
    }
    $imp->total_diario = $total_diario;
    $imp->diferencias = $diferencias;
    $imp->utilidad_diaria_calculada = $utilidad_diaria_calculada;
    $imp->utilidad_diaria_total = $utilidad_diaria_total;
    $imp->saldo_diario_fichas = $saldo_diario_fichas;
    $imp->total_diario_retiros = $total_diario_retiros;
    $imp->total_diario_reposiciones = $total_diario_reposiciones;
    $imp->save();
  }

  public function eliminar($id)
  {
    $imp = ImportacionDiariaMesas::find($id);
    foreach ($imp->detalles as $d) {
      $d->delete();
    }
    ImportacionDiariaMesas::destroy($id);
    return 1;
  }

  public function imprimirDiario($id_importacion){
    $controllerDiarias = new ImportadorController;
    $importacion = ImportacionDiariaMesas::find($id_importacion);
    $det_importacion = $importacion->detalles()->orderBy('siglas_juego','asc')->orderBy('nro_mesa','asc')->get();
    $casino = $importacion->casino;

    $view = view('Informes.informeDiario', compact(['importacion','det_importacion','casino']));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view);
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $importacion->casino->codigo."/".$importacion->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf->stream('informe_diario_'.$importacion->casino->codigo.'_'.$importacion->fecha.'.pdf', Array('Attachment'=>0));
  }

  public function ajustarDetalle(Request $request){
    $validator =  Validator::make($request->all(),[
      'id_detalle_importacion_diaria_mesas' => 'required|exists:detalle_importacion_diaria_mesas,id_detalle_importacion_diaria_mesas'
    ], array(), self::$atributos)->validate();
    $dimp = DetalleImportacionDiariaMesas::find($request->id_detalle_importacion_diaria_mesas);
    $dimp->ajuste_fichas = $request->ajuste_fichas;
    $dimp->observacion = $request->observacion;
    $dimp->save();
    return $dimp;
  }
}
