import "/js/jquery.js";

import "/js/highcharts_11_3_0/highcharts.js";
import "/js/highcharts_11_3_0/highcharts-more.js";
import "/js/highcharts_11_3_0/highcharts-3d.js";
import "/js/highcharts_11_3_0/modules/exporting.js";
import "/js/highcharts_11_3_0/modules/export-data.js";
import "/js/highcharts_11_3_0/modules/accessibility.js";
import "/js/highcharts_11_3_0/modules/drilldown.js";
import "/js/highcharts_11_3_0/modules/offline-exporting.js";

function formatEsp(n){
  n = n+'';
  const negativo = n?.[0] == '-'? '-' : '';
  n = negativo.length? n.substr(1) : n;
  
  const partes = n.split('.');
  let entero  = partes?.[0] ?? '';
  
  entero = entero.split('').reverse().join('')//Doy vuelta el numero... 
  .match(/(.{1,3}|^$)/g).map(function(s){return s.split('').reverse().join('');})//junto los miles y los pongo en orden
  .reverse().join('.');//Lo pongo en orden correcto y lo uno
  
  //Saco los ceros de sobra, y la parte decimal si es solo .000..
  let decimal = (partes?.[1] ?? '').replaceAll(/0+$/g,'')
  if(decimal.length){
    decimal = ','+decimal;
  }
  
  return negativo+entero+decimal;
}
function formatPje(f){
  return formatEsp(f.toFixed(2))+' %';
}

$(document).ready(function() {
  $('[data-js-click-seleccionar-tablas]').click(function(e){
    const tgt = $(e.currentTarget);
    const selector = tgt.attr('data-js-click-seleccionar-tablas') ?? null;
    if(selector === null || selector.length == 0) return;
    const div = $(selector);
    let range = null; 
    let sel = null;
    if (document.createRange && window.getSelection) {
      range = document.createRange();
      sel   = window.getSelection();
      sel.removeAllRanges();
      
      try {
        range.selectNodeContents(div[0]);
        sel.addRange(range);
      } catch (e) {
        console.log(e);
      }
    }
    //Copiar manualmente porque mantiene el formato de tabla al pegar el Sistema Operativo/navegador ...
    //navigator.clipboard.writeText(window.getSelection().toString());
  });
  
  Highcharts.setOptions({
    colors: colors
  });

  const url = new URL(window.location.href);
  const planilla = url.searchParams.get('planilla');
  const año = url.searchParams.get('año');
  if(planilla == 'canon_total'){
    const data_series_anual = {
      name: 'Anual',
      data: []
    };
    
    const data_series_mensual = [];
    
    for(const cidx in casinos){
      const cas = casinos[cidx];
      if(cas == 'Total') continue;
      
      const canon_anual = data[cas]?.[año]?.[0]?.canon_total ?? null;
      data_series_anual.data.push({
        name: cas,
        colorIndex: cidx,
        y: canon_anual !== null? parseFloat(canon_anual) : null
      });
      
      const cas_mensual = {
        name: cas,
        data: [],
        colorIndex: cidx
      };
      for(let m=1;m<=12;m++){
        const canon_mensual = data[cas]?.[año]?.[m]?.canon_total ?? null;
        cas_mensual.data.push({
          name: meses[m],
          y: canon_mensual !== null? parseFloat(canon_mensual) : null
        });
      }
      data_series_mensual.push(cas_mensual);
    }
    
    Highcharts.chart($('#graficoTorta')?.[0], {
      chart: {
        type: 'pie'
      },
      title: { 
        text: 'Canon Total Casinos '+año, 
        style: {
          fontWeight: 'bold'
        }
      },
      legend: {
        labelFormatter: function () {
          return this.name + " " + formatPje(this.percentage);
        },
        layout: 'horizontal',
        align: 'center',
        verticalAlign: 'bottom',
      },
      tooltip: { 
        formatter: function(){return this.key+` - ${'$ '+formatEsp(this.y.toFixed(2))} / ${'$ '+formatEsp(this.total.toFixed(2))} - <b>${formatPje(this.percentage)}</b>`;}
      },
      plotOptions: {
        series: {
          allowPointSelect: true,
          cursor: 'pointer',
          depth: 35,
          dataLabels: {
            enabled: true,
            formatter: function(){return '$ '+formatEsp(this.y.toFixed(2));},
            distance: 20,
            style: {
              textOutline: 'none' 
            }
          },
          showInLegend: true
        }
      },
      series: [data_series_anual],
    });
    
    Highcharts.chart($('#graficoLineas')?.[0],{
      chart: {
        type: 'line'
      },
      title: {
        text: 'Evolución Canon Por Mes Por Casino '+año
      },
      xAxis: {
        title: {
          text: 'Mes',
        },
        type: 'category',
        step: 1
      },
      yAxis: {
        title: {
          text: '$'
        },
      },
      tooltip: { 
        formatter: function(){return this.series.name+' - $'+formatEsp(this.y);}
      },
      plotOptions: {
        series: {
          cursor: 'pointer',
          dataLabels: {
            formatter: function(){
              return '$ '+formatEsp(this.y);
            }
          }
        },
        line: {
          dataLabels: {
            enabled: true,
            align: 'center',
            verticalAlign: 'middle',
            color: 'black',
            style: {
              textOutline: 'none' 
            }
          }
        }
      },
      series: data_series_mensual,
    });
  }
});
