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

class NuevaIntervencionMtm extends Notification
{
    use Queueable;

    public $logMovimiento;
    public $user;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($logMov)
    {
        $id_usuario = session('id_usuario');
        $user = Usuario::find($id_usuario);
        $this->user = $user;
        $this->logMovimiento=$logMov;
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
        $titulo = 'Tienes una intervención de MTMs para revisar';
        $main = $this->user->nombre.' creó una intervención de MTMs del tipo '.TipoMovimiento::find($this->logMovimiento->id_tipo_movimiento)
                                 ->descripcion;
        return (new MailMessage)
              ->subject('Tienes una intervención de MTMs para revisar - CAS Lotería de Santa Fe')
              ->markdown('vendor.mail.Notificacion.index', ['titulo' => $titulo,
                                                                'main' => $main,
                                                                'ruta' => 'http://localhost:8000/eventualidadesMTM',
                                                                'boton' => 'VER INTERVENCIONES MTMs'
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
      $tipoMov = TipoMovimiento::find($this->logMovimiento->id_tipo_movimiento);
        return[
            'logMovimiento'=>$this->logMovimiento,
            'user'=> $this->user,
            'descripcion' => "creó una intervención de MTM ".$tipoMov->descripcion."."
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
