<?php

namespace Pcteckserv\CmsCore\Updates;

use Carbon\CarbonImmutable;

final readonly class InstalledPackage
{
    public function __construct(
        public string $name,
        public ?string $installedVersion,
        public ?string $availableVersion,
        public string $channel,
        public ?string $checkedAt,
        public ?string $lastAppliedRelease = null,
    ) {
    }

    public function hasUpdate(): bool
    {
        if (StarterPackage::is($this->name) && $this->installedVersion === null && $this->availableVersion !== null) {
            return true;
        }

        if ($this->installedVersion === null || $this->availableVersion === null) {
            return false;
        }

        $comparisonVersion = str_starts_with($this->installedVersion, 'dev-') && $this->lastAppliedRelease !== null
            ? $this->lastAppliedRelease
            : $this->installedVersion;

        return version_compare($this->normalizeVersion($this->availableVersion), $this->normalizeVersion($comparisonVersion), '>');
    }

    public function formattedCheckedAt(): string
    {
        if ($this->checkedAt === null || trim($this->checkedAt) === '') {
            return '-';
        }

        return CarbonImmutable::parse($this->checkedAt, 'UTC')
            ->timezone(config('cms-core.admin_timezone', 'Europe/Lisbon'))
            ->format('d/m/Y H:i:s');
    }

    private function normalizeVersion(string $version): string
    {
        return ltrim($version, 'v');
    }
}
