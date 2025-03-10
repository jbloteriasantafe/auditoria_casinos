import "/js/lib/decimal.js";

import "/js/Components/inputFecha.js";
import {AUX} from "/js/Components/AUX.js";

import "/js/highcharts_11_3_0/highcharts.js";
import "/js/highcharts_11_3_0/highcharts-3d.js";
import "/js/highcharts_11_3_0/exporting.js";
import "/js/highcharts_11_3_0/export-data.js";
import "/js/highcharts_11_3_0/accessibility.js";
import "/js/highcharts_11_3_0/modules/drilldown.js";

function prepararParaPresentar(data, categorias) {
  categorias.sort();
  const results = {};
  {
    const cats_count = categorias.length;
    const used = new Array(cats_count).fill('N');
    let used_count = 0;
    
    const recursive = (carry) => {
      const used_str = used.join('');
      results[used_str] = results[used_str] || [];
      results[used_str].push(carry);

      if (used_count === cats_count) {
        return;
      }

      for (let cidx = 0; cidx < cats_count; cidx++) {
        if (used[cidx] === 'S') continue;
        used[cidx] = 'S';
        used_count++;
        carry.push(categorias[cidx]);

        recursive(carry);

        used[cidx] = 'N';
        used_count--;
        carry.pop();
      }
    };

    recursive([]);
  }
  
  const groups = [];
  const vals = [];

  Object.keys(results).forEach((usd) => {
    const comb_cats = [];
    categorias.forEach((cat, catidx) => {
      if (usd[catidx] === 'S') comb_cats.push(cat);
    });

    const comb_groups = data.reduce((acc, item) => {
      const groupKey = comb_cats.map((cat, catidx) => item[cat]);
      const key = JSON.stringify(groupKey);

      if (!acc[key]) {
        acc[key] = {
          group: groupKey,
          items: []
        };
      }

      acc[key].items.push(item);
      return acc;
    }, {});

    const comb_groups_mapped = Object.values(comb_groups).map((group) => {
      const r = {};
      comb_cats.forEach((cat, cidx) => {
        r[cat] = group.group[cidx];
      });
      r.cantidad = group.items.reduce((carry, item) => {
        return new Decimal(carry).plus(item.cantidad).toFixed(2);
      }, 0);
      return r;
    });

    groups.push(comb_cats);
    vals.push(comb_groups_mapped);
  });

  return { 'groups': groups, 'vals': vals };
}

function lexicalComp(s1,s2){
  return -((s1<s2)+0)+((s1>s2)+0);
}
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
function simplificarEsp(n){
  if(n == null) return n;
  if(Math.abs(n) >= 1000000){
    return formatEsp((n/1000000).toFixed(1))+'M';
  }
  if(Math.abs(n) >= 1000){
    return formatEsp((n/1000).toFixed(1))+'k';
  }
  return n;
}

function generarGraficoTortaSubcategorias(div,titulo,
  nombre_categoria,categorias,por_categoria,
  nombre_subcategoria,subcategorias,por_categoria_por_subcategoria
){
  categorias.sort();
  subcategorias.sort();
  
  const dataseries = [];
  for(const cat of categorias){
    const val = por_categoria[cat] ?? null;
    dataseries.push({name: cat,y: parseFloat(val),drilldown: cat});
  }
  
  const dataseries_sub = [];
  for(const cat of categorias){
    const aux = {
      id: cat,
      name: nombre_subcategoria,
      data: []
    };
    for(const subcat of subcategorias){
      const val = por_categoria_por_subcategoria?.[cat]?.[subcat] ?? null;
      aux.data.push([subcat,parseFloat(val)]);
    }
    
    dataseries_sub.push(aux);
  }
      
  const grafico = div.addClass('grafico col-md-12');
  div.append(grafico);
  Highcharts.chart(grafico[0], {
    chart: {
      height: 450,
      backgroundColor: "#fff",
      type: 'pie',
      options3d: {
        enabled: true,
        alpha: 45,
        beta: 0
      },
    },
    title: { 
      text: titulo, 
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
      formatter: function(){return this.key+` - ${formatEsp(this.y.toFixed(2))} / ${formatEsp(this.total.toFixed(2))} - <b>${formatPje(this.percentage)}</b>`;}
    },
    plotOptions: {
      series: {
        allowPointSelect: true,
        cursor: 'pointer',
        depth: 35,
        dataLabels: {
          enabled: true,
          formatter: function(){return formatEsp(this.y.toFixed(2));},
          distance: 20,
          style: {
            textOutline: 'none' 
          }
        },
        showInLegend: true
      }
    },
    series: [{
      name: nombre_categoria,
      data: dataseries
    }],
    drilldown: {
      series: dataseries_sub
    }
  });
}

