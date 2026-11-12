<?php

namespace Pcteckserv\CmsCore\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class ThemedMail extends Mailable
{
    public function __construct(public readonly array $email)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->email['subject']);
    }

    public function build(): self
    {
        return $this->withSymfonyMessage(function ($message): void {
            $body = $this->email['body'] ?? '';
            if (!empty($this->email['action_url'])) {
                $body .= "\n\n".($this->email['action_label'] ?? 'Abrir ligação').': '.$this->email['action_url'];
            }
            $message->text($body);
        });
    }

    public function content(): Content
    {
        return new Content(view: 'cms-core::emails.message', with: ['email' => $this->email]);
    }
}
