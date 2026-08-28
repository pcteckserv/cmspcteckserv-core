<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;
use Pcteckserv\CmsCore\Models\ActivityLog;
use Pcteckserv\CmsCore\Models\Permission;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Support\Permissions\PermissionRegistry;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_com_sucesso_cria_log(): void
    {
        $user = User::factory()->create(['password' => Hash::make('palavra-passe-segura')]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'palavra-passe-segura',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('cms_activity_logs', [
            'action' => 'auth.login',
            'category' => 'authentication',
            'user_id' => $user->id,
            'subject_id' => $user->id,
        ]);
    }

    public function test_login_falhado_cria_log_sem_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('palavra-passe-segura')]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'errada',
        ])->assertSessionHasErrors('email');

        $log = ActivityLog::query()->where('action', 'auth.login_failed')->firstOrFail();

        $this->assertSame($user->email, $log->properties['email']);
        $this->assertSame('[REMOVIDO]', $log->properties['password']);
        $this->assertStringNotContainsString('errada', json_encode($log->properties));
    }

    public function test_logout_cria_log(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect('/');

        $this->assertDatabaseHas('cms_activity_logs', [
            'action' => 'auth.logout',
            'user_id' => $user->id,
        ]);
    }

    public function test_logger_regista_action_personalizada_subject_e_valores(): void
    {
        $user = User::factory()->create();

        $log = app(ActivityLoggerContract::class)->log(
            action: 'my-plugin.entity.published',
            category: 'plugins',
            description: 'Entidade publicada.',
            subject: $user,
            properties: ['api_token' => 'segredo', 'visible' => 'ok'],
            oldValues: ['name' => 'Antigo'],
            newValues: ['name' => 'Novo'],
        );

        $this->assertSame('my-plugin.entity.published', $log->action);
        $this->assertSame(User::class, $log->subject_type);
        $this->assertSame($user->id, $log->subject_id);
        $this->assertSame('[REMOVIDO]', $log->properties['api_token']);
        $this->assertSame('ok', $log->properties['visible']);
        $this->assertSame(['name' => 'Antigo'], $log->old_values);
        $this->assertSame(['name' => 'Novo'], $log->new_values);
    }

    public function test_utilizador_sem_permissao_nao_ve_logs_e_autorizado_ve(): void
    {
        app(PermissionRegistry::class)->register([
            'core.activity-logs.view' => ['label' => 'Ver logs de atividade', 'group' => 'Auditoria'],
        ]);

        $plainUser = User::factory()->create();
        $admin = $this->userWithPermission('core.activity-logs.view');

        $this->actingAs($plainUser)->get(route('admin.activity-logs.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.activity-logs.index'))->assertOk();
    }

    public function test_filtros_funcionam(): void
    {
        ActivityLog::query()->create(['action' => 'auth.login', 'category' => 'authentication', 'description' => 'Login', 'ip_address' => '127.0.0.1']);
        ActivityLog::query()->create(['action' => 'media.deleted', 'category' => 'media', 'description' => 'Media removida', 'ip_address' => '10.0.0.1']);

        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('admin.activity-logs.index', ['category' => 'media']))
            ->assertOk()
            ->assertSee('<code>media.deleted</code>', false)
            ->assertDontSee('<code>auth.login</code>', false);
    }

    public function test_super_admin_nao_aparece_nos_utilizadores_roles_ou_logs(): void
    {
        $superAdmin = $this->superAdmin();

        app(ActivityLoggerContract::class)->log(
            action: 'auth.login',
            category: 'authentication',
            description: 'Utilizador iniciou sessão.',
            subject: $superAdmin,
            user: $superAdmin,
        );

        $this->actingAs($superAdmin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee($superAdmin->email)
            ->assertDontSee('Super Admin');

        $this->actingAs($superAdmin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertDontSee('core.super_admin')
            ->assertDontSee('Super Admin');

        $this->actingAs($superAdmin)
            ->get(route('admin.activity-logs.index'))
            ->assertOk()
            ->assertDontSee('<code>auth.login</code>', false)
            ->assertDontSee($superAdmin->email);
    }

    public function test_logger_funciona_sem_request_http(): void
    {
        $this->app->forgetInstance('request');

        $log = app(ActivityLoggerContract::class)->log(
            action: 'system.task',
            category: 'system',
            description: 'Tarefa executada.',
        );

        $this->assertNull($log->ip_address);
        $this->assertNull($log->url);
    }

    public function test_pruning_respeita_retention_days(): void
    {
        config(['cms-core.activity_log.retention_days' => 10]);

        ActivityLog::query()->create(['action' => 'old.log', 'created_at' => now()->subDays(11)]);
        ActivityLog::query()->create(['action' => 'new.log', 'created_at' => now()->subDays(2)]);

        $this->artisan('cms-core:activity-logs:prune')->assertSuccessful();

        $this->assertDatabaseMissing('cms_activity_logs', ['action' => 'old.log']);
        $this->assertDatabaseHas('cms_activity_logs', ['action' => 'new.log']);
    }

    private function userWithPermission(string $permissionKey): User
    {
        $permission = Permission::query()->firstOrCreate(
            ['key' => $permissionKey],
            ['label' => $permissionKey, 'group' => 'Auditoria'],
        );
        $role = Role::query()->create(['name' => 'Auditor', 'key' => 'core.auditor']);
        $role->permissions()->sync([$permission->id]);

        $user = User::factory()->create();
        $user->cmsRoles()->sync([$role->id]);

        return $user;
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
