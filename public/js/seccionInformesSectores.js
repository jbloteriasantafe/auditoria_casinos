$(document).ready(function(){
    const casinos = $('#sel_casinos option');
    if(casinos.length > 0) casinos[0].selected = true;
    $('#sel_casinos').change();
});

$('#btn_refrescar').click(function(){
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
      });
      $.ajax({
        type: "GET",
        url: 'informeSector/obtenerMTMs',
        dataType: 'json',
        success: function(data){
            console.log(data);
        },
        error: function(data){
            console.log(data);
        }
      });
});
/*

<datalist id="dataCasinos">
    <!-- <option value="{{$c->id_casino}}">{{$c->nombre}}</option> -->
</datalist>
<datalist id="dataSectores">
    <!--<option value="{{$s->id_sector}}" data-id-casino="{{$s->id_casino}}">{{$s->descripcion}}</option> -->
</datalist>
<datalist id="dataIslas">
    <!--<option value="{{$i->id_isla}}" data-id-sector="{{$i->id_sector}}" data-id-casino="{{$i->id_casino}}">{{$i->nro_isla}}</option> -->
</datalist>
<datalist id="dataMaquinas">
    <!--<option value="{{$m->id_maquina}}" 
    data-id-isla="{{$m->id_isla}}" 
    data-id-sector="{{$m->id_sector}}" 
    data-id-casino="{{$m->id_casino}}"
    data-id-estado-maquina="{{$m->id_estado_maquina}}"
    @if ($m->borrada == 1)
    class="borrada"
    @endif
    style='background: {{get($colores[$m->id_estado_maquina])}}'
    ><b>{{$m->nro_admin}}</b> <small>{{$m->estado_descripcion}}</small></option> -->
</datalist>

                            <!--<option value="{{$c->id_casino}}">{{$c->nombre}}</option> -->
*/

$('#sel_casinos').change(function(){
    const id_casino = $(this).val();
    const sectores = $('#dataSectores option[data-id-casino="'+id_casino+'"]').clone();
    let sel_sectores = $('#sel_sectores');
    sel_sectores.empty();
    if(sectores.length > 0) sectores[0].selected=true;
    sel_sectores.append(sectores);
    sel_sectores.change();
});
$('#sel_sectores').change(function(){
    const id_sector = $(this).val();
    const islas = ordenar($('#dataIslas option[data-id-sector="'+id_sector+'"]').clone(),function(a,b){
        const x = parseInt(a.textContent);
        const y = parseInt(b.textContent);
        if(isNaN(x)) return x;
        if(isNaN(y)) return y;
        return x<y;
    }); 
    let sel_islas = $('#sel_islas');
    sel_islas.empty();
    if(islas.length > 0) islas[0].selected=true;
    sel_islas.append(islas);
    sel_islas.change();
});
$('#sel_islas').change(function(){
    const id_isla = $(this).val();
    const maquinas = $('#dataMaquinas option[data-id-isla="'+id_isla+'"]').clone();
    let sel_maquinas = $('#sel_maquinas');
    sel_maquinas.empty();
    if(maquinas.length > 0) maquinas[0].selected=true;
    sel_maquinas.append(maquinas);
    sel_maquinas.change();
    $('#sel_maquinas option').dblclick(function(){
        const ya_esta = $('#sel_encoladas option[value="'+$(this).val()+'"]').length > 0;
        if(ya_esta) return;
        if($(this).hasClass('borrada')) return;
        const fila = $(this).clone();
        fila.dblclick(function(){
            $(this).remove();
        });
        $('#sel_encoladas').append(fila);
    });
})
$('#btn_ordenar').click(function(){
    const maqs_ord = ordenar($('#sel_encoladas option').clone(),function(a,b){
        const x = parseInt(a.textContent);
        const y = parseInt(b.textContent);
        if(isNaN(x)) return x;
        if(isNaN(y)) return y;
        return x<y;
    });
    $('#sel_encoladas option').remove();
    $('#sel_encoladas').append(maqs_ord); 
});

$('#btn_limpiar').click(function(){
    $('#sel_encoladas').empty();
});

$('#btn_estado').click(function(){
    const maquinas = [];
    $('#sel_encoladas option').each(function(idx,m){
        maquinas.push({
            id_maquina : $(m).val(),
            id_estado_maquina : $('#sel_estado').val()
        });
    });
    console.log(maquinas);
});

function ordenar(list, comp, onadd = function(add) { return add; }) {
    //Encuentra el optimo valor, con una lista negra
    function find_val(list, comp, blacklist) {
        let ret = null;
        let ret_idx = null;
        for (let i = 0; i < list.length; i++) {
            let item = list[i];
            if (!blacklist[i] && (ret_idx === null || comp(item, ret))) {
                ret = item;
                ret_idx = i;
            }
        }
        return { elem: ret, index: ret_idx };
    }

    let newlist = [];
    let used = [];

    for (let i = 0; i < list.length; i++) {
        used.push(false);
    }

    for (let i = 0; i < list.length; i++) {
        let to_add = find_val(list, comp, used);
        newlist.push(onadd(to_add.elem));
        used[to_add.index] = true;
    }

    return newlist;
}