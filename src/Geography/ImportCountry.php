<?php

namespace Pcteckserv\CmsCore\Geography;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ImportCountry
{
    public const URL = 'https://countriesnow.space/api/v0.1/countries/states';

    // Territórios ISO ausentes da lista de subdivisões da API v0.1.
    private const WITHOUT_PROVIDER_REGIONS = ['AS', 'BQ', 'CW', 'GF', 'SX'];

    public function __construct(private readonly CountryCatalog $catalog)
    {
    }

    public function import(string $country): void
    {
        $this->persist($this->prepare([$country]));
    }

    public function prepare(array $countries): array
    {
        Validator::make(['countries' => $countries], [
            'countries' => ['array', 'list', 'max:249'],
            'countries.*' => ['required', 'string', 'distinct', Rule::in(array_keys($this->catalog->countries()))],
        ])->validate();
        if (!$this->catalog->installed()) {
            throw ValidationException::withMessages(['countries' => 'Execute a atualização da base de dados do CMS antes de importar regiões.']);
        }
        if ($countries === []) {
            return [];
        }
        try {
            $response = Http::acceptJson()->connectTimeout(5)->timeout(20)->withOptions([
                'allow_redirects' => false,
                'on_headers' => static function ($response): void {
                    if ((int) $response->getHeaderLine('Content-Length') > 4_194_304) {
                        throw new \RuntimeException('Resposta geográfica demasiado grande.');
                    }
                },
            ])->get(self::URL)->throw();
            if (strlen($response->body()) > 4_194_304) {
                throw new \RuntimeException('Resposta geográfica demasiado grande.');
            }
            $payload = $response->json();
            if (!is_array($payload) || ($payload['error'] ?? null) !== false || !is_array($payload['data'] ?? null)) {
                throw new \RuntimeException('Resposta geográfica inválida.');
            }
            $prepared = [];
            foreach ($countries as $country) {
                $rows = array_values(array_filter($payload['data'], fn ($row) => is_array($row) && ($row['iso2'] ?? null) === $country));
                if ($rows === [] && in_array($country, self::WITHOUT_PROVIDER_REGIONS, true)) {
                    $prepared[$country] = ['regions' => [], 'source' => 'local'];
                    continue;
                }
                if (count($rows) !== 1) {
                    throw new \RuntimeException('País ausente ou repetido na resposta.');
                }
                $data = Validator::make($rows[0], [
                    'states' => ['present', 'array', 'max:1000'],
                    'states.*.name' => ['required', 'string', 'max:80', 'regex:/^[^\x00-\x1F\x7F<>]+$/u'],
                    'states.*.state_code' => ['required', 'string', 'max:16', 'regex:/^[A-Z0-9-]+$/D', 'distinct:strict'],
                ])->validate();
                $regions = [];
                foreach ($data['states'] as $state) {
                    $code = str_starts_with($state['state_code'], $country.'-') ? $state['state_code'] : $country.'-'.$state['state_code'];
                    if (isset($regions[$code])) {
                        throw new \RuntimeException('Identificador de região repetido.');
                    }
                    $name = $country === 'PT' ? match ($code) {
                        'PT-01' => 'Aveiro', 'PT-02' => 'Beja', 'PT-03' => 'Braga', 'PT-04' => 'Bragança',
                        'PT-05' => 'Castelo Branco', 'PT-06' => 'Coimbra', 'PT-07' => 'Évora', 'PT-08' => 'Faro',
                        'PT-09' => 'Guarda', 'PT-10' => 'Leiria', 'PT-11' => 'Lisboa', 'PT-12' => 'Portalegre',
                        'PT-13' => 'Porto', 'PT-14' => 'Santarém', 'PT-15' => 'Setúbal', 'PT-16' => 'Viana do Castelo',
                        'PT-17' => 'Vila Real', 'PT-18' => 'Viseu', 'PT-20' => 'Açores', 'PT-30' => 'Madeira',
                        default => $state['name'],
                    } : $state['name'];
                    $regions[$code] = ['code' => $code, 'name' => $name, 'aliases' => array_values(array_unique([$state['name'], $state['state_code'], $name]))];
                }
                $prepared[$country] = ['regions' => $regions, 'source' => self::URL];
            }
            return $prepared;
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('Importação geográfica indisponível.', ['exception' => $exception::class]);
            throw ValidationException::withMessages(['countries' => 'Não foi possível importar as regiões. Os dados anteriores foram preservados. Tente novamente mais tarde.']);
        }

    }

    public function persist(array $prepared): void
    {
        DB::transaction(function () use ($prepared): void {
            foreach ($prepared as $country => $entry) {
                $regions = $entry['regions'];
                DB::table('cms_geography')->insertOrIgnore([
                    'country' => $country, 'name' => $this->catalog->countries()[$country], 'regions' => '[]', 'revision' => 0, 'source' => $entry['source'], 'created_at' => now(), 'updated_at' => now(),
                ]);
                $previous = DB::table('cms_geography')->where('country', $country)->lockForUpdate()->first();
                $stored = json_decode($previous->regions, true, 32, JSON_THROW_ON_ERROR);
                foreach ($stored as $region) {
                    $code = $region['code'];
                    // Atualizações preservam identificadores e aliases usados em regras e moradas.
                    if (isset($regions[$code])) {
                        $regions[$code]['name'] = $region['name'];
                        $regions[$code]['aliases'] = array_values(array_unique([...$region['aliases'], $region['name'], ...$regions[$code]['aliases']]));
                    } else {
                        $regions[$code] = $region;
                    }
                }
                DB::table('cms_geography')->where('country', $country)->update([
                    'regions' => json_encode(array_values($regions), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'source' => $entry['source'], 'revision' => $previous->revision + 1, 'updated_at' => now(),
                ]);
            }
        });
        $this->catalog->forget();
    }
}
