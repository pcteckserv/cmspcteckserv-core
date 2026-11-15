<?php

namespace Pcteckserv\CmsCore\Support;

use RuntimeException;

/** Verificação independente do arranque do Laravel e da base de dados. */
class ReleaseIntegrity
{
    public const FILE = 'core-release.json';

    public static function hash(string $file): string
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($extension, ['php','json','js','mjs','scss','css','svg','xml','html','md','txt','yml','yaml','csv'], true)) {
            return hash('sha256', str_replace("\r\n", "\n", (string) file_get_contents($file)));
        }
        return hash_file('sha256', $file);
    }

    public function verify(string $directory, ?string $expectedVersion = null): array
    {
        $root = realpath($directory);
        $result = ['valid'=>false, 'version'=>null, 'errors'=>[]];
        if ($root === false || !is_file($root.'/'.self::FILE)) {
            $result['errors'][] = 'Manifesto de integridade da distribuição não encontrado.';
            return $result;
        }
        $manifest = json_decode((string) file_get_contents($root.'/'.self::FILE), true);
        if (!is_array($manifest) || ($manifest['schema'] ?? null) !== 1 || !is_string($manifest['version'] ?? null) || !preg_match('/^\d+\.\d+\.\d+$/D', $manifest['version']) || !is_array($manifest['files'] ?? null)) {
            $result['errors'][] = 'Manifesto de integridade inválido.';
            return $result;
        }
        $result['version'] = $manifest['version'];
        if ($expectedVersion !== null && ltrim($expectedVersion, 'v') !== $manifest['version']) $result['errors'][] = 'A versão dos ficheiros não corresponde à versão pedida.';
        foreach (['composer.json', 'src/CmsCoreServiceProvider.php'] as $required) if (!isset($manifest['files'][$required])) $result['errors'][] = 'O manifesto não identifica '.$required.'.';
        $prefix = str_replace('\\', '/', $root).'/';
        foreach ($manifest['files'] as $relative => $hash) {
            if (!is_string($relative) || str_contains($relative, "\0") || str_contains($relative, '\\') || str_starts_with($relative, '/') || str_contains($relative, ':') || in_array('..', explode('/', $relative), true) || !is_string($hash) || !preg_match('/^[a-f0-9]{64}$/D', $hash)) {
                $result['errors'][] = 'O manifesto contém um caminho ou hash inválido.';
                continue;
            }
            $file = realpath($root.'/'.$relative);
            if ($file === false || !is_file($file) || !str_starts_with(str_replace('\\', '/', $file), $prefix)) {
                $result['errors'][] = 'Ficheiro ausente ou fora da distribuição: '.$relative.'.';
                continue;
            }
            if (!hash_equals($hash, self::hash($file))) $result['errors'][] = 'Ficheiro diferente da distribuição: '.$relative.'.';
        }
        $result['valid'] = $result['errors'] === [];
        return $result;
    }

    public function assertValid(string $directory, string $expectedVersion): void
    {
        $result = $this->verify($directory, $expectedVersion);
        if (!$result['valid']) throw new RuntimeException('A distribuição do Core não está íntegra. '.implode(' ', array_slice($result['errors'], 0, 4)));
    }
}
