<?php

declare(strict_types=1);
use Pcteckserv\CmsCore\Support\ReleaseIntegrity;

// Exportação privada da transação genérica para instalações cujo atualizador bloqueia snapshots.
$output = $argv[1] ?? '';
$site = $argv[2] ?? '';
$composer = $argv[3] ?? '';
$target = $argv[4] ?? '3.5.1';
if (! in_array($site, ['maxihome', 'cglimpezas'], true) || $output === '' || file_exists($output) || ! is_file($composer) || ! preg_match('/^3\.\d+\.\d+$/D', $target)) {
    throw new RuntimeException('Indique ZIP novo, maxihome/cglimpezas, composer.phar e versão Core 3.');
}
$root = dirname(__DIR__);
require_once $root.'/src/Support/ReleaseIntegrity.php';
(new ReleaseIntegrity)->assertValid($root, $target);
$id = $site.'-'.date('Ymd-His').'-'.bin2hex(random_bytes(6));
$key = bin2hex(random_bytes(32));
$filename = 'atualizar-core-'.bin2hex(random_bytes(10)).'.php';
$prefix = 'app/storage/app/core-bootstrap/'.$id.'/';
$files = [];
$payload = [];
$old = [];
foreach (['src/Updates/CoreReleasePolicy.php', 'src/Support/ReleaseIntegrity.php', 'src/Updates/InstalledComposerSnapshot.php', 'src/Updates/UpdateResult.php', 'src/Support/ComposerCommand.php', 'src/Updates/ComposerDeploymentTransaction.php'] as $file) {
    $bytes = str_replace("\r\n", "\n", file_get_contents($root.'/'.$file));
    $payload[$file] = $bytes;
    $files[$file] = hash('sha256', $bytes);
}
$payload['composer.phar'] = file_get_contents($composer);
$files['composer.phar'] = hash('sha256', $payload['composer.phar']);
// O gerador funciona também fora de uma aplicação Laravel/Composer.
$lines = [];
$exit = 0;
exec('git -C '.escapeshellarg($root).' tag --list '.escapeshellarg('v3.*'), $lines, $exit);
if ($exit !== 0) {
    throw new RuntimeException('Não foi possível enumerar as versões publicadas.');
}
foreach ($lines as $tag) {
    if (! preg_match('/^v(3\.\d+\.\d+)$/D', $tag, $match) || version_compare($match[1], '3.1.9', '<') || version_compare($match[1], $target, '>=')) {
        continue;
    }
    $temporary = sys_get_temp_dir().'/core-old-'.bin2hex(random_bytes(10)).'.zip';
    exec('git -C '.escapeshellarg($root).' -c core.autocrlf=false archive --format=zip -o '.escapeshellarg($temporary).' '.escapeshellarg($tag), $unused, $exit);
    if ($exit !== 0 || ! is_file($temporary)) {
        throw new RuntimeException('Não foi possível preparar uma distribuição anterior.');
    }
    $relative = 'old-core-'.$match[1].'.zip';
    $payload[$relative] = file_get_contents($temporary);
    unlink($temporary);
    $files[$relative] = hash('sha256', $payload[$relative]);
    $old[$match[1]] = $relative;
}
$script = str_replace(['__KEY_HASH__', '__EXPIRES__', '__ID__', '__PROJECT__', '__TARGET__', '__FILES__', '__OLD_ARCHIVES__'], [hash('sha256', $key), (string) (time() + 172800), $id, 'pcteckserv/'.$site, $target, base64_encode(json_encode($files, JSON_THROW_ON_ERROR)), base64_encode(json_encode($old, JSON_THROW_ON_ERROR))], file_get_contents(__DIR__.'/templates/snapshot-core-bootstrap.php'));
$zip = new ZipArchive;
if ($zip->open($output, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
    throw new RuntimeException('Não foi possível criar o ZIP.');
}
try {
    foreach ($payload as $file => $bytes) {
        $zip->addFromString($prefix.$file, $bytes);
    }
    $zip->addFromString('public_html/'.$filename, $script);
    $zip->addFromString('ACESSO-PRIVADO.txt', 'https://'.$site.'.pt/'.$filename.'?chave='.$key."\n\nEndereço privado, válido durante 48 horas. Não partilhe nem publique.\n");
    $zip->addFromString('LEIA-ME.txt', 'PASSAGEM DO ATUALIZADOR — '.strtoupper($site)."\n\n1. Guarde pelo FTP uma cópia da pasta app/vendor/pcteckserv/cms-core e dos ficheiros app/composer.json e app/composer.lock.\n2. Extraia este ZIP e envie app e public_html para a pasta que já contém essas duas pastas. Não crie app/app. Nenhum ficheiro existente do Core é substituído durante o envio.\n3. Abra o endereço COMPLETO de ACESSO-PRIVADO.txt, incluindo a chave. Abrir apenas o nome do PHP apresenta 404.\n4. Confirme o website e as versões e carregue em Preparar e atualizar o Core. Aguarde a confirmação; não inicie outras atualizações em simultâneo.\n5. No painel, abra Sistema → Atualizações e atualize o Starter e depois os plugins pretendidos.\n\nA operação usa a transação comum do Core: congela as dependências instaladas, prepara a versão publicada $target numa pasta privada, valida os ficheiros e troca a instalação. Laravel e plugins não mudam de versão. Não altera .env, media, documentos ou contas. Uma falha na publicação repõe os ficheiros anteriores; migrations já executadas não são desfeitas.\n\nSe os ficheiros do Core não corresponderem a uma versão declarada incluída no pacote, valida essa MESMA versão numa distribuição canónica privada. A nova versão só substitui os ficheiros atuais depois de preparar também as dependências; numa falha, a instalação original é preservada. Versões desconhecidas ou incompatibilidades são recusadas.\n\nAs cópias ficam em app/storage/app/core-updates e app/storage/app/core-bootstrap/$id. Conserve-as até confirmar o funcionamento. O finalizador e os ficheiros de envio eliminam-se após sucesso. Se não existir Composer local, instala composer.phar em app para permitir as atualizações seguintes. O acesso expira em 48 horas.\n");
} finally {
    $zip->close();
}
echo json_encode(['created' => true, 'site' => $site, 'target' => $target, 'payload_files' => count($files), 'previous_releases' => count($old)]).PHP_EOL;
