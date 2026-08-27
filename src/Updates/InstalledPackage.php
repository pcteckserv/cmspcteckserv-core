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
    ) {
    }

    public function hasUpdate(): bool
    {
        if ($this->installedVersion === null || $this->availableVersion === null) {
            return false;
        }

        return version_compare($this->normalizeVersion($this->availableVersion), $this->normalizeVersion($this->installedVersion), '>');
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
