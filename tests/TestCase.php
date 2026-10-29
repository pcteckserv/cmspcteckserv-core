<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;

abstract class TestCase extends LaravelTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $app['config']->set([
            'cms-plugins.plugins' => [],
            'cms-plugins.repository_url' => '',
            'cms-core.updates.packages' => ['pcteckserv/cms-core'],
            'cms-core.updates.repositories' => [],
            'cms-core.updates.github_token' => null,
            'cms-core.updates.starter.repository' => null,
        ]);

        return $app;
    }
}
