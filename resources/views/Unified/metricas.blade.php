@extends('includes.dashboard')
@section('headerLogo')
    <span class="etiquetaLogoMaquinas">@svg('maquinas', 'iconoMaquinas')</span>
@endsection

@section('estilos')
    <style>
        .met { padding:6px 4px 30px; color:#1e293b; }
        .met-head { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:14px; }
        .met-head h3 { margin:0; font-weight:600; font-size:22px; }
        .met-head h3 i { color:#6366f1; margin-right:6px; }
        .met-volver { color:#64748b; font-size:13px; text-decoration:none !important; }
        .met-volver:hover { color:#4f46e5; }

        /* Barra de filtros */
        .met-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:10px 14px; background:#fff; border:1px solid #e2e8f0;
            border-radius:12px; padding:12px 14px; margin-bottom:16px; }
        .met-fechas { display:flex; align-items:center; gap:8px; }
        .met-fechas label { margin:0; font-size:12px; color:#64748b; font-weight:500; text-transform:none; letter-spacing:normal; }
        .met-fechas input { height:34px; border:1px solid #e2e8f0; border-radius:8px; padding:0 10px; font-size:13px; color:#334155; outline:none; }
        .met-fechas input:focus { border-color:#a5b4fc; box-shadow:0 0 0 3px rgba(99,102,241,.12); }
        .met-presets { display:inline-flex; padding:3px; background:#f1f5f9; border-radius:9px; }
        .met-presets button { height:28px; border:none; background:transparent; color:#64748b; border-radius:7px; padding:0 11px; font-size:12px; font-weight:500; }
        .met-presets button.active { background:#fff; color:#4f46e5; box-shadow:0 1px 3px rgba(15,23,42,.12); }
        .met-toolbar .met-sep { flex:1; }

        /* Selector de casinos (mismo estilo que el calendario) */
        .met-select { height:34px; display:inline-flex; align-items:center; gap:8px; padding:0 12px; border:1px solid #e2e8f0; background:#fff;
            border-radius:17px; color:#334155; font-size:13px; outline:none; max-width:300px; }
        .met-select:hover, .open > .met-select { border-color:#a5b4fc; box-shadow:0 0 0 3px rgba(99,102,241,.12); }
        .met-select > i:first-child { color:#6366f1; }
        .met-select-label { overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
        .met-badge { background:#6366f1; color:#fff; border-radius:9px; font-size:11px; font-weight:600; padding:1px 7px; }
        .met-menu { width:300px; padding:0; border:1px solid #e2e8f0; border-radius:10px; box-shadow:0 10px 30px rgba(15,23,42,.15); margin-top:6px; overflow:hidden; }
        .met-menu-head { padding:10px; border-bottom:1px solid #f1f5f9; background:#fafbfc; }
        .met-buscar { position:relative; }
        .met-buscar i { position:absolute; left:10px; top:9px; color:#94a3b8; font-size:12px; }
        .met-buscar input { width:100%; height:30px; border:1px solid #e2e8f0; border-radius:7px; padding:0 10px 0 28px; font-size:13px; outline:none; }
        .met-acciones { display:flex; gap:6px; margin-top:8px; }
        .met-acciones button { flex:1; height:26px; border:1px solid #e2e8f0; background:#fff; border-radius:6px; font-size:12px; color:#475569; }
        .met-acciones button:hover { background:#eef2ff; border-color:#c7d2fe; color:#4338ca; }
        .met-lista { max-height:300px; overflow-y:auto; padding:4px 0; }
        .met-item { display:flex !important; align-items:center; gap:9px; margin:0; padding:7px 14px; font-size:13px; font-weight:normal !important;
            text-transform:none !important; letter-spacing:normal !important; color:#334155; cursor:pointer; }
        .met-item:hover { background:#f8fafc; }
        .met-item input { display:none; }
        .met-check { width:16px; height:16px; flex:0 0 16px; border:1.5px solid #cbd5e1; border-radius:4px; display:flex; align-items:center;
            justify-content:center; font-size:10px; color:#fff; }
        .met-check i { opacity:0; }
        .met-item input:checked + .met-check { background:#6366f1; border-color:#6366f1; }
        .met-item input:checked + .met-check i { opacity:1; }
        .met-item-tipo { margin-left:auto; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:.04em; }

        /* KPIs */
        .met-kpis { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:14px; margin-bottom:16px; }
        .met-kpi { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; position:relative; overflow:hidden; }
        .met-kpi::before { content:''; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--c); }
        .met-kpi-label { font-size:12px; color:#64748b; font-weight:500; }
        .met-kpi-valor { font-size:28px; font-weight:700; line-height:1.2; margin-top:4px; color:#0f172a; }
        .met-kpi-valor small { font-size:13px; font-weight:500; color:#64748b; margin-left:3px; }
        .met-kpi-sub { font-size:11px; color:#94a3b8; margin-top:2px; }

        /* Tarjetas de gráficos */
        .met-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; }
        .met-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; min-width:0; }
        .met-card.ancho { grid-column:1 / -1; }
        .met-card h4 { margin:0 0 2px; font-size:15px; font-weight:600; color:#1e293b; }
        .met-card .met-ayuda { font-size:12px; color:#94a3b8; margin-bottom:8px; }
        .met-chart { height:300px; }

        /* Trabadas */
        .met-tabla { width:100%; font-size:13px; }
        .met-tabla th { font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:#64748b; font-weight:600; padding:8px 10px;
            border-bottom:1px solid #e2e8f0; background:#f8fafc; }
        .met-tabla td { padding:8px 10px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
        .met-tabla tr:hover td { background:#f8fafc; }
        .met-rama { display:inline-block; padding:1px 8px; border-radius:10px; font-size:11px; font-weight:600; }
        .met-rama.MKT { background:#eff6ff; color:#1d4ed8; }
        .met-rama.FISC { background:#ecfdf5; color:#047857; }
        .met-dias { font-weight:700; }
        .met-dias.alto { color:#dc2626; }
        .met-dias.medio { color:#d97706; }
        .met-abrir { color:#4f46e5; font-weight:500; white-space:nowrap; }
        .met-vacio { padding:24px; text-align:center; color:#94a3b8; }
        .met-trabadas-wrap { max-height:420px; overflow-y:auto; }
        .met-cargando { opacity:.55; transition:opacity .15s; pointer-events:none; }

        @media (max-width: 1100px) {
            .met-grid { grid-template-columns:1fr; }
            .met-kpis { grid-template-columns:repeat(2, minmax(0, 1fr)); }
        }
    </style>
@endsection

@section('contenidoVista')
    <div class="met">
        <div class="met-head">
            <h3><i class="fa fa-bar-chart"></i> Métricas de Notas</h3>
            <a href="/notas-unificadas" class="met-volver"><i class="fa fa-arrow-left"></i> Volver a Notas Unificadas</a>
        </div>

        <div class="met-toolbar">
            <div class="met-fechas">
                <label for="metDesde">Cargadas desde</label>
                <input type="date" id="metDesde">
                <label for="metHasta">hasta</label>
                <input type="date" id="metHasta">
            </div>
            <div class="met-presets" id="metPresets">
                <button type="button" data-meses="3">3 meses</button>
                <button type="button" data-meses="6" class="active">6 meses</button>
                <button type="button" data-meses="12">12 meses</button>
                <button type="button" data-meses="anio">Este año</button>
                <button type="button" data-meses="todas">Todas</button>
            </div>
            <div class="met-sep"></div>
            <div class="dropdown" id="metRecursoDrop">
                <button type="button" class="met-select dropdown-toggle" data-toggle="dropdown">
                    <i class="fa fa-building"></i>
                    <span class="met-select-label" id="metRecursoLabel">Todos los casinos</span>
                    <span class="met-badge" id="metRecursoBadge" style="display:none;"></span>
                    <i class="fa fa-angle-down" style="color:#94a3b8;"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-right met-menu" id="metRecursoMenu">
                    <div class="met-menu-head">
                        <div class="met-buscar">
                            <i class="fa fa-search"></i>
                            <input type="text" id="metRecursoBuscar" placeholder="Buscar casino o plataforma..." autocomplete="off">
                        </div>
                        <div class="met-acciones">
                            <button type="button" data-accion="todos"><i class="fa fa-check-square-o"></i> Todos</button>
                            <button type="button" data-accion="ninguno"><i class="fa fa-eraser"></i> Limpiar</button>
                        </div>
                    </div>
                    <div class="met-lista" id="metRecursoLista"></div>
                </div>
            </div>
        </div>

        <div id="metContenido">
            <div class="met-kpis">
                <div class="met-kpi" style="--c:#6366f1;">
                    <div class="met-kpi-label">Notas cargadas</div>
                    <div class="met-kpi-valor" id="kpiTotal">—</div>
                    <div class="met-kpi-sub">en el período</div>
                </div>
                <div class="met-kpi" style="--c:#10b981;">
                    <div class="met-kpi-label">Aprobadas</div>
                    <div class="met-kpi-valor" id="kpiAprobadas">—</div>
                    <div class="met-kpi-sub">llegaron a APROBADO - NOTA/DISPOSICIÓN</div>
                </div>
                <div class="met-kpi" style="--c:#3b82f6;">
                    <div class="met-kpi-label">Tiempo promedio hasta aprobar</div>
                    <div class="met-kpi-valor" id="kpiPromedio">—</div>
                    <div class="met-kpi-sub">desde la carga inicial</div>
                </div>
                <div class="met-kpi" style="--c:#ef4444;">
                    <div class="met-kpi-label">Notas trabadas</div>
                    <div class="met-kpi-valor" id="kpiTrabadas">—</div>
                    <div class="met-kpi-sub">más de {{ $diasTrabada }} días en el mismo estado</div>
                </div>
            </div>

            <div class="met-grid">
                <div class="met-card ancho">
                    <h4>Notas trabadas</h4>
                    <div class="met-ayuda">Notas que llevan más de {{ $diasTrabada }} días en su estado actual (sin contar los estados finales: aprobados, con informe, rechazado, visto por auditoría, tomado conocimiento, no autorizado, fuera de término, vencido).
                        No dependen del rango de fechas: incluye notas viejas olvidadas.</div>
                    <div class="met-trabadas-wrap" id="metTrabadas"></div>
                </div>
                <div class="met-card">
                    <h4>Tiempo en cada estado</h4>
                    <div class="met-ayuda">Días promedio y mediana que pasa una nota en cada estado (solo estados de los que ya salió).</div>
                    <div class="met-chart" id="chTiempoEstado"></div>
                </div>
                <div class="met-card">
                    <h4>Tiempo hasta aprobar, por mes</h4>
                    <div class="met-ayuda">Días promedio desde la carga hasta la aprobación, según el mes en que se aprobó.</div>
                    <div class="met-chart" id="chAprobacion"></div>
                </div>
                <div class="met-card">
                    <h4>Notas cargadas por mes</h4>
                    <div class="met-ayuda">Por rama: Marketing y Fiscalización.</div>
                    <div class="met-chart" id="chPorMes"></div>
                </div>
                <div class="met-card">
                    <h4>Estado actual</h4>
                    <div class="met-ayuda">Dónde están hoy las notas cargadas en el período.</div>
                    <div class="met-chart" id="chPorEstado"></div>
                </div>
                <div class="met-card">
                    <h4>Por casino / plataforma</h4>
                    <div class="met-ayuda">Cantidad de notas cargadas en el período.</div>
                    <div class="met-chart" id="chPorRecurso"></div>
                </div>
                <div class="met-card">
                    <h4>Por categoría / tipo de evento</h4>
                    <div class="met-ayuda">Categoría (MKT) o tipo de evento (FISC).</div>
                    <div class="met-chart" id="chPorCategoria"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="/js/highcharts_11_3_0/highcharts.js"></script>
    <script src="/js/highcharts_11_3_0/modules/no-data-to-display.js"></script>
    <script>
        window.MET_RECURSOS = {!! json_encode($recursos) !!};
    </script>
    <script src="/js/notas_metricas.js?v={{ filemtime(public_path('js/notas_metricas.js')) }}"></script>
@endsection
