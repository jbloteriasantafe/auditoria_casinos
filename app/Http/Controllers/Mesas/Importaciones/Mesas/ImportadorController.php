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
use App\Mesas\Ficha;
use App\Mesas\Moneda;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;
use App\Mesas\Cierre;
use App\Mesas\DetalleCierre;

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

public function importarCierres(Request $request){
  $header_esperado = ['nro_admin','cod_juego','hora_apertura','hora_cierre','anticipos','total'];
  $fichas_totales = 16;
  for($i=1;$i<=$fichas_totales;$i++){
    $header_esperado[] = 'ficha_valor'.$i;
    $header_esperado[] = 'importe'.$i;
  }
  $header_esperado_inv = [];
  foreach($header_esperado as $idx => $nombre) $header_esperado_inv[$nombre] = $idx;

  $validator =  Validator::make($request->all(),[
    'id_casino' => 'required|exists:casino,id_casino',
    'id_moneda' => 'required|exists:moneda,id_moneda',
    'fecha' => 'required|date',
    'archivo' => 'required|file',
  ], array(), self::$atributos)->after(function($validator) use ($header_esperado){
    if($validator->errors()->any()) return;
    $fecha = $validator->getData()['fecha'];
    if($fecha >= date('Y-m-d')){
      $validator->errors()->add('fecha', 'No es posible importar una fecha futura.');
      return;
    }
    $archivo = $validator->getData()['archivo'];
    $headers = [];
    $handle = fopen($archivo->getRealPath(), 'r');

    $recibido = fgetcsv($handle,1600,';','"');
    $recibido2 = [];//Lo convierto porque lo mandan en un encoding raro, me figura como binaria la cadena
    foreach($recibido as $h) $recibido2[] = utf8_encode($h);
    
    if($recibido2 != $header_esperado){
      $validator->errors()->add('archivo', 'El formato del archivo no es correcto.');
      fclose($handle);
      return;
    }
    fclose($handle);
  })->validate();

  $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
  $id_casino = $request->id_casino;
  $id_moneda = $request->id_moneda;
  $fecha     = $request->fecha;
  $handle  = fopen($request->archivo->getRealPath(), 'r');
  $return  = [];
  $primero = true;

  $errores = [];
  DB::beginTransaction();
  try{
    while(($fila = fgetcsv($handle,1600,';','"')) !== FALSE){
      if($primero){//Ignoro la primer fila porque es el header
        $primero = false;
        continue;
      }
      //Si no tiene nro_admin o no abrio lo ignoro
      $nro_admin = $fila[$header_esperado_inv['nro_admin']];
      $hora_apertura = $fila[$header_esperado_inv['hora_apertura']];
      $hora_cierre = $fila[$header_esperado_inv['hora_cierre']];
      if(empty($nro_admin) || empty($hora_apertura) || empty($hora_cierre) || ($hora_apertura == $hora_cierre)) continue;
      $cod_juego = $fila[$header_esperado_inv['cod_juego']];
      $anticipos = str_replace(',','.',$fila[$header_esperado_inv['anticipos']]);
      $total     = str_replace(',','.',$fila[$header_esperado_inv['total']]);
      $fichas = [];
      for($i=1;$i<=$fichas_totales;$i++){
        $ficha_valor = str_replace(',','.',$fila[$header_esperado_inv['ficha_valor'.$i]]);
        $importe     = str_replace(',','.',$fila[$header_esperado_inv['importe'.$i]]);
        if(floatval($ficha_valor) == 0 || floatval($importe) == 0) continue;
        $fichas[] = ['ficha_valor' => $ficha_valor,'importe' => $importe];
      }
      $error = $this->crearCierre($id_usuario,$fecha,$id_casino,$id_moneda,$nro_admin,$cod_juego,$hora_apertura,$hora_cierre,$anticipos,$total,$fichas);
      if(!is_null($error)) $errores[] = $error;
    }
  }
  catch(Exception $e){
    DB::rollback();
    fclose($handle);
    throw $e;
  }
  fclose($handle);
  if(count($errores) > 0){
    DB::rollback();
    return response()->json(['archivo' => $errores],422);
  }
  DB::commit();
  return 0;
}

