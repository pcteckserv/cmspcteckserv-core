<?php

namespace Pcteckserv\CmsCore\Updates;

use Illuminate\Support\Facades\File;
use Pcteckserv\CmsCore\Support\ComposerCommand;
use Pcteckserv\CmsCore\Support\ReleaseIntegrity;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

if (! class_exists(CoreReleasePolicy::class, false)) {
    require_once __DIR__.'/CoreReleasePolicy.php';
}
if (! class_exists(ReleaseIntegrity::class, false)) {
    require_once is_file(__DIR__.'/../Support/ReleaseIntegrity.php') ? __DIR__.'/../Support/ReleaseIntegrity.php' : __DIR__.'/ReleaseIntegrity.php';
}

/** Prepara o Core fora da instalação, sem atualizar os restantes pacotes existentes. */
class ComposerDeploymentTransaction
{
    private const PACKAGE = 'pcteckserv/cms-core';

    public function update(?string $target): UpdateResult
    {
        return $this->execute($target, null);
    }

    /** Passagem explícita de uma distribuição antiga incoerente, sem a publicar antes das dependências. */
    public function updateFromVerifiedDistribution(?string $target, string $currentDistribution): UpdateResult
    {
        return $this->execute($target, null, $currentDistribution);
    }

    public function remove(string $package): UpdateResult
    {
        return $this->execute(null, $package);
    }

