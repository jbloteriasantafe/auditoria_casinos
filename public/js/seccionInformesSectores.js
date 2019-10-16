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
    const islas = $('#dataIslas option[data-id-sector="'+id_sector+'"]').clone(); 
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