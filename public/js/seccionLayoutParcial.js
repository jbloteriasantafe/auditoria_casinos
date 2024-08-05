import '/js/Components/inputFecha.js';
import '/js/Components/FiltroTabla.js';
import '/js/Components/modalEliminar.js';
import {AUX} from "/js/Components/AUX.js";
import '/js/Components/cambioCasinoSelectSectores.js';

$(function(){
  
$('.tituloSeccionPantalla').text('Layout Parcial');

$('[data-js-cambio-casino-select-sectores]')
.trigger('set_url',['layout_parcial/obtenerSectoresPorCasino'])
.trigger('change');

$('[data-js-filtro-tabla]').each(function(idx,fObj){ $(fObj).on('busqueda',function(e,ret,tbody,molde){  
  ret.data.forEach(function(r){
    const fila = molde.clone();
    fila.find('.fecha').text(r.fecha);
    fila.find('.casino').text(r.casino);
    fila.find('.sector').text(r.sector);
    fila.find('.subrelevamiento').text(r.subcontrol ?? '');
    fila.find('button').val(r.id_layout_parcial);
    fila.find('[data-id_estado_relevamiento]').hide()
    .filter(function(idx,obj){
      return $(obj).attr('data-id_estado_relevamiento').split(',').includes(r.id_estado_relevamiento+'');
    }).show();
    tbody.append(fila);
  });
  tbody.find('[data-js-planilla]').click(function(e){
    window.open('layout_parcial/generarPlanillaLayoutParcial/' + $(e.currentTarget).val(),'_blank');
  });
  tbody.find('[data-js-imprimir]').click(function(e){
    window.open('layout_parcial/generarPlanillaLayoutParcial/' + $(e.currentTarget).val(),'_blank');
  });
  tbody.find('[data-js-abrir-modal]').click(function(e){
    const boton = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-validar-layout-parcial]').trigger('mostrar',[boton.val(),boton.attr('data-js-abrir-modal')]);
  });
}).trigger('buscar'); });
  
$('#btn-nuevoLayoutParcial').click(function(e){
  e.preventDefault();
  $('[data-js-modal-layout-parcial]').trigger('mostrar');
});

$('#btn-layoutSinSistema').click(function(e){
  e.preventDefault();
  $('[data-js-modal-layout-parcial-sin-sistema]').trigger('mostrar');
});

$('[data-js-modal-layout-parcial]').each(function(idx,Mobj){
  const M = $(Mobj);
  
  M.on('mostrar',function(e){
    M.find('[data-js-icono-carga]').hide();
    ocultarErrorValidacion(M.find('[name]').each(function(idx,obj){
      $(obj).val($(obj).attr('data-default') ?? '');
    }).change());
    M.modal('show');
  });
  
  M.find('[data-js-generar]').click(function(e){
    e.preventDefault();
    
    const formData = AUX.form_entries(M.find('form')[0]);
    ocultarErrorValidacion(M.find('[name]'));
    M.find('[data-js-icono-carga]').show();
    
    AUX.POST('layout_parcial/crearLayoutParcial',formData,
      function(data){
        M.find('[data-js-icono-carga]').hide();
        $('[data-js-filtro-tabla]').trigger('buscar');
        
        if(data.nombre_zip !== undefined){
          let iframe = document.getElementById("download-container");
          if (iframe === null){
            iframe = document.createElement('iframe');
            iframe.id = "download-container";
            iframe.style.visibility = 'hidden';
            document.body.appendChild(iframe);
          }
          iframe.src = '/layout_parcial/descargarLayoutParcialZip/' + data.nombre_zip;
          AUX.mensajeExito('Layout Parcial creado');
          M.modal('hide');
        }
        else if(data.existeLayoutParcial == 1){
          AUX.mensajeError('Deberá finalizar el relevamiento existente para poder generar uno nuevo.');
        }
      },
      function(data){
        M.find('[data-js-icono-carga]').hide();
        AUX.mostrarErroresNames(M,data.responseJSON ?? {});
      }
    );
  });
});

$('[data-js-modal-layout-parcial-sin-sistema]').each(function(idx,Mobj){
  const M = $(Mobj);
  
  M.on('mostrar',function(e){
    M.find('[data-js-icono-carga]').hide();
    ocultarErrorValidacion(M.find('[name]').val(''));
    M.find('[data-js-fecha]').each(function(idx,fObj){
      $(fObj).data('datetimepicker').reset();
    });
    M.modal('show');
  });
  
  M.find('[data-js-usar-relevamiento-backup]').click(function(e){
    e.preventDefault();
    
    const formData = AUX.form_entries(M.find('form')[0]);
    ocultarErrorValidacion(M.find('[name]'));
    M.find('[data-js-icono-carga]').show();
    
    AUX.POST('layout_parcial/usarLayoutBackup',formData,
      function(data){
        M.find('[data-js-icono-carga]').hide();
        $('[data-js-filtro-tabla]').trigger('buscar');
        AUX.mensajeExito('Layout Parcial de backup habilitado');
        M.modal('hide');
      },
      function(data){
        M.find('[data-js-icono-carga]').hide();
        AUX.mostrarErroresNames(M,data.responseJSON ?? {});
      }
    );
  });
});

$('[data-js-modal-ver-cargar-validar-layout-parcial]').each(function(mIdx,mObj){
  const M = $(mObj);  
  M.on('mostrar',function(e,id_layout_parcial,modo){
    M.attr('data-css-modo',modo);
    
    AUX.GET('layout_parcial/obtenerLayoutParcial/'+id_layout_parcial,{},function(data){
      const tbody = M.find('[data-js-tabla-relevado] tbody').empty();
      const molde = M.find('[data-js-molde-relevado]').clone().removeAttr('data-js-molde-relevado');
      
      data.detalles.forEach(function(d){
        const fila = molde.clone();
        fila.find('[name]').each(function(idx,nobj){
          const o = $(nobj);
          const name = o.attr('name');
          const dname = d[name];
          o.val(dname?.valor ?? dname ?? '');
          if(o.is('[data-js-editable-original]')){
            o.attr('data-js-editable-original',dname?.valor_antiguo?.length?
              dname.valor_antiguo
            : (dname.valor ?? dname));
            
            if(o.attr('data-js-editable-original') == o.val()){
              o.attr('readonly',true);
            }
          }
        });
        
        fila.find('[name="nro_admin"]').val(d.nro_admin.valor);
        fila.find('[name="nro_isla"]').val(d.nro_isla.valor);
        fila.find('[name="marca"]').val(d.marca.valor);
        fila.find('[name="juego"]').val(d.juego.valor);
        fila.find('[name="nro_serie"]').val(d.nro_serie.valor);
        tbody.append(fila);
      });
      
      tbody.find('[data-js-editable-original]').on('dblclick',function(e){
        $(e.currentTarget).removeAttr('readonly');
      });
      
      M.find('[data-js-modo-habilitar]:not([data-js-fecha])').attr('disabled',true).filter(function(idx,obj){
        return $(obj).attr('data-js-modo-habilitar').split(',').includes(modo+'');
      }).removeAttr('disabled');
      
      M.find('[data-js-modo-habilitar][data-js-fecha]').each(function(idx,obj){
        obj.disabled(true);
      }).filter(function(idx,obj){
        return $(obj).attr('data-js-modo-habilitar').split(',').includes(modo+'');
      }).each(function(idx,obj){
        obj.disabled(false);
      });
      
      M.modal('show');
    });
  });
});
  
});

