<?php

namespace App\Http\Controllers\Bingo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\DB;
use Validator;

use App\Bingo\SesionBingo;
use App\Bingo\SesionBingoRe;
use App\Bingo\DetalleSesionBingo;
use App\Bingo\DetalleSesionBingoRe;
use App\Bingo\EstadoSesionBingo;
use App\Bingo\PartidaBingo;
use App\Bingo\DetallePartidaBingo;
use App\Bingo\ReporteEstado;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

use Dompdf\Dompdf;
use PDF;
use View;
class SesionesController extends Controller
{
  private static $atributos = [
  ];

    private function errorOut($map){
      return response()->json($map,422);
    }

    public function index(){
      //Busco los casinos a los que esta asociado el usuario
      $casinos = UsuarioController::getInstancia()->getCasinos();
      //agrego a seccion reciente a BINGo
      UsuarioController::getInstancia()->agregarSeccionReciente('Sesiones y Relevamientos' ,'bingo');

      return view('Bingo.sesion', ['casinos' => $casinos]);
    }

    public function buscarSesion(Request $request){
      //Busco los casinos a los que esta asociado el usuario
      $casinos = UsuarioController::getInstancia()->getCasinos();
      //reglas que vienen desde el buscador para poder filtrar
      $reglas = array();
      if(isset($request->fecha)){
        $reglas[]=['bingo_sesion.fecha_inicio', '=', $request->fecha];
      }
      if($request->casino!=0){
        $reglas[]=['casino.id_casino', '=', $request->casino];
      }
      if($request->estado != 0){
        $reglas[]=['bingo_estado_sesion.id_estado_sesion', '=', $request->estado];
      }
      $sort_by = $request->sort_by;
      //consulta a la db para obtener las sesiones que cumplan con las reglas
      $resultados = DB::table('bingo_sesion')
                         ->select('bingo_sesion.*', 'casino.nombre', 'bingo_estado_sesion.descripcion', 'u1.nombre as nombre_inicio', 'u2.nombre as nombre_fin')
                         ->leftJoin('casino' , 'bingo_sesion.id_casino','=','casino.id_casino')
                         ->leftJoin('bingo_estado_sesion' , 'bingo_sesion.id_estado','=','bingo_estado_sesion.id_estado_sesion')
                         ->leftJoin('usuario as u1', 'bingo_sesion.id_usuario_inicio', '=', 'u1.id_usuario')
                         ->leftJoin('usuario as u2', 'bingo_sesion.id_usuario_fin', '=', 'u2.id_usuario')
                         ->when($sort_by,function($query) use ($sort_by){
                          return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                        },function($query){
                          return $query->orderBy('fecha_inicio','desc');
                        })
                      ->where($reglas)
                      ->whereIn('casino.id_casino', $casinos)
                      // ->orderBy('hora_inicio', 'desc')
                      ->paginate($request->page_size);

     return $resultados;
    }

    public function guardarSesion(Request $request){
      //Validación de los datos
      Validator::make($request->all(), [
            'pozo_dotacion_inicial' => 'required|numeric',
            'pozo_extra_inicial' => 'required|numeric',
            'fecha_inicio' => 'required',
            'hora_inicio' => 'required',
            'detalles.*.valor_carton' => 'required|numeric',
            'detalles.*.serie_inicial' => 'required|numeric',
            'detalles.*.carton_inicial' => 'required|numeric',
        ], array(), self::$atributos)->after(function($validator){
          //valido que no exista importacion para el mismo día en el casino
          $regla_carga = array();
          $regla_carga [] =['bingo_sesion.id_casino', '=', $validator->getData()['casino']];
          $regla_carga [] =['bingo_sesion.fecha_inicio', '=', $validator->getData()['fecha_inicio']];
          $carga = SesionBingo::where($regla_carga)->count();
          if($carga > 0){
            $validator->errors()->add('sesion_cargada','La sesion para esta fecha se encuentra cargarda.');
          }
        })->validate();

      //busco la id del usuario que guarda la sesión
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario;

      //Se crea una nueva sesión y se le cargan los datos
      $sesion = new SesionBingo;

      $sesion->fecha_inicio = $request->fecha_inicio;
      $sesion->hora_inicio = $request->hora_inicio;
      $sesion->pozo_dotacion_inicial = $request->pozo_dotacion_inicial;
      $sesion->pozo_extra_inicial = $request->pozo_extra_inicial;

      $sesion->id_casino = $request->casino;
      $sesion->id_usuario_inicio = $usuario;
      $sesion->id_estado = '1';

      $sesion->save();

      //GUARDA LOS DETALLES DE LA SESIÓN
      foreach ($request->detalles as $detalle) {
            $detalle_sesion = new DetalleSesionBingo;

            $detalle_sesion->sesionBingo()->associate($sesion->id_sesion);
            $detalle_sesion->valor_carton = $detalle['valor_carton'];
            $detalle_sesion->serie_inicio = $detalle['serie_inicial'];
            $detalle_sesion->carton_inicio = $detalle['carton_inicial'];

            $detalle_sesion->save();
      }
      //Guardo la información para el reporte de estado
      app(\App\Http\Controllers\Bingo\ReportesController::class)->guardarReporteEstado($sesion->id_casino, $sesion->fecha_inicio, 4);
      return ['sesion' => $sesion, 'casino' => $sesion->casino, 'estado' => $sesion->estadoSesion, 'nombre_inicio' => $sesion->usuarioInicio->nombre, 'nombre_fin' => '-'];
    }

