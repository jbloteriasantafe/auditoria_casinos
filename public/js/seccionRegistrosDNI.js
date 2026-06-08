import "/js/Components/modal.js";
import "/js/Components/inputFecha.js";
import "/js/Components/FiltroTabla.js";
import '/js/Components/modalEliminar.js';
import {AUX} from "/js/Components/AUX.js";
import "/js/md5.js";

import '/js/highcharts_11_3_0/highcharts.js';
import '/js/highcharts_11_3_0/highcharts-more.js';
import '/js/highcharts_11_3_0/modules/heatmap.js';
import '/js/highcharts_11_3_0/modules/accessibility.js';

function flattenObject(ob) {
    var toReturn = {};

    for (var i in ob) {
        if (!ob.hasOwnProperty(i)) continue;

        if ((typeof ob[i]) == 'object' && ob[i] !== null) {
            var flatObject = flattenObject(ob[i]);
            for (var x in flatObject) {
                if (!flatObject.hasOwnProperty(x)) continue;

                toReturn[i + '.' + x] = flatObject[x];
            }
        } else {
            toReturn[i] = ob[i];
        }
    }
    return toReturn;
}


function totales_claves(data){
  const flatData = flattenObject(data);
  let años = {};
  const meses = [1,2,3,4,5,6,7,8,9,9,10,11,12];
  let grupos_etarios = {};
  for(const k in flatData){
    const karr = k.split('.');
    años[parseInt(karr[0])] = 1;
    grupos_etarios[karr[karr.length-1]] = 1;
  }
  años = Object.keys(años).map(function(a){return parseInt(a);}).sort();;
  grupos_etarios = Object.keys(grupos_etarios).sort();
  return {años: años,meses: meses,grupos_etarios: grupos_etarios};
}

function lerpF(t, x0, x1) {
  //(t=0,x0),(t=1,x1)
  return (1 - t) * x0 + t * x1;
}
function lerpColor(t, c0, c1) {
  return [
    lerpF(t, c0[0], c1[0]),
    lerpF(t, c0[1], c1[1]),
    lerpF(t, c0[2], c1[2]),
  ];
}
function c_to_rgb(c){
  return [255 * c[0], 255 * c[1], 255 * c[2]];
}
function rgb_to_c(rgb){
  return [rgb[0]/255,rgb[1]/255,rgb[2]/255];
}
function bezierColor(t,lowColor = [0.9, 0.1, 0.1],highColor = [0.1, 0.8, 0.15],controlPoint = [1.0, 0.95, 0.0]) {
  //Bezier curve interpolation
  return lerpColor(
    t,
    lerpColor(t, lowColor, controlPoint),
    lerpColor(t, controlPoint, highColor)
  );
}

function getQuantile(sorted_arr, q){
  if(sorted_arr.length == 0) return {q: null, idx: null};
  const max_pos = (sorted_arr.length - 1);
  const fpos = max_pos * Math.max(0,Math.min(q,1));
  const ipos = Math.floor(fpos);
  const t = fpos - ipos;
  return {
    q: lerpF(t,sorted_arr[ipos],sorted_arr[Math.min(ipos+1,max_pos)]),
    idx: fpos
  };
}

