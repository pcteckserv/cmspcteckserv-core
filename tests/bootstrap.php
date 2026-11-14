<?php

$coreRoot = dirname(__DIR__);
$applicationRoot = getenv('CMS_CORE_TEST_APP') ?: dirname($coreRoot).'/cmspcteckserv-starter';
$configCache = sys_get_temp_dir().'/cms-core-tests-'.bin2hex(random_bytes(8)).'.php';
putenv('APP_CONFIG_CACHE='.$configCache);
$_ENV['APP_CONFIG_CACHE'] = $configCache;

if (! is_file($applicationRoot.'/vendor/autoload.php')) {
    throw new RuntimeException('Defina CMS_CORE_TEST_APP para uma aplicação Laravel com as dependências de teste instaladas.');
}

require_once $applicationRoot.'/vendor/autoload.php';

spl_autoload_register(static function (string $class) use ($coreRoot): void {
    $prefix = 'Pcteckserv\\CmsCore\\';
    if (str_starts_with($class, $prefix)) {
        $path = $coreRoot.'/src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
}, true, true);

putenv('APP_BASE_PATH='.$applicationRoot);
$_ENV['APP_BASE_PATH'] = $applicationRoot;
require_once __DIR__.'/TestCase.php';
