(function($){
  var BASE = '/notas-unificadas/mail';
  var catActual = null;
  var esCasino = false;

  var catTitulos = {
    'auditoria':    'Auditoría / Despacho',
    'casino':       'Casino / Plataforma',
    'funcionario1': 'Funcionario 1',
    'funcionario2': 'Funcionario 2'
  };

  var catIconos = {
    'auditoria':    '<i class="fa fa-balance-scale"></i>',
    'casino':       '<i class="fa fa-building"></i>',
    'funcionario1': '<i class="fa fa-user-tie"></i>',
    'funcionario2': '<i class="fa fa-user-tie"></i>'
  };

  // ========== TRANSICIONES ==========

  function cargarTransiciones(){
    $.get(BASE + '/transiciones', { categoria: catActual }, function(data){
      var tbody = $('#tbodyTransiciones');
      tbody.empty();
      if(!data.length){
        tbody.append('<tr><td colspan="5" class="text-center text-muted" style="padding:15px; font-size:12px;">Sin transiciones configuradas</td></tr>');
        return;
      }
      var puedeEditarTrans = !!window.ESADMIN_MAILS;
      data.forEach(function(t){
        var origen = t.id_estado_origen == 0 ? '<span style="color:#8b5cf6; font-weight:600;">AL CREAR</span>' : t.estado_origen;
        var tipoBadge = (t.id_tipo_evento && t.id_tipo_evento != 0 && t.tipo_evento_nombre)
          ? '<span style="background:#e0e7ff; color:#3730a3; padding:2px 8px; border-radius:10px; font-size:11px;">' + t.tipo_evento_nombre + '</span>'
          : '<span style="color:#94a3b8; font-size:11px;">Todos</span>';
        var accionesHtml = puedeEditarTrans
          ? ('<button class="btn btn-xs btn-ask-del-trans" data-id="' + t.id + '" style="border-radius:50%; width:24px; height:24px; padding:0; background:#e2e8f0; color:#64748b; border:1px solid #cbd5e1; transition:all 0.15s;" onmouseover="this.style.background=\'#d9534f\';this.style.color=\'#fff\';this.style.borderColor=\'#d43f3a\';" onmouseout="this.style.background=\'#e2e8f0\';this.style.color=\'#64748b\';this.style.borderColor=\'#cbd5e1\';"><i class="fa fa-trash" style="font-size:10px;"></i></button>' +
             '<span class="confirm-del-trans" data-id="' + t.id + '" style="display:none;">' +
               '<button class="btn btn-xs btn-success btn-confirm-del-trans" data-id="' + t.id + '" style="border-radius:50%; width:24px; height:24px; padding:0;"><i class="fa fa-check" style="font-size:10px;"></i></button> ' +
               '<button class="btn btn-xs btn-default btn-cancel-del-trans" style="border-radius:50%; width:24px; height:24px; padding:0;"><i class="fa fa-times" style="font-size:10px;"></i></button>' +
             '</span>')
          : '';
        tbody.append(
          '<tr>' +
            '<td style="padding:8px 15px;">' + origen + '</td>' +
            '<td style="padding:8px 15px; text-align:center; color:#94a3b8;"><i class="fa fa-long-arrow-right"></i></td>' +
            '<td style="padding:8px 15px;">' + t.estado_destino + '</td>' +
            '<td style="padding:8px 15px;">' + tipoBadge + '</td>' +
            '<td style="padding:8px 15px; text-align:center; white-space:nowrap;">' + accionesHtml + '</td>' +
          '</tr>'
        );
      });
    });
  }

  $(document).on('click', '#btnNuevaTransicion', function(){
    $('#panelNuevaTransicion').slideToggle(200);
  });

  $(document).on('click', '#btnGuardarTransicion', function(){
    var origen = $('#selTransOrigen').val();
    var destino = $('#selTransDestino').val();
    if(origen === destino){
      alert('El estado origen y destino no pueden ser iguales.');
      return;
    }
    $.ajax({
      url: BASE + '/transiciones',
      method: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        id_estado_origen: origen,
        id_estado_destino: destino,
        categoria: catActual,
        id_tipo_evento: $('#selTransTipoEvento').val() || 0
      },
      success: function(res){
        if(res.success){
          $('#panelNuevaTransicion').slideUp(200);
          cargarTransiciones();
        }
      },
      error: function(xhr){
        var msg = xhr.responseJSON ? xhr.responseJSON.msg : 'Error al guardar';
        alert(msg);
      }
    });
  });

  // Transiciones: click trash → mostrar confirm/cancel
  $(document).on('click', '.btn-ask-del-trans', function(){
    $(this).hide();
    $(this).siblings('.confirm-del-trans').css('display','inline');
  });
  $(document).on('click', '.btn-cancel-del-trans', function(){
    var wrap = $(this).closest('.confirm-del-trans');
    wrap.hide();
    wrap.siblings('.btn-ask-del-trans').show();
  });
  $(document).on('click', '.btn-confirm-del-trans', function(){
    var id = $(this).data('id');
    $.ajax({
      url: BASE + '/transiciones/' + id,
      method: 'DELETE',
      data: { _token: $('meta[name="csrf-token"]').attr('content') },
      success: function(){ cargarTransiciones(); }
    });
  });

  // ========== DESTINATARIOS ==========

  function toggleColumnaCasino(){
    esCasino = (catActual === 'casino');
    if(esCasino){
      $('.th-dest-casino, .col-dest-casino, .td-dest-casino').show();
      $('#divDestCasino').show();
      var cols = $('#colgroupDest col');
      cols.eq(0).css('width','25%');
      cols.eq(1).css('width','25%');
      cols.eq(2).css('width','40%');
      cols.eq(3).css('width','10%');
    } else {
      $('.th-dest-casino, .col-dest-casino, .td-dest-casino').hide();
      $('#divDestCasino').hide();
      var cols = $('#colgroupDest col');
      cols.eq(0).css('width','0%');
      cols.eq(1).css('width','40%');
      cols.eq(2).css('width','50%');
      cols.eq(3).css('width','10%');
    }
  }

  function cargarDestinatarios(){
    $.get(BASE + '/destinatarios', function(data){
      var filtrados = data.filter(function(d){
        if(catActual === 'auditoria') return d.categoria === 'auditoria' || d.categoria === 'despacho';
        if(catActual === 'casino') return d.categoria === 'casino' || d.categoria === 'plataforma';
        return d.categoria === catActual;
      });

      var tbody = $('#tbodyDestinatarios');
      tbody.empty();
      var colSpan = esCasino ? 4 : 3;
      if(!filtrados.length){
        tbody.append('<tr><td colspan="' + colSpan + '" class="text-center text-muted" style="padding:15px; font-size:12px;">No hay destinatarios</td></tr>');
        return;
      }
      filtrados.forEach(function(d){
        var casinoVal = '';
        if(d.id_casino) casinoVal = 'c_' + d.id_casino;
        else if(d.id_plataforma) casinoVal = 'p_' + d.id_plataforma;

        var casinoCell = esCasino
          ? '<td style="padding:8px 15px;" class="td-dest-casino">' + (d.nombre_casino || '<span style="color:#8b5cf6; font-weight:600;">Todos</span>') + '</td>'
          : '';

        tbody.append(
          '<tr>' +
            casinoCell +
            '<td style="padding:8px 15px;">' + (d.nombre || '<span class="text-muted">—</span>') + '</td>' +
            '<td style="padding:8px 15px;">' + d.email + '</td>' +
            '<td style="padding:8px 15px; text-align:right; white-space:nowrap;">' +
              '<button class="btn btn-xs btn-edit-dest" data-id="' + d.id + '" data-nombre="' + (d.nombre||'').replace(/'/g,'&#39;') + '" data-email="' + d.email + '" data-casino="' + casinoVal + '" style="border-radius:50%; width:24px; height:24px; padding:0; margin-right:3px; background:#3498db; color:#fff; border:none;"><i class="fa fa-pencil-alt" style="font-size:10px;"></i></button>' +
              '<button class="btn btn-xs btn-ask-del-dest" data-id="' + d.id + '" style="border-radius:50%; width:24px; height:24px; padding:0; background:#e2e8f0; color:#64748b; border:1px solid #cbd5e1; transition:all 0.15s;" onmouseover="this.style.background=\'#d9534f\';this.style.color=\'#fff\';this.style.borderColor=\'#d43f3a\';" onmouseout="this.style.background=\'#e2e8f0\';this.style.color=\'#64748b\';this.style.borderColor=\'#cbd5e1\';"><i class="fa fa-trash" style="font-size:10px;"></i></button>' +
              '<span class="confirm-del-dest" data-id="' + d.id + '" style="display:none;">' +
                '<button class="btn btn-xs btn-success btn-confirm-del-dest" data-id="' + d.id + '" style="border-radius:50%; width:24px; height:24px; padding:0;"><i class="fa fa-check" style="font-size:10px;"></i></button> ' +
                '<button class="btn btn-xs btn-default btn-cancel-del-dest" style="border-radius:50%; width:24px; height:24px; padding:0;"><i class="fa fa-times" style="font-size:10px;"></i></button>' +
              '</span>' +
            '</td>' +
          '</tr>'
        );
      });
    });
  }

  // ========== SELECCIONAR CATEGORÍA ==========

  window.seleccionarCatMail = function(cat){
    catActual = cat;

    // Marcar card activa
    $('.card-cat-mail').css({ background: '#fff' });
    var fondos = { auditoria: '#eff6ff', casino: '#fef9ee', funcionario1: '#f0fdf4', funcionario2: '#fdf2f8' };
    $('.card-cat-mail[data-cat="' + cat + '"]').css({ background: fondos[cat] });

    $('#tituloCatMail').html(catIconos[cat] + ' ' + catTitulos[cat]);
    $('#panelDestinatarios').slideDown(200);
    $('#panelNuevoDestinatario').hide();
    $('#panelNuevaTransicion').hide();
    $('#hidDestId').val('');
    $('#selTransTipoEvento').val('0');

    toggleColumnaCasino();
    cargarTransiciones();
    cargarDestinatarios();
  };

  // ========== ABM DESTINATARIOS ==========

  $(document).on('click', '#btnNuevoDestinatario', function(){
    $('#hidDestId').val('');
    $('#inpDestNombre').val('');
    $('#inpDestEmail').val('');
    $('#selDestCasino').val('0');
    $('#panelNuevoDestinatario').slideDown(200);
  });

  $(document).on('click', '.btn-edit-dest', function(){
    $('#hidDestId').val($(this).data('id'));
    $('#inpDestNombre').val($(this).data('nombre'));
    $('#inpDestEmail').val($(this).data('email'));
    $('#selDestCasino').val($(this).data('casino') || '0');
    $('#panelNuevoDestinatario').slideDown(200);
  });

  $(document).on('click', '#btnGuardarDestinatario', function(){
    var email = $('#inpDestEmail').val().trim();
    var nombre = $('#inpDestNombre').val().trim();
    if(!email){
      alert('Ingrese un email.');
      return;
    }
    var editId = $('#hidDestId').val();
    var casinoPlat = esCasino ? $('#selDestCasino').val() : null;

    var payload = {
      _token: $('meta[name="csrf-token"]').attr('content'),
      email: email,
      nombre: nombre,
      categoria: catActual,
      id_casino_plat: casinoPlat
    };

    if(editId){
      $.ajax({
        url: BASE + '/destinatarios/' + editId,
        method: 'PUT',
        data: payload,
        success: function(res){
          if(res.success){
            $('#panelNuevoDestinatario').slideUp(200);
            $('#hidDestId').val('');
            cargarDestinatarios();
          }
        }
      });
    } else {
      $.ajax({
        url: BASE + '/destinatarios',
        method: 'POST',
        data: payload,
        success: function(res){
          if(res.success){
            $('#panelNuevoDestinatario').slideUp(200);
            cargarDestinatarios();
          }
        }
      });
    }
  });

  // Destinatarios: click trash → ocultar edit+trash, mostrar confirm/cancel
  $(document).on('click', '.btn-ask-del-dest', function(){
    $(this).hide();
    $(this).siblings('.btn-edit-dest').hide();
    $(this).siblings('.confirm-del-dest').css('display','inline');
  });
  $(document).on('click', '.btn-cancel-del-dest', function(){
    var wrap = $(this).closest('.confirm-del-dest');
    wrap.hide();
    wrap.siblings('.btn-ask-del-dest').show();
    wrap.siblings('.btn-edit-dest').show();
  });
  $(document).on('click', '.btn-confirm-del-dest', function(){
    var id = $(this).data('id');
    $.ajax({
      url: BASE + '/destinatarios/' + id,
      method: 'DELETE',
      data: { _token: $('meta[name="csrf-token"]').attr('content') },
      success: function(){ cargarDestinatarios(); }
    });
  });

  // ========== INIT ==========

  $('#modalGestionMails').on('show.bs.modal', function(){
    catActual = null;
    esCasino = false;
    $('.card-cat-mail').css({ background: '#fff' });
    $('#panelDestinatarios').hide();
    $('#panelNuevaTransicion').hide();
    $('#panelNuevoDestinatario').hide();
  });

})(jQuery);
