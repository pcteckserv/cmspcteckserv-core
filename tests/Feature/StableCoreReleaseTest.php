<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Pcteckserv\CmsCore\Plugins\PluginCatalog;
use Pcteckserv\CmsCore\Updates\ComposerInstalledPackageReader;
use Pcteckserv\CmsCore\Updates\CoreReleasePolicy;
use Pcteckserv\CmsCore\Updates\GitTagUpdateChecker;
use Pcteckserv\CmsCore\Updates\InstalledPackage;
use Pcteckserv\CmsCore\Updates\PackageVersionRegistry;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class StableCoreReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_2_keeps_maintenance_line_and_core_3_keeps_major(): void
    {
        $this->assertTrue(CoreReleasePolicy::allows('v2.9.0','v2.9.2'));
        $this->assertFalse(CoreReleasePolicy::allows('v2.9.0','v3.0.1'));
        $this->assertFalse(CoreReleasePolicy::allows('v2.9.0','v2.20.0'));
        $this->assertFalse(CoreReleasePolicy::allows('v2.9.0','v2.9.3-beta.1'));
        $this->assertTrue(CoreReleasePolicy::allows('3.0.1','3.1.9'));
        $this->assertFalse(CoreReleasePolicy::allows('3.1.9','4.0.0'));
        $this->assertFalse(CoreReleasePolicy::allows(null,'3.1.9'));
    }

    public function test_real_git_tags_select_the_latest_stable_compatible_line(): void
    {
        $root=storage_path('framework/testing/core-tags-'.bin2hex(random_bytes(8)));File::ensureDirectoryExists($root);
        try {
            (new Process(['git','init','--quiet',$root]))->mustRun();
            (new Process(['git','-c','user.name=Core tests','-c','user.email=core@example.test','commit','--quiet','--allow-empty','-m','fixture'],$root))->mustRun();
            foreach(['v2.9.0','v2.9.2','v2.20.0','v3.0.1','v3.1.9','v3.2.0-beta.1','v4.0.0','audit-localhost-2026-11-15'] as $tag)(new Process(['git','tag',$tag],$root))->mustRun();
            config(['cms-core.updates.repositories.pcteckserv/cms-core'=>$root,'cms-core.updates.github_token'=>null]);
            foreach(['2.9.0'=>'v2.9.2','3.0.1'=>'v3.1.9'] as $installed=>$expected){
                $reader=new class($installed) extends ComposerInstalledPackageReader {
                    public function __construct(private string $version){}
                    public function read(string $package): array{return ['version'=>$this->version];}
                };
                $this->assertSame($expected,(new GitTagUpdateChecker($reader))->latestVersion('pcteckserv/cms-core'));
            }
        } finally { File::deleteDirectory($root); }
    }

    public function test_known_old_repository_is_mapped_without_replacing_custom_repositories(): void
    {
        $old='https://github.com/diogoleitean/pcteckcms_core.git';$new='https://github.com/pcteckserv/cmspcteckserv-core.git';
        $this->assertSame($new,CoreReleasePolicy::repository($old));
        $custom='https://example.test/custom/core.git';$this->assertSame($custom,CoreReleasePolicy::repository($custom));
        $manifest=['repositories'=>[['type'=>'vcs','url'=>$old],['type'=>'vcs','url'=>$custom],['type'=>'path','url'=>'packages/test']]];
        $result=CoreReleasePolicy::portableRepositories($manifest);
        $this->assertSame($new,$result['repositories'][0]['url']);$this->assertSame($custom,$result['repositories'][1]['url']);
        $this->assertSame($manifest['repositories'][2],$result['repositories'][2]);
    }

    public function test_incompatible_cached_core_candidate_is_cleared_and_cannot_show_update(): void
    {
        config(['cms-core.updates.packages'=>['pcteckserv/cms-core'],'cms-core.updates.bundled_versions'=>[],'cms-core.updates.starter.repository'=>null]);
        DB::table('cms_installed_packages')->insert(['name'=>'pcteckserv/cms-core','installed_version'=>'v2.9.0','available_version'=>'v3.0.1','channel'=>'stable']);
        $reader=new class extends ComposerInstalledPackageReader {public function read(string $package):array{return ['version'=>'v2.9.0'];}};
        $checker=new class extends GitTagUpdateChecker {public function latestVersion(string $package):?string{return null;}};
        $registry=new PackageVersionRegistry($checker,new PluginCatalog(),$reader);
        $result=$registry->checkRemoteUpdates()->firstWhere('name','pcteckserv/cms-core');
        $this->assertNull($result->availableVersion);$this->assertFalse($result->hasUpdate());
        $this->assertDatabaseHas('cms_installed_packages',['name'=>'pcteckserv/cms-core','available_version'=>null]);
        $this->assertFalse((new InstalledPackage('pcteckserv/cms-core','2.9.0','3.0.1','stable',null))->hasUpdate());
        $this->assertTrue((new InstalledPackage('pcteckserv/cms-core','2.9.0','2.9.2','stable',null))->hasUpdate());
    }

    public function test_starter_bundled_core_version_cannot_hide_a_known_update(): void
    {
        config(['cms-core.updates.packages'=>['pcteckserv/cms-core'],'cms-core.updates.bundled_versions.pcteckserv/cms-core'=>'3.0.1','cms-core.updates.starter.repository'=>null]);
        DB::table('cms_installed_packages')->insert(['name'=>'pcteckserv/cms-core','installed_version'=>'3.1.8','available_version'=>'3.1.9','channel'=>'stable']);
        $reader=new class extends ComposerInstalledPackageReader {public function read(string $package):array{return ['version'=>'3.1.8'];}};
        $registry=new PackageVersionRegistry(new GitTagUpdateChecker(),new PluginCatalog(),$reader);$registry->sync();
        $this->assertSame('3.1.9',$registry->all()->firstWhere('name','pcteckserv/cms-core')->availableVersion);
    }

    public function test_composer_error_keeps_the_tail_without_terminal_control_characters(): void
    {
        config(['cms-core.updates.github_token'=>'fake-test-token']);
        $transaction=new class extends \Pcteckserv\CmsCore\Updates\ComposerDeploymentTransaction {
            public function fail():void{$this->process([PHP_BINARY,'-r','fwrite(STDERR,str_repeat("prefix ",600)."\x08\x1B[31mFINAL fake-test-token\x1B[0m");exit(1);'],base_path());}
        };
        try{$transaction->fail();$this->fail('O processo devia falhar.');}
        catch(\RuntimeException $exception){
            $this->assertStringContainsString('FINAL [credencial ocultada]',$exception->getMessage());
            $this->assertStringNotContainsString("\x08",$exception->getMessage());$this->assertStringNotContainsString("\x1B",$exception->getMessage());
        }
    }
}
