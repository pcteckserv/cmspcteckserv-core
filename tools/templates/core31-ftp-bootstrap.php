<?php
const KEY_HASH = '__KEY_HASH__';
const EXPIRES = 0; // __EXPIRY__
$key=$_POST['chave']??$_GET['chave']??'';
if(!is_string($key)||!hash_equals(KEY_HASH,hash('sha256',$key))||time()>EXPIRES){http_response_code(404);exit('Página não encontrada.');}
header('Content-Type: text/html; charset=UTF-8');header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');
$error=null;$done=false;
try{
    $root=realpath(__DIR__.'/../app');if(!$root)throw new RuntimeException('Não foi encontrada a pasta app.');
    $installed=json_decode((string)file_get_contents($root.'/vendor/composer/installed.json'),true,flags:JSON_THROW_ON_ERROR);
    $version=null;$laravel=null;foreach($installed['packages']??$installed as $package){if(($package['name']??null)==='pcteckserv/cms-core')$version=ltrim($package['version']??'','v');if(($package['name']??null)==='laravel/framework')$laravel=ltrim($package['version']??'','v');}
    if($version!=='3.1.1')throw new RuntimeException('Esta passagem só se aplica ao Core 3.1.1.');
    $core=realpath($root.'/vendor/pcteckserv/cms-core');if(!$core||!str_starts_with(str_replace('\\','/',$core),str_replace('\\','/',$root).'/')||is_file($core.'/core-release.json'))throw new RuntimeException('A localização ou distribuição do Core exige uma verificação própria.');
    $hash=static fn($file)=>is_file($file)?hash('sha256',str_replace("\r\n","\n",file_get_contents($file))):'';
    foreach(['composer.json'=>'__COMPOSER_HASH__','src/CmsCoreServiceProvider.php'=>'__PROVIDER_HASH__'] as $file=>$expected)if(!hash_equals($expected,$hash($core.'/'.$file)))throw new RuntimeException('Os ficheiros base não coincidem com Core 3.1.1.');
    $files=json_decode(base64_decode('__FILES__'),true,flags:JSON_THROW_ON_ERROR);
    foreach($files as $file=>$expected){$resolved=realpath($core.'/'.$file);if(!$resolved||!str_starts_with(str_replace('\\','/',$resolved),str_replace('\\','/',$core).'/')||!hash_equals($expected,$hash($resolved)))throw new RuntimeException('Transferência incompleta ou diferente: '.$file.'.');}
    $oldFiles=json_decode(base64_decode('__OLD_FILES__'),true,flags:JSON_THROW_ON_ERROR);$newFiles=json_decode(base64_decode('__PATCH_FILES__'),true,flags:JSON_THROW_ON_ERROR);
    foreach($newFiles as $file=>$expected){if(!in_array($hash($core.'/'.$file),[$oldFiles[$file],$expected],true))throw new RuntimeException('O atualizador instalado difere da versão esperada: '.$file.'.');$payload=$root.'/bootstrap/updates/core31-'.basename($file).'.payload';if(!hash_equals($expected,$hash($payload)))throw new RuntimeException('O payload não foi enviado ou foi alterado: '.$file.'.');}
    $framework=$root.'/vendor/laravel/framework/src/Illuminate/Foundation/Application.php';
    if(!$laravel||!is_file($framework)||!preg_match('/\bconst\s+VERSION\s*=\s*[\'\"]v?([^\'\"]+)[\'\"]/',file_get_contents($framework),$match)||$match[1]!==$laravel)throw new RuntimeException('Os ficheiros e metadados Laravel não coincidem.');
    if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
        $work=$root.'/storage/app/core-updates';if(!is_dir($work)&&!mkdir($work,0700,true))throw new RuntimeException('A pasta de recuperação não está disponível.');$backups=[];
        foreach($newFiles as $file=>$expected)if($hash($core.'/'.$file)!==$expected){$bytes=file_get_contents($core.'/'.$file);$backups[$file]=$bytes;if(file_put_contents($work.'/'.basename($file).'-311-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.before',$bytes,LOCK_EX)===false)throw new RuntimeException('Não foi possível preservar os ficheiros anteriores.');}
        try{foreach($newFiles as $file=>$expected){$destination=$core.'/'.$file;$payload=$root.'/bootstrap/updates/core31-'.basename($file).'.payload';if($hash($destination)===$expected)continue;$temporary=$destination.'.bootstrap-'.bin2hex(random_bytes(6));if(file_put_contents($temporary,file_get_contents($payload),LOCK_EX)===false||!rename($temporary,$destination))throw new RuntimeException('Não foi possível publicar a proteção.');}}
        catch(Throwable $failure){$restored=true;foreach($backups as $file=>$bytes){if($hash($core.'/'.$file)!==hash('sha256',str_replace("\r\n","\n",$bytes)))file_put_contents($core.'/'.$file,$bytes,LOCK_EX);if($hash($core.'/'.$file)!==hash('sha256',str_replace("\r\n","\n",$bytes)))$restored=false;}throw new RuntimeException($restored?'A publicação não terminou; os ficheiros anteriores foram repostos.':'A publicação e a reposição não terminaram. Não atualize: recupere a cópia em storage/app/core-updates.');}
        foreach([...array_keys($files),...array_keys($newFiles)] as $file)if(function_exists('opcache_invalidate'))@opcache_invalidate($core.'/'.$file,true);
        foreach(array_keys($newFiles) as $file){$class='Pcteckserv\\CmsCore\\Updates\\'.pathinfo($file,PATHINFO_FILENAME);if(!class_exists($class,false))require_once $core.'/'.$file;if(!defined($class.'::CMS_STAGED_CORE_BOOTSTRAP'))throw new RuntimeException('A cache PHP ainda não ativou a proteção. Não atualize enquanto esta verificação não concluir.');}
        $done=true;foreach(array_keys($newFiles) as $file)@unlink($root.'/bootstrap/updates/core31-'.basename($file).'.payload');@unlink(__FILE__);
    }
}catch(Throwable $exception){$error=$exception->getMessage();}
$escape=static fn($value)=>htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
?><!doctype html><html lang="pt-PT"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Proteger atualização Core</title><style>body{font:16px system-ui;background:#f3f5f7;color:#183743;padding:32px}main{max-width:760px;margin:auto;background:white;padding:32px;border-radius:16px}p{line-height:1.6}button{font:inherit;padding:14px 22px}.error{background:#fff0ed;padding:16px}</style><main><h1><?= $done?'Atualizador protegido':'Validar proteção Core 3.1.1' ?></h1>
<?php if($error):?><p class="error"><?= $escape($error) ?></p>
<?php elseif($done):?><p>A proteção foi instalada, sem executar Composer nem alterar versões. Volte a admin/updates, verifique atualizações e atualize primeiro o Core para 3.1.9. Depois atualize os plugins ativos.</p><p>O finalizador e os payloads foram eliminados; se continuarem no FTP, elimine-os manualmente.</p>
<?php else:?><p>Os ficheiros foram validados. Concluir guarda uma cópia do atualizador e ativa a preparação isolada do Core.</p><form method="POST"><input type="hidden" name="chave" value="<?= $escape($key) ?>"><button>Concluir proteção</button></form><?php endif;?></main></html>