var guardado;
var salida; //cantidad de veces que se apreta salir

$(document).ready(function(){
  $('#dtpFecha').datetimepicker({
    todayBtn:  1,
    language:  'es',
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy - HH:ii',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 0,
    ignoreReadonly: true,
    minuteStep: 5,
  });
});

/*****   Modal de validacion  *****/
$(document).on('click','[data-js-validar]',function(e){
  e.preventDefault();
  $('#mensajeExito').hide();
  var modal = $('#modalValidarControl'); // formato en html
  var id_layout_parcial = $(this).val();
  modal.find('#id_layout_parcial').val(id_layout_parcial);

  //Setear el id del layout en el modal
  var id_layout_parcial = $(this).val();
  $('#id_layout_parcial').val(id_layout_parcial);

  $.get('layout_parcial/obtenerLayoutParcialValidar/' + id_layout_parcial, function(data) {
    $('#validarFechaActual').val(data.layout_parcial.fecha);
    $('#validarFechaEjecucion').val(data.layout_parcial.fecha_ejecucion);
    $('#validarCasino').val(data.casino);
    $('#validarSector').val(data.sector);

    if (data.usuario_cargador != null) {
      $('#validarFiscaCarga').val(data.usuario_cargador.nombre);
    }
    if (data.usuario_fiscalizador){
      $('#validarFiscaToma').val(data.usuario_fiscalizador.nombre);
    }

    $('#observacion_fiscalizacion').val(data.layout_parcial.observacion_fiscalizacion);
    $('#validarTecnico').val(data.layout_parcial.tecnico);

    //Limpiar la lista de máquinas del layout
    modal.find('#tablaMaquinasLayouts > tbody tr').remove();

    //Agregar las máquinas en la lista
    for (var i = 0; i < data.detalles.length; i++) {
      // agregarFilaMaquina(data.detalles[i], modal, "Carga");
      agregarFilaTablaMaquinasLayout(data.detalles[i],modal,"Validar");
    }
  });

  //Mostrar modal
  $('#modalValidarControl').modal('show');
});

