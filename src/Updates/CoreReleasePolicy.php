<?php

namespace Pcteckserv\CmsCore\Updates;

/** Política das linhas estáveis; independente de Laravel e de credenciais. */
final class CoreReleasePolicy
{
    public static function allows(?string $installed, ?string $candidate): bool
    {
        if (!is_string($installed) || !is_string($candidate)
            || !preg_match('/^v?(\d+)\.(\d+)\.(\d+)$/D',$installed,$current)
            || !preg_match('/^v?(\d+)\.(\d+)\.(\d+)$/D',$candidate,$next)) return false;
        if ($current[1] !== $next[1]) return false;
        // As linhas Core 2 mantêm contratos históricos diferentes entre minors.
        return $current[1] !== '2' || $current[2] === $next[2];
    }

    public static function repository(string $url): string
    {
        $normalized = strtolower(rtrim($url,'/'));
        if (in_array($normalized,[
            'https://github.com/diogoleitean/pcteckcms_core.git',
            'https://github.com/diogoleitean/pcteckcms_core',
        ],true)) return 'https://github.com/pcteckserv/cmspcteckserv-core.git';
        return $url;
    }

    public static function requiresIntegrity(string $version): bool
    {
        $version=ltrim($version,'v');
        return version_compare($version,'3.1.7','>=')
            || (str_starts_with($version,'2.9.') && version_compare($version,'2.9.2','>='));
    }

    public static function portableRepositories(array $manifest): array
    {
        foreach ($manifest['repositories'] ?? [] as $key=>$repository) {
            if (is_array($repository) && in_array($repository['type'] ?? '',['vcs','git'],true)
                && is_string($repository['url'] ?? null)) {
                $manifest['repositories'][$key]['url'] = self::repository($repository['url']);
            }
        }
        return $manifest;
    }
}
