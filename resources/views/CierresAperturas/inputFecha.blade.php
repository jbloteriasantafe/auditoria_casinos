@component('CierresAperturas/include_guard',['nombre' => 'input_fecha'])
<link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/>
<style>
  .date[data-disabled="1"][data-js-fecha] span {
    display: none;
    visibility: hidden;
  }
</style>
@endcomponent

<div class="form-group">
  <div class='input-group date' style="width: 100%;" data-disabled="0" data-js-fecha {!! $attrs_dtp ?? '' !!}>
    <input type='text' class="form-control" placeholder="{{ $placeholder ?? 'aaaa-mm-dd' }}" {!! $attrs ?? '' !!} />
    <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
    <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
  </div>
</div>
