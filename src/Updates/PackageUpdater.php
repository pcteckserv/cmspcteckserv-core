<?php

namespace Pcteckserv\CmsCore\Updates;

use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\DB;

class PackageUpdater
{
    public function __construct(
        private readonly GitTagUpdateChecker $updateChecker,
    ) {
    }

    public function update(string $package): UpdateResult
    {
        $installedPackage = $this->installedComposerPackage($package);
        $previousVersion = $installedPackage['version'] ?? null;
        $availableVersion = $this->availableVersion($package);

        $composer = $this->run([$this->composerExecutable(), 'update', $package, '--with-dependencies']);

        if (! $composer->isSuccessful()) {
            return new UpdateResult(false, 'Composer falhou: '.$this->processOutput($composer));
        }

        $updatedPackage = $this->installedComposerPackage($package);
        $updatedVersion = $updatedPackage['version'] ?? null;

        if ($previousVersion !== null && $updatedVersion === $previousVersion) {
            $majorUpgrade = $this->majorUpgradeConstraint($previousVersion, $availableVersion);

            if ($majorUpgrade !== null && ($installedPackage['dist']['type'] ?? null) !== 'path') {
                $composer = $this->run([$this->composerExecutable(), 'require', $package.':'.$majorUpgrade, '--with-dependencies']);

                if (! $composer->isSuccessful()) {
                    return new UpdateResult(false, 'Composer falhou ao atualizar a constraint para '.$majorUpgrade.': '.$this->processOutput($composer));
                }

                $updatedPackage = $this->installedComposerPackage($package);
                $updatedVersion = $updatedPackage['version'] ?? null;
            }
        }

        if ($previousVersion !== null && $updatedVersion === $previousVersion) {
            $repositoryHint = ($installedPackage['dist']['type'] ?? null) === 'path'
                ? ' A package continua instalada a partir do repositório local path '.($installedPackage['dist']['url'] ?? 'sem caminho').'.'
                : '';

            return new UpdateResult(false, 'O Composer terminou sem alterar a versão instalada (continua em '.$previousVersion.'). Verifique se o composer.json permite instalar a versão disponível.'.$repositoryHint);
        }

        $migrate = $this->run([PHP_BINARY, 'artisan', 'migrate', '--force']);

        if (! $migrate->isSuccessful()) {
            return new UpdateResult(false, 'Migrations falharam: '.$this->processOutput($migrate));
        }

        $cache = $this->run([PHP_BINARY, 'artisan', 'optimize:clear']);

        if (! $cache->isSuccessful()) {
            return new UpdateResult(false, 'Limpeza de cache falhou: '.$this->processOutput($cache));
        }

        return new UpdateResult(true, 'Atualização concluída com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function installedComposerPackage(string $package): array
    {
        $process = $this->run([$this->composerExecutable(), 'show', $package, '--format=json']);

        if (! $process->isSuccessful()) {
            return [];
        }

        $packageData = json_decode($process->getOutput(), true);

        return is_array($packageData) ? $packageData : [];
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

    private function availableVersion(string $package): ?string
    {
        $storedVersion = DB::table('cms_installed_packages')
            ->where('name', $package)
            ->value('available_version');

        if (is_string($storedVersion) && $storedVersion !== '') {
            return $storedVersion;
        }

        return $this->updateChecker->latestVersion($package);
    }

    private function majorUpgradeConstraint(?string $installedVersion, ?string $availableVersion): ?string
    {
        if (! is_string($installedVersion) || ! is_string($availableVersion)) {
            return null;
        }

        $installedMajor = $this->majorVersion($installedVersion);
        $availableMajor = $this->majorVersion($availableVersion);

        if ($installedMajor === null || $availableMajor === null || $availableMajor <= $installedMajor) {
            return null;
        }

        return '^'.$availableMajor.'.0';
    }

    private function majorVersion(string $version): ?int
    {
        if (! preg_match('/^v?(\\d+)/', $version, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * @return array<string, string>
     */
    private function environment(): array
    {
        $this->ensureComposerDirectories();

        $environment = [
            'PATH' => $this->pathWithPhp(),
            'Path' => $this->pathWithPhp(),
            'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
            'WINDIR' => getenv('WINDIR') ?: getenv('SystemRoot') ?: 'C:\\Windows',
            'COMSPEC' => getenv('COMSPEC') ?: 'C:\\Windows\\System32\\cmd.exe',
            'PATHEXT' => getenv('PATHEXT') ?: '.COM;.EXE;.BAT;.CMD',
            'COMPOSER_HOME' => storage_path('framework/cache/composer'),
            'APPDATA' => storage_path('framework/cache/composer'),
            'TMP' => storage_path('framework/cache/composer-tmp'),
            'TEMP' => storage_path('framework/cache/composer-tmp'),
            'GIT_CONFIG_GLOBAL' => $this->gitConfigPath(),
            'GIT_TERMINAL_PROMPT' => '0',
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

    private function ensureComposerDirectories(): void
    {
        foreach ([
            storage_path('framework/cache/composer'),
            storage_path('framework/cache/composer-tmp'),
        ] as $directory) {
            if (! is_dir($directory)) {
                mkdir($directory, 0775, true);
            }
        }

        $safeDirectory = str_replace('\\', '/', base_path());
        $gitConfig = "[safe]\n\tdirectory = {$safeDirectory}\n";

        $token = config('cms-core.updates.github_token');

        if (is_string($token) && $token !== '') {
            $authorization = base64_encode('x-access-token:'.$token);

            $gitConfig .= "[http \"https://github.com/\"]\n";
            $gitConfig .= "\textraheader = AUTHORIZATION: basic {$authorization}\n";
        }

        $gitConfigPath = $this->gitConfigPath();
        if (! is_file($gitConfigPath) || file_get_contents($gitConfigPath) !== $gitConfig) {
            file_put_contents($gitConfigPath, $gitConfig);
        }
    }

    private function gitConfigPath(): string
    {
        return storage_path('framework/cache/composer-gitconfig');
    }
}
