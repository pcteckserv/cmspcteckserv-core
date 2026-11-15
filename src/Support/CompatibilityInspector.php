<?php

namespace Pcteckserv\CmsCore\Support;

use Composer\Semver\Semver;
use Throwable;

/** Inspeção só de leitura: não carrega .env, Laravel, Composer executável ou base de dados. */
class CompatibilityInspector
{
    public function inspect(string $root, string $target): array
    {
        $manifest = $this->read($root.'/composer.json');
        $lock = $this->read($root.'/composer.lock');
        $installed = $this->read($root.'/vendor/composer/installed.json');
        $lockedPackages = $this->packages($lock['packages'] ?? []);
        $installedPackages = $this->packages($installed['packages'] ?? $installed);
        $packages = $installedPackages ?: $lockedPackages;
        $core = $packages['pcteckserv/cms-core'] ?? [];
        $current = is_string($core['version'] ?? null) ? $core['version'] : null;
        $errors = []; $warnings = []; $plugins = []; $drift = []; $currentProblems = [];
        if ($manifest === []) $errors[] = 'O projeto não tem um composer.json válido.';
        if ($core === []) $errors[] = 'Não foi possível identificar a versão do Core.';
        if (!preg_match('/^\d+\.\d+\.\d+$/D', ltrim($target,'v'))) $errors[] = 'A versão pretendida não é uma versão estável válida.';
        if (!$installedPackages) $warnings[] = 'Dependências não instaladas: a avaliação usa o lock e não confirma os ficheiros executados.';
        foreach ($packages as $name => $package) {
            $constraint = $package['require']['pcteckserv/cms-core'] ?? null;
            if ($name !== 'pcteckserv/cms-core' && is_string($constraint)) {
                $code = $installedPackages ? $this->read($root.'/vendor/'.$name.'/composer.json') : [];
                $pluginManifest = $installedPackages ? $this->read($root.'/vendor/'.$name.'/cms-plugin.json') : [];
                $codeConstraint = $code['require']['pcteckserv/cms-core'] ?? $constraint;
                if (!is_string($codeConstraint)) $codeConstraint = 'restrição inválida';
                $compatible = $this->satisfies($target, $constraint) && $this->satisfies($target, $codeConstraint);
                $currentCompatible = is_string($current) && $this->satisfies($current, $codeConstraint);
                $plugins[] = ['package'=>$name, 'version'=>$package['version'] ?? null, 'code_version'=>$pluginManifest['version'] ?? null, 'requires_core'=>$constraint, 'code_requires_core'=>$codeConstraint, 'current_compatible'=>$currentCompatible, 'compatible'=>$compatible];
                if (!$compatible) $errors[] = $name.' exige Core '.$constraint.($constraint !== $codeConstraint ? ' (ficheiros: '.$codeConstraint.')' : '').'.';
                if ($constraint !== $codeConstraint) $warnings[] = 'As restrições do Core nos ficheiros e nos metadados de '.$name.' divergem.';
                if (!$currentCompatible) $currentProblems[] = $name.' contém código que exige Core '.$codeConstraint.'.';
                if (is_string($pluginManifest['version'] ?? null) && is_string($package['version'] ?? null) && preg_match('/^v?\d/', $package['version']) && ltrim($pluginManifest['version'],'v') !== ltrim($package['version'],'v')) $warnings[] = 'Os ficheiros e os metadados de versão de '.$name.' divergem.';
            }
            if (isset($installedPackages[$name], $lockedPackages[$name]) && ($installedPackages[$name]['version'] ?? null) !== ($lockedPackages[$name]['version'] ?? null)) $drift[] = $name;
        }
        $majorChange = is_string($current) && explode('.',ltrim($current,'v'))[0] !== explode('.',ltrim($target,'v'))[0];
        if ($majorChange) $errors[] = 'A mudança de versão principal exige uma migração própria.';
        $snapshot = ($core['dist']['type'] ?? null) === 'path' || ($core['extra']['cms-original-dist-type'] ?? null) === 'path';
        if ($snapshot) $warnings[] = 'O Core é um snapshot: exige uma distribuição dos ficheiros, sem apenas alterar a versão declarada.';
        if ($drift) $warnings[] = 'Há versões instaladas diferentes do lock. A atualização tem de preservar a instalação efetiva.';
        $integrity = null;
        if ($installedPackages) {
            $directory = $root.'/vendor/pcteckserv/cms-core';
            if (!is_file($directory.'/src/CmsCoreServiceProvider.php')) $errors[] = 'Falta o provider do Core instalado.';
            if (is_file($directory.'/'.ReleaseIntegrity::FILE)) {
                $integrity = (new ReleaseIntegrity())->verify($directory, $current);
                if (!$integrity['valid']) $errors = [...$errors, ...$integrity['errors']];
            } else $warnings[] = 'Esta versão antiga não inclui manifesto de integridade.';
        }
        return ['project'=>$manifest['name'] ?? null, 'installed_core'=>$installedPackages ? $current : null, 'locked_core'=>$lockedPackages['pcteckserv/cms-core']['version'] ?? null, 'target_core'=>$target, 'compatible'=>$errors === [], 'automatic_update_allowed'=>$errors === [] && !$snapshot && $installedPackages !== [], 'requires_constraint_update'=>is_string($manifest['require']['pcteckserv/cms-core'] ?? null) && !$this->satisfies($target, $manifest['require']['pcteckserv/cms-core']), 'snapshot'=>$snapshot, 'plugins'=>$plugins, 'lock_drift'=>$drift, 'integrity'=>$integrity, 'current_problems'=>$currentProblems, 'errors'=>array_values(array_unique($errors)), 'warnings'=>$warnings];
    }

    private function read(string $file): array
    {
        if (!is_file($file)) return [];
        $value = json_decode((string) file_get_contents($file), true);
        return is_array($value) ? $value : [];
    }

    private function packages(mixed $packages): array
    {
        if (!is_array($packages)) return [];
        $result = [];
        foreach ($packages as $package) {
            if (is_array($package) && is_string($package['name'] ?? null) && preg_match('~^[a-z0-9_.-]+/[a-z0-9_.-]+$~D', $package['name'])) $result[$package['name']] = $package;
        }
        return $result;
    }

    private function satisfies(string $version, mixed $constraint): bool
    {
        if (!is_string($constraint)) return false;
        try { return Semver::satisfies(ltrim($version,'v'), $constraint); }
        catch (Throwable) { return false; }
    }
}