function generarGraficoColumnasComparativasSubcategorias(div,titulo,
  nombre_categoria,categorias,por_categoria,
  nombre_subcategoria,subcategorias,por_categoria_por_subcategoria,
  nombre_subsubcategoria,subsubcategorias,por_categoria_por_subcategoria_por_subsubcategoria,
  opts = {}){
    
  const mainData = [];
  const drilldown = {
    activeDataLabelStyle: {
      cursor: 'default',
      color: null,
      fontWeight: null,
      textDecoration: null
    },
    allowPointDrilldown: false,
    series: []
  };
    
  for(const subcat of subcategorias){
    const aux = {
      id: subcat,
      name: subcat,
      //stack: nombre_subcategoria,
      data: []
    };
    
    for(const cat of categorias){
      aux.data.push({name: cat,y: por_categoria_por_subcategoria?.[cat]?.[subcat] ?? null});
    }
    
    mainData.push(aux);
  }
  
  for(const subcat of subcategorias){
    const aux = {
      name: 'Año-Mes',
      linkedTo: subcat,
      grouping: false,
      dataLabels: {
        enabled: false
      },
      data: []
    };
    for(const cat of categorias){
      aux.data.push({name: cat,drilldown: cat+' '+subcat});
    }
    mainData.push(aux);
  }
    
  for(const subcat of subcategorias){
    for(const cat of categorias){
      const aux = {
        id: cat+' '+subcat,
        name: cat+' '+subcat,
        data: []
      };
      
      for(const subsubcat of subsubcategorias){
        aux.data.push([subsubcat,por_categoria_por_subcategoria_por_subsubcategoria?.[cat]?.[subcat]?.[subsubcat] ?? null]);  
      }
      
      drilldown.series.push(aux);
    }
  }
  const grafico = $('<div>').addClass('grafico col-md-12');
  div.append(grafico);
  
  const highchart_opts = {
    chart: {
      type: 'column'
    },
    title: {
      text: titulo
    },
    xAxis: {
      title: {
        text: nombre_categoria,
      },
      type: 'category',
      step: 1
    },
    yAxis: {
      title: {
        text: ''
      },
      min: 0
    },
    tooltip: { 
      formatter: function(){return this.series.name+' - '+formatEsp(this.y)+' / '+formatEsp(this.total);}
    },
    plotOptions: {
      series: {
        stacking: 'normal',
        cursor: 'pointer',
        dataLabels: {
          formatter: function(){
            return simplificarEsp(this.y);
          }
        }
      },
      column: {
        stacking: 'normal',
        dataLabels: {
          enabled: true,
          distance: 20,
          style: {
            textOutline: 'none' 
          }
        }
      }
    },
    series: mainData,
    drilldown: drilldown
  };
  
  const mergeDeep = function(...objects) {
    const isObject = obj => obj && typeof obj === 'object';
    
    return objects.reduce((prev, obj) => {
      Object.keys(obj).forEach(key => {
        const pVal = prev[key];
        const oVal = obj[key];
        
        if (Array.isArray(pVal) && Array.isArray(oVal)) {
          prev[key] = pVal.concat(...oVal);
        }
        else if (isObject(pVal) && isObject(oVal)) {
          prev[key] = mergeDeep(pVal, oVal);
        }
        else {
          prev[key] = oVal;
        }
      });
      
      return prev;
    }, {});
  };
  
  Highcharts.chart(grafico[0], mergeDeep(highchart_opts,opts));
}