/*****   Modal de carga | nueva manera   *****/
$(document).on('click','[data-js-cargar]',function(e){
  e.preventDefault();
  guardado = true;
  $('#mensajeExito').hide();

  var modal = $('#modalCargaControlLayout');
  var id_layout_parcial = $(this).val();
  modal.find('#id_layout_parcial').val(id_layout_parcial);

  $.get('layout_parcial/obtenerLayoutParcial/' + id_layout_parcial, function(data){
    $('#cargaFechaActual').val(data.layout_parcial.fecha);
    $('#cargaFechaGeneracion').val(data.layout_parcial.fecha_generacion);
    $('#cargaCasino').val(data.casino);
    $('#cargaSector').val(data.sector);
    var subrelevamiento = data.layout_parcial.sub_control != null ? data.layout_parcial.sub_control : "";
    $('#cargaSubrelevamiento').val(subrelevamiento);
    $('#fecha').val(data.layout_parcial.fecha_ejecucion);
    $('#fecha_ejecucion').val(data.layout_parcial.fecha_ejecucion);

    if (data.usuario_cargador != null) {
      $('#fiscaCarga').val(data.usuario_cargador.nombre);
    }

    $('#inputFisca').generarDataList('layout_parcial/buscarUsuariosPorNombreYCasino/'+ data.id_casino,'usuarios','id_usuario','nombre',2);
    $('#inputFisca').setearElementoSeleccionado(0,"");
    if (data.usuario_fiscalizador){
      $('#inputFisca').setearElementoSeleccionado(data.usuario_fiscalizador.id_usuario,data.usuario_fiscalizador.nombre);
    }

    $('#tecnico').val(data.layout_parcial.tecnico);

    //Limpiar la lista de máquinas del layout
    modal.find('#tablaMaquinasLayouts > tbody tr').remove();
    //Agregar las máquinas en la lista
    for (var i = 0; i < data.detalles.length; i++) {
      agregarFilaTablaMaquinasLayout(data.detalles[i],modal,"Carga");
    }
  });

  $('#modalCargaControlLayout').modal('show');
});

//Escuchar los checkboxs que cambian su estado
$('#contenedorMaquinas').on('change','.inputConCheck input:checkbox',function(){
  var input = $(this).parent().parent().find(':text');

  //Si se checkea, se deshabilita el input y se muestra contenido original
  if (this.checked) {
    input.attr('readonly',true);
    input.val(input.attr('data-original'));
    //Si se descheckea se habilita el input para introducir texto correcto
  }else {
    input.attr('readonly',false);
  }
});