    public function guardarCierreSesion(Request $request){
      //Validación de los datos
      Validator::make($request->all(), [
            'pozo_dotacion_final' => 'required|numeric',
            'pozo_extra_final' => 'required|numeric',
            'fecha_fin' => 'required',
            'hora_fin' => 'required',
            'detalles.*.valor_carton_f' => 'required|numeric',
            'detalles.*.serie_final' => 'required|numeric',
            'detalles.*.carton_final' => 'required|numeric',
        ])->validate();
      //Busco la id del usuario que cerro la sesión
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario;
      //busco la sesión y le cargo los datos de cierre
      $sesion=SesionBingo::findorfail($request->id_sesion);

      $sesion->fecha_fin = $request->fecha_fin;
      $sesion->hora_fin = $request->hora_fin;
      $sesion->pozo_dotacion_final = $request->pozo_dotacion_final;
      $sesion->pozo_extra_final = $request->pozo_extra_final;

      $sesion->id_usuario_fin = $usuario;
      $sesion->id_estado = '2';

      $sesion->save();

      //GUARDA LOS DETALLES DEL CIERRE DE SESIÓN
      $i = 0;
      foreach ($request->detalles as $detalle) {
            $detalle_sesion = $sesion->detallesSesion[$i];
            $detalle_sesion['serie_fin'] = $detalle['serie_final'];
            $detalle_sesion['carton_fin'] = $detalle['carton_final'];
            $detalle_sesion->save();
            $i++;
      }
      //Guardo la información para el reporte de estado
      app(\App\Http\Controllers\Bingo\ReportesController::class)->guardarReporteEstado($sesion->id_casino, $sesion->fecha_inicio, 2);
      app(\App\Http\Controllers\Bingo\ReportesController::class)->eliminarReporteEstado($sesion->id_casino, $sesion->fecha_inicio, 4);

      return ['sesion' => $sesion, 'casino' => $sesion->casino, 'estado' => $sesion->estadoSesion, 'nombre_inicio' => $sesion->usuarioInicio->nombre, 'nombre_fin' => $sesion->usuarioFin->nombre];
      }

