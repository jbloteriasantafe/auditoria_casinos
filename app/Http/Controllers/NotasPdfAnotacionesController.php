
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\NotaIngreso;
use App\Http\Controllers\UsuarioController;
use App\Models\Expediente;
use App\Models\Movimiento;

class NotasPdfAnotacionesController extends Controller
{
    /**
     * Listar todos los PDFs disponibles de una nota
     */
    public function listarPdfs($id_nota)
    {
        $nota = NotaIngreso::findOrFail($id_nota);
        
        $pdfs = [];
        
        // Recolectar todos los PDFs disponibles
        if($nota->path_pautas && $this->esPdf($nota->path_pautas)) {
            $pdfs[] = [
                'tipo' => 'pautas',
                'nombre' => 'Solicitud Concesionario',
                'archivo' => basename($nota->path_pautas),
                'url' => "/notas-unificadas/visualizar/{$id_nota}/pautas"
            ];
        }
        
        if($nota->path_diseno && $this->esPdf($nota->path_diseno)) {
            $pdfs[] = [
                'tipo' => 'diseno',
                'nombre' => 'Diseño/Arte',
                'archivo' => basename($nota->path_diseno),
                'url' => "/notas-unificadas/visualizar/{$id_nota}/diseno"
            ];
        }
        
        if($nota->path_bases && $this->esPdf($nota->path_bases)) {
            $pdfs[] = [
                'tipo' => 'bases',
                'nombre' => 'Bases y Condiciones',
                'archivo' => basename($nota->path_bases),
                'url' => "/notas-unificadas/visualizar/{$id_nota}/bases"
            ];
        }
        
        if($nota->path_varios && $this->esPdf($nota->path_varios)) {
            $pdfs[] = [
                'tipo' => 'varios',
                'nombre' => 'Archivos Varios',
                'archivo' => basename($nota->path_varios),
                'url' => "/notas-unificadas/visualizar/{$id_nota}/varios"
            ];
        }
        
        if($nota->path_informe && $this->esPdf($nota->path_informe)) {
            $pdfs[] = [
                'tipo' => 'informe',
                'nombre' => 'Informe Técnico',
                'archivo' => basename($nota->path_informe),
                'url' => "/notas-unificadas/visualizar/{$id_nota}/informe"
            ];
        }
        
        return response()->json($pdfs);
    }

