<?php

require_once dirname(__DIR__).'/src/Support/ReleaseIntegrity.php';
$result = (new Pcteckserv\CmsCore\Support\ReleaseIntegrity())->verify($argv[1] ?? dirname(__DIR__), $argv[2] ?? null);
echo json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n";
exit($result['valid'] ? 0 : 1);
