<?php

namespace App\Http\Controllers;

use App\Archivo;
use App\Casino;
use App\Eventualidad;
use App\Isla;
use App\Maquina;
use App\Notifications\NuevaIntervencionTecnica;
use App\Sector;
use App\TipoEventualidad;
use App\Turno;
use App\Usuario;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
//use Illuminate\Contracts\View\View;
use Response;
use Validator;
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
        'file' => 'informe técnico', //informe técnico
        'id_tipo_eventualidad' => 'tipo eventualidad',
    ];

    private static $instance;

    public static function getInstancia()
    {
        if (!isset(self::$instance)) {
            self::$instance = new EventualidadController();
        }
        return self::$instance;
    }

    public function buscarTodoDesdeFiscalizador()
    {
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
            ->orderBy('hora', 'DES')
            ->take(25)
            ->get();
        $query1 = DB::statement(DB::raw("
                                       DROP TABLE eventualidades_temp
                                   "));

        $sectores = DB::table('sector')
        ->whereIn('sector.id_casino',$casinos)->get();
        $islas = DB::table('isla')
        ->join('sector','isla.id_sector','=','sector.id_sector')
        ->whereIn('sector.id_casino',$casinos)->get();

        $turnos = Turno::all();
        $tiposEventualidades = TipoEventualidad::all();
        $casinos = $usuario->casinos;
        $esControlador = 0;
        $usuario = Usuario::find(session('id_usuario'));
        foreach ($usuario->roles as $rol) {
            if ($rol->descripcion == "CONTROL" || $rol->descripcion == "ADMINISTRADOR" || $rol->descripcion == "SUPERUSUARIO") {
                $esControlador = 1;
            }
        }
        UsuarioController::getInstancia()->agregarSeccionReciente('Eventualidades', 'eventualidades');

        return view('eventualidades', ['eventualidades' => $eventualidades,
            'esControlador' => $esControlador,
            'turnos' => $turnos,
            'tiposEventualidades' => $tiposEventualidades,
            'casinos' => $casinos,
            'sectores' => $sectores,
            'islas' => $islas]);
    }

    //desde controlador busca
    public function buscarPorTipoFechaCasinoTurno(Request $request)
    {
        $usuario = Usuario::find(session('id_usuario'));
        $casinos = $usuario->casinos;
        $cas_id = [];
        foreach($casinos as $cas){
          $cas_id[] = $cas->id_casino;
        }
        $reglas = array();
        if (!empty($request->id_tipo_eventualidad) || $request->id_tipo_eventualidad != 0) {
            $reglas[] = ['te.id_tipo_eventualidad', '=', $request['id_tipo_eventualidad']];
        }

        if (!empty($request->id_casino) || $request->id_casino != 0) {
            $reglas[] = ['c.id_casino', '=', $request['id_casino']];
        }

        //Si pone '-', buscamos los turnos null osea que se hicieron fuera de turno.
        $turno_null = false;
        if (!empty($request->nro_turno) && $request->nro_turno != '-') {
            $reglas[] = ['e.turno', '=', $request['nro_turno']];
        }
        else if (!empty($request->nro_turno) && $request->nro_turno == '-'){
            $turno_null = true;
        } 


        //Left join pq puede ser que no tenga ninguna maquina.
        $resultados = DB::table('eventualidad as e')
        ->selectRaw('e.*,DATE(e.fecha_generacion) as fecha, TIME(e.fecha_generacion) as hora,te.descripcion,c.nombre')
        ->join('tipo_eventualidad as te','te.id_tipo_eventualidad','=','e.id_tipo_eventualidad')
        ->join('casino as c','e.id_casino','=','c.id_casino')
        ->leftJoin('maquina_tiene_eventualidad as me','me.id_eventualidad','=','e.id_eventualidad')
        ->leftJoin('maquina as m','me.id_maquina','=','m.id_maquina')
        ->leftJoin('isla as i','m.id_isla','=','i.id_isla');
  
        $resultados = $resultados
        ->whereIn('e.id_casino',$cas_id)//Me quedo con solo los del casino del user
        ->where($reglas);

        if($turno_null){
            $resultados = $resultados->whereNull('e.turno');
        }

        if(!is_null($request->id_sector)){
            $resultados = $resultados->where('i.id_sector','=',$request->id_sector);
        }
        if(!is_null($request->id_isla)){
            $resultados = $resultados->where('i.id_isla','=',$request->id_isla);
        }
        if(!is_null($request->nro_admin)){
            $resultados = $resultados->where('m.nro_admin','=',$request->nro_admin);
        }
        
        if(!empty($request->fecha)){
            $fecha = explode(" ", $request->fecha);
            $mes = $this->traducirMes($fecha[1]);;
            $resultados = $resultados->whereYear('e.fecha_generacion', '=', $fecha[2])
                ->whereMonth('e.fecha_generacion', '=', $mes)
                ->whereDay('e.fecha_generacion', '=', $fecha[0])
                ->orderBy('e.fecha_generacion', 'DESC');
        }

        $resultados = $resultados->orderBy('fecha', 'DESC')
        ->orderBy('hora','desc');

        //Elimina duplicados con groupby, si lo hacemos con distinct no anda el paginate
        $resultados_pag = $resultados
        ->groupBy('e.id_eventualidad')
        ->paginate($request->page_size);
        $esControlador = 0;
        foreach ($usuario->roles as $rol) {
            if ($rol->descripcion == "CONTROL" || $rol->descripcion == "ADMINISTRADOR" || $rol->descripcion == "SUPERUSUARIO") {
                $esControlador = 1;
            }
        }
        $tiposEventualidades = TipoEventualidad::all();

        return ['eventualidades' => $resultados_pag,
            'esControlador' => $esControlador,
            'tiposEventualidades' => $tiposEventualidades,
            'casinos' => $casinos];
    }

    private function traducirMes($mes){
        switch ($mes) {
            case 'Enero':
                return '01';
            case 'Febrero':
                return '02';
            case 'Marzo':
                return '03';
            case 'Abril':
                return '04';
            case 'Mayo':
                return '05';
            case 'Junio':
                return '06';
            case 'Julio':
                return '07';
            case 'Agosto':
                return '08';
            case 'Septiembre':
                return '09';
            case 'Octubre':
                return '10';
            case 'Noviembre':
                return '11';
            case 'Diciembre':
                return '12';
            default:
                return null;
        }
    }

    public function obtenerSectorEnCasino($id_casino, $sector)
    {
        $id_usuario = session('id_usuario');
        $casinos = Usuario::find($id_usuario)->casinos();
        if ($id_casino == 0){
            $id_casino = $casinos->first()->id_casino;
        }
        else if ($casinos->where('casino.id_casino',$id_casino)->count() == 0){
            return ['sectores' => []];
        }
        $sectores = Sector::where([['sector.id_casino', '=', $id_casino], ['sector.descripcion', 'like', $sector . '%']])->get();
        foreach ($sectores as $sector) {
            $sector->descripcion = $sector->descripcion;
        }
        return ['sectores' => $sectores];
    }

    public function obtenerIslaEnCasino($id_casino, $nro_isla)
    {
        $id_usuario = session('id_usuario');
        $casinos = Usuario::find($id_usuario)->casinos();
        if ($id_casino == 0) {
            $id_casino = $casinos->first()->id_casino;
        }
        else if ($casinos->where('id_casino',$id_casino)->count() == 0){
            return ['islas' => []];
        }
        $islas = Isla::where([['isla.id_casino', '=', $id_casino], ['isla.nro_isla', 'like', $nro_isla . '%']])->get();
        foreach ($islas as $isla) {
            $isla->nro_isla = $isla->nro_isla;
        }
        return ['islas' => $islas];
    }

    //crear funciones que le permitan seleccionar entre sectores, islas, o maquinas
    public function seleccionarAfectados($seleccion, $id_casino = 0)
    {
        if ($id_casino = 0) {
            $id_casino = UsuarioController::getInstancia()->buscarCasinoDelUsuario(session('id_usuario')); //busca 1 solo casino, del usuario
        }

        //debe mandar tmb los tipos de eventualidades
        $tipoEventualidades = TipoEventualidad::all();

        switch ($seleccion) {
            case 1: //selecciona sectores
                return ['sectores' => $this->buscarSectoresCasino($id_casino), 'tipoEventualidades' => $tipoEventualidades];
                break;
            case 2: //selecciona islas
                return ['islas' => $this->buscarIslasCasino($id_casino), 'tipoEventualidades' => $tipoEventualidades];
                break;
            case 3: //selecciona maquinas
                return ['maquinas' => $this->buscarMaquinasCasino($id_casino), 'tipoEventualidades' => $tipoEventualidades];
                break;
            default:
                return 0;
                break;
        }

    }

    private function buscarSectoresCasino($id_casino)
    {
        $sectores = Sector::where('id_casino', '=', $id_casino)->get();
        return $sectores;
    }

    private function buscarIslasCasino($id_casino)
    {
        $islas = Isla::where('id_casino', '=', $id_casino)->get();
        return $islas;
    }

    private function buscarMaquinasCasino($id_casino)
    {
        $maquinas = Maquina::where('id_casino', '=', $id_casino)->get();
        return $maquinas;
    }

    //crea la eventualidad e imprime la planilla
    public function crearEventualidad($id_casino){
        if($id_casino === null) return;
        $ucontrol = UsuarioController::getInstancia(); 
        $user = $ucontrol->quienSoy()['usuario'];
        if(!$ucontrol->usuarioTieneCasinoCorrespondiente($user->id_usuario,$id_casino)) 
          return;
        
        $casino = Casino::find($id_casino);
        if($casino === null) return;

        $turno = $this->turno($casino->id_casino);
        if($turno->count() == 0) $turno = null;
        else $turno = $turno->first()->nro_turno;

        DB::beginTransaction();
        $evento = new Eventualidad;
        try{
          $evento->id_tipo_eventualidad = 3; // --- ->no tiene tipo_eventualidad
          $evento->id_estado_eventualidad = 6; //CREADO
          $evento->id_casino = $id_casino;
          $evento->fecha_generacion = date('Y-m-d h:i:s', time());
          $evento->turno = $turno;
          $evento->save();
          DB::commit();
        }
        catch (Exception $e){
          DB::rollBack();
          throw $e;
        }

        return $evento->id_eventualidad;
    }

    //por si quiere imprimir de nuevo la planilla
    public function verPlanillaVacia($id)
    {
        $evento = Eventualidad::find($id);
        $rel = new \stdClass();
        $rel->nro_turno = $evento->turno;
        $casino = $evento->casino;

        $rel->maquinas = null;
        $sectores = DB::table('maquina_tiene_eventualidad')
            ->select('maquina.nro_admin')
            ->join('maquina', 'maquina.id_maquina', '=', 'maquina_tiene_eventualidad.id_maquina')
            ->where('maquina_tiene_eventualidad.id_eventualidad', '=', $id)
            ->distinct()
            ->get();

        $result = "";
        foreach ($evento->maquinas()->get() as $idx => $mtm) {
            if ($idx == 0) {
                $result = $mtm->nro_admin . "";
            } else {
                $result .= ", " . $mtm->nro_admin;
            }

        }
        $rel->maquinas = $result;

        $rel->sectores = null;
        $sectores = DB::table('maquina_tiene_eventualidad')
            ->select('sector.descripcion')
            ->join('maquina', 'maquina.id_maquina', '=', 'maquina_tiene_eventualidad.id_maquina')
            ->join('isla', 'isla.id_isla', '=', 'maquina.id_isla')
            ->join('sector', 'sector.id_sector', '=', 'isla.id_sector')
            ->where('maquina_tiene_eventualidad.id_eventualidad', '=', $id)
            ->distinct()
            ->get();
        $result = "";
        foreach ($sectores as $idx => $s) {
            if ($idx == 0) {
                $result = $s->descripcion . "";
            } else {
                $result .= ", " . $s->descripcion;
            }

        }
        $rel->sectores = $result;

        $islas = DB::table('maquina_tiene_eventualidad')
            ->select('isla.nro_isla')
            ->join('maquina', 'maquina.id_maquina', '=', 'maquina_tiene_eventualidad.id_maquina')
            ->join('isla', 'isla.id_isla', '=', 'maquina.id_isla')
            ->where('maquina_tiene_eventualidad.id_eventualidad', '=', $id)
            ->distinct()
            ->get();

        $result = "";
        foreach ($islas as $idx => $i) {
            if ($idx == 0) {
                $result .= $i->nro_isla . "";
            } else {
                $result .= ", " . $i->nro_isla;
            }

        }
        $rel->islas = $result;

        $rel->tipo_ev_falla_tec = null;
        $rel->tipo_ev_ambiental = null;
        if ($evento->id_tipo_eventualidad == 1) {
            $rel->tipo_ev_falla_tec = "X";
        } else if ($evento->id_tipo_eventualidad == 2){
            $rel->tipo_ev_ambiental = "X";
        }

        $rel->observaciones = $evento->observaciones;

        //  $cas = Casino::find($casino->id_casino);
        $rel->casinoCod = $casino->nombre;
        $view = View::make('planillaEventualidades', compact('rel'));
        $dompdf = new Dompdf();
        $dompdf->set_paper('A4', 'portrait');
        $dompdf->loadHtml($view->render());
        $dompdf->render();
        $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
        $dompdf->getCanvas()->page_text(20, 815, $rel->casinoCod . "/" . $rel->nro_turno, $font, 10, array(0, 0, 0));
        $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0, 0, 0));
        return $dompdf->stream('planilla.pdf', array('Attachment' => 0));
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
            'file' => 'sometimes|mimes:pdf', //informe técnico
            'id_tipo_eventualidad' => 'required |exists:tipo_eventualidad,id_tipo_eventualidad',
            'seleccion' => 'required',
        ], array(), self::$atributos)->after(function ($validator) {
            switch ($validator->getData()['seleccion']) {
                case 0: //islas
                    if (empty($validator->getData()['islas'])) {
                        $validator->errors()->add('islas', 'No se han cargado islas a la eventualidad.');
                    }
                    break;
                case 1: //sectores
                    if (empty($validator->getData()['sectores'])) {
                        $validator->errors()->add('sectores', 'No se han cargado sectores a la eventualidad.');
                    }
                    break;
                case 2: //maquinas
                    if (empty($validator->getData()['maquinas'])) {
                        $validator->errors()->add('maquinas', 'No se han cargado máquinas a la eventualidad.');
                    }
                    break;
                default:
                    break;
            }
        })->validate();

        if (isset($validator)) {
            if ($validator->fails()) {
                return [
                    'errors' => $validator->getMessageBag()->toArray(),
                ];
            }
        }

        $evento = Eventualidad::find($req['id_eventualidad']);
        $ucontrol = UsuarioController::getInstancia(); 
        $user = $ucontrol->quienSoy()['usuario'];
        $id_usuario = $user->id_usuario;
        if(!$user->es_fiscalizador || !$user->usuarioTieneCasino($evento->id_casino)){
          //TODO: agregar manejo de errores real
          dump('No tiene acceso a esa accion');
          return;
        }

        $maquinas = array();
        if (!empty($req['sectores'])) {
            $evento->sectores = 1;
            $sectores = explode(",", $req['sectores']);
            foreach ($sectores as $sec) {
                $sector = Sector::find($sec);
                foreach ($sector->islas as $isla) {
                    foreach ($isla->maquinas as $maq) {
                        $maquinas[] = $maq->id_maquina;
                    }
                }
            }
        }
        if (!empty($req['islas'])) {
            $evento->islas = 1;
            $islas = explode(",", $req['islas']);
            foreach ($islas as $is) {
                $isla = Isla::find($is);
                foreach ($isla->maquinas as $maq) {
                    $maquinas[] = $maq->id_maquina;
                }
            }
        }

        if (!empty($req['maquinas'])) {
            $evento->maquinas = 1;
            $maqs_enviadas = explode(",", $req['maquinas']);

            foreach($maqs_enviadas as $maq){
              $maqbd = Maquina::find($maq);
              if(!is_null($maqbd)){
                $maquinas[] = $maqbd->id_maquina;
              }
            }
        }
        DB::beginTransaction();
        try {
            $evento->observaciones = $req['observaciones'];
            $evento->fecha_toma = $req['fecha_toma'];
            $evento->tipo_eventualidad()->associate($req['id_tipo_eventualidad']);
            $evento->estado_eventualidad()->associate(1); //notificado
            $evento->maquinas()->sync($maquinas, false);
            $evento->fiscalizadores()->sync([$req['id_fiscalizador']], false);
            $evento->save();
            if ($req->file != null) {
                $archivo = new Archivo;
                $file = $req->file;
                $nombre_archivo = $file->getClientOriginalName();
                $data = base64_encode(file_get_contents($file->getRealPath()));
                $archivo->nombre_archivo = $nombre_archivo;
                $archivo->archivo = $data;
                $archivo->save();
                $evento->archivo()->associate($archivo->id_archivo);
            }
            $evento->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
            return;
        }
        // notificaciones
        $usuarios = UsuarioController::getInstancia()->obtenerControladores($evento->id_casino, $id_usuario);
        foreach ($usuarios as $user) {
            $u = Usuario::find($user->id_usuario);
            if ($u != null) {
                $u->notify(new NuevaIntervencionTecnica($evento));
            }
        }

        return 1;
    }

    public function eliminarEventualidad($id){
        $ucontrol = UsuarioController::getInstancia(); 
        $user = $ucontrol->quienSoy()['usuario'];
        $evento = Eventualidad::find($id);
        if($evento === null) return 0; 
        if(!$user->es_controlador || !$user->usuarioTieneCasino($evento->id_casino)) 
          return 0;
        
        if ($evento->fecha_toma == null) {
            $evento->casino()->dissociate();
            $evento->estado_eventualidad()->dissociate();
            Eventualidad::destroy($id);
        }
        return 1;
    }

    private function noEsFiscalizador($fiscalizadores, $id_usuario, $id_fiscalizador)
    {
        $aux = true;
        if (empty($fiscalizadores)) {
            return true;
        }
        foreach ($fiscalizadores as $fisca) {
            if ($fisca->id_usuario == $id_usuario || $fisca->id_usuario == $id_fiscalizador) {
                $aux = false; //si es fisca
            }
        }
        return $aux;
    }

    //desde controlador
    public function visualizarEventualidadID($id_eventualidad)
    {
        $resultados = DB::table('maquina')
            ->select('maquina.id_maquina', 'maquina.nro_admin', 'isla.nro_isla', 'sector.descripcion')
            ->join('isla', 'isla.id_isla', '=', 'maquina.id_isla')
            ->join('sector', 'sector.id_sector', '=', 'isla.id_sector')
            ->join('maquina_tiene_eventualidad', 'maquina_tiene_eventualidad.id_maquina', '=', 'maquina.id_maquina')
            ->where('maquina_tiene_eventualidad.id_eventualidad', '=', $id_eventualidad)
            ->get();
        $ev = Eventualidad::find($id_eventualidad);

        return ['maquinas' => $resultados,
            'eventualidad' => $ev,
            'fiscalizador' => $ev->fiscalizadores->first()
        ];

    }

    public function validarEventualidad($id_eventualidad)
    { //recibe request con id_eventualidad y las maquinas que si se van a marcar como observadass por eventualidad
        // foreach ($request['maquinas'] as $maquina) {
        //   //marca cada maq como observada
        //   MTMController::getInstancia()->asociarMTMsEventualidad($maquina['id_maquina']);
        // }
        $ucontrol = UsuarioController::getInstancia(); 
        $user = $ucontrol->quienSoy()['usuario'];
        $evento = Eventualidad::find($id_eventualidad);
        if($evento === null) return 0; 
        if(!$user->es_controlador || !$user->usuarioTieneCasino($evento->id_casino)) 
          return 0;

        $evento->estado_eventualidad()->associate(4);
        $evento->save();
        return 1;
    }

    public function leerArchivoEventualidad($id)
    {
        $ucontrol = UsuarioController::getInstancia(); 
        $user = $ucontrol->quienSoy()['usuario'];
        $file = Eventualidad::find($id);
        if($file === null) return 0; 
        if(!$user->usuarioTieneCasino($file->id_casino)) 
          return 0;
        
        $data = $file->archivo->archivo;
        return Response::make(base64_decode($data), 200, ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $file->nombre_archivo . '"']);
    }

    private function turno($casino){ //dependiendo del datetime y el casino, devuelvo el casino que corresponde
        $day_of_week = date("w"); //0 domingo, 6 para sabado
        //Necesitamos, 1 para lunes, 7 para domingo
        //0 domingo -> 7 domingo
        //1 lunes -> se mantiene igual
        //2 martes -> se mantiene igual
        //3 miercoles -> se mantiene igual
        //4 jueves -> se mantiene igual
        //5 viernes -> se mantiene igual
        //6 Sabado -> se mantiene igual
        if($day_of_week == 0) $day_of_week = 7;
        $this_hour = date("H:i:s");
        $turnos = Turno::where('id_casino', $casino);
        $turnos = $turnos->where('dia_desde','<=',$day_of_week);
        $turnos = $turnos->where('dia_hasta','>=',$day_of_week);
        $turnos = $turnos->where('entrada','<=',$this_hour);
        $turnos = $turnos->where('salida','>=',$this_hour);
        return $turnos;
    }    

}
