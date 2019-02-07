<?php

namespace App\Notifications;

use Carbon\Carbon;
use App\Usuario;
use App\LogMovimiento;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class RelevamientoCargado extends Notification
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
        $this->user = $user;
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
      $log = LogMovimiento::find($this->fiscalizacion->id_log_movimiento);
      $titulo = 'Tienes un movimiento de MTMs para validar';
      $main = 'Te notificamos que el movimiento del día '.$log->fecha.' del tipo '
             .$log->tipo_movimiento->descripcion.' ya ha sido fiscalizado.';
      return (new MailMessage)
            ->subject('Tienes una intervención de MTMs para revisar - CAS Lotería de Santa Fe')
            ->markdown('vendor.mail.Notificacion.index', ['titulo' => $titulo,
                                                              'main' => $main,
                                                              'ruta' => 'http://10.1.121.30:8000/movimientos',
                                                              'boton' => 'VER MOVIMIENTOS'
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
        $log = LogMovimiento::find($this->fiscalizacion->id_log_movimiento);
        return[
            'fiscalizacion'=>$this->fiscalizacion,
            'user'=> ' ',
            'descripcion' => "El movimiento del día".$log->fecha." ya se fiscalizó."
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
