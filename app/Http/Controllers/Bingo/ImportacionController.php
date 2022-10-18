<?php

namespace App\Http\Controllers\Bingo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\DB;
use App\Bingo\ImportacionBingo;
use App\Bingo\ReporteEstado;
use Validator;
use App\Casino;

use App\Http\Controllers\Bingo\ReportesController;
class ImportacionController extends Controller
{
    private static $instance;
    public static function getInstancia() {
      if (!isset(self::$instance)) {
        self::$instance = new self();
      }
      return self::$instance;
    }
    private static $atributos = [
    ];
    public function index(){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));

      $casinos=array();
      foreach($usuario['usuario']->casinos as $casino){
        $casinos[]=$casino->id_casino;
      }
      //agrego a seccion reciente a BINGo
      UsuarioController::getInstancia()->agregarSeccionReciente('Importación Bingo' , 'importacion-bingo');


      return view('Bingo.importacion', ['casinos' => $casinos]);
    }

    public function buscarRelevamiento(Request $request){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));

      $casinos=array();
      foreach($usuario['usuario']->casinos as $casino){
        $casinos[]=$casino->id_casino;
      }

      $sort_by = $request->sort_by;
      $resultados = DB::table('bingo_importacion')
      ->select('bingo_importacion.*', 'casino.codigo', 'usuario.nombre')
      ->leftJoin('casino' , 'bingo_importacion.id_casino','=','casino.id_casino')
      ->leftJoin('usuario', 'bingo_importacion.id_usuario', '=', 'usuario.id_usuario')
      ->when($sort_by,function($query) use ($sort_by){
        return $query->orderBy($sort_by['columna'],$sort_by['orden']);
      },function($query){
        return $query->orderBy('fecha','desc');
      })
      ->whereIn('bingo_importacion.num_partida',[0,1])
      ->whereIn('casino.id_casino', $casinos);
      if($request->casino!=0){
        $resultados = $resultados->where('casino.id_casino','=',$request->casino);
      }
      if(isset($request->fecha)){
        $ff = explode('-', $request->fecha);
        $aaaa = $ff[0];
        $mm = $ff[1];
        $resultados = $resultados->whereYear('fecha', '=', $aaaa)
        ->whereMonth('fecha','=', $mm);
      }
     return $resultados->paginate($request->page_size);
    }

    public function eliminarImportacion($id){
      $importaciones = $this->obtenerImportacionCompleta($id);
      ReportesController::getInstancia()->reporteEstadoSet($sesion->id_casino, $sesion->fecha_inicio,'importacion',0);
      foreach ($importaciones as $importacion) {
        $eliminar = ImportacionBingo::findOrFail($importacion->id_importacion);
        $eliminar->delete();
      }
      return ['importaciones' => $importaciones];
    }

    public function guardarImportacion(Request $request){
      $id_usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $casino  = Casino::findOrFail($request->id_casino)->codigo;

      //obtengo el archivo
      $archivoCSV = $request->archivo;
      //obtengo la fecha del archivo importado para luego validar
      $nfecha = $this->fechaArchivo($archivoCSV);
      if(is_null($nfecha)) return $this->errorOut(['archivo' => 'No se pudo obtener la fecha del archivo.']);

      //si decidió guardar igual un archivo con fecha y casino ya importado, borro
      //cargo las observaciones de re importación
      if($request->guarda_igual == 1){
        //Validación del motivo
        Validator::make($request->all(), ['motivo' => 'required'])->validate();
        $importacion = ImportacionBingo::where([
          ['bingo_importacion.id_casino', '=', $request->id_casino],
          ['bingo_importacion.fecha', '=', $nfecha]]
        )->first();
        $this->eliminarImportacion($importacion->id_importacion);
        DB::table('bingo_obs_importacion')
        ->insert([
          'id_casino' => $request->id_casino,
          'fecha_importacion' => $nfecha,
          'id_usuario' => $id_usuario,
          'observacion' => $request->motivo
        ]);
      }

      //valido la importacion
      $this->validarImportacion($request->id_casino, $nfecha);

      //abro el archivo
      $fileHandle = fopen($archivoCSV->getRealPath(), "r");
      //guardo todas las filas en lines, como csv corta con ',', habrá más columnas por fila
      $lines = array();
      while( ($row = fgetcsv($fileHandle)) !== FALSE ) {
        $lines[] = $row;
      }

      $resultado = array(); //variable auxiliar para guardar  el array separando

      //si es de MEL/StaFe
      if($request->id_casino == '1' || $request->id_casino == '2'){
        foreach ($lines as $line) {
          //limpio las lineas, guardando todo el array en una sola linea
          $puntoxnada = str_replace('.','',implode(',',$line));
          $comaxpunto = str_replace(',','.', $puntoxnada);
          $sinpeso = str_replace('$','', $comaxpunto);
          $final = str_replace(' ', '', $sinpeso);
          //creo el array separando las columnas de la fila
          $resultado[] = explode(';', $final);
        }
      }else{ //es de rosario
        foreach ($lines as $line) {
          $final = str_replace(',','',implode(',',$line));
          $sinpeso = str_replace('$','', $final);
          $resultado[] = explode(';', $sinpeso);
        }
      }
      //@Elegancia: Podria estandarizarse la forma que se manda $resultado, para que no tenga que switchear internamente.
      $todo_ok = DB::transaction(function() use($resultado,$request,$id_usuario,$nfecha){
        return $this->guardarImportacionArr($resultado,$request->id_casino,$id_usuario,$nfecha);
      });
      if(!$todo_ok) {
        return $this->errorOut(['archivo_valido' => 'El archivo que esta queriendo importar no es válido para el casino seleccionado.']);
      }
      return 1;
    }

    public function obtenerImportacionCompleta($id){
      $importacion = ImportacionBingo::findOrFail($id);

      $reglas = array();
      $reglas[]=['bingo_importacion.fecha', '=', $importacion->fecha];
      $reglas[]=['bingo_importacion.id_casino', '=', $importacion->id_casino];

      $respuesta = DB::table('bingo_importacion')
                        ->select('bingo_importacion.*')
                        ->where($reglas)
                        ->get();

      return $respuesta;
    }

    public function obtenerImportacionFC($fecha, $id_casino){
      $importacion = ImportacionBingo::where('id_casino','=',$id_casino)->where('fecha','=',$fecha)->first();
      if($importacion != null) return $this->obtenerImportacionCompleta($importacion->id_importacion);
      return -1;
    }

    protected function guardarImportacionArr($resultado,$id,$usuario,$fecha_archivo){
      $una_importacion = new ImportacionBingo;
      //Por si viene un archivo vacio
      $una_importacion->id_casino         = $id;
      $una_importacion->id_usuario        = $usuario;
      $una_importacion->num_partida       = 0;
      $una_importacion->hora_inicio       = 0;
      $una_importacion->serieA            = 0;
      $una_importacion->carton_inicio_A   = 0;
      $una_importacion->carton_fin_A      = 0;
      $una_importacion->cartones_vendidos = 0;
      $una_importacion->serieB            = 0;
      $una_importacion->carton_inicio_B   = 0;
      $una_importacion->carton_fin_B      = 0;
      $una_importacion->valor_carton      = 0;
      $una_importacion->cant_bola         = 0;
      $una_importacion->recaudado         = 0;
      $una_importacion->premio_linea      = 0;
      $una_importacion->premio_bingo      = 0;
      $una_importacion->pozo_dot          = 0;
      $una_importacion->pozo_extra        = 0;
      $una_importacion->fecha             = $fecha_archivo;
      if(count($resultado) == 0){
        $una_importacion->save();
        return true;
      }
      foreach($resultado as $row){
        $importacion = clone $una_importacion;
        //Si manda sin numero de partida lo ignoramos, pasa a veces que mandan una fila toda vacia (i.e. ;;;;;;;... etc)
        if($row[0] == '') continue;
        if($id == 3){//Rosario
          $fecha = explode('/', $row[18]);
          //si no pudo separar la fecha en 3 partes, el archivo no es válido
          if(count($fecha) != 3) {
            return false;
          }
          $nfecha = $fecha[2] . '-' . $fecha[1] . '-' . $fecha[0];
          $importacion->num_partida       = (int)$row[0];
          $importacion->hora_inicio       = $row[1];
          $importacion->serieA            = (int)$row[3];
          $importacion->carton_inicio_A   = (int)$row[4];
          $importacion->carton_fin_A      = (int)$row[5];
          $importacion->cartones_vendidos = (int)$row[9];
          if($row[6] != '')  $importacion->serieB          = (int)$row[6];
          if($row[7] != '')  $importacion->carton_inicio_B = (int)$row[7];
          if($row[8] != '')  $importacion->carton_fin_B    = (int)$row[8];
          $importacion->valor_carton      = (int)$row[11];
          if($row[12] != '') $importacion->cant_bola       = (int)$row[12];
          $importacion->recaudado         = (float)$row[13];
          $importacion->premio_linea      = (float)$row[14];
          $importacion->premio_bingo      = (float)$row[15];
          $importacion->pozo_dot          = (float)$row[16];
          $importacion->pozo_extra        = (float)$row[17];
          $importacion->fecha             = $nfecha;
        }
        else if($id == 1 || $id == 2){//SFE/MEL
          if(count($row) != 20) {
            return false;
          }
          $fecha = explode('/', $row[19]);
          $nfecha = $fecha[2] . '-' . $fecha[1] . '-' . $fecha[0];
          $importacion->num_partida       = (int)$row[0];
          $importacion->hora_inicio       = $row[1];
          $importacion->serieA            = (int)$row[3];
          $importacion->carton_inicio_A   = (int)$row[4];
          $importacion->carton_fin_A      = (int)$row[5];
          $importacion->cartones_vendidos = (int)$row[6];
          $importacion->serieB            = (int)$row[7];
          $importacion->carton_inicio_B   = (int)$row[8];
          $importacion->carton_fin_B      = (int)$row[9];
          $importacion->valor_carton      = (int)$row[12];
          $importacion->cant_bola         = (int)$row[13];
          $importacion->recaudado         = (float)$row[14];
          $importacion->premio_linea      = (float)$row[15];
          $importacion->premio_bingo      = (float)$row[16];
          $importacion->pozo_dot          = (float)$row[17];
          $importacion->pozo_extra        = (float)$row[18];
          $importacion->fecha             = $nfecha;
        }
        else{
          return false;
        }
        $importacion->save();
      }
      //Guardo la información para el reporte de estados
      ReportesController::getInstancia()->reporteEstadoSet($id, $fecha_archivo,'importacion',1);
      return true;
    }

    //Funcion auxiliar para obtener la fecha de la importacion
    protected function fechaArchivo($archivo){
      $path = $archivo->getRealPath();
      $fileHandle_validator = fopen($path, "r");
      $file_line = fgetcsv($fileHandle_validator,null,';');
      $error_leer = is_null($file_line) || ($file_line === false);
      if(!$error_leer){
        $fecha = end($file_line);
        $fecha_arr = explode('/',$fecha);
        $dia_1digito = count($fecha_arr[0]) < 2;
        $mes_1digito = count($fecha_arr[1]) < 2;
        $ret = $fecha_arr[2].'-'.($mes_1digito? '0' : '').$fecha_arr[1].'-'.($dia_1digito? '0' : '').$fecha_arr[0];
        return $ret;
      }
      //Fallback, leo del nombre
      $filename = $archivo->getClientOriginalName();
      //Puede ser nombre estilo "Resumen Sesion Jugadas 08-03-2020.csv" o "030820.csv"
      $es_mmddyy = strpos($filename,'Resumen') === false;

      if($es_mmddyy){
        $matches = [];
        preg_match('/[0-9]{6,6}/',$filename,$matches);
        if(count($matches) == 0) return null;
        $f = $matches[0];
        return '20'.$f[4].$f[5].'-'.$f[0].$f[1].'-'.$f[2].$f[3];
      }
      else{//dd-mm-yyyy
        $matches = [];
        preg_match('/[0-9]{2,2}-[0-9]{2,2}-[0-9]{4,4}/',$filename,$matches);
        if(count($matches) == 0) return null;
        $f = $matches[0];
        return substr($f,6,4).'-'.substr($f,3,2).'-'.substr($f,0,2);
      }
      return null;
    }
    //Función para validar la importacion
    protected function validarImportacion($id_casino, $fecha){
      Validator::make(['id_casino' => $id_casino, 'fecha' =>$fecha],[
          'id_casino' => 'required'
      ], array(), self::$atributos)->after(function($validator){
        //valido que no exista importacion para el mismo día en el casino
        $regla_carga = array();
        $regla_carga [] =['bingo_importacion.id_casino', '=', $validator->getData()['id_casino']];
        $regla_carga [] =['bingo_importacion.fecha', '=', $validator->getData()['fecha']];
        $carga = ImportacionBingo::where($regla_carga)->count();
        if($carga > 0){
          $validator->errors()->add('importacion_cargada','La importación para esta fecha se encuentra cargarda.');
        }
      })->validate();
    }

    private function errorOut($map){
      return response()->json($map,422);
    }
}
