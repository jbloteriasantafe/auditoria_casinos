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
use App\Autoexclusion\NombreEstadoAutoexclusion;
use App\Autoexclusion\Encuesta;
use App\Autoexclusion\ImportacionAE;

use Illuminate\Support\Facades\DB;

class AutoexclusionController extends Controller
{
    private static $atributos = [
    ];

    public function index(){
      $juegos =  DB::table('ae_juego_preferido')->get();
      $ocupaciones =  DB::table('ae_ocupacion')->get();
      $frecuencias = DB::table('ae_frecuencia_asistencia')->get();
      $casinos = DB::table('casino')->get();
      $estados_autoexclusion = DB::table('ae_nombre_estado')->get();
      $estados_civiles = DB::table('ae_estado_civil')->get();
      $capacitaciones = DB::table('ae_capacitacion')->get();


      return view('Autoexclusion.index', ['juegos' => $juegos,
                                          'ocupaciones' => $ocupaciones,
                                          'casinos' => $casinos,
                                          'frecuencias' => $frecuencias,
                                          'estados_autoexclusion' => $estados_autoexclusion,
                                          'estados_civiles' => $estados_civiles,
                                          'capacitaciones' => $capacitaciones
                                        ]);
    }

    //Función para buscar los autoexcluidos existentes en el sistema
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

      //filtro de búsqueda por estado
      if(!empty($request->estado)){
        $reglas[]=['ae_estado.id_nombre_estado','=',$request->estado];
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

      //filtro de búsqueda por fecha de finalización
      if(!empty($request->fecha_finalizacion)){
        $reglas[]=['ae_estado.fecha_renovacion','=',$request->fecha_finalizacion];
      }

      //filtro de búsqueda por fecha de cierre definitivo
      if(!empty($request->fecha_cierre_definitivo)){
        $reglas[]=['ae_estado.fecha_cierre_ae','=',$request->fecha_cierre_definitivo];
      }

      if(!empty($request->id_autoexcluido)) {
        $reglas[]=['ae_datos.id_autoexcluido','=',$request->id_autoexcluido];
      }

      $sort_by = $request->sort_by;
      $resultados = DB::table('ae_datos')
        ->select('ae_datos.*', 'ae_datos_contacto.*', 'ae_encuesta.*', 'ae_estado.*', 'ae_nombre_estado.descripcion')
        //hago un left join de datos contacto y encuesta porque son opcionales, sino solo me devolveria
        //los autoexcluidos que tienen datos de contacto y de encuesta existentes
        ->leftJoin('ae_datos_contacto' , 'ae_datos.id_autoexcluido' , '=' , 'ae_datos_contacto.id_autoexcluido')
        ->leftJoin('ae_encuesta' , 'ae_datos.id_autoexcluido' , '=' , 'ae_encuesta.id_autoexcluido')
        ->join('ae_estado' , 'ae_datos.id_autoexcluido' , '=' , 'ae_estado.id_autoexcluido')
        ->join('ae_nombre_estado', 'ae_nombre_estado.id_nombre_estado', '=', 'ae_estado.id_nombre_estado')
        ->when($sort_by,function($query) use ($sort_by){
                        return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                    })
        ->where($reglas)
        ->paginate($request->page_size);

      return $resultados;
    }

