@component('CierresAperturas/include_guard',['nombre' => 'aperturasSorteadas'])
<style>
  .aperturasSorteadas .panel-body {
    display: flex;
  }
  .aperturasSorteadas .panel-body > *{
    flex: 1;
  }
  .aperturasSorteadas .panel-body > .listaBotones{
    flex: 0.4;
  }
  .aperturasSorteadas .listaBotones {
    display: flex;
    flex-direction: column;
  }
  .aperturasSorteadas .listaBotones > * {
    flex: 1;
    width: 100%;
    margin: 0.2em 0em;
    font-family: Roboto-Condensed;
    font-weight: bold;
  }
  .aperturasSorteadas .listaAperturasSorteadas {
    width: 100%;
    height: 100%;
    margin: 0em 0.5em;
  }
  .aperturasSorteadas .listaAperturasSorteadas .cargada{
    background-color: rgba(36, 134, 1, 0.3);
  }
</style>
@endcomponent
<div data-id_casino="" data-js-aperturas-sorteadas class="col-md-12 aperturasSorteadas">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4>APERTURAS SORTEADAS</h4>
      <select name="id_casino" class="form-control" data-js-cambio-casino style="width: 100%;">
        @foreach ($casinos as $cas)
        <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
        @endforeach
      </select>
      <div class="form-group">
        <?php $hoy = date("Y-m-d"); ?>
        @component('CierresAperturas/inputFecha',[
          'attrs' => 'name="fecha_backup" data-js-cambio-fecha-backup',
          'attrs_dtp' => "data-start-view=\"month\" data-enddate=\"$hoy\""]
        )
        @endcomponent
      </div>
    </div>
    <div class="panel-body">
      <div class="listaBotones">
        <button data-js-usar-backup type="button" class="btn btn-primary" disabled>USAR BACKUP</button>
        <button data-js-sortear type="button" class="btn btn-primary" disabled>SORTEAR</button>
        <button data-js-descargar type="button" class="btn btn-primary" disabled>DESCARGAR</button>
      </div>
      <div>
        <select data-js-lista-aperturas-sorteadas class="form-control listaAperturasSorteadas" multiple>
        </select>
      </div>
    </div>
  </div>
</div>
