import '/js/Components/inputFecha.js';
import '/js/Components/FiltroTabla.js';
import '/js/Components/modalEliminar.js';
import {AUX} from "/js/Components/AUX.js";

$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Autoexcluidos');
  
  const input_fecha_iso = {
    language: "es",
    todayBtn: 1,
    autoclose: 1,
    todayHighlight: 1,
    format: "yyyy-mm-dd",
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true,
  };

  $("#dtpFechaAutoexclusionEstado").datetimepicker(input_fecha_iso);
  $("#dtpFechaNacimiento").datetimepicker(input_fecha_iso);
  $("#fecha_nacimiento,#fecha_autoexclusion").off("focus keydown keyup"); //Saco los evento de DTP que genera problemas si quiero chequear
  $('[data-js-setear-plataforma]').each(function(){
    const $t = $(this);
    $t.on('change',function(){
      const $plat = $($t.attr('data-js-setear-plataforma'));
      $plat.val($t.find('option:selected').attr('data-id_plataforma') ?? '');
    }).trigger('change');
  });

  $('[data-js-filtro-tabla]').on('busqueda',function(e,ret,tbody,molde){
    ret.data.forEach(function(ae){
      tbody.append(generarFilaTabla(molde,ae));
    });
    tbody.find('.pop').popover({
      html:true
    });
  }).trigger('buscar');

  $('[data-js-tabs]').each(function(_,tab_group_obj){
    const tab_group = $(tab_group_obj);
    tab_group.find('[data-js-tab]').each(function(__,tobj){
      const tab = $(tobj);
      const target = $(tab.attr('data-js-tab'));
      tab.on('mostrar.tab',function(e,mostrar){
        tab.toggleClass('active',mostrar);
        target.toggle(mostrar);  
      }).on('click',function(e){
        tab_group.find('[data-js-tab]').trigger('mostrar.tab',false);
        tab.trigger('mostrar.tab',true);
      });
    }).eq(0).click();
  });

  $('[data-js-modal-ver-cargar-ae]').each(function(_,m_obj){
      const M = $(m_obj);

      const setImportacion = function(name,tipo,external_filename){
        const modo = M.attr('data-modo');
        const file_picker = M.find(`[name="${name}"]`);
        const file_clear = M.find(`[data-js-click-file-clear="${name}"]`);
        const external_link = M.find(`[data-file-external-link="${name}"]`);
        const internal_link = M.find(`[data-file-internal-link="${name}"]`);

        const oldUrl = internal_link.data('blobUrl');
        if (oldUrl) {
          URL.revokeObjectURL(oldUrl);
          internal_link.removeData('blobUrl');
        }

        if(tipo == 'FILEPICK'){
          file_picker.show();
          if(file_picker.length){
            file_picker[0].value = null;
          }
          file_clear.hide();
          external_link.text('').hide();

          internal_link.removeAttr('href').text('').hide();
          return;
        }
        if(tipo == 'INTERNAL'){
          file_picker.hide();//No se limpia el file_picker para que se mande en el post
          file_clear.toggle(modo != 'VER');
          external_link.text('').hide();

          if(file_picker[0]?.files?.length){
            const file = file_picker[0].files[0];
            const url = URL.createObjectURL(file);

            internal_link
            .data('blobUrl',url)
            .attr('href',url)
            .attr('target','_blank')
            .text(file.name)
            .show();
          }
          return;
        }
        if(tipo == 'EXTERNAL'){
          file_picker.hide();
          if(file_picker.length){
            file_picker[0].value = null;
          }
          file_clear.toggle(modo != 'VER');
          external_link.text(external_filename).data('filename',external_filename).show()
          internal_link.removeAttr('href').text('').hide();
          return;
        }

        throw 'setImportacion: tipo no manejado | '+tipo;
      };
      
      const fill = function(div,prefix,obj){//@HACK @TODO: mover a AUX
        const subscript = function(s){
          return prefix === null? s : (prefix+'['+s+']');
        };
        for(const k in obj){
          const val = obj[k];
          if(typeof val == 'object'){
            fill(div,subscript(k),val);
          }
          else{
            const name = subscript(k);
            const named_obj = div.find(`[name="${name}"]`);
            if(named_obj.is('input[type="file"]')){
              if(val !== null){
                setImportacion(name,'EXTERNAL',val);
              }
              else{
                setImportacion(name,'FILEPICK',null);
              }
            }
            else{
              named_obj.val(val);
            }
          }
        }
      };
      
      const fillError = function(div,obj){//@HACK @TODO: mover a AUX
        for(const k in obj){
          const val = obj[k];
          if(typeof val == 'object' && !Array.isArray(val)){
            console.log(k,val,'Valor inesperado');
          }
          else{
            const name_arr = k.split('.');
            let name = name_arr?.[0] ?? '';
            for(let idx=1;idx<name_arr.length;idx++){
              name+='['+name_arr[idx]+']';
            }
            mostrarErrorValidacion(div.find(`[name="${name}"]`),Array.isArray(val)? val.join(', ') : val,true);
          }
        }
      };
      
      const filterFunction = function(attr){
        const check_params = {
          modo: M.attr('data-modo'),
          resolucion: M.find('[name="estado_resolucion"]').val()
        };
              
        return function(_,r_obj){
          let json_rdata = null;
          try{
            json_rdata = JSON.parse($(r_obj).attr(attr));
          }
          catch(error){
            console.log(r_obj,json_rdata);
            throw error;
          }
          
          if(!Array.isArray(json_rdata)){
            console.log(r_obj,json_rdata);
            throw 'Valor inesperado de "'+$(r_obj).attr(attr)+'" se esperaba un arreglo de objetos';
          }
          for(const obj of json_rdata){
            if(typeof obj !== 'object'){
              console.log(r_obj,obj);
              throw 'Valor inesperado de "'+$(r_obj).attr(attr)+'" se esperaba un arreglo de objetos';
            }
            let all_true = true;
            for(const param in check_params){
              const check_val = check_params[param];
              const obj_val = obj[param] ?? undefined;
              all_true = all_true && (obj_val == '*' || obj_val === check_val || obj_val === undefined);
            }
            if(all_true) return true;
          }
          return false;
        };
      };
          
      const setReadonly = function(){
        const setReadOnlyObj = function(state){
          return function(_,r_obj){
            const r = $(r_obj);
            const f = r.children('[data-js-fecha]');
            if(f.length){
              f[0].readonly(state);  
            }
            else{
              if(state){ r.attr('readonly',true);  }
              else{      r.removeAttr('readonly'); }
            }
          };
        }
        
        M.find('[data-readonly]:not([data-js-fecha])')
        .each(setReadOnlyObj(false))
        .filter(filterFunction('data-readonly'))
        .each(setReadOnlyObj(true));
      }
      
      const setVisible = function(){
        M.find('[data-mostrar]').hide().filter(filterFunction('data-mostrar')).show();
      }

      M.find('[data-js-change-calcular-fechas]').change(function(e){
        const tgt = $(e.currentTarget);

        const params_basicos = {
          id_autoexclusion: M.find('[name="id_autoexclusion"]').val(),
          nro_dni: M.find('[name="nro_dni"]').val(),
          estado_fecha: M.find('[name="estado_fecha"]').val(),
        };

        const params_983 = {
          estado_fecha_finalizacion: M.find('[name="estado_fecha_finalizacion"]').val()
        };

        const params_494 = {
          estado_años_adicionales: M.find('[name="estado_años_adicionales"]').val(),
          estado_alcance_interjurisdiccional: M.find(['[name="estado_alcance_interjurisdiccional"]']).val()
        };

        //Primero se mandan los atributos basicos, se setea lo retornado
        //Luego se setean los particulares a cada resolucion y se reenvia para recalcular
        //Esto es para evitar errores de validacion de la API (ej enviar 983 con años_adicionales)

        const url = tgt.attr('data-js-change-calcular-fechas');
        const handleErr = function(err){
          $('[data-js-modal-procesando]').modal('hide');
          const json = err?.responseJSON ?? {};
          AUX.mensajeError(json?.mensaje ?? json?.message ?? '');
          fillError(M.find('form'),json);
        };

        ocultarErrorValidacion(M.find('[name]'));
        $('[data-js-modal-procesando]').modal('show');
        AUX.GET(url,params_basicos,
          function(fechas1){
            let params = null;
            switch(fechas1.estado_resolucion){
              case "983":{
                params = {...params_basicos,...params_983};
              }break;
              case "494":{
                params = {...params_basicos,...params_494};
              }break;
              default:{
                throw "Tipo de resolucion sin manejar: "+fechas1.estado_resolucion;
              }break;
            }

            AUX.GET(url,params,
              function(fechas2){
                for(const k in fechas2){
                  const domobj = M.find(`[name="${k}"]`);
                  domobj.val(fechas2[k]);
                  if(!domobj.attr('data-js-change-calcular-fechas')){
                    domobj.change();
                  }
                }
                render(undefined,undefined,true);
                $('[data-js-modal-procesando]').modal('hide');
              },
              handleErr
            );
          },
          handleErr
        );
      });

      M.find('[data-js-change-aproximar-fecha_nacimiento]').change(function(e){
        const modo = M.attr('data-modo');

        if(modo != 'NUEVO'){
          return;
        }

        const ya_cargo = M.find('[name="nombres"],[name="apellidos"],[name="sexo"]').filter(function(_,domobj){
	        return $(domobj).val().length > 0
        }).length > 0;

        if(ya_cargo){
          return;
        }

        const dni = parseInt(M.find('[name="nro_dni"]').val());
        if(isNaN(dni)){
          return;
        }

        //Obtenido regresionando con Median
        // 28/10/2026 Octavio
        const segundos = dni * 44.4661 - 922908824.8301;//Ver abajo de todo de donde saco estos valores
        const fecha = new Date(segundos * 1000);
        M.find('[name="fecha_nacimiento"]').closest('[data-js-fecha]').data("datetimepicker").setDate(fecha);
      });

      M.find('form input[type="file"]').change(function(e){
        const filepicker = $(e.currentTarget);
        const name = filepicker.attr('name');
        setImportacion(name,'INTERNAL',null);
      });

      M.find('[data-js-click-file-clear]').click(function(e){
        const file_clear = $(e.currentTarget);
        const name = file_clear.attr('data-js-click-file-clear');
        setImportacion(name,'FILEPICK',null);
      });

      M.find('[data-file-external-link]').click(function(e){
        e.preventDefault();
        const tgt = $(e.currentTarget);
        const params = new URLSearchParams({
          id_autoexcluido: M.find('[name="id_autoexcluido"]').val(),
          tipo_archivo: tgt.attr('data-file-external-link')
        });
        window.open(tgt.attr('href')+'?'+params.toString(),'_blank');
      })
            
      const render = function(ae,historial,mantener_historial = false){
        const form = M.find('form');
        M.find('input[type="file"]').show().each(function(_,fp_dom){
          const name = $(fp_dom).attr('name');
          M.find(`[data-js-click-file-clear="${name}"]`).hide();
          M.find(`[data-file-external-link="${name}"]`).text('').hide();
          M.find(`[data-file-internal-link="${name}"]`).text('').hide();
        });
        
        if(ae !== undefined){
          fill(M,null,ae ?? {});
        }
        setReadonly();
        setVisible();  
        
        (mantener_historial?
           M.find('[data-js-select-historial]')
         : M.find('[data-js-select-historial]').empty())
         .append(
          (historial ?? []).map(function(h,hidx){
            const o = $('<option>');
            o.val(h.id_autoexcluido);
            o.text(h.created_by + ' - '+h.created_at);
            o.data('canon',h);
            return o;
          })
        );

        M.find('[data-js-tab]').eq(0).click();
      };
      
      M.find('[data-js-select-historial]').change(function(e){
        const tgt = $(e.currentTarget);
        render(tgt.find('option:selected').data('canon'),null,true);
      });
      
      M.on('mostrar.modal',function(e,url,id_autoexclusion,modo){      
        M.attr('data-modo',modo.toUpperCase());
        const form = M.find('form');
        form.find('[name]').val('');
        form.find('select option[default]').each(function(_,o){
          $(o).parent().val($(o).val());
        });
        
        if(modo !== 'NUEVO'){
          AUX.GET(url,{id_autoexclusion: id_autoexclusion},function(ae){       
            render(ae?.autoexcluido,ae?.historial,false);
            if(M.is(':hidden')){
              M.modal('show');
            }
          });
        }
        else {
          render({estado: "Pendiente de val.", estado_fecha: FECHA_API,origen: IDENTITY},[],false);
          if(M.is(':hidden')){
            M.modal('show');
          }
        }
      });
      
      M.find('[data-js-click-enviar]').click(async function(e){
        const tgt = $(e.currentTarget);
        const url = tgt.attr('data-js-click-enviar');
        const form = M.find('form');
        const entries = AUX.form_entries(form?.[0]);
                
        //@HACK @TODO: agregar funcionalidad a AUX para convertir objetos a FD
        const newfd = new FormData();//Necesito FormData si voy a mandar sin procesar (porque mando archivos)
        for(const name in entries){
          const file_picker = M.find(`input[type="file"][name="${name}"]`);
          if(file_picker.length){
            const external_link = M.find(`[data-file-external-link="${name}"]`);
            const internal_link = M.find(`[data-file-internal-link="${name}"]`);
            if(external_link.is(':visible') && !internal_link.is(':visible')){
              const filename = external_link.data('filename');
              if(filename === undefined){
                throw 'ERROR: No se pudo recuperar el filename del link externo';
              }
              newfd.append(name,'-');//- es invalido en base64 asi que sirve como flag para mantener
            }
            else if(!external_link.is(':visible') && internal_link.is(':visible')){
              if(file_picker[0]?.files?.length){
                const file = file_picker[0].files[0];
                newfd.append(name,file,file.name);
              }
              else{
                throw 'ERROR: No se puede recuperar el archivo del filepicker';
              }
            }
            else if(!external_link.is(':visible') && !internal_link.is(':visible')){
              newfd.append(name,'');
            }
            else{
              throw "ERROR: No puede haber link externo e interno visibles"
            }
          }
          else{
            newfd.append(name,entries[name]);
          }
        }
        ocultarErrorValidacion(M.find('[name]'));
        AUX.POST(url,newfd,
          function(data){
            AUX.mensajeExito('Guardado');
            M.modal('hide');
            $('[data-js-filtro-tabla]').trigger('buscar');
          },
          function(data){
            const json = data.responseJSON ?? {};
            AUX.mensajeError(json?.mensaje ?? json?.message ?? '');
            fillError($(form),json);
          },
          {
            contentType: false,
            processData: false,
          }
        );
      });
    });
});

