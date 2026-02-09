<!-- Modal para ver versiones -->
<div class="modal fade" id="modal-versiones" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-history"></i> Historial de Versiones
                </h4>
            </div>
            <div class="modal-body">
                <div id="lista-versiones"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('click', '.btn-ver-versiones', function() {
    var idNota = $(this).data('id');
    var tipo = $(this).data('tipo');
    
    $.get(`/notas-unificadas/historial-versiones/${idNota}/${tipo}`, function(response) {
        if(response.success) {
            var html = '<div class="list-group">';
            response.versiones.forEach(function(v) {
                html += `
                    <div class="list-group-item">
                        <h5 class="list-group-item-heading">
                            <i class="fa fa-file-pdf-o text-danger"></i> 
                            Versión ${v.version}
                        </h5>
                        <p class="list-group-item-text">
                            <small>${v.nombre_original}</small><br>
                            <small class="text-muted">${v.created_at}</small>
                        </p>
                        <div class="btn-group btn-group-sm" style="margin-top: 5px;">
                            <a href="/notas-unificadas/visualizar-version/${v.id}" target="_blank" class="btn btn-primary">
                                <i class="fa fa-eye"></i> Ver
                            </a>
                            <a href="/storage/${v.path}" class="btn btn-default" download>
                                <i class="fa fa-download"></i> Descargar
                            </a>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            $('#lista-versiones').html(html);
            $('#modal-versiones').modal('show');
        } else {
            alert('Error al cargar versiones');
        }
    });
});
</script>
