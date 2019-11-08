<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Response;
use App\GliSoft;
use App\Archivo;
use App\Casino;
use Illuminate\Support\Facades\DB;
use App\Juego;

use Validator;

class GliSoftController extends Controller
{
  private static $atributos = [

    'nro_certificado' => 'Nro certificado',
    'observaciones' => 'Niveles de Progresivo',
    'file' => 'Archivo GLI',
  ];

  private static $instance;

  public static function getInstancia() {
      if(!isset(self::$instance)){
          self::$instance = new GliSoftController();
      }
      return self::$instance;
  }

  public function buscarTodo(){
      $glisofts=GliSoft::all();
      $casinos=Casino::all();
      UsuarioController::getInstancia()->agregarSeccionReciente('GLI Software' , 'certificadoSoft');
      //Ordenar por nombre ascendiente ignorando mayusculas
      $juegos = Juego::all()->sortBy("nombre_juego",SORT_NATURAL|SORT_FLAG_CASE); 
      //Hay juegos con el mismo nombre, les doy uno unico
      $juegosarr = [];
      foreach($juegos as $j){
        $nombre = $j->nombre_juego;
        if(!isset($juegosarr[$nombre])){
          $juegosarr[$nombre] = [];
        }
        $juegosarr[$nombre][] = $j;
      }
      //formato juegosarr = {'juego1' => [j1,j2],'juego2' => [j3],...}
      return view('seccionGLISoft' , ['glis' => $glisofts,'casinos' => $casinos,'juegos' => $juegosarr]);
  }

  public function obtenerGliSoft($id){
    $glisoft = GliSoft::find($id);

    if(!empty($glisoft->archivo)){
      $nombre_archivo = $glisoft->archivo->nombre_archivo;
      $size=$glisoft->archivo->archivo;
    }else{
      $nombre_archivo = null;
    }
    $juegosYTPagos = array();
    foreach ($glisoft->juegos as $juego) {
      $juegosYTPagos[]= ['juego'=> $juego, 'tablas_de_pago' => $juego->tablasPago];
    }
    return ['glisoft' => $glisoft , 'expedientes' => $glisoft->expedientes, 'nombre_archivo' => $nombre_archivo , 'juegos' => $juegosYTPagos];
  }

  public function leerArchivoGliSoft($id){
    $data = DB::table('gli_soft')->select('archivo.archivo','archivo.nombre_archivo')
                                ->join('archivo','archivo.id_archivo','=','gli_soft.id_archivo')
                                ->where('gli_soft.id_gli_soft','=',$id)->first();


    return Response::make(base64_decode($data->archivo), 200, [ 'Content-Type' => 'application/pdf',
                                                      'Content-Disposition' => 'inline; filename="'. $data->nombre_archivo  . '"']);
  }

  //METODO QUE RESPONDEN A GUARDAR
  public function guardarGliSoft(Request $request){

    Validator::make($request->all(), [
      'nro_certificado' => ['required','regex:/^\d?\w(.|-|_|\d|\w)*$/'],
      'observaciones' => 'nullable|string',
      'file' => 'sometimes|mimes:pdf',
      'juego' => 'nullable|exists:juego,id_juego'
    ], array(), self::$atributos)->after(function ($validator){
        //$validator->getData()['descripcion'] get campo de validador
    })->validate();

    $GLI=new GliSoft;

    $GLI->nro_archivo =$request->nro_certificado;
    $GLI->observaciones=$request->observaciones;

    if($request->file != null){
      $file=$request->file;
      $archivo=new Archivo;
      $data=base64_encode(file_get_contents($file->getRealPath()));
      $nombre_archivo=$file->getClientOriginalName();
      $archivo->nombre_archivo=$nombre_archivo;
      $archivo->archivo=$data;
      $archivo->save();
      $GLI->archivo()->associate($archivo->id_archivo);
    }

    $GLI->save();

    if(!empty($request->expedientes)){
      $expedientesReq = explode(',',$request->expedientes);
    }else{
      $expedientesReq=null;
    }
    if($expedientesReq != null){
      foreach ($expedientesReq as $exp) {
        if($this->noEstabaEnLista($exp,$GLI->expedientes)){
          $GLI->expedientes()->attach($exp);
        }
      }
    }
    if(isset($request->juegos)){
      $juegos=explode("," , $request->juegos);
      JuegoController::getInstancia()->asociarGLI($juegos , $GLI->id_gli_soft);
    }

    $GLI->save();

    //obtengo solo el nombre del archivo para devolverlo a la vista
    if(!empty($GLI->archivo)){
      $nombre_archivo = $GLI->archivo->nombre_archivo;
    }
    else{
      $nombre_archivo = null;
    }

    return ['gli_soft' => $GLI,  'nombre_archivo' =>$nombre_archivo];
  }

  public function guardarGliSoft_gestionarMaquina($nro_certificado,$observaciones,$file){

        $GLI=new GliSoft;
        $GLI->nro_archivo =$nro_certificado;
        $GLI->observaciones=$observaciones;

        if($file != null){
          $archivo=new Archivo;
          $data=base64_encode(file_get_contents($file->getRealPath()));
          $nombre_archivo=$file->getClientOriginalName();
          $archivo->nombre_archivo=$nombre_archivo;
          $archivo->archivo=$data;
          $archivo->save();
          $GLI->archivo()->associate($archivo->id_archivo);
        }

        $GLI->save();



        return $GLI;
  }

