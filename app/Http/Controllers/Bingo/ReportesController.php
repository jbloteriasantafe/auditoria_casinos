<?php

namespace App\Http\Controllers\Bingo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Utils;
use App\Bingo\ReporteEstado;
use App\Bingo\SesionBingo;
use App\Bingo\ImportacionBingo;
use App\Bingo\PartidaBingo;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\Bingo\SesionesController;
use App\Http\Controllers\Bingo\ImportacionController;
use Illuminate\Support\Facades\DB;

class ReportesController extends Controller{
    private static $instance;
    public static function getInstancia() {
      if (!isset(self::$instance)) {
        self::$instance = new self();
      }
      return self::$instance;
    }
    //función index de reporte de diferencia
    public function reportesDiferencia(){
        //Busco los casinos a los que esta asociado el usuario
        $casinos = UsuarioController::getInstancia()->getCasinos();
        //agrego a seccion reciente a BINGo
        UsuarioController::getInstancia()->agregarSeccionReciente('Reporte Diferencia' , 'diferencia-bingo');

        return view('Bingo.ReporteDiferencia', ['casinos' => $casinos, 'estados' => []]);
    }
    
    public function buscarReportesDiferencia(Request $request){
      return $this->buscarEstado($request);
    }
    //BUSCAR ESTADOS
    public function buscarEstado(Request $request,$id_importacion = null){
      $sort_by = ['columna' => 'fechas.fecha','orden' => 'desc'];
      if(!empty($request->sort_by)){
        $sort_by['columna'] = $request->sort_by['columna'];
        $sort_by['orden']   = $request->sort_by['orden'];
      }
      $reglas = [];
      if(!empty($request->casino)){
        $reglas[] = ['c.id_casino','=',$request->casino];
      }
      if(!is_null($id_importacion)){
        $reglas[] = ['bi.id_importacion','=',$id_importacion];
      }
      $id_casinos = UsuarioController::getInstancia()->quienSoy()['usuario']->casinos->map(function($c){
        return $c->id_casino;
      })->toArray();
      
      $resultados = DB::table('casino as c')
      ->selectRaw('fechas.fecha as fecha_sesion,c.nombre as casino,
       MAX(bi.fecha)        as imp_fecha_inicio,
       MAX(bi.hora_inicio)  as imp_hora_inicio,
       MAX(bs.fecha_inicio) as ses_fecha_inicio,
       MAX(bs.hora_inicio)  as ses_hora_inicio,
       MAX(IF(bs.id_estado = 2,bs.id_sesion,NULL)) as sesion_cerrada,
       MAX(bp.id_partida) as relevamiento,
       MAX(bi.id_importacion) as importacion,
       MAX(IF(bre.visado,bre.id_reporte_estado,NULL)) as visado'
      )
      ->join(//Todas las fechas que existen para el casino, sean de sesion,importacion o reporte
        DB::raw('(
          SELECT distinct id_casino,fecha_inicio as fecha FROM bingo_sesion
          UNION
          SELECT distinct id_casino,fecha as fecha FROM bingo_importacion
          UNION
          SELECT distinct id_casino,fecha_sesion as fecha FROM bingo_reporte_estado
          ) as fechas'
        ),
        'fechas.id_casino','=','c.id_casino'
      )
      ->leftJoin('bingo_sesion as bs',function($j){
        return $j->on('bs.fecha_inicio','=','fechas.fecha')->on('bs.id_casino','=','c.id_casino');
      })
      ->leftJoin('bingo_partida as bp','bp.id_sesion','=','bs.id_sesion')//Chequear todos los cartones?
      ->leftJoin('bingo_importacion as bi',function($j){
        return $j->on('bi.fecha','=','fechas.fecha')->on('bi.id_casino','=','c.id_casino');
      })//Mover el visado a la importacion
      ->leftJoin('bingo_reporte_estado as bre',function($j){
        return $j->on('bre.fecha_sesion','=','fechas.fecha')->on('bre.id_casino','=','c.id_casino');
      })
      ->where($reglas)->whereIn('c.id_casino',$id_casinos)
      ->groupBy('fechas.fecha','c.id_casino')
      ->orderBy($sort_by['columna'],$sort_by['orden']);
      
