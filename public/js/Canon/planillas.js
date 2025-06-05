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

function exportToExcel(table,sheetname='Sheet1',filename='File.xlsx') {
    const workbook = XLSX.utils.book_new();
    
    // Convert table to worksheet with styles
    const worksheet = htmlTableToWorksheet(table);
    
    // Add worksheet to workbook
    XLSX.utils.book_append_sheet(workbook, worksheet, sheetname);
    
    // Export the workbook
    XLSX.writeFile(workbook,filename);
}

function htmlTableToWorksheet(table) {
  const ws = XLSX.utils.table_to_sheet(table,{raw: true});
  const range = XLSX.utils.decode_range(ws['!ref']);
  {
    const primeros_headers = table.querySelector('th').closest('tr').querySelectorAll('th');
    const widths = [];
    const rootFontSize = parseInt(window.getComputedStyle(document.body).getPropertyValue('font-size').slice(0,-2));
    primeros_headers.forEach(function(th,thidx){
      const ch = Math.ceil(th.getBoundingClientRect().width/rootFontSize);
      return {ch: (ch+'')};
    });
    ws['!cols'] = widths;
  }
  /*
   * Hago todo este barullo porque necesito la esquina superior izquierda de cada celda
   * para estilizarlo bien, y como las celdas pueden tener rowSpan y colSpan hago lo siguiente:
   * 1. Encuentro las dimensiones maximas de la tabla y genero una matriz
   * 2. Para cada celda, la guardo en su posicion y la extiendo con NULL si tiene colSpan>1 o rowSpan>1
   *    Notablemente tengo que reencontrar la posición para cada celda buscando desde arriba a la izquierda
   * 3. Itero sobre la matriz, skipeando los nulos
   * */
  let max_width = -1/0;
  let max_height = 0;
  for(const child of table.children){
    if(!(child.tagName == 'TBODY' || child.tagName == 'THEAD')) continue;
    for(const tr of child.rows){
      let w = 0;
      for(const htmlCell of tr.cells){
        w+=htmlCell.colSpan;
      }
      max_width = Math.max(max_width,w);
      max_height++;
    }
  }
  
  const used = Array.from(
    {length: max_height},
    () => Array.from({length: max_width}, () => false)
  );
  
  for(const child of table.children){
    if(!(child.tagName == 'TBODY' || child.tagName == 'THEAD')) continue;
    for(const tr of child.rows){
      for(const htmlCell of tr.cells){
        let row = 0;
        let col = 0;
        let found_spot = false;
        for(;row<max_height;row++){
          for(col=0;col<max_width;col++){
            if(!used[row][col]){
              found_spot = true;
              break;
            }
          }
          if(found_spot){
            break;
          }
        }
        
        if(!found_spot){
          throw 'Unreachable';
        }
        
        for(let r=0;r<htmlCell.rowSpan;r++){
          for(let c=0;c<htmlCell.colSpan;c++){
            used[row+r][col+c] = true;
          }
        }
                
        const corners = [[row,col]];
        
        if(htmlCell.rowSpan > 1){
          corners.push([row+htmlCell.rowSpan-1,col]);
        }
        if(htmlCell.colSpan > 1){
          corners.push([row,col+htmlCell.colSpan-1]);
        }
        if(htmlCell.rowSpan > 1 && htmlCell.colSpan > 1){
          corners.push([row+htmlCell.rowSpan-1,col+htmlCell.colSpan-1]);
        }
        
        for(const c of corners){
          const corner = XLSX.utils.encode_cell({r: c[0], c: c[1]});
          let excelStyle = getExcelStyleFromHtml(htmlCell);
          if (excelStyle) {
            ws[corner] = ws[corner] || {t: 's',v: ''};
            ws[corner].s = excelStyle;
          }
        }
      }
    }
  }
  
  return ws;
}

