<?php

require_once __DIR__.'/ftp-package-support.php';
$root=dirname(__DIR__);[$output,$url]=coreFtpExportArguments($argv);
require_once $root.'/src/Support/ReleaseIntegrity.php';
(new Pcteckserv\CmsCore\Support\ReleaseIntegrity())->assertValid($root,'3.1.9');
$baseVersion='3.1.1';$original=coreReadGitFile($root,'v'.$baseVersion,'src/Updates/PackageUpdater.php');
$needle="    public function update(string \$package): UpdateResult\n    {\n";
if(substr_count($original,$needle)!==1||!str_contains($original,'function availableVersion(string $package): ?string'))throw new RuntimeException('O atualizador da tag não tem o contrato esperado.');
$injection=<<<'PHP'
        // Passagem temporária: o Core é preparado fora da instalação; os outros fluxos mantêm-se.
        if ($package === 'pcteckserv/cms-core') {
            foreach ([
                InstalledComposerSnapshot::class=>__DIR__.'/InstalledComposerSnapshot.php',
                ComposerDeploymentTransaction::class=>__DIR__.'/ComposerDeploymentTransaction.php',
            ] as $class=>$file) if (!class_exists($class,false)) require_once $file;
            return app(ComposerDeploymentTransaction::class)->update($this->availableVersion($package));
        }

PHP;
$patched=str_replace($needle,$needle.$injection,$original);
$payloads=['src/Updates/InstalledPackage.php'=>str_replace("\r\n","\n",file_get_contents($root.'/src/Updates/InstalledPackage.php')),'src/Updates/GitTagUpdateChecker.php'=>str_replace("\r\n","\n",file_get_contents($root.'/src/Updates/GitTagUpdateChecker.php')),'src/Updates/PackageVersionRegistry.php'=>str_replace("\r\n","\n",file_get_contents($root.'/src/Updates/PackageVersionRegistry.php')),'src/Updates/PackageUpdater.php'=>$patched];
$oldHashes=[];$newHashes=[];
foreach($payloads as $file=>&$bytes){$class=pathinfo($file,PATHINFO_FILENAME);$marker="class ".$class."\n{\n";if(substr_count($bytes,$marker)!==1)throw new RuntimeException('Contrato de classe inesperado.');$bytes=str_replace($marker,$marker."    public const CMS_STAGED_CORE_BOOTSTRAP = '3.1.9';\n",$bytes);$oldHashes[$file]=hash('sha256',coreReadGitFile($root,'v'.$baseVersion,$file));$newHashes[$file]=hash('sha256',$bytes);}unset($bytes);
$files=['src/Updates/CoreReleasePolicy.php','src/Updates/InstalledComposerSnapshot.php','src/Updates/ComposerDeploymentTransaction.php','src/Support/ReleaseIntegrity.php'];
$key=bin2hex(random_bytes(32));$name='proteger-core31-'.bin2hex(random_bytes(12)).'.php';$hashes=[];
$zip=new ZipArchive();if($zip->open($output,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('Não foi possível criar o ZIP.');
try{
    foreach($files as $file){$bytes=str_replace("\r\n","\n",file_get_contents($root.'/'.$file));$hashes[$file]=hash('sha256',$bytes);$zip->addFromString('app/vendor/pcteckserv/cms-core/'.$file,$bytes);}
    foreach($payloads as $file=>$bytes)$zip->addFromString('app/bootstrap/updates/core31-'.basename($file).'.payload',$bytes);
    $script=str_replace(['__KEY_HASH__','const EXPIRES = 0; // __EXPIRY__','__FILES__','__COMPOSER_HASH__','__PROVIDER_HASH__','__OLD_FILES__','__PATCH_FILES__'],[hash('sha256',$key),'const EXPIRES = '.(time()+172800).';',base64_encode(json_encode($hashes,JSON_THROW_ON_ERROR)),hash('sha256',coreReadGitFile($root,'v'.$baseVersion,'composer.json')),hash('sha256',coreReadGitFile($root,'v'.$baseVersion,'src/CmsCoreServiceProvider.php')),base64_encode(json_encode($oldHashes,JSON_THROW_ON_ERROR)),base64_encode(json_encode($newHashes,JSON_THROW_ON_ERROR))],file_get_contents(__DIR__.'/templates/core31-ftp-bootstrap.php'));
    $zip->addFromString('public_html/'.$name,$script);
    $zip->addFromString('ACESSO-PRIVADO.txt',$url.'/'.$name.'?chave='.$key."\n");
    $zip->addFromString('LEIA-ME.txt',<<<TEXT
PROTEÇÃO DO ATUALIZADOR — APENAS CORE 3.1.1

Usar no Turkesa se ainda estiver em Core 3.1.1. Não usar o atualizador antigo
antes desta proteção: esse código pode chamar Composer com -W.

1. Guarde pelo FTP uma cópia de app/vendor/pcteckserv/cms-core.
2. Envie os quatro helpers e os quatro payloads, respeitando os caminhos app.
   Nenhum PackageUpdater.php existente é substituído durante este envio.
3. Envie o PHP de public_html e abra o endereço de ACESSO-PRIVADO.txt.
4. Conclua a proteção. O script valida a instalação 3.1.1, guarda o atualizador
   anterior e instala a passagem do ramo Core para a transação genérica, a
   seleção de versões estáveis e o registo de atualizações sem cache incompatível.
5. Verifique atualizações no painel e atualize primeiro o Core para 3.1.9.

O verificador recusa outra versão, ficheiros base diferentes, payload alterado
e incoerência de ficheiros/metadados Laravel. Não muda .env, versões declaradas,
manifestos Composer ou dados e não executa Composer. O mesmo algoritmo genérico
é usado nesta passagem e no Core novo; não é mantido um atualizador no Starter.
O acesso expira em 48 horas. O finalizador e os payloads eliminam-se ao concluir.
Se ainda existirem depois de concluir, elimine-os pelo FTP.
TEXT);
}finally{$zip->close();}
echo "Passagem Core 3.1.1 preparada; nenhum site foi alterado.\n";
