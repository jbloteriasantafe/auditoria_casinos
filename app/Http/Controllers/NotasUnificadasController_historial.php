    /**
     * Vista para mostrar historial de versiones de un archivo
     */
    public function verHistorialVersiones($id, $tipo) {
        $nota = NotaIngreso::findOrFail($id);
        $versiones = \App\Models\NotaArchivoVersion::where('id_nota_ingreso', $id)
            ->where('tipo_archivo', $tipo)
            ->orderBy('version', 'desc')
            ->get();
        
        $tipoNombres = [
            'solicitud' => 'Solicitud',
            'diseno' => 'Diseño',
            'bases' => 'Bases y Condiciones',
            'varios' => 'Archivos Varios',
            'informe' => 'Informe Técnico'
        ];
        
        return view('Unified.historial_versiones', [
            'nota' => $nota,
            'versiones' => $versiones,
            'tipo' => $tipo,
            'tipoNombre' => $tipoNombres[$tipo] ?? $tipo
        ]);
    }