//Modificado de https://gist.github.com/holmberd/945375f099cbb4139e37fef8055bc430
function keyBy(data,propiedad,val_access = function(x){return x;}){
  return data.reduce((carry, obj) => {
    const key = obj[propiedad];
    carry[key] = val_access(obj);
    return carry;
  }, {});
}

function groupBy(data,propiedad){
  return data.reduce((carry, obj) => {
    const key = obj[propiedad];
    if (!carry[key]) {
      carry[key] = [];
    }
    carry[key].push(obj);
    return carry;
  }, {});
}

function nestGroupsBy(data, propiedades, val_access = function(x){return x;}, propidx=0) {
  if (propidx >= propiedades.length) return null;
  
  const last_prop = propidx == (propiedades.length-1);
  const propiedad = propiedades[propidx];
  if(last_prop){
    return keyBy(data, propiedad, val_access)
  }
  const agrupado  = groupBy(data, propiedad);
  for (const key in agrupado) {
    agrupado[key] = nestGroupsBy(agrupado[key], propiedades, val_access,propidx+1);
  }
  return agrupado;
}

function extraerObjeto(data,categorias){
  const sorted_categorias = [...categorias];
  sorted_categorias.sort();
  const gidx = data.groups.findIndex(x => x.toString() == sorted_categorias.toString());//@SLOW?
  if(gidx == -1) return [];
  const access_function = function(x){return x?.cantidad? parseFloat(x.cantidad) : null;};
  return nestGroupsBy(data.vals[gidx],categorias,access_function);
}

