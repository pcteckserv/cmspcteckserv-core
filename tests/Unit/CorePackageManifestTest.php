<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CorePackageManifestTest extends TestCase
{
    public function test_core_version_is_inferred_from_git_tags(): void
    {
        $manifest = json_decode(
            file_get_contents(dirname(__DIR__, 2).'/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertArrayNotHasKey(
            'version',
            $manifest,
            'A versão do Core deve ser obtida pela tag Git para evitar releases rejeitadas pelo Composer.',
        );
    }
}
