<?php

require_once __DIR__.'/ftp-package-support.php';
$root=dirname(__DIR__);[$output,$url]=coreFtpExportArguments($argv);$known=[];
foreach(['3.1.7','3.1.8'] as $version){
    $manifest=coreReadGitFile($root,'v'.$version,'core-release.json');$data=json_decode($manifest,true,flags:JSON_THROW_ON_ERROR);
    $pythonLf=hash('sha256',coreReadGitFile($root,'v'.$version,'resources/icons/build-catalog.py'));$pythonCrlf=$data['files']['resources/icons/build-catalog.py'];
    if(substr_count($manifest,$pythonCrlf)!==1)throw new RuntimeException('O hash Python no manifesto não é único.');
    $known[$version]=['manifest'=>hash('sha256',$manifest),'corrected_manifest'=>hash('sha256',str_replace($pythonCrlf,$pythonLf,$manifest)),'helper'=>$data['files']['src/Support/ReleaseIntegrity.php'],'python_crlf'=>$pythonCrlf,'python_lf'=>$pythonLf];
}
$key=bin2hex(random_bytes(32));$name='alinhar-integridade-core-'.bin2hex(random_bytes(12)).'.php';
$script=str_replace(['__KEY_HASH__','const EXPIRES = 0; // __EXPIRY__','__KNOWN__'],[hash('sha256',$key),'const EXPIRES = '.(time()+172800).';',base64_encode(json_encode($known,JSON_THROW_ON_ERROR))],file_get_contents(__DIR__.'/templates/integrity-ftp-repair.php'));
$zip=new ZipArchive();if($zip->open($output,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('Não foi possível criar o ZIP.');
try{
    $zip->addFromString('public_html/'.$name,$script);
    $zip->addFromString('ACESSO-PRIVADO.txt',$url.'/'.$name.'?chave='.$key."\n");
    $zip->addFromString('LEIA-ME.txt',<<<TEXT
COERÊNCIA DE INTEGRIDADE — APENAS CORE 3.1.7 OU 3.1.8

Usar se a atualização parar por diferença em resources/icons/build-catalog.py.
Este ficheiro Python é um utilitário de desenvolvimento. Os manifestos destas
versões guardaram o hash com CRLF, enquanto o Git fornece o mesmo texto em LF.

1. Guarde uma cópia da pasta app/vendor/pcteckserv/cms-core pelo FTP.
2. Envie apenas o PHP deste ZIP para public_html.
3. Abra o endereço de ACESSO-PRIVADO.txt e confirme o alinhamento.
4. Depois verifique atualizações no painel e atualize o Core para 3.1.9.

O script valida a versão instalada, o manifesto e o verificador originais da
tag, e recusa qualquer outra diferença. Se o Python coincidir byte a byte com
o ficheiro LF da tag, corrige apenas o seu hash conhecido no manifesto.
Guarda o manifesto original em storage/app/core-updates e valida todo o Core.
Não altera código PHP/Python, versões, composer.json, lock, .env ou dados. Não corre
Composer. Não é necessário se a instalação já for íntegra. Elimina-se depois
de concluir e o acesso expira em 48 horas. Core 3.1.9 passa a distribuir LF.
TEXT);
}finally{$zip->close();}
echo "Reparação limitada de integridade preparada; nenhum site foi alterado.\n";
