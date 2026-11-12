<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Pcteckserv\CmsCore\Mail\EmailTheme;
use Pcteckserv\CmsCore\Mail\MailTransportSafety;
use Pcteckserv\CmsCore\Mail\ThemedMail;
use Pcteckserv\CmsCore\Models\Permission;
use Pcteckserv\CmsCore\Services\PermissionSynchronizer;
use Pcteckserv\CmsCore\Support\SiteOptions;
use Tests\TestCase;

class EmailThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        app('view')->replaceNamespace('cms-core', dirname(__DIR__, 2).'/resources/views');
        app(SiteOptions::class)->setMany(['smtp_enabled' => '0']);
        config(['mail.default' => 'array', 'mail.mailers.array' => ['transport' => 'array'], 'mail.from.address' => 'site@example.test', 'mail.from.name' => 'Site de teste']);
    }

    private function operator(bool $test = false): void
    {
        app(PermissionSynchronizer::class)->sync();
        $user = User::factory()->create();
        $keys = ['core.email-theme.view', ...($test ? ['core.email-theme.test'] : [])];
        $user->cmsPermissions()->sync(Permission::query()->whereIn('key', $keys)->pluck('id'));
        $this->actingAs($user);
    }

    public function test_visitantes_nao_acedem_ao_tema_preview_ou_envio(): void
    {
        $this->get('/admin/email-theme')->assertRedirect(route('login'));
        $this->get('/admin/email-theme/preview')->assertRedirect(route('login'));
        $this->post('/admin/email-theme/test', ['recipient' => 'teste@example.test'])->assertRedirect(route('login'));
    }

    public function test_menu_preview_e_documentacao_respeitam_permissoes(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/admin/email-theme')->assertForbidden();
        $this->get('/admin/email-theme/preview')->assertForbidden();
        $this->get('/admin')->assertOk()->assertDontSee(route('admin.email-theme.index'));
        $this->operator();
        $this->get('/admin/email-theme')->assertOk()
            ->assertSee('Tema de email')->assertSee('Pré-visualização renderizada')
            ->assertSee('cms-core-email-theme')->assertSee('resources/views/vendor/cms-core/emails/layout.blade.php')
            ->assertSee('sandbox', false)->assertSee(route('admin.email-theme.preview'), false)
            ->assertDontSee('action="'.route('admin.email-theme.test').'"', false);
        $this->post('/admin/email-theme/test', ['recipient' => 'teste@example.test'])->assertForbidden();
        $preview = $this->get('/admin/email-theme/preview')->assertOk()->assertSee('cms-email__brand')->assertSee('#000000')->assertSee('Olá Ana');
        $this->assertStringContainsString('no-store', $preview->headers->get('Cache-Control'));
        $this->assertStringContainsString("default-src 'none'", $preview->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("frame-ancestors 'self'", $preview->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('noindex', $preview->headers->get('X-Robots-Tag'));
    }

    public function test_email_de_teste_utiliza_o_tema_e_apenas_dados_simulados(): void
    {
        $this->operator(true);
        $this->get('/admin/email-theme')->assertOk()->assertSee('Enviar email de teste');
        $transport = Mail::mailer()->getSymfonyTransport();
        $this->post('/admin/email-theme/test', ['recipient' => 'teste@example.test', 'subject' => 'INJETADO', 'body' => 'INJETADO', 'from_address' => 'injetado@example.test'])
            ->assertRedirect(route('admin.email-theme.index'))->assertSessionHasNoErrors()->assertSessionHas('cms_email_theme_success');
        $email = $transport->messages()->first()->getOriginalMessage();
        $this->assertSame('teste@example.test', $email->getTo()[0]->getAddress());
        $this->assertSame('site@example.test', $email->getFrom()[0]->getAddress());
        $this->assertSame('[TESTE] Tema de email', $email->getSubject());
        $this->assertStringContainsString('cms-email__brand', $email->getHtmlBody());
        $this->assertStringContainsString('cms-email__footer', $email->getHtmlBody());
        $this->assertStringNotContainsString('INJETADO', $email->getHtmlBody());
    }

    public function test_destinatarios_invalidos_csrf_e_limite_de_envios(): void
    {
        $this->operator(true);
        $url = '/admin/email-theme/test';
        $this->app['env'] = 'local';
        $this->post($url, ['recipient' => 'teste@example.test'])->assertStatus(419);
        $this->app['env'] = 'testing';
        $this->postJson($url, ['recipient' => 'invalido'])->assertUnprocessable();
        $this->postJson($url, ['recipient' => ['teste@example.test']])->assertUnprocessable();
        $this->postJson($url, ['recipient' => "teste@example.test\r\nBcc: outro@example.test"])->assertUnprocessable();
        $this->assertCount(0, Mail::mailer()->getSymfonyTransport()->messages());
    }

    public function test_limite_impede_sexto_email(): void
    {
        $this->operator(true);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/email-theme/test', ['recipient' => 'teste@example.test'])->assertSessionHasNoErrors();
        }
        $this->post('/admin/email-theme/test', ['recipient' => 'teste@example.test'])->assertTooManyRequests()->assertSee('Aguarde um minuto');
        $this->assertCount(5, Mail::mailer()->getSymfonyTransport()->messages());
    }

    public function test_transporte_inseguro_ou_ciclico_e_bloqueado(): void
    {
        $this->operator(true);
        config(['mail.default' => 'failover', 'mail.mailers.failover' => ['transport' => 'failover', 'mailers' => ['smtp', 'log']], 'mail.mailers.log' => ['transport' => 'log']]);
        Mail::fake();
        $this->post('/admin/email-theme/test', ['recipient' => 'teste@example.test'])->assertSessionHasErrors('recipient');
        Mail::assertNothingSent();
        config(['mail.mailers.failover.mailers' => ['failover']]);
        $this->assertFalse(app(MailTransportSafety::class)->ready());
    }

    public function test_falhas_do_transporte_nao_expoem_detalhes_nem_repetem_envio(): void
    {
        $this->operator(true);
        Log::spy();
        Mail::shouldReceive('to')->once()->with('teste@example.test')->andThrow(new \RuntimeException('SEGREDO-DO-TRANSPORTE'));
        $this->post('/admin/email-theme/test', ['recipient' => 'teste@example.test'])->assertSessionHasErrors('recipient')->assertSessionMissing('cms_email_theme_success');
        $this->assertStringNotContainsString('SEGREDO', json_encode(session('errors')->all()));
        Log::shouldHaveReceived('warning')->once()->with('Envio de teste do tema de email por verificar.', ['exception' => \RuntimeException::class]);
    }

    public function test_resultado_incerto_nao_anuncia_sucesso(): void
    {
        $this->operator(true);
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andReturnNull();
        $this->post('/admin/email-theme/test', ['recipient' => 'teste@example.test'])->assertSessionHasErrors('recipient')->assertSessionMissing('cms_email_theme_success');
    }

    public function test_escaping_e_override_global_do_cabecalho(): void
    {
        $data = app(EmailTheme::class)->sample();
        $data['heading'] = '<script>alert(1)</script>';
        $data['body'] = '{{ config("app.key") }}';
        $html = (new ThemedMail($data))->render();
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString(config('app.key'), $html);
        $directory = storage_path('framework/testing/email-theme-'.bin2hex(random_bytes(6)));
        mkdir($directory.'/emails', 0777, true);
        file_put_contents($directory.'/emails/header.blade.php', '<p>CABEÇALHO GLOBAL DO SITE</p>');
        try {
            view()->getFinder()->prependNamespace('cms-core', $directory);
            view()->getFinder()->flush();
            $html = (new ThemedMail($data))->render();
            $this->assertStringContainsString('CABEÇALHO GLOBAL DO SITE', $html);
            $this->assertStringContainsString('&lt;script&gt;', $html);
        } finally {
            unlink($directory.'/emails/header.blade.php');
            rmdir($directory.'/emails');
            rmdir($directory);
            view()->getFinder()->flush();
        }
    }
}
