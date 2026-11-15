<?php
// Template: os valores são preenchidos pelo exportador, nunca por pedidos HTTP.
const KEY_HASH = '__KEY_HASH__';
const EXPIRES = 0; // __EXPIRY__
$key=$_POST['chave']??$_GET['chave']??'';
if(!is_string($key)||!hash_equals(KEY_HASH,hash('sha256',$key))||time()>EXPIRES){http_response_code(404);exit('Página não encontrada.');}
header('Content-Type: text/html; charset=UTF-8');header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');
$error=null;$done=false;
try{
    $root=realpath(__DIR__.'/../app');
    if(!$root)throw new RuntimeException('Não foi encontrada a pasta app junto a public_html.');
    $installed=json_decode((string)file_get_contents($root.'/vendor/composer/installed.json'),true);
    $version=null;$laravelVersion=null;foreach($installed['packages']??$installed as $package){
        if(($package['name']??null)==='pcteckserv/cms-core')$version=ltrim($package['version']??'','v');
        if(($package['name']??null)==='laravel/framework')$laravelVersion=ltrim($package['version']??'','v');
    }
    if($version!=='2.9.0')throw new RuntimeException('Esta passagem destina-se apenas ao Core 2.9.0. Não concluir noutra versão.');
    $files=json_decode(base64_decode('__FILES__'),true,flags:JSON_THROW_ON_ERROR);
    $core=realpath($root.'/vendor/pcteckserv/cms-core');
    if(!$core)throw new RuntimeException('Não foi encontrado o Core instalado.');
    foreach(['composer.json'=>'__COMPOSER_HASH__','src/CmsCoreServiceProvider.php'=>'__PROVIDER_HASH__'] as $relative=>$hash){
        if(!is_file($core.'/'.$relative)||!hash_equals($hash,hash('sha256',str_replace("\r\n","\n",file_get_contents($core.'/'.$relative)))))throw new RuntimeException('Os ficheiros base do Core diferem da linha 2.9.0. É necessária uma verificação da instalação antes de concluir.');
    }
    $framework=$root.'/vendor/laravel/framework/src/Illuminate/Foundation/Application.php';
    if(!$laravelVersion||!is_file($framework)||!preg_match('/\bconst\s+VERSION\s*=\s*[\'\"]v?([^\'\"]+)[\'\"]/',file_get_contents($framework),$match)||$match[1]!==$laravelVersion)throw new RuntimeException('Os ficheiros e metadados do Laravel não coincidem. É necessária uma verificação da tentativa anterior antes de concluir.');
    foreach($files as $relative=>$hash){
        $file=realpath($core.'/'.$relative);
        if(!$file||!is_file($file)||!str_starts_with(str_replace('\\','/',$file),str_replace('\\','/',$core).'/')
            ||!hash_equals($hash,hash('sha256',str_replace("\r\n","\n",(string)file_get_contents($file)))))throw new RuntimeException('Transferência incompleta ou diferente: '.$relative.'. Termine o envio antes de concluir.');
    }
    if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
        foreach(array_keys($files) as $relative)if(str_ends_with($relative,'.php')&&function_exists('opcache_invalidate'))@opcache_invalidate($core.'/'.$relative,true);
        require_once $core.'/src/Updates/PackageUpdater.php';
        if(!class_exists(Pcteckserv\CmsCore\Updates\CoreReleasePolicy::class,false))throw new RuntimeException('A cache PHP ainda não ativou a proteção. Não atualize enquanto esta verificação não concluir.');
        foreach(glob($root.'/storage/framework/views/*.php')?:[] as $compiled){
            if(!is_link($compiled)&&preg_match('/^[a-f0-9]{40,64}\.php$/D',basename($compiled)))@unlink($compiled);
        }
        $done=true;@unlink(__FILE__);
    }
}catch(Throwable $exception){$error=$exception->getMessage();}
$escape=static fn($value)=>htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
?><!doctype html><html lang="pt-PT"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Proteção do atualizador Core 2.9</title>
<style>body{font:16px system-ui;background:#f3f5f7;color:#183743;margin:0;padding:32px}main{max-width:760px;margin:32px auto;background:white;padding:32px;border-radius:16px}button{background:#183743;color:white;border:0;padding:14px 22px;border-radius:8px;font:inherit}p{line-height:1.6}.error{background:#fff0ed;padding:16px;border-radius:8px}</style>
<main><p>MANUTENÇÃO DO CORE 2.9</p><h1><?= $done?'Proteção do atualizador concluída':'Validar a transferência por FTP' ?></h1>
<?php if($error):?><p class="error" role="alert"><?= $escape($error) ?></p>
<?php elseif($done):?><p>Os ficheiros do atualizador foram validados. Não foi executado Composer nem alterada a versão instalada.</p><p>Volte a <strong>admin/updates</strong>, verifique as atualizações e atualize apenas o Core para <strong>2.9.2</strong>. Core 3 exige uma migração própria.</p><p>Este ficheiro temporário foi eliminado. Se continuar no FTP, elimine-o manualmente.</p>
<?php else:?><p>A transferência dos ficheiros necessários está completa. Concluir invalida a cache destes ficheiros e as views compiladas; não altera .env, Composer ou bases de dados.</p><form method="POST"><input type="hidden" name="chave" value="<?= $escape($key) ?>"><button>Concluir proteção do atualizador</button></form><?php endif;?></main></html>
