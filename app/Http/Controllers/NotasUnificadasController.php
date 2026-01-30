<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotaIngreso;
use App\Models\Expediente;
use App\Models\Movimiento;
use App\Models\NotaTieneActivo;
use App\Models\Disposicion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;

use App\Isla; // Importar modelo Isla (Legacy)

class NotasUnificadasController extends Controller
{
    /**
     * Display a listing of the resource.
     * Bandeja de entrada según ROL (FISC / MKT / CONCESIONARIO)
     */
    public function index(Request $request)
    {
        // LEGACY AUTHENTICATION COMPATIBILITY
        $id_usuario = session('id_usuario');
        if(!$id_usuario) return redirect('login');
        
        $usuario_data = UsuarioController::getInstancia()->buscarUsuario($id_usuario);
        $usuario = $usuario_data['usuario'];
        
        // $usuario = Auth::user(); // DEPRECATED for this system
         if(!$usuario) {
             return redirect('login');
         }
        // $rol = $usuario->roles()->first();
        
        $casinos = $usuario->casinos;

        // --- LEGACY DATA FOR FISCALIZACION ---
        try {
            $categorias = DB::connection('gestion_notas_mysql')->table('categorias')->get();
            $tipos_evento = DB::connection('gestion_notas_mysql')->table('tipo_eventos')->get();
        } catch (\Exception $e) {
            $categorias = [];
            $tipos_evento = [];
        }
        // -------------------------------------
        // 2. Obtener Grupos de Trámite (con notas hijas)
        // -------------------------------------
        $gruposQuery = \App\Models\GrupoTramite::with(['notas.casino', 'notas.expedientes', 'casino']);
        
        // Search - buscar en datos del grupo y en las notas hijas
        if ($request->has('q') && !empty($request->q)) {
            $q = $request->q;
            $gruposQuery->where(function ($sub) use ($q) {
                $sub->where('nro_nota', 'LIKE', "%$q%")
                    ->orWhere('titulo', 'LIKE', "%$q%")
                    ->orWhere('anio', 'LIKE', "%$q%")
                    ->orWhereHas('casino', function ($c) use ($q) {
                        $c->where('nombre', 'LIKE', "%$q%");
                    })
                    ->orWhereHas('notas', function ($n) use ($q) {
                        $n->where('tipo_solicitud', 'LIKE', "%$q%");
                    })
                    ->orWhereHas('notas.expedientes', function ($e) use ($q) {
                        $e->where('estado_actual', 'LIKE', "%$q%");
                    });
            });
        }

        // Filters
        if ($request->has('id_casino') && !empty($request->id_casino)) {
            $gruposQuery->where('id_casino', $request->id_casino);
        }
        if ($request->has('tipo') && !empty($request->tipo)) {
            $gruposQuery->where('tipo_solicitud', $request->tipo);
        }

        // Quick Filters
        if ($request->has('quick_filter')) {
            if($request->quick_filter === 'hoy') {
                $gruposQuery->whereDate('created_at', \Carbon\Carbon::today());
            }
        }

        // Sorting
        $sort = $request->get('sort_by', 'id');
        $order = $request->get('order', 'desc');
        if(!in_array($order, ['asc','desc'])) $order = 'desc';
        
        $gruposQuery->orderBy($sort, $order);

        // Pagination
        $grupos = $gruposQuery->paginate(10);
        
        // Preserve params
        $grupos->appends($request->all());

        // Para mantener compatibilidad, también pasamos las notas sueltas 
        // (en caso de que haya notas sin grupo - legacy)
        $notasSueltas = NotaIngreso::with(['casino', 'expedientes'])
            ->whereNull('id_grupo')
            ->orderBy('id', 'desc')
            ->get();


        if ($request->ajax()) {
            if ($request->get('view_mode') === 'kanban') {
                return view('Unified.kanban_notas', compact('grupos', 'notasSueltas'));
            }
            return view('Unified.tabla_notas', compact('grupos', 'notasSueltas'));
        }

        // Filter duplicates manually since names differ
        // We prefer "Casino (CODE)" over just "Casino" if both exist, or we just remove known duplicates
        $casinos = \App\Casino::all()->filter(function($c){
            return !in_array($c->nombre, ['City Center Online', 'Bplay']);
        })->values();
        $casinos_original = $casinos;

        // Legacy Data (Hardcoded fallback as DB connection is missing in dev)
        // Legacy Data (Hardcoded fallback)
        // TODO: Move to Database tables 'tipo_evento' and 'categoria_nota'
        
        $categorias = array_map(function ($item) { return (object) $item; }, [
            // Marketing
            ['idcategoria' => 1, 'categoria' => 'Diseño', 'tipo_tarea' => 'MKT'],
            ['idcategoria' => 2, 'categoria' => 'Pautas', 'tipo_tarea' => 'MKT'],
            ['idcategoria' => 3, 'categoria' => 'Contratos', 'tipo_tarea' => 'MKT'],
            ['idcategoria' => 4, 'categoria' => 'Torneo', 'tipo_tarea' => 'MKT'],
            ['idcategoria' => 5, 'categoria' => 'Torneo + Gráficas', 'tipo_tarea' => 'MKT'],
            // Fiscalizacion (Legacy System Match)
            ['idcategoria' => 1, 'categoria' => 'Alta MTM', 'tipo_tarea' => 'FISC'],
            ['idcategoria' => 2, 'categoria' => 'Baja Definitiva MTM', 'tipo_tarea' => 'FISC'],
            ['idcategoria' => 3, 'categoria' => 'Cambio Denominación', 'tipo_tarea' => 'FISC'],
            ['idcategoria' => 4, 'categoria' => 'Cambio Layout', 'tipo_tarea' => 'FISC'],
            ['idcategoria' => 5, 'categoria' => 'Cambio Juego/Versión', 'tipo_tarea' => 'FISC'],
            ['idcategoria' => 6, 'categoria' => 'Torneo', 'tipo_tarea' => 'FISC'],
            ['idcategoria' => 7, 'categoria' => 'Promoción', 'tipo_tarea' => 'FISC'],
            ['idcategoria' => 8, 'categoria' => 'Modificación MTM Varios', 'tipo_tarea' => 'FISC'],
            ['idcategoria' => 9, 'categoria' => 'Mesas', 'tipo_tarea' => 'FISC'],
            ['idcategoria' => 10, 'categoria' => 'Bingo', 'tipo_tarea' => 'FISC'],
            ['idcategoria' => 11, 'categoria' => 'Otros', 'tipo_tarea' => 'FISC'],
            ['idcategoria' => 12, 'categoria' => 'Baja Transitoria MTM', 'tipo_tarea' => 'FISC']
        ]);

        $tipos_evento = array_map(function ($item) { return (object) $item; }, [
            // Marketing (Eventos)
            ['idtipoevento' => 1, 'tipo_nombre' => 'Activaciones', 'tipo_tarea' => 'MKT'],
            ['idtipoevento' => 2, 'tipo_nombre' => 'Medios Masivos/Tradicionales', 'tipo_tarea' => 'MKT'],
            ['idtipoevento' => 3, 'tipo_nombre' => 'Medios Digitales', 'tipo_tarea' => 'MKT'],
            ['idtipoevento' => 4, 'tipo_nombre' => 'Promociones', 'tipo_tarea' => 'MKT'],
            ['idtipoevento' => 5, 'tipo_nombre' => 'Torneos', 'tipo_tarea' => 'MKT'],
            ['idtipoevento' => 7, 'tipo_nombre' => 'Contratos', 'tipo_tarea' => 'MKT'],
            // Fiscalizacion (Legacy System Match)
            ['idtipoevento' => 1, 'tipo_nombre' => 'Cambio de Layout', 'tipo_tarea' => 'FISC'],
            ['idtipoevento' => 2, 'tipo_nombre' => 'Altas MTM', 'tipo_tarea' => 'FISC'],
            ['idtipoevento' => 3, 'tipo_nombre' => 'Bajas MTM', 'tipo_tarea' => 'FISC'],
            ['idtipoevento' => 4, 'tipo_nombre' => 'Modificaciones MTM', 'tipo_tarea' => 'FISC'],
            ['idtipoevento' => 5, 'tipo_nombre' => 'Cambio de Denominación', 'tipo_tarea' => 'FISC'],
            ['idtipoevento' => 6, 'tipo_nombre' => 'Altas JOL', 'tipo_tarea' => 'FISC'],
            ['idtipoevento' => 7, 'tipo_nombre' => 'Bajas JOL', 'tipo_tarea' => 'FISC'],
            ['idtipoevento' => 8, 'tipo_nombre' => 'Modificaciones JOL', 'tipo_tarea' => 'FISC'],
            ['idtipoevento' => 9, 'tipo_nombre' => 'Gestiones Varias', 'tipo_tarea' => 'FISC'],
            ['idtipoevento' => 10, 'tipo_nombre' => 'Promoción', 'tipo_tarea' => 'FISC'],
            ['idtipoevento' => 11, 'tipo_nombre' => 'Torneo', 'tipo_tarea' => 'FISC'],
            ['idtipoevento' => 12, 'tipo_nombre' => 'Baja Transitoria', 'tipo_tarea' => 'FISC'],
            ['idtipoevento' => 13, 'tipo_nombre' => 'Bases y Condiciones ESPECIALES', 'tipo_tarea' => 'FISC']
        ]);

        // Inject Online Casinos for the UI
        try {
            $plataformas = \App\Models\CasinoOnline::all();
            
            // Transform Platform -> Casino format (ID Offset +100 to match legacy hardcode)
            // CityCenter (1) -> 101
            // Bplay (2) -> 102
            $casinos_online = $plataformas->map(function($p) {
                $c = new \stdClass();
                $c->id_casino = $p->id_plataforma + 100; 
                $c->nombre = $p->nombre;
                $c->codigo = $p->codigo;
                return $c;
            });

            \Log::info("Plataformas Online encontradas: " . $casinos_online->count());

        } catch(\Exception $e) {
            \Log::error("CRITICAL: Error connecting to Online DB: " . $e->getMessage());
            $casinos_online = collect([]); 
        }

        // Merge Online Casinos
        if(isset($casinos_online)) {
            $casinos = $casinos->concat($casinos_online);
        }

        // Retornar vista principal (Bandejas)
        return view('Unified.index', compact('grupos', 'notasSueltas', 'casinos', 'categorias', 'tipos_evento'));
    }