function generarFilaTabla(molde,ae) {
  const fila = molde.clone();
  if(ae.es_primer_ae){
    fila.find('td').css('font-style','italic');
  }
  if(ae.deleted){
    fila.find('td').css('color','darkred');
  }
  
  fila.attr('data-id', ae.id_autoexclusion);
  fila.find('.id_autoexclusion').text(ae.id_autoexclusion).attr('title',ae.id_autoexclusion);
  fila.find('.origen').text(ae.origen).attr('title',ae.origen);
  fila.find('.nro_dni').text(ae.nro_dni).attr('title',ae.nro_dni);
  fila.find('.apellidos').text(ae.apellidos).attr('title',ae.apellidos);
  fila.find('.nombres').text(ae.nombres).attr('title',ae.nombres);
  fila.find('.estado_resolucion	').text(ae.estado_resolucion	).attr('title',ae.estado_resolucion);
  fila.find('.estado').text(ae.estado).attr('title',ae.estado);

  //Lo cambio a otro formato porque es mas corto que entra en pantalla mas chicas
  const convertir_fecha = function(fecha){
    if(fecha === null) return '-';
    const yyyymmdd = fecha.split('-');
    return yyyymmdd[2] + '/' + yyyymmdd[1] + '/' + yyyymmdd[0].substring(2);
  }
  fila.find('.estado_fecha').text(convertir_fecha(ae.estado_fecha)).attr('title',ae.estado_fecha);
  fila.find('.estado_fecha_finalizable').text(convertir_fecha(ae.estado_fecha_finalizable)).attr('title',ae.estado_fecha_finalizable);
  fila.find('.estado_fecha_finalizable_vencimiento').text(convertir_fecha(ae.estado_fecha_finalizable_vencimiento)).attr('title',ae.estado_fecha_finalizable_vencimiento);
  fila.find('.estado_fecha_vencimiento').text(convertir_fecha(ae.estado_fecha_vencimiento)).attr('title',ae.estado_fecha_vencimiento);

  fila.find('button').val(ae.id_autoexclusion);

  if(!(CASINOS_USUARIO.includes(ae.origen) || PLATAFORMAS_USUARIO.includes(ae.origen))){
    fila.find('[data-origen-exclusivo]').remove();
  }
  
  fila.find('[data-visible]').each(function(_,obj){
    const o = $(obj);
    try{
      const visible_json = JSON.parse(o.attr('data-visible'));
      let visible = true;
      for(const k in visible_json){
        visible = visible && visible_json[k] == ae[k];
      }
      if(!visible){
        o.remove();
      }
    }
    catch (error){
      console.log(obj,error);
      o.remove();
    }
  });

  const renametext = {
    'Foto1': 'Foto #1',
    'Foto2': 'Foto #2',
    'Solicitud Ae': 'Solicitud AE',
    'Solicitud Revocacion': 'Solicitud Finalización',
    'Scandni': 'Scan DNI'
  };

  const ul = $('<ul>').css('text-align','left');
  for(const text in (IMPORTACIONES ?? {})){
    const name = IMPORTACIONES[text];
    const retext = renametext[text] ?? text;
    const item = $('<a>').attr('data-js-click-subir-archivo',JSON.stringify({
      tipo_archivo: name.replace('importacion_',''),
      nro_dni: ae.nro_dni,
      id_autoexclusion: ae.id_autoexclusion,
      titulo: retext
    }))
    .text(retext);

    const li = $('<li>').append(item);
    if(ae[name] !== null){//Marca de "ya subido", fuera del <a> para no ensuciar el titulo del modal
      li.append($('<span>').css('color','#00C853').text(' ✔'));
    }

    ul.append(li);
  }
  fila.find('[data-js-click-mostrar-popover-adjuntar]').attr('data-content',ul[0].outerHTML);
  fila.find('[data-js-click-mostrar-popover-adjuntar]').popover();
  
  let papel_destruido = (ae.papel_destruido_by != null || ae.papel_destruido_at != null)+0;
  if(['Melincué','Santa Fe','Rosario','CityCenter'].indexOf(ae.origen) == -1){//Tiene que ser físico
    papel_destruido = -1;//Si tiene plataforma, no puede tener papel, saco todo
  }
  
  fila.find('[data-papel-destruido]').filter(`[data-papel-destruido!="${papel_destruido}"]`).remove();
  const popover_content = $(`[data-js-molde-popover-papel][data-papel-destruido="${papel_destruido}"]`).clone()
  .removeAttr('data-js-molde-popover-papel');
  
  popover_content.find('.modificado').text(ae.created_by + ' ' + ae.created_at);
  popover_content.find('.destruido').text(ae.papel_destruido_by + ' ' + ae.papel_destruido_at);
  fila.find('[data-papel-destruido]').attr('data-content',popover_content?.[0]?.outerHTML);
  
  return fila;
}

//Opacidad del modal al minimizar
$("#btn-minimizar").click(function () {
  if ($(this).data("minimizar") == true) {
    $(".modal-backdrop").css("opacity", "0.1");
    $(this).data("minimizar", false);
  } else {
    $(".modal-backdrop").css("opacity", "0.5");
    $(this).data("minimizar", true);
  }
});

//Opacidad del modal al minimizar
$("#btn-minimizarMaquinas").click(function () {
  if ($(this).data("minimizar") == true) {
    $(".modal-backdrop").css("opacity", "0.1");
    $(this).data("minimizar", false);
  } else {
    $(".modal-backdrop").css("opacity", "0.5");
    $(this).data("minimizar", true);
  }
});

//Opacidad del modal al minimizar
$("#btn-minimizar-importar").click(function () {
  if ($(this).data("minimizar") == true) {
    $(".modal-backdrop").css("opacity", "0.1");
    $(this).data("minimizar", false);
  } else {
    $(".modal-backdrop").css("opacity", "0.5");
    $(this).data("minimizar", true);
  }
});

$("#columna input").focusin(function () {
  $(this).removeClass("alerta");
});

