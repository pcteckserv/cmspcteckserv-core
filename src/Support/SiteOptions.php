<?php

namespace Pcteckserv\CmsCore\Support;

use Illuminate\Support\Facades\Schema;
use Pcteckserv\CmsCore\Models\SiteOption;

class SiteOptions
{
    public function all(): array
    {
        $defaults = $this->defaults();

        if (! Schema::hasTable('cms_site_options')) {
            return $defaults;
        }

        $stored = SiteOption::query()
            ->whereIn('key', array_keys($defaults))
            ->pluck('value', 'key')
            ->all();

        return array_replace($defaults, array_filter(
            $stored,
            static fn ($value): bool => $value !== null && $value !== ''
        ));
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        return $this->all()[$key] ?? $fallback;
    }

    public function setMany(array $options): void
    {
        foreach ($options as $key => $value) {
            SiteOption::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }

    public function defaults(): array
    {
        return config('cms-core.site_options', []);
    }
}
