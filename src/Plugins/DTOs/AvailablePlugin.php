<?php

namespace Pcteckserv\CmsCore\Plugins\DTOs;

class AvailablePlugin
{
    public function __construct(
        public readonly string $slug,
        public readonly string $directory,
        public readonly string $package,
        public readonly string $label,
        public readonly ?string $description,
        public readonly ?string $provider,
        public readonly ?string $versionConstraint,
        public readonly string $repositoryPath,
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function installData(): array
    {
        return [
            'package' => $this->package,
            'version_constraint' => $this->versionConstraint,
            'slug' => $this->slug,
            'label' => $this->label,
            'description' => $this->description,
            'provider' => $this->provider,
            'repository_type' => 'path',
            'repository_url' => $this->repositoryPath,
        ];
    }
}
