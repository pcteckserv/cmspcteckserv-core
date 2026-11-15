<?php

$root = $argv[1] ?? '';
$target = $argv[2] ?? '';
$autoload = $argv[3] ?? dirname(__DIR__, 3).'/autoload.php';
if ($root === '' || $target === '' || !is_file($autoload)) {
    fwrite(STDERR, "Utilização: php audit-installation.php <projeto> <versão-Core> [vendor/autoload.php]\n"); exit(2);
}
require_once $autoload;
require_once dirname(__DIR__).'/src/Support/ReleaseIntegrity.php';
require_once dirname(__DIR__).'/src/Support/CompatibilityInspector.php';
$report = (new Pcteckserv\CmsCore\Support\CompatibilityInspector())->inspect($root, $target);
echo json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
exit($report['compatible'] ? 0 : 1);
