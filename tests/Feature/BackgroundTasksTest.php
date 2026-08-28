<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Services\Queues\QueueMonitor;
use Tests\TestCase;

class BackgroundTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitor_mostra_jobs_pendentes_e_falhados(): void
    {
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\ExampleJob']),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\FailedJob']),
            'exception' => 'Erro de teste',
            'failed_at' => now(),
        ]);

        $overview = app(QueueMonitor::class)->overview();

        $this->assertSame(1, $overview['pending_jobs']);
        $this->assertSame(1, $overview['failed_jobs']);
        $this->assertSame('App\\Jobs\\ExampleJob', $overview['recent_jobs'][0]['name']);
    }

    public function test_painel_de_tarefas_exige_autenticacao_e_permissao(): void
    {
        $this->get(route('admin.queues.dashboard'))->assertRedirect(route('login'));

        $plainUser = User::factory()->create();
        $admin = $this->superAdmin();

        $this->actingAs($plainUser)->get(route('admin.queues.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.queues.dashboard'))->assertOk()->assertSee('Tarefas em segundo plano');
    }

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(
            ['key' => 'core.super_admin'],
            ['name' => 'Super Admin', 'is_protected' => true],
        );

        $user = User::factory()->create();
        $user->cmsRoles()->sync([$role->id]);

        return $user;
    }
}
