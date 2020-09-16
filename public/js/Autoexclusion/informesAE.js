$(document).ready(function(){

  const iso_dtp = {
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd/mm/yy',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true
  };

  $('#dtpFechaAutoexclusionD').datetimepicker(iso_dtp);
  $('#dtpFechaAutoexclusionH').datetimepicker(iso_dtp);
  $('#dtpFechaVencimientoD').datetimepicker(iso_dtp);
  $('#dtpFechaVencimientoH').datetimepicker(iso_dtp);
  $('#dtpFechaRevocacionD').datetimepicker(iso_dtp);
  $('#dtpFechaRevocacionH').datetimepicker(iso_dtp);
  $('#dtpFechaCierreD').datetimepicker(iso_dtp);
  $('#dtpFechaCierreH').datetimepicker(iso_dtp);

  $('#barraMenu').attr('aria-expanded','true');
  $('.tituloSeccionPantalla').text('Informes de Autoexcluidos');

  $('#btn-buscar').trigger('click');
});

//PAGINACION
$('#btn-buscar').click(function(e, pagina, page_size, columna, orden,async=true) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    e.preventDefault();

    //Fix error cuando librería saca los selectores
    if (isNaN($('#herramientasPaginacion').getPageSize())) {
        var size = 10; // por defecto
    } else {
        var size = $('#herramientasPaginacion').getPageSize();
    }

    var page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
    var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
    var sort_by = (columna != null) ? { columna, orden } : { columna: $('#tablaInformesAE .activa').attr('value'), orden: $('#tablaInformesAE .activa').attr('estado') };
    if (sort_by == null) { // limpio las columnas
        $('#tablaInformesAE th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado', '');
    }

    const iso = function(dtp){
      //getDate me retorna hoy si esta vacio, lo tengo que verificar
      if(dtp.find('input').val().length == 0) return "";
      const date = dtp.data("datetimepicker").getDate();
      const y = date.getFullYear();
      const m = date.getMonth()+1;
      const d = date.getDate();
      return y + (m<10?'-0':'-') + m + (d<10?'-0':'-') + d;
    }

    const rango_val_nc = function(s1,s2){
        const obj1 = $(s1);
        const obj2 = $(s2);
        const nc = $(s1).parent().find('.no_contesta');
        if(nc.prop('checked')) return -1;
        const val1 = parseFloat(obj1.val());
        const val2 = parseFloat(obj2.val());
        return [isNaN(val1)? '' : val1,isNaN(val2)? '' : val2];
    }

    var formData = {
        casino:    $('#buscadorCasino').val(),
        estado:    $('#buscadorEstado').val(),
        apellido:  $('#buscadorApellido').val(),
        dia_semanal: $('#buscadorDia').val(),
        edad_desde: $('#buscadorRangoEtarioD').val(),
        edad_hasta: $('#buscadorRangoEtarioH').val(),
        dni:       $('#buscadorDni').val(),
        sexo:      $('#buscadorSexo').val(),
        localidad: $('#buscadorLocalidad').val(),
        provincia: $('#buscadorProvincia').val(),
        fecha_autoexclusion_desde: iso($('#dtpFechaAutoexclusionD')),
        fecha_autoexclusion_hasta: iso($('#dtpFechaAutoexclusionH')),
        fecha_vencimiento_desde:   iso($('#dtpFechaVencimientoD')),
        fecha_vencimiento_hasta:   iso($('#dtpFechaVencimientoH')),
        fecha_revocacion_desde:    iso($('#dtpFechaRevocacionD')),
        fecha_revocacion_hasta:    iso($('#dtpFechaRevocacionH')),
        fecha_cierre_desde:        iso($('#dtpFechaCierreD')),
        fecha_cierre_hasta:        iso($('#dtpFechaCierreH')),
        hace_encuesta: $('#buscadorEncuesta').val(),
        frecuencia: $('#buscadorFrecuencia').val(),
        veces: rango_val_nc('#buscadorVecesD','#buscadorVecesH'),
        horas: rango_val_nc('#buscadorHorasD','#buscadorHorasH'),
        compania: $('#buscadorCompania').val(),
        juego: $('#buscadorJuego').val(),
        juego_responsable: $('#buscadorJuegoResponsable').val(),
        club: $('#buscadorClub').val(),
        autocontrol: $('#buscadorAutocontrol').val(),
        recibir_info: $('#buscadorRecibirInfo').val(),
        medio: $('#buscadorMedio').val(),
        page: page_number,
        sort_by: sort_by,
        page_size: page_size,
    }

    $.ajax({
        type: 'GET',
        url: 'http://' + window.location.host + '/informesAutoexcluidos/buscarAutoexcluidos',
        data: formData,
        async: async,
        dataType: 'json',
        success: function(resultados) {
            $('#herramientasPaginacion')
                .generarTitulo(page_number, page_size, resultados.total, clickIndice);

            $('#cuerpoTabla tr').not('.filaTabla').remove();

            for (var i = 0; i < resultados.data.length; i++) {
                $('#tablaInformesAE tbody').append(generarFilaTabla(resultados.data[i]));
            }

            $('#herramientasPaginacion')
                .generarIndices(page_number, page_size, resultados.total, clickIndice);
        },
        error: function(data) {
            console.log('Error:', data);
        }
    });
});