function modalAgregarEditarAE(dni, id_autoexcluido = null) {
  //vuelvo a step1
  $(".page").removeClass("active");
  $(".step1").addClass("active");

  $("#frmAgregarAE :input").val("");
  $("#hace_encuesta").prop("checked", true).change();
  ocultarErrorValidacion($("#frmAgregarAE :input"));
  //Limpio el texto de archivos y muestro el input
  //boton -> div (esconder) -> a (limpiar) -> div -> div -> input (mostrar)
  $("#frmAgregarAE .sacarArchivo")
    .parent()
    .hide()
    .find("a")
    .text("")
    .attr("href", "")
    .parent()
    .parent()
    .find("input")
    .show();

  //oculto el botón anterior
  $("#btn-prev").hide();

  //oculto botón guardar y muestro botón siguiente en caso que se encuentre oculto
  $("#btn-guardar").hide();
  $("#btn-next").show();

  //cargar select provincia
  cargarProvincias();

  //Restablezco los valores ininiciales de step
  $(".step").removeClass("finish");
  $(".step").removeClass("actived");
  $("#one").addClass("actived");

  $("#nro_dni").val(dni);
  $("#modalAgregarAE .modal-title").text("| AGREGAR AUTOEXCLUIDO");
  $("#modalAgregarAE").attr("modo", "agregar");
  $("#modalAgregarAE").attr("id-autoexcluido", -1);
  $("#id_estado_civil").val(6); //No contesta
  $("#id_capacitacion").val(6); //No contesta
  $("#id_ocupacion").val(12); //No contesta
  if (id_autoexcluido !== null) {
    $("#modalAgregarAE .modal-title").text("| EDITAR AUTOEXCLUIDO");
    $("#modalAgregarAE").attr("modo", "editar");
    $("#modalAgregarAE").attr("id-autoexcluido", id_autoexcluido);
    setTimeout(function () {
      $("#btn-next").click();
    }, 250);
  }
  //muestra modal
  $("#modalAgregarAE").modal("show");
}

//Botón ver formularios AE
$("#btn-ver-formularios-ae").click(function (e) {
  e.preventDefault();
  //muestra modal
  $("#modalFormulariosAE").modal("show");
});

$("#btn-descargar-ae").click(function (e) {
  e.preventDefault();
  window.open("autoexclusion/BDCSV", "_blank");
});

//Botón importar masivo
$('#btn-importar-masivo').click(function(e){
  e.preventDefault();
  $('#modalImportarMasivo').modal('show');
  $('#mensajeExitoImportacion').hide();
  $('#mensajeErrorImportacion').hide();
  $('#selectCasinoImportacion').val('');
  $('#archivoImportacion').val('');
  $('#archivoImportacionText').val('');
  //Todavía no se importó nada: cerrar el modal es cancelar.
  resetearBotonCerrarImportacion();
});

function resetearBotonCerrarImportacion(){
    $('#btn-cerrar-importar').removeClass('btn-successAceptar').addClass('btn-default').text('CANCELAR');
}

function marcarBotonCerrarComoFinalizar(){
    //btn-successAceptar es el estilo de acción principal de los modales (borde teal).
    $('#btn-cerrar-importar').removeClass('btn-default').addClass('btn-successAceptar').text('FINALIZAR');
}

// Setup CSRF for all ajax 
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
});

$('#btn-importar').click(function(e){
    e.preventDefault();
    const id_casino = $('#selectCasinoImportacion').val();
    const archivo = $('#archivoImportacion')[0].files[0];

    $('#mensajeExitoImportacion').hide();
    $('#mensajeErrorImportacion').hide();
    $('#listaErroresImportacion').empty();

    if(!id_casino || id_casino == ""){
        mostrarErrorImportacion("Debe seleccionar un Casino/Plataforma.");
        return;
    }
    if(!archivo){
        mostrarErrorImportacion("Debe subir un archivo.");
        return;
    }

    const formData = new FormData();
    formData.append('id_casino', id_casino);
    formData.append('archivo', archivo);

    $('#loadingImportacion').show();
    $('#btn-importar').prop('disabled', true);

    $.ajax({
        url: 'autoexclusion/importarMasivo',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(data){
            $('#loadingImportacion').hide();
            $('#btn-importar').prop('disabled', false);

            //El controlador responde 200 con {error:...} si la importación se cayó entera (rollback).
            if(data.error || !data.resumen){
                mostrarErrorImportacion(data.error || 'Respuesta inesperada del servidor.');
                return;
            }

            // Clean previous lists
            $('#detalleNuevas ul').empty();
            $('#detallePrevias ul').empty();
            $('#detalleVigentes ul').empty();
            $('#detalleCubiertas ul').empty();
            $('#detalleRepetidas ul').empty();
            
            // Function to populate generic lists
            const fillList = (selector, dataArr, countElement) => {
                if(dataArr && dataArr.length > 0){
                    $(selector).show();
                    $(countElement).text(dataArr.length);
                    dataArr.forEach(item => {
                        $(selector + ' ul').append($('<li>').text(item));
                    });
                } else {
                    $(selector).hide();
                }
            };

            fillList('#detalleNuevas', data.resumen.detalles_nuevas, '#detalleNuevas .badge');
            fillList('#detallePrevias', data.resumen.detalles_previas, '#detallePrevias .badge');
            fillList('#detalleVigentes', data.resumen.detalles_vigentes, '#detalleVigentes .badge');
            fillList('#detalleCubiertas', data.resumen.detalles_cubiertas, '#detalleCubiertas .badge');
            fillList('#detalleRepetidas', data.resumen.detalles_repetidas, '#detalleRepetidas .badge');
            
            $('#mensajeExitoImportacion').show();

            if(data.errores > 0 && data.detalle_errores && data.detalle_errores.length > 0){
                 $('#mensajeErrorImportacion').find('p').text("Algunos registros tuvieron errores:");
                 data.detalle_errores.forEach(err => {
                     $('#listaErroresImportacion').append($('<li>').text(err));
                 });
                 $('#mensajeErrorImportacion').show();
            }

            //Ya se importó: cerrar el modal a partir de acá es finalizar, no cancelar.
            marcarBotonCerrarComoFinalizar();
            $('[data-js-buscar]').trigger('click');
        },
        error: function(data){
             $('#loadingImportacion').hide();
             $('#btn-importar').prop('disabled', false);
             console.log(data);
             let msg = "Error del servidor.";
             if(data.responseJSON){
                if(data.responseJSON.id_casino && data.responseJSON.id_casino.length > 0) msg = data.responseJSON.id_casino[0]; // Validation array
                else if(data.responseJSON.archivo && data.responseJSON.archivo.length > 0) msg = data.responseJSON.archivo[0];
                else if(data.responseJSON.error) msg = data.responseJSON.error;
                else if(data.responseJSON.id_casino) msg = data.responseJSON.id_casino; 
             }
             mostrarErrorImportacion(msg);
        }
    });
});

function mostrarErrorImportacion(msg){
    $('#mensajeErrorImportacion').find('p').text(msg);
    $('#mensajeErrorImportacion').show();
}

//función para autocompletar el input de provincia
function cargarProvincias() {
  const options = {
    url: "/js/Autoexclusion/provincias.json",
    listLocation: "provincias",
    getValue: "nombre",
    list: {
      match: {
        enabled: true,
      },
      onChooseEvent: function () {
        cargarLocalidades();
        cargarLocalidadesVinculo();
      },
    },
  };

  $("#nombre_provincia").easyAutocomplete(options);
  $("#nombre_provincia_vinculo").easyAutocomplete(options);
}

//función para autocompletar el input de localidad de los datos personales
function cargarLocalidades() {
  const nombre_provincia = $("#nombre_provincia").val();
  const localidades = [];

  $.getJSON("/js/Autoexclusion/localidades.json", function (data) {
    $.each(data.localidades, function (key, value) {
      //si pertenece a la provincia seleccionada, la agrego
      if (value.provincia.nombre === nombre_provincia) {
        var localidad = {
          id: value.id,
          nombre: value.nombre,
        };
        localidades.push(localidad);
      }
    }); // close each()
  }); // close getJSON()

  const options = {
    data: localidades,
    getValue: "nombre",
    list: {
      match: {
        enabled: true,
      },
    },
  };

  $("#nombre_localidad").easyAutocomplete(options);
}

//función para autocompletar el input de localidad del contacto
function cargarLocalidadesVinculo() {
  const nombre_provincia = $("#nombre_provincia_vinculo").val();
  const localidades = [];

  $.getJSON("/js/Autoexclusion/localidades.json", function (data) {
    $.each(data.localidades, function (key, value) {
      //si pertenece a la provincia seleccionada, la agrego
      if (value.provincia.nombre === nombre_provincia) {
        var localidad = {
          id: value.id,
          nombre: value.nombre,
        };
        localidades.push(localidad);
      }
    }); // close each()
  }); // close getJSON()

  const options = {
    data: localidades,
    getValue: "nombre",
    list: {
      match: {
        enabled: true,
      },
    },
  };

  $("#nombre_localidad_vinculo").easyAutocomplete(options);
}

//función para actualizar fechas
$("#fecha_autoexlusion,#años_adicionales").change(function () {
  const iso = function(f){
    if(f === null) return '';
    const mes = f.getMonth()+1;
    const dia = f.getDate();
    return f.getFullYear() + (mes<10?'-0':'-') + mes + (dia<10?'-0':'-') + dia;
  }
  const fecha_autoexclusion = validarDTP($('#dtpFechaAutoexclusionEstado'));
  if(fecha_autoexclusion == null){
    $("#fecha_renovacion,#fecha_vencimiento_periodo,#fecha_cierre_definitivo" ).val("");
    return;
  }

  let qs = '?';
  qs+=encodeURIComponent('años_adicionales')+'='+encodeURIComponent($('#años_adicionales').val());
  qs+='&'+encodeURIComponent('alcance_interjurisdiccional')+'='+encodeURIComponent($('#alcance_interjurisdiccional').val());

  $.ajax({
    url: 'autoexclusion/generarEstado/' 
      + iso(fecha_autoexclusion) + '/' 
      + $('#nro_dni').val()
      + qs,
    type: 'GET',
    success: function(data){
      $('#resolucion').val(data?.resolucion ?? '');
      $('#primer_ae').val((!!data?.primer_ae)? 1 : 0);
      $("#fecha_renovacion" ).val(data?.fechas?.fecha_renovacion ?? '');
      $("#fecha_vencimiento_periodo" ).val(data?.fechas?.fecha_vencimiento  ?? '');
      $('#fecha_cierre_definitivo').val(data?.fechas?.fecha_cierre_ae ?? '');
      $('#fecha_cierre_minima').val(data?.fechas?.fecha_cierre_minima ?? '').parent().css("opacity", "0");
      $('#años_adicionales').val(data?.fechas?.años_adicionales ?? '').parent().css("opacity", "0");
      $('#alcance_interjurisdiccional').val(data?.alcance_interjurisdiccional ?? '').parent().css('opacity',"0");
      if(data?.resolucion == NOMBRE_NUEVA_RESOLUCION){
        $("#fecha_vencimiento_periodo").parent().css("opacity", "0");
        $("#fecha_renovacion").parent().css("opacity", "0");
        $('#fecha_cierre_minima').parent().css("opacity", "");
        $('#años_adicionales').parent().css("opacity", "");
        $('#alcance_interjurisdiccional').parent().css('opacity',"");
      }
      else if(data?.resolucion == '983' && (!!data?.primer_ae)){
        if(!!data?.primer_ae){
          $("#fecha_vencimiento_periodo").parent().css("opacity", "");
          $("#fecha_renovacion").parent().css("opacity", "");
        }
        else{
          $("#fecha_vencimiento_periodo").parent().css("opacity", "0");
          $("#fecha_renovacion").parent().css("opacity", "0");
        }
      }
      else{
        throw 'Resolucion '+data?.resolucion+' sin implementar';
      }
    },
    error: function(data){
      mensajeError('Error al generarle el estado');
    }
  });
});

