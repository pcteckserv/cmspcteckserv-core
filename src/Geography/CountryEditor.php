<?php

namespace Pcteckserv\CmsCore\Geography;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CountryEditor
{
    public function __construct(private readonly CountryCatalog $catalog)
    {
    }

    public function save(string $country, array $input): void
    {
        DB::transaction(function () use ($country, $input): void {
            $row = DB::table('cms_geography')->where('country', $country)->lockForUpdate()->first();
            abort_unless($row, 404);
            if ((int) $row->revision !== (int) $input['_revision']) {
                throw ValidationException::withMessages(['_revision' => 'Este país foi alterado noutra sessão. Recarregue antes de guardar.']);
            }
            $previous = array_column(json_decode($row->regions, true, 32, JSON_THROW_ON_ERROR), null, 'code');
            $regions = [];
            foreach ($input['regions'] as $region) {
                $code = $region['code'] ?? '';
                if ($code !== '' && !isset($previous[$code])) {
                    throw ValidationException::withMessages(['regions' => 'Não altere os identificadores das regiões existentes.']);
                }
                $code = $code ?: $country.'-CUSTOM-'.strtoupper((string) Str::uuid());
                $regions[$code] = [
                    'code' => $code, 'name' => trim($region['name']),
                    'aliases' => array_values(array_unique([...($previous[$code]['aliases'] ?? []), ...(isset($previous[$code]) ? [$previous[$code]['name']] : []), trim($region['name'])])),
                ];
            }
            if (array_diff_key($previous, $regions)) {
                throw ValidationException::withMessages(['regions' => 'Preserve as regiões existentes para manter as regras e moradas compatíveis.']);
            }
            DB::table('cms_geography')->where('country', $country)->update([
                'name' => trim($input['name']), 'regions' => json_encode(array_values($regions), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'revision' => $row->revision + 1, 'updated_at' => now(),
            ]);
        });
        $this->catalog->forget();
    }
}
