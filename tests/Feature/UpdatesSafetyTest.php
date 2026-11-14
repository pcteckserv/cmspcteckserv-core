<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Pcteckserv\CmsCore\Models\InstalledPlugin;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Plugins\PluginCatalog;
use Pcteckserv\CmsCore\Updates\ComposerInstalledPackageReader;
use Pcteckserv\CmsCore\Updates\GitTagUpdateChecker;
use Pcteckserv\CmsCore\Updates\InstalledPackage;
use Pcteckserv\CmsCore\Updates\PackageUpdater;
use Pcteckserv\CmsCore\Updates\PackageVersionRegistry;
use Pcteckserv\CmsCore\Updates\StarterPackage;
use Tests\TestCase;

final class UpdatesSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function plugin(string $slug, string $status, ?string $installed = '1.0.0'): void
    {
        InstalledPlugin::create(['slug'=>$slug, 'name'=>'tests/'.$slug, 'package'=>'tests/'.$slug, 'label'=>'Plugin '.$slug, 'status'=>$status, 'installed_version'=>$installed]);
    }

    public function test_only_installed_active_plugins_are_checked_even_if_inactive_packages_are_configured(): void
    {
        $this->plugin('active', 'enabled');
        $this->plugin('inactive', 'disabled');
        $this->plugin('not-installed', 'enabled', null);
        config(['cms-core.updates.packages'=>['tests/inactive']]);
        $checker = Mockery::mock(GitTagUpdateChecker::class);
        $checker->shouldReceive('latestVersion')->once()->with('tests/active')->andReturn('1.1.0');
        $reader = Mockery::mock(ComposerInstalledPackageReader::class);
        $reader->shouldReceive('read')->with('tests/active')->andReturn(['version'=>'1.0.0']);
        $packages = (new PackageVersionRegistry($checker, new PluginCatalog(), $reader))->checkRemoteUpdates();
        $this->assertSame('tests/active', $packages->sole()->name);
    }

    public function test_page_only_lists_active_plugins_with_a_newer_version(): void
    {
        $this->plugin('active-update', 'enabled');
        $this->plugin('active-current', 'enabled');
        $this->plugin('inactive', 'disabled');
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['key'=>'core.super_admin'], ['name'=>'Super Admin', 'is_protected'=>true]);
        $user->cmsRoles()->sync([$role->id]);
        $this->mock(PackageVersionRegistry::class)->shouldReceive('checkRemoteUpdates')->andReturn(collect([
            new InstalledPackage('tests/active-update', '1.0.0', '1.1.0', 'stable', null),
            new InstalledPackage('tests/active-current', '1.0.0', '1.0.0', 'stable', null),
            new InstalledPackage('tests/inactive', '1.0.0', '9.9.9', 'stable', null),
        ]));
        $this->actingAs($user)->get(route('admin.updates.index'))->assertOk()->assertSee('tests/active-update')->assertDontSee('tests/active-current')->assertDontSee('tests/inactive');
    }

    public function test_inactive_plugin_and_protected_core_never_reach_composer(): void
    {
        $this->plugin('inactive', 'disabled');
        config(['cms-core.updates.allow_core_updates'=>false]);
        $reader = Mockery::mock(ComposerInstalledPackageReader::class);
        $reader->shouldNotReceive('read');
        $updater = new PackageUpdater(Mockery::mock(GitTagUpdateChecker::class), null, null, $reader);
        $this->assertFalse($updater->update('tests/inactive')->successful);
        $this->assertFalse($updater->update('pcteckserv/cms-core')->successful);
    }

    public function test_failed_starter_lookup_cannot_reuse_a_release_from_another_repository(): void
    {
        config(['cms-core.updates.packages'=>[], 'cms-core.updates.starter.repository'=>'example/cglimpezas', 'cms-core.updates.starter.version'=>'0.0.5']);
        DB::table('cms_installed_packages')->insert(['name'=>StarterPackage::NAME, 'installed_version'=>'1.34.0', 'available_version'=>'1.35.0', 'channel'=>'stable']);
        $checker = Mockery::mock(GitTagUpdateChecker::class);
        $checker->shouldReceive('latestVersion')->once()->with(StarterPackage::NAME)->andReturn(null);
        $starter = (new PackageVersionRegistry($checker, new PluginCatalog()))->checkRemoteUpdates()->sole();
        $this->assertSame('0.0.5', $starter->installedVersion);
        $this->assertNull($starter->availableVersion);
    }

    public function test_starter_identity_comes_from_the_current_site_and_repository_without_credentials(): void
    {
        config(['app.name'=>'CG Limpezas', 'cms-core.updates.starter.repository'=>'https://test-token@github.com/example/cglimpezas.git']);
        $this->assertSame('CG Limpezas', StarterPackage::displayName());
        $this->assertSame('example/cglimpezas', StarterPackage::repositoryLabel());
        $html = view('cms-core::admin.updates.partials.packages-table', ['packages'=>[new InstalledPackage(StarterPackage::NAME, '0.0.5', '0.0.6', 'stable', null)], 'statuses'=>[]])->render();
        $this->assertStringContainsString('CG Limpezas · Starter', $html);
        $this->assertStringContainsString('example/cglimpezas', $html);
        $this->assertStringNotContainsString('test-token', $html);
    }

    public function test_even_a_direct_same_version_request_cannot_reinstall_a_core_snapshot(): void
    {
        DB::table('cms_installed_packages')->insert(['name'=>'pcteckserv/cms-core', 'installed_version'=>'3.1.2', 'available_version'=>'3.1.2', 'channel'=>'stable']);
        $reader = Mockery::mock(ComposerInstalledPackageReader::class);
        $reader->shouldReceive('read')->once()->andReturn(['version'=>'3.1.2', 'dist'=>['type'=>'path']]);
        $updater = new PackageUpdater(Mockery::mock(GitTagUpdateChecker::class), null, null, $reader);
        $result = $updater->update('pcteckserv/cms-core');
        $this->assertFalse($result->successful);
        $this->assertStringContainsString('snapshot', $result->message);
    }

    public function test_current_active_plugin_is_not_sent_to_composer(): void
    {
        $this->plugin('current', 'enabled');
        DB::table('cms_installed_packages')->insert(['name'=>'tests/current', 'installed_version'=>'1.0.0', 'available_version'=>'1.0.0', 'channel'=>'stable']);
        $reader = Mockery::mock(ComposerInstalledPackageReader::class);
        $reader->shouldReceive('read')->once()->andReturn(['version'=>'1.0.0', 'dist'=>['type'=>'zip']]);
        $result = (new PackageUpdater(Mockery::mock(GitTagUpdateChecker::class), null, null, $reader))->update('tests/current');
        $this->assertFalse($result->successful);
        $this->assertStringContainsString('Não existe uma atualização', $result->message);
    }

    public function test_http_request_for_inactive_plugin_is_rejected_before_the_updater(): void
    {
        $this->plugin('inactive', 'disabled');
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['key'=>'core.super_admin'], ['name'=>'Super Admin', 'is_protected'=>true]);
        $user->cmsRoles()->sync([$role->id]);
        $this->mock(PackageUpdater::class)->shouldNotReceive('update');
        $this->actingAs($user)->post(route('admin.updates.run', ['package'=>'tests/inactive']))->assertRedirect()->assertSessionHas('cms_update_error');
    }
}