    public function modificarCierreSesion(Request $request){
        //Validación de los datos
        Validator::make($request->all(), [
              'pozo_dotacion_final' => 'required|numeric',
              'pozo_extra_final' => 'required|numeric',
              'fecha_fin' => 'required',
              'hora_fin' => 'required',
              'detalles.*.valor_carton_f' => 'required|numeric',
              'detalles.*.serie_final' => 'required|numeric',
              'detalles.*.carton_final' => 'required|numeric',
          ])->validate();
        //busco la id del usuario que esta modificando
        $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario;
        //busco la sesion a modificar
        $sesion=SesionBingo::findorfail($request->id_sesion);
        //busco la sesion re abierta para agregarle los datos al historial
        $sesionre = SesionBingoRe::where('id_sesion','=',$request->id_sesion)->get()->last();
        //Guarda los detalles anteriores de la sesión
        if($sesionre->detallesSesion->count() == 0){
          //Si no tiene detalles de reapertura guardados, los guardo
          foreach ($sesion->detallesSesion as $detalle) {

                $detalle_sesion = new DetalleSesionBingoRe;

                $detalle_sesion->sesionBingoRe()->associate($sesionre->id_sesion_re);
                $detalle_sesion->id_detalle_sesion = $detalle['id_detalle_sesion'];
                $detalle_sesion->valor_carton = $detalle['valor_carton'];
                $detalle_sesion->serie_fin = $detalle['serie_fin'];
                $detalle_sesion->carton_fin = $detalle['carton_fin'];
                $detalle_sesion->save();
          }
        }else{
          $i = 0;
          foreach ($sesion->detallesSesion as $detalle){
                $detalle_sesion = $sesionre->detallesSesion[$i];
                $detalle_sesion['serie_fin'] = $detalle['serie_final'];
                $detalle_sesion['carton_fin'] = $detalle['carton_final'];
                $detalle_sesion->save();
                $i++;
          }
        }

        //Guarda las modificaciones en la sesion
        $sesion->fecha_fin = $request->fecha_fin;
        $sesion->hora_fin = $request->hora_fin;
        $sesion->pozo_dotacion_final = $request->pozo_dotacion_final;
        $sesion->pozo_extra_final = $request->pozo_extra_final;

        $sesion->id_usuario_fin = $usuario;
        $sesion->id_estado = '2';

        $sesion->save();

        //GUARDA LOS DETALLES modificados DEL CIERRE DE SESIÓN
        $i = 0;
        foreach ($request->detalles as $detalle){
              $detalle_sesion = $sesion->detallesSesion[$i];
              $detalle_sesion['valor_carton'] = $detalle['valor_carton_f'];
              $detalle_sesion['serie_fin'] = $detalle['serie_final'];
              $detalle_sesion['carton_fin'] = $detalle['carton_final'];
              $detalle_sesion->save();
              $i++;
        }

        //Guardo la información para el reporte de estado
        app(\App\Http\Controllers\Bingo\ReportesController::class)->guardarReporteEstado($sesion->id_casino, $sesion->fecha_inicio, 2);

        return ['sesion' => $sesion, 'casino' => $sesion->casino, 'estado' => $sesion->estadoSesion, 'nombre_inicio' => $sesion->usuarioInicio->nombre, 'nombre_fin' => $sesion->usuarioFin->nombre];
        }

    public function modificarSesion(Request $request){
      //Validación de los datos
      Validator::make($request->all(), [
            'pozo_dotacion_inicial' => 'required|numeric',
            'pozo_extra_inicial' => 'required|numeric',
            'fecha_inicio' => 'required',
            'hora_inicio' => 'required',
            'detalles.*.valor_carton' => 'required|numeric',
            'detalles.*.serie_inicial' => 'required|numeric',
            'detalles.*.carton_inicial' => 'required|numeric',
        ])->validate();

        //busco la id del usuario que esta modificando
        $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario;
        //busco la sesion a modificar
        $sesion=SesionBingo::findorfail($request->id_sesion);
        //busco la sesion re abierta para agregarle los datos al historial
        $sesionre = SesionBingoRe::where('id_sesion','=',$request->id_sesion)->get()->last();
            // dd($sesionre);
            if($sesionre != null) {
                // Guarda los datos anteriores de la sesion
                $sesionre->pozo_dotacion_inicial = $sesion->pozo_dotacion_inicial;
                $sesionre->pozo_extra_inicial = $sesion->pozo_extra_inicial;
                $sesionre->id_usuario_inicio = $usuario;
                $sesionre->fecha_inicio = $sesion->fecha_inicio;
                $sesionre->hora_inicio = $sesion->hora_inicio;

                $sesionre->save();

                //Guarda los detalles anteriores de la sesión
                if($sesionre->detallesSesion->count() == 0){

                  //Si no tiene detalles de reapertura guardados, los guardo
                  foreach ($sesion->detallesSesion as $detalle) {

                        $detalle_sesion = new DetalleSesionBingoRe;

                        $detalle_sesion->sesionBingoRe()->associate($sesionre->id_sesion_re);
                        $detalle_sesion->id_detalle_sesion = $detalle['id_detalle_sesion'];
                        $detalle_sesion->valor_carton = $detalle['valor_carton'];
                        $detalle_sesion->serie_inicio = $detalle['serie_inicio'];
                        $detalle_sesion->carton_inicio = $detalle['carton_inicio'];
                        $detalle_sesion->save();
                  }
                }else{
                  $i = 0;
                  foreach ($sesion->detallesSesion as $detalle){
                        $detalle_sesion = $sesionre->detallesSesion[$i];
                        $detalle_sesion['serie_inicio'] = $detalle['serie_inicial'];
                        $detalle_sesion['carton_inicio'] = $detalle['carton_inicial'];
                        $detalle_sesion->save();
                        $i++;
                  }
                }

          }
            //Guarda las modificaciones en la sesion
            $sesion->fecha_inicio = $request->fecha_inicio;
            $sesion->hora_inicio = $request->hora_inicio;
            $sesion->pozo_dotacion_inicial = $request->pozo_dotacion_inicial;
            $sesion->pozo_extra_inicial = $request->pozo_extra_inicial;
            $sesion->id_casino = $request->casino;
            $sesion->id_usuario_inicio = $usuario;

            $sesion->save();

            //GUARDA LOS DETALLES modificados de la sesion
            $i = 0;
            foreach ($request->detalles as $detalle){
                  $detalle_sesion = $sesion->detallesSesion[$i];
                  $detalle_sesion['valor_carton'] = $detalle['valor_carton'];
                  $detalle_sesion['serie_inicio'] = $detalle['serie_inicial'];
                  $detalle_sesion['carton_inicio'] = $detalle['carton_inicial'];
                  $detalle_sesion->save();
                  $i++;
            }


            return ['sesion' => $sesion, 'casino' => $sesion->casino, 'estado' => $sesion->estadoSesion, 'nombre_inicio' => $sesion->usuarioInicio->nombre, 'nombre_fin' => '-'];
            }

