<?php

namespace Pcteckserv\CmsCore\Plugins;

class PluginInstallResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly string $message,
    ) {
    }
}
