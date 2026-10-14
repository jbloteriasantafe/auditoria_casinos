<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\NotaEstado;

/**
 * Tablero de métricas de Notas Unificadas (superusuario / despacho / auditor).
 *
 * El historial de estados se reconstruye desde movimientos_expedientes:
 *  - accion INICIO                                   -> entrada a CARGA INICIAL
 *  - accion MODIFICACION "... cambió estado a: X"    -> entrada al estado X
 * Si una nota no tiene INICIO, la carga es expedientes_notas.created_at.
 */
class NotasMetricasController extends Controller
{
    const DIAS_TRABADA = 7;
    // Estados finales: una nota en alguno de estos NO cuenta como trabada (se comparan sin tildes)
    const ESTADOS_FINALES = [
        'APROBADO - NOTA/DISPOSICION', 'APROBADO POR FUNCIONARIO', 'CON INFORME', 'CON INFORME NEGATIVO',
        'RECHAZADO', 'VISTO POR AUDITORIA', 'TOMADO CONOCIMIENTO', 'NO AUTORIZADO', 'FUERA DE TERMINO', 'VENCIDO',
    ];
    const ESTADO_APROBADO = 'APROBADO - NOTA/DISPOSICION';

    private function usuario()
    {
        $id = session('id_usuario');
        return $id ? UsuarioController::getInstancia()->buscarUsuario($id)['usuario'] : null;
    }

    public static function puedeVerMetricas($u)
    {
        return $u && ($u->es_superusuario || $u->es_despacho || $u->es_auditor);
    }

    public function index()
    {
        $u = $this->usuario();
        if (!self::puedeVerMetricas($u)) {
            return redirect('/notas-unificadas');
        }

        // Casinos/plataformas para el filtro (mismo armado que el listado)
        $recursos = collect(\App\Casino::all())->map(function ($c) {
            return ['id' => (string) $c->id_casino, 'nombre' => $c->nombre, 'plataforma' => false];
        });
        foreach (NotasUnificadasController::obtenerPlataformasOnlinePublic() as $p) {
            $recursos->push(['id' => 'p_' . $p->id_plataforma, 'nombre' => str_replace('.bet.ar', '', $p->nombre) . ' (' . $p->codigo . ')', 'plataforma' => true]);
        }
        foreach (NotasUnificadasController::obtenerPlataformasDeportivas() as $d) {
            $recursos->push(['id' => 'p_' . $d->id_plataforma, 'nombre' => $d->nombre, 'plataforma' => true]);
        }

        return view('Unified.metricas', ['recursos' => $recursos->values(), 'diasTrabada' => self::DIAS_TRABADA]);
    }

