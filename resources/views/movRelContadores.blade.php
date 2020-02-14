<div class="row">
    <div id="" class="table-editable">
        <table id="tablaCargarContadores" class="table">
            <thead>
                <tr>
                    <th class="col-xs-6"><h6><b>CONTADORES</b></h6></th>
                    <th class="col-xs-6"><h6><b>TOMA</b></h6></th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div> 
</div>

<script type="text/javascript">
function agregarContadores(maquina,toma){
    $('#tablaCargarContadores tbody').empty();
    for (let i = 1; i < 7; i++){
        let fila = $('<tr>');
        let nombre_cont = maquina["cont" + i];
        if(nombre_cont === null) continue;

        let val_cont = null;
        if(toma != null){
            val_cont = toma["vcont" + i];
        }

        fila.append($('<td>').addClass('col-xs-6').text(nombre_cont));
        fila.attr('data-contador',nombre_cont);
        fila.append($('<td>').addClass('col-xs-6')
        .append($('<input>').addClass('valorModif form-control'))
        );
        if(val_cont != null){
            fila.find('input').val(val_cont);
        }

        $('#tablaCargarContadores tbody').append(fila);
    }
}
function obtenerDatosContadores(){
    let tabla = $('#tablaCargarContadores tbody > tr');
    let contadores=[];
    $.each(tabla, function(index, value){
        const cont={
            nombre: $(this).attr('data-contador'),
            valor: $(this).find('.valorModif').val()
        }
        contadores.push(cont);
    });
    return contadores;
}
</script>

