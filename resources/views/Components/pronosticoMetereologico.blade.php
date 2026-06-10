<?php 
  $locacion = $locacion ?? '';
  $id = $id ?? 'id'.uniqid(); 
  $ahoraUrl = $queryUrl ?? "/configCuenta/pronosticoMetereologicoAhora";
  $pronosticoUrl = $queryUrl ?? "/configCuenta/pronosticoMetereologicoPronostico";
  $iconPreUrl = $iconPreUrl ?? "/configCuenta/pronosticoMetereologicoIcon/";
  $iconPostUrl = $iconPostUrl ?? "@2x.png";
?>
@component('Components.include_guard',['nombre' => 'weather_widget_style'])
<style>
  .weather-widget {
    all: revert;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: linear-gradient(135deg, #2c3e50, #3498db);
    color: #ffffff;
    border-radius: 16px;
    padding: 20px;
    width: 100%;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    box-sizing: border-box;
  }
  
  /*
  .weather-widget.day {
    background: linear-gradient(135deg, #3498db, #8e44ad); /* Bright Day Blue into Soft Purple */
    box-shadow: 0 8px 32px rgba(52, 152, 219, 0.3);
  }
  
  .weather-widget.night {
    background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); /* Deep Midnight/Dark Navy */
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
  }
  */
  
  .weather-widget * {
    all: revert;
    box-sizing: border-box;
  }

  .weather-widget .weather-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
  }

  .weather-widget .weather-city {
    font-size: 1.1rem;
    font-weight: 600;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
  }

  .weather-widget .weather-search-box {
    display: flex;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    padding: 4px 8px;
    align-items: center;
  }

  .weather-widget .weather-search-input {
    background: transparent;
    border: none;
    color: white;
    font-size: 0.85rem;
    width: 90px;
    outline: none;
  }

  .weather-widget .weather-search-input::placeholder {
    color: rgba(255, 255, 255, 0.7);
  }

  .weather-widget .weather-search-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0 4px;
  }
  
  .weather-widget .weather-expand-btn {
    color: white;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.2);
    cursor: pointer;
    padding: 0.5em;
    margin-top: 1em;
    width: 100%;
    font-weight: 600;
  }
  .weather-widget .weather-expand-btn:hover {
    background: rgba(255, 255, 255, 0.2);
  }

  .weather-widget .weather-body {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 15px 0;
  }

  .weather-widget .weather-title {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    font-size: 1.1rem;
    font-weight: 600;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
  }
  
  .weather-widget .weather-main {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
  }

  .weather-widget .weather-icon {
    width: 64px;
    height: 64px;
    filter: drop-shadow(0px 4px 8px rgba(0,0,0,0.15));
  }

  .weather-widget .weather-temp {
    font-size: 3rem;
    margin: 0;
    font-weight: 300;
    flex: 1;
    text-align: left;
  }

  .weather-widget .weather-desc {
    font-size: 1rem;
    text-transform: capitalize ! important;
    color: rgba(255, 255, 255, 0.9);
    text-align: center;
  }

  .weather-widget .weather-footer {
    display: flex;
    justify-content: space-around;
    padding-bottom: 1em;
  }
  
  .weather-widget .info-item {
    flex: 1;
    text-align: center;
  }

  .weather-widget .info-item .label {
    display: block;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 4px;
  }

  .weather-widget .info-item .value {
    font-size: 0.95rem;
    font-weight: 600;
  }
  
  .weather-widget .weather-table {
    padding-top: 1em;
    max-height: 25em;
    overflow-y: scroll;
    width: 100%;
    display: flex;
    flex-direction: column;
  }
  
  .weather-widget .weather-table-item {
    border-top: 1px dashed rgba(255, 255, 255, 0.3);
    width: 100%;
    flex: 1;
  }
  
  .weather-widget .weather-loading {
    animation: weatherspin 2s linear infinite;
    display: inline-block;
  }
  
  @keyframes weatherspin {
    from {
      transform: rotate(0deg);
    }
    to {
      transform: rotate(360deg);
    }
  }
  
  .weather-widget[data-weather-widget-expanded="0"] [data-weather-widget-view-expand]:not([data-weather-widget-view-expand="0"]) {
    display: none;
  }
  .weather-widget[data-weather-widget-expanded="1"] [data-weather-widget-view-expand]:not([data-weather-widget-view-expand="1"]) {
    display: none;
  }
</style>
@endcomponent

