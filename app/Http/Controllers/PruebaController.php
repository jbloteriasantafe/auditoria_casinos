<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use PDF;
use Dompdf\Dompdf;
use View;
use File;
use Response;
use DateTime;
use App\Maquina;
use App\PruebaJuego;
use App\Casino;
use App\PruebaProgresivo;
use App\Progresivo;
use App\NivelProgresivo;
use App\Archivo;

/*
  Controlador encargado de sortear una cantidad de maquians definida
   para las pruebas de juegos y pruebas de progresivo
*/

class PruebaController extends Controller
{
  private static $atributos = [
  ];
  private static $instance;

  private static $cantidad_progresivos = 1;

  private static $cantidad_maquinas = 1;

  public static function getInstancia(){
    if (!isset(self::$instance)) {
      self::$instance = new PruebaController();
    }
    return self::$instance;
  }

  public function buscarTodo(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));

    $casinos=array();
    foreach($usuario['usuario']->casinos as $casino){
        $casinos[]=$casino->id_casino;
    }

    $casinos= Casino::whereIn('id_casino' , $casinos)->get();
    UsuarioController::getInstancia()->agregarSeccionReciente('Prueba de Juego' , 'prueba_juego');

    return view('seccionPruebaJuegos',['casinos' => $casinos]);

  }

  public function buscarTodoPruebaProgresivo(){

    UsuarioController::getInstancia()->agregarSeccionReciente('Prueba de Progresivo' , 'prueba_progresivos');
    return view('seccionPruebaProgresivos');
  }

  public function obtenerPruebaJuego($id_prueba_juego){
    $prueba = PruebaJuego::find($id_prueba_juego);

    if(!empty($prueba->archivo)){
      $nombre_archivo = $prueba->archivo->nombre_archivo;
      $size=$prueba->archivo->archivo;
    }else{
      $nombre_archivo = null;
    }

    return ['prueba' => $prueba , 'nombre_archivo' => $nombre_archivo , 'maquina' => $prueba->juego];
  }

  public function obtenerPDF($id_prueba_juego){

    $file = PruebaJuego::find($id_prueba_juego);
    $data = $file->archivo->archivo;

    return Response::make(base64_decode($data), 200, ['Content-Type' => 'application/pdf',
                                                      'Content-Disposition' => 'inline; filename="'. $file->nombre_archivo  . '"']);
  }

  //retorno arreglo pruebas con una maquina
  public function sortearMaquinaPruebaDeJuego(Request $request){
    Validator::make($request->all(),[
        'id_casino' => 'required|exists:casino,id_casino',
        'id_sector' => 'required|exists:sector,id_sector',
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    //return: siempre es un arreglo aunque se sortee una sola maquina
    $maquinas = Maquina::join('isla','isla.id_isla' ,'=', 'maquina.id_isla')
    ->where([['maquina.id_casino','=', $request->id_casino],['isla.id_sector','=', $request->id_sector]])
    ->whereHas('estado_maquina',function($q){$q->where('descripcion','Ingreso')->orWhere('descripcion','ReIngreso');})
    ->inRandomOrder()->take(self::$cantidad_maquinas)->get();

    $pruebas = array();

    $casino = Casino::find($request->id_casino);
    //guardar registro de pureba de juego
    foreach ($maquinas as $maquina) {
      $prueba_juego = new PruebaJuego;
      $prueba_juego->fecha = date('Y-m-d');
      $prueba_juego->id_maquina = $maquina->id_maquina;
      $prueba_juego->save();

      $año = substr($prueba_juego->fecha,0,4);
      $mes = substr($prueba_juego->fecha,5,2);
      $dia = substr($prueba_juego->fecha,8,2);
      $prueba_juego->fecha = $dia."-".$mes."-".$año;

      $pruebas[] = $prueba_juego;
    }

    return ['pruebas' => $pruebas , 'maquina' => $maquinas[0] , 'casino' => $casino];
  }

  public function sortearMaquinaPruebaDeProgresivo(Request $request){
    Validator::make($request->all(),[
        'id_casino' => 'required|exists:casino,id_casino',
        'id_sector' => 'required|exists:sector,id_sector',
    ], array(), self::$atributos)->after(function($validator){
        // $datos = $validator->getData()['id_casino']; validar si se llega a guardar algo en la base de datos
    })->validate();

    $progresivos = Maquina::select('sector.descripcion','isla.nro_isla','progresivo.id_progresivo','progresivo.nombre_progresivo','progresivo.linkeado','maquina.id_maquina')
                        ->join('casino','maquina.id_casino','=','casino.id_casino')
                        ->join('isla','maquina.id_isla','=','isla.id_isla')
                        ->join('sector','isla.id_sector','=','sector.id_sector')
                        ->join('pozo','maquina.id_pozo','=','pozo.id_pozo')
                        ->join('pozo_tiene_nivel_progresivo','pozo.id_pozo','=','pozo_tiene_nivel_progresivo.id_pozo')
                        ->join('nivel_progresivo','pozo_tiene_nivel_progresivo.id_nivel_progresivo','=','nivel_progresivo.id_nivel_progresivo')
                        ->join('progresivo','nivel_progresivo.id_progresivo','=','progresivo.id_progresivo')
                        ->where([['maquina.id_casino','=', $request->id_casino],['isla.id_sector','=', $request->id_sector]])
                        ->whereHas('estado_maquina',function($q){$q->where('descripcion','Ingreso')->orWhere('descripcion','ReIngreso');})
                        ->groupBy('sector.descripcion','isla.nro_isla','progresivo.id_progresivo','progresivo.nombre_progresivo','progresivo.linkeado','maquina.id_maquina')
                        ->inRandomOrder()->take(self::$cantidad_progresivos)
                        ->get();

    //return: siempre es un arreglo aunque se sortee una sola maquina
    $pruebas = array();

    $casino = Casino::find($request->id_casino);
    //guardar registro de pureba de juego
    foreach ($progresivos as $progresivo) {
      $prueba_progresivos = new PruebaProgresivo();
      $prueba_progresivos->fecha = date('Y-m-d');
      $prueba_progresivos->id_progresivo = $progresivo->id_progresivo;
      $prueba_progresivos->id_maquina = $progresivo->id_maquina;

      $prueba_progresivos->save();

      $auxiliar = new \stdClass();
      $año = substr($prueba_progresivos->fecha,0,4);
      $mes = substr($prueba_progresivos->fecha,5,2);
      $dia = substr($prueba_progresivos->fecha,8,2);
      $prueba_progresivos->fecha = $dia."-".$mes."-".$año;
      if($progresivo->linkeado == 1){
          $auxiliar->linkeado = "Linkeado";
      }
      else if($progresivo->linkeado == 0){
          $auxiliar->linkeado = "Individual";
      }

      $auxiliar->nombre_progresivo = $progresivo->nombre_progresivo;
      $pruebas[] = $prueba_progresivos;
    }

    return ['pruebas' => $pruebas , 'casino' => $casino, 'aux' => $auxiliar];
  }

  public function generarPlanillaPruebaDeJuego($id_prueba_juego){//se va a cambiar a id_prueba_juego
    $rel= new \stdClass();
    $prueba_juego = PruebaJuego::find($id_prueba_juego);
    $maquina = Maquina::find($prueba_juego->id_maquina);
    $rel->sector = $maquina->isla->sector->descripcion;
    $rel->isla = $maquina->isla->nro_isla;
    $rel->marca = $maquina->marca;
    $rel->modelo = $maquina->modelo;
    $rel->nro_admin =$maquina->nro_admin;
    $rel->casino =  $maquina->casino->nombre;
    $rel->casinoCod = $maquina->casino->codigo;

    if($maquina->nro_serie != null){
    $rel->nro_serie = $maquina->nro_serie;
    }
    else{
      $rel->nro_serie = '-';
    }
    $rel->fecha = date('d-m-Y');

    $formula = array();

    foreach ($maquina->formula['attributes'] as $atributo){
      if($atributo != '-'  && $atributo != '+' && gettype($atributo) != 'integer' && $atributo != null){//si no es el id y si no es un operador
        $formula[]=$atributo;
      }
    }

    $rel->formula = $formula;

    $view = View::make('planillaJuegos', compact('rel'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->set_option('chroot',public_path());
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    // $dompdf->getCanvas()->page_text(20, 815, (($rel->nro_relevamiento != null) ? $rel->nro_relevamiento : "AUX")."/".$rel->casinoCod."/".$rel->sector."/".$rel->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(20, 815, "Prueba_juego_". $prueba_juego->id_prueba_juego .  "/".$rel->casinoCod."/".$rel->sector."/".$rel->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('PruebaDeJuego_' . $rel->fecha . '_'. $maquina->nro_admin . ' .pdf', Array('Attachment'=>0));
  }

  public function buscarPruebasDeJuego(Request $request){
    $reglas = array();
    if(!empty($request->fecha)){
      $reglas[] = ['prueba_juego.fecha','=',$request->fecha];
    }
    if(!empty($request->nro_admin)){
      $reglas[] = ['maquina.nro_admin','=',$request->nro_admin];
    }
    if(!empty($request->marca)){
      $reglas[] = ['maquina.marca','=',$request->marca];
    }

    $casinos = array();
    if($request->casino==-1){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      foreach($usuario->casinos as $casino){
        $casinos [] = $casino->id_casino;
      }
    }else{
      $casinos[]=$request->casino;
    }
    $sort_by = $request->sort_by;

    $resultados=DB::table('prueba_juego')
    ->select('prueba_juego.*' , 'maquina.*', 'casino.nombre as nombre_casino')
    ->join('maquina','maquina.id_maquina','=','prueba_juego.id_maquina')
    ->join('casino','maquina.id_casino','=','casino.id_casino')
    ->where($reglas)
    ->whereIn('maquina.id_casino',$casinos)
    ->when($sort_by,function($query) use ($sort_by){
                    return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                })
    ->paginate($request->page_size);

    foreach($resultados as $result){
      $año = substr($result->fecha,0,4);
      $mes = substr($result->fecha,5,2);
      $dia = substr($result->fecha,8,2);
      $result->fecha = $dia."-".$mes."-".$año;
    }

    return  $resultados;
  }

  public function buscarPruebasProgresivo(Request $request){
    $reglas = array();
    if(!empty($request->fecha)){
      $reglas[] = ['prueba_progresivo.fecha','=',$request->fecha];
    }
    if(!empty($request->n_progresivo)){
      $reglas[] = ['progresivo.nombre_progresivo','like',$request->n_progresivo."%"];
    }
    if($request->tipo != -1){
      if($request->tipo == 1){
              $reglas[] = ['progresivo.linkeado','=',1];
      }
      else if($request->tipo == 0){
              $reglas[] = ['progresivo.individual','=',1];
      }
    }

    $casinos = array();
    if($request->casino==-1){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      foreach($usuario->casinos as $casino){
        $casinos [] = $casino->id_casino;
      }
    }else{
      $casinos[]=$request->casino;
    }
    $sort_by = $request->sort_by;

    $resultados=DB::table('prueba_progresivo')
    ->select('prueba_progresivo.id_prueba_progresivo as id_prueba_progresivo', 'prueba_progresivo.fecha as fecha' ,'progresivo.id_progresivo as id_progresivo' , 'progresivo.individual as individual', 'progresivo.nombre_progresivo as nombre_progresivo', 'casino.nombre as nombre_casino')
    ->join('progresivo','prueba_progresivo.id_progresivo','=','progresivo.id_progresivo')
    ->join('nivel_progresivo' , 'progresivo.id_progresivo', '=', 'nivel_progresivo.id_progresivo')
    ->join('pozo_tiene_nivel_progresivo' , 'pozo_tiene_nivel_progresivo.id_nivel_progresivo', '=', 'nivel_progresivo.id_nivel_progresivo')
    ->join('pozo' , 'pozo_tiene_nivel_progresivo.id_pozo', '=', 'pozo.id_pozo')
    ->join('maquina','maquina.id_pozo','=','pozo.id_pozo')
    ->join('casino','maquina.id_casino','=','casino.id_casino')
    ->where($reglas)
    ->whereIn('maquina.id_casino',$casinos)
    ->groupBy('prueba_progresivo.id_prueba_progresivo' ,'prueba_progresivo.fecha' , 'progresivo.nombre_progresivo' , 'casino.nombre' ,'progresivo.individual', 'progresivo.id_progresivo')
    ->when($sort_by,function($query) use ($sort_by){
                    return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                })
    ->paginate($request->page_size);


    foreach($resultados as $result){
      $año = substr($result->fecha,0,4);
      $mes = substr($result->fecha,5,2);
      $dia = substr($result->fecha,8,2);

      $result->fecha = $dia."-".$mes."-".$año;

      if($result->individual == 1){
        $result->individual = "Individual";
      }
      else{
        $result->individual = "Linkeado";
      }
    }

    return  $resultados;
  }

  public function generarPlanillaPruebaDeProgresivos($id_prueba_progresivo){//se va a cambiar a id_prueba_juego
    $prueba= new \stdClass();
    $prueba_progresivo = PruebaProgresivo::find($id_prueba_progresivo);
    $maquina = Maquina::find($prueba_progresivo->id_maquina);
    $progresivo = Progresivo::find($prueba_progresivo->id_progresivo);
    $nivel_progresivo = ($prueba_progresivo->progresivo->niveles);

    $prueba->sector = $maquina->isla->sector->descripcion;
    $prueba->isla = $maquina->isla->nro_isla;
    $prueba->admin = $maquina->nro_admin;
    $prueba->nombre_progresivo = $progresivo->nombre_progresivo;
    $prueba->casino = $maquina->casino->nombre;
    $prueba->codCasino = $maquina->casino->codigo;
    if($progresivo->linkeado == 1){
      $prueba->tipo = "Linkeado";
    }
    else if($progresivo->individual == 1){
      $prueba->tipo = "Individual";
    }

    $view = View::make('planillaProgresivos',compact('prueba','nivel_progresivo'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->set_option('chroot',public_path());
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $id_prueba_progresivo."/".$prueba->codCasino."/".$prueba->sector."/".$prueba_progresivo->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('PruebaDeProg_' . $prueba_progresivo->fecha . ' .pdf', Array('Attachment'=>0));

  }

  public function guardarPruebaJuego(Request $request){

    Validator::make($request->all(), [
      'id_prueba_juego',
      'fecha' => 'required',
      'observacion' => 'nullable|string',
      'file' => 'sometimes|mimes:pdf',
    ], array(), self::$atributos)->after(function ($validator){
        //$validator->getData()['descripcion'] get campo de validador
    })->validate();

    $id_prueba_juego = $request->id_prueba_juego;
    $prueba = PruebaJuego::find($id_prueba_juego);
    $this->borrarArchivo($prueba);

    if($request->file != null){
      $file=$request->file;
      $archivo=new Archivo;
      $data=base64_encode(file_get_contents($file->getRealPath()));
      $nombre_archivo=$file->getClientOriginalName();
      $archivo->nombre_archivo=$nombre_archivo;
      $archivo->archivo=$data;
      $archivo->save();
      $prueba->archivo()->associate($archivo->id_archivo);
    }
    // $prueba->fecha_ejecucion = $request->fecha; no existe campo en la bd
    $prueba->observacion = $request->observacion;
    $prueba->save();
    return ['codigo' => 200];
  }

  private function borrarArchivo($prueba){
    if(isset($prueba->archivo)){
      $archivo = $prueba->archivo;
      $prueba->archivo()->dissociate();
      $archivo->delete();
      $prueba->save();
    }
  }

}
