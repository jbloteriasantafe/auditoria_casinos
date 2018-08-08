<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\ContadorHorario;
use View;
use App\Relevamiento;
use App\EstadoRelevamiento;
use App\Casino;
use App\Sector;
use App\TipoMoneda;

/*
    Controllador encargado de checkear si para un dia fueron cargados los relevamientos y ademas, ver si los relevamientos fueron cargados y validados.
    Devuelve a vista "menu_contadores"
*/

class MenuContadoresController extends Controller
{
  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new MenuContadoresController();
    }
    return self::$instance;
  }


  public function estado_actividades(){
    $pdo = DB::connection('mysql')->getPdo();
    // $string_query =
    // "SELECT relevamiento.fecha, contador_horario.id_contador_horario , sector.id_sector, relevamiento.id_estado_relevamiento
    //  FROM relevamiento join sector on relevamiento.id_sector = sector.id_sector
    //                   join casino on casino.id_casino = sector.id_casino
    //                   left join contador_horario  on (contador_horario.fecha = relevamiento.fecha AND contador_horario.id_casino = casino.id_casino)
    //  WHERE relevamiento.fecha = '%s'  AND backup = 0 and casino.id_casino='%s'
    //  GROUP BY relevamiento.fecha, contador_horario.id_contador_horario , relevamiento.id_sector";

     $string_query ="SELECT relevamiento.fecha as fecha, relevamiento.id_estado_relevamiento as id_estado_relevamiento, sector.id_sector as id_sector, contador_horario.id_contador_horario as id_contador_horario

     FROM relevamiento
      join sector on relevamiento.id_sector = sector.id_sector
      join casino on casino.id_casino = sector.id_casino
      left join contador_horario  on (contador_horario.fecha = relevamiento.fecha AND contador_horario.id_casino = casino.id_casino)

     WHERE relevamiento.fecha = '%s'  AND backup = 0 and casino.id_casino='%s'
     GROUP BY relevamiento.fecha, sector.id_sector, contador_horario.id_contador_horario,relevamiento.id_estado_relevamiento
     ORDER BY sector.id_sector asc
     ";

    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = $usuario->casinos;
    $retorno = array();

    foreach ($casinos as $casino){ //loop por casino

      $fecha_actual= date("Y-m-d"); // fecha de hoy
      $sectores = $casino->sectores;

      for ($i=0; $i < 30 ; $i++){

          $consulta = sprintf($string_query ,$fecha_actual ,  $casino->id_casino);
          $resultados = $pdo->query($consulta);
          //si existe contandor importado para esa fecha, tienen id asociado.
          $detalle = new \stdClass();
          $detalle->cantidad_sectores = $sectores->count();
          $detalle->fecha= $fecha_actual;

          if($resultados->rowCount() != 0){

            $auxiliar = array(); //guarda el estado del sector auxiliar[id_sector] = estado del sector
            foreach ($resultados as $row) { //por cada renglon de respuesta ( todos los relevamientos de esa fecha )

            $detalle->importado = $row['id_contador_horario'] != null ? true: false;

            if(array_key_exists( $row['id_sector'],$auxiliar)){ // si esta seteada la posicion con id del sector

                  $auxiliar[ $row['id_sector'] ] = min($auxiliar[$row['id_sector']] ,$row['id_estado_relevamiento']);

              }else {


                $auxiliar[ $row['id_sector'] ] = $row['id_estado_relevamiento'];
              }

            }


            //seteo de condiciones iniciales
            $cantidad_finalizados = 0;
            $cantidad_validados = 0;
            $validado= 1;
            $finalizado= 1;

            foreach ($sectores as $sector) {
              $condicion = array_key_exists($sector->id_sector , $auxiliar) ? $auxiliar[$sector->id_sector] : 0 ;
              switch ($condicion) {
                case 3:
                  $cantidad_finalizados++;
                  $validado = 0;
                  break;
                case 4:
                  $cantidad_finalizados++;
                  $cantidad_validados++;
                  break;
                default:
                  $validado = 0;
                  $finalizado = 0;
                  break;
              }
            }

            $detalle->cantidad_sectores_relevados = $cantidad_finalizados;
            $detalle->cantidad_sectores_validados = $cantidad_validados;
            $detalle->validado = $validado;
            $detalle->finalizado = $finalizado;


          }else {
            $detalle = new \stdClass();
            $detalle->fecha = $fecha_actual;
            $detalle->cantidad_sectores  = $sectores->count();;
            $detalle->cantidad_sectores_relevados = 0;
            $detalle->cantidad_sectores_validados = 0;
            //caso rosario, si estan ambas monedas importadas, doy como qeu se importo el dia
            $cantidad_importacion = ContadorHorario::where([['fecha' , $fecha_actual] , ['id_casino', $casino->id_casino] ])->count();
            if($casino->id_casino == 3){
              $detalle->importado  = $cantidad_importacion == 2 ? 1 : 0;
            }else {
              $detalle->importado  = $cantidad_importacion == 1 ? 1 : 0;
            }
          }

          $ajustes[$casino->id_casino]['nombre'] = $casino->nombre;
          $ajustes[$casino->id_casino]['detalles'][] = $detalle;
          $fecha_actual=date('Y-m-d' , strtotime($fecha_actual . ' - 1 days'));

      }

    }
    // return $ajustes;
    UsuarioController::getInstancia()->agregarSeccionReciente('menu_contadores');

    return view('menu_contadores' , ['ajustes_finales' => $ajustes] );

  }

  public function estadosDeActividades(){//devuelve el estado de las actividades( contadores importados, relevamientos finalizados, o validados)
      // sprintf($string_query ,$fecha_fin ,  $id_casino)
          $pdo = DB::connection('mysql')->getPdo();
      //query obtiene
      $query= "SELECT fecha_generacion, casino.nombre, estado_relevamiento.descripcion, tipo_moneda.descripcion as moneda
         FROM relevamiento
         JOIN estado_relevamiento
          JOIN sector
          JOIN casino
          JOIN contador_horario
          JOIN tipo_moneda
            ON (relevamiento.id_estado_relevamiento = estado_relevamiento.id_estado_relevamiento
              AND relevamiento.id_sector = sector.id_sector
              AND sector.id_casino = casino.id_casino
              AND casino.id_casino = contador_horario.id_casino
              AND contador_horario.id_tipo_moneda = tipo_moneda.id_tipo_moneda)
             WHERE fecha_generacion
                   BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                   AND
                   CURDATE()
              GROUP BY fecha_generacion, casino.nombre, estado_relevamiento.descripcion, tipo_moneda.descripcion
             ORDER BY fecha_generacion DESC";


        $query2 = "SELECT fecha_generacion, casino.nombre, casino.id_casino, tipo_moneda.descripcion as moneda
         FROM relevamiento
         JOIN estado_relevamiento
          JOIN sector
          JOIN casino
          JOIN contador_horario
          JOIN tipo_moneda
            ON (relevamiento.id_estado_relevamiento = estado_relevamiento.id_estado_relevamiento
              AND relevamiento.id_sector = sector.id_sector
              AND sector.id_casino = casino.id_casino
              AND casino.id_casino = contador_horario.id_casino
              AND contador_horario.id_tipo_moneda = tipo_moneda.id_tipo_moneda)
             WHERE fecha_generacion
                   BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                   AND
                   CURDATE()
              GROUP BY  fecha_generacion, casino.nombre, tipo_moneda.descripcion
             ORDER BY fecha_generacion DESC";


        $query3= "SELECT contador_horario.fecha, casino.nombre, tipo_moneda.descripcion as moneda
         FROM relevamiento
         JOIN estado_relevamiento
          JOIN sector
          JOIN casino
          JOIN contador_horario
          JOIN tipo_moneda
            ON (relevamiento.id_estado_relevamiento = estado_relevamiento.id_estado_relevamiento
              AND relevamiento.id_sector = sector.id_sector
              AND sector.id_casino = casino.id_casino
              AND casino.id_casino = contador_horario.id_casino
              AND contador_horario.id_tipo_moneda = tipo_moneda.id_tipo_moneda)
             WHERE contador_horario.fecha
                   BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                   AND
                   CURDATE()
              GROUP BY contador_horario.fecha, casino.nombre, tipo_moneda.descripcion
             ORDER BY contador_horario.fecha DESC";
      $tabla2=$pdo->query($query2);

      $ajustesFinales = array();
      foreach($tabla2 as $resultado){
        $res = new \stdClass();
        $res->fecha = $resultado['fecha_generacion'];
        $res->tipoMoneda = $resultado['moneda'];
        $res->casino = $resultado['nombre'];
        $res->idCasino = $resultado['id_casino'];
        $res->relevamientosFinalizados = 0;
        $res->relevamientosTemp = 0;
        $tabla1=$pdo->query($query);
        $tabla3=$pdo->query($query3);
        foreach($tabla1 as $resultado2){
          if(($res->fecha == $resultado2['fecha_generacion']) && ($res->casino == $resultado2['nombre']) && ($res->tipoMoneda == $resultado2['moneda'])){
            if(($resultado2['descripcion'] == 'Cargando') || ($resultado2['descripcion'] == 'Generado')){
              $res->relevamientosTemp += 1;
            }
            else{
            $res->relevamientosTemp += 1;
            $res->relevamientosFinalizados += 1;
          }
        }
      }
       foreach($tabla3 as $resultado3){
          if(($res->fecha == $resultado3['fecha']) && ($res->casino == $resultado3['nombre']) && ($res->tipoMoneda == $resultado3['moneda'])){
            $res->contador = 'OK';
          }
          else{
            $res->contador = 'NONE';
          }
        }
        $fechaP = $resultado['fecha_generacion'];
        $anio = $fechaP[0].$fechaP[1].$fechaP[2].$fechaP[3];
        $mes = $fechaP[5].$fechaP[6];
        $dia = $fechaP[8].$fechaP [9];
        $fecha_final = $dia."-".$mes."-".$anio;
        $res->fechaP= $fecha_final;

        $ajustesFinales[] = $res;
    };
    UsuarioController::getInstancia()->agregarSeccionReciente('menu_contadores');

      return view('menu_contadores' , ['ajustesFinales' => $ajustesFinales]);
      // subdate(current_date, 1) < - - dia anterior
    }

};
