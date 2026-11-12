<?php

namespace Pcteckserv\CmsCore\Mail;

use Pcteckserv\CmsCore\Support\SiteOptions;

final class EmailTheme
{
    public function title(): string
    {
        return (string) app(SiteOptions::class)->get('site_title', config('app.name', 'Site'));
    }

    public function sample(): array
    {
        return [
            'subject' => '[TESTE] Tema de email',
            'heading' => 'O seu tema de email',
            'body' => "Olá Ana,\n\nEste é um email de demonstração do tema global do site.\n\nOs emails de diferentes módulos, como Loja e Clientes, partilham este cabeçalho e rodapé. O assunto, o título e o conteúdo são fornecidos por cada módulo.\n\nEste exemplo não contém dados reais de clientes ou encomendas.",
            'action_label' => 'Visitar o site',
            'action_url' => url('/'),
        ];
    }
}
