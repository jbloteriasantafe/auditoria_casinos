<a href='calendario_eventos' style=" text-decoration: none;">
<div class="opcionesHoverNotificaciones" style="font-size:15px;border-bottom:1px solid #CCC; height:70px;padding:10px 5px 10px 15px;font-family: Roboto-Regular;color: #000000;">
    <img src="../img/logos/calendario_blue.png" width="12%"></img> <span><strong>{{$notif->created_at->day}}/{{$notif->created_at->month}}:</strong> <strong>{{$notif->data['user']['nombre']}}</strong> {{$notif->data['descripcion']}}</span><br>
</div>

</a>