$("#btn-prev").on("click", function () {
  //si no es el primero, tengo anterio
  if ($(".page.active").index() > 0) {
    //cambio form active
    $(".page.active").removeClass("active").prev().addClass("active");
    //cambio step active
    $(".step.actived")
      .removeClass("actived")
      .removeClass("finish")
      .prev()
      .addClass("actived");
    //oculto botón anterior
    $("#btn-prev").hide();
  }

  //si le dio al boton de anterior y la pagina activa (despues de darle al boton)
  //es del step #2 (index1) o step #3 (index2), muestro los botones de ANTERIOR
  //y SIGUIENTE, y oculto el de ENVIAR
  if ($(".page.active").index() == 1 || $(".page.active").index() == 2) {
    $("#btn-prev").show();
    $("#btn-next").show();
    $("#btn-guardar").hide();
  }
  setTimeout(function () {
    //@HACK: Elimino el delay que heredamos de barraNavegacion.js
    $("#btn-prev").prop("disabled", false);
  }, 250);
});
function mensajeError(msg) {
  $("#mensajeError .textoMensaje").empty();
  $("#mensajeError .textoMensaje").append($("<h4>" + msg + "</h4>"));
  $("#mensajeError").hide();
  setTimeout(function () {
    $("#mensajeError").show();
  }, 250);
}
function mensajeExito(msg) {
  $("#mensajeExito p").text(msg);
  $("#mensajeExito").hide();
  setTimeout(function () {
    $("#mensajeExito").show();
  }, 250);
}

function limpiarNull(val) {
  return val == null ? "" : val;
}
const to_iso = function (d, m, y) {
  //@HACK timezone de Argentina, supongo que esta bien porque el servidor esta en ARG
  return (
    y +
    (m < 10 ? "-0" : "-") +
    m +
    (d < 10 ? "-0" : "-") +
    d +
    "T00:00:00.000-03:00"
  );
};


$("#nro_dni").change(function () {
  const dni = parseInt($(this).val());
  //Obtenido regresionando segun arriba 07/04/2021 Octavio
  const segundos = dni * 44.8858 - 942979898.2748;
  const fecha = new Date(segundos * 1000);
  $("#dtpFechaNacimiento").data("datetimepicker").setDate(fecha);
});

function validarFechaIso(fecha) {
  const fecha_regexp_iterator = fecha.matchAll(
    /[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}/g
  );
  const valido_regexp = Array.from(fecha_regexp_iterator).length == 1;
  if (!valido_regexp) {
    return { err: 1, msg: "El formato tiene que ser AAAA-MM-DD" };
  }
  const f = fecha.split("-");
  const y = parseInt(f[0]),
    m = parseInt(f[1]),
    d = parseInt(f[2]);
  const date = new Date(to_iso(d, m, y));
  const hoy = new Date();
  if (date == "Invalid Date" || y <= 1900 || date >= hoy) {
    return { err: 1, msg: "Valor inválido" };
  }
  return { err: 0, date: new Date(to_iso(d, m, y)) };
}

function validarDTP(dtp) {
  const input = dtp.find("input");
  ocultarErrorValidacion(input);
  const fecha = input.val();
  //el change() de mas abajo triggerea un change() que hace que se llame de vuelta
  //escapo la recursion asi...
  if (fecha == null || fecha.length == 0) return;
  const err_ret = validarFechaIso(fecha);
  if (err_ret.err != 0) {
    dtp.data("datetimepicker").reset(); //triggerea change()!
    setTimeout(function () {
      mostrarErrorValidacion(input, err_ret.msg, true);
    }, 250);
    return null;
  }
  dtp.data("datetimepicker").setDate(err_ret.date);
  return err_ret.date;
}

$("#fecha_nacimiento").change(function () {
  validarDTP($("#dtpFechaNacimiento"));
});

function validarDNI() {
  if (isNaN($("#nro_dni").val()) && $("#nro_dni").val() != "") {
    mostrarErrorValidacion(
      $("#nro_dni"),
      "El número de DNI debe ser un dato de tipo numérico",
      false
    );
    return 0;
  }
  let valid = 1;
  //si existe dni, precargo el formulario con los datos
  let url = "/autoexclusion/existeAutoexcluido/" + $("#nro_dni").val();
  if ($("#modalAgregarAE").attr("modo") == "editar") {
    url =
      "/autoexclusion/buscarAutoexcluido/" +
      $("#modalAgregarAE").attr("id-autoexcluido");
  }
  $.ajax({
    url: url,
    async: false,
    type: "GET",
    success: function (data) {
      if ($("#modalAgregarAE").attr("modo") != "editar") {
        if (data['id'] > 0) {
          //ID
          $("#modalAgregarAE").modal("hide");
          mensajeError("Autoexcluido ya cargado y en vigencia");
          valid = 0;
          setTimeout(function () {
            mostrarAutoexcluido(data);
          }, 500);
        } else if (data['id']  == "0") {
          //AE nuevo, muestro las fechas de renovacion/vencimiento si es 983
          if(data['resolucion'] == NOMBRE_NUEVA_RESOLUCION){
            $("#fecha_vencimiento_periodo").parent().css("opacity", "0");
            $("#fecha_renovacion").parent().css("opacity", "0");
            $('#fecha_cierre_minima').parent().css("opacity", "");
            $('#años_adicionales').parent().css("opacity", "");
            $('#alcance_interjurisdiccional').parent().css('opacity',"");
          }
          else if(data['resolucion'] == '983'){
            $("#fecha_vencimiento_periodo").parent().css("opacity", "");
            $("#fecha_renovacion").parent().css("opacity", "");
            $('#fecha_cierre_minima').parent().css("opacity", "0");
            $('#años_adicionales').parent().css("opacity", "0");
            $('#alcance_interjurisdiccional').parent().css('opacity',"0");
          }
          else{
            throw 'Error: Resolución desconocida: ' + data['resolucion'];
          }

          $("#id_estado").val($("#id_estado option:first").val());
          $('#resolucion').val(data['resolucion']);
          $('#primer_ae').val(1);
        } else if (data['id']  < 0) {
          //El AE es cargable pero ya tuvo uno, le escondo las fechas de renovacion/vencimiento
          if(data['resolucion'] == NOMBRE_NUEVA_RESOLUCION){
            $("#fecha_vencimiento_periodo").parent().css("opacity", "0");
            $("#fecha_renovacion").parent().css("opacity", "0");
            $('#fecha_cierre_minima').parent().css("opacity", "");
            $('#años_adicionales').parent().css("opacity", "");
            $('#alcance_interjurisdiccional').parent().css('opacity',"");
          }
          else if(data['resolucion'] == '983'){
            $("#fecha_vencimiento_periodo").parent().css("opacity", "0");
            $("#fecha_renovacion").parent().css("opacity", "0");
            $('#fecha_cierre_minima').parent().css("opacity", "0");
            $('#años_adicionales').parent().css("opacity", "0");
            $('#alcance_interjurisdiccional').parent().css('opacity',"0");
          }
          else{
            throw 'Error: Resolución desconocida: ' + data['resolucion'];
          }

          $("#id_estado").val($("#id_estado option:first").val());
          $('#resolucion').val(data['resolucion']);
          $('#primer_ae').val(0);
        }
      }
      else {
        $("#apellido").val(data.autoexcluido.apellido);
        $("#nombres").val(data.autoexcluido.nombres);
        $("#fecha_nacimiento").val(data.autoexcluido.fecha_nacimiento);
        $("#id_sexo").val(data.autoexcluido.id_sexo);
        $("#id_estado_civil").val(data.autoexcluido.id_estado_civil);
        $("#domicilio").val(data.autoexcluido.domicilio);
        $("#nro_domicilio").val(data.autoexcluido.nro_domicilio);
        $("#nombre_provincia").val(data.autoexcluido.nombre_provincia);
        $("#nombre_localidad").val(data.autoexcluido.nombre_localidad);
        $("#telefono").val(data.autoexcluido.telefono);
        $("#correo").val(data.autoexcluido.correo);
        $("#id_ocupacion").val(data.autoexcluido.id_ocupacion);
        $("#id_capacitacion").val(data.autoexcluido.id_capacitacion);

        if (data.datos_contacto != null) {
          $("#nombre_apellido").val(data.datos_contacto.nombre_apellido);
          $("#domicilio_vinculo").val(data.datos_contacto.domicilio);
          $("#nombre_provincia_vinculo").val(
            data.datos_contacto.nombre_provincia
          );
          $("#nombre_localidad_vinculo").val(
            data.datos_contacto.nombre_localidad
          );
          $("#telefono_vinculo").val(data.datos_contacto.telefono);
          $("#vinculo").val(data.datos_contacto.vinculo);
        }

        //precargo el step del estado
        if (data.estado.id_casino != null)
          $("#id_casino").val(data.estado.id_casino);
        else if (data.estado.id_plataforma != null)
          $("#id_casino").val(-data.estado.id_plataforma);

        $("#id_estado").val(data.estado.id_nombre_estado);
        
        // Guardar estado original para verificar si cambia manualmente
        $("#estado_original_id").val(data.estado.id_nombre_estado);
        $("#estado_original_texto").val($("#id_estado option:selected").text());
        
        // Restricción: Si no es superusuario, deshabilitar o bloquear cambios manuales de estado
        if ($("#es_superusuario").val() == "0") {
            $("#id_estado").prop("disabled", true);
        } else {
            $("#id_estado").prop("disabled", false);
        }

        $('#resolucion').val(data.estado.resolucion);
        $('#primer_ae').val(data.es_primer_ae? 1 : 0);

        $("#fecha_autoexlusion").val(data.estado.fecha_ae);
        $("#fecha_cierre_definitivo").val(data.estado.fecha_cierre_ae);

        if(data.estado.resolucion == NOMBRE_NUEVA_RESOLUCION){
          $("#fecha_vencimiento_periodo").parent().css("opacity", "0");
          $("#fecha_vencimiento_periodo").val('');
          $("#fecha_renovacion").parent().css("opacity", "0");
          $("#fecha_renovacion").val('');
          $('#fecha_cierre_minima').parent().css("opacity", "");
          $('#fecha_cierre_minima').val(data.estado.fecha_cierre_minima);
          $('#años_adicionales').parent().css("opacity", "");
          $('#años_adicionales').val(data.estado.años_adicionales);
          $('#alcance_interjurisdiccional').parent().css('opacity',"");
          $('#alcance_interjurisdiccional').val(data.estado.alcance_interjurisdiccional);
        }
        else if(data.estado.resolucion == '983'){
          $('#fecha_cierre_minima').parent().css("opacity", "0");
          $('#fecha_cierre_minima').val('');
          $('#años_adicionales').parent().css("opacity", "0");
          $('#años_adicionales').val('');
          $('#alcance_interjurisdiccional').parent().css('opacity',"0");
          $('#alcance_interjurisdiccional').val('');

          if(data.es_primer_ae){
            $("#fecha_vencimiento_periodo").parent().css("opacity", "");
            $("#fecha_renovacion").parent().css("opacity", "");
            $("#fecha_vencimiento_periodo").val(data.estado.fecha_vencimiento);
            $("#fecha_renovacion").val(data.estado.fecha_renovacion);
          }
          else{
            $("#fecha_vencimiento_periodo").parent().css("opacity", "0");
            $("#fecha_renovacion").parent().css("opacity", "0");
            $("#fecha_vencimiento_periodo").val("");
            $("#fecha_renovacion").val("");
          }
        }
        else{
          throw 'Error: Resolución desconocida: ' + data.estado.resolucion;
        }

        //precargo el step de la encuesta
        if (data.encuesta != null) {
          $('#hace_encuesta').prop('checked', true).change();
          $('#juego_preferido').val(data.encuesta.id_juego_preferido);
          $('#id_frecuencia_asistencia').val(data.encuesta.id_frecuencia_asistencia);
          $('#veces').val(data.encuesta.veces);
          $('#tiempo_jugado').val(data.encuesta.tiempo_jugado);
          $('#socio_club_jugadores').val(data.encuesta.club_jugadores);
          $('#conoce_plataformas').val(data.encuesta.conoce_plataformas);
          $('#utiliza_plataformas').val(data.encuesta.utiliza_plataformas);
          $('#juego_responsable').val(data.encuesta.juego_responsable);
          $('#autocontrol_juego').val(data.encuesta.autocontrol_juego);
          $('#como_asiste').val(data.encuesta.como_asiste);
          $('#recibir_informacion').val(data.encuesta.recibir_informacion);
          $('#medio_recepcion').val(data.encuesta.medio_recibir_informacion);
          $('#observaciones').val(data.encuesta.observacion);
        }
        else{
          $('#hace_encuesta').prop('checked',false).change();
        }

        if (data.importacion != null) {
          const textoFoto1 = $("#foto1").parent().find("div");
          const textoFoto2 = $("#foto2").parent().find("div");
          const textoDNI = $("#scan_dni").parent().find("div");
          const textoAE = $("#solicitud_autoexclusion").parent().find("div");
          const textoFIN = $("#solicitud_revocacion").parent().find("div");
          const textoCAR = $("#caratula").parent().find("div");
          const link =
            "/autoexclusion/mostrarArchivo/" +
            data.importacion.id_importacion +
            "/";
          textoFoto1
            .find("a")
            .text(limpiarNull(data.importacion.foto1))
            .attr("href", link + "foto1");
          textoFoto2
            .find("a")
            .text(limpiarNull(data.importacion.foto2))
            .attr("href", link + "foto2");
          textoDNI
            .find("a")
            .text(limpiarNull(data.importacion.scandni))
            .attr("href", link + "scandni");
          textoAE
            .find("a")
            .text(limpiarNull(data.importacion.solicitud_ae))
            .attr("href", link + "solicitud_ae");
          textoFIN
            .find("a")
            .text(limpiarNull(data.importacion.solicitud_revocacion))
            .attr("href", link + "solicitud_revocacion");
          textoCAR
            .find("a")
            .text(limpiarNull(data.importacion.caratula))
            .attr("href", link + "caratula");
          textoFoto1.toggle(data.importacion.foto1 != null);
          textoFoto2.toggle(data.importacion.foto2 != null);
          textoDNI.toggle(data.importacion.scandni != null);
          textoAE.toggle(data.importacion.solicitud_ae != null);
          textoFIN.toggle(data.importacion.solicitud_revocacion != null);
          textoCAR.toggle(data.importacion.caratula != null);
          $("#foto1").toggle(data.importacion.foto1 == null);
          $("#foto2").toggle(data.importacion.foto2 == null);
          $("#scan_dni").toggle(data.importacion.scandni == null);
          $("#solicitud_autoexclusion").toggle(
            data.importacion.solicitud_ae == null
          );
          $("#solicitud_revocacion").toggle(
            data.importacion.solicitud_revocacion == null
          );
          $("#caratula").toggle(data.importacion.caratula == null);
        }
      }
    },
    error: function (data) {
      console.log(data);
      mensajeError("Error al solicitar el DNI");
      valid = 0;
    },
  });

  return valid;
}

