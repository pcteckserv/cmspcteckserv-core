<?php

namespace Pcteckserv\CmsCore\Plugins\DTOs;

final readonly class PluginDefinition
{
    public function __construct(
        public string $slug,
        public string $name,
        public string $package,
        public string $label,
        public ?string $description,
        public ?string $provider,
        public ?string $repository,
    ) {
    }
}