//Armar la tabla agregando una fila por cada máquina para layout
function agregarFilaTablaMaquinasLayout(fila,modal,estado){
  var popGenerico = $('<a>').addClass('pop')
  .attr("title","VALOR DEL SISTEMA")
  .attr("data-placement" , "top")
  .attr("data-trigger" , "hover");

  var inputGenerico = $('<input>').addClass('form-control inputLayout modificable').attr({'type':'text','readonly':true});

  //Datas originales
  var data_maquina = (fila.nro_admin.valor == null) ? '' : fila.nro_admin.valor;
  var data_isla = (fila.nro_isla.valor == null) ? '' : fila.nro_isla.valor;
  var data_fabricante = (fila.marca.valor == null) ? '' : fila.marca.valor;
  var data_juego = (fila.juego.valor == null) ? '' : fila.juego.valor;
  var data_serie = (fila.nro_serie.valor == null) ? '' : fila.nro_serie.valor;

  var maquina = popGenerico.clone().attr('data-content',fila.nro_admin.valor_antiguo)
                           .append(inputGenerico.clone().addClass('nro_admin').attr('data-original',data_maquina).val(fila.nro_admin.valor));
  var isla = popGenerico.clone().attr('data-content',fila.nro_isla.valor_antiguo)
                        .append(inputGenerico.clone().addClass('nro_isla').attr('data-original',data_isla).val(fila.nro_isla.valor));
  var fabricante = popGenerico.clone().attr('data-content',fila.marca.valor_antiguo)
                              .append(inputGenerico.clone().addClass('marca').attr('data-original',data_fabricante).val(fila.marca.valor));
  var juego = popGenerico.clone().attr('data-content',fila.juego.valor_antiguo)
                         .append(inputGenerico.clone().addClass('juego').attr('data-original',data_juego).val(fila.juego.valor));
  var nro_serie = popGenerico.clone().attr('data-content',fila.nro_serie.valor_antiguo)
                             .append(inputGenerico.clone().addClass('nro_serie').attr('data-original',data_serie).val(fila.nro_serie.valor));


  var juegosPack='<div align="left">';
  var botonMultiJuego;                           
  if (fila.tiene_pack_bandera){
    if (estado == "Validar") {
      fila.juegos_pack.forEach(j => {
        juegosPack =  juegosPack
                       +   '<span style="position:relative;top:-3px;">'+ j.nombre_juego +'</span><br>'
                       
     });
    }
    else{
      fila.juegos_pack.forEach(j => {
        juegosPack =  juegosPack
                       +   '<input type="radio" class="seleccionJuego" value="'+ j.nombre_juego +'" data-idjuego="'+j.id_juego+'" >'
                       +   '<span style="position:relative;top:-3px;">'+ j.nombre_juego +'</span><br>'
                       
      });
    }

    juegosPack= juegosPack+'</div>';
    botonMultiJuego = $('<button>')
    .attr('data-trigger','manual')
    .attr('data-toggle','popover')
    .attr('data-placement','left')
    .attr('data-html','true')
    .attr('title','JUEGOS')
    .attr('data-content',juegosPack)
    .attr('type','button')
    .addClass('btn btn-warning pop medida')
    .append($('<i>').addClass('fas fa-exchange-alt'));
  }else{
    botonMultiJuego="-"
  }                            
  
  var bandera = false; //Si tuvo algun error

  // para gestion de pack de juego se agregan validaciones para distinguir si el juego cambio dentro del paquete

  if (fila.tiene_pack_bandera){//posee pack
    if (fila.juego.valor_antiguo != "") {//cambio el juego
      inPack=false;
      fila.juegos_pack.forEach(j => {

        if(fila.juego.valor==j.nombre_juego){//el juego pertenece al pack
          inPack=true;
        }
                       
     });

     if(inPack){// cambio el juego dentro de los valores del pack
      juego.addClass('modificado').find('input').css('border','2px solid blue');bandera=true;
     }else{// cambio por uno que no pertence al pack
      juego.addClass('modificado').find('input').css('border','2px solid red');bandera=true;
     }
    }
  }else{// no implementa pack 
    if (fila.juego.valor_antiguo != "") {juego.addClass('modificado').find('input').css('border','2px solid red');bandera=true;}
  }

  //Para validar habilitar el POP
  if (fila.nro_admin.valor_antiguo != "") {maquina.addClass('modificado').find('input').css('border','2px solid red');bandera=true;}
  if (fila.nro_isla.valor_antiguo != "") {isla.addClass('modificado').find('input').css('border','2px solid red'); bandera=true;}
  if (fila.marca.valor_antiguo != "") {fabricante.addClass('modificado').find('input').css('border','2px solid red');bandera=true;}
  if (fila.nro_serie.valor_antiguo != "") {nro_serie.addClass('modificado').find('input').css('border','2px solid red');bandera=true;}

  var no_toma = $('<input>').attr('type','checkbox').addClass('checkboxLayout check_notoma').prop('checked', fila.no_toma).prop('disabled',false);
  var denominacion = $('<input>').addClass('form-control den_sala').attr({'data-original':fila.denominacion,'type':'text'}).val(fila.denominacion);
  var porcentaje_dev = $('<input>').addClass('form-control porc_dev').attr({'data-original':fila.porcentaje_dev,'type':'text'}).val(fila.porcentaje_dev);

  var botonProgresivo = $('<button>').addClass('btn btn-default progresivo').attr('type','button')
                                     .attr('data-toggle','collapse').attr('data-target','#progresivo'+fila.id_maquina).hide()
                                     .append($('<i>').addClass('fa fa-fw fa-angle-down'));
  var boton_gestionar = $('<a>').addClass('btn btn-success pop gestion_maquina')
  .attr('type' , 'button')
  .attr('href' , 'http://' + window.location.host + '/maquinas/' + fila.id_maquina)
  .attr('target' , '_blank')
  .attr("data-placement" , "top")
  .attr('data-trigger','hover')
  .attr('title','GESTIONAR MÁQUINA')
  .attr('data-content','Ir a sección máquina')
  .append($('<i>').addClass('fa fa-fw fa-wrench'))
  .hide();

  var filaMaquinaLayout = $('<tr>').attr('id',fila.id_maquina);

  filaMaquinaLayout.append($('<td>').append(maquina));
  filaMaquinaLayout.append($('<td>').append(isla));
  filaMaquinaLayout.append($('<td>').append(fabricante));
  filaMaquinaLayout.append($('<td>').append(botonMultiJuego));
  filaMaquinaLayout.append($('<td>').append(juego));
  filaMaquinaLayout.append($('<td>').append(nro_serie));
  filaMaquinaLayout.append($('<td>').append(no_toma));
  filaMaquinaLayout.append($('<td>').append(denominacion));
  filaMaquinaLayout.append($('<td>').append(porcentaje_dev));
  filaMaquinaLayout.append($('<td>').append(botonProgresivo).append(boton_gestionar));

  modal.find('#tablaMaquinasLayouts > tbody').append(filaMaquinaLayout);
  //Todo lo de PROGRESIVO
  if (fila.progresivo != null){
    botonProgresivo.show();

    var rowProgresivo = modal.find('.rowProgresivo').first().clone().show();

    var data_nombre = (fila.progresivo.nombre_progresivo.valor == null) ? '' : fila.progresivo.nombre_progresivo.valor;
    var data_maximo = (fila.progresivo.maximo.valor == null) ? '' : fila.progresivo.maximo.valor;
    var data_recuperacion = (fila.progresivo.porc_recuperacion.valor == null) ? '' : fila.progresivo.porc_recuperacion.valor;

    rowProgresivo.find('.nombre_progresivo').addClass('modificable').attr('data-original',data_nombre).val(fila.progresivo.nombre_progresivo.valor);
    if (fila.progresivo.individual.valor)
      rowProgresivo.find('.tipo_progresivo').addClass('modificable').attr('data-original','LINKEADO').val('LINKEADO');
    else
      rowProgresivo.find('.tipo_progresivo').addClass('modificable').attr('data-original','INDIVIDUAL').val('INDIVIDUAL');
    rowProgresivo.find('.maximo_progresivo').addClass('modificable').attr('data-original',data_maximo).val(fila.progresivo.maximo.valor);
    rowProgresivo.find('.recuperacion_progresivo').addClass('modificable').attr('data-original',data_recuperacion).val(fila.progresivo.porc_recuperacion.valor);

    var filaProgresivo = $('<tr>').attr('id','progresivo' + fila.id_maquina).attr('data-progresivo',fila.progresivo.id_progresivo).addClass('collapse out')
                                  .append($('<td>').css('border-top','none').attr('colspan','9')
                                                   .append(rowProgresivo));
     //Todo lo de NIVELES
    if (fila.niveles != null) {
      var rowNivelProgresivo = modal.find('.rowNivelProgresivo').first().clone().show();
      var filaNivel = rowNivelProgresivo.find('.tablaNivelProgresivo .filaNivel').first();

      //Limpiar tabla de niveles
      rowNivelProgresivo.find('.tablaNivelProgresivo .filaNivel').remove();

      for (var i = 0; i < fila.niveles.length; i++) {
        var filaNivelNueva = filaNivel.clone().show();
        var data_nro = (fila.niveles[i].nro_nivel.valor == null) ? '' : fila.niveles[i].nro_nivel.valor;
        var data_nombre = (fila.niveles[i].nombre_nivel.valor == null) ? '' : fila.niveles[i].nombre_nivel.valor;
        var data_base = (fila.niveles[i].base.valor == null) ? '' : fila.niveles[i].base.valor;
        var data_oculto = (fila.niveles[i].porc_oculto.valor == null) ? '' : fila.niveles[i].porc_oculto.valor;
        var data_visible = (fila.niveles[i].porc_visible.valor == null) ? '' : fila.niveles[i].porc_visible.valor;

        filaNivelNueva.find('.nro_nivel').addClass('modificable').attr('data-original',data_nro).val(fila.niveles[i].nro_nivel.valor);
        filaNivelNueva.find('.nombre_nivel').addClass('modificable').attr('data-original',data_nombre).val(fila.niveles[i].nombre_nivel.valor);
        filaNivelNueva.find('.base_nivel').addClass('modificable').attr('data-original',data_base).val(fila.niveles[i].base.valor);
        filaNivelNueva.find('.porc_oculto').addClass('modificable').attr('data-original',data_oculto).val(fila.niveles[i].porc_oculto.valor);
        filaNivelNueva.find('.porc_visible').addClass('modificable').attr('data-original',data_visible).val(fila.niveles[i].porc_visible.valor);

        rowNivelProgresivo.find('.tablaNivelProgresivo tbody').append(filaNivelNueva);
      }

      filaProgresivo.children().append(rowNivelProgresivo);
    }

    modal.find('#tablaMaquinasLayouts > tbody').append(filaProgresivo);
  }

  //Esto hay que modificarlo SOLO para el que esté mal
  if (estado == "Validar") {
    // popGenerico.addClass('modificado');
    $('.inputLayout').removeClass('modificable');
    $('.check_notoma').attr('disabled',true);
    $('.den_sala').attr('readonly',true);
    $('.porc_dev').attr('readonly',true);
  }

  //muestra pop solo aqullos campos que fueron modificado
  $('.pop.modificado').popover({ html:true });
  
  filaMaquinaLayout.find('.gestion_maquina').show();
}

