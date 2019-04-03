<?php

namespace App\Http\Controllers\Mesas\Importaciones\Mesas;

use Auth;
use Session;
use Illuminate\Http\Request;
use Response;
use App\Http\Controllers\Controller;

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

use App\Mesas\ImportacionMensualMesas;
use App\Mesas\DetalleImportacionMensualMesas;

use App\Http\Controllers\UsuarioController;

class MensualController extends Controller
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

    return view('Importaciones.importacionMensual',  [
                                                'mensuales' =>[],
                                                'casinos'=>$casinos,
                                                'moneda'=>$monedas,
                                              ]);
  }

  public function buscar($id_importacion){
   $importacion = ImportacionMensualMesas::find($id_importacion);
   return [
             'importacion' => $importacion,
             'casino' => $importacion->casino,
             'detalles' => $importacion->detalles,
             'moneda' => $importacion->moneda
           ];
  }

 public function importarMensual(Request $request){
   $importacion = new ImportacionMensualMesas;
   $importacion->save();
   $validator =  Validator::make($request->all(),[
     'id_casino' => 'required|exists:casino,id_casino',
     'id_moneda' => 'required|exists:moneda,id_moneda',
     'fecha' => 'required'
   ], array(), self::$atributos)->after(function($validator) use ($importacion){
     //dd($importacion);
                 if($validator->getData()['id_casino'] != 0 &&
                     $validator->getData()['id_moneda'] != 0
                   ){
                     $lista = $this->existeImportacion($validator,$importacion);
                     $validator = $lista[0];
                     $importacion = $lista[1];
                     $validator = $this->validarHeaders($validator);
                     if($importacion != null){
                       $lista2 = $this->validarCarga($validator,$importacion);
                       $validator = $lista2[0];
                       $importacion = $lista2[1];

                       if($importacion != null){
                         $lista3 = $this->validarFechas($importacion,$validator);
                         $validator = $lista3[0];
                         $importacion = $lista3[1];
                       }
                     }
                 }else{
                   ImportacionMensualMesas::destroy($importacion->id_importacion_mensual_mesas);
                 }
                 })->validate();
   if(isset($validator)){
     if ($validator->fails()){
         ImportacionMensualMesas::destroy($importacion->id_importacion_mensual_mesas);
         return ['errors' => $validator->messages()->toJson()];

       }
    }

   //crear los $detalles
   try{
     /*
       row_1 fecha 0
       row_3 venta ->total diario? 1
       row_4 utilidad 2
       row_5 fill 3
       row_6 credit 4
     */
     $pdo = DB::connection('mysql')->getPdo();
     DB::connection()->disableQueryLog();
     $crea_detalles = sprintf("INSERT INTO detalle_importacion_mensual_mesas
                               (id_importacion_mensual_mesas,
                               fecha_dia,
                               total_diario,
                               utilidad,
                               reposiciones_dia,
                               retiros_dia
                              )
                               SELECT csv.id_archivo,
                                      csv.row_8,
                                      csv.row_3,
                                      csv.row_4,
                                      csv.row_5,
                                      csv.row_6
                               FROM filas_csv_mesas_bingos as csv
                               WHERE csv.id_archivo = '%d';
                               ",$importacion->id_importacion_mensual_mesas,
                               $importacion->id_moneda);

                               // dd([$crea_detalles,$importacion->id_importacion_mensual_mesas,
                               // $importacion->id_casino,
                               // $importacion->id_casino,
                               // $importacion->id_moneda]);
      $pdo->exec($crea_detalles);
   }catch(Exception $e){
     dd($e);
   }
   $this->actualizarTotales($importacion->id_importacion_mensual_mesas);
   DB::table('filas_csv_mesas_bingos')->where('id_archivo','=',$importacion->id_importacion_mensual_mesas)->delete();
   return 1;
 }


 //dias del mes
 private function validarFechas($importacion,$validator){
   $csv = CSVImporter::where('id_archivo','=',$importacion->id_importacion_mensual_mesas)
                        ->orderBy('row_8','asc')->get();
    $ff = explode('-',$importacion->fecha_mes);
    $end = $validator->getData()['diasDelmes'];
    //dd($end);
    if(count($csv) == $end){

      for ($i=1; $i <= $end ; $i++) {
        $csv_fecha = CSVImporter::where('id_archivo','=',$importacion->id_importacion_mensual_mesas)
                            ->where('row_8','=',$i)
                             ->orderBy('row_8','asc')->get();
        if(count($csv_fecha) != 1){
          $validator->errors()->add('error','En fecha '.$i.'.'
                                    );
          ImportacionMensualMesas::destroy($importacion->id_importacion_mensual_mesas);
          $importacion = null;
        }
      }
    }else{
      $validator->errors()->add('error','No se encontró una fila por cada día del mes.'
                                );
      ImportacionMensualMesas::destroy($importacion->id_importacion_mensual_mesas);
      $importacion = null;
    }

    return [$validator,$importacion];
 }

 private function existeImportacion($validator ,$importacion){
   $fecha = explode('-',$validator->getData()['fecha']);
   $check_import = ImportacionMensualMesas::where([['id_casino','=',$validator->getData()['id_casino']],
                                                   ['id_moneda','=',$validator->getData()['id_moneda']]
                                                   ])
                                            ->whereYear('fecha_mes','=',$fecha[0])
                                            ->whereMonth('fecha_mes','=',$fecha[1])
                                            ->get();
    if(count($check_import)>0){
      $casino = Casino::find($validator->getData()['id_casino']);
      $moneda = Moneda::find($validator->getData()['id_moneda']);
      $validator->errors()->add('error','Ya existe un archivo importado para la fecha:'.$validator->getData()['fecha']
                                .' de '.$casino->nombre.' en '.$moneda->siglas.'.'
                                );
      ImportacionMensualMesas::destroy($importacion->id_importacion_mensual_mesas);
      $importacion = null;
    }
    return [$validator,$importacion];
 }

 private function validarHeaders($validator){
   $path = $validator->getData()['archivo']->getRealPath();
   $fila = 1;
   if (($gestor = fopen($path, "r")) !== FALSE) {
       while (($datos = fgetcsv($gestor, 1000, ",")) == 1) {
           $cantidad_columnas = count($datos);
           if($cantidad_columnas == 5){
             //ok
           }else{
             $validator->errors()->add('error','Las columnas del archivo deben ser: \nFECHA,VENTA,UTILIDAD,COTIZACION.');
           }
       }
       fclose($gestor);
   }else{
     $validator->errors()->add('error','No se pudo leer el archivo');
   }
   return $validator;
 }

 private function validarCarga($validator, $importacion){

   $importacion->fecha_mes = $validator->getData()['fecha'].'-01';
   $importacion->moneda()->associate($validator->getData()['id_moneda']);
   $importacion->casino()->associate($validator->getData()['id_casino']);
   $importacion->diferencias = 1;
   $importacion->validado = 0;
   $importacion->save();

   $pdo = DB::connection('mysql')->getPdo();
   DB::connection()->disableQueryLog();
   $path = $validator->getData()['archivo']->getRealPath();
   //$anio = 2018;//explode($importacion->fecha_mes,'-')[0];
   $query = sprintf("LOAD DATA local INFILE '%s'
                     INTO TABLE filas_csv_mesas_bingos
                     FIELDS TERMINATED BY ';'
                     OPTIONALLY ENCLOSED BY '\"'
                     ESCAPED BY '\"'
                     LINES TERMINATED BY '\\n'
                     IGNORE 1 LINES
                     (@0,@1,@2,@3,@4)
                     SET id_archivo = '%d',
                         row_8 = @0,
                         row_3 = CAST(REPLACE(@1,',','') as DECIMAL(15,2)),
                         row_4 = CAST(REPLACE(@2,',','') as DECIMAL(15,2)),
                         row_5 = CAST(REPLACE(@3,',','.') as DECIMAL(15,2)),
                         row_6 = CAST(REPLACE(@3,',','.') as DECIMAL(15,2));
                     ",$path,$importacion->id_importacion_mensual_mesas);
                     /*
                       row_1 fecha 0
                       row_3 venta ->total diario? 1
                       row_4 utilidad 2
                       row_5 fill 3
                       row_6 credit 4
                     */

                     /* en las diarias::
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
     //dd($query);
   }catch(Exception $e){
     //dd('ij');
     $validator->errors()->add('error','La 1er columna debe tener la fecha (aaaa-mm-dd),'.
     ' la 2da la venta, y luego utilidad, cotización'.
     ' (números de hasta 15 dígitos separado de los decimales con coma, '.
     'si no posee cotización completar con 0s)');
     $importacion = null;
   }
   return [$validator,$importacion];
 }

 public function filtros(Request $request){
   $reglas=array();

   if($request->id_moneda !=0 && !empty($request->id_moneda)){
     $reglas[]=['importacion_mensual_mesas.id_moneda' , '=' , $request->id_moneda ];
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

       $sort_by = ['columna' => 'fecha_mes','orden'=>'desc'];
   }

   if(!isset($request->fecha) || $request->fecha == 0 ){
     $resultados = DB::table('importacion_mensual_mesas')
                       ->join('moneda','moneda.id_moneda','=','importacion_mensual_mesas.id_moneda')
                       ->join('casino','casino.id_casino','=','importacion_mensual_mesas.id_casino')
                       ->whereIn('casino.id_casino',$casinos)
                       ->where($reglas)
                       ->whereNull('importacion_mensual_mesas.deleted_at')
                       ->when($sort_by,function($query) use ($sort_by){
                                       return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                   })
                       ->paginate($request->page_size);
   }else{
     $fecha=explode("-", $request['fecha']);
     $resultados = DB::table('importacion_mensual_mesas')
                       ->join('moneda','moneda.id_moneda','=','importacion_mensual_mesas.id_moneda')
                       ->join('casino','casino.id_casino','=','importacion_mensual_mesas.id_casino')
                       ->whereYear('fecha_mes' , '=' ,$fecha[0])
                       ->whereMonth('fecha_mes','=', $fecha[1])
                       ->where($reglas)
                       ->whereNull('importacion_mensual_mesas.deleted_at')
                       ->whereIn('casino.id_casino',$casinos)
                       ->when($sort_by,function($query) use ($sort_by){
                                       return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                   })
                       ->paginate($request->page_size);

   }

   return ['importaciones'=>$resultados] ;
 }

 public function guardarObservacion(Request $request){
   $validator=  Validator::make($request->all(),[
     'id_importacion' => 'required|exists:importacion_mensual_mesas,id_importacion_mensual_mesas',
     'observacion' => 'nullable'
   ], array(), self::$atributos)->after(function($validator){  })->validate();
   if(isset($validator)){
     if ($validator->fails()){
         return ['errors' => $validator->messages()->toJson()];
         }
    }

   $importacion = ImportacionMensualMesas::find($request->id_importacion);
   $importacion->observacion  = $request->observacion;
   $importacion->validado = 1;
   $importacion->save();
   return response()->json(['ok' => true], 200);
 }

 public function eliminar($id){
   $imp = ImportacionMensualMesas::find($id);
   foreach ($imp->detalles as $d) {
     $d->delete();
   }
   ImportacionMensualMesas::destroy($id);
   return 1;
 }


  public function actualizarTotales($id_importacion_mensual_mesas)
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
    //por cada importacion mensual
    foreach ($datos as $importacion) {
       $imp = ImportacionDiariaMesas::find($importacion->id_importacion_mensual_mesas);
       //obtengo us detalles y los mando a controlar
       foreach ($imp->detalles as $detalle){
         $impdfecha = ImportacionDiariaMesas::where([['fecha','=',$detalle->fecha_dia],
                                                  ['id_casino','=',$detalle->id_casino],
                                                  ['id_moneda','=',$detalle->id_moneda]
                                                  ])
                                                  ->get()->first();
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
    if($impfecha != null){

      $utilidad_calculada = $impdfecha->saldo_diario_fichas + $detalle->total_diario
                            - $detalle->reposiciones_dia +$detalle->retiros_dia;
      $diferencia_utilidad = $detalle->utilidad - $utilidad_calculada;
      $detalle->diferencias = $diferencia_utilidad;
      $detalle->saldo_fichas_dia = $impdfecha->saldo_diario_fichas;
      $detalle->utilidad_calculada_dia = $utilidad_calculada;
      $detalle->save();
    }else{
      $utilidad_calculada = 0;
      $diferencia_utilidad = $detalle->utilidad - $utilidad_calculada;
      $detalle->diferencias = $diferencia_utilidad;
      $detalle->saldo_fichas_dia = $impdfecha->saldo_diario_fichas;
      $detalle->utilidad_calculada_dia = $utilidad_calculada;
      $detalle->save();
    }
  }


}
?>
