import '../CierresAperturas/inputFecha.js';
import '../CierresAperturas/FiltroTabla.js';
import {AUX} from "../CierresAperturas/AUX.js";
import './modificarMinimo.js';
import './regenerarBackup.js';
import './generar.js';
import './eliminar.js';
import './cargarModificarApuestas.js';

$(function() {
  $('.tituloSeccionPantalla').text('Relevamientos de Valores Mínimos de Apuestas');

  $('#dtpFecha,#dtpFechaCarga,#dtpFechaBUp,#dtpFechaBUpEjecucion').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2
  });
  
  $('[data-js-filtro-tabla]').on('busqueda',function(e,ret,tbody,molde){
    ret.data.forEach(function(obj){
      const fila = molde.clone();
      fila.find('.fecha').text(obj.fecha);
      fila.find('.nro_turno').text(obj.nro_turno);
      fila.find('.casino').text(obj.nombre);
      fila.find('[data-estados]').filter(function(b){
        const estados = $(this)?.attr('data-estados')?.split(',') ?? [];
        return !estados.includes(obj.id_estado_relevamiento+'');
      }).remove();
      
      fila.find('button').val(obj.id_relevamiento_apuestas);
      tbody.append(fila);
    });
    $('[data-js-aperturas-sorteadas]').trigger('buscar');
  }).trigger('buscar');
  
  $('#btn-minimo').click(function(e){
    e.preventDefault();
    $('[data-js-modificar-minimo]').trigger('mostrar');
  });
  
  $('#btn-backUp').on('click',function(e){
    e.preventDefault();
    $('[data-js-regenerar-backup]').trigger('mostrar');
  });
  
  $('[data-js-regenerar-backup],[data-js-eliminar],\
     [data-js-cargar-modificar-validar]').on('success',function(e){
    $('[data-js-filtro-tabla]').trigger('buscar');
  });
});

$(document).on('click','[data-js-eliminar-apuesta]',function(e){
  $('[data-js-eliminar]').trigger('mostrar',[$(this).val()]);
});

$(document).on('click','[data-js-nueva-pestaña]',function(){
  window.open($(this).attr('data-js-nueva-pestaña')+'/'+$(this).val(),'_blank');
});

$(document).on('click', '[data-js-cargar-apuesta]', function(e){
  $('[data-js-cargar-modificar-validar]').trigger('mostrar',['Cargar',$(this).val()]);
});

$(document).on('click', '[data-js-modificar-apuesta]', function(e){
  $('[data-js-cargar-modificar-validar]').trigger('mostrar',['Modificar',$(this).val()]);
});

$(document).on('click', '[data-js-validar-apuesta]', function(e){
  $('[data-js-cargar-modificar-validar]').trigger('mostrar',['Validar',$(this).val()]);
});
