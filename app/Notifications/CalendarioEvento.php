<?php

namespace App\Notifications;

use Carbon\Carbon;
use App\Usuario;
use App\TipoEvento;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CalendarioEvento extends Notification
{
    use Queueable;

    public $evento;
    public $user;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($evento)
    {
        $id_usuario = session('id_usuario');
        $user = Usuario::find($id_usuario);
        $this->user = $user;
        $this->evento=$evento;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database','mail'];//,'broadcast','mail'
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $titulo = 'Tienes un nuevo evento en el calendario';
        $main = $this->user->nombre.' creó un nuevo evento del tipo '.TipoEvento::find($this->evento->id_tipo_evento)->descripcion;
        return (new MailMessage)
              ->subject('Tienes un nuevo evento - CAS Lotería de Santa Fe')
              ->markdown('vendor.mail.Notificacion.index', ['titulo' => $titulo,
                                                                'main' => $main,
                                                                'ruta' => 'http://localhost:8000/calendario_eventos',
                                                                'boton' => 'VER CALENDARIO'
                                                              ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
      $tipoMov = TipoEvento::find($this->evento->id_tipo_evento);
        return[
            'evento'=>$this->evento,
            'user'=> $this->user,
            'descripcion' => "creó un evento del tipo ".$tipoMov->descripcion."."
        ];
    }


    // public function toBroadcast($notifiable)
    // {
    //     return new BroadcastMessage([
    //         'logMovimiento'=>$this->$logMovimiento,
    //         'user'=>auth()->user()
    //     ]);
    // }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

}
