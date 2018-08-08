<?php

namespace App\Notifications;

use Carbon\Carbon;
use App\Usuario;
use App\TipoMovimiento;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class RelevamientoGenerado extends Notification
{
    use Queueable;

    public $fiscalizacion;
    public $user;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($fiscalizacion)
    {
        $id_usuario = session('id_usuario');
        $user = Usuario::find($id_usuario);
        $this->user=$user;
        $this->fiscalizacion=$fiscalizacion;
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
        $titulo = 'Tienes un movimiento para relevar';
        $main = 'Tienes un movimiento del tipo '.TipoMovimiento::find($this->fiscalizacion->log_movimiento->id_tipo_movimiento)
                                 ->descripcion.' para relevar.';
        return (new MailMessage)
              ->subject('Tienes un movimiento para relevar - CAS Lotería de Santa Fe')
              ->markdown('vendor.mail.Notificacion.index', ['titulo' => $titulo,
                                                                'main' => $main,
                                                                'ruta' => 'http://localhost:8000/relevamientos_movimientos',
                                                                'boton' => 'VER RELEVAMIENTOS'
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
        return[
            'fiscalizacion'=>$this->fiscalizacion,
            'user'=> ' ',
            'descripcion' => "Tiene un nuevo movimiento para relevar."
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