    //Función para agregar un nuevo autoexcluido complet, o editar uno existente
    public function agregarAE(Request $request){
      Validator::make($request->all(), [
        'ae_datos.nro_dni'          => 'required|integer',
        'ae_datos.apellido'         => 'required|string|max:100',
        'ae_datos.nombres'          => 'required|string|max:150',
        'ae_datos.fecha_nacimiento' => 'required|date',
        'ae_datos.id_sexo'          => 'required|integer',
        'ae_datos.domicilio'        => 'required|string|max:100',
        'ae_datos.nro_domicilio'    => 'required|integer',
        'ae_datos.nombre_localidad' => 'required|string|max:200',
        'ae_datos.nombre_provincia' => 'required|string|max:200',
        'ae_datos.telefono'         => 'required|string|max:200',
        'ae_datos.correo'           => 'required|string|max:100',
        'ae_datos.id_ocupacion'     => 'required|integer|exists:ae_ocupacion,id_ocupacion',
        'ae_datos.id_capacitacion'  => 'required|integer|exists:ae_capacitacion,id_capacitacion',
        'ae_datos.id_estado_civil'  => 'required|integer|exists:ae_estado_civil,id_estado_civil',
        'ae_datos_contacto.nombre_apellido'  => 'nullable|string|max:200',
        'ae_datos_contacto.domicilio'        => 'nullable|string|max:200',
        'ae_datos_contacto.nombre_localidad' => 'nullable|string|max:200',
        'ae_datos_contacto.nombre_provincia' => 'nullable|string|max:200',
        'ae_datos_contacto.telefono'         => 'nullable|string|max:200',
        'ae_datos_contacto.vinculo'          => 'nullable|string|max:200',
        'ae_estado.id_nombre_estado'  => 'required|integer|exists:ae_nombre_estado,id_nombre_estado',
        'ae_estado.id_casino'         => 'required|integer|exists:casino,id_casino',
        'ae_estado.fecha_ae'          => 'required|date',
        'ae_estado.fecha_vencimiento' => 'required|date',
        'ae_estado.fecha_renovacion'  => 'required|date',
        'ae_estado.fecha_cierre_ae'   => 'required|date',
        'ae_encuesta.id_juego_preferido'        => 'nullable|integer|exists:ae_juego_preferido,id_juego_preferido',
        'ae_encuesta.id_frecuencia_asistencia'  => 'nullable|integer|exists:ae_frecuencia_asistencia,id_frecuencia',
        'ae_encuesta.veces'                     => 'nullable|integer',
        'ae_encuesta.tiempo_jugado'             => 'nullable|integer',
        'ae_encuesta.club_jugadores'            => 'nullable|string|max:2',
        'ae_encuesta.juego_responsable'         => 'nullable|string|max:2',
        'ae_encuesta.autocontrol_juego'         => 'nullable|string|max:2',
        'ae_encuesta.recibir_informacion'       => 'nullable|string|max:2',
        'ae_encuesta.medio_recibir_informacion' => 'nullable|string|max:100',
        'ae_encuesta.como_asiste'               => 'nullable|integer',
        'ae_encuesta.observacion'               => 'nullable|string|max:200',
        'ae_importacion.foto1'                => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        'ae_importacion.foto2'                => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        'ae_importacion.solicitud_ae'         => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        'ae_importacion.solicitud_revocacion' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        'ae_importacion.scandni'              => 'nullable|file|mimes:jpg,jpeg,png,pdf',
      ], array(), self::$atributos)->after(function($validator){})->validate();
      
      $esNuevo = null;
      DB::transaction(function() use($request, &$esNuevo){
        $datos = $request['ae_datos'];
        $esNuevo = Autoexcluido::where('nro_dni', '=', $datos['nro_dni'])->count() == 0;
        $bdAE = null;
        if($esNuevo){
          $bdAE = new AutoExcluido;
        }
        else {
          $bdAE = Autoexcluido::where('nro_dni', '=', $datos['nro_dni'])->first();
        }

        foreach($datos as $key => $val){
          $bdAE->{$key} = $val;
        }
        $bdAE->save();

        $id_autoexcluido = $bdAE->id_autoexcluido;

        $tieneContacto = ContactoAE::where('id_autoexcluido', '=', $id_autoexcluido)->count() > 0;
        $bdContacto = null;
        if($tieneContacto){
          $bdContacto = ContactoAE::where('id_autoexcluido', '=', $id_autoexcluido)->first();
        }
        else{
          $bdContacto = new ContactoAE;
          $bdContacto->id_autoexcluido = $id_autoexcluido;
        }

        $datos_contacto = $request['ae_datos_contacto'];
        foreach($datos_contacto as $key => $val){
          $bdContacto->{$key} = $val;
        }
        $bdContacto->save();

        $tieneEstado = EstadoAE::where('id_autoexcluido', '=', $id_autoexcluido)->count() > 0;
        $bdEstado = null;
        if($tieneEstado){
          $bdEstado = EstadoAE::where('id_autoexcluido', '=', $id_autoexcluido)->first();
        }
        else{
          $bdEstado = new EstadoAE;
          $bdEstado->id_autoexcluido = $id_autoexcluido;
        }

        $id_usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario;
        $bdEstado->id_usuario = $id_usuario;
        $estado = $request['ae_estado'];
        foreach($estado as $key => $val){
          $bdEstado->{$key} = $val;
        }
        $bdEstado->save();

        $tieneEncuesta = Encuesta::where('id_autoexcluido', '=', $id_autoexcluido)->count() > 0;
        $bdEncuesta = null;
        if($tieneEncuesta){
          $bdEncuesta =  Encuesta::where('id_autoexcluido', '=', $id_autoexcluido)->first();
        }
        else{
          $bdEncuesta = new Encuesta;
          $bdEncuesta->id_autoexcluido = $id_autoexcluido;
        }
        $encuesta = $request['ae_encuesta'];
        foreach($encuesta as $key => $val){
          $bdEncuesta->{$key} = $val;
        }
        $bdEncuesta->save();

        $importacion = $request['ae_importacion'];
        $this->subirImportacionArchivos($bdAE,$importacion);
      });
      return ['nuevo' => $esNuevo];
    }
 