private function crearCierre($id_usuario,$fecha,$id_casino,$id_moneda,$nro_admin,$cod_juego,$hora_apertura,$hora_cierre,$anticipos,$total,$fichas){
  $juego = JuegoMesa::withTrashed()->where('juego_mesa.id_casino',$id_casino)
  ->where(function($q) use ($cod_juego){
    return $q->where('juego_mesa.siglas','like',$cod_juego)->orWhere('juego_mesa.nombre_juego','like',$cod_juego);
  })
  ->where(function ($q) use ($fecha){
    return $q->whereNull('juego_mesa.deleted_at')->orWhere('juego_mesa.deleted_at','>',$fecha);
  })->get()->first();
  if(is_null($juego)) return 'NO SE ENCONTRO EL JUEGO '.$cod_juego;

  $mesa = $juego->mesas()
  ->where('mesa_de_panio.nro_admin','=',$nro_admin)
  ->where(function($q) use ($fecha){
    return $q->whereNull('mesa_de_panio.deleted_at')->orWhere('mesa_de_panio.deleted_at','>',$fecha);
  })
  ->where(function($q) use ($id_moneda){//Multimoneda o coincide la moneda
    return $q->whereNull('mesa_de_panio.id_moneda')->orWhere('mesa_de_panio.id_moneda','=',$id_moneda);
  })->get()->first();
  if(is_null($mesa)) return 'NO SE ENCONTRO LA MESA '.$cod_juego.' '.$nro_admin;

  $ya_existe = Cierre::where([
    ['id_casino','=',$id_casino],['id_mesa_de_panio','=',$mesa->id_mesa_de_panio],['id_moneda','=',$id_moneda],
    ['fecha','=',$fecha],['hora_inicio','=',$hora_apertura.':00'],['hora_fin','=',$hora_cierre.':00']
  ])->get()->count() > 0;

  if($ya_existe) return 'YA EXISTE UN CIERRE PARA LA MESA '.$cod_juego.' '.$nro_admin.' EN LA FECHA '.$fecha.' '.$hora_apertura.'-'.$hora_cierre;
  
  $cierre = new Cierre;
  $cierre->fecha                = $fecha;
  $cierre->hora_inicio          = $hora_apertura;
  $cierre->hora_fin             = $hora_cierre;
  $cierre->total_pesos_fichas_c = $total;
  $cierre->total_anticipos_c    = $anticipos;
  $cierre->id_casino            = $id_casino;
  $cierre->id_fiscalizador      = $id_usuario;
  $cierre->id_moneda            = $id_moneda;
  $cierre->id_tipo_mesa         = $juego->id_tipo_mesa;
  $cierre->id_mesa_de_panio     = $mesa->id_mesa_de_panio;
  $cierre->save();

  $total_validacion = 0;

  foreach($fichas as $f){
    if(fmod($f['importe'],$f['ficha_valor']) != 0) return 'ERROR EL MONTO '.$f['importe'].' NO ES MULTIPLO DE '.$f['ficha_valor'];

    $ficha = Ficha::withTrashed()->where([['ficha.valor_ficha','=',$f['ficha_valor']],['ficha.id_moneda','=',$id_moneda]])
    ->join('ficha_tiene_casino',function($j) use ($id_casino,$fecha){
      return $j->on('ficha_tiene_casino.id_ficha','=','ficha.id_ficha')
      ->where('ficha_tiene_casino.id_casino','=',$id_casino);
    })
    ->where(function($q) use ($fecha){
      return $q->whereNull('ficha.deleted_at')->orWhere('ficha.deleted_at','>',$fecha);
    })
    ->where(function($q) use ($fecha){
      return $q->whereNull('ficha_tiene_casino.deleted_at')->orWhere('ficha_tiene_casino.deleted_at','>',$fecha);
    })->get()->first();
    
    if(is_null($ficha)) return 'NO SE ENCONTRO LA FICHA DE '.$f['ficha_valor'];
    $d = new DetalleCierre;
    $d->id_ficha       = $ficha->id_ficha;
    $d->monto_ficha    = $f['importe'];
    $d->id_cierre_mesa = $cierre->id_cierre_mesa;
    $d->save();
    $total_validacion +=floatval($f['importe']);
  }

  if(floatval($total) != $total_validacion) return 'ERROR EL MONTO TOTAL ('.$total.') NO COINCIDE CON EL DE LAS FICHAS ('.$total_validacion.')';
  return null;
}

