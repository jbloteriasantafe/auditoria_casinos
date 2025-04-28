import "/js/lib/decimal.js";

import "/js/Components/inputFecha.js";
import {AUX} from "/js/Components/AUX.js";

import "/js/highcharts_11_3_0/highcharts.js";
import "/js/highcharts_11_3_0/highcharts-more.js";
import "/js/highcharts_11_3_0/highcharts-3d.js";
import "/js/highcharts_11_3_0/exporting.js";
import "/js/highcharts_11_3_0/export-data.js";
import "/js/highcharts_11_3_0/accessibility.js";
import "/js/highcharts_11_3_0/modules/drilldown.js";

function calcularTodosLosGrupos(data, categorias,count_key) {//Realiza todas las combinaciones posibles de categorias y ya las suma
  categorias.sort();
  const combinations = new Set();
  {//Encuentro todas las combinaciones para N categorias
    const find_combinations = (used,used_count) => {
      combinations.add(used.join(''));
      
      if (used_count === used.length) {
        return;
      }

      for (let uidx = 0; uidx < used.length; uidx++) {
        if (used[uidx] === 'S') continue;
        used[uidx] = 'S';
        find_combinations(used,used_count+1);
        used[uidx] = 'N';
      }
    };
    find_combinations(new Array(categorias.length).fill('N'),0);
  }
  
  const ret = [];
  
  for(const comb of combinations){
    const group_attrs = categorias.filter((cat,catidx) => {
      return comb[catidx] == 'S';
    });
    
    const groups_flattened = {};
    for(const d of data){
      const groupKey = group_attrs.map((gattr) => d[gattr]);
      const key = groupKey.join('|');
      
      if(!groups_flattened[key]){
        groups_flattened[key] = {};
        group_attrs.forEach((gattr) => {
          groups_flattened[key][gattr] = d[gattr];
        });
        groups_flattened[key].total = Decimal(0,{precision: 15});
        groups_flattened[key].count = 0; 
      }
      
      const val = d[count_key] ?? null;
      if(val !== null){
        groups_flattened[key].total = groups_flattened[key].total.plus(Decimal(val));
        groups_flattened[key].count += 1;
      }
    }
    
    for(const key in groups_flattened){
      const v = groups_flattened[key];
      groups_flattened[key].average = (new (Decimal.set({precision: 50}))(0))
      .plus(v.total).div(v.count).toFixed(4);
      groups_flattened[key].total   = v.total.toFixed(2);
    }
    
    ret.push([
      group_attrs,
      Object.values(groups_flattened)
    ]);
  }

  return ret;
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
      aux.data.push({
        name: cat,
        y: por_categoria_por_subcategoria?.[cat]?.[subcat] ?? null,
        drilldown: cat+' '+subcat
      });
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
          align: 'center',
          verticalAlign: 'middle',
          color: 'black',
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
function keyBy(data,propiedad,extract_key){
  return data.reduce((carry, obj) => {
    const key = obj[propiedad];
    carry[key] = obj[extract_key]? parseFloat(obj[extract_key]) : null;
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

function nestGroupsBy(data, propiedades, propidx, extract_key) {
  const propiedad = propiedades[propidx];
  if(propidx == (propiedades.length-1)){//Si es la ultima propiedad, uso keyby para guardar el valor en vez de un arreglo
    return keyBy(data, propiedad, extract_key)
  }
  const agrupado  = groupBy(data, propiedad);
  for (const key in agrupado) {
    agrupado[key] = nestGroupsBy(agrupado[key], propiedades, propidx+1, extract_key);
  }
  return agrupado;
}

function extraerObjeto(grupos,categorias,extract_key='total'){
  const sorted_categorias_str = [...categorias].sort().toString();//sort es destructivo, tengo que clonarlo con [...]
  const gidx = grupos.findIndex(G => G[0].toString() == sorted_categorias_str);//@SLOW?
  if(gidx == -1) return [];
  return nestGroupsBy(grupos[gidx][1],categorias,0,extract_key);
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
    
    loadingDiv.css('text-align','center').empty().append($('<i>').addClass('fa fa-spinner fa-spin'));
    
    AUX.GET(
      url,
      {'periodo': [$T('[name="periodo[0]"]').val(),$T('[name="periodo[1]"]').val()]},
      function(data){
        loadingDiv.css('text-align','unset').empty();
        success(data);
      },
      function(data){
        console.log(data);
        loadingDiv.css('text-align','unset').empty().text(' ERROR DE CARGA ');
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
      const data = calcularTodosLosGrupos(data_raw,['Casino','Periodo','Actividad'],'cantidad');
      
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
      const data = calcularTodosLosGrupos(data_raw,['Casino','Periodo','Estado','Provincia','Departamento'],'cantidad');
      
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
    
    const calcularCuartiles = function(sorted_obj_arr){
      const ret = {};
      
      for(const k in sorted_obj_arr){
        const length = sorted_obj_arr[k].length ?? 0;
        const max = length-1;
        
        ret[k] = [//No son exactamente cuartiles pero se aproximan cuando n es grande
          //interp a b
          [0,0,0],
          [max*0.25-Math.floor(max*0.25),Math.floor(max*0.25),Math.ceil(max*0.25)],
          [max*0.50-Math.floor(max*0.50),Math.floor(max*0.50),Math.ceil(max*0.50)],
          [max*0.75-Math.floor(max*0.75),Math.floor(max*0.75),Math.ceil(max*0.75)],
          [1,max,max]
        ].map(function(q){
          const t = q[0];
          const DA = sorted_obj_arr[k][q[1]];
          const DB = sorted_obj_arr[k][q[2]];
          if(length == 0) return null;
          return parseFloat(DA.mul(1-t).plus(DB.mul(t)).valueOf());
        });
      }
      
      return ret;
    }
    
    const calcularPdevs = function (apuestaObj, premioObj) {
      const D50 = Decimal.set({precision: 50});
      const pdevObj = {};
      let min_pdev = new D50(1 / 0);
      let max_pdev = new D50(-1 / 0);
      let avg_pdev = new D50(0);
      let pdev_count = 0;

      function processObjects(aObj, pObj, resultObj) {
        for (const key in aObj) {
          const a = aObj[key];
          const p = pObj[key];
          if(typeof aObj[key] === 'object'){
            resultObj[key] = resultObj[key] ?? {};
            processObjects(aObj[key],pObj[key],resultObj[key]);
          }
          else{
            const pdev = (new D50(p)).div(a).mul(100);
            resultObj[key] = pdev;
            if(!pdev.isNaN()) {
              min_pdev = Decimal.min(min_pdev, pdev);
              max_pdev = Decimal.max(max_pdev, pdev);
              avg_pdev = avg_pdev.plus(pdev);
              pdev_count++;
            }
          }
        }
      }
      
      processObjects(apuestaObj,premioObj,pdevObj);
      avg_pdev = pdev_count > 0? avg_pdev.div(pdev_count) : new D50(0);
      
      let stddev_pdev = new D50(0);
      function calcularStdDev(pObj) {
        if(pObj.minus){//Llegamos a un Decimal
          if(!pObj.isNaN()){
            stddev_pdev = stddev_pdev.plus(pObj.minus(avg_pdev).pow(2));
          }
          return;
        }
        for(const key in pObj) {
          calcularStdDev(pObj[key]);
        }
      }
      calcularStdDev(pdevObj);
      stddev_pdev = stddev_pdev.div(pdev_count).squareRoot();
      
      function obtenerSorted(pObj,resultObj) {//Elimina el ultimo nivel por un arreglo ordenado de pdevs
        let final = null;
        for(const key in pObj){
          if(final === null){//Me fijo si es el nivel final
            for(const key2 in pObj[key]){
              final = !!pObj[key][key2].minus;
              break;
            }
          }
          
          if(final){//Si es el nivel final, lo paso a un arreglo ordenado
            resultObj[key] = [];
            for(const key2 in pObj[key]){
              resultObj[key].push(pObj[key][key2]);
            }
            resultObj[key].sort();
            break;
          }
          //Si no es, sigo para adentro
          resultObj[key] = {};
          obtenerSorted(pObj[key],resultObj[key]);
        }
      }
      
      const pdevObjSorted = {};
      obtenerSorted(pdevObj,pdevObjSorted,null);

      return {
        min: min_pdev,
        max: max_pdev,
        avg: avg_pdev,
        count: pdev_count,
        stddev: stddev_pdev,
        pdev: pdevObj,
        sorted: pdevObjSorted
      };
    }
    
    const obtenerProducidosSemanales = function(colorIndex,casino,periodo,semana){
      let apuestaARS = [];
      let premioARS = [];
      
      AUX.GET(
        'informesGenerales/producidos_semana',
        {
          Casino: casino,
          Periodo: periodo,
          Semana: semana
        },
        function(data_raw){
          apuestaARS = calcularTodosLosGrupos(data_raw,['Fecha','RangoMaquina'],'ApuestaARS');
          premioARS  = calcularTodosLosGrupos(data_raw,['Fecha','RangoMaquina'],'PremioARS');
        },
        function(err){
          console.log(err);
        },
        {async: false}
      );
      
      const apuestaARS_fecha_rango = extraerObjeto(apuestaARS,['Fecha','RangoMaquina'],'total');
      const premioARS_fecha_rango  = extraerObjeto(premioARS,['Fecha','RangoMaquina'],'total');
      
      const fechas = [...new Set(
        Object.keys(extraerObjeto(apuestaARS,['Fecha'],'total')).concat(
          Object.keys(extraerObjeto(premioARS,['Fecha'],'total'))
        )
      )].sort();
      
      const rangos = [...new Set(
        Object.keys(extraerObjeto(apuestaARS,['RangoMaquina'],'total')).concat(
          Object.keys(extraerObjeto(premioARS,['RangoMaquina'],'total'))
        )
      )].sort();
      
      const pdev_fecha_rango = {};
      const pdevs = calcularPdevs(apuestaARS_fecha_rango,premioARS_fecha_rango);
      const pdev_fecha_sorted = {};
      for(const f in pdevs.pdev){
        pdev_fecha_sorted[f] = pdev_fecha_sorted[f] ?? [];
        for(const r in pdevs.pdev[f]){
          const pdev = pdevs.pdev[f][r];
          if(pdev !== undefined && !pdev.isNaN()){
            pdev_fecha_sorted[f].push(pdev);
          }
        }
        pdev_fecha_sorted[f].sort();
      }
      
      const series = {
        id: casino+' '+periodo+' '+semana,
        name: casino+' '+periodo+' '+semana,
        type: 'boxplot',
        colorIndex: colorIndex,
        data: [],
      };
      
      const cuartiles = calcularCuartiles(pdev_fecha_sorted);
      for(const f in cuartiles){
        const qs = cuartiles[f];
        series.data.push({
          name: f,
          low: qs[0],
          q1: qs[1],
          median: qs[2],
          q3: qs[3],
          high: qs[4]
        });
      }
      
      return series;
    };
    
    GET($('#divPorcentajesDevolucion'),'informesGenerales/producidos',function(data_raw){
      const apuestaARS = calcularTodosLosGrupos(data_raw,['Casino','Periodo','Semana','Fecha'],'ApuestaARS');
      const premioARS  = calcularTodosLosGrupos(data_raw,['Casino','Periodo','Semana','Fecha'],'PremioARS');
      const apuestaARS_casino_periodo_semana_fecha = extraerObjeto(apuestaARS,['Casino','Periodo','Semana','Fecha'],'total');
      const premioARS_casino_periodo_semana_fecha  = extraerObjeto(premioARS,['Casino','Periodo','Semana','Fecha'],'total');
      
      const casinos = [...new Set(
        Object.keys(extraerObjeto(apuestaARS,['Casino'],'total')).concat(
          Object.keys(extraerObjeto(premioARS,['Casino'],'total'))
        )
      )].sort();
      
      const periodos = [...new Set(
        Object.keys(extraerObjeto(apuestaARS,['Periodo'],'total')).concat(
          Object.keys(extraerObjeto(premioARS,['Periodo'],'total'))
        )
      )].sort();
      
      const semanas = [...new Set(
        Object.keys(extraerObjeto(apuestaARS,['Semana'],'total')).concat(
          Object.keys(extraerObjeto(premioARS,['Semana'],'total'))
        )
      )].sort();
      
      const pdevs = calcularPdevs(apuestaARS_casino_periodo_semana_fecha,premioARS_casino_periodo_semana_fecha);
      console.log(pdevs.sorted);
      const pdev_casino_periodo_sorted = {};
      
      for(const c of casinos){//calculo la desviación estandar y tambien los agrupo ordenados por periodo para calcular cuartiles
        pdev_casino_periodo_sorted[c] = pdev_casino_periodo_sorted[c] ?? {};
        for(const p of periodos){
          pdev_casino_periodo_sorted[c][p] = pdev_casino_periodo_sorted[c][p] ?? [];
          
          for(const s in (pdevs.pdev[c][p] ?? {})){            
            for(const f in (pdevs.pdev[c][p][s] ?? {})){
              const pdev = pdevs.pdev[c][p][s][f];
              if(pdev !== undefined && !pdev.isNaN()){
                pdev_casino_periodo_sorted[c][p].push(pdev);
              }
            }
          }
          
          pdev_casino_periodo_sorted[c][p].sort();
        }
      }
      
      const series_data = [];
      const drilldown_series = [];
      for(const cidx in casinos){//Calculo los cuartiles por periodo y por semana
        const c = casinos[cidx];
        const s = {
          name: c,
          type: 'boxplot',
          colorIndex: cidx,
          data: []
        };
        
        const cuartiles = calcularCuartiles(pdev_casino_periodo_sorted?.[c] ?? {});
        for(const p of periodos){
          {
            const qs = cuartiles[p] ?? [null,null,null,null,null];
            s.data.push({
              name: p,
              low: qs[0],
              q1: qs[1],
              median: qs[2],
              q3: qs[3],
              high: qs[4],
              drilldown: c+' '+p,
            });
          }
          {
            const daux = {
              id: c+' '+p,
              name: c+' '+p,
              type: 'boxplot',
              colorIndex: cidx,
              data: []
            };
            const cuartilesSemanas = calcularCuartiles(pdevs.sorted?.[c]?.[p] ?? {});
            for(const s in cuartilesSemanas){
              const qs = cuartilesSemanas[s];
              daux.data.push({
                name: s,
                low: qs[0],
                q1: qs[1],
                median: qs[2],
                q3: qs[3],
                high: qs[4],
                drilldown: c+' '+p+' '+s,
                drilldown_async_get_series: function(){
                  return obtenerProducidosSemanales(cidx,c,p,s);
                }
              });
            }
            drilldown_series.push(daux);
          }
        }
        
        series_data.push(s);
      }
      
      //Calculo los ticks para que esten en avg+n*stddev
      const tickPositions = (pdevs.avg.isNaN() || pdevs.stddev.isNaN())? undefined : [];
      if(tickPositions !== undefined){
        const low = [];
        const high = [];
        
        let s = pdevs.avg.minus(pdevs.stddev);
        do {
          low.push(parseFloat(s.toFixed(2)));
          s = s.minus(pdevs.stddev)
        }
        while(s >= pdevs.min);
        low.push(parseFloat(s.toFixed(2)));
        
        s = pdevs.avg.plus(pdevs.stddev);
        do {
          high.push(parseFloat(s.toFixed(2)));
          s = s.plus(pdevs.stddev)
        }
        while(s <= pdevs.max);
        high.push(parseFloat(s.toFixed(2)));
        
        tickPositions.push(...low.reverse());
        tickPositions.push(parseFloat(pdevs.avg.toFixed(2)));
        tickPositions.push(...high);
      }
      
      const addedSeries = {};
      const titulos_xAxis_nivel = ['Período','Semana','Día','Maquina','Maquina'];
      const actualizarTituloXAxis = function(chart){
        setTimeout(function () {
          const nivel = chart?.series?.[0]?.options?._levelNumber;
          chart.xAxis?.[0]?.setTitle({ text: (titulos_xAxis_nivel?.[nivel] ?? '') });
        }, 200);//@HACK
      };
      Highcharts.chart($('#divPorcentajesDevolucion')[0], {
        chart: {
          events: {
            drilldown: function(e){              
              const chart = this;
              actualizarTituloXAxis(chart);
              
              if(!e || !e.point || !e.point.drilldown || !e.point.drilldown_async_get_series) return;
              
              for(let pidx=1;pidx<(e?.points?.length ?? 0);pidx++){
                const p = e.points[pidx];
                if(p.drilldown && p.drilldown_async_get_series){
                  //Agrego para cargar si no esta
                  addedSeries[p.drilldown] = addedSeries[p.drilldown] ?? p.drilldown_async_get_series;
                }
              }
              
              chart.showLoading('Obteniendo datos...');
              chart.addSeriesAsDrilldown(e.point,e.point.drilldown_async_get_series());
              
              for(const series_id in addedSeries){
                if(addedSeries[series_id] !== true){
                  chart.addSeries(addedSeries[series_id]());
                  addedSeries[series_id] = true;//Lo pongo cargado
                }
              }
              
              chart.hideLoading();
            },
            drillupall: function(e){
              const chart = this;
              actualizarTituloXAxis(chart);
              
              if(Object.keys(addedSeries).length){
                chart.showLoading('Limpiando datos...');
              }
              for(const series_id in addedSeries){
                if(addedSeries[series_id]){//Si esta cargada la serie, la borro
                  setTimeout(function(){
                    chart.get(series_id)?.remove();
                    delete addedSeries[series_id];
                    if(Object.keys(addedSeries).length == 0){
                      chart.hideLoading();
                    }
                  },1000);//@HACK
                }
              }
            }
          }
        },
        title: {
          text: 'Evolución de Porcentajes de Devolución de Maquinas'
        },
        legend: {
          enabled: true
        },
        xAxis: {
          type: 'category',
          title: {
            text: titulos_xAxis_nivel[0]
          }
        },
        tooltip: { 
          useHTML: true,
          formatter: function(){
            const p = this.point;
            let html = '';
            if(p.high !== undefined){
              html += `<b>${p.series.name} - ${p.options.name}</b>`;
              html += '<br>Máximo: '+formatPje(p.high);
              html += '<br>Q3: '+formatPje(p.q3);
              html += '<br>Mediana: '+formatPje(p.median);
              html += '<br>Q1: '+formatPje(p.q1);
              html += '<br>Mínimo: '+formatPje(p.low);
            }
            else{
              html += `<b>${this.series.name} - ${p.options.name}</b>`;
              html += '<br>Porcentaje Devolución: '+formatPje(p.y);
            }
            return html;
          }
        },
        yAxis: {
          title: {
            text: 'Porcentaje Devolución'
          },
          tickPositions: tickPositions,
          startOnTick: true,
          labels: {
            formatter: function(){
              return formatPje(this.value);
            }
          }
        },
        series: series_data,
        drilldown: {
          activeDataLabelStyle: {
            textDecoration: 'none',
          },
          activeAxisLabelStyle: {
            textDecoration: 'none',
          },
          series: drilldown_series
        }
      });
    });
  });
  
  T.trigger('redraw');
  $T('[name="periodo[1]"],[name="periodo[0]"]').change(function(){
    T.trigger('redraw');
  });
})});