    /**
     * Store a newly created resource in storage.
     * Carga inicial del concesionario
     */
    public function store(Request $request)
    {
        \Log::info("STORE: Request Data = " . json_encode($request->all()));
        
        // 0. Pre-Process: If FISCALIZACION, set tipo_solicitud = EVENTO
        if($request->tipo_tarea === 'FISCALIZACION') {
            $request->merge(['tipo_solicitud' => 'EVENTO']);
            // Merge FISC specific inputs to generic names for validation
            $request->merge([
                'id_tipo_evento' => $request->id_tipo_evento_fisc,
                'id_categoria'   => $request->id_categoria_fisc,
            ]);
        } elseif ($request->tipo_tarea === 'MARKETING') {
            // Merge MKT specific inputs to generic names for validation
            $request->merge([
                'id_tipo_evento' => $request->id_tipo_evento_mkt,
                'id_categoria'   => $request->id_categoria_mkt,
            ]);
        }

        // 1. Validar request
        $rules = [
            'nro_nota' => 'required',
            'anio' => 'required|integer',
            'titulo' => 'required|string',
            'id_casino' => 'required|integer',
            'tipo_solicitud' => 'required|in:EVENTO,PUBLICIDAD',
        ];
        
        if($request->tipo_solicitud == 'EVENTO') {
            $rules['fecha_inicio_evento'] = 'required|date';
            $rules['fecha_fin_evento'] = 'required|date';
        }
        
        // Custom messages for better UX
        $messages = [
            'nro_nota.required' => 'El Número de Nota es requerido',
            'anio.required' => 'El Año es requerido',
            'anio.integer' => 'El Año debe ser un número entero',
            'titulo.required' => 'El Título es requerido',
            'id_casino.required' => 'Debe seleccionar un Casino',
            'id_casino.integer' => 'El Casino debe ser un valor numérico',
            'tipo_solicitud.required' => 'Debe seleccionar un Tipo de Solicitud (EVENTO o PUBLICIDAD)',
            'tipo_solicitud.in' => 'El Tipo de Solicitud debe ser EVENTO o PUBLICIDAD',
            'fecha_inicio_evento.required' => 'La Fecha de Inicio es requerida para eventos',
            'fecha_fin_evento.required' => 'La Fecha de Fin es requerida para eventos',
        ];
        
        // Use manual validation to capture and return errors
        $validator = \Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            \Log::warning("STORE VALIDATION FAILED: " . json_encode($validator->errors()->toArray()));
            return response()->json([
                'success' => false,
                'msg' => 'Error de validación: ' . implode(', ', $validator->errors()->all())
            ], 422);
        }
        
        // Custom valid: If Evento, extra fields required
        if($request->tipo_solicitud == 'EVENTO') {
            $extraRules = [
                'id_tipo_evento' => 'required',
                'id_categoria' => 'required',
            ];
            $extraMessages = [
                'id_tipo_evento.required' => 'Debe seleccionar un Tipo de Evento',
                'id_categoria.required' => 'Debe seleccionar una Categoría',
            ];
            $extraValidator = \Validator::make($request->all(), $extraRules, $extraMessages);
            if ($extraValidator->fails()) {
                \Log::warning("STORE EXTRA VALIDATION FAILED: " . json_encode($extraValidator->errors()->toArray()));
                return response()->json([
                    'success' => false,
                    'msg' => 'Error de validación (Evento): ' . implode(', ', $extraValidator->errors()->all())
                ], 422);
            }
        }

        // 0.B Buscar o Crear Grupo de Trámite
        $grupo = null;
        if($request->has('id_grupo_existente') && $request->id_grupo_existente) {
             $grupo = \App\Models\GrupoTramite::find($request->id_grupo_existente);
        }
        
        if(!$grupo) {
             $grupo = \App\Models\GrupoTramite::where('nro_nota', $request->nro_nota)
                    ->where('anio', $request->anio)
                    ->where('id_casino', $request->id_casino)
                    ->first();
        }
        
