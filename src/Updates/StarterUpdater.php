<?php

namespace Pcteckserv\CmsCore\Updates;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class StarterUpdater
{
    /**
     * @var array<int, string>
     */
    private array $excludedPaths = [
        '.env',
        '.git',
        '.github',
        '.cms_installed',
        'bootstrap/cache',
        'node_modules',
        'public/hot',
        'public/installer.php',
        'public/storage',
        'storage',
        'vendor',
    ];

    public function update(?string $availableVersion = null): UpdateResult
    {
        $repository = StarterPackage::repository();

        if ($repository === null) {
            return new UpdateResult(false, 'Configure STARTER_GITHUB_REPOSITORY antes de atualizar o Starter.');
        }

        $version = $availableVersion ?: app(GitTagUpdateChecker::class)->latestVersion(StarterPackage::NAME);

        if (! is_string($version) || $version === '') {
            return new UpdateResult(false, 'Não foi encontrada uma versão válida do Starter no repositório configurado.');
        }

        $temporaryPath = storage_path('framework/cache/cms-starter-'.bin2hex(random_bytes(6)));

        try {
            File::ensureDirectoryExists($temporaryPath);
            $this->cloneRepository($repository, $version, $temporaryPath);
            $this->copyStarterFiles($temporaryPath, base_path());
            $this->writeInstalledVersion($version);
            $this->runArtisan(['migrate', '--force']);
            $this->runArtisan(['optimize:clear']);
        } catch (ProcessFailedException $exception) {
            $errorOutput = $exception->getProcess()->getErrorOutput();

            if (str_contains($errorOutput, 'could not read Username') || str_contains($errorOutput, 'Authentication failed') || str_contains($errorOutput, 'Duplicate header')) {
                return new UpdateResult(false, 'Falha de autenticação ao descarregar o Starter do GitHub. Verifique se STARTER_GITHUB_TOKEN ou CMS_GITHUB_TOKEN está configurado e com permissões de leitura no repositório.');
            }

            return new UpdateResult(false, 'Falha ao atualizar o Starter: '.$exception->getMessage());
        } finally {
            File::deleteDirectory($temporaryPath);
        }

        return new UpdateResult(true, 'Starter atualizado com sucesso para '.$version.'.');
    }

    private function cloneRepository(string $repository, string $version, string $destination): void
    {
        $process = new Process(
            $this->cloneCommand($repository, $version, $destination),
            base_path(),
            $this->environment()
        );
        $process->setTimeout(300);
        $process->mustRun();
    }

    /**
     * @return array<int, string>
     */
    public function cloneCommand(string $repository, string $version, string $destination): array
    {
        $command = ['git'];
        $token = StarterPackage::token();

        if (is_string($token) && $token !== '' && str_starts_with($repository, 'https://github.com/')) {
            $authorization = base64_encode('x-access-token:'.$token);
            $command[] = '-c';
            $command[] = 'http.https://github.com/.extraheader=AUTHORIZATION: basic '.$authorization;
        }

        return array_merge($command, [
            'clone',
            '--depth',
            '1',
            '--branch',
            $version,
            $repository,
            $destination,
        ]);
    }

    private function copyStarterFiles(string $source, string $destination): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($items as $item) {
            $relativePath = str_replace('\\', '/', $items->getSubPathName());

            if ($this->isExcluded($relativePath)) {
                continue;
            }

            $target = $destination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            if ($item->isDir()) {
                File::ensureDirectoryExists($target);

                continue;
            }

            File::ensureDirectoryExists(dirname($target));
            File::copy($item->getPathname(), $target);
        }
    }

    private function isExcluded(string $relativePath): bool
    {
        foreach ($this->excludedPaths as $excludedPath) {
            if ($relativePath === $excludedPath || str_starts_with($relativePath, $excludedPath.'/')) {
                return true;
            }
        }

        return false;
    }

    private function writeInstalledVersion(string $version): void
    {
        File::ensureDirectoryExists(dirname(StarterPackage::versionFile()));
        File::put(StarterPackage::versionFile(), $version.PHP_EOL);
    }

    /**
     * @param array<int, string> $arguments
     */
    private function runArtisan(array $arguments): void
    {
        $process = new Process(array_merge([PHP_BINARY, 'artisan'], $arguments), base_path(), $this->environment());
        $process->setTimeout(300);
        $process->mustRun();
    }

    /**
     * @return array<string, string>
     */
    public function environment(): array
    {
        $currentEnvironment = getenv();
        $environment = is_array($currentEnvironment) ? $currentEnvironment : [];
        $path = $environment['PATH'] ?? $environment['Path'] ?? '';

        unset(
            $environment['GIT_CONFIG_COUNT'],
            $environment['GIT_CONFIG_KEY_0'],
            $environment['GIT_CONFIG_VALUE_0']
        );

        return $environment + [
            'PATH' => $path,
            'Path' => $path,
            'GIT_TERMINAL_PROMPT' => '0',
            'GCM_INTERACTIVE' => 'Never',
        ];
    }
}
