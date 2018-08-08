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

class NuevoMovimiento extends Notification
{
    use Queueable;

    public $logMovimiento;
    public $tipoMov;
    public $user;
    public $message;
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
        $this->tipoMov = TipoMovimiento::find($logMov->id_tipo_movimiento);
        //$this->message = (object) array('image' => 'http://localhost:8000/img/bannerCorreo.jpg');
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
      $titulo = 'Tienes un movimiento para asignar MTMs';
      $main = 'Tienes un movimiento del tipo '.$this->tipoMov->descripcion
      .' al cual asignarle máquinas para enviar a fiscalizar.'. $this->user->nombre.', lo ha iniciado.';
      return (new MailMessage)
            ->subject('Tienes un movimiento para asignar MTMs - CAS Lotería de Santa Fe')
            // ->attach(public_path('/img').'/bannerCorreo.jpg', [
            //                   'as' => 'bannerCorreo.jpg',
            //                   'mime' => 'img/jpeg',
            //           ])
            ->markdown('vendor.mail.Notificacion.index', ['titulo' => $titulo,
                                                              'main' => $main,
                                                              'ruta' => 'http://localhost:8000/movimientos',
                                                              'boton' => 'VER MOVIMIENTOS'
                                                            ]);
        // return (new MailMessage)
        //             ->line($titulo)
        //             ->action('Ver movimientos', 'http://localhost:8000/movimientos')
        //             ->line($main);
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
            'logMovimiento'=>$this->logMovimiento,
            'user'=> $this->user,
            'descripcion' => "un movimiento de ". $this->tipoMov->descripcion .".",
            'message' => (object) array('image' => 'http://localhost:8000/img/bannerCorreo.jpg')
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
