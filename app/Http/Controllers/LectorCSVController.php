<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;
use PDO;
use App\Maquina;
use App\Sector;
use App\Isla;
use App\ContadorHorario;
use App\Producido;
use App\Beneficio;
use App\DetalleContadorHorario;
use App\DetalleProducido;
use App\TipoMoneda;
use App\Http\Controllers\ContadorController;
use App\Http\Controllers\ProducidoController;
use App\Http\Controllers\BeneficioController;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class LectorCSVController extends Controller
{
  private static $instance;

  public static function getInstancia(){
    if (!isset(self::$instance)){
      self::$instance = new LectorCSVController();
    }
    return self::$instance;
  }

  public function cargaMasivaMaquinas(Request $request){
        $file= $request->file;
        $id_casino=$request->id_casino;
        $path =$file->getRealPath();
        $temporales = array();
        $errores=array();
        $bandera=true;
        $fichero = fopen($path, 'r');
        $row = fgetcsv($fichero,300,';','"'); //OBTENGO TITULOS DE COLUMNAS
        $indices = $this->reconocerColumnas($row);
        while($row = fgetcsv($fichero,300,';','"')){
                //seteo valores entidad
                $temporal = new Maquina;
                $temporal->id_casino = $id_casino;

                if(isset($indices['admin'])){
                  $temporal->nro_admin = $row[$indices['admin']];
                }else{
                  $errores[]='No se reconoció número de administración';
                }

                //ISLA
                if(isset($indices['isla'])){
                  $isla = IslaController::getInstancia()->encontrarOCrear($row[$indices['isla']] ,$id_casino); // por ahora no tiene sector la isla
                  $temporal->id_isla = $isla->id_isla;
                }else{
                  $errores[]='No se reconoció número de isla';
                }

                if(isset($indices['marca']) && isset($indices['modelo'])){
                  $temporal->marca = $row[$indices['marca']];
                  $temporal->modelo = $row[$indices['modelo']];
                }else if (isset($indices['marca_modelo'])){
                  $arreglo_separado_marca_modelo = $this->separarMarcaYModelo($row[$indices['marca_modelo']]);
                  if(isset($arreglo_separado_marca_modelo['marca'])){
                    $temporal->marca = $arreglo_separado_marca_modelo['marca'];
                    $temporal->modelo = $arreglo_separado_marca_modelo['modelo'];
                  }
                }else {
                  $errores[] = 'No se reconoció marca y modelo';
                }

                if(isset($indices['juego'])){
                  $juego = JuegoController::getInstancia()->encontrarOCrear($row[$indices['juego']]); // crea juego
                  $temporal->id_juego= $juego->id_juego;

                  $temporal->marca_juego =  MTMController::getInstancia()->abreviarMarca($temporal->marca) .' - '. $juego->nombre_juego;
                }else {
                  $errores[]= 'No se reconoció juego';
                }

                //UNIDAD_MEDIDA
                if(isset($indices['unidad_medida'])){
                  $unidad = isset($row['unidad_medida']) ? strtolower($row[$indices['unidad_medida']]) : 'credito';//si no viene, asumo crédito
                  if($unidad =='cred' || $unidad  =='creditos' ||  $unidad  =='créditos' || $unidad  =='credito' ||  $unidad  =='crédito'){
                    $temporal->id_unidad_medida=1;//crédito
                  }else {
                    $temporal->id_unidad_medida=2;//pesos
                  }
                }else {
                  $temporal->id_unidad_medida = 1;
                }

                //$temporal->denominacion = $row[7];
                if(isset($indices['denominacion'])){
                  $denominacion=str_replace("," , "." ,  $row[$indices['denominacion']]);
                  $temporal->denominacion = $denominacion;
                }else {//denominacion default
                  $temporal->denominacion = '0.01';
                }

                if(isset($indices['porcentaje_devolucion'])){
                  $auxiliar=explode("%", $row[$indices['porcentaje_devolucion']]);
                  if(isset($auxiliar[1])){
                    $porcentaje=$auxiliar[1];
                    $porcentaje=str_replace("," , "." ,  $porcentaje);
                  }else{
                    $porcentaje=$auxiliar[0];
                    $porcentaje=str_replace("," , "." ,  $porcentaje);
                  }
                  $temporal->porcentaje_devolucion = $porcentaje;
                }else {
                  $temporal->porcentaje_devolucion = 85;
                }

                $bandera=MTMController::getInstancia()->validarMaquinaTemporal($temporal, $id_casino);
                $total[] = $temporal;
        }
        //valido que todos las valores del archivo son validos.Si bandera=true no hubo error. pasan de tabla temporal a tabla real
        foreach ($total as $maquina){
          $maquina->save();
        }

        if(empty($errores)){
            MTMController::getInstancia()->guardarMaquinasTemporales($temporales ,$id_casino);
        }

        fclose($fichero);

        return ['codigo' => 200];
  }

  public function actualizaMaestroRosario(Request $request){
    //requerimiento borrar las islas del casino antes
    $file= $request->file;
    $id_casino=3;// casino rosario
    $path =$file->getRealPath();// obtengo direccion del archivo
    $fichero = fopen($path, 'r');//abro el archivo
    $row = fgetcsv($fichero,300,';','"'); // salteo la 1er linea
    $maquinas = array();
    //condiciones iniciales
    $row = fgetcsv($fichero,300,';','"');
    //para proxima maquian
    $nro_isla_anterior=$row[1];
    $id_sector_anterior=$row[2];

    $islas = Isla::where([['id_casino',3],['nro_isla',$row[1]]])->get();
    $array_id_sector= array();

    if($islas->count() == 1){
      $isla= $islas[0];
    }else {
      $isla= $islas[0];
      foreach ($islas as $una_isla) {
        $array_id_sector[]=$una_isla->id_sector;
      }
    }

    if($isla->id_sector == null){
      $isla->id_sector = $row[2];
      $isla->id_casino = $id_casino;
      $isla->save();
    }

    $arreglo = explode(" ",trim($row[5]));
    $marca  = $arreglo[0];
    if(isset($arreglo[1])){
      unset($arreglo[0]);
      $modelo = implode(" ", $arreglo);
    }else{
      $modelo =" ";
    }

    $maquinas[] = ['nro_admin' => $row[0],'id_sector' => $row[2],'marca' => $marca,'modelo' => $modelo,'juego' => $row[6],'unidad_medida' => $row[7],'denominacion' => $row[8],'denominacion_sala' => $row[12] , 'porcentaje_devolucion' => str_replace("%","",$row[9]),'nro_serie' => $row[11],'id_formula' => $row[10]]; // si no existe la maquina la creo

    while($row = fgetcsv($fichero,300,';','"')){
        if($nro_isla_anterior == $row[1] && $id_sector_anterior == $row[2]){//si no es igual la renglon anterior
          $arreglo = explode(" ",trim($row[5]));
          $marca  = $arreglo[0];
          if(isset($arreglo[1])){
            unset($arreglo[0]);
            $modelo = implode(" ", $arreglo);
          }else{
            $modelo =" ";
          }

          $maquinas[] = ['nro_admin' => $row[0],'id_sector' => $row[2],'marca' => $marca,'modelo' => $modelo,'juego' => $row[6],'unidad_medida' => $row[7],'denominacion' => $row[8],'denominacion_sala' => $row[12] , 'porcentaje_devolucion' => str_replace("%","",$row[9]),'nro_serie' => $row[11],'id_formula' => $row[10]]; // si no existe la maquina la creo

        }else{

          foreach ($maquinas as $maquina) {
              //busco la maquina, supongo que es de rosario
              $maquina_encontrada = Maquina::where([['nro_admin',$maquina['nro_admin']],['id_casino',3]])->first();

              if($maquina_encontrada != null){
                $maquina_encontrada->marca = $maquina['marca'];
                $maquina_encontrada->nro_serie = $maquina['nro_serie'];
                $maquina_encontrada->modelo = $maquina['modelo'];
                $maquina_encontrada->isla()->associate($isla->id_isla);
                $maquina_encontrada->id_formula = $maquina['id_formula'];
                $juego = JuegoController::getInstancia()->encontrarOCrear($maquina['juego']);
                $maquina_encontrada->id_juego = $juego->id_juego;
                $maquina_encontrada->save();
                $maquina_encontrada->juegos()->sync([$juego->id_juego => ['porcentaje_devolucion' => str_replace(",", "." , $maquina['porcentaje_devolucion']) , 'denominacion' => $maquina['denominacion_sala']]]);
              }else{
                $maquina_nueva = new Maquina;
                $maquina_nueva->nro_admin = $maquina['nro_admin'];
                $maquina_nueva->marca = $maquina['marca'];
                $maquina_nueva->modelo = $maquina['modelo'];
                $juego = JuegoController::getInstancia()->encontrarOCrear($maquina['juego']);
                $maquina_nueva->id_juego = $juego->id_juego;
                $maquina_nueva->nro_serie = $maquina['nro_serie'];
                $maquina_nueva->marca_juego = MTMController::getInstancia()->abreviarMarca($maquina['marca']) . ' - ' . $maquina['juego'];
                $maquina_nueva->id_estado_maquina = ($maquina['id_sector'] == 17) ? 4 : 1; // 4 -> egreso temporal , 1 -> ingreso
                $maquina_nueva->id_formula = $maquina['id_formula'];
                $maquina_nueva->id_isla = $isla->id_isla;
                $maquina_nueva->id_casino = 3;
                $maquina_nueva->save();
                $maquina_nueva->juegos()->sync([$juego->id_juego => [ 'porcentaje_devolucion' => str_replace(",", "." , $maquina['porcentaje_devolucion']), 'denominacion' => $maquina['denominacion_sala']]]);
              }
          }

          $maquinas= array();
          $isla->save();

          $arreglo = explode(" ",trim($row[5]));
          $marca  = $arreglo[0];
          if(isset($arreglo[1])){
            unset($arreglo[0]);
            $modelo = implode(" ", $arreglo);
          }else{
            $modelo =" ";
          }

          $maquinas[] = ['nro_admin' => $row[0],'id_sector' => $row[2],'marca' => $marca,'modelo' => $modelo,'juego' => $row[6],'unidad_medida' => $row[7],'denominacion' => $row[8],'denominacion_sala' => $row[12] , 'porcentaje_devolucion' => str_replace("%","",$row[9]),'nro_serie' => $row[11],'id_formula' => $row[10]]; // si no existe la maquina la creo

          //setear la nueva isla
          $islas = Isla::where([['id_casino',3],['nro_isla',$row[1]]])->get();
          $array_id_sector= array();

          if($islas->count() == 1 ){//1
            $isla = $islas[0];
          }else if($islas->count() > 1) {// > 1
            $isla = $islas[0];
            foreach ($islas as $una_isla) {
              $array_id_sector[]=$una_isla->id_sector;
            }
          }else{// 0
            $isla = new Isla;
            $isla->nro_isla= $row[1];
          }

          if($isla->id_sector == null){
            $isla->id_sector = $row[2];
            $isla->id_casino = $id_casino;
            $isla->save();
          }else if($isla->nro_isla == $row[1] && !in_array($row[2],$array_id_sector)){// si es distinto de null
            $isla->codigo = $isla->sector->descripcion;
            $isla->save();

            $isla_nueva = new Isla;
            $isla_nueva->codigo = Sector::find($row[2])->descripcion; //
            $isla_nueva->nro_isla = $row[1];
            $isla_nueva->id_sector = $row[2];
            $isla_nueva->id_casino = 3;
            $isla_nueva->save();
            $isla = $isla_nueva;
          }else{
            //nada
          }

        }
        $nro_isla_anterior=$row[1];
        $id_sector_anterior=$row[2];

    }//fin while

    //al final del while tengo que cargar las maquinas de la ultima isla
    //repite algoritmo de guardado de maquinas
    foreach ($maquinas as $maquina) {
        //busco la maquina, supongo que es de rosario
        $maquina_encontrada = Maquina::where([['nro_admin',$maquina['nro_admin']],['id_casino',3]])->first();

        if($maquina_encontrada != null){
          $maquina_encontrada->nro_serie = $maquina['nro_serie'];
          $maquina_encontrada->isla()->associate($isla->id_isla);
          $maquina_encontrada->id_formula = $maquina['id_formula'];
          $maquina_encontrada->modelo = $maquina['modelo'];
          $maquina_encontrada->marca = $maquina['marca'];
          $juego = JuegoController::getInstancia()->encontrarOCrear($maquina['juego']);
          $maquina_encontrada->id_juego = $juego->id_juego;
          $maquina_encontrada->save();
          $maquina_encontrada->juegos()->sync([$juego->id_juego => [ 'porcentaje_devolucion' => str_replace(",", "." , $maquina['porcentaje_devolucion']) , 'denominacion' => $maquina['denominacion_sala']]]);
        }else{
          $maquina_nueva = new Maquina;
          $maquina_nueva->nro_admin  = $maquina['nro_admin'];
          $maquina_nueva->marca = $maquina['marca'];
          $maquina_nueva->modelo = $maquina['modelo'];
          $juego = JuegoController::getInstancia()->encontrarOCrear($maquina['juego']);
          $maquina_nueva->id_juego = $juego->id_juego;
          $maquina_nueva->nro_serie = $maquina['nro_serie'];
          $maquina_nueva->marca_juego = MTMController::getInstancia()->abreviarMarca($maquina['marca']) . ' - ' . $maquina['juego'];
          $maquina_nueva->id_estado_maquina = ($maquina['id_sector'] == 17) ? 4 : 1; // 4 -> egreso temporal , 1 -> ingreso
          $maquina_nueva->id_formula = $maquina['id_formula'];
          $maquina_nueva->id_isla = $isla->id_isla;
          $maquina_nueva->id_casino = 3;
          $maquina_nueva->save();
          $maquina_nueva->juegos()->sync([$juego->id_juego => [ 'porcentaje_devolucion' => str_replace(",", "." , $maquina['porcentaje_devolucion']) , 'denominacion' => $maquina['denominacion_sala']]]);
        }
    }

  }

  public function actualizarMaestroMelincue(Request $request){

    $file= $request->file;
    $id_casino=1;
    $path =$file->getRealPath();
    $temporales = Array();
    $errores=0;
    $bandera=true;
    $fichero = fopen($path, 'r');
    $row = fgetcsv($fichero,300,';','"'); // salteo la 1er linea
    while($row = fgetcsv($fichero,300,';','"')){
            //seteo valores entidad
            $temporal = new Maquina;
            $temporal->id_casino = 1;
            $temporal->nro_admin = $row[0];
            // $temporal->nro_isla=$row[1];
            $isla = IslaController::getInstancia()->encontrarOCrear($row[1] ,$id_casino); // por ahora no tiene sector la isla
            $isla->id_sector = 11;
            $isla->save();

            $temporal->id_isla = $isla->id_isla;

            $temporal->marca = $row[2];

            $temporal->modelo = $row[6];

            $temporal->nro_serie = $row[8];

            $denominacion=0.01;

            $temporal->denominacion = $denominacion;

            $juego = JuegoController::getInstancia()->encontrarOCrear($row[3]); // crea juego

            $temporal->id_juego= $juego->id_juego;

            $temporal->marca_juego =  MTMController::getInstancia()->abreviarMarca($row[3]) .' - '. $row[5];

            // $temporal->unidad_medida = $row[6];
            if(strtolower($row[10])=='cred' || strtolower($row[10]) =='creditos' ||  strtolower($row[10]) =='créditos'){
              $temporal->id_unidad_medida=1;//credito
            }else {
              $temporal->id_unidad_medida=2;//pesos
            }

            $temporal->id_formula = $row[18];

            $temporal->save();

            $temporal->juegos()->syncWithoutDetaching([$juego->id_juego => ['denominacion' => $row[9]]]);

    }


    fclose($fichero);
    return ['codigo' => 200];

  }

  public function auxiliarNiveles($row){
      $niveles = array();
      $nivel = new NivelProgresivo;//nivel 1
      $nivel->nombre_nivel = $row[8];
      $nivel->porc_oculto =  $row[9];
      $nivel->porc_visible = $row[10];
      $nivel->save();
      $niveles[]= $nivel;
      $nivel = new NivelProgresivo;//nivel 2
      $nivel->nombre_nivel = $row[12];
      $nivel->porc_oculto =  $row[13];
      $nivel->porc_visible = $row[14];
      $nivel->save();
      $niveles[]= $nivel;
      $nivel = new NivelProgresivo;//nivel 3
      $nivel->nombre_nivel = $row[16];
      $nivel->porc_oculto =  $row[17];
      $nivel->porc_visible = $row[18];
      $nivel->save();
      $niveles[]= $nivel;
      $nivel = new NivelProgresivo;//nivel 4
      $nivel->nombre_nivel = $row[20];
      $nivel->porc_oculto =  $row[21];
      $nivel->porc_visible = $row[22];
      $nivel->save();
      $niveles[]= $nivel;
      $nivel = new NivelProgresivo;//nivel 5
      $nivel->nombre_nivel = $row[24];
      $nivel->porc_oculto =  $row[25];
      $nivel->porc_visible = $row[26];
      $nivel->save();
      $niveles[]= $nivel;
      $nivel = new NivelProgresivo;//nivel
      $nivel->nombre_nivel = $row[28];
      $nivel->porc_oculto =  $row[29];
      $nivel->porc_visible = $row[30];
      $nivel->save();
      $niveles[]= $nivel;
      return $niveles;
  }

  public function cargaMasivaProgresivos(Request $request){// IMPORTADOR PROGRESIVO
        $file= $request->file;
        $id_casino=$request->id_casino;
        $path =$file->getRealPath();
        $casinos=[2];
        $fichero = fopen($path, 'r');
        $row = fgetcsv($fichero,300,';','"'); // salteo la 1er linea

        $row = fgetcsv($fichero,300,';','"'); // segunda linea

        $nombre_juego_anterior = '';
        $isla_anterior='';

        $nombres = array();
        $maquinas = array();
        $pozos = array();

        $progresivo = new Progresivo();
        $progresivo->maximo = $row[7];
        $progresivo->save(); //guardo para obetener id
        $niveles = $this->auxiliarNiveles($row,$progresivo->id_progresivo);

        if($row[2] == 'LINK'){//linkeado crea pozo y asosia parte de las maquinas

          $progresivo->linkeado = $row[2] == 'LINK' ? 1 : 0;
          $pozo = new Pozo;
          $pozo->base = $row[11];
          $pozo->save();
          $maquinas[] = $row[2];
        }else{
          $progresivo->individual = $row[2] == 'LINK' ? 0 : 1;
        }


        if($row[5] != 'N'){
          $progresivo->nombre_progresivo = $row[5];
        }else if($row[6] != $nombre_juego_anterior && !isset($progresivo->nombre_progresivo)){
          $nombres[] = $row[6];
          $nombre_juego_anterior = $row[6];
        }

        while($row = fgetcsv($fichero,300,';','"')){

          if($row[6] != $nombre_juego_anterior && $row[0] != $isla_anterior && $row[2] != $tipo_anterior){//si cambio de isla y juego es otro progresivo
            //guardo el anterior
            $progresivo->save();
            foreach ($maquinas as $id_maq) {
                $maquina = Maquina::find($id_maq);
                $maquina->pozo()->associate($pozo->id_pozo);
                $maquina->save();
            }


            foreach($pozos as $pozo){
              $pozo->save();
              foreach($maquinas as $id_maquina){
                $maquina = Maquina::find($id_maquina);
                $maquina->pozo()->associate($pozo);
                $maquina->save();
              }
              $pozo->niveles_progresivo()->sync($niveles);
            }

            //empiezo de nuevo
             $nombre_juego_anterior = $row[6];
             $isla_anterior = $row[0];
             $maquinas = array();

             $progresivo = new Progresivo();
             $progresivo->maximo = $row[7];
             $progresivo->individual = $row[2] == 'LINK' ? 0 : 1;
             $progresivo->linkeado = $row[2] == 'LINK' ? 1 : 0;

          }else if($row[0] != $isla_anterior){
            if($row[2] == 'LINK'){
              $maquinas [] = $row[1];
            }else{
              $pozo = new \stdClass();
              $pozo->maquinas = $maquinas;
            }

          }

          $juego_anterior = $row[0];
          $isla_anterior = $row[6];
          $tipo_anterior = $row[2];
        }//fin while -- termina de leer
        fclose($fichero);
        return 'fin lector';
  }
  // importarContadorSantaFeMelincue crea un nuevo contador horario
  // carga en una tabla temporal los datos del archivo csv
  // al temporal lo opera para para tomar los ultimos datos de los contadores
  // genera un join con las maquinas para tener valores de maquinas que existan en el maestro de mtm
  // y con esto se va agregado en los detalles_contadores
  // luego elimina los temporales
  public function importarContadorSantaFeMelincue($archivoCSV,$casino){

    $contador = new ContadorHorario;
    $contador->id_casino = $casino;
    $contador->cerrado = 0;
    $contador->save();

    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();
    $path = $archivoCSV->getRealPath();

    $query = sprintf("LOAD DATA local INFILE '%s'
                      INTO TABLE contadores_temporal
                      FIELDS TERMINATED BY ';'
                      OPTIONALLY ENCLOSED BY '\"'
                      ESCAPED BY '\"'
                      LINES TERMINATED BY '\\n'
                      IGNORE 1 LINES
                      (@0,@1,@2,@3,@4,@5,@6,@7,@8,@9,@10,@11,@12,@13,@14,@15,@16)
                      SET id_contador_horario = '%d',
                                      horario = STR_TO_DATE(@0,'%s'),
                                      tipo = @1,
                                      maquina = @3,
                                      coinin = CAST(REPLACE(@6,',','.') as DECIMAL(15,2)),
                                      coinout = CAST(REPLACE(@8,',','.') as DECIMAL(15,2)),
                                      jackpot = CAST(REPLACE(@12,',','.') as DECIMAL(15,2)),
                                      fecha = STR_TO_DATE(@16,'%s')
                      ",$path,$contador->id_contador_horario,"%d/%m/%Y %H:%i:%s","%d/%m/%Y");

    $pdo->exec($query);

    $cont_temporal = DB::table('contadores_temporal')->where('id_contador_horario','=',$contador->id_contador_horario)->first();
    $contador->fecha = date('Y-m-d' , strtotime($cont_temporal->fecha  . ' + 1 days'));
    $contador->save();
    //se esta volviendo a validar, algo que se hizo en importacion controller, se anula ya que esto lo verifica el controlador superior
    /*
    $contador_cerrado = ContadorHorario::where([['id_casino','=',$casino],['fecha','=',$contador->fecha],['cerrado','=',1]])->count();
    if($contador_cerrado > 0){
      $query = sprintf(" DELETE FROM contadores_temporal WHERE id_contador_horario = '%d'",$contador->id_contador_horario);
      $pdo->exec($query);
      $contador->delete();
      Validator::make($contador_cerrado,[
          'contador_cerrado' => 'required|integer',
      ], array(), self::$atributos)->after(function($validator){
          if($validator->getData()['contador_cerrado'] > 0){
              $validator->errors()->add('contador_cerrado','El Contador para esa fecha ya está cerrado y no se puede reimportar.');
          }
      })->validate();
    }*/

    $contadores = DB::table('contador_horario')->where([['id_contador_horario','<>',$contador->id_contador_horario],['id_casino','=',$casino],['fecha','=',$contador->fecha]])->get();
    if($contadores != null){
      foreach($contadores as $cont){
        $query = sprintf(" DELETE FROM detalle_contador_horario
                           WHERE id_contador_horario = '%d'
                           ",$cont->id_contador_horario);
        $pdo->exec($query);

        $query = sprintf(" DELETE FROM contador_horario
                           WHERE id_contador_horario = '%d'
                           ",$cont->id_contador_horario);
        $pdo->exec($query);
      }
    }
    //obtener mtm e ir insertando en detalle contador horario

    //cambiar sentencia para actualizar los campos de contadores donde la mtm y el id contador sean iguales

    $query = sprintf(" INSERT INTO detalle_contador_horario (coinin,coinout,jackpot,id_maquina,id_contador_horario)
                       SELECT ct.coinin, ct.coinout, ct.jackpot, mtm.id_maquina, ct.id_contador_horario
                       FROM contadores_temporal AS ct, maquina AS mtm,
                            (SELECT MAX(horario) AS horario, maquina
                             FROM contadores_temporal
                             WHERE id_contador_horario = '%d'
                             GROUP BY maquina) AS ct_a
                       WHERE ct.id_contador_horario = '%d'
                         AND ct_a.maquina = ct.maquina
                         AND ct_a.horario = ct.horario
                         AND ct.tipo IN ('AJU','')
                         AND ct.maquina = mtm.nro_admin
                         AND mtm.id_casino = '%d'
                       ",$contador->id_contador_horario,$contador->id_contador_horario,$casino);

    $pdo->exec($query);

    $query = sprintf(" DELETE FROM contadores_temporal
                       WHERE id_contador_horario = '%d'
                       ",$contador->id_contador_horario);

    $pdo->exec($query);

    $pdo=null;

    $cantidad_registros = DetalleContadorHorario::where('id_contador_horario','=',$contador->id_contador_horario)->count();

    return ['id_contador_horario' => $contador->id_contador_horario,'fecha' => $contador->fecha,'casino' => $contador->casino->nombre,'cantidad_registros' => $cantidad_registros,'tipo_moneda' => ContadorHorario::find($contador->id_contador_horario)->tipo_moneda->descripcion];
  }

  // importarProducidoSantaFeMelincue crea nuevo producido
  // inserta en una tabla temporal , formateando a valores validos
  // luego toma esta tabla , hace un join con maquinas para tomar solo las mtm del maestro validas
  // y va generando los detalles producidos
  // es posbile que en el archivo no envien mtm (diversos motivos) en ese caso se fuerza a que tenga
  // producido 0 y se genera un log en el archivo de producido
  // como santa fe y melincue tiene en el archivo el beneficio en su ultima linea
  // tambien se crea el archivo de beneficio
  public function importarProducidoSantaFeMelincue($archivoCSV,$casino){

    $producido = new Producido;
    $producido->id_casino = $casino;
    $producido->validado = 0;
    $producido->save();

    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();
    $path = $archivoCSV->getRealPath();

    $query = sprintf("LOAD DATA local INFILE '%s'
                      INTO TABLE producido_temporal
                      FIELDS TERMINATED BY ';'
                      OPTIONALLY ENCLOSED BY '\"'
                      ESCAPED BY '\"'
                      LINES STARTING BY '%d' TERMINATED BY '\\n'
                      (@0,@1,@2,@3,@4,@5,@6,@7,@8,@9,@10,@11,@12,@13,@14,@15,@16,@17,@18,@19,@20,@21,@22,@23,@24,@25,@26,@27,@28,@29,@30,@31)
                       SET id_producido = '%d',
                                maquina = CAST(@1 as UNSIGNED),
                                  fecha = STR_TO_DATE(@2,'%s'),
                                  valor = CAST(REPLACE(@11,',','.') as DECIMAL(15,2))
                      ",$path,$casino,$producido->id_producido,"%Y%m%d");

    $pdo->exec($query);

    $prod_temp = DB::table('producido_temporal')->where('id_producido','=',$producido->id_producido)->first();
    $producido->fecha = $prod_temp->fecha;
    $producido->save();
    $producido_validado = Producido::where([['id_casino','=',$casino],['fecha','=',$producido->fecha],['validado','=',1]])->count();

    //despues de haberlo creado se pregunta si npodia cearlo y sino podia, vuelve atras borrando las tablas que recien armo
    if($producido_validado > 0){
      $query = sprintf(" DELETE FROM producido_temporal WHERE id_producido = '%d'",$producido->id_producido);
      $pdo->exec($query);
      $producido->delete();
      Validator::make($producido_validado,[
          'producido_validado' => 'required|integer',
      ], array(), self::$atributos)->after(function($validator){
          if($validator->getData()['producido_validado'] > 0){
              $validator->errors()->add('producido_validado','El Producido para esa fecha ya está validado y no se puede reimportar.');
          }
      })->validate();
    }
    //si ya hay producidos para esa fecha pero aun no esta validado primero borra todos los detalles producido y luego el producido
    $producidos = DB::table('producido')->where([['id_producido','<>',$producido->id_producido],['id_casino','=',$casino],['fecha','=',$producido->fecha]])->get();
    if($producidos != null){
      foreach($producidos as $prod){
        $query = sprintf(" DELETE FROM detalle_producido
                           WHERE id_producido = '%d'
                           ",$prod->id_producido);
        $pdo->exec($query);

        $query = sprintf(" DELETE FROM producido
                           WHERE id_producido = '%d'
                           ",$prod->id_producido);
        $pdo->exec($query);
      }
    }

    $query = sprintf(" INSERT INTO detalle_producido (valor,id_maquina,id_producido)
                       SELECT prod_a.valor,mtm.id_maquina,'%d'
                       FROM maquina AS mtm,(SELECT SUM(valor) AS valor, maquina
                                            FROM producido_temporal
                                            WHERE id_producido = '%d'
                                            GROUP BY maquina) AS prod_a

                       WHERE prod_a.maquina = mtm.nro_admin
                         AND mtm.deleted_at IS NULL
                         AND mtm.id_casino = '%d'
                       ",$producido->id_producido,$producido->id_producido,$casino);

    $pdo->exec($query);

    $query = sprintf(" DELETE FROM producido_temporal
                       WHERE id_producido = '%d'
                       ",$producido->id_producido);

    $pdo->exec($query);

    $cantidad_maquinas = Maquina::where('id_casino','=',$casino)->whereHas('estado_maquina',function($q){
                                  $q->where('descripcion','=','Ingreso')->orWhere('descripcion','=','ReIngreso');})->count();

    $query = sprintf("LOAD DATA local INFILE '%s'
                      INTO TABLE beneficio
                      FIELDS TERMINATED BY ';'
                      OPTIONALLY ENCLOSED BY '\"'
                      ESCAPED BY '\"'
                      LINES STARTING BY 'CTR' TERMINATED BY '\\n'
                      (@0,@1,@2,@3,@4,@5,@6,@7,@8,@9,@10,@11,@12,@13)
                      SET id_casino = '%d',
                              fecha = STR_TO_DATE('%s','%s'),
                             coinin = CAST(REPLACE(@4,',','.') as DECIMAL(15,2)),
                            coinout = CAST(REPLACE(@5,',','.') as DECIMAL(15,2)),
                            jackpot = CAST(REPLACE(@6,',','.') as DECIMAL(15,2)),
                              valor = CAST(REPLACE(@9,',','.') as DECIMAL(15,2)),
              porcentaje_devolucion = 100*(CAST(REPLACE(@5,',','.') as DECIMAL(15,2)) + CAST(REPLACE(@6,',','.') as DECIMAL(15,2)))/(CAST(REPLACE(@4,',','.') as DECIMAL(15,2))),
                  cantidad_maquinas = '%d',
               promedio_por_maquina = CAST(REPLACE(@9,',','.') as DECIMAL(15,2))/'%d'
                      ",$path,$casino,$producido->fecha,"%Y-%m-%d",$cantidad_maquinas,$cantidad_maquinas);

    $pdo->exec($query);

    $ben = Beneficio::find(DB::table('beneficio')->max('id_beneficio'));
    if($ben != null){
      $beneficios = Beneficio::where([['id_beneficio','<>',$ben->id_beneficio],['id_casino','=',$casino],['fecha','=',$ben->fecha]])->get();
      if($beneficios != null){
          foreach($beneficios as $beneficio){
            $query = sprintf(" DELETE FROM beneficio
                               WHERE id_beneficio = '%d'
                               ",$beneficio->id_beneficio);
            $pdo->exec($query);
          }
      }
    }

    $pdo=null;

    $cantidad_registros = DetalleProducido::where('id_producido','=',$producido->id_producido)->count();
    // implementacion para contemplar los casos donde ciertas maquinas no reportan producido
    $mtms= Maquina::select("id_maquina","nro_admin")
                    ->where("id_casino","=",$casino)
                    ->whereNull("deleted_at")
                    ->whereIn("id_estado_maquina",[1,2,4,5,7])
                    ->get();
    $cant_mtm_forzadas=0;
    $id_mtm_forzadas=array();
    foreach($mtms as $m){
      $cant=DetalleProducido::where("id_maquina","=",$m->id_maquina)
                            ->where("id_producido","=", $producido->id_producido)
                            ->count();

      if(!$cant){
        $daux= new DetalleProducido;
        $daux->valor=0;
        $daux->id_maquina=$m->id_maquina;
        $daux->id_producido=$producido->id_producido;
        $daux->save();
        $cant_mtm_forzadas=$cant_mtm_forzadas+1;
        array_push($id_mtm_forzadas,$m->id_maquina);
      }
    }
    $producido->cant_mtm_forzadas=$cant_mtm_forzadas;
    $producido->id_mtm_forzadas=implode(",",$id_mtm_forzadas);
    $producido->save();
  //fin de implementacion
    return ['id_producido' => $producido->id_producido,'fecha' => $producido->fecha,'casino' => $producido->casino->nombre,'cantidad_registros' => $cantidad_registros,'tipo_moneda' => Producido::find($producido->id_producido)->tipo_moneda->descripcion, 'cant_mtm_forzadas' => $cant_mtm_forzadas];
  }
  // importarBeneficioSantaFeMelincue se crea temporal insertando todos los valores del csv
  // solo se toma la linea de beneficio para insertar en la tabla real
  // luego se elimina los temporales
  public function importarBeneficioSantaFeMelincue($archivoCSV,$casino){

    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();
    $path = $archivoCSV->getRealPath();

    $cantidad_maquinas = Maquina::where('id_casino','=',$casino)->whereHas('estado_maquina',function($q){
                                   $q->where('descripcion','=','Ingreso')->orWhere('descripcion','=','ReIngreso');})->count();

    $query = sprintf("LOAD DATA local INFILE '%s'
                      INTO TABLE beneficio
                      FIELDS TERMINATED BY ';'
                      OPTIONALLY ENCLOSED BY '\"'
                      ESCAPED BY '\"'
                      LINES STARTING BY 'CTR' TERMINATED BY '\\n'
                      (@0,@1,@2,@3,@4,@5,@6,@7,@8,@9,@10,@11,@12,@13)
                      SET id_casino = '%d',
                              fecha = STR_TO_DATE(SUBSTRING(@1,5,8),'%s'),
                             coinin = CAST(REPLACE(@4,',','.') as DECIMAL(15,2)),
                            coinout = CAST(REPLACE(@5,',','.') as DECIMAL(15,2)),
                            jackpot = CAST(REPLACE(@6,',','.') as DECIMAL(15,2)),
                              valor = CAST(REPLACE(@9,',','.') as DECIMAL(15,2)),
              porcentaje_devolucion = 100*(CAST(REPLACE(@5,',','.') as DECIMAL(15,2)) + CAST(REPLACE(@6,',','.') as DECIMAL(15,2)))/(CAST(REPLACE(@4,',','.') as DECIMAL(15,2))),
                  cantidad_maquinas = '%d',
               promedio_por_maquina = CAST(REPLACE(@9,',','.') as DECIMAL(15,2))/'%d'
                      ",$path,$casino,"%Y%m%d",$cantidad_maquinas,$cantidad_maquinas);

    $pdo->exec($query);
    //usar query en vez de exec

    $ben = Beneficio::find(DB::table('beneficio')->max('id_beneficio'));
    if($ben != null){
      $fecha=explode("-", $ben->fecha);
      $beneficios = Beneficio::where([['id_beneficio','<>',$ben->id_beneficio],['id_casino','=',$casino]])
                              ->whereYear('fecha','=',$fecha[0])
                              ->whereMonth('fecha','=', $fecha[1])
                              ->get();
      //['fecha','=',$ben->fecha]])->get();
      if($beneficios != null){
        foreach($beneficios as $beneficio){
          $query = sprintf(" DELETE FROM beneficio
                             WHERE id_beneficio = '%d'
                             ",$beneficio->id_beneficio);
          $pdo->exec($query);
        }
      }
    }

    $pdo=null;


    return ['id_beneficio' => $ben->id_beneficio,'fecha' => $ben->fecha,'casino' => $ben->casino->nombre,'tipo_moneda' => $ben->tipo_moneda->descripcion];
  }
  // importarContadorRosario misma metodologia que en santa fe, se tiene en cuenta el formato
  // de archivo de rosario y que tienen distintos tipos de moneda
  // se tiene en cuenta la denominacion de carga, esto permite realziar las transformaciones de
  // creadito a plata, esta denominacion la toma del maestro de maquinas
  // se deja de manera estatica la denominacion que se tomo al momento de cargar
  public function importarContadorRosario($archivoCSV,$fecha,$id_tipo_moneda){

        $contador = new ContadorHorario;
        $contador->id_casino = 3;
        $contador->cerrado = 0;
        $contador->fecha = $fecha;
        $contador->id_tipo_moneda = $id_tipo_moneda;
        $contador->save();

        $pdo = DB::connection('mysql')->getPdo();
        DB::connection()->disableQueryLog();
        $contadores = DB::table('contador_horario')->where([['id_contador_horario','<>',$contador->id_contador_horario]
                                                           ,['id_casino','=',3]
                                                           ,['fecha','=',$contador->fecha]
                                                           ,['id_tipo_moneda','=',$contador->id_tipo_moneda]])->get();
        if($contadores != null){
          foreach($contadores as $cont){
            $query = sprintf(" DELETE FROM detalle_contador_horario
                               WHERE id_contador_horario = '%d'
                               ",$cont->id_contador_horario);
            $pdo->exec($query);

            $query = sprintf(" DELETE FROM contador_horario
                               WHERE id_contador_horario = '%d'
                               ",$cont->id_contador_horario);
            $pdo->exec($query);
          }
        }

        $path = $archivoCSV->getRealPath();

        $query = sprintf("LOAD DATA local INFILE '%s'
                          INTO TABLE contadores_temporal
                          FIELDS TERMINATED BY ';'
                          OPTIONALLY ENCLOSED BY '\"'
                          ESCAPED BY '\"'
                          LINES TERMINATED BY '\\n'
                          IGNORE 2 LINES
                          (@0,@1,@2,@3,@4,@5,@6,@7,@8,@9,@10,@11,@12,@13,@14,@15)
                          SET id_contador_horario = '%d',
                                          maquina = SUBSTRING(@1,1,4),
                                           coinin = CAST(REPLACE(@2,',','.') as DECIMAL(15,2)),
                                          coinout = CAST(REPLACE(@3,',','.') as DECIMAL(15,2)),
                                          jackpot = CAST(REPLACE(@14,',','.') as DECIMAL(15,2)),
                                       progresivo = CAST(REPLACE(@15,',','.') as DECIMAL(15,2))
                          ",$path,$contador->id_contador_horario);

        $pdo->exec($query);

        //Borro la ultima fila porque carga el "Total" como 0,0,0,0
        $last_id = DB::select("SELECT max(id_contadores_temporal) as max FROM contadores_temporal");
        $last_id = $last_id[0]->max;
        DB::table('contadores_temporal')->where('id_contadores_temporal','=',$last_id)->delete();


        $query = sprintf(" INSERT INTO detalle_contador_horario (coinin,coinout,jackpot,progresivo,id_maquina,id_contador_horario,denominacion_carga)
                           SELECT ct.coinin * mtm.denominacion, ct.coinout * mtm.denominacion, ct.jackpot * mtm.denominacion,
                                  ct.progresivo * mtm.denominacion, mtm.id_maquina, ct.id_contador_horario, mtm.denominacion
                           FROM contadores_temporal AS ct 
                           RIGHT JOIN maquina AS mtm ON ct.maquina = mtm.nro_admin
                           WHERE ct.id_contador_horario = '%d'
                             AND ct.maquina = mtm.nro_admin
                             AND mtm.id_casino = 3
                           ",$contador->id_contador_horario);

        $pdo->exec($query);

        
        //Maquinas que no encontro en la BD pero estaban en el archivo de contadores.
        $no_encontro_q = sprintf(
        "SELECT largo.maquina as maquina
        FROM
          (SELECT distinct
          ct.maquina
          FROM contadores_temporal AS ct 
          LEFT JOIN maquina mtm on (ct.maquina = mtm.nro_admin and mtm.id_casino = %d)
          WHERE ct.id_contador_horario = %d) 
        as largo
        LEFT JOIN
          (SELECT distinct
          ct.maquina
          FROM contadores_temporal AS ct 
          JOIN maquina mtm on (ct.maquina = mtm.nro_admin and mtm.id_casino = %d)
          WHERE ct.id_contador_horario = %d) 
        as corto on (largo.maquina = corto.maquina)
        WHERE corto.maquina IS NULL",3,$contador->id_contador_horario,3,$contador->id_contador_horario);

        $no_encontro = DB::select($no_encontro_q);

        $query = sprintf(" DELETE FROM contadores_temporal
                           WHERE id_contador_horario = '%d'
                           ",$contador->id_contador_horario);
        $pdo->exec($query);

        $cantidad_registros = DetalleContadorHorario::where('id_contador_horario','=',$contador->id_contador_horario)->count();

        $pdo=null;

        return [
          'id_contador_horario' => $contador->id_contador_horario,
          'fecha' => $contador->fecha,
          'casino' => $contador->casino->nombre,
          'cantidad_registros' => $cantidad_registros,
          'tipo_moneda' => ContadorHorario::find($contador->id_contador_horario)->tipo_moneda->descripcion,
          'no_encontro' => $no_encontro];
  }
  // importarProducidoRosario se inserta la informacion en una tabla temporal, formateando lo necesario
  // se consiera el tipo de moneda generar el formato
  // luego se realiza el join con mtm para importar producidos en maquinas que existan en el maestro y sean validas
  // se considera la posibilidad que en el archivo no se envien reportes de mtm (diversos motivos)
  // en ese caso se fuerza el valor de producido a cero y se genera un log de las maquinas que no reportaron
  public function importarProducidoRosario($archivoCSV,$fecha,$id_tipo_moneda){
    $producido = new Producido;
    $producido->id_casino = 3;
    $producido->validado = 0;
    $producido->fecha = $fecha;

    $producido->id_tipo_moneda = $id_tipo_moneda;
    $producido->save();

    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();
    $producidos = DB::table('producido')->where([['id_producido','<>',$producido->id_producido],['id_casino','=',3],['fecha','=',$producido->fecha],['id_tipo_moneda',$id_tipo_moneda]])->get();
    if($producidos != null){
      foreach($producidos as $prod){
        $query = sprintf(" DELETE FROM detalle_producido
                           WHERE id_producido = '%d'
                           ",$prod->id_producido);
        $pdo->exec($query);

        $query = sprintf(" DELETE FROM producido
                           WHERE id_producido = '%d'
                           ",$prod->id_producido);
        $pdo->exec($query);
      }
    }


    $path = $archivoCSV->getRealPath();

    // Dependiendo del archivo a importar, se ignora cierta candidad de lineas
    if ($id_tipo_moneda==1){
      // moneda en pesos
      $linIgnore=5;
    }else{
      // moneda en dolares
      $linIgnore=2;
    }
    {//FORMA NUEVA
      $skip_next = false;
      $fd = fopen($path,"r");
      $arreglo_a_insertar = [];
      if($fd){
        $nline = 0;
        while (($line = fgets($fd)) !== false) {
          if($linIgnore>=0){
            $linIgnore--;
            continue;
          }
          if(substr($line,0,5)=="Total"){
            $skip_next = true;
            continue;
          }
          if($skip_next){
            $skip_next = false;
            continue;
          }
          if(substr($line,0,9)=="Promedios"){
            continue;
          }
          if(substr($line,0,7)=="Maximos"){
            continue;
          }
          if(substr($line,0,7)=="Minimos"){
            continue;
          }
          $campos = explode(";",$line);
          if(count($campos)!=5){
            $errstr = "Error al parsear linea ".($nline+1);
            return ['errores' => [$errstr]];
          }

          $denom = $campos[0]; //No se usa
          $maquina = substr($campos[1],0,strlen($campos[1])-2);//Le saco los ultimos dos caracteres
          $ubicacion = $campos[2];//No se usa
          $periodo = $campos[3];//No se usa
          $total = trim($campos[4]);//Le saco el fin de linea

          if($maquina == "99990"){
            continue;//Ignoro maquina especial
          }

          if($periodo!=$total){
            //Deberian ser iguales.
            $errstr = "Error al parsear linea ".($nline+1);
            $errstr .= "\nPeriodo difiere del total";
            $errstr .= "\nDenom ".$denom;
            $errstr .= "\nMaquina ".$maquina;
            $errstr .= "\nUbicacion ".$ubicacion;
            $errstr .= "\nPeriodo ".$periodo;
            $errstr .= "\nTotal ".$total;
            return ['errores' => [$errstr]];
          }
          $total = str_replace('.','',$total); //Le saco el separador de los miles
          $total = str_replace(',','.',$total); //Cambio la coma por el punto
          $total = floatval($total);//Parseo el numero
          $arreglo_a_insertar[] = [
            "valor" => $total,
            "maquina"=>$maquina,
            "id_producido"=>$producido->id_producido,
            "fecha"=>$fecha
          ];
          $nline++;
        }
        fclose($fd);
      }
      else{
        return ['errores'=>["Fallo al abrir el archivo ".$path]];
      }

      DB::table('producido_temporal')->insert($arreglo_a_insertar); 
    } //FIN FORMA NUEVA
    
    /* FORMA VIEJA
    {
      $query = sprintf("LOAD DATA local INFILE '%s'
                        INTO TABLE producido_temporal
                        CHARACTER SET latin1
                        FIELDS TERMINATED BY ';'
                        OPTIONALLY ENCLOSED BY '\"'
                        ESCAPED BY '\"'
                        LINES TERMINATED BY '\\n'
                        IGNORE %d LINES
                        (@0,@1,@2,@3,@4)
                        SET id_producido = '%d',
                                  maquina = SUBSTRING(@1,1,4),
                                    valor = CAST(REPLACE(REPLACE(@4,'.',''),',','.') as DECIMAL(15,2))
                        ",$path,$linIgnore,$producido->id_producido);

      $pdo->exec($query);
    }*/
    
    $query = sprintf("INSERT INTO detalle_producido (valor,id_maquina,id_producido)
                      SELECT prod.valor, mtm.id_maquina, prod.id_producido
                      FROM producido_temporal AS prod
                      JOIN maquina as mtm on (mtm.nro_admin = prod.maquina and mtm.deleted_at IS NULL and mtm.id_tipo_moneda = '%d')
                      WHERE prod.id_producido = '%d'
                      AND mtm.id_casino = 3",$id_tipo_moneda,$producido->id_producido);

    $pdo->exec($query);

    $query = sprintf("DELETE FROM producido_temporal
                      WHERE id_producido = '%d'",$producido->id_producido);

    $pdo->exec($query);

    $cantidad_registros = DetalleProducido::where('id_producido','=',$producido->id_producido)->distinct()->count();

    $pdo=null;

    // implementacion para contemplar los casos en que las mtms no reporten
    /*$mtms= Maquina::select("id_maquina","nro_admin")
                ->where("id_casino","=",3)
                ->where("id_tipo_moneda","=",$id_tipo_moneda)
                ->whereNull("deleted_at")
                ->whereIn("id_estado_maquina",[1,2,4,5,6,7])
                ->get();*/
    $mtms= Maquina::where(['id_casino','=',3],['id_tipo_moneda','=',$id_tipo_moneda])
    ->whereNull("deleted_at")->whereIn("id_estado_maquina",[1,2,4,5,6,7]);

    $cant_mtm_forzadas=0;
    $id_mtm_forzadas=array();
    foreach($mtms as $m){
      //Por algun motivo, no se cual, esta devolviendo maquinas que no deberia (con moneda en pesos importando dolares)
      //Le agrego este chequeo
      if($m->id_tipo_moneda != $id_tipo_moneda) continue;
      if($m->id_casino != 3) continue;
      if(!is_null($m->deleted_at)) continue;
      if($m->id_estado_maquina == 3) continue;
      $cant=DetalleProducido::where("id_maquina","=",$m->id_maquina)
                            ->where("id_producido","=", $producido->id_producido)
                            ->count();
      if($cant==0){
        $daux= new DetalleProducido;
        $daux->valor=0;
        $daux->id_maquina=$m->id_maquina;
        $daux->id_producido=$producido->id_producido;
        $daux->save();
        $cant_mtm_forzadas=$cant_mtm_forzadas+1;
        array_push($id_mtm_forzadas,$m->id_maquina);
      }
    }
    $producido->cant_mtm_forzadas=$cant_mtm_forzadas;
    $producido->id_mtm_forzadas=implode(",",$id_mtm_forzadas);
    $producido->save();

    return ['id_producido' => $producido->id_producido,'fecha' => $producido->fecha,'casino' => $producido->casino->nombre,'cantidad_registros' => $cantidad_registros,'tipo_moneda' => Producido::find($producido->id_producido)->tipo_moneda->descripcion, 'cant_mtm_forzadas' => $cant_mtm_forzadas];
  }
  // importarBeneficioRosario vuelca el contenido del csv en un temporal, formateando los datos necesarios
  // luego vuelca en la tabla real
  public function importarBeneficioRosario($archivoCSV,$id_tipo_moneda){

    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();
    $path = $archivoCSV->getRealPath();

    $cantidad_maquinas = Maquina::where('id_casino','=',3)->whereHas('estado_maquina',function($q){
                                  $q->where('descripcion','=','Ingreso')->orWhere('descripcion','=','ReIngreso');})->count();

    $id_beneficio = DB::table('beneficio')->max('id_beneficio') + 1;

    $query = sprintf("LOAD DATA local INFILE '%s'
                      INTO TABLE beneficio_temporal
                      FIELDS TERMINATED BY ';'
                      OPTIONALLY ENCLOSED BY '\"'
                      ESCAPED BY '\"'
                      LINES TERMINATED BY '\\n'
                      IGNORE 1 LINES
                      (@0,@1,@2,@3,@4,@5,@6,@7)
                          SET fecha = STR_TO_DATE(@0,'%s'),
                             coinin = CAST(REPLACE(REPLACE(@1,'.',''),',','.') as DECIMAL(15,2)),
                            coinout = CAST(REPLACE(REPLACE(@2,'.',''),',','.') as DECIMAL(15,2)),
                              valor = CAST(REPLACE(REPLACE(@3,'.',''),',','.') as DECIMAL(15,2)),
                       id_beneficio = '%d'
                      ",$path,"%d/%m/%Y",$id_beneficio);

    $pdo->exec($query);

    $query = sprintf(" DELETE FROM beneficio
                       WHERE id_beneficio IN (SELECT b.id_beneficio
                                              FROM (SELECT * FROM beneficio WHERE id_casino = 3 AND id_tipo_moneda = '%d') AS b
                                              JOIN (SELECT * FROM beneficio_temporal WHERE id_beneficio = '%d') AS bt
                                              ON b.fecha = bt.fecha)
                       ",$id_tipo_moneda,$id_beneficio);
    $pdo->exec($query);

    $query = sprintf(" INSERT INTO beneficio (id_casino,fecha,coinin,coinout,valor,porcentaje_devolucion,cantidad_maquinas,promedio_por_maquina,id_tipo_moneda)
                       SELECT 3,fecha,coinin,coinout,valor,(coinout/coinin),'%d',(valor/'%d'),'%d'
                       FROM beneficio_temporal
                       WHERE id_beneficio = '%d'
                         AND fecha IS NOT NULL
                       ",$cantidad_maquinas,$cantidad_maquinas,$id_tipo_moneda,$id_beneficio);

    $pdo->exec($query);

    $query = sprintf(" DELETE FROM beneficio_temporal
                       WHERE id_beneficio = '%d'
                       ",$id_beneficio);
    $pdo->exec($query);

    $cantidad_registros = Beneficio::where('id_beneficio','>=',$id_beneficio)->count();
    $beneficios = DB::table('beneficio')->select('beneficio.id_beneficio','beneficio.fecha','tipo_moneda.descripcion','casino.nombre')
                                        ->where('id_beneficio','>=',$id_beneficio)
                                        ->join('casino','casino.id_casino','=','beneficio.id_casino')
                                        ->join('tipo_moneda','tipo_moneda.id_tipo_moneda','=','beneficio.id_tipo_moneda')->get();
    $pdo=null;

    return ['cantidad_registros' => $cantidad_registros,'beneficios' => $beneficios];
  }

  public function reconocerColumnas($row){
    $arreglo_nro_admin=['NINT','NINTERNO','NADMIN','NROADMIN','ADMIN','ADM','ADMINISTRATIVO','NROADMINISTRACION','NROADMINISTRACIN','NADM','NROADM'];
    $arreglo_isla=['ISLAS','ISLA','NROISLAS','NROISLA','NISLAS','NISLA'];
    $arreglo_nro_serie=['NSERIE','SERIE','NROSERIE'];
    $arreglo_sector=['NIVEL','SECTOR','NROSECTOR','IDSECTOR'];//NIVEL PUEDE DAR CONFLICTOS CON NIVEL DE PROGRESIVO. NIVEL DE SECTOR TIENE PRIORIDAD
    $arreglo_marca=['MARCA'];
    $arreglo_modelo=['MODELO'];
    $arreglo_marca_modelo=['MARCAMODELO','MODELOMARCA','MARCAYJUEGO'];//IGUAL
    $arreglo_denominacion=['DEN','DENOMINACION','DENOMINACIN','DENO','DENBASE']; //CONFLICTO CON DEN SALA, SI ES QUE DEN ES USADO PARA DEN DEL JUEGO
    $arreglo_devolucion=['DEVOLUCION','DEVOLUCIN','DEV','%','%DEVOLUCION','%DEVOLUCIN','%DEV'];
    $arreglo_unidad_medida=['UNIDADMEDIDA','UNIDADMED','MEDIDA','UNIDAD','UMEDIDA'];
    $arreglo_formula=['FORMULA','IDFORMULA','FBENEFICIO','FORMULABENEFICIO','FRMULA','FRMULABENEFICIO'];
    $arreglo_juego=['JUEGO','NOMBREJUEGO','NOMBREDELJUEGO','TIPODEJUEGO','NOMBREDEJUEGO'];
    $arreglo_den_juego=['DENSALA','DENJUEGO','DENOMINACIONSALA','DENOSALA','DENOMINACIONDESALA'];

    $arreglo_final= array();
    foreach ($row as $index => $nombre_columna) {
      $nombre_columna = $this->clean($nombre_columna);
      if(in_array($nombre_columna, $arreglo_nro_admin)){
        if(!isset($array['admin'])){
          $array['admin'] = $index;
        }
      }else if(in_array($nombre_columna, $arreglo_isla)){
        if(!isset($array['isla'])){
          $array['isla'] = $index;
        }
      }else if(in_array($nombre_columna, $arreglo_sector)){
        if(!isset($array['sector'])){
          $array['sector'] = $index;
        }
      }else if(in_array($nombre_columna, $arreglo_nro_serie)){
        if(!isset($array['serie'])){
          $array['serie'] = $index;
        }
      }else if(in_array($nombre_columna, $arreglo_denominacion)){
        if(!isset($array['den_base'])){
          $array['den_base'] = $index;
        }
      }else if(in_array($nombre_columna, $arreglo_devolucion)) {
        if(!isset($array['porcentaje_devolucion'])){
          $array['porcentaje_devolucion'] = $index;
        }
      }else if(in_array($nombre_columna, $arreglo_formula)){
        if(!isset($array['formula'])){
          $array['formula'] = $index;
        }
      }else if(in_array($nombre_columna, $arreglo_unidad_medida)){
        if(!isset($array['unidad_medida'])){
          $array['unidad_medida'] = $index;
        }
      }else if(in_array($nombre_columna, $arreglo_marca)){
          if(!isset($array['marca'])){
            $array['marca'] = $index;
          }
      }else if(in_array($nombre_columna, $arreglo_modelo)){
        if(!isset($array['modelo'])){
          $array['modelo'] = $index;
        }
      }else if(in_array($nombre_columna, $arreglo_marca_modelo)){ // CUANDO NOMBRE DE COLUMNA ES MARCA Y REPRESENTA MARCA - MODELO, ROMPE !!!
        if(!isset($array['marca_modelo'])){
          unset($array['marca']);
          unset($array['modelo']);
          $array['marca_modelo'] = $index;
        }
      }else if(in_array($nombre_columna, $arreglo_juego)){
        if(!isset($array['juego'])){
          $array['juego'] = $index;
        }
      }else if(in_array($nombre_columna, $arreglo_den_juego)){
        if(!isset($array['den_juego'])){
          $array['den_juego'] = $index;
        }
      }
    }

    return $array;
  }

  public function clean($string) {
     $string = str_replace(' ', '', $string); // Replaces all spaces.
     $string =preg_replace('/[^A-Za-z0-9\%]/', '', $string);  // Removes special chars.
     return strtoupper($string); //to upper
  }

  public function separarMarcaYModelo($marca_modelo){
    $marcas = MTMController::getInstancia()->buscarMarcas('');
    $retorno = false;
    $nombre_marcas = array();
    foreach ($marcas['marcas'] as $marca) {
      $nombre_marcas[]= $marca->marca;
      $pos = explode($marca, $marca_modelo);
      if(isset($pos[1])){
        $retorno = ['marca' => $marca , 'modelo' => $pos[1]];
        break;
      }
    }

    return $retorno;
  }
}