//Eventos cuando cierra el modal
$('.modal').on('hidden.bs.modal', function() {
  $('#tecnico').popover('hide');
  $('#fecha').popover('hide');
  $('#frmLayoutParcial').trigger('reset');
  $('#frmLayoutSinSistema').trigger('reset');
  $('#inputFisca').popover('hide');
  $('.popover').removeClass('popAlerta');

  if($('#casino').length > 1){
    $('.selectSector' , this).empty();
  }
  
  $(this).find('#contenedorMaquinas div').remove();

  $(this).find('#tablaMaquinasLayouts > tbody tr  ').remove();

  $('#id_layout_parcial').val(0);
});

//Devuelve un array de máquinas del layout con todos los datos en el modal
function llenarMaquinas() {
  var maquinas = [];

  //Todas las filas de máquinas
  $.each($('#modalCargaControlLayout #tablaMaquinasLayouts > tbody > tr:not(.collapse)'), function(indice, fila) {
    var filaProgresivo = $('#modalCargaControlLayout #tablaMaquinasLayouts').find('#progresivo' + $(this).attr('id'));

    var progresivo = "";

    //Si tiene progresivo
    if (filaProgresivo.length) {
      var nombre_progresivo = filaProgresivo.find('.nombre_progresivo');
      var tipo = filaProgresivo.find('.tipo_progresivo');
      var maximo = filaProgresivo.find('.maximo_progresivo');
      var porc_recuperacion = filaProgresivo.find('.recuperacion_progresivo');

      progresivo = {
        id_progresivo: filaProgresivo.attr('data-progresivo'),
        nombre_progresivo: {
          valor: nombre_progresivo.val(),
          correcto: nombre_progresivo.attr('data-original') == nombre_progresivo.val(),
        },
        individual:{
          valor: tipo.val(),
          correcto: tipo.attr('data-original') == tipo.val(),
        },
        maximo: {
          valor: maximo.val(),
          correcto: maximo.attr('data-original') == maximo.val(),
        },
        porc_recuperacion: {
          valor: porc_recuperacion.val(),
          correcto: porc_recuperacion.attr('data-original') == porc_recuperacion.val(),
        }
      } // JSON progresivo
     } //if filaProgresivo.length

    var niveles = [];

    //Recorrer todos los niveles
    $.each(filaProgresivo.find('.tablaNivelProgresivo > tbody > tr'), function(i) {
      var nro_nivel = $(this).find('.nro_nivel');
      var nombre_nivel = $(this).find('.nombre_nivel');
      var base = $(this).find('.base_nivel');
      var porc_visible = $(this).find('.porc_visible');
      var porc_oculto = $(this).find('.porc_oculto');

      var nivel = {
        nro_nivel: {
          valor: nro_nivel.val(),
          correcto: nro_nivel.attr('data-original') == nro_nivel.val(),
        },
        nombre_nivel: {
          valor: nombre_nivel.val(),
          correcto: nombre_nivel.attr('data-original') == nombre_nivel.val(),
        },
        base: {
          valor: base.val(),
          correcto: base.attr('data-original') == base.val(),
        },
        porc_visible: {
          valor: porc_visible.val(),
          correcto: porc_visible.attr('data-original') == porc_visible.val(),
        },
        porc_oculto: {
          valor: porc_oculto.val(),
          correcto: porc_oculto.attr('data-original') == porc_oculto.val(),
        }
      }

      niveles.push(nivel);
    }); //foreach niveles

    //Datos de la máquina
    var nro_admin = $(this).find('.nro_admin');
    var nro_isla = $(this).find('.nro_isla');
    var marca = $(this).find('.marca');
    var juego = $(this).find('.juego');
    var nro_serie = $(this).find('.nro_serie');
    var check_notoma = $(this).find('.check_notoma');
    var den_sala = $(this).find('.den_sala');
    var porc_dev = $(this).find('.porc_dev');

    var maquina = {
      id_maquina: $(this).attr('id'),
      nro_admin: {
        valor: nro_admin.val(),
        correcto: nro_admin.attr('data-original') == nro_admin.val(),
      },
      nro_isla: {
        valor: nro_isla.val(),
        correcto: nro_isla.attr('data-original') == nro_isla.val(),
      },
      marca: {
        valor: marca.val(),
        correcto: marca.attr('data-original') == marca.val(),
      },
      juego: {
        valor: juego.val(),
        correcto: juego.attr('data-original') == juego.val(),
      },
      nro_serie: {
        valor: nro_serie.val(),
        correcto: nro_serie.attr('data-original') == nro_serie.val(),
      },
      no_toma: check_notoma.is(':checked') ? 1 : 0,
      denominacion: den_sala.val(),
      porcentaje_dev: porc_dev.val(),

      //Datos de progresivo
      progresivo: progresivo,

      //Niveles
      niveles: niveles,
    } //var maquina

    maquinas.push(maquina);

  }); //foreach
  
  return maquinas;
}