public function importarDiario(Request $request){
    $validator =  Validator::make($request->all(),[
      'id_casino' => 'required|exists:casino,id_casino',
      'id_moneda' => 'required|exists:moneda,id_moneda',
      'fecha' => 'required|date',
      'archivo' => 'required|file',
    ], array(), self::$atributos)->after(function($validator){
      if($validator->errors()->any()) return;
      $fecha = $validator->getData()['fecha'];
      if($fecha >= date('Y-m-d')){
        $validator->errors()->add('fecha', 'No es posible importar una fecha futura.');
        return;
      }
      $archivo = $validator->getData()['archivo'];
      $headers = [];
      $handle = fopen($archivo->getRealPath(), 'r');

      $recibido = fgetcsv($handle,1600,';','"');
      $recibido2 = [];//Lo convierto porque lo mandan en un encoding raro, me figura como binaria la cadena
      foreach($recibido as $h) $recibido2[] = utf8_encode($h);
      $esperado = ['JUEGO','N°MESA','DROP','DROP TARJETA','UTILIDAD','FILL','CREDIT','PROPINAS'];
      if($recibido2 != $esperado){
        $validator->errors()->add('archivo', 'El formato del archivo no es correcto.');
        fclose($handle);
        return;
      }
      fclose($handle);
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
        row_10 drop_tarjeta
        row_11 propina
      */
      $query = sprintf("LOAD DATA local INFILE '%s'
      INTO TABLE filas_csv_mesas_bingos
      FIELDS TERMINATED BY ';'
      OPTIONALLY ENCLOSED BY '\"'
      ESCAPED BY '\"'
      LINES TERMINATED BY '\\n'
      IGNORE 1 LINES
      (@0,@1,@2,@3,@4,@5,@6,@7)
      SET id_archivo = '%d',
          row_1      = @0,
          row_2      = @1,
          row_3      = CAST(REPLACE(REPLACE(@2,'.',''),',','.') as DECIMAL(15,2)),
          row_10     = CAST(REPLACE(REPLACE(@3,'.',''),',','.') as DECIMAL(15,2)),
          row_4      = CAST(REPLACE(REPLACE(@4,'.',''),',','.') as DECIMAL(15,2)),
          row_5      = CAST(REPLACE(REPLACE(@5,'.',''),',','.') as DECIMAL(15,2)),
          row_6      = CAST(REPLACE(REPLACE(@6,'.',''),',','.') as DECIMAL(15,2)),
          row_11     = CAST(REPLACE(REPLACE(@7,'.',''),',','.') as DECIMAL(15,2))",
          $path,$iid, $fecha
      );

      $pdo->exec($query);

      //@HACK: saldo_fichas calculado a pata hasta que lo manden en el archivo
      $crea_detalles = sprintf("INSERT INTO detalle_importacion_diaria_mesas
        (id_importacion_diaria_mesas, siglas_juego, nro_mesa, droop, droop_tarjeta, utilidad, reposiciones, retiros, 
        saldo_fichas,propina)
        SELECT 
        csv.id_archivo, csv.row_1, csv.row_2, csv.row_3,csv.row_10, csv.row_4, csv.row_5, csv.row_6,
        (csv.row_4 - csv.row_3 + csv.row_5 - csv.row_6),csv.row_11
        FROM filas_csv_mesas_bingos as csv
        WHERE csv.id_archivo = '%d' AND csv.row_1 <> '' AND csv.row_2 <> '' AND SUBSTR(csv.row_1,0,7) <> 'TOTALES';",
        $iid);

      $pdo->exec($crea_detalles);

      $setea_totales = sprintf("UPDATE importacion_diaria_mesas i,
      (
        SELECT SUM(d.droop) as droop              , SUM(d.droop_tarjeta) as droop_tarjeta , SUM(d.utilidad) as utilidad,
               SUM(d.reposiciones) as reposiciones, SUM(d.retiros) as retiros             , SUM(d.saldo_fichas) as saldo_fichas,
               SUM(d.propina) as propina
        FROM detalle_importacion_diaria_mesas d
        WHERE d.id_importacion_diaria_mesas = '%d'
        GROUP BY d.id_importacion_diaria_mesas
      ) total
      SET i.droop    = IFNULL(total.droop,0),    i.droop_tarjeta = IFNULL(total.droop_tarjeta,0),
          i.utilidad = IFNULL(total.utilidad,0), i.reposiciones = IFNULL(total.reposiciones,0), 
          i.retiros  = IFNULL(total.retiros,0),  i.saldo_fichas = IFNULL(total.saldo_fichas,0),
          i.propina  = IFNULL(total.propina,0)
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
    $fechas = [];
    while(date('m',strtotime($fecha)) == $mes){
      $fechas[] = 'select "'.$fecha.'" as fecha';
      $fecha = date('Y-m-d' , strtotime($fecha . ' + 1 days'));
    }
    $tabla_fechas = '('.implode(' union all ',$fechas).') as fechas';

    //Verifico que todas las importaciones tengan cierres (salvo que el saldo fichas sea 0)
    $ret = DB::table(DB::raw($tabla_fechas))
    ->selectRaw('fechas.fecha, idm.id_importacion_diaria_mesas, idm.validado,
    SUM(c1.id_cierre_mesa IS NOT NULL) > 0 as tiene_cierre,
    COUNT(
      idm.id_importacion_diaria_mesas IS NOT NULL
      AND (
           (c1.id_cierre_mesa IS NOT NULL AND c2.id_cierre_mesa IS NOT NULL)
        OR (c1.id_cierre_mesa IS NULL AND c2.id_cierre_mesa IS NULL AND didm.saldo_fichas = 0)
      )
      AND mp.nro_admin = didm.nro_mesa
      AND (
        (jm.siglas LIKE didm.siglas_juego) OR (jm.nombre_juego LIKE didm.siglas_juego)
      )
    ) 
    = COUNT(didm.id_detalle_importacion_diaria_mesas) as todos_los_cierres')
    ->leftJoin('cierre_mesa as c1',function($j)  use ($request){
      return $j->on('c1.fecha','=','fechas.fecha')
      ->where([['c1.id_moneda','=',$request->id_moneda],['c1.id_casino','=',$request->id_casino]])
      ->whereNull('c1.deleted_at');
    })
    ->leftJoin('cierre_mesa as c2',function($j){
      return $j->on('c2.fecha','=',DB::raw('DATE_SUB(c1.fecha, INTERVAL 1 DAY)'))
      ->on('c2.id_moneda','=','c1.id_moneda')->on('c2.id_casino','=','c1.id_casino')
      ->on('c2.id_mesa_de_panio','=','c1.id_mesa_de_panio')
      ->whereNull('c2.deleted_at');
    })
    ->leftJoin('mesa_de_panio as mp','mp.id_mesa_de_panio','=','c1.id_mesa_de_panio')
    ->leftJoin('juego_mesa as jm','jm.id_juego_mesa','=','mp.id_juego_mesa')
    //Aca hay 2 branches en la query a proposito, para que el chequeo de cierres no dependa del chequeo de la importacion
    ->leftJoin('importacion_diaria_mesas as idm',function($j) use ($request) {
      return $j->on('idm.fecha','=','fechas.fecha')
      ->where([['idm.id_moneda','=',$request->id_moneda],['idm.id_casino','=',$request->id_casino]])
      ->whereNull('idm.deleted_at');
    })
    ->leftJoin('detalle_importacion_diaria_mesas as didm','didm.id_importacion_diaria_mesas','=','idm.id_importacion_diaria_mesas')
    ->orderBy('fechas.fecha',$request->sort_by["orden"])
    ->groupBy(DB::raw('fechas.fecha, idm.id_importacion_diaria_mesas'))
    ->get();

    return $ret;
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
    Validator::make($request->all(),[
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

  public function mensualPorMonedaPorJuego($id_casino,$id_moneda,$anio_mes){
    $detalles = ImportacionDiariaMesas::whereYear('fecha','=',$anio_mes[0])
    ->whereMonth('fecha','=',$anio_mes[1])
    ->where('id_casino','=',$id_casino)
    ->where('id_moneda','=',$id_moneda)
    ->whereNull('deleted_at')
    ->orderBy('fecha','asc')->get()->toArray();//si no hago toArray me retorna vacio despues...
        
    $total = DB::table('importacion_diaria_mesas as IDM')
    ->whereYear('IDM.fecha','=',$anio_mes[0])->whereMonth('IDM.fecha','=',$anio_mes[1])
    ->where('IDM.id_casino','=',$id_casino)->where('IDM.id_moneda','=',$id_moneda)
    ->whereNull('IDM.deleted_at')
    ->selectRaw('SUM(IDM.droop) as droop, SUM(IDM.retiros) as retiros, SUM(IDM.utilidad) as utilidad, SUM(IDM.saldo_fichas) as saldo_fichas,
      "---" as hold, 0 as conversion_total')
    ->groupBy(DB::raw('"constant"'))
    ->first();

    if(is_null($total)){//No hay importacion en todo el mes
      $total = new \stdClass;
      $total->droop = 0;
      $total->retiros = 0;
      $total->utilidad = 0;
      $total->saldo_fichas = 0;
      $total->conversion_total = 0;
      $total->mesas = 0;
    }
    $total->hold = $total->droop? round(($total->utilidad * 100)/$total->droop,2) : "---";

    $total->mesas = DB::table('importacion_diaria_mesas as IDM')
    ->join('detalle_importacion_diaria_mesas as DIDM','DIDM.id_importacion_diaria_mesas','=','IDM.id_importacion_diaria_mesas')
    ->selectRaw('COUNT(distinct CONCAT(DIDM.siglas_juego,DIDM.nro_mesa)) as mesas')
    ->whereYear('IDM.fecha','=',$anio_mes[0])->whereMonth('IDM.fecha','=',$anio_mes[1])
    ->where('IDM.id_casino','=',$id_casino)->where('IDM.id_moneda','=',$id_moneda)
    ->where(function($q){
      return $q->whereRaw('IFNULL(DIDM.droop,0) <> 0 OR IFNULL(DIDM.droop_tarjeta,0) <> 0 OR IFNULL(DIDM.reposiciones,0) <> 0
                        OR IFNULL(DIDM.retiros,0) <> 0 OR IFNULL(DIDM.utilidad,0) <> 0 OR IFNULL(DIDM.saldo_fichas,0) <> 0 OR IFNULL(DIDM.propina <> 0,0)');
    })
    ->groupBy(DB::raw('"constant"'))
    ->first();
    $total->mesas = is_null($total->mesas)? 0 : $total->mesas->mesas;

    foreach($detalles as &$d){
      $total->conversion_total += $d['conversion_total'];
      $d['mesas'] = DB::table('detalle_importacion_diaria_mesas as DIDM')
      ->selectRaw('COUNT(distinct CONCAT(DIDM.siglas_juego,DIDM.nro_mesa)) as mesas')
      ->where('id_importacion_diaria_mesas','=',$d['id_importacion_diaria_mesas'])
      ->where(function($q){
        return $q->whereRaw('IFNULL(DIDM.droop,0) <> 0 OR IFNULL(DIDM.droop_tarjeta,0) <> 0 OR IFNULL(DIDM.reposiciones,0) <> 0
                          OR IFNULL(DIDM.retiros,0) <> 0 OR IFNULL(DIDM.utilidad,0) <> 0 OR IFNULL(DIDM.saldo_fichas,0) <> 0 OR IFNULL(DIDM.propina <> 0,0)');
      })
      ->groupBy(DB::raw('"constant"'))
      ->first();
      $d['mesas'] = is_null($d['mesas'])? 0 : $d['mesas']->mesas;
    }

    $juegos = DB::table('importacion_diaria_mesas as IDM')
    ->join('detalle_importacion_diaria_mesas as DIDM','IDM.id_importacion_diaria_mesas','=','DIDM.id_importacion_diaria_mesas')
    ->whereYear('IDM.fecha','=',$anio_mes[0])->whereMonth('IDM.fecha','=',$anio_mes[1])
    ->where('IDM.id_casino','=',$id_casino)->where('IDM.id_moneda','=',$id_moneda)
    ->whereNull('IDM.deleted_at')->whereNull('DIDM.deleted_at')
    ->selectRaw('DIDM.siglas_juego, DIDM.nro_mesa, SUM(DIDM.utilidad) as utilidad')
    ->groupBy('DIDM.siglas_juego','DIDM.nro_mesa')
    ->orderBy('DIDM.siglas_juego','asc')
    ->orderBy('DIDM.nro_mesa','asc')
    ->get();

    foreach($juegos as $j) $j->porcentaje = round(100*$j->utilidad/$total->utilidad,2);

    return [
      'moneda' => Moneda::find($id_moneda)->siglas,
      'juegos' => $juegos,
      'detalles' => $detalles,
      'total' => $total,
    ];
  }

  public function imprimirMensual(Request $request){
    $casino = Casino::find($request->id_casino);
    $date = explode('-',$request->fecha);
    $mes = $date[0].'-'.$date[1];
    $datos = $this->mensualPorMonedaPorJuego($request->id_casino,$request->id_moneda,[intval($date[0]),intval($date[1])]);
    $view = view('Informes.informeMes', compact('datos','casino','mes'));
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
