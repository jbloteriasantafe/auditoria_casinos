<div class="row">
    <div class="col-lg-12" id="tomaProgresivo" style="overflow: scroll;max-height: 250px;">
    <h6>PROGRESIVOS</h6>
    <h5 id="sinProgresivos" hidden>La maquina no posee progresivos asignados</h5>
    <table class="table table-fixed" id="tablaProgresivos">
        <thead>
        <tr>
            <th width="17%">PROGRESIVO</th>
            @for($i=6;$i>0;$i--)
            <th width="11%">NIVEL{{$i}}</th>
            @endfor
            <th width="17%">CAUSA NO TOMA</th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
    </div>
</div>

<table hidden>
  <tr id="filaEjemploProgresivo">
    <td class="nombreProgresivo" width="17%">PROGRESIVO99</td>
    @for ($i=6;$i>0;$i--)
    <td width="11%">
      <input class="nivel{{$i}} form-control" min="0" data-toggle="tooltip" data-placement="down" title="nivel{{$i}}"></input>
    </td>
    @endfor
    <td width="17%">
      <select class="causaNoToma form-control">
        <option value="-1"></option>
        @foreach($causasNoTomaProgresivo as $causa)
        <option value="{{$causa->id_tipo_causa_no_toma_progresivo}}">{{$causa->descripcion}}</option>
        @endforeach
      </select>
    </td>
  </tr>
</table>

<script type="text/javascript">
function agregarProgresivos(progresivos){
  $('#tomaProgresivo tbody').empty();
  if(progresivos === null || progresivos.length == 0){
    $('#sinProgresivos').show();
    $('#tablaProgresivos').hide();
    return;
  }
  $('#sinProgresivos').hide();
  $('#tablaProgresivos').show();
  progresivos.forEach( prog => {
    let fila = $('#filaEjemploProgresivo').clone().removeAttr('id');
    let nombre = prog.nombre;
    if(!prog.pozo.es_unico){ nombre += '(' + prog.pozo.descripcion + ')';}
    if(prog.es_individual) nombre = 'INDIVIDUAL';
    fila.find('.nombreProgresivo').text(nombre).attr('title',nombre).attr('data-id-pozo',prog.pozo.id_pozo);
    prog.pozo.niveles.forEach( niv => {
      let nivel = fila.find('.nivel'+ niv.nro_nivel);
      nivel.attr('placeholder',niv.nombre_nivel).addClass('habilitado');
      nivel.attr('data-id-nivel',niv.id_nivel_progresivo)
    });
    $('#tomaProgresivo tbody').append(fila);
    $('#tomaProgresivo tbody input').not('.habilitado').attr('disabled',true);
  });
}

function obtenerDatosProgresivos(){
  progresivos = [];
  $('#tomaProgresivo tbody tr').each(function(){
    let fila = $(this);
    let obj = {
      id_pozo : fila.find('.nombreProgresivo').attr('data-id-pozo'),
      niveles : [],
      id_tipo_causa_no_toma_progresivo: fila.find('.causaNoToma').val()
    };
    $(this).find('input.habilitado').each(function(){
      obj.niveles.push({
        id_nivel_progresivo: $(this).attr('data-id-nivel'),
        val : $(this).val()
      });
    });

    progresivos.push(obj);
  });
  return progresivos;
}
</script>