// Event listener para detectar cambios manuales en el estado
$("#id_estado").change(function () {
    const estado_original_id = $("#estado_original_id").val();
    
    // Si no hay estado original guardado (ej: alta nueva), o no cambió el estado, no hacer nada.
    if (estado_original_id === "" || $(this).val() == estado_original_id) return;
    
    // Si es superusuario y hace un cambio de estado en edición
    if ($("#es_superusuario").val() == "1" && $("#modalAgregarAE").attr("modo") == "editar") {
        $("#modalConfirmacionEstado").modal("show");
    }
});

// Confirmar cambio de estado
$("#btn-confirmar-estado").click(function() {
    $("#modalConfirmacionEstado").modal("hide");
    
    const estado_original_texto = $("#estado_original_texto").val();
    const estado_nuevo_texto = $("#id_estado option:selected").text();
    const nombre_usuario = $("#nombre_usuario").val();
    
    // formatear fecha actual
    const date = new Date();
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const strDate = day + '-' + month + '-' + year;
    
    const mensaje_obs = "El usuario " + nombre_usuario + " ha cambiado el estado de " + estado_original_texto + " a " + estado_nuevo_texto + " el dia " + strDate + ".\n";
    
    // Agregar mensaje a las observaciones
    const obs_actual = $("#observaciones").val();
    if (obs_actual.indexOf(mensaje_obs) === -1) {
        $("#observaciones").val(obs_actual + (obs_actual ? "\n" : "") + mensaje_obs);
    }
    
    // Actualizar el "original" al nuevo para que si vuelve a cambiar en la misma sesión, lo tome bien.
    $("#estado_original_id").val($("#id_estado").val());
    $("#estado_original_texto").val(estado_nuevo_texto);
});

// Cancelar cambio de estado
$("#btn-cancelar-estado, #modalConfirmacionEstado .close").click(function() {
    const estado_original_id = $("#estado_original_id").val();
    $("#id_estado").val(estado_original_id);
});

// Fix para el scroll de modales anidados: al cerrar el modal de confirmación, 
// devolverle la clase 'modal-open' al body porque el modal padre sigue abierto.
$('#modalConfirmacionEstado').on('hidden.bs.modal', function () {
    if ($('#modalAgregarAE').is(':visible')) {
        $('body').addClass('modal-open');
    }
});

function validarDatosPersonales() {
  let valid = 1;
  $(".step2 input[required],.step2 select[required]").each(function () {
    if ($(this).val() == "") {
      mostrarErrorValidacion(
        $(this),
        "El campo no puede estar en blanco",
        false
      );
      valid = 0;
    }
  });
  $(".step2 input[alpha]").each(function () {
    if (!/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚäëïöüÄËÏÖÜ]*$/.test($(this).val())) {
      mostrarErrorValidacion(
        $(this),
        "El campo no puede contener números",
        false
      );
      valid = 0;
    }
  });
  $(".step2 input[numeric]").each(function () {
    if (!/^[0-9\s]*$/.test($(this).val())) {
      mostrarErrorValidacion($(this), "El campo es numérico", false);
      valid = 0;
    }
  });
  $(".step2 input[email]").each(function () {
    if (!$(this).val().includes("@") && $(this).val().length > 0) {
      mostrarErrorValidacion($(this), "Correo invalido", false);
      valid = 0;
    }
  });
  $(".step2 input[data-size]").each(function () {
    const size = parseInt($(this).attr("data-size"));
    if (isNaN(size) || $(this).val().length > size) {
      mostrarErrorValidacion(
        $(this),
        "El campo tiene un máximo de " + size + " caracteres",
        false
      );
      valid = 0;
    }
  });
  return valid;
}

function validarExtensionArchivo(id) {
  if ($(id).val()) {
    let extension = $(id)[0].files[0].type;
    //@TODO: despues de hacer pruebas, quitar lo de PNG
    if (
      extension != "image/jpeg" &&
      extension != "image/png" &&
      extension != "application/pdf"
    ) {
      return 0;
    }
  }
  return 1;
}

function validarFechaImagenes() {
  let valid = 1;
  /*$(".step3 :input").each(function () {
    if ($(this).val() == "") {
      mostrarErrorValidacion(
        $(this),
        "El campo no puede estar en blanco",
        false
      );
      valid = 0;
    }
  });*/
  if (validarExtensionArchivo("#foto1") == 0) {
    mostrarErrorValidacion(
      $("#foto1"),
      "El tipo de archivo debe ser de tipo JPG o PDF",
      true
    );
    valid = 0;
  }
  if (validarExtensionArchivo("#foto2") == 0) {
    mostrarErrorValidacion(
      $("#foto2"),
      "El tipo de archivo debe ser de tipo JPG o PDF",
      true
    );
    valid = 0;
  }
  if (validarExtensionArchivo("#solicitud_autoexclusion") == 0) {
    mostrarErrorValidacion(
      $("#solicitud_autoexclusion"),
      "El tipo de archivo debe ser de tipo JPG o PDF",
      true
    );
    valid = 0;
  }
  if (validarExtensionArchivo("#scan_dni") == 0) {
    mostrarErrorValidacion(
      $("#scan_dni"),
      "El tipo de archivo debe ser de tipo JPG o PDF",
      true
    );
    valid = 0;
  }
  if (validarExtensionArchivo("#solicitud_revocacion") == 0) {
    mostrarErrorValidacion(
      $("#solicitud_revocacion"),
      "El tipo de archivo debe ser de tipo JPG o PDF",
      true
    );
    valid = 0;
  }
  if (validarExtensionArchivo("#caratula") == 0) {
    mostrarErrorValidacion(
      $("#caratula"),
      "El tipo de archivo debe ser de tipo JPG o PDF",
      true
    );
    valid = 0;
  }
  return valid;
}

