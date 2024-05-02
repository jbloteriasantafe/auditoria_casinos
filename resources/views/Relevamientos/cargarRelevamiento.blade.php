<!-- Modal cargar relevamientos -->
<div class="modal fade" id="modalCargaRelevamiento" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width:90%;">
    <div class="modal-content">
      <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#FF6E40;">
       <button id="btn-minimizarCargar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCargar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
       <h3 class="modal-title">CARGAR RELEVAMIENTO</h3>
      </div>
      <div id="colapsadoCargar" class="collapse in">
        <div class="modal-body modalCuerpo">
          <form id="frmCargaRelevamiento" name="frmCargaRelevamiento" class="form-horizontal" novalidate="">
            <div class="row">
              <div class="col-lg-2 col-xl-offset-1">
                <h5>FECHA DE RELEVAMIENTO</h5>
                <input id="cargaFechaActual" type='text' class="form-control" readonly>
              </div>
              <div class="col-lg-2">
                <h5>FECHA DE GENERACIÓN</h5>
                <input id="cargaFechaGeneracion" type='text' class="form-control" readonly>
              </div>
              <div class="col-lg-2">
                <h5>CASINO</h5>
                <input id="cargaCasino" type='text' class="form-control" readonly>
                <span id="alertaCasino" class="alertaSpan"></span>
              </div>
              <div class="col-lg-2">
                <h5>SECTOR</h5>
                <input id="cargaSector" type='text' class="form-control" readonly>
                <span id="alertaSector" class="alertaSpan"></span>
              </div>
              <div class="col-lg-2">
                <h5>SUB RELEVAMIENTO</h5>
                <input id="cargaSubrelevamiento" type='text' class="form-control" readonly>
                <span id="alertaSubrelevamiento" class="alertaSpan"></span>
              </div>
            </div>
            <div class="row">
              <div class="col-md-2 col-xl-offset-1">
                <h5>FISCALIZADOR CARGA</h5>
                <input id="fiscaCarga" type="text"class="form-control" readonly>
              </div>
              <div class="col-md-2">
                <h5>FISCALIZADOR TOMA</h5>
                <input id="inputFisca" class="form-control" type="text" autocomplete="off">
              </div>
              <div class="col-md-2">
                <h5>TÉCNICO</h5>
                <input id="tecnico" type="text"class="form-control">
              </div>
              <div class="col-md-3">
                <h5>HORA EJECUCIÓN</h5>
                 <div class='input-group date' id='dtpFecha' data-link-field="fecha_ejecucion" data-date-format="HH:ii" data-link-format="HH:ii">
                   <input type='text' class="form-control" placeholder="Fecha de ejecución del relevamiento" id="fecha"  data-content='Este campo es <strong>requerido</strong>' data-trigger="manual" data-toggle="popover" data-placement="top" autocomplete="off" />
                   <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                   <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                 </div>
                 <input type="hidden" id="fecha_ejecucion" value=""/>
              </div>
            </div>
            <br>
            <br>
            <div class="row">
              <div class="col-md-12">
                <table id="tablaCargaRelevamiento" class="table">
                  <thead>
                    <tr>
                      <th width="3%">MTM</th>
                      @for($c=1;$c<=$CONTADORES;$c++)
                      <th {{$c<=$CONTADORES_VISIBLES? '' : 'hidden'}}>CONTADOR {{$c}}</th>
                      @endfor
                      <th width="2%">DIF</th>
                      <th width="12%">CAUSA DE NO TOMA</th>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
            </div>
            <br>
            <div class="row">
              <div class="col-md-8 col-md-offset-2">
                <h5>OBSERVACIONES</h5>
                <textarea id="observacion_carga" class="form-control" style="resize:vertical;"></textarea>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-warningModificar" id="btn-finalizar" value="nuevo" style="position:absolute;left:20px;">FINALIZAR RELEVAMIENTO</button>
          <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">GUARDAR TEMPORALMENTE</button>
          <button type="button" class="btn btn-default" id="btn-salir">SALIR</button>
          <div class="mensajeSalida">
            <br>
            <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">CAMBIOS SIN GUARDAR</span>
            <br>
            <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Presione SALIR nuevamente para salir sin guardar cambios.</span>
            <br>
            <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Presione GUARDAR TEMPORALMENTE para guardar los cambios.</span>
          </div>
          <input type="hidden" id="id_relevamiento" value="0">
        </div>
      </div>
    </div>
  </div>
