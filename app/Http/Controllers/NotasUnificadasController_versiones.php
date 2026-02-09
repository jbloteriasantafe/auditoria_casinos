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