<div id="{{$id}}" class="weather-widget" data-weather-widget-expanded="0">
  <div class="weather-header">
    <div class="location-box">
      <span class="weather-city">Detectando locación</span>
    </div>
    <div class="weather-search-box">
      <input type="text" class="weather-search-input" placeholder="Buscar ciudad" value="{!! $locacion !!}">
      <button class="weather-search-btn">🔍</button>
    </div>
  </div>
  <div class="weather-table-item" data-item="ahora">
    @section('item-body')
    <div class="weather-body">
      <div class="weather-title">
        ---
      </div>
      <div class="weather-main">
        <div style="flex: 1;display: flex;justify-content: right;align-items: center;">
          <img class="weather-icon" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg'></svg>" alt="Icono del clima">
        </div>
        <h2 class="weather-temp">--°C</h2>
      </div>
      <div class="weather-desc">--</div>
    </div>
    <div class="weather-footer">
      <div class="info-item">
        <span class="label">Mínima</span>
        <span class="weather-temp_min value" data-set-loading>↺</span>
      </div>
      <div class="info-item">
        <span class="label">Máxima</span>
        <span class="weather-temp_max value" data-set-loading>↺</span>
      </div>
      <div class="info-item">
        <span class="label">Sensación</span>
        <span class="weather-feels_like value" data-set-loading>↺</span>
      </div>
      <div class="info-item">
        <span class="label">Humedad</span>
        <span class="weather-humidity value" data-set-loading>↺</span>
      </div>
      <div class="info-item">
        <span class="label">Viento</span>
        <span class="weather-wind value" data-set-loading>↺ </span>
      </div>
    </div>
    @endsection
    @yield('item-body')
  </div>
  <button class="weather-expand-btn" data-js-click-weather-widget-set-expand="1" data-weather-widget-view-expand="0">Ver Más</button>
  <button class="weather-expand-btn" data-js-click-weather-widget-set-expand="0" data-weather-widget-view-expand="1">Ver Menos</button>
  <div class="weather-footer" data-weather-widget-view-expand="1">
    <div class="weather-table">
      @for($i=0;$i<40;$i++)
      <div class="weather-table-item" data-item="{{$i}}">
        @yield('item-body')
      </div>
      @endfor
    </div>
  </div>
</div>

