<?php

namespace Pcteckserv\CmsCore\Updates;

use Symfony\Component\Process\Process;

class GitTagUpdateChecker
{
    public function latestVersion(string $package): ?string
    {
        $repository = config("cms-core.updates.repositories.{$package}");

        if (! is_string($repository) || $repository === '') {
            return null;
        }

        $process = new Process($this->command($repository));
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

        return preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version) === 1
            ? $version
            : null;
    }

    /**
     * @return array<int, string>
     */
    private function command(string $repository): array
    {
        $token = config('cms-core.updates.github_token');

        if (! is_string($token) || $token === '' || ! str_starts_with($repository, 'https://github.com/')) {
            return ['git', 'ls-remote', '--tags', '--refs', $repository];
        }

        return [
            'git',
            '-c',
            "http.https://github.com/.extraheader=AUTHORIZATION: bearer {$token}",
            'ls-remote',
            '--tags',
            '--refs',
            $repository,
        ];
    }
}
