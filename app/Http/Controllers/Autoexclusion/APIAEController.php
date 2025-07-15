<?php

namespace App\Http\Controllers\Autoexclusion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\Autoexclusion\AutoexclusionController;
use App\Http\Controllers\Autoexclusion\DecryptDataController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use App\Casino;
use App\Plataforma;
use App\Autoexclusion as AE;
use Dompdf\Dompdf;
use View;
use PDF;
use GuzzleHttp\Client;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class APIAEController extends Controller
{
    private static $atributos = [];

    private static $instance;
    public static function getInstancia($actualizar = true){
      if (!isset(self::$instance)){
          self::$instance = new APIAEController($actualizar);
      }
      return self::$instance;
    }

    public function __construct($actualizar = true){//Actualizar estados antes de cada request
      if($actualizar) AutoexclusionController::getInstancia(false)->actualizarVencidosRenovados();
    }

    public function fechas(Request $request,string $dni){
        //No actualizo (false) porque ya se actualiza al crear al ser construido el controlador por Laravel
        $id = AutoexclusionController::getInstancia(false)->existeAutoexcluido($dni);
        //0 No tuvo, -1 Ya tuvo y estan vencidos
        if($id <= 0) return $this->errorOut(['error' => 'SIN AE']);
        
        $ae = AE\Autoexcluido::find($id);
        //No deberia pasar pero lo dejo chequeado por las dudas
        if(is_null($ae)) return $this->errorOut(['error' => 'ERROR UNREACHABLE']);

        $e = $ae->estado;
        $ret = ['fecha_ae' => $e->fecha_ae,'fecha_cierre_ae' => $e->fecha_cierre_ae];
        if($ae->es_primer_ae){
            $ret['fecha_renovacion']  = $e->fecha_renovacion;
            $ret['fecha_vencimiento'] = $e->fecha_vencimiento;
            if(!is_null($e->fecha_revocacion_ae)) $ret['fecha_revocacion_ae'] = $e->fecha_revocacion_ae;
        }
        return $ret;
    }
    
    public function finalizar(Request $request,string $dni){
        $AEC = AutoexclusionController::getInstancia(false);
        //No actualizo (false) porque ya se actualiza al crear al ser construido el controlador por Laravel
        $id = $AEC->existeAutoexcluido($dni);
        if($id <= 0) return $this->errorOut(['error' => 'SIN AE']);
        $ret = $AEC->cambiarEstadoAE($id,4);//Fin. por AE
        return $ret !== 1? $ret : response()->json('Finalizado',200);
    }
    
    private function verificarConflictoFechas($dni,$fecha_ae,bool $finalizado){//Verifico que no pise a algun AE ya en la BD
        $q = DB::table('ae_datos as aed')->select('aee.*')
        ->join('ae_estado as aee','aee.id_autoexcluido','=','aed.id_autoexcluido')
        ->whereNull('aed.deleted_at')->whereNull('aee.deleted_at')
        ->where('aed.nro_dni','=',$dni);
    
        /*
        Agarra dos casos (el de abajo es el que se agregaria)
    
        fecha_ae                                         fecha_cierre_ae
            ┌──────────────────────────────────────────────┐
            │                                              │
                │                                              │
                └──────────────────────────────────────────────┘
            $fecha_ae
        fecha_ae                                         fecha_cierre_ae
            ┌──────────────────────────────────────────────┐
            │                                              │
                │                      │
                └──────────────────────┘
            $fecha_ae
        */
        $dentro_algun_completo =  (clone $q)->whereNull('aee.fecha_revocacion_ae')
        ->where('aee.fecha_ae','<=',$fecha_ae)->where('aee.fecha_cierre_ae','>=',$fecha_ae)
        ->count() > 0;
        if($dentro_algun_completo) return 1;
    
        /*
        Agarra dos casos (el de abajo es el que se agregaria)
    
        fecha_ae                 fecha_vencimiento
            ┌──────────────────────┐
            │                      │
                │                                              │
                └──────────────────────────────────────────────┘
            $fecha_ae
        fecha_ae                 fecha_vencimiento
            ┌──────────────────────┐
            │                      │
                │                      │
                └──────────────────────┘
            $fecha_ae
        */
        $dentro_algun_finalizado = (clone $q)->whereNotNull('aee.fecha_revocacion_ae')
        ->where('aee.fecha_ae','<=',$fecha_ae)->where('aee.fecha_vencimiento','>=',$fecha_ae)
        ->count() > 0;
        if($dentro_algun_finalizado) return 2;
    
        $fecha_fin = null;
        {
          $fechas = AutoexclusionController::getInstancia(false)->generarFechas($fecha_ae);
          if($finalizado) $fecha_fin = $fechas->fecha_vencimiento;
          else            $fecha_fin = $fechas->fecha_cierre_ae;
        }
    
        /*
        Agarra dos casos (el de abajo es el que se agregaria)
                                  fecha_ae                                         fecha_cierre_ae
                                        ┌──────────────────────────────────────────────┐
                                        │                                              │
                                │                      │
                                └──────────────────────┘
                                                  $fecha_fin
                                  fecha_ae                                         fecha_cierre_ae
                                        ┌──────────────────────────────────────────────┐
                                        │                                              │
        │                                              │
        └──────────────────────────────────────────────┘
                                                  $fecha_fin
        */
        $se_extiende_dentro_de_alguno_ya_existente_completo = (clone $q)->whereNull('aee.fecha_revocacion_ae')
        ->where('aee.fecha_ae','<=',$fecha_fin)->where('aee.fecha_cierre_ae','>=',$fecha_fin)
        ->count() > 0;
        if($se_extiende_dentro_de_alguno_ya_existente_completo) return 3;
    
        /*
        Agarra dos casos (el de abajo es el que se agregaria)
                                  fecha_ae                    fecha_vencimiento
                                        ┌──────────────────────┐
                                        │                      │
                                │                      │
                                └──────────────────────┘
                                                  $fecha_fin
                                  fecha_ae                    fecha_vencimiento
                                        ┌──────────────────────┐
                                        │                      │
        │                                              │
        └──────────────────────────────────────────────┘
                                                  $fecha_fin
        */
        $se_extiende_dentro_de_alguno_ya_existente_finalizado = (clone $q)->whereNotNull('aee.fecha_revocacion_ae')
        ->where('aee.fecha_ae','<=',$fecha_fin)->where('aee.fecha_vencimiento','>=',$fecha_fin)
        ->count() > 0;
        if($se_extiende_dentro_de_alguno_ya_existente_finalizado) return 4;
    
        return 0;
    }
    
    public function agregar(Request $request){
        $validator = Validator::make($request->all(), [
          'ae_datos.nro_dni'          => 'required|integer',
          'ae_datos.apellido'         => 'required|string|max:100',
          'ae_datos.nombres'          => 'required|string|max:150',
          'ae_datos.fecha_nacimiento' => 'required|date',
          'ae_datos.sexo'             => 'required|string|max:4|exists:ae_sexo,codigo',
          'ae_datos.domicilio'        => 'required|string|max:100',
          'ae_datos.nro_domicilio'    => 'required|integer',
          'ae_datos.piso'             => 'nullable|string|max:5',
          'ae_datos.dpto'             => 'nullable|string|max:5',
          'ae_datos.codigo_postal'    => 'required|string|max:10',
          'ae_datos.nombre_localidad' => 'required|string|max:200',
          'ae_datos.nombre_provincia' => 'required|string|max:200',
          'ae_datos.telefono'         => 'required|string|max:200',
          'ae_datos.correo'           => 'required|string|max:100',
          'ae_datos.ocupacion'        => 'nullable|string|max:4|exists:ae_ocupacion,codigo',
          'ae_datos.capacitacion'     => 'nullable|string|max:4|exists:ae_capacitacion,codigo',
          'ae_datos.estado_civil'     => 'nullable|string|max:4|exists:ae_estado_civil,codigo',
          'ae_estado.fecha_ae'        => 'required|date',
          'ae_estado.fecha_revocacion_ae' => 'nullable|date',
        ], array(), self::$atributos)->after(function($validator){
          if($validator->errors()->any()) return;
          $data = $validator->getData();
          $se_puede_agregar = $this->verificarConflictoFechas($data['ae_datos']['nro_dni'],$data['ae_estado']['fecha_ae'],false);
          if($se_puede_agregar > 0){//Aca tal vez deberiamos dejar que conflicte... simplemente verificar que no tenga AE vigentes...
            return $validator->errors()->add('ae_datos.nro_dni','AE VIGENTE');
          }
          if($data['ae_estado']['fecha_ae'] > date('Y-m-d')){
            return $validator->errors()->add('ae_estado.fecha_ae','No puede agregar un AE en esa fecha');
          }
          if(!empty($data['ae_estado']['fecha_revocacion_ae'])){//Si envia uno finalizado
            //Verificar que sea su primer autoexclusion
            $AEC = AutoexclusionController::getInstancia(false);
            if($AEC->existeAutoexcluido($data['ae_datos']['nro_dni']) != 0){
              return $validator->errors()->add('ae_estado.fecha_revocacion_ae','No puede finalizar un AE repetido');
            }
            //Verificar que la fecha de revocacion tenga sentido (este dentro de (frenov,fvencimiento])
            $fs = $AEC->generarFechas($data['ae_estado']['fecha_ae']);
            if($data['ae_estado']['fecha_revocacion_ae'] <= $fs->fecha_renovacion){
              return $validator->errors()->add('ae_estado.fecha_revocacion_ae','No puede finalizar un AE en esa fecha');
            }
            if($data['ae_estado']['fecha_revocacion_ae'] > $fs->fecha_vencimiento){
              return $validator->errors()->add('ae_estado.fecha_revocacion_ae','No puede finalizar un AE en esa fecha');
            }
            if($data['ae_estado']['fecha_revocacion_ae'] > date('Y-m-d')){
              return $validator->errors()->add('ae_estado.fecha_revocacion_ae','No puede finalizar un AE en esa fecha');
            }
          }
        });
    
        if($validator->errors()->any()) return $this->errorOut($validator->errors());
    
        $request = $request->all();
    
        //Sexo siempre viene asi que en realidad el tercer valor nunca se usa
        $except = ['sexo'         => ['id_sexo',        'ae_sexo', 'X'],
                   'ocupacion'    => ['id_ocupacion',   'ae_ocupacion', 'NC'],
                   'capacitacion' => ['id_capacitacion','ae_capacitacion', 'NC'],
                   'estado_civil' => ['id_estado_civil','ae_estado_civil', 'NC']];
    
        foreach($except as $key => $defecto){//Pongo valores por defecto "No contesta" si no lo envia.
          if(!array_key_exists($key,$request['ae_datos'])) $request['ae_datos'][$key] = $defecto[2];
        }
        $api_token = AuthenticationController::getInstancia()->obtenerAPIToken();
        DB::transaction(function() use($request,$api_token,$except){
          $ae = new AE\Autoexcluido;
          $ae_datos = $request['ae_datos'];
    
          foreach($ae_datos as $key => $val){
            if(!array_key_exists($key,$except)) $ae->{$key} = $val;
            else{
              $table = $except[$key][1];
              $id_name = $except[$key][0];
              $row = DB::table($table)->select($id_name)->where('codigo',$val)->get()->first();
              $ae->{$id_name} = $row->{$id_name};
            }
          }
          $ae->save();
    
          $contacto = new AE\ContactoAE;
          $contacto->id_autoexcluido = $ae->id_autoexcluido;
          $contacto->save();
    
          $ae_estado = $request['ae_estado'];
          $ae_estado['id_usuario'] = AuthenticationController::getInstancia()->obtenerIdUsuario();
          if(empty($ae_estado['fecha_revocacion_ae'])){
            $ae_estado['id_nombre_estado'] = 1;//Vigente
          }else{
            $ae_estado['id_nombre_estado'] = 4;//Fin. por AE
          }
          $ae_estado['id_plataforma'] = ($api_token->metadata ?? [])['id_plataforma'] ?? null;
          $AEC = AutoexclusionController::getInstancia(false);
          $AEC->setearEstado($ae,$ae_estado);
          $AEC->subirImportacionArchivos($ae,[]);
        });
    
        return response()->json('Agregado',200);
    }

    private function errorOut($map){
        return response()->json($map,422);
    }

    // AGREGADO POR IGNACIO
    public function set_importacion_archivos(Request $request){
      $validator = Validator::make($request->all(), [
            'dni' => 'required|string|max:150',
            'file' => 'required|file|mimes:webp',
        ]);
      if($validator->fails()) {
        return response()->json($validator->errors(), 422);
      }
      $data = $request->all();
      $aes = AE\Autoexcluido::where('nro_dni', $data['dni'])->max('id_autoexcluido');
      if($aes){
        $ae = AE\Autoexcluido::find($aes);
        $importacion = AE\ImportacionAE::where('id_autoexcluido', $ae->id_autoexcluido)->first();
        DB::transaction(function() use ($data, $aes , $ae , $importacion){
          $aec = AutoexclusionController::getInstancia(false);
          
          $barra = strpos($data['file']->getMimeType(),'/');
          $extension = substr($data['file']->getMimeType(),$barra+1);
          $nombre_archivo =  date("dmY") . '-' . $ae->nro_dni . '-3.' . $extension;

          $pathCons = public_path('/importacionesAutoexcluidos/documentos');
          if (!file_exists($pathCons)) {
            mkdir($pathCons, 0755, true);
          }
          $path = $pathCons . '/' . $nombre_archivo;

          copy($data['file']->getRealPath(), $path);

          $importacion->scandni = $nombre_archivo;
          $importacion->save();
                  
        });

        return response()->json('Actualizado', 200);
      }
    }
    
    public function exclusion_registro(Request $request, $dni){
      if($request->header('X-API-Key') !== env("APP_KEY_SEVA")){
        return response()->json(['error' => 'Key de API inválida'], 401);
      }
      $aes = AE\Autoexcluido::where('nro_dni',$dni)->max('id_autoexcluido');
      if($aes){
        $autoexcluido = AE\Autoexcluido::find($aes);
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

        $pdfContent = $dompdf->output();
        $pdfBase64 = base64_encode($pdfContent);
        return response()->json(["content" => $pdfBase64]);
      }
      return response()->json(["Error" => "Sin AE"]);
    }
    //devuelve el pdf de constancia de reingreso
    public function reingreso(Request $request, $dni){
      if($request->header('X-API-Key') !== env("APP_KEY_SEVA")){
        return response()->json(['error' => 'Key de API inválida'], 401);
      }
      $aes = AE\Autoexcluido::where('nro_dni',$dni)->max('id_autoexcluido');
      if($aes){
        $ae = AE\Autoexcluido::find($aes);
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
        $pdfContent = $dompdf->output();
        $pdfBase64 = base64_encode($pdfContent);
        return response()->json(["content" => $pdfBase64]);
      }
      return response()->json(["Error" => "Sin AE"]);
    }

    public function ultimos_datos(Request $request, $dni){
      // esto solo sirve si las id son asiganadas de manera creciente cambiar en caso contrario
      // no se puede usaer el created_at ya que no esta seteado 
      if($request->header('X-API-Key') !== env("APP_KEY_SEVA")){
        return response()->json(['error' => 'Key de API inválida'], 401);
      }
      $aes = AE\Autoexcluido::where('nro_dni',$dni)->max('id_autoexcluido');
      if($aes){
        $ae = AE\Autoexcluido::find($aes);
        return response()->json(
           [          
            'nro_dni'          => $ae->nro_dni,
            'apellido'         => $ae->apellido,
            'nombres'          => $ae->nombres,
            'fecha_nacimiento' => $ae->fecha_nacimiento,
            'sexo'             => $ae->sexo->codigo,
            'domicilio'        => $ae->domicilio,
            'nro_domicilio'    => $ae->nro_domicilio,
            'piso'             => $ae->piso,
            'dpto'             => $ae->dpto,
            'codigo_postal'    => $ae->codigo_postal,
            'nombre_localidad' => $ae->nombre_localidad,
            'nombre_provincia' => $ae->nombre_provincia,
            'telefono'         => $ae->telefono,
            'correo'           => $ae->correo,
            'ocupacion'        => $ae->ocupacion->codigo,
            'capacitacion'     => $ae->capacitacion->codigo,
            'estado_civil'     => $ae->estado_civil
          ]
          );
      }
    }

    public function obtener_autoExclusiones(Request $request, $dni) {
      $data = DB::table('ae_datos as aedato')
      ->select('aedato.nro_dni', 'estado.id_estado', 'estado.id_nombre_estado', 'estado.fecha_ae', 'estado.fecha_vencimiento', 'estado.fecha_cierre_ae')
      ->join('ae_estado as estado','estado.id_autoexcluido','=','aedato.id_autoexcluido')
      ->where('aedato.nro_dni','=',$dni) 
      ->paginate($request->page_size);
      return $data;
    }

    public function obtener_noticias(Request $request){
      $client = new Client();
      $url = env("APP_SEVA_URL");
      $key = env("APP_X_API_KEY");
      $page = $request->page;
      $dataNoEncrpt = json_encode($request->all());
      $response = $client->post("${url}api/resources/get-news-list?page=${page}", [
          'headers' => [
            "X-API-Key" =>  $key,
          ],
          'form_params' => [
              'data' => DecryptDataController::encrypt($dataNoEncrpt)
          ]
      ]);
      $content = json_decode($response->getBody(), true);
      return DecryptDataController::decrypt($content['data']);
    }

    public function subir_noticias(Request $request){
      $client = new Client();
      $url = env("APP_SEVA_URL");
      $key = env("APP_X_API_KEY");
      try{
        $multipart = [];

        // Agrega otros datos como "title" y "abstract" al multipart
        foreach ($request->except(['image', 'pdf']) as $key => $value) {
            $multipart[] = [
                'name' => $key,
                'contents' => $value,
            ];
        }

        // Agrega los archivos si están presentes
        if ($request->hasFile('image')) {
            $multipart[] = [
                'name' => 'image',
                'contents' => fopen($request->file('image')->getPathname(), 'r'),
                'filename' => $request->file('image')->getClientOriginalName(),
            ];
        }

        if ($request->hasFile('pdf')) {
            $multipart[] = [
                'name' => 'pdf',
                'contents' => fopen($request->file('pdf')->getPathname(), 'r'),
                'filename' => $request->file('pdf')->getClientOriginalName(),
            ];
        }
        $response = $client->post("${url}api/resources/post-pdf-document", [
            'headers' => [
              "X-API-Key" =>  $key,
            ],
            'multipart' => $multipart,
            
        ]);
        $content = json_decode($response->getBody(), true);
        $data = DecryptDataController::decrypt($content['data']);
      }catch(Exception $e){
        Log::info($e);
        $data = $e->getMessage();
      }
      return $data;
    }

    public function borrar_noticias(Request $request){
      $client = new Client();
      $url = env("APP_SEVA_URL");
      $key = env("APP_X_API_KEY");
      $dataNoEncrpt = json_encode($request->all());
      Log::info("${url}api/resources/remove-news");
      $response = $client->post("${url}api/resources/remove-news", [
          'headers' => [
            "X-API-Key" => $key,
          ],
          'form_params' => $request->all(), //cambiar
      ]);
      $content = json_decode($response->getBody(), true);
      return DecryptDataController::decrypt($content['data']);
    }

    private function getDniByCuil ($cuil){
      return explode('-',$cuil)[1];
    }
    public function registrar_encuesta(Request $request){
      $userId = null;
      
      $validator = Validator::make($request->all(), [
        'cuil' => 'required|string|regex:/^[0-9]{2}\-[0-9]{5,8}-[0-9]$/',
        'frecuencia' => 'required|string|max:50|exists:frecuencias_encuesta_seva,nombre',
        'asistencia' => 'required|string|max:50|exists:asistencia_casino_seva,nombre',
        'horas' => 'required|numeric|between:1,24',
        'socioClubJugadores' => 'required|boolean',
        'conocePlataformasOnline' => 'required|boolean',
        'utilizaPlataformasOnline' => 'required|boolean',
        'problemasAutocontrol' => 'required|boolean',
        'deseaRecibirInfo' => 'required|boolean',
        'maquinasTradicionales' => 'required|boolean',
        'ruletaElectronica' => 'required|boolean',
        'carteados' => 'required|boolean',
        'ruletaAmericana' => 'required|boolean',
        'dados' => 'required|boolean',
        'bingo' => 'required|boolean',
      ],[
        'required' => 'El valor es requerido',
        'boolean' => 'El valor tiene que ser booleano',
        'numeric' => 'El valor tiene que ser numerico',
        'horas.between' => 'Tiene que estar en [1,24]',
        'cuil.regex' => 'CUIL en formato incorrecto',
        'frecuencia.exists' => 'No se encontro la frecuencia',
        'asistencia.exists' => 'No se encontro la asistencia'
      ],[])->after(function($validator) use (&$userId){
        if($validator->errors()->any()) return;
        
        $validateData = $validator->getData();
        $dni = $this->getDniByCuil($validateData['cuil']);
        
        $userId_y_encuestaId = $this->get_userId_y_encuestaId($dni);
        
        if($userId_y_encuestaId[0] === null){ 
          return $validator->errors()->add('cuil','No se encontro el usuario');
        }
        
        if($userId_y_encuestaId[1] !== null){
          return $validator->errors()->add('cuil','La encuesta ya fue completada por este usuario');
        }
        
        $userId = $userId_y_encuestaId[0];
      });
      
      if($validator->errors()->any()){
        return response()->json($validator->errors(),422);
      }
    
      $validateData = $validator->getData();
      try{
        $frecuenciaId = DB::table('frecuencias_encuesta_seva')
                        ->where('nombre',$validateData['frecuencia'])
                        ->value('id');
        $asistenciaId = DB::table('asistencia_casino_seva')
                        ->where('nombre',$validateData['asistencia'])
                        ->value('id');

        $to_insert = [
          'id_autoexcluido' => $userId,
          'id_frecuencia' => $frecuenciaId,
          'id_asistencia' => $asistenciaId,
          'tiempo_juego' => $validateData['horas'],
          'maquinas_tradicionales'=> $validateData['maquinasTradicionales'],
          'ruleta_electronica'=> $validateData['ruletaElectronica'],
          'carteados' => $validateData['carteados'],
          'ruleta_americana' => $validateData['ruletaAmericana'],
          'dados' => $validateData['dados'],
          'bingo' => $validateData['bingo'],
          'club_jugadores' => $validateData['socioClubJugadores'],
          'conoce_plataformas' => $validateData['conocePlataformasOnline'],
          'utiliza_plataformas' => $validateData['utilizaPlataformasOnline'],
          'problemas_autocontrol' => $validateData['problemasAutocontrol'],
          'informacion_juego_responsable' => $validateData['deseaRecibirInfo'],
          'updated_at' => Carbon::now(),
        ];
        $encuestaGuardar = DB::table('encuesta_seva')->insert($to_insert);
        if(!$encuestaGuardar) return response()->json(['error' => 'No se pudo crear el registro'],500);
        return response()->json(['mensaje' => 'Registro creado con exito']);
      }catch(QueryException $e){
        Log::error('Error en consulta: ' . (string)$e);
        return response()->json(json_decode(json_encode($e),true), 500);
      }
    }
    
    private function get_userId_y_encuestaId($dni){
      $userId = DB::table('ae_datos')
      ->where('nro_dni',$dni)
      ->whereNull('deleted_at')
      ->value('id_autoexcluido');
      
      if($userId === null){ 
        return [null,null];
      }
      
      $encuestaId = DB::table('encuesta_seva')
      ->where('id_autoexcluido', $userId)
      ->value('id_encuesta');
      
      return [$userId,$encuestaId];
    }
    
    public function respondio_encuesta(Request $request){
      $encuestaId = null;
      $validator = Validator::make($request->all(),[
        'cuil' => 'required|string|regex:/^[0-9]{2}\-[0-9]{5,8}-[0-9]$/',
      ],[
        'required' => 'El valor es requerido',
        'cuil.regex' => 'CUIL en formato incorrecto',
      ],[])->after(function($validator) use (&$encuestaId){
        if($validator->errors()->any()) return;
        
        $validateData = $validator->getData();
        $dni = $this->getDniByCuil($validateData['cuil']);
        
        $userId_y_encuestaId = $this->get_userId_y_encuestaId($dni);
        
        if($userId_y_encuestaId[0] === null){ 
          return $validator->errors()->add('cuil','No se encontro el usuario');
        }
        
        $encuestaId = $userId_y_encuestaId[1];
      });

      if($validator->errors()->any()){
        return response()->json($validator->errors(),422);
      }
    
      try {
        $respondioEncuesta = $encuestaId !== null? 1 : 0;
        return response()->json(['respondioEncuesta' => $respondioEncuesta],200);
      } catch (QueryException $e) {
        Log::error('Error en consulta: ' . (string)$e);
        return response()->json(json_decode(json_encode($e),true), 500);
      }
    }
}