    public function subirImportacionArchivos($autoexcluido,$importacion) {
      $tieneImportacion = ImportacionAE::where('id_autoexcluido','=',$autoexcluido->id_autoexcluido)->count() > 0;
      $bdImp = null;
      if ($tieneImportacion){
        $bdImp = ImportacionAE::where('id_autoexcluido','=',$autoexcluido->id_autoexcluido)->first();
      }
      else {
        $bdImp = new ImportacionAE;
        $bdImp->id_autoexcluido = $autoexcluido->id_autoexcluido;
      }

      //consigo el path del directorio raiz del proyecto
      $pathCons = realpath('../') . '/public/importacionesAutoexcluidos/';

      //fecha actual, sin formatear
      $ahora = date("dmY");

      $carpeta = [ 'foto1' => 'fotos/', 'foto2' => 'fotos/', 'scandni' => 'documentos/', 'solicitud_ae' => 'solicitudes/'];
      $numero_identificador = [ 'foto1' => '1', 'foto2' => '2', 'scandni' => '3', 'solicitud_ae' => '4'];
      foreach($carpeta as $tipo => $ignorar){
        if(is_null($importacion) || !array_key_exists($tipo,$importacion)){//Si no viene en el request, lo borro
          $bdImp->{$tipo} = NULL;
        }
      }
      if(!is_null($importacion)) foreach($importacion as $tipo => $file){
        //No esta en el arreglo de arriba, ignoro
        if(!array_key_exists($tipo,$carpeta) || !array_key_exists($tipo,$numero_identificador)) continue;
        if(is_null($file)){//Si viene en el request y es nulo, lo considero que es el mismo archivo
          continue;
        }
        $barra = strpos($file->getMimeType(),'/');
        $extension = substr($file->getMimeType(),$barra+1);
        $nombre_archivo = $ahora . '-' . $autoexcluido->nro_dni . '-' . $numero_identificador[$tipo] . '.' . $extension;
        $path = $pathCons . $carpeta[$tipo] . $nombre_archivo;
        copy($file->getRealPath(),$path);
        $bdImp->{$tipo} = $nombre_archivo;
      }
      $bdImp->save();
    }