    private function execute(?string $target, ?string $removing, ?string $currentDistribution = null): UpdateResult
    {
        @set_time_limit(0);
        ignore_user_abort(true);
        $root = base_path();
        $work = storage_path('app/core-updates');
        File::ensureDirectoryExists($work, 0700);
        $mutex = fopen($work.'/update.lock', 'c');
        if ($mutex === false || ! flock($mutex, LOCK_EX | LOCK_NB)) {
            if (is_resource($mutex)) {
                fclose($mutex);
            }

            return new UpdateResult(false, 'Já existe uma atualização do Core em curso.');
        }
        $run = $work.'/'.date('Ymd-His').'-'.bin2hex(random_bytes(6));
        $swapped = false;
        $maintenanceCreated = false;
        $maintenance = $root.'/storage/framework/maintenance.php';
        try {
            if ($removing === null && ! config('cms-core.updates.allow_core_updates', true)) {
                throw new RuntimeException('A atualização do Core está desativada neste site.');
            }
            $manifestBytes = File::get($root.'/composer.json');
            $lockBytes = File::get($root.'/composer.lock');
            $manifest = json_decode($manifestBytes, true, flags: JSON_THROW_ON_ERROR);
            $lock = json_decode($lockBytes, true, flags: JSON_THROW_ON_ERROR);
            $installedBytes = File::get($root.'/vendor/composer/installed.json');
            $installed = json_decode($installedBytes, true, flags: JSON_THROW_ON_ERROR);
            $core = $this->packages($installed['packages'] ?? $installed)[self::PACKAGE] ?? [];
            $current = ltrim($core['version'] ?? '', 'v');
            $target = ltrim($target ?? '', 'v');
            $snapshotCore = ($core['dist']['type'] ?? null) === 'path' || ($core['extra']['cms-original-dist-type'] ?? null) === 'path';
            if ($removing === null && $snapshotCore && ! CoreReleasePolicy::requiresIntegrity($target)) {
                throw new RuntimeException('A passagem de um snapshot do Starter exige uma versão publicada com manifesto de integridade.');
            }
            if ($removing === null && (! preg_match('/^\d+\.\d+\.\d+$/', $current) || ! preg_match('/^\d+\.\d+\.\d+$/', $target) || ! version_compare($target, $current, '>'))) {
                throw new RuntimeException('Não existe uma nova versão estável do Core confirmada para instalar.');
            }
            if ($removing === null && explode('.', $target)[0] !== explode('.', $current)[0]) {
                throw new RuntimeException('Esta versão muda a versão principal do Core. É necessária uma migração do Starter e dos plugins antes de atualizar.');
            }
            if ($removing === null && ! CoreReleasePolicy::allows($current, $target)) {
                throw new RuntimeException('A versão pedida não pertence à linha estável deste Core. É necessária uma migração própria.');
            }
            if (! is_file($root.'/vendor/pcteckserv/cms-core/src/CmsCoreServiceProvider.php')) {
                throw new RuntimeException('A instalação atual do Core está incompleta. Reponha os ficheiros antes de atualizar.');
            }
            if ($currentDistribution !== null) {
                (new ReleaseIntegrity)->assertValid($currentDistribution, $current);
            } elseif (is_file($root.'/vendor/pcteckserv/cms-core/core-release.json')) {
                (new ReleaseIntegrity)->assertValid($root.'/vendor/pcteckserv/cms-core', $current);
            }
            if (is_link($root.'/vendor') || isset($manifest['config']['vendor-dir'])) {
                throw new RuntimeException('A localização de vendor exige uma publicação própria.');
            }
            File::ensureDirectoryExists($run.'/stage', 0700);
            if ((stat($root.'/vendor')['dev'] ?? -1) !== (stat($run)['dev'] ?? -2)) {
                throw new RuntimeException('A preparação e a instalação têm de estar no mesmo disco.');
            }
            if ($removing !== null && ($removing === self::PACKAGE || ! preg_match('~^[a-z0-9_.-]+/[a-z0-9_.-]+$~D', $removing) || ! isset($this->packages($installed['packages'] ?? $installed)[$removing]))) {
                throw new RuntimeException('O pacote indicado não pode ser removido.');
            }
            $baseline = (new InstalledComposerSnapshot)->baseline($lock, $installed, $run.'/stage', $removing);
            $finalManifest = CoreReleasePolicy::portableRepositories((new InstalledComposerSnapshot)->portableManifest($manifest));
            if ($removing === null) {
                $finalManifest['require'][self::PACKAGE] = $target;
                if ($snapshotCore) {
                    $repository = config('cms-core.updates.repositories.'.self::PACKAGE);
                    if (! is_string($repository) || trim($repository) === '') {
                        throw new RuntimeException('Configure o repositório do Core antes de atualizar este snapshot.');
                    }
                    $repositories = array_values($finalManifest['repositories'] ?? []);
                    array_unshift($repositories, ['type' => 'vcs', 'url' => CoreReleasePolicy::repository($repository), 'only' => [self::PACKAGE]]);
                    $finalManifest['repositories'] = $repositories;
                }
            } else {
                unset($finalManifest['require'][$removing], $finalManifest['require-dev'][$removing]);
                $finalManifest['require'][self::PACKAGE] = $core['version'];
            }
            $stageManifest = $this->stageManifest($finalManifest, $baseline);
            if ($removing !== null) {
                $stageManifest['repositories'][0]['package'][] = $this->packages([...$baseline['packages'], ...$baseline['packages-dev']])[self::PACKAGE];
                $stageManifest['require'][self::PACKAGE] = $core['version'];
            }
            // Congelar o que existe no vendor, mesmo que um Starter antigo tenha reposto outro lock.
            foreach ([...$baseline['packages'], ...$baseline['packages-dev']] as $package) {
                if ($package['name'] !== self::PACKAGE && $package['name'] !== $removing) {
                    $section = in_array($package['name'], array_column($baseline['packages-dev'], 'name'), true) ? 'require-dev' : 'require';
                    $stageManifest[$section][$package['name']] = $package['version'];
                }
            }
            $this->json($run.'/stage/composer.json', $stageManifest);
            $this->json($run.'/stage/composer.lock', $baseline);
            $this->composer(['update', $removing ?? self::PACKAGE, '--with-dependencies', '--minimal-changes', '--no-install', '--no-scripts', '--no-plugins', '--no-interaction'], $run.'/stage');
            $planned = json_decode(File::get($run.'/stage/composer.lock'), true, flags: JSON_THROW_ON_ERROR);
            $this->validatePlan($baseline, $planned, $removing === null ? $target : $current, $removing);
            // Nenhum script do site é executado durante a preparação.
            $this->copyVendor($root.'/vendor', $run.'/stage/vendor', PHP_OS_FAMILY === 'Windows');
            $this->prepareChangedPackages($run.'/stage', $installed, $planned);
            $noDev = ($installed['dev'] ?? true) === false;
            $this->composer(['install', ...($noDev ? ['--no-dev'] : []), '--no-scripts', '--no-plugins', '--no-interaction', '--prefer-dist'], $run.'/stage');
            $preparedCore = $run.'/stage/vendor/pcteckserv/cms-core';
            // As distribuições novas têm de trazer todos os ficheiros da versão anunciada.
            if ($removing === null && (CoreReleasePolicy::requiresIntegrity($target) || is_file($preparedCore.'/core-release.json'))) {
                (new ReleaseIntegrity)->assertValid($preparedCore, $target);
            }
            $this->process([(new ComposerCommand)->phpBinary(), '-r', 'require $argv[1]."/vendor/autoload.php"; if (!class_exists("Pcteckserv\\CmsCore\\CmsCoreServiceProvider")) exit(2);', $run.'/stage'], $root);
            (new InstalledComposerSnapshot)->publishArchives($run.'/stage');
            $finalJson = $this->encode($finalManifest);
            $planned['content-hash'] = $this->contentHash($finalJson);
            $this->json($run.'/stage/composer.lock', $planned);
            // Validar também o manifesto final, sem as restrições temporárias da preparação.
            $this->json($run.'/stage/composer.json', $finalManifest);
            $this->composer(['install', '--dry-run', ...($noDev ? ['--no-dev'] : []), '--no-scripts', '--no-plugins', '--no-interaction'], $run.'/stage');
            $publishingVendor = $this->runtimeVendor($run.'/stage/vendor', $run.'/runtime/vendor');
            if ($publishingVendor !== $run.'/stage/vendor') {
                if ($removing === null && CoreReleasePolicy::requiresIntegrity($target)) {
                    (new ReleaseIntegrity)->assertValid($publishingVendor.'/pcteckserv/cms-core', $target);
                }
                $this->process([(new ComposerCommand)->phpBinary(), '-r', 'require $argv[1]."/autoload.php"; if (!class_exists("Pcteckserv\\CmsCore\\CmsCoreServiceProvider")) exit(2);', $publishingVendor], $root);
            }
            File::put($run.'/composer.json.before', $manifestBytes);
            File::put($run.'/composer.lock.before', $lockBytes);
            // Não publicar se outro processo alterou a instalação durante a preparação.
            if (File::get($root.'/composer.json') !== $manifestBytes || File::get($root.'/composer.lock') !== $lockBytes || File::get($root.'/vendor/composer/installed.json') !== $installedBytes) {
                throw new RuntimeException('A instalação foi alterada durante a preparação. Volte a verificar as atualizações.');
            }
            if (! is_file($maintenance)) {
                $this->atomicWrite($maintenance, '<?php http_response_code(503); header("Retry-After: 60"); echo "Atualização em curso. Tente novamente dentro de instantes."; exit;');
                $maintenanceCreated = true;
            }
            if (! $this->moveDirectory($root.'/vendor', $run.'/previous-vendor')) {
                throw new RuntimeException('Não foi possível preservar as dependências atuais.');
            }
            $swapped = true;
            if (! $this->moveDirectory($publishingVendor, $root.'/vendor')) {
                throw new RuntimeException('Não foi possível publicar as dependências preparadas.');
            }
            $this->atomicWrite($root.'/composer.json', $finalJson);
            $this->atomicWrite($root.'/composer.lock', File::get($run.'/stage/composer.lock'));
            $this->clearBootstrapCache($root);
            // Reconstruir também o autoload do projeto, cujas classes não estão na pasta de preparação.
            $this->composer(['dump-autoload', ...($noDev ? ['--no-dev'] : []), '--no-scripts', '--no-plugins', '--no-interaction'], $root);
            foreach (($removing === null ? [['package:discover'], ['migrate', '--force'], ['optimize:clear']] : [['package:discover'], ['optimize:clear']]) as $arguments) {
                $this->process([(new ComposerCommand)->phpBinary(), $root.'/artisan', ...$arguments], $root);
            }
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            if ($removing !== null) {
                return new UpdateResult(true, 'Plugin removido. As restantes versões instaladas e os dados foram preservados.');
            }

            return new UpdateResult(true, 'Core atualizado de '.$current.' para '.$target.'. As versões do Laravel e dos plugins foram preservadas.');
        } catch (Throwable $error) {
            if ($swapped) {
                try {
                    if (is_dir($root.'/vendor') && ! $this->moveDirectory($root.'/vendor', $run.'/failed-vendor')) {
                        throw new RuntimeException('Não foi possível afastar a instalação que falhou.');
                    }
                    if (! $this->moveDirectory($run.'/previous-vendor', $root.'/vendor')) {
                        throw new RuntimeException('Não foi possível repor as dependências anteriores.');
                    }
                    $this->atomicWrite($root.'/composer.json', File::get($run.'/composer.json.before'));
                    $this->atomicWrite($root.'/composer.lock', File::get($run.'/composer.lock.before'));
                    $this->clearBootstrapCache($root);
                    if (function_exists('opcache_reset')) {
                        opcache_reset();
                    }
                } catch (Throwable $rollback) {
                    // Manter a página de manutenção e a cópia anterior para recuperação por FTP.
                    $maintenanceCreated = false;

                    return new UpdateResult(false, 'A reposição automática dos ficheiros não terminou. A cópia anterior encontra-se em storage/app/core-updates/'.basename($run).'/previous-vendor. Contacte o responsável pela publicação.');
                }
            }

            return new UpdateResult(false, ($removing === null ? 'Core não atualizado. ' : 'Plugin não removido. ').$error->getMessage().($swapped ? ' Os ficheiros anteriores foram repostos. Alterações já executadas na base de dados não são revertidas automaticamente.' : ' Os ficheiros da instalação foram preservados.'));
        } finally {
            if ($maintenanceCreated) {
                @unlink($maintenance);
            }
            // A cópia anterior fica disponível por FTP. A preparação falhada não é publicada.
            if (is_dir($run.'/stage')) {
                File::deleteDirectory($run.'/stage');
            }
            if (is_dir($run.'/runtime')) {
                File::deleteDirectory($run.'/runtime');
            }
            flock($mutex, LOCK_UN);
            fclose($mutex);
        }
    }

