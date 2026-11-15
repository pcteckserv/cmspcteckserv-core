<?php

$root = dirname(__DIR__);
require_once $root.'/src/Support/ReleaseIntegrity.php';
$version = $argv[1] ?? '';
if (!preg_match('/^\d+\.\d+\.\d+$/D', $version)) {
    fwrite(STDERR, "Indique a versão estável da distribuição.\n"); exit(1);
}
$files = ['composer.json'=>\Pcteckserv\CmsCore\Support\ReleaseIntegrity::hash($root.'/composer.json')];
foreach (['src','config','database','resources','routes','tools'] as $folder) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$folder, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) if ($file->isFile()) {
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        // Git normaliza ficheiros de texto; os hashes devem coincidir em Windows e Linux.
        $files[$relative] = \Pcteckserv\CmsCore\Support\ReleaseIntegrity::hash($file->getPathname());
    }
}
ksort($files);
file_put_contents($root.'/core-release.json', json_encode(['schema'=>1,'version'=>$version,'files'=>$files], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n");
echo 'Manifesto criado para '.count($files)." ficheiros.\n";
