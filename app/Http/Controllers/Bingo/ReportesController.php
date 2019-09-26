<?php

namespace App\Http\Controllers\Bingo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Bingo\ReporteEstado;
use App\Bingo\SesionBingo;
use App\Bingo\ImportacionBingo;
use App\Bingo\PartidaBingo;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\DB;

class ReportesController extends Controller{
    //BUSCAR IMPORTACIONES, RELEVADOS Y ESTADOS PARA ARMAR REPORTE DE DIFERENCIA
    public function buscarReportesDiferencia(Request $request){
      //obtener todos los estados
      $estados = $this->obtenerEstados($request);
      //por cada estado, obtener las importaciones y relevamientos(sesiones/partidas)
      $respuesta = $this->obtenerCargados($estados);

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
        //agrego a seccion reciente a BINGo
        UsuarioController::getInstancia()->agregarSeccionReciente('Reporte Estado' , 'estado-bingo');

        return view('Bingo.ReporteEstado', ['casinos' => $casinos]);
    }
    //BUSCAR ESTADOS
    public function buscarEstado(Request $request){
      //obtengo los estados
      $resultados = $this->obtenerEstados($request);

      return $resultados;
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
      //consulta a la db para obtener los estados que cumplan con las reglas
      $resultados = DB::table('bingo_reporte_estado')
                         ->select('bingo_reporte_estado.*', 'casino.nombre')
                         ->leftJoin('casino' , 'bingo_reporte_estado.id_casino','=','casino.id_casino')
                         ->when($sort_by,function($query) use ($sort_by){
                          return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                        },function($query){
                          return $query->orderBy('id_reporte_estado','desc');
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

      $reglas = array();
      $reglas [] =['fecha_sesion','=', $importacion[0]->fecha];
      $reglas [] =['id_casino','=', $importacion[0]->id_casino];
      $reporte = ReporteEstado::where($reglas)->first();

      return ['importacion' => $importacion, 'sesion' => $sesion, 'reporte' => $reporte];
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
