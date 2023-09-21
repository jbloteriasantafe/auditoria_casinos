@component('Components/include_guard',['nombre' => 'filtro_tabla'])
<link rel="stylesheet" href="/css/paginacion.css">
<style>
  .filtro_tabla tr {
    display: flex;
  }
  .filtro_tabla th,.filtro_tabla td {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    word-break: break-word;
  }
</style>
@endcomponent
<?php
$id = uniqid();
?>
<div id="{{$id}}" data-js-filtro-tabla class="filtro_tabla">
  <div class="row" data-js-filtro-tabla-filtro>
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading" data-toggle="collapse" href="#{{$id}} .collapse" style="cursor: pointer">
          <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
        </div>
        <div class="panel-collapse collapse in" aria-expanded="true" style="">
          <div class="panel-body">
            <form class="row" data-js-filtro-form>
              {{ $filtros ?? '' }}
              <div class="col-md-4">
                <h5>&nbsp;</h5>
                <button data-target="{{$target_buscar}}" data-js-buscar class="btn btn-infoBuscar" type="button">
                  <i class="fa fa-fw fa-search"></i> BUSCAR
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="row" data-js-filtro-tabla-tabla>
    <div class="col-md-12 filtro_tabla_tabla">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4>{!! $titulo ?? 'TITULO TABLA' !!}</h4>
        </div>
        <div class="panel-body">
          <div class="table-responsibe">
            <table data-js-filtro-tabla-resultados class="table tablesorter">
              <thead>
                {!! $cabecera ?? '<tr><th>COLUMNA</th></tr>' !!}
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
          <table data-js-filtro-tabla-molde hidden>
            {!! $molde ?? '<tr><td>COLUMNA</td></tr>' !!}
          </table>
        </div>
        <div class="row zonaPaginacion herramientasPaginacion"></div>
      </div>
    </div>
  </div>
</div>
