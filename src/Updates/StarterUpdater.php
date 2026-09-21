<?php

namespace Pcteckserv\CmsCore\Updates;

use Illuminate\Support\Facades\File;
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
        } finally {
            File::deleteDirectory($temporaryPath);
        }

        return new UpdateResult(true, 'Starter atualizado com sucesso para '.$version.'.');
    }

    private function cloneRepository(string $repository, string $version, string $destination): void
    {
        $process = new Process([
            'git',
            'clone',
            '--depth',
            '1',
            '--branch',
            $version,
            $repository,
            $destination,
        ], base_path(), $this->environment());
        $process->setTimeout(300);
        $process->mustRun();
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
    private function environment(): array
    {
        $currentEnvironment = getenv();
        $environment = is_array($currentEnvironment) ? $currentEnvironment : [];
        $path = $environment['PATH'] ?? $environment['Path'] ?? '';

        $environment = $environment + [
            'PATH' => $path,
            'Path' => $path,
            'GIT_TERMINAL_PROMPT' => '0',
        ];

        $token = StarterPackage::token();

        if ($token === null) {
            return $environment;
        }

        return $environment + [
            'GIT_CONFIG_COUNT' => '1',
            'GIT_CONFIG_KEY_0' => 'http.https://github.com/.extraheader',
            'GIT_CONFIG_VALUE_0' => 'AUTHORIZATION: bearer '.$token,
        ];
    }
}
