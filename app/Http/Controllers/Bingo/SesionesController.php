<?php

namespace App\Http\Controllers\Bingo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\Bingo\ReportesController;
use Illuminate\Support\Facades\DB;
use Validator;

use App\Bingo\SesionBingo;
use App\Bingo\SesionBingoRe;
use App\Bingo\DetalleSesionBingo;
use App\Bingo\DetalleSesionBingoRe;
use App\Bingo\PartidaBingo;
use App\Bingo\DetallePartidaBingo;
use App\Bingo\ReporteEstado;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

use Dompdf\Dompdf;
use PDF;
use View;
class SesionesController extends Controller
{
  private static $instance;
  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }
  private static $atributos = [];

  private function errorOut($map){
    return response()->json($map,422);
  }

  public function index(){
    $UC = UsuarioController::getInstancia();
    $UC->agregarSeccionReciente('Sesiones y Relevamientos' ,'bingo');
    return view('Bingo.sesion', ['casinos' => $UC->quienSoy()['usuario']->casinos]);
  }

  public function buscarSesion(Request $request){
    //Busco los casinos a los que esta asociado el usuario
    $casinos = UsuarioController::getInstancia()->getCasinos();
    //reglas que vienen desde el buscador para poder filtrar
    $reglas = [];
    if(isset($request->fecha)){
      $reglas[]=['bingo_sesion.fecha_inicio', '=', $request->fecha];
    }
    if($request->casino!=0){
      $reglas[]=['casino.id_casino', '=', $request->casino];
    }
    if($request->estado != 0){
      $reglas[]=['bingo_estado_sesion.id_estado_sesion', '=', $request->estado];
    }
    $sort_by = ['columna' => 'fecha_inicio', 'orden' => 'desc'];
    if(!empty($request->sort_by)) $sort_by = $request->sort_by;
    //consulta a la db para obtener las sesiones que cumplan con las reglas
    return DB::table('bingo_sesion')
    ->select('bingo_sesion.*', 'casino.nombre', 'bingo_estado_sesion.descripcion', 'u1.nombre as nombre_inicio', 'u2.nombre as nombre_fin')
    ->leftJoin('casino' , 'bingo_sesion.id_casino','=','casino.id_casino')
    ->leftJoin('bingo_estado_sesion' , 'bingo_sesion.id_estado','=','bingo_estado_sesion.id_estado_sesion')
    ->leftJoin('usuario as u1', 'bingo_sesion.id_usuario_inicio', '=', 'u1.id_usuario')
    ->leftJoin('usuario as u2', 'bingo_sesion.id_usuario_fin', '=', 'u2.id_usuario')
    ->orderBy($sort_by['columna'],$sort_by['orden'])
    ->where($reglas)
    ->whereIn('casino.id_casino', $casinos)
    ->paginate($request->page_size);
  }
  //Llamado al CREAR la sesion del dia por primera vez (setea los datos de INICIO)
  public function guardarSesion(Request $request){
    //Validación de los datos
    Validator::make($request->all(), [
      'pozo_dotacion_inicial' => 'required|numeric',
      'pozo_extra_inicial' => 'required|numeric',
      'fecha_inicio' => 'required',
      'hora_inicio' => 'required',
      'detalles.*.valor_carton' => 'required|numeric',
      'detalles.*.serie_inicial' => 'required|numeric',
      'detalles.*.carton_inicial' => 'required|numeric',
    ], [], self::$atributos)->after(function($validator){
      if($validator->errors()->any()) return;
      //valido que no exista importacion para el mismo día en el casino
      $cargada = SesionBingo::where([
        ['bingo_sesion.id_casino','=',$validator->getData()['casino']],
        ['bingo_sesion.fecha_inicio','=',$validator->getData()['fecha_inicio']]
      ])->count() > 0;
      if($cargada){
        $validator->errors()->add('sesion_cargada','La sesion para esta fecha se encuentra cargarda.');
      }
    })->validate();

    return DB::transaction(function() use ($request){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario;
      //Se crea una nueva sesión y se le cargan los datos
      $sesion = new SesionBingo;
      $sesion->fecha_inicio          = $request->fecha_inicio;
      $sesion->hora_inicio           = $request->hora_inicio;
      $sesion->pozo_dotacion_inicial = $request->pozo_dotacion_inicial;
      $sesion->pozo_extra_inicial    = $request->pozo_extra_inicial;
      $sesion->id_casino             = $request->casino;
      $sesion->id_usuario_inicio     = $usuario;
      $sesion->id_estado             = '1';
      $sesion->save();

      //GUARDA LOS DETALLES DE LA SESIÓN
      foreach ($request->detalles as $detalle) {
        $detalle_sesion = new DetalleSesionBingo;
        $detalle_sesion->sesionBingo()->associate($sesion->id_sesion);
        $detalle_sesion->valor_carton  = $detalle['valor_carton'];
        $detalle_sesion->serie_inicio  = $detalle['serie_inicial'];
        $detalle_sesion->carton_inicio = $detalle['carton_inicial'];
        $detalle_sesion->save();
      }
      //Guardo la información para el reporte de estado
      ReportesController::getInstancia()->reporteEstadoSet($sesion->id_casino, $sesion->fecha_inicio,['sesion_abierta'=>1]);
      return ['sesion' => $sesion, 'casino' => $sesion->casino, 'estado' => $sesion->estadoSesion, 'nombre_inicio' => $sesion->usuarioInicio->nombre, 'nombre_fin' => '-'];
    });
  }
  //Llamado al CERRAR la sesion del dia por primera vez (cambia los datos de FIN)
  public function guardarCierreSesion(Request $request,$eliminar_reporte = false){
    Validator::make($request->all(), [
      'id_sesion'           => 'required|integer|exists:bingo_sesion,id_sesion',
      'pozo_dotacion_final' => 'required|numeric',
      'pozo_extra_final'    => 'required|numeric',
      'fecha_fin'           => 'required',
      'hora_fin'            => 'required',
      'detalles.*.valor_carton_f' => 'required|numeric',
      'detalles.*.serie_final'    => 'required|numeric',
      'detalles.*.carton_final'   => 'required|numeric',
    ])->after(function($validator){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $sesion = SesionBingo::find($data['id_sesion']);
      foreach($data['detalles'] as $idx => $d){
        $tiene_valor_carton = $sesion->detallesSesion()->where('valor_carton','=',$d['valor_carton_f'])->count() > 0;
        if(!$tiene_valor_carton){
          $validator->errors()->add("detalles.$idx.valor_carton_f","No existe un carton con valor $d[valor_carton_f]");
        }
      }
    })->validate();
    
    return DB::transaction(function() use ($request,$eliminar_reporte){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario;
      //busco la sesión y le cargo los datos de cierre
      $sesion = SesionBingo::findorfail($request->id_sesion);
      $sesion->fecha_fin           = $request->fecha_fin;
      $sesion->hora_fin            = $request->hora_fin;
      $sesion->pozo_dotacion_final = $request->pozo_dotacion_final;
      $sesion->pozo_extra_final    = $request->pozo_extra_final;
      $sesion->id_usuario_fin      = $usuario;
      $sesion->id_estado           = '2';
      $sesion->save();
    
      foreach($request->detalles as $d){
        //Busco si el detalle que mando ya existe con valor_carton
        $det_sesion = $sesion->detallesSesion()->where('valor_carton','=',$d['valor_carton_f'])->first();
        $det_sesion->serie_fin  = $d['serie_final'];
        $det_sesion->carton_fin = $d['carton_final'];
        $det_sesion->save();
      }
      
      //Guardo la información para el reporte de estado
      ReportesController::getInstancia()->reporteEstadoSet(
        $sesion->id_casino, $sesion->fecha_inicio,['sesion_cerrada'=>1,'sesion_abierta'=>0]
      );
      
      return ['sesion' => $sesion, 'casino' => $sesion->casino, 'estado' => $sesion->estadoSesion, 'nombre_inicio' => $sesion->usuarioInicio->nombre, 'nombre_fin' => $sesion->usuarioFin->nombre];
    });
  }
  //Cierra sesion de una sesion reabierta (cambia los datos de FIN)
  public function modificarCierreSesion(Request $request){
    return $this->guardarCierreSesion($request,true);
  }
  //Re abre una sesion cerrada, se guardan los datos de la sesion en otra tabla y se le cambia el estado
  public function reAbrirSesion(Request $request, $id){
    Validator::make($request->all(), [
      'motivo' => 'required'
    ])->validate();
    $permiso = UsuarioController::getInstancia()->chequearRolFiscalizador();
    if( $permiso == 1){
      return $this->errorOut(['no_tiene_permiso' => 'Su rol en el sistema no le permite reabrir una sesión.']);
    }

    return DB::transaction(function() use ($request,$id){      
      $sesion = SesionBingo::findorfail($id);
      //Guardo los datos que contenia la sesion
      $sesionre = new SesionBingoRe;
      $sesionre->id_sesion             = $id;
      $sesionre->hora_fin              = $sesion->hora_fin;
      $sesionre->fecha_fin             = $sesion->fecha_fin;
      $sesionre->hora_inicio           = $sesion->hora_inicio;
      $sesionre->fecha_inicio          = $sesion->fecha_inicio;
      $sesionre->id_usuario_inicio     = $sesion->id_usuario_inicio;
      $sesionre->id_usuario_fin        = $sesion->id_usuario_fin;
      $sesionre->pozo_dotacion_inicial = $sesion->pozo_dotacion_inicial;
      $sesionre->pozo_extra_inicial    = $sesion->pozo_extra_inicial;
      $sesionre->pozo_dotacion_final   = $sesion->pozo_dotacion_final;
      $sesionre->pozo_extra_final      = $sesion->pozo_extra_final;
      $sesionre->observacion           = $request->motivo;
      $sesionre->fecha_re              = date("Y-m-d");
      $sesionre->save();
      //Restablezco valores de la sesion
      $sesion->id_estado = '1';
      $sesion->id_usuario_fin = null;
      $sesion->save();
      
      foreach($sesion->detallesSesion as $d){
        $dre = new DetalleSesionBingoRe;
        $dre->id_sesion_re = $sesionre->id_sesion_re;
        $dre->id_detalle_sesion = $d->id_detalle_sesion;
        $dre->valor_carton  = $d->valor_carton;
        $dre->serie_inicio  = $d->serie_inicio;
        $dre->serie_fin     = $d->serie_fin;
        $dre->carton_inicio = $d->carton_inicio;
        $dre->carton_fin    = $d->carton_fin;
        $dre->save();
      }
      //Guardo la información para el reporte de estado
      ReportesController::getInstancia()->reporteEstadoSet(
        $sesion->id_casino, $sesion->fecha_inicio,['sesion_cerrada'=>0,'sesion_abierta'=>1,'visado' => 0]
      );

      return ['sesion' => $sesion, 'casino' => $sesion->casino, 'estado' => $sesion->estadoSesion, 'nombre_inicio' => $sesion->usuarioInicio->nombre, 'nombre_fin' => '-'];
    });
  }
  //Toca en el lapiz de modificar (cambia los datos de INICIO)
  public function modificarSesion(Request $request){
    Validator::make($request->all(), [
      'id_sesion'             => 'required|integer|exists:bingo_sesion,id_sesion',
      'pozo_dotacion_inicial' => 'required|numeric',
      'pozo_extra_inicial'    => 'required|numeric',
      'fecha_inicio'          => 'required',
      'hora_inicio'           => 'required',
      'detalles.*.valor_carton'   => 'required|numeric',
      'detalles.*.serie_inicial'  => 'required|numeric',
      'detalles.*.carton_inicial' => 'required|numeric',
    ])->validate();
    return DB::transaction(function() use ($request){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario;
      $sesion   = SesionBingo::findorfail($request->id_sesion);    
      $sesion->fecha_inicio          = $request->fecha_inicio;
      $sesion->hora_inicio           = $request->hora_inicio;
      $sesion->pozo_dotacion_inicial = $request->pozo_dotacion_inicial;
      $sesion->pozo_extra_inicial    = $request->pozo_extra_inicial;
      $sesion->id_casino             = $request->casino;
      $sesion->id_usuario_inicio     = $usuario;
      $sesion->save();

      $valores_cartones_usados = [];
      foreach($request->detalles as $d){
        $valores_cartones_usados[] = $d['valor_carton'];
        //Busco si el detalle que mando ya existe con valor_carton
        //Si ya esta, le asigno los valores
        //Si no, creo uno nuevo
        $det_sesion = $sesion->detallesSesion()->where('valor_carton','=',$d['valor_carton'])->first();
        if(is_null($det_sesion)){
          $det_sesion = new DetalleSesionBingo;
          $det_sesion->sesionBingo()->associate($sesion->id_sesion);
        }
        $det_sesion->valor_carton  = $d['valor_carton'];
        $det_sesion->serie_inicio  = $d['serie_inicial'];
        $det_sesion->carton_inicio = $d['carton_inicial'];
        $det_sesion->save();
        
        //Verifico si en los historicos ya existe uno con el mismo valor carton desenlazado para enlazarlos
        foreach(SesionBingoRe::where('id_sesion','=',$sesion->id_sesion)->get() as $sre){
          $detsre_enlazables = $sre->detallesSesion()->where('valor_carton','=',$d['valor_carton'])
          ->whereNull('id_detalle_sesion')->get();
          foreach($detsre_enlazables as $dre){
            $dre->id_detalle_sesion = $det_sesion->id_detalle_sesion;
            $dre->save();
          }
        }
      }
      
      //Desenlazo los cartones que no existen mas
      $detalles_a_borrar = $sesion->detallesSesion()->whereNotIn('valor_carton',$valores_cartones_usados)->get();
      foreach($detalles_a_borrar as $d_borr){
        $dets_re = DetalleSesionBingoRe::where('id_detalle_sesion','=',$d_borr->id_detalle_sesion)->get();
        foreach($dets_re as $dre){//Le desconecto los detalles viejos
          $dre->id_detalle_sesion = null;
          $dre->save();
        }
        $d_borr->delete();  
      }
      
      return ['sesion' => $sesion, 'casino' => $sesion->casino, 'estado' => $sesion->estadoSesion, 'nombre_inicio' => $sesion->usuarioInicio->nombre, 'nombre_fin' => '-'];
    });
  }

  public function eliminarSesion($id){
    $sesion = SesionBingo::findorfail($id);
    //si esta cerrada, los fiscalizadores no pueden eliminar la sesión
    if($sesion->id_estado == 2) {
      $permiso = UsuarioController::getInstancia()->chequearRolFiscalizador();
      if($permiso == 1){
          return $this->errorOut(['no_tiene_permiso' => 'Su rol en el sistema no le permite reabrir una sesión.']);
      }
    }
    $reporte = ReporteEstado::where([
      ['fecha_sesion','=', $sesion->fecha_inicio],
      ['id_casino','=', $sesion->id_casino]
    ])->first();
    DB::transaction(function() use ($sesion,$reporte){
      // Elimina los relevamientos asociados a la sesión y sus detalles.
      foreach ($sesion->partidasSesion as $partida) {
        foreach ($partida->detallesPartida as $detalle) {
          $detalle->delete();
        }
        $partida->delete();
      }
      // Elimina los detalles de la sesion
      foreach ($sesion->detallesSesion as $detalle) {
        $detalle->delete();
      }
      $sesion->delete();
      
      ReportesController::getInstancia()->reporteEstadoSet(
        $sesion->id_casino, $sesion->fecha_inicio,['sesion_cerrada'=>0,'sesion_abierta'=>0,'relevamiento'=>0,'visado' => 0]
      );
      
      return ['sesion' => $sesion];
    });
  }

  public function obtenerSesion($id){
    $sesion   = SesionBingo::findorfail($id);
    $detalles = $sesion->detallesSesion()->orderBy('valor_carton','asc')->get();
    $partidas = $sesion->partidasSesion->map(function($partida){
      return [$partida,$partida->usuario->nombre];
    });
    $historico = DB::table('bingo_re_sesion')
    ->select('bingo_re_sesion.*', 'u1.nombre as nombre_inicio', 'u2.nombre as nombre_fin')
    ->leftJoin('usuario as u1', 'bingo_re_sesion.id_usuario_inicio', '=', 'u1.id_usuario')
    ->leftJoin('usuario as u2', 'bingo_re_sesion.id_usuario_fin', '=', 'u2.id_usuario')
    ->where('id_sesion','=', $id)
    ->get();
    return ['sesion' => $sesion, 'detalles' => $detalles, 'partidas' => $partidas, 'historico' => $historico];
  }
  
  //obtener datos de sesiones a partir de fecha y casino
  public function obtenerSesionFC($fecha, $id_casino){//Usado en ReportesController
    $sesion = SesionBingo::where('id_casino','=',$id_casino)->where('fecha_inicio','=',$fecha)->select('id_sesion')->first();
    return !is_null($sesion)? $this->obtenerSesion($sesion->id_sesion) : -1;
  }

  public function guardarRelevamiento(Request $request){
    Validator::make($request->all(), [
      'id_sesion'         => 'required|integer|exists:bingo_sesion,id_sesion',
      'nro_partida'       => 'required|numeric',
      'hora_jugada'       => 'required|date_format:H:i',
      'valor_carton'      => 'required|numeric',
      'serie_inicio'      => 'required|numeric',
      'serie_fin'         => 'required|numeric',
      'carton_inicio_i'   => 'required|numeric',
      'carton_fin_i'      => 'required|numeric',
      'carton_inicio_f'   => 'required|numeric',
      'carton_fin_f'      => 'required|numeric',
      'cartones_vendidos' => 'required|numeric',
      'premio_linea'      => 'required|numeric',
      'premio_bingo'      => 'required|numeric',
      'maxi_linea'        => 'required|numeric',
      'maxi_bingo'        => 'required|numeric',
      'pos_bola_bingo'    => 'required|numeric',
      'pos_bola_linea'    => 'required|numeric',
      'detalles.*.nombre_premio'  => 'required|numeric',
      'detalles.*.carton_ganador' => 'required|numeric',
    ])->after(function($validator){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $sesion = SesionBingo::find($data['id_sesion']);
      //si esta cerrada, mensaje de error que no se pueden cargar los relevamientos
      if($sesion->id_estado == 2){
        $validator->errors()->add('relevamiento_cerrado', 'La sesión está cerrada, no se pueden cargar relevamientos.');
      }
      $partida_cargada = $sesion->partidasSesion()->where('num_partida','=',$data['nro_partida'])->count() > 0;
      if($partida_cargada){
        $validator->errors()->add('partida_cargada', 'Ya se ha cargado un relevamiento con el mismo número de partida.');
      }
    })->validate();

    return DB::transaction(function() use ($request){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario;
      $sesion = SesionBingo::findOrFail($request->id_sesion);
      $partida = new PartidaBingo;
      $partida->id_usuario        = $usuario;
      $partida->id_sesion         = $request->id_sesion;
      $partida->num_partida       = $request->nro_partida;
      $partida->hora_inicio       = $request->hora_jugada;
      $partida->valor_carton      = $request->valor_carton;
      $partida->serie_inicio      = $request->serie_inicio;
      $partida->carton_inicio_i   = $request->carton_inicio_i;
      $partida->carton_fin_i      = $request->carton_fin_i;
      $partida->serie_fin         = $request->serie_fin;
      $partida->carton_inicio_f   = $request->carton_inicio_f;
      $partida->carton_fin_f      = $request->carton_fin_f;
      $partida->cartones_vendidos = $request->cartones_vendidos;
      $partida->premio_linea      = $request->premio_linea;
      $partida->premio_bingo      = $request->premio_bingo;
      $partida->pozo_dot          = $request->maxi_linea;
      $partida->pozo_extra        = $request->maxi_bingo;
      $partida->bola_bingo        = $request->pos_bola_bingo;
      $partida->bola_linea        = $request->pos_bola_linea;
      $partida->save();

      //GUARDA LOS DETALLES DEL RELEVAMIENTO
      foreach ($request->detalles as $detalle) {
        $detalle_partida = new DetallePartidaBingo;
        $detalle_partida->partidaBingo()->associate($partida->id_partida);
        $detalle_partida->id_premio = $detalle['nombre_premio'];
        $detalle_partida->carton    = $detalle['carton_ganador'];
        $detalle_partida->save();
      }
        //Guardo la información para el reporte de estado
      ReportesController::getInstancia()->reporteEstadoSet(
        $sesion->id_casino, $sesion->fecha_inicio,['relevamiento'=>1]
      );
      return ['partida' => $partida];
    });
  }

  public function eliminarPartida($id){
    $permiso = UsuarioController::getInstancia()->chequearRolFiscalizador();
    if( $permiso == 1){
      return $this->errorOut(['no_tiene_permiso' => 'Su rol en el sistema no le permite reabrir una sesión.']);
    }
    return DB::transaction(function() use ($id){
      //busco la partida
      $partida = PartidaBingo::findorfail($id);
      //veo si la partida que se está por eliminar es la última
      $sesion = SesionBingo::findOrFail($partida->id_sesion);
      $partidas = $sesion->partidasSesion->count();
      //si es la última, cambio la información en el reporte de estado
      if($partidas == 1){
        ReportesController::getInstancia()->reporteEstadoSet(
          $sesion->id_casino, $sesion->fecha_inicio, ['relevamiento' => 0]
        );
      }
      //elimino la partidas
      $partida->delete();
      return ['partida' => $partida];
    });
  }
  
  private function generarPlanilla($ruta_planilla,$nombre_pdf){
    $view = View::make($ruta_planilla);
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    return $dompdf->stream($nombre_pdf, Array('Attachment'=>0));
  }
  public function generarPlanillaSesion(){
    return $this->generarPlanilla('Bingo.planillaSesion','Sesion-Bingo.pdf');
  }
  public function generarPlanillaCierreSesion(){
    return $this->generarPlanilla('Bingo.planillaCierreSesion','CierreSesion-Bingo.pdf');
  }
  public function generarPlanillaRelevamiento(){
    return $this->generarPlanilla('Bingo.planillaRelevamiento','Relevamiento-Bingo.pdf');
  }
}
