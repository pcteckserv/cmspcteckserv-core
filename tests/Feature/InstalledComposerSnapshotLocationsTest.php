<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Pcteckserv\CmsCore\Updates\InstalledComposerSnapshot;
use Tests\TestCase;
use ZipArchive;

class InstalledComposerSnapshotLocationsTest extends TestCase
{
    private function fixture(callable $check): void
    {
        $original=base_path();$root=$original.'/tests/.runtime/installed-location-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($root.'/vendor/composer');File::ensureDirectoryExists($root.'/stage');
        $this->app->setBasePath($root);
        $package=['name'=>'tests/location-plugin','version'=>'1.0.0','dist'=>['type'=>'path','url'=>'../missing-development-plugin'],'transport-options'=>['relative'=>true]];
        try{$check($root,$package,new InstalledComposerSnapshot());}
        finally{$this->app->setBasePath($original);File::deleteDirectory($root);}
    }

    private function packageFiles(string $directory,string $identity='tests/location-plugin'): void
    {
        File::ensureDirectoryExists($directory.'/src');
        File::put($directory.'/composer.json',json_encode(['name'=>$identity]));
        File::put($directory.'/src/marker.php','<?php // installed code, not development source');
    }

    public function test_installed_composer_path_inside_packages_is_archived(): void
    {
        $this->fixture(function($root,$package,$snapshot){
            $this->packageFiles($root.'/packages/tests/location-plugin');
            $package['install-path']='../../packages/tests/location-plugin';
            $result=$snapshot->baseline([],['packages'=>[$package]],$root.'/stage');
            $zip=new ZipArchive();$this->assertTrue($zip->open($root.'/stage/'.$result['packages'][0]['dist']['url']));
            $this->assertSame('<?php // installed code, not development source',$zip->getFromName('package/src/marker.php'));$zip->close();
            $this->assertArrayNotHasKey('transport-options',$result['packages'][0]);
        });
    }

    public function test_standard_packaged_distribution_is_found_with_old_vendor_metadata(): void
    {
        $this->fixture(function($root,$package,$snapshot){
            $this->packageFiles($root.'/packages/tests/location-plugin');
            $package['install-path']='../tests/location-plugin';
            $result=$snapshot->baseline([],['packages'=>[$package]],$root.'/stage');
            $this->assertFileExists($root.'/stage/'.$result['packages'][0]['dist']['url']);
        });
    }

    public function test_regular_vendor_installation_still_works(): void
    {
        $this->fixture(function($root,$package,$snapshot){
            $this->packageFiles($root.'/vendor/tests/location-plugin');
            $result=$snapshot->baseline([],['packages'=>[$package]],$root.'/stage');
            $this->assertFileExists($root.'/stage/'.$result['packages'][0]['dist']['url']);
        });
    }

    public function test_different_package_identity_is_refused(): void
    {
        $this->fixture(function($root,$package,$snapshot){
            $this->packageFiles($root.'/packages/tests/location-plugin','tests/another-plugin');
            $this->expectException(\RuntimeException::class);$this->expectExceptionMessage('identidade');
            $snapshot->baseline([],['packages'=>[$package]],$root.'/stage');
        });
    }

    public function test_ambiguous_copies_are_not_selected_without_composer_location(): void
    {
        $this->fixture(function($root,$package,$snapshot){
            $this->packageFiles($root.'/vendor/tests/location-plugin');$this->packageFiles($root.'/packages/tests/location-plugin');
            $this->expectException(\RuntimeException::class);$this->expectExceptionMessage('mais de uma');
            $snapshot->baseline([],['packages'=>[$package]],$root.'/stage');
        });
    }

    public function test_registered_location_outside_application_is_refused(): void
    {
        $this->fixture(function($root,$package,$snapshot){
            $package['install-path']=dirname($root);
            $this->expectException(\RuntimeException::class);$this->expectExceptionMessage('fora');
            $snapshot->baseline([],['packages'=>[$package]],$root.'/stage');
        });
    }

    public function test_empty_old_vendor_directory_does_not_hide_packaged_files(): void
    {
        $this->fixture(function($root,$package,$snapshot){
            File::ensureDirectoryExists($root.'/vendor/tests/location-plugin');
            $this->packageFiles($root.'/packages/tests/location-plugin');
            $package['install-path']='../tests/location-plugin';
            $result=$snapshot->baseline([],['packages'=>[$package]],$root.'/stage');
            $this->assertFileExists($root.'/stage/'.$result['packages'][0]['dist']['url']);
        });
    }

    public function test_explicit_composer_location_disambiguates_two_copies(): void
    {
        $this->fixture(function($root,$package,$snapshot){
            $this->packageFiles($root.'/vendor/tests/location-plugin');$this->packageFiles($root.'/packages/tests/location-plugin');
            File::put($root.'/packages/tests/location-plugin/src/marker.php','packaged source');
            $package['install-path']='../../packages/tests/location-plugin';
            $result=$snapshot->baseline([],['packages'=>[$package]],$root.'/stage');
            $zip=new ZipArchive();$zip->open($root.'/stage/'.$result['packages'][0]['dist']['url']);
            $this->assertSame('packaged source',$zip->getFromName('package/src/marker.php'));$zip->close();
        });
    }
}
