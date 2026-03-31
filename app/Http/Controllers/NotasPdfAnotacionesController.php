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

        $tipos = [
            'solicitud' => ['nombre' => 'Solicitud Concesionario', 'campos' => ['path_solicitud', 'path_pautas']],
            'diseno'    => ['nombre' => 'Diseño/Arte',             'campos' => ['path_diseno']],
            'bases'     => ['nombre' => 'Bases y Condiciones',     'campos' => ['path_bases']],
            'varios'    => ['nombre' => 'Archivos Varios',         'campos' => ['path_varios']],
            'informe'   => ['nombre' => 'Informe Técnico',         'campos' => ['path_informe']],
        ];

        foreach($tipos as $tipoKey => $tipoInfo) {
            // Mostrar solo la versión más reciente por tipo (las demás se ven dentro del editor)
            $ultima = \App\Models\NotaArchivoVersion::where('id_nota_ingreso', $id_nota)
                ->where('tipo_archivo', $tipoKey)
                ->orderBy('version', 'desc')
                ->first();

            if($ultima && $this->esPdf($ultima->path_archivo)) {
                $pdfs[] = [
                    'tipo'       => $tipoKey,
                    'version_id' => $ultima->id,
                    'nombre'     => $tipoInfo['nombre'],
                    'archivo'    => $ultima->nombre_original ?? basename($ultima->path_archivo),
                    'url'        => "/notas-unificadas/visualizar-version/{$ultima->id}",
                ];
            } else {
                // Retrocompatibilidad: notas sin historial de versiones
                $path = null;
                foreach($tipoInfo['campos'] as $campo) {
                    if(!empty($nota->$campo)) { $path = $nota->$campo; break; }
                }
                if($path && $this->esPdf($path)) {
                    $pdfs[] = [
                        'tipo'       => $tipoKey,
                        'version_id' => null,
                        'nombre'     => $tipoInfo['nombre'],
                        'archivo'    => basename($path),
                        'url'        => "/notas-unificadas/visualizar/{$id_nota}/{$tipoKey}",
                    ];
                }
            }
        }

        return response()->json($pdfs);
    }

    /**
     * Obtener datos completos de un PDF específico para el editor
     */
    public function obtenerDatos($id_nota, $tipo, Request $request)
    {
        $nota = NotaIngreso::findOrFail($id_nota);

        $versionId = $request->query('version_id');

        if($versionId) {
            $version = \App\Models\NotaArchivoVersion::findOrFail($versionId);
            $path = $version->path_archivo;
            $url  = "/notas-unificadas/visualizar-version/{$versionId}";
        } else {
            $path = $this->obtenerPath($nota, $tipo);
            $url  = "/notas-unificadas/visualizar/{$id_nota}/{$tipo}";
        }

        if(!$path || !Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'Archivo no encontrado'], 404);
        }
        
        // Obtener anotaciones guardadas (flechas y rectángulos) — filtradas por version_id
        $anotaciones = DB::table('notas_pdf_anotaciones')
            ->where('id_nota_ingreso', $id_nota)
            ->where('tipo_archivo', $tipo)
            ->where(function($q) use ($versionId) {
                if($versionId) {
                    $q->where('version_id', $versionId);
                } else {
                    $q->whereNull('version_id');
                }
            })
            ->orderBy('pagina', 'asc')
            ->get();

        // Obtener comentarios con sus respuestas — filtrados por version_id
        $comentarios = DB::table('notas_pdf_comentarios')
            ->where('id_nota_ingreso', $id_nota)
            ->where('tipo_archivo', $tipo)
            ->where(function($q) use ($versionId) {
                if($versionId) {
                    $q->where('version_id', $versionId);
                } else {
                    $q->whereNull('version_id');
                }
            })
            ->whereNull('padre_id')
            ->orderBy('numero_ref', 'asc')
            ->get();
        
        // Cache de imágenes por nombre de usuario
        $imagenesCache = [];
        $resolverImagen = function($nombre) use (&$imagenesCache) {
            if (!isset($imagenesCache[$nombre])) {
                $u = \App\Usuario::where('nombre', $nombre)->first();
                $imagenesCache[$nombre] = $u ? $u->imagen : null;
            }
            return $imagenesCache[$nombre];
        };

        foreach($comentarios as $c) {
            $c->user_imagen = $resolverImagen($c->usuario);
            $c->respuestas = DB::table('notas_pdf_comentarios')
                ->where('padre_id', $c->id)
                ->orderBy('created_at', 'asc')
                ->get();
            foreach($c->respuestas as $r) {
                $r->user_imagen = $resolverImagen($r->usuario);
            }
        }
        
        // Versiones disponibles para este tipo (para el selector dentro del editor)
        $versiones = \App\Models\NotaArchivoVersion::where('id_nota_ingreso', $id_nota)
            ->where('tipo_archivo', $tipo)
            ->orderBy('version', 'desc')
            ->get()
            ->map(function($v) {
                return [
                    'id'             => $v->id,
                    'version'        => $v->version,
                    'nombre_original'=> $v->nombre_original ?? basename($v->path_archivo),
                    'created_at'     => $v->created_at ? \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i') : null,
                    'url'            => "/notas-unificadas/visualizar-version/{$v->id}",
                ];
            });

        return response()->json([
            'nota' => [
                'id' => $nota->id_nota_ingreso,
                'nro_nota' => $nota->nro_nota,
                'anio' => $nota->anio,
                'titulo' => $nota->titulo,
                'path_solicitud' => $nota->path_solicitud,
                'path_diseno' => $nota->path_diseno,
                'path_bases' => $nota->path_bases,
                'path_varios' => $nota->path_varios,
                'path_informe' => $nota->path_informe,
            ],
            'tipo_archivo'   => $tipo,
            'version_id'     => $versionId,
            'url'            => $url,
            'versiones'      => $versiones,
            'anotaciones'    => $anotaciones,
            'comentarios'    => $comentarios
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
            
            $versionId = $request->input('version_id') ?: null;

            $userImagen = $user->imagen ?? null;

            $id = DB::table('notas_pdf_comentarios')->insertGetId([
                'id_nota_ingreso' => $request->id_nota_ingreso,
                'tipo_archivo' => $request->tipo_archivo,
                'version_id' => $versionId,
                'pagina' => $request->pagina,
                'pos_x' => $request->pos_x,
                'pos_y' => $request->pos_y,
                'numero_ref' => $request->input('numero_ref'),
                'id_usuario' => session('id_usuario'),
                'usuario' => $user->nombre,
                'mensaje' => $request->mensaje,
                'padre_id' => $request->padre_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $nuevo = DB::table('notas_pdf_comentarios')->where('id', $id)->first();
            $nuevo->user_imagen = $userImagen;
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
        $comentario = DB::table('notas_pdf_comentarios')->where('id', $id)->first();
        if (!$comentario) {
            return response()->json(['success' => false, 'msg' => 'No encontrado'], 404);
        }

        $id_usuario = session('id_usuario');
        $esPropio = ($comentario->id_usuario == $id_usuario);

        if (!$esPropio) {
            $usuario_data = UsuarioController::getInstancia()->buscarUsuario($id_usuario);
            $u = $usuario_data['usuario'];
            $esAdmin = $u->es_superusuario || $u->es_administrador || $u->es_auditor || $u->es_despacho || $u->es_control;
            if (!$esAdmin) {
                return response()->json(['success' => false, 'msg' => 'Solo puede eliminar sus propios comentarios'], 403);
            }
        }

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
            $versionId = $request->input('version_id') ?: null;

            if (!$id_nota || !$tipo || !$pagina) {
                throw new \Exception("Faltan datos requeridos");
            }

            // Usar updateOrInsert para crear o actualizar
            DB::table('notas_pdf_anotaciones')->updateOrInsert(
                [
                    'id_nota_ingreso' => $id_nota,
                    'tipo_archivo' => $tipo,
                    'version_id' => $versionId,
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

    /**
     * Subir nueva versión de un archivo desde el editor de anotaciones
     */
    public function subirNuevaVersion(Request $request)
    {
        try {
            $idNota = $request->id_nota;
            $tipo = $request->tipo_archivo;
            $nota = \App\Models\NotaIngreso::findOrFail($idNota);

            if (!$request->hasFile('archivo') || !$request->file('archivo')->isValid()) {
                return response()->json(['success' => false, 'msg' => 'Archivo no válido'], 422);
            }

            $file = $request->file('archivo');
            $nombreOriginal = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $baseName = pathinfo($nombreOriginal, PATHINFO_FILENAME);
            $uniqueName = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName) . '.' . $extension;

            // Mapear tipo a carpeta
            $folders = [
                'solicitud' => 'solicitudes', 'pautas' => 'solicitudes',
                'diseno' => 'disenos', 'bases' => 'bases',
                'informe' => 'informes', 'varios' => 'archivos_varios'
            ];
            $folder = $folders[$tipo] ?? 'archivos_varios';
            $path = $file->storeAs($folder, $uniqueName, 'public');

            // Crear versión
            $version = \App\Models\NotaArchivoVersion::getNextVersion($nota->id, $tipo);
            $nuevaVersion = \App\Models\NotaArchivoVersion::create([
                'id_nota_ingreso' => $nota->id,
                'tipo_archivo' => $tipo,
                'version' => $version,
                'path_archivo' => $path,
                'nombre_original' => $nombreOriginal,
                'created_at' => \Carbon\Carbon::now(),
                'created_by' => session('id_usuario') ?? 1
            ]);

            // Actualizar campo principal de la nota (retrocompatibilidad)
            $campoNota = $this->obtenerCampoNota($tipo);
            if ($campoNota) {
                $nota->$campoNota = $path;
                $nota->save();
            }

            // Registrar movimiento
            $userId = session('id_usuario') ?? 1;
            $usuario = \App\Usuario::find($userId);
            $nombreUsr = $usuario ? $usuario->nombre : 'Usuario';
            $expediente = $nota->expedientes->first();
            if ($expediente) {
                $mov = new \App\Models\Movimiento;
                $mov->id_expediente_nota = $expediente->id;
                $mov->id_usuario = $userId;
                $mov->fecha_movimiento = \Carbon\Carbon::now();
                $mov->accion = 'ADJUNTO_REEMPLAZADO';
                $mov->comentario = $nombreUsr . ' subió nueva versión (v' . $version . ') de ' . $tipo . ': "' . $nombreOriginal . '"';
                $mov->save();
            }

            return response()->json([
                'success' => true,
                'version_id' => $nuevaVersion->id,
                'version' => $version,
                'nombre_original' => $nombreOriginal,
                'msg' => 'Versión ' . $version . ' subida correctamente'
            ]);

        } catch (\Throwable $e) {
            \Log::error("Error subirNuevaVersion: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    // ========== HELPERS ==========

    private function obtenerCampoNota($tipo)
    {
        $map = [
            'solicitud' => 'path_solicitud', 'pautas' => 'path_solicitud',
            'diseno' => 'path_diseno', 'bases' => 'path_bases',
            'informe' => 'path_informe', 'varios' => 'path_varios'
        ];
        return $map[$tipo] ?? null;
    }
    
    private function esPdf($path)
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf';
    }
    
    private function obtenerPath($nota, $tipo)
    {
        switch($tipo) {
            case 'pautas':
            case 'solicitud':
                return $nota->path_solicitud ?? $nota->path_pautas;
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
