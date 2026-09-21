<?php

namespace Pcteckserv\CmsCore\Support;

use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class ComposerCommand
{
    private ?string $resolvedPhpBinary = null;

    public function __construct(
        private readonly ?ComposerRepositoryCleaner $repositoryCleaner = null,
    ) {
    }

    /**
     * @param array<int, string> $arguments
     * @return array<int, string>
     */
    public function build(array $arguments): array
    {
        $recoverInstalledPackages = ($arguments[0] ?? null) === 'update'
            && ($arguments[1] ?? null) === 'pcteckserv/cms-core';
        ($this->repositoryCleaner ?? new ComposerRepositoryCleaner())
            ->removeInvalidPathRepositories($recoverInstalledPackages);

        $configured = trim((string) config('cms-core.updates.composer_binary', ''));

        if ($configured !== '') {
            return [...$this->commandFor($configured), ...$arguments];
        }

        $localPhar = base_path('composer.phar');

        if (is_file($localPhar) && is_readable($localPhar)) {
            return [$this->phpBinary(), $localPhar, ...$arguments];
        }

        $name = PHP_OS_FAMILY === 'Windows' ? 'composer.bat' : 'composer';
        $executable = (new ExecutableFinder())->find($name);

        if ($executable !== null) {
            return [$executable, ...$arguments];
        }

        throw new RuntimeException(
            'O Composer não está disponível no servidor. Volte a publicar a aplicação com um pacote de deploy que inclua composer.phar.',
        );
    }

    /**
     * @param array<int, string> $arguments
     * @return array<int, string>
     */
    public function php(array $arguments): array
    {
        return [$this->phpBinary(), ...$arguments];
    }

    public function phpBinary(): string
    {
        if ($this->resolvedPhpBinary !== null) {
            return $this->resolvedPhpBinary;
        }

        $configured = trim((string) config('cms-core.updates.php_cli_binary', ''));

        if ($configured !== '') {
            if (! $this->isCliBinary($configured)) {
                throw new RuntimeException('O executável PHP CLI configurado não existe ou não é compatível.');
            }

            return $this->resolvedPhpBinary = $configured;
        }

        if (in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
            return $this->resolvedPhpBinary = PHP_BINARY;
        }

        $name = PHP_OS_FAMILY === 'Windows' ? 'php.exe' : 'php';
        $candidates = array_filter([
            PHP_BINDIR.DIRECTORY_SEPARATOR.$name,
            dirname(PHP_BINARY).DIRECTORY_SEPARATOR.$name,
            (new ExecutableFinder())->find($name),
            PHP_OS_FAMILY !== 'Windows' ? '/usr/local/bin/php' : null,
            PHP_OS_FAMILY !== 'Windows' ? '/usr/bin/php' : null,
        ]);

        foreach (array_unique($candidates) as $candidate) {
            if ($this->isCliBinary($candidate)) {
                return $this->resolvedPhpBinary = $candidate;
            }
        }

        throw new RuntimeException(
            'O PHP CLI não está disponível no servidor. Defina CMS_PHP_CLI_BINARY com o caminho do executável PHP CLI.',
        );
    }

    /** @return array<int, string> */
    private function commandFor(string $configured): array
    {
        $path = str_ends_with(strtolower($configured), '.phar')
            ? $configured
            : (new ExecutableFinder())->find($configured);

        if ($path === null || ! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('O executável Composer configurado não existe ou não pode ser lido.');
        }

        return str_ends_with(strtolower($path), '.phar') ? [$this->phpBinary(), $path] : [$path];
    }

    private function isCliBinary(string $path): bool
    {
        if (! is_file($path) || ! is_readable($path)) {
            return false;
        }

        $process = new Process([$path, '-r', 'echo PHP_SAPI;']);
        $process->setTimeout(10);
        $process->run();

        return $process->isSuccessful()
            && in_array(trim($process->getOutput()), ['cli', 'phpdbg'], true);
    }
}
