<?php

namespace Pcteckserv\CmsCore\Updates;

use Symfony\Component\Process\Process;

class PackageUpdater
{
    public function update(string $package): UpdateResult
    {
        $composer = $this->run(['composer', 'update', $package, '--with-dependencies']);

        if (! $composer->isSuccessful()) {
            return new UpdateResult(false, 'A atualização falhou durante o Composer. Verifica permissões, autenticação GitHub e logs do servidor.');
        }

        $migrate = $this->run([PHP_BINARY, 'artisan', 'migrate', '--force']);

        if (! $migrate->isSuccessful()) {
            return new UpdateResult(false, 'O package foi atualizado, mas as migrations falharam. Corre php artisan migrate --force no terminal.');
        }

        return new UpdateResult(true, 'Atualização concluída com sucesso.');
    }

    /**
     * @param array<int, string> $command
     */
    private function run(array $command): Process
    {
        $process = new Process($command, base_path());
        $process->setTimeout(300);
        $process->run();

        return $process;
    }
}
