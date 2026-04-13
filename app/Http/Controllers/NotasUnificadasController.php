<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotaIngreso;
use App\Models\Expediente;
use App\Models\Movimiento;
use App\Models\NotaTieneActivo;
use App\Models\Disposicion;
use App\Models\NotaTipoEvento;
use App\Models\NotaCategoria;
use App\Models\NotaEstado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;

use App\Isla; // Importar modelo Isla (Legacy)

class NotasUnificadasController extends Controller
{
    // URL base de la API del sistema online
    const API_ONLINE_URL = 'http://10.1.121.30:8003/api/auditoria';
    const API_ONLINE_TOKEN = 'TokenParaJuego';
    // Prueba: 
    //const API_ONLINE_URL = 'http://10.1.121.24:8004/api/auditoria';

    /**
     * Obtener plataformas y juegos desde la API online (cacheado 1 hora)
     */
    private static $datosOnline = null;

    private static function obtenerDatosOnline()
    {
        if (self::$datosOnline !== null) {
            return self::$datosOnline;
        }

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::API_ONLINE_URL . '/plataformasYJuegos');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_PROXY, null);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'API-Token: ' . self::API_ONLINE_TOKEN
            ]);
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code != 200 || $response === false) {
                \Log::error("API Online error: HTTP $code");
                self::$datosOnline = [];
                return [];
            }
            self::$datosOnline = json_decode($response) ?: [];
            return self::$datosOnline;
        } catch (\Exception $e) {
            \Log::error("API Online error: " . $e->getMessage());
            self::$datosOnline = [];
            return [];
        }
    }

    private static function obtenerPlataformasOnline()
    {
        return self::obtenerDatosOnline();
    }

    /**
     * Resolver nombre de casino o plataforma
     */
    public static function resolverNombreCasino($id_casino, $id_plataforma = null)
    {
        if ($id_plataforma) {
            $plataformas = self::obtenerPlataformasOnline();
            foreach ($plataformas as $p) {
                if ($p->id_plataforma == $id_plataforma) {
                    return str_replace('.bet.ar', '', $p->nombre) . ' (' . $p->codigo . ')';
                }
            }
            return 'Plataforma #' . $id_plataforma;
        }

        if ($id_casino) {
            $casino = \App\Casino::find($id_casino);
            return $casino ? $casino->nombre : 'Casino #' . $id_casino;
        }

        return '---';
    }

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

        // --- PERMISOS DE CASINO POR USUARIO ---
        // Superusuarios/controladores ven todo; otros solo sus casinos asignados o plataforma por rol
        // Permisos: null = sin filtro (super/admin/control)
        $casinosPermitidos = null;
        $plataformasPermitidas = null;

        if (!$usuario->es_superusuario && !$usuario->es_controlador) {
            // Buscar roles de notas unificadas del usuario
            $rolesNotas = DB::table('usuario_tiene_rol')
                ->join('rol', 'rol.id_rol', '=', 'usuario_tiene_rol.id_rol')
                ->where('usuario_tiene_rol.id_usuario', $id_usuario)
                ->where('rol.descripcion', 'LIKE', 'CARGA_NOTAS_%')
                ->pluck('rol.descripcion')
                ->toArray();

            // CARGA_NOTAS_UNIFICADAS = acceso a casinos físicos asignados
            $tieneCasinos = in_array('CARGA_NOTAS_UNIFICADAS', $rolesNotas);
            // Otros roles CARGA_NOTAS_* = acceso a plataformas (ej: CARGA_NOTAS_BPLAY, CARGA_NOTAS_CCO)
            $rolesPlataforma = array_filter($rolesNotas, function($r) {
                return $r !== 'CARGA_NOTAS_UNIFICADAS';
            });

            if ($tieneCasinos) {
                $casinosPermitidos = DB::table('usuario_tiene_casino')
                    ->where('id_usuario', $id_usuario)
                    ->pluck('id_casino')
                    ->toArray();
            }

            if (!empty($rolesPlataforma)) {
                $plataformasPermitidas = [];
                $plataformasOnlineData = self::obtenerPlataformasOnline();
                foreach ($rolesPlataforma as $rolDesc) {
                    $codigo = str_replace('CARGA_NOTAS_', '', $rolDesc);
                    foreach ($plataformasOnlineData as $p) {
                        if ($p->codigo === $codigo) {
                            $plataformasPermitidas[] = $p->id_plataforma;
                            break;
                        }
                    }
                }
            }

            // Si no tiene ningún rol de notas, no ve nada
            if (!$tieneCasinos && empty($rolesPlataforma)) {
                $casinosPermitidos = [];
                $plataformasPermitidas = [];
            }
        }

        $esFuncionario = $usuario->tieneRol('FUNCIONARIO');

        // Obtener Grupos de Trámite (con notas hijas)
        $gruposQuery = \App\Models\GrupoTramite::with(['notas', 'notas.expedientes', 'casino']);

        // Filtrar por casinos/plataformas permitidos del usuario
        if ($casinosPermitidos !== null || $plataformasPermitidas !== null) {
            $gruposQuery->where(function($q) use ($casinosPermitidos, $plataformasPermitidas) {
                if ($casinosPermitidos !== null && !empty($casinosPermitidos)) {
                    $q->orWhereIn('id_casino', $casinosPermitidos);
                }
                if ($plataformasPermitidas !== null && !empty($plataformasPermitidas)) {
                    $q->orWhereIn('id_plataforma', $plataformasPermitidas);
                }
            });
        }

        // Funcionario: excluir grupos que SOLO tienen notas FISC (sin MKT)
        if ($esFuncionario) {
            $gruposQuery->whereHas('notas', function($q) {
                $q->where('tipo_rama', 'MKT');
            });
        }
        
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
        if ($request->has('id_plataforma') && !empty($request->id_plataforma)) {
            $gruposQuery->where('id_plataforma', $request->id_plataforma);
        } elseif ($request->has('id_casino') && !empty($request->id_casino)) {
            $gruposQuery->where('id_casino', $request->id_casino);
        }
        // Rama (MKT / FISC)
        if ($request->has('rama') && !empty($request->rama)) {
            $rama = $request->rama;
            $gruposQuery->whereHas('notas', function($q) use ($rama) {
                $q->where('tipo_rama', $rama);
            });
        }
        // Estado del expediente
        if ($request->has('estado') && !empty($request->estado)) {
            $estado = $request->estado;
            $gruposQuery->whereHas('notas.expedientes', function($q) use ($estado) {
                $q->where('estado_actual', $estado);
            });
        }
        // Rango de fechas (fecha de carga)
        if ($request->has('fecha_desde') && !empty($request->fecha_desde)) {
            $gruposQuery->whereDate('created_at', '>=', $request->fecha_desde);
        }
        if ($request->has('fecha_hasta') && !empty($request->fecha_hasta)) {
            $gruposQuery->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        // Quick Filters
        if ($request->has('quick_filter')) {
            if($request->quick_filter === 'hoy') {
                // Grupos que tienen alguna nota creada hoy
                $gruposQuery->whereHas('notas', function($q) {
                    $q->whereDate('created_at', \Carbon\Carbon::today());
                });
            }
            if($request->quick_filter === 'proximos') {
                // Grupos con alguna nota (MKT o FISC) con fecha_pretendida_aprobacion >= hoy
                $hoy = \Carbon\Carbon::today()->toDateString();
                $gruposQuery->whereHas('notas', function($q) use ($hoy) {
                    $q->whereDate('fecha_pretendida_aprobacion', '>=', $hoy);
                });
                $gruposQuery->orderByRaw('(
                    SELECT MIN(ni.fecha_pretendida_aprobacion)
                    FROM notas_ingreso ni
                    WHERE ni.id_grupo = grupos_tramites.id
                      AND ni.fecha_pretendida_aprobacion >= ?
                ) ASC', [$hoy]);
            }
            if($request->quick_filter === 'por_vencer') {
                // Grupos con fecha_pretendida_aprobacion entre hoy y hoy+7 días (cualquier estado)
                $hoy = \Carbon\Carbon::today()->toDateString();
                $en7dias = \Carbon\Carbon::today()->addDays(7)->toDateString();
                $gruposQuery->whereHas('notas', function($q) use ($hoy, $en7dias) {
                    $q->whereDate('fecha_pretendida_aprobacion', '>=', $hoy)
                      ->whereDate('fecha_pretendida_aprobacion', '<=', $en7dias);
                });
                $gruposQuery->orderByRaw('(
                    SELECT MIN(ni.fecha_pretendida_aprobacion)
                    FROM notas_ingreso ni
                    WHERE ni.id_grupo = grupos_tramites.id
                      AND ni.fecha_pretendida_aprobacion >= ?
                      AND ni.fecha_pretendida_aprobacion <= ?
                ) ASC', [$hoy, $en7dias]);
            }
        }

        // Sorting (no aplicar si ya se ordenó por quick_filter con orden propio)
        if (!$request->has('quick_filter') || !in_array($request->quick_filter, ['proximos', 'por_vencer'])) {
            $sort = $request->get('sort_by', 'id');
            $order = $request->get('order', 'desc');
            if(!in_array($order, ['asc','desc'])) $order = 'desc';

            // Columnas que están en notas_ingreso, no en grupos_tramites
            if ($sort === 'fecha_pretendida_aprobacion') {
                $gruposQuery->orderByRaw('(
                    SELECT MIN(ni.fecha_pretendida_aprobacion)
                    FROM notas_ingreso ni
                    WHERE ni.id_grupo = grupos_tramites.id
                ) ' . $order);
            } else {
                // Validar columnas permitidas de grupos_tramites
                $columnasPermitidas = ['id', 'created_at', 'nro_nota', 'id_casino', 'anio', 'titulo', 'tipo_solicitud'];
                if (!in_array($sort, $columnasPermitidas)) $sort = 'id';
                $gruposQuery->orderBy($sort, $order);
            }
        }

        $pageSize = $request->page_size ?: 10;
        $grupos = $gruposQuery->paginate($pageSize);
        
        // Preserve params
        $grupos->appends($request->all());

        // Notas sueltas sin grupo (legacy) - limitadas para no traer todo
        $notasSueltasQuery = NotaIngreso::with(['casino', 'expedientes'])
            ->whereNull('id_grupo');
        if ($casinosPermitidos !== null || $plataformasPermitidas !== null) {
            $notasSueltasQuery->where(function($q) use ($casinosPermitidos, $plataformasPermitidas) {
                if ($casinosPermitidos !== null && !empty($casinosPermitidos)) {
                    $q->orWhereIn('id_casino', $casinosPermitidos);
                }
                if ($plataformasPermitidas !== null && !empty($plataformasPermitidas)) {
                    $q->orWhereIn('id_plataforma', $plataformasPermitidas);
                }
            });
        }
        $notasSueltas = $notasSueltasQuery->orderBy('id', 'desc')->limit(50)->get();


        // Funcionario NO puede eliminar; los demás roles admin sí
        $puedeEliminar = !$esFuncionario && ($usuario->es_superusuario || $usuario->es_administrador || $usuario->es_auditor || $usuario->es_despacho || $usuario->es_control);

        // Nivel de permisos para cambio de estado: funcionario tiene prioridad sobre admin
        $esAdmin = $usuario->es_superusuario || $usuario->es_administrador || $usuario->es_auditor || $usuario->es_despacho || $usuario->es_control;
        $nivelEstado = $esFuncionario ? 'funcionario' : ($esAdmin ? 'admin' : 'regular');

        if ($request->ajax()) {
            if ($request->get('view_mode') === 'kanban') {
                return response()->json([
                    'html' => view('Unified.kanban_notas', compact('grupos', 'notasSueltas', 'puedeEliminar'))->render(),
                    'total' => $grupos->total(),
                ]);
            }
            return response()->json([
                'html' => view('Unified.tabla_notas', compact('grupos', 'notasSueltas', 'puedeEliminar', 'esFuncionario'))->render(),
                'total' => $grupos->total(),
            ]);
        }

        // Casinos físicos
        $casinos = \App\Casino::all()->map(function($c) {
            $c->es_plataforma = false;
            return $c;
        });

        // Plataformas online (desde API)
        $plataformasOnline = self::obtenerPlataformasOnline();
        $casinos_online = collect($plataformasOnline)->map(function($p) {
            $c = new \stdClass();
            $c->id_casino = null;
            $c->id_plataforma = $p->id_plataforma;
            $c->nombre = str_replace('.bet.ar', '', $p->nombre) . ' (' . $p->codigo . ')';
            $c->codigo = $p->codigo;
            $c->es_plataforma = true;
            return $c;
        });
        $casinos = $casinos->concat($casinos_online);

        // Filtrar dropdown según permisos del usuario
        if ($casinosPermitidos !== null || $plataformasPermitidas !== null) {
            $casinos = $casinos->filter(function($c) use ($casinosPermitidos, $plataformasPermitidas) {
                if (!$c->es_plataforma && $casinosPermitidos !== null) {
                    return in_array($c->id_casino, $casinosPermitidos);
                }
                if ($c->es_plataforma && $plataformasPermitidas !== null) {
                    return in_array($c->id_plataforma, $plataformasPermitidas);
                }
                return false;
            })->values();
        }

        // Tipos de evento y categorías desde BD
        $categorias = NotaCategoria::activasPorRama();
        $tipos_evento = NotaTipoEvento::activosPorRama();

        $estados = NotaEstado::activos();

        $totalGrupos = $grupos->total();

        // Retornar vista principal (Bandejas)
        return view('Unified.index', compact('grupos', 'notasSueltas', 'casinos', 'categorias', 'tipos_evento', 'estados', 'puedeEliminar', 'nivelEstado', 'esFuncionario', 'totalGrupos'));
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
                'id_categoria'   => null,
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
            'tipo_solicitud' => 'required|in:EVENTO,PUBLICIDAD',
        ];

        // Debe tener casino O plataforma (no ambos, no ninguno)
        if (!$request->id_casino && !$request->id_plataforma) {
            return response()->json([
                'success' => false,
                'msg' => 'Debe seleccionar un Casino o Plataforma'
            ], 422);
        }
        
        if($request->tipo_solicitud == 'EVENTO') {
            $rules['fecha_inicio_evento'] = 'required|date';
            // Fecha fin obligatoria solo para MKT, opcional para FISC
            if($request->tipo_tarea === 'FISCALIZACION') {
                $rules['fecha_fin_evento'] = 'nullable|date';
            } else {
                $rules['fecha_fin_evento'] = 'required|date';
            }
        }
        
        // Custom messages for better UX
        $messages = [
            'nro_nota.required' => 'El Número de Nota es requerido',
            'anio.required' => 'El Año es requerido',
            'anio.integer' => 'El Año debe ser un número entero',
            'titulo.required' => 'El Título es requerido',
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
        
        // (Validación extra de EVENTO eliminada — ya no existe esa opción)

        // 0.B Buscar o Crear Grupo de Trámite
        $grupo = null;
        if($request->has('id_grupo_existente') && $request->id_grupo_existente) {
             $grupo = \App\Models\GrupoTramite::find($request->id_grupo_existente);
        }
        
        if(!$grupo) {
             $q = \App\Models\GrupoTramite::where('nro_nota', $request->nro_nota)
                    ->where('anio', $request->anio);
             if ($request->id_plataforma) {
                 $q->where('id_plataforma', $request->id_plataforma);
             } else {
                 $q->where('id_casino', $request->id_casino);
             }
             $grupo = $q->first();
        }
        
        // Determinar qué ramas crear basándose en tipo_tarea
        $ramasACrear = [];
        if($request->tipo_tarea === 'MARKETING') {
            $ramasACrear = ['MKT'];
        } elseif($request->tipo_tarea === 'FISCALIZACION') {
            $ramasACrear = ['FISC'];
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
                $grupo->id_casino = $request->id_plataforma ? null : $request->id_casino;
                $grupo->id_plataforma = $request->id_plataforma ?: null;
                $grupo->titulo = $request->titulo;
                $grupo->tipo_solicitud = $request->tipo_solicitud;
                $grupo->fecha_inicio_evento = $request->fecha_inicio_evento;
                $grupo->fecha_fin_evento = $request->fecha_fin_evento;
                $grupo->id_tipo_evento = $request->id_tipo_evento;
                $grupo->id_categoria = $request->id_categoria;
                if ($request->id_grupo_padre) {
                    $grupo->id_grupo_padre = $request->id_grupo_padre;
                }
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
                $nota->id_casino = $request->id_plataforma ? null : $request->id_casino;
                $nota->id_plataforma = $request->id_plataforma ?: null;
                $nota->titulo = $request->titulo;
                $nota->tipo_solicitud = $tipo_solicitud;
                $nota->tipo_rama = $tipo_rama;
                
                // Clasificación
                $nota->id_tipo_evento = $request->id_tipo_evento;
                $nota->id_categoria = $request->id_categoria;
                
                // Fechas
                $nota->fecha_inicio_evento = $request->fecha_inicio_evento;
                $nota->fecha_fin_evento = $request->fecha_fin_evento;
                $nota->fecha_referencia = $request->fecha_referencia;
                if ($tipo_rama === 'MKT' && $request->fecha_pretendida_aprobacion) {
                    $nota->fecha_pretendida_aprobacion = $request->fecha_pretendida_aprobacion;
                }
                
                $nota->save();

                // Asociar Activos
                if ($request->has('activos')) {
                    $this->procesarActivos($nota, $request->activos);
                }

                // Crear Expediente
                $exp = new \App\Models\Expediente();
                $exp->id_nota_ingreso = $nota->id;
                $exp->tipo_rama = $tipo_rama;
                $exp->estado_actual = NotaEstado::CARGA_INICIAL;
                $exp->save();

                // Movimiento Inicial
                $usuarioInicio = \App\Usuario::find(session('id_usuario'));
                $nombreInicio = $usuarioInicio ? $usuarioInicio->nombre : 'Usuario';
                $mov = new \App\Models\Movimiento();
                $mov->id_expediente_nota = $exp->id;
                $mov->id_usuario = session('id_usuario') ?? 1;
                $mov->fecha_movimiento = \Carbon\Carbon::now();
                $mov->accion = 'INICIO';
                $mov->comentario = $nombreInicio . ' realizó la carga inicial del trámite';
                $mov->save();

                return $nota;
            };

            $ids_notas = [];
            $main_nota = null;

            // Crear las notas según las ramas determinadas
            foreach($ramasACrear as $rama) {
                $tipoSol = $request->tipo_solicitud ?: 'PUBLICIDAD';

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
             // Buscar juegos desde cache (datos de API online)
             $id_plataforma = $request->id_plataforma;
             $datos = self::obtenerDatosOnline();
             $juegos = [];
             foreach ($datos as $plat) {
                 if ($plat->id_plataforma == $id_plataforma && isset($plat->juegos)) {
                     $juegos = $plat->juegos;
                     break;
                 }
             }
             // Filtrar por búsqueda localmente
             $busquedaLower = mb_strtolower($busqueda);
             $filtrados = array_filter($juegos, function($j) use ($busquedaLower) {
                 return mb_strpos(mb_strtolower($j->nombre_juego), $busquedaLower) !== false
                     || mb_strpos(mb_strtolower($j->cod_juego ?? ''), $busquedaLower) !== false;
             });
             $resultados = array_map(function($j) {
                 $data = [
                     'Cod Juego' => $j->cod_juego ?? '-',
                     'Juego' => $j->nombre_juego,
                     'Categoria' => $j->categoria ?? '-',
                     '% Dev' => $j->porcentaje_devolucion ?? '-',
                     'Plataforma' => ($j->escritorio ? 'PC ' : '') . ($j->movil ? 'Movil' : '')
                 ];
                 $info_str = "Cat: {$data['Categoria']} | %Dev: {$data['% Dev']}";
                 return ['id' => $j->id_juego, 'text' => $j->nombre_juego, 'info' => $info_str, 'data' => $data];
             }, array_slice(array_values($filtrados), 0, 20));
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
            // Helper para registrar versión (reutilizado en ambas ramas)
            $crearVersion = function($nota, $tipo, $path, $nombreOriginal) {
                $v = \App\Models\NotaArchivoVersion::getNextVersion($nota->id, $tipo);
                \App\Models\NotaArchivoVersion::create([
                    'id_nota_ingreso' => $nota->id,
                    'tipo_archivo'    => $tipo,
                    'version'         => $v,
                    'path_archivo'    => $path,
                    'nombre_original' => $nombreOriginal,
                    'created_at'      => \Carbon\Carbon::now(),
                    'created_by'      => session('id_usuario') ?? 1,
                ]);
            };

            if($id_nota_mkt && is_numeric($id_nota_mkt)) {
                $notaMkt = NotaIngreso::find($id_nota_mkt);
                if($notaMkt) {
                    if ($request->hasFile('adjuntoSolicitud') && $request->file('adjuntoSolicitud')->isValid()) {
                        $f = $request->file('adjuntoSolicitud');
                        $notaMkt->path_solicitud = $p = $storeFile($f, 'solicitudes');
                        $crearVersion($notaMkt, 'solicitud', $p, $f->getClientOriginalName());
                    }
                    if ($request->hasFile('adjuntoDisenio') && $request->file('adjuntoDisenio')->isValid()) {
                        $f = $request->file('adjuntoDisenio');
                        $notaMkt->path_diseno = $p = $storeFile($f, 'disenos');
                        $crearVersion($notaMkt, 'diseno', $p, $f->getClientOriginalName());
                    }
                    if ($request->hasFile('adjuntoBases') && $request->file('adjuntoBases')->isValid()) {
                        $f = $request->file('adjuntoBases');
                        $notaMkt->path_bases = $p = $storeFile($f, 'bases');
                        $crearVersion($notaMkt, 'bases', $p, $f->getClientOriginalName());
                    }
                    if ($request->hasFile('adjuntoInformeMkt') && $request->file('adjuntoInformeMkt')->isValid()) {
                        $f = $request->file('adjuntoInformeMkt');
                        $notaMkt->path_informe = $p = $storeFile($f, 'informes');
                        $crearVersion($notaMkt, 'informe', $p, $f->getClientOriginalName());
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
                    if ($request->hasFile('adjuntoSolicitudFisc') && $request->file('adjuntoSolicitudFisc')->isValid()) {
                        $f = $request->file('adjuntoSolicitudFisc');
                        $notaFisc->path_solicitud = $p = $storeFile($f, 'solicitudes');
                        $crearVersion($notaFisc, 'solicitud', $p, $f->getClientOriginalName());
                    }
                    if ($request->hasFile('adjuntoVarios') && $request->file('adjuntoVarios')->isValid()) {
                        $f = $request->file('adjuntoVarios');
                        $notaFisc->path_varios = $p = $storeFile($f, 'archivos_varios');
                        $crearVersion($notaFisc, 'varios', $p, $f->getClientOriginalName());
                    }
                    if ($request->hasFile('adjuntoInformeFisc') && $request->file('adjuntoInformeFisc')->isValid()) {
                        $f = $request->file('adjuntoInformeFisc');
                        $notaFisc->path_informe = $p = $storeFile($f, 'informes');
                        $crearVersion($notaFisc, 'informe', $p, $f->getClientOriginalName());
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
        $userId = session('id_usuario') ?? Auth::id() ?? 1;
        $usuarioAdj = \App\Usuario::find($userId);
        $nombreAdj = $usuarioAdj ? $usuarioAdj->nombre : 'Usuario';

        // Helper para guardar archivo
        $storeFile = function($file, $folder) use ($disk) {
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueName = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName) . '.' . $extension;
            return $file->storeAs($folder, $uniqueName, $disk);
        };

        // Helper para registrar movimiento
        $logMovimiento = function($nota, $campo, $nombreArchivo, $accion = 'ADJUNTO_AGREGADO') use ($userId, $nombreAdj) {
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
            $mov->comentario = $nombreAdj . ' ' . ($accion === 'ADJUNTO_REEMPLAZADO' ? 'reemplazó' : 'agregó')
                . ' ' . ($camposLegibles[$campo] ?? $campo)
                . ': "' . $nombreArchivo . '"';
            $mov->save();
        };
        
        try {
            // Mapeo de campos de formulario a campos de BD
            $camposArchivos = [
                'adjuntoSolicitud' => ['campo' => 'path_solicitud', 'folder' => 'solicitudes', 'tipo' => 'solicitud'],
                'adjuntoDisenio' => ['campo' => 'path_diseno', 'folder' => 'disenos', 'tipo' => 'diseno'],
                'adjuntoBases' => ['campo' => 'path_bases', 'folder' => 'bases', 'tipo' => 'bases'],
                'adjuntoInforme' => ['campo' => 'path_informe', 'folder' => 'informes', 'tipo' => 'informe'],
                'adjuntoVarios' => ['campo' => 'path_varios', 'folder' => 'archivos_varios', 'tipo' => 'varios']
            ];
            
            $archivosSubidos = [];
            
            foreach($camposArchivos as $inputName => $config) {
                if($request->hasFile($inputName) && $request->file($inputName)->isValid()) {
                    $file = $request->file($inputName);
                    $nombreOriginal = $file->getClientOriginalName();
                    $campo = $config['campo'];
                    $tipoArchivo = $config['tipo'];
                    
                    // Guardar archivo físicamente
                    $path = $storeFile($file, $config['folder']);
                    
                    // Guardar versión en tabla de versiones
                    $version = \App\Models\NotaArchivoVersion::getNextVersion($nota->id, $tipoArchivo);
                    \App\Models\NotaArchivoVersion::create([
                        'id_nota_ingreso' => $nota->id,
                        'tipo_archivo' => $tipoArchivo,
                        'version' => $version,
                        'path_archivo' => $path,
                        'nombre_original' => $nombreOriginal,
                        'created_at' => \Carbon\Carbon::now(),
                        'created_by' => $userId
                    ]);
                    
                    // Determinar si es reemplazo o nuevo
                    $accion = !empty($nota->$campo) ? 'ADJUNTO_REEMPLAZADO' : 'ADJUNTO_AGREGADO';
                    
                    // Actualizar campo principal (para retrocompatibilidad)
                    $nota->$campo = $path;
                    
                    // Registrar movimiento
                    $logMovimiento($nota, $campo, "$nombreOriginal (v$version)", $accion);
                    
                    $archivosSubidos[] = "$nombreOriginal (v$version)";
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
                if(!$exp) return response()->json(['success' => false, 'msg' => 'Sin expediente'], 400);

                $estadoActual = $exp->estado_actual;
                $id_usuario = session('id_usuario');
                $usuario_data = UsuarioController::getInstancia()->buscarUsuario($id_usuario);
                $usuario = $usuario_data['usuario'];

                $esFuncionario = $usuario->tieneRol('FUNCIONARIO');
                $esAdmin = !$esFuncionario && ($usuario->es_superusuario || $usuario->es_administrador || $usuario->es_auditor || $usuario->es_despacho || $usuario->es_control);

                $nivel = $esAdmin ? 'admin' : ($esFuncionario ? 'funcionario' : 'regular');
                if (!NotaEstado::transicionPermitida($estadoActual, $value, $nivel)) {
                    return response()->json(['success' => false, 'msg' => 'No tiene permisos para esta transición de estado'], 403);
                }

                $exp->estado_actual = $value;
                $exp->save();

                $mov = new Movimiento;
                $mov->id_expediente_nota = $exp->id;
                $mov->id_usuario = $id_usuario;
                $mov->fecha_movimiento = \Carbon\Carbon::now();
                $mov->accion = 'MODIFICACION';
                $mov->comentario = $usuario->nombre . ' cambió estado a: ' . $value;
                $mov->save();
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
            $q->orderBy('id', 'desc')->take(3);
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

    // ! VISUALIZAR (para mostrar PDFs en el navegador)
    public function visualizarArchivo($id, $tipo) {
        $nota = NotaIngreso::findOrFail($id);
        $path = null;
        
        switch($tipo) {
            case 'pautas': 
            case 'solicitud': 
                $path = $nota->path_solicitud; 
                break;
            case 'diseno': 
                $path = $nota->path_diseno; 
                break;
            case 'bases':  
                $path = $nota->path_bases; 
                break;
            case 'varios': 
                $path = $nota->path_varios; 
                break;
            case 'informe': 
                $path = $nota->path_informe; 
                break;
        }

        if(!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'Archivo no encontrado');
        }

        $fullPath = Storage::disk('public')->path($path);
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    
    $mimeType = mime_content_type($fullPath);
    
    // Debug
    // \Log::info("Visualizar archivo: Path=$path, Ext=$extension, Mime=$mimeType");

    // Si es PDF (por mime o por extension), mostrar inline
    if($mimeType === 'application/pdf' || $extension === 'pdf') {
        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"'
        ]);
    }
    
    // Si es imagen, mostrar inline
    if(strpos($mimeType, 'image/') === 0) {
        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"'
        ]);
    }
    
    // Otros archivos: descargar
    return response()->download($fullPath, basename($path));
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




    public function show($id) {
        $nota = NotaIngreso::with(['casino', 'expedientes'])->findOrFail($id);
        
        if (request()->ajax()) {
            return view('Unified.detalle_nota_drawer', compact('nota'));
        }

        return view('Unified.detalle_nota_drawer', compact('nota')); 
    }
    /**
     * Verifica si el usuario actual puede eliminar notas/adjuntos
     */
    private function puedeEliminar() {
        $id_usuario = session('id_usuario');
        if (!$id_usuario) return false;
        $usuario_data = UsuarioController::getInstancia()->buscarUsuario($id_usuario);
        $u = $usuario_data['usuario'];
        return $u->es_superusuario || $u->es_administrador || $u->es_auditor || $u->es_despacho || $u->es_control;
    }

    public function destroy($id) {
        if (!$this->puedeEliminar()) {
            return response()->json(['success' => false, 'msg' => 'No tiene permisos para eliminar'], 403);
        }
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
        if (!$this->puedeEliminar()) {
            return response()->json(['success' => false, 'msg' => 'No tiene permisos para eliminar'], 403);
        }
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
            
            // Notas de aprobación del grupo
            $notasAprobacion = DB::table('grupo_notas_aprobacion')
                ->where('id_grupo', $grupo->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($na) {
                    return [
                        'id' => $na->id,
                        'tipo_rama' => $na->tipo_rama,
                        'nombre_original' => $na->nombre_original,
                        'created_at' => $na->created_at ? \Carbon\Carbon::parse($na->created_at)->format('d/m/Y H:i') : null,
                        'url' => '/notas-unificadas/nota-aprobacion/visualizar/' . $na->id,
                    ];
                });

            // Datos de relación padre/hijos
            $grupoPadre = null;
            if ($grupo->id_grupo_padre) {
                $gp = \App\Models\GrupoTramite::with('casino')->find($grupo->id_grupo_padre);
                if ($gp) {
                    $grupoPadre = [
                        'id' => $gp->id,
                        'nro_nota' => $gp->nro_nota,
                        'anio' => $gp->anio,
                        'titulo' => $gp->titulo,
                        'casino' => $gp->casino ? $gp->casino->nombre : self::resolverNombreCasino($gp->id_casino, $gp->id_plataforma),
                    ];
                }
            }

            $gruposHijos = \App\Models\GrupoTramite::with('casino')
                ->where('id_grupo_padre', $grupo->id)
                ->get()
                ->map(function($gh) {
                    return [
                        'id' => $gh->id,
                        'nro_nota' => $gh->nro_nota,
                        'anio' => $gh->anio,
                        'titulo' => $gh->titulo,
                        'casino' => $gh->casino ? $gh->casino->nombre : self::resolverNombreCasino($gh->id_casino, $gh->id_plataforma),
                    ];
                });

            return response()->json([
                'success' => true,
                'grupo' => [
                    'id' => $grupo->id,
                    'nro_nota' => $grupo->nro_nota,
                    'anio' => $grupo->anio,
                    'id_casino' => $grupo->id_casino,
                    'id_plataforma' => $grupo->id_plataforma,
                    'tipo_solicitud' => $grupo->tipo_solicitud,
                    'fecha_inicio_evento' => $grupo->fecha_inicio_evento,
                    'fecha_fin_evento' => $grupo->fecha_fin_evento,
                    'titulo' => $grupo->titulo,
                    'casino' => $grupo->casino ? $grupo->casino->nombre : self::resolverNombreCasino($grupo->id_casino, $grupo->id_plataforma),
                    'created_at' => $grupo->created_at ? $grupo->created_at->format('d/m/Y H:i') : null,
                ],
                'grupo_padre' => $grupoPadre,
                'grupos_hijos' => $gruposHijos,
                'mkt' => $notaMkt,
                'fisc' => $notaFisc,
                'notas_aprobacion' => $notasAprobacion,
            ]);
        } catch(\Throwable $e) {
            \Log::error("getDetalleGrupo error: " . $e->getMessage() . " | " . $e->getFile() . ":" . $e->getLine());
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
            $estado = $exp->estado_actual ?: $estado;
            if($exp->movimientos && $exp->movimientos->count() > 0) {
                
                foreach($exp->movimientos->sortByDesc('id') as $mov) {
                    $usuario = $mov->usuario;
                    $movimientos[] = [
                        'id' => $mov->id,
                        'id_usuario' => $mov->id_usuario,
                        'fecha' => $mov->fecha_movimiento ? (is_string($mov->fecha_movimiento) ? $mov->fecha_movimiento : $mov->fecha_movimiento->format('d/m/Y H:i')) : null,
                        'accion' => $mov->accion,
                        'comentario' => $mov->comentario,
                        'usuario' => $usuario->nombre ?? 'Sistema',
                        'user_imagen' => $usuario ? $usuario->imagen : null,
                    ];
                }
            }
        }
        
        // Activos asociados (enriquecer con datos reales)
        $activos = $this->enriquecerActivos($nota->activos);
        
        // Adjuntos
        $adjuntos = [
            'solicitud' => $nota->path_solicitud ? ['existe' => true, 'nombre' => basename($nota->path_solicitud), 'path' => $nota->path_solicitud] : ['existe' => false],
            'diseno' => $nota->path_diseno ? ['existe' => true, 'nombre' => basename($nota->path_diseno), 'path' => $nota->path_diseno] : ['existe' => false],
            'bases' => $nota->path_bases ? ['existe' => true, 'nombre' => basename($nota->path_bases), 'path' => $nota->path_bases] : ['existe' => false],
            'informe' => $nota->path_informe ? ['existe' => true, 'nombre' => basename($nota->path_informe), 'path' => $nota->path_informe] : ['existe' => false],
            'varios' => $nota->path_varios ? ['existe' => true, 'nombre' => basename($nota->path_varios), 'path' => $nota->path_varios] : ['existe' => false],
        ];
        
        // Resolver nombre de casino/plataforma
        $casinoNombre = self::resolverNombreCasino($nota->id_casino, $nota->id_plataforma);

        // Resolver tipo_evento y categoría por ID desde BD
        $tipoEventoNombre = $nota->id_tipo_evento ? NotaTipoEvento::nombrePorId($nota->id_tipo_evento) : null;
        $categoriaNombre = $nota->id_categoria ? NotaCategoria::nombrePorId($nota->id_categoria) : null;

        return [
            'id' => $nota->id,
            'nro_nota' => $nota->nro_nota ?? 'N/A',
            'anio' => $nota->anio,
            'tipo_rama' => $nota->tipo_rama,
            'tipo_solicitud' => $nota->tipo_solicitud,
            'descripcion' => $nota->titulo ?? 'Sin descripción',
            'casino' => $casinoNombre,
            'id_casino' => $nota->id_casino,
            'id_plataforma' => $nota->id_plataforma,
            'estado' => $estado,
            'tipo_evento' => $tipoEventoNombre,
            'id_tipo_evento' => $nota->id_tipo_evento,
            'categoria' => $categoriaNombre,
            'id_categoria' => $nota->id_categoria,
            'fecha_inicio' => $nota->fecha_inicio_evento,
            'fecha_fin' => $nota->fecha_fin_evento,
            'fecha_referencia' => $nota->fecha_referencia,
            'fecha_pretendida_aprobacion' => $nota->fecha_pretendida_aprobacion,
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
                'id_tipo_evento' => 'id_tipo_evento',
                'id_categoria' => 'id_categoria',
                'fecha_pretendida_aprobacion' => 'fecha_pretendida_aprobacion'
            ];
            
            foreach($campoMapping as $frontendCampo => $dbCampo) {
                if($request->has($frontendCampo)) {
                    $nota->$dbCampo = $request->$frontendCampo;
                }
            }
            
            $nota->save();

            // Registrar movimiento de edición
            $exp = $nota->expedientes->first();
            $id_usuario = session('id_usuario');
            $usuarioEdit = \App\Usuario::find($id_usuario);
            $nombreEdit = $usuarioEdit ? $usuarioEdit->nombre : 'Usuario';

            if($exp) {
                Movimiento::create([
                    'id_expediente_nota' => $exp->id,
                    'id_usuario' => $id_usuario ?? 1,
                    'fecha_movimiento' => \Carbon\Carbon::now(),
                    'accion' => 'EDITADO',
                    'comentario' => $nombreEdit . ' editó la nota'
                ]);
            }

            // Cambio de estado
            if ($request->has('estado') && $request->estado && $exp) {
                $nuevoEstado = $request->estado;
                $estadoActual = $exp->estado_actual;

                if ($nuevoEstado !== $estadoActual) {
                    $esFuncionario = $usuarioEdit->tieneRol('FUNCIONARIO');
                    $esAdmin = !$esFuncionario && ($usuarioEdit->es_superusuario || $usuarioEdit->es_administrador || $usuarioEdit->es_auditor || $usuarioEdit->es_despacho || $usuarioEdit->es_control);

                    $nivel = $esAdmin ? 'admin' : ($esFuncionario ? 'funcionario' : 'regular');
                    if (!NotaEstado::transicionPermitida($estadoActual, $nuevoEstado, $nivel)) {
                        return response()->json(['success' => false, 'msg' => 'No tiene permisos para esta transición de estado'], 403);
                    }

                    $exp->estado_actual = $nuevoEstado;
                    $exp->save();

                    Movimiento::create([
                        'id_expediente_nota' => $exp->id,
                        'id_usuario' => $id_usuario,
                        'fecha_movimiento' => \Carbon\Carbon::now(),
                        'accion' => 'MODIFICACION',
                        'comentario' => $nombreEdit . ' cambió estado a: ' . $nuevoEstado
                    ]);
                }
            }

            return response()->json(['success' => true, 'msg' => 'Nota actualizada']);
        } catch(\Throwable $e) {
            \Log::error("updateNota error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Agregar activos a una Nota existente
     */
    public function addActivos(Request $request, $id) {
        try {
            $nota = NotaIngreso::findOrFail($id);
            $activos = $request->activos ?: [];
            $this->procesarActivos($nota, $activos);

            // Recargar y devolver la lista actualizada (misma lógica que getDetalleNota)
            $nota->load('activos');
            $lista = $this->enriquecerActivos($nota->activos);
            return response()->json(['success' => true, 'activos' => $lista]);
        } catch(\Throwable $e) {
            \Log::error("addActivos error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Enriquecer colección de activos con datos de máquina/isla
     */
    private function enriquecerActivos($activos) {
        $lista = [];
        foreach($activos as $activo) {
            $info = [
                'id' => $activo->id,
                'id_activo' => $activo->id_activo ?? 'N/A',
                'tipo_activo' => $activo->tipo_activo ?? 'ISLA',
            ];
            if($activo->tipo_activo === 'MTM' && $activo->id_activo) {
                $maq = \App\Maquina::find($activo->id_activo);
                if($maq) {
                    $info['nro_admin'] = $maq->nro_admin;
                    $info['marca'] = $maq->marca ?? '';
                    $isla = $maq->id_isla ? \App\Isla::find($maq->id_isla) : null;
                    $info['nro_isla'] = $isla ? $isla->nro_isla : null;
                }
            }
            $lista[] = $info;
        }
        return $lista;
    }

    /**
     * Eliminar un activo de una Nota
     */
    public function removeActivo($id) {
        try {
            $activo = NotaTieneActivo::findOrFail($id);
            $notaId = $activo->id_nota_ingreso;
            $activo->delete();

            $restantes = NotaTieneActivo::where('id_nota_ingreso', $notaId)->get();
            $activos = $this->enriquecerActivos($restantes);

            return response()->json(['success' => true, 'activos' => $activos]);
        } catch(\Throwable $e) {
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
                    'id' => $mov->id,
                    'id_usuario' => $mov->id_usuario,
                    'fecha' => \Carbon\Carbon::now()->format('d/m/Y H:i'),
                    'accion' => 'COMENTARIO',
                    'comentario' => $request->comentario,
                    'usuario' => $usuario->nombre ?? 'Usuario',
                    'user_imagen' => $usuario ? $usuario->imagen : null,
                ]
            ]);
        } catch(\Throwable $e) {
            \Log::error("addComentario error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar un comentario (movimiento)
     */
    public function deleteComentario($id) {
        try {
            $mov = Movimiento::where('accion', 'COMENTARIO')
                ->findOrFail($id);

            $userId = session('id_usuario');
            $esPropio = ($mov->id_usuario == $userId);

            if (!$esPropio && !$this->puedeEliminar()) {
                return response()->json(['success' => false, 'msg' => 'No tiene permisos para eliminar este comentario'], 403);
            }

            $mov->delete();
            return response()->json(['success' => true]);
        } catch(\Throwable $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar un adjunto específico
     */
    public function deleteAdjunto($id, $campo) {
        if (!$this->puedeEliminar()) {
            return response()->json(['success' => false, 'msg' => 'No tiene permisos para eliminar'], 403);
        }
        try {
            $nota = NotaIngreso::findOrFail($id);
            
            $camposPermitidos = ['path_solicitud', 'path_diseno', 'path_bases', 'path_informe', 'path_varios'];
            if(!in_array($campo, $camposPermitidos)) {
                return response()->json(['success' => false, 'msg' => 'Campo inválido'], 400);
            }
            
            $campoTipoMap = [
                'path_solicitud' => 'solicitud',
                'path_diseno'    => 'diseno',
                'path_bases'     => 'bases',
                'path_informe'   => 'informe',
                'path_varios'    => 'varios',
            ];
            $tipo = $campoTipoMap[$campo];

            // Eliminar todas las versiones históricas del disco y BD
            $versiones = \App\Models\NotaArchivoVersion::where('id_nota_ingreso', $nota->id)
                ->where('tipo_archivo', $tipo)
                ->get();
            foreach($versiones as $v) {
                Storage::disk('public')->delete($v->path_archivo);
            }
            \App\Models\NotaArchivoVersion::where('id_nota_ingreso', $nota->id)
                ->where('tipo_archivo', $tipo)
                ->delete();

            // Eliminar archivo del campo principal y limpiar BD
            $pathActual = $nota->$campo;
            if($pathActual) {
                Storage::disk('public')->delete($pathActual);
                $nota->$campo = null;
                $nota->save();

                $exp = $nota->expedientes->first();
                if($exp) {
                    $usuarioDel = \App\Usuario::find(session('id_usuario'));
                    $nombreDel = $usuarioDel ? $usuarioDel->nombre : 'Usuario';
                    Movimiento::create([
                        'id_expediente_nota' => $exp->id,
                        'id_usuario'         => session('id_usuario') ?? 1,
                        'fecha_movimiento'   => \Carbon\Carbon::now(),
                        'accion'             => 'ADJUNTO_ELIMINADO',
                        'comentario'         => $nombreDel . " eliminó adjunto: " . basename($pathActual)
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
        if (!$this->puedeEliminar()) {
            return response()->json(['success' => false, 'msg' => 'No tiene permisos para eliminar'], 403);
        }
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
                        $exp->estado_actual = NotaEstado::PENDIENTE_ADJUNTOS;
                        $exp->save();
                    
                        // Create movement log
                        $mov = new \App\Models\Movimiento();
                        $mov->id_expediente_nota = $exp->id;
                        $mov->id_usuario = session('id_usuario') ?? 1;
                        $mov->fecha_movimiento = \Carbon\Carbon::now();
                        $mov->accion = 'SOLICITUD';
                        $usuarioSol = \App\Usuario::find(session('id_usuario'));
                        $mov->comentario = ($usuarioSol ? $usuarioSol->nombre : 'Usuario') . ' solicitó carga de adjuntos a Marketing';
                        $mov->save();
                    }
                }
            });

            return response()->json(['success' => true]);

        } catch(\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obtener versiones de un archivo para comparación
     */
    public function getVersionesArchivo($id, $tipo) {
        try {
            $versiones = \App\Models\NotaArchivoVersion::getVersions($id, $tipo);
            
            return response()->json([
                'success' => true,
                'versiones' => $versiones->map(function($v) {
                    return [
                        'id' => $v->id,
                        'version' => $v->version,
                        'nombre_original' => $v->nombre_original,
                        'created_at' => $v->created_at->format('d/m/Y H:i'),
                        'path' => $v->path_archivo
                    ];
                })
            ]);
        } catch(\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Visualizar una versión específica de un archivo
     */
    public function visualizarVersion($idVersion) {
        try {
            $version = \App\Models\NotaArchivoVersion::findOrFail($idVersion);
            $path = $version->path_archivo;
            
            if(!Storage::disk('public')->exists($path)) {
                abort(404, 'Archivo no encontrado');
            }
            
            $fullPath = Storage::disk('public')->path($path);
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            
            if($extension === 'pdf') {
                return response()->file($fullPath, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . basename($path) . '"'
                ]);
            }
            
            return response()->file($fullPath);
            
        } catch(\Exception $e) {
            abort(404, 'Versión no encontrada');
        }
    }
    /**
     * Obtener historial de versiones de un archivo (AJAX)
     */
    public function getHistorialVersionesAjax($id, $tipo) {
        try {
            $versiones = \App\Models\NotaArchivoVersion::where('id_nota_ingreso', $id)
                ->where('tipo_archivo', $tipo)
                ->orderBy('version', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'versiones' => $versiones->map(function($v) {
                    return [
                        'id' => $v->id,
                        'version' => $v->version,
                        'nombre_original' => $v->nombre_original,
                        'created_at' => $v->created_at->format('d/m/Y H:i'),
                        'path' => $v->path_archivo
                    ];
                })
            ]);
        } catch(\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    // ==================== NOTAS DE APROBACIÓN ====================

    /**
     * Subir nota(s) de aprobación para un grupo
     */
    public function subirNotaAprobacion(Request $request)
    {
        try {
            $idGrupo = $request->input('id_grupo');
            $tipoRama = $request->input('tipo_rama'); // MKT o FISC
            $disk = 'public';
            $userId = session('id_usuario') ?? Auth::id() ?? 1;

            if (!$idGrupo || !$tipoRama) {
                return response()->json(['success' => false, 'msg' => 'Faltan datos requeridos'], 400);
            }

            $grupo = \App\Models\GrupoTramite::findOrFail($idGrupo);
            $subidos = [];

            $archivos = $request->file('archivos_aprobacion');
            if (!$archivos || !is_array($archivos)) {
                $archivos = $request->file('archivos_aprobacion') ? [$request->file('archivos_aprobacion')] : [];
            }

            foreach ($archivos as $file) {
                if (!$file || !$file->isValid()) continue;

                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $baseName = pathinfo($originalName, PATHINFO_FILENAME);
                $uniqueName = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName) . '.' . $extension;
                $path = $file->storeAs('notas_aprobacion', $uniqueName, $disk);

                DB::table('grupo_notas_aprobacion')->insert([
                    'id_grupo' => $idGrupo,
                    'tipo_rama' => $tipoRama,
                    'path_archivo' => $path,
                    'nombre_original' => $originalName,
                    'created_by' => $userId,
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ]);

                $subidos[] = $originalName;
            }

            return response()->json([
                'success' => true,
                'msg' => count($subidos) . ' nota(s) de aprobación subida(s)',
                'archivos' => $subidos,
            ]);
        } catch (\Throwable $e) {
            \Log::error("Error subirNotaAprobacion: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Visualizar (inline) una nota de aprobación
     */
    public function visualizarNotaAprobacion($id)
    {
        $registro = DB::table('grupo_notas_aprobacion')->where('id', $id)->first();
        if (!$registro) abort(404);

        $disk = Storage::disk('public');
        if (!$disk->exists($registro->path_archivo)) abort(404);

        $fullPath = $disk->path($registro->path_archivo);
        $mime = $disk->mimeType($registro->path_archivo);

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $registro->nombre_original . '"',
        ]);
    }

    /**
     * Descargar una nota de aprobación
     */
    public function descargarNotaAprobacion($id)
    {
        $registro = DB::table('grupo_notas_aprobacion')->where('id', $id)->first();
        if (!$registro) abort(404);

        $disk = Storage::disk('public');
        if (!$disk->exists($registro->path_archivo)) abort(404);

        return response()->download($disk->path($registro->path_archivo), $registro->nombre_original);
    }

    /**
     * Eliminar una nota de aprobación
     */
    public function eliminarNotaAprobacion($id)
    {
        if (!$this->puedeEliminar()) {
            return response()->json(['success' => false, 'msg' => 'No tiene permisos para eliminar'], 403);
        }
        try {
            $registro = DB::table('grupo_notas_aprobacion')->where('id', $id)->first();
            if (!$registro) {
                return response()->json(['success' => false, 'msg' => 'No encontrado'], 404);
            }

            // Eliminar archivo físico
            $disk = Storage::disk('public');
            if ($disk->exists($registro->path_archivo)) {
                $disk->delete($registro->path_archivo);
            }

            DB::table('grupo_notas_aprobacion')->where('id', $id)->delete();

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Buscar grupos para vincular como nota padre
     */
    public function buscarGrupos(Request $request)
    {
        $q = $request->q;
        if (!$q || strlen($q) < 2) {
            return response()->json([]);
        }

        $resultados = \App\Models\GrupoTramite::with('casino')
            ->where(function($query) use ($q) {
                $query->where('nro_nota', 'like', '%' . $q . '%')
                      ->orWhere('titulo', 'like', '%' . $q . '%');
            })
            ->orderBy('anio', 'desc')
            ->orderBy('nro_nota', 'desc')
            ->take(15)
            ->get()
            ->map(function($g) {
                $ramas = $g->notas()->pluck('tipo_rama')->toArray();
                return [
                    'id' => $g->id,
                    'nro_nota' => $g->nro_nota,
                    'anio' => $g->anio,
                    'titulo' => $g->titulo,
                    'casino' => $g->casino ? $g->casino->nombre : self::resolverNombreCasino($g->id_casino, $g->id_plataforma),
                    'ramas' => $ramas,
                ];
            });

        return response()->json($resultados);
    }

    /**
     * Asignar relación padre a un grupo
     */
    public function asignarGrupoPadre(Request $request)
    {
        try {
            $grupo = \App\Models\GrupoTramite::findOrFail($request->id_grupo);
            $padreId = $request->id_grupo_padre;

            // Validar que no se relacione consigo mismo
            if ($padreId == $grupo->id) {
                return response()->json(['success' => false, 'msg' => 'No se puede relacionar un grupo consigo mismo.'], 422);
            }

            // Validar que no genere circularidad
            if ($padreId) {
                $actual = \App\Models\GrupoTramite::find($padreId);
                while ($actual && $actual->id_grupo_padre) {
                    if ($actual->id_grupo_padre == $grupo->id) {
                        return response()->json(['success' => false, 'msg' => 'Relación circular detectada.'], 422);
                    }
                    $actual = \App\Models\GrupoTramite::find($actual->id_grupo_padre);
                }
            }

            $grupo->id_grupo_padre = $padreId;
            $grupo->save();

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Quitar relación padre de un grupo
     */
    public function quitarGrupoPadre(Request $request)
    {
        try {
            $grupo = \App\Models\GrupoTramite::findOrFail($request->id_grupo);
            $grupo->id_grupo_padre = null;
            $grupo->save();

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }
}
