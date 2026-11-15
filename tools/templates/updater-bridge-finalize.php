<?php
const KEY_HASH = '__KEY_HASH__';
const EXPIRES = 0; // __EXPIRY__
$key=$_POST['chave']??$_GET['chave']??'';
if(!is_string($key)||!hash_equals(KEY_HASH,hash('sha256',$key))||time()>EXPIRES){http_response_code(404);exit('Página não encontrada.');}
header('Content-Type: text/html; charset=UTF-8'); header('Cache-Control: no-store'); header('Referrer-Policy: no-referrer');
$error=null; $done=false;
try{
    $root=realpath(__DIR__.'/../app');
    if(!$root)throw new RuntimeException('Não foi encontrada a pasta app junto a public_html.');
    $installed=json_decode((string)file_get_contents($root.'/vendor/composer/installed.json'),true,flags:JSON_THROW_ON_ERROR);
    $version=null; foreach($installed['packages']??$installed as $package)if(($package['name']??null)==='pcteckserv/cms-core')$version=ltrim($package['version']??'','v');
    if($version!=='3.0.1'||is_file($root.'/vendor/pcteckserv/cms-core/src/Updates/ComposerDeploymentTransaction.php'))throw new RuntimeException('Esta ponte destina-se apenas ao Core 3.0.1 sem transação própria.');
    $loader=$root.'/app/Updates/SiteComposerTransaction.php';
    if(!is_file($loader)||!str_contains(file_get_contents($loader),'bootstrap/updates/ComposerDeploymentTransaction.php'))throw new RuntimeException('A instalação não tem o carregador da ponte esperado.');
    $files=json_decode(base64_decode('__FILES__'),true,flags:JSON_THROW_ON_ERROR);
    $bridge=realpath($root.'/bootstrap/updates');
    if(!$bridge)throw new RuntimeException('Não foi encontrada a pasta da ponte.');
    foreach($files as $relative=>$hash){
        $file=realpath($bridge.'/'.$relative);
        if(!$file||!is_file($file)||!str_starts_with(str_replace('\\','/',$file),str_replace('\\','/',$bridge).'/')||!hash_equals($hash,hash('sha256',str_replace("\r\n","\n",file_get_contents($file)))))throw new RuntimeException('Transferência incompleta ou diferente: '.$relative.'.');
    }
    if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
        foreach(array_keys($files) as $file)if(function_exists('opcache_invalidate'))@opcache_invalidate($bridge.'/'.$file,true);
        $done=true; @unlink(__FILE__);
    }
}catch(Throwable $exception){$error=$exception->getMessage();}
$escape=static fn($value)=>htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
?><!doctype html><html lang="pt-PT"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Validar ponte do Core</title>
<style>body{font:16px system-ui;background:#f3f5f7;color:#183743;padding:32px}main{max-width:760px;margin:32px auto;background:white;padding:32px;border-radius:16px}p{line-height:1.6}button{font:inherit;padding:14px 22px}.error{background:#fff0ed;padding:16px}</style><main>
<h1><?= $done?'Ponte validada':'Validar a transferência por FTP' ?></h1>
<?php if($error):?><p class="error" role="alert"><?= $escape($error) ?></p>
<?php elseif($done):?><p>A cache da ponte foi invalidada. Não foi executado Composer nem alterada a versão instalada.</p><p>Verifique as atualizações no painel e atualize primeiro o Core para __RELEASE__. Só depois atualize os plugins.</p><p>Este ficheiro temporário foi eliminado; se continuar no FTP, elimine-o manualmente.</p>
<?php else:?><p>Os quatro ficheiros estão completos. Concluir invalida apenas a cache desses ficheiros.</p><form method="POST"><input type="hidden" name="chave" value="<?= $escape($key) ?>"><button>Concluir validação da ponte</button></form><?php endif;?></main></html>
