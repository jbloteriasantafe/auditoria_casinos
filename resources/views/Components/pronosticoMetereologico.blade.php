<?php 
  $locacion = $locacion ?? '';
  $id = $id ?? 'id'.uniqid(); 
  $queryUrl = $queryUrl ?? "/configCuenta/pronosticoMetereologico/";
  $iconPreUrl = $iconPreUrl ?? "https://openweathermap.org/img/wn/";
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

  .weather-widget .weather-body {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 15px 0;
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
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    padding-top: 15px;
    margin-top: 10px;
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
</style>
@endcomponent

<div id="{{$id}}" class="weather-widget">
  <div class="weather-header">
    <div class="location-box">
      <span class="weather-city">Detectando locación</span>
    </div>
    <div class="weather-search-box">
      <input type="text" class="weather-search-input" placeholder="Buscar ciudad" value="{!! $locacion !!}">
      <button class="weather-search-btn">🔍</button>
    </div>
  </div>
  <div class="weather-body">
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
      <span class="label">Humedad</span>
      <span class="weather-humidity value">--%</span>
    </div>
    <div class="info-item">
      <span class="label">Viento</span>
      <span class="weather-wind value">-- m/s</span>
    </div>
  </div>
</div>

@component('Components.include_guard',['nombre' => 'weather_widget_script'])
<script>
  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('.weather-widget').forEach((widget) => {
      const cityEl = widget.getElementsByClassName("weather-city")?.[0];
      const tempEl = widget.getElementsByClassName("weather-temp")?.[0];
      const descEl = widget.getElementsByClassName("weather-desc")?.[0];
      const iconEl = widget.getElementsByClassName("weather-icon")?.[0];
      const humidityEl = widget.getElementsByClassName("weather-humidity")?.[0];
      const windEl = widget.getElementsByClassName("weather-wind")?.[0];
      const searchInput = widget.getElementsByClassName("weather-search-input")?.[0];
      const searchBtn = widget.getElementsByClassName("weather-search-btn")?.[0];
      
      searchBtn?.addEventListener("click", async () => {
        const url = "{!! $queryUrl !!}"+searchInput.value.trim();
        try {
          const response = await fetch(url);
          if (!response.ok){
            if(cityEl){
              const data = await response.json();
              if(cityEl){
                cityEl.textContent = data?.error?.join(', ') ?? 'ERROR';
              }
            }
          }
          else{
            const data = await response.json();
            if(cityEl){
              cityEl.textContent = `${data?.name}, ${data?.sys?.country}`;
            }
            if(tempEl){
              tempEl.textContent = `${Math.round(data?.main?.temp)}°C`;
            }
            if(descEl){
              descEl.textContent = data?.weather?.[0]?.description;
            }
            if(humidityEl){
              humidityEl.textContent = `${data?.main?.humidity}%`;
            }
            if(windEl){
              windEl.textContent = `${data?.wind?.speed} m/s`;
            }
            
            const iconCode = data?.weather[0]?.icon;
            if(iconEl){
              iconEl.src = "{!! $iconPreUrl !!}"+iconCode+"{!! $iconPostUrl !!}";
            }
          }
        }
        catch (error) {
          cityEl.textContent = "Error al cargar el tiempo";
          console.error(error);
        }
      });
      
      searchInput?.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
          searchBtn?.dispatchEvent(new Event('click'));
        }
      });
      
      searchBtn?.dispatchEvent(new Event('click'));
    });
  });
</script>
@endcomponent
