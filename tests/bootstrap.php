<?php

$coreRoot = dirname(__DIR__);
$applicationRoot = getenv('CMS_CORE_TEST_APP') ?: dirname($coreRoot).'/cmspcteckserv-starter';
$configCache = 'bootstrap/cache/core-tests-'.bin2hex(random_bytes(8)).'.php';
putenv('APP_CONFIG_CACHE='.$configCache);
$_ENV['APP_CONFIG_CACHE'] = $configCache;

// Opt-in apenas para uma base de dados descartável, nunca para a base de dados do site.
if ($database = getenv('CMS_CORE_TEST_MYSQL_DATABASE')) {
    if (!preg_match('/^cms_core_test_[a-z0-9_]+$/D', $database)) throw new RuntimeException('Nome da base de dados de testes inválido.');
    foreach (['DB_CONNECTION'=>'mysql','DB_DATABASE'=>$database,'DB_HOST'=>'127.0.0.1','DB_PORT'=>getenv('CMS_CORE_TEST_MYSQL_PORT') ?: '3306','DB_USERNAME'=>getenv('CMS_CORE_TEST_MYSQL_USER') ?: 'root','DB_PASSWORD'=>getenv('CMS_CORE_TEST_MYSQL_PASSWORD') ?: '','DB_URL'=>''] as $key=>$value) {
        putenv($key.'='.$value); $_ENV[$key]=$value; $_SERVER[$key]=$value;
    }
}
register_shutdown_function(static function () use ($applicationRoot,$configCache): void { @unlink($applicationRoot.'/'.$configCache); });

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
