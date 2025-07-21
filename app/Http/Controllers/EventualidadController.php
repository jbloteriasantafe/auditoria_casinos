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
use App\Eventualidades;
use App\ObservacionEventualidades;
use Illuminate\Support\Facades\Storage;

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

    private static $errores = [

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
            'islas' => $islas,
            'usuario' => $usuario]);
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

    public function guardarEventualidad(Request $request)
    {

    Validator::make($request->all(),[
        'fecha_toma' => 'required|date',
        'turno' => 'required|integer|min:1|max:3',
        'horario' => 'nullable|string|max:50',
        'id_casino' => 'required|exists:casino,id_casino',
        'procedimientos' => 'required|array',
        'procedimientos.*.nombre' => 'string',
        'procedimientos.*.check' => 'nullable|boolean',
        'procedimientos.*.asterisco' => 'nullable|boolean',
        'procedimientos.*.observaciones' => 'nullable|string|max:1000',
        'menores' => 'required|in:Si,No',
        'fumadores' => 'required|in:Si,No',
        'boletin_adjunto' => 'required|in:Si,No',
        'observaciones' => 'nullable|string|max:4000',
    ]);

    try {
        DB::beginTransaction();

        $eventualidades = new Eventualidades();
        //$eventualidades->fecha_toma = date('Y-m-d h:i:s', time());
        $eventualidades->fecha_toma = $request->fecha_toma;
        $eventualidades->fecha_carga = date('Y-m-d h:i:s', time());
        $eventualidades->id_turno = $request->turno;
        $eventualidades->horario = $request->horario;
        $eventualidades->id_casino = $request->id_casino;
        $eventualidades->procedimientos = $request->has('procedimientos')
          ? json_encode($request->input('procedimientos'),JSON_UNESCAPED_UNICODE)
          : null;
        $eventualidades->menores = $request->menores;
        $eventualidades->fumadores = $request->fumadores;
        $eventualidades->boletin_adjunto = $request->boletin_adjunto;
        $eventualidades->observaciones = $request->observaciones;
        $eventualidades->estado_eventualidad = 1; //generado
        $eventualidades->otros_fiscalizadores = $request->otros_fiscalizadores;
        $eventualidades->id_usuario_generador = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
        $eventualidades->save();

        $eventualidades->procedimientos = json_decode($eventualidades->procedimientos, true);

        DB::commit();

        return response()->json(['success' => true, 'id' => $eventualidades->id_eventualidades]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }

}


public function PDF($id) {
  $eventualidad = Eventualidades::with(['casino','turno'])->findOrFail($id);
  if (is_string($eventualidad->procedimientos)) {
    $eventualidad->procedimientos = json_decode($eventualidad->procedimientos, true);
  }
  $view = View::make('eventualidad', compact('eventualidad'));
  $dompdf = new Dompdf();
  $dompdf->set_paper('A4', 'portrait');
  $dompdf->loadHtml($view);
  $dompdf->render();

  $canvas = $dompdf->getCanvas();
    $cpdf   = $canvas->get_cpdf();
    $cpdf->addInfo('Subject','event_loteria_pdf');
    $cpdf->addInfo('Title', $id);

    $output   = $dompdf->output();

    $dir = storage_path('app/public/EventualidadesCrudas');
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    $hoy = date('Y_m_d',strtotime($eventualidad->fecha_toma));
    $filename = "eventualidades{$eventualidad->id_eventualidades}_{$hoy}_{$eventualidad->casino->nombre}_turno_{$eventualidad->turno->nro_turno}.pdf";
    $path     = "{$dir}/{$filename}";

    file_put_contents($path, $output);

    return response()->download(
        $path,
        $filename,
        ['Content-Type' => 'application/pdf']
      );
}
public function obtenerTurnos($id_casino)
{
    $dia = date('N');                      // 1..7
    if (request()->query('useYesterday')) {
        $dia = $dia - 1 ?: 7;              // si baja de 1 lo llevas a 7
    }

    $turnos = Turno::where('id_casino', $id_casino)
        ->where('dia_desde', '<=', $dia)
        ->where('dia_hasta', '>=', $dia)
        ->orderBy('nro_turno')
        ->get(['id_turno','nro_turno','entrada','salida']);

    return response()->json(['turnos' => $turnos]);
}



public function ultimasIntervenciones(Request $request)
{
    $user = Usuario::find(session('id_usuario'));
    // Suponiendo que la relación se llama "casinos" y cada Casino tiene id_casino
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $query = Eventualidades::with(['casino','turno'])
              // aquí filtramos por los casinos del usuario
              ->whereIn('id_casino', $allowedCasinoIds)
              ->orderBy('fecha_toma','desc');

    if ($f_casino = $request->query('id_casino')) {
      if (in_array($f_casino, $allowedCasinoIds)) {
        $query->where('id_casino', $f_casino);
      }
    }
    if ($f_estado = $request->query('estado_eventualidad')) {
      $query->where('estado_eventualidad', $f_estado);
    }
    if ($f_fecha = $request->query('fecha')) {
      $query->where('fecha_toma',">=", $f_fecha);
    }
    if ($f_hasta = $request->query('hasta')){
      $query->where('fecha_toma',"<=",$f_hasta);
    }
    if ($f_turno = $request->query('nro_turno')) {
      $query->whereHas('turno', function($q) use ($f_turno) {
        $q->where('nro_turno', $f_turno);
      });
    }
    if ($request->observados == 1) {
    $query->whereHas('observaciones', function($q){
        $q->whereNotNull('id_archivo');
      });
    }

    $total = $query->count();
    $evs   = $query
               ->skip(($page-1)*$perPage)
               ->take($perPage)
               ->get();

    $esControlador = $user->roles
        ->pluck('descripcion')
        ->intersect(['ADMINISTRADOR','SUPERUSUARIO'])
        ->isNotEmpty() ? 1 : 0;

    return response()->json([
      'intervenciones' => $evs,
      'controlador'    => $esControlador,
      'pagination'     => [
         'current_page' => $page,
         'per_page'     => $perPage,
         'total'        => $total,
      ],
    ]);
}



public function subirEventualidad(Request $request)
{

    //valido, subo archivo

    $this->validate($request,[
            'upload' => 'required|file|mimes:pdf',
        ], [
            'upload.required' => 'Debés seleccionar un archivo.',
            'upload.mimes'    => 'Solo se permiten archivos PDF.',
        ]);

    $file = $request->file('upload');
    $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());

    $dir = storage_path('app/public/EventualidadesAFirmar');
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }

    $path = $file->storeAs('public/EventualidadesAFirmar', $filename);

    $fullPath = storage_path("app/{$path}");
    $head     = file_get_contents($fullPath, false, null, 0, 8192);
    $title    = null;
    if (preg_match('/\/Title\s*\((.*?)\)/', $head, $m)) {
        $title = $m[1];
    }
    $title = preg_replace('/[^\d]/', '', $title);
    // valido que realmente sea un id numerico
    if (! $title || ! ctype_digit($title)) {
      Storage::disk('public')->delete("EventualidadesAFirmar/{$filename}");
        return response()->json([
            'success' => false,
            'cod'     => 3,
            'error'   => 'No se encontró un Title válido en los metadatos.'
        ], 422);
    }

    $subject = null;
    if (preg_match('/\/Subject\s*\(\s*(.*?)\s*\)/', $head, $m2)) {
      $subject = preg_replace('/[^\x20-\x7E]/', '', $m2[1]);
  }

  if ($subject !== 'event_loteria_pdf') {
      Storage::disk('public')->delete("EventualidadesAFirmar/{$filename}");
      return response()->json([
          'success' => false,
          'cod'     => 4,
          'error'   => "El PDF no es original. Subject: [$subject]"
      ], 422);
  }

    //modifico el objeto en la base de datos
      $id= (int) $title;
        $ev = Eventualidades::find($id);
        if (! $ev) {
          return response()->json([
            'success' => false,
            'cod'     => 2,
            'error'   => "No existe eventualidad con ID $id"
          ], 404);
        }
        if($ev->estado_eventualidad==3) {
          Storage::disk('public')->delete("EventualidadesAFirmar/{$filename}");
          return response()->json([
                'success' => false,
                'error'   => "Esta eventualidad ya esta visada.",
                'cod'     => 3
                ]);
        }
        if ($ev->id_archivo) Storage::disk('public')->delete("EventualidadesAFirmar/{$ev->id_archivo}");

        $ev->id_archivo = $filename;
        $ev->estado_eventualidad = 2; // marcado como firmado
        $ev->save();

    // 4) Responder JSON
    return response()->json([
      'success' => true,
      'path'    => $path,
      'url'     => Storage::url($path) //
    ]);
}

