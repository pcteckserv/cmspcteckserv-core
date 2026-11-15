<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;

abstract class TestCase extends LaravelTestCase
{
    protected function assertJsonValueSame(mixed $expected, mixed $actual): void
    {
        // MySQL pode reordenar chaves de objetos JSON; tipos e ordem das listas mantêm-se.
        $canonical = static function (mixed $value) use (&$canonical): mixed {
            if (!is_array($value)) return $value;
            if (!array_is_list($value)) ksort($value);
            foreach ($value as &$item) $item = $canonical($item);
            return $value;
        };
        $this->assertSame($canonical($expected), $canonical($actual));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $app['config']->set([
            'cms-plugins.plugins' => [],
            'cms-plugins.repository_url' => '',
            'cms-plugins.local_repository' => null,
            'cms-core.updates.bundled_versions' => [],
            'cms-core.updates.starter.version' => null,
            'cms-core.updates.packages' => ['pcteckserv/cms-core'],
            'cms-core.updates.allow_core_updates' => true,
            'cms-core.updates.repositories' => [],
            'cms-core.updates.github_token' => null,
            'cms-core.updates.starter.repository' => null,
        ]);

        return $app;
    }
}