$(function(){ $('[data-tablero-inicio]').each(function(){
  const  T = $(this);
  const $T = T.find.bind(T);
  
  {
    const hoy = new Date();
    $T('[name="periodo[1]"]').parent('[data-js-fecha]').data('datetimepicker').setDate(hoy);
    hoy.setFullYear(hoy.getFullYear()-1);
    $T('[name="periodo[0]"]').parent('[data-js-fecha]').data('datetimepicker').setDate(hoy);
  }
  
  function GET(loadingDiv,url,success = function(data){},error = function(data){}){
    if(loadingDiv.length == 0) return;
    let progress = 0;
    const intervalID = setInterval(function(){
        const message = ['―','/','|','\\'];
        loadingDiv.css('text-align','center').text(message[progress]);
        progress = (progress + 1)%4;
    },100);
    
    AUX.GET(
      url,
      {'periodo': [$T('[name="periodo[0]"]').val(),$T('[name="periodo[1]"]').val()]},
      function(data){
        clearInterval(intervalID);
        loadingDiv.css('text-align','unset').text('');
        success(data);
      },
      function(data){
        console.log(data);
        clearInterval(intervalID);
        loadingDiv.css('text-align','unset').text(' ERROR DE CARGA ');
        error(data);
      }
    );
  }
  
  Highcharts.theme = {
    colors: ['#4285F4', '#EA4335', '#FBBC04', '#34A853', '#D2E3FC', '#FAD2CF',' #FEEFC3', '#CEEAD6', '#F1F3F4'],
  };
  // Apply the theme
  Highcharts.setOptions(Highcharts.theme);
  
  T.on('redraw',function(){
    GET($T('#divBeneficiosAnualesPorCasino,#divBeneficiosAnualesPorActividad,#divBeneficiosMensuales'),'informesGenerales/beneficios',function(data_raw){
      const data = prepararParaPresentar(data_raw,['Casino','Periodo','Actividad']);
      
      const totalizado_por_casino = extraerObjeto(data,['Casino']);
      const totalizado_por_casino_actividad = extraerObjeto(data,['Casino','Actividad']);
      const totalizado_por_actividad = extraerObjeto(data,['Actividad']);
      const totalizado_por_actividad_casino = extraerObjeto(data,['Actividad','Casino']);
      const totalizado_por_periodo = extraerObjeto(data,['Periodo']);
      const totalizado_por_periodo_casino = extraerObjeto(data,['Periodo','Casino']);
      const totalizado_por_periodo_casino_actividad = extraerObjeto(data,['Periodo','Casino','Actividad']);
      const totalizado_por_periodo_actividad = extraerObjeto(data,['Periodo','Actividad']);
      const totalizado_por_periodo_actividad_casino = extraerObjeto(data,['Periodo','Actividad','Casino']);
      
      const keys_totalizado_por_casino = Object.keys(totalizado_por_casino).sort();
      const keys_totalizado_por_actividad = Object.keys(totalizado_por_actividad).sort();
      const keys_totalizado_por_periodo = Object.keys(totalizado_por_periodo).sort();
      
      generarGraficoTortaSubcategorias($T('#divBeneficiosAnualesPorCasino').append('div'),'BENEFICIOS TOTALES EN PESOS POR CASINO',
        'Casino',keys_totalizado_por_casino,totalizado_por_casino,
        'Actividad',keys_totalizado_por_actividad,totalizado_por_casino_actividad
      );
      generarGraficoTortaSubcategorias($T('#divBeneficiosAnualesPorActividad'),'BENEFICIOS TOTALES EN PESOS POR ACTIVIDAD',
        'Actividad',keys_totalizado_por_actividad,totalizado_por_actividad,
        'Casino',keys_totalizado_por_casino,totalizado_por_actividad_casino
      );
      
      generarGraficoColumnasComparativasSubcategorias(
        $T('#divBeneficiosMensuales'),'BENEFICIOS TOTALES EN PESOS P/MES P/CASINO',
        'Periodo',keys_totalizado_por_periodo,totalizado_por_periodo,
        'Casino',keys_totalizado_por_casino,totalizado_por_periodo_casino,
        'Actividad',keys_totalizado_por_actividad,totalizado_por_periodo_casino_actividad
      );
      
      generarGraficoColumnasComparativasSubcategorias(
        $T('#divBeneficiosMensuales'),'BENEFICIOS TOTALES EN PESOS P/MES P/ACTIVIDAD',
        'Periodo',keys_totalizado_por_periodo,totalizado_por_periodo,
        'Actividad',keys_totalizado_por_actividad,totalizado_por_periodo_actividad,
        'Casino',keys_totalizado_por_casino,totalizado_por_periodo_actividad_casino,
      );
    });
    
    GET($T('#divAutoexcluidosAnualesPorCasino,#divAutoexcluidosAnualesPorEstado,#divAutoexcluidosMensuales,#divDistribucionAutoexcluidosProvincias,#divDistribucionAutoexcluidosDepartamentos'),'informesGenerales/autoexcluidos',function(data_raw){
      const data = prepararParaPresentar(data_raw,['Casino','Periodo','Estado','Provincia','Departamento']);
      
      const totalizado_por_casino = extraerObjeto(data,['Casino']);
      const totalizado_por_casino_estado = extraerObjeto(data,['Casino','Estado']);
      const totalizado_por_estado = extraerObjeto(data,['Estado']);
      const totalizado_por_estado_casino = extraerObjeto(data,['Estado','Casino']);
      const totalizado_por_periodo = extraerObjeto(data,['Periodo']);
      const totalizado_por_periodo_casino = extraerObjeto(data,['Periodo','Casino']);
      const totalizado_por_periodo_casino_estado = extraerObjeto(data,['Periodo','Casino','Estado']);
      const totalizado_por_periodo_estado = extraerObjeto(data,['Periodo','Estado']);
      const totalizado_por_periodo_estado_casino = extraerObjeto(data,['Periodo','Estado','Casino']);
      const totalizado_por_provincia = extraerObjeto(data,['Provincia']);
      const totalizado_por_provincia_casino = extraerObjeto(data,['Provincia','Casino']);
      const totalizado_por_provincia_casino_estado = extraerObjeto(data,['Provincia','Casino','Estado']);
      const totalizado_por_departamento = extraerObjeto(data,['Departamento']);
      const totalizado_por_departamento_casino = extraerObjeto(data,['Departamento','Casino']);
      const totalizado_por_departamento_casino_estado = extraerObjeto(data,['Departamento','Casino','Estado']);
      
      const keys_totalizado_por_casino = Object.keys(totalizado_por_casino).sort();
      const keys_totalizado_por_estado = Object.keys(totalizado_por_estado).sort();
      const keys_totalizado_por_periodo = Object.keys(totalizado_por_periodo).sort();
      
      generarGraficoTortaSubcategorias($T('#divAutoexcluidosAnualesPorCasino'),'AUTOEXCLUIDOS TOTALES POR CASINO',
        'Casino',keys_totalizado_por_casino,totalizado_por_casino,
        'Estado',keys_totalizado_por_estado,totalizado_por_casino_estado
      );
      generarGraficoTortaSubcategorias($T('#divAutoexcluidosAnualesPorEstado'),'AUTOEXCLUIDOS TOTALES POR ESTADO',
        'Estado',keys_totalizado_por_estado,totalizado_por_estado,
        'Casino',keys_totalizado_por_casino,totalizado_por_estado_casino
      );
          
      generarGraficoColumnasComparativasSubcategorias(
        $T('#divAutoexcluidosMensuales'),'AUTOEXCLUIDOS MENSUALES P/CASINO',
        'Periodo',keys_totalizado_por_periodo,totalizado_por_periodo,
        'Casino',keys_totalizado_por_casino,totalizado_por_periodo_casino,
        'Estado',keys_totalizado_por_estado,totalizado_por_periodo_casino_estado
      );
      generarGraficoColumnasComparativasSubcategorias(
        $T('#divAutoexcluidosMensuales'),'AUTOEXCLUIDOS MENSUALES P/ESTADO',
        'Periodo',keys_totalizado_por_periodo,totalizado_por_periodo,
        'Estado',keys_totalizado_por_estado,totalizado_por_periodo_estado,
        'Casino',keys_totalizado_por_casino,totalizado_por_periodo_estado_casino,
      );
      
      const keys_totalizado_por_provincia = Object.keys(totalizado_por_provincia).sort((a,b) => {
        const ord = totalizado_por_provincia[a]-totalizado_por_provincia[b];
        if(ord != 0) return -ord;
        return a >= b;
      });
      
      const keys_totalizado_por_departamento = Object.keys(totalizado_por_departamento).sort((a,b) => {
        const ord = totalizado_por_departamento[a]-totalizado_por_departamento[b];
        if(ord != 0) return -ord;
        return a >= b;
      });
      
      generarGraficoColumnasComparativasSubcategorias(
        $T('#divDistribucionAutoexcluidosProvincias'),'Provincia de origen de Autoexcluidos (aproximado)',
        'Provincia',keys_totalizado_por_provincia,totalizado_por_provincia,
        'Casino',keys_totalizado_por_casino,totalizado_por_provincia_casino,
        'Estado',keys_totalizado_por_estado,totalizado_por_provincia_casino_estado,
        { yAxis: { min: 1, type: 'logarithmic' } }
      );
      
      generarGraficoColumnasComparativasSubcategorias(
        $T('#divDistribucionAutoexcluidosDepartamentos'),'Departamento de origen de Autoexcluidos (aproximado)',
        'Departamento',keys_totalizado_por_departamento,totalizado_por_departamento,
        'Casino',keys_totalizado_por_casino,totalizado_por_departamento_casino,
        'Estado',keys_totalizado_por_estado,totalizado_por_departamento_casino_estado,
        { yAxis: { min: 1, type: 'logarithmic' } }
      );
    });
  });
  
  T.trigger('redraw');
  $T('[name="periodo[1]"],[name="periodo[0]"]').change(function(){
    T.trigger('redraw');
  });
})});
