<?php
declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Pcteckserv\CmsCore\Support\ReleaseIntegrity;
use Pcteckserv\CmsCore\Updates\ComposerDeploymentTransaction;
use Pcteckserv\CmsCore\Updates\CoreReleasePolicy;

const BOOTSTRAP_KEY_HASH = '__KEY_HASH__';
const BOOTSTRAP_EXPIRES = __EXPIRES__;
const BOOTSTRAP_ID = '__ID__';
const BOOTSTRAP_PROJECT = '__PROJECT__';
const BOOTSTRAP_TARGET = '__TARGET__';
const BOOTSTRAP_FILES = '__FILES__';
const BOOTSTRAP_OLD_ARCHIVES = '__OLD_ARCHIVES__';

ini_set('display_errors', '0');
$key = $_POST['chave'] ?? $_GET['chave'] ?? '';
if (! is_string($key) || ! hash_equals(BOOTSTRAP_KEY_HASH, hash('sha256', $key)) || time() > BOOTSTRAP_EXPIRES) {
    http_response_code(404);
    exit('Página não encontrada.');
}
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");

function bootstrapExtract(string $zipPath, string $destination): void
{
    if (! mkdir($destination, 0700, true)) {
        throw new RuntimeException('Não foi possível preparar a reposição.');
    }
    $zip = new ZipArchive;
    if ($zip->open($zipPath) !== true) {
        throw new RuntimeException('Arquivo de reposição inválido.');
    }
    try {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! is_string($name) || $name === '' || str_contains($name, '..') || str_contains($name, '\\') || str_contains($name, ':') || str_starts_with($name, '/')) {
                throw new RuntimeException('Caminho inválido no arquivo.');
            }
            $zip->getExternalAttributesIndex($i, $opsys, $attributes);
            if ((($attributes >> 16) & 0170000) === 0120000) {
                throw new RuntimeException('Ligações simbólicas não são permitidas.');
            }
            $path = $destination.'/'.$name;
            if (str_ends_with($name, '/')) {
                if (! is_dir($path)) {
                    mkdir($path, 0700, true);
                }

                continue;
            }
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0700, true);
            }
            $bytes = $zip->getFromIndex($i);
            if (! is_string($bytes) || file_put_contents($path, $bytes, LOCK_EX) === false) {
                throw new RuntimeException('Extração incompleta.');
            }
        }
    } finally {
        $zip->close();
    }
}