//botón siguiente modal agregar ae
$("#btn-next").on("click", function () {
  const step = $(".page.active").index() + 1;
  let valid = 1;
  switch (step) {
    case 1:
      valid = validarDNI();
      break;
    case 2:
      valid = validarDatosPersonales();
      break;
    case 3:
      valid = validarFechaImagenes();
      break;
    default:
      return;
  }

  //verifico que el step sea valido para avanzar y, si no es el último step, tengo siguiente:
  if (
    valid == 1 &&
    step < 4 &&
    $(".page.active").index() < $(".page").length - 1
  ) {
    //cambio form active
    $(".page.active").removeClass("active").next().addClass("active");
    //cambio step active
    $(".step.actived")
      .removeClass("actived")
      .addClass("finish")
      .next()
      .addClass("actived");
    //muestro botón anterior
    $("#btn-prev").show();
  }

  //si llegue al final, muestro botón de guardar y oculto el de siguiente
  if ($(".page.active").index() == $(".page").length - 1) {
    $("#btn-guardar").show();
    $("#btn-next").hide();
  }
  setTimeout(function () {
    //@HACK: Elimino el delay que heredamos de barraNavegacion.js
    $("#btn-next").prop("disabled", false);
  }, 250);
});

function strRequest(objectName, keyname) {
  return objectName + "[" + keyname + "]";
}
function clearNullUndef(val) {
  return typeof val !== "undefined" && val !== null ? val : "";
}
//botón guardar ae
$("#btn-guardar").click(function (e) {
  // Esto esta hecho de esta forma "a pata" porque al enviar datos ademas de archivos,
  // No puede "JSONear" los archivos y da error, se necesita mandarlo como request comun con Content-type = text/html
  // Pero en esta modalidad no hace el pasaje automatico de objeto -> dato y manda un [Object object]
  // Se hacen dos funciones auxiliares para hacer esto. -Octavio 17 Julio 2020
  const formData = new FormData();

  const id_autoexcluido = $("#modalAgregarAE").attr("id-autoexcluido");
  const ae_datos = {
    id_autoexcluido: id_autoexcluido < 0 ? null : id_autoexcluido,
    nro_dni: $("#nro_dni").val(),
    apellido: $("#apellido").val(),
    nombres: $("#nombres").val(),
    fecha_nacimiento: $("#fecha_nacimiento").val(),
    id_sexo: $("#id_sexo").val(),
    id_estado_civil: $("#id_estado_civil").val(),
    domicilio: $("#domicilio").val(),
    nro_domicilio: $("#nro_domicilio").val(),
    piso: $("#piso").val(),
    dpto: $("#dpto").val(),
    codigo_postal: $("#codigo_postal").val(),
    nombre_localidad: $("#nombre_localidad").val(),
    nombre_provincia: $("#nombre_provincia").val(),
    telefono: $("#telefono").val(),
    correo: $("#correo").val(),
    id_ocupacion: $("#id_ocupacion").val(),
    id_capacitacion: $("#id_capacitacion").val(),
  };

  for (const key in ae_datos) {
    formData.append(strRequest("ae_datos", key), clearNullUndef(ae_datos[key]));
  }

  const ae_datos_contacto = {
    nombre_apellido: $("#nombre_apellido").val(),
    domicilio: $("#domicilio_vinculo").val(),
    nombre_localidad: $("#nombre_localidad_vinculo").val(),
    nombre_provincia: $("#nombre_provincia_vinculo").val(),
    telefono: $("#telefono_vinculo").val(),
    vinculo: $("#vinculo").val(),
  };
  for (const key in ae_datos_contacto) {
    formData.append(
      strRequest("ae_datos_contacto", key),
      clearNullUndef(ae_datos_contacto[key])
    );
  }

  const ae_estado = {
    id_nombre_estado: $("#id_estado").val(),
    fecha_ae: $("#fecha_autoexlusion").val(),
    fecha_vencimiento: $("#fecha_vencimiento_periodo").val(),
    fecha_renovacion: $("#fecha_renovacion").val(),
    fecha_cierre_ae: $("#fecha_cierre_definitivo").val(),
    fecha_cierre_minima: $('#fecha_cierre_minima').val(),
    años_adicionales: $('#años_adicionales').val(),
    alcance_interjurisdiccional: $('#alcance_interjurisdiccional').val(),
    resolucion: $('#resolucion').val(),
    primer_ae: $('#primer_ae').val()
  };
  {
    const op = $('#id_casino option:selected');
    if(op.is('[data-tipo="casino"]')){
      ae_estado.id_casino = op.val();
    }
    else if(op.is('[data-tipo="plataforma"]')){
      ae_estado.id_plataforma = -op.val();
    }
    else if(op.is('[data-tipo="plataforma_aadd"]')){
      ae_estado.id_plataforma = -op.val();
    }
    else{
      throw 'ERROR TIPO DE OPCION SIN MANEJAR';
    }
  }
  for (const key in ae_estado) {
    formData.append(strRequest("ae_estado", key), ae_estado[key]);
  }
  
  formData.append('hace_encuesta',$('#hace_encuesta').is(':checked')? 1 : 0);
  const ae_encuesta = {
    id_juego_preferido: $('#juego_preferido').val(),
    id_frecuencia_asistencia: $('#id_frecuencia_asistencia').val(),
    veces: $('#veces').val(),
    tiempo_jugado: $('#tiempo_jugado').val(),
    club_jugadores: $('#socio_club_jugadores').val(),
    conoce_plataformas: $('#conoce_plataformas').val(),
    utiliza_plataformas: $('#utiliza_plataformas').val(),
    juego_responsable: $('#juego_responsable').val(),
    autocontrol_juego: $('#autocontrol_juego').val(),
    recibir_informacion: $('#recibir_informacion').val(),
    medio_recibir_informacion: $('#medio_recepcion').val(),
    como_asiste: $('#como_asiste').val(),
    observacion: $('#observaciones').val(),
  }
  for(const key in ae_encuesta){
    formData.append(strRequest('ae_encuesta',key),clearNullUndef(ae_encuesta[key]));
  }

  const ae_importacion_cargado = {
    foto1: $("#foto1").parent().find("a").text().length != 0,
    foto2: $("#foto2").parent().find("a").text().length != 0,
    solicitud_ae:
      $("#solicitud_autoexclusion").parent().find("a").text().length != 0,
    solicitud_revocacion:
      $("#solicitud_revocacion").parent().find("a").text().length != 0,
    scandni: $("#scan_dni").parent().find("a").text().length != 0,
    caratula: $("#caratula").parent().find("a").text().length != 0,
  };
  const ae_importacion = {
    foto1: $("#foto1")[0].files[0],
    foto2: $("#foto2")[0].files[0],
    solicitud_ae: $("#solicitud_autoexclusion")[0].files[0],
    solicitud_revocacion: $("#solicitud_revocacion")[0].files[0],
    scandni: $("#scan_dni")[0].files[0],
    caratula: $("#caratula")[0].files[0],
  };
  for (const key in ae_importacion) {
    const cargado = ae_importacion_cargado[key];
    const file_val = clearNullUndef(ae_importacion[key]);
    if (cargado) {
      //Si ya tiene cargado un archivo, mando vacio
      formData.append(strRequest("ae_importacion", key), "");
    } else if (file_val != "") {
      //Si mando un archivo nuevo,
      formData.append(strRequest("ae_importacion", key), file_val);
    }
  }

  //url de destino, dependiendo si se esta creando o modificando una sesión
  //dependiendo el valor del botón guarda o edita
  $.ajax({
    type: "POST",
    url: '/autoexclusion/agregarAE',
    data: formData,
    processData: false,
    contentType: false,
    cache: false,
    success: function (data) {
        $('#modalAgregarAE').modal('hide');
        $('[data-js-filtro-tabla]').trigger('buscar');
        mensajeExito('La autoexclusión fue '+(data.nuevo? 'GUARDADA' : 'EDITADA') + ' correctamente.');
    },
    error: function (data) {
      console.log(data);
    }
  });
});

function mostrarAutoexcluido(id_autoexcluido) {
  $("#modalVerMas input:checked").prop("checked", false);
  $("#modalVerMas input").val("");
  $("#modalVerMas select").val("");
  $.get(
    "/autoexclusion/buscarAutoexcluido/" + id_autoexcluido,
    function (data) {
      $("#infoApellido").val(data.autoexcluido.apellido);
      $("#infoNombres").val(data.autoexcluido.nombres);
      $("#infoFechaNacimiento").val(data.autoexcluido.fecha_nacimiento);
      $("#infoDni").val(data.autoexcluido.nro_dni);
      $("#infoSexo").val(data.autoexcluido.id_sexo);
      $("#infoEstadoCivil").val(data.autoexcluido.id_estado_civil);
      $("#infoDomicilio").val(data.autoexcluido.domicilio);
      $("#infoNroDomicilio").val(data.autoexcluido.nro_domicilio);
      $("#infoProvincia").val(data.autoexcluido.nombre_provincia);
      $("#infoLocalidad").val(data.autoexcluido.nombre_localidad);
      $("#infoTelefono").val(data.autoexcluido.telefono);
      $("#infoEmail").val(data.autoexcluido.correo);
      $("#infoOcupacion").val(data.autoexcluido.id_ocupacion);
      $("#infoCapacitacion").val(data.autoexcluido.id_capacitacion);

      if (data.datos_contacto != null) {
        $("#infoNombreApellidoVinculo").val(
          data.datos_contacto.nombre_apellido
        );
        $("#infoDomiclioVinculo").val(data.datos_contacto.domicilio);
        $("#infoProvinciaVinculo").val(data.datos_contacto.nombre_provincia);
        $("#infoLocalidadVinculo").val(data.datos_contacto.nombre_localidad);
        $("#infoTelefonoVinculo").val(data.datos_contacto.telefono);
        $("#infoVinculo").val(data.datos_contacto.vinculo);
      }

      if(data.estado.id_casino != null) $('#infoCasino').val(data.estado.id_casino);
      else if(data.estado.id_plataforma != null) $('#infoCasino').val(-data.estado.id_plataforma);

      $('#infoEstado').val(data.estado.id_nombre_estado);
      $('#infoResolucion').val(data.estado.resolucion);
      $('#infoPrimerAE').val(data.es_primer_ae? 'SI' : 'NO');
      $('#infoFechaAutoexclusion').val(data.estado.fecha_ae);
      $('#infoFechaCierreDefinitivo').val(data.estado.fecha_cierre_ae);
      if(data.estado.resolucion == '983'){
        $('#infoFechaCierreMinima').val('');
        $('#infoAñosAdicionales').val('');
        $('#infoAlcanceInterjurisdiccional').val('');
        if(data.es_primer_ae){
          $('#infoFechaVencimiento').val(data.estado.fecha_vencimiento);
          $('#infoFechaRenovacion').val(data.estado.fecha_renovacion);
        }
        else{
          $('#infoFechaVencimiento').val('');
          $('#infoFechaRenovacion').val('');  
        }
      }
      else if(data.estado.resolucion == NOMBRE_NUEVA_RESOLUCION){
        $('#infoFechaCierreMinima').val(data.estado.fecha_cierre_minima ?? '');
        $('#infoAñosAdicionales').val(data.estado.años_adicionales ?? '');
        $('#infoAlcanceInterjurisdiccional').val(data.estado.alcance_interjurisdiccional ?? '');
        $('#infoFechaVencimiento').val('');
        $('#infoFechaRenovacion').val('');
      }
      
      if (data.encuesta != null) {
        $('#infoJuegoPreferido').val(data.encuesta.id_juego_preferido);
        $('#infoFrecuenciaAsistencia').val(data.encuesta.id_frecuencia_asistencia);
        $('#infoVeces').val(data.encuesta.veces);
        $('#infoTiempoJugado').val(data.encuesta.tiempo_jugado);
        $('#infoSocioClubJugadores').val(data.encuesta.club_jugadores);
        $('#infoConocePlataformas').val(data.encuesta.conoce_plataformas);
        $('#infoUtilizaPlataformas').val(data.encuesta.utiliza_plataformas);
        $('#infoJuegoResponsable').val(data.encuesta.juego_responsable);
        $('#infoAutocontrol').val(data.encuesta.autocontrol_juego);
        $('#infoComoAsiste').val(data.encuesta.como_asiste);
        $('#infoRecibirInformacion').val(data.encuesta.recibir_informacion);
        $('#infoMedioRecepcion').val(data.encuesta.medio_recibir_informacion);
        $('#infoObservaciones').val(data.encuesta.observacion);
      }

      //seteo en el value de los botones de ver mas el id de la importacion, para después
      //buscar en el backend los paths a los archivos y mostrarlos oportunamente
      if(data.importacion != null){
          $(".archivosImportados button").val(data.importacion.id_importacion);
          for(const archivo of ['foto1','foto2','scandni','solicitud_ae','solicitud_revocacion','caratula']){
            $(`.archivosImportados [data-tipo="${archivo}"]`).prop("disabled", !data?.importacion?.[archivo]);
          }
      }
      else {
          $(".archivosImportados button").val("");
          $('.archivosImportados button').prop("disabled", true);
      }

      $("#modalVerMas").modal("show");
    }
  ).fail(function(data){
      console.log(data);
      alert("Error al obtener los datos del autoexcluido. Ver consola.");
  });
}


