
$('#ticket').click(function(e){
    e.preventDefault();
    $('#modalTicket .ticket-asunto').val('');
    $('#modalTicket .ticket-mensaje').val('');
    $('#modalTicket .ticket-adjunto').val('');
    $('#modalTicket').modal('show');
    });

    $('#modalTicket .ticket-enviar').click(function(e){
    e.preventDefault();

    function leerArchivos(idx,result,done){
        const files = $('#modalTicket .ticket-adjunto')[0].files;
        if(idx >= files.length) return done(result);
    
        const file_reader = new FileReader();
        const file = files[idx];
        file_reader.onload = function(){
        const aux = {};
        aux[file.name] = file_reader.result;
        leerArchivos(idx+1,result.concat([aux]),done);
        };
        file_reader.readAsDataURL(file);
    };

    leerArchivos(0,[],function(archivos){
        const asunto  = $('#modalTicket .ticket-asunto').val();
        const mensaje = $('#modalTicket .ticket-mensaje').val();
        enviarTicket(asunto,mensaje,archivos);
    });
});

function enviarTicket(asunto,mensaje,archivos = []){//Usado desde otros scripts, donde generalmente no creo que se manden archivos
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
    $.ajax({
        type: "POST",
        url: '/enviarTicket',
        data: {
            'subject': asunto,
            'message': mensaje,
            'attachments' : archivos
        },
        success: function (data) {
            $('#mensajeExito p').text('Ticket #'+data+' creado');
            $('#mensajeExito').hide();
            setTimeout(function() {
                $('#mensajeExito').show();
            }, 250);
            $('#modalTicket').modal('hide');
        },
        error: function (data) {
            $('#mensajeError .textoMensaje').empty();
            $('#mensajeError .textoMensaje').append($('<h4>'+data.responseText+'</h4>'));
            $('#mensajeError').hide();
            setTimeout(function() {
                $('#mensajeError').show();
            }, 250);
            $('#modalTicket').modal('hide');
        }
    });
}