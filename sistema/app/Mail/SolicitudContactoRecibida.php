<?php

namespace App\Mail;

use App\Models\SolicitudContacto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso al laboratorio de una nueva solicitud enviada desde el sitio web.
 *
 * Se envía al correo configurado en config/sitio.php. Con MAIL_MAILER=log el
 * mensaje completo se escribe en storage/logs/laravel.log, lo que permite
 * comprobar el contenido en desarrollo sin servidor de correo.
 */
class SolicitudContactoRecibida extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public SolicitudContacto $solicitud)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva solicitud de contacto · '.$this->solicitud->nombre,
            replyTo: $this->solicitud->email
                ? [new Address($this->solicitud->email, $this->solicitud->nombre)]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.solicitud-contacto');
    }
}
