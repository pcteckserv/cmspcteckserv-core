<?php

namespace Pcteckserv\CmsCore\Updates;

final class StarterPackage
{
    public const NAME = 'pcteckserv/cms-starter';

    public static function is(string $package): bool
    {
        return $package === self::NAME;
    }

    public static function repository(): ?string
    {
        $repository = config('cms-core.updates.starter.repository');

        if (! is_string($repository) || trim($repository) === '') {
            return null;
        }

        $repository = trim($repository);

        return preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repository) === 1
            ? 'https://github.com/'.$repository.'.git'
            : $repository;
    }

    public static function token(): ?string
    {
        $token = config('cms-core.updates.starter.github_token');

        return is_string($token) && $token !== ''
            ? $token
            : null;
    }

    public static function displayName(): string
    {
        return trim((string) config('app.name', '')) ?: 'Projeto';
    }

    public static function repositoryLabel(): ?string
    {
        $repository = self::repository();

        if ($repository === null) {
            return null;
        }

        $path = str_starts_with($repository, 'git@github.com:')
            ? substr($repository, strlen('git@github.com:'))
            : (in_array(parse_url($repository, PHP_URL_HOST), ['github.com', 'www.github.com'], true)
                ? (string) parse_url($repository, PHP_URL_PATH) : '');
        $path = preg_replace('/\.git$/', '', trim($path, '/'));

        return preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $path) === 1 ? $path : null;
    }

    public static function versionFile(): string
    {
        return base_path('storage/app/cms-starter-version');
    }
}