$(document).on("click", "[data-js-click-nuevo-ae]", function (e) {
  e.preventDefault();
  const tgt = $(e.currentTarget);
  $('[data-js-modal-ver-cargar-ae]').trigger('mostrar.modal',[tgt.attr('data-js-click-nuevo-ae'),tgt.val(),'NUEVO']);
});

$(document).on("click", "[data-js-click-ver-ae]", function (e) {
  e.preventDefault();
  const tgt = $(e.currentTarget);
  $('[data-js-modal-ver-cargar-ae]').trigger('mostrar.modal',[tgt.attr('data-js-click-ver-ae'),tgt.val(),'VER']);
});

$(document).on("click", "[data-js-click-editar-ae]", function (e) {
  e.preventDefault();
  const tgt = $(e.currentTarget);
  $('[data-js-modal-ver-cargar-ae]').trigger('mostrar.modal',[tgt.attr('data-js-click-editar-ae'),tgt.val(),'EDITAR']);
});

$(document).on("click", "#btnEliminar", function (e) {
  e.preventDefault();
  const id = $(this).val();
  $('[data-js-modal-eliminar]').trigger('mostrar',[{
    mensaje: '¿DESEA ELIMINAR EL AUTOEXCLUIDO?',
    url: '/autoexclusion/eliminarAE/'+ id,
    success: function(data){
      $('[data-js-filtro-tabla]').trigger('buscar');
    }
  }]);
});

$(document).on("click", "[data-js-click-generar-pdf]", function (e) {
  e.preventDefault();
  const tgt = $(e.currentTarget);
  const params = {
    id_autoexclusion: $(e.currentTarget).val(),
  };
  window.open(
    tgt.attr('data-js-click-generar-pdf') + "?" + (new URLSearchParams(params))?.toString(),
    "_blank"
  );
});

$(document).on("click","[data-js-click-cambiar-estado]", function(e){
  const tgt = $(e.currentTarget);
  try{
    const json_params = JSON.parse(tgt.attr('data-js-click-cambiar-estado'));
    const params = {
      id_autoexclusion: $(e.currentTarget).val(),
      de: json_params.de,
      a: json_params.a
    };
    AUX.GET(json_params.url,params,
      function(data){
        AUX.mensajeExito('Cambio a '+params.a+' realizado');
        $('[data-js-filtro-tabla]').trigger('buscar');
      },
      function(err){
        const json = err.responseJSON;
        console.log(json);
        AUX.mensajeError(json?.mensaje ?? json?.message ?? '');
      }
    );
  }
  catch(error){
    console.log(e.currentTarget,error);
  }
});

$(document).on('click','.btnDestruirPapel',function(e){
  AUX.POST(
    '/autoexclusion/destruirPapel',
    {id_autoexcluido: $(this).val()},
    function(data){
      AUX.mensajeExito('Papel destruido');
      $('[data-js-filtro-tabla]').trigger('buscar');
    },
    function(data){
      AUX.mensajeError('Error al destruir el papel');
      console.log(data);
    }
  );
});

//Salir del modal ver mas
$("#btn-salir").click(function () {
  $("#modalVerMas").modal("hide");
});

//Mostrar archivos ver mas
$(".btn-ver-mas").click(function () {
  let tipo_archivo = $(this).attr("data-tipo");
  window.open(
    "/autoexclusion/mostrarArchivo/" + $(this).val() + "/" + tipo_archivo,
    "_blank"
  );
});

$(document).ready(function(){
  $('[data-js-modal-subir-archivo]').each(function(_,Mobj){
    const M = $(Mobj);

    const fill = function(div,prefix,obj){//@HACK @TODO: mover a AUX
      const subscript = function(s){
        return prefix === null? s : (prefix+'['+s+']');
      };
      for(const k in obj){
        const val = obj[k];
        if(typeof val == 'object'){
          fill(div,subscript(k),val);
        }
        else{
          const name = subscript(k);
          const named_obj = div.find(`[name="${name}"]`);
          named_obj.val(val);
        }
      }
    };

    const fillError = function(div,obj){//@HACK @TODO: mover a AUX
      for(const k in obj){
        const val = obj[k];
        if(typeof val == 'object' && !Array.isArray(val)){
          console.log(k,val,'Valor inesperado');
        }
        else{
          const name_arr = k.split('.');
          let name = name_arr?.[0] ?? '';
          for(let idx=1;idx<name_arr.length;idx++){
            name+='['+name_arr[idx]+']';
          }
          mostrarErrorValidacion(div.find(`[name="${name}"]`),Array.isArray(val)? val.join(', ') : val,true);
        }
      }
    };

    const dropzone = M.find("[data-js-dropzone]");
    const fileInput = M.find('[name="archivo"]');

    dropzone.off('dragover').on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.css('background-color', '#e2e8f0');
    });

    dropzone.off('dragleave').on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.css('background-color', '#f8fafc');
    });

    dropzone.off('drop').on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.css('background-color', '#f8fafc');
        const files = e.originalEvent.dataTransfer.files;
        if(files.length > 0) {
            fileInput[0].files = files;
            fileInput.trigger('change');
        }
    });

    fileInput.off('change').on('change', function() {
        const display = $("#filenameDisplay");
        if(this.files && this.files.length > 0) {
            display.text(this.files[0].name).css({'color': '#1e40af', 'font-weight': 'bold'});
        } else {
            display.text("Arrastre el archivo aquí o haga click en Seleccionar Archivo").css({'color': '#64748b', 'font-weight': 'normal'});
        }
    });
    
    M.on('modal.mostrar',function(e,params){
      ocultarErrorValidacion(M.find('form input').val(''));
      M.find("#filenameDisplay").text("Arrastre el archivo aquí o haga click en Seleccionar Archivo").css({'color': '#64748b', 'font-weight': 'normal'});
      M.find('.tipo_archivo').text(params?.titulo ?? 'ERROR');
      M.find('form [name]').val('');
      fill(M.find('form'),null,params ?? {});

      M.modal("show");
    });

    M.find("[data-js-click-enviar-archivo]").click(function (e) {
      const url = $(e.currentTarget).attr('data-js-click-enviar-archivo');
      const form = M.find('form')?.[0];
      const entries = AUX.form_entries(form);
      const fd = new FormData();
      for(const name in entries){
        const val = entries[name];
        const obj = M.find(`[name="${name}"]`);
        if(obj.is('[type="file"]')){
          const file = obj[0].files?.[0] ?? null;
          if(file !== null){
            fd.append(name,file,file.name);
          }
        }
        else{
          fd.append(name,val);
        }
      }
      ocultarErrorValidacion(M.find('[name]'));
      AUX.POST(url,fd,
        function(data){
          AUX.mensajeExito('Guardado');
          M.modal('hide');
          $('[data-js-filtro-tabla]').trigger('buscar');
        },
        function(data){
          const json = data.responseJSON ?? {};
          AUX.mensajeError(json?.mensaje ?? json?.message ?? '');
          fillError($(form),json);
        },
        {
          contentType: false,
          processData: false,
        }
      );
    });
  });
})

//Click en boton adentro del popover
$(document).on("click", "[data-js-click-subir-archivo]", function (e) {
  const tgt = $(e.currentTarget);
  const params = tgt.attr('data-js-click-subir-archivo'); 
  $('[data-js-modal-subir-archivo]').trigger('modal.mostrar',JSON.parse(params));
});

//Mostrar formularios
$(".btn-ver-formulario").click(function () {
  window.open(
    "/autoexclusion/mostrarFormulario/" + $(this).attr("id"),
    "_blank"
  );
});

$(".sacarArchivo").click(function () {
  const div = $(this).parent();
  const input = div.parent().find("input");
  div.hide();
  input.show();
  div.find("a").text("");
});

$("#contenedorFiltros input").on("keypress", function (e) {
  if (e.which == 13) {
    e.preventDefault();
    $('[data-js-filtro-tabla]').trigger('buscar');
  }
});

$("#hace_encuesta").change(function () {
  const show = $(this).is(":checked");
  const divs = $(this)
    .parent()
    .parent()
    .parent()
    .children()
    .not(".no_esconder");
  divs.toggle(show);
});



