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

  private static $instance;
  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new ImportadorController();
    }
    return self::$instance;
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
    if($t_mesa == "TODOS") $detalles[] = $d;
    else{
      $juego = $d->juego_mesa();
      $tipo_mesa = is_null($juego)? null : $juego->tipo_mesa;
      if(is_null($tipo_mesa) && is_null($t_mesa)) $detalles[] = $d;
      else if($tipo_mesa->descripcion == $t_mesa) $detalles[] = $d;
    }
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
        saldo_fichas)
        SELECT 
        csv.id_archivo, csv.row_1, csv.row_2, csv.row_3, csv.row_4, csv.row_5, csv.row_6,
        (csv.row_4 - csv.row_3 + csv.row_5 - csv.row_6)
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
      SET i.droop        = IFNULL(total.droop,0)       , i.utilidad = IFNULL(total.utilidad,0),
          i.reposiciones = IFNULL(total.reposiciones,0), i.retiros  = IFNULL(total.retiros,0),
          i.saldo_fichas = IFNULL(total.saldo_fichas,0)
      WHERE i.id_importacion_diaria_mesas = '%d'",$iid,$iid);

      $pdo->exec($setea_totales);
  
      $importacion->nombre_csv = $request->archivo->getClientOriginalName();
      $importacion->save();
      $importacion->actualizarCierres();

      DB::table('filas_csv_mesas_bingos')->where('id_archivo','=',$importacion->id_importacion_diaria_mesas)->delete();
    });
    return 1;
  }

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

  public function guardarImportacionDiaria(Request $request){
    $validator = Validator::make($request->all(),[
      'id_importacion' => 'required|exists:importacion_diaria_mesas,id_importacion_diaria_mesas',
      'observacion' => 'nullable|string|max:200'
    ], ['max' => 'El valor es muy grande'], self::$atributos)->after(function($validator){  })->validate();
    DB::transaction(function() use ($request){
      $importacion = ImportacionDiariaMesas::find($request->id_importacion);
      $importacion->observacion  = $request->observacion;
      $importacion->validado = 1;
      $importacion->save();
    });
    return response()->json(['ok' => true], 200);
  }

  public function eliminar($id){
    DB::transaction(function() use ($id){
      $imp = ImportacionDiariaMesas::find($id);
      foreach ($imp->detalles as $d) {
        $d->delete();
      }
      ImportacionDiariaMesas::destroy($id);
    });
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
      'id_detalle_importacion_diaria_mesas' => 'required|exists:detalle_importacion_diaria_mesas,id_detalle_importacion_diaria_mesas',
      'ajuste' => 'nullable|numeric',
      'observacion' => 'nullable|string|max:64',
    ],[
      'required' => 'No puede estar vacio',
      'max' => 'El valor es muy grande',
      'numeric' => 'El valor tiene que ser numérico',
    ], self::$atributos)->validate();
    $dimp = DetalleImportacionDiariaMesas::find($request->id_detalle_importacion_diaria_mesas);
    $dimp->ajuste_fichas = $request->ajuste_fichas;
    $dimp->observacion = $request->observacion;
    $dimp->save();
    return $dimp;
  }

  public function mensualPorMonedaPorJuego($id_casino,$date){
    $por_moneda = array();
    foreach(Moneda::all() as $moneda){
      $detalles = ImportacionDiariaMesas::whereYear('fecha','=',$date[0])
      ->whereMonth('fecha','=',$date[1])
      ->where('id_casino','=',$id_casino)
      ->where('id_moneda','=',$moneda->id_moneda)
      ->whereNull('deleted_at')
      ->orderBy('fecha','asc')->get()->toArray();//si no hago toArray me retorna vacio despues...
      
      if(count($detalles) == 0) continue;
      
      $total = DB::table('importacion_diaria_mesas as IDM')
      ->whereYear('IDM.fecha','=',$date[0])->whereMonth('IDM.fecha','=',$date[1])
      ->where('IDM.id_casino','=',$id_casino)->where('IDM.id_moneda','=',$moneda->id_moneda)
      ->whereNull('IDM.deleted_at')
      ->selectRaw('SUM(IDM.droop) as droop, SUM(IDM.retiros) as retiros, SUM(IDM.utilidad) as utilidad, SUM(IDM.saldo_fichas) as saldo_fichas,
       "---" as hold, 0 as conversion_total')
      ->groupBy('IDM.id_casino','IDM.id_moneda')
      ->first();

      if($total->droop != 0){
        $total->hold = round(($total->utilidad * 100)/$total->droop,2);
      }
      foreach($detalles as $d) $total->conversion_total += $d['conversion_total'];

      $juegos = DB::table('importacion_diaria_mesas as IDM')
      ->join('detalle_importacion_diaria_mesas as DIDM','IDM.id_importacion_diaria_mesas','=','DIDM.id_importacion_diaria_mesas')
      ->whereYear('IDM.fecha','=',$date[0])->whereMonth('IDM.fecha','=',$date[1])
      ->where('IDM.id_casino','=',$id_casino)->where('IDM.id_moneda','=',$moneda->id_moneda)
      ->whereNull('IDM.deleted_at')->whereNull('DIDM.deleted_at')
      ->selectRaw('DIDM.siglas_juego, DIDM.nro_mesa, SUM(DIDM.utilidad) as utilidad')
      ->groupBy('DIDM.siglas_juego','DIDM.nro_mesa')
      ->orderBy('DIDM.siglas_juego','asc')
      ->orderBy('DIDM.nro_mesa','asc')
      ->get();

      foreach($juegos as $j) $j->porcentaje = round(100*$j->utilidad/$total->utilidad,2);
      
      $por_moneda[] = [
        'moneda' => $moneda->siglas,
        'juegos' => $juegos,
        'detalles' => $detalles,
        'total' => $total,
      ];
    }
    return $por_moneda;
  }

  public function imprimirMensual($fecha,$id_casino){
    $casino = Casino::find($id_casino);
    $date = explode('-',$fecha);
    $mes = $date[0].'-'.$date[1];
    $por_moneda = $this->mensualPorMonedaPorJuego($id_casino,$date);
    $view = view('Informes.informeMes', compact('por_moneda','casino','mes'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view);
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $casino->codigo."/".$mes, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf->stream('informe_mensual_'.$casino->codigo."-".$mes.'.pdf', Array('Attachment'=>0));
  }
}
