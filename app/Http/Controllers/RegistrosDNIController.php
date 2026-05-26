<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;

class RegistrosDNIController extends Controller
{
  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }
  
  private function resumen_hash(array $args){
    $concat_ws_args = implode(',',array_map(function($a){return "IFNULL($a,'')";},$args));
    return "UNHEX(SHA1(CONCAT_WS('|',$concat_ws_args)))";
  }
  
  public function __construct(){
    //Cambiar el default de la version si hay alguna modificación estructural 
    //que diferencie los registros anteriores de los nuevos
    DB::statement('CREATE TABLE IF NOT EXISTS registros_dni_importacion (
      id_registros_dni_importacion INT AUTO_INCREMENT PRIMARY KEY,
      id_casino INT NOT NULL,
      fecha_informado DATE NOT NULL,
      md5 VARCHAR(32) NOT NULL,
      nombre_archivo VARCHAR(255) NOT NULL,
      created_at DATETIME NOT NULL,
      created_by INT NOT NULL,
      desde DATETIME NULL,-- Usado para prefiltar id_registros_dni_importacion
      hasta DATETIME NULL,-- Usado para prefiltar id_registros_dni_importacion
      version varchar(32) DEFAULT "inicial",
      INDEX idx_casino_fecha (id_casino, fecha_informado),
      KEY `fk_importacion_registros_dni_id_casino` (`id_casino`),
      KEY `fk_importacion_registros_dni_created_by` (`created_by`),
      CONSTRAINT `fk_importacion_registros_dni_id_casino` FOREIGN KEY (`id_casino`) REFERENCES `casino` (`id_casino`),
      CONSTRAINT `fk_importacion_registros_dni_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuario` (`id_usuario`)
    )');
    
    DB::statement('CREATE TABLE IF NOT EXISTS registros_dni (
      id_registros_dni INT AUTO_INCREMENT,
      id_casino INT NOT NULL, 
      id_registros_dni_importacion INT NOT NULL,
      fecha_nacimiento DATE NOT NULL,
      timestamp DATETIME NOT NULL,
      edad INT GENERATED ALWAYS AS (TIMESTAMPDIFF(YEAR, fecha_nacimiento, timestamp)) STORED NOT NULL,
      PRIMARY KEY (id_registros_dni, id_casino),
      INDEX idx_importacion_timestamp (id_registros_dni_importacion,timestamp),
      KEY `fk_registros_dni_importacion` (`id_registros_dni_importacion`),
      KEY `fk_registros_dni_id_casino` (`id_casino`)
      -- Sin FK por el particionado
      -- CONSTRAINT `fk_registros_dni_importacion` FOREIGN KEY (`id_registros_dni_importacion`) REFERENCES `registros_dni_importacion` (`id_registros_dni_importacion`),
      -- CONSTRAINT `fk_registros_dni_id_casino` FOREIGN KEY (`id_casino`) REFERENCES `casino` (`id_casino`)
    )
    PARTITION BY LIST (id_casino) (
      PARTITION p_casino_1 VALUES IN (1),
      PARTITION p_casino_2 VALUES IN (2),
      PARTITION p_casino_3 VALUES IN (3),
      PARTITION p_casino_4 VALUES IN (4),
      PARTITION p_casino_5 VALUES IN (5),
      PARTITION p_others VALUES IN (6,7,8,9,10,11,12,13,14,15,16,17,18,19,20)
    )');
        
    DB::statement('CREATE TABLE IF NOT EXISTS registros_dni_resumen (
      id_registros_dni_resumen INT AUTO_INCREMENT PRIMARY KEY,
      id_casino INT NULL,                    
      id_registros_dni_importacion INT NULL,
      dia DATE NULL,
      hora INT NULL,
      edad INT NULL,
      cantidad INT NOT NULL,
      resumen_hash BINARY(20) GENERATED ALWAYS AS ('.
        $this->resumen_hash([
          'id_casino',
          'id_registros_dni_importacion',
          'dia',
          'hora',
          'edad'
        ])
      .') STORED NOT NULL,
      -- UNIQUE INDEX para que soporte los campos NULL de los totales
      UNIQUE KEY uniq_registros_dni_resumen (id_casino, id_registros_dni_importacion, dia, hora, edad),
      -- En teoria es practicamente imposible una colision hash de SHA1...
      UNIQUE KEY uniq_registros_dni_resumen_hash (resumen_hash),
      KEY `fk_registros_dni_resumen_importacion` (`id_registros_dni_importacion`),
      KEY `fk_registros_dni_id_casino` (`id_casino`)
      -- Sin FK para facilitar el borrado
      -- CONSTRAINT `fk_registros_dni_resumen_importacion` FOREIGN KEY (`id_registros_dni_importacion`) REFERENCES `registros_dni_importacion` (`id_registros_dni_importacion`),
      -- CONSTRAINT `fk_registros_dni_id_casino` FOREIGN KEY (`id_casino`) REFERENCES `casino` (`id_casino`)
    )');
  }
  
  public function index(){
    $casinos = UsuarioController::getInstancia()->quienSoy()['usuario']->casinos;
    UsuarioController::getInstancia()->agregarSeccionReciente('Registros DNI' , 'registrosDNI');
    return view('seccionRegistrosDNI',[
      'casinos' => $casinos
    ]);
  }
  
  public function eliminar(Request $request,$id_importacion_registros_dni){
    return DB::transaction(function() use ($id_importacion){
      return 1;
    });
  }
  
  public function obtener(Request $request){
    return $request->id_importacion_registros_dni;
  }
  
  public function buscar_importaciones(Request $request){
    $u = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $reglas = [];
    if(isset($request->id_casino)){
      $reglas[] = ['ri.id_casino','=',$request->id_casino];
    }
    if(isset($request->informado) && is_array($request->informado)){
      if(!empty($request->informado[0])){
        $reglas[] = ['ri.fecha_informado','>=',$request->informado[0]];
      }
      if(!empty($request->importado[1])){
        $reglas[] = ['ri.fecha_informado','<=',$request->informado[1]];
      }
    }
    
    if(isset($request->reportado) && is_array($request->reportado)){
      if(\DateTime::createFromFormat('Y-m-d',$request->reportado[0]) !== false){
        if(!empty($request->reportado[0])){
          $reglas[] = [DB::raw("(
                '{$request->reportado[0]} 00:00:00' <= ri.desde
            OR  '{$request->reportado[0]} 00:00:00' <= ri.hasta
          )"),'=','1'];
        }
      }
      if(\DateTime::createFromFormat('Y-m-d',$request->reportado[1]) !== false){
        if(!empty($request->reportado[1])){
          $reglas[] = [DB::raw("(
                '{$request->reportado[1]} 23:59:59' >= ri.desde 
            OR  '{$request->reportado[1]} 23:59:59' >= ri.hasta
          )"),'=','1'];
        }
      }
    }
    
    $sort_by = [
      'columna' => 'ri.fecha_informado',
      'orden' => 'desc'
    ];
    
    if(!empty($request->sort_by) && !empty($request->sort_by['columna'])){
      $sort_by['columna'] = $request->sort_by['columna'];
      if(!empty($request->sort_by['orden'])){
        $sort_by['orden'] = $request->sort_by['orden'];
      }
    }
    
    $ret = DB::table('registros_dni_importacion as ri')
    ->selectRaw('ri.id_registros_dni_importacion, ri.fecha_informado, ri.desde, ri.hasta,ri.nombre_archivo,ri.md5,
      (
        SELECT rr.cantidad
        FROM registros_dni_resumen as rr
        WHERE rr.id_registros_dni_importacion = ri.id_registros_dni_importacion
        AND rr.dia IS NULL
        AND rr.hora IS NULL
        AND rr.edad IS NULL
      ) as cantidad_registros,
      (
        SELECT SUM(rr.cantidad) as cantidad
        FROM registros_dni_resumen as rr
        WHERE rr.id_registros_dni_importacion = ri.id_registros_dni_importacion
        AND rr.dia IS NULL
        AND rr.hora IS NULL
        AND rr.edad <= 17
        GROUP BY "constant"
      ) as cantidad_menores
    ')
    ->where($reglas)
    ->whereIn('ri.id_casino',$u->casinos->pluck('id_casino'))
    ->orderBy($sort_by['columna'],$sort_by['orden'])
    ->paginate($request->page_size ?? 10);
        
    return $ret;
  }
  
  public function buscar_registros(Request $request){
    $u = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $reglas = [];
    if(isset($request->id_casino)){
      $reglas[] = ['r.id_casino','=',$request->id_casino];
    }
    if(isset($request->informado) && is_array($request->informado)){
      if(!empty($request->informado[0])){
        $reglas[] = ['ri.fecha_informado','>=',$request->informado[0]];
      }
      if(!empty($request->informado[1])){
        $reglas[] = ['ri.fecha_informado','<=',$request->informado[1]];
      }
    }
    
    if(isset($request->reportado) && is_array($request->reportado)){
      if(!empty($request->reportado[0])){
        $reglas[] = ['r.timestamp','>=',$request->reportado[0].' 00:00:00'];
      }
      if(!empty($request->reportado[1])){
        $reglas[] = ['r.timestamp','<=',$request->reportado[1].' 23:59:59'];
      }
    }
    
    if(isset($request->md5)){
      $reglas[] = ['ri.md5','=',$request->md5];
    }
    
    $sort_by = [
      'columna' => 'r.timestamp',
      'orden' => 'desc'
    ];
    
    if(!empty($request->sort_by) && !empty($request->sort_by['columna'])){
      $sort_by['columna'] = $request->sort_by['columna'];
      if(!empty($request->sort_by['orden'])){
        $sort_by['orden'] = $request->sort_by['orden'];
      }
    }
    
    $ret = DB::table('registros_dni as r')
    ->select('r.*','ri.fecha_informado')
    ->join('registros_dni_importacion as ri','ri.id_registros_dni_importacion','=','r.id_registros_dni_importacion')
    ->where($reglas)
    ->whereIn('r.id_casino',$u->casinos->pluck('id_casino'))
    ->orderBy($sort_by['columna'],$sort_by['orden'])
    ->paginate($request->page_size ?? 10);
        
    return $ret;
  }
  
  public function importar(Request $request)
  {
    $now = (new \DateTimeImmutable());//->format('Y-m-d H:i:s');
    $user = \App\Http\Controllers\UsuarioController::getInstancia()->quienSoy()['usuario'];
    $es_excel = -1;
    Validator::extend('excel',function($attribute, $value, $params, $validator) use (&$es_excel){
      if (!$value instanceof \Illuminate\Http\UploadedFile) {
        return false;
      }
      // No funciona, devuelve binario bin, octet-stream
      //$ext = $value->guessExtension();
      //$mime_file = mime_content_type($value->getRealPath());
      //$ext_client = $value->getClientOriginalExtension();
      $mime_client = $value->getClientMimeType();
      if(in_array($mime_client,[
        'application/vnd.ms-excel','application/vnd.ms-excel.addin.macroEnabled.12','application/vnd.ms-excel.template.macroEnabled.12',
        'application/vnd.ms-excel.sheet.macroEnabled.12','application/vnd.ms-excel.sheet.binary.macroEnabled.12',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.spreadsheet-template'
      ])){
        $es_excel = true;
      }
      elseif(in_array($mime_client,[
        'text/csv','application/csv','text/plain'
      ])){
        $es_excel = false;
      }
      return $es_excel !== -1;
    });
    Validator::make($request->all(), [
      'id_casino' => 'required|exists:casino,id_casino,deleted_at,NULL',
      'fecha_informado' => 'required|date',
      'archivo' => 'required|excel',
      'md5' => 'required|string|max:32'
    ], [
      'required' => 'El valor es requerido',
      'exists' => 'No es un valor existente',
      'file' => 'Tiene que ser un archivo',
      'archivo.mimes' => 'Solo se aceptan archivos tipos .csv .txt .xls .xlsx .ods'
    ], [])->after(function($validator) use ($user){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      if(!$user->es_superusuario && !$user->usuarioTieneCasino($id_casino)){
        return $validator->errors()->add('id_casino', 'No tiene acceso a ese casino');
      }
      $md5calculado = md5_file($data['archivo']->getRealPath());
      if($md5calculado != $data['md5']){
        return $validator->errors()->add('md5', 'Error se espero el md5 "'.$md5calculado.'"');
      }
      $ya_existe = DB::table('registros_dni_importacion')->where('md5',$data['md5'])
      ->where('id_casino',$data['id_casino'])->first();
      if($ya_existe !== null){
        return $validator->errors()->add('md5', 'Ya existe un archivo con este md5, ('.$ya_existe->fecha_informado.')');
      }
    })->validate();
    
    $archivos = [];
    $outdir = '';
    $clean = null;
    if($es_excel){
      $rmdir = function ($dir) use (&$rmdir) {//Borra recursivamente... cuidado con que se lo llama
        assert(substr($dir, 0, strlen(storage_path())) == storage_path());//Chequea que no se llame con un path raro
        if (is_dir($dir) === false)
          return false;
        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $f) {
          $fpath = $dir . '/' . $f;
          if (is_dir($fpath)) {
            $rmdir($fpath);
          } else {
            unlink($fpath);
          }
        }

        return rmdir($dir);
      };
      
      //@TODO: pasar a /tmp o habilitar la forma de switchear entre implementaciones
      $carpeta_storage = storage_path('registrosDNI');
      if (!is_dir($carpeta_storage)) {
        mkdir($carpeta_storage);
      }
      $carpeta_storage .= '/' . uniqid();
      $rmdir($carpeta_storage);
      mkdir($carpeta_storage);
     
      $err_file = $carpeta_storage . '/log.err';
      $out_dir = $carpeta_storage . '/out';
      mkdir($out_dir);

      $excel = $request->archivo->getPathName();
      $outfile = $out_dir . '/out.csv';

      $output = [];
      $return_var = null;
      exec('ssconvert --export-file-per-sheet ' . escapeshellarg($excel) . ' ' . $outfile . ' 2> ' . $err_file, $output, $return_var);

      $clean = function () use ($err_file, $rmdir, $carpeta_storage) {
        try {
          unlink($err_file);
          $rmdir($carpeta_storage);
        } catch (\Exception $e) {
          //
        }
      };

      if ($return_var != 0) {
        $mensaje_error = '<span>Code: ' . $return_var . '</span>';
        $mensaje_error .= '<p>Output:<pre><code></code>' . implode("\r\n", $output) . '</pre></p>';
        $mensaje_error .= '<p>Error:<pre><code></code>' . file_get_contents($err_file) . '</pre></p>';
        $clean();
        return response()->json(['mensaje' => $mensaje_error],422);
      }
      $archivos = scandir($out_dir);
    }
    else{//csv
      $out_dir = $request->archivo->getPath();
      $archivos = [$request->archivo->getFileName()];
      $clean = function(){};
    }
    
    $header_esperado = ['fecha_nac','mayor_edad','edad','timestamp'];
    $datos = [];    
    foreach ($archivos as $a) {
      if ($a == '.' || $a == '..')
        continue;
      $abs_a = $out_dir . '/' . $a;
      $fhandle = fopen($abs_a, 'r');
      if ($fhandle === FALSE){
        continue;
      }
      $header = fgetcsv($fhandle);
      {
        $haux = [];
        foreach($header as $h){
          $h = trim($h," \t\n\r\0\x0B\xEF\xBB\xBF");//Sacar caracter BOM insertado por excel https://stackoverflow.com/questions/54145035/cant-remove-ufeff-from-a-string
          $haux[] = utf8_encode($h);//Lo convierto porque pueden mandarlo en un encoding raro
        }
        $header = $haux;
      }
      
      if($header != $header_esperado) {
        $mensaje_error  = '<p>Header inesperado: '.implode('|',$header).'</p>';
        $mensaje_error .= '<p>Header esperado: '.implode('|',$header_esperado).'</p>';
        fclose($fhandle);
        $clean();
        return response()->json(['mensaje' => $mensaje_error],422);
      }
      $filaidx = 0;
      while(($fila = fgetcsv($fhandle)) !== FALSE){
        if(count($fila) == 0) continue;//Skip fila vacia
        if(count($fila) != count($header)){
          $mensaje_error = '<p>Fila('.$filaidx.'): Se recibieron '.count($fila).' columna(s), se esperaban '.count($header).'</p>';
          fclose($fhandle);
          $clean();
          return response()->json(['mensaje' => $mensaje_error],422);
        }
        $d = [];
        foreach($header as $attr_idx => $attr){
          $d[$attr] = trim($fila[$attr_idx]);
        }
        $datos[] = $d;
      }
      
      fclose($fhandle);
    }
    $clean();
    
    return DB::transaction(function() use (&$request,&$datos,&$user,&$now) {
      $min_timestamp = null;
      $max_timestamp = null;
      foreach($datos as $didx => $d){
        if($min_timestamp === null){
          $min_timestamp = $d['timestamp'] ?? null;
        }
        else{
          $min_timestamp = min($min_timestamp,$d['timestamp'] ?? $min_timestamp);
        }
        
        if($max_timestamp === null){
          $max_timestamp = $d['timestamp'] ?? null;
        }
        else{
          $max_timestamp = max($max_timestamp,$d['timestamp'] ?? $max_timestamp);
        }
      }
      
      $id_registros_dni_importacion = DB::table('registros_dni_importacion')->insertGetId([
        'id_casino' => $request->id_casino,
        'fecha_informado' => $request->fecha_informado,
        'md5' => $request->md5,
        'nombre_archivo' => $request->archivo->getClientOriginalName(),
        'desde' => $min_timestamp,
        'hasta' => $max_timestamp,
        'created_at' => $now->format('Y-m-d h:i:s'),
        'created_by' => $user->id_usuario
      ]);
      
      $datos_a_insertar = array_fill(0,count($datos),[
        'id_casino' => $request->id_casino,
        'id_registros_dni_importacion' => $id_registros_dni_importacion,
        'fecha_nacimiento' => null,
        'timestamp' => null
      ]);      
      foreach($datos as $didx => $d){
        $datos_a_insertar[$didx]['fecha_nacimiento'] = $d['fecha_nac'];
        $datos_a_insertar[$didx]['timestamp'] = $d['timestamp'];
        //edad se calcula solo como columna generada
      }
      DB::table('registros_dni')->insert($datos_a_insertar);
      
      $registros = count($datos);
      $desde = $min_timestamp;
      $hasta = $max_timestamp;
      $mensaje = $registros?
        "Importados $registros ($desde al $hasta)"
      : "0 registros importados";
      
      $this->recalcular_resumenes();
      
      return compact('registros','desde','hasta','mensaje');
    });
  }
  
  private function recalcular_resumenes(){
    $id_casinos = \App\Casino::all()->pluck('id_casino');
    
    //Importaciones borradas, esta en el resumen pero no existe mas
    $ids_a_eliminar = DB::table('registros_dni_resumen as rr')
    ->select('rr.id_registros_dni_importacion')->distinct()
    ->leftJoin('registros_dni_importacion as ri','ri.id_registros_dni_importacion','=','rr.id_registros_dni_importacion')
    ->whereIn('rr.id_casino',$id_casinos)//Necesario porque esta particionado... evitarle problemas al optimizador
    ->whereNull('ri.id_registros_dni_importacion')
    ->whereNotNull('rr.id_registros_dni_importacion')
    ->get()->pluck('id_registros_dni_importacion')
    ->toArray();//Saco duplicados por PHP para evitar complciar la query con distinct
        
    //Importaciones agregadas, no estan resumidas
    $ids_a_agregar = DB::table('registros_dni_importacion as ri')
    ->select('ri.id_registros_dni_importacion')->distinct()
    ->leftJoin('registros_dni_resumen as rr','rr.id_registros_dni_importacion','=','ri.id_registros_dni_importacion')
    ->whereNull('rr.id_registros_dni_importacion')
    ->get()->pluck('id_registros_dni_importacion')
    ->toArray();//Saco duplicados por PHP para evitar complciar la query con distinct
    
    if(count($ids_a_agregar)){
      $placeholders = implode(',', array_fill(0, count($ids_a_agregar), '?'));
      //Resumo cada importación por dia,hora,edad
      //Rara vez se necesita los registros individuales por segundo
      DB::statement("INSERT INTO registros_dni_resumen 
        (id_casino,id_registros_dni_importacion,dia,hora,edad,cantidad)
        SELECT 
          r.id_casino,
          r.id_registros_dni_importacion,
          DATE(r.timestamp),
          HOUR(r.timestamp),
          r.edad,
          COUNT(*)
        FROM registros_dni as r
        WHERE r.id_registros_dni_importacion IN ($placeholders)
        GROUP BY r.id_casino,r.id_registros_dni_importacion,DATE(r.timestamp),HOUR(r.timestamp),r.edad",
        $ids_a_agregar
      );
      
      //Uso el resumen anterior para calcular el total por dia-hora sin edad
      DB::statement("INSERT INTO registros_dni_resumen 
        (id_casino,id_registros_dni_importacion,dia,hora,edad,cantidad)
        SELECT 
          r.id_casino, 
          r.id_registros_dni_importacion, 
          r.dia, 
          r.hora, 
          NULL, 
          SUM(r.cantidad)
        FROM registros_dni_resumen as r
        WHERE r.id_registros_dni_importacion IN ($placeholders)
          AND r.dia  IS NOT NULL 
          AND r.hora IS NOT NULL 
          AND r.edad IS NOT NULL 
        GROUP BY r.id_casino, r.id_registros_dni_importacion, r.dia, r.hora",
        $ids_a_agregar
      );
      
      //Ahora por dia-edad sin hora
      DB::statement("INSERT INTO registros_dni_resumen 
        (id_casino,id_registros_dni_importacion,dia,hora,edad,cantidad)
        SELECT 
          r.id_casino, 
          r.id_registros_dni_importacion, 
          r.dia, 
          NULL, 
          r.edad, 
          SUM(r.cantidad)
        FROM registros_dni_resumen as r
        WHERE r.id_registros_dni_importacion IN ($placeholders)
          AND r.dia  IS NOT NULL 
          AND r.hora IS NOT NULL 
          AND r.edad IS NOT NULL
        GROUP BY r.id_casino, r.id_registros_dni_importacion, r.dia, r.edad",
        $ids_a_agregar
      );
      
      //Ahora por hora-edad sin dia
      DB::statement("INSERT INTO registros_dni_resumen 
        (id_casino,id_registros_dni_importacion,dia,hora,edad,cantidad)
        SELECT 
          r.id_casino, 
          r.id_registros_dni_importacion, 
          NULL, 
          r.hora, 
          r.edad, 
          SUM(r.cantidad)
        FROM registros_dni_resumen as r
        WHERE r.id_registros_dni_importacion IN ($placeholders)
          AND r.dia  IS NOT NULL 
          AND r.hora IS NOT NULL 
          AND r.edad IS NOT NULL
        GROUP BY r.id_casino, r.id_registros_dni_importacion, r.hora, r.edad",
        $ids_a_agregar
      );
      
      //Totalizaciones dobles
      
      //Total diario
      DB::statement("
        INSERT INTO registros_dni_resumen 
        (id_casino,id_registros_dni_importacion,dia,hora,edad,cantidad)
        SELECT 
          r.id_casino, 
          r.id_registros_dni_importacion, 
          r.dia, 
          NULL, 
          NULL, 
          SUM(r.cantidad)
        FROM registros_dni_resumen as r
        WHERE r.id_registros_dni_importacion IN ($placeholders)
          AND r.dia  IS NOT NULL 
          AND r.hora IS NOT NULL 
          AND r.edad IS NULL -- Uso los SIN EDAD como base, porque hay maximo 24 horas y un 1-2 semanas por importación, es la menor multiplicidad
        GROUP BY r.id_casino, r.id_registros_dni_importacion, r.dia",
        $ids_a_agregar
      );
      
      //Total por hora
      DB::statement("
        INSERT INTO registros_dni_resumen 
        (id_casino,id_registros_dni_importacion,dia,hora,edad,cantidad)
        SELECT 
          r.id_casino, 
          r.id_registros_dni_importacion, 
          NULL, 
          r.hora, 
          NULL, 
          SUM(r.cantidad)
        FROM registros_dni_resumen as r
        WHERE r.id_registros_dni_importacion IN ($placeholders)
          AND r.dia  IS NOT NULL 
          AND r.hora IS NOT NULL 
          AND r.edad IS NULL -- Idem
        GROUP BY r.id_casino, r.id_registros_dni_importacion, r.hora",
        $ids_a_agregar
      );
      
      DB::statement("
        INSERT INTO registros_dni_resumen 
        (id_casino,id_registros_dni_importacion,dia,hora,edad,cantidad)
        SELECT 
          r.id_casino, 
          r.id_registros_dni_importacion, 
          NULL, 
          NULL, 
          r.edad, 
          SUM(r.cantidad)
        FROM registros_dni_resumen as r
        WHERE r.id_registros_dni_importacion IN ($placeholders)
          AND r.dia  IS NOT NULL 
          AND r.hora IS NULL  -- No puedo usar los sin edad por que la necesito asi que uso los sin hora
          AND r.edad IS NOT NULL
        GROUP BY r.id_casino, r.id_registros_dni_importacion, r.edad",
        $ids_a_agregar
      );
      
      //Total de importación
      DB::statement("INSERT INTO registros_dni_resumen 
        (id_casino,id_registros_dni_importacion,dia,hora,edad,cantidad)
        SELECT 
          r.id_casino, 
          r.id_registros_dni_importacion, 
          NULL, 
          NULL, 
          NULL, 
          SUM(r.cantidad)
        FROM registros_dni_resumen as r
        WHERE r.id_registros_dni_importacion IN ($placeholders)
          AND r.dia  IS NOT NULL -- |dias| < |horas| < |edades| asi que me conviene agrupar usando los dias
          AND r.hora IS NULL 
          AND r.edad IS NULL
        GROUP BY r.id_casino, r.id_registros_dni_importacion",
        $ids_a_agregar
      );
      
      //Todos los totales los agrego al total del casino
      // El DUPLICATE KEY ocurre por resumen_hash
      // el UNIQUE KEY(id_casino,id...) no triggerea porque NULL != NULL
      // Para esto necesitamos el resumen_hash
      DB::statement("INSERT INTO registros_dni_resumen 
        (id_casino, id_registros_dni_importacion, dia, hora, edad, cantidad)
        SELECT 
          r.id_casino,
          NULL,
          r.dia,
          r.hora,
          r.edad,
          r.cantidad
        FROM registros_dni_resumen AS r
        WHERE r.id_registros_dni_importacion IN ($placeholders)
        ON DUPLICATE KEY UPDATE 
        registros_dni_resumen.cantidad = registros_dni_resumen.cantidad + VALUES(cantidad)",
        $ids_a_agregar
      );
    }
    
    if(count($ids_a_eliminar)){
      $placeholders = implode(',', array_fill(0, count($ids_a_eliminar), '?'));
      //Borro del total del casino restando
      //Tengo que rehashear la columna... podría guardarlo pero la verdad solo se usa cuando se borra
      DB::statement("
        UPDATE registros_dni_resumen as resumen_casino
        INNER JOIN registros_dni_resumen as resumen_importacion
          ON resumen_casino.resumen_hash = ".$this->resumen_hash([
            'resumen_importacion.id_casino',
            'NULL',
            'resumen_importacion.dia',
            'resumen_importacion.hora',
            'resumen_importacion.edad'
          ])."
        SET resumen_casino.cantidad = resumen_casino.cantidad - resumen_importacion.cantidad
        WHERE resumen_casino.id_registros_dni_importacion IS NULL
          AND resumen_importacion.id_registros_dni_importacion IN ($placeholders)", 
        $ids_a_eliminar
      );
      //Borro los que quedan en 0
      DB::statement("
        DELETE FROM registros_dni_resumen 
        WHERE id_registros_dni_importacion IS NULL AND cantidad <= 0"
      ); 
      DB::statement("
        DELETE FROM registros_dni_resumen 
        WHERE id_registros_dni_importacion IN ($placeholders)",
        $ids_a_eliminar
      );
    }
  }
  
  public function borrar_importacion(Request $request,$id_registros_dni_importacion){
    //@TODO: validar permisos
    return DB::transaction(function() use ($id_registros_dni_importacion){
      DB::statement("
        DELETE FROM registros_dni
        WHERE id_registros_dni_importacion = :id_registros_dni_importacion",
        compact('id_registros_dni_importacion')
      );
      DB::statement("
        DELETE FROM registros_dni_importacion
        WHERE id_registros_dni_importacion = :id_registros_dni_importacion",
        compact('id_registros_dni_importacion')
      );
      $this->recalcular_resumenes();
      return ['mensaje' => 'Importación borrada'];
    });
  }
}