    /**
     * Obtener datos completos de un PDF específico para el editor
     */
    public function obtenerDatos($id_nota, $tipo)
    {
        $nota = NotaIngreso::findOrFail($id_nota);
        
        $path = $this->obtenerPath($nota, $tipo);
        
        if(!$path || !Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'Archivo no encontrado'], 404);
        }
        
        $url = "/notas-unificadas/visualizar/{$id_nota}/{$tipo}";
        
        // Obtener anotaciones guardadas (flechas y rectángulos)
        $anotaciones = DB::table('notas_pdf_anotaciones')
            ->where('id_nota_ingreso', $id_nota)
            ->where('tipo_archivo', $tipo)
            ->orderBy('pagina', 'asc')
            ->get();
        
        // Obtener comentarios con sus respuestas
        $comentarios = DB::table('notas_pdf_comentarios')
            ->where('id_nota_ingreso', $id_nota)
            ->where('tipo_archivo', $tipo)
            ->whereNull('padre_id')
            ->orderBy('numero_ref', 'asc')
            ->get();
        
        foreach($comentarios as $c) {
            $c->respuestas = DB::table('notas_pdf_comentarios')
                ->where('padre_id', $c->id)
                ->orderBy('created_at', 'asc')
                ->get();
        }
        
        return response()->json([
            'nota' => [
                'id' => $nota->id_nota_ingreso,
                'nro_nota' => $nota->nro_nota,
                'anio' => $nota->anio,
                'titulo' => $nota->titulo ,
                'path_solicitud' => $nota->path_solicitud,
                'path_diseno' => $nota->path_diseno,
                'path_bases' => $nota->path_bases,
                'path_varios' => $nota->path_varios,
                'path_informe' => $nota->path_informe,
            ],
            'tipo_archivo' => $tipo,
            'url' => $url,
            'anotaciones' => $anotaciones,
            'comentarios' => $comentarios
        ]);
    }

    /**
     * Guardar comentario
     */
    public function guardarComentario(Request $request)
    {
        try {
            if(!$request->has('mensaje')) {
                throw new \Exception("El mensaje está vacío");
            }
            
            $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
            
            $id = DB::table('notas_pdf_comentarios')->insertGetId([
                'id_nota_ingreso' => $request->id_nota_ingreso,
                'tipo_archivo' => $request->tipo_archivo,
                'pagina' => $request->pagina,
                'pos_x' => $request->pos_x,
                'pos_y' => $request->pos_y,
                'numero_ref' => $request->input('numero_ref'),
                'usuario' => $user->nombre,
                'mensaje' => $request->mensaje,
                'padre_id' => $request->padre_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            $nuevo = DB::table('notas_pdf_comentarios')->where('id', $id)->first();
            return response()->json($nuevo);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resolver comentario
     */
    public function resolverComentario(Request $request)
    {
        $id = $request->id;
        $resuelto = $request->resuelto ? 1 : 0;
        
        DB::table('notas_pdf_comentarios')
            ->where('id', $id)
            ->update(['resuelto' => $resuelto]);
        
        return response()->json(['success' => true]);
    }

    /**
     * Eliminar comentario
     */
    public function eliminarComentario($id)
    {
        DB::table('notas_pdf_comentarios')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Guardar anotaciones (flechas y rectángulos) de una página
     */
    public function guardarAnotaciones(Request $request)
    {
        try {
            \Log::info("INTENTO GUARDAR ANOTACIONES", $request->all());
            $id_nota = $request->input('id_nota_ingreso');
            $tipo = $request->input('tipo_archivo');
            $pagina = $request->input('pagina');
            $json = $request->input('anotaciones');
            
            if (!$id_nota || !$tipo || !$pagina) {
                throw new \Exception("Faltan datos requeridos");
            }
            
            // Usar updateOrInsert para crear o actualizar
            DB::table('notas_pdf_anotaciones')->updateOrInsert(
                [
                    'id_nota_ingreso' => $id_nota,
                    'tipo_archivo' => $tipo,
                    'pagina' => $pagina
                ],
                [
                    'anotaciones_json' => $json,
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            // LOG DE AUDITORIA
            // Solo si se solicita explícitamente (Botón Guardar)
            $generarLog = $request->input('generar_log');
            
            if($generarLog == 1) {
                try {
                    $nota = NotaIngreso::find($id_nota);
                    
                    if($nota) {
                       // Obtener o crear expediente
                       $expediente = $nota->expedientes->first();
                       
                       if(!$expediente) {
                           $expediente = Expediente::create([
                               'id_nota_ingreso' => $nota->id,
                               'estado_actual' => 'EN_PROCESO'
                           ]);
                       }
                       
                       if($expediente) {
                           $id_usuario = session('id_usuario');
                           
                           if($id_usuario) {
                               $usuario_data = UsuarioController::getInstancia()->buscarUsuario($id_usuario);
                               $usuario = $usuario_data['usuario'];
                               
                               $mov = new Movimiento;
                               $mov->fecha_movimiento = date('Y-m-d H:i:s');
                               $mov->id_usuario = $usuario->id_usuario;
                               $mov->id_expediente_nota = $expediente->id; 
                               $mov->accion = 'ADJUNTO_ANOTADO'; 
                               // Formato exacto pedido por usuario "Persona, Archivo : realizo anotaciones"
                               // Usamos tipo_archivo en mayusculas
                               $mov->comentario = $usuario->nombre . ', ' . strtoupper($tipo) . ': Realizó anotaciones en página ' . $pagina;
                               $mov->save();
                           }
                       }
                    }
                } catch(\Exception $e) {
                    \Log::error("Error guardando log auditoria: " . $e->getMessage());
                }
            }
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            \Log::error("Error guardando anotaciones: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ========== HELPERS ==========
    
    private function esPdf($path)
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf';
    }
    
    private function obtenerPath($nota, $tipo)
    {
        switch($tipo) {
            case 'pautas':
            case 'solicitud':
                return $nota->path_pautas ?? $nota->path_solicitud;
            case 'diseno':
                return $nota->path_diseno;
            case 'bases':
                return $nota->path_bases;
            case 'varios':
                return $nota->path_varios;
            case 'informe':
                return $nota->path_informe;
            default:
                return null;
        }
    }
}
