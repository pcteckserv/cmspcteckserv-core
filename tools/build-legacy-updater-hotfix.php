<?php

$root=dirname(__DIR__);$output=$argv[1]??'';$baseUrl=rtrim($argv[2]??'','/');
if($output===''||file_exists($output)||!is_dir(dirname($output))||!filter_var($baseUrl,FILTER_VALIDATE_URL)||parse_url($baseUrl,PHP_URL_SCHEME)!=='https')throw new RuntimeException('Indique um ZIP novo e o URL HTTPS do site.');
require_once $root.'/src/Support/ReleaseIntegrity.php';
(new Pcteckserv\CmsCore\Support\ReleaseIntegrity())->assertValid($root,'2.9.2');
$groups=[
    '1-novos'=>['src/Updates/CoreReleasePolicy.php','src/Updates/ComposerDeploymentTransaction.php','src/Updates/InstalledComposerSnapshot.php','src/Support/ReleaseIntegrity.php'],
    '2-proteger-atualizacao'=>['src/Updates/PackageUpdater.php'],
    '3-painel'=>['src/Updates/GitTagUpdateChecker.php','src/Updates/PackageVersionRegistry.php','src/Updates/InstalledPackage.php','resources/views/admin/updates/index.blade.php','resources/views/admin/updates/partials/packages-table.blade.php'],
];
$hashes=[];$key=bin2hex(random_bytes(32));$name='concluir-core-29-'.bin2hex(random_bytes(12)).'.php';
$zip=new ZipArchive();if($zip->open($output,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('Não foi possível criar o pacote.');
try{
    foreach($groups as $group=>$files)foreach($files as $file){$bytes=str_replace("\r\n","\n",file_get_contents($root.'/'.$file));$hashes[$file]=hash('sha256',$bytes);$zip->addFromString($group.'/app/vendor/pcteckserv/cms-core/'.$file,$bytes);}
    $template=file_get_contents($root.'/tools/templates/legacy-updater-finalize.php');
    $template=str_replace(['__KEY_HASH__','const EXPIRES = 0; // __EXPIRY__','__FILES__','__COMPOSER_HASH__','__PROVIDER_HASH__'],[hash('sha256',$key),'const EXPIRES = '.(time()+172800).';',base64_encode(json_encode($hashes,JSON_THROW_ON_ERROR)),hash('sha256',str_replace("\r\n","\n",file_get_contents($root.'/composer.json'))),hash('sha256',str_replace("\r\n","\n",file_get_contents($root.'/src/CmsCoreServiceProvider.php')))],$template);
    $zip->addFromString('4-concluir/public_html/'.$name,$template);
    $readme=<<<TEXT
PROTEÇÃO DO ATUALIZADOR — APENAS CORE 2.9.0

Este pacote contém uma passagem temporária do atualizador do Core. Não é um
Starter e não substitui o Core completo. Não altera .env, composer.json,
composer.lock, plugins, versões declaradas ou dados. Não aplicar a Core 2.9.1
ou superior: essas versões podem ter manifesto de integridade próprio.

1. Guarde pelo FTP uma cópia de app/vendor/pcteckserv/cms-core.
2. Envie o conteúdo de 1-novos para as pastas correspondentes do alojamento.
3. Envie 2-proteger-atualizacao. Só depois envie 3-painel.
4. Envie o ficheiro de 4-concluir/public_html para public_html.
5. Abra o endereço de ACESSO-PRIVADO.txt e clique em Concluir proteção.
   Não tente atualizar o Core antes de concluir este passo.
6. Em admin/updates, prima Verificar atualizações e atualize APENAS o Core
   para 2.9.2. A versão continua 2.9.0 até esta instalação terminar.

O finalizador valida os ficheiros e invalida a cache, sem executar comandos.
O acesso expira em 48 horas; o finalizador elimina-se após sucesso. Se a
validação falhar, não execute a atualização. A seleção Core 3 é bloqueada.

O novo atualizador prepara a instalação separadamente e congela as versões
efetivamente instaladas do Laravel e dos plugins. Não reverte alterações
parciais que uma tentativa antiga já tenha efetuado.

Não copie as pastas numeradas para dentro de app: copie o seu CONTEÚDO,
respeitando a ordem acima e os caminhos app e public_html apresentados.
TEXT;
    $zip->addFromString('LEIA-ME.txt',$readme);
    $zip->addFromString('ACESSO-PRIVADO.txt',$baseUrl.'/'.$name.'?chave='.$key."\n");
    $zip->addFromString('HASHES.json',json_encode($hashes,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
}finally{$zip->close();}
echo "Pacote preparado; os ficheiros dos sites não foram alterados. O acesso privado está apenas dentro do ZIP.\n";
