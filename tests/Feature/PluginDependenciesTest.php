<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Pcteckserv\CmsCore\Plugins\PluginManager;
use Tests\TestCase;

final class PluginDependenciesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dependencies_must_be_enabled_and_cannot_be_disabled_while_in_use(): void
    {
        config(['cms-plugins.plugins'=>[
            'dependency'=>['package'=>'pcteckserv/cms-customers','label'=>'Dependência'],
            'dependent'=>['package'=>'pcteckserv/cms-agenda','label'=>'Dependente','requires'=>['dependency']],
        ]]);
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
    }
}