public function visarEventualidad($id){

  $dir = storage_path('app/public/EventualidadesFirmadas');
  if (!file_exists($dir)) {
      mkdir($dir, 0755, true);
  }

  $ev = Eventualidades::find($id);
  if (! $ev) {
    return response()->json([
      'success' => false,
      'error'   => "Ocurrió un error al intentar visar la eventualidad con id $id"
    ], 404);
  }

  $disk    = 'public';
  $source  = 'EventualidadesAFirmar/'  . $ev->id_archivo;
  $target  = 'EventualidadesFirmadas/' . $ev->id_archivo;

  Storage::disk($disk)->move($source, $target);

  Storage::disk($disk)->delete("EventualidadesCrudas/eventualidad_{$ev->id_eventualidades}.pdf");

  $ev->estado_eventualidad = 3; // marcado como visado
  $ev->save();

  return 1;
}

public function eliminarEventualidad($id){
  $ev = Eventualidades::findOrFail($id);

  if ($ev->id_archivo) {
      Storage::disk('public')->delete("EventualidadesCrudas/eventualidad_{$ev->id_eventualidades}.pdf");
      Storage::disk('public')->delete("EventualidadesAFirmar/{$ev->id_archivo}");
      Storage::disk('public')->delete("EventualidadesFirmadas/{$ev->id_archivo}");
  }


  if($ev===null) return 0;
  $ev->casino()->dissociate();
  $ev->estado_eventualidad = null;
  $ev->save();

  Eventualidades::destroy($id);


  return 1;
}

