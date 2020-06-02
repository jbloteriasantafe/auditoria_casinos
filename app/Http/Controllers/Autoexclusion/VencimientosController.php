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

class VencimientosController extends Controller
{
    private static $atributos = [
    ];

    public function todo(){
      $juegos =  DB::table('ae_juego_preferido')->get();
      $ocupaciones =  DB::table('ae_ocupacion')->get();
      $frecuencias = DB::table('ae_frecuencia_asistencia')->get();
      $casinos = DB::table('casino')->get();
      $estados_autoexclusion = DB::table('ae_nombre_estado')->get();

      return view('Autoexclusion.vencimientos', ['juegos' => $juegos,
                                                'ocupaciones' => $ocupaciones,
                                                'casinos' => $casinos,
                                                'frecuencias' => $frecuencias,
                                                'estados_autoexclusion' => $estados_autoexclusion
                                                ]);
    }

    //Función que retorna los autoexcluidos que pueden finalizar su autoexclusión
    public function buscarAutoexcluidos(Request $request){
      $reglas = Array();
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

      $fecha_hoy = date('y-m-j');
      $fecha_tope = date('y-m-j', strtotime($fecha_hoy . '+30 days'));

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

      //filtro de búsqueda por fecha de autoexclusion
      if(!empty($request->fecha_autoexclusion)){
        $reglas[]=['ae_estado.fecha_ae','=',$request->fecha_autoexclusion];
      }

      //filstro de búsqueda por fecha de vencimiento
      if(!empty($request->fecha_vencimiento)){
        $reglas[]=['ae_estado.fecha_vencimiento','=',$request->fecha_vencimiento];
      }

      if(!empty($request->id_autoexcluido)) {
        $reglas[]=['ae_datos.id_autoexcluido','=',$request->id_autoexcluido];
      }

      $sort_by = $request->sort_by;
      $resultados = DB::table('ae_datos')
        ->select('ae_datos.*', 'ae_estado.*')
        ->join('ae_estado' , 'ae_datos.id_autoexcluido' , '=' , 'ae_estado.id_autoexcluido')
        ->when($sort_by,function($query) use ($sort_by){
                        return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                    })
        ->where($reglas)
        ->where('ae_estado.fecha_vencimiento', '>=', $fecha_hoy)
        ->where('ae_estado.fecha_vencimiento', '<=', $fecha_tope)
        ->where('ae_estado.id_nombre_estado', '!=', 2) //@TODO: ver si hay que excluir tambien otros estados
        ->paginate($request->page_size);

      return $resultados;
    }

    public function imprimirFormularioFinalizacion($id){
      $ae = Autoexcluido::find($id);
      $estado = EstadoAE::where('id_autoexcluido', '=', $ae['id_autoexcluido'])->first();

      $datos = array(
        'fecha_vencimiento' => date("d-m-Y", strtotime($estado->fecha_vencimiento))
      );

      $view = View::make('Autoexclusion.planillaFormularioFinalizacionAE', compact('ae', 'datos'));
      $dompdf = new Dompdf();
      $dompdf->set_paper('A4', 'portrait');
      $dompdf->loadHtml($view->render());
      $dompdf->render();
      $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
      $dompdf->getCanvas()->page_text(20, 820, "Dirección General de Casinos y Bingos / Caja de Asistencia Social - Lotería de Santa Fe", $font, 8, array(0,0,0));
      $dompdf->getCanvas()->page_text(525, 820, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 8, array(0,0,0));

      return $dompdf->stream("solicitud_finalizacion_autoexclusion_" . date('Y-m-d') . ".pdf", Array('Attachment'=>0));
    }

    public function finalizarAutoexclusion(Request $request){
      Validator::make($request->all(), [
            'fecha_finalizacion' => 'required',
            'formulario_finalizacion' => 'file|mimes:jpg,jpeg,png,pdf',
        ], array(), self::$atributos)->after(function($validator){
        })->validate();

      //guardo en bdd la fecha de revocacion de la autoexclusion
      DB::transaction(function() use($request){
        $estado = EstadoAE::where('id_autoexcluido', '=', $request->id_ae)->first();
        $estado->fecha_revocacion_ae = $request->fecha_finalizacion;
        $estado->id_nombre_estado = 2; //cambio el estado a renovado (2)
        $estado->save();
      });

      $ae = Autoexcluido::find($request->id_ae);

      //consigo el path del directorio raiz del proyecto
      $pathAbs = realpath('../');

      //genero el nombre del archivo
      $nombre_archivo = date("dmY") . '-' . $ae->nro_dni . '-5';

      //establezco el path de destino del archivo
      $pathDestinoFormulario = $pathAbs . '/public/importacionesAutoexcluidos/solicitudes/' . $nombre_archivo;

      //copio el archivo subido al filesystem
      copy($request->formulario_finalizacion->getRealPath(), $pathDestinoFormulario);

      $imp = ImportacionAE::where('ae_importacion.id_autoexcluido','=',$request->id_ae)->first();
      $imp->solicitud_revocacion = $nombre_archivo;
      $imp->save();

      return ['codigo' => 200];
    }


}
