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

    public function todo($dni = null){
      UsuarioController::getInstancia()->agregarSeccionReciente('Galería Autoexcluidos' , 'galeriaAE');
      $juegos =  DB::table('ae_juego_preferido')->get();
      $ocupaciones =  DB::table('ae_ocupacion')->get();
      $frecuencias = DB::table('ae_frecuencia_asistencia')->get();
      $casinos = DB::table('casino')->get();
      $estados_autoexclusion = DB::table('ae_nombre_estado')->get();
      
      return view('Autoexclusion.galeriaImagenesAutoexcluidos', ['juegos' => $juegos,
                                                                'ocupaciones' => $ocupaciones,
                                                                'casinos' => $casinos,
                                                                'frecuencias' => $frecuencias,
                                                                'estados_autoexclusion' => $estados_autoexclusion,
                                                                'dni' => $dni
                                                                ]);
    }

    public function getPathsFotosAutoexcluidos (Request $request) {
      AutoexclusionController::getInstancia()->actualizarVencidosRenovados();
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
        ->selectRaw("ae_datos.id_autoexcluido,ae_datos.nro_dni,ae_importacion.id_importacion,'foto1' as tipo_archivo,ae_importacion.foto1 as nombre")
        ->join('ae_importacion', 'ae_importacion.id_autoexcluido', '=', 'ae_datos.id_autoexcluido')
        ->join('ae_estado' , 'ae_estado.id_autoexcluido' , '=' , 'ae_datos.id_autoexcluido')
        ->where($reglas)->whereNotNull('ae_importacion.foto1')->whereRaw('LENGTH(ae_importacion.foto1) > 0');

      $resultados_foto2 = DB::table('ae_datos')
        ->selectRaw("ae_datos.id_autoexcluido,ae_datos.nro_dni,ae_importacion.id_importacion,'foto2' as tipo_archivo,ae_importacion.foto2 as nombre")
        ->join('ae_importacion', 'ae_importacion.id_autoexcluido', '=', 'ae_datos.id_autoexcluido')
        ->join('ae_estado' , 'ae_estado.id_autoexcluido' , '=' , 'ae_datos.id_autoexcluido')
        ->where($reglas)->whereNotNull('ae_importacion.foto2')->whereRaw('LENGTH(ae_importacion.foto2) > 0');

      $count =  (clone $resultados_foto1)->count();
      $count += (clone $resultados_foto2)->count();
      $union = $resultados_foto1->union($resultados_foto2);
    
      $pages = ceil($count/floatval($request->size));
      $page = $request->page;
      $page = ($page < 1)? 1 : $page;
      $page = ($page > $pages)? $pages : $page;
      $data = (clone $union)->skip(($page-1)*$request->size)->take($request->size)->get();
      return ['page' => $page,'pages' => $pages,'data' => $data];
    }

    //Retorna el estado de su ultima autoexclusió
    public function getDatosUnAutoexcluido ($nro_dni) {
      AutoexclusionController::getInstancia()->actualizarVencidosRenovados();
      
      $resultado = DB::table('ae_datos')
        ->select('ae_datos.*', 'ae_estado.*', 'ae_nombre_estado.descripcion as estado', 'casino.nombre as casino')
        ->join('ae_estado' , 'ae_datos.id_autoexcluido' , '=' , 'ae_estado.id_autoexcluido')
        ->join('ae_nombre_estado', 'ae_nombre_estado.id_nombre_estado', '=', 'ae_estado.id_nombre_estado')
        ->join('casino', 'casino.id_casino', 'ae_estado.id_casino')
        ->where('ae_datos.nro_dni', '=', $nro_dni)
        ->orderBy('ae_datos.id_autoexcluido','desc')
        ->take(1)->get();

      return $resultado;
    }

}