    public function eliminarSesion($id){

      $sesion=SesionBingo::findorfail($id);

      //si esta cerrada, los fiscalizadores no pueden eliminar la sesión
      if($sesion->id_estado == 2) {
        $permiso = app(\App\Http\Controllers\UsuarioController::class)->chequearRolFiscalizador();
        if( $permiso == 1){
            return $this->errorOut(['no_tiene_permiso' => 'Su rol en el sistema no le permite reabrir una sesión.']);
        }
      }

      //Guardo la información para el reporte de estado
      //si no tiene cargado importacion, elimino el reporte

      //armo las reglas para la busqueda
      $reglas = array();
      $reglas [] =['fecha_sesion','=', $sesion->fecha_inicio];
      $reglas [] =['id_casino','=', $sesion->id_casino];
      //busco el reporte que cumpla con las reglas
      $reporte = ReporteEstado::where($reglas)->first();
      // dd($reporte);
      if($reporte->importacion == null || $reporte->importacion == 0) {
        $reporte->delete();
      }
      else{
        app(\App\Http\Controllers\Bingo\ReportesController::class)->eliminarReporteEstado($sesion->id_casino, $sesion->fecha_inicio, 2);
        app(\App\Http\Controllers\Bingo\ReportesController::class)->eliminarReporteEstado($sesion->id_casino, $sesion->fecha_inicio, 3);
        app(\App\Http\Controllers\Bingo\ReportesController::class)->eliminarReporteEstado($sesion->id_casino, $sesion->fecha_inicio, 4);
      }


      // Elimina los relevamientos asociados a la sesión y sus detalles.
      foreach ($sesion->partidasSesion as $partida) {
        foreach ($partida->detallesPartida as $detalle) {
          $detalle->delete();
        }
        $partida->delete();
      }

      // Elimina los detalles de la sesion
      foreach ($sesion->detallesSesion as $detalle) {
        $detalle->delete();
      }

      $sesion->delete();

      return ['sesion' => $sesion];
    }

