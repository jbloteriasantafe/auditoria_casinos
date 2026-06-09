<?php

namespace App\Http\Controllers;

use App\Casino;
use App\Procedimiento;
use App\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProcedimientoController extends Controller
{
    private static $instance;
    public static function getInstancia()
    {
        if (!isset(self::$instance)) self::$instance = new self();
        return self::$instance;
    }

    /**
     * IDs de los casinos asignados al usuario logueado. El ABM sólo puede ver/tocar estos:
     * un usuario no modifica la asignación de procedimientos de casinos que no maneja.
     */
    private function casinosDelUsuario()
    {
        $user = Usuario::find(session('id_usuario'));
        if (!$user) return [];
        return $user->casinos->pluck('id_casino')->map(function ($x) { return (int)$x; })->values()->toArray();
    }

    public function data()
    {
        $misCasinos = $this->casinosDelUsuario();

        $procedimientos = Procedimiento::orderBy('orden')
            ->get(['id_procedimiento', 'nombre', 'orden']);

        $casinos = Casino::whereIn('id_casino', $misCasinos)
            ->orderBy('nombre')->get(['id_casino', 'nombre']);

        // Por cada procedimiento, los casinos DEL USUARIO en los que está activo.
        $asignaciones = DB::table('casino_tiene_procedimiento')
            ->where('activo', 1)
            ->whereIn('id_casino', $misCasinos ?: [-1])
            ->get()
            ->groupBy('id_procedimiento')
            ->map(function ($g) {
                return $g->pluck('id_casino')->map(function ($id) { return (int)$id; })->values()->toArray();
            });

        return response()->json([
            'procedimientos'  => $procedimientos,
            'casinos'         => $casinos,           // sólo los del usuario
            'total_casinos'   => count($misCasinos),
            'asignaciones'    => (object) $asignaciones->toArray(),
        ]);
    }

    public function get($id)
    {
        $misCasinos = $this->casinosDelUsuario();
        $p = Procedimiento::findOrFail($id);

        // Estado activo/inactivo por cada casino DEL USUARIO (0 si no hay fila en la pivote).
        $pivotPorCasino = DB::table('casino_tiene_procedimiento')
            ->where('id_procedimiento', $id)
            ->whereIn('id_casino', $misCasinos ?: [-1])
            ->pluck('activo', 'id_casino');

        $casinos = Casino::whereIn('id_casino', $misCasinos)
            ->orderBy('nombre')->get(['id_casino', 'nombre'])
            ->map(function ($c) use ($pivotPorCasino) {
                return [
                    'id_casino' => (int)$c->id_casino,
                    'nombre'    => $c->nombre,
                    'activo'    => (int)($pivotPorCasino[$c->id_casino] ?? 0),
                ];
            })->values();

        return response()->json([
            'id_procedimiento' => (int)$p->id_procedimiento,
            'nombre'           => $p->nombre,
            'orden'            => (int)$p->orden,
            'casinos'          => $casinos,
        ]);
    }

    public function guardar(Request $request)
    {
        $this->validate($request, [
            'nombre'      => 'required|string|max:150|unique:procedimiento,nombre',
            'posicion'    => 'nullable|in:antes,despues',
            'ref_id'      => 'nullable|integer|exists:procedimiento,id_procedimiento',
            'casinos'     => 'array',   // mapa { id_casino: 0|1 } — activo por casino
        ]);

        DB::beginTransaction();
        try {
            // activo global SIEMPRE 1: el gate real es por casino (casino_tiene_procedimiento.activo).
            $p = Procedimiento::create([
                'nombre' => $request->nombre,
                'orden'  => 0,
                'activo' => 1,
            ]);

            $this->reordenar($p->id_procedimiento, $request->input('posicion'), $request->input('ref_id'));
            $this->guardarActivosPorCasino($p->id_procedimiento, $request->input('casinos', []));

            DB::commit();
            return response()->json(['success' => true, 'id' => $p->id_procedimiento]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function modificar(Request $request)
    {
        $this->validate($request, [
            'id_procedimiento' => 'required|exists:procedimiento,id_procedimiento',
            'nombre' => ['required', 'string', 'max:150',
                Rule::unique('procedimiento', 'nombre')->ignore($request->id_procedimiento, 'id_procedimiento')],
            'posicion' => 'nullable|in:antes,despues',
            'ref_id'   => 'nullable|integer|exists:procedimiento,id_procedimiento',
            'casinos'  => 'array',
        ]);

        DB::beginTransaction();
        try {
            $p = Procedimiento::findOrFail($request->id_procedimiento);
            $p->update(['nombre' => $request->nombre, 'activo' => 1]);

            $this->reordenar($p->id_procedimiento, $request->input('posicion'), $request->input('ref_id'));
            $this->guardarActivosPorCasino($p->id_procedimiento, $request->input('casinos', []));

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function eliminar($id)
    {
        $misCasinos = $this->casinosDelUsuario();

        // No se puede borrar un procedimiento que está activo en casinos que el usuario NO maneja:
        // sería tocar la configuración de otros. En ese caso, que lo desactive para sus casinos.
        $usadoPorOtros = DB::table('casino_tiene_procedimiento')
            ->where('id_procedimiento', $id)
            ->where('activo', 1)
            ->whereNotIn('id_casino', $misCasinos ?: [-1])
            ->exists();
        if ($usadoPorOtros) {
            return response()->json([
                'success' => false,
                'error'   => 'Este procedimiento está activo en casinos que no administrás. Desactivalo para tus casinos en lugar de borrarlo.'
            ], 409);
        }

        try {
            DB::beginTransaction();
            DB::table('casino_tiene_procedimiento')->where('id_procedimiento', $id)->delete();
            Procedimiento::destroy($id);   // RESTRICT en eventualidad_tiene_procedimiento bloquea si tiene histórico
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error'   => 'Procedimiento en uso por eventualidades existentes. Desactivalo en lugar de borrar.'
            ], 409);
        }
    }

    /**
     * Setea activo/inactivo del procedimiento por cada casino DEL USUARIO. NO toca casinos ajenos
     * (la pivote de otros casinos queda intacta). $activos = mapa { id_casino: 0|1 }.
     */
    private function guardarActivosPorCasino($idProcedimiento, $activos)
    {
        $misCasinos = $this->casinosDelUsuario();
        foreach ($misCasinos as $idCasino) {
            $on = !empty($activos[$idCasino]) ? 1 : 0;   // empty('0') === true → off
            DB::table('casino_tiene_procedimiento')->updateOrInsert(
                ['id_casino' => $idCasino, 'id_procedimiento' => $idProcedimiento],
                ['activo' => $on]
            );
        }
    }

    /**
     * Reubica el procedimiento $idActual en la lista global según "antes/después de" $refId,
     * y renumera todo el catálogo a 10,20,30,… (orden es global, no por casino).
     */
    private function reordenar($idActual, $posicion, $refId)
    {
        $ids = Procedimiento::orderBy('orden')->orderBy('id_procedimiento')
            ->pluck('id_procedimiento')->map(function ($x) { return (int)$x; })->toArray();

        // saco el actual de la secuencia
        $ids = array_values(array_filter($ids, function ($x) use ($idActual) { return $x !== (int)$idActual; }));

        // posición de inserción
        $pos = count($ids); // por defecto: al final
        $refId = $refId ? (int)$refId : null;
        if ($refId && $refId !== (int)$idActual) {
            $idx = array_search($refId, $ids, true);
            if ($idx !== false) {
                $pos = ($posicion === 'antes') ? $idx : $idx + 1;
            }
        } elseif ($posicion === 'antes') {
            $pos = 0; // al principio
        }

        array_splice($ids, $pos, 0, [(int)$idActual]);

        foreach ($ids as $i => $id) {
            Procedimiento::where('id_procedimiento', $id)->update(['orden' => ($i + 1) * 10]);
        }
    }
}