/*
-- Matias me habia pedido si podia aproximarse la fecha asi no tenian que mover mucho.
-- Script para calcular la regresion. No lo pongo como un request GET porque no es 
-- necesario cambiarlo siempre (capaz me olvide de algo en el where y con algun AE que 
-- agreguen se rompe todo). Lo pongo aca para que se vea que no lo saque de la galera.
-- Saco los muy viejos, muy nuevos y los DNI muy altos (extranjeros).
-- Creo que se aproximaria mejor con una cuadratica o exponencial pero con esto funciona

DROP PROCEDURE IF EXISTS DniStampLinreg;
DELIMITER $$
CREATE PROCEDURE DniStampLinreg()
BEGIN
    CREATE TEMPORARY TABLE temp_dni_stamp (nro_dni bigint, stamp bigint);
    INSERT INTO temp_dni_stamp
        select distinct ae.nro_dni, DATEDIFF(ae.fecha_nacimiento,'1970-01-01')*24*60*60 as stamp
        from ae_datos as ae
        where ae.deleted_at is null and ae.fecha_nacimiento is not null 
        and YEAR(ae.fecha_nacimiento) > 1940 and YEAR(ae.fecha_nacimiento) < 2002 
        and ae.nro_dni < 50000000
        ORDER BY `ae`.`nro_dni` asc;

    CREATE TEMPORARY TABLE temp_dni_stamp_uniq (nro_dni bigint, stamp bigint);
    INSERT INTO temp_dni_stamp_uniq
        select nro_dni,MIN(stamp) as stamp
        from temp_dni_stamp
        group by temp_dni_stamp.nro_dni;
    
    SET @x  := (SELECT SUM(nro_dni)         FROM temp_dni_stamp_uniq);
    SET @x2 := (SELECT SUM(nro_dni*nro_dni) FROM temp_dni_stamp_uniq);
    SET @y  := (SELECT SUM(stamp)           FROM temp_dni_stamp_uniq);
    SET @y2 := (SELECT SUM(stamp*stamp)     FROM temp_dni_stamp_uniq);
    SET @xy := (SELECT SUM(nro_dni*stamp)   FROM temp_dni_stamp_uniq);
    SET @n  := (SELECT COUNT(nro_dni)       FROM temp_dni_stamp_uniq);
    
    DROP TABLE temp_dni_stamp_uniq;
    DROP TABLE temp_dni_stamp;
    
    SELECT (@y*@x2 - @x*@xy)/(@n*@x2-@x*@x) as intercept, (@n*@xy - @x*@y )/(@n*@x2-@x*@x) as slope;
END$$

-- En otra consulta
Call DniStampLinreg();
*/

/*
-- Usando median para sacar outliers (generado por Claude)
-- Test copy of DniStampRobust: same logic, but returns its values through OUT
-- parameters instead of a final SELECT, so another procedure can collect them.
-- The original DniStampRobust is not changed. If you edit one, edit the other too.
DROP PROCEDURE IF EXISTS DniStampRobustOut;
DELIMITER $$
CREATE PROCEDURE DniStampRobustOut(IN  sample_rate    DOUBLE,
                                   OUT o_intercept    DOUBLE,
                                   OUT o_slope        DOUBLE,
                                   OUT o_rows_used    INT,
                                   OUT o_buckets_used INT)
BEGIN
    -- sample_rate: fraction of DNIs to use, between 0 and 1 (1.0 = use all of them)
    DECLARE bucket_size BIGINT DEFAULT 250000;  -- width of each DNI range
    DECLARE min_count   INT    DEFAULT 30;      -- ignore ranges with fewer people than this
    DECLARE v_rows      INT;
    DECLARE v_n         INT;
    DECLARE v_slope     DOUBLE;
    DECLARE v_intercept DOUBLE;

    DROP TEMPORARY TABLE IF EXISTS
        tmp_sorted, tmp_bstats, tmp_buckets, tmp_buckets2, tmp_slopes, tmp_icpt;

    -- 1. One birth timestamp per DNI, optionally sampled, stored sorted by (range, stamp).
    --    The AUTO_INCREMENT id records each row's position in that order, which is what
    --    lets us find medians without window functions.
    CREATE TEMPORARY TABLE tmp_sorted (
        id      INT    NOT NULL AUTO_INCREMENT PRIMARY KEY,
        bucket  INT    NOT NULL,
        nro_dni BIGINT NOT NULL,
        stamp   BIGINT NOT NULL
    ) ENGINE=MEMORY;

    INSERT INTO tmp_sorted (bucket, nro_dni, stamp)
        SELECT FLOOR(u.nro_dni / bucket_size), u.nro_dni, u.stamp
        FROM (
            SELECT nro_dni,
                   MIN(DATEDIFF(fecha_nacimiento, '1970-01-01')) * 86400 AS stamp
            FROM ae_datos
            WHERE deleted_at IS NULL
              AND fecha_nacimiento >= '1980-01-01'   -- same as YEAR() > 1940, but can use an index
              AND fecha_nacimiento <  '2002-01-01'   -- same as YEAR() < 2002
              AND nro_dni < 70000000
            GROUP BY nro_dni
        ) u
        WHERE RAND() < sample_rate
        ORDER BY FLOOR(u.nro_dni / bucket_size), u.stamp;

    SELECT COUNT(*) INTO v_rows FROM tmp_sorted;

    -- 2. Per range: where it starts in tmp_sorted, how many rows it has, and its mean DNI
    --    (a mean is fine for x: DNIs inside one range can't be far off from each other)
    CREATE TEMPORARY TABLE tmp_bstats (
        bucket   INT    NOT NULL PRIMARY KEY,
        first_id INT    NOT NULL,
        n        INT    NOT NULL,
        x        DOUBLE NOT NULL
    ) ENGINE=MEMORY;

    INSERT INTO tmp_bstats (bucket, first_id, n, x)
        SELECT bucket, MIN(id), COUNT(*), AVG(nro_dni)
        FROM tmp_sorted
        GROUP BY bucket
        HAVING COUNT(*) >= min_count;

    -- 3. Median stamp of each range = the middle row(s) by position
    CREATE TEMPORARY TABLE tmp_buckets (
        bucket INT    NOT NULL PRIMARY KEY,
        x      DOUBLE NOT NULL,
        y      DOUBLE NOT NULL
    ) ENGINE=MEMORY;

    INSERT INTO tmp_buckets (bucket, x, y)
        SELECT b.bucket, b.x, AVG(s.stamp)
        FROM tmp_sorted s
        JOIN tmp_bstats b ON b.bucket = s.bucket
        WHERE s.id - b.first_id IN (FLOOR((b.n - 1) / 2), CEIL((b.n - 1) / 2))
        GROUP BY b.bucket, b.x;

    -- 4. Theil-Sen slope: median of the slopes between every pair of ranges
    --    (MySQL can't use the same TEMPORARY table twice in one query, hence the copy)
    CREATE TEMPORARY TABLE tmp_buckets2 ENGINE=MEMORY SELECT * FROM tmp_buckets;

    CREATE TEMPORARY TABLE tmp_slopes (
        id  INT    NOT NULL AUTO_INCREMENT PRIMARY KEY,
        val DOUBLE NOT NULL
    ) ENGINE=MEMORY;

    INSERT INTO tmp_slopes (val)
        SELECT (b.y - a.y) / (b.x - a.x) AS val
        FROM tmp_buckets a
        JOIN tmp_buckets2 b ON b.bucket > a.bucket
        ORDER BY val;

    SELECT COUNT(*) INTO v_n FROM tmp_slopes;
    SELECT AVG(val) INTO v_slope
    FROM tmp_slopes
    WHERE id IN (FLOOR((v_n + 1) / 2), CEIL((v_n + 1) / 2));

    -- 5. Intercept: median of (y - slope * x) over the ranges
    CREATE TEMPORARY TABLE tmp_icpt (
        id  INT    NOT NULL AUTO_INCREMENT PRIMARY KEY,
        val DOUBLE NOT NULL
    ) ENGINE=MEMORY;

    INSERT INTO tmp_icpt (val)
        SELECT y - v_slope * x AS val
        FROM tmp_buckets
        ORDER BY val;

    SELECT COUNT(*) INTO v_n FROM tmp_icpt;
    SELECT AVG(val) INTO v_intercept
    FROM tmp_icpt
    WHERE id IN (FLOOR((v_n + 1) / 2), CEIL((v_n + 1) / 2));

    DROP TEMPORARY TABLE
        tmp_sorted, tmp_bstats, tmp_buckets, tmp_buckets2, tmp_slopes, tmp_icpt;

    SET o_intercept    = v_intercept;
    SET o_slope        = v_slope;
    SET o_rows_used    = v_rows;
    SET o_buckets_used = v_n;
END$$
DELIMITER ;


-- Runs DniStampRobustOut several times and returns one table:
-- one row per run, plus a "mean" and a "std dev" row at the end.
DROP PROCEDURE IF EXISTS DniStampRuns;
DELIMITER $$
CREATE PROCEDURE DniStampRuns(IN times INT, IN sample_rate DOUBLE)
BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE r_intercept, r_slope DOUBLE;
    DECLARE r_rows, r_buckets INT;
    DECLARE m_intercept, m_slope, m_rows, m_buckets DOUBLE;
    DECLARE s_intercept, s_slope, s_rows, s_buckets DOUBLE;

    DROP TEMPORARY TABLE IF EXISTS tmp_runs;
    CREATE TEMPORARY TABLE tmp_runs (
        seq          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        run          VARCHAR(10),
        intercept    DOUBLE,
        slope        DOUBLE,
        rows_used    DOUBLE,
        buckets_used DOUBLE
    ) ENGINE=MEMORY;

    WHILE i < times DO
        SET i = i + 1;
        CALL DniStampRobustOut(sample_rate, r_intercept, r_slope, r_rows, r_buckets);
        INSERT INTO tmp_runs (run, intercept, slope, rows_used, buckets_used)
        VALUES (i, r_intercept, r_slope, r_rows, r_buckets);
    END WHILE;

    -- Summary rows (computed into variables first: MySQL can't read and
    -- write the same TEMPORARY table in one statement)
    SELECT AVG(intercept), AVG(slope), AVG(rows_used), AVG(buckets_used),
           STDDEV_SAMP(intercept), STDDEV_SAMP(slope), STDDEV_SAMP(rows_used), STDDEV_SAMP(buckets_used)
    INTO m_intercept, m_slope, m_rows, m_buckets,
         s_intercept, s_slope, s_rows, s_buckets
    FROM tmp_runs;

    INSERT INTO tmp_runs (run, intercept, slope, rows_used, buckets_used)
    VALUES ('mean',    m_intercept, m_slope, m_rows, m_buckets),
           ('std dev', s_intercept, s_slope, s_rows, s_buckets);

    SELECT run, intercept, slope, rows_used, buckets_used
    FROM tmp_runs
    ORDER BY seq;

    DROP TEMPORARY TABLE tmp_runs;
END$$
DELIMITER ;

-- In another query: 10 runs with a 30% sample
CALL DniStampRuns(10, 0.3);

-- Using the result (FROM_UNIXTIME returns NULL for negative stamps, i.e. pre-1970 births):
-- SELECT DATE_ADD('1970-01-01', INTERVAL ROUND(@intercept + @slope * 30123456) SECOND);
*/