//Paginacion
$(document).on('click', '#tablaInformesAE thead tr th[value]', function(e) {
    $('#tablaInformesAE th').removeClass('activa');
    if ($(e.currentTarget).children('i').hasClass('fa-sort')) {
        $(e.currentTarget).children('i')
            .removeClass().addClass('fa fa-sort-desc')
            .parent().addClass('activa').attr('estado', 'desc');
    } else {
        if ($(e.currentTarget).children('i').hasClass('fa-sort-desc')) {
            $(e.currentTarget).children('i')
                .removeClass().addClass('fa fa-sort-asc')
                .parent().addClass('activa').attr('estado', 'asc');
        } else {
            $(e.currentTarget).children('i')
                .removeClass().addClass('fa fa-sort')
                .parent().attr('estado', '');
        }
    }
    $('#tablaInformesAE th:not(.activa) i')
        .removeClass().addClass('fa fa-sort')
        .parent().attr('estado', '');
    clickIndice(e,
        $('#herramientasPaginacion').getCurrentPage(),
        $('#herramientasPaginacion').getPageSize());
});

function clickIndice(e, pageNumber, tam,async = true) {
    if (e != null) {
        e.preventDefault();
    }
    var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
    var columna = $('#tablaInformesAE .activa').attr('value');
    var orden = $('#tablaInformesAE .activa').attr('estado');
    $('#btn-buscar').trigger('click', [pageNumber, tam, columna, orden,async]);
}

function generarFilaTabla(unAutoexcluido) {
    const convertir_fecha = function(fecha){
      if(fecha === null || fecha.length == 0) return '-';
      yyyymmdd = fecha.split('-');
      return yyyymmdd[2] + '/' + yyyymmdd[1] + '/' + yyyymmdd[0].substring(2);
    }
    let fila = $('#cuerpoTabla .filaTabla').clone().removeClass('filaTabla').show();
    fila.attr('data-id', unAutoexcluido.id_autoexcluido);
    fila.find('.casino').text(unAutoexcluido.casino);
    const estado = unAutoexcluido.estado + (unAutoexcluido.estado == unAutoexcluido.puede? ''  : (' ⤻ ' + unAutoexcluido.puede));
    fila.find('.estado').text(estado).attr('title',estado);
    fila.find('.apellido').text(unAutoexcluido.apellido).attr('title',unAutoexcluido.apellido);
    fila.find('.nombres').text(unAutoexcluido.nombres).attr('title',unAutoexcluido.nombres);
    fila.find('.dni .link').text(unAutoexcluido.nro_dni).attr('href','/autoexclusion/'+unAutoexcluido.nro_dni);
    fila.find('.dni .btnVerFoto').attr('href','/galeriaImagenesAutoexcluidos/'+unAutoexcluido.nro_dni);
  
    fila.find('.localidad').text(unAutoexcluido.nombre_localidad).attr('title',unAutoexcluido.nombre_localidad);
    fila.find('.provincia').text(unAutoexcluido.nombre_provincia).attr('title',unAutoexcluido.nombre_provincia);

    fila.find('.fecha_ae').text(convertir_fecha(unAutoexcluido.fecha_ae));
    fila.find('.fecha_vencimiento_primer_periodo').text(convertir_fecha(unAutoexcluido.fecha_vencimiento));
    fila.find('.fecha_finalizacion').text(convertir_fecha(unAutoexcluido.fecha_revocacion_ae));
    fila.find('.fecha_cierre_ae').text(convertir_fecha(unAutoexcluido.fecha_cierre_ae));

    if(!unAutoexcluido.es_primer_ae){
        fila.find('td').css('font-style','italic');
        fila.find('.fecha_finalizacion').text('-').attr('title','-');
        fila.find('.fecha_vencimiento_primer_periodo').text('-').attr('title','-');
    }
    fila.css('display', 'flow-root');
    return fila;
}

