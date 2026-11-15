<?php
const KEY_HASH = '__KEY_HASH__';
const EXPIRES = 0; // __EXPIRY__
$key=$_POST['chave']??$_GET['chave']??'';
if(!is_string($key)||!hash_equals(KEY_HASH,hash('sha256',$key))||time()>EXPIRES){http_response_code(404);exit('Página não encontrada.');}
header('Content-Type: text/html; charset=UTF-8');header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');
$error=null;$done=false;$needsRepair=false;$mutex=null;
try{
    $root=realpath(__DIR__.'/../app');if(!$root)throw new RuntimeException('Não foi encontrada a pasta app.');
    $installed=json_decode((string)file_get_contents($root.'/vendor/composer/installed.json'),true,flags:JSON_THROW_ON_ERROR);
    $version=null;foreach($installed['packages']??$installed as $package)if(($package['name']??null)==='pcteckserv/cms-core')$version=ltrim($package['version']??'','v');
    $known=json_decode(base64_decode('__KNOWN__'),true,flags:JSON_THROW_ON_ERROR);$expected=$known[$version??'']??null;
    if(!$expected)throw new RuntimeException('Esta reparação só se aplica ao Core 3.1.7 ou 3.1.8.');
    $work=$root.'/storage/app/core-updates';if(!is_dir($work)&&!mkdir($work,0700,true))throw new RuntimeException('A pasta de recuperação não está disponível.');
    $mutex=fopen($work.'/update.lock','c');if(!$mutex||!flock($mutex,LOCK_EX|LOCK_NB))throw new RuntimeException('Existe outra operação Core em curso.');
    $core=realpath($root.'/vendor/pcteckserv/cms-core');if(!$core)throw new RuntimeException('Core instalado não encontrado.');
    $manifest=$core.'/core-release.json';$helper=$core.'/src/Support/ReleaseIntegrity.php';$python=$core.'/resources/icons/build-catalog.py';
    foreach([$manifest,$helper,$python] as $file){$resolved=realpath($file);if(!$resolved||!is_file($resolved)||!str_starts_with(str_replace('\\','/',$resolved),str_replace('\\','/',$core).'/'))throw new RuntimeException('A distribuição está incompleta ou usa uma localização inesperada.');}
    $normalize=static fn($bytes)=>str_replace("\r\n","\n",$bytes);
    $original=file_get_contents($manifest);$manifestHash=hash('sha256',$normalize($original));
    if(!in_array($manifestHash,[$expected['manifest'],$expected['corrected_manifest']],true)||!hash_equals($expected['helper'],hash('sha256',$normalize(file_get_contents($helper)))))throw new RuntimeException('O manifesto ou o verificador diferem da distribuição original. Nenhum ficheiro foi alterado.');
    require_once $helper;$verifier=new Pcteckserv\CmsCore\Support\ReleaseIntegrity();$validation=$verifier->verify($core,$version);
    if(!$validation['valid']&&$validation['errors']!==['Ficheiro diferente da distribuição: resources/icons/build-catalog.py.'])throw new RuntimeException('Existem outras diferenças no Core. A reparação foi recusada sem alterar ficheiros.');
    $pythonBytes=file_get_contents($python);$canonical=$normalize($pythonBytes);
    if(!hash_equals($expected['python_lf'],hash('sha256',$canonical)))throw new RuntimeException('O conteúdo Python não coincide com a distribuição original.');
    $needsRepair=!$validation['valid'];
    $candidate=str_replace($expected['python_crlf'],$expected['python_lf'],$original);
    if($needsRepair&&(!hash_equals($expected['python_lf'],hash('sha256',$pythonBytes))||!hash_equals($expected['corrected_manifest'],hash('sha256',$normalize($candidate)))))throw new RuntimeException('A diferença encontrada não corresponde à correção conhecida do manifesto.');
    if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
        if($needsRepair){
            $backup=$work.'/manifest-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json.before';
            if(file_put_contents($backup,$original,LOCK_EX)===false)throw new RuntimeException('Não foi possível guardar a cópia anterior.');
            $temporary=$manifest.'.integrity-'.bin2hex(random_bytes(6));
            if(file_put_contents($temporary,$candidate,LOCK_EX)===false||!rename($temporary,$manifest))throw new RuntimeException('Não foi possível corrigir o manifesto. O original foi preservado.');
            if(!$verifier->verify($core,$version)['valid']){file_put_contents($manifest,$original,LOCK_EX);throw new RuntimeException('A validação final falhou; o manifesto anterior foi reposto.');}
        }
        $done=true;@unlink(__FILE__);
    }
}catch(Throwable $exception){$error=$exception->getMessage();}
finally{if(is_resource($mutex)){flock($mutex,LOCK_UN);fclose($mutex);}}
$escape=static fn($value)=>htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
?><!doctype html><html lang="pt-PT"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Integridade do Core</title><style>body{font:16px system-ui;background:#f3f5f7;color:#183743;padding:32px}main{max-width:760px;margin:auto;background:white;padding:32px;border-radius:16px}p{line-height:1.6}button{font:inherit;padding:14px 22px}.error{background:#fff0ed;padding:16px}</style><main>
<h1><?= $done?'Integridade confirmada':'Verificar a integridade do Core' ?></h1>
<?php if($error):?><p class="error"><?= $escape($error) ?></p>
<?php elseif($done):?><p>O Core coincide com o manifesto original. Volte a admin/updates, verifique atualizações e atualize para 3.1.9. Não foram alterados PHP, versões, ambiente ou dados.</p><p>Este ficheiro temporário foi eliminado; se continuar no FTP, elimine-o manualmente.</p>
<?php else:?><p><?= $needsRepair?'O Python coincide com a tag original em LF. Concluir guarda uma cópia do manifesto e corrige apenas o hash conhecido deste ficheiro. O código não é alterado.':'A instalação já é íntegra. Concluir apenas elimina este ficheiro temporário.' ?></p><form method="POST"><input type="hidden" name="chave" value="<?= $escape($key) ?>"><button>Concluir verificação</button></form><?php endif;?></main></html>