    public function obtenerSesion($id){

      $sesion = SesionBingo::findorfail($id);

      $detalles = array();
      foreach ($sesion->detallesSesion as $detalle) {
        $detalles[] = $detalle;
      }

      $partidas = array();
      foreach ($sesion->partidasSesion as $partida) {
        $relevamiento = array();
        $relevamiento []= $partida;
          array_push($relevamiento, $partida->usuario->nombre);
        $partidas[] = $relevamiento;
      }

       $historico = DB::table('bingo_re_sesion')
       ->where('id_sesion','=', $id)
       ->select('bingo_re_sesion.*', 'u1.nombre as nombre_inicio', 'u2.nombre as nombre_fin')
       ->leftJoin('usuario as u1', 'bingo_re_sesion.id_usuario_inicio', '=', 'u1.id_usuario')
       ->leftJoin('usuario as u2', 'bingo_re_sesion.id_usuario_fin', '=', 'u2.id_usuario')
       ->where('id_sesion','=', $id)
       ->get();

      return ['sesion' => $sesion, 'detalles' => $detalles, 'partidas' => $partidas, 'historico' => $historico];

    }
    //obtener datos de la sesion solamente
    public function obtenerSesionSH($id){
        $sesion = SesionBingo::findorfail($id);


        $reglas = array();
        $reglas [] = ['id_casino','=',$sesion->id_casino];
        $reglas [] = ['fecha_inicio','=',$sesion->fecha_inicio];

        $sesion = DB::table('bingo_sesion')
                           ->select('bingo_sesion.*', 'u1.nombre as nombre_inicio', 'u2.nombre as nombre_fin')
                           ->leftJoin('usuario as u1', 'bingo_sesion.id_usuario_inicio', '=', 'u1.id_usuario')
                           ->leftJoin('usuario as u2', 'bingo_sesion.id_usuario_fin', '=', 'u2.id_usuario')
                           ->where($reglas)
                           ->first();

        return $sesion;
    }
    //obtener datos de sesiones a partir de fecha y casino
    public function obtenerSesionFC($fecha, $id_casino, $valor = null){
      $reglas = array();
      $reglas [] = ['id_casino','=',$id_casino];
      $reglas [] = ['fecha_inicio','=',$fecha];

      $sesion = SesionBingo::where('id_casino','=',$id_casino)->where('fecha_inicio','=',$fecha)->first();
      if($sesion != null) {
        return $this->obtenerSesion($sesion->id_sesion);
        // if ($valor == 'diferencia')  return $this->obtenerSesion($sesion->id_sesion);
        // else return $this->obtenerSesionSH($sesion->id_sesion);
      }
      return -1;
    }

    public function guardarRelevamiento(Request $request){

      //Validación de los datos
      Validator::make($request->all(), [
            'nro_partida' => 'required|numeric',
            'hora_jugada' => 'required|date_format:H:i:s',
            'valor_carton' => 'required|numeric',
            'serie_inicio' => 'required|numeric',
            'serie_fin' => 'required|numeric',
            'carton_inicio_i' => 'required|numeric',
            'carton_fin_i' => 'required|numeric',
            'carton_inicio_f' => 'required|numeric',
            'carton_fin_f' => 'required|numeric',
            'cartones_vendidos' => 'required|numeric',
            'premio_linea' => 'required|numeric',
            'premio_bingo' => 'required|numeric',
            'maxi_linea' => 'required|numeric',
            'maxi_bingo' => 'required|numeric',
            'pos_bola_bingo' => 'required|numeric',
            'pos_bola_linea' => 'required|numeric',
            'detalles.*.nombre_premio' => 'required|numeric',
            'detalles.*.carton_ganador' => 'required|numeric',
        ])->after(function($validator){
          //obtengo la sesión
          $sesion = SesionBingo::find($validator->getData()['id_sesion']);
          //si esta cerrada, mensaje de error que no se pueden cargar los relevamientos
          if($sesion->id_estado == 2){
              $validator->errors()->add('relevamiento_cerrado', 'La sesión está cerrada, no se pueden cargar relevamientos.');
          }
          //verifica que las partidas no tengan numero de partida repetido
          foreach ($sesion->partidasSesion as $partida){
            if ( $partida->num_partida == $validator->getData()['nro_partida']){
                $validator->errors()->add('partida_cargada', 'Ya se ha cargado un relevamiento con el mismo número de partida.');
            }
          }
        })->validate();


      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario;

      $sesion = SesionBingo::findOrFail($request->id_sesion);

      $partida = new PartidaBingo;

      $partida->id_usuario = $usuario;

      $partida->id_sesion = $request->id_sesion;
      $partida->num_partida = $request->nro_partida;
      $partida->hora_inicio = $request->hora_jugada;
      $partida->valor_carton = $request->valor_carton;
      $partida->serie_inicio = $request->serie_inicio;
      $partida->carton_inicio_i = $request->carton_inicio_i;
      $partida->carton_fin_i = $request->carton_fin_i;
      $partida->serie_fin = $request->serie_fin;
      $partida->carton_inicio_f = $request->carton_inicio_f;
      $partida->carton_fin_f = $request->carton_fin_f;
      $partida->cartones_vendidos = $request->cartones_vendidos;
      $partida->premio_linea = $request->premio_linea;
      $partida->premio_bingo = $request->premio_bingo;
      $partida->pozo_dot = $request->maxi_linea;
      $partida->pozo_extra = $request->maxi_bingo;
      $partida->bola_bingo = $request->pos_bola_bingo;
      $partida->bola_linea = $request->pos_bola_linea;
      $partida->save();

      //GUARDA LOS DETALLES DEL RELEVAMIENTO
      foreach ($request->detalles as $detalle) {
            $detalle_partida = new DetallePartidaBingo;

            $detalle_partida->partidaBingo()->associate($partida->id_partida);
            $detalle_partida->id_premio = $detalle['nombre_premio'];
            $detalle_partida->carton = $detalle['carton_ganador'];

            $detalle_partida->save();
      }
      //Guardo la información para el reporte de estado
      app(\App\Http\Controllers\Bingo\ReportesController::class)->guardarReporteEstado($sesion->id_casino, $sesion->fecha_inicio, 3);

        return ['partida' => $partida];
    }

