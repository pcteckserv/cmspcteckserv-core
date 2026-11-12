<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Pcteckserv\CmsCore\Mail\ThemedMail;
use Pcteckserv\CmsCore\Models\BackupPlan;
use Pcteckserv\CmsCore\Models\Permission;
use Pcteckserv\CmsCore\Services\Backups\BackupNotificationService;
use Pcteckserv\CmsCore\Services\PermissionSynchronizer;
use Pcteckserv\CmsCore\Support\SiteOptions;
use Tests\TestCase;

class CoreEmailDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        app(SiteOptions::class)->setMany(['smtp_enabled' => '0']);
        config(['mail.default' => 'array', 'mail.mailers.array' => ['transport' => 'array'], 'mail.from.address' => 'site@example.test']);
    }

    private function operator(string $permission): void
    {
        app(PermissionSynchronizer::class)->sync();
        $user = User::factory()->create();
        $user->cmsPermissions()->sync(Permission::query()->where('key', $permission)->pluck('id'));
        $this->actingAs($user);
    }

    private function plan(): BackupPlan
    {
        return BackupPlan::query()->create([
            'name' => 'Backup de teste', 'type' => 'full', 'frequency' => 'daily',
            'run_at' => '02:00', 'timezone' => 'Europe/Lisbon',
            'notification_emails' => ['admin@example.test'],
            'notification_events' => ['backup_failed' => true],
            'repeat_alert_after_minutes' => 360,
        ]);
    }

    public function test_teste_smtp_utiliza_o_tema_e_preserva_destinatario(): void
    {
        $this->operator('core.smtp.test');
        app(SiteOptions::class)->setMany(['smtp_enabled' => '1']);
        Mail::fake();
        $this->post('/admin/smtp-settings/test', ['test_recipient' => 'destino@example.test'])->assertRedirect()->assertSessionHas('cms_smtp_success');
        Mail::assertSent(ThemedMail::class, fn ($mail) => $mail->hasTo('destino@example.test') && $mail->email['subject'] === 'Teste SMTP - CMS PCTECK');
    }

    public function test_alertas_de_backup_usam_o_tema_sem_duplicar_envios(): void
    {
        Mail::fake();
        $plan = $this->plan();
        $service = app(BackupNotificationService::class);
        $service->sendForEvent($plan, 'backup_failed');
        $service->sendForEvent($plan->fresh(), 'backup_failed');
        Mail::assertSent(ThemedMail::class, 1);
        Mail::assertSent(ThemedMail::class, fn ($mail) => $mail->hasTo('admin@example.test') && $mail->email['body'] !== '');
    }

    public function test_teste_de_backup_respeita_permissao_e_usa_tema(): void
    {
        $plan = $this->plan();
        $url = route('admin.backups.plans.test-email', $plan);
        Mail::fake();
        $this->actingAs(User::factory()->create())->post($url)->assertForbidden();
        Mail::assertNothingSent();
        $this->operator('backups.configure');
        $this->post($url)->assertRedirect()->assertSessionHas('cms_backups_success');
        Mail::assertSent(ThemedMail::class, fn ($mail) => $mail->hasTo('admin@example.test') && $mail->email['subject'] === '[CMS PCTECKSERV] Teste de alertas de backup');
    }

    public function test_modelo_partilhado_preserva_texto_cabecalhos_e_anexos(): void
    {
        $transport = Mail::mailer()->getSymfonyTransport();
        $mail = (new ThemedMail([
            'subject' => 'Mensagem de teste', 'body' => "Texto & símbolos <script>literal</script>\nSegunda linha",
            'action_label' => 'Confirmar', 'action_url' => 'https://example.test/confirmar?token=simulado',
        ]))->replyTo('reply@example.test')->cc('copia@example.test')->bcc('reservado@example.test')
            ->attachData('Conteúdo de teste', 'exemplo.txt', ['mime' => 'text/plain']);
        Mail::to('destino@example.test')->send($mail);
        $email = $transport->messages()->first()->getOriginalMessage();
        $this->assertStringContainsString("Texto & símbolos <script>literal</script>\nSegunda linha", $email->getTextBody());
        $this->assertStringContainsString('https://example.test/confirmar?token=simulado', $email->getTextBody());
        $this->assertStringContainsString('cms-email__brand', $email->getHtmlBody());
        $this->assertStringNotContainsString('<script>', $email->getHtmlBody());
        $this->assertSame('reply@example.test', $email->getReplyTo()[0]->getAddress());
        $this->assertSame('copia@example.test', $email->getCc()[0]->getAddress());
        $this->assertSame('reservado@example.test', $email->getBcc()[0]->getAddress());
        $this->assertCount(1, $email->getAttachments());
    }
}