function estadisticas(div,data){ 
  const claves = totales_claves(data);
  let meses = [];
  let inv_gini = [];
  let reportes_por_mes = [];
  let reportes_por_dia = [];
  let reportes_por_dia_qs = [];
  let plotLines = [];
  
  //Guardo cuando efectivamente comienza el reporte, para no mostrar todos los años completos
  let skip_inicial = null;
  let encontro_inicio = false;
  let skip_final = null;
  let comenzo_final = false;
  
  let x = 0;
  let max_reportes_diarios = 100;
  for(const a of claves.años){
    for(const m of claves.meses){
      const label = `${(a+'').padStart(4,'0')}-${(m+'').padStart(2,'0')}`;
      const dias_mes_totales = (new Date(a,m,0)).getDate();//En JS el mes es 0-indexado, si pasamos m nos da el mes que viene, si pasamos día = 0, le resta 1 dia
      meses.push(label);
      
      const dias_reportados = data?.[a]?.[m] ?? {};
      let reportes_mensuales = 0;
      const rd = {};
      {
        for(let d=1;d<=dias_mes_totales;d++){
          let reporte_diario = 0;
          for(const g of claves.grupos_etarios){
            reporte_diario += (dias_reportados?.[d]?.[g] ?? 0);
          }
          max_reportes_diarios = Math.max(max_reportes_diarios,reporte_diario);
          reportes_mensuales += reporte_diario;
          rd[d] = reporte_diario;
        }
      }
      
      
      const dias_reportados_length = Object.keys(dias_reportados).length;
      const reportes_promedio = dias_reportados_length? (reportes_mensuales/dias_reportados_length) : 0;
      {
        let igini = 1;
        if(reportes_mensuales > 0){
          let abs_diff_sum = 0;
          for (const d1 in dias_reportados) {
            for (const d2 in dias_reportados){
              abs_diff_sum += Math.abs(rd[d1] - rd[d2]);
            }
          }
          igini = 1 - (abs_diff_sum / (2*dias_reportados_length*reportes_mensuales)); 
        }
        const p = dias_reportados_length/dias_mes_totales;
        inv_gini.push({
          x: x,
          y: igini,
          custom: {
            p: p
          }
        });
      }
      
      const sorted_reportes_diarios = Object.values(rd).filter((x) => x > 0).sort((a, b) => a - b);
      const qs = [
        getQuantile(sorted_reportes_diarios,0.00),
        getQuantile(sorted_reportes_diarios,0.25),
        getQuantile(sorted_reportes_diarios,0.50),
        getQuantile(sorted_reportes_diarios,0.75),
        getQuantile(sorted_reportes_diarios,1.00)
      ];
      
      reportes_por_dia_qs.push({
        x: x,
        low:    qs[0].q,
        q1:     qs[1].q,
        median: qs[2].q,
        q3:     qs[3].q,
        high:   qs[4].q,
        custom: {
          dlow: (qs[0].idx+1),
          dq1: (qs[1].idx+1),
          dmedian: (qs[2].idx+1),
          dq3: (qs[3].idx+1),
          dhigh: (qs[4].idx+1),
        }
      });
      
            
      const mes_esta = data?.[a]?.[m] ?? null;        
      if(mes_esta === null){
        if(!encontro_inicio){
          skip_inicial++;
        }
        comenzo_final = true;
        skip_final++;
      }
      else{
        encontro_inicio = true;
        comenzo_final = false;
        skip_final = null;
      }
      
      reportes_por_dia.push({
        x: x,
        //name: label,
        y: reportes_promedio
      });
      
      const p = dias_reportados_length/dias_mes_totales;
      reportes_por_mes.push({
        x: x,
        //name: label,
        y: reportes_mensuales,
        color: 'rgb('+c_to_rgb(bezierColor(p)).join(',')+')',
        custom: {
          porcentaje: Math.round(p*100*100)/100.0
        }
      });
      
      plotLines.push({
        value: x+0.5,      
        color: m == 12? '#090909' : '#aaaaaa',
        width:  2,
        zIndex: 3,          
      });
      x++;
    }
  }
    
  meses = meses.slice(skip_inicial ?? 0,meses.length-skip_final);
  reportes_por_mes = reportes_por_mes.slice(skip_inicial ?? 0,reportes_por_mes.length-skip_final);
  reportes_por_dia_qs = reportes_por_dia_qs.slice(skip_inicial ?? 0,reportes_por_dia_qs.length-skip_final);
  inv_gini = inv_gini.slice(skip_inicial ?? 0,inv_gini.length-skip_final);
  //meses_estados = meses_estados.slice(skip_inicial ?? 0,meses_estados.length-skip_final);
  //meses_estados = meses_estados.flat();
  reportes_por_dia = reportes_por_dia.slice(skip_inicial ?? 0,reportes_por_dia.length-skip_final);
  
  if(meses.length != reportes_por_dia.length){
    throw 'Error de implementación';
  }
  const div_diario      = $('<div>').css('width','100%').attr('data-key','diario');
  const div_uniformidad = $('<div>').css('width','100%').attr('data-key','uniformidad');
  $(div).append(div_diario);
  $(div).append(div_uniformidad);
  //$(div).append(div_completitud);
    
  const highcharts_obj_base = {
    chart: {
      zoomType: 'xy'
    },
    tooltip: {
      shared: true,
      useHTML: true,
      formatter: function(){
        let s = '';
        const x = this.points[0].point.x;
        const uniformidad_mes = this.points.filter(function(p){
          return p.series.options.id == 'uniformidad-mes';
        })?.[0]?.series;
        const reportes_dia = this.points.filter(function(p){
          return p.series.options.id == 'reportes-dia';
        })?.[0]?.series;
        const reportes_dia_qs = this.points.filter(function(p){
          return p.series.options.id == 'reportes-dia-qs';
        })?.[0]?.series;
        
        const r2d = (f) => Math.round(f*100)/100.0;
        
        s += '<div style="width: 100%;">';
        uniformidad_mes?.points?.filter(p => p.x === x).forEach(function(point){
          s += '<p><b>Uniformidad de reportes:</b> '+r2d(point.y)+'</p>';
          s += '<p><b>Días reportados:</b> '+r2d(100*point.custom.p)+'%</p>';
        });
        s += '</div>';
        
        s += '<div style="width: 100%;">';
        reportes_dia?.points?.filter(p => p.x === x).forEach(function(point){
          s += '<p><b>Reportes por día:</b> '+r2d(point.y)+'</p>';
        });
        s += '</div>';
        
        s += '<div style="width: 100%;">';
        reportes_dia_qs?.points?.filter(p => p.x === x).forEach(function(point){
          s += '<p><b>Mínimo:</b> '+r2d(point.low)+' ('+r2d(point.custom.dlow)+')</p>';
          s += '<p><b>Q1:</b> '+r2d(point.q1)+' ('+r2d(point.custom.dq1)+')</p>';
          s += '<p><b>Media:</b> '+r2d(point.median)+' ('+r2d(point.custom.dmedian)+')</p>';
          s += '<p><b>Q3:</b> '+r2d(point.q3)+' ('+r2d(point.custom.dq3)+')</p>';
          s += '<p><b>Máximo:</b> '+r2d(point.high)+' ('+r2d(point.custom.dhigh)+')</p>';
        });
        s += '</div>';
        
        return s;
      }
    },
    xAxis: [{
      categories: meses, 
      crosshair: true,
      plotLines: plotLines,
      labels: {
        style: {
          fontWeight: 'bold',
          color: '#0f172a',
          fontSize: '11px'
        }
      }
    }],
    legend: {
      enabled: true,
    }
  };
  
  Highcharts.chart(div_diario[0], {
    ...highcharts_obj_base,
    title: {
      text: 'Reportes diarios'
    },
    plotOptions: {
      boxplot: {
        maxPointWidth: 30 // Caps the width at 30 pixels so they look slim and elegant
      }
    },
    yAxis: [{
      title: { text: 'Reportes por día' },
      min: 0,
      max: max_reportes_diarios,
      //opposite: true
    }],
    series: [
      {
        id: 'reportes-dia-qs',
        name: 'Cuartiles',
        type: 'boxplot',
        yAxis: 0,
        data: reportes_por_dia_qs,
        color: 'rgb(0,0,100)',
        showInLegend: true,
        zIndex: 2
      },
      {
        id: 'reportes-dia',
        name: 'Reportes por día',
        type: 'spline',
        yAxis: 0,
        data: reportes_por_dia,
        color: 'rgb(50,50,50)',
        showInLegend: true,
        zIndex: 3
      },
    ]
  });
  
  Highcharts.chart(div_uniformidad[0], {
    ...highcharts_obj_base,
    title: {
      text: 'Uniformidad y % reportado'
    },
    yAxis: [{
      title: { text: 'Uniformidad de reportes diarios' },
      min: 0,
      max: 1,
    }],
    colorAxis: {
      min: 0,
      max: 1,
      stops: [
        [0, 'rgb(255,50,0)'],   // 0.0: Vibrant Red (Low uniformity)
        [0.5, 'rgb(255,255,0)'], // 0.5: Bright Yellow (Midpoint bypasses brown!)
        [1, 'rgb(0,255,0)']    // 1.0: Emerald Green (High uniformity)
      ]
    },
    series: [
      {
        id: 'uniformidad-mes',
        name: 'Uniformidad (con % reportado)',
        type: 'column',
        yAxis: 0,
        data: inv_gini,
        colorKey: 'custom.p',
        showInLegend: true,
        zIndex: 1
      }
    ]
  });
}

