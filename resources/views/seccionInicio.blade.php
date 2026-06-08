@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoInicio">@svg('home','iconoHome')</span>
@endsection
@section('contenidoVista')

@section('estilos')
<link rel='stylesheet' href='/css/fullcalendar.min.css'/>
@endsection
<?php
$usuario = \App\Http\Controllers\UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
?>

@section('contenidoVista')

@if(!$usuario->es_control)
<div class="row">
  <div class="col-md-7">
    <div class="row">
      <div class="col-md-6">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4>BIENVENIDO {{$usuario->nombre}}</h4>
          </div>
          <div class="panel-body">
            <div class="row">
              <div class="col-lg-12">
                <h5 style="display:inline-block">ROL Y PERMISO </h5>
                <span style="margin-top:8px; margin-left: 15px;">
                  - {{implode(' - ',$usuario->roles->pluck('descripcion')->toArray())}} -
                </span>
                <br>
                <h5 style="display:inline-block">CASINO(S) ASOCIADO(S) </h5>
                <span style="margin-top:6px; margin-left: 15px;">
                  - {{implode(' - ',$usuario->casinos->pluck('nombre')->toArray())}} -
                </span>
                <br><br><br>
              </div>
            </div>
          </div> <!-- panel-body -->
        </div> <!-- panel -->
      </div>
      <div class="col-md-6">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4><?php
              $week_days = array ("Domingo", "Lunes", "Martes", "Miercoles", "Jueves", "Viernes", "Sabado");
              $months = array ("", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
              $year_now = date ("Y");
              $month_now = date ("n");
              $day_now = date ("j");
              $week_day_now = date ("w");
              $date = $week_days[$week_day_now] . ", " . $day_now . " de " . $months[$month_now] . " del " . $year_now;
              echo $date;
            ?></h4>
          </div>
          <div class="panel-body">
            <div class="row">
              <div class="col-lg-12">
                <?php $idWidgetClima = 'id'.uniqid(); ?>
                <style>
                  #{!! $idWidgetClima !!}, #{!! $idWidgetClima !!} * {
                    all: revert;
                    box-sizing: border-box;
                  }
                  #{!! $idWidgetClima !!} { 
                    width: 100%;
                  }
                  #{!! $idWidgetClima !!}.weather-widget {
                    font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: linear-gradient(135deg, #2c3e50, #3498db);
                    color: #ffffff;
                    border-radius: 16px;
                    padding: 20px;
                    width: 100%;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                    box-sizing: border-box;
                  }

                  #{!! $idWidgetClima !!} .weather-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 10px;
                    margin-bottom: 15px;
                  }

                  #{!! $idWidgetClima !!} .weather-city {
                    font-size: 1.1rem;
                    font-weight: 600;
                    text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
                  }

                  #{!! $idWidgetClima !!} .weather-search-box {
                    display: flex;
                    background: rgba(255, 255, 255, 0.2);
                    border-radius: 20px;
                    padding: 4px 8px;
                    align-items: center;
                  }

                  #{!! $idWidgetClima !!} .weather-search-input {
                    background: transparent;
                    border: none;
                    color: white;
                    font-size: 0.85rem;
                    width: 90px;
                    outline: none;
                  }

                  #{!! $idWidgetClima !!} .weather-search-input::placeholder {
                    color: rgba(255, 255, 255, 0.7);
                  }

                  #{!! $idWidgetClima !!} .weather-search-btn {
                    background: none;
                    border: none;
                    cursor: pointer;
                    padding: 0 4px;
                  }

                  #{!! $idWidgetClima !!} .weather-body {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    margin: 15px 0;
                  }

                  #{!! $idWidgetClima !!} .weather-main {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                    width: 100%;
                  }

                  #{!! $idWidgetClima !!} .weather-icon {
                    width: 64px;
                    height: 64px;
                    filter: drop-shadow(0px 4px 8px rgba(0,0,0,0.15));
                  }

                  #{!! $idWidgetClima !!} .weather-temp {
                    font-size: 3rem;
                    margin: 0;
                    font-weight: 300;
                    flex: 1;
                    text-align: left;
                  }

                  #{!! $idWidgetClima !!} .weather-desc {
                    font-size: 1rem;
                    text-transform: capitalize ! important;
                    color: rgba(255, 255, 255, 0.9);
                    text-align: center;
                  }

                  #{!! $idWidgetClima !!} .weather-footer {
                    display: flex;
                    justify-content: space-around;
                    border-top: 1px solid rgba(255, 255, 255, 0.2);
                    padding-top: 15px;
                    margin-top: 10px;
                  }

                  #{!! $idWidgetClima !!} .info-item {
                    flex: 1;
                    text-align: center;
                  }

                  #{!! $idWidgetClima !!} .info-item .label {
                    display: block;
                    font-size: 0.75rem;
                    color: rgba(255, 255, 255, 0.7);
                    margin-bottom: 4px;
                  }

                  #{!! $idWidgetClima !!} .info-item .value {
                    font-size: 0.95rem;
                    font-weight: 600;
                  }
                </style>
                <div id="{{$idWidgetClima}}" class="weather-widget">
                  <div class="weather-header">
                    <div class="location-box">
                      <span class="weather-city">Detectando locación</span>
                    </div>
                    <div class="weather-search-box">
                      <input type="text" class="weather-search-input" placeholder="Buscar ciudad">
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
                <script>
                  document.addEventListener("DOMContentLoaded", () => {
                    const widget = document.getElementById("{!! $idWidgetClima !!}");
                    const cityEl = widget.getElementsByClassName("weather-city")?.[0];
                    const tempEl = widget.getElementsByClassName("weather-temp")?.[0];
                    const descEl = widget.getElementsByClassName("weather-desc")?.[0];
                    const iconEl = widget.getElementsByClassName("weather-icon")?.[0];
                    const humidityEl = widget.getElementsByClassName("weather-humidity")?.[0];
                    const windEl = widget.getElementsByClassName("weather-wind")?.[0];
                    const searchInput = widget.getElementsByClassName("weather-search-input")?.[0];
                    const searchBtn = widget.getElementsByClassName("weather-search-btn")?.[0];
                    
                    searchBtn?.addEventListener("click", async () => {
                      const url = "/configCuenta/pronosticoMetereologico/"+searchInput.value.trim();
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
                          
                          // Set Weather Icon directly from OpenWeather CDN
                          const iconCode = data?.weather[0]?.icon;
                          if(iconEl){
                            iconEl.src = `https://openweathermap.org/img/wn/${iconCode}@2x.png`;
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
                </script>
              </div>
            </div>
          </div> <!-- panel-body -->
        </div> <!-- panel -->
      </div>
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4>ÚLTIMAS SECCIONES VISITADAS</h4>
          </div>
          <style media="screen">
              .seccionVisitada {
                  text-align: center;
                  height: 200px;
              }
              .seccionVisitada a {
                  text-decoration: none;
              }
              .seccionVisitada:hover i {
                  opacity: 1;
              }
              .seccionVisitada:hover .icon {
                  transform: scale(1.3);
                  top: 0px;
              }
              .seccionVisitada i {
                  color: #aaa;
                  display: block;
                  opacity: 0;
                  /*transition: opacity 100ms;*/
              }

              .seccionVisitada .icon {
                  stroke: #aaa;
                  position: relative;
                  top: -15px;
              }

              .seccionVisitada h6 {
                font-family: Roboto-Condensed;
                font-size: 18px;
              }
              /* ICONOS */
              .seccionVisitada .iconoMaquinas {
                width: 80px; height: 80px;
              }
              .seccionVisitada .iconoUsuarios {
                width: 52px; height: 52px;
                margin: 14px 0px 14px 0px;
              }
              .seccionVisitada .iconoExpedientes {
                width: 62px; height: 62px;
                margin: 9px 0px 9px 0px;
              }
          </style>
          <?php
          $iconos_por_ruta = [
            'casinos' => [null,'casinos','iconoCasinosModif'],
            'usuarios' => [null,'usuario','iconoUsuarios'],
            'roles_permisos' => [null,'usuario','iconoUsuarios'],
            'configCuenta' => [null,'usuario','iconoUsuarios'],
            'logActividades' => [null,'usuario','iconoUsuarios'],
            'expedientes' => [null,'expedientes','iconoExpedientes'],
            'resoluciones' => [null,'expedientes','iconoExpedientes'],
            'disposiciones' => [null,'expedientes','iconoExpedientes'],
            'informeEstadoParque' => [null,'informes','iconoMaquinas'],
            'estadisticas_relevamientos'  => [null,'informes','iconoMaquinas'],
            'informeContableMTM' => [null,'informes','iconoMaquinas'],
            'informeSector' => [null,'informes','iconoMaquinas'],
            'informeDiarioBasico' => [null,'informes','iconoMaquinas'],
            'relevamientosProgresivo' => [null,'maquinas','iconoMaquinas'],
            'maquinas' => [null,'maquinas','iconoMaquinas'],
            'progresivos' => [null,'maquinas','iconoMaquinas'],
            'islas' => [null,'maquinas','iconoMaquinas'],
            'formulas' => [null,'maquinas','iconoMaquinas'],
            'juegos' => [null,'maquinas','iconoMaquinas'],
            'certificadoSoft' => [null,'maquinas','iconoMaquinas'],
            'certificadoHard' => [null,'maquinas','iconoMaquinas'],
            'sectores' => [null,'maquinas','iconoMaquinas'],
            'importaciones' => [null,'maquinas','iconoMaquinas'],
            'relevamientos' => [null,'maquinas','iconoMaquinas'],
            'mtm_a_pedido' => [null,'maquinas','iconoMaquinas'],
            'producidos' => [null,'maquinas','iconoMaquinas'],
            'beneficios' => [null,'maquinas','iconoMaquinas'],
            'layout_total' => [null,'maquinas','iconoMaquinas'],
            'layout_parcial' => [null,'maquinas','iconoMaquinas'],
            'prueba_juego' => [null,'maquinas','iconoMaquinas'],
            'prueba_progresivos' => [null,'maquinas','iconoMaquinas'],
            'relevamientos_movimientos' => [null,'maquinas','iconoMaquinas'],
            'eventualidades' => [null,'maquinas','iconoMaquinas'],
            'eventualidadesMTM' => [null,'maquinas','iconoMaquinas'],
            'estadisticasGenerales' => [null,'tablero_modif','iconoTableroModif'],
            'estadisticasPorCasino' => [null,'tablero_modif','iconoTableroModif'],
            'interanuales' => [null,'tablero_control','iconoTableroModif'],
            'informesMTM' => [null,'informes','iconoInformesModif'],
            'informesMesas' => [null,'informes','iconoInformesModif'],
            'bingo' => [null,'bingos','iconoInformesModif'],
            'diferencia-bingo' => ['bingo/reportesDiferencia','bingos','iconoInformesModif'],
            'estado-bingo' => ['bingo/reportesEstado','bingos','iconoInformesModif'],
            'registrosDNI' => [null,'usuario','iconoUsuarios'],
            'importacion-bingo' => ['bingo/importarRelevamiento','bingos','iconoInformesModif'],
            'informe-bingo' => ['bingo/informe','bingos','iconoInformesModif'],
            'relevamientosControlAmbiental' => [null,'maquinas','iconoMaquinas'],
            'autoexclusion' => [null,'usuario','iconoUsuarios'],
            'galeriaImagenesAutoexcluidos' => ['galeriaImagenesAutoexcluidos','usuario','iconoUsuarios'],
            'informesAutoexcluidos' => [null,'informes','iconoInformesModif'],
          ];
          ?>
          <div class="panel-body">
            <div class="row">
              @foreach($ultimas_visitadas as $visitada)
                <div class="col-md-3 seccionVisitada">
                  <?php 
                    $val = $iconos_por_ruta[$visitada->ruta] ?? [$visitada->ruta,null,null];
                  ?>
                  <a href="{{$val[0] ?? $visitada->ruta}}" style="color: rgb(68,68,68);">
                    <i class="fa fa-share fa-2x"></i>
                    @if($val[1] || $val[2])
                    @svg($val[1],$val[2])
                    @endif
                    <h6>{{$visitada->seccion}}</h6>
                  </a>
                </div>
              @endforeach
            </div>
          </div>
        </div> <!-- panel -->
      </div>
    </div>
  </div>
  <div class="col-md-5">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h4>CALENDARIO</h4>
      </div>
      <div class="panel-body">
        <div class="row">
          <div class="col-lg-12">
            <div id="calendarioInicio"></div>
          </div>
        </div>
      </div> <!-- panel-body -->
    </div> <!-- panel -->
  </div>
</div>
@endif

@if($usuario->es_superusuario)
@include('seccionInicioTablero')
@endif

<meta name="_token" content="{!! csrf_token() !!}" />
@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| AYUDA INICIO</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjetas de Inicio</h5>
  <p>En esta sección se podrá ver una ayuda rápida al calendario con actividades previstas y cargadas en el sistema, incluyendo feriados y días no hábiles.
  Además de la situación actual del clima en Santa Fe y los últimos accesos a los cuales cada usuario visitó por última vez.</p>
</div>

@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
<script src='/js/moment.min.js'></script>
<script src='/js/fullcalendar.min.js'></script>
<script src='/js/locale-all.js'></script>
<script src="/js/gcal.min.js" charset="utf-8"></script>
<script src="/js/seccionInicio.js"></script>
@endsection


