<?php

namespace Tests\Feature;

use Pcteckserv\CmsCore\Support\ComposerCommand;
use Pcteckserv\CmsCore\Support\ComposerRepositoryCleaner;
use RuntimeException;
use Tests\TestCase;

class ComposerCommandTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2).'/src/Support/ComposerRepositoryCleaner.php';
        require_once dirname(__DIR__, 2).'/src/Support/ComposerCommand.php';

        parent::setUp();
    }

    public function test_usa_composer_phar_configurado_com_o_php_atual(): void
    {
        $composerPhar = sys_get_temp_dir().'/cms-composer-'.bin2hex(random_bytes(8)).'.phar';
        file_put_contents($composerPhar, 'test');

        try {
            config(['cms-core.updates.composer_binary' => $composerPhar]);

            $this->assertSame(
                [PHP_BINARY, $composerPhar, 'show', 'pcteckserv/cms-core'],
                (new ComposerCommand())->build(['show', 'pcteckserv/cms-core']),
            );
        } finally {
            @unlink($composerPhar);
        }
    }

    public function test_rejeita_executavel_composer_configurado_inexistente(): void
    {
        config(['cms-core.updates.composer_binary' => base_path('composer-inexistente.phar')]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('não existe ou não pode ser lido');

        (new ComposerCommand())->build(['show']);
    }

    public function test_recupera_repositorios_nas_atualizacoes_do_core_e_dos_plugins(): void
    {
        $composerPhar = sys_get_temp_dir().'/cms-composer-'.bin2hex(random_bytes(8)).'.phar';
        file_put_contents($composerPhar, 'test');

        try {
            config(['cms-core.updates.composer_binary' => $composerPhar]);

            foreach ([
                ['update', 'pcteckserv/cms-core'],
                ['update', 'pcteckserv/cms-portfolio'],
                ['show', 'pcteckserv/cms-portfolio'],
                ['config'],
            ] as $arguments) {
                $cleaner = \Mockery::mock(ComposerRepositoryCleaner::class);
                $cleaner->shouldReceive('removeInvalidPathRepositories')->once()
                    ->with($arguments[0] === 'update');

                $this->assertSame(
                    [PHP_BINARY, $composerPhar, ...$arguments],
                    (new ComposerCommand($cleaner))->build($arguments),
                );
            }
        } finally {
            @unlink($composerPhar);
        }
    }

    public function test_usa_php_cli_para_composer_e_comandos_artisan(): void
    {
        config([
            'cms-core.updates.composer_binary' => null,
            'cms-core.updates.php_cli_binary' => PHP_BINARY,
        ]);

        $command = new ComposerCommand();

        $this->assertSame(PHP_BINARY, $command->phpBinary());
        $this->assertSame([PHP_BINARY, 'artisan', 'migrate', '--force'], $command->php(['artisan', 'migrate', '--force']));
    }

    public function test_rejeita_php_cli_configurado_incompativel(): void
    {
        config(['cms-core.updates.php_cli_binary' => base_path('php-inexistente')]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PHP CLI configurado não existe ou não é compatível');

        (new ComposerCommand())->phpBinary();
    }
}
