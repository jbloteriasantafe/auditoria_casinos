<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Response;
use App\Notifications\NuevaIntervencionTecnica;
use App\Eventualidad;
use App\TipoEventualidad;
use App\Usuario;
use App\Isla;
use App\Archivo;
use App\Sector;
use App\Turno;
use App\Maquina;
use App\Casino;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
//use Illuminate\Contracts\View\View;
use PDF;
use Dompdf\Dompdf;
use View;



class EventualidadController extends Controller
{
  private static $atributos = [
    'id_eventualidad' => 'eventualidad',
    'id_fiscalizador' => 'Usuario fiscalizador',
    'observaciones' => 'Observaciones',
    'fecha_toma' => 'fecha registro eventualidad',
    'sectores' => 'sectores afectados',
    'islas' => 'islas afectadas',
    'maquinas' => 'maquinas afectadas',
    'file' => 'informe técnico',//informe técnico
    'id_tipo_eventualidad' => 'tipo eventualidad'
  ];

  private static $instance;

  public static function getInstancia() {
      if(!isset(self::$instance)){
          self::$instance = new EventualidadController();
      }
      return self::$instance;
  }


  // public function buscarTodoDesdeControlador(){
  //   $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
  //   foreach ($usuario->casinos as $casino) {
  //     $casinos[] = $casino->id_casino;
  //   }
  //
  //   $eventualidades = DB::table('eventualidad')
  //                       ->select('eventualidad.*','tipo_eventualidad.*','casino.*')
  //                       ->join('tipo_eventualidad','eventualidad.id_tipo_eventualidad','=','tipo_eventualidad.id_tipo_eventualidad')
  //                       ->join('casino','casino.id_casino','=','eventualidad.id_casino')
  //                       ->whereIn('casino.id_casino', $casinos)
  //                       ->orderBy('eventualidad.fecha_generacion','DES')
  //                       ->get();
  //
  //
  //
  //   // $eventualidades = Eventualidad::all();
  //   // $tiposEventualidades = TipoEventualidad::all();
  //   // $casinos = Casino::all();
  //   UsuarioController::getInstancia()->agregarSeccionReciente('Eventualidades', 'eventualidades');
  //
  //   return view('eventualidades',['eventualidades'=>$eventualidades , 'esControlador' => 0]);//$esControlador]);
  // }


