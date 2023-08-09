{{-- Include guard para evitar importar varias veces algo (como estilos en-linea) --}}
<?php $include = !in_array($nombre,$GLOBALS['include_guard'] ?? []) ?>
@if($include)
<?php
$GLOBALS['include_guard'] = $GLOBALS['include_guard'] ?? []; 
$GLOBALS['include_guard'][] = $nombre;
?>
{!! $slot !!}
@endif
