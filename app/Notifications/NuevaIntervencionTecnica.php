<?php

namespace App\Notifications;

use Carbon\Carbon;
use App\Usuario;
use App\TipoEventualidad;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/*
* Las intervenciones técnicas son las eventualidades ambientales, o tecnicas
* que afectan a un grupo de máquinas
*/
class NuevaIntervencionTecnica extends Notification
{
    use Queueable;

    public $eventualidad;
    public $user;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($ev)
    {
        $id_usuario = session('id_usuario');
        $user = Usuario::find($id_usuario);
        $this->user = $user;
        $this->eventualidad=$ev;
      //  $this->message = (object) array('image' => 'http://localhost:8000/img/bannerCorreo.jpg');
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
        $titulo = 'Tienes una intervención técnica para revisar';
        $main = 'Ha surgido una eventualidad del tipo '.TipoEventualidad::find($this->eventualidad->id_tipo_eventualidad)
                                 ->descripcion.' al cual revisar.'.$this->user->nombre.' la ha creado.';
        return (new MailMessage)
            //  ->embed('http://localhost:8000/img/bannerCorreo.jpg')
              ->subject('Tienes una intervención técnica para revisar - CAS Lotería de Santa Fe')
              ->markdown('vendor.mail.Notificacion.index', ['titulo' => $titulo,
                                                                'main' => $main,
                                                                'ruta' => 'http://localhost:8000/eventualidades',
                                                                'boton' => 'VER INTERVENCIONES TÉCNICAS'
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
      $tipoEv = TipoEventualidad::find($this->eventualidad->id_tipo_eventualidad);
        return[
            'eventualidad'=>$this->eventualidad,
            'user'=> $this->user,
            'descripcion' => "cargó una intervención técnica del tipo ".$tipoEv->descripcion."."
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