function revisarCargaCompleta() {
  var cargaCompleta = true;

  $.each($('#modalCargaControlLayout #tablaMaquinasLayouts > tbody tr'), function(){
    //Si toma, los inputs deben estar cargados
    if (!$(this).find('.check_notoma').is(':checked')) {
      //Si los inputs están incompletos
      if ($(this).find('.den_sala').val() == '' || $(this).find('.porc_dev').val() == '' ) {
        cargaCompleta = false;
      }
    }
    //Para salir del each cuando encuentre campos incompletos
    return cargaCompleta;
  });

  return cargaCompleta;
}

//Finalizar la carga de layout parcial
$('#modalCargaControlLayout #btn-finalizar').click(function(){
  //Si la carga está completa MANDAR LOS DATOS
  if (revisarCargaCompleta()) {
    var maquinas = llenarMaquinas();
    var id_layout_parcial = $('#modalCargaControlLayout #id_layout_parcial').val();

    var formData = {
      id_layout_parcial: id_layout_parcial,
      fiscalizador_toma: $('#modalCargaControlLayout #inputFisca').obtenerElementoSeleccionado(),
      tecnico: $('#modalCargaControlLayout #tecnico').val(),
      fecha_ejecucion: $('#modalCargaControlLayout #fecha_ejecucion').val(),
      maquinas: maquinas,
      observacion: $('#modalCargaControlLayout #observacion_carga').val(),
    }
    
    $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
    $.ajax({
      type: "POST",
      url: 'layout_parcial/cargarLayoutParcial',
      data: formData,
      dataType: 'json',
      success: function (data) {
        $('#mensajeExito h3').text('ÉXITO DE CARGA');
        $('#mensajeExito .cabeceraMensaje').addClass('modificar');
        $('#mensajeExito p').text("Se ha cargado correctamente el control de Layout Parcial.");

        $('#modalCargaControlLayout').modal('hide');

        $('#mensajeExito').show();

        var pageNumber = $('#herramientasPaginacion').getCurrentPage();
        var tam = $('#tituloTabla').getPageSize();
        var columna = $('#tablaLayouts .activa').attr('value');
        var orden = $('#tablaLayouts .activa').attr('estado');
        $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
      },
      error: function (error) {
        var response = JSON.parse(error.responseText);

        if(typeof response.fiscalizador_toma !== 'undefined'){
          mostrarErrorValidacion($('#inputFisca'),response.fiscalizador_toma[0] ,true );
        }
        if(typeof response.tecnico !== 'undefined'){
          mostrarErrorValidacion($('#tecnico') ,response.tecnico[0],true);
        }
        if(typeof response.fecha_ejecucion !== 'undefined'){
          mostrarErrorValidacion($('#fecha') ,response.fecha_ejecucion[0]  ,true );
        }

        var i = 0;
        $('#tablaMaquinasLayouts tbody tr').each(function() {
          if(typeof response['maquinas.'+ i +'.marca.valor'] !== 'undefined'){
            filaError = i;
            mostrarErrorValidacion($(this).find('.marca') ,response['maquinas.'+ i +'.marca.valor'][0] ,false);
          }
          if(typeof response['maquinas.'+ i +'.nro_isla.valor'] !== 'undefined'){
            filaError = i;
            mostrarErrorValidacion($(this).find('.nro_isla') , response['maquinas.'+ i +'.nro_isla.valor'][0],false);
          }
          if(typeof response['maquinas.'+ i +'.juego.valor'] !== 'undefined'){
            filaError = i;
            mostrarErrorValidacion($(this).find('.juego'), response['maquinas.'+ i +'.juego.valor'][0],false);
          }
          if(typeof response['maquinas.'+ i +'.porcentaje_dev.valor'] !== 'undefined'){
            filaError = i;
            mostrarErrorValidacion($(this).find('.porc_dev'), response['maquinas.'+ i +'.porcentaje_dev.valor'][0],false);
          }
          if(typeof response['maquinas.'+ i +'.denominacion.valor'] !== 'undefined'){
            filaError = i;
            mostrarErrorValidacion($(this).find('.den_sala'), response['maquinas.'+ i +'.denominacion.valor'][0],false);
          }

          i++;
        })
      },
    }); // $.ajax
  }
  //Si no, mostrar mensajes!
  else {
    $.each($('#modalCargaControlLayout #tablaMaquinasLayouts tbody tr'), function(indice, fila) {
      //Si toma, los inputs deben estar cargados
      if (!$(this).find('.check_notoma').is(':checked')) {
        //Si los inputs están incompletos dar ALERTA
        if ($(this).find('.den_sala').val() == '') $(this).find('.den_sala').addClass('alerta');
        if ($(this).find('.porc_dev').val() == '') $(this).find('.porc_dev').addClass('alerta');
      }
    });
  }
});

