<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Pcteckserv\CmsCore\Updates\ComposerInstalledPackageReader;
use Pcteckserv\CmsCore\Updates\GitTagUpdateChecker;
use Pcteckserv\CmsCore\Updates\PackageUpdater;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class LegacyMajorUpgradeGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_2_never_runs_composer_for_core_3(): void
    {
        $reader=Mockery::mock(ComposerInstalledPackageReader::class);
        $reader->shouldReceive('read')->once()->with('pcteckserv/cms-core')->andReturn(['version'=>'v2.9.0']);
        DB::table('cms_installed_packages')->insert(['name'=>'pcteckserv/cms-core','installed_version'=>'2.9.0','available_version'=>'v3.1.7','channel'=>'stable']);
        $updater=new class(Mockery::mock(GitTagUpdateChecker::class),null,null,$reader) extends PackageUpdater {
            protected function run(array $command): Process { throw new \RuntimeException('Composer não deve ser executado.'); }
        };
        $result=$updater->update('pcteckserv/cms-core');
        $this->assertFalse($result->successful);
        $this->assertStringContainsString('migração',$result->message);
        $this->assertDatabaseHas('cms_installed_packages',['installed_version'=>'2.9.0']);
    }
}
