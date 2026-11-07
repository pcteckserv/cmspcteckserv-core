<?php

namespace Pcteckserv\CmsCore\Geography;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;


final class CountryCatalog
{
    private ?array $imported = null;

    private array $regionLookups = [];

    public function installed(): bool
    {
        return Schema::hasTable('cms_geography');
    }

    public function countries(): array
    {
        $countries = CountryNames::all();
        foreach ($this->imported() as $code => $row) {
            $countries[$code] = $row['name'];
        }
        asort($countries, SORT_NATURAL | SORT_FLAG_CASE);

        return $countries;
    }

    public function imported(): array
    {
        if ($this->imported === null) {
            $this->imported = [];
            if ($this->installed()) {
                foreach (DB::table('cms_geography')->orderBy('country')->get() as $row) {
                    $this->imported[$row->country] = [
                        'name' => $row->name,
                        'regions' => json_decode($row->regions, true, 32, JSON_THROW_ON_ERROR),
                        'updated_at' => $row->updated_at,
                        'source' => $row->source,
                        'revision' => (int) $row->revision,
                    ];
                }
            }
        }

        return $this->imported;
    }

    public function forget(): void
    {
        $this->imported = null;
        $this->regionLookups = [];
    }

    public function regions(?string $country): array
    {
        return $this->imported()[strtoupper($country ?? '')]['regions'] ?? [];
    }

    public function regionCode(?string $country, ?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $needle = $this->fold($value);
        $lookup = $this->regionLookup($country);
        if (isset($lookup['codes'][$needle])) {
            return $lookup['codes'][$needle];
        }
        $matches = $lookup['aliases'][$needle] ?? [];

        // Não adivinhar quando um nome corresponde a mais de uma subdivisão.
        return count($matches) === 1 ? array_key_first($matches) : null;
    }

    public function regionAmbiguous(?string $country, ?string $value): bool
    {
        $needle = $this->fold($value ?? '');
        $lookup = $this->regionLookup($country);

        return !isset($lookup['codes'][$needle]) && count($lookup['aliases'][$needle] ?? []) > 1;
    }

    private function regionLookup(?string $country): array
    {
        $country = strtoupper($country ?? '');
        if (!isset($this->regionLookups[$country])) {
            $lookup = ['codes' => [], 'aliases' => []];
            foreach ($this->regions($country) as $region) {
                $lookup['codes'][$this->fold($region['code'])] = $region['code'];
                foreach ($region['aliases'] as $alias) {
                    $lookup['aliases'][$this->fold($alias)][$region['code']] = true;
                }
            }
            $this->regionLookups[$country] = $lookup;
        }

        return $this->regionLookups[$country];
    }

    public function regionsMatch(?string $country, string $left, string $right): bool
    {
        if (mb_strtoupper(trim($left)) === mb_strtoupper(trim($right))) {
            return true;
        }
        $code = $this->regionCode($country, $left);

        return $code !== null && $code === $this->regionCode($country, $right);
    }

    public function regionOptions(?string $country, string $current = ''): array
    {
        $options = [];
        foreach ($this->regions($country) as $region) {
            $options[$region['code']] = $region['name'];
        }
        asort($options, SORT_NATURAL | SORT_FLAG_CASE);
        if ($current !== '' && !isset($options[$current])) {
            $code = $this->regionCode($country, $current);
            $options = [$current => $options[$code] ?? $current.' (valor anterior)'] + $options;
        }

        return $options;
    }

    public function describe(array $location): string
    {
        $country = $location['country'] ?? '';
        $region = $location['region'] ?? '';
        $label = $this->countries()[$country] ?? $country;
        $options = $this->regionOptions($country, $region);

        return implode(' / ', array_filter([$label, $options[$region] ?? $region, $location['postal_code'] ?? ''], fn ($value) => $value !== ''));
    }

    public function payload(): array
    {
        $countries = $this->countries();

        return ['countries' => $countries, 'regions' => array_map(fn ($row) => $row['regions'], array_intersect_key($this->imported(), $countries))];
    }

    private function fold(string $value): string
    {
        $value = preg_replace('/\p{Mn}/u', '', trim($value));

        return mb_strtoupper(preg_replace_callback('/\p{Latin}+/u', static fn ($match) => Str::ascii($match[0]), $value));
    }
}