//Check de no toma que habilita o deshabilita la denominacion y porcentaje_dev
$('#modalCargaControlLayout').on('change','.check_notoma', function(){
  var den_sala = $(this).parent().parent().find('.den_sala');
  var porc_dev = $(this).parent().parent().find('.porc_dev');

  den_sala.removeClass('alerta');
  porc_dev.removeClass('alerta');

  if (this.checked) {
    den_sala.val('').prop('disabled',true);
    porc_dev.val('').prop('disabled',true);
  }
  else {
    den_sala.prop('disabled',false);
    porc_dev.prop('disabled',false);
  }
});

//Sacar alerta de los inputs al darle foco
$('#modalCargaControlLayout').on('focus change','input.alerta',function(){
  $(this).removeClass('alerta');
});

//Validar el relevamiento de layout parcial
$('#btn-validarRelevamiento').click(function(){ //metodo de validar
  var formData = {
    observacion_validacion: $('#observacion_validacion').val(),
    id_layout_parcial: $('#id_layout_parcial').val(),
  }
  
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  $.ajax({
    type: "POST",
    url: 'layout_parcial/validarLayoutParcial',
    data: formData,
    dataType: 'json',
    success: function (data) {
      //Una vez validido disparo evento buscar con fecha descendentemente
      $('#modalValidarControl').modal('hide');

      $('#mensajeExito h3').text('ÉXITO DE VALIDACIÓN');
      $('#mensajeExito .cabeceraMensaje').removeClass('modificar');
      $('#mensajeExito p').text("Se ha validado correctamente el control de Layout Parcial.");
      $('#mensajeExito').show();

      var pageNumber = $('#herramientasPaginacion').getCurrentPage();
      var tam = $('#tituloTabla').getPageSize();
      var columna = $('#tablaLayouts .activa').attr('value');
      var orden = $('#tablaLayouts .activa').attr('estado');
      $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);    
    },
    error: function (error) {
      var response = JSON.parse(error.responseText);

      if(typeof response.observacion_validacion !== 'undefined'){
        mostrarErrorValidacion($('#observacion_validacion'),response.observacion_validacion[0] ,true );
      }
    },
  });
});