    public function datos(Request $request)
    {
        $u = $this->usuario();
        if (!self::puedeVerMetricas($u)) {
            return response()->json(['success' => false, 'msg' => 'Sin permisos'], 403);
        }

        try {
            // "desde" vacío = todas (sin límite inferior)
            $desde = $request->input('desde') ? \Carbon\Carbon::parse($request->input('desde'))->startOfDay() : \Carbon\Carbon::create(2000, 1, 1);
            $hasta = \Carbon\Carbon::parse($request->input('hasta') ?: \Carbon\Carbon::now()->toDateString())->endOfDay();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => 'Rango de fechas inválido'], 422);
        }
        $ahora = \Carbon\Carbon::now();

        // ---- Notas en alcance + filtro de casino/plataforma ----
        list($casinos, $plataformas) = NotasUnificadasController::alcanceNotasUsuario($u);
        $base = DB::table('notas_ingreso as n')
            ->join('grupos_tramites as g', 'g.id', '=', 'n.id_grupo')
            ->join('expedientes_notas as e', 'e.id_nota_ingreso', '=', 'n.id')
            ->select('n.id', 'n.nro_nota', 'n.anio', 'n.titulo', 'n.tipo_rama', 'n.id_categoria', 'n.id_tipo_evento',
                'g.id_casino', 'g.id_plataforma', 'e.id as id_exp', 'e.estado_actual', 'e.created_at as exp_creado');
        $base->where(function ($q) use ($casinos, $plataformas) {
            NotasUnificadasController::aplicarAlcanceGrupos($q, $casinos, $plataformas);
        });
        $idCasinoFiltro = [];
        $idPlataformaFiltro = [];
        foreach ((array) $request->input('recursos', []) as $r) {
            if (strpos($r, 'p_') === 0)
                $idPlataformaFiltro[] = (int) substr($r, 2);
            elseif ($r !== '')
                $idCasinoFiltro[] = (int) $r;
        }
        if ($idCasinoFiltro || $idPlataformaFiltro) {
            $base->where(function ($q) use ($idCasinoFiltro, $idPlataformaFiltro) {
                if ($idCasinoFiltro)
                    $q->orWhereIn('g.id_casino', $idCasinoFiltro);
                if ($idPlataformaFiltro)
                    $q->orWhereIn('g.id_plataforma', $idPlataformaFiltro);
            });
        }
        $notas = $base->get();

        // ---- Historial de estados por expediente ----
        $movs = DB::table('movimientos_expedientes')
            ->whereIn('id_expediente_nota', $notas->pluck('id_exp')->all() ?: [0])
            ->whereIn('accion', ['INICIO', 'MODIFICACION'])
            ->whereNull('deleted_at')
            ->orderBy('fecha_movimiento')->orderBy('id')
            ->get(['id_expediente_nota', 'accion', 'comentario', 'fecha_movimiento'])
            ->groupBy('id_expediente_nota');

        $categorias = DB::table('nota_categorias')->pluck('descripcion', 'id');
        $tiposEvento = DB::table('nota_tipos_evento')->pluck('descripcion', 'id');
        $nombres = [];

        $tiempoEstado = [];      // estado => [dias...] (solo tramos cerrados)
        $aprobPorMes = [];       // 'YYYY-MM' => [dias...]
        $diasAprobacion = [];
        $porMes = [];            // 'YYYY-MM' => ['MKT'=>n,'FISC'=>n]
        $porRecurso = [];
        $porCategoria = [];
        $porEstado = [];
        $trabadas = [];
        $totalPeriodo = 0;
        $aprobadasPeriodo = 0;

        foreach ($notas as $n) {
            $tramos = $this->tramos($movs->get($n->id_exp, collect()), $n->exp_creado);
            if (!$tramos) {
                continue;
            }
            $carga = $tramos[0]['entrada'];
            $recurso = $n->id_plataforma ? 'p_' . $n->id_plataforma : (string) $n->id_casino;
            if (!isset($nombres[$recurso])) {
                $nombres[$recurso] = NotasUnificadasController::resolverNombreCasino($n->id_casino, $n->id_plataforma);
            }

            // Notas trabadas: estado actual (no final) hace más de N días. Independiente del rango de fechas.
            if (!self::esEstadoFinal($n->estado_actual)) {
                $entradaActual = null;
                foreach (array_reverse($tramos) as $t) {
                    if ($t['estado'] === $n->estado_actual) {
                        $entradaActual = $t['entrada'];
                        break;
                    }
                }
                // Cambio de estado sin movimiento registrado: tomar el último tramo conocido
                $entradaActual = $entradaActual ?: end($tramos)['entrada'];
                $dias = $entradaActual->diffInDays($ahora);
                if ($dias > self::DIAS_TRABADA) {
                    $trabadas[] = [
                        'id_nota' => $n->id,
                        'nro' => $n->nro_nota . '-' . $n->anio,
                        'titulo' => $n->titulo,
                        'recurso' => $nombres[$recurso],
                        'rama' => $n->tipo_rama,
                        'estado' => $n->estado_actual,
                        'dias' => $dias,
                        'desde' => $entradaActual->format('d/m/Y'),
                    ];
                }
            }

            // El resto de las métricas: notas cargadas dentro del rango
            if ($carga->lt($desde) || $carga->gt($hasta)) {
                continue;
            }
            $totalPeriodo++;

            $mes = $carga->format('Y-m');
            if (!isset($porMes[$mes]))
                $porMes[$mes] = ['MKT' => 0, 'FISC' => 0];
            $porMes[$mes][$n->tipo_rama === 'FISC' ? 'FISC' : 'MKT']++;
            $porRecurso[$nombres[$recurso]] = (isset($porRecurso[$nombres[$recurso]]) ? $porRecurso[$nombres[$recurso]] : 0) + 1;
            $cat = $n->tipo_rama === 'FISC'
                ? 'FISC · ' . (isset($tiposEvento[$n->id_tipo_evento]) ? $tiposEvento[$n->id_tipo_evento] : 'Sin tipo')
                : 'MKT · ' . (isset($categorias[$n->id_categoria]) ? $categorias[$n->id_categoria] : 'Sin categoría');
            $porCategoria[$cat] = (isset($porCategoria[$cat]) ? $porCategoria[$cat] : 0) + 1;
            $porEstado[$n->estado_actual] = (isset($porEstado[$n->estado_actual]) ? $porEstado[$n->estado_actual] : 0) + 1;

            // Tiempo en cada estado: solo tramos cerrados
            for ($i = 0; $i < count($tramos) - 1; $i++) {
                $tiempoEstado[$tramos[$i]['estado']][] = $this->dias($tramos[$i]['entrada'], $tramos[$i + 1]['entrada']);
            }

            // Tiempo total hasta aprobar (primera entrada a APROBADO - NOTA/DISPOSICION)
            foreach ($tramos as $t) {
                if ($t['estado'] === self::ESTADO_APROBADO) {
                    $d = $this->dias($carga, $t['entrada']);
                    $diasAprobacion[] = $d;
                    $aprobPorMes[$t['entrada']->format('Y-m')][] = $d;
                    $aprobadasPeriodo++;
                    break;
                }
            }
        }

        // ---- Armado de la respuesta ----
        $orden = NotaEstado::orderBy('orden')->pluck('descripcion')->toArray();
        $tiempoEstadoOut = [];
        foreach ($tiempoEstado as $estado => $lista) {
            $tiempoEstadoOut[] = ['estado' => $estado, 'promedio' => $this->prom($lista), 'mediana' => $this->mediana($lista), 'n' => count($lista)];
        }
        usort($tiempoEstadoOut, function ($a, $b) use ($orden) {
            $ia = array_search($a['estado'], $orden);
            $ib = array_search($b['estado'], $orden);
            return ($ia === false ? 99 : $ia) - ($ib === false ? 99 : $ib);
        });

        ksort($porMes);
        ksort($aprobPorMes);
        arsort($porRecurso);
        arsort($porCategoria);
        arsort($porEstado);
        usort($trabadas, function ($a, $b) {
            return $b['dias'] - $a['dias'];
        });

        return response()->json([
            'success' => true,
            'kpis' => [
                'total' => $totalPeriodo,
                'aprobadas' => $aprobadasPeriodo,
                'trabadas' => count($trabadas),
                'promedio_aprobacion' => $this->prom($diasAprobacion),
            ],
            'tiempo_estado' => $tiempoEstadoOut,
            'aprobacion_mes' => array_map(function ($mes, $lista) {
                return ['mes' => $mes, 'promedio' => $this->prom($lista), 'n' => count($lista)];
            }, array_keys($aprobPorMes), array_values($aprobPorMes)),
            'por_mes' => array_map(function ($mes, $v) {
                return ['mes' => $mes, 'MKT' => $v['MKT'], 'FISC' => $v['FISC']];
            }, array_keys($porMes), array_values($porMes)),
            'por_recurso' => $porRecurso,
            'por_categoria' => $porCategoria,
            'por_estado' => $porEstado,
            'trabadas' => $trabadas,
            'dias_trabada' => self::DIAS_TRABADA,
        ]);
    }

    /**
     * Tramos [estado, entrada] ordenados. Estados consecutivos repetidos se unen.
     */
    private function tramos($movs, $expCreado)
    {
        $tramos = [];
        $tieneInicio = false;
        foreach ($movs as $m) {
            $estado = null;
            if ($m->accion === 'INICIO') {
                $estado = NotaEstado::CARGA_INICIAL;
                $tieneInicio = true;
            } elseif (preg_match('/estado a: (.+)$/u', trim($m->comentario), $mm)) {
                $estado = trim($mm[1]);
            }
            if ($estado === null) {
                continue;
            }
            if ($tramos && end($tramos)['estado'] === $estado) {
                continue;
            }
            $tramos[] = ['estado' => $estado, 'entrada' => \Carbon\Carbon::parse($m->fecha_movimiento)];
        }
        if (!$tieneInicio && $expCreado) {
            $creado = \Carbon\Carbon::parse($expCreado);
            if (!$tramos || $tramos[0]['entrada']->gt($creado)) {
                array_unshift($tramos, ['estado' => NotaEstado::CARGA_INICIAL, 'entrada' => $creado]);
            }
        }
        return $tramos;
    }

    public static function esEstadoFinal($estado)
    {
        $norm = strtr(mb_strtoupper(trim((string) $estado), 'UTF-8'), ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U']);
        return in_array($norm, self::ESTADOS_FINALES);
    }

    private function dias($a, $b)
    {
        return round($a->diffInMinutes($b) / 1440, 1);
    }

    private function prom($lista)
    {
        return $lista ? round(array_sum($lista) / count($lista), 1) : null;
    }

    private function mediana($lista)
    {
        if (!$lista)
            return null;
        sort($lista);
        $c = count($lista);
        return $c % 2 ? $lista[intdiv($c, 2)] : round(($lista[$c / 2 - 1] + $lista[$c / 2]) / 2, 1);
    }
}
