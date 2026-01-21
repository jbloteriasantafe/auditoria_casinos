<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecuperarContrasena extends Mailable
{
    use Queueable, SerializesModels;

    /**
    * El recipiente
    * @var string
    */
    public $email;
    
    /**
    * El recipiente especificamente ingresado (Que es equivalente en terminos de correo)
    * Ejemplo pepito@gmail.com === pepito+ABC@gmail.com
    * @var string
    */
    public $email_especifico;
    
    /**
    * Link de reset
    * @var string
    */
    public $link;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $email,string $email_especifico,string $link)
    {
      $this->email = $email;
      $this->email_especifico = $email_especifico;
      $this->link = $link;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Restablecer su contraseña')
        ->view('recuperarContrasena');
    }
}
