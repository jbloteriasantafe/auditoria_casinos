<?php

namespace App\Http\Controllers\Autoexclusion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Dompdf\Dompdf;
use View;
use Validator;
use PDF;

use App\Casino;
use App\Plataforma;
use App\Autoexclusion as AE;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AutoexclusionController extends Controller
{
    private static $atributos = [];

    private static $instance;
    public static function getInstancia($actualizar = true){
      if (!isset(self::$instance)){
          self::$instance = new AutoexclusionController($actualizar);
      }
      return self::$instance;
    }

    public function __construct($actualizar = true){//Actualizar estados antes de cada request
      if($actualizar) $this->actualizarVencidosRenovados();
    }

    public function index($dni = ''){
      UsuarioController::getInstancia()->agregarSeccionReciente('Autoexclusión' , 'autoexclusion');
      $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
      $estados_autoexclusion = AE\NombreEstadoAutoexclusion::all();
      $estados_elegibles = AE\NombreEstadoAutoexclusion::where('deprecado',0)->get();

      if(!$usuario->tienePermiso('modificar_ae') && !$usuario->tienePermiso('aym_ae_plataformas'))
        $estados_elegibles = AE\NombreEstadoAutoexclusion::where('id_nombre_estado',3)->get();
      
      return view('Autoexclusion.index', ['juegos' => AE\JuegoPreferidoAE::all(),
                                          'ocupaciones' => AE\OcupacionAE::all(),
                                          'casinos' => Casino::all(),
                                          'plataformas' => Plataforma::all(),
                                          'usuario' => $usuario,
                                          'frecuencias' => AE\FrecuenciaAsistenciaAE::all(),
                                          'estados_autoexclusion' => $estados_autoexclusion,
                                          'estados_elegibles' => $estados_elegibles,
                                          'estados_civiles' => AE\EstadoCivilAE::all(),
                                          'capacitaciones' => AE\CapacitacionAE::all(),
                                          'dni' => $dni
                                        ]);
    }

    public function indexNoticias($dni = ''){
      UsuarioController::getInstancia()->agregarSeccionReciente('Autoexclusión' , 'autoexclusion');
      $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];

      if(!$usuario->tienePermiso('modificar_ae') && !$usuario->tienePermiso('aym_ae_plataformas'))
        $estados_elegibles = AE\NombreEstadoAutoexclusion::where('id_nombre_estado',3)->get();
      
      return view('Autoexclusion.indexNoticias', [
                                          'usuario' => $usuario,
                                          'dni' => $dni
                                        ]);
    }

  //Función para buscar los autoexcluidos existentes en el sistema
  public function buscarAutoexcluidos(Request $request){
    $reglas = Array();
    $filters = [
      'nombres' => 'ae_datos.nombres',
      'apellido' => 'ae_datos.apellido',
      'correo' => 'ae_datos.correo',
      'dni' => 'ae_datos.nro_dni',
      'estado' => 'ae_estado.id_nombre_estado',
      'casino' => 'ae_estado.id_casino',
      'plataforma' => 'ae_estado.id_plataforma',
      'fecha_autoexclusion_d' => 'ae_estado.fecha_ae',
      'fecha_autoexclusion_h' => 'ae_estado.fecha_ae',
      'fecha_vencimiento_d' => 'ae_estado.fecha_vencimiento',
      'fecha_vencimiento_h' => 'ae_estado.fecha_vencimiento',
      'fecha_renovacion_d' => 'ae_estado.fecha_renovacion',
      'fecha_renovacion_h' => 'ae_estado.fecha_renovacion',
      'fecha_cierre_definitivo_d' => 'ae_estado.fecha_cierre_ae',
      'fecha_cierre_definitivo_h' => 'ae_estado.fecha_cierre_ae',
    ]; 
    foreach($filters as $key => $column){ //echo asi por las dudas si necesito agregar mas tipos de filtros y evitar tanto los if agrupando diferentes tipos de filtros en uno
      if (!empty($request->$key)) {
        switch($key){
          case 'nombres':
          case 'apellido':
          case 'correo':
            // si es nombre , apellido o correo
            $reglas[] = [$column, 'LIKE','%' . $request->$key . '%'];
            break;
          case 'dni':
          case 'estado':
          case 'plataforma':
          case 'casino':
            // si es dni estado plataforma o casino
            $reglas[] = [$column, '=', $request->$key];
            break;
          case 'fecha_autoexclusion_d':
          case 'fecha_vencimiento_d':
          case 'fecha_renovacion_d':
          case 'fecha_cierre_definitivo_d':
            // si es fecha _ bla bla _ d 
            $reglas[] = [$column, '>=', $request->$key];
            break;
          case 'fecha_autoexclusion_h':
          case 'fecha_vencimiento_h':
          case 'fecha_renovacion_h':
          case 'fecha_cierre_definitivo_h':
            // si es fecha_blabla_h
            $reglas[] = [$column, '<=', $request->$key];
            break;
          case 'papel_destruido':
            $reglas[]=[DB::raw('ae_estado.id_plataforma IS NULL'),'=','1'];
            $nulos = $request->papel_destruido == 'SI'? '0' : '1';
            $reglas[]=[DB::raw('ae_estado.papel_destruido_id_usuario IS NULL'),'=',$nulos];
            $reglas[]=[DB::raw('ae_estado.papel_destruido_datetime IS NULL'),'=',$nulos];
            $break;
        }
      }
    }
    $sort_by = ['columna' => 'ae_datos.id_autoexcluido', 'orden' => 'desc'];
    if(!empty($request->sort_by)){
      $sort_by = $request->sort_by;
    }
    
    $resultados = DB::table('ae_datos')
      ->selectRaw('ae_datos.id_autoexcluido, ae_datos.nro_dni, ae_datos.apellido, ae_datos.nombres,
                   ae_estado.fecha_ae, ae_estado.fecha_renovacion, ae_estado.fecha_vencimiento, ae_estado.fecha_cierre_ae,
                   ae_estado.id_nombre_estado,ae_estado.id_casino,ae_estado.id_plataforma,
                   
                   ae_estado.id_usuario as modificado_id_usuario,
                   IF(ae_estado.id_usuario IS NULL,NULL,(SELECT u2.nombre from usuario as u2 where u2.id_usuario = ae_estado.id_usuario)) as modificado_nombre_usuario,
                   COALESCE(ae_estado.updated_at,ae_estado.created_at) as modificado_datetime,
                   
                   ae_estado.papel_destruido_id_usuario,
                   IF(ae_estado.papel_destruido_id_usuario IS NULL,NULL,(SELECT u2.nombre from usuario as u2 where u2.id_usuario = ae_estado.papel_destruido_id_usuario)) as papel_destruido_nombre_usuario,
                   ae_estado.papel_destruido_datetime,
                   
                   ae_nombre_estado.descripcion as desc_estado,
                   IFNULL(casino.nombre,plataforma.nombre) as casino_plataforma,
                   ae_importacion.foto1,ae_importacion.foto2,ae_importacion.scandni,
                   ae_importacion.solicitud_ae,ae_importacion.solicitud_revocacion,ae_importacion.caratula')
      ->join('ae_estado'         , 'ae_datos.id_autoexcluido' , '=' , 'ae_estado.id_autoexcluido')
      ->leftJoin('ae_importacion', 'ae_importacion.id_autoexcluido','=','ae_datos.id_autoexcluido')
      ->join('ae_nombre_estado', 'ae_nombre_estado.id_nombre_estado', '=', 'ae_estado.id_nombre_estado')
      ->leftjoin('casino','ae_estado.id_casino','=','casino.id_casino')
      ->leftjoin('plataforma','ae_estado.id_plataforma','=','plataforma.id_plataforma')
      ->whereNull('ae_datos.deleted_at')->whereNull('ae_estado.deleted_at')
      ->when($sort_by,function($query) use ($sort_by){
        return $query->orderBy($sort_by['columna'],$sort_by['orden']);
      })
      ->where($reglas)
      ->paginate($request->page_size);

    //Agrego algunos atributos dinamicos utiles para el frontend
    $resultados->getCollection()->transform(function ($row){
      $ae = AE\Autoexcluido::find($row->id_autoexcluido);
      $row->es_primer_ae = $ae->es_primer_ae;
      $row->estado_transicionable = $ae->estado_transicionable;
      return $row;
    });
    return $resultados;
  }

  //Función para agregar un nuevo autoexcluido complet, o editar uno existente
  //@TODO: Agregar poder enviar la fecha de revocacion si se elige Fin. Por AE en el estado
  //@TODO: Verificar conflico de fechas segun la fecha_ae, como esta ahora asume que fecha_ae es siempre la ultima AE
  public function agregarAE(Request $request){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    Validator::make($request->all(), [
      'ae_datos.id_autoexcluido'  => 'nullable|integer|exists:ae_datos,id_autoexcluido',
      'ae_datos.nro_dni'          => 'required|integer',
      'ae_datos.apellido'         => 'required|string|max:100',
      'ae_datos.nombres'          => 'required|string|max:150',
      'ae_datos.fecha_nacimiento' => 'required|date',
      'ae_datos.id_sexo'          => 'required|integer|exists:ae_sexo,id_sexo',
      'ae_datos.domicilio'        => 'required|string|max:100',
      'ae_datos.nro_domicilio'    => 'required|string|max:11',
      'ae_datos.piso'             => 'nullable|string|max:5',
      'ae_datos.dpto'             => 'nullable|string|max:5',
      'ae_datos.codigo_postal'    => 'nullable|string|max:10',
      'ae_datos.nombre_localidad' => 'required|string|max:200',
      'ae_datos.nombre_provincia' => 'required|string|max:200',
      'ae_datos.telefono'         => 'required|string|max:200',
      'ae_datos.correo'           => 'nullable|string|max:100',
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
      'ae_estado.id_casino'         => 'nullable|integer|exists:casino,id_casino',
      'ae_estado.id_plataforma'     => 'nullable|integer|exists:plataforma,id_plataforma',
      'ae_estado.fecha_ae'          => 'required|date',
      'hace_encuesta'                         => 'required|boolean',
      'ae_encuesta.id_juego_preferido'        => 'nullable|integer|exists:ae_juego_preferido,id_juego_preferido',
      'ae_encuesta.id_frecuencia_asistencia'  => 'nullable|integer|exists:ae_frecuencia_asistencia,id_frecuencia',
      'ae_encuesta.veces'                     => 'nullable|integer',
      'ae_encuesta.tiempo_jugado'             => 'nullable|integer',
      'ae_encuesta.club_jugadores'            => 'nullable|string|max:2',
      'ae_encuesta.conoce_plataformas'        => 'nullable|string|max:2',
      'ae_encuesta.utiliza_plataformas'       => 'nullable|string|max:2',
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
      'ae_importacion.caratula'              => 'nullable|file|mimes:jpg,jpeg,png,pdf',
    ], array(), self::$atributos)->after(function($validator) use ($user){
      $data = $validator->getData();
      $id_casino = $data['ae_estado']['id_casino'];
      $id_plataforma = $data['ae_estado']['id_plataforma'];
      $estado = $data['ae_estado']['id_nombre_estado'];
      if(!$user->es_superusuario){
        if(!is_null($id_casino) && !$user->usuarioTieneCasino($id_casino)){
          $validator->errors()->add('ae_estado.id_casino', 'No tiene acceso a ese casino');
        }
        else if(!is_null($id_plataforma) && !$user->tienePermiso('aym_ae_plataformas')){
          $validator->errors()->add('ae_estado.id_casino', 'No tiene acceso a esa plataforma');
        }
        else if(is_null($id_casino) == is_null($id_plataforma)){
          $validator->errors()->add('ae_estado.id_casino','Error al procesar el casino.');
        }
      }
      if(!is_numeric($data['ae_datos']['nro_domicilio'])){
        $validator->errors()->add('ae_datos.nro_domicilio','El valor no es numérico');
      }
      if($user->es_fiscalizador && $estado != 3){
        $validator->errors()->add('ae_estado.id_nombre_estado', 'No puede agregar autoexcluidos con ese estado');
      }
      if($user->es_fiscalizador && !$data['hace_encuesta']){
        $validator->errors()->add('hace_encuesta', 'La encuesta no es opcional para los fiscalizadores');
      }

      $id_ae = $data['ae_datos']['id_autoexcluido'];
      $todos_vencidos = true;
      {
        $aes = AE\Autoexcluido::where('nro_dni','=', $data['ae_datos']['nro_dni'])->get();
        foreach($aes as $ae){
          $e = $ae->estado;
          $vencido = $e->id_nombre_estado == 4 || $e->id_nombre_estado == 5 || $ae->estado_transicionable == 5;
          $todos_vencidos = $vencido && $todos_vencidos;
          if(!$todos_vencidos) break;
        }
      }
      //Si es para crear uno nuevo y ya hay uno vigente, error
      if(is_null($id_ae) && !$todos_vencidos){
        $validator->errors()->add('ae_datos.nro_dni', 'Ya existe un autoexcluido vigente con ese DNI.');
      }
    })->validate();
    
    $esNuevo = false;
    DB::transaction(function() use($request, &$esNuevo, $user){
      $ae_datos = $request['ae_datos'];
      $ae = null;
      if(is_null($ae_datos['id_autoexcluido'])){
        $ae = new AE\Autoexcluido;
        $esNuevo = true;
      }
      else{
        $ae = AE\Autoexcluido::find($ae_datos['id_autoexcluido']);
        $esNuevo = false;
      }

      foreach($ae_datos as $key => $val){
        $ae->{$key} = $val;
      }
      
      $ae->save();
      
      $contacto = $ae->contacto;
      if(is_null($contacto)){
        $contacto = new AE\ContactoAE;
        $contacto->id_autoexcluido = $ae->id_autoexcluido;
      }
      
      $ae_datos_contacto = $request['ae_datos_contacto'];
      foreach($ae_datos_contacto as $key => $val){
        $contacto->{$key} = $val;
      }
      $contacto->save();
      
      $estado = $request['ae_estado'];
      $estado['id_usuario'] = $user->id_usuario;
      $this->setearEstado($ae,$estado);

      $encuesta = $ae->encuesta;
      if($request['hace_encuesta']){
        if(is_null($encuesta)){
          $encuesta = new AE\Encuesta;
          $encuesta->id_autoexcluido = $ae->id_autoexcluido;
        }
        $ae_encuesta = $request['ae_encuesta'];
        foreach($ae_encuesta as $key => $val){
          $encuesta->{$key} = $val;
        }
        $encuesta->save();
      }
      else{
        if(!is_null($encuesta)){
          $encuesta->delete();
        }
      }
      $this->subirImportacionArchivos($ae,$request['ae_importacion']);
    });
    return ['nuevo' => $esNuevo];
  }

  public function generarFechas($fecha_ae){//Usado en APIAEController
    $date_ae = date_create_from_format('Y-m-d',$fecha_ae);
    $ret = new \stdClass();
    // @BUG? (definir bien los limites): Si quiero ser compatible
    // con el el frontend tengo que ponerle 1 dia menos
    $ret->fecha_renovacion    = (clone $date_ae)->modify('+149 day')->format('Y-m-d');
    $ret->fecha_vencimiento   = (clone $date_ae)->modify('+179 day')->format('Y-m-d');
    $ret->fecha_cierre_ae     = (clone $date_ae)->modify('+364 day')->format('Y-m-d');
    return $ret;
  }

  public function setearEstado($ae,$ae_estado){//Usado en APIAEController
    $estado = $ae->estado;
    if(is_null($estado)){
      $estado = new AE\EstadoAE;
      $estado->id_autoexcluido = $ae->id_autoexcluido;
    }

    //@IMPORTANTE: que cuando se finalize este seteado en algo la fecha de revocacion, para en un futuro saber si finalizo
    if($ae_estado['id_nombre_estado'] == 4){//Fin por AE
      if(!empty($estado->fecha_revocacion_ae)){//Si ya tenia una fecha_revocacion_ae, le dejo esa
        $ae_estado['fecha_revocacion_ae'] = $estado->fecha_revocacion_ae;
      }
      else if(empty($ae_estado['fecha_revocacion_ae'])){//Le pongo la de hoy si no me mando una  
        $ae_estado['fecha_revocacion_ae'] = date('Y-m-d');
      }
    }
    //Si lo modificaron a vencido o estaba vencido y lo corrigieron (ej el apellido, la dirección, etc), mantenerle la fecha de revocacion
    //@IMPORTANTE: Si se le quiere sacar la fecha_revocacion_ae a un vencido, ponerlo primero en vigente u otro estado
    else if($ae_estado['id_nombre_estado'] == 5){//Vencido
      $ae_estado['fecha_revocacion_ae'] = $estado->fecha_revocacion_ae;
    }
    //En cualquier otro caso, se le limpia la fecha de revocación
    else{
      $ae_estado['fecha_revocacion_ae'] = null;
    }

    foreach($ae_estado as $key => $val){//Traspasa todos los valores enviados al objeto
      $estado->{$key} = $val;
    }

    $fs = $this->generarFechas($estado->fecha_ae);
    $estado->fecha_renovacion  = $fs->fecha_renovacion;
    $estado->fecha_vencimiento = $fs->fecha_vencimiento;
    $estado->fecha_cierre_ae   = $fs->fecha_cierre_ae;
    $estado->ultima_actualizacion_estado = null;
    $estado->save();
  }

  public function subirImportacionArchivos($ae,$ae_importacion) {//Usado en APIAEController
    $importacion = $ae->importacion;
    if (is_null($importacion)){
      $importacion = new AE\ImportacionAE;
      $importacion->id_autoexcluido = $ae->id_autoexcluido;
    }

    //consigo el path del directorio raiz del proyecto
    $pathCons = realpath('../') . '/public/importacionesAutoexcluidos/';

    //fecha actual, sin formatear
    $ahora = date("dmY");

    $carpeta = ['foto1' => 'fotos/', 
                'foto2' => 'fotos/',
                'scandni' => 'documentos/',
                'solicitud_ae' => 'solicitudes/',
                'solicitud_revocacion' => 'solicitudes/',
                'caratula' => 'solicitudes/'
              ];
    $numero_identificador = ['foto1' => '1',
                              'foto2' => '2',
                              'scandni' => '3', 
                              'solicitud_ae' => '4',
                              'solicitud_revocacion' => '5',
                              'caratula' => 'CAR'];
    
    foreach($carpeta as $tipo => $ignorar){
      if(is_null($ae_importacion) || !array_key_exists($tipo,$ae_importacion)){//Si no viene en el request, lo borro
        $importacion->{$tipo} = NULL;
      }
    }
    if(!is_null($ae_importacion)) foreach($ae_importacion as $tipo => $file){
      //No esta en el arreglo de arriba, ignoro
      if(!array_key_exists($tipo,$carpeta) || !array_key_exists($tipo,$numero_identificador)) continue;
      if(is_null($file)){//Si viene en el request y es nulo, lo considero que es el mismo archivo
        continue;
      }
      $barra = strpos($file->getMimeType(),'/');
      $extension = substr($file->getMimeType(),$barra+1);
      $nombre_archivo = $ahora . '-' . $ae->nro_dni . '-' . $numero_identificador[$tipo] . '.' . $extension;
      $path = $pathCons . $carpeta[$tipo] . $nombre_archivo;
      copy($file->getRealPath(),$path);
      $importacion->{$tipo} = $nombre_archivo;
    }
    $importacion->save();
  }

  public function subirArchivo(Request $request) {
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    Validator::make($request->all(), [
        'id_autoexcluido' => 'required|integer|exists:ae_datos,id_autoexcluido',
        'tipo_archivo' => ['required','string',Rule::in(['foto1','foto2','scandni','solicitud_ae','solicitud_revocacion','caratula'])],
        'archivo' => 'required|file|mimes:jpg,jpeg,png,pdf',
      ], array(), self::$atributos)->after(function($validator) use ($user){
        $id = $validator->getData()['id_autoexcluido'];
        $id_casino = AE\Autoexcluido::find($id)->estado->id_casino;
        $id_plataforma = AE\Autoexcluido::find($id)->estado->id_plataforma;
        if(!$user->es_superusuario){
          if(!is_null($id_casino) && !$user->usuarioTieneCasino($id_casino)){
            $validator->errors()->add('ae_estado.id_casino', 'No tiene acceso a ese casino');
            return;
          }
          if(!is_null($id_plataforma) && !$user->tienePermiso('aym_ae_plataformas')){
            $validator->errors()->add('ae_estado.id_plataforma', 'No tiene acceso a esa plataforma');
            return;
          }
        }
    })->validate();

    DB::transaction(function() use ($request){
      $ae_importacion = [
        'foto1' => null,//Necesito mandar nulo para que el metodo no lo borre de la BD
        'foto2' => null,
        'scandni' => null,
        'solicitud_ae' => null,
        'solicitud_revocacion' => null,
        'caratula' => null,
      ];
      $ae_importacion[$request->tipo_archivo] = $request->archivo;
      $this->subirImportacionArchivos(AE\Autoexcluido::find($request->id_autoexcluido),$ae_importacion);
    });
    return ['codigo' => 200];
  }

  public function existeAutoexcluido($dni){//Usado en APIAEController
    $aes = AE\Autoexcluido::where('nro_dni',$dni)->get();
    $todos_vencidos = true;
    foreach($aes as $ae){
      $e = $ae->estado;
      $vencido = $e->id_nombre_estado == 5 || $ae->estado_transicionable == 5;
      $todos_vencidos = $vencido && $todos_vencidos;
      if(!$todos_vencidos) break;
    }
    //Si estan todos los anteriores finalizados (o no hay), dejo crear uno nuevo.
    if($todos_vencidos){
      //Si ya estuvo AE retorno -1 sino 0
      if(count($aes) > 0) return -1;
      else return 0;
    }

    //Si llegue aca es porque hay uno en vigencia, lo devuelvo para mostrarlo
    $ae = AE\Autoexcluido::where('nro_dni',$dni)
    ->join('ae_estado','ae_estado.id_autoexcluido','=','ae_datos.id_autoexcluido')
    ->orderBy('ae_estado.fecha_ae','desc')->first();
    return $ae->id_autoexcluido;
  }

  public function buscarAutoexcluido ($id) {
    try {
        $ae = AE\Autoexcluido::find($id);
        if (!$ae) return ['error' => 'No encontrado'];
        return ['autoexcluido' => $ae,
                'es_primer_ae' => $ae->es_primer_ae,
                'datos_contacto' => $ae->contacto,
                'estado' => $ae->estado,
                'encuesta' => $ae->encuesta,
                'importacion' => $ae->importacion];
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
    }
  }

  public function mostrarArchivo ($id_importacion,$tipo_archivo) {
    $imp = AE\ImportacionAE::where('id_importacion', '=', $id_importacion)->first();
    $pathCons = realpath('../') . '/public/importacionesAutoexcluidos/';
    if($id_importacion == 0 && $tipo_archivo == 'sin_foto') return response()->file(realpath('../') . '/public/img/img_user.jpg');
    $paths = [
      'foto1' => 'fotos', 'foto2' => 'fotos', 'scandni' => 'documentos', 'solicitud_ae' => 'solicitudes', 'solicitud_revocacion' => 'solicitudes',
      'caratula' => 'solicitudes'
    ];

    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    // Agrege en paths 'scandni' ya que ahora se guardan los documentos.
    if($user->es_casino_ae){
      $paths = [
        'foto1' => 'fotos', 'foto2' => 'fotos', 'scandni' => 'documentos'
      ];
    }

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
    else if ($id_formulario == 6){
      $path = $pathForms . 'RVE N° 983.pdf';
    }
    else return "Formulario invalido";
    if(!file_exists($path)) return "Formulario no cargado en el sistema, informar al administrador";
    return response()->file($path);
  }

  public function generarSolicitudAutoexclusion($id){
    $autoexcluido = AE\Autoexcluido::find($id);
    $estado = $autoexcluido->estado;
    $datos_estado = array(
      'fecha_ae' => date("d/m/Y",strtotime($estado->fecha_ae)),
      'fecha_vencimiento' => date("d/m/Y", strtotime($estado->fecha_vencimiento)),
      'fecha_cierre' => date("d/m/Y", strtotime($estado->fecha_cierre_ae))
    );
    $encuesta = $autoexcluido->encuesta;
    $es_primer_ae = $autoexcluido->es_primer_ae;
    if (is_null($encuesta)) {
      $encuesta = array(
        'id_frecuencia_asistencia' => -1,
        'veces' => -1,
        'tiempo_jugado' => -1,
        'como_asiste' => -1,
        'id_juego_preferido' => -1,
        'club_jugadores' => -1,
        'conoce_plataformas' => -1,
        'utiliza_plataformas' => -1,
        'autocontrol_juego' => -1,
        'recibir_informacion' => -1,
        'medio_recibir_informacion' => -1,
        'observacion' => ''
      );
    }
    $contacto = $autoexcluido->contacto;
    $view = View::make('Autoexclusion.planillaFormularioAE1', compact('autoexcluido', 'encuesta', 'datos_estado', 'contacto','es_primer_ae'));
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
    $ae = AE\Autoexcluido::find($id);
    $datos = array(
      'apellido_y_nombre' => $ae->apellido . ', ' . $ae->nombres,
      'dni' => $ae->nro_dni,
      'domicilio_completo' => $ae->domicilio . ' ' . $ae->nro_domicilio,
      'localidad' => ucwords(strtolower($ae->nombre_localidad)),
      'fecha_cierre_definitivo' => date('d/m/Y', strtotime($ae->estado->fecha_cierre_ae))
    );

    //Si revoco, le permitimos entrar a partir de la fecha del vencimiento
    if(!is_null($ae->estado->fecha_revocacion_ae)){
      $datos['fecha_cierre_definitivo'] = date('d/m/Y',strtotime($ae->estado->fecha_vencimiento));
    }

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
    $ae = AE\Autoexcluido::find($id);
    $estado = $ae->estado;
    $datos = array(
      'fecha_revocacion_ae' => date("d/m/Y", strtotime($estado->fecha_revocacion_ae)),
      'fecha_vencimiento'   => date("d/m/Y", strtotime($estado->fecha_vencimiento))
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

  public function cambiarEstadoAE($id,$id_estado){//Usado en APIAEController
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $ae = AE\Autoexcluido::find($id);

    if(is_null($ae)) return $this->errorOut(['id_autoexcluido' => 'AE inexistente']);
    
    $estado = $ae->estado;

    $usuario_valido = $usuario->tienePermiso('modificar_ae') || $usuario->tienePermiso('aym_ae_plataformas');
    if(!$usuario_valido
      || (!is_null($estado->id_casino) && !$usuario->usuarioTieneCasino($estado->id_casino))
      || (!is_null($estado->id_plataforma) && !$usuario->tienePermiso('aym_ae_plataformas'))
    ){
      return $this->errorOut(['rol' => 'No puede realizar esa acción']);
    }

    if($ae->estado_transicionable != $id_estado){
      return $this->errorOut(['id_autoexcluido' => 'No puede cambiar a ese estado']);
    }

    if($estado->id_nombre_estado == $id_estado){
      return $this->errorOut(['id_autoexcluido' => 'Ya se encuentra en ese estado']);
    }

    if($id_estado == 4){//Si es fin por AE guardo la fecha que lo pidio revocar.
      $estado->fecha_revocacion_ae = date('Y-m-d');
    }
    $estado->id_nombre_estado = $id_estado;
    $estado->ultima_actualizacion_estado = null;
    $estado->save();
    return 1;
  }

  //Esta funcion se fija sobre todos los AE los que estan para cambiar a vencido/renovado y lo hace
  //Hay que insertarla antes de cada busqueda para obtener lo mas actualizado.
  public function actualizarVencidosRenovados(){//Usado en APIAE,GaleriaImagenes e InformesAE
    //solo es necesaria 1 actualizacion por dia, se filtran todos los que no fueron actualizados hoy
    DB::transaction(function (){
      $vigentes = AE\EstadoAE::whereIn('id_nombre_estado',[1,7])->where(function($q){
        return $q->whereNull('ultima_actualizacion_estado')->orWhere('ultima_actualizacion_estado','<',DB::raw('CURRENT_DATE()'));
      })->get();
      foreach($vigentes as $v){//Vigentes que pasan a renovados por 1er AE o que vencieron
        $ae = $v->ae;
        $nuevo_estado = $ae->estado_transicionable;
        $v->ultima_actualizacion_estado = date('Y-m-d');
        //Aca en principio podria asignarlo derecho pero por las dudas chequeo de vuelta, por si esta mal el codigo de getEstadoTransicionable
        if($nuevo_estado == 2){//Renovado
          $v->id_nombre_estado = 2;
        }
        if($nuevo_estado == 5){//Vencido
          $v->id_nombre_estado = 5;
        }
        $v->save();        
      }
    });
    DB::transaction(function (){//Renovados que vencieron a los 12 meses o finalizados que vencieron a los 6 meses
      $renovados_y_finalizados = AE\EstadoAE::whereIn('id_nombre_estado',[2,4])->where(function($q){
        return $q->whereNull('ultima_actualizacion_estado')->orWhere('ultima_actualizacion_estado','<',DB::raw('CURRENT_DATE()'));
      })->get();
      foreach($renovados_y_finalizados as $e){
        $ae = $e->ae;
        $nuevo_estado = $ae->estado_transicionable;
        $e->ultima_actualizacion_estado = date('Y-m-d');
        if($nuevo_estado == 5){//Vencido
          $e->id_nombre_estado = 5;
        }
        $e->save();
      }
    });
  }

  public function eliminarAE($id_autoexcluido){
    DB::transaction(function() use($id_autoexcluido){
      $ae = AE\Autoexcluido::find($id_autoexcluido);
      if(is_null($ae)) return;
      $fields = ['contacto', 'estado', 'importacion', 'encuesta'];
      foreach($fields as $field){
        if(!is_null($ae->$field)){
          $ae->$field->delete();
        }
      }
      $ae->delete();
    });
    return 1;
  }

  private function errorOut($map){
    return response()->json($map,422);
  }

  public function BDCSV(){
    $filename = 'ae_bd_'.date('Ymdhis').'.csv';

    if(!$fhandle = fopen($filename,'w')){
      return 'NO SE PUEDE CREAR EL ARCHIVO';
    }

    $primer_ae = true;
    $query = DB::table('ae_datos as ad')
    ->selectRaw("ae.fecha_ae, ae.fecha_renovacion, ae.fecha_vencimiento, ae.fecha_revocacion_ae, ae.fecha_cierre_ae,
    CASE 
        WHEN ane.id_nombre_estado = 6 THEN 'Pendiente de val.'
        WHEN ane.id_nombre_estado = 7 THEN 'Vigente'
        ELSE ane.descripcion
    END as estado,
    ad.nro_dni,ad.nombres,ad.apellido,ad.fecha_nacimiento,
    s.codigo as sexo,
    ad.telefono, ad.correo, ad.nombre_provincia, ad.nombre_localidad, ad.codigo_postal, ad.domicilio, ad.nro_domicilio")
    ->join('ae_sexo as s','s.id_sexo','=','ad.id_sexo')
    ->join('ae_estado as ae','ae.id_autoexcluido','=','ad.id_autoexcluido')
    ->join('ae_nombre_estado as ane','ane.id_nombre_estado','=','ae.id_nombre_estado')
    ->whereNull('ad.deleted_at')->whereNull('ae.deleted_at')->orderBy('ae.fecha_ae','desc')->chunk(1000,function($aes) use (&$fhandle,&$primer_ae){
      $aes2 = json_decode(json_encode($aes), true);
      foreach($aes2 as $ae){
        //$aux = json_decode(json_encode($ae), true);
        if($primer_ae){
          //Agrego los nombres de las columnas
          fputcsv($fhandle,array_keys($ae),";",'"',"\\");
          $primer_ae = false;
        }
        fputcsv($fhandle,array_values($ae),";",'"',"\\");
      }
    });
    fclose($fhandle);

    $headers = array(
      "Content-type" => "text/csv",
    );
    return response()->download($filename,$filename,$headers)->deleteFileAfterSend(true);
  }
    
    public function importarMasivo(Request $request){
        $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
        
        Validator::make($request->all(), [
            'id_casino' => 'required',
            'archivo'   => 'required|file',
        ])->validate();

        $id_casino_input = $request->id_casino;
        $id_plataforma = null;
        $id_casino = null;
        
        if($id_casino_input < 0){
            $id_plataforma = abs($id_casino_input);
        } else {
            $id_casino = $id_casino_input;
        }

         if(!$user->es_superusuario){
            if(!is_null($id_casino) && !$user->usuarioTieneCasino($id_casino)){
                 return response()->json(['id_casino' => 'No tiene acceso a ese casino'], 422);
            }
            if(!is_null($id_plataforma) && !$user->tienePermiso('aym_ae_plataformas')){
                 return response()->json(['id_casino' => 'No tiene acceso a esa plataforma'], 422);
            }
        }
        
        // Allowed platforms for mass import
        if ($id_plataforma != 1 && $id_plataforma != 2) {
             return response()->json(['id_casino' => 'Solo implementado para Plataforma 1 y 2 por el momento.'], 422);
        }

        $archivo = $request->file('archivo');
        $cant_procesados = 0;
        $cant_saltados = 0;
        $cant_errores = 0;
        $errores_detalle = [];

        try {
            DB::beginTransaction();

            $path = $archivo->getRealPath();
            $handle = fopen($path, 'r');
            
            if ($handle === false) {
                throw new \Exception("No se pudo abrir el archivo. Asegurese que sea un CSV valido.");
            }

            // 1. Detect Delimiter
            $firstLine = fgets($handle);
            rewind($handle);
            $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';

            // 2. Read Header
            $header = fgetcsv($handle, 0, $delimiter);
            if (!$header) {
                 fclose($handle);
                 throw new \Exception("El archivo esta vacio o no tiene cabecera.");
            }
            
            // Normalize Header to lowercase and Strip BOM
            $header = array_map(function($h) { 
                // Remove BOM if present (EF BB BF)
                $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
                return strtolower(trim($h)); 
            }, $header);
            
            Log::info("Headers found: " . implode(', ', $header));

            $col_map = array_flip($header);

            // 3. Define Column Mappings (Aliases)
            // Function to retrieve value by list of possible keys
            $getVal = function($aliases) use ($col_map) {
                foreach ($aliases as $alias) {
                    if (isset($col_map[$alias])) return $col_map[$alias];
                }
                return null;
            };

            // Map Column Indices
            $idx_dni          = $getVal(['dni', 'documento', 'nro_dni']);
            $idx_fecha_ae     = $getVal(['date se set', 'date_se_set', 'fecha_exclusion', 'fecha_solicitud', 'fecha_ae']);
            $idx_geo_city     = $getVal(['city', 'localidad', 'player_city', 'nombre_localidad']);
            $idx_geo_prov     = $getVal(['province', 'provincia', 'nombre_provincia']);
            $idx_domicilio    = $getVal(['address', 'addres', 'domicilio', 'calle']);
            $idx_nombre       = $getVal(['first name', 'first_name', 'nombres', 'nombre']);
            $idx_apellido     = $getVal(['last name', 'last_name', 'apellido']);
            $idx_sexo         = $getVal(['gender', 'sexo']);
            $idx_nacimiento   = $getVal(['dateofbirth', 'fecha_nacimiento']);
            $idx_telefono     = $getVal(['phonenumbermobile', 'phone', 'telefono', 'movil']);
            $idx_email        = $getVal(['email', 'correo']);
            $idx_status_plat1 = $getVal(['total_exclusions']);
            $idx_status_plat2 = $getVal(['player_status']);

            // Validate critical columns
            if (is_null($idx_dni)) {
                fclose($handle);
                throw new \Exception("No se encontro la columna DNI en el archivo. (Columnas encontradas: " . implode(', ', $header) . ")");
            }

            $max_fecha_ae = AE\EstadoAE::where('id_plataforma', $id_plataforma)->max('fecha_ae');
            Log::info("ImportarMasivo (Unified CSV): Plat $id_plataforma, Delim '$delimiter', MaxF $max_fecha_ae");

             // 4. Loop Rows
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                 if (count($row) < 2) continue; // Skip empty rows

                 $dni = $row[$idx_dni] ?? null;
                 if (!$dni || !is_numeric($dni)) continue;

                 // --- Date Logic ---
                 $fecha_ae_raw = ($idx_fecha_ae !== null) ? ($row[$idx_fecha_ae] ?? null) : null;
                 $fecha_ae = null;
                 
                 // Try parsing various date formats
                 if ($fecha_ae_raw) {
                     $formats = [
                         'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 
                         'Y-m-d H:i:s', 'Y-m-d',
                         'd-m-Y', 'd/m/y',
                         'd/m/Y h:i:s a', 'd/m/Y h:i a' // Support am/pm
                     ];
                     // Sanitize: convert 'a.m.' -> 'am' to match Carbon 'a' format
                     $fecha_ae_clean = str_ireplace(['a.m.', 'p.m.', 'a. m.', 'p. m.'], ['am', 'pm', 'am', 'pm'], $fecha_ae_raw);
                     $fecha_ae_clean = trim($fecha_ae_clean);
                     
                     foreach ($formats as $fmt) {
                         try {
                             $fecha_ae = \Carbon\Carbon::createFromFormat($fmt, $fecha_ae_clean);
                             if ($fecha_ae) break;
                         } catch (\Exception $e) {}
                     }
                 }
                 
                 // If null, default to now (or skip if strict?)
                 // User says "saltea todos", implying they want data.
                 if (!$fecha_ae) $fecha_ae = \Carbon\Carbon::now();
                 
                 // Calculated Dates
                 $fecha_renovacion = $fecha_ae->copy()->addMonths(6);
                 $fecha_vencimiento = $fecha_ae->copy()->addYear();

                 // Check Max Date (disabled to allow fixing data)
                 /*
                 if ($max_fecha_ae && $fecha_ae->format('Y-m-d') <= $max_fecha_ae) {
                     $cant_saltados++;
                     continue;
                 }
                 */
                 
                 // Prevent Exact Duplicates (DNI + Fecha AE + Plataforma)
                 // This prevents re-importing the same file, but allows fixing if only DNI existed before.
                 $dup = AE\EstadoAE::where('id_plataforma', $id_plataforma)
                                   ->whereDate('fecha_ae', $fecha_ae->format('Y-m-d'))
                                   ->whereHas('ae', function($q) use ($dni) {
                                       $q->where('nro_dni', $dni);
                                   })->first();
                 if ($dup) {
                     $cant_saltados++;
                     continue;
                 }


                 // --- Gender Logic ---
                 $sexo_raw = ($idx_sexo !== null) ? strtolower($row[$idx_sexo] ?? '') : '';
                 $id_sexo = 2; // Default to 'Otro'
                 // Mapping: M/Male/Hombre -> 0, F/Female/Mujer -> 1
                 if (in_array($sexo_raw, ['male', 'masculino', 'm', 'hombre', '0'])) $id_sexo = 0;
                 if (in_array($sexo_raw, ['female', 'femenino', 'f', 'mujer', '1'])) $id_sexo = 1;

                 // --- Address Logic ---
                 $localidad = ($idx_geo_city !== null) ? ($row[$idx_geo_city] ?? '-') : '-';
                 $provincia = ($idx_geo_prov !== null) ? ($row[$idx_geo_prov] ?? '-') : '-';
                 $domicilio = ($idx_domicilio !== null) ? ($row[$idx_domicilio] ?? '-') : $localidad;
                 
                 // --- DOB Logic ---
                 $fecha_nac = null;
                 if ($idx_nacimiento !== null && !empty($row[$idx_nacimiento])) {
                     $nac_raw = $row[$idx_nacimiento];
                     $nac_clean = str_ireplace(['a.m.', 'p.m.'], ['am', 'pm'], $nac_raw);
                     $nac_clean = trim($nac_clean);
                     foreach ($formats as $fmt) {
                        try {
                             $fecha_nac = \Carbon\Carbon::createFromFormat($fmt, $nac_clean)->format('Y-m-d');
                             if($fecha_nac) break;
                        } catch (\Exception $e) {}
                     }
                 }
                 if(!$fecha_nac) $fecha_nac = '1900-01-01'; // Default backup

                 // --- Phone Logic ---
                 $telefono = '-';
                 if ($idx_telefono !== null) {
                     $raw_tel = $row[$idx_telefono] ?? '';
                     // Keep only digits
                     $telefono = preg_replace('/[^0-9]/', '', $raw_tel);
                     if (empty($telefono)) $telefono = '-';
                 }

                 // --- Status Logic ---
                 $id_nombre_estado = 1; // Vigente
                 // Plat 1 logic
                 if ($idx_status_plat1 !== null) {
                     $total = $row[$idx_status_plat1] ?? 1;
                     if ($total > 1) $id_nombre_estado = 2; 
                 }
                 // Plat 2 logic
                 if ($idx_status_plat2 !== null) {
                     $stat = strtolower($row[$idx_status_plat2] ?? '');
                     if (strpos($stat, 'renov') !== false) $id_nombre_estado = 2;
                 }

                 // Prepare Arrays
                 $datos_ae = [
                    'nro_dni' => $dni,
                    'apellido' => ($idx_apellido !== null) ? ($row[$idx_apellido] ?? '-') : '-',
                    'nombres'  => ($idx_nombre !== null) ? ($row[$idx_nombre] ?? '-') : '-',
                    'fecha_nacimiento' => $fecha_nac,
                    'id_sexo' => $id_sexo,
                    'domicilio' => $domicilio,
                    'nro_domicilio' => '0',
                    'piso' => null, 'dpto' => null, 'codigo_postal' => null,
                    'nombre_localidad' => $localidad,
                    'nombre_provincia' => $provincia,
                    'telefono' => $telefono,
                    'correo' => ($idx_email !== null) ? ($row[$idx_email] ?? null) : null,
                    'id_ocupacion' => 12, 
                    'id_capacitacion' => 6, 
                    'id_estado_civil' => 6, 
                    'id_autoexcluido' => null
                 ];

                 $estado_ae = [
                    'id_nombre_estado' => $id_nombre_estado,
                    'id_casino' => null,
                    'id_plataforma' => $id_plataforma,
                    'fecha_ae' => $fecha_ae->format('Y-m-d'),
                    'fecha_renovacion' => $fecha_renovacion->format('Y-m-d'),
                    'fecha_vencimiento' => $fecha_vencimiento->format('Y-m-d'),
                    'fecha_cierre_ae' => $fecha_vencimiento->format('Y-m-d'), // Set closing date same as vencimiento?
                 ];

                 // Insert
                 try {
                    if ($this->existeAutoexcluido($dni) > 0) {
                        $cant_saltados++;
                        continue;
                    }
                    $this->crearAutoexcluidoInterno($datos_ae, $estado_ae, $user);
                    $cant_procesados++;
                 } catch (\Exception $e) {
                     $cant_errores++;
                     $errores_detalle[] = "DNI $dni: " . $e->getMessage();
                 }
            } // end while

            fclose($handle);
            DB::commit();
            
            // Save file record (Generic for now as per original flow) but verify if needed
            // $this->subirImportacionArchivos(null, []); // REMOVED: Causes Non-Object error and is unnecessary for bulk import
            Log::info("Masivo process complete.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error CRITICO masivo Unified: " . $e->getMessage());
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 200);
        }

        return response()->json([
            'procesados' => $cant_procesados,
            'saltados' => $cant_saltados,
            'errores' => $cant_errores,
            'detalle_errores' => $errores_detalle,
            'debug' => [
                'plat' => $id_plataforma,
                'msg' => 'Unified CSV parser used'
            ]
        ]);
    }

    private function crearAutoexcluidoInterno($ae_datos, $ae_estado_data, $user) {
        $ae = new AE\Autoexcluido;
        foreach($ae_datos as $key => $val){
            $ae->{$key} = $val;
        }
        $ae->save();

        $contacto = new AE\ContactoAE;
        $contacto->id_autoexcluido = $ae->id_autoexcluido;
        $contacto->save();

        $estado_data = $ae_estado_data;
        $estado_data['id_usuario'] = $user->id_usuario;
        $this->setearEstado($ae, $estado_data);
    }

  public function destruirPapel(Request $request){
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $ae_estado = null;
    Validator::make($request->all(), [
      'id_autoexcluido' => 'required|integer|exists:ae_datos,id_autoexcluido',
    ], [], self::$atributos)->after(function($validator) use ($user,&$ae_estado){
      if($validator->errors()->any()) return;
      $id = $validator->getData()['id_autoexcluido'];
      $ae_estado = AE\Autoexcluido::find($id)->estado;
      $id_casino = $ae_estado->id_casino;
      $id_plataforma = $ae_estado->id_plataforma;
        
      if(!$user->es_superusuario && !$user->es_administrador)
        return $validator->errors()->add('privilegios', 'No tiene acceso');
      
      if(is_null($id_casino))
        return $validator->errors()->add('ae_estado.id_casino', 'No tiene casino por ende no tiene papeles.');
      
      if($user->es_administrador && !$user->usuarioTieneCasino($id_casino))
        return $validator->errors()->add('ae_estado.id_casino', 'No tiene acceso a ese casino');
      
      if(!is_null($ae_estado->papel_destruido_id_usuario))
        return $validator->errors()->add('ae_estado.papel_destruido_id_usuario','El papel ya fue destruido');
        
      if(!is_null($ae_estado->papel_destruido_datetime))
        return $validator->errors()->add('ae_estado.papel_destruido_datetime','El papel ya fue destruido');
    })->validate();
    
    return DB::transaction(function() use ($user,&$ae_estado){
      $ae_estado->papel_destruido_id_usuario = $user->id_usuario;
      $ae_estado->papel_destruido_datetime = date('Y-m-d H:i:s');
      $ae_estado->save();
    });
    return $request->id_autoexcluido;
  }
}
