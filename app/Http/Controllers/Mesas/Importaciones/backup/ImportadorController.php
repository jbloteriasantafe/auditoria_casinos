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

    return view('Importaciones.importacionDiaria',  [
                                                'diarias' =>[],
                                                'casinos'=>$casinos,
                                                'moneda'=>$monedas,
                                              ]);
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
  //$importacion = ImportacionDiariaMesas::find($id_importacion);
  $importacion =  ImportacionDiariaMesas::find($id_importacion);

  // DB::table('importacion_diaria_mesas')
  //                   ->join('detalle_importacion_diaria_mesas','importacion_diaria_mesas.id_importacion_diaria_mesas','=','detalle_importacion_diaria_mesas.id_importacion_diaria_mesas')
  //                   ->where('detalle_importacion_diaria_mesas.tipo_mesa','=',$t_mesa)
  //                   ->where('importacion_diaria_mesas.id_importacion_diaria_mesas', '=',$id_importacion)
  //                   ->get();

  return [
            'importacion' => $importacion,
            'casino' => $importacion->casino,
            'detalles' => $importacion->detalles()->where('tipo_mesa','=',$t_mesa)->get(),
            'moneda' => $importacion->moneda
          ];
}

  public function importarDiario(Request $request){
    $importacion = new ImportacionDiariaMesas;
    $importacion->save();
    $validator =  Validator::make($request->all(),[
      'id_casino' => 'required|exists:casino,id_casino',
      'id_moneda' => 'required|exists:moneda,id_moneda',
      'fecha' => 'required|date',
      'cotizacion_diaria' => ['nullable','required_if:id_moneda,2','regex:/^\d\d?\d?\d?\d?\d?\d?\d?([,|.]?\d?\d?\d?)?$/'],
    ], array(), self::$atributos)->after(function($validator) use ($importacion){
                  if($validator->getData()['id_casino'] != 0 &&
                      $validator->getData()['id_moneda'] != 0
                    ){
                    $validator = $this->existeImportacion($validator,$importacion);
                    $validator = $this->validarHeaders($validator);
                    $lista = $this->validarCarga($validator,$importacion);
                    $validator = $lista[0];
                    $importacion = $lista[1];
                    if(!empty($importacion->id_importacion_diaria_mesas) && $importacion->id_importacion_diaria_mesas != null){
                    $lista = $this->validarNrosMesasJuegos($validator,$importacion);
                    $validator = $lista[0];
                    $importacion = $lista[1];
                  }
                }else{
                  ImportacionDiariaMesas::destroy($importacion->id_importacion_diaria_mesas);
                }


                  })->validate();
    if(isset($validator)){
      if ($validator->fails()){
          ImportacionDiariaMesas::destroy($importacion->id_importacion_diaria_mesas);
          return ['errors' => $validator->messages()->toJson()];

        }
     }

    //crear los $detalles
    try{
      /*
        row_1 nombre juegos
        row_2 nro_mesa
        row_3 drop
        row_4 utilidad
        row_5 fill//reposiciones
        row_6 credit//retiros
        row_7 fecha
      */
      $pdo = DB::connection('mysql')->getPdo();
      //DB::connection()->disableQueryLog();
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
                                      AND moneda.id_moneda = mesa.id_moneda;
                                ",$importacion->id_importacion_diaria_mesas,
                                $importacion->id_casino,
                                $importacion->id_casino,
                                $importacion->id_moneda);

                                // dd([$crea_detalles,$importacion->id_importacion_diaria_mesas,
                                // $importacion->id_casino,
                                // $importacion->id_casino,
                                // $importacion->id_moneda]);


      $pdo->exec($crea_detalles);
    }catch(Exception $e){
      dd($e);
    }
    $this->calcularDiffIDM();
    $this->actualizarTotalesImpDiaria($importacion->id_importacion_diaria_mesas);

    // $agrega_comando = new ComandoEnEspera;
    // $agrega_comando->nombre_comando = 'IDM:calcularDiff';
    // $agrega_comando->fecha_a_ejecutar = Carbon::now()->addMinutes(30)->format('Y:m:d H:i:s');
    // $agrega_comando->save();

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

  private function existeImportacion($validator,$importacion){
    $f =$validator->getData()['fecha'];
    $fecha = explode('-',$f);

    $check_import = ImportacionDiariaMesas::where([['id_casino','=',$validator->getData()['id_casino']],
                                                    ['id_moneda','=',$validator->getData()['id_moneda']]
                                                    ])
                                             ->whereYear('fecha','=',$fecha[0])
                                             ->whereMonth('fecha','=',$fecha[1])
                                             ->whereDay('fecha','=',$fecha[2])
                                             ->get();
     if(count($check_import)>0){
       $casino = Casino::find($validator->getData()['id_casino']);
       $moneda = Moneda::find($validator->getData()['id_moneda']);
       $validator->errors()->add('error','Ya existe un archivo importado para la fecha:'.$validator->getData()['fecha']
                                 .' de '.$casino->nombre.' en '.$moneda->siglas.'.'
                                 );
      ImportacionDiariaMesas::destroy($importacion->id_importacion_diaria_mesas);
     }
     return $validator;
  }

  private function validarHeaders($validator){
    $path = $validator->getData()['archivo']->getRealPath();
    $fila = 1;
    if (($gestor = fopen($path, "r")) !== FALSE) {
        while (($datos = fgetcsv($gestor, 1000, ";")) == 1) {
            $cantidad_columnas = count($datos);
            if($cantidad_columnas == 6){
              //ok
            }else{
              $validator->errors()->add('error','Las columnas del archivo deben ser: \nJUEGO,NRO MESA,DROP,UTILIDAD,FILL,CREDIT.');
            }
        }
        fclose($gestor);
    }else{
      $validator->errors()->add('error','No se pudo leer el archivo');
    }
    return $validator;
  }

  private function validarCarga($validator, $importacion){
    $importacion->fecha = $validator->getData()['fecha'];
    $importacion->moneda()->associate($validator->getData()['id_moneda']);
    $importacion->casino()->associate($validator->getData()['id_casino']);
    if(!empty($validator->getData()['cotizacion_diaria'])){
      $cotizacion = str_replace(',','.',$validator->getData()['cotizacion_diaria']);
      $importacion->cotizacion = $cotizacion;
    }


    $importacion->diferencias = 1;
    $importacion->validado = 0;
    $importacion->save();
    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();
    $path = $validator->getData()['archivo']->getRealPath();

    $query = sprintf("LOAD DATA local INFILE '%s'
                      INTO TABLE filas_csv_mesas_bingos
                      FIELDS TERMINATED BY ';'
                      OPTIONALLY ENCLOSED BY '\"'
                      ESCAPED BY '\"'
                      LINES TERMINATED BY '\\n'
                      IGNORE 1 LINES
                      (@0,@1,@2,@3,@4,@5)
                      SET id_archivo = '%d',
                                      row_1 = @0,
                                      row_2 = @1,
                                      row_3 = CAST(REPLACE(@2,',','') as DECIMAL(15,2)),
                                      row_4 = CAST(REPLACE(@3,',','') as DECIMAL(15,2)),
                                      row_5 = CAST(REPLACE(@4,',','') as DECIMAL(15,2)),
                                      row_6 = CAST(REPLACE(@5,',','') as DECIMAL(15,2)),
                                      row_7 = '%s'
                      ",$path,$importacion->id_importacion_diaria_mesas, $importacion->fecha);
                      /*
                        row_1 nombre juegos
                        row_2 nro_mesa
                        row_3 drop
                        row_4 utilidad
                        row_5 fill//reposiciones
                        row_6 credit//retiros
                        row_7 fecha
                      */
    try{
      $pdo->exec($query);
    }catch(Exception $e){
      $validator->errors()->add('error','La 1er columna debe tener los nombres de los juegos, la 2da los nros. de las mesas, y luego drop, fill, credit (números de hasta 15 dígitos separado de los decimales con coma)');
      $importacion = null;
    }
    return [$validator,$importacion];
  }

  private function validarNrosMesasJuegos($validator,$importacion){
    $id_casino =$validator->getData()['id_casino'];


    $comprueba_juegos = DB::table('filas_csv_mesas_bingos')
                          ->select('filas_csv_mesas_bingos.id','filas_csv_mesas_bingos.row_1','filas_csv_mesas_bingos.row_2','juego_mesa.siglas')
                                      ->crossJoin('juego_mesa',
                                               function ($join) use($id_casino){
                                                 $join->on('juego_mesa.nombre_juego', 'LIKE', 'filas_csv_mesas_bingos.row_1')
                                                      ->orOn('juego_mesa.siglas', 'LIKE', 'filas_csv_mesas_bingos.row_1')
                                                      ->where('juego_mesa.id_casino', '=',$id_casino);
                                               }
                                              )
                                      ->where('row_1','<>','')
                                      ->where('filas_csv_mesas_bingos.id_archivo','=',$importacion->id_importacion_diaria_mesas)
                                      ->get();
    $ids_csv = array();
    foreach ($comprueba_juegos as $cc) {
      $ids_csv[] = $cc->id;
    }
    $iid =$importacion->id_importacion_diaria_mesas;
    $datos = CSVImporter::where('id_archivo','=',$iid)
                          ->whereNotIn('id',$ids_csv)
                          ->get();

    foreach ($datos as $ddd) {
      $juegos_coinciden = JuegoMesa::where('siglas','LIKE',$ddd->row_1)
      ->orWhere('nombre_juego','LIKE',$ddd->row_1)->get();
      if(!empty($ddd->row_1) && $ddd->row_1 != '' && count($juegos_coinciden) == 0){
        // dd($importacion);
        DB::table('filas_csv_mesas_bingos')
        ->where('id_archivo','=',$iid)->delete();
        ImportacionDiariaMesas::destroy($iid);
        $validator->errors()->add('error','Los nombres de los juegos deben coincidir con los del sistema.');
        $importacion = null;
      }
    }


    // if(count($comprueba_juegos) != (count($datos))){
    //   //dd([count($comprueba_juegos),count($datos),$comprueba_juegos,$datos]);
    //
    // }

    $comprueba_nros = CSVImporter::where('row_2','like','0%')->get();
    $comprueba_nrosceroo = CSVImporter::where('row_2','like','0')->get();
    if(count($comprueba_nros) > 0 && count($comprueba_nros) != count($comprueba_nrosceroo) ){
      //dd([count($comprueba_juegos),count($datos),$datos]);
      DB::table('filas_csv_mesas_bingos')->where('id_archivo','=',$importacion->id_importacion_diaria_mesas)->delete();
      $validator->errors()->add('error', 'Los números de las mesas no deben comenzar con 0s.');
      $importacion = null;
    }
    return [$validator,$importacion];
  }



  //fecha casino moneda

  public function filtros(Request $request){
    $reglas=array();

    if($request->id_moneda !=0 && !empty($request->moneda)){
      $reglas[]=['importacion_diaria_mesas.moneda' , '=' , $request->moneda ];
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

    $date = new DateTime(); //date & time of right now. (Like time())
    $date->sub(new DateInterval('P2M')); //no se usa pero se podría para que solo revise los de hace dos meses

    $datos = DB::table('importacion_diaria_mesas as imp')
                      ->select('imp.*','det.*','cierre_mesa.id_cierre_mesa as id_cierre',
                                         'cierre_mesa.total_pesos_fichas_c')
                      ->join('detalle_importacion_diaria_mesas as det',
                             'det.id_importacion_diaria_mesas','=',
                             'imp.id_importacion_diaria_mesas')
                      ->join('cierre_mesa', function($join){
                          $join->on('cierre_mesa.id_mesa_de_panio','=',
                                    'det.id_mesa_de_panio')
                          ->on('cierre_mesa.fecha','=','det.fecha');
                        }
                      )
                      ->where('imp.validado','=',0)
                      ->where('imp.diferencias','<>',0)
                      ->orderBy('imp.fecha','desc')
                      ->whereNull('imp.deleted_at')
                      ->get();
                      //dd($datos);
    ///por cada importacion

    // $aux = 0;
    // $aux2 = 0;
    foreach ($datos as $detalle){
      //ver si hay que considerar algo para elegir cuando hay múltiples cierres
      $cierre = Cierre::find($detalle->id_cierre);
      if($cierre != null){
        //$aux++;
        $fue_usado = DetalleImportacionDiariaMesas::where('id_cierre_mesa','=',$cierre->id_cierre_mesa)->get();
        if(count($fue_usado) == 0){
          //$aux2++;
          $this->calcularDifCierresImp($detalle);
        }
      }
    }
    //dd($aux,$aux2);

    $datos = DB::table('importacion_diaria_mesas as imp')
                        ->select('imp.*')
                        ->where('imp.validado','=',0)
                        ->where('imp.diferencias','<>',0)
                        ->orderBy('imp.fecha','desc')
                        ->whereNull('imp.deleted_at')
                        ->get();
    foreach ($datos as $importacion) {
      $imp = ImportacionDiariaMesas::find($importacion->id_importacion_diaria_mesas);
      $con_diferrencias = DetalleImportacionDiariaMesas::where([
        ['id_importacion_diaria_mesas','=',$importacion->id_importacion_diaria_mesas],
                                            ['diferencia_cierre','<>',0]
                                          ])->get();

      if(count($con_diferrencias) == 0){
        $imp->diferencias = 0;
        $imp->save();
      }else{
        $diferencia= 0;
        foreach ($con_diferrencias as $error) {
          $diferencia+= $error->diferencia_cierre;
        }
        $imp->diferencias = $diferencia;
        $imp->save();
      }
      $this->actualizarTotalesImpDiaria($importacion->id_importacion_diaria_mesas);
   }
 }

  ///tengo que agregar al detalle el ultimo cierre con el que fue asociado para el calculo de la diff
  private function calcularDifCierresImp($detalle){
    $cierre = Cierre::find($detalle->id_cierre);
    $detalle_importacion = DetalleImportacionDiariaMesas::find($detalle->id_detalle_importacion_diaria_mesas);
    if($cierre != null){ //si está cargado el cierre de la importacion
      //busco el cierre anterior
      $last_cierre = Cierre::where('fecha','<',$cierre->fecha)
                            ->orderBy('fecha','desc')
                            ->first();
      if($last_cierre != null){
        $check_usado = DetalleImportacionDiariaMesas::where('id_ultimo_cierre',
                                              '=',$last_cierre->id_cierre_mesa)
                                                    ->get();
        if(count($check_usado)==0){
          // calculo la diferencia entre ambos cierres
         $dif_cierres = $last_cierre->total_pesos_fichas_c -
                         $cierre->total_pesos_fichas_c;
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
        }else{
          $detalle_importacion->diferencia_cierre = $detalle_importacion->utilidad;
          $detalle_importacion->cierre()->associate($cierre->id_cierre_mesa);
          $detalle_importacion->save();
        }
      }
    }else{

    }
    //else of all -> las diferencias quedan igual que lo importado..
  }


  public function eliminar($id){
    $imp = ImportacionDiariaMesas::find($id);
    foreach ($imp->detalles as $d) {
      $d->delete();
    }
    ImportacionDiariaMesas::destroy($id);
    return 1;
  }

  public function calcularDiffIMM(){
    $date = new DateTime(); //date & time of right now. (Like time())
    $date->sub(new DateInterval('P2M'));

    $datos = DB::table('importacion_mensual_mesas as imp')
                      ->select('imp.*','det.*')
                      ->join('detalle_importacion_mensual_mesas as det',
                             'det.id_importacion_mensual_mesas','=',
                             'imp.id_importacion_mensual_mesas')
                      ->where('imp.diferencias','<>',0)
                      ->whereNull('imp.deleted_at')
                      ->get();
    foreach ($datos as $importacion) {
       $imp = ImportacionDiariaMesas::find($importacion->id_importacion_mensual_mesas);
       foreach ($imp->detalles as $detalle){
         $impdfecha = ImportacionDiariaMesas::where([['fecha','=',$detalle->fecha_dia],
                                                  ['id_casino','=',$detalle->id_casino],
                                                  ['id_moneda','=',$detalle->id_moneda]
                                                  ])
                                                  ->get();
          $this->calcularDifImpD($detalle,$impdfecha);
       }


      $con_diferencias = DetalleImportacionMensualMesas::where([
      ['id_importacion_mensual_mesas','=',$importacion->id_importacion_diaria_mesas],
      ['diferencias','<>',0]])->get();

      if(count($con_diferencias) == 0){
        $imp->diferencias = 0;
        $imp->save();
      }
      $this->actualizarTotales($importacion->id_importacion_mensual_mesas);
   }
  }

  /*
  * recibe detalle de imp mensual y la importacion diaria de esa fecha
  */
  private function calcularDifImpD($detalle,$impdfecha){

    $utilidad_calculada = $impdfecha->saldo_diario_fichas + $detalle->total_diario
                          - $detalle->reposiciones_dia +$detalle->retiros_dia;
    $diferencia_utilidad = $detalle->utilidad - $utilidad_calculada;
    $detalle->diferencias = $diferencia_utilidad;
    $detalle->saldo_fichas_dia = $impdfecha->saldo_diario_fichas;
    $detalle->utilidad_calculada_dia = $utilidad_calculada;
    $detalle->save();
  }

  private function actualizarTotales($id_importacion_mensual_mesas)
  {
    $imp = ImportacionMensualMesas::find($id_importacion_mensual_mesas);
    $total_mensual = 0;
    $diferencias = 0;
    $utilidad_calculada = 0;
    $retiros_mes = 0;
    $reposiciones_mes = 0;
    $saldo_fichas_mes = 0;
    $total_utilidad_mensual = 0;
    foreach ($imp->detalles as $detalle) {
      $total_mensual += $imp->total_diario;
      $diferencias += $imp->diferencias;
      $utilidad_calculada += $imp->utilidad_calculada_dia;
      $retiros_mes += $imp->retiros_dia;
      $reposiciones_mes += $imp->reposiciones_dia;
      $saldo_fichas_mes += $imp->saldo_fichas_dia;
      $total_utilidad_mensual += $imp->total_diario;
    }
    $imp->total_mensual;
    $imp->diferencias;
    $imp->saldo_fichas_mes;
    $imp->utilidad_calculada;
    $imp->retiros_mes;
    $imp->reposiciones_mes;
    $imp->save();
  }

}