//Opacidad del modal al minimizar
$('#btn-minimizarCargar').click(function(){
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//Opacidad del modal al minimizar
$('#btn-minimizarValidar').click(function(){
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

$(document).on('keypress','.inputLayout', function(e){
  if(e.which == 13) {
    $(this).blur();
  }
});

//Prueba doble click
$(document).on('dblclick','.inputLayout.modificable', function(e){
  if ($(this).prop('readonly')) {
    $(this).prop('readonly',false);
    $(this).css('border','2px solid orange');
  }
  else {
    $(this).blur();
    $(this).prop('readonly',true);
    $(this).css('border','1px solid #ccc');
    $(this).val($(this).attr('data-original')); //Si se arrepiente de modificación se setea el valor original
  }
  clearSelection();
});

function clearSelection(){
  if(document.selection && document.selection.empty) {
    document.selection.empty();
  } else if(window.getSelection) {
    var sel = window.getSelection();
    sel.removeAllRanges();
  }
}

$(document).on('click','.pop',function(e){
  //e.preventDefault(); reestablece funcionamiento de layaout gestionar 
 //estos era util para obtener info
  var fila = $(this).parent().parent();
  $('.pop').not(this).popover('hide');
  
  if ($(this).next('div.popover:visible').length){
    $(this).popover('hide');
  }else{
    $(this).popover('show');
  }
});

// cambia el el nombre del juego dentro de los valores posibles del paquete
$(document).on('click','.seleccionJuego',function(e){
  e.preventDefault();
 //estos era util para obtener info
  var fila = $(this).parent().parent().parent().parent().parent();
  var nombre_juego=$(this).val();
  $('.pop').not(this).popover('hide');
  $(this).popover('show');
  fila.children().find('.juego').val(nombre_juego)
});