        // Determinar qué ramas crear basándose en tipo_tarea
        $ramasACrear = [];
        if($request->tipo_tarea === 'MARKETING') {
            if($request->tipo_solicitud === 'EVENTO') {
                $ramasACrear = ['MKT', 'FISC']; // Evento crea ambas
            } else {
                $ramasACrear = ['MKT']; // Publicidad solo MKT
            }
        } elseif($request->tipo_tarea === 'FISCALIZACION') {
            $ramasACrear = ['FISC']; // Solo fiscalización
        }

        // Si el grupo ya existe, verificar qué ramas faltan
        if($grupo) {
            $ramasExistentes = $grupo->notas->pluck('tipo_rama')->toArray();
            $ramasNuevas = array_diff($ramasACrear, $ramasExistentes);
            
            if(empty($ramasNuevas)) {
                return response()->json([
                    'success' => false, 
                    'msg' => 'Ya existen notas para las ramas solicitadas en este trámite (Nota ' . $request->nro_nota . '-' . $request->anio . ').'
                ], 422);
            }
            
            $ramasACrear = $ramasNuevas; // Solo crear las que faltan
            \Log::info("GRUPO EXISTENTE: Anidando ramas " . implode(',', $ramasACrear) . " bajo grupo #" . $grupo->id);
        }

        DB::beginTransaction();

