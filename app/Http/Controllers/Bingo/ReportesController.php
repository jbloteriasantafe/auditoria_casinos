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
use Illuminate\Support\Facades\DB;

class ReportesController extends Controller{
    private static $instance;
    public static function getInstancia() {
      if (!isset(self::$instance)) {
        self::$instance = new self();
      }
      return self::$instance;
    }
    //BUSCAR IMPORTACIONES, RELEVADOS Y ESTADOS PARA ARMAR REPORTE DE DIFERENCIA
    public function buscarReportesDiferencia(Request $request){
      //obtener todos los estados
      $estados = $this->obtenerEstados($request);
      //por cada estado, obtener las importaciones y relevamientos(sesiones/partidas)
      $respuesta = $this->obtenerCargados($estados);

      // //obtengo el pozo dotación inicial, último pozo dotación de la sesión anterior
      // $pozo_dotacion_inicial = $this->obtenerPozoDotacionInicial();

      return ['respuesta' => $respuesta, 'estados' => $estados];
      // return $respuesta;
    }
    //función index de reporte de diferencia
    public function reportesDiferencia(){
        //Busco los casinos a los que esta asociado el usuario
        $casinos = UsuarioController::getInstancia()->getCasinos();
        //agrego a seccion reciente a BINGo
        UsuarioController::getInstancia()->agregarSeccionReciente('Reporte Diferencia' , 'diferencia-bingo');

        return view('Bingo.ReporteDiferencia', ['casinos' => $casinos]);
    }
    //función index de reporte de estados
    public function reportesEstado(){
        //Busco los casinos a los que esta asociado el usuario
        $casinos = UsuarioController::getInstancia()->getCasinos();
        UsuarioController::getInstancia()->agregarSeccionReciente('Reporte Estado' , 'estado-bingo');
        return view('Bingo.ReporteEstado', ['casinos' => $casinos]);
    }
    
    //BUSCAR ESTADOS
    public function buscarEstado(Request $request){
      $id_casino  = empty($request->casino)? null : $request->casino;
      $id_casinos = UsuarioController::getInstancia()->quienSoy()['usuario']->casinos->map(function($c){
        return $c->id_casino;
      })->toArray();
      $inicio = '2012-01-01';
      $fin    = date('Y-m-d');
      $dates  = Utils::tablaDates($fin);
      $sort_by = ['columna' => "$dates.date",'orden' => 'desc'];
      if(!empty($request->sort_by)){
        $sort_by['columna'] = $request->sort_by['columna'];
        $sort_by['orden']   = $request->sort_by['orden'];
      }
      return DB::table(DB::raw($dates))
      ->selectRaw('dates.date as fecha_sesion,c.nombre as casino,
       SUM(bs.id_sesion IS NOT NULL AND bs.id_estado = 2) > 0 as sesion_cerrada,
       SUM(bp.id_partida IS NOT NULL) > 0 as relevamiento,
       SUM(bi.id_importacion IS NOT NULL) > 0 as importacion,
       SUM(bre.id_reporte_estado IS NOT NULL and bre.visado) > 0 as visado'
      )
      ->join('casino as c',function($j) use ($id_casino,$id_casinos){
        if(!is_null($id_casino)) $j->where('c.id_casino','=',$id_casino);
        return $j->whereIn('c.id_casino',$id_casinos);
      })
      ->leftJoin('bingo_sesion as bs',function($j) use ($dates){
        return $j->on('bs.fecha_inicio','=',"$dates.date")->on('bs.id_casino','=','c.id_casino');
      })
      ->leftJoin('bingo_partida as bp','bp.id_sesion','=','bs.id_sesion')//Chequear todos los cartones?
      ->leftJoin('bingo_importacion as bi',function($j) use ($dates){
        return $j->on('bi.fecha','=',"$dates.date")->on('bi.id_casino','=','c.id_casino');
      })//Mover el visado a la importacion
      ->leftJoin('bingo_reporte_estado as bre',function($j) use ($dates){
        return $j->on('bre.fecha_sesion','=',"$dates.date")->on('bre.id_casino','=','c.id_casino');
      })
      ->where("$dates.date",'>=',$inicio)->where("$dates.date",'<=',$fin)
      ->groupBy("$dates.date",'c.id_casino')
      ->orderBy($sort_by['columna'],$sort_by['orden'])
      ->paginate($request->page_size);
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

    //funcion auxiliar para obtener tods los estados
    public function obtenerEstados($request){
      //Busco los casinos a los que esta asociado el usuario
      $casinos = UsuarioController::getInstancia()->getCasinos();
      //reglas que vienen desde el buscador para poder filtrar
      $reglas = array();
      if(isset($request->fecha)){
        $reglas[]=['bingo_reporte_estado.fecha_sesion', '=', $request->fecha];
      }
      if($request->casino!=0){
        $reglas[]=['casino.id_casino', '=', $request->casino];
      }
      $sort_by = $request->sort_by;
      // dd($sort_by);
      //consulta a la db para obtener los estados que cumplan con las reglas
      $resultados = DB::table('bingo_reporte_estado')
                         ->select('bingo_reporte_estado.*', 'casino.nombre')
                         ->leftJoin('casino' , 'bingo_reporte_estado.id_casino','=','casino.id_casino')
                         ->when($sort_by,function($query) use ($sort_by){
                          return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                        },function($query){
                          return $query->orderBy('fecha_sesion','desc');
                        })
                      ->where($reglas)
                      ->whereIn('casino.id_casino', $casinos)
                      ->orderBy('id_reporte_estado', 'desc')
                      ->paginate($request->page_size);
      return $resultados;
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
      $importacion = app(\App\Http\Controllers\Bingo\ImportacionController::class)
                        ->obtenerImportacionCompleta($id);

      $sesion = app(\App\Http\Controllers\Bingo\SesionesController::class)
                    ->obtenerSesionFC($importacion[0]->fecha,$importacion[0]->id_casino, 'diferencia');

      //armo las reglas para obtener el reporte de estado de la importación
      $reglas = array();
      $reglas [] =['fecha_sesion','=', $importacion[0]->fecha];
      $reglas [] =['id_casino','=', $importacion[0]->id_casino];
      $reporte = ReporteEstado::where($reglas)->first();

      //busco la primer ocurrencia que cumpla con la id de casino y con fecha menor
      $cumple = array();
      $cumple [] =['fecha','<', $importacion[0]->fecha];
      $cumple [] =['id_casino','=', $importacion[0]->id_casino];
      $id_importacion_anterior = ImportacionBingo::where($cumple)->orderBy('fecha','dsc')->first();

      //si existe, busco la primer anterior
      if($id_importacion_anterior != null){
        $id_importacion_anterior = $id_importacion_anterior->id_importacion;
        $importacion_anterior = app(\App\Http\Controllers\Bingo\ImportacionController::class)
                          ->obtenerImportacionCompleta($id_importacion_anterior);
        $pozo_dotacion_inicial = $importacion_anterior->last()->pozo_dot;

      }else {
        $pozo_dotacion_inicial = -1;
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
