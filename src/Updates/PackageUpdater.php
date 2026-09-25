<?php

namespace Pcteckserv\CmsCore\Updates;

use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Pcteckserv\CmsCore\Models\InstalledPlugin;
use Pcteckserv\CmsCore\Plugins\PluginRepository;
use Pcteckserv\CmsCore\Plugins\InstalledPluginVersionReader;
use Pcteckserv\CmsCore\Support\ComposerCommand;
use Throwable;

class PackageUpdater
{
    private readonly ComposerCommand $composerCommand;

    public function __construct(
        private readonly GitTagUpdateChecker $updateChecker,
        private readonly ?StarterUpdater $starterUpdater = null,
        ?ComposerCommand $composerCommand = null,
        private readonly ?ComposerInstalledPackageReader $installedPackageReader = null,
        private readonly ?InstalledPluginVersionReader $installedPluginVersionReader = null,
    ) {
        $this->composerCommand = $composerCommand ?? new ComposerCommand();
    }

    public function update(string $package): UpdateResult
    {
        if (StarterPackage::is($package)) {
            return ($this->starterUpdater ?? app(StarterUpdater::class))->update($this->availableVersion($package));
        }

        $installedPackage = $this->installedComposerPackage($package);
        $previousVersion = $installedPackage['version'] ?? null;
        if (! is_string($previousVersion) || $previousVersion === '') {
            return new UpdateResult(false, 'Não foi possível confirmar a versão instalada. A atualização não foi iniciada.');
        }

        $availableVersion = $this->availableVersion($package);
        $plugin = InstalledPlugin::query()->where('package', $package)->whereNotNull('installed_version')->first();
        $isPathPlugin = $plugin !== null && ($plugin->metadata['repository_type'] ?? null) === 'path';

        if ($isPathPlugin) {
            try {
                $source = app(PluginRepository::class)->find($plugin->slug);
            } catch (Throwable $exception) {
                Log::warning('Falha ao atualizar a origem do plugin.', [
                    'package' => $package,
                    'exception' => $exception::class,
                ]);

                return new UpdateResult(false, 'Não foi possível atualizar o repositório do plugin. Verifique a ligação e as credenciais de acesso.');
            }

            if ($source === null || $source->package !== $package) {
                return new UpdateResult(false, 'O plugin não foi encontrado no repositório configurado.');
            }

            if ($source->version === null) {
                return new UpdateResult(false, 'O plugin não tem uma versão válida no ficheiro cms-plugin.json.');
            }

            $availableVersion = $source->version;
        }

        if (! $isPathPlugin
            && $previousVersion !== null
            && ($installedPackage['dist']['type'] ?? null) === 'path'
            && is_string($availableVersion)
            && version_compare($this->normalizeVersion($availableVersion), $this->normalizeVersion($previousVersion), '>')) {
            $this->updateComposerPathRepositoryVersion($package, $availableVersion);
        }

        $composer = $this->run($this->composerCommand->build(['update', $package, '--with-dependencies']));

        if (! $composer->isSuccessful()) {
            return new UpdateResult(false, 'Composer falhou: '.$this->processOutput($composer));
        }

        if ($isPathPlugin) {
            $reinstall = $this->run($this->composerCommand->build(['reinstall', $package, '--no-interaction']));

            if (! $reinstall->isSuccessful()) {
                return new UpdateResult(false, 'Não foi possível reinstalar o código atualizado do plugin. Verifique as permissões do Composer.');
            }

            $installedManifestVersion = ($this->installedPluginVersionReader ?? new InstalledPluginVersionReader())
                ->read($package);

            if ($installedManifestVersion !== $availableVersion) {
                return new UpdateResult(
                    false,
                    'A reinstalação terminou, mas os ficheiros instalados não correspondem à versão disponível'
                        .' (instalada: '.($installedManifestVersion ?? 'desconhecida').'; disponível: '.$availableVersion.').'
                );
            }
        }

        $updatedPackage = $this->installedComposerPackage($package);
        $updatedVersion = $updatedPackage['version'] ?? null;
        if (! is_string($updatedVersion) || $updatedVersion === '') {
            return new UpdateResult(false, 'Não foi possível confirmar a versão instalada após executar o Composer. Verifique a instalação antes de tentar novamente.');
        }

        if (! $isPathPlugin && $previousVersion !== null && $updatedVersion === $previousVersion
            && ($installedPackage['dist']['type'] ?? null) !== 'path'
            && is_string($availableVersion)
            && version_compare($this->normalizeVersion($availableVersion), $this->normalizeVersion($previousVersion), '>')) {
            $composer = $this->run($this->composerCommand->build([
                'require',
                $package.':'.$this->normalizeVersion($availableVersion),
                '--with-dependencies',
                '--no-interaction',
            ]));

            if (! $composer->isSuccessful()) {
                return new UpdateResult(false, 'Composer falhou ao atualizar a constraint para '.$availableVersion.': '.$this->processOutput($composer));
            }

            $updatedPackage = $this->installedComposerPackage($package);
            $updatedVersion = $updatedPackage['version'] ?? null;
            if (! is_string($updatedVersion) || $updatedVersion === '') {
                return new UpdateResult(false, 'Não foi possível confirmar a versão instalada após executar o Composer. Verifique a instalação antes de tentar novamente.');
            }
        }

        if (! $isPathPlugin && $previousVersion !== null && $updatedVersion === $previousVersion
            && ($updatedPackage['source']['reference'] ?? $updatedPackage['dist']['reference'] ?? null)
                === ($installedPackage['source']['reference'] ?? $installedPackage['dist']['reference'] ?? null)) {
            $repositoryHint = ($installedPackage['dist']['type'] ?? null) === 'path'
                ? ' A package continua instalada a partir do repositório local path '.($installedPackage['dist']['url'] ?? 'sem caminho').'.'
                : '';

            return new UpdateResult(false, 'O Composer terminou sem alterar a versão instalada (continua em '.$previousVersion.'). Verifique se o composer.json permite instalar a versão disponível.'.$repositoryHint);
        }

        if (! $isPathPlugin && is_string($availableVersion) && is_string($updatedVersion)
            && version_compare($this->normalizeVersion($updatedVersion), $this->normalizeVersion($availableVersion), '<')) {
            return new UpdateResult(false, 'A versão instalada ('.$updatedVersion.') continua abaixo da versão disponível ('.$availableVersion.'). Verifique as constraints do composer.json.');
        }

        $migrate = $this->run($this->composerCommand->php(['artisan', 'migrate', '--force']));

        if (! $migrate->isSuccessful()) {
            return new UpdateResult(false, 'Migrations falharam: '.$this->processOutput($migrate));
        }

        $cache = $this->run($this->composerCommand->php(['artisan', 'optimize:clear']));

        if (! $cache->isSuccessful()) {
            return new UpdateResult(false, 'Limpeza de cache falhou: '.$this->processOutput($cache));
        }

        if ($isPathPlugin) {
            $metadata = $plugin->metadata ?? [];
            $metadata['last_applied_release'] = $availableVersion;
            $metadata['version'] = $availableVersion;
            $plugin->forceFill(['metadata' => $metadata, 'installed_version' => $availableVersion])->save();
        }

        return new UpdateResult(true, 'Atualização concluída com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function installedComposerPackage(string $package): array
    {
        return ($this->installedPackageReader ?? new ComposerInstalledPackageReader($this->composerCommand))
            ->read($package);
    }

    /**
     * @param array<int, string> $command
     */
    protected function run(array $command): Process
    {
        $process = new Process($command, base_path());
        $process->setTimeout(300);
        $process->setEnv($this->environment());
        $process->run();

        return $process;
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

    private function normalizeVersion(string $version): string
    {
        return ltrim($version, 'v');
    }

    protected function updateComposerPathRepositoryVersion(string $package, string $availableVersion): bool
    {
        $composerPath = base_path('composer.json');

        if (! is_file($composerPath) || ! is_readable($composerPath) || ! is_writable($composerPath)) {
            return false;
        }

        $manifest = json_decode((string) file_get_contents($composerPath), true);

        if (! is_array($manifest) || ! isset($manifest['repositories']) || ! is_array($manifest['repositories'])) {
            return false;
        }

        $changed = false;

        foreach ($manifest['repositories'] as &$repository) {
            if (! is_array($repository) || ($repository['type'] ?? null) !== 'path') {
                continue;
            }

            $configuredVersion = $repository['options']['versions'][$package] ?? null;

            if ($configuredVersion !== null && $configuredVersion !== $this->normalizeVersion($availableVersion)) {
                $repository['options']['versions'][$package] = $this->normalizeVersion($availableVersion);
                $changed = true;
            }
        }

        unset($repository);

        if (! $changed) {
            return false;
        }

        $encoded = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (! is_string($encoded)) {
            return false;
        }

        file_put_contents($composerPath, $encoded.PHP_EOL);

        return true;
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
            'GCM_INTERACTIVE' => 'Never',
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

        $composerConfigPath = storage_path('framework/cache/composer/config.json');
        $composerConfig = json_encode([
            'config' => [
                'github-protocols' => ['https'],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (! is_file($composerConfigPath) || file_get_contents($composerConfigPath) !== $composerConfig) {
            file_put_contents($composerConfigPath, $composerConfig);
        }

        $safeDirectory = str_replace('\\', '/', base_path());
        $gitConfig = "[safe]\n\tdirectory = {$safeDirectory}\n";
        $gitConfig .= "[url \"https://github.com/\"]\n";
        $gitConfig .= "\tinsteadOf = git@github.com:\n";

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
