<?php

function coreReadGitFile(string $root,string $tag,string $file): string
{
    $process=proc_open(['git','show',$tag.':'.$file],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root);
    if(!is_resource($process))throw new RuntimeException('Não foi possível ler a tag local.');
    fclose($pipes[0]);$bytes=stream_get_contents($pipes[1]);fclose($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[2]);
    if(proc_close($process)!==0)throw new RuntimeException('A tag necessária não está disponível neste checkout.');
    return str_replace("\r\n","\n",$bytes);
}

function coreFtpExportArguments(array $argv): array
{
    $output=$argv[1]??'';$url=rtrim($argv[2]??'','/');
    if($output===''||file_exists($output)||!is_dir(dirname($output))||!filter_var($url,FILTER_VALIDATE_URL)||parse_url($url,PHP_URL_SCHEME)!=='https')throw new RuntimeException('Indique um ZIP novo e o URL HTTPS do site.');
    return [$output,$url];
}
