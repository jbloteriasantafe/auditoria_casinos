<?php $uid = uniqid(); ?>
<div class="modal fade {!! $clases_modal ?? '' !!}" id="{{$uid}}" {!! $attrs_modal !!} data-js-modal tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  @if(isset($grande))
  <div class="modal-dialog modal-lg" style="width: {{$grande}}%;">
  @else
  <div class="modal-dialog">
  @endif
    <div class="modal-content">
      <div class="modal-header" style="{!! $estilo_cabecera ?? '' !!}">
        <button type="button" class="close" data-dismiss="modal" data-js-salir><i class="fa fa-times"></i></button>
        <button type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#{{$uid}} .collapse" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">
          {!! $titulo ?? '##TITULO-MODAL##' !!}
        </h3>
      </div>
      <div class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          {!! $cuerpo ?? '##CUERPO-MODAL##' !!}
        </div>
        <div class="modal-footer">
          {!! $pie ?? '' !!}
          @if(!isset($salir))
          <button type="button" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>
          @else
          {!! $salir !!}
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
