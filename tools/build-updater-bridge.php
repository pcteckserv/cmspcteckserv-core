<?php

// Exporta o mesmo atualizador genérico para a ponte FTP já existente em Core 3.0.1.
$root=dirname(__DIR__); $output=$argv[1]??''; $baseUrl=rtrim($argv[2]??'','/');
if($output===''||file_exists($output)||!is_dir(dirname($output))||!filter_var($baseUrl,FILTER_VALIDATE_URL)||parse_url($baseUrl,PHP_URL_SCHEME)!=='https')throw new RuntimeException('Indique um ZIP novo e o URL HTTPS do site.');
require_once $root.'/src/Support/ReleaseIntegrity.php';
$release=json_decode(file_get_contents($root.'/core-release.json'),true,flags:JSON_THROW_ON_ERROR);
$version=$release['version']??'';
(new Pcteckserv\CmsCore\Support\ReleaseIntegrity())->assertValid($root,$version);
$files=['InstalledComposerSnapshot.php'=>'src/Updates/InstalledComposerSnapshot.php','CoreReleasePolicy.php'=>'src/Updates/CoreReleasePolicy.php','ReleaseIntegrity.php'=>'src/Support/ReleaseIntegrity.php','ComposerDeploymentTransaction.php'=>'src/Updates/ComposerDeploymentTransaction.php'];
$hashes=[]; $key=bin2hex(random_bytes(32)); $name='concluir-ponte-core-'.bin2hex(random_bytes(12)).'.php';
$zip=new ZipArchive(); if($zip->open($output,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('Não foi possível criar o pacote.');
try{
    foreach($files as $target=>$source){
        $bytes=str_replace("\r\n","\n",file_get_contents($root.'/'.$source)); $hashes[$target]=hash('sha256',$bytes);
        $zip->addFromString('app/bootstrap/updates/'.$target,$bytes);
    }
    $template=str_replace(['__KEY_HASH__','const EXPIRES = 0; // __EXPIRY__','__FILES__','__RELEASE__'],[hash('sha256',$key),'const EXPIRES = '.(time()+172800).';',base64_encode(json_encode($hashes,JSON_THROW_ON_ERROR)),$version],file_get_contents($root.'/tools/templates/updater-bridge-finalize.php'));
    $zip->addFromString('public_html/'.$name,$template);
    $zip->addFromString('ACESSO-PRIVADO.txt',$baseUrl.'/'.$name.'?chave='.$key."\n");
    $zip->addFromString('HASHES.json',json_encode($hashes,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
    $zip->addFromString('LEIA-ME.txt',<<<TEXT
PONTE FTP DO ATUALIZADOR — CORE {$version}

APENAS para uma instalação Core 3.0.1 que já tenha a ponte em
app/Updates/SiteComposerTransaction.php e app/bootstrap/updates.
Não aplicar ao StudioFranco Core 2.9 nem a um Core que já tenha a transação.

1. Guarde uma cópia de app/bootstrap/updates pelo FTP.
2. Envie os QUATRO ficheiros de app/bootstrap/updates deste ZIP para o mesmo
   caminho do servidor. Transfira ComposerDeploymentTransaction.php por último.
3. Envie o ficheiro de public_html e abra o endereço de ACESSO-PRIVADO.txt.
   Conclua a validação antes de tentar atualizar. O acesso expira em 48 horas.
4. No painel, verifique atualizações e atualize primeiro o Core para {$version}.
5. Só depois atualize os plugins ativos. Formulários 2 exige Core ^3.1.0.

Os quatro ficheiros são os originais genéricos desta distribuição do Core.
A ponte não altera Starter, .env, Composer, versões declaradas ou dados.
O finalizador valida o envio, invalida a cache desses ficheiros e elimina-se.
A atualização só é executada posteriormente pelo painel.

O atualizador prepara as alterações numa cópia privada. Preserva as versões
instaladas dos restantes pacotes, recupera plugins em packages e reinstala
na preparação os pacotes alterados, incluindo cópias Git sem .git após FTP.
Não reverte alterações parciais de tentativas antigas. Guarde o backup do site.

TEXT);
}finally{$zip->close();}
echo "Ponte completa preparada; nenhum ficheiro de site foi alterado.\n";