    protected function stageManifest(array $manifest, array $lock): array
    {
        $frozen = array_values(array_filter([...($lock['packages'] ?? []), ...($lock['packages-dev'] ?? [])], fn ($p) => $p['name'] !== self::PACKAGE));
        // Os manifests path atuais podem pertencer a plugins mais recentes que os instalados.
        $repositories = array_values(array_filter($manifest['repositories'] ?? [], fn ($r) => ! is_array($r) || ($r['type'] ?? '') !== 'path'));
        array_unshift($repositories, ['type' => 'package', 'package' => $frozen]);
        $manifest['repositories'] = $repositories;
        unset($manifest['scripts']);

        return $manifest;
    }

    protected function validatePlan(array $before, array $after, string $target, ?string $removing = null): void
    {
        $old = $this->packages([...($before['packages'] ?? []), ...($before['packages-dev'] ?? [])]);
        $new = $this->packages([...($after['packages'] ?? []), ...($after['packages-dev'] ?? [])]);
        if (ltrim($new[self::PACKAGE]['version'] ?? '', 'v') !== $target) {
            throw new RuntimeException('A preparação não selecionou a versão pedida do Core.');
        }
        if ($removing !== null && isset($new[$removing])) {
            throw new RuntimeException('O plugin continua a ser exigido por outro pacote instalado. Desative ou remova primeiro os dependentes.');
        }
        foreach ($old as $name => $package) {
            if ($name !== $removing && ($name !== self::PACKAGE || $removing !== null) && (! isset($new[$name]) || $this->identity($new[$name]) !== $this->identity($package))) {
                throw new RuntimeException('A compatibilidade exige alterar '.$name.'. Esta atualização deve ser preparada separadamente.');
            }
        }
    }

