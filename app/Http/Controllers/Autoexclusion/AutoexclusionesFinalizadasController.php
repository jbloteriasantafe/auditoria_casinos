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

class AutoexclusionesFinalizadasController extends Controller
{
    private static $atributos = [
    ];

    public function todo(){
      $juegos =  DB::table('ae_juego_preferido')->get();
      $ocupaciones =  DB::table('ae_ocupacion')->get();
      $frecuencias = DB::table('ae_frecuencia_asistencia')->get();
      $casinos = DB::table('casino')->get();
      $estados_autoexclusion = DB::table('ae_nombre_estado')->get();

      return view('Autoexclusion.autoexclusionesFinalizadas', ['juegos' => $juegos,
                                                              'ocupaciones' => $ocupaciones,
                                                              'casinos' => $casinos,
                                                              'frecuencias' => $frecuencias,
                                                              'estados_autoexclusion' => $estados_autoexclusion
                                                              ]);
    }

    public function buscarAutoexcluidos(Request $request){
      $reglas = Array();
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

      //filtro de búsqueda por apellido
      if(!empty($request->apellido)){
        $reglas[]=['ae_datos.apellido','LIKE', '%' . $request->apellido . '%'];
      }

      //filtro de búsqueda por dni
      if(!empty($request->dni)){
        $reglas[]=['ae_datos.nro_dni','=',$request->dni];
      }

      //filtro de búsqueda por casino
      if(!empty($request->casino)){
        $reglas[]=['ae_estado.id_casino','=',$request->casino];
      }

      //filtro de búsqueda por fecha de autoexclusion desde
      if(!empty($request->fecha_autoexclusion_desde)){
        $reglas[]=['ae_estado.fecha_ae','>=',$request->fecha_autoexclusion_desde];
      }

      //filtro de búsqueda por fecha de autoexclusion hasta
      if(!empty($request->fecha_autoexclusion_hasta)){
        $reglas[]=['ae_estado.fecha_ae','<=',$request->fecha_autoexclusion_hasta];
      }

      //filtro de búsqueda por fecha de vencimiento desde
      if(!empty($request->fecha_vencimiento_desde)){
        $reglas[]=['ae_estado.fecha_vencimiento','>=',$request->fecha_vencimiento_desde];
      }

      //filtro de búsqueda por fecha de vencimiento hasta
      if(!empty($request->fecha_vencimiento_hasta)){
        $reglas[]=['ae_estado.fecha_vencimiento','<=',$request->fecha_vencimiento_hasta];
      }

      //filtro de búsqueda por fecha de finalización desde
      if(!empty($request->fecha_finalizacion_desde)){
        $reglas[]=['ae_estado.fecha_revocacion_ae','>=',$request->fecha_finalizacion_desde];
      }

      //filtro de búsqueda por fecha de finalizacion hasta
      if(!empty($request->fecha_finalizacion_hasta)){
        $reglas[]=['ae_estado.fecha_revocacion_ae','<=',$request->fecha_finalizacion_hasta];
      }

      $sort_by = $request->sort_by;
      $resultados = DB::table('ae_datos')
        ->select('ae_datos.*', 'ae_estado.*', 'casino.nombre as cas')
        ->join('ae_estado' , 'ae_datos.id_autoexcluido' , '=', 'ae_estado.id_autoexcluido')
        ->join('casino', 'ae_estado.id_casino', '=', 'casino.id_casino')
        ->when($sort_by,function($query) use ($sort_by){
                        return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                    })
        ->where($reglas)
        ->where('ae_estado.id_nombre_estado', '=', 2)
        ->paginate($request->page_size);

      return $resultados;
    }

    public function verSolicitudFinalizacion ($id_ae) {
      $imp = ImportacionAE::where('id_autoexcluido', '=', $id_ae)->first();
      if(is_null($imp)) return 'Autoexcluido no encontrado';
      $pathAbs = realpath('../');
      $path = $pathAbs . '/public/importacionesAutoexcluidos/solicitudes/' . $imp->solicitud_revocacion;
      if(file_exists($path)) return response()->file($path);
      return "Solicitud de finalización no encontrada";
    }
}