    public function reAbrirSesion(Request $request, $id){
      //Validación del motivo
      Validator::make($request->all(), [
            'motivo' => 'required'
        ])->validate();

      //control de permiso
        $permiso = app(\App\Http\Controllers\UsuarioController::class)->chequearRolFiscalizador();
        if( $permiso == 1){
            return $this->errorOut(['no_tiene_permiso' => 'Su rol en el sistema no le permite reabrir una sesión.']);
        }

      $sesion = SesionBingo::findorfail($id);

      //Guardo los datos que contenia la sesion
      $sesionre = new SesionBingoRe;
      $sesionre->id_sesion = $id;
      $sesionre->hora_fin = $sesion->hora_fin;
      $sesionre->fecha_fin = $sesion->fecha_fin;
      $sesionre->hora_inicio = $sesion->hora_inicio;
      $sesionre->fecha_inicio = $sesion->fecha_inicio;
      $sesionre->id_usuario_inicio = $sesion->id_usuario_inicio;
      $sesionre->id_usuario_fin = $sesion->id_usuario_fin;
      $sesionre->pozo_dotacion_inicial = $sesion->pozo_dotacion_inicial;
      $sesionre->pozo_extra_inicial = $sesion->pozo_extra_inicial;
      $sesionre->pozo_dotacion_final = $sesion->pozo_dotacion_final;
      $sesionre->pozo_extra_final = $sesion->pozo_extra_final;
      $sesionre->observacion = $request->motivo;

      $sesionre->fecha_re = date("Y-m-d");
      $sesionre->save();
      //Restablezco valores de la sesion
      $sesion->id_estado = '1';
      $sesion->id_usuario_fin = null;
      $sesion->save();

      //Guardo la información para el reporte de estado
      app(\App\Http\Controllers\Bingo\ReportesController::class)->eliminarReporteEstado($sesion->id_casino, $sesion->fecha_inicio, 2);
        app(\App\Http\Controllers\Bingo\ReportesController::class)->guardarReporteEstado($sesion->id_casino, $sesion->fecha_inicio, 4);

      return ['sesion' => $sesion, 'casino' => $sesion->casino, 'estado' => $sesion->estadoSesion, 'nombre_inicio' => $sesion->usuarioInicio->nombre, 'nombre_fin' => '-'];
      }

      public function eliminarPartida($id){

        $permiso = app(\App\Http\Controllers\UsuarioController::class)->chequearRolFiscalizador();
        if( $permiso == 1){
            return $this->errorOut(['no_tiene_permiso' => 'Su rol en el sistema no le permite reabrir una sesión.']);
        }

        $partida = PartidaBingo::findorfail($id);

        $partida->delete();

        return ['partida' => $partida];
      }

      public function generarPlanillaSesion(){

        $view = View::make('Bingo.planillaSesion');
        $dompdf = new Dompdf();
        $dompdf->set_paper('A4', 'portrait');
        $dompdf->loadHtml($view->render());
        $dompdf->render();
        $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
        return $dompdf->stream("Sesion-Bingo.pdf", Array('Attachment'=>0));
      }

      public function generarPlanillaCierreSesion(){

        $view = View::make('Bingo.planillaCierreSesion');
        $dompdf = new Dompdf();
        $dompdf->set_paper('A4', 'portrait');
        $dompdf->loadHtml($view->render());
        $dompdf->render();
        $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
        return $dompdf->stream("CierreSesion-Bingo.pdf", Array('Attachment'=>0));
      }

      public function generarPlanillaRelevamiento(){

        $view = View::make('Bingo.planillaRelevamiento');
        $dompdf = new Dompdf();
        $dompdf->set_paper('A4', 'portrait');
        $dompdf->loadHtml($view->render());
        $dompdf->render();
        $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
        $dompdf->stream("Relevamiento-Bingo.pdf", Array('Attachment'=>0));
      }
}
