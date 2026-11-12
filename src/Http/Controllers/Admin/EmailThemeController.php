<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Pcteckserv\CmsCore\Http\Requests\Admin\TestEmailThemeRequest;
use Pcteckserv\CmsCore\Mail\EmailTheme;
use Pcteckserv\CmsCore\Mail\MailTransportSafety;
use Pcteckserv\CmsCore\Mail\ThemedMail;
use Pcteckserv\CmsCore\Support\SiteOptions;
use Throwable;

final class EmailThemeController
{
    public function index(): View
    {
        return view('cms-core::admin.email-theme.index');
    }

    public function preview(EmailTheme $theme): Response
    {
        return response()->view('cms-core::emails.message', ['email' => $theme->sample()])
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; img-src 'self' data: https:; base-uri 'none'; frame-ancestors 'self'; form-action 'none'");
    }

    public function test(TestEmailThemeRequest $request, EmailTheme $theme, SiteOptions $options, MailTransportSafety $safety): RedirectResponse
    {
        $target = redirect()->route('admin.email-theme.index');
        try {
            $options->applyMailConfig();
            if (!$safety->ready() || !filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)) {
                return $target->withErrors(['recipient' => 'Configure um transporte de email válido nas definições SMTP antes de enviar o teste.'])->withInput($request->only('recipient'));
            }
            // Um envio por pedido, sem repetição: uma falha pode ocorrer após a aceitação.
            $sent = Mail::to($request->validated('recipient'))->send(new ThemedMail($theme->sample()));
            if (!$sent instanceof SentMessage) {
                return $target->withErrors(['recipient' => 'O envio não foi confirmado. Verifique o transporte antes de repetir o teste.']);
            }
        } catch (Throwable $exception) {
            Log::warning('Envio de teste do tema de email por verificar.', ['exception' => $exception::class]);

            return $target->withErrors(['recipient' => 'Não foi possível confirmar o envio do email de teste. Verifique o transporte antes de repetir.']);
        }

        return $target->with('cms_email_theme_success', 'O transporte aceitou o email de teste. Verifique a caixa de entrada e o correio não solicitado.');
    }
}
