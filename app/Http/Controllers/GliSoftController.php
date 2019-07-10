<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Response;
use App\GliSoft;
use App\Archivo;
use App\Casino;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
      return view('seccionGLISoft' , ['glis' => $glisofts,'casinos' => $casinos]);
  }

  public function obtenerGliSoft($id){
    $glisoft = GliSoft::find($id);
    $size = 0;
    $nombre_archivo = null;

    if(!empty($glisoft->archivo)){
      $nombre_archivo = $glisoft->archivo->nombre_archivo;
      //Saca el tamaño approx de una string encodeada en base64
      $size=(int) (strlen(rtrim($glisoft->archivo->archivo, '=')) * 3 / 4);
    }
    
    $juegosYTPagos = array();
    foreach ($glisoft->juegos as $juego) {
      $juegosYTPagos[]= ['juego'=> $juego, 'tablas_de_pago' => $juego->tablasPago];
    }
    return ['glisoft' => $glisoft ,
    'expedientes' => $glisoft->expedientes,
    'nombre_archivo' => $nombre_archivo ,
    'juegos' => $juegosYTPagos,
    'size' => $size];
  }

  public function leerArchivoGliSoft($id){
    $archivo = GliSoft::find($id)->archivo;

    return Response::make(base64_decode($archivo->archivo),
    200,
    [ 'Content-Type' => 'application/pdf',
    'Content-Disposition' => 'inline; filename="'. $archivo->nombre_archivo  . '"']);
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
      $archivo->setearDatosArchivo($file);
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
    $GLI->save();

    if(!empty($request->juegos)){
      $juegos=explode("," , $request->juegos);
      JuegoController::getInstancia()->asociarGLI($juegos , $GLI->id_gli_soft);
    }

    $nombre_archivo = null;
    //obtengo solo el nombre del archivo para devolverlo a la vista
    if(!empty($GLI->archivo)){
      $nombre_archivo = $GLI->archivo->nombre_archivo;
    }

    return ['gli_soft' => $GLI,  'nombre_archivo' =>$nombre_archivo];
  }

  public function guardarGliSoft_gestionarMaquina($nro_certificado,$observaciones,$file){
        $GLI=new GliSoft;
        $GLI->nro_archivo =$nro_certificado;
        $GLI->observaciones=$observaciones;

        if($file != null){
          $archivo=new Archivo;
          $archivo->setearDatosArchivo($file);
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
    $sort_by = $request->sort_by;
    $resultados=DB::table('gli_soft')
    ->select('gli_soft.*', 'archivo.nombre_archivo')
    ->leftJoin('archivo' , 'archivo.id_archivo' , '=' , 'gli_soft.id_archivo')
    ->where($reglas)
    //->groupBy('gli_soft.id_gli_soft')
    ->when($sort_by,function($query) use ($sort_by){
                       return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                   })

       ->paginate($request->page_size);

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


      $GLI->save();

      $JuegoController=JuegoController::getInstancia();
      $JuegoController->desasociarGLI($GLI->id_gli_soft);
      if(!empty($request->juegos)){
        $juegos=explode("," , $request->juegos);
        $JuegoController->asociarGLI($juegos , $GLI->id_gli_soft);
      }

      if(!empty($request->file)){
          if($GLI->archivo != null){
            $archivoAnterior=$GLI->archivo;
            $GLI->archivo()->dissociate();
            $GLI->save();
            $archivoAnterior->delete();
          }

          $file=$request->file;
          $archivo=new Archivo;
          $archivo->setearDatosArchivo($file);
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

    //Retornar error si tiene un jueog asociado.
    foreach($juegos as $juego){
      $juego->GliSoft()->dissociate();
      $juego->save();
    }

    DB::table('expediente_tiene_gli_sw')
    ->where('expediente_tiene_gli_sw.id_gli_soft' , '=' , $id)
    ->delete();

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
