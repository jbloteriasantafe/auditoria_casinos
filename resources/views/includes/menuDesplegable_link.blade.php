@if($link != '#')
<li class='enlace'>
  <a href='{{$link}}'>{!! $op !!}</a>
</li>
@else
<li class='desactivado'>
  <span>{!! $op !!}</span>
</li>
@endif