        try {
            // Si no existe el grupo, crearlo
            if(!$grupo) {
                $grupo = new \App\Models\GrupoTramite();
                $grupo->nro_nota = $request->nro_nota;
                $grupo->anio = $request->anio;
                $grupo->id_casino = $request->id_casino;
                $grupo->titulo = $request->titulo;
                $grupo->tipo_solicitud = $request->tipo_solicitud;
                $grupo->fecha_inicio_evento = $request->fecha_inicio_evento;
                $grupo->fecha_fin_evento = $request->fecha_fin_evento;
                $grupo->id_tipo_evento = $request->id_tipo_evento;
                $grupo->id_categoria = $request->id_categoria;
                $grupo->save();
                
                \Log::info("GRUPO NUEVO CREADO: ID " . $grupo->id);
            }

            // Helper Closure to Create Note (child of grupo)
            $createNota = function($tipo_solicitud, $tipo_rama) use ($request, $grupo) {
                $nota = new NotaIngreso();
                $nota->id_grupo = $grupo->id;  // FK al grupo padre
                $nota->nro_nota = $request->nro_nota; // Mismo número, no prefijos
                $nota->anio = $request->anio;
                $nota->fecha_ingreso = \Carbon\Carbon::now();
                $nota->id_casino = $request->id_casino;
                $nota->titulo = $request->titulo;
                $nota->tipo_solicitud = $tipo_solicitud;
                $nota->tipo_rama = $tipo_rama;
                
                // Clasificación
                $nota->id_tipo_evento = $request->id_tipo_evento;
                $nota->id_categoria = $request->id_categoria;
                
                // Fechas
                $nota->fecha_inicio_evento = $request->fecha_inicio_evento;
                $nota->fecha_fin_evento = $request->fecha_fin_evento;
                
                $nota->save();

                // Asociar Activos
                if ($request->has('activos')) {
                    $this->procesarActivos($nota, $request->activos);
                }

                // Crear Expediente
                $exp = new \App\Models\Expediente();
                $exp->id_nota_ingreso = $nota->id;
                $exp->tipo_rama = $tipo_rama;
                $exp->estado_actual = 'INICIO';
                $exp->save();

                // Movimiento Inicial
                $mov = new \App\Models\Movimiento();
                $mov->id_expediente_nota = $exp->id;
                $mov->id_usuario = 1; // TODO: Auth::id()
                $mov->fecha_movimiento = \Carbon\Carbon::now();
                $mov->accion = 'INICIO';
                $mov->comentario = 'Carga inicial del trámite';
                $mov->save();

                return $nota;
            };

            $ids_notas = [];
            $main_nota = null;

            // Crear las notas según las ramas determinadas
            foreach($ramasACrear as $rama) {
                $tipoSol = ($rama === 'MKT') ? 'EVENTO_MKT' : 'EVENTO_FISC';
                if($request->tipo_solicitud === 'PUBLICIDAD') {
                    $tipoSol = 'PUBLICIDAD';
                }
                
                $nota = $createNota($tipoSol, $rama);
                $ids_notas[strtolower($rama)] = $nota->id;
                
                if(!$main_nota) $main_nota = $nota;
            }

            DB::commit();
            
            return response()->json([
                'success' => true, 
                'id_grupo' => $grupo->id,
                'ids_notas' => $ids_notas,
                'nro_nota' => $grupo->nro_nota,
                'anio' => $grupo->anio,
                'titulo' => $grupo->titulo,
                'tipo_solicitud' => $request->tipo_solicitud,
                'ramas_creadas' => $ramasACrear
            ]);
        
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("STORE ERROR: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
            return response()->json(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * Visualizar el detalle de una nota con su chat
     */


    /**
     * Lógica "Explosiva" para activos
     * Si es ISLA -> Busca todas las MTMs y las guarda una por una.
     * Si es OTRO -> Lo guarda directo.
     */
    private function procesarActivos(NotaIngreso $nota, array $activos)
    {
        foreach ($activos as $activo) {
            $tipo = strtoupper($activo['tipo']);
            $id = $activo['id'];

            if ($tipo === 'ISLA') {
                // "Explotar" la isla
                $isla = Isla::with('maquinas')->find($id);
                if ($isla) {
                    foreach ($isla->maquinas as $mtm) {
                        NotaTieneActivo::create([
                            'id_nota_ingreso' => $nota->id,
                            'tipo_activo' => 'MTM', // Guardamos el átomo (Máquina)
                            'id_activo' => $mtm->id_maquina
                        ]);
                    }
                }
            } else {
                // Guardado directo (Juegos, Mesas, o MTMs individuales)
                NotaTieneActivo::create([
                    'id_nota_ingreso' => $nota->id,
                    'tipo_activo' => $tipo,
                    'id_activo' => $id
                ]);
            }
        }
    }
    /**
     * Buscar Activos (AJAX)
     */
    /**
     * Buscar Activos (AJAX)
     */
    public function buscarActivos(Request $request) {
        $busqueda = $request->q;
        $id_casino = $request->id_casino;
        $tipo = $request->tipo;
        $resultados = [];

        if($tipo == 'ISLA') {
            $resultados = \App\Isla::where('id_casino', $id_casino)
                ->where('nro_isla', 'like', $busqueda . '%')
                ->with('sector')
                ->withCount('maquinas')
                ->take(20)->get()->map(function($i){
                    $texto = 'Isla ' . $i->nro_isla . ' (Sector ' . ($i->sector->descripcion ?? 'N/A') . ')';
                    
                    // Structured Data
                    $data = [
                        'Nro Isla' => $i->nro_isla,
                        'Sector' => $i->sector ? $i->sector->descripcion : '-',
                        'Cant. Maquinas' => $i->maquinas_count
                    ];
                    
                    $info = 'Cant. Maquinas: ' . $i->maquinas_count;
                    return ['id' => $i->id_isla, 'text' => $texto, 'info' => $info, 'data' => $data];
                });
        } elseif($tipo == 'MTM') {
            $resultados = \App\Maquina::where('id_casino', $id_casino)
                ->where('nro_admin', 'like', $busqueda . '%')
                ->with(['isla.sector', 'juego_activo', 'unidad_medida', 'tipoMaquina']) 
                ->take(20)->get()->map(function($m){
                    $texto = 'MTM ' . $m->nro_admin . ' - ' . $m->marca;
                    
                    // Structured Data for Dynamic Columns
                    $data = [
                        'Nro Admin' => $m->nro_admin,
                        'Marca' => $m->marca,
                        'Modelo' => $m->modelo,
                        'Isla' => $m->isla ? $m->isla->nro_isla : '-',
                        // 'Sector' => $m->isla && $m->isla->sector ? $m->isla->sector->descripcion : '-', // Too wide?
                        'Juego' => $m->juego_activo ? $m->juego_activo->nombre_juego : '-',
                        '% Dev' => $m->obtenerPorcentajeDevolucion() ?? '-',
                    ];
                    
                    // Keep 'info' str for search list preview, but send data for table
                    $info_str = "Isla: {$data['Isla']} | Juego: {$data['Juego']} | %Dev: {$data['% Dev']}";
                    
                    return ['id' => $m->id_maquina, 'text' => $texto, 'info' => $info_str, 'data' => $data];
                });
        } elseif($tipo == 'MESA') {
            $resultados = \App\Mesas\Mesa::where('id_casino', $id_casino)
                ->where('nro_mesa', 'like', $busqueda . '%')
                ->with(['juego', 'sector', 'moneda'])
                ->take(20)->get()->map(function($m){
                    $texto = 'Mesa ' . $m->nro_mesa . ' - ' . ($m->juego->nombre_juego ?? 'Sin Juego');
                    
                    $data = [
                        'Nro Mesa' => $m->nro_mesa,
                        'Juego' => $m->juego->nombre_juego ?? '-',
                        'Sector' => $m->sector ? $m->sector->descripcion : '-', 
                        'Moneda' => $m->moneda ? $m->moneda->descripcion : '-'
                    ];
                    
                    $info_str = "Juego: {$data['Juego']} | Sec: {$data['Sector']}";
                    return ['id' => $m->id_mesa_de_panio, 'text' => $texto, 'info' => $info_str, 'data' => $data];
                });
        } elseif($tipo == 'JUEGO_ONLINE') {
             // Usar la conexion online
             $resultados = \App\JuegoOnline::where('nombre_juego', 'like', '%' . $busqueda . '%')
             ->with('categoria_juego')
             ->take(20)->get()->map(function($j){
                 $data = [
                     'Cod Juego' => $j->cod_juego ?? '-',
                     'Juego' => $j->nombre_juego,
                     'Categoria' => $j->categoria_juego ? $j->categoria_juego->nombre : '-',
                     '% Dev' => $j->porcentaje_devolucion ?? '-',
                     'Plataforma' => ($j->escritorio ? 'PC ' : '') . ($j->movil ? 'Movil' : '')
                 ];
                 $info_str = "Cat: {$data['Categoria']} | %Dev: {$data['% Dev']}";
                 return ['id' => $j->id_juego, 'text' => $j->nombre_juego, 'info' => $info_str, 'data' => $data];
             });
        }
        
        return response()->json($resultados);
    }

    public function obtenerActivosIsla($id_isla) {
        $mtms = \App\Maquina::where('id_isla', $id_isla)
        ->with(['isla.sector', 'juego_activo', 'unidad_medida', 'tipoMaquina']) 
        ->get()->map(function($m){
            $texto = 'MTM ' . $m->nro_admin . ' - ' . $m->marca;
            
            // Format MATCHES MTM Search logic exactly
            $data = [
                'Nro Admin' => $m->nro_admin,
                'Marca' => $m->marca,
                'Modelo' => $m->modelo,
                'Isla' => $m->isla ? $m->isla->nro_isla : '-',
                'Juego' => $m->juego_activo ? $m->juego_activo->nombre_juego : '-',
                '% Dev' => $m->obtenerPorcentajeDevolucion() ?? '-',
            ];
            
            $info_str = "Isla: {$data['Isla']} | Juego: {$data['Juego']} | %Dev: {$data['% Dev']}";
            
            return ['id' => $m->id_maquina, 'text' => $texto, 'info' => $info_str, 'data' => $data, 'tipo' => 'MTM'];
        });

        return response()->json($mtms);
    }
    /**
     * Wizard Step 2: Vista de Adjuntos
     */
    public function vistaAdjuntar($id) {
        $nota = NotaIngreso::findOrFail($id);
        return view('Unified.wizard_step_2', compact('nota'));
    }

    /**
     * Wizard Step 2: Guardar Adjuntos
     * 
     * ESTRUCTURA DE ADJUNTOS:
     * ========================
     * COMÚN (ambas ramas):
     *   - path_solicitud: Solicitud Concesionario
     *   - path_informe: Informe Técnico (instancia posterior)
     * 
     * MKT (Marketing):
     *   - path_diseno: Diseño/Arte
     *   - path_bases: Bases y Condiciones
     * 
     * FISC (Fiscalización):
     *   - path_varios: Archivos Varios (.zip con todo)
     */
    public function guardarAdjuntos(Request $request) {
        \Log::info("UPLOAD: METHOD ENTRY - RAW INPUTS: " . json_encode($request->only(['id_nota_mkt', 'id_nota_fisc'])));
        $disk = 'public'; 
        
        // IDs recibidos del frontend
        $id_nota_fisc = $request->id_nota_fisc; 
        $id_nota_mkt = $request->id_nota_mkt;   

        \Log::info("WIZARD UPLOAD START: MKT=" . ($id_nota_mkt ?? 'null') . ", FISC=" . ($id_nota_fisc ?? 'null'));
        
        // Helper function to store file with original name + timestamp
        $storeFile = function($file, $folder) use ($disk) {
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueName = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName) . '.' . $extension;
            return $file->storeAs($folder, $uniqueName, $disk);
        };
        
        try {
            // ===================================================
            // ! GUARDAR ADJUNTOS MKT (Marketing)
            // ===================================================
            if($id_nota_mkt && is_numeric($id_nota_mkt)) {
                $notaMkt = NotaIngreso::find($id_nota_mkt);
                if($notaMkt) {
                    // Solicitud Concesionario (común)
                    if ($request->hasFile('adjuntoSolicitud') && $request->file('adjuntoSolicitud')->isValid()) {
                        $notaMkt->path_solicitud = $storeFile($request->file('adjuntoSolicitud'), 'solicitudes');
                    }
                    // Diseño (MKT)
                    if ($request->hasFile('adjuntoDisenio') && $request->file('adjuntoDisenio')->isValid()) {
                        $notaMkt->path_diseno = $storeFile($request->file('adjuntoDisenio'), 'disenos');
                    }
                    // Bases y Condiciones (MKT)
                    if ($request->hasFile('adjuntoBases') && $request->file('adjuntoBases')->isValid()) {
                        $notaMkt->path_bases = $storeFile($request->file('adjuntoBases'), 'bases');
                    }
                    // Informe Técnico (opcional - instancia posterior)
                    if ($request->hasFile('adjuntoInformeMkt') && $request->file('adjuntoInformeMkt')->isValid()) {
                        $notaMkt->path_informe = $storeFile($request->file('adjuntoInformeMkt'), 'informes');
                    }
                    
                    $notaMkt->save();
                    \Log::info("MKT Files Saved for Note " . $notaMkt->id);
                }
            }

            // ===================================================
            // ! GUARDAR ADJUNTOS FISC (Fiscalización)
            // ===================================================
            if($id_nota_fisc && is_numeric($id_nota_fisc)) {
                $notaFisc = NotaIngreso::find($id_nota_fisc);
                if($notaFisc) {
                    // Solicitud Concesionario (común)
                    if ($request->hasFile('adjuntoSolicitudFisc') && $request->file('adjuntoSolicitudFisc')->isValid()) {
                        $notaFisc->path_solicitud = $storeFile($request->file('adjuntoSolicitudFisc'), 'solicitudes');
                    }
                    // Archivos Varios - .zip con todo (FISC)
                    if ($request->hasFile('adjuntoVarios') && $request->file('adjuntoVarios')->isValid()) {
                        $notaFisc->path_varios = $storeFile($request->file('adjuntoVarios'), 'archivos_varios');
                    }
                    // Informe Técnico (opcional - instancia posterior)
                    if ($request->hasFile('adjuntoInformeFisc') && $request->file('adjuntoInformeFisc')->isValid()) {
                        $notaFisc->path_informe = $storeFile($request->file('adjuntoInformeFisc'), 'informes');
                    }
                    
                    $notaFisc->save();
                    \Log::info("FISC Files Saved for Note " . $notaFisc->id);
                }
            }

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            \Log::error("Error guardarAdjuntos Trace: " . $e->getTraceAsString());
            return response()->json(['success' => false, 'msg' => "Server Error: " . $e->getMessage()], 500);
        }
    }

    /**
     * Agregar adjuntos a una nota existente (por turnos)
     * Permite que MKT o FISC suban sus archivos en diferentes momentos
     */
    public function agregarAdjuntos(Request $request, $id) {
        $disk = 'public';
        $nota = NotaIngreso::findOrFail($id);
        $userId = Auth::id() ?? 1;
        
        // Helper para guardar archivo
        $storeFile = function($file, $folder) use ($disk) {
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueName = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName) . '.' . $extension;
            return $file->storeAs($folder, $uniqueName, $disk);
        };
        
        // Helper para registrar movimiento
        $logMovimiento = function($nota, $campo, $nombreArchivo, $accion = 'ADJUNTO_AGREGADO') use ($userId) {
            // Obtener o crear expediente para la nota
            $expediente = $nota->expedientes->first();
            if(!$expediente) {
                $expediente = Expediente::create([
                    'id_nota_ingreso' => $nota->id,
                    'estado_actual' => 'EN_PROCESO'
                ]);
            }
            
            $camposLegibles = [
                'path_solicitud' => 'Solicitud Concesionario',
                'path_diseno' => 'Diseño',
                'path_bases' => 'Bases y Condiciones',
                'path_informe' => 'Informe Técnico',
                'path_varios' => 'Archivos Varios'
            ];
            
            $mov = new Movimiento;
            $mov->id_expediente_nota = $expediente->id;
            $mov->id_usuario = $userId;
            $mov->fecha_movimiento = \Carbon\Carbon::now();
            $mov->accion = $accion;
            $mov->comentario = ($accion === 'ADJUNTO_REEMPLAZADO' ? 'Reemplazó' : 'Agregó') 
                . ' ' . ($camposLegibles[$campo] ?? $campo) 
                . ': "' . $nombreArchivo . '"';
            $mov->save();
        };
        
        try {
            // Mapeo de campos de formulario a campos de BD
            $camposArchivos = [
                'adjuntoSolicitud' => ['campo' => 'path_solicitud', 'folder' => 'solicitudes'],
                'adjuntoDisenio' => ['campo' => 'path_diseno', 'folder' => 'disenos'],
                'adjuntoBases' => ['campo' => 'path_bases', 'folder' => 'bases'],
                'adjuntoInforme' => ['campo' => 'path_informe', 'folder' => 'informes'],
                'adjuntoVarios' => ['campo' => 'path_varios', 'folder' => 'archivos_varios']
            ];
            
            $archivosSubidos = [];
            
            foreach($camposArchivos as $inputName => $config) {
                if($request->hasFile($inputName) && $request->file($inputName)->isValid()) {
                    $file = $request->file($inputName);
                    $nombreOriginal = $file->getClientOriginalName();
                    $campo = $config['campo'];
                    
                    // Determinar si es reemplazo o nuevo
                    $accion = !empty($nota->$campo) ? 'ADJUNTO_REEMPLAZADO' : 'ADJUNTO_AGREGADO';
                    
                    // Guardar archivo
                    $nota->$campo = $storeFile($file, $config['folder']);
                    
                    // Registrar movimiento
                    $logMovimiento($nota, $campo, $nombreOriginal, $accion);
                    
                    $archivosSubidos[] = $nombreOriginal;
                }
            }
            
            $nota->save();
            
            return response()->json([
                'success' => true, 
                'msg' => count($archivosSubidos) . ' archivo(s) subido(s) correctamente',
                'archivos' => $archivosSubidos
            ]);
            
        } catch(\Throwable $e) {
            \Log::error("Error agregarAdjuntos: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener historial de adjuntos/movimientos de una nota
     */
    public function getHistorialAdjuntos($id) {
        try {
            $nota = NotaIngreso::with('expedientes.movimientos.usuario')->findOrFail($id);
            
            $historial = [];
            foreach($nota->expedientes as $exp) {
                if(!$exp->movimientos) continue;
                
                foreach($exp->movimientos as $mov) {
                    // Filtrar solo movimientos de adjuntos
                    $comentario = $mov->comentario ?? '';
                    $accion = $mov->accion ?? '';
                    
                    if(strpos($accion, 'ADJUNTO') !== false || strpos($comentario, 'Agregó') !== false || strpos($comentario, 'Reemplazó') !== false) {
                        $fecha = $mov->fecha_movimiento;
                        if($fecha && !is_string($fecha)) {
                            $fechaStr = $fecha->format('d/m/Y H:i');
                        } else {
                            $fechaStr = $fecha ?? date('d/m/Y H:i');
                        }
                        
                        $historial[] = [
                            'fecha' => $fechaStr,
                            'usuario' => $mov->usuario->nombre ?? 'Usuario',
                            'accion' => $accion,
                            'detalle' => $comentario
                        ];
                    }
                }
            }
            
            // Ordenar por fecha desc
            usort($historial, function($a, $b) {
                return strtotime($b['fecha']) - strtotime($a['fecha']);
            });
            
            // Include current attachment status
            $adjuntos = [
                'solicitud' => $nota->path_solicitud ? ['existe' => true, 'nombre' => basename($nota->path_solicitud)] : ['existe' => false],
                'diseno' => $nota->path_diseno ? ['existe' => true, 'nombre' => basename($nota->path_diseno)] : ['existe' => false],
                'bases' => $nota->path_bases ? ['existe' => true, 'nombre' => basename($nota->path_bases)] : ['existe' => false],
                'informe' => $nota->path_informe ? ['existe' => true, 'nombre' => basename($nota->path_informe)] : ['existe' => false],
                'varios' => $nota->path_varios ? ['existe' => true, 'nombre' => basename($nota->path_varios)] : ['existe' => false],
            ];
            
            return response()->json(['success' => true, 'historial' => $historial, 'adjuntos' => $adjuntos, 'nota_id' => $id]);
        } catch(\Throwable $e) {
            \Log::error("Error getHistorialAdjuntos ID={$id}: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
            
            // Return empty object (not array) so JS can check properties
            $emptyAdjuntos = [
                'solicitud' => ['existe' => false],
                'diseno' => ['existe' => false],
                'bases' => ['existe' => false],
                'informe' => ['existe' => false],
                'varios' => ['existe' => false],
            ];
            return response()->json(['success' => true, 'historial' => [], 'adjuntos' => $emptyAdjuntos, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Inline Update (Quick Edit)
     */
    public function quickUpdate(Request $request) {
        try {
            $nota = NotaIngreso::findOrFail($request->id);
            $field = $request->field; 
            $value = $request->value;

            if($field === 'estado') {
                $exp = $nota->expedientes->first();
                if($exp) {
                     $exp->estado_actual = $value;
                     $exp->save();
                     
                     // Register movement
                     $mov = new Movimiento;
                     $mov->id_expediente_nota = $exp->id;
                     $mov->id_usuario = 1; // HARDCODED TEMP
                     $mov->fecha_movimiento = \Carbon\Carbon::now();
                     $mov->accion = 'MODIFICACION';
                     $mov->comentario = 'Cambio de estado rápido a: ' . $value;
                     $mov->save();
                }
            }
            
            return response()->json(['success' => true]);
        } catch(\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Upload Single File (Drag & Drop)
     */
    public function uploadArchivo(Request $request) {
        try {
            $nota = NotaIngreso::findOrFail($request->id_nota);
            $tipo = $request->tipo; // pautas, diseno, bases
            
            if(!$request->hasFile('file')) {
                return response()->json(['success' => false, 'msg' => 'No file provided'], 400);
            }

            $disk = 'public';
            $path = null;

            if($tipo == 'pautas') {
                $path = $request->file('file')->store('pautas', $disk);
                $nota->path_pautas = $path;
            } elseif($tipo == 'diseno') {
                $path = $request->file('file')->store('disenos', $disk);
                $nota->path_diseno = $path;
            } elseif($tipo == 'bases') {
                $path = $request->file('file')->store('bases', $disk);
                $nota->path_bases = $path;
            } else {
                return response()->json(['success' => false, 'msg' => 'Invalid file type'], 400);
            }

            $nota->save();

            return response()->json(['success' => true]);
        } catch(\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Get Last Movements for Tooltip
     */
    public function getMovimientos($id) {
        $nota = NotaIngreso::with(['expedientes.movimientos' => function($q){
            $q->orderBy('id_movimiento', 'desc')->take(3);
        }])->find($id);

        if(!$nota) return response()->json([]);

        $movs = [];
        if($nota->expedientes->count() > 0) {
            foreach($nota->expedientes->first()->movimientos as $m) {
                $movs[] = [
                    'fecha' => \Carbon\Carbon::parse($m->fecha_movimiento)->format('d/m H:i'),
                    'estado' => $m->accion // Or $m->estado_actual ??
                ];
            }
        }
        return response()->json($movs);
    }


    // ! DESCARGAR
    public function descargarArchivo($id, $tipo) {
        $nota = NotaIngreso::findOrFail($id);
        $path = null;
        
        switch($tipo) {
            case 'pautas': $path = $nota->path_pautas; break;
            case 'diseno': $path = $nota->path_diseno; break;
            case 'bases':  $path = $nota->path_bases; break;
        }

        if(!$path || !Storage::disk('public')->exists($path)) {
            return redirect()->back()->with('error', 'Archivo no encontrado');
        }

        // Get the full path and original filename
        $fullPath = Storage::disk('public')->getDriver()->getAdapter()->applyPathPrefix($path);
        $filename = basename($path); // Extract original filename with extension
        
        // Use response()->download() with the correct filename
        return response()->download($fullPath, $filename);
    }

    public function getCalendarEvents(Request $request){
        // Fetch notes with dates to show in calendar
        $notas = DB::table('nota')
            ->join('expediente', 'nota.id_expediente', '=', 'expediente.id_expediente')
            ->select('nota.id_nota', 'nota.fecha', 'nota.identificacion', 'expediente.concepto', 'expediente.nro_exp_org', 'expediente.nro_exp_interno', 'expediente.nro_exp_control')
            ->limit(100) // Optimize as needed
            ->get();

        $events = [];
        foreach($notas as $n){
            $titulo = "Nota " . $n->identificacion;
            // Add some info
            if($n->nro_exp_org){
               $titulo .= " | Exp: " . $n->nro_exp_org . "-" . $n->nro_exp_interno;
            }

            $events[] = [
                'title' => $titulo,
                'start' => $n->fecha,
                'url' => 'javascript:verNota(' . $n->id_nota . ')',
                'color' => '#1976D2'
            ];
        }

        return response()->json($events);
    }

    // ! COMENTARIOS ("POST-ITS")
    public function addComment(Request $request){
        $request->validate([
            'id_nota' => 'required|integer',
            'comentario' => 'required|string|max:500'
        ]);

        $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
        
        $comentario = new \App\NotaComentario;
        $comentario->id_nota = $request->id_nota;
        $comentario->id_usuario = $usuario->id_usuario;
        $comentario->comentario = $request->comentario;
        $comentario->save();

        return response()->json([
            'status' => 'success', 
            'comentario' => $comentario->load('usuario')
        ]);
    }

    public function getComments($id){
        $comentarios = \App\NotaComentario::where('id_nota', $id)
                        ->with('usuario')
                        ->orderBy('created_at', 'desc')
                        ->get();
        return response()->json($comentarios);
    }



    // ! PRESENCE SYSTEM (Fix for 500 Error)
    public function heartbeat(Request $request) {
        try {
            // ! Check if table exists (Self-Healing)
            if(!\Illuminate\Support\Facades\Schema::hasTable('presencia_usuarios')) {
                 $this->fixPresenceTable(); // Create if missing
            }

            $user_id = session('id_usuario');
            if(!$user_id) return response()->json(['status' => 'guest']);

            DB::table('presencia_usuarios')->updateOrInsert(
                ['id_usuario' => $user_id],
                [
                    'ubicacion' => $request->ubicacion ?? 'Viendo notas',
                    'last_seen' => \Carbon\Carbon::now(),
                    'created_at' => \Carbon\Carbon::now(), // Only on insert
                    'updated_at' => \Carbon\Carbon::now()
                ]
            );
            return response()->json(['status' => 'ok']);
        } catch(\Exception $e) {
            \Log::error("Heartbeat error: " . $e->getMessage());
            return response()->json(['status' => 'error'], 200); // Suppress 500 to frontend
        }
    }

    public function getPresence(){
        try {
            // Get users seen in last 2 minutes
            $users = DB::table('presencia_usuarios')
                ->join('usuario', 'presencia_usuarios.id_usuario', '=', 'usuario.id_usuario')
                ->where('last_seen', '>=', \Carbon\Carbon::now()->subMinutes(2))
                ->select('usuario.user_name', 'usuario.id_usuario', 'presencia_usuarios.ubicacion')
                ->get();
                
            // Filter out self if needed, or keeping it to show "You are here"
            return response()->json($users);
        } catch(\Exception $e) {
            // Return empty list on error (e.g. table not found) to suppress 500 logs in frontend polling
            return response()->json([]);
        }
    }

    public function show($id) {
        $nota = NotaIngreso::with(['casino', 'expedientes'])->findOrFail($id);
        
        if (request()->ajax()) {
            return view('Unified.detalle_nota_drawer', compact('nota'));
        }

        return view('Unified.detalle_nota_drawer', compact('nota')); 
    }
    public function destroy($id) {
        try {
            DB::transaction(function() use ($id){
                $nota = NotaIngreso::findOrFail($id);
                $nota->delete();
            });
            return response()->json(['success' => true]);
        } catch(\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar un Grupo de Trámite y todas sus notas hijas
     */
    public function destroyGrupo($id) {
        try {
            DB::transaction(function() use ($id) {
                $grupo = \App\Models\GrupoTramite::findOrFail($id);
                
                // Eliminar todas las notas hijas primero
                foreach($grupo->notas as $nota) {
                    // Eliminar expedientes y movimientos asociados
                    foreach($nota->expedientes as $exp) {
                        \App\Models\Movimiento::where('id_expediente_nota', $exp->id)->delete();
                        $exp->delete();
                    }
                    // Eliminar activos asociados
                    NotaTieneActivo::where('id_nota_ingreso', $nota->id)->delete();
                    $nota->delete();
                }
                
                // Eliminar el grupo
                $grupo->delete();
            });
            
            return response()->json(['success' => true]);
        } catch(\Exception $e) {
            \Log::error("Error al eliminar grupo: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // ! MODAL DE DETALLE - Ver/Editar Trámite
    // =========================================================================

    /**
     * Obtener detalle completo de un Grupo de Trámite
     */
    public function getDetalleGrupo($id) {
        try {
            $grupo = \App\Models\GrupoTramite::with(['notas.expedientes.movimientos.usuario', 'notas.activos'])
                ->findOrFail($id);
            
            $notaMkt = null;
            $notaFisc = null;
            
            foreach($grupo->notas as $nota) {
                $notaData = $this->formatNotaDetalle($nota);
                if($nota->tipo_rama === 'MKT') {
                    $notaMkt = $notaData;
                } else {
                    $notaFisc = $notaData;
                }
            }
            
            return response()->json([
                'success' => true,
                'grupo' => [
                    'id' => $grupo->id,
                    'nro_nota' => $grupo->nro_nota, // Needed for Complement
                    'anio' => $grupo->anio,         // Needed for Complement
                    'id_casino' => $grupo->id_casino, // Needed for Complement
                    'tipo_solicitud' => $grupo->tipo_solicitud, // Needed for Complement
                    'fecha_inicio_evento' => $grupo->fecha_inicio_evento, // Needed
                    'fecha_fin_evento' => $grupo->fecha_fin_evento,       // Needed
                    'tipo_tarea' => $grupo->tipo_tarea,
                    'titulo' => $grupo->titulo,
                    'casino' => $grupo->casino ? $grupo->casino->nombre : '---',
                    'created_at' => $grupo->created_at ? $grupo->created_at->format('d/m/Y H:i') : null,
                ],
                'mkt' => $notaMkt,
                'fisc' => $notaFisc
            ]);
        } catch(\Throwable $e) {
            \Log::error("getDetalleGrupo error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener detalle de una Nota individual
     */
    public function getDetalleNota($id) {
        try {
            $nota = NotaIngreso::with(['expedientes.movimientos.usuario', 'activos', 'grupo'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'nota' => $this->formatNotaDetalle($nota),
                'grupo' => $nota->grupo ? [
                    'id' => $nota->grupo->id,
                    'tipo_tarea' => $nota->grupo->tipo_tarea,
                    'titulo' => $nota->grupo->titulo,
                ] : null
            ]);
        } catch(\Throwable $e) {
            \Log::error("getDetalleNota error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Formatear nota para el modal de detalle
     */
    private function formatNotaDetalle($nota) {
        // Obtener estado del último movimiento
        $estado = 'INGRESADO';
        $movimientos = [];
        
        if($nota->expedientes && $nota->expedientes->count() > 0) {
            $exp = $nota->expedientes->first();
            if($exp->movimientos && $exp->movimientos->count() > 0) {
                $estado = $exp->movimientos->last()->accion ?? 'INGRESADO';
                
                foreach($exp->movimientos->sortByDesc('id_movimiento') as $mov) {
                    $movimientos[] = [
                        'id' => $mov->id_movimiento,
                        'fecha' => $mov->fecha_movimiento ? (is_string($mov->fecha_movimiento) ? $mov->fecha_movimiento : $mov->fecha_movimiento->format('d/m/Y H:i')) : null,
                        'accion' => $mov->accion,
                        'comentario' => $mov->comentario,
                        'usuario' => $mov->usuario->nombre ?? 'Sistema',
                    ];
                }
            }
        }
        
        // Activos asociados
        $activos = [];
        if($nota->activos && $nota->activos->count() > 0) {
            foreach($nota->activos as $activo) {
                $activos[] = [
                    'id' => $activo->id ?? $activo->id_activo,
                    'id_activo' => $activo->id_activo ?? 'N/A',
                    'tipo_activo' => $activo->tipo_activo ?? 'ISLA',
                ];
            }
        }
        
        // Adjuntos
        $adjuntos = [
            'solicitud' => $nota->path_solicitud ? ['existe' => true, 'nombre' => basename($nota->path_solicitud), 'path' => $nota->path_solicitud] : ['existe' => false],
            'diseno' => $nota->path_diseno ? ['existe' => true, 'nombre' => basename($nota->path_diseno), 'path' => $nota->path_diseno] : ['existe' => false],
            'bases' => $nota->path_bases ? ['existe' => true, 'nombre' => basename($nota->path_bases), 'path' => $nota->path_bases] : ['existe' => false],
            'informe' => $nota->path_informe ? ['existe' => true, 'nombre' => basename($nota->path_informe), 'path' => $nota->path_informe] : ['existe' => false],
            'varios' => $nota->path_varios ? ['existe' => true, 'nombre' => basename($nota->path_varios), 'path' => $nota->path_varios] : ['existe' => false],
        ];
        
        // Determine casino name (could be relationship or string)
        $casinoNombre = 'N/A';
        if($nota->casino) {
            if(is_object($nota->casino)) {
                $casinoNombre = $nota->casino->nombre ?? $nota->casino->name ?? 'N/A';
            } else {
                $casinoNombre = $nota->casino;
            }
        } elseif($nota->id_casino) {
            // Fallback to known IDs
            if($nota->id_casino == 101) $casinoNombre = 'CCOL';
            elseif($nota->id_casino == 102) $casinoNombre = 'BPLAY';
            else $casinoNombre = 'Casino #' . $nota->id_casino;
        }
        
        return [
            'id' => $nota->id,
            'nro_nota' => $nota->nro_nota ?? 'N/A',
            'tipo_rama' => $nota->tipo_rama,
            'tipo_solicitud' => $nota->tipo_solicitud,
            'descripcion' => $nota->titulo ?? 'Sin descripción', // titulo actúa como descripción
            'casino' => $casinoNombre,
            'estado' => $estado,
            'fecha_inicio' => $nota->fecha_inicio_evento,
            'fecha_fin' => $nota->fecha_fin_evento,
            'created_at' => $nota->created_at ? $nota->created_at->format('d/m/Y H:i') : null,
            'adjuntos' => $adjuntos,
            'activos' => $activos,
            'movimientos' => $movimientos,
        ];
    }

    /**
     * Actualizar campos de una Nota
     */
    public function updateNota(Request $request, $id) {
        try {
            $nota = NotaIngreso::findOrFail($id);
            
            // Campos editables (mappeo de frontend a DB)
            $campoMapping = [
                'nro_nota_ing' => 'nro_nota',
                'descripcion' => 'titulo',
                'fecha_inicio' => 'fecha_inicio_evento',
                'fecha_fin' => 'fecha_fin_evento',
                'tipo_solicitud' => 'tipo_solicitud'
            ];
            
            foreach($campoMapping as $frontendCampo => $dbCampo) {
                if($request->has($frontendCampo)) {
                    $nota->$dbCampo = $request->$frontendCampo;
                }
            }
            
            $nota->save();
            
            // Registrar movimiento de edición
            $exp = $nota->expedientes->first();
            if($exp) {
                Movimiento::create([
                    'id_expediente_nota' => $exp->id,
                    'id_usuario' => session('id_usuario') ?? 1,
                    'fecha_movimiento' => \Carbon\Carbon::now(),
                    'accion' => 'EDITADO',
                    'comentario' => 'Nota editada por usuario'
                ]);
            }
            
            return response()->json(['success' => true, 'msg' => 'Nota actualizada']);
        } catch(\Throwable $e) {
            \Log::error("updateNota error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Agregar comentario a una Nota
     */
    public function addComentario(Request $request, $id) {
        try {
            $nota = NotaIngreso::with('expedientes')->findOrFail($id);
            $exp = $nota->expedientes->first();
            
            if(!$exp) {
                return response()->json(['success' => false, 'msg' => 'No se encontró expediente'], 400);
            }
            
            $mov = Movimiento::create([
                'id_expediente_nota' => $exp->id,
                'id_usuario' => session('id_usuario') ?? 1,
                'fecha_movimiento' => \Carbon\Carbon::now(),
                'accion' => 'COMENTARIO',
                'comentario' => $request->comentario
            ]);
            
            // Get user name
            $usuario = \App\Usuario::find(session('id_usuario'));
            
            return response()->json([
                'success' => true,
                'movimiento' => [
                    'id' => $mov->id_movimiento,
                    'fecha' => \Carbon\Carbon::now()->format('d/m/Y H:i'),
                    'accion' => 'COMENTARIO',
                    'comentario' => $request->comentario,
                    'usuario' => $usuario->nombre ?? 'Usuario'
                ]
            ]);
        } catch(\Throwable $e) {
            \Log::error("addComentario error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar un adjunto específico
     */
    public function deleteAdjunto($id, $campo) {
        try {
            $nota = NotaIngreso::findOrFail($id);
            
            $camposPermitidos = ['path_solicitud', 'path_diseno', 'path_bases', 'path_informe', 'path_varios'];
            if(!in_array($campo, $camposPermitidos)) {
                return response()->json(['success' => false, 'msg' => 'Campo inválido'], 400);
            }
            
            $pathActual = $nota->$campo;
            if($pathActual) {
                // Eliminar archivo físico
                Storage::disk('public')->delete($pathActual);
                
                // Limpiar campo en BD
                $nota->$campo = null;
                $nota->save();
                
                // Registrar movimiento
                $exp = $nota->expedientes->first();
                if($exp) {
                    Movimiento::create([
                        'id_expediente_nota' => $exp->id,
                        'id_usuario' => session('id_usuario') ?? 1,
                        'fecha_movimiento' => now(),
                        'accion' => 'ADJUNTO_ELIMINADO',
                        'comentario' => "Eliminó adjunto: " . basename($pathActual)
                    ]);
                }
            }
            
            return response()->json(['success' => true, 'msg' => 'Adjunto eliminado']);
        } catch(\Throwable $e) {
            \Log::error("deleteAdjunto error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    public function eliminarMasivo(Request $request) {
        try {
            $ids = $request->ids;
            if(!is_array($ids) || count($ids) == 0) return response()->json(['success' => false, 'msg' => 'No IDs provided'], 400);

            DB::transaction(function() use ($ids){
                NotaIngreso::whereIn('id', $ids)->delete();
            });
            
            return response()->json(['success' => true]);
        } catch(\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    // ! EMERGENCY DB FIXER
    public function fixPresenceTable() {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('presencia_usuarios')) {
                \Illuminate\Support\Facades\Schema::create('presencia_usuarios', function ($table) {
                    $table->increments('id_presencia');
                    $table->integer('id_usuario');
                    $table->string('ubicacion')->nullable();
                    $table->dateTime('last_seen')->nullable();
                    $table->timestamps();
                });
                return "Table 'presencia_usuarios' created successfully.";
            }
            return "Table 'presencia_usuarios' already exists.";
        } catch (\Exception $e) {
            return "Error creating table: " . $e->getMessage();
        }
    }

    // ! COLLABORATIVE FLOW
    public function flujoColaborativo(Request $request) {
        try {
            $id_nota = $request->id_nota;
            $accion = $request->accion;
            
            if(!$id_nota) return response()->json(['success' => false, 'msg' => 'ID missing'], 400);

            DB::transaction(function() use ($id_nota, $accion){
                // We update the state of the note's first expediente
                $nota = NotaIngreso::findOrFail($id_nota);
                
                // Assuming we use Expediente status for tracking workflow
                $exp = $nota->expedientes()->first(); // Or orderBy created_at dest
                
                if($exp) {
                    if($accion == 'SOLICITAR_MKT') {
                        $exp->estado_actual = 'PENDIENTE_ADJUNTOS'; // New Status
                        $exp->save();
                    
                        // Create movement log
                        $mov = new \App\Models\Movimiento();
                        $mov->id_expediente_nota = $exp->id;
                        $mov->id_usuario = 1; // HARDCODED TEMP
                        $mov->fecha_movimiento = \Carbon\Carbon::now();
                        $mov->accion = 'SOLICITUD';
                        $mov->comentario = 'Solicitud de carga de adjuntos a Marketing';
                        $mov->save();
                    }
                }
            });

            return response()->json(['success' => true]);

        } catch(\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }
}
