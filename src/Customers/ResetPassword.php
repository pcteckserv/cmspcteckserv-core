<?php

namespace Pcteckserv\CmsCore\Customers;

use Illuminate\Auth\Notifications\ResetPassword as Base;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends Base
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Recuperar acesso à sua conta')
            ->greeting('Olá, '.$notifiable->name.'.')
            ->line('Recebemos um pedido para definir uma nova palavra-passe para a sua conta.')
            ->action('Definir palavra-passe', $this->resetUrl($notifiable))
            ->line('Esta ligação expira em '.config('auth.passwords.shop_customers.expire', 60).' minutos.')
            ->line('Se não fez este pedido, pode ignorar este email.');
    }

    protected function resetUrl($notifiable): string
    {
        return route('customers.password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
    }
}
