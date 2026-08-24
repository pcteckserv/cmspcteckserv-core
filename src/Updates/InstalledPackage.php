<?php

namespace Pcteckserv\CmsCore\Updates;

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

        return version_compare($this->availableVersion, $this->installedVersion, '>');
    }
}
