import '/js/Components/inputFecha.js';
import '/js/Components/FiltroTabla.js';
import {AUX} from "/js/Components/AUX.js";
import './modificarMinimo.js';
import './regenerarBackup.js';
import './generar.js';
import './eliminar.js';
import './cargarModificarApuestas.js';

$(function() {
  $('.tituloSeccionPantalla').text('Relevamientos de Valores Mínimos de Apuestas');
  
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
  
  $('#btn-generar').click(function(e){
    e.preventDefault();
    $('[data-js-generar]').trigger('mostrar');
  });
  
  $('#btn-minimo').click(function(e){
    e.preventDefault();
    $('[data-js-modificar-minimo]').trigger('mostrar');
  });
  
  $('#btn-backUp').on('click',function(e){
    e.preventDefault();
    $('[data-js-regenerar-backup]').trigger('mostrar');
  });
  
  $('[data-js-generar-relevamiento],[data-js-regenerar-backup],[data-js-eliminar],\
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

$(document).on('click', '[data-js-ver-apuesta]', function(e){
  $('[data-js-cargar-modificar-validar]').trigger('mostrar',['Ver',$(this).val()]);
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
