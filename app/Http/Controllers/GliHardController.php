<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Response;
use App\GliHard;
use App\Casino;
use App\Archivo;
use Illuminate\Support\Facades\DB;
use Validator;

class GliHardController extends Controller
{
  private static $instance;

  public static function getInstancia() {
      if (!isset(self::$instance)) {
          self::$instance = new GliHardController();
      }
      return self::$instance;
  }

  public function getGli($id){
    return GliHard::find($id);
  }

  public function buscarTodo(){
      $glihard=GliHard::all();
      $casinos=Casino::all();
      UsuarioController::getInstancia()->agregarSeccionReciente('GLI Hard' , 'certificadoHard');

      return view('seccionGLIHard' , ['glis' => $glihard, 'casinos' =>$casinos ]);
  }

  public function obtenerGliHard($id){
    $glihard = GliHard::find($id);
    if(!empty($glihard->archivo)){
      $nombre_archivo = $glihard->archivo->nombre_archivo;
    }
    else{
      $nombre_archivo = null;
    }
    return ['glihard' => $glihard,'nombre_archivo' => $nombre_archivo, 'expedientes' => $glihard->expedientes ];
  }

  public function leerArchivoGliHard($id){
    $file = GliHard::find($id);
    $data = $file->archivo->archivo;

    return Response::make(base64_decode($data), 200, [ 'Content-Type' => 'application/pdf',
                                                      'Content-Disposition' => 'inline; filename="'. $file->nombre_archivo  . '"']);
  }

  public function buscarGliHardsPorNroArchivo($nro_archivo){
    $reglas = Array();
    if(!empty($nro_archivo))
      $reglas[]=['nro_archivo', 'like', '%'.$nro_archivo.'%'];
    $resultado = GliHard::where($reglas)->get();
    return ['gli_hards' => $resultado];
  }

  public function buscarGliHard(Request $request){
    $reglas = array();

    if(!empty($request->certificado)){
      $reglas[]=['gli_hard.nro_archivo' , 'like' ,'%' . $request->certificado . '%']; //nro_archivo = certificado ? ?
    }
    if(!empty($request->nombre_archivo)){
      $reglas[]=['archivo.nombre_archivo' , 'like' , '%' . $request->nombre_archivo . '%' ];
    }
    $sort_by = $request->sort_by;
    $resultados=DB::table('gli_hard')
                  ->select('gli_hard.*', 'archivo.nombre_archivo')
                  ->leftJoin('archivo' , 'archivo.id_archivo' , '=' , 'gli_hard.id_archivo')
                  ->where($reglas)
                 ->when($sort_by,function($query) use ($sort_by){
                                    return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                })

                    ->paginate($request->page_size);

    return ['resultados' => $resultados];
  }

  public function guardarGliHard(Request $request){

      Validator::make($request->all(), [
        'nro_certificado' => 'required|alpha_dash',
        'expedientes' => 'nullable',
        'file' => 'sometimes|mimes:pdf'
      ])->after(function ($validator){
          //$validator->getData()['descripcion'] get campo de validador
      })->validate();

      $GLI=new GliHard;
      $GLI->nro_archivo =$request->nro_certificado;

      if($request->file != null){
            $archivo=new Archivo;
            $file=$request->file;
            $nombre_archivo=$file->getClientOriginalName();
            $data=base64_encode(file_get_contents($file->getRealPath()));
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

      if(!empty($GLI->archivo)){
        $nombre_archivo = $GLI->archivo->nombre_archivo;
      }
      else{
        $nombre_archivo = '-';
      }

      return ['glihard' => $GLI, 'nombre_archivo' => $nombre_archivo];
  }

  public function guardarNuevoGliHard($nro_certificado,$expedientes,$file){
    $request = new Request;
    $request->merge(['nro_certificado'=>$nro_certificado]);
    $request->merge(['expedientes'=>$expedientes]);
    $request->merge(['file'=>$file]);
    dump($request->all());
    return $this->guardarGliHard($request)['glihard'];
  }

  public function modificarGliHard(Request $request){

      Validator::make($request->all(), [
        'id_gli_hard' => 'required|exists:gli_hard,id_gli_hard',
        'nro_certificado' => 'required|alpha_dash|unique:gli_hard,nro_archivo,'.$request->id_gli_hard.',id_gli_hard',
         'file' => 'sometimes|mimes:pdf',
      ])->after(function ($validator){
          //$validator->getData()['descripcion'] get campo de validador
      })->validate();

      $GLI=GliHard::find($request->id_gli_hard);

      $GLI->nro_archivo =$request->nro_certificado;
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
            if(!is_null($archivoAnterior)){
              $GLI->archivo()->dissociate();
              $GLI->save();
              $archivoAnterior->delete();
            }
          }
      }
      if(!empty($request->expedientes)){
        $expedientesReq = explode(',',$request->expedientes);
      }else{
        $expedientesReq=null;
      }
      if(isset($GLI->expedientes)){
        foreach ($GLI->expedientes as $expediente) {
          if($this->noEstaEnLista($expediente ,$expedientesReq )){
            $GLI->expedientes()->detach($expediente->id_expediente);
          }
        }
      }

      if($expedientesReq != null){
        foreach ($expedientesReq as $exp) {
          if($this->noEstabaEnLista($exp,$GLI->expedientes)){
            $GLI->expedientes()->attach($exp);
          }
        }
      }

      $GLI->save();

      if(!empty($GLI->archivo)){
        $nombre_archivo = $GLI->archivo->nombre_archivo;
      }
      else{
        $nombre_archivo = '-';
      }

      return ['glihard' => $GLI , 'nombre_archivo' => $nombre_archivo ];
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


  public function eliminarGliHard($id){
    $GLI=GliHard::find($id);

    $archivo=$GLI->archivo;
    $GLI->archivo()->dissociate();
    $GLI->expedientes()->detach();
    //lo tengo que guardar primero al gli para que no me tire error por integridad referencial con archivo
    $GLI->save();
    $GLI->delete();
    if(!empty($archivo))
      $archivo->delete();

    return ['gli' => $GLI];
  }

}
