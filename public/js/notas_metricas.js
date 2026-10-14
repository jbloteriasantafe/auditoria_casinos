/**
 * Tablero de métricas de Notas Unificadas (/notas-unificadas/metricas).
 * Datos: GET /notas-unificadas/metricas/datos?desde&hasta&recursos[]
 * Gráficos con Highcharts 11 (local).
 */
(function () {
    'use strict';

    var COLOR_MKT = '#3b82f6', COLOR_FISC = '#10b981';
    var PALETA = ['#6366f1', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16',
        '#f97316', '#92400e', '#14b8a6', '#a855f7', '#eab308', '#64748b', '#0ea5e9'];
    var COLOR_ESTADO = {
        'CARGA INICIAL': '#94a3b8',
        'CONTROL INICIADO': '#3b82f6',
        'VISTO CON OBSERVACIONES': '#f59e0b',
        'OBS/COMUNICADO': '#f97316',
        'OBS/CORREGIDA': '#eab308',
        'APROBADO POR FUNCIONARIO': '#14b8a6',
        'APROBADO - NOTA/DISPOSICION': '#10b981',
        'VENCIDO': '#64748b',
        'RECHAZADO': '#ef4444',
        'CON INFORME': '#f59e0b',
        'CON INFORME NEGATIVO': '#dc2626',
        'VISTO POR AUDITORÍA': '#0ea5e9',
        'TOMADO CONOCIMIENTO': '#8b5cf6',
        'NO AUTORIZADO': '#b91c1c',
        'FUERA DE TÉRMINO': '#78350f'
    };
    var MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    var recursos = window.MET_RECURSOS || [];
    var colorRecurso = {};
    recursos.forEach(function (r, i) { colorRecurso[r.id] = PALETA[i % PALETA.length]; });
    var charts = {};
    var req = null;

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
    function mesLabel(ym) { var p = ym.split('-'); return MESES[+p[1] - 1] + ' ' + p[0].slice(2); }
    function dias(v) { return v == null ? '—' : (Math.round(v * 10) / 10).toString().replace('.', ','); }

    Highcharts.setOptions({
        lang: {
            decimalPoint: ',', thousandsSep: '.', noData: 'Sin datos para los filtros elegidos',
            months: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
        },
        chart: { style: { fontFamily: 'inherit' }, backgroundColor: 'transparent', spacing: [8, 4, 4, 4] },
        title: { text: null },
        credits: { enabled: false },
        legend: { itemStyle: { fontWeight: '500', color: '#475569', fontSize: '12px' } },
        xAxis: { lineColor: '#e2e8f0', tickColor: '#e2e8f0', labels: { style: { color: '#64748b', fontSize: '11px' } } },
        yAxis: { gridLineColor: '#f1f5f9', title: { text: null }, labels: { style: { color: '#94a3b8', fontSize: '11px' } } },
        tooltip: { backgroundColor: '#0f172a', borderWidth: 0, borderRadius: 8, style: { color: '#fff', fontSize: '12px' }, shadow: false },
        plotOptions: { series: { animation: { duration: 400 } }, bar: { borderRadius: 4, borderWidth: 0 }, column: { borderRadius: 4, borderWidth: 0 } }
    });

    // ---------- filtros ----------
    function aplicarPreset(meses) {
        var hoy = new Date(), desde;
        if (meses === 'anio') desde = new Date(hoy.getFullYear(), 0, 1);
        else if (meses !== 'todas') desde = new Date(hoy.getFullYear(), hoy.getMonth() - (+meses), hoy.getDate());
        $('#metDesde').val(desde ? iso(desde) : '');  // "Todas": sin fecha desde
        $('#metHasta').val(iso(hoy));
        $('#metPresets button').removeClass('active').filter('[data-meses="' + meses + '"]').addClass('active');
    }

    function recursosSeleccionados() {
        return $('.metRecursoChk:checked').map(function () { return this.value; }).get();
    }

    function armarMenuRecursos() {
        var html = '';
        recursos.forEach(function (r) {
            html += '<label class="met-item" data-nombre="' + esc(r.nombre.toLowerCase()) + '">' +
                '<input type="checkbox" class="metRecursoChk" value="' + esc(r.id) + '">' +
                '<span class="met-check"><i class="fa fa-check"></i></span>' + esc(r.nombre) +
                '<span class="met-item-tipo">' + (r.plataforma ? 'Online' : 'Casino') + '</span></label>';
        });
        $('#metRecursoLista').html(html);
    }

    function actualizarLabelRecursos() {
        var sel = $('.metRecursoChk:checked');
        var n = sel.length;
        $('#metRecursoLabel').text(n === 0 ? 'Todos los casinos' : n === 1 ? sel.closest('label').text().replace(/(Online|Casino)$/, '').trim() : 'Casinos / plataformas');
        $('#metRecursoBadge').text(n).toggle(n > 1);
    }

    // ---------- datos ----------
    function cargar() {
        if (req) req.abort();
        $('#metContenido').addClass('met-cargando');
        req = $.getJSON('/notas-unificadas/metricas/datos', {
            desde: $('#metDesde').val(), hasta: $('#metHasta').val(), recursos: recursosSeleccionados()
        }).done(render).fail(function (xhr) {
            if (xhr.statusText === 'abort') return;
            if (typeof notificacion === 'function') notificacion('error', 'No se pudieron cargar las métricas');
        }).always(function () { $('#metContenido').removeClass('met-cargando'); });
    }

    function grafico(id, opts) {
        if (charts[id]) charts[id].destroy();
        charts[id] = Highcharts.chart(id, opts);
    }

    function render(d) {
        // KPIs
        $('#kpiTotal').text(d.kpis.total);
        $('#kpiAprobadas').html(d.kpis.aprobadas + (d.kpis.total ? '<small>' + Math.round(d.kpis.aprobadas * 100 / d.kpis.total) + '%</small>' : ''));
        $('#kpiPromedio').html(d.kpis.promedio_aprobacion == null ? '—' : dias(d.kpis.promedio_aprobacion) + '<small>días</small>');
        $('#kpiTrabadas').text(d.kpis.trabadas);

        // Tiempo en cada estado (barras horizontales: promedio + mediana)
        grafico('chTiempoEstado', {
            chart: { type: 'bar' },
            xAxis: { categories: d.tiempo_estado.map(function (t) { return t.estado; }) },
            yAxis: { labels: { format: '{value} d' } },
            tooltip: {
                shared: true, formatter: function () {
                    var t = d.tiempo_estado[this.points[0].point.index];
                    return '<b>' + esc(t.estado) + '</b><br>Promedio: ' + dias(t.promedio) + ' días<br>Mediana: ' + dias(t.mediana) +
                        ' días<br><span style="color:#94a3b8">' + t.n + ' pasos medidos</span>';
                }
            },
            series: [
                { name: 'Promedio', color: '#6366f1', data: d.tiempo_estado.map(function (t) { return t.promedio; }) },
                { name: 'Mediana', color: '#c7d2fe', data: d.tiempo_estado.map(function (t) { return t.mediana; }) }
            ]
        });

        // Tiempo hasta aprobar por mes
        grafico('chAprobacion', {
            chart: { type: 'spline' },
            xAxis: { categories: d.aprobacion_mes.map(function (m) { return mesLabel(m.mes); }) },
            yAxis: { min: 0, labels: { format: '{value} d' } },
            legend: { enabled: false },
            tooltip: {
                formatter: function () {
                    var m = d.aprobacion_mes[this.point.index];
                    return '<b>' + mesLabel(m.mes) + '</b><br>Promedio: ' + dias(m.promedio) + ' días<br><span style="color:#94a3b8">' + m.n + ' notas aprobadas</span>';
                }
            },
            series: [{
                name: 'Días hasta aprobar', color: '#10b981', lineWidth: 3,
                marker: { radius: 5, fillColor: '#fff', lineWidth: 2, lineColor: '#10b981' },
                data: d.aprobacion_mes.map(function (m) { return m.promedio; })
            }]
        });

        // Notas por mes (apiladas MKT/FISC)
        grafico('chPorMes', {
            chart: { type: 'column' },
            xAxis: { categories: d.por_mes.map(function (m) { return mesLabel(m.mes); }) },
            yAxis: { allowDecimals: false, stackLabels: { enabled: true, style: { color: '#475569', fontWeight: '600', textOutline: 'none' } } },
            plotOptions: { column: { stacking: 'normal' } },
            tooltip: { shared: true },
            series: [
                { name: 'Marketing', color: COLOR_MKT, data: d.por_mes.map(function (m) { return m.MKT; }) },
                { name: 'Fiscalización', color: COLOR_FISC, data: d.por_mes.map(function (m) { return m.FISC; }) }
            ]
        });

        // Estado actual (dona)
        grafico('chPorEstado', {
            chart: { type: 'pie' },
            tooltip: { pointFormat: '<b>{point.y}</b> notas ({point.percentage:.0f}%)' },
            plotOptions: {
                pie: {
                    innerSize: '58%', borderWidth: 2, borderColor: '#fff',
                    dataLabels: { enabled: true, format: '{point.name}: {point.y}', style: { fontSize: '11px', fontWeight: '500', color: '#475569', textOutline: 'none' }, distance: 14 }
                }
            },
            series: [{
                name: 'Notas',
                data: Object.keys(d.por_estado).map(function (k, i) {
                    return { name: k, y: d.por_estado[k], color: COLOR_ESTADO[k] || PALETA[i % PALETA.length] };
                })
            }]
        });

        // Por casino / plataforma
        var recNombres = Object.keys(d.por_recurso);
        grafico('chPorRecurso', {
            chart: { type: 'bar' },
            xAxis: { categories: recNombres },
            yAxis: { allowDecimals: false },
            legend: { enabled: false },
            tooltip: { pointFormat: '<b>{point.y}</b> notas' },
            plotOptions: { bar: { dataLabels: { enabled: true, style: { fontWeight: '600', color: '#475569', textOutline: 'none' } } } },
            series: [{
                name: 'Notas', colorByPoint: true,
                colors: recNombres.map(function (nom) {
                    var r = recursos.filter(function (x) { return x.nombre === nom; })[0];
                    return r ? colorRecurso[r.id] : '#6366f1';
                }),
                data: recNombres.map(function (k) { return d.por_recurso[k]; })
            }]
        });

        // Por categoría / tipo de evento
        var cats = Object.keys(d.por_categoria);
        grafico('chPorCategoria', {
            chart: { type: 'bar' },
            xAxis: { categories: cats },
            yAxis: { allowDecimals: false },
            legend: { enabled: false },
            tooltip: { pointFormat: '<b>{point.y}</b> notas' },
            plotOptions: { bar: { dataLabels: { enabled: true, style: { fontWeight: '600', color: '#475569', textOutline: 'none' } } } },
            series: [{
                name: 'Notas',
                data: cats.map(function (k) { return { y: d.por_categoria[k], color: k.indexOf('FISC') === 0 ? COLOR_FISC : COLOR_MKT }; })
            }]
        });

        renderTrabadas(d.trabadas, d.dias_trabada);
    }

    function renderTrabadas(lista, umbral) {
        if (!lista.length) {
            $('#metTrabadas').html('<div class="met-vacio"><i class="fa fa-check-circle" style="color:#10b981;"></i> No hay notas trabadas</div>');
            return;
        }
        var html = '<table class="met-tabla"><thead><tr><th>Nota</th><th>Título</th><th>Casino / Plataforma</th><th>Rama</th>' +
            '<th>Estado</th><th>Desde</th><th style="text-align:right;">Días</th><th></th></tr></thead><tbody>';
        lista.forEach(function (t) {
            var nivel = t.dias > umbral * 4 ? 'alto' : t.dias > umbral * 2 ? 'medio' : '';
            html += '<tr><td><b>' + esc(t.nro) + '</b></td><td>' + esc(t.titulo) + '</td><td>' + esc(t.recurso) + '</td>' +
                '<td><span class="met-rama ' + esc(t.rama) + '">' + esc(t.rama) + '</span></td><td>' + esc(t.estado) + '</td>' +
                '<td>' + esc(t.desde) + '</td><td style="text-align:right;"><span class="met-dias ' + nivel + '">' + t.dias + '</span></td>' +
                '<td style="text-align:right;"><a class="met-abrir" href="/notas-unificadas?abrir_nota=' + t.id_nota + '&rama=' + encodeURIComponent(t.rama) + '">' +
                'Abrir <i class="fa fa-external-link"></i></a></td></tr>';
        });
        $('#metTrabadas').html(html + '</tbody></table>');
    }

    $(function () {
        armarMenuRecursos();
        aplicarPreset(6);

        $('#metPresets').on('click', 'button', function () {
            aplicarPreset($(this).data('meses'));
            cargar();
        });
        $('#metDesde, #metHasta').on('change', function () {
            $('#metPresets button').removeClass('active');
            cargar();
        });

        $('#metRecursoMenu').on('click', function (e) { e.stopPropagation(); });
        $(document).on('change', '.metRecursoChk', function () { actualizarLabelRecursos(); cargar(); });
        $('#metRecursoMenu').on('click', 'button[data-accion]', function () {
            var marcar = $(this).data('accion') === 'todos';
            $('#metRecursoLista .met-item').each(function () {
                if (!marcar || $(this).is(':visible')) $(this).find('input').prop('checked', marcar);
            });
            actualizarLabelRecursos();
            cargar();
        });
        $('#metRecursoBuscar').on('input keyup', function () {
            var q = $.trim(this.value).toLowerCase();
            $('#metRecursoLista .met-item').each(function () {
                $(this).toggle(!q || $(this).data('nombre').indexOf(q) !== -1);
            });
        });
        $('#metRecursoDrop').on('shown.bs.dropdown', function () { $('#metRecursoBuscar').trigger('focus'); });

        cargar();
    });
})();
