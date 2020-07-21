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
      
      $resultados_foto1 = DB::table('ae_datos')
        ->selectRaw("ae_datos.id_autoexcluido,ae_importacion.id_importacion,'foto1' as tipo_archivo")
        ->join('ae_importacion', 'ae_importacion.id_autoexcluido', '=', 'ae_datos.id_autoexcluido')
        ->join('ae_estado' , 'ae_estado.id_autoexcluido' , '=' , 'ae_datos.id_autoexcluido')
        ->where($reglas)->whereNotNull('ae_importacion.foto1');

      $resultados_foto2 = DB::table('ae_datos')
        ->selectRaw("ae_datos.id_autoexcluido,ae_importacion.id_importacion,'foto2' as tipo_archivo")
        ->join('ae_importacion', 'ae_importacion.id_autoexcluido', '=', 'ae_datos.id_autoexcluido')
        ->join('ae_estado' , 'ae_estado.id_autoexcluido' , '=' , 'ae_datos.id_autoexcluido')
        ->where($reglas)->whereNotNull('ae_importacion.foto2');

      $resultados_sol_ae = DB::table('ae_datos')
        ->selectRaw("ae_datos.id_autoexcluido,ae_importacion.id_importacion,'solicitud_ae' as tipo_archivo")
        ->join('ae_importacion', 'ae_importacion.id_autoexcluido', '=', 'ae_datos.id_autoexcluido')
        ->join('ae_estado' , 'ae_estado.id_autoexcluido' , '=' , 'ae_datos.id_autoexcluido')
        ->where($reglas)->whereNotNull('ae_importacion.solicitud_ae');

      $resultados_sol_rev = DB::table('ae_datos')
        ->selectRaw("ae_datos.id_autoexcluido,ae_importacion.id_importacion,'solicitud_rev' as tipo_archivo")
        ->join('ae_importacion', 'ae_importacion.id_autoexcluido', '=', 'ae_datos.id_autoexcluido')
        ->join('ae_estado' , 'ae_estado.id_autoexcluido' , '=' , 'ae_datos.id_autoexcluido')
        ->where($reglas)->whereNotNull('ae_importacion.solicitud_revocacion');

      $resultados_dni = DB::table('ae_datos')
        ->selectRaw("ae_datos.id_autoexcluido,ae_importacion.id_importacion,'scandni' as tipo_archivo")
        ->join('ae_importacion', 'ae_importacion.id_autoexcluido', '=', 'ae_datos.id_autoexcluido')
        ->join('ae_estado' , 'ae_estado.id_autoexcluido' , '=' , 'ae_datos.id_autoexcluido')
        ->where($reglas)->whereNotNull('ae_importacion.scandni');

      return $resultados_foto1->union($resultados_foto2)->union($resultados_sol_ae)
                              ->union($resultados_sol_rev)->union($resultados_dni)->get();
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
