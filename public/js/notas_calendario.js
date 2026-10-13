/**
 * Calendario de vigencia de eventos (Notas Unificadas).
 * Modal con dos vistas sobre el mismo endpoint (/notas-unificadas/calendario-vigencias):
 *  - Línea de tiempo: una fila por casino/plataforma, eventos como barras (HTML/CSS propio).
 *  - Calendario mensual: FullCalendar v3 (se carga diferido la primera vez).
 * Click en un evento -> abre el detalle de la nota (handler .btn-ver-detalle-nota).
 * La línea de tiempo es continua: se navega arrastrando (mouse/touch) o con la rueda horizontal.
 */
(function () {
    'use strict';

    var COLOR_RAMA = { MKT: '#3b82f6', FISC: '#10b981' };
    var PALETA = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16',
        '#f97316', '#92400e', '#14b8a6', '#a855f7', '#eab308', '#64748b', '#0ea5e9', '#d946ef'];
    var MESES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre',
        'Octubre', 'Noviembre', 'Diciembre'];
    var DIAS = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];

    var recursos = window.CAL_EV_RECURSOS || [];
    var colorRecurso = {};
    recursos.forEach(function (r, i) { colorRecurso[r.id] = PALETA[i % PALETA.length]; });

    var hoy = new Date();
    var state = { anio: hoy.getFullYear(), mes: hoy.getMonth(), recursos: [] };
    var cache = {};
    var fcCargado = false, fcCargando = null, fcIniciado = false;

    // ---------- utilidades de fecha (strings YYYY-MM-DD, sin zona horaria) ----------
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function ymd(a, m, d) { return a + '-' + pad(m + 1) + '-' + pad(d); }
    function diasEnMes(a, m) { return new Date(a, m + 1, 0).getDate(); }
    function fmt(s) { var p = s.split('-'); return p[2] + '/' + p[1] + '/' + p[0]; }
    function aUTC(s) { var p = s.split('-'); return Date.UTC(+p[0], +p[1] - 1, +p[2]); }
    function deUTC(t) { var d = new Date(t); return ymd(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate()); }
    function addDias(s, n) { return deUTC(aUTC(s) + n * 86400000); }
    function difDias(a, b) { return Math.round((aUTC(b) - aUTC(a)) / 86400000); }
    function hoyStr() { var h = new Date(); return ymd(h.getFullYear(), h.getMonth(), h.getDate()); }
    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

    // ---------- datos ----------
    function ramasSeleccionadas() {
        return $('.calEvRama:checked').map(function () { return this.value; }).get();
    }

    function fetchEventos(desde, hasta) {
        var params = { desde: desde, hasta: hasta, rama: ramasSeleccionadas(), id_casino: [], id_plataforma: [] };
        state.recursos.forEach(function (id) {
            if (id.indexOf('p_') === 0) params.id_plataforma.push(id.substring(2));
            else params.id_casino.push(id);
        });
        var key = JSON.stringify(params);
        if (cache[key]) return $.Deferred().resolve(cache[key]).promise();
        return $.getJSON('/notas-unificadas/calendario-vigencias', params).then(function (res) {
            cache[key] = (res && res.eventos) || [];
            return cache[key];
        });
    }

    function tooltip(e) {
        return (e.nro_nota ? 'Nota ' + e.nro_nota + (e.anio ? '/' + e.anio : '') + ' — ' : '') + (e.titulo || '') +
            '\n' + e.recurso_nombre + ' · ' + e.tipo_rama + (e.categoria ? ' · ' + e.categoria : '') +
            '\n' + fmt(e.inicio) + (e.sin_fin ? ' (sin fecha de fin)' : ' → ' + fmt(e.fin)) +
            (e.estado ? '\nEstado: ' + e.estado : '');
    }

    function abrirDetalle(idNota, rama) {
        // abrirModalDetalleNota vive dentro del IIFE de unified_wizard.js: se dispara vía su handler delegado
        var $a = $('<a class="btn-ver-detalle-nota" style="display:none"></a>')
            .attr('data-nota-id', idNota).attr('data-tipo-rama', rama).appendTo('body');
        $a.trigger('click');
        $a.remove();
    }

    // ---------- línea de tiempo continua (se arrastra para moverse en el tiempo) ----------
    // Se dibuja la ventana visible + BUFFER días a cada lado; al arrastrar se desplaza la
    // pista con transform y al soltar se "confirma" el corrimiento y se redibuja centrado.
    var DW = 36;                 // ancho de un día (px)
    var BUFFER = 60;             // días extra dibujados a cada lado
    var LANE_H = 22, LANE_GAP = 3, ROW_PAD = 5;
    var MES_CORTO = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    var tl = { inicio: null, dx: 0, vis: 30, renderId: 0, drag: null, wheelTimer: null };

    function rangoMeses(desde, hasta) {
        var pd = desde.split('-'), ph = hasta.split('-');
        var a = +pd[0], m = +pd[1] - 1, out = [];
        while (a < +ph[0] || (a === +ph[0] && m <= +ph[1] - 1)) {
            out.push([ymd(a, m, 1), ymd(a, m, diasEnMes(a, m))]);
            if (++m > 11) { m = 0; a++; }
        }
        return out;
    }

    // Trae por mes (cacheado) y une sin duplicados
    function fetchRango(desde, hasta) {
        var reqs = rangoMeses(desde, hasta).map(function (r) { return fetchEventos(r[0], r[1]); });
        return $.when.apply($, reqs).then(function () {
            var vistos = {}, out = [];
            Array.prototype.slice.call(arguments).forEach(function (lista) {
                (lista || []).forEach(function (e) {
                    if (!vistos[e.id_nota]) { vistos[e.id_nota] = 1; out.push(e); }
                });
            });
            return out;
        });
    }

    function fmtCorto(s) { var p = s.split('-'); return +p[2] + ' ' + MES_CORTO[+p[1] - 1] + ' ' + p[0]; }

    function tituloTimeline() {
        var ini = addDias(tl.inicio, Math.round(-tl.dx / DW));
        $('#calEvTitulo').text(fmtCorto(ini) + ' – ' + fmtCorto(addDias(ini, tl.vis - 1)));
    }

    function aplicarDesplazamiento() {
        $('#calEvTimeline .cal-tl2-track').css('transform', 'translateX(' + (-BUFFER * DW + tl.dx) + 'px)');
        tituloTimeline();
    }

    // Confirma el corrimiento (redondeado a días) y redibuja
    function confirmarDesplazamiento() {
        var dias = Math.round(-tl.dx / DW);
        if (!dias) {
            tl.dx = 0;
            $('#calEvTimeline .cal-tl2-track').addClass('snap');
            aplicarDesplazamiento();
            return;
        }
        // El resto (fracción de día) se anima hasta 0 al redibujar
        tl.dx += dias * DW;
        tl.inicio = addDias(tl.inicio, dias);
        renderTimeline();
    }

    function renderTimeline() {
        var $c = $('#calEvTimeline');
        if (!$c.find('.cal-tl2').length) {
            $c.html('<div class="cal-tl2"><div class="cal-tl2-left"></div>' +
                '<div class="cal-tl2-view"><div class="cal-tl2-track"></div></div></div>' +
                '<div class="cal-tl2-ayuda"><i class="fa fa-arrows-h"></i> Arrastrá para moverte en el tiempo</div>');
        }
        if (!tl.inicio) tl.inicio = addDias(hoyStr(), -7);
        tl.vis = Math.max(7, Math.ceil(($c.find('.cal-tl2-view').width() || 1200) / DW));
        tituloTimeline();

        var bIni = addDias(tl.inicio, -BUFFER), nDias = tl.vis + 2 * BUFFER, bFin = addDias(bIni, nDias - 1);
        var id = ++tl.renderId;
        $c.find('.cal-tl2').addClass('cargando');
        fetchRango(bIni, bFin).then(function (eventos) {
            if (id !== tl.renderId) return;
            dibujarTimeline(eventos, bIni, nDias, bFin);
            $c.find('.cal-tl2').removeClass('cargando');
        }, function () {
            if (id !== tl.renderId) return;
            $c.find('.cal-tl2').removeClass('cargando');
            notificacionCal('Error al cargar los eventos.');
        });
    }

    function notificacionCal(msg) {
        if (typeof notificacion === 'function') notificacion('error', msg);
    }

    function dibujarTimeline(eventos, bIni, nDias, bFin) {
        var $c = $('#calEvTimeline'), hStr = hoyStr();
        var visIni = tl.inicio;
        var porRecurso = {}, conVisibles = {};
        // Filas: solo casinos/plataformas con eventos en lo visible. Se recalculan al soltar
        // el arrastre; mientras se arrastra se ve todo lo precargado de esas filas.
        var margenIni = visIni, margenFin = addDias(visIni, tl.vis - 1);
        eventos.forEach(function (e) {
            (porRecurso[e.recurso] = porRecurso[e.recurso] || []).push(e);
            if (e.inicio <= margenFin && e.fin >= margenIni) conVisibles[e.recurso] = true;
        });
        var filas = recursos.filter(function (r) {
            if (state.recursos.length && state.recursos.indexOf(r.id) === -1) return false;
            return !!conVisibles[r.id];
        });
        Object.keys(conVisibles).forEach(function (id) {
            if (!recursos.some(function (r) { return r.id === id; })) {
                filas.push({ id: id, nombre: porRecurso[id][0].recurso_nombre });
            }
        });

        // Encabezado: banda de meses + días
        var left = '<div class="cal-tl2-head cal-tl2-corner">Casino / Plataforma</div>';
        var track = '<div class="cal-tl2-head"><div class="cal-tl2-meses">';
        var i, d, p, dow;
        // El mes en curso se rotula al borde izquierdo visible (si su día 1 quedó fuera de pantalla)
        var iVis = difDias(bIni, visIni);
        for (i = 0; i < nDias; i++) {
            d = addDias(bIni, i);
            p = d.split('-');
            if (i === 0 || p[2] === '01' || i === iVis) {
                var finMes = ymd(+p[0], +p[1] - 1, diasEnMes(+p[0], +p[1] - 1));
                var tapado = i < iVis && finMes >= visIni;   // su rótulo va en iVis
                var conInicio = i === iVis && p[2] !== '01'; // rótulo "arrastrado" del mes en curso
                if (!tapado && (i !== iVis || conInicio || p[2] === '01')) {
                    track += '<div class="cal-tl2-mes' + (conInicio ? ' arrastrado' : '') + '" style="left:' + (i * DW) + 'px">' +
                        MESES[+p[1] - 1] + ' ' + p[0] + '</div>';
                }
            }
        }
        track += '</div><div class="cal-tl2-dias">';
        var cols = '';
        for (i = 0; i < nDias; i++) {
            d = addDias(bIni, i);
            p = d.split('-');
            dow = new Date(+p[0], +p[1] - 1, +p[2]).getDay();
            var cls = (dow === 0 || dow === 6 ? ' finde' : '') + (d === hStr ? ' hoy' : '') + (p[2] === '01' ? ' inicio-mes' : '');
            track += '<div class="cal-tl2-dia' + cls + '" style="left:' + (i * DW) + 'px"><small>' + DIAS[dow] + '</small>' + (+p[2]) + '</div>';
            if (cls) cols += '<div class="cal-tl2-col' + cls + '" style="left:' + (i * DW) + 'px"></div>';
        }
        track += '</div></div><div class="cal-tl2-cols">' + cols + '</div>';

        filas.forEach(function (r) {
            var evs = (porRecurso[r.id] || []).slice().sort(function (x, y) {
                return x.inicio < y.inicio ? -1 : x.inicio > y.inicio ? 1 : (x.fin < y.fin ? 1 : -1);
            });
            // Carriles: eventos superpuestos van a sub-filas
            var finCarril = [];
            evs.forEach(function (e) {
                var l = 0;
                while (l < finCarril.length && finCarril[l] >= e.inicio) l++;
                finCarril[l] = e.fin;
                e._lane = l;
            });
            var alto = Math.max(1, finCarril.length) * (LANE_H + LANE_GAP) - LANE_GAP + 2 * ROW_PAD;

            left += '<div class="cal-tl2-recurso" style="height:' + alto + 'px" title="' + esc(r.nombre) + '">' +
                '<span class="cal-tl-dot" style="background:' + (colorRecurso[r.id] || '#999') + '"></span>' +
                '<span class="cal-tl2-nombre">' + esc(r.nombre) + '</span></div>';

            track += '<div class="cal-tl2-fila" style="height:' + alto + 'px">';
            evs.forEach(function (e) {
                var ini = e.inicio < bIni ? bIni : e.inicio;
                var fin = e.fin > bFin ? bFin : e.fin;
                var x = difDias(bIni, ini) * DW, w = (difDias(ini, fin) + 1) * DW - 2;
                // Si la barra arranca antes de lo visible, correr el texto para que se lea
                var pad = Math.max(0, Math.min(difDias(ini, visIni) * DW, w - 60));
                var label = (e.nro_nota ? e.nro_nota + ' · ' : '') + (e.titulo || '');
                track += '<div class="cal-tl2-ev' + (e.inicio < bIni ? ' corte-ini' : '') + (e.fin > bFin ? ' corte-fin' : '') + '"' +
                    ' style="left:' + (x + 1) + 'px; width:' + w + 'px; top:' + (ROW_PAD + e._lane * (LANE_H + LANE_GAP)) + 'px;' +
                    ' background:' + (COLOR_RAMA[e.tipo_rama] || '#64748b') + '; padding-left:' + (pad + 6) + 'px;"' +
                    ' data-id="' + e.id_nota + '" data-rama="' + esc(e.tipo_rama) + '" title="' + esc(tooltip(e)) + '">' + esc(label) + '</div>';
            });
            track += '</div>';
        });

        if (!filas.length) {
            track += '<div class="cal-tl2-vacio" style="margin-left:' + (BUFFER * DW) + 'px; width:' + (tl.vis * DW) + 'px">No hay eventos en este período para los filtros elegidos.</div>';
            left += '<div class="cal-tl2-recurso" style="height:60px"></div>';
        }

        $c.find('.cal-tl2-left').html(left);
        var $track = $c.find('.cal-tl2-track').removeClass('snap').css('width', nDias * DW + 'px').html(track);
        aplicarDesplazamiento();
        if (!tl.drag && tl.dx) {
            // Animar desde el corrimiento pendiente hasta su lugar
            void $track[0].offsetWidth;
            $track.addClass('snap');
            tl.dx = 0;
            aplicarDesplazamiento();
        }
    }

    function initArrastre() {
        var view = $('#calEvTimeline .cal-tl2-view')[0];
        if (!view || view._arrastreOk) return;
        view._arrastreOk = true;

        view.addEventListener('pointerdown', function (e) {
            if (e.button !== 0) return;
            tl.drag = { x0: e.clientX, dx0: tl.dx, movido: false, target: e.target };
            $('#calEvTimeline .cal-tl2-track').removeClass('snap');
            view.setPointerCapture(e.pointerId);
        });
        view.addEventListener('pointermove', function (e) {
            if (!tl.drag) return;
            var dx = e.clientX - tl.drag.x0;
            if (!tl.drag.movido && Math.abs(dx) < 5) return;
            if (!tl.drag.movido) { tl.drag.movido = true; $(view).addClass('arrastrando'); }
            tl.dx = tl.drag.dx0 + dx;
            aplicarDesplazamiento();
            // Si se acerca al borde de lo dibujado, confirmar y seguir arrastrando desde ahí
            if (Math.abs(tl.dx) > (BUFFER - 10) * DW) {
                var dias = Math.round(-tl.dx / DW);
                tl.inicio = addDias(tl.inicio, dias);
                tl.drag.x0 = e.clientX;
                tl.drag.dx0 = tl.dx + dias * DW;
                tl.dx = tl.drag.dx0;
                renderTimeline();
            }
        });
        var soltar = function () {
            if (!tl.drag) return;
            var d = tl.drag;
            tl.drag = null;
            $(view).removeClass('arrastrando');
            if (d.movido) {
                confirmarDesplazamiento();
            } else {
                var $ev = $(d.target).closest('.cal-tl2-ev');
                if ($ev.length) abrirDetalle($ev.data('id'), $ev.data('rama'));
            }
        };
        view.addEventListener('pointerup', soltar);
        view.addEventListener('pointercancel', soltar);

        // Trackpad / rueda horizontal (shift + rueda también)
        view.addEventListener('wheel', function (e) {
            var delta = Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : (e.shiftKey ? e.deltaY : 0);
            if (!delta) return;
            e.preventDefault();
            tl.dx -= delta;
            aplicarDesplazamiento();
            clearTimeout(tl.wheelTimer);
            tl.wheelTimer = setTimeout(confirmarDesplazamiento, 180);
        }, { passive: false });
    }

    function moverTimeline(dias) {
        tl.inicio = addDias(tl.inicio || hoyStr(), dias);
        tl.dx = dias * DW;  // arranca corrido y anima hasta su lugar
        renderTimeline();
    }

    // ---------- calendario mensual (FullCalendar v3, carga diferida) ----------
    function cargarFullCalendar() {
        if (fcCargado) return $.Deferred().resolve().promise();
        if (fcCargando) return fcCargando;
        if (!$('link[href^="/css/fullcalendar.min.css"]').length) {
            $('head').append('<link rel="stylesheet" href="/css/fullcalendar.min.css">');
        }
        var getScript = function (url) { return $.ajax({ url: url, dataType: 'script', cache: true }); };
        var pMoment = window.moment ? $.Deferred().resolve().promise() : getScript('/js/moment.min.js');
        fcCargando = pMoment.then(function () {
            return $.fn.fullCalendar ? null : getScript('/js/fullcalendar.min.js');
        }).then(function () {
            return getScript('/js/locale-all.js');
        }).then(function () { fcCargado = true; });
        return fcCargando;
    }

    function renderLeyenda(eventos) {
        var vistos = {};
        eventos.forEach(function (e) { vistos[e.recurso] = e.recurso_nombre; });
        var html = Object.keys(vistos).sort(function (x, y) { return vistos[x].localeCompare(vistos[y]); }).map(function (id) {
            return '<span class="cal-ev-ley-item"><span class="cal-tl-dot" style="background:' + (colorRecurso[id] || '#999') + '"></span>' + esc(vistos[id]) + '</span>';
        }).join('');
        $('#calEvLeyenda').html(html);
    }

    function renderMes() {
        cargarFullCalendar().then(function () {
            var $cal = $('#calEvMes');
            var fecha = ymd(state.anio, state.mes, 1);
            if (!fcIniciado) {
                $cal.fullCalendar({
                    locale: 'es',
                    header: { left: '', center: '', right: 'month,listMonth' },
                    defaultDate: fecha,
                    aspectRatio: 2.4,
                    eventLimit: 4,
                    editable: false,
                    events: function (start, end, tz, cb) {
                        // end es exclusivo en FullCalendar
                        fetchEventos(start.format('YYYY-MM-DD'), end.clone().subtract(1, 'days').format('YYYY-MM-DD')).then(function (eventos) {
                            renderLeyenda(eventos);
                            cb(eventos.map(function (e) {
                                return {
                                    title: '[' + e.recurso_nombre + '] ' + (e.titulo || ''),
                                    start: e.inicio,
                                    end: addDias(e.fin, 1),
                                    allDay: true,
                                    color: colorRecurso[e.recurso] || COLOR_RAMA[e.tipo_rama],
                                    id_nota: e.id_nota,
                                    tipo_rama: e.tipo_rama,
                                    tooltip: tooltip(e),
                                    rango: e.inicio === e.fin ? fmt(e.inicio).slice(0, 5)
                                        : fmt(e.inicio).slice(0, 5) + ' → ' + fmt(e.fin).slice(0, 5)
                                };
                            }));
                        }, function () { cb([]); });
                    },
                    eventRender: function (ev, el, view) {
                        el.attr('title', ev.tooltip);
                        // Agenda: todos son de día completo, en vez de "todo el día" mostrar el rango
                        if (view.name === 'listMonth') el.find('.fc-list-item-time').text(ev.rango);
                    },
                    eventClick: function (ev) { abrirDetalle(ev.id_nota, ev.tipo_rama); return false; }
                });
                // Leyenda dentro de la barra de FullCalendar, a la izquierda de Mes/Agenda
                $cal.find('.fc-toolbar .fc-left').append($('#calEvLeyenda'));
                fcIniciado = true;
            } else {
                $cal.fullCalendar('gotoDate', fecha);
                $cal.fullCalendar('refetchEvents');
            }
        }, function () {
            $('#calEvMes').html('<p class="text-danger text-center">No se pudo cargar el calendario.</p>');
        });
    }

    // ---------- orquestación ----------
    function vistaActiva() { return $('#calEvTabMes').hasClass('active') ? 'mes' : 'timeline'; }

    function render() {
        if (vistaActiva() === 'mes') {
            $('#calEvTitulo').text(MESES[state.mes] + ' ' + state.anio);
            renderMes();
        } else {
            renderTimeline();
            initArrastre();
        }
    }

    // ◀ ▶: en la línea de tiempo corren 2 semanas; en el calendario, un mes.
    // Las dos vistas quedan sincronizadas (el mes sigue al centro de la línea de tiempo).
    function mover(delta) {
        if (vistaActiva() === 'mes') {
            var d = new Date(state.anio, state.mes + delta, 1);
            state.anio = d.getFullYear();
            state.mes = d.getMonth();
            tl.inicio = addDias(ymd(state.anio, state.mes, 1), -3);
            render();
        } else {
            moverTimeline(delta * 14);
        }
    }

    function sincronizarMesDesdeTimeline() {
        if (!tl.inicio) return;
        var p = addDias(tl.inicio, Math.floor(tl.vis / 2)).split('-');
        state.anio = +p[0];
        state.mes = +p[1] - 1;
    }

    function armarMenuRecursos() {
        var html = '';
        recursos.forEach(function (r) {
            html += '<label class="cal-ev-rec-item" data-nombre="' + esc(r.nombre.toLowerCase()) + '">' +
                '<input type="checkbox" class="calEvRecursoChk" value="' + esc(r.id) + '">' +
                '<span class="cal-ev-rec-check"><i class="fa fa-check"></i></span>' +
                '<span class="cal-tl-dot" style="background:' + colorRecurso[r.id] + '"></span>' +
                '<span class="cal-tl2-nombre">' + esc(r.nombre) + '</span>' +
                '<span class="cal-ev-rec-tipo">' + (r.plataforma ? 'Online' : 'Casino') + '</span></label>';
        });
        html += '<div class="cal-ev-rec-vacio" style="display:none;">Sin coincidencias</div>';
        $('#calEvRecursoLista').html(html);
    }

    function filtrarListaRecursos() {
        var q = $.trim($('#calEvRecursoBuscar').val()).toLowerCase();
        var visibles = 0;
        $('#calEvRecursoLista .cal-ev-rec-item').each(function () {
            var ok = !q || $(this).data('nombre').indexOf(q) !== -1;
            $(this).toggle(ok);
            if (ok) visibles++;
        });
        $('#calEvRecursoLista .cal-ev-rec-vacio').toggle(visibles === 0);
    }

    function leerRecursos() {
        state.recursos = $('.calEvRecursoChk:checked').map(function () { return this.value; }).get();
        var n = state.recursos.length;
        $('#calEvRecursoLabel').text(n === 0 ? 'Todos los casinos' : n === 1
            ? $('.calEvRecursoChk:checked').closest('label').find('.cal-tl2-nombre').text() : 'Casinos / plataformas');
        $('#calEvRecursoBadge').text(n).toggle(n > 1);
        render();
    }

    $(function () {
        if (!$('#modalCalendarioEventos').length) return;
        armarMenuRecursos();

        $('#btnCalendarioEventos').on('click', function () {
            cache = {};
            $('#modalCalendarioEventos').modal('show');
        });
        $('#modalCalendarioEventos').on('shown.bs.modal', render);

        $('#calEvPrev').on('click', function () { mover(-1); });
        $('#calEvNext').on('click', function () { mover(1); });
        $('#calEvHoy').on('click', function () {
            state.anio = hoy.getFullYear();
            state.mes = hoy.getMonth();
            tl.inicio = addDias(hoyStr(), -7);
            tl.dx = 0;
            render();
        });
        $('a[href="#calEvTabMes"]').on('show.bs.tab', sincronizarMesDesdeTimeline);
        $('a[href="#calEvTabMes"], a[href="#calEvTabTimeline"]').on('shown.bs.tab', render);
        $(document).on('change', '.calEvRama', render);
        $(window).on('resize', function () {
            if ($('#modalCalendarioEventos').hasClass('in') && vistaActiva() === 'timeline') renderTimeline();
        });

        // Dropdown de recursos: no cerrar al tildar
        $('#calEvRecursoMenu').on('click', function (e) { e.stopPropagation(); });
        $(document).on('change', '.calEvRecursoChk', leerRecursos);
        $('#calEvRecursoMenu').on('click', 'button[data-accion]', function () {
            // "Todos" tilda solo los visibles según la búsqueda
            var marcar = $(this).data('accion') === 'todos';
            $('#calEvRecursoLista .cal-ev-rec-item').each(function () {
                if (!marcar || $(this).is(':visible')) $(this).find('input').prop('checked', marcar);
            });
            leerRecursos();
        });
        $('#calEvRecursoBuscar').on('input keyup', filtrarListaRecursos);
        $('#calEvRecursoDrop').on('shown.bs.dropdown', function () { $('#calEvRecursoBuscar').trigger('focus'); });

        // Modal de detalle encima del calendario: subir su z-index y el del backdrop
        $(document).on('show.bs.modal', '.modal', function () {
            if (this.id === 'modalCalendarioEventos' || !$('#modalCalendarioEventos').hasClass('in')) return;
            var $m = $(this).css('z-index', 1060);
            setTimeout(function () { $('.modal-backdrop').last().css('z-index', 1055); }, 0);
            $m.one('hidden.bs.modal', function () {
                $m.css('z-index', '');
                // Bootstrap 3 quita modal-open al cerrar el segundo modal: restaurar scroll del calendario
                if ($('#modalCalendarioEventos').hasClass('in')) $('body').addClass('modal-open');
            });
        });
    });
})();
