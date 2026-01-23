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
    * Link de reset
    * @var string
    */
    public $link;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $email,string $link)
    {
      $this->email = $email;
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
