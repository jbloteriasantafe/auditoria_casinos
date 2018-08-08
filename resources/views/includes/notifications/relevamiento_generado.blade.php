<a href='relevamientos_movimientos'>
  <div class="opcionesHoverNotificaciones" style="font-size:15px;border-bottom:1px solid #CCC; height:70px;padding:10px 5px 10px 15px;font-family: Roboto-Regular;color: #000000;">


<img src="../img/logos/tablero_general_blue.png" width="12%"></img><span><strong>{{$notif->created_at->day}}/{{date("F", mktime(0, 0, 0, $notif->created_at->month, 1))}}:</strong>  {{$notif->data['descripcion']}}</span><br>
    <!-- <img src="../img/logos/tablero_general_blue.png" width="12%"></img><span><strong>{{$notif->created_at->day}}/{{$notif->created_at->month}}:</strong>  {{$notif->data['descripcion']}}</span><br> -->
</div>


</a>
