<?php

// Exporta a correção genérica para instalações que ainda carregam a ponte 3.1.6.
$root = dirname(__DIR__);
$output = $argv[1] ?? '';
if ($output === '' || file_exists($output) || !is_dir(dirname($output))) {
    fwrite(STDERR, "Indique um ficheiro ZIP novo numa pasta existente.\n"); exit(1);
}
if (!class_exists(ZipArchive::class)) throw new RuntimeException('É necessária a extensão PHP ZIP.');
$release = json_decode(file_get_contents($root.'/core-release.json'), true, flags: JSON_THROW_ON_ERROR);
$version = $release['version'] ?? '';
if (!is_string($version) || !preg_match('/^\d+\.\d+\.\d+$/D',$version)) throw new RuntimeException('Versão da distribuição inválida.');
require_once $root.'/src/Support/ReleaseIntegrity.php';
(new Pcteckserv\CmsCore\Support\ReleaseIntegrity())->assertValid($root,$version);
$bytes = file_get_contents($root.'/src/Updates/InstalledComposerSnapshot.php');
$hash = hash('sha256',$bytes);
$readme = <<<TEXT
PONTE DO ATUALIZADOR — CORE {$version}

Destina-se ao CGLimpezas com a ponte 3.1.6 em bootstrap/updates, enquanto o
Core antigo ainda não contém ComposerDeploymentTransaction. Não é um Starter
novo nem uma cópia completa do Core. Não altera versões, .env ou Composer.

1. Pelo FTP, guarde uma cópia do ficheiro atual:
   app/bootstrap/updates/InstalledComposerSnapshot.php
2. Envie o ficheiro com o mesmo caminho deste ZIP, substituindo apenas esse
   ficheiro na pasta app do servidor. Use transferência binária.
3. No painel, abra Atualizações e prima Verificar atualizações.
4. Atualize primeiro o Core para {$version}. Só depois tente remover o plugin
   desativado. Formulários 2.0.0 exige Core ^3.1.0.

O ficheiro exportado é exatamente o helper genérico do Core {$version}.
Procura install-path do Composer e as localizações vendor e packages dentro
da aplicação. Recusa identidades diferentes, origens externas e ambiguidades.
Não executa uma atualização apenas por ser enviado por FTP.

SHA256 do ficheiro PHP: {$hash}

Se já estiver instalado Core 3.1.6 ou superior, a classe pode ser carregada
do vendor e esta ponte pode não ser utilizada. Este pacote não substitui
ficheiros do vendor. Confirme a versão e o carregamento antes de o aplicar.

TEXT;
$zip = new ZipArchive();
if ($zip->open($output,ZipArchive::CREATE|ZipArchive::EXCL) !== true) throw new RuntimeException('Não foi possível criar o ZIP.');
try {
    if (!$zip->addFromString('app/bootstrap/updates/InstalledComposerSnapshot.php',$bytes)
        || !$zip->addFromString('LEIA-ME.txt',$readme)
        || !$zip->addFromString('SHA256.txt',$hash."  app/bootstrap/updates/InstalledComposerSnapshot.php\n")) throw new RuntimeException('Não foi possível preparar a ponte.');
} finally { $zip->close(); }
echo "Ponte preparada; nenhum ficheiro de site foi alterado.\n";