$("#contenedorFiltros input").on('keypress',function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#btn-buscar').click();
  }
});

$('#agregarCSV').click(function(){
  //Realizo una busqueda sincronica para no agregar mal si esta escrito un filtro pero no hizo click en buscar.
  clickIndice(null,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize(),false);
  const e = function(s){
    return s.length == 0? '\xa0' : s;
  };
  const assign = function(obj,s){
    obj.text(s).attr('title',s);
  };
  const fila = $('#tablaCSV tbody .filaTablaCSV').clone().removeClass('filaTablaCSV').css('display','');
  fila.find('.padding').css('display','none');
  fila.dblclick(function(){$(this).remove();exportarCSV();});

  const casino = $('#buscadorCasino').val() == ''? '\xa0' : $('#buscadorCasino option:selected').attr('data-codigo');
  assign(fila.find('.casino'),casino);

  const estado = $('#buscadorEstado').val() == ''? '\xa0' : $('#buscadorEstado option:selected').text();
  assign(fila.find('.estado'),estado);

  assign(fila.find('.apellido'),e($('#buscadorApellido').val()));

  const dia = $('#buscadorDia').val() == ''? '\xa0' : $('#buscadorDia option:selected').text();
  assign(fila.find('.dia_semanal'),dia);

  const rango_etario = e($('#buscadorRangoEtarioD').val()) +' - '+ e($('#buscadorRangoEtarioH').val());
  assign(fila.find('.rango_etario'),rango_etario.length == 5? '\xa0' : rango_etario);

  assign(fila.find('.dni'),e($('#buscadorDni').val()));
  const sexo = $('#buscadorSexo').val() == ''? '\xa0' : $('#buscadorSexo option:selected').text();
  assign(fila.find('.sexo'),e(sexo));
  assign(fila.find('.localidad'),e($('#buscadorLocalidad').val()));
  assign(fila.find('.provincia'),e($('#buscadorProvincia').val()));
  const f_ae = e($('#buscadorFechaAutoexclusionD').val())+' - '+e($('#buscadorFechaAutoexclusionH').val());
  assign(fila.find('.f_ae'),f_ae.length == 5? '\xa0' : f_ae);
  const f_v = e($('#buscadorFechaVencimientoD').val())   +' - '+e($('#buscadorFechaVencimientoH').val())
  assign(fila.find('.f_v'),f_v.length == 5? '\xa0' : f_v);
  const f_r = e($('#buscadorFechaRevocacionD').val())    +' - '+e($('#buscadorFechaRevocacionH').val());
  assign(fila.find('.f_r'),f_r.length == 5? '\xa0' : f_r);
  const f_c = e($('#buscadorFechaCierreD').val())       +' - '+e($('#buscadorFechaCierreH').val());
  assign(fila.find('.f_c'),f_c.length == 5? '\xa0' : f_c);

  const hace_encuesta = $('#buscadorEncuesta option:selected').text();
  assign(fila.find('.hace_encuesta'),$('#buscadorEncuesta').val() == ''? '\xa0' : hace_encuesta);
  const frecuencia = $('#buscadorFrecuencia option:selected').text();
  assign(fila.find('.frecuencia'),$('#buscadorFrecuencia').val() == ''? '\xa0' : frecuencia);
  const compania = $('#buscadorCompania option:selected').text();
  assign(fila.find('.compania'),$('#buscadorCompania').val() == ''? '\xa0' : compania);
  const juego = $('#buscadorJuego option:selected').text();
  assign(fila.find('.juego'),$('#buscadorJuego').val() == ''? '\xa0' : juego);
  const programa = $('#buscadorJuegoResponsable option:selected').text();
  assign(fila.find('.programa'),$('#buscadorJuegoResponsable').val() == ''? '\xa0' : programa);
  const socio = $('#buscadorClub option:selected').text();
  assign(fila.find('.socio'),$('#buscadorClub').val() == ''? '\xa0' : socio);
  const autocontrol = $('#buscadorAutocontrol option:selected').text();
  assign(fila.find('.autocontrol'),$('#buscadorAutocontrol').val() == ''? '\xa0' : autocontrol);
  const recibir_info = $('#buscadorRecibirInfo option:selected').text();
  assign(fila.find('.recibir_info'),$('#buscadorRecibirInfo').val() == ''? '\xa0' : recibir_info);
  const medio = $('#buscadorMedio option:selected').text();
  assign(fila.find('.medio'),$('#buscadorMedio').val() == ''? '\xa0' : medio);

  const range_val = function(s1,s2){
      const obj1 = $(s1);
      const obj2 = $(s2);
      if($(s1).parent().find('.no_contesta').prop('checked')) return 'No contesta';
      return obj1.val() + ' - ' + obj2.val();
  }

  const horas = range_val('#buscadorHorasD','#buscadorHorasH');
  assign(fila.find('.horas'),horas.length == 3? '\xa0' : horas);
  const veces = range_val('#buscadorVecesD','#buscadorVecesH');
  assign(fila.find('.veces'),veces.length == 3? '\xa0' : veces);

  const cant = $('#herramientasPaginacion h4').text().split(' ')[6];//@HACK
  assign(fila.find('.cant'),cant == null? '0' : cant);
  fila.find('td').filter(function () { return $(this).text() == '\xa0';}).css('background','rgba(0,0,0,0.1)');
  $('#tablaCSV tbody').append(fila);
  exportarCSV()
});

