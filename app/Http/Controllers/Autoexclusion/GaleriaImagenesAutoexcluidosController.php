<?php

namespace App\Http\Controllers\Autoexclusion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Dompdf\Dompdf;
use View;
use Validator;
use PDF;

use App\Autoexclusion\Autoexcluido;
use App\Autoexclusion\ContactoAE;
use App\Autoexclusion\EstadoAE;
use App\Autoexclusion\Encuesta;
use App\Autoexclusion\ImportacionAE;

use Illuminate\Support\Facades\DB;

class GaleriaImagenesAutoexcluidosController extends Controller
{
    private static $atributos = [
    ];

    public function todo(){
      $juegos =  DB::table('ae_juego_preferido')->get();
      $ocupaciones =  DB::table('ae_ocupacion')->get();
      $frecuencias = DB::table('ae_frecuencia_asistencia')->get();
      $casinos = DB::table('casino')->get();
      $estados_autoexclusion = DB::table('ae_nombre_estado')->get();

      return view('Autoexclusion.galeriaImagenesAutoexcluidos', ['juegos' => $juegos,
                                                                'ocupaciones' => $ocupaciones,
                                                                'casinos' => $casinos,
                                                                'frecuencias' => $frecuencias,
                                                                'estados_autoexclusion' => $estados_autoexclusion
                                                                ]);
    }

    public function getPathsFotosAutoexcluidos (Request $request) {
      $reglas = Array();

      //filtro de búsqueda por apellido
      if(!empty($request->apellido)){
        $reglas[] = ['ae_datos.apellido','LIKE', '%' . $request->apellido . '%'];
      }

      //filtro de búsqueda por dni
      if(!empty($request->dni)){
        $reglas[] = ['ae_datos.nro_dni','=',$request->dni];
      }

      //filtro de búsqueda por casino
      if(!empty($request->casino)){
        $reglas[] = ['ae_estado.id_casino','=',$request->casino];
      }

      $resultados = DB::table('ae_datos')
        ->select('ae_datos.id_autoexcluido as id_ae', 'ae_importacion.foto1 as path')
        ->join('ae_importacion', 'ae_importacion.id_autoexcluido', '=', 'ae_datos.id_autoexcluido')
        ->join('ae_estado' , 'ae_estado.id_autoexcluido' , '=' , 'ae_datos.id_autoexcluido')
        ->where($reglas)
        ->get();

      return $resultados;
    }

    public function getDatosUnAutoexcluido ($id_ae) {
      $resultado = DB::table('ae_datos')
        ->select('ae_datos.*', 'ae_estado.*', 'ae_nombre_estado.descripcion as estado', 'casino.nombre as casino')
        ->join('ae_estado' , 'ae_datos.id_autoexcluido' , '=' , 'ae_estado.id_autoexcluido')
        ->join('ae_nombre_estado', 'ae_nombre_estado.id_nombre_estado', '=', 'ae_estado.id_nombre_estado')
        ->join('casino', 'casino.id_casino', 'ae_estado.id_casino')
        ->where('ae_datos.id_autoexcluido', '=', $id_ae)
        ->get();

      return $resultado;
    }

}
