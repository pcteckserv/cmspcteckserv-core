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
        public readonly ?string $version = null,
        public readonly ?string $coreConstraint = null,
    ) {
    }

    public function compatible(): bool
    {
        try {
            return ! $this->coreConstraint || \Composer\Semver\Semver::satisfies(\Composer\InstalledVersions::getPrettyVersion('pcteckserv/cms-core'), $this->coreConstraint);
        } catch (\UnexpectedValueException) {
            return false;
        }
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
            'version' => $this->version,
        ];
    }
}