function getExcelStyleFromHtml(cell) {
    const style = {};
    const computedStyle = window.getComputedStyle(cell);
    
    const fontWeight = computedStyle.getPropertyValue('font-weight');
    if (fontWeight && (fontWeight === 'bold' || parseInt(fontWeight) >= 700)) {
      style.font = style.font || {};
      style.font.bold = true;
    }
    
    const rootFontSize = parseInt(window.getComputedStyle(document.body).getPropertyValue('font-size').slice(0,-2));
    const fontSize = computedStyle.getPropertyValue('font-size')?.slice(0,-2);
    if (fontSize) {
      style.font = style.font || {};
      const rel_sz = parseInt(fontSize)/rootFontSize;
      style.font.sz = (11*rel_sz)+'';//11 es el default de exportacion
    }
    
    const bgColor = computedStyle.getPropertyValue('background-color');
    if (bgColor && bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') {
      style.fill = {
          patternType: "solid",
          fgColor: { rgb: hexToRgb(bgColor) || 'FFFFFF' }
      };
    }
    
    const textColor = computedStyle.getPropertyValue('color');
    if(textColor) {
      style.font = style.font || {};
      style.font.color = { rgb: hexToRgb(textColor) || '000000' };
    }
    
    const borders = ['right','bottom'];
    if(cell.closest('tr').querySelector('th','td') == cell){
      borders.push('left');
    }
    if(cell.closest('table').querySelector('th','td') == cell){
      borders.push('top');
    }
        
    for(const b of borders){
      const border = computedStyle.getPropertyValue(`border-${b}-width`);
      const color  = computedStyle.getPropertyValue(`border-${b}-color`);
      if(border && border !== '0px') {
        style.border = style.border || {};
        style.border[b] = { style: 'thin', color: { rgb: hexToRgb(color) || '000000' } };
      }
    }
    
    const textAlign = computedStyle.getPropertyValue('text-align');
    if(textAlign){
      style.alignment = style.alignment || {};
      style.alignment.horizontal = textAlign;
    }
    
    const verticalAlignCenter = cell.tagName == 'TH';
    if(verticalAlignCenter){
      style.alignment = style.alignment || {};
      style.alignment.vertical = 'center';
    }
    
    const overflowWrap = computedStyle.getPropertyValue('overflow-wrap');
    if(overflowWrap && (overflowWrap == 'break-word' || overflowWrap == 'anywhere')){
      style.alignment = style.alignment || {};
      style.alignment.wrapText = true;
    }
    
    return Object.keys(style).length > 0 ? style : undefined;
}

// Helper function to convert CSS color to Excel RGB format
function hexToRgb(cssColor) {
    // Handle rgb() format
    const rgbMatch = cssColor.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*\d+\.?\d*)?\)$/);
    if (rgbMatch) {
        const r = parseInt(rgbMatch[1]).toString(16).padStart(2, '0');
        const g = parseInt(rgbMatch[2]).toString(16).padStart(2, '0');
        const b = parseInt(rgbMatch[3]).toString(16).padStart(2, '0');
        return r + g + b;
    }
    
    // Handle hex format
    const hexMatch = cssColor.match(/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/);
    if (hexMatch) {
        let hex = hexMatch[1];
        if (hex.length === 3) {
            hex = hex.split('').map(c => c + c).join('');
        }
        return hex.toUpperCase();
    }
    
    return null;
}

$(document).ready(function() {
  $('[data-js-click-descargar-tabla]').click(function(e){
    const tgt = $(e.currentTarget);
    const selector = tgt.attr('data-js-click-descargar-tabla') ?? null;
    if(selector === null || selector.length == 0) return;
    const div = $(selector);
    const nombre_planilla = 
      (planilla.length? planilla : 'planilla')
    + (año.length? ('_'+año) : '')
    + (mes.length? ('_'+mes) : '')
    + (fecha_planilla.length? ('_'+fecha_planilla) : '')
    + '.xlsx';
    
    const table = div.find('table')?.[0] ?? null;
    if(table) exportToExcel(
      table,
      'Hoja 1',
      nombre_planilla
    );
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