  public function buscarTodoDesdeFiscalizador(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    foreach ($usuario->casinos as $casino) {
      $casinos[] = $casino->id_casino;
    }
    $eventualidades = DB::unprepared(DB::raw("CREATE TEMPORARY TABLE eventualidades_temp
                                                        AS (
                                                            SELECT eventualidad.*,DATE(eventualidad.fecha_generacion) as fecha, TIME(eventualidad.fecha_generacion) as hora,tipo_eventualidad.descripcion,casino.nombre
                                                            FROM eventualidad inner join casino on eventualidad.id_casino = casino.id_casino
                                                                 inner join tipo_eventualidad on tipo_eventualidad.id_tipo_eventualidad = eventualidad.id_tipo_eventualidad
                                                            );
                                         "
                                         )
                                   );
    $eventualidades = DB::table('eventualidades_temp')
                        ->whereIn('id_casino', $casinos)
                        ->orderBy('fecha', 'DES')
                        ->orderBy('hora','DES')
                        ->take(25)
                        ->get();
    $query1 = DB::statement(DB::raw("
                                       DROP TABLE eventualidades_temp
                                   "));

                        $turnos = Turno::all();
                        $tiposEventualidades = TipoEventualidad::all();
                        $casinos = $usuario->casinos;
      $esControlador=0;
      foreach ($usuario->roles as $rol) {
        if($rol->id_rol == 1 || $rol->id_rol == 2 || $rol->id_rol == 4){
            $esControlador=1;
            break;
        }
      }

      UsuarioController::getInstancia()->agregarSeccionReciente('Eventualidades', 'eventualidades');

      return view('eventualidades',['eventualidades'=>$eventualidades,
                  'esControlador' => 0/*$esControlador*/,
                   'turnos' =>$turnos,
                  'tiposEventualidades'=> $tiposEventualidades,
                  'casinos' => $casinos]);
  }

  //desde controlador busca
  public function buscarPorTipoFechaCasinoTurno(Request $request){
    $reglas = array();
    if(!empty($request->id_tipo_eventualidad) || $request->id_tipo_eventualidad !=0)
      $reglas[]=['id_tipo_eventualidad','=', $request['id_tipo_eventualidad']];

    if(!empty($request->id_casino) || $request->id_casino!=0)
      $reglas[]=['id_casino','=', $request['id_casino']];
    if(!empty($request->nro_turno) || $request->nro_turno!=0)
      $reglas[]=['turno','=', $request['nro_turno']];

      $eventualidades = DB::unprepared(DB::raw("CREATE TEMPORARY TABLE eventualidades_temp
                                                          AS (
                                                              SELECT eventualidad.*,DATE(eventualidad.fecha_generacion) as fecha, TIME(eventualidad.fecha_generacion) as hora,tipo_eventualidad.descripcion,casino.nombre
                                                              FROM eventualidad inner join casino on eventualidad.id_casino = casino.id_casino
                                                                   inner join tipo_eventualidad on tipo_eventualidad.id_tipo_eventualidad = eventualidad.id_tipo_eventualidad
                                                              );
                                           "
                                           )
                                     );

    if(empty($request->fecha))
    {
      $resultados = DB::table('eventualidades_temp')
                          ->where($reglas)
                          ->orderBy('fecha', 'DESC')
                          ->take(25)
                          ->get();

      }else{
        $fecha=explode(" ", $request->fecha);
        $mes=null;
        switch ($fecha[1]) {
          case 'Enero':
              $mes='01';
            break;
          case 'Febrero':
              $mes='02';
            break;
          case 'Marzo':
              $mes='03';
            break;
          case 'Abril':
              $mes='04';
            break;
          case 'Mayo':
              $mes='05';
            break;
          case 'Junio':
              $mes='06';
            break;
          case 'Julio':
              $mes='07';
            break;
          case 'Agosto':
              $mes='08';
            break;
          case 'Septiembre':
              $mes='09';
            break;
          case 'Octubre':
              $mes='10';
            break;
          case 'Noviembre':
              $mes='11';
            break;
          case 'Diciembre':
              $mes='12';
            break;
          default:
            # code...
            break;
        }
        $resultados = DB::table('eventualidades_temp')
                            ->where($reglas)
                            ->whereYear('fecha' , '=' ,$fecha[2])
                            ->whereMonth('fecha','=', $mes)
                            ->whereDay('fecha','=',$fecha[0])
                            ->orderBy('fecha', 'DESC')
                            ->take(25)
                            ->get();
      }
      $esControlador=0;
      $usuario = Usuario::find( session('id_usuario'));
      foreach ($usuario->roles as $rol) {
        if($rol->descripcion == "CONTROL" || $rol->descripcion == "ADMINISTRADOR" || $rol->descripcion == "SUPERUSUARIO"){
            $esControlador=1;
        }
      }
      $tiposEventualidades = TipoEventualidad::all();
      $casinos = $usuario->casinos;

      $query1 = DB::statement(DB::raw("
                                         DROP TABLE eventualidades_temp
                                     "));

      return ['eventualidades' => $resultados,
       'esControlador' => $esControlador,
       'tiposEventualidades'=> $tiposEventualidades,
       'casinos' => $casinos];
  }

  //hacer uno asi similar para sector y para isla
  public function obtenerSectorEnCasino($id_casino,$sector){
      //dado un casino,devuelve sectores que concuerden con el nombre del sector
      if($id_casino == 0){
        $id_usuario = session('id_usuario');
        $casino = Usuario::find($id_usuario)->casinos()->first();
        $id_casino = $casino->id_casino;
      }
      $sectores = Sector::where([['sector.id_casino' , '=' , $id_casino] ,['sector.descripcion' , 'like' , $sector . '%']])->get();
      foreach ($sectores as $sector) {
        $sector->descripcion = $sector->descripcion;
      }
      return ['sectores' => $sectores];
  }

  public function obtenerIslaEnCasino($id_casino,$nro_isla){
      //dado un casino,devuelve sectores que concuerden con el nro admin dado
      if($id_casino == 0){
        $id_usuario = session('id_usuario');
        $casino = Usuario::find($id_usuario)->casinos()->first();
        $id_casino = $casino->id_casino;
      }
      $islas = Isla::where([['isla.id_casino' , '=' , $id_casino] ,['isla.nro_isla' , 'like' , $nro_isla . '%']])->get();
      foreach ($islas as $isla) {
        $isla->nro_isla = $isla->nro_isla;
      }
      return ['islas' => $islas];
  }


  //crear funciones que le permitan seleccionar entre sectores, islas, o maquinas
  public function seleccionarAfectados($seleccion, $id_casino = 0){
    if($id_casino = 0){
      $id_casino = UsuarioController::getInstancia()->buscarCasinoDelUsuario(session('id_usuario'));//busca 1 solo casino, del usuario
    }

    //debe mandar tmb los tipos de eventualidades
    $tipoEventualidades = TipoEventualidad::all();

    switch($seleccion){
      case 1: //selecciona sectores
        return ['sectores' =>$this->buscarSectoresCasino($id_casino),'tipoEventualidades' =>$tipoEventualidades ];
        break;
      case 2: //selecciona islas
        return ['islas' =>$this->buscarIslasCasino($id_casino),'tipoEventualidades' =>$tipoEventualidades ];
        break;
      case 3: //selecciona maquinas
        return ['maquinas' => $this->buscarMaquinasCasino($id_casino),'tipoEventualidades' =>$tipoEventualidades ];
        break;
      default:
        return 0;
      break;
    }

  }

  private function buscarSectoresCasino($id_casino){
    $sectores =Sector::where('id_casino','=',$id_casino)->get();
    return $sectores;
  }

  private function buscarIslasCasino($id_casino){
    $islas =Isla::where('id_casino','=',$id_casino)->get();
    return $islas;
  }

  private function buscarMaquinasCasino($id_casino){
    $maquinas =Maquina::where('id_casino','=',$id_casino)->get();
    return $maquinas;
  }

  //crea la eventualidad e imprime la planilla
  public function crearEventualidad(){
    $rel= new \stdClass();
    $id_casino = UsuarioController::getInstancia()->buscarCasinoDelUsuario(session('id_usuario'));
    $casino = Casino::find($id_casino);
    $date = date('Y-m-d h:i:s', time());
    $turno = $this->turno($casino);//dependiendo del datetime y el casino, devuelvo el casino que corresponde
    $rel->nro_turno = $turno;

    $evento = new Eventualidad;
    $evento->turno=$turno;
    $evento->tipo_eventualidad()->associate(3); // --- ->no tiene tipo_eventualidad
    $evento->fecha_generacion = date('Y-m-d h:i:s', time());
    $evento->save();
    $evento->estado_eventualidad()->associate(6); //CREADO
    $evento->casino()->associate($id_casino);
    $evento->tipo_eventualidad()->associate(3);// --
    $evento->turno = $turno;
    $evento->save();
    $rel->casinoCod = $casino->nombre;
    $rel->maquinas=null;
    $rel->sectores=null;
    $rel->islas=null;
    $rel->tipo_ev_falla_tec=null;
    $rel->tipo_ev_ambiental = null;
    $rel->observaciones = null;

    $view = View::make('planillaEventualidades', compact('rel'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $rel->casinoCod."/".$rel->nro_turno, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));

  }

  //por si quiere imprimir de nuevo la planilla
  public function verPlanillaVacia($id){
    $evento = Eventualidad::find($id);
    $rel= new \stdClass();
    $rel->nro_turno = $evento->turno;
    $casino = $evento->casino;

    $rel->maquinas=null;
    $rel->sectores=null;
    $rel->islas=null;
    $rel->tipo_ev_falla_tec=null;
    $rel->tipo_ev_ambiental = null;
    $rel->observaciones = null;

    if($evento->id_estado_eventualidad == 1){
      if($evento->maquinas ==1){
          $result = "";

        foreach ($evento->maquinass as $mtm) {
          $result .= $mtm->nro_admin . ". ";
        }
        $rel->maquinas=$result;
      }elseif ($evento->sectores==1) {
        $sectores = DB::table('eventualidad')
                        ->select('sector.descripcion')
                        ->join('maquina_tiene_eventualidad','maquina_tiene_eventualidad.id_eventualidad','=','eventualidad.id_eventualidad')
                        ->join('maquina','maquina.id_maquina','=','maquina_tiene_eventualidad.id_maquina')
                        ->join('isla','isla.id_isla','=','maquina.id_isla')
                        ->join('sector','sector.id_sector','=','isla.id_sector')
                        ->where('maquina_tiene_eventualidad.id_eventualidad','=',$id)
                        ->distinct()
                        ->get();



        $result = "";
        foreach ($sectores as $s) {
          $result .= $s->descripcion . ". ";
        }
        //$result .= ".";
        $rel->sectores=$result;
      }elseif ($evento->islas==1) {
        $islas = DB::table('eventualidad')
                        ->select('isla.nro_isla')
                        ->join('maquina_tiene_eventualidad','maquina_tiene_eventualidad.id_eventualidad','=','eventualidad.id_eventualidad')
                        ->join('maquina','maquina.id_maquina','=','maquina_tiene_eventualidad.id_maquina')
                        ->join('isla','isla.id_isla','=','maquina.id_isla')
                        ->where('maquina_tiene_eventualidad.id_eventualidad','=',$id)
                        ->distinct()
                        ->get();



        $result = "";
        foreach ($islas as $s) {
          $result .= $s->nro_isla . ". ";
        }
        //$result .= ".";
        $rel->islas=$result;

      }
      if($evento->id_tipo_eventualidad == 1){
        $rel->tipo_ev_falla_tec = "X";
      }else{
        $rel->tipo_ev_ambiental = "X";
      }
      $rel->observaciones = $evento->observaciones;
    }

    //  $cas = Casino::find($casino->id_casino);
    $rel->casinoCod = $casino->nombre;
    $view = View::make('planillaEventualidades', compact('rel'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 815, $rel->casinoCod."/".$rel->nro_turno, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }

  //crea la eventualidad que el fiscalizador vio
  //el fiscalizador puede seleccionar sectores O islas O maquinas. (or excluyente)
  //sugiero que el fisca seleccione primero cual de esas va a cargar, y desp si se le permita elegir
  public function CargarYGuardarEventualidad(Request $req){

        $validator = Validator::make($req->all(), [
          'id_eventualidad' => 'nullable|exists:eventualidad,id_eventualidad',
          'id_fiscalizador' => 'nullable|exists:usuario,id_usuario',
          'observaciones' => 'required| max:100',
          'fecha_toma' => 'required|date',
          'sectores' => 'nullable',
          'sectores.*.id' => 'required|exists:sector,id_sector',
          'islas' => 'nullable',
          'islas.*.id' => 'required|exists:isla,id_isla',
          'maquinas' => 'nullable',
          'maquinas.*.id' => 'required|exists:maquina,id_maquina',
          'file' => 'sometimes|mimes:pdf',//informe técnico
          'id_tipo_eventualidad' => 'required |exists:tipo_eventualidad,id_tipo_eventualidad',
          'seleccion' => 'required'
          ], array(), self::$atributos)->after(function ($validator){

            switch ($validator->getData()['seleccion']) {
              case 0://islas
                if(empty($validator->getData()['islas'])){
                  $validator->errors()->add('islas', 'No se han cargado islas a la eventualidad.');
                }
                break;
              case 1://sectores
                if(empty($validator->getData()['sectores'])){
                  $validator->errors()->add('sectores', 'No se han cargado sectores a la eventualidad.');
                }
                break;
              case 2://maquinas
                  if(empty($validator->getData()['maquinas'])){
                    $validator->errors()->add('maquinas', 'No se han cargado máquinas a la eventualidad.');
                  }
                break;
              default:
                break;
            }

          })->validate();

          if(isset($validator))
          {
            if ($validator->fails())
            {
              return [
                    'errors' => $v->getMessageBag()->toArray()
                ];
            }
          }

        $evento = Eventualidad::find($req['id_eventualidad']);

        $id_usuario = session('id_usuario');

        if($this->noEsFiscalizador($evento->fiscalizadores,$id_usuario,$req['id_fiscalizador'])){
          if($req['id_fiscalizador']== null)
          {
            $evento->fiscalizadores()->attach($id_usuario);
          }else{
            $evento->fiscalizadores()->attach($req['id_fiscalizador']);
          }
        }

        $evento->observaciones= $req['observaciones'];
        $evento->fecha_toma= $req['fecha_toma'];
        $evento->tipo_eventualidad()->associate($req['id_tipo_eventualidad']);
        $evento->estado_eventualidad()->associate(1); //notificado


        if(!empty($req['sectores']))
        {
          $evento->sectores=1;
          $coso = explode(",",$req['sectores']);
          foreach($coso as $sec)
          {
            $sector = Sector::find($sec);
            foreach ($sector->islas as $isla) {
              foreach ($isla->maquinas as $maquina) {
                $evento->maquinass()->attach($maquina->id_maquina);
                //MTMController::getInstancia()->asociarAEventualidad($maquina['id_maquina']);
                $evento->save();
              }
            }
          }
        }

        if(!empty($req['islas']))
        {
            $evento->islas=1;
            $coso = explode(",",$req['islas']);
          foreach($coso as $is)
          {

            $isla = Isla::find($is);
            foreach ($isla->maquinas as $maquina)
            {
              $evento->maquinass()->attach($maquina->id_maquina);
              //MTMController::getInstancia()->asociarAEventualidad($maquina['id_maquina']);  //no se hace porque se hará cuando el controlador lo valide
              $evento->save();
            }
          }
        }
        if(!empty($req['maquinas']))
        {
          $evento->maquinas=1;
          $coso = explode(",",$req['maquinas']);
          foreach($coso as $maq)
          {
            $evento->maquinass()->attach($maq);
            //MTMController::getInstancia()->asociarAEventualidad($maquina['id_maquina']);  //no se hace porque se hará cuando el controlador lo valide
            $evento->save();
          }
        }

        if($req->file != null)
        {

              $archivo=new Archivo;
              $file=$req->file;
              $nombre_archivo=$file->getClientOriginalName();
              $data=base64_encode(file_get_contents($file->getRealPath()));
              $archivo->nombre_archivo=$nombre_archivo;
              $archivo->archivo=$data;
              $archivo->save();
              $evento->archivo()->associate($archivo->id_archivo);

        }

        $evento->save();

        // notificaciones
        $usuarios = UsuarioController::getInstancia()->obtenerControladores($evento->id_casino);
        foreach ($usuarios as $user){
          $u = Usuario::find($user->id_usuario);
          $u->notify(new NuevaIntervencionTecnica($evento));
        }

        return 1;
  }

  public function eliminarEventualidad($id){
    $evento = Eventualidad::find($id);
    if($evento->fecha_toma == null){
      $evento->casino()->dissociate();
      $evento->estado_eventualidad()->dissociate();
      Eventualidad::destroy($id);
    }
    return 1;
  }

  private function noEsFiscalizador($fiscalizadores,$id_usuario,$id_fiscalizador){
    $aux = true;
    if(empty($fiscalizadores)){
      return true;
    }
    foreach ($fiscalizadores as $fisca) {
      if($fisca->id_usuario == $id_usuario || $fisca->id_usuario==$id_fiscalizador){
        $aux=false; //si es fisca
      }
    }
    return $aux;
  }

  //desde controlador
  public function visualizarEventualidadID($id_eventualidad){
      $resultados = DB::table('maquina')
                    ->select('maquina.id_maquina','maquina.nro_admin', 'isla.nro_isla', 'sector.descripcion')
                    ->join('isla','isla.id_isla','=','maquina.id_isla')
                    ->join('sector','sector.id_sector','=','isla.id_sector')
                    ->join('maquina_tiene_eventualidad','maquina_tiene_eventualidad.id_maquina','=','maquina.id_maquina')
                    ->where('maquina_tiene_eventualidad.id_eventualidad','=',$id_eventualidad)
                    ->get();
      $ev = Eventualidad::find($id_eventualidad);

      if(isset( $ev->archivo)){
        $data = $ev->archivo->archivo;
        $pdf =Response::make(base64_decode($data), 200, [ 'Content-Type' => 'application/pdf',
                                                            'Content-Disposition' => 'inline; filename="'. $ev->archivo->nombre_archivo  . '"']);
        $ruta = "Eventualidad-".$ev->tipo_eventualidad->descripcion."-".$ev->archivo->nombre_archivo.".pdf";
        file_put_contents($ruta, $pdf);

        return ['maquinas' =>$resultados,
                'eventualidad' => $ev,
                'fiscalizador'=> $ev->fiscalizadores->first(),
                'ruta' => $ruta
               ];
      }

      return ['maquinas' =>$resultados,
              'eventualidad' => $ev,
              'fiscalizador'=> $ev->fiscalizadores->first(),
              'pdf' => null
             ];

    }

  public function validarEventualidad(Request $request){  //recibe request con id_eventualidad y las maquinas que si se van a marcar como observadass por eventualidad
      $eventualidad = Eventualidad::find($request['id_eventualidad']);
      foreach ($request['maquinas'] as $maquina) {
        //marca cada maq como observada
        MTMController::getInstancia()->asociarMTMsEventualidad($maquina['id_maquina']);
      }

    }

  public function leerArchivoEventualidad($id){
      $file = Eventualidad::find($id);
      $data = $file->archivo->archivo;
      //ver como retornar esto :P
      return Response::make(base64_decode($data), 200, [ 'Content-Type' => 'applicatio n/pdf',
                                                        'Content-Disposition' => 'inline; filename="'. $file->nombre_archivo  . '"']);
    }

  private function turno($casino){//dependiendo del datetime y el casino, devuelvo el casino que corresponde
     $day_of_week = date("w");
     $this_hour = date("H:i:s");
     $turnos = Turno::where('id_casino',$casino)->get();
     $bandera = false;
     $i=0;
    //  while ($bandera != true) {
    //    if(($day_of_week >= $turnos[$i]->dia_desde && $day_of_week <= $turnos[$i]->dia_hasta) && ($this_hour >= $turnos[$i]->entrada && $day_of_week <= $turnos[$i]->salidas)){
    //      $bandera = true;
    //      $turno= $turnos[$i];
    //    }
    //    $i++;
    //  }

     return 1; //$turno->nro_turno;
  }


}