$('#limpiarCSV').click(function(e){
  $('#tablaCSV tbody tr').not('.filaTablaCSV').remove();
  exportarCSV();
});

$('#columnasCSV').change(function(){
  exportarCSV();
});

$('#importarCSV').click(function(){
    $('#importarCSVinput').click();
});

$('#importarCSVinput').change(function(){
    const archivos = $('#importarCSVinput')[0].files;
    if(archivos.length == 0) return;
    const csv = archivos[0];
    const reader = new FileReader();
    reader.onload = function(){
        importarCSV(reader.result);
    }
    reader.readAsText(csv);
});

function exportarCSV(){
    const vacio = function(s){
        return s == '\xa0' || s == '\xa0 - \xa0';
    }
    const filas = [];
    const borrar = $('#columnasCSV').is(':checked');
    const borrar_col = [];
    const cabezera = [];
    $('#tablaCSV thead tr th').each(function(idx,val){
        cabezera.push($(val).text());
        borrar_col.push(borrar);
    });
    filas.push(cabezera);

    $('#tablaCSV tbody tr').not('.filaTablaCSV').each(function(rowidx,val){
        const f = [];
        $(val).find('td').each(function(colidx,val2){
            const t = $(val2).text();
            borrar_col[colidx] = borrar_col[colidx] && vacio(t);
            f.push(t);
        });
        filas.push(f);
    });

    transformadas = [];
    for(const f in filas){
        const sin_cols_innecesarias = filas[f].filter(function(elem,idx){
            return !borrar_col[idx];
        });
        const vaciado = sin_cols_innecesarias.map(elem => vacio(elem)? '' : ('"'+elem+'"'))
        transformadas.push(vaciado);
    }

    let csv = "";
    transformadas.forEach(function(f){
        f.join(',');
        csv += f + '\n';
    });

    const a = document.getElementById("descargarCSV");
    const file = new Blob([csv], {type: 'text/csv'});
    a.href = URL.createObjectURL(file);
    a.download = 'informeAE.csv';
    
    mostrarColumnas(borrar_col);
}


function mostrarColumnas(hidecols){
    $('#tablaCSV thead tr th').each(function(idx,elem){
        console.log(idx);
        $(elem).css('display',hidecols[idx]? 'none' : '');
    });
    $('#tablaCSV tbody tr').not('.filaTablaCSV').each(function(){
        $(this).find('td').each(function(idx,elem){
            $(elem).css('display',hidecols[idx]? 'none' : '');
        })
    });
}

