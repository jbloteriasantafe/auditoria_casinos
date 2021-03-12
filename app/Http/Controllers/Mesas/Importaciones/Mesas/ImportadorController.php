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
    return view('Importaciones.importacionDiaria',['diarias' =>[],'casinos'=>$casinos,'moneda'=>$monedas]);
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
 public function buscarPorTipoMesa($id_importacion,$t_mesa){
  $importacion =  ImportacionDiariaMesas::find($id_importacion);

  return [
            'importacion' => $importacion,
            'casino' => $importacion->casino,
            'detalles' => $importacion->detalles()->where('tipo_mesa','=',$t_mesa)->get(),
            'moneda' => $importacion->moneda
          ];
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
      $data = $validator->getData();
      $fecha = $data['fecha'];
      //VALIDO LA FECHA
      if($fecha >= date('Y-m-d')){
        $validator->errors()->add('fecha', 'No es posible importar la fecha indicada. Debe ser menor.');
        return;
      }
      $id_casino = $data['id_casino'];
      $id_moneda = $data['id_moneda'];

      $misma_fecha = ImportacionDiariaMesas::where([
        ['id_casino','=',$id_casino],['id_moneda','=',$id_moneda],['fecha','=',$fecha]]
      )->whereNull('deleted_at')->get();
      if(count($misma_fecha) > 0){
        foreach($misma_fecha as $imp){
          $imp->detalles()->delete();
          $imp->delete();
        }
        $validator->errors()->add('error','Se eliminaron importaciones, existentes presione REENVIAR.');
        return;
      }
      //valido que esté la imp del dia anterior si y solo si -> en el mes anterior importó al menos una vez
      $fecha = Carbon::parse($data['fecha']);
      $mes_anterior = (clone $fecha)->subMonth(1);
      $importo_mes_anterior = ImportacionDiariaMesas::where([['id_casino','=',$id_casino],['id_moneda','=',$id_moneda]])
      ->whereYear('fecha','=',$mes_anterior->format('m'))
      ->whereMonth('fecha','=',$mes_anterior->format('Y'))->get()->count() > 0;
      if($importo_mes_anterior){
        $dia_anterior = (clone $fecha)->subDay(1)->format('Y-m-d');
        $importo_dia_anterior = ImportacionDiariaMesas::where([
          ['id_casino','=',$id_casino],['id_moneda','=',$id_moneda],['fecha','=',$dia_anterior]
        ])->get()->count() > 0;
        if(!$importo_dia_anterior){
          $validator->errors()->add('fecha', 'La importación para la fecha anterior no se ha encontrado.');
          return;
        }
      }

      $path = $data['archivo']->getRealPath();
      if (($gestor = fopen($path, "r")) !== FALSE) {
          $seis_columnas = true;
          while (($datos = fgetcsv($gestor, 1000, ";")) == 1) {
              $seis_columnas = count($datos) == 6;
              if(!$seis_columnas){
                $validator->errors()->add('error','Las columnas del archivo deben ser: \nJUEGO,NRO MESA,DROP,UTILIDAD,FILL,CREDIT.');
                break;
              }
          }
          fclose($gestor);
          if(!$seis_columnas) return;
      }else{
        $validator->errors()->add('error','No se pudo leer el archivo');
        return;
      }
    })->validate();

    /*DB::transaction(function() use ($request,&$importacion){

    });*/

    $importacion = new ImportacionDiariaMesas;
    $importacion->fecha = $request->fecha;
    $importacion->moneda()->associate($request->id_moneda);
    $importacion->casino()->associate($request->id_casino);
    if(!empty($request->cotizacion_diaria)){
      $importacion->cotizacion = str_replace(',','.',$request->cotizacion_diaria);
    }
    $importacion->diferencias = 1;
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
      row_7 fecha
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
        row_3      = CAST(REPLACE(@2,',','') as DECIMAL(15,2)),
        row_4      = CAST(REPLACE(@3,',','') as DECIMAL(15,2)),
        row_5      = CAST(REPLACE(@4,',','') as DECIMAL(15,2)),
        row_6      = CAST(REPLACE(@5,',','') as DECIMAL(15,2)),
        row_7      = '%s'",$path,$iid, $importacion->fecha);

    try{
      $pdo->exec($query);
    }catch(Exception $e){
      ImportacionDiariaMesas::destroy($iid);
      return response()->json([
        'error' => ['La 1er columna debe tener los nombres de los juegos, la 2da los nros. de las mesas, y luego '
        .'drop, fill, credit (números de hasta 15 dígitos separado de los decimales con coma)']
      ],422);
    }

    $comprueba_juegos = DB::table('filas_csv_mesas_bingos')
    ->select('filas_csv_mesas_bingos.id','filas_csv_mesas_bingos.row_1','filas_csv_mesas_bingos.row_2')
    ->crossJoin('juego_mesa',
      function ($join) use($request){
        $join->on('juego_mesa.nombre_juego', 'LIKE', 'filas_csv_mesas_bingos.row_1')
            ->orOn('juego_mesa.siglas', 'LIKE', 'filas_csv_mesas_bingos.row_1')
            ->where('juego_mesa.id_casino', '=',$request->id_casino);
      }
    )
    ->where('row_1','<>','')
    ->where('filas_csv_mesas_bingos.id_archivo','=',$iid)
    ->get();
    $ids_csv = array();
    foreach ($comprueba_juegos as $cc) {
      $ids_csv[] = $cc->id;
    }
    $datos = CSVImporter::where('id_archivo','=',$iid)
    ->whereNotIn('id',$ids_csv)
    ->get();
    foreach ($datos as $d) {
      $juegos_coinciden = JuegoMesa::where('siglas','LIKE',$d->row_1)
      ->orWhere('nombre_juego','LIKE',$d->row_1)->get()->count() > 0;
      if(!empty($d->row_1) && !$juegos_coinciden){//@FIX: Join de algun tipo?
        DB::table('filas_csv_mesas_bingos')->where('id_archivo','=',$iid)->delete();
        ImportacionDiariaMesas::destroy($iid);
        return response()->json(['error' => ['Los nombres de los juegos deben coincidir con los del sistema.']],422);
      }
    }

    $comprueba_nros = CSVImporter::where([['row_2','like','0%'],['id_archivo','=',$iid]])->get()->count();
    $comprueba_nrosceroo = CSVImporter::where([['row_2','like','0'],['id_archivo','=',$iid]])->get()->count();
    if($comprueba_nros > 0 && $comprueba_nros != $comprueba_nrosceroo){//@FIX: Regex?
      DB::table('filas_csv_mesas_bingos')->where('id_archivo','=',$iid)->delete();
      ImportacionDiariaMesas::destroy($iid);
      return response()->json(['error' => ['Los números de las mesas no deben comenzar con 0s.']],422);
    }

    //crear los $detalles
    try{
      $pdo = DB::connection('mysql')->getPdo();
      $crea_detalles = sprintf("INSERT INTO detalle_importacion_diaria_mesas
        (id_importacion_diaria_mesas,
        id_mesa_de_panio,
        id_moneda,
        fecha,
        utilidad,
        droop,
        reposiciones,
        retiros,
        id_juego_mesa,
        nro_mesa,
        nombre_juego,
        codigo_moneda,
        diferencia_cierre,
        tipo_mesa)
        SELECT csv.id_archivo,
              mesa.id_mesa_de_panio,
              moneda.id_moneda,
              csv.row_7,
              csv.row_4,
              csv.row_3,
              csv.row_5,
              csv.row_6,
              juego.id_juego_mesa,
              mesa.nro_mesa,
              juego.nombre_juego,
              moneda.siglas,
              csv.row_4,
              tipo_mesa.descripcion
        FROM filas_csv_mesas_bingos as csv, mesa_de_panio as mesa,
              juego_mesa as juego, moneda, tipo_mesa
        WHERE csv.id_archivo = '%d'
              AND juego.id_casino = '%d'
              AND mesa.id_casino = '%d'
              AND (juego.nombre_juego LIKE csv.row_1
                    OR juego.siglas LIKE csv.row_1
                  )
              AND mesa.nro_admin = csv.row_2
              AND moneda.id_moneda = '%d'
              AND juego.id_tipo_mesa = tipo_mesa.id_tipo_mesa
              AND juego.deleted_at IS NULL
              AND mesa.deleted_at IS NULL
              AND mesa.id_juego_mesa = juego.id_juego_mesa
              AND moneda.id_moneda = mesa.id_moneda;",
      $importacion->id_importacion_diaria_mesas,
      $importacion->id_casino,
      $importacion->id_casino,
      $importacion->id_moneda);

      $pdo->exec($crea_detalles);
      $importacion->nombre_csv = $request->archivo->getClientOriginalName();
      $importacion->save();
    }catch(Exception $e){
      DB::table('filas_csv_mesas_bingos')->where('id_archivo','=',$iid)->delete();
      DetalleImportacionDiariaMesas::where('id_importacion_diaria_mesas',$id)->destroy();
      ImportacionDiariaMesas::destroy($iid);
      return response()->json(['error' => [$e->getMessage()]],422);
    }

    $this->calcularDiffIDM();

    DB::table('filas_csv_mesas_bingos')->where('id_archivo','=',$importacion->id_importacion_diaria_mesas)->delete();
    return 1;
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

  //fecha casino moneda

  public function filtros(Request $request){
    //$this->importacionesSinCierre();
    //dd('what');
    $reglas=array();

    if($request->id_moneda !=0 && !empty($request->id_moneda)){
      $reglas[]=['importacion_diaria_mesas.id_moneda' , '=' , $request->id_moneda ];
    }

    if($request->casino==0 || empty($request->casino)){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $casinos = array();
      foreach($usuario->casinos as $casino){
        $casinos[]=$casino->id_casino;
      }
    }else{
      $casinos[]=$request->casino;
    }

    if(!empty( $request->sort_by)){
      $sort_by = $request->sort_by;
    }else{

        $sort_by = ['columna' => 'fecha','orden'=>'desc'];
    }
    //dd($reglas);

    if(!isset($request->fecha) || $request->fecha == 0 ){
      $resultados = DB::table('importacion_diaria_mesas')
                        ->join('moneda','moneda.id_moneda','=','importacion_diaria_mesas.id_moneda')
                        ->join('casino','casino.id_casino','=','importacion_diaria_mesas.id_casino')
                        ->whereIn('casino.id_casino',$casinos)
                        ->where($reglas)
                        ->whereNull('importacion_diaria_mesas.deleted_at')
                        ->when($sort_by,function($query) use ($sort_by){
                                        return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                    })
                        ->paginate($request->page_size);
                        return ['importaciones'=>$resultados] ;
    }else{
      $fecha=explode("-", $request['fecha']);
      $resultados = DB::table('importacion_diaria_mesas')
                        ->join('moneda','moneda.id_moneda','=','importacion_diaria_mesas.id_moneda')
                        ->join('casino','casino.id_casino','=','importacion_diaria_mesas.id_casino')
                        ->whereYear('fecha' , '=' ,$fecha[0])
                        ->whereMonth('fecha','=', $fecha[1])
                        ->whereDay('fecha','=', $fecha[2])
                        ->where($reglas)
                        ->whereIn('casino.id_casino',$casinos)
                        ->whereNull('importacion_diaria_mesas.deleted_at')
                        ->when($sort_by,function($query) use ($sort_by){
                                        return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                    })
                        ->paginate($request->page_size);
    }

    return ['importaciones'=>$resultados] ;
  }

  public function guardarObservacion(Request $request){
    $validator=  Validator::make($request->all(),[
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
  * Busca las imp. diarias que tengan diferencias y que no hayan sido validadas
  * y recalcula las diferencias
  */
  public function calcularDiffIDM(){
    //todas las imp diarias sin validar y con diferencias
    //junto con los posibles cierres a juntarse
    //trae solo los detalles que hagan join con cierres
    $datos = DB::table('importacion_diaria_mesas as imp')
    ->select('imp.*','det.*','cierre_mesa.id_cierre_mesa as id_cierre','cierre_mesa.total_pesos_fichas_c')
    ->join('detalle_importacion_diaria_mesas as det','det.id_importacion_diaria_mesas','=','imp.id_importacion_diaria_mesas')
    ->join('cierre_mesa', function($join){
        $join->on('cierre_mesa.id_mesa_de_panio','=','det.id_mesa_de_panio')
        ->on('cierre_mesa.fecha','=','det.fecha');
      }
    )
    ->where('imp.diferencias','<>',0)->whereNull('imp.deleted_at')
    ->orderBy('imp.fecha','asc')->get();
    ///por cada importacion

    foreach ($datos as $detalle){
      //ver si hay que considerar algo para elegir cuando hay múltiples cierres
      $this->calcularDifCierresImp($detalle);
    }

    $datos = DB::table('importacion_diaria_mesas as imp')
    ->select('imp.*')
    ->where('imp.validado','=',0)->where('imp.diferencias','<>',0)->whereNull('imp.deleted_at')
    ->orderBy('imp.fecha','desc')->get();

    foreach ($datos as $importacion) {
      $imp = ImportacionDiariaMesas::find($importacion->id_importacion_diaria_mesas);
      $con_diferrencias = DetalleImportacionDiariaMesas::where([
        ['id_importacion_diaria_mesas','=',$importacion->id_importacion_diaria_mesas],['diferencia_cierre','<>',0]]
      )->get();

      if(count($con_diferrencias) == 0){
        $imp->diferencias = 0;
        $imp->save();
      }else{
        $diferencia = 0;
        foreach ($con_diferrencias as $error) {
          $diferencia += $error->diferencia_cierre;
        }
        $imp->diferencias = $diferencia;
        $imp->save();
      }
      $this->actualizarTotalesImpDiaria($importacion->id_importacion_diaria_mesas);
   }
  }

  ///agrega al detalle el ultimo cierre con el que fue asociado
  //para el calculo de la diff
  private function calcularDifCierresImp($detalle){
    $cierre = Cierre::find($detalle->id_cierre); //ccierre de la fecha a importada
    $detalle_importacion = DetalleImportacionDiariaMesas::find($detalle->id_detalle_importacion_diaria_mesas);
    if($cierre == null){ //si no está cargado el cierre de la importacion
      //creo el cierre de la mesa pero con todo en cero.
      $cierre = new Cierre;
      $mesa = Mesa::find($detalle_importacion->id_mesa_de_panio);
      $cierre->fecha = $detalle_importacion->importacion_diaria_mesas->fecha;
      $cierre->total_pesos_fichas_c = 0;
      $cierre->total_anticipos_c = 0;
      $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $cierre->fiscalizador()->associate($user->id_usuario);
      $cierre->mesa()->associate($mesa->id_mesa_de_panio);
      $cierre->moneda()->associate($detalle_importacion->importacion_diaria_mesas->id_moneda);
      $cierre->tipo_mesa()->associate($mesa->juego->tipo_mesa->id_tipo_mesa);
      $cierre->casino()->associate($mesa->id_casino);
      $cierre->estado_cierre()->associate(2);//validado con diferencias -> asi no se puede modificar desde la sec de cierres.-
      $cierre->save();
    }
    //busco el cierre anterior
    $last_cierre = Cierre::where('fecha','<',$cierre->fecha)
                          ->where('id_mesa_de_panio','=',$cierre->id_mesa_de_panio)
                          ->orderBy('fecha','desc')
                          ->get()->first();
    if($last_cierre != null){
      $check_usado = DetalleImportacionDiariaMesas::where('id_ultimo_cierre',
                                            '=',$last_cierre->id_cierre_mesa)
                                                  ->get();
      if(count($check_usado) == 0 ||
        $check_usado->first()->id_detalle_importacion_diaria_mesas ==
        $detalle_importacion->id_detalle_importacion_diaria_mesas) {
          // calculo la diferencia entre ambos cierres
          $dif_cierres = $cierre->total_pesos_fichas_c-
                         $last_cierre->total_pesos_fichas_c ;
           //formula = (Cx+1 - Cx ) +DROP -FILL+CREDIT = UTILIDAD CALCULADA
           $calculado = $dif_cierres + $detalle_importacion->droop - $detalle_importacion->reposiciones + $detalle_importacion->retiros;
           if ( $calculado == $detalle_importacion->utilidad) {
             $detalle_importacion->diferencia_cierre = 0;
           }
           else{
             $detalle_importacion->diferencia_cierre = abs($calculado - $detalle_importacion->utilidad);
           }
           $detalle_importacion->saldo_fichas = $dif_cierres;
           $detalle_importacion->utilidad_calculada = $calculado;
           $detalle_importacion->cierre()->associate($cierre->id_cierre_mesa);
           $detalle_importacion->cierre_anterior()->associate($last_cierre->id_cierre_mesa);
           $detalle_importacion->save();
      }
    }
    else { //tiene el cierre en cero y no tiene ultimo cierre
      $detalle_importacion->cierre()->associate($cierre->id_cierre_mesa);
      $detalle_importacion->saldo_fichas = 0;

      $calculado =  $detalle_importacion->droop - $detalle_importacion->reposiciones + $detalle_importacion->retiros;
      $detalle_importacion->utilidad_calculada = $calculado;
      $detalle_importacion->diferencia_cierre = abs($calculado - $detalle_importacion->utilidad);

      $detalle_importacion->cierre()->associate($cierre->id_cierre_mesa);
      $detalle_importacion->save();
    }

  }

  public function recalcularUtilidadDia($detalle_importacion_diaria)
  {
    //falta buscar datos para que se haga todo..-

    $dif_cierres = $cierre->total_pesos_fichas_c -
                    $last_cierre->total_pesos_fichas_c;
    //formula = (Cx+1 - Cx ) +DROP -FILL+CREDIT = UTILIDAD CALCULADA
    $calculado = $dif_cierres - $detalle_importacion->reposiciones + $detalle_importacion->retiros;
    if ( $calculado == $detalle_importacion->utilidad) {
      $detalle_importacion->diferencia_cierre = 0;
    }else{
      $detalle_importacion->diferencia_cierre = abs($calculado - $detalle_importacion->utilidad);
    }
    $detalle_importacion->saldo_fichas = $dif_cierres;
    $detalle_importacion->utilidad_calculada = $calculado;
    $detalle_importacion->cierre()->associate($cierre->id_cierre_mesa);
    $detalle_importacion->cierre_anterior()->associate($last_cierre->id_cierre_mesa);
    $detalle_importacion->save();
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

  public function importacionesSinCierre()
  {
    $detalles = DetalleImportacionDiariaMesas::whereNull('id_cierre_mesa')->where('utilidad','<>',0)->get();
    //dd($detalles);
    foreach ($detalles as $detalle_importacion) {
      $cierre = null;
      if($cierre == null){ //si no está cargado el cierre de la importacion
        //creo el cierre de la mesa pero con todo en cero.
        $cierre = new Cierre;
        $mesa = Mesa::find($detalle_importacion->id_mesa_de_panio);
        $cierre->fecha =$detalle_importacion->importacion_diaria_mesas->fecha;
        $cierre->total_pesos_fichas_c = 0;
        $cierre->total_anticipos_c = 0;
        $cierre->fiscalizador()->associate(Auth::user()->id);
        $cierre->mesa()->associate($mesa->id_mesa_de_panio);
        $cierre->moneda()->associate($detalle_importacion->importacion_diaria_mesas->id_moneda);
        $cierre->tipo_mesa()->associate($mesa->juego->tipo_mesa->id_tipo_mesa);
        $cierre->casino()->associate($mesa->id_casino);
        $cierre->estado_cierre()->associate(2);//validado con diferencias -> asi no se puede modificar desde la sec de cierres.-
        $cierre->observacion = 'Cierre creado automáticamente al realizar la importación diaria.';
        $cierre->save();
      }
      //busco el cierre anterior
      $last_cierre = Cierre::where('fecha','<',$cierre->fecha)
                            ->where('id_mesa_de_panio','=',$cierre->id_mesa_de_panio)
                            ->orderBy('fecha','desc')
                            ->get()->first();
      if($last_cierre != null){
        $check_usado = DetalleImportacionDiariaMesas::where('id_ultimo_cierre',
                                              '=',$last_cierre->id_cierre_mesa)
                                                    ->get();
        if(count($check_usado) == 0 ||
          $check_usado->first()->id_detalle_importacion_diaria_mesas ==
          $detalle_importacion->id_detalle_importacion_diaria_mesas) {
            // calculo la diferencia entre ambos cierres
            $dif_cierres = $cierre->total_pesos_fichas_c-
                           $last_cierre->total_pesos_fichas_c ;
             //formula = (Cx+1 - Cx ) +DROP -FILL+CREDIT = UTILIDAD CALCULADA
             $calculado = $dif_cierres + $detalle_importacion->droop - $detalle_importacion->reposiciones + $detalle_importacion->retiros;
             if ( $calculado == $detalle_importacion->utilidad) {
               $detalle_importacion->diferencia_cierre = 0;
             }
             else{
               $detalle_importacion->diferencia_cierre = abs($calculado - $detalle_importacion->utilidad);
             }
             $detalle_importacion->saldo_fichas = $dif_cierres;
             $detalle_importacion->utilidad_calculada = $calculado;
             $detalle_importacion->cierre()->associate($cierre->id_cierre_mesa);
             $detalle_importacion->cierre_anterior()->associate($last_cierre->id_cierre_mesa);
             $detalle_importacion->save();
        }
      }
      else { //tiene el cierre en cero y no tiene ultimo cierre
        //$detalle_importacion->cierre()->associate($cierre->id_cierre_mesa);
        $detalle_importacion->saldo_fichas = 0;

        $calculado =  $detalle_importacion->droop - $detalle_importacion->reposiciones + $detalle_importacion->retiros;
        $detalle_importacion->utilidad_calculada = $calculado;
        $detalle_importacion->diferencia_cierre = abs($calculado - $detalle_importacion->utilidad);

        $detalle_importacion->cierre()->associate($cierre->id_cierre_mesa);
        $detalle_importacion->save();
        //dd('si',$cierre);
      }
      //dd('no',$cierre);
    }
  }

}