      if(!is_null($id_importacion)) return $resultados->first();
      return $resultados->paginate($request->page_size);
    }
    
    //Funcion para guardar el estado de cargada la importación
    public function guardarReporteEstado($id_casino, $fecha, $valor){
      //armo las reglas para filtrar los datos del reporte
      $reglas = array();
      $reglas [] =['fecha_sesion','=', $fecha];
      $reglas [] =['id_casino','=', $id_casino];
      //busco el reporte que cumpla con las reglas
      $reporte = ReporteEstado::where($reglas)->first();
      //si aún no existe reporte, lo creo. Sino, modifico el que encontro.
      //dependiendo de donde viene($valor), asigno 1 ->SI
      if($reporte == null){
        $reporte = new ReporteEstado;
        $reporte->fecha_sesion = $fecha;
        $reporte->id_casino = $id_casino;
        if($valor == 1)$reporte->importacion = 1;
        if($valor == 2)$reporte->sesion_cerrada = 1;
        if($valor == 3)$reporte->relevamiento = 1;
        if($valor == 4)$reporte->sesion_abierta = 1;
        $reporte->save();
      }else{
        $reporte->fecha_sesion = $fecha;
        $reporte->id_casino = $id_casino;
        if($valor == 1)$reporte->importacion = 1;
        if($valor == 2)$reporte->sesion_cerrada = 1;
        if($valor == 3)$reporte->relevamiento = 1;
        if($valor == 4)$reporte->sesion_abierta = 1;
        $reporte->save();
      }
    }
    //Funcion para guardar el estado de cargada la importación
    public function eliminarReporteEstado($id_casino, $fecha, $valor){
      //armo las reglas para filtrar los datos del reporte
      $reglas = array();
      $reglas [] =['fecha_sesion','=', $fecha];
      $reglas [] =['id_casino','=', $id_casino];
      //como sé que existe un reporte de estado, le cambio el valor a 0 ->No
      $reporte = ReporteEstado::where($reglas)->first();
      if($valor == 1)$reporte->importacion = 0;
      if($valor == 2)$reporte->sesion_cerrada = 0;
      if($valor == 3)$reporte->relevamiento = 0;
      if($valor == 4)$reporte->sesion_abierta = 0;
      $reporte->save();
    }
    //obtiene los datos relevados y las importaciones
    public function obtenerCargados($estados){
      //variables para guardar los datos de relevados e importaciones
      $relevados = array();
      $importaciones = array();
      //por cada estado, busco sus datos
      foreach ($estados as $estado) {
        $fecha = $estado->fecha_sesion;
        $casino = $estado->id_casino;
        $relevados [] = app(\App\Http\Controllers\Bingo\SesionesController::class)
                            ->obtenerSesionFC($fecha, $casino);
        $importaciones []  = app(\App\Http\Controllers\Bingo\ImportacionController::class)
                                ->obtenerImportacionFC($fecha, $casino);
      }
      return ['relevados' => $relevados, 'importaciones' => $importaciones];
    }

    public function obtenerDiferencia($id){
      $data = $this->buscarEstado(request(),$id);
      if(is_null($data)) return response()->json(['id_importacion' => 'No encontrado']);
      
      $importacion = ImportacionController::getInstancia()->obtenerImportacionCompleta($id);

      $sesion = is_null($data->sesion_cerrada)? 
        null :
        SesionesController::getInstancia()->obtenerSesion($data->sesion_cerrada);
      
      $reporte = ReporteEstado::where([
        ['fecha_sesion','=', $importacion[0]->fecha],['id_casino','=', $importacion[0]->id_casino]
      ])->first();

      //busco la primer ocurrencia que cumpla con la id de casino y con fecha menor
      $bd_importacion_anterior = ImportacionBingo::where([
        ['fecha','<', $importacion[0]->fecha],['id_casino','=', $importacion[0]->id_casino]
      ])->orderBy('fecha','desc')->first();

      $pozo_dotacion_inicial = null;
      if(!is_null($bd_importacion_anterior)){//si existe, busco la primer anterior
        $id_importacion_anterior = $bd_importacion_anterior->id_importacion;
        $importacion_anterior = ImportacionController::getInstancia()->obtenerImportacionCompleta($id_importacion_anterior);
        $pozo_dotacion_inicial = $importacion_anterior->last()->pozo_dot;
      }

      return ['importacion' => $importacion, 'sesion' => $sesion, 'reporte' => $reporte, 'pozoDotInicial' => $pozo_dotacion_inicial];
    }

    //guardar los datos del reporte de diferencia (observaciones + visado)
    public function guardarReporteDiferencia(Request $request){
      $importacion = ImportacionBingo::find($request->id_importacion);
      $reglas = array();
      $reglas [] = ['id_casino','=',$importacion->id_casino];
      $reglas [] = ['fecha_sesion','=',$importacion->fecha];

      $reporte = ReporteEstado::where($reglas)->first();

      $reporte->visado = 1;
      $reporte->observaciones_visado = $request->observacion;
      $reporte->save();

      return $request->id_importacion;
    }
    //obtiene el último pozo dotación de la sesión anterior para utilizarlo como sesión inicial
    protected function obtenerPozoDotacionInicial(){

    }
  }
