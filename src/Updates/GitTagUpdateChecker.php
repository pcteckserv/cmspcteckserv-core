<?php

namespace Pcteckserv\CmsCore\Updates;

use Illuminate\Support\Facades\Schema;
use Pcteckserv\CmsCore\Models\InstalledPlugin;
use Pcteckserv\CmsCore\Plugins\PluginRepository;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GitTagUpdateChecker
{
    public function latestVersion(string $package): ?string
    {
        if (StarterPackage::is($package)) {
            $repository = StarterPackage::repository();

            return $repository === null ? null : $this->latestVersionFromRepository($repository, StarterPackage::token());
        }

        if (Schema::hasTable((new InstalledPlugin())->getTable())) {
            $plugin = InstalledPlugin::query()->where('package', $package)->whereNotNull('installed_version')->first();

            if (($plugin?->metadata['repository_type'] ?? null) === 'path') {
                try {
                    $source = app(PluginRepository::class)->find($plugin->slug);

                    return $source?->package === $package ? $source->version : null;
                } catch (ProcessFailedException $exception) {
                    return null;
                }
            }
        }

        $repository = config("cms-core.updates.repositories.{$package}");

        if ((! is_string($repository) || $repository === '') && Schema::hasTable((new InstalledPlugin())->getTable())) {
            $plugin = InstalledPlugin::query()->where('package', $package)->whereNotNull('installed_version')->first();
            $metadata = $plugin?->metadata ?? [];
            $repository = match ($metadata['repository_type'] ?? null) {
                'vcs', 'git' => $metadata['repository_url'] ?? null,
                default => null,
            };
        }

        if (! is_string($repository) || $repository === '') {
            return null;
        }

        return $this->latestVersionFromRepository($repository, config('cms-core.updates.github_token'));
    }

    private function latestVersionFromRepository(string $repository, mixed $token): ?string
    {
        $gitVersion = $this->latestGitVersion($repository, $token);

        if ($gitVersion !== null) {
            return $gitVersion;
        }

        return $this->latestGithubVersion($repository, $token);
    }

    protected function latestGitVersion(string $repository, mixed $token): ?string
    {
        $process = $this->gitProcess($this->command($repository, $token));
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        return collect(explode("\n", trim($process->getOutput())))
            ->map(fn (string $line): ?string => $this->versionFromLine($line))
            ->filter()
            ->sort(fn (string $a, string $b): int => version_compare($a, $b))
            ->last();
    }

    /** @param array<int, string> $command */
    protected function gitProcess(array $command): Process
    {
        $process = new Process($command);
        $process->setEnv([
            'GIT_TERMINAL_PROMPT' => '0',
            'GCM_INTERACTIVE' => 'Never',
        ]);

        return $process;
    }

    protected function latestGithubVersion(string $repository, mixed $token): ?string
    {
        if (! is_string($token) || $token === '' || ! preg_match('#^https://github\.com/([^/]+)/([^/.]+)(?:\.git)?$#', $repository, $matches)) {
            return null;
        }

        $context = stream_context_create([
            'http' => [
                'header' => implode("\r\n", [
                    'Accept: application/vnd.github+json',
                    'Authorization: Bearer '.$token,
                    'User-Agent: PCTECK-CMS-Updater',
                    'X-GitHub-Api-Version: 2022-11-28',
                ]),
                'ignore_errors' => true,
                'timeout' => 30,
            ],
        ]);

        $response = @file_get_contents("https://api.github.com/repos/{$matches[1]}/{$matches[2]}/tags?per_page=100", false, $context);

        if (! is_string($response)) {
            return null;
        }

        $tags = json_decode($response, true);

        if (! is_array($tags)) {
            return null;
        }

        return collect($tags)
            ->map(fn (mixed $tag): ?string => is_array($tag) && isset($tag['name']) && is_string($tag['name'])
                ? $this->versionFromTag($tag['name'])
                : null)
            ->filter()
            ->sort(fn (string $a, string $b): int => version_compare($a, $b))
            ->last();
    }

    private function versionFromLine(string $line): ?string
    {
        if (! str_contains($line, 'refs/tags/')) {
            return null;
        }

        $tag = preg_replace('/\^{}$/', '', substr($line, strrpos($line, '/') + 1));

        if (! is_string($tag)) {
            return null;
        }

        $version = ltrim($tag, 'v');

        return $this->versionFromTag($version);
    }

    private function versionFromTag(string $tag): ?string
    {
        $version = ltrim($tag, 'v');

        return preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version) === 1
            ? 'v'.$version
            : null;
    }

    /**
     * @return array<int, string>
     */
    private function command(string $repository, mixed $token): array
    {
        if (! is_string($token) || $token === '' || ! str_starts_with($repository, 'https://github.com/')) {
            return ['git', 'ls-remote', '--tags', '--refs', $repository];
        }

        $authorization = base64_encode('x-access-token:'.$token);

        return [
            'git',
            '-c',
            "http.https://github.com/.extraheader=AUTHORIZATION: basic {$authorization}",
            'ls-remote',
            '--tags',
            '--refs',
            $repository,
        ];
    }
}
