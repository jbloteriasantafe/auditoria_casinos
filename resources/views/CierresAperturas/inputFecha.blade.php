<link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/>
<div class="form-group">
  <div class='input-group date' data-js-fecha>
    <input type='text' class="form-control" placeholder="{{ $placeholder ?? 'aaaa-mm-dd' }}" {!! $attrs ?? '' !!} />
    <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
    <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
  </div>
</div>
