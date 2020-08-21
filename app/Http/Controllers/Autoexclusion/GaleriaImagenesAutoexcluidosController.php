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
        ->selectRaw("ae_datos.id_autoexcluido,ae_importacion.id_importacion,'foto1' as tipo_archivo,ae_importacion.foto1 as nombre")
        ->join('ae_importacion', 'ae_importacion.id_autoexcluido', '=', 'ae_datos.id_autoexcluido')
        ->join('ae_estado' , 'ae_estado.id_autoexcluido' , '=' , 'ae_datos.id_autoexcluido')
        ->where($reglas)->whereNotNull('ae_importacion.foto1')->whereRaw('LENGTH(ae_importacion.foto1) > 0');

      $resultados_foto2 = DB::table('ae_datos')
        ->selectRaw("ae_datos.id_autoexcluido,ae_importacion.id_importacion,'foto2' as tipo_archivo,ae_importacion.foto2 as nombre")
        ->join('ae_importacion', 'ae_importacion.id_autoexcluido', '=', 'ae_datos.id_autoexcluido')
        ->join('ae_estado' , 'ae_estado.id_autoexcluido' , '=' , 'ae_datos.id_autoexcluido')
        ->where($reglas)->whereNotNull('ae_importacion.foto2')->whereRaw('LENGTH(ae_importacion.foto2) > 0');

      $count =  (clone $resultados_foto1)->count();
      $count += (clone $resultados_foto2)->count();
      $union = $resultados_foto1->union($resultados_foto2);
      
      $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      if(!$user->es_casino_ae){
        $resultados_sol_ae = DB::table('ae_datos')
        ->selectRaw("ae_datos.id_autoexcluido,ae_importacion.id_importacion,'solicitud_ae' as tipo_archivo,ae_importacion.solicitud_ae as nombre")
        ->join('ae_importacion', 'ae_importacion.id_autoexcluido', '=', 'ae_datos.id_autoexcluido')
        ->join('ae_estado' , 'ae_estado.id_autoexcluido' , '=' , 'ae_datos.id_autoexcluido')
        ->where($reglas)->whereNotNull('ae_importacion.solicitud_ae')->whereRaw('LENGTH(ae_importacion.solicitud_ae) > 0');
        $resultados_sol_rev = DB::table('ae_datos')
        ->selectRaw("ae_datos.id_autoexcluido,ae_importacion.id_importacion,'solicitud_revocacion' as tipo_archivo,ae_importacion.solicitud_revocacion as nombre")
        ->join('ae_importacion', 'ae_importacion.id_autoexcluido', '=', 'ae_datos.id_autoexcluido')
        ->join('ae_estado' , 'ae_estado.id_autoexcluido' , '=' , 'ae_datos.id_autoexcluido')
        ->where($reglas)->whereNotNull('ae_importacion.solicitud_revocacion')->whereRaw('LENGTH(ae_importacion.solicitud_revocacion) > 0');
        $resultados_dni = DB::table('ae_datos')
        ->selectRaw("ae_datos.id_autoexcluido,ae_importacion.id_importacion,'scandni' as tipo_archivo,ae_importacion.scandni as nombre")
        ->join('ae_importacion', 'ae_importacion.id_autoexcluido', '=', 'ae_datos.id_autoexcluido')
        ->join('ae_estado' , 'ae_estado.id_autoexcluido' , '=' , 'ae_datos.id_autoexcluido')
        ->where($reglas)->whereNotNull('ae_importacion.scandni')->whereRaw('LENGTH(ae_importacion.scandni) > 0');
        $resultados_caratula = DB::table('ae_datos')
        ->selectRaw("ae_datos.id_autoexcluido,ae_importacion.id_importacion,'caratula' as tipo_archivo,ae_importacion.caratula as nombre")
        ->join('ae_importacion', 'ae_importacion.id_autoexcluido', '=', 'ae_datos.id_autoexcluido')
        ->join('ae_estado' , 'ae_estado.id_autoexcluido' , '=' , 'ae_datos.id_autoexcluido')
        ->where($reglas)->whereNotNull('ae_importacion.caratula')->whereRaw('LENGTH(ae_importacion.caratula) > 0');
        $count += (clone $resultados_sol_ae)->count();
        $count += (clone $resultados_sol_rev)->count();
        $count += (clone $resultados_dni)->count();
        $count += (clone $resultados_caratula)->count();
        $union = $union->union($resultados_sol_ae)
        ->union($resultados_sol_rev)->union($resultados_dni)->union($resultados_caratula);
      }
      
      $pages = ceil($count/floatval($request->size));
      $page = $request->page;
      $page = ($page < 1)? 1 : $page;
      $page = ($page > $pages)? $pages : $page;
      $data = (clone $union)->skip(($page-1)*$request->size)->take($request->size)->get();
      return ['page' => $page,'pages' => $pages,'data' => $data];
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
