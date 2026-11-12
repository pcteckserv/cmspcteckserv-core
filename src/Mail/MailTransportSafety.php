<?php

namespace Pcteckserv\CmsCore\Mail;

final class MailTransportSafety
{
    public function ready(?string $mailer = null, array $seen = []): bool
    {
        $mailer ??= config('mail.default');
        if (!$mailer || in_array($mailer, $seen, true)) {
            return false;
        }
        $transport = config('mail.mailers.'.$mailer.'.transport');
        if (!$transport || $transport === 'log') {
            return false;
        }
        if ($transport === 'array') {
            return app()->runningUnitTests();
        }
        if (in_array($transport, ['failover', 'roundrobin'], true)) {
            $children = config('mail.mailers.'.$mailer.'.mailers', []);
            if (!$children) {
                return false;
            }
            foreach ($children as $child) {
                if (!$this->ready($child, [...$seen, $mailer])) {
                    return false;
                }
            }
        }

        return true;
    }
}