    private function identity(array $package): array
    {
        return [$package['version'] ?? null, $package['source'] ?? null, $package['dist'] ?? null, $package['require'] ?? []];
    }

    /** Instala novamente apenas os pacotes alterados, na cópia privada.
     * Deploys por FTP podem conservar installation-source=source sem conservar .git.
     * O GitDownloader não consegue atualizar essas cópias; os ficheiros reais ficam intactos.
     */
    private function prepareChangedPackages(string $stage, array $installed, array $planned): void
    {
        $new = $this->packages([...($planned['packages'] ?? []), ...($planned['packages-dev'] ?? [])]);
        $kept = [];
        $vendor = realpath($stage.'/vendor');
        if ($vendor === false) {
            throw new RuntimeException('A cópia das dependências não está disponível.');
        }
        foreach ($installed['packages'] ?? $installed as $package) {
            $name = $package['name'] ?? '';
            if (isset($new[$name]) && $this->identity($new[$name]) === $this->identity($package)) {
                $kept[] = $package;

                continue;
            }
            if (! preg_match('~^[a-z0-9_.-]+/[a-z0-9_.-]+$~D', $name)) {
                throw new RuntimeException('Identidade inválida na cópia Composer.');
            }
            $directory = $stage.'/vendor/'.$name;
            if (is_link($directory)) {
                throw new RuntimeException('O pacote preparado contém uma ligação simbólica: '.$name.'.');
            }
            if (is_dir($directory)) {
                $resolved = realpath($directory);
                $normalize = static fn ($path) => PHP_OS_FAMILY === 'Windows' ? strtolower(str_replace('\\', '/', $path)) : str_replace('\\', '/', $path);
                if ($resolved === false || ! str_starts_with($normalize($resolved).'/', $normalize($vendor).'/')) {
                    throw new RuntimeException('O pacote preparado está fora da cópia vendor.');
                }
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
                foreach ($iterator as $file) {
                    if ($file->isLink()) {
                        throw new RuntimeException('O pacote preparado contém uma ligação simbólica: '.$name.'.');
                    }
                    if (PHP_OS_FAMILY === 'Windows' && $file->isFile() && ! is_writable($file->getPathname())) {
                        chmod($file->getPathname(), 0666);
                    }
                }
                unset($iterator,$file);
                File::deleteDirectory($directory);
                clearstatcache();
                if (is_dir($directory)) {
                    throw new RuntimeException('Não foi possível preparar a reinstalação de '.$name.'.');
                }
            }
        }
        if (isset($installed['packages'])) {
            $installed['packages'] = $kept;
        } else {
            $installed = $kept;
        }
        $this->json($stage.'/vendor/composer/installed.json', $installed);
    }

