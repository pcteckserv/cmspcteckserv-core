<?php

namespace Pcteckserv\CmsCore\Plugins;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Pcteckserv\CmsCore\Plugins\DTOs\AvailablePlugin;
use Symfony\Component\Process\Process;

class PluginRepository
{
    /**
     * @return Collection<int, AvailablePlugin>
     */
    public function available(): Collection
    {
        $root = $this->ensureLocalCopy();
        $metadataFile = (string) config('cms-plugins.metadata_file', 'cms-plugin.json');

        return collect(File::directories($root))
            ->map(fn (string $directory): ?AvailablePlugin => $this->fromDirectory($directory, $metadataFile))
            ->filter()
            ->sortBy('label')
            ->values();
    }

    public function find(string $slug): ?AvailablePlugin
    {
        return $this->available()->first(fn (AvailablePlugin $plugin): bool => $plugin->slug === $slug);
    }

    private function fromDirectory(string $directory, string $metadataFile): ?AvailablePlugin
    {
        $path = $directory.DIRECTORY_SEPARATOR.$metadataFile;

        if (! File::isFile($path)) {
            return null;
        }

        $data = json_decode((string) File::get($path), true);

        if (! is_array($data)) {
            return null;
        }

        $package = $data['package'] ?? null;

        if (! is_string($package) || ! preg_match('/^[a-z0-9_.-]+\/[a-z0-9_.-]+$/', $package)) {
            return null;
        }

        $directoryName = basename($directory);
        $slug = isset($data['slug']) && is_string($data['slug']) && $data['slug'] !== ''
            ? $data['slug']
            : Str::slug($directoryName);

        if (! preg_match('/^[a-z0-9][a-z0-9_-]*$/', $slug)) {
            return null;
        }

        $version = $data['version'] ?? null;

        if ($version !== null && (! is_string($version) || ! preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $version))) {
            return null;
        }

        return new AvailablePlugin(
            slug: $slug,
            directory: $directoryName,
            package: $package,
            label: isset($data['label']) && is_string($data['label']) ? $data['label'] : Str::headline(Str::after($package, '/')),
            description: isset($data['description']) && is_string($data['description']) ? $data['description'] : null,
            provider: isset($data['provider']) && is_string($data['provider']) ? $data['provider'] : null,
            versionConstraint: isset($data['version_constraint']) && is_string($data['version_constraint']) ? $data['version_constraint'] : null,
            repositoryPath: $directory,
            version: $version,
        );
    }

    private function ensureLocalCopy(): string
    {
        $repositoryUrl = (string) config('cms-plugins.repository_url');
        $target = storage_path('framework/cache/cms-plugin-repository');

        if ($repositoryUrl === '') {
            return $target;
        }

        if (! File::isDirectory($target.DIRECTORY_SEPARATOR.'.git')) {
            File::ensureDirectoryExists(dirname($target));
            $this->run(array_values(array_filter([
                'git',
                'clone',
                '--depth',
                '1',
                $this->branchOption(),
                $repositoryUrl,
                $target,
            ])));

            return $target;
        }

        $this->run(['git', '-C', $target, 'pull', '--ff-only']);

        return $target;
    }

    private function branchOption(): ?string
    {
        $branch = config('cms-plugins.repository_branch');

        return is_string($branch) && $branch !== '' ? '--branch='.$branch : null;
    }

    /**
     * @param array<int, string> $command
     */
    private function run(array $command): void
    {
        $process = new Process($command, base_path());
        $process->setTimeout(300);
        $environment = [
            'GIT_TERMINAL_PROMPT' => '0',
            'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
            'WINDIR' => getenv('WINDIR') ?: getenv('SystemRoot') ?: 'C:\\Windows',
        ];

        $token = config('cms-core.updates.github_token');

        if (is_string($token) && $token !== '') {
            $environment += [
                'GIT_CONFIG_COUNT' => '2',
                'GIT_CONFIG_KEY_0' => 'credential.helper',
                'GIT_CONFIG_VALUE_0' => '',
                'GIT_CONFIG_KEY_1' => 'http.https://github.com/.extraheader',
                'GIT_CONFIG_VALUE_1' => 'AUTHORIZATION: basic '.base64_encode('x-access-token:'.$token),
            ];
        }

        $process->setEnv($environment);
        $process->mustRun();
    }
}
