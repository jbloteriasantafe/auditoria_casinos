$(document).ready(function(){
    $('#sel_casinos option')[0].selected = true;
    $('#sel_casinos').change();
});

$('#sel_casinos').change(function(){
    const id_casino = $(this).val();
    const sectores = $('#dataSectores option[data-id-casino="'+id_casino+'"]').clone();
    sectores[0].selected=true;
    $('#sel_sectores').empty().append(sectores).change();
});
$('#sel_sectores').change(function(){
    const id_sector = $(this).val();
    const islas = $('#dataIslas option[data-id-sector="'+id_sector+'"]').clone(); 
    islas[0].selected=true;
    $('#sel_islas').empty().append(islas).change();
});
$('#sel_islas').change(function(){
    const id_isla = $(this).val();
    const maquinas = $('#dataMaquinas option[data-id-isla="'+id_isla+'"]').clone();
    maquinas[0].selected=true;
    $('#sel_maquinas').empty().append(maquinas).change();
})