$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Registros DNIs');
  
  $('[data-js-change-visualizando]').change(function(e){
    const o = e.currentTarget;
    const selec = $($(o).attr('data-js-change-visualizando'));
    selec.attr('data-visualizando',$(o).val());
  }).trigger('change');
  
  $('[data-js-change-clear]').change(function(e){
    const o = e.currentTarget;
    const selec = $($(o).attr('data-js-change-clear'));  
    $(selec).val('').trigger('onlyset');
  });
  
  $('[data-js-change-set]').on('change onlyset',function(e){
    const o = e.currentTarget;
    const selec = $($(o).attr('data-js-change-set'));
    selec.val($(o).val());
  }).trigger('onlyset');//Setea los hidden inputs el trigger
  
  $('[data-js-change-trigger-buscar]').change(function(e){
    const o = e.currentTarget;
    const selec = $($(o).attr('data-js-change-trigger-buscar'));
    selec.trigger('buscar');
  }).eq(0).trigger('change');//Trigereo solo 1 change para que busque al iniciar la pantalla
    
  $('[data-js-click-mostrar-modal]').click(function(e){
    const o = e.currentTarget;
    const selec = $(o).attr('data-js-click-mostrar-modal');
    const params = JSON.parse($(o).attr('data-js-click-mostrar-modal-params') ?? '{}');
    $(selec).trigger('mostrar',params ?? {});
  });
  
  $('[data-js-modal-importar-registros-dni]').each(function(_,Mobj){
    const M = $(Mobj);
    M.find('#archivo').change(function(e){
      $(e.currentTarget).trigger('fileselect');//@HACK para que dispare en md5.js
    });
    M.on('mostrar',function(e,params){
      M.find('[name]:not([readonly])').val('');
      M.find('[data-js-fecha]').data('datetimepicker').reset();
      M.modal('show');
    });
  });
  
  $('[data-js-click-submit-form]').click(function(e){
    const o = e.currentTarget;
    const select = $(o).attr('data-js-click-submit-form');
    const $form = $(select);
    const form = $form?.[0] ?? undefined;
    const formData = new FormData(form);
    const ajax_params = JSON.parse($form.attr('data-ajax-params') ?? '{}') ?? {};
    const modal_cargando = $('[data-js-modal-cargando]').eq(0).modal('show');
    $.ajax({
      type: $form.attr('method'),
      url: $form.attr('action'),
      data: formData,
      ...ajax_params,
      success: function (data) {
        modal_cargando.modal('hide');
        $('[data-js-filtro-tabla]').trigger('buscar');
        AUX.mensajeExito(data?.mensaje ?? '');
        $(o).closest('.modal').modal('hide');
      },
      error: function (data) {
        modal_cargando.modal('hide');
        const json = data.responseJSON ?? {};
        AUX.mensajeError(json?.mensaje ?? '');
        AUX.mostrarErroresNames($form,json);
        console.log(data);
      }
    });
  });
  
  const f_click_borrar = function(e){
    const tgt = $(e.currentTarget);
    const url = tgt.attr('data-js-click-borrar');
    const id  = tgt.val();
    const modal_cargando = $('[data-js-modal-cargando]').eq(0);
    $('[data-js-modal-eliminar]').trigger('mostrar',[{
      url: url+'/'+id,
      url_params: {},
      mensaje: 'Esta seguro que desea eliminarlo',
      success: function(data){
        AUX.mensajeExito(data?.mensaje ?? '');
        $('[data-js-filtro-tabla]').trigger('buscar');
      },
      error: function (data) {
        const json = data.responseJSON ?? {};
        AUX.mensajeError(json?.mensaje ?? '');
        console.log(data);
      },
      ext_params: {
        beforeSend: function(){
          modal_cargando.modal('show');
        },
        complete: function(){
          modal_cargando.modal('hide');
        }
      }
    }]);
  };
  
  const f_click_asignar = function(e){
    const o = e.currentTarget;
    const md5 = $(o).find('[data-key="md5"]').text();
    $('input[name="md5"]').val(md5);
    const target_view = $(o).attr('data-js-click-asignar-md5');
    $('[data-js-change-visualizando]').val(target_view).trigger('change');
    $('[data-js-filtro-tabla]').trigger('buscar');
  };
  
  //Para el boton limpiar
  $('[data-js-click-asignar-md5]').click(f_click_asignar);
  
  $('[data-visible="importaciones"],[data-visible="registros"]').find('[data-js-filtro-tabla]').each(function(idx,fObj){ 
    $(fObj).on('busqueda',function(e,ret,tbody,molde){  
      ret.data.forEach(function(r){
        const fila = molde.clone();
        for(const k in r){
          fila.find(`[data-key="${k}"]`).text(r[k] ?? '-');
        }
        fila.find('button').val(r.id_registros_dni_importacion ?? r.id_registros_dni);
        fila.attr('data-table-key-id',r.id_registros_dni_importacion? 'id_registros_dni_importacion' : 'id_registros_dni');
        fila.attr('data-table-key-val',r.id_registros_dni_importacion ?? r.id_registros_dni);
        tbody.append(fila);
      });
      tbody.find('[data-js-click-borrar]').click(f_click_borrar);
      tbody.find('[data-js-click-asignar-md5]').click(f_click_asignar);
    });
  });
  
  $('[data-visible="estadisticas"]').find('[data-js-filtro-tabla]').each(function(idx,fObj){
    $(fObj).on('busqueda',function(e,ret,tbody,molde){  
      const tr = molde.clone();
      const div = tr.find('[data-key="estadisticas"]');
      estadisticas(div[0],ret.data);
      tbody.append(tr);
    });
  });
  
  $('[data-js-click-descargar]').click(function(e){
    const o = e.currentTarget;
    const form = $(o).closest('[data-js-filtro-tabla]').find('[data-js-filtro-tabla-filtro] form');
    const url = $(o).attr('data-js-click-descargar');
    const fd = new FormData(form?.[0]);
    const params = (new URLSearchParams(fd)).toString();
    window.open(url+'?'+params,'_blank');
  });
  
});
