<div data-tablero-inicio>
  @section('iniciopanel')
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-body">
          <div class="row">
            <br>
  @endsection
  @section('finpanel')
            <br>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endsection

  @yield('iniciopanel')
  <div class="col-md-4">
    <h5>PERÍODO</h5>
    <div style="display: flex;">
      @component('Components/inputFecha',[
        'attrs' => 'name="periodo[0]"',
        'attrs_dtp' => 'data-date-format="yyyy-mm" data-start-view="year" data-min-view="decade"',
        'form_group_attrs' => 'style="padding: 0 !important;flex: 1;"',
        'placeholder' => 'DESDE'
      ])
      @endcomponent
      @component('Components/inputFecha',[
        'attrs' => 'name="periodo[1]"',
        'attrs_dtp' => 'data-date-format="yyyy-mm" data-start-view="year" data-min-view="decade"',
        'form_group_attrs' => 'style="padding: 0 !important;flex: 1;"',
        'placeholder' => 'HASTA'
      ])
      @endcomponent
    </div>
  </div>
  @yield('finpanel')
     
  @yield('iniciopanel')
  <div id="divBeneficiosAnualesPorCasino" class="col-md-3"></div>
  <div id="divBeneficiosAnualesPorActividad" class="col-md-3"></div>
  <div id="divAutoexcluidosAnualesPorCasino" class="col-md-3"></div>
  <div id="divAutoexcluidosAnualesPorEstado" class="col-md-3"></div>
  @yield('finpanel')

  @yield('iniciopanel')
  <div class="row">
    <div id="divBeneficiosMensuales" >
    </div>
  </div>
  <hr>
  <div class="row">
    <div id="divAutoexcluidosMensuales">
    </div>
  </div>
  @yield('finpanel')

  @yield('iniciopanel')
  <div class="row">
    <div id="divDistribucionAutoexcluidosProvincias" class="col-md-6">
    </div>
    <div id="divDistribucionAutoexcluidosDepartamentos" class="col-md-6">
    </div>
  </div>
  @yield('finpanel')
  <script src="/js/seccionInicioTablero.js" type="module"></script>
</div>