</div>

<table hidden>
  <tr class="moldeCarga" data-medida="" data-denominacion="">
    <td class="maquina">0000</td>
    @for($c=1;$c<=$CONTADORES;$c++)
    <td {{$c<=$CONTADORES_VISIBLES? '' : 'hidden'}}><input class="contador cont{{$c}} form-control"></td>
    <td hidden><input class="formulaCont{{$c}}"></td>
    <td hidden><input class="formulaOper{{$c}}"></td>
    @endfor
    <td hidden><input class="producidoCalculado form-control" style="text-align: right; border: 2px solid rgb(109, 199, 190); color: rgb(109, 199, 190);"></td>
    <td hidden><input class="producido form-control" style="text-align: right; border: 2px solid rgb(109, 199, 190); color: rgb(109, 199, 190);"></td>
    <td hidden><input class="diferencia form-control" style="text-align: right;"></td>
    <td style="text-align: center;" class="estado_diferencia">
      <i class="fa fa-times icono_estado icono_incorrecto" style="color: rgb(239, 83, 80);" hidden></i>
      <i class="fa fa-check icono_estado icono_correcto" style="color: rgb(102, 187, 106);" hidden></i>
      <i class="fa fa-ban icono_estado icono_no_toma" style="color: rgb(30, 144, 255);" hidden></i>
      <a class="pop icono_estado icono_truncado" data-content="Contadores importados truncados" data-placement="top" rel="popover" data-trigger="hover" hidden>
        <i class="pop fa fa-exclamation" style="color: rgb(255, 167, 38); display: inline-block;"></i>
      </a>
      <a class="pop icono_estado icono_no_importado" data-content="No se importaron contadores" data-placement="top" rel="popover" data-trigger="hover" hidden>
        <i class="pop fa fa-question" style="color: rgb(66, 165, 245); display: inline-block;"></i>
      </a>
    </td>
    <td>
      <select class="tipo_causa_no_toma form-control">
        <option value=""></option>
        @foreach($tipos_causa_no_toma as $t)
        <option value="{{$t->id_tipo_causa_no_toma}}" {{$t->deprecado? 'disabled' : ''}}>{{$t->descripcion}}</option>
        @endforeach
      </select>
    </td>
    <td hidden>
      @php
      $popup = function($select){
        $checked1 = $select == 1? 'checked' : '';
        $checked2 = $select == 2? 'checked' : '';
        return
         '<div align="left">
          <input type="radio" name="medida" value="credito" '.$checked1.'>
          <i style="margin-left:5px;position:relative;top:-3px;" class="fa fa-fw fa-life-ring"></i>
          <span style="position:relative;top:-3px;"> Cŕedito</span><br>
          <input type="radio" name="medida" value="pesos" '.$checked2.'>
          <i style="margin-left:5px;position:relative;top:-3px;" class="fas fa-dollar-sign"></i>
          <span style="position:relative;top:-3px;"> Pesos</span> <br><br>
          <button id="1" class="btn btn-deAccion btn-successAccion ajustar" type="button" style="margin-right:8px;">AJUSTAR</button>
          <button class="btn btn-deAccion btn-defaultAccion cancelarAjuste" type="button">CANCELAR</button>
        </div>';
      };
      @endphp
      <button data-medida="1" class="btn btn-warning pop medida" title="AJUSTE" data-trigger="manual" data-toggle="popover" data-placement="left" data-html="true" type="button" class="btn btn-warning pop medida"
       data-content="{{$popup(1)}}">
        <i class="fa fa-fw fa-life-ring"></i>
      </button>
      <button data-medida="2" class="btn btn-warning pop medida" title="AJUSTE" data-trigger="manual" data-toggle="popover" data-placement="left" data-html="true" type="button" class="btn btn-warning pop medida"
       data-content="{{$popup(2)}}">
        <i class="fas fa-dollar-sign"></i>
      </button>
    </td>
    <td hidden>
      <select class="a_pedido form-control acciones_validacion">
        <option value="0" selected>NO</option>
        <option value="1">1 día</option>
        <option value="5">5 días</option>
        <option value="10">10 días</option>
        <option value="15">15 días</option>
      </select>
    </td>
    <td hidden>
      <button class="btn btn-success estadisticas_no_toma acciones_validacion" type="button">
        <i class="fas fa-fw fa-external-link-square-alt"></i>
      </button>
    </td>
  </tr>
</table>
