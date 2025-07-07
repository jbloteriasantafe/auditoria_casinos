<div id="{{$id ?? uniqid()}}" data-listas-autocompletar
  data-listas-autocompletar-sacar-id_casino="{{$selector_id_casino}}"
  data-listas-autocompletar-sacar-str="{{$selector_str}}" 
  data-listas-autocompletar-poner-id="{{$selector_output_id ?? ''}}" hidden>
  <!-- Tiene TODAS las entradas de todos los casinos -->
  <datalist data-lista-todas>
    @foreach($data as $d)
    <option data-id_casino="{{$get_id_casino($d)}}" data-id="{{$get_id($d)}}" data-str="{{$get_str($d)}}"></option>
    @endforeach
  </datalist>
  
  <!-- Las entradas del casino elegido -->
  <datalist data-lista-cas id="{{$outputCasListId ?? uniqid()}}"></datalist>
  <!-- Tiene las que va buscando dinamicamente -->
  <datalist data-lista-str id="{{$outputStrListId ?? uniqid()}}"></datalist>
</div>