public function guardarObservacion(Request $request){



  Validator::make($request->all(),[
      'id_eventualidades' => 'required|integer',
      'observacion' => 'string|max:5000'
  ]);

  try {
      DB::beginTransaction();

      $observacion = new ObservacionEventualidades();
      $observacion->id_eventualidades = $request->id_eventualidades;
      $observacion->observacion = $request->observacion;

      $observacion->id_usuario_generador = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
      $observacion->save();

      DB::commit();

      return response()->json(['success' => true, 'id' => $observacion->id_observacion_eventualidades]);
  } catch (\Exception $e) {
      DB::rollBack();
      return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
  }

}

public function PDFObs($id) {
  $observacion = ObservacionEventualidades::with(['eventualidad'])->findOrFail($id);

  $view = View::make('observacionEventualidad', compact('observacion'));
  $dompdf = new Dompdf();
  $dompdf->set_paper('A4', 'portrait');
  $dompdf->loadHtml($view->render());
  $dompdf->render();

  $canvas = $dompdf->getCanvas();
    $cpdf   = $canvas->get_cpdf();
    $cpdf->addInfo('Title', $observacion->id_observacion_eventualidades);

    $output   = $dompdf->output();

    $dir = storage_path('app/public/EventualidadesFirmadas/Observaciones');
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = "observacion_de_eventualidad_{$observacion->id_eventualidades}_{$observacion->id_observacion_eventualidades}.pdf";
    $path     = "{$dir}/{$filename}";

    file_put_contents($path, $output);



    return response()->download(
        $path,
        $filename,
        ['Content-Type' => 'application/pdf']
      );
}