    private function packages(array $packages): array
    {
        $result = [];
        foreach ($packages as $package) {
            $result[$package['name']] = $package;
        }

        return $result;
    }

    /** A pasta usada pelo Composer pode ficar aberta por ferramentas no Windows.
     * Publicar uma cópia dos ficheiros de execução, sem transportar a pasta .git.
     * O próximo update reinstala os pacotes alterados na preparação, também sem .git.
     */
    private function runtimeVendor(string $source, string $target): string
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return $source;
        }
        $this->copyVendor($source, $target, true);

        return $target;
    }

    private function copyVendor(string $source, string $target, bool $withoutGit): void
    {
        File::ensureDirectoryExists($target, 0700);
        $iterator = new \RecursiveIteratorIterator(new \RecursiveCallbackFilterIterator(new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS), static function ($file) use ($source, $withoutGit) {
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($source) + 1));

            // Downloads incompletos do Composer não são ficheiros da instalação.
            return ! ($withoutGit && $file->getFilename() === '.git') && ! preg_match('~^composer/tmp-[a-f0-9]+(?:\.[a-z0-9]+)?(?:/|$)~i', $relative);
        }), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $file) {
            $destination = $target.'/'.substr($file->getPathname(), strlen($source) + 1);
            if ($file->isLink() && $file->isDir()) {
                throw new RuntimeException('A preparação contém uma ligação de pastas que exige uma publicação própria.');
            }
            if ($file->isDir()) {
                File::ensureDirectoryExists($destination);
            } elseif (! File::copy($file->getPathname(), $destination)) {
                throw new RuntimeException('Não foi possível preparar os ficheiros de execução.');
            }
        }
    }

    protected function composer(array $arguments, string $directory): void
    {
        // Resolver o executável sem o cleaner do ComposerCommand, que altera o composer.json real.
        $binary = config('cms-core.updates.composer_binary') ?: (is_file(base_path('composer.phar')) ? base_path('composer.phar') : (new ExecutableFinder)->find(PHP_OS_FAMILY === 'Windows' ? 'composer.bat' : 'composer'));
        if (! is_string($binary) || $binary === '') {
            throw new RuntimeException('O Composer não está disponível no servidor. Publique composer.phar na pasta app.');
        }
        $command = str_ends_with(strtolower($binary), '.phar') ? [(new ComposerCommand)->phpBinary(), $binary] : [$binary];
        $this->process([...$command, ...$arguments], $directory);
    }

    protected function process(array $command, string $directory): void
    {
        $environment = ['COMPOSER_HOME' => storage_path('framework/cache/composer'), 'COMPOSER' => $directory.'/composer.json', 'COMPOSER_VENDOR_DIR' => $directory.'/vendor', 'COMPOSER_NO_INTERACTION' => '1', 'GIT_TERMINAL_PROMPT' => '0', 'GCM_INTERACTIVE' => 'Never'];
        $token = config('cms-core.updates.github_token');
        if (is_string($token) && $token !== '') {
            $environment['COMPOSER_AUTH'] = json_encode(['github-oauth' => ['github.com' => $token]], JSON_THROW_ON_ERROR);
        }
        $process = new Process($command, $directory, $environment);
        $process->setTimeout(300)->run();
        if (! $process->isSuccessful()) {
            $output = trim($process->getErrorOutput()."\n".$process->getOutput());
            if (is_string($token) && $token !== '') {
                $output = str_replace([$token, rawurlencode($token), base64_encode('x-access-token:'.$token)], '[credencial ocultada]', $output);
            }
            $output = preg_replace('/\x1B\[[0-?]*[ -\/]*[@-~]/', '', $output) ?? '';
            $output = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $output) ?? '';
            $output = preg_replace('~(https?://)[^\s/@]+(?::[^\s/@]*)?@~i', '$1[credencial ocultada]@', $output) ?? '';
            $output = preg_replace('/\s+/u', ' ', $output) ?? '';
            throw new RuntimeException('A validação ou instalação falhou: '.mb_substr($output, -2400));
        }
    }

    private function clearBootstrapCache(string $root): void
    {
        foreach (['config', 'packages', 'services', 'events'] as $file) {
            @unlink($root.'/bootstrap/cache/'.$file.'.php');
        }
        foreach (glob($root.'/bootstrap/cache/routes-*.php') ?: [] as $file) {
            @unlink($file);
        }
    }

    private function moveDirectory(string $from, string $to): bool
    {
        gc_collect_cycles();
        clearstatcache();
        // No Windows, o antivírus pode manter um ficheiro recém-criado aberto por instantes.
        for ($attempt = 0; $attempt < (PHP_OS_FAMILY === 'Windows' ? 20 : 1); $attempt++) {
            if (@rename($from, $to)) {
                return true;
            }
            if (PHP_OS_FAMILY === 'Windows') {
                usleep(100000);
            }
        }

        return false;
    }

    private function atomicWrite(string $path, string $bytes): void
    {
        $temporary = $path.'.core-update-'.bin2hex(random_bytes(4));
        if (file_put_contents($temporary, $bytes, LOCK_EX) === false || ! rename($temporary, $path)) {
            throw new RuntimeException('Não foi possível publicar um ficheiro da atualização.');
        }
    }

    private function json(string $path, array $data): void
    {
        File::put($path, $this->encode($data));
    }

    private function encode(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
    }

    /** Algoritmo de Composer\Package\Locker::getContentHash (Composer, licença MIT). */
    private function contentHash(string $json): string
    {
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        $relevant = array_intersect_key($data, array_flip(['name', 'version', 'require', 'require-dev', 'conflict', 'replace', 'provide', 'minimum-stability', 'prefer-stable', 'repositories', 'extra']));
        if (isset($data['config']['platform'])) {
            $relevant['config']['platform'] = $data['config']['platform'];
        }
        if (isset($data['config']['policy'])) {
            $relevant['config']['policy'] = $data['config']['policy'];
        }
        ksort($relevant);

        return md5(json_encode($relevant, JSON_THROW_ON_ERROR));
    }
}
