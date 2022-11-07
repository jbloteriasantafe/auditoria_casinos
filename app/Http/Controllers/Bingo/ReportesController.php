<?php

namespace App\Http\Controllers\Bingo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Bingo\ReporteEstado;
use App\Bingo\ImportacionBingo;
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
  
  public function reportesDiferencia(){
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Reporte Diferencia' , 'diferencia-bingo');
    return view('Bingo.ReporteDiferencia', ['casinos' => $usuario->casinos,
      'puede_visar' => $usuario->es_administrador || $usuario->es_superusuario || $usuario->es_auditor,
      'puede_ver'   => $usuario->es_administrador || $usuario->es_superusuario || $usuario->es_auditor
    ]);
  }
  
  public function buscarReportesDiferencia(Request $request){
    return $this->buscarEstado($request);
  }
  
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
    //MIN(bi.fecha) y MIN(bs.fecha_inicio) simplemente limpia el NULL porque es = o NULL por el leftJoin
    //SUBSTR(MIN(CONCAT(LPAD(_ID_,10,"0"),_HORA_)),11) obtiene la hora del primero en la tabla con esa fecha (tiene _ID_ mas grande)
    //LPAD porque se hace comparación caracter a caracter, se le concatena la hora para despues sacarsela
    ->selectRaw('fechas.fecha as fecha_sesion,c.nombre as casino,
     MIN(bi.fecha)        as imp_fecha_inicio,
     MIN(bs.fecha_inicio) as ses_fecha_inicio,
     SUBSTR(MIN(CONCAT(LPAD(bi.id_importacion,10,"0"),bi.hora_inicio)),11) as imp_hora_inicio,
     SUBSTR(MIN(CONCAT(LPAD(bs.id_sesion     ,10,"0"),bs.hora_inicio)),11) as ses_hora_inicio,
     MIN(IF(bs.id_estado = 2,bs.id_sesion,NULL)) as sesion_cerrada,
     BIT_OR(bp.id_partida IS NOT NULL) as relevamiento,
     MIN(bi.id_importacion) as importacion,
     BIT_OR(bre.id_reporte_estado IS NOT NULL AND bre.visado = 1) as visado'
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
  
  public function reporteEstadoSet($id_casino,$fecha,array $attrs_vals){
    $reporte = ReporteEstado::where([['fecha_sesion','=', $fecha],['id_casino','=', $id_casino]])
    ->orderBy('id_reporte_estado','desc')->first();
    if(is_null($reporte)) $reporte = new ReporteEstado;
    $reporte->fecha_sesion = $fecha;
    $reporte->id_casino = $id_casino;
    foreach($attrs_vals as $attr => $val){
      $reporte->{$attr} = $val;
    }
    $reporte->save();
    
    if(empty($reporte->importacion)  && empty($reporte->sesion_cerrada)
    && empty($reporte->relevamiento) && empty($reporte->relevamiento)){
      $reporte->delete();
    }
    
    return $this;
  }

  public function obtenerDiferencia($id){
    $data = $this->buscarEstado(request(),$id);
    if(is_null($data)) return response()->json(['id_importacion' => 'No encontrado']);
    
    $importacion = ImportacionController::getInstancia()->obtenerImportacionCompleta($id);

    $sesion = is_null($data->sesion_cerrada)?  null :
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
}