public function subirObservacion(Request $request)
{
    $this->validate($request,[
        'uploadObs' => 'required|file|mimes:pdf,jpeg,jpg,png',
    ], [
        'uploadObs.required' => 'Debés seleccionar un archivo.',
        'uploadObs.mimes'    => 'Solo se permiten archivos PDF o JPEG,JPG,PNG.',
    ]);

    $file = $request->file('uploadObs');
    $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());

    $dir = storage_path('app/public/EventualidadesFirmadas/ObservacionesFirmadas');
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }

    $path = $file->storeAs('public/EventualidadesFirmadas/ObservacionesFirmadas', $filename);

    $fullPath = storage_path("app/{$path}");

    if($file->extension() === "pdf"){

      $head     = file_get_contents($fullPath, false, null, 0, 8192);
      $title    = null;
      if (preg_match('/\/Title\s*\((.*?)\)/', $head, $m)) {
          $title = $m[1];
      }

      // valido que realmente sea un id numerico
      if (! $title || ! ctype_digit($title)) {
        $id = $request->input('id_eventualidades');

        $ob = new ObservacionEventualidades();
        $ob->id_eventualidades=$id;
        $ob->id_usuario_generador = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
        $ob->id_archivo=$filename;
        $ob->save();
        return 2;
      }
    } else{
          $id = $request->input('id_eventualidades');

          $ob = new ObservacionEventualidades();
          $ob->id_eventualidades=$id;
          $ob->id_usuario_generador = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
          $ob->id_archivo=$filename;
          $ob->save();
          return 1;
      }



    return response()->json([
      'success' => true,
      'path'    => $path,
      'url'     => Storage::url($path),
    ]);
  }

  public function getObservaciones($id)
{
    $obs = ObservacionEventualidades::where('id_eventualidades', $id)
        ->orderBy('created_at','desc')
        ->get(['id_observacion_eventualidades','id_archivo','created_at'])
        // filtramos aquí para quedarnos solo con los que tienen archivo
        ->filter(function($o) {
            return ! is_null($o->id_archivo);
        })
        // reapilamos índices (opcional, pero a veces útil en JSON)
        ->values();

    $obs->transform(function($o){
        $o->url = '/eventualidades/visualizarArchivo/observaciones/'.$o->id_archivo;
        return $o;
    });

    $usuario = Usuario::find(session('id_usuario'));
    $esControlador = $usuario->roles
        ->pluck('descripcion')
        ->intersect(['ADMINISTRADOR','SUPERUSUARIO'])
        ->isNotEmpty() ? 1 : 0;

    return response()->json([
        'obs'           => $obs,
        'controlador'   => $esControlador,
    ]);
}

  public function eliminarObservacion($id){
    $ob = ObservacionEventualidades::findOrFail($id);

    if ($ob->id_archivo) {
        Storage::disk('public')->delete("EventualidadesFirmadas/ObservacionesFirmadas/{$ob->id_archivo}");
        Storage::disk('public')->delete("EventualidadesFirmadas/Observaciones/observacion_de_eventualidad_{$ob->id_eventualidades}_{$ob->id_observacion_eventualidades}");
    }


    if($ob===null) return 0;
    $ob->eventualidad()->dissociate();
    $ob->save();

    ObservacionEventualidades::destroy($id);


    return 1;
  }

  public function visualizarArchivo($estado,$id_archivo){
    //@TODO Validar que el id_archivo exista y que el usuario tenga acceso
    $path = [
      'firmado' => 'app/public/EventualidadesAFirmar',
      'visado' => 'app/public/EventualidadesFirmadas',
      'observaciones' => 'app/public/EventualidadesFirmadas/ObservacionesFirmadas'
    ];

    if(!array_key_exists($estado,$path)){
      throw new \Exception('Estado '.$estado.' invalido');
    }

    $abs_file = storage_path($path[$estado].'/'.$id_archivo);
    return response()->stream(function () use ($abs_file) {
        readfile($abs_file);
      }, 200, [
      'Content-Type' => mime_content_type($abs_file),
      'Content-Disposition' => "inline; filename=\"$id_archivo\""
    ]);
  }
}
