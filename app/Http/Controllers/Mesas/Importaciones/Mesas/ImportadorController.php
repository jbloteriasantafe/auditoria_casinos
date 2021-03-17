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
        $validator->errors()->add('fecha', 'No es posible importar una fecha futura.');
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
      }

      //VALIDO LA FECHA
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

      //VALIDO EL CSV
      //Chequeamos por borrados? Y si esta importando uno viejo? no me parece correcto
      $path = $data['archivo']->getRealPath();
      $moneda = Moneda::find($id_moneda);
      if (($gestor = fopen($path, "r")) !== FALSE) {
          $datos = fgetcsv($gestor, 1000, ";");
          if(!$datos || count($datos) != 6){
            $validator->errors()->add('error','Las columnas del archivo deben ser: \nJUEGO,NRO MESA,DROP,UTILIDAD,FILL,CREDIT.');
            return;
          }
          while($datos = fgetcsv($gestor, 1000, ";")){
            $juego = $datos[0];
            if($juego == "" || substr($juego,0,7) == "TOTALES") continue;
            
            $juego_bd = DB::table('juego_mesa')->where('juego_mesa.id_casino','=',$id_casino)
            ->where(function($q) use ($juego){
              return $q->where('juego_mesa.siglas','like',$juego)->orWhere('juego_mesa.nombre_juego','like',$juego);
            });
            $existe_juego = (clone $juego_bd)->count() > 0;
            if(!$existe_juego){
              $validator->errors()->add('error','No se encontro el juego '.$juego.' en el sistema');
              continue;
            }

            $mesa = $datos[1];
            if($mesa == "") continue;

            $existe_mesa = $juego_bd->join('mesa_de_panio as mesa','mesa.id_juego_mesa','=','juego_mesa.id_juego_mesa')
            ->where('mesa.id_casino','=',$id_casino)->where('mesa.nro_admin','=',$mesa)->where('mesa.id_moneda','=',$id_moneda)
            ->count() > 0;
            if(!$existe_mesa){
              $validator->errors()->add('error','No se encontro la mesa '.$juego.$mesa.'-'.$moneda->siglas.' en el sistema');
              continue;
            }
          }
          fclose($gestor);
      }else{
        $validator->errors()->add('error','No se pudo leer el archivo');
        return;
      }
    })->validate();

    DB::transaction(function() use ($request,&$importacion){
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
          row_7      = '%s'",$path,$iid, $importacion->fecha
      );

      $pdo->exec($query);

      $crea_detalles = sprintf("INSERT INTO detalle_importacion_diaria_mesas
        (id_importacion_diaria_mesas, id_mesa_de_panio, id_moneda,
        fecha, utilidad, droop, reposiciones, retiros, id_juego_mesa,
        nro_mesa, nombre_juego, codigo_moneda, diferencia_cierre, tipo_mesa)
        SELECT 
        csv.id_archivo, mesa.id_mesa_de_panio, moneda.id_moneda,
        csv.row_7, csv.row_4, csv.row_3, csv.row_5, csv.row_6, juego.id_juego_mesa,
        mesa.nro_mesa, juego.nombre_juego, moneda.siglas, csv.row_4, tipo_mesa.descripcion
        FROM filas_csv_mesas_bingos as csv
        JOIN juego_mesa as juego ON (juego.nombre_juego LIKE csv.row_1 OR juego.siglas LIKE csv.row_1)
        JOIN tipo_mesa ON (juego.id_tipo_mesa = tipo_mesa.id_tipo_mesa)
        JOIN mesa_de_panio as mesa ON (mesa.id_juego_mesa = juego.id_juego_mesa AND mesa.nro_admin = csv.row_2)
        JOIN moneda ON (moneda.id_moneda = mesa.id_moneda)
        WHERE csv.id_archivo = '%d' AND juego.id_casino = '%d' AND mesa.id_casino = '%d' AND moneda.id_moneda = '%d'
        AND csv.row_1 <> '' AND csv.row_2 <> '' AND SUBSTR(csv.row_1,0,7) <> 'TOTALES';",
        $importacion->id_importacion_diaria_mesas,
        $importacion->id_casino,
        $importacion->id_casino,
        $importacion->id_moneda
      );

      $pdo->exec($crea_detalles);
      $importacion->nombre_csv = $request->archivo->getClientOriginalName();
      $importacion->save();

      $this->calcularDiffIDM($iid);

      DB::table('filas_csv_mesas_bingos')->where('id_archivo','=',$importacion->id_importacion_diaria_mesas)->delete();
    });
    return 1;
  }

  //fecha casino moneda
  public function filtros(Request $request){
    $reglas=array();

    if(!empty($request->id_moneda)){
      $reglas[]=['importacion_diaria_mesas.id_moneda','=',$request->id_moneda];
    }

    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = array();
    foreach($usuario->casinos as $casino){
      $casinos[]=$casino->id_casino;
    }

    if(!empty($request->casino)){
      $reglas[]=['importacion_diaria_mesas.id_casino','=',$request->id_casino];
    }

    $sort_by = ['columna' => 'fecha','orden'=>'desc'];
    if(!empty( $request->sort_by)){
      $sort_by = $request->sort_by;
    }

    $resultados = DB::table('importacion_diaria_mesas')
    ->join('moneda','moneda.id_moneda','=','importacion_diaria_mesas.id_moneda')
    ->join('casino','casino.id_casino','=','importacion_diaria_mesas.id_casino')
    ->whereIn('casino.id_casino',$casinos)
    ->where($reglas)->whereNull('importacion_diaria_mesas.deleted_at');

    if(isset($request->fecha)){
      $f = explode("-", $request['fecha']);
      $resultados = $resultados->whereYear('fecha' , '=' ,$f[0])->whereMonth('fecha','=', $f[1])->whereDay('fecha','=', $f[2]);
    }

    $resultados = $resultados->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    })->paginate($request->page_size);
    return ['importaciones'=>$resultados] ;
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
}