    public function subirSolicitudAE(Request $request) {
      //no me anda el nullable en el validator, armo el array que le paso al validator a pata
      $arr = array();
      if ($request->solicitudAE != 'undefined') {$arr['solicitudAE'] = $request->solicitudAE;}

      Validator::make($arr, [
          'solicitudAE' => 'file|mimes:jpg,jpeg,png,pdf', //@TODO: quitar el mimes png despues, lo puse para probar nomas
        ], array(), self::$atributos)->after(function($validator){
        })->validate();

      //consigo el path del directorio raiz del proyecto
      $pathAbs = realpath('../');

      //fecha actual, sin formatear
      $ahora = date("dmY");

      $nombre_archivo = $ahora . '-' . $request->nro_dni . '-4';
      $pathDestino = $pathAbs . '/public/importacionesAutoexcluidos/solicitudes/' . $nombre_archivo;
      copy($request->solicitudAE->getRealPath(), $pathDestino);

      $id_ae = (DB::table('ae_datos')->where('ae_datos.nro_dni','=',$request->nro_dni)->first())->id_autoexcluido;
      $imp = ImportacionAE::where('ae_importacion.id_autoexcluido','=',$id_ae)->first();

      //guardo el nombre del archivo en la bdd
      if (isset($arr['solicitudAE'])) {$imp->solicitud_ae = $nombre_archivo;}
      $imp->save();

      return ['codigo' => 200];
    }

    //Función para obtener los datos de un autoexcluido a partir de un DNI
    public function existeAutoexcluido($dni){
      $autoexcluido = DB::table('ae_datos')->where('ae_datos.nro_dni','=',$dni)->first();

      if ($autoexcluido != null) {
        $datos_contacto = DB::table('ae_datos_contacto')->where('id_autoexcluido','=',$autoexcluido->id_autoexcluido)->first();
        $estado = DB::table('ae_estado')->where('id_autoexcluido','=',$autoexcluido->id_autoexcluido)->first();
        $encuesta = DB::table('ae_encuesta')->where('id_autoexcluido','=',$autoexcluido->id_autoexcluido)->first();
        $importacion  = DB::table('ae_importacion')->where('id_autoexcluido','=',$autoexcluido->id_autoexcluido)->first();

        $resultados = array(
          'autoexcluido' => $autoexcluido,
          'datos_contacto'=> $datos_contacto,
          'estado' => $estado,
          'encuesta' => $encuesta,
          'importacion' => $importacion
        );
      }
      else {
        $resultados = -1;
      }

      return $resultados;
    }


  public function buscarAutoexcluido ($id) {
    $autoexcluido = Autoexcluido::find($id);
    $datos_contacto = ContactoAE::where('id_autoexcluido', '=', $id)->first();
    $estado = EstadoAE::where('id_autoexcluido', '=', $id)->first();
    $encuesta = Encuesta::where('id_autoexcluido', '=', $id)->first();
    $importacion = ImportacionAE::where('id_autoexcluido', '=', $id)->first();

    return ['autoexcluido' => $autoexcluido,
            'datos_contacto' => $datos_contacto,
            'estado' => $estado,
            'encuesta' => $encuesta,
            'importacion' => $importacion
          ];
  }

  public function mostrarArchivo ($id_importacion,$tipo_archivo) {
    $imp = ImportacionAE::where('id_importacion', '=', $id_importacion)->first();
    $pathCons = realpath('../') . '/public/importacionesAutoexcluidos/';

    $paths = [
      'foto1' => 'fotos', 'foto2' => 'fotos', 'scandni' => 'documentos', 'solicitud_ae' => 'solicitudes', 'solicitud_revocacion' => 'solicitudes'
    ];

    $path = $pathCons;
    if(array_key_exists($tipo_archivo,$paths)){
      $path = $path . $paths[$tipo_archivo] . '/' . $imp->{$tipo_archivo};
    }
    if($pathCons != $path && file_exists($path)) return response()->file($path);
    return "Archivo no encontrado o inaccesible";
  }

  public function mostrarFormulario ($id_formulario) {
    $pathForms = realpath('../') . '/public/importacionesAutoexcluidos/formularios/';

    if ($id_formulario == 1) {
      $path = $pathForms . 'Carátula AU 1°.pdf';
    }
    else if ($id_formulario == 2) {
      $path = $pathForms . 'Carátula AU 2°.pdf';
    }
    else if ($id_formulario == 3) {
      $path = $pathForms . 'Formulario AU 1°.pdf';
    }
    else if ($id_formulario == 4) {
      $path = $pathForms . 'Formulario AU 2°.pdf';
    }
    else if ($id_formulario == 5) {
      $path = $pathForms . 'Formulario Finalización AU.pdf';
    }
    else {
      $path = $pathForms . 'RVE N° 983.pdf';
    }

    return response()->file($path);
  }

