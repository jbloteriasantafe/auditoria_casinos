$(document).ready(function(){
    const casinos = $('#sel_casinos option');
    if(casinos.length > 0) casinos[0].selected = true;
    $('#sel_casinos').change();
});

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
})

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