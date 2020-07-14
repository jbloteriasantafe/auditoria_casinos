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

class InformesAEController extends Controller
{
    private static $atributos = [
    ];

    public function todo(){
      $juegos =  DB::table('ae_juego_preferido')->get();
      $ocupaciones =  DB::table('ae_ocupacion')->get();
      $frecuencias = DB::table('ae_frecuencia_asistencia')->get();
      $casinos = DB::table('casino')->get();
      $estados_autoexclusion = DB::table('ae_nombre_estado')->get();

      return view('Autoexclusion.informesAE', ['juegos' => $juegos,
                                                              'ocupaciones' => $ocupaciones,
                                                              'casinos' => $casinos,
                                                              'frecuencias' => $frecuencias,
                                                              'estados_autoexclusion' => $estados_autoexclusion
                                                              ]);
    }


    public function buscarAutoexcluidos(Request $request){
      $reglas = Array();
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

      //filtro de búsqueda por casino
      if(!empty($request->casino)){
        $reglas[]=['ae_estado.id_casino','=',$request->casino];
      }

      //filtro de búsqueda por estado
      if(!empty($request->estado)){
        $reglas[]=['ae_estado.id_nombre_estado','=',$request->estado];
      }

      //filtro de búsqueda por apellido
      if(!empty($request->apellido)){
        $reglas[]=['ae_datos.apellido','LIKE', '%' . $request->apellido . '%'];
      }

      //filtro de búsqueda por dni
      if(!empty($request->dni)){
        $reglas[]=['ae_datos.nro_dni','=',$request->dni];
      }

      //filtro de búsqueda por sexo
      if($request->sexo != null){
        $reglas[]=['ae_datos.id_sexo','=',$request->sexo];
      }

      //filtro de búsqueda por localidad
      if(!empty($request->localidad)){
        $reglas[]=['ae_datos.nombre_localidad','LIKE', '%' . $request->localidad . '%'];
      }

      //filtro de búsqueda por provincia
      if(!empty($request->provincia)){
        $reglas[]=['ae_datos.nombre_provincia','LIKE', '%' . $request->provincia . '%'];
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

      //filtro de búsqueda por fecha de revocación desde
      if(!empty($request->fecha_revocacion_desde)){
        $reglas[]=['ae_estado.fecha_revocacion_ae','>=',$request->fecha_revocacion_desde];
      }

      //filtro de búsqueda por fecha de revocación hasta
      if(!empty($request->fecha_revocacion_hasta)){
        $reglas[]=['ae_estado.fecha_revocacion_ae','<=',$request->fecha_revocacion_hasta];
      }

      //filtro de búsqueda por fecha de cierre desde
      if(!empty($request->fecha_cierre_desde)){
        $reglas[]=['ae_estado.fecha_cierre_ae','>=',$request->fecha_cierre_desde];
      }

      //filtro de búsqueda por fecha de cierre hasta
      if(!empty($request->fecha_cierre_hasta)){
        $reglas[]=['ae_estado.fecha_cierre_ae','<=',$request->fecha_cierre_hasta];
      }

      $sort_by = $request->sort_by;
      $resultados = DB::table('ae_datos')
        ->select('ae_datos.*', 'ae_estado.*', 'casino.codigo as casino', 'ae_nombre_estado.descripcion as estado')
        ->join('ae_estado' , 'ae_datos.id_autoexcluido' , '=', 'ae_estado.id_autoexcluido')
        ->join('casino', 'ae_estado.id_casino', '=', 'casino.id_casino')
        ->join('ae_nombre_estado', 'ae_estado.id_nombre_estado', '=', 'ae_nombre_estado.id_nombre_estado')
        ->when($sort_by,function($query) use ($sort_by){
                        return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                    })
        ->where($reglas)
        ->paginate($request->page_size);

      return $resultados;
    }

    public function verFoto($id_ae){
      $imp = ImportacionAE::where('id_autoexcluido', '=', $id_ae)->first();

      return response()->file($imp->foto1);
    }



}
