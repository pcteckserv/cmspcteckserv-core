<?php

namespace Pcteckserv\CmsCore\Updates;

use Composer\Semver\Intervals;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use RuntimeException;

/** Valida a atualização dos ficheiros do site sem substituir Composer ou vendor. */
final class StarterDependencyPolicy
{
    private const OPTIONAL_PLUGINS = [
        'pcteckserv/cms-contact-forms', 'pcteckserv/cms-portfolio', 'pcteckserv/cms-customers',
        'pcteckserv/cms-agenda', 'pcteckserv/cms-realstate', 'pcteckserv/cms-blog',
        'pcteckserv/cms-moloni', 'pcteckserv/cms-easypay', 'pcteckserv/cms-paypal',
        'pcteckserv/cms-shop', 'pcteckserv/cms-ai', 'pcteckserv/cms-product-customization',
    ];

    public function assertCompatible(string $source, string $destination): void
    {
        $read = static fn (string $path): array => is_file($path) ? json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) : [];
        $current = $read($destination.'/composer.json');
        $incoming = $read($source.'/composer.json');
        if (! is_string($current['name'] ?? null) || $current['name'] === '' || ($incoming['name'] ?? null) !== $current['name']) {
            throw new RuntimeException('O repositório não corresponde ao Starter deste site. Nenhum ficheiro foi copiado.');
        }
        foreach (['require', 'require-dev', 'autoload', 'autoload-dev'] as $key) {
            $before = $current[$key] ?? [];
            $after = $incoming[$key] ?? [];
            if ($key === 'require') {
                foreach (['pcteckserv/cms-core', ...self::OPTIONAL_PLUGINS] as $package) {
                    unset($before[$package], $after[$package]);
                }
                $parser = new VersionParser;
                foreach ($before as $package => $constraint) {
                    if (! isset($after[$package]) || $constraint === $after[$package]) {
                        continue;
                    }
                    try {
                        if (Intervals::isSubsetOf($parser->parseConstraints($constraint), $parser->parseConstraints($after[$package]))) {
                            $after[$package] = $constraint;
                        }
                    } catch (\UnexpectedValueException) {
                        // Restrições inválidas mantêm a recusa, sem alterar dependências.
                    }
                }
            }
            if ($this->normalise($before) !== $this->normalise($after)) {
                throw new RuntimeException('Este Starter altera dependências ou autoload fora dos plugins opcionais e exige uma publicação preparada separadamente. Nenhum ficheiro foi copiado.');
            }
        }
        $metadata = $read($destination.'/vendor/composer/installed.json');
        $lock = $read($source.'/composer.lock');
        $version = static function (array $packages): ?string {
            foreach ($packages as $package) {
                if (($package['name'] ?? '') === 'pcteckserv/cms-core') {
                    return ltrim((string) ($package['version'] ?? ''), 'v');
                }
            }

            return null;
        };
        $installed = $version($metadata['packages'] ?? $metadata);
        $sourceVersion = $version($lock['packages'] ?? []);
        // O lock da distribuição identifica a linha; o requisito Composer declara a API mínima.
        $constraint = $incoming['require']['pcteckserv/cms-core'] ?? null;
        if (is_string($constraint) && preg_match('/^v?\d+\.\d+\.\d+$/D', $constraint)) {
            $constraint = '^'.ltrim($constraint, 'v');
        }
        try {
            $compatible = is_string($installed) && is_string($sourceVersion) && is_string($constraint)
                && preg_match('/^\d+\.\d+\.\d+$/D', $installed) && preg_match('/^\d+\.\d+\.\d+$/D', $sourceVersion)
                && explode('.', $installed)[0] === explode('.', $sourceVersion)[0] && Semver::satisfies($installed, $constraint);
        } catch (\UnexpectedValueException) {
            $compatible = false;
        }
        if (! $compatible) {
            throw new RuntimeException('O Starter exige uma versão compatível do Core ('.($constraint ?? 'não declarada').'). Atualize primeiro o Core; nenhum ficheiro foi copiado.');
        }
    }

    private function normalise(array $values): array
    {
        if (! array_is_list($values)) {
            ksort($values);
        }
        foreach ($values as &$value) {
            if (is_array($value)) {
                $value = $this->normalise($value);
            }
        }

        return $values;
    }
}