@component('Components.include_guard',['nombre' => 'weather_widget_script'])
<script>
  document.addEventListener("DOMContentLoaded", () => {
    const fillWeatherTableItem = (wti,data) => {
      const titleEl = wti.getElementsByClassName("weather-title")?.[0];
      const tempEl = wti.getElementsByClassName("weather-temp")?.[0];
      const descEl = wti.getElementsByClassName("weather-desc")?.[0];
      const humidityEl = wti.getElementsByClassName("weather-humidity")?.[0];
      const tempMinEl = wti.getElementsByClassName("weather-temp_min")?.[0];
      const tempMaxEl = wti.getElementsByClassName("weather-temp_max")?.[0];
      const feelsLikeEl = wti.getElementsByClassName("weather-feels_like")?.[0];
      const windEl = wti.getElementsByClassName("weather-wind")?.[0];
      const iconEl = wti.getElementsByClassName("weather-icon")?.[0];
      const tempFormatter = new Intl.NumberFormat(
        "es-AR",
        {minimumFractionDigits: 1,maximumFractionDigits: 1}
      );
      const windFormatter = new Intl.NumberFormat(
        "es-AR",
        {minimumFractionDigits: 2,maximumFractionDigits: 2}
      );
      
      if(titleEl){
        titleEl.textContent = data?.dt_txt ?? '---';
      }
      
      if(tempEl){
        const temp = tempFormatter.format(data?.main?.temp);
        tempEl.textContent = `${temp}°C`;
      }
      
      if(tempMinEl){
        const temp = tempFormatter.format(data?.main?.temp_min);
        tempMinEl.textContent = `${temp}°C`;
      }
      
      if(tempMaxEl){
        const temp = tempFormatter.format(data?.main?.temp_max);
        tempMaxEl.textContent = `${temp}°C`;
      }
      if(feelsLikeEl){
        const temp = tempFormatter.format(data?.main?.feels_like);
        feelsLikeEl.textContent = `${temp}°C`;
      }
      
      if(descEl){
        descEl.textContent = data?.weather?.[0]?.description;
      }
      
      if(humidityEl){
        humidityEl.textContent = `${data?.main?.humidity}%`;
      }
      
      if(windEl){
        const wind = windFormatter.format(data?.wind?.speed);
        windEl.textContent = `${wind} m/s`;
      }
      
      if(iconEl){
        iconEl.src = "{!! $iconPreUrl !!}"+data?.weather?.[0]?.icon+"{!! $iconPostUrl !!}";
      }
      
      wti.setAttribute('data-pod',data?.sys?.pod ?? data?.weather?.[0]?.icon?.slice(-1) ?? '');
    };
    
    const dataGetTime = (dt,timezone) => {
      const date = (new Date(dt * 1000 + timezone * 1000)).toISOString();
      const dia = date.slice(0,'YYYY-MM-DD'.length);
      const hora = date.slice('YYYY-MM-DDT'.length,-('.000Z'.length));
      return dia+' '+hora;
    };
    
    document.querySelectorAll('.weather-widget').forEach((widget) => {
      const searchInput = widget.getElementsByClassName("weather-search-input")?.[0];
      const searchBtn = widget.getElementsByClassName("weather-search-btn")?.[0];
      const cityEl = widget.getElementsByClassName("weather-city")?.[0];
      const ahoraEl = widget.querySelector(".weather-table-item[data-item='ahora']");
      const pronosticoEls = Array.from(
        document.querySelectorAll(".weather-table-item[data-item]:not([data-item='ahora'])")
      ).sort((a,b) => Number(a.getAttribute('data-item'))-Number(b.getAttribute('data-item')))
      
      searchBtn?.addEventListener("click", async () => {
        const params = {
          locacion: searchInput?.value?.trim()
        };
        const dfltData = () => {
          return {
            name: 'ERROR',
            dt_txt: 'ERROR',
            sys: {country: '---',pod: ''},
            main: {temp: 999, humidity: 999,temp_min: 999,temp_max: 999,feels_like: 999},
            wind: {speed: 999},
            weather: [{description: '---', icon: ''}]
          };
        };
        
        let data = dfltData();
        
        ahoraEl?.querySelectorAll('[data-set-loading]')?.forEach(function(el){
          el.textContent = '↻';
          el.classList.add('weather-loading');
        });
        
        try {
          const response = await fetch("{!! $ahoraUrl !!}?"+(new URLSearchParams(params).toString()));
          if (response.ok){
            data = await response.json();
          }
          else{
            const err = await response.json();
            data.name = err?.errors?.join(', ') ?? 'ERROR';
          }
        }
        catch (error) {
          console.error(error);
        }
        
        if(cityEl){
          cityEl.textContent = `${data?.name}, ${data?.sys?.country}`;
        }
        
        ahoraEl?.querySelectorAll('[data-set-loading]')?.forEach(function(el){
          el.classList.remove('weather-loading');
        });
        
        const timezone = data.timezone;
        
        data.dt_txt = 'AHORA ('+dataGetTime(data.dt,timezone)+')';
        fillWeatherTableItem(
          ahoraEl,
          data
        );
        
        data = Array.from({ length: 40 }, (_, idx) => {
          pronosticoEls?.[idx]?.querySelectorAll('[data-set-loading]')?.forEach(function(el){
            el.textContent = '↻';
            el.classList.add('weather-loading');
          });
          return dfltData();
        });
        
        try {
          const response = await fetch("{!! $pronosticoUrl !!}?"+(new URLSearchParams(params).toString()));
          if (response.ok){
            const aux = await response.json();
            data = aux?.list ?? data;
          }
          else{
            const err = await response.json();
            for(const didx in data){
              data[didx].dt_txt = err?.errors?.join(', ') ?? 'ERROR';
            }
            console.log(err);
          }
        }
        catch (error) {
          console.error(error);
        }
        
        widget?.querySelectorAll('.weather-loading')?.forEach(function(el){
          el.classList.remove('weather-loading');
        });
        
        for(const idx in data){
          data[idx].dt_txt = dataGetTime(data[idx].dt,timezone);
          fillWeatherTableItem(
            pronosticoEls?.[idx],
            data[idx]
          );
        }
      });
      
      searchInput?.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
          searchBtn?.dispatchEvent(new Event('click'));
        }
      });
      
      widget.querySelectorAll('[data-js-click-weather-widget-set-expand]').forEach((button) => {
        button.addEventListener("click",(e) => {
          widget.setAttribute(
            'data-weather-widget-expanded',
            button.getAttribute('data-js-click-weather-widget-set-expand')
          );
        });
      });
      
      searchBtn?.dispatchEvent(new Event('click'));
    });
  });
</script>
@endcomponent
