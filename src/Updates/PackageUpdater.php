<?php

namespace Pcteckserv\CmsCore\Updates;

use Symfony\Component\Process\Process;

class PackageUpdater
{
    public function update(string $package): UpdateResult
    {
        $composer = $this->run([$this->composerExecutable(), 'update', $package, '--with-dependencies']);

        if (! $composer->isSuccessful()) {
            return new UpdateResult(false, 'Composer falhou: '.$this->processOutput($composer));
        }

        $migrate = $this->run([PHP_BINARY, 'artisan', 'migrate', '--force']);

        if (! $migrate->isSuccessful()) {
            return new UpdateResult(false, 'Migrations falharam: '.$this->processOutput($migrate));
        }

        return new UpdateResult(true, 'Atualizacao concluida com sucesso.');
    }

    /**
     * @param array<int, string> $command
     */
    private function run(array $command): Process
    {
        $process = new Process($command, base_path());
        $process->setTimeout(300);
        $process->setEnv($this->environment());
        $process->run();

        return $process;
    }

    private function composerExecutable(): string
    {
        return PHP_OS_FAMILY === 'Windows' ? 'composer.bat' : 'composer';
    }

    private function processOutput(Process $process): string
    {
        $output = trim($process->getErrorOutput()) ?: trim($process->getOutput());

        if ($output === '') {
            return 'sem detalhe devolvido pelo processo.';
        }

        return mb_strimwidth($output, 0, 800, '...');
    }

    /**
     * @return array<string, string>
     */
    private function environment(): array
    {
        $environment = [
            'PATH' => $this->pathWithPhp(),
        ];

        $token = config('cms-core.updates.github_token');

        if (! is_string($token) || $token === '') {
            return $environment;
        }

        return $environment + [
            'COMPOSER_AUTH' => json_encode([
                'github-oauth' => [
                    'github.com' => $token,
                ],
            ], JSON_THROW_ON_ERROR),
        ];
    }

    private function pathWithPhp(): string
    {
        $path = getenv('PATH') ?: getenv('Path') ?: '';
        $phpDirectory = dirname(PHP_BINARY);

        if (str_contains($path, $phpDirectory)) {
            return $path;
        }

        return $phpDirectory.PATH_SEPARATOR.$path;
    }
}
