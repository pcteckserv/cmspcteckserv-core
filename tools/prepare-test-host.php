<?php

// Gera uma aplicação de teste independente, sem ler ambientes ou dados dos sites.
$destination = $argv[1] ?? '';
if ($destination === '' || file_exists($destination)) {
    fwrite(STDERR, "Indique uma pasta nova para a aplicação de teste.\n");
    exit(1);
}
$root = dirname(__DIR__);
$release = is_file($root.'/core-release.json') ? json_decode(file_get_contents($root.'/core-release.json'), true, flags: JSON_THROW_ON_ERROR) : [];
$version = $argv[2] ?? $release['version'] ?? '3.1.7';
if (!is_string($version) || !preg_match('/^\d+\.\d+\.\d+$/D',$version)) throw new RuntimeException('Versão de teste inválida.');
$template = $root.'/tests/Host';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($template, FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
    if (!$file->isFile()) continue;
    $target = $destination.'/'.substr($file->getPathname(), strlen($template) + 1);
    if (!is_dir(dirname($target))) mkdir(dirname($target), 0755, true);
    if (!copy($file->getPathname(), $target)) throw new RuntimeException('Não foi possível preparar a aplicação de teste.');
}
$manifest = json_decode(file_get_contents($destination.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
$manifest['repositories'] = [['type'=>'path', 'url'=>str_replace('\\', '/', $root), 'options'=>['symlink'=>false, 'versions'=>['pcteckserv/cms-core'=>$version]]]];
file_put_contents($destination.'/composer.json', json_encode($manifest, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
foreach (['bootstrap/cache','config','public','database/migrations','storage/framework/cache/data','storage/framework/views','storage/framework/sessions','storage/logs','storage/app/private'] as $folder) if (!is_dir($destination.'/'.$folder)) mkdir($destination.'/'.$folder,0755,true);
echo "Aplicação de teste preparada.\n";
