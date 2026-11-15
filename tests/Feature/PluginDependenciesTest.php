<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Composer\InstalledVersions;
use Pcteckserv\CmsCore\Plugins\PluginManager;
use Tests\TestCase;

final class PluginDependenciesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dependencies_must_be_enabled_and_cannot_be_disabled_while_in_use(): void
    {
        config(['cms-plugins.plugins'=>[
            'dependency'=>['package'=>'tests/dependency','label'=>'Dependência'],
            'dependent'=>['package'=>'tests/dependent','label'=>'Dependente','requires'=>['dependency']],
        ]]);
        $original = InstalledVersions::getRawData();
        $fixture = $original;
        foreach (['tests/dependency', 'tests/dependent'] as $name) $fixture['versions'][$name] = ['pretty_version'=>'1.0.0', 'version'=>'1.0.0.0', 'aliases'=>[], 'reference'=>'test-fixture', 'install_path'=>null, 'dev_requirement'=>false];
        InstalledVersions::reload($fixture);
        try {
        $manager=app(PluginManager::class);
        try {
            $manager->enable('dependent');
            $this->fail('Foi permitido ativar sem a dependência.');
        } catch (ValidationException $error) {
            $this->assertStringContainsString('Ative primeiro',$error->getMessage());
        }
        $manager->enable('dependency');
        $manager->enable('dependent');
        try {
            $manager->disable('dependency');
            $this->fail('Foi permitido desligar uma dependência em utilização.');
        } catch (ValidationException $error) {
            $this->assertStringContainsString('Desative primeiro',$error->getMessage());
        }
        $this->assertTrue($manager->isEnabled('dependency'));
        $manager->disable('dependent');
        $manager->disable('dependency');
        $this->assertFalse($manager->isEnabled('dependency'));
        } finally { InstalledVersions::reload($original); }
    }
}