  public function generarSolicitudAutoexclusion($id){
    $autoexcluido = Autoexcluido::find($id);
    $enc = Encuesta::where('id_autoexcluido', '=', $autoexcluido['id_autoexcluido'])->first();
    $estado = EstadoAE::where('id_autoexcluido', '=', $autoexcluido['id_autoexcluido'])->first();
    $contacto = ContactoAE::where('id_autoexcluido', '=', $autoexcluido['id_autoexcluido'])->first();

    $datos_estado = array(
      'fecha_vencimiento' => date("d-m-Y", strtotime($estado->fecha_vencimiento)),
      'fecha_cierre' => date("d-m-Y", strtotime($estado->fecha_cierre_ae))
    );

    if ($enc != null) {
      $encuesta = $enc;
    }
    else {
      $encuesta = array(
        'id_frecuencia_asistencia' => -1,
        'veces' => -1,
        'tiempo_jugado' => -1,
        'como_asiste' => -1,
        'id_juego_preferido' => -1,
        'club_jugadores' => -1,
        'autocontrol_juego' => -1,
        'recibir_informacion' => -1,
        'medio_recibir_informacion' =>-1
      );
    }

    $view = View::make('Autoexclusion.planillaFormularioAE1', compact('autoexcluido', 'encuesta', 'datos_estado', 'contacto'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 820, "Dirección General de Casinos y Bingos / Caja de Asistencia Social - Lotería de Santa Fe", $font, 8, array(0,0,0));
    $dompdf->getCanvas()->page_text(525, 820, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 8, array(0,0,0));

    return $dompdf->stream("solicitud_autoexclusion_" . date('Y-m-d') . ".pdf", Array('Attachment'=>0));
  }

  public function generarConstanciaReingreso($id){
    $autoexcluido = Autoexcluido::find($id);
    $estado = EstadoAE::where('id_autoexcluido', '=', $autoexcluido['id_autoexcluido'])->first();

    $datos = array(
      'apellido_y_nombre' => $autoexcluido->apellido . ', ' . $autoexcluido->nombres,
      'dni' => $autoexcluido->nro_dni,
      'domicilio_completo' => $autoexcluido->domicilio . ' ' . $autoexcluido->nro_domicilio,
      'localidad' => ucwords(strtolower($autoexcluido->nombre_localidad)),
      'fecha_cierre_definitivo' => date('d-m-Y', strtotime($estado->fecha_cierre_ae))
    );

    $view = View::make('Autoexclusion.planillaConstanciaReingreso', compact('datos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 820, "Dirección General de Casinos y Bingos / Caja de Asistencia Social - Lotería de Santa Fe", $font, 8, array(0,0,0));
    $dompdf->getCanvas()->page_text(525, 820, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 8, array(0,0,0));

    return $dompdf->stream("constancia_reingreso_" . date('Y-m-d') . ".pdf", Array('Attachment'=>0));
  }

  public function generarSolicitudFinalizacionAutoexclusion($id){
    $autoexcluido = Autoexcluido::find($id);
    $estado = EstadoAE::where('id_autoexcluido', '=', $autoexcluido['id_autoexcluido'])->first();

    $datos_estado = array(
      'fecha_vencimiento' => date("d-m-Y", strtotime($estado->fecha_vencimiento)),
      'fecha_cierre' => date("d-m-Y", strtotime($estado->fecha_cierre_ae))
    );

    $view = View::make('Autoexclusion.planillaFormularioFinalizacionAE', compact('autoexcluido', 'datos_estado'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 820, "Dirección General de Casinos y Bingos / Caja de Asistencia Social - Lotería de Santa Fe", $font, 8, array(0,0,0));
    $dompdf->getCanvas()->page_text(525, 820, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 8, array(0,0,0));

    return $dompdf->stream("solicitud_finalizacion_autoexclusion_" . date('Y-m-d') . ".pdf", Array('Attachment'=>0));
  }
}