  public function buscarGliSofts(Request $request){
    $reglas = array();
    if(!empty($request->certificado)){
      $reglas[]=['gli_soft.nro_archivo' , 'like' , '%' .  $request->certificado . '%'];
    }
    if(!empty($request->nombre_archivo)){
      $reglas[]=['archivo.nombre_archivo' , 'like' , '%' . $request->nombre_archivo . '%' ];
    }
    if(isset($request->id_juego)){
      $reglas[]=['juego_glisoft.id_juego' , '=' , $request->id_juego];
    }
    $sort_by = $request->sort_by;
    $resultados=DB::table('gli_soft')
    ->select('gli_soft.*', 'archivo.nombre_archivo')
    ->leftJoin('archivo' , 'archivo.id_archivo' , '=' , 'gli_soft.id_archivo')
    ->leftJoin('juego_glisoft','juego_glisoft.id_gli_soft','=','gli_soft.id_gli_soft')
    ->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    })
    ->where($reglas);
    //Elimino duplicados y pagino.
    $resultados = $resultados->groupBy('gli_soft.id_gli_soft')->paginate($request->page_size);

    foreach ($resultados as $index => $resultado) {
      $gli = GliSoft::find($resultado->id_gli_soft);
      $resultados[$index]->eliminar =  ($gli->expedientes->count() && $gli->juegos->count() && $gli->maquinas->count());
    }

    return ['resultados' => $resultados];
  }

  public function modificarGliSoft(Request $request){

      Validator::make($request->all(), [
        'id_gli_soft' => 'required|exists:gli_soft,id_gli_soft',
        'nro_certificado' => ['required','regex:/^\d?\w(.|-|_|\d|\w)*$/','unique:gli_soft,nro_archivo,'.$request->id_gli_soft.',id_gli_soft'],
        'observaciones' => 'nullable|string',
        'file' => 'sometimes|mimes:pdf',
        'juegos' => 'nullable'
      ])->after(function ($validator){
          //$validator->getData()['descripcion'] get campo de validador
      })->validate();

      $GLI=GliSoft::find($request->id_gli_soft);

      $GLI->nro_archivo =$request->nro_certificado;
      $GLI->observaciones=$request->observaciones;

      if(!empty($request->expedientes)){
        $expedientesReq = explode(',',$request->expedientes);
      }else{
        $expedientesReq=null;
      }
      if(isset($GLI->expedientes)){
        foreach ($GLI->expedientes as $expediente) {
          if($this->noEstaEnLista($expediente ,  $expedientesReq )){
            $GLI->expedientes()->detach($expediente->id_expediente);
          }
        }
      }

      if($request->expedientes != null){
        for($i=0; $i<count(  $expedientesReq); $i++){
          if($this->noEstabaEnLista(  $expedientesReq[$i],$GLI->expedientes)){
            $GLI->expedientes()->attach(  $expedientesReq[$i]);
          }
        }
      }



      $JuegoController=JuegoController::getInstancia();
      $JuegoController->desasociarGLI($GLI->id_gli_soft);
      if(!empty($request->juegos)){
        $juegos=explode("," , $request->juegos);
        $JuegoController->asociarGLI($juegos , $GLI->id_gli_soft);
      }

      $GLI->save();

      if(!empty($request->file)){
          if($GLI->archivo != null){
            $archivoAnterior=$GLI->archivo;
            $GLI->archivo()->dissociate();
            $GLI->save();
            $archivoAnterior->delete();
          }

          $file=$request->file;
          $archivo=new Archivo;
          $archivo->nombre_archivo=$file->getClientOriginalName();
          $data=base64_encode(file_get_contents($file->getRealPath()));
          $archivo->archivo=$data;
          $archivo->save();
          $GLI->archivo()->associate($archivo->id_archivo);
          $GLI->save();

      }else{
          if($request->borrado == "true"){
            $archivoAnterior=$GLI->archivo;
            $GLI->archivo()->dissociate();
            $GLI->save();
            $archivoAnterior->delete();
          }
      }

      $GLI->save();
      $GLI=GliSoft::find($request->id_gli_soft);

      if(!empty($GLI->archivo)){
        $nombre_archivo = $GLI->archivo->nombre_archivo;
      }
      else{
        $nombre_archivo = null;
      }

      return ['gli_soft' => $GLI , 'nombre_archivo' => $nombre_archivo ];
  }

  public function getGli($id){
    return GliSoft::find($id);
  }

  public function buscarGliSoftsPorNroArchivo($nro_archivo){
    $reglas = Array();
    if(!empty($nro_archivo))
      $reglas[]=['nro_archivo', 'like', '%'.$nro_archivo.'%'];
    $resultado = GliSoft::where($reglas)->get();
    return ['gli_softs' => $resultado];
  }

  private function noEstaEnLista($expediente ,$expedientes ){//expediente posta ,expedientes del request
    for($i=0; $i<count($expedientes); $i++){
      if($expedientes[$i] == $expediente->id_expediente){
        return false; //si esta en lista
      }
    }
    return true;
  }
  private function noEstabaEnLista($expediente ,$expedientes ){//expediente del request ,expedientes del gli
    if(isset($expedientes)){
      foreach ($expedientes as $exp) {
        if($exp->id_expediente == $expediente){
          return false; //si esta en lista
        }
      }
    }
    return true;
  }



  public function eliminarGLI($id){
    $GLI=GliSoft::find($id);
    $juegos=$GLI->juegos;
    foreach($juegos as $juego){
      $juego->gliSoftOld()->dissociate();
      $juego->save();
    }
    $GLI->setearJuegos([]);
    $GLI->expedientes()->sync([]);

    $archivo=$GLI->archivo;
    $GLI->archivo()->dissociate();
    //lo tengo que guardar primero al gli para que no me tire error por integridad referencial con archivo
    $GLI->save();
    $GLI->delete();
    if(!empty($archivo))
      $archivo->delete();

    return ['gli' => $GLI];
  }

}