function importarCSV(s){
    $('#limpiarCSV').click();
    s = s.replace(/\r\n/g,'\n');//Saco el retorno de linea de Windows
    let lines = s.split('\n');
    if(lines.length == 0) return;
    const colnames = lines[0].split(',');
    const tablecols = $('#tablaCSV thead tr');
    const colidxs = {};
    // Nota: Las columnas pueden faltar por la opcion de remover columnas, por eso
    // es necesario este paso
    for(const idx in colnames){// Saco cual es el numero de la columna
        const col = colnames[idx].replace(/"/g,'');//Le saco comillas
        const th = tablecols.find('th:contains('+col+')');
        if(th.length == 0) continue;//No existe columna con ese nombre
        const filtro = th.attr('data-busq');
        const es_fecha = th.is('[fecha]');
        const attr = th.attr('data-busq-attr');
        colidxs[idx] = {filtro: filtro,es_fecha: es_fecha,attr: attr,rango: th.is('[rango]'),opcional: th.is('[opcional]')};
    }
    lines  = lines.slice(1);
    if(lines.length == 0) return;
    const to_iso = function(s){
        const ddmmyy = s.split('/');
        if(ddmmyy.length < 3) return null;
        //@HACK timezone de Argentina, supongo que esta bien porque el servidor esta en ARG
        return '20'+ddmmyy[2]+'-'+ddmmyy[1]+'-'+ddmmyy[0]+'T00:00:00.000-03:00';
    }
    //NOTA: esto tal vez termino siendo artificialmente generico, capaz era mejor hardcodear cada opcion en un switch
    for(const lineidx in lines){
        if(lines[lineidx].length == 0) continue;
        const cols = lines[lineidx].split(',');
        limpiarFiltros();
        for(const colidx in cols){
            if(!colidxs.hasOwnProperty(colidx)) continue;
            const aux = colidxs[colidx];
            const text = cols[colidx].replace(/"/g,'');
            if(aux.es_fecha){
                const fechas = text.split('-');
                const desde = to_iso(fechas[0]? fechas[0].replace(/ /g,'') : '');
                const hasta = to_iso(fechas[1]? fechas[1].replace(/ /g,'') : '');
                const dtpD = $(aux.filtro+'D');
                const dtpH = $(aux.filtro+'H');
                if(desde != null) dtpD.data("datetimepicker").setDate(new Date(desde));
                if(hasta != null) dtpH.data("datetimepicker").setDate(new Date(hasta));
            }
            else if(aux.rango){
                if(aux.opcional){
                    if(text == "No contesta"){
                        $(aux.filtro+'D').parent().find('.no_contesta').prop('checked',true).change();
                        continue;
                    }
                }
                const vals = text.split('-');
                cargarVal($(aux.filtro+'D'),aux.attr,vals[0]? vals[0] : '');
                cargarVal($(aux.filtro+'H'),aux.attr,vals[1]? vals[1] : '');
            }
            else{
                cargarVal($(aux.filtro),aux.attr,text);
            }
        }
        $('#agregarCSV').click();
    }
}

function cargarVal(dom,attr,text){
    if(dom.is('select')){
        const selval = dom.find('option').filter(function () { //Busco el val del option para setearlo
            const seltext = (attr)? $(this).attr(attr) : $(this).text();
            return seltext == text; 
        }).val();
        dom.val(selval);
    }
    else if(dom.is('input')){
        dom.val(text);
    }
}

function limpiarFiltros(){
    $('#collapseFiltros input').val('');
    $('#collapseFiltros select').val('');
    $('#collapseFiltros .no_contesta').prop('checked',true).change().prop('checked',false).change();
}

function mensajeError(msg){
    $('#mensajeError .textoMensaje').empty();
    $('#mensajeError .textoMensaje').append($('<h4>'+msg+'</h4>'));
    $('#mensajeError').hide();
    setTimeout(function() {
      $('#mensajeError').show();
    }, 250);
  }

  $('#buscadorEncuesta').change(function(){
    $('#contenedorFiltros .encuesta').attr('disabled',$(this).val() === "0").val('');
  })

  $('.no_contesta').change(function(){
      const checked = $(this).prop('checked');
      $(this).parent().find('input').not('.no_contesta').attr('disabled',checked).val('');
  })

  $('.encuesta').change(function(){
      if($(this).val()!='') $('#buscadorEncuesta').val(1);
  })