$error = null;
$done = false;
$repairNeeded = false;
$current = '';
$work = null;
$mutex = null;
$ready = null;
try {
    if (PHP_VERSION_ID < 80300 || ! class_exists(ZipArchive::class)) {
        throw new RuntimeException('É necessário PHP 8.3 ou superior com ZIP.');
    }
    $root = realpath(__DIR__.'/../app');
    if (! $root || ! is_file($root.'/vendor/composer/installed.json')) {
        throw new RuntimeException('Envie o ficheiro para public_html, junto à pasta app do site.');
    }
    $work = $root.'/storage/app/core-bootstrap/'.BOOTSTRAP_ID;
    if (is_link($work) || ! is_dir($work)) {
        throw new RuntimeException('Envie primeiro todos os ficheiros privados da pasta app.');
    }
    $manifest = json_decode((string) file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    if (($manifest['name'] ?? null) !== BOOTSTRAP_PROJECT) {
        throw new RuntimeException('Este pacote pertence a outro website. Nenhum ficheiro foi alterado.');
    }
    $metadata = json_decode((string) file_get_contents($root.'/vendor/composer/installed.json'), true, flags: JSON_THROW_ON_ERROR);
    $laravel = null;
    foreach ($metadata['packages'] ?? $metadata as $package) {
        if (($package['name'] ?? '') === 'pcteckserv/cms-core') {
            $current = ltrim($package['version'] ?? '', 'v');
        }
        if (($package['name'] ?? '') === 'laravel/framework') {
            $laravel = ltrim($package['version'] ?? '', 'v');
        }
    }
    if (! preg_match('/^3\.\d+\.\d+$/D', $current) || ! is_string($laravel) || ! str_starts_with($laravel, '13.') || ! version_compare(BOOTSTRAP_TARGET, $current, '>')) {
        throw new RuntimeException('A instalação não corresponde à linha Core 3 / Laravel 13 anterior à versão pretendida.');
    }
    $core = $root.'/vendor/pcteckserv/cms-core';
    if (is_link($root.'/vendor') || is_link($core) || realpath($core) !== realpath($root.'/vendor').DIRECTORY_SEPARATOR.'pcteckserv'.DIRECTORY_SEPARATOR.'cms-core') {
        throw new RuntimeException('Localização do Core inesperada.');
    }
    $files = json_decode(base64_decode(BOOTSTRAP_FILES, true), true, flags: JSON_THROW_ON_ERROR);
    foreach ($files as $relative => $hash) {
        if (str_contains($relative, '..') || is_link($work.'/'.$relative) || ! is_file($work.'/'.$relative) || ! hash_equals($hash, hash_file('sha256', $work.'/'.$relative))) {
            throw new RuntimeException('Envio incompleto ou alterado. Nenhum ficheiro foi instalado.');
        }
    }
    // Carregar a transação validada antes de o autoload antigo resolver estas classes.
    foreach (['src/Updates/CoreReleasePolicy.php', 'src/Support/ReleaseIntegrity.php', 'src/Updates/InstalledComposerSnapshot.php', 'src/Updates/UpdateResult.php', 'src/Support/ComposerCommand.php', 'src/Updates/ComposerDeploymentTransaction.php'] as $file) {
        require_once $work.'/'.$file;
    }
    if (is_file($core.'/core-release.json')) {
        try {
            (new ReleaseIntegrity)->assertValid($core, $current);
        } catch (Throwable) {
            $repairNeeded = true;
        }
    }
    if (! is_file($core.'/core-release.json') && CoreReleasePolicy::requiresIntegrity($current)) {
        $repairNeeded = true;
    }
    $old = json_decode(base64_decode(BOOTSTRAP_OLD_ARCHIVES, true), true, flags: JSON_THROW_ON_ERROR);
    if ($repairNeeded && ! isset($old[$current])) {
        throw new RuntimeException('Os ficheiros atuais não correspondem à versão declarada e esta reposição específica não está incluída. Não atualize até repor a distribuição instalada.');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        @set_time_limit(0);
        ignore_user_abort(true);
        $mutex = fopen($work.'/bootstrap.lock', 'c');
        if (! $mutex || ! flock($mutex, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('Já existe uma operação em curso.');
        }
        if ($repairNeeded) {
            $ready = $work.'/ready-'.bin2hex(random_bytes(8));
            bootstrapExtract($work.'/'.$old[$current], $ready);
            (new ReleaseIntegrity)->assertValid($ready, $current);
        }
        require $root.'/vendor/autoload.php';
        $app = require $root.'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        config(['cms-core.updates.allow_core_updates' => true, 'cms-core.updates.composer_binary' => $work.'/composer.phar']);
        $transaction = new ComposerDeploymentTransaction;
        $result = $ready === null ? $transaction->update(BOOTSTRAP_TARGET) : $transaction->updateFromVerifiedDistribution(BOOTSTRAP_TARGET, $ready);
        if (! $result->successful) {
            throw new RuntimeException($result->message);
        }
        if (! is_file($root.'/composer.phar') && ! copy($work.'/composer.phar', $root.'/composer.phar')) {
            throw new RuntimeException('O Core foi atualizado, mas copie composer.phar para app antes de voltar a atualizar pelo painel.');
        }
        file_put_contents($work.'/concluido.json', json_encode(['project' => BOOTSTRAP_PROJECT, 'from' => $current, 'to' => BOOTSTRAP_TARGET, 'completed_at' => gmdate('c')], JSON_THROW_ON_ERROR));
        $done = true;
        foreach (array_keys($files) as $file) {
            @unlink($work.'/'.$file);
        }
        @unlink(__FILE__);
    }
} catch (Throwable $exception) {
    $error = $exception->getMessage();
} finally {
    if (is_resource($mutex)) {
        flock($mutex, LOCK_UN);
        fclose($mutex);
    }
}
$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?><!doctype html><html lang="pt-PT"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Atualização preparada do Core</title><style>body{font:16px system-ui;background:#f2f5f7;color:#183743;margin:0;padding:24px}main{max-width:760px;margin:auto;background:white;padding:28px;border-radius:16px}p{line-height:1.6}.error{background:#fff0ed;padding:16px;overflow-wrap:anywhere}button{font:inherit;padding:14px 22px;cursor:pointer}</style><main>
<h1><?= $done ? 'Core atualizado' : 'Desbloquear atualização do Core' ?></h1>
<?php if ($error) { ?><p class="error"><?= $escape($error) ?></p>
<?php } elseif ($done) { ?><p>O Core foi atualizado para <?= $escape(BOOTSTRAP_TARGET) ?> com os ficheiros da versão publicada. Laravel, plugins e dados foram preservados. Volte a Sistema → Atualizações e atualize o Starter e os plugins pretendidos.</p><p>O finalizador foi eliminado. Conserve as cópias privadas até confirmar o funcionamento.</p>
<?php } else { ?><p>Website: <?= $escape(BOOTSTRAP_PROJECT) ?>. Core instalado: <?= $escape($current) ?>. Versão a instalar: <?= $escape(BOOTSTRAP_TARGET) ?>.</p><p><?= $repairNeeded ? 'A versão declarada será validada a partir da distribuição canónica incluída; os ficheiros atuais só são substituídos quando a nova instalação estiver preparada. ' : '' ?>A nova versão será preparada e validada fora da instalação. As outras dependências ficam congeladas; alterações incompatíveis são recusadas.</p><form method="post"><input type="hidden" name="chave" value="<?= $escape($key) ?>"><button>Preparar e atualizar o Core</button></form><?php }?></main></html>
