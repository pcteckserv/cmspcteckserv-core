<?php

namespace Pcteckserv\CmsCore\Plugins;

use Illuminate\Support\Str;
use Pcteckserv\CmsCore\Models\InstalledPlugin;
use Symfony\Component\Process\Process;

class PluginInstaller
{
    /**
     * @param array{
     *     package: string,
     *     version_constraint?: string|null,
     *     slug?: string|null,
     *     label?: string|null,
     *     description?: string|null,
     *     provider?: string|null,
     *     repository_type?: string|null,
     *     repository_url?: string|null
     * } $data
     */
    public function install(array $data): PluginInstallResult
    {
        $package = $data['package'];
        $versionConstraint = $this->versionConstraint($data['version_constraint'] ?? null, $data['repository_type'] ?? null);
        $slug = $this->slug($data['slug'] ?? null, $package);

        if (! empty($data['repository_type']) && ! empty($data['repository_url'])) {
            $repository = $this->configureRepository($slug, $data['repository_type'], $data['repository_url']);

            if (! $repository->isSuccessful()) {
                return new PluginInstallResult(false, 'Não foi possível configurar o repositório Composer: '.$this->processOutput($repository));
            }
        }

        $composer = $this->run([$this->composerExecutable(), 'require', $package.':'.$versionConstraint, '--with-dependencies']);

        if (! $composer->isSuccessful()) {
            return new PluginInstallResult(false, 'Composer falhou: '.$this->processOutput($composer));
        }

        $migrate = $this->run([PHP_BINARY, 'artisan', 'migrate', '--force']);

        if (! $migrate->isSuccessful()) {
            return new PluginInstallResult(false, 'O plugin foi instalado, mas as migrations falharam: '.$this->processOutput($migrate));
        }

        $permissions = $this->run([PHP_BINARY, 'artisan', 'cms:permissions:sync']);

        if (! $permissions->isSuccessful()) {
            return new PluginInstallResult(false, 'O plugin foi instalado, mas a sincronização de permissões falhou: '.$this->processOutput($permissions));
        }

        $cache = $this->run([PHP_BINARY, 'artisan', 'optimize:clear']);

        if (! $cache->isSuccessful()) {
            return new PluginInstallResult(false, 'O plugin foi instalado, mas a limpeza de cache falhou: '.$this->processOutput($cache));
        }

        InstalledPlugin::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $package,
                'package' => $package,
                'label' => $data['label'] ?: Str::headline(Str::after($package, '/')),
                'description' => $data['description'] ?? null,
                'provider' => $data['provider'] ?? null,
                'installed_version' => $this->installedVersion($package),
                'installed_at' => now(),
                'last_error' => null,
                'metadata' => [
                    'version_constraint' => $versionConstraint,
                    'repository_type' => $data['repository_type'] ?? null,
                    'repository_url' => $data['repository_url'] ?? null,
                ],
            ],
        );

        return new PluginInstallResult(true, 'Plugin instalado com sucesso.');
    }

    private function configureRepository(string $slug, string $type, string $url): Process
    {
        $name = 'cms-plugin-'.$slug;

        $typeProcess = $this->run([$this->composerExecutable(), 'config', 'repositories.'.$name.'.type', $type]);

        if (! $typeProcess->isSuccessful()) {
            return $typeProcess;
        }

        return $this->run([$this->composerExecutable(), 'config', 'repositories.'.$name.'.url', $url]);
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

    private function installedVersion(string $package): ?string
    {
        $process = $this->run([$this->composerExecutable(), 'show', $package, '--format=json']);

        if (! $process->isSuccessful()) {
            return null;
        }

        $packageData = json_decode($process->getOutput(), true);

        return is_array($packageData) && isset($packageData['versions'][0])
            ? (string) $packageData['versions'][0]
            : null;
    }

    private function slug(?string $slug, string $package): string
    {
        if (is_string($slug) && $slug !== '') {
            return $slug;
        }

        return Str::slug(Str::after($package, '/'));
    }

    private function versionConstraint(?string $constraint, ?string $repositoryType): string
    {
        if (is_string($constraint) && trim($constraint) !== '') {
            return trim($constraint);
        }

        return $repositoryType === 'path' ? '*@dev' : '*';
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

        $gitConfig = "[safe]\n\tdirectory = ".str_replace('\\', '/', base_path())."\n";
        $gitConfig .= "[url \"https://github.com/\"]\n";
        $gitConfig .= "\tinsteadOf = git@github.com:\n";

        $token = config('cms-core.updates.github_token');

        if (is_string($token) && $token !== '') {
            $authorization = base64_encode('x-access-token:'.$token);

            $gitConfig .= "[http \"https://github.com/\"]\n";
            $gitConfig .= "\textraheader = AUTHORIZATION: basic {$authorization}\n";
        }

        if (! is_file($this->gitConfigPath()) || file_get_contents($this->gitConfigPath()) !== $gitConfig) {
            file_put_contents($this->gitConfigPath(), $gitConfig);
        }
    }

    private function gitConfigPath(): string
    {
        return storage_path('framework/cache/composer-gitconfig');
    